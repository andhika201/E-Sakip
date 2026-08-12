<?php

namespace App\Services;

use Config\Database;

/**
 * Menentukan OPD pemilik sebuah blok unit Lampiran 8.
 *
 * URUTAN PRIORITAS (tidak boleh diacak):
 *   1. exact  — nama unit sama persis dengan master OPD setelah normalisasi
 *   2. alias  — pasangan nama/kode unit yang pernah dipetakan manual
 *   3. parent_rule — unit bawahan mengikuti induknya (Puskesmas -> Dinkes, dst.)
 *   4. fuzzy  — HANYA SARAN, tidak pernah auto-commit
 *   5. manual — dipetakan pengguna lewat halaman mapping
 *
 * Nama OPD yang dipakai aplikasi SELALU diambil dari master OPD, tidak pernah
 * dari Excel. Bila tidak ada yang cocok, hasilnya `pending_mapping` — TIDAK
 * pernah jatuh ke OPD default apa pun.
 */
class OpdResolver
{
    /** @var array<string, int> nama_normal => opd_id */
    private array $masterByNama = [];

    /** @var array<int, array{id:int,nama:string,normal:string}> */
    private array $master = [];

    /** @var array<string, int> nama_normal => opd_id */
    private array $aliasByNama = [];

    /** @var array<string, int> kode_unit => opd_id */
    private array $aliasByKode = [];

    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::connect();
        $this->muatMaster();
        $this->muatAlias();
    }

    /** Normalisasi nama unit: huruf besar, tanpa tanda baca, spasi tunggal. */
    public static function normalNama(string $nama): string
    {
        $n = mb_strtoupper(trim($nama), 'UTF-8');
        // Samakan penulisan yang sering berbeda antar dokumen.
        $n = str_replace(['`', "'", '"', '.', ',', '/', '\\', '-', '(', ')'], ' ', $n);
        $n = preg_replace('/\s+/u', ' ', $n);

        return trim((string) $n);
    }

    private function muatMaster(): void
    {
        $rows = $this->db->table('opd')->select('id, nama_opd')->get()->getResultArray();
        foreach ($rows as $r) {
            $id     = (int) $r['id'];
            $normal = self::normalNama((string) $r['nama_opd']);
            $this->master[$id] = ['id' => $id, 'nama' => (string) $r['nama_opd'], 'normal' => $normal];

            // Bila ada duplikat nama di master, pakai id terkecil supaya
            // hasilnya deterministik antar-import.
            if (!isset($this->masterByNama[$normal]) || $id < $this->masterByNama[$normal]) {
                $this->masterByNama[$normal] = $id;
            }
        }
    }

    private function muatAlias(): void
    {
        if (!$this->db->tableExists('opd_import_alias')) {
            return;
        }

        $rows = $this->db->table('opd_import_alias')
            ->select('kode_unit, nama_unit_normal, opd_id')
            ->get()->getResultArray();

        foreach ($rows as $r) {
            $opdId = (int) $r['opd_id'];
            if (!isset($this->master[$opdId])) {
                continue; // alias menunjuk OPD yang sudah dihapus
            }
            $this->aliasByNama[(string) $r['nama_unit_normal']] = $opdId;
            if (!empty($r['kode_unit'])) {
                $this->aliasByKode[(string) $r['kode_unit']] = $opdId;
            }
        }
    }

    /** Nama canonical dari master OPD (bukan dari Excel). */
    public function namaOpd(?int $opdId): string
    {
        return $opdId && isset($this->master[$opdId]) ? $this->master[$opdId]['nama'] : '';
    }

    /**
     * Tentukan OPD untuk satu unit.
     *
     * @return array{opd_id: int|null, method: string|null, status: string,
     *               saran_opd_id: int|null, saran_skor: float|null, alasan: string}
     */
    public function resolve(string $namaUnit, ?string $kodeUnit = null): array
    {
        $hasil = [
            'opd_id'       => null,
            'method'       => null,
            'status'       => 'pending_mapping',
            'saran_opd_id' => null,
            'saran_skor'   => null,
            'alasan'       => '',
        ];

        $normal = self::normalNama($namaUnit);
        if ($normal === '') {
            $hasil['alasan'] = 'Nama unit kosong.';

            return $hasil;
        }

        // 1) EXACT — nama persis sama dengan master OPD.
        if (isset($this->masterByNama[$normal])) {
            return [
                'opd_id' => $this->masterByNama[$normal], 'method' => 'exact', 'status' => 'resolved',
                'saran_opd_id' => null, 'saran_skor' => null, 'alasan' => 'Cocok persis dengan master OPD.',
            ];
        }

        // 2) ALIAS — hasil mapping manual sebelumnya (kode dulu, baru nama).
        if ($kodeUnit !== null && $kodeUnit !== '' && isset($this->aliasByKode[$kodeUnit])) {
            return [
                'opd_id' => $this->aliasByKode[$kodeUnit], 'method' => 'alias', 'status' => 'resolved',
                'saran_opd_id' => null, 'saran_skor' => null, 'alasan' => 'Alias kode unit tersimpan.',
            ];
        }
        if (isset($this->aliasByNama[$normal])) {
            return [
                'opd_id' => $this->aliasByNama[$normal], 'method' => 'alias', 'status' => 'resolved',
                'saran_opd_id' => null, 'saran_skor' => null, 'alasan' => 'Alias nama unit tersimpan.',
            ];
        }

        // 3) RULE UNIT BAWAHAN.
        $rule = $this->rule($normal);
        if ($rule !== null) {
            return [
                'opd_id' => $rule['opd_id'], 'method' => 'parent_rule', 'status' => 'resolved',
                'saran_opd_id' => null, 'saran_skor' => null, 'alasan' => $rule['alasan'],
            ];
        }

        // 4) FUZZY — hanya saran, TIDAK pernah dipakai otomatis.
        $saran = $this->fuzzy($normal);
        if ($saran !== null) {
            $hasil['saran_opd_id'] = $saran['opd_id'];
            $hasil['saran_skor']   = $saran['skor'];
            $hasil['alasan']       = 'Perlu konfirmasi. Saran: ' . $this->namaOpd($saran['opd_id'])
                . ' (' . number_format($saran['skor'], 0) . '% mirip).';

            return $hasil;
        }

        $hasil['alasan'] = 'Tidak ada padanan di master OPD.';

        return $hasil;
    }

    /**
     * Aturan unit bawahan -> OPD induk.
     *
     * @return array{opd_id:int, alasan:string}|null
     */
    private function rule(string $normal): ?array
    {
        // Puskesmas -> Dinas Kesehatan
        if (preg_match('/^(UPT[D]?\s+)?PUSKESMAS\b/u', $normal) === 1) {
            $id = $this->cariMasterAwalan('DINAS KESEHATAN');
            if ($id) {
                return ['opd_id' => $id, 'alasan' => 'Aturan: Puskesmas mengikuti Dinas Kesehatan.'];
            }
        }

        // "Bagian ... Sekretariat Daerah" atau "Bagian ..." -> Sekretariat Daerah
        if (preg_match('/^BAGIAN\b/u', $normal) === 1
            || preg_match('/\bSEKRETARIAT DAERAH$/u', $normal) === 1) {
            $id = $this->cariMasterAwalan('SEKRETARIAT DAERAH');
            if ($id) {
                return ['opd_id' => $id, 'alasan' => 'Aturan: Bagian mengikuti Sekretariat Daerah.'];
            }
        }

        // RSUD -> master RSUD bila ada. Nomenklatur master biasanya
        // "RUMAH SAKIT UMUM DAERAH ...", sedangkan SIPD menulis "RSUD ...".
        if (preg_match('/^(RSUD|RUMAH SAKIT UMUM DAERAH)\b/u', $normal) === 1) {
            $id = $this->cariMasterAwalan('RUMAH SAKIT UMUM DAERAH') ?: $this->cariMasterAwalan('RSUD');
            if ($id) {
                return ['opd_id' => $id, 'alasan' => 'Aturan: RSUD mengikuti master RSUD.'];
            }
        }

        // Kelurahan TETAP ke master kelurahannya masing-masing bila tersedia.
        // Tidak pernah dinaikkan ke kecamatan — hanya dicocokkan lebih longgar
        // (mis. "KELURAHAN PRINGSEWU TIMUR" vs master yang sama).
        if (preg_match('/^KELURAHAN\b/u', $normal) === 1) {
            $id = $this->cariMasterSamaTanpaSpasi($normal);
            if ($id) {
                return ['opd_id' => $id, 'alasan' => 'Aturan: Kelurahan dipetakan ke master kelurahannya sendiri.'];
            }
        }

        return null;
    }

    /** OPD pertama (id terkecil) yang namanya diawali teks tertentu. */
    private function cariMasterAwalan(string $awalan): ?int
    {
        $kandidat = null;
        foreach ($this->master as $m) {
            if (str_starts_with($m['normal'], $awalan)) {
                if ($kandidat === null || $m['id'] < $kandidat) {
                    $kandidat = $m['id'];
                }
            }
        }

        return $kandidat;
    }

    /** Cocok setelah spasi dihilangkan (mis. "GADING REJO" vs "GADINGREJO"). */
    private function cariMasterSamaTanpaSpasi(string $normal): ?int
    {
        $rapat    = str_replace(' ', '', $normal);
        $kandidat = null;
        foreach ($this->master as $m) {
            if (str_replace(' ', '', $m['normal']) === $rapat) {
                if ($kandidat === null || $m['id'] < $kandidat) {
                    $kandidat = $m['id'];
                }
            }
        }

        return $kandidat;
    }

    /**
     * Saran terdekat memakai similar_text. Ambang 75% dipilih supaya saran
     * yang muncul benar-benar mirip; berapa pun skornya hasil ini TIDAK
     * pernah dipakai otomatis.
     *
     * @return array{opd_id:int, skor:float}|null
     */
    private function fuzzy(string $normal): ?array
    {
        $terbaik = null;
        $skorMax = 0.0;

        foreach ($this->master as $m) {
            $persen = 0.0;
            similar_text($normal, $m['normal'], $persen);
            if ($persen > $skorMax) {
                $skorMax = $persen;
                $terbaik = $m['id'];
            }
        }

        return ($terbaik !== null && $skorMax >= 75.0)
            ? ['opd_id' => $terbaik, 'skor' => round($skorMax, 2)]
            : null;
    }

    /** Simpan alias supaya mapping manual dipakai ulang pada import berikutnya. */
    public function simpanAlias(string $namaUnit, ?string $kodeUnit, int $opdId, ?int $userId): void
    {
        if (!$this->db->tableExists('opd_import_alias') || $opdId <= 0) {
            return;
        }

        $normal = self::normalNama($namaUnit);
        if ($normal === '') {
            return;
        }

        $tabel = $this->db->table('opd_import_alias');
        $ada   = $tabel->where('nama_unit_normal', $normal)->get()->getRowArray();

        $isi = [
            'kode_unit'      => ($kodeUnit !== null && $kodeUnit !== '') ? $kodeUnit : null,
            'nama_unit_asli' => $namaUnit,
            'opd_id'         => $opdId,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($ada) {
            $this->db->table('opd_import_alias')->where('id', (int) $ada['id'])->update($isi);
        } else {
            $this->db->table('opd_import_alias')->insert($isi + [
                'nama_unit_normal' => $normal,
                'created_by'       => $userId,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        $this->aliasByNama[$normal] = $opdId;
        if (!empty($isi['kode_unit'])) {
            $this->aliasByKode[$isi['kode_unit']] = $opdId;
        }
    }
}

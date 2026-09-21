<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Pengesahan LAKIP per tahun + permintaan pembukaan bila ada typo.
 *
 * =====================================================================
 * MENGAPA TANPA VERSI/SNAPSHOT
 *
 * LAKIP di sini bukan dokumen berjenjang seperti IKU — ia laporan tahunan
 * yang angkanya tunggal. Yang dibutuhkan bukan salinan beku, melainkan
 * KEADAAN: sudah disahkan atau belum, dan bila perlu diperbaiki, siapa yang
 * meminta serta siapa yang mengizinkan.
 *
 * Karena itu tidak ada tabel salinan. `lakip` tetap satu-satunya tempat
 * angka. Yang ditambahkan hanya kunci dan riwayat izin membukanya.
 *
 * =====================================================================
 * ALURNYA
 *
 *   OPD  : sahkan()          -> tahun terkunci
 *   OPD  : ajukanPembukaan() -> permintaan menunggu (alasan WAJIB)
 *   Kab  : setujui()         -> tahun dibuka, OPD boleh menyunting
 *   Kab  : tolak()           -> tetap terkunci, alasannya tercatat
 *   OPD  : sahkan()          -> mengunci lagi sesudah diperbaiki
 *
 * Riwayat permintaan TIDAK PERNAH dihapus — termasuk yang ditolak. Itulah
 * yang membuat alur ini bisa dipertanggungjawabkan, bukan sekadar tombol
 * buka-kunci.
 */
class LakipPengesahanModel extends Model
{
    protected $table         = 'lakip_pengesahan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public const STATUS_DISAHKAN = 'disahkan';
    public const STATUS_DIBUKA   = 'dibuka';

    public const MINTA_MENUNGGU  = 'menunggu';
    public const MINTA_DISETUJUI = 'disetujui';
    public const MINTA_DITOLAK   = 'ditolak';
    public const MINTA_DITARIK   = 'ditarik';

    /** Fitur ini mati dengan sendirinya bila migrasinya belum dijalankan. */
    public function siap(): bool
    {
        return $this->db->tableExists('lakip_pengesahan')
            && $this->db->tableExists('lakip_buka_permintaan');
    }

    /**
     * Lingkup kabupaten disimpan sebagai opd_id = 0, mengikuti tabel `lakip`.
     *
     * @return array{mode: string, opd_id: int}
     */
    private function kunciLingkup(string $mode, ?int $opdId): array
    {
        return [
            'mode'   => $mode === 'kabupaten' ? 'kabupaten' : 'opd',
            'opd_id' => $mode === 'kabupaten' ? 0 : (int) $opdId,
        ];
    }

    /** @return array<string,mixed>|null keadaan pengesahan, null bila belum pernah disahkan */
    public function keadaan(int $tahun, string $mode, ?int $opdId): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        $k = $this->kunciLingkup($mode, $opdId);

        return $this->db->table('lakip_pengesahan')
            ->where('tahun', $tahun)
            ->where('mode', $k['mode'])
            ->where('opd_id', $k['opd_id'])
            ->get()->getRowArray();
    }

    /**
     * Inti penjaga: bolehkah angka LAKIP lingkup+tahun ini disunting?
     *
     * Dipanggil SEBELUM setiap tulis di controller. Sengaja mengembalikan
     * "tidak terkunci" saat fitur belum terpasang, supaya basis data lama
     * tidak mendadak membeku.
     */
    public function terkunci(int $tahun, string $mode, ?int $opdId): bool
    {
        $keadaan = $this->keadaan($tahun, $mode, $opdId);

        return $keadaan !== null && $keadaan['status'] === self::STATUS_DISAHKAN;
    }

    /**
     * Sahkan (atau sahkan ulang sesudah perbaikan).
     *
     * Menolak mengesahkan tahun yang belum punya satu pun angka: mengunci
     * halaman kosong hanya menyusahkan, tanpa melindungi apa pun.
     *
     * @return array{id: int, jumlah_realisasi: int, sahkan_ulang: bool}
     */
    public function sahkan(int $tahun, string $mode, ?int $opdId, ?int $userId, array $opsi = []): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Fitur pengesahan LAKIP belum terpasang di basis data ini.');
        }

        $k = $this->kunciLingkup($mode, $opdId);

        $verifikasi = $k['mode'] === 'kabupaten'
            ? $this->validasiKabupaten($tahun)
            : ['jumlah_realisasi' => $this->jumlahRealisasi($tahun, $k)];
        $isi = (int) $verifikasi['jumlah_realisasi'];

        if ($isi < 1) {
            throw new RuntimeException('Belum ada satu pun realisasi yang terisi untuk tahun ' . $tahun . '.');
        }

        $sekarang = date('Y-m-d H:i:s');
        $ada      = $this->keadaan($tahun, $mode, $opdId);

        $data = [
            'status'        => self::STATUS_DISAHKAN,
            'nomor'         => $opsi['nomor']   ?? ($ada['nomor']   ?? null),
            'catatan'       => $opsi['catatan'] ?? ($ada['catatan'] ?? null),
            'disahkan_oleh' => $userId,
            'disahkan_pada' => $sekarang,
            // Jejak pembukaan sebelumnya dibersihkan: kunci baru, keadaan baru.
            'dibuka_oleh'   => null,
            'dibuka_pada'   => null,
        ];

        // Kolom ini hadir bersama document-level binding. Guard menjaga
        // instalasi legacy yang migration-nya belum dijalankan.
        foreach (['source_type', 'source_version_id', 'lakip_dokumen_id'] as $kolom) {
            if ($this->db->fieldExists($kolom, 'lakip_pengesahan')) {
                $data[$kolom] = $verifikasi[$kolom] ?? null;
            }
        }

        $this->db->transStart();
        if ($ada) {
            $this->db->table('lakip_pengesahan')->where('id', $ada['id'])->update($data);
            $id = (int) $ada['id'];
        } else {
            $this->db->table('lakip_pengesahan')->insert($data + [
                'tahun'  => $tahun,
                'mode'   => $k['mode'],
                'opd_id' => $k['opd_id'],
            ]);
            $id = (int) $this->db->insertID();
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Pengesahan gagal disimpan; perubahan dibatalkan.');
        }

        return ['id' => $id, 'jumlah_realisasi' => $isi, 'sahkan_ulang' => $ada !== null];
    }

    private function jumlahRealisasi(int $tahun, array $k): int
    {
        return $this->db->table('lakip')
            ->where('tahun', $tahun)->where('mode', $k['mode'])->where('opd_id', $k['opd_id'])
            ->where('capaian_tahun_ini IS NOT NULL', null, false)
            ->where("capaian_tahun_ini <> ''", null, false)->countAllResults();
    }

    /** Validasi formal untuk LAKIP Kabupaten; legacy RPJMD tetap didukung. */
    private function validasiKabupaten(int $tahun): array
    {
        $kolom = 'id, source_type, source_version_id, source_entity_id, status, capaian_tahun_ini';
        if ($this->db->fieldExists('lakip_dokumen_id', 'lakip')) {
            $kolom .= ', lakip_dokumen_id';
        }

        $rows = $this->db->table('lakip')
            ->select($kolom)
            ->where('tahun', $tahun)->where('mode', 'kabupaten')->where('opd_id', 0)
            ->get()->getResultArray();

        if ($rows === []) {
            throw new RuntimeException('LAKIP Kabupaten belum memiliki baris untuk disahkan.');
        }

        $sumber = [];
        $entitas = [];
        $dokumenId = null;
        $isi = 0;
        foreach ($rows as $row) {
            $tipe = strtolower(trim((string) ($row['source_type'] ?? '')));
            if (! in_array($tipe, ['iku', 'rpjmd'], true)) {
                throw new RuntimeException('Setiap baris LAKIP Kabupaten harus memiliki sumber yang sah sebelum pengesahan.');
            }
            if (! in_array(strtolower(trim((string) ($row['status'] ?? ''))), ['selesai', 'siap'], true)) {
                throw new RuntimeException('Seluruh indikator LAKIP Kabupaten harus berstatus selesai sebelum pengesahan.');
            }
            if (($row['capaian_tahun_ini'] ?? '') === null || trim((string) $row['capaian_tahun_ini']) === '') {
                throw new RuntimeException('Seluruh indikator wajib memiliki capaian tahun ini sebelum pengesahan.');
            }
            $kunci = $tipe . ':' . (int) ($row['source_version_id'] ?? 0);
            $sumber[$kunci] = ['source_type' => $tipe, 'source_version_id' => (int) ($row['source_version_id'] ?? 0)];
            $entitas[(int) ($row['source_entity_id'] ?? 0)] = true;
            if (! empty($row['lakip_dokumen_id'])) {
                $dokumenId ??= (int) $row['lakip_dokumen_id'];
                if ($dokumenId !== (int) $row['lakip_dokumen_id']) {
                    throw new RuntimeException('LAKIP Kabupaten memiliki document binding campuran.');
                }
            }
            $isi++;
        }

        if (count($sumber) !== 1) {
            throw new RuntimeException('LAKIP Kabupaten memiliki source type atau source version campuran.');
        }
        $source = reset($sumber);

        if ($source['source_type'] === 'iku') {
            if ($source['source_version_id'] <= 0) {
                throw new RuntimeException('LAKIP bersumber IKU wajib memiliki source version sebelum pengesahan.');
            }
            $wajib = $this->db->table('iku_revisi_indikator')
                ->where('revisi_id', $source['source_version_id'])
                ->where('jenis_perubahan !=', 'dihentikan')->countAllResults();
            if ($wajib !== count($entitas)) {
                throw new RuntimeException('Jumlah indikator IKU yang lengkap belum sama dengan sumber acuan.');
            }
        } else {
            $wajib = $this->db->table('rpjmd_target')->where('tahun', $tahun)->countAllResults();
            if ($wajib !== count($entitas)) {
                throw new RuntimeException('Jumlah indikator RPJMD yang lengkap belum sama dengan sumber acuan.');
            }
        }

        return $source + ['lakip_dokumen_id' => $dokumenId, 'jumlah_realisasi' => $isi];
    }

    /** Permintaan pembukaan oleh OPD. Alasan WAJIB — itu inti pertanggungjawabannya. */
    public function ajukanPembukaan(int $tahun, string $mode, ?int $opdId, string $alasan, ?int $userId): int
    {
        $alasan  = trim($alasan);
        $keadaan = $this->keadaan($tahun, $mode, $opdId);

        if ($keadaan === null) {
            throw new RuntimeException('LAKIP tahun ' . $tahun . ' belum disahkan, jadi belum perlu dibuka.');
        }

        if ($keadaan['status'] !== self::STATUS_DISAHKAN) {
            throw new RuntimeException(
                'LAKIP tahun ' . $tahun . ' sedang dalam keadaan terbuka — silakan langsung perbaiki.'
            );
        }

        if ($alasan === '') {
            throw new RuntimeException('Alasan wajib diisi: sebutkan apa yang keliru dan mengapa perlu dibuka.');
        }

        if ($this->permintaanMenunggu((int) $keadaan['id']) !== null) {
            throw new RuntimeException('Sudah ada permintaan yang menunggu keputusan admin kabupaten.');
        }

        $this->db->table('lakip_buka_permintaan')->insert([
            'pengesahan_id' => (int) $keadaan['id'],
            'alasan'        => $alasan,
            'status'        => self::MINTA_MENUNGGU,
            'diminta_oleh'  => $userId,
            'diminta_pada'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    /** @return array<string,mixed>|null permintaan yang masih menunggu keputusan */
    public function permintaanMenunggu(int $pengesahanId): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        return $this->db->table('lakip_buka_permintaan')
            ->where('pengesahan_id', $pengesahanId)
            ->where('status', self::MINTA_MENUNGGU)
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
    }

    /** Riwayat lengkap permintaan pada satu lingkup+tahun, terbaru dulu. */
    public function riwayat(int $pengesahanId): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('lakip_buka_permintaan')
            ->where('pengesahan_id', $pengesahanId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();
    }

    /** Kotak masuk admin_kab: seluruh permintaan yang menunggu keputusan. */
    public function menungguKeputusan(): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('lakip_buka_permintaan p')
            ->select('p.*, g.tahun, g.mode, g.opd_id, o.nama_opd')
            ->join('lakip_pengesahan g', 'g.id = p.pengesahan_id')
            ->join('opd o', 'o.id = g.opd_id', 'left')
            ->where('p.status', self::MINTA_MENUNGGU)
            ->orderBy('p.diminta_pada', 'ASC')
            ->get()->getResultArray();
    }

    /** admin_kab menyetujui: tahun dibuka, OPD boleh menyunting. */
    public function setujui(int $permintaanId, ?int $userId, ?string $tanggapan = null): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->transBegin();

        try {
            $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
                'status'          => self::MINTA_DISETUJUI,
                'tanggapan'       => $tanggapan,
                'ditanggapi_oleh' => $userId,
                'ditanggapi_pada' => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('lakip_pengesahan')->where('id', (int) $p['pengesahan_id'])->update([
                'status'      => self::STATUS_DIBUKA,
                'dibuka_oleh' => $userId,
                'dibuka_pada' => date('Y-m-d H:i:s'),
            ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi persetujuan gagal.');
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $p;
    }

    /** admin_kab menolak: tetap terkunci, alasan penolakan tercatat. */
    public function tolak(int $permintaanId, ?int $userId, ?string $tanggapan = null): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
            'status'          => self::MINTA_DITOLAK,
            'tanggapan'       => $tanggapan,
            'ditanggapi_oleh' => $userId,
            'ditanggapi_pada' => date('Y-m-d H:i:s'),
        ]);

        return $p;
    }

    /** OPD menarik permintaannya sendiri selagi belum diputuskan. */
    public function tarik(int $permintaanId): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
            'status' => self::MINTA_DITARIK,
        ]);

        return $p;
    }

    /** @return array<string,mixed> permintaan yang dipastikan masih menunggu */
    private function wajibMenunggu(int $permintaanId): array
    {
        $p = $this->db->table('lakip_buka_permintaan')
            ->where('id', $permintaanId)->get()->getRowArray();

        if ($p === null) {
            throw new RuntimeException('Permintaan tidak ditemukan.');
        }

        if ($p['status'] !== self::MINTA_MENUNGGU) {
            throw new RuntimeException(
                'Permintaan ini sudah diputuskan sebelumnya (' . $p['status'] . ').'
            );
        }

        return $p;
    }
}

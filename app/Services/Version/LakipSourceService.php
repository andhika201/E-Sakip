<?php

namespace App\Services\Version;

use App\Models\DokumenVersiModel;
use CodeIgniter\Database\ConnectionInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Pemilihan SUMBER + VERSI untuk sebuah LAKIP (§24–§28).
 *
 * =====================================================================
 * PERUBAHAN PERILAKU: IKU MENJADI SUMBER BAWAAN
 *
 *   Sebelumnya (dipaksa oleh `mode`, tanpa pilihan):
 *     LAKIP Kabupaten -> rpjmd_target
 *     LAKIP OPD       -> renstra_target
 *
 *   Sekarang (§24):
 *     LAKIP Kabupaten -> default IKU Kabupaten, alternatif RPJMD
 *     LAKIP OPD       -> default IKU OPD,       alternatif Renstra
 *
 * Alasannya bukan selera: IKU adalah dokumen yang memang dirancang sebagai
 * indikator KINERJA UTAMA, dan LAKIP melaporkan capaian kinerja. RPJMD/Renstra
 * tetap tersedia sebagai alternatif untuk unit yang belum menyusun IKU.
 * =====================================================================
 *
 * SATU LAKIP = SATU SUMBER (§2.12, §25). Tidak ada sumber campuran per
 * indikator. Kalau sebagian indikator dari IKU dan sebagian dari Renstra,
 * angka totalnya tidak berarti apa-apa dan tidak ada yang bisa menjelaskan
 * dari mana sebuah baris berasal.
 */
class LakipSourceService
{
    public const SUMBER_IKU     = 'iku';
    public const SUMBER_RPJMD   = 'rpjmd';
    public const SUMBER_RENSTRA = 'renstra';

    public const MODE_KABUPATEN = 'kabupaten';
    public const MODE_OPD       = 'opd';

    private ConnectionInterface $db;

    private VersionResolver $resolver;

    private DokumenVersiModel $versi;

    public function __construct(
        ?ConnectionInterface $db = null,
        ?VersionResolver $resolver = null,
        ?DokumenVersiModel $versi = null
    ) {
        $this->db       = $db ?? db_connect();
        $this->versi    = $versi ?? new DokumenVersiModel($this->db);
        $this->resolver = $resolver ?? new VersionResolver($this->db, $this->versi);
    }

    /* =========================================================
     * PILIHAN SUMBER
     * =======================================================*/

    /** Sumber bawaan selalu IKU (§24). */
    public function sumberBawaan(): string
    {
        return self::SUMBER_IKU;
    }

    /** Alternatif tergantung tingkat: Kabupaten -> RPJMD, OPD -> Renstra. */
    public function sumberAlternatif(string $mode): string
    {
        return $this->modeSah($mode) === self::MODE_KABUPATEN
            ? self::SUMBER_RPJMD
            : self::SUMBER_RENSTRA;
    }

    /**
     * Dua pilihan sumber untuk sebuah lingkup, siap dijadikan radio button.
     *
     * @return array<int,array{nilai:string,label:string,bawaan:bool,tersedia:bool,jml_versi:int}>
     */
    public function pilihanSumber(string $mode, ?int $opdId, int $tahun): array
    {
        $mode = $this->modeSah($mode);
        $out  = [];

        foreach ([self::SUMBER_IKU, $this->sumberAlternatif($mode)] as $tipe) {
            $versi = $this->pilihanVersi($tipe, $mode, $opdId, $tahun);

            $out[] = [
                'nilai'     => $tipe,
                'label'     => $this->labelSumber($tipe, $mode),
                'bawaan'    => $tipe === self::SUMBER_IKU,
                'tersedia'  => $versi !== [],
                'jml_versi' => count($versi),
            ];
        }

        return $out;
    }

    /**
     * Versi PUBLISHED dari sebuah sumber yang periodenya memuat $tahun.
     *
     * Lintas periode dengan sengaja (§28): LAKIP 2025 tidak tahu apakah RPJMD
     * yang memayunginya berperiode 2020-2024 atau 2025-2029, dan justru itulah
     * yang membuat "RPJMD lama / V-1" bisa dilayani.
     *
     * Draft & pending TIDAK PERNAH muncul (§2.11, §27) — menawarkan pilihan
     * yang pasti ditolak saat disimpan hanya membuang waktu operator.
     */
    public function pilihanVersi(string $sumberType, string $mode, ?int $opdId, int $tahun): array
    {
        // IKU dialihkan ke registrinya sendiri; lihat catatan pada
        // pilihanVersiIku() di bawah.
        if (strtolower(trim($sumberType)) === self::SUMBER_IKU) {
            return $this->pilihanVersiIku($mode, $opdId, $tahun);
        }

        [$modul, $scopeNama, $pemilik] = $this->petakan($sumberType, $mode, $opdId);

        return $this->resolver->pilihanSumber($modul, $scopeNama, $pemilik, $tahun);
    }

    /* =========================================================
     * VERSI IKU HIDUP DI REGISTRINYA SENDIRI
     *
     * =====================================================================
     * MENGAPA TIDAK LEWAT dokumen_versi
     *
     * Layanan ini semula memetakan seluruh sumber — termasuk IKU — ke registri
     * bersama `dokumen_versi`. Untuk RPJMD dan Renstra itu benar. Untuk IKU
     * tidak: versinya berakhir hidup di `iku_revisi`, dengan garis waktunya
     * sendiri (`berlaku_mulai_tahun`), jaminan tunggalnya sendiri
     * (`berlaku_key`), dan alur pengesahannya sendiri.
     *
     * Akibatnya `pilihanVersi('iku', ...)` selalu mengembalikan daftar KOSONG —
     * bukan karena tidak ada versinya, melainkan karena dicari di tabel yang
     * memang tidak pernah diisi. Tidak ada galat; dropdown-nya sekadar kosong.
     *
     * Yang benar bukan mencerminkan `iku_revisi` ke `dokumen_versi` — itu
     * melahirkan dua sumber kebenaran untuk hal yang sama — melainkan membaca
     * langsung ke tempat versinya benar-benar berada.
     * =======================================================*/

    /**
     * Versi IKU yang bisa dipakai LAKIP tahun $tahun.
     *
     * Yang ditawarkan: SEMUA revisi yang PERNAH RESMI (`berlaku` atau
     * `superseded`) dan periodenya memuat tahun laporan. Draft dan pengajuan
     * yang belum diputuskan tidak pernah muncul (§2.11, §27): menawarkan
     * pilihan yang pasti ditolak saat disimpan hanya membuang waktu operator.
     *
     * Sampai 14 Sep 2026 daftarnya juga disaring MASA BERLAKU (revisi yang
     * berlaku pada tahun itu saja), sehingga dropdown "Versi yang dipakai"
     * praktis hanya berisi satu pilihan — pemakai tidak bisa menilai LAKIP
     * terhadap revisi lain walau punya alasan. Kini masa berlaku hanya
     * menentukan REKOMENDASI; memilih selain rekomendasi tetap wajib beralasan
     * saat disimpan (§27), jadi pagarnya tidak hilang, hanya pindah ke tempat
     * yang tepat.
     *
     * @return array<int,array<string,mixed>> bentuknya disamakan dengan
     *         VersionResolver::pilihanSumber() supaya tampilan tidak perlu
     *         tahu sumbernya dari registri yang mana
     */
    public function pilihanVersiIku(string $mode, ?int $opdId, int $tahun): array
    {
        $mode   = $this->modeSah($mode);
        $opdKey = $mode === self::MODE_KABUPATEN ? 0 : (int) $opdId;

        if (! $this->db->tableExists('iku_revisi')) {
            return [];
        }

        $rows = $this->db->table('iku_revisi')
            ->where('opd_key', $opdKey)
            ->whereIn('status', ['berlaku', 'superseded'])
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->orderBy('berlaku_mulai_tahun', 'DESC')
            ->orderBy('nomor', 'DESC')
            ->get()->getResultArray();

        if ($rows === []) {
            return [];
        }

        // Rekomendasi = revisi yang benar-benar berlaku pada tahun laporan
        // (masa berlakunya memuat tahun itu). Bila lebih dari satu memenuhi
        // (garis waktunya bermasalah), tidak ada yang ditandai — persis
        // seperti perlakuan VersionResolver.
        $berlakuPadaTahun = static function (array $r) use ($tahun): bool {
            $mulai  = (int) $r['berlaku_mulai_tahun'];
            $sampai = $r['berlaku_sampai_tahun'] !== null ? (int) $r['berlaku_sampai_tahun'] : null;

            return $mulai <= $tahun && ($sampai === null || $sampai >= $tahun);
        };

        $kandidat    = array_values(array_filter($rows, $berlakuPadaTahun));
        $rekomendasi = count($kandidat) === 1 ? (int) $kandidat[0]['id'] : null;

        foreach ($rows as &$r) {
            $r['version_no']       = (int) $r['nomor'];
            $r['label']            = (string) ($r['nama'] ?? ('Revisi ke-' . $r['nomor']));
            $r['effective_from']   = (int) $r['berlaku_mulai_tahun'];
            $r['effective_to']     = $r['berlaku_sampai_tahun'] !== null
                ? (int) $r['berlaku_sampai_tahun'] : null;
            $r['berlaku_pada_tahun'] = $berlakuPadaTahun($r);
            $r['rekomendasi']      = $rekomendasi !== null && (int) $r['id'] === $rekomendasi;
            $r['badge']            = $r['status'] === 'berlaku' ? 'CURRENT' : 'HISTORICAL';
            // Teks masa berlaku untuk dropdown: "berlaku 2026–…" / "berlaku 2025".
            $r['masa']             = 'berlaku ' . $r['effective_from']
                . ($r['effective_to'] === null ? '–…'
                    : ($r['effective_to'] === $r['effective_from'] ? '' : '–' . $r['effective_to']));
        }
        unset($r);

        return $rows;
    }


    /**
     * Versi yang direkomendasikan untuk tahun laporan (§26).
     *
     * Tanggal rujukan bakunya 31 Desember tahun laporan — LAKIP menilai kinerja
     * satu tahun penuh, jadi yang relevan adalah dokumen yang berlaku di ujung
     * tahun itu, bukan yang berlaku saat LAKIP-nya disusun.
     *
     * @throws VersionConflictException bila >1 versi berlaku bersamaan
     */
    public function rekomendasi(
        string $sumberType,
        string $mode,
        ?int $opdId,
        int $tahun,
        ?string $tanggalRujukan = null
    ): ?array {
        // IKU hidup di registrinya sendiri (iku_revisi) — lihat catatan panjang
        // di atas pilihanVersiIku(). Tanpa cabang ini, rekomendasi dicari di
        // dokumen_versi yang untuk IKU memang tidak pernah diisi.
        if (strtolower(trim($sumberType)) === self::SUMBER_IKU) {
            foreach ($this->pilihanVersiIku($mode, $opdId, $tahun) as $r) {
                if (! empty($r['rekomendasi'])) {
                    return $r;
                }
            }

            return null;
        }

        [$modul, $scopeNama, $pemilik] = $this->petakan($sumberType, $mode, $opdId);

        return $this->resolver->rekomendasiUntukTahun(
            $modul,
            $scopeNama,
            $pemilik,
            $tahun,
            $tanggalRujukan ?? VersionResolver::akhirTahun($tahun)
        );
    }

    /**
     * Validasi pilihan operator sebelum snapshot dibuat.
     *
     * Menegakkan §27 di SERVER: memilih versi selain rekomendasi WAJIB disertai
     * alasan. Diperiksa di sini, bukan hanya di form, karena form bisa dilewati.
     *
     * @return array{
     *     sumber_type:string, sumber_versi:array, rekomendasi:?array,
     *     adalah_rekomendasi:bool, tanggal_rujukan:string, galat:string[]
     * }
     */
    public function validasiPilihan(
        string $sumberType,
        int $sumberVersiId,
        string $mode,
        ?int $opdId,
        int $tahun,
        ?string $alasanOverride = null,
        ?string $tanggalRujukan = null
    ): array {
        $sumberType = $this->sumberSah($sumberType, $mode);
        $mode       = $this->modeSah($mode);
        $tanggal    = $tanggalRujukan ?? VersionResolver::akhirTahun($tahun);

        // Id versi IKU adalah id `iku_revisi`, BUKAN id `dokumen_versi` —
        // dua tabel itu ruang angka yang berbeda. Memvalidasinya ke
        // dokumen_versi berarti "tidak ditemukan", atau lebih buruk:
        // kebetulan cocok dengan dokumen lain dan lolos sebagai dokumen salah.
        if ($sumberType === self::SUMBER_IKU) {
            return $this->validasiPilihanIku($sumberVersiId, $mode, $opdId, $tahun, $alasanOverride, $tanggal);
        }

        $galat  = [];
        $versi  = $this->versi->ambil($sumberVersiId);
        $rekom  = null;

        try {
            $rekom = $this->rekomendasi($sumberType, $mode, $opdId, $tahun, $tanggal);
        } catch (VersionConflictException $e) {
            $galat[] = $e->getMessage();
        }

        if ($versi === null) {
            $galat[] = 'Versi sumber tidak ditemukan.';

            return [
                'sumber_type'        => $sumberType,
                'sumber_versi'       => [],
                'rekomendasi'        => $rekom,
                'adalah_rekomendasi' => false,
                'tanggal_rujukan'    => $tanggal,
                'galat'              => $galat,
            ];
        }

        // Anti-IDOR: versi yang ditebak dari URL harus benar-benar milik modul,
        // lingkup, dan periode yang sedang dikerjakan (§55).
        [$modul, $scopeNama, $pemilik] = $this->petakan($sumberType, $mode, $opdId);

        if ($versi['modul'] !== $modul) {
            $galat[] = 'Versi yang dipilih bukan dokumen ' . strtoupper($sumberType) . '.';
        }

        if ((string) $versi['scope_key'] !== $scopeNama || (int) $versi['opd_key'] !== (int) ($pemilik ?? 0)) {
            $galat[] = 'Versi yang dipilih bukan milik lingkup ini.';
        }

        if ((int) $versi['periode_mulai'] > $tahun || (int) $versi['periode_akhir'] < $tahun) {
            $galat[] = 'Periode versi (' . $versi['periode_mulai'] . '-' . $versi['periode_akhir']
                . ') tidak memuat tahun laporan ' . $tahun . '.';
        }

        // §2.11 — draft/pending tidak boleh jadi sumber resmi.
        if ($versi['status'] !== DokumenVersiModel::STATUS_PUBLISHED) {
            $galat[] = 'Hanya versi yang sudah ditetapkan yang boleh menjadi sumber LAKIP. '
                . 'Status versi ini: ' . $versi['status'] . '.';
        }

        $adalahRekomendasi = $rekom !== null && (int) $rekom['id'] === (int) $versi['id'];

        // §27 — bukan rekomendasi berarti wajib beralasan.
        if (! $adalahRekomendasi && trim((string) $alasanOverride) === '') {
            $galat[] = 'Versi yang dipilih bukan rekomendasi sistem untuk tahun ' . $tahun
                . '. Alasan penggunaan versi wajib diisi.';
        }

        return [
            'sumber_type'        => $sumberType,
            'sumber_versi'       => $versi,
            'rekomendasi'        => $rekom,
            'adalah_rekomendasi' => $adalahRekomendasi,
            'tanggal_rujukan'    => $tanggal,
            'galat'              => $galat,
        ];
    }

    /**
     * validasiPilihan() untuk sumber IKU — memeriksa ke `iku_revisi`.
     *
     * Daftar dari pilihanVersiIku() sudah tersaring lingkup + periode +
     * status resmi (berlaku/superseded), jadi satu pemeriksaan keanggotaan
     * sekaligus menegakkan anti-IDOR (§55), periode, dan §2.11: id yang
     * bukan milik lingkup ini, di luar periode, atau masih draft/menunggu
     * memang tidak pernah ada dalam daftar.
     *
     * Bentuk kembaliannya sama persis dengan cabang dokumen_versi supaya
     * pemakai (praTinjau/siapkan, view pemilih sumber) tidak perlu tahu
     * versinya berasal dari registri yang mana.
     *
     * SENGAJA lebih ketat dari cabang dokumen_versi dalam satu hal: revisi
     * yang masa berlakunya TIDAK memuat tahun laporan ditolak sama sekali,
     * beralasan sekalipun. Masa berlaku revisi IKU adalah pernyataan resmi
     * "tahun ini dinilai dengan dokumen itu"; §27 memberi ruang memilih versi
     * non-rekomendasi, bukan ruang menilai tahun dengan dokumen yang secara
     * resmi tidak pernah memayunginya.
     */
    private function validasiPilihanIku(
        int $sumberVersiId,
        string $mode,
        ?int $opdId,
        int $tahun,
        ?string $alasanOverride,
        string $tanggal
    ): array {
        $galat  = [];
        $daftar = $this->pilihanVersiIku($mode, $opdId, $tahun);

        $versi = null;
        $rekom = null;

        foreach ($daftar as $r) {
            if ((int) $r['id'] === $sumberVersiId) {
                $versi = $r;
            }

            if (! empty($r['rekomendasi'])) {
                $rekom = $r;
            }
        }

        if ($versi === null) {
            $galat[] = 'Versi IKU yang dipilih tidak tersedia untuk lingkup dan tahun laporan ini.';

            return [
                'sumber_type'        => self::SUMBER_IKU,
                'sumber_versi'       => [],
                'rekomendasi'        => $rekom,
                'adalah_rekomendasi' => false,
                'tanggal_rujukan'    => $tanggal,
                'galat'              => $galat,
            ];
        }

        $adalahRekomendasi = $rekom !== null && (int) $rekom['id'] === (int) $versi['id'];

        // §27 — bukan rekomendasi berarti wajib beralasan.
        if (! $adalahRekomendasi && trim((string) $alasanOverride) === '') {
            $galat[] = 'Versi yang dipilih bukan rekomendasi sistem untuk tahun ' . $tahun
                . '. Alasan penggunaan versi wajib diisi.';
        }

        return [
            'sumber_type'        => self::SUMBER_IKU,
            'sumber_versi'       => $versi,
            'rekomendasi'        => $rekom,
            'adalah_rekomendasi' => $adalahRekomendasi,
            'tanggal_rujukan'    => $tanggal,
            'galat'              => $galat,
        ];
    }

    /* =========================================================
     * PEMETAAN
     * =======================================================*/

    /**
     * Terjemahkan (sumber, mode, opd) menjadi lingkup dokumen_versi.
     *
     * RPJMD selalu kabupaten. IKU & Renstra mengikuti mode. Ini satu-satunya
     * tempat pemetaan itu ditulis, supaya tidak ada dua versi aturan.
     *
     * @return array{0:string,1:string,2:?int} [modul, scope_key, opd_key]
     */
    public function petakan(string $sumberType, string $mode, ?int $opdId): array
    {
        $sumberType = strtolower(trim($sumberType));
        $mode       = $this->modeSah($mode);

        switch ($sumberType) {
            case self::SUMBER_RPJMD:
                // RPJMD tidak punya pemilik OPD walau LAKIP-nya milik OPD.
                return [VersionScope::MODUL_RPJMD, VersionScope::SCOPE_KABUPATEN, null];

            case self::SUMBER_RENSTRA:
                if ($mode !== self::MODE_OPD || empty($opdId)) {
                    throw new InvalidArgumentException('Sumber Renstra hanya untuk LAKIP tingkat OPD.');
                }

                return [VersionScope::MODUL_RENSTRA, VersionScope::SCOPE_OPD, (int) $opdId];

            case self::SUMBER_IKU:
                return $mode === self::MODE_KABUPATEN
                    ? [VersionScope::MODUL_IKU, VersionScope::SCOPE_KABUPATEN, null]
                    : [VersionScope::MODUL_IKU, VersionScope::SCOPE_OPD, (int) $opdId];

            default:
                throw new InvalidArgumentException('Sumber LAKIP tidak dikenal: ' . $sumberType);
        }
    }

    public function modeSah(string $mode): string
    {
        $mode = strtolower(trim($mode));

        if (! in_array($mode, [self::MODE_KABUPATEN, self::MODE_OPD], true)) {
            throw new InvalidArgumentException('Mode LAKIP tidak dikenal: ' . $mode);
        }

        return $mode;
    }

    public function sumberSah(string $sumberType, string $mode): string
    {
        $sumberType = strtolower(trim($sumberType));
        $mode       = $this->modeSah($mode);

        $boleh = [self::SUMBER_IKU, $this->sumberAlternatif($mode)];

        if (! in_array($sumberType, $boleh, true)) {
            throw new InvalidArgumentException(
                'Sumber ' . $sumberType . ' tidak berlaku untuk LAKIP tingkat ' . $mode
                . '. Yang tersedia: ' . implode(' atau ', $boleh) . '.'
            );
        }

        return $sumberType;
    }

    private function labelSumber(string $tipe, string $mode): string
    {
        switch ($tipe) {
            case self::SUMBER_IKU:
                return $mode === self::MODE_KABUPATEN ? 'IKU Kabupaten' : 'IKU Perangkat Daerah';

            case self::SUMBER_RPJMD:
                return 'RPJMD';

            default:
                return 'Renstra Perangkat Daerah';
        }
    }
}

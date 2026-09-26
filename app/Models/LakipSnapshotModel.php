<?php

namespace App\Models;

use App\Models\Concerns\TransaksiAman;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * SNAPSHOT TAHUNAN LAKIP — pembekuan seluruh baris LAKIP satu tahun.
 *
 * ---------------------------------------------------------------------
 * MASALAH YANG DISELESAIKAN
 *
 * LAKIP adalah dokumen TAHUNAN, tapi seluruh isinya dirakit dari query hidup
 * ke renstra_target / rpjmd_target / lakip setiap kali halaman dibuka —
 * cetak() memakai query yang sama persis dengan index(). Akibatnya menyunting
 * Renstra atau RPJMD hari ini ikut mengubah bunyi LAKIP tahun-tahun lampau.
 *
 * Bukti bahwa ini bukan kekhawatiran teoretis: FK `lakip` ke kedua tabel
 * target memakai ON DELETE SET NULL, dan di basis data produksi 111 dari 214
 * baris `lakip` sudah yatim — kedua kolom targetnya NULL. Baris-baris itu
 * sudah lenyap diam-diam dari semua laporan, karena setiap query menyaring
 * lewat rt.tahun / rpj.tahun.
 *
 * Karena itu snapshot MENYALIN TEKS (sasaran, indikator, satuan, nilai), bukan
 * sekadar menyimpan id target. Arsip yang isinya cuma penunjuk akan ikut kosong
 * begitu sumbernya hilang.
 *
 * ---------------------------------------------------------------------
 * YANG DIBEKUKAN: MASUKAN, BUKAN HASIL HITUNG
 *
 * Ini keputusan penting dan tidak kentara. `hitungCapaianLakip()` ada TIGA
 * definisi di proyek ini, semuanya dijaga `function_exists()`:
 *
 *   app/Helpers/lakip_helper.php          -> dipakai cetak & Excel
 *   views/adminOpd/lakip/lakip.php        -> dipakai layar OPD
 *   views/adminKabupaten/lakip/lakip.php  -> dipakai layar Kab
 *
 * Dulu ketiganya BERBEDA: helper tanpa batas, kedua view memotong ke 200%,
 * sehingga layar dan PDF menampilkan angka berbeda untuk capaian ekstrem
 * (Persentase Daerah Rawan Pangan: 200% di layar, 1.125% di PDF). Sejak
 * 23 Sep 2026 pemotongan itu dicabut dan ketiganya memberi hasil yang SAMA;
 * batas 0-200% kini hanya dipasang pada perhitungan RATA-RATA di
 * LakipKabupatenCapaianService::ringkasan().
 *
 * Yang dibekukan tetap BAHAN MENTAHNYA (target, realisasi, satuan, jenis
 * indikator, nilai *_hitung), bukan angka persen jadi — supaya perubahan
 * rumus di kemudian hari tidak membuat arsip lama ikut bergeser, dan supaya
 * ketiga definisi di atas boleh disatukan tanpa menyentuh isi snapshot.
 *
 * Konsekuensi lain dari prinsip yang sama: rehidrasi() mengembalikan
 * ['rows', 'lakipMap'] dengan bentuk PERSIS seperti LakipModel::getLakipByMode(),
 * sehingga controller & view tidak perlu tahu sedang membaca arsip atau data
 * hidup.
 *
 * ---------------------------------------------------------------------
 * INVARIANT YANG DIJAGA DI SINI
 *
 *  3. Satu snapshot aktif per (tahun, mode, opd). UNIQUE index di atas
 *     generated column `aktif_key` yang menjaminnya; siapkan() menolak dan
 *     menyuruh pemakai memilih Lihat / Bandingkan / Sinkronkan (Case 12).
 *  6. Snapshot 'final' terkunci: tidak boleh disinkronkan, tidak boleh
 *     dibangun ulang, tidak boleh disunting destruktif.
 *  7. Semua operasi majemuk satu transaksi penuh (Case 13).
 * ---------------------------------------------------------------------
 */
class LakipSnapshotModel extends Model
{
    use TransaksiAman;

    protected $table         = 'lakip_snapshot';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

    /** Sumber baris yang mungkin dibekukan. Nilainya sama dengan LakipSourceService. */
    public const SUMBER_RENSTRA = 'renstra';
    public const SUMBER_RPJMD   = 'rpjmd';
    public const SUMBER_IKU     = 'iku';

    /* =========================================================
     * KESIAPAN
     * =======================================================*/

    /**
     * Tabel snapshot sudah terpasang atau belum.
     *
     * Meniru LakipAnalisisModel::siap(). Perhatikan tableExists() memakai cache
     * per-koneksi, jadi bila SQL dijalankan di tengah request yang sama hasilnya
     * bisa basi — itu sebabnya pemeriksaan ini memakai argumen kedua `false`
     * pada pemanggilan pertama.
     */
    public function siap(): bool
    {
        static $siap = null;

        if ($siap !== null) {
            return $siap;
        }

        try {
            foreach (['lakip_snapshot', 'lakip_snapshot_baris'] as $t) {
                if (! $this->db->tableExists($t, false)) {
                    return $siap = false;
                }
            }

            return $siap = true;
        } catch (Throwable $e) {
            return $siap = false;
        }
    }

    /* =========================================================
     * PEMBACAAN
     * =======================================================*/

    /**
     * Snapshot yang sedang aktif untuk satu lingkup, atau null.
     *
     * Inilah yang dipakai halaman LAKIP untuk memutuskan apakah tombol
     * "Siapkan LAKIP" ditampilkan, atau diganti Lihat / Bandingkan / Sinkronkan.
     */
    public function aktif(string $tahun, string $mode, ?int $opdId): ?array
    {
        if (! $this->siap() || $tahun === '') {
            return null;
        }

        return $this->db->table('lakip_snapshot')
            ->where('tahun', $tahun)
            ->where('mode', $this->modeSah($mode))
            ->where('opd_id', $this->opdSah($mode, $opdId))
            ->where('aktif', 1)
            ->get()
            ->getRowArray() ?: null;
    }

    /** Seluruh versi snapshot satu lingkup, terbaru dulu. */
    public function daftar(string $tahun, string $mode, ?int $opdId): array
    {
        if (! $this->siap() || $tahun === '') {
            return [];
        }

        return $this->db->table('lakip_snapshot')
            ->where('tahun', $tahun)
            ->where('mode', $this->modeSah($mode))
            ->where('opd_id', $this->opdSah($mode, $opdId))
            ->orderBy('versi', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function ambil(int $snapshotId): ?array
    {
        if (! $this->siap() || $snapshotId <= 0) {
            return null;
        }

        return $this->db->table('lakip_snapshot')->where('id', $snapshotId)->get()->getRowArray() ?: null;
    }

    /** Apakah lingkup ini sudah dikunci (punya snapshot final yang aktif)? */
    public function terkunci(string $tahun, string $mode, ?int $opdId): bool
    {
        $s = $this->aktif($tahun, $mode, $opdId);

        return $s !== null && $s['status'] === self::STATUS_FINAL;
    }

    /* =========================================================
     * REHIDRASI — arsip dikembalikan ke bentuk yang dikenal view
     * =======================================================*/

    /**
     * Bentuk yang SAMA PERSIS dengan LakipModel::getLakipByMode().
     *
     * Kunci-kunci di bawah bukan karangan: itu daftar kolom yang benar-benar
     * dihasilkan getIndexRenstraTargets()/getIndexRpjmdTargets() dan
     * getLakipMapRenstra()/getLakipMapRpjmd(). Karena bentuknya identik,
     * controller cukup menukar sumbernya dan seluruh view, helper cetak, serta
     * kedua fungsi Excel bekerja tanpa diubah.
     *
     * @return array{rows: array<int, array<string, mixed>>, lakipMap: array<int, array<string, mixed>>}
     */
    public function rehidrasi(int $snapshotId): array
    {
        $kosong = ['rows' => [], 'lakipMap' => []];

        $snapshot = $this->ambil($snapshotId);
        if (! $snapshot) {
            return $kosong;
        }

        $mode   = $snapshot['mode'];
        $sumber = $this->sumberSnapshot($snapshot);
        $kunci  = $this->kolomKunci($sumber);
        $baris  = $this->db->table('lakip_snapshot_baris')
            ->where('snapshot_id', $snapshotId)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $rows     = [];
        $lakipMap = [];

        foreach ($baris as $b) {
            $targetId = (int) ($b[$kunci] ?? 0);

            $row = [
                'target_id'         => $targetId,
                'tahun'             => $snapshot['tahun'],
                'target_tahun_ini'  => $b['target'],
                'indikator_id'      => $b['indikator_id'] !== null ? (int) $b['indikator_id'] : null,
                'indikator_sasaran' => $b['indikator'],
                'satuan'            => $b['satuan'],
                'jenis_indikator'   => $b['jenis_indikator'],
                'sasaran_id'        => $b['sasaran_id'] !== null ? (int) $b['sasaran_id'] : null,
                'sasaran'           => $b['sasaran'],
            ];

            if ($sumber === self::SUMBER_IKU) {
                // View LAKIP membaca `arsip_id` untuk menautkan baris ke
                // arsip revisinya; tanpa ini tautan "lihat di IKU" mati.
                $row['arsip_id']              = $b['iku_revisi_indikator_id'] !== null
                    ? (int) $b['iku_revisi_indikator_id']
                    : null;
                $row['perubahan_substansial'] = (int) ($b['perubahan_substansial'] ?? 0);
            }

            // Jalur RPJMD memang tidak punya kedua kolom ini. Menambahkannya
            // akan mengubah $sasKey pada view AdminKab (opd_id ikut jadi bagian
            // kunci rowspan) dan menggeser tampilan.
            if ($mode !== 'kabupaten') {
                $row['opd_id']   = (int) $b['opd_id'];
                $row['nama_opd'] = $b['nama_opd'];
            }

            $rows[] = $row;

            // Baris target tanpa baris LAKIP memang tidak boleh masuk map —
            // view merendernya sebagai '-'. Ini juga yang mempertahankan
            // perilaku filter status: saat dibekukan, baris yang tersaring
            // filter memang tidak ada di map.
            if ($b['lakip_id'] === null) {
                continue;
            }

            $lakipMap[$targetId] = [
                'id'                => (int) $b['lakip_id'],
                'renstra_target_id' => $b['renstra_target_id'] !== null ? (int) $b['renstra_target_id'] : null,
                'rpjmd_target_id'   => $b['rpjmd_target_id'] !== null ? (int) $b['rpjmd_target_id'] : null,
                'source_type'       => $sumber,
                'source_entity_id'  => $sumber === self::SUMBER_IKU ? $targetId : null,
                'target_hitung'     => $b['target_hitung'],
                'target_lalu'       => $b['target_lalu'],
                'capaian_lalu'      => $b['capaian_lalu'],
                'capaian_tahun_ini' => $b['realisasi'],
                'capaian_hitung'    => $b['capaian_hitung'],
                'status'            => $b['status_lakip'],
                'tahun_target'      => $snapshot['tahun'],
                'target_tahun_ini'  => $b['target'],
                'indikator_id'      => $b['indikator_id'] !== null ? (int) $b['indikator_id'] : null,
                'sasaran_id'        => $b['sasaran_id'] !== null ? (int) $b['sasaran_id'] : null,
            ];
        }

        return ['rows' => $rows, 'lakipMap' => $lakipMap];
    }

    /**
     * Dua tabel tambahan (Analisis Faktor & Efisiensi Program) versi beku,
     * dalam bentuk yang sama dengan LakipAddendumTrait::lakipAddendumData().
     *
     * @return array{analisisMap: array<int, array<int, array<string,mixed>>>, efisiensiRows: array<int, array<string,mixed>>}
     */
    public function rehidrasiAddendum(int $snapshotId): array
    {
        $kosong = ['analisisMap' => [], 'efisiensiRows' => []];

        $snapshot = $this->ambil($snapshotId);
        if (! $snapshot) {
            return $kosong;
        }

        $mode   = $snapshot['mode'];
        $kolom  = ($mode === 'kabupaten') ? 'rpjmd_target_id' : 'renstra_target_id';
        $analis = [];

        if ($this->db->tableExists('lakip_snapshot_analisis')) {
            $rows = $this->db->table('lakip_snapshot_analisis a')
                ->select('a.*, b.renstra_target_id, b.rpjmd_target_id')
                ->join('lakip_snapshot_baris b', 'b.id = a.snapshot_baris_id', 'left')
                ->where('a.snapshot_id', $snapshotId)
                ->orderBy('a.urutan', 'ASC')
                ->orderBy('a.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                if ($r[$kolom] === null) {
                    continue;
                }

                $analis[(int) $r[$kolom]][] = [
                    'id'                => (int) ($r['sumber_analisis_id'] ?? 0),
                    'renstra_target_id' => $r['renstra_target_id'] !== null ? (int) $r['renstra_target_id'] : null,
                    'rpjmd_target_id'   => $r['rpjmd_target_id'] !== null ? (int) $r['rpjmd_target_id'] : null,
                    'opd_id'            => (int) $snapshot['opd_id'],
                    'tahun'             => $snapshot['tahun'],
                    'faktor_pendukung'  => $r['faktor_pendukung'],
                    'faktor_penghambat' => $r['faktor_penghambat'],
                    'upaya_peningkatan' => $r['upaya_peningkatan'],
                ];
            }
        }

        $efisiensi = [];

        if ($this->db->tableExists('lakip_snapshot_program')) {
            foreach (
                $this->db->table('lakip_snapshot_program')
                    ->where('snapshot_id', $snapshotId)
                    ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
                    ->get()->getResultArray() as $p
            ) {
                $efisiensi[] = [
                    'id'         => (int) $p['id'],
                    'program_id' => $p['program_id'] !== null ? (int) $p['program_id'] : null,
                    'program'    => $p['program'],
                    'nama_program' => $p['program'],
                    'opd_id'     => (int) $snapshot['opd_id'],
                    'tahun'      => $snapshot['tahun'],
                    'anggaran'   => $p['anggaran'],
                    'realisasi'  => $p['realisasi'],
                    'efisiensi'  => $p['efisiensi'],
                ];
            }
        }

        return ['analisisMap' => $analis, 'efisiensiRows' => $efisiensi];
    }

    /* =========================================================
     * PEMBEKUAN
     * =======================================================*/

    /**
     * Siapkan snapshot pertama untuk satu lingkup.
     *
     * CASE 12 / INVARIANT 3 — bila lingkup ini SUDAH punya snapshot aktif,
     * method ini MELEMPAR, bukan diam-diam membuat snapshot kedua. Pemanggil
     * menangkapnya dan menampilkan pilihan Lihat / Bandingkan / Sinkronkan.
     *
     * @param array $scope ['tahun' => string, 'mode' => string, 'opdScope' => int|null]
     * @param array $bahan ['rows', 'lakipMap', 'analisisMap', 'efisiensiRows', 'filter_status', 'iku_revisi_id']
     */
    public function siapkan(array $scope, array $bahan, ?int $userId = null): int
    {
        $this->pastikanSiap();

        [$tahun, $mode, $opdId] = $this->bacaScope($scope);

        if ($this->aktif($tahun, $mode, $opdId) !== null) {
            throw new RuntimeException(
                'LAKIP tahun ' . $tahun . ' untuk lingkup ini sudah memiliki snapshot. '
                . 'Gunakan Lihat Snapshot, Bandingkan dengan data terbaru, atau Sinkronkan Snapshot.'
            );
        }

        return $this->dalamTransaksi(
            fn () => $this->tulisVersi($tahun, $mode, $opdId, 1, $bahan, $userId, 'dibuat'),
            'pembuatan snapshot LAKIP'
        );
    }

    /**
     * Sinkronkan: buat versi baru dari data terkini, nonaktifkan versi lama.
     *
     * INVARIANT 6 — ditolak bila snapshot aktifnya sudah final. Setelah
     * finalisasi, satu-satunya cara mengubah angka adalah lewat
     * `lakip_penyesuaian` yang tercatat lengkap dengan dasar kebijakannya.
     *
     * Versi lama TIDAK dihapus, hanya di-nonaktifkan (aktif = 0) sehingga
     * UNIQUE index tetap terpenuhi dan riwayat pembekuan tetap bisa ditelusuri.
     */
    public function sinkronkan(array $scope, array $bahan, ?int $userId = null): int
    {
        $this->pastikanSiap();

        [$tahun, $mode, $opdId] = $this->bacaScope($scope);

        $lama = $this->aktif($tahun, $mode, $opdId);

        if ($lama === null) {
            throw new RuntimeException('Belum ada snapshot untuk lingkup ini. Gunakan "Siapkan LAKIP" lebih dulu.');
        }

        if ($lama['status'] === self::STATUS_FINAL) {
            throw new RuntimeException(
                'Snapshot LAKIP tahun ' . $tahun . ' sudah difinalkan dan tidak boleh disinkronkan ulang. '
                . 'Perubahan setelah finalisasi hanya boleh lewat Penyesuaian Kebijakan yang tercatat.'
            );
        }

        return $this->dalamTransaksi(function () use ($lama, $tahun, $mode, $opdId, $bahan, $userId) {
            // Nonaktifkan DULU, kalau tidak UNIQUE `uq_lakip_snapshot_aktif`
            // akan menolak penyisipan versi baru.
            $this->db->table('lakip_snapshot')->where('id', (int) $lama['id'])->update([
                'aktif'      => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->tulisVersi(
                $tahun,
                $mode,
                $opdId,
                ((int) $lama['versi']) + 1,
                $bahan,
                $userId,
                'disinkronkan'
            );
        }, 'sinkronisasi snapshot LAKIP');
    }

    /**
     * Kunci tahun: snapshot jadi final.
     *
     * Tidak disediakan "buka kunci". Invariant 6 menyatakan LAKIP final tidak
     * boleh disunting destruktif; menyediakan tombol buka kunci sama saja
     * dengan membatalkan seluruh jaminan itu. Koreksi setelah finalisasi
     * ditempuh lewat Penyesuaian Kebijakan, yang meninggalkan jejak.
     */
    public function finalkan(int $snapshotId, ?int $userId = null): bool
    {
        $this->pastikanSiap();

        $snapshot = $this->ambil($snapshotId);

        if (! $snapshot) {
            throw new RuntimeException('Snapshot tidak ditemukan.');
        }
        if ((int) $snapshot['aktif'] !== 1) {
            throw new RuntimeException('Hanya snapshot yang sedang aktif yang bisa difinalkan.');
        }
        if ($snapshot['status'] === self::STATUS_FINAL) {
            throw new RuntimeException('Snapshot ini sudah final.');
        }

        return (bool) $this->db->table('lakip_snapshot')->where('id', $snapshotId)->update([
            'status'          => self::STATUS_FINAL,
            'difinalkan_oleh' => $userId,
            'difinalkan_pada' => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /* =========================================================
     * PERBANDINGAN
     * =======================================================*/

    /**
     * Bandingkan isi snapshot dengan bahan terkini.
     *
     * Dipakai tombol "Bandingkan dengan data terbaru" (Case 12), supaya pemakai
     * bisa melihat apa yang berubah SEBELUM memutuskan menyinkronkan.
     *
     * @return array{
     *     berubah: array<int, array<string,mixed>>, baru: array<int, array<string,mixed>>,
     *     hilang: array<int, array<string,mixed>>, sama: int
     * }
     */
    public function bandingkan(int $snapshotId, array $bahan): array
    {
        $snapshot = $this->ambil($snapshotId);
        $hasil    = ['berubah' => [], 'baru' => [], 'hilang' => [], 'sama' => 0];

        if (! $snapshot) {
            return $hasil;
        }

        $kunci = $this->kolomKunci($this->sumberSnapshot($snapshot));
        $beku  = [];

        foreach (
            $this->db->table('lakip_snapshot_baris')
                ->where('snapshot_id', $snapshotId)->get()->getResultArray() as $b
        ) {
            $beku[(int) ($b[$kunci] ?? 0)] = $b;
        }

        $lakipMap = $bahan['lakipMap'] ?? [];

        foreach (($bahan['rows'] ?? []) as $r) {
            $targetId = (int) ($r['target_id'] ?? 0);
            $lk       = $lakipMap[$targetId] ?? null;

            if (! isset($beku[$targetId])) {
                $hasil['baru'][] = [
                    'target_id' => $targetId,
                    'indikator' => $r['indikator_sasaran'] ?? '',
                    'sasaran'   => $r['sasaran'] ?? '',
                ];
                continue;
            }

            $b     = $beku[$targetId];
            $selisih = [];

            $bandingkan = [
                'sasaran'         => [$b['sasaran'], $r['sasaran'] ?? null],
                'indikator'       => [$b['indikator'], $r['indikator_sasaran'] ?? null],
                'satuan'          => [$b['satuan'], $r['satuan'] ?? null],
                'jenis_indikator' => [$b['jenis_indikator'], $r['jenis_indikator'] ?? null],
                'target'          => [$b['target'], $r['target_tahun_ini'] ?? null],
                'realisasi'       => [$b['realisasi'], $lk['capaian_tahun_ini'] ?? null],
                'target_lalu'     => [$b['target_lalu'], $lk['target_lalu'] ?? null],
                'capaian_lalu'    => [$b['capaian_lalu'], $lk['capaian_lalu'] ?? null],
                'status'          => [$b['status_lakip'], $lk['status'] ?? null],
            ];

            foreach ($bandingkan as $label => [$lamaNilai, $baruNilai]) {
                if ((string) $lamaNilai !== (string) ($baruNilai ?? '')) {
                    $selisih[$label] = ['snapshot' => $lamaNilai, 'sekarang' => $baruNilai];
                }
            }

            if (empty($selisih)) {
                $hasil['sama']++;
                continue;
            }

            $hasil['berubah'][] = [
                'target_id' => $targetId,
                'indikator' => $b['indikator'],
                'sasaran'   => $b['sasaran'],
                'selisih'   => $selisih,
            ];
        }

        // Sisa yang tidak tersentuh = ada di snapshot tapi tidak ada lagi di
        // data hidup. Dengan 111 baris `lakip` yatim di produksi, ini bukan
        // kasus langka.
        foreach ($bahan['rows'] ?? [] as $r) {
            unset($beku[(int) ($r['target_id'] ?? 0)]);
        }

        foreach ($beku as $targetId => $b) {
            $hasil['hilang'][] = [
                'target_id' => $targetId,
                'indikator' => $b['indikator'],
                'sasaran'   => $b['sasaran'],
            ];
        }

        return $hasil;
    }

    /* =========================================================
     * PENULIS VERSI
     * =======================================================*/

    /**
     * Tulis satu versi snapshot beserta seluruh barisnya.
     *
     * Dipanggil HANYA dari dalam dalamTransaksi(): kalau penulisan baris gagal
     * di tengah, kepala snapshot ikut dibatalkan (Case 13).
     */
    /* =========================================================
     * SUMBER BARIS
     * =======================================================*/

    /**
     * Sumber baris yang dibekukan snapshot ini.
     *
     * Sebelum LAKIP boleh bersumber IKU, "sumber" bisa disimpulkan dari mode
     * saja (kabupaten -> rpjmd, selain itu -> renstra). Sekarang tidak lagi:
     * satu OPD yang sama bisa membekukan dokumen ber-sumber Renstra tahun lalu
     * dan ber-sumber IKU tahun ini. Karena itu sumbernya DICATAT, bukan
     * disimpulkan ulang saat dibaca — snapshot yang dibaca dengan asumsi sumber
     * yang salah menghasilkan tabel kosong tanpa galat apa pun.
     */
    private function sumberBahan(array $bahan, string $mode): string
    {
        $sumber = (string) ($bahan['sumber_type'] ?? '');

        if ($sumber === self::SUMBER_IKU) {
            return self::SUMBER_IKU;
        }

        return $mode === 'kabupaten' ? self::SUMBER_RPJMD : self::SUMBER_RENSTRA;
    }

    /**
     * Kolom `lakip_snapshot_baris` yang memegang kunci baris untuk satu sumber.
     *
     * Kunci itulah yang menautkan baris beku ke baris hidup: id target Renstra,
     * id target RPJMD, atau — untuk IKU — id indikator BERJALAN (bukan id
     * arsipnya, karena arsip berganti tiap revisi sementara realisasi disimpan
     * terhadap indikator berjalan).
     */
    private function kolomKunci(string $sumber): string
    {
        switch ($sumber) {
            case self::SUMBER_IKU:
                return 'iku_indikator_id';

            case self::SUMBER_RPJMD:
                return 'rpjmd_target_id';

            default:
                return 'renstra_target_id';
        }
    }

    /** Sumber yang tercatat pada satu snapshot; jatuh ke tebakan lama bila kosong. */
    private function sumberSnapshot(array $snapshot): string
    {
        $sumber = (string) ($snapshot['source_type'] ?? '');

        if (in_array($sumber, [self::SUMBER_IKU, self::SUMBER_RENSTRA, self::SUMBER_RPJMD], true)) {
            return $sumber;
        }

        // Snapshot lama dibuat sebelum kolom ini ada. Semuanya Renstra/RPJMD:
        // sumber IKU belum mungkin saat itu.
        return $snapshot['mode'] === 'kabupaten' ? self::SUMBER_RPJMD : self::SUMBER_RENSTRA;
    }

    private function tulisVersi(
        string $tahun,
        string $mode,
        int $opdId,
        int $versi,
        array $bahan,
        ?int $userId,
        string $aksi
    ): int {
        $db     = $this->db;
        $now    = date('Y-m-d H:i:s');
        $sumber = $this->sumberBahan($bahan, $mode);
        $kunci  = $this->kolomKunci($sumber);

        $kepala = [
            'tahun'                => $tahun,
            'mode'                 => $mode,
            'opd_id'               => $opdId,
            'versi'                => $versi,
            'label'                => 'LAKIP ' . $tahun . ' versi ' . $versi,
            'status'               => self::STATUS_DRAFT,
            'aktif'                => 1,
            'sumber_iku_revisi_id' => isset($bahan['iku_revisi_id']) ? (int) $bahan['iku_revisi_id'] : null,
            'source_type'          => $sumber,
            'source_version_id'    => isset($bahan['sumber_versi_id']) && (int) $bahan['sumber_versi_id'] > 0
                ? (int) $bahan['sumber_versi_id']
                : null,
            'filter_status'        => ($bahan['filter_status'] ?? '') !== '' ? (string) $bahan['filter_status'] : null,
            'catatan'              => $bahan['catatan'] ?? null,
            'dibuat_oleh'          => $userId,
            'dibuat_pada'          => $now,
            'updated_at'           => $now,
        ];

        if ($aksi === 'disinkronkan') {
            $kepala['disinkronkan_oleh'] = $userId;
            $kepala['disinkronkan_pada'] = $now;
        }

        $db->table('lakip_snapshot')->insert($kepala);
        $snapshotId = (int) $db->insertID();

        $lakipMap   = $bahan['lakipMap'] ?? [];
        $analisMap  = $bahan['analisisMap'] ?? [];
        $jumlah     = 0;

        foreach (($bahan['rows'] ?? []) as $urutan => $r) {
            $targetId = (int) ($r['target_id'] ?? 0);
            $lk       = $lakipMap[$targetId] ?? null;

            $db->table('lakip_snapshot_baris')->insert([
                'snapshot_id'       => $snapshotId,
                'urutan'            => $urutan,
                'sumber'            => $sumber,
                // Hanya kolom kunci milik sumbernya yang diisi; dua lainnya
                // sengaja NULL supaya tidak ada id yang tampak sah di kolom
                // yang artinya lain.
                'renstra_target_id' => $kunci === 'renstra_target_id' ? ($targetId ?: null) : null,
                'rpjmd_target_id'   => $kunci === 'rpjmd_target_id' ? ($targetId ?: null) : null,
                'iku_indikator_id'  => $kunci === 'iku_indikator_id' ? ($targetId ?: null) : null,

                // Jejak ke baris ARSIP revisi: bukan kunci, hanya penelusuran —
                // dari sini terbaca redaksi indikator persis seperti saat itu.
                'iku_revisi_indikator_id' => $sumber === self::SUMBER_IKU && ! empty($r['arsip_id'])
                    ? (int) $r['arsip_id']
                    : null,
                'perubahan_substansial'   => ! empty($r['perubahan_substansial']) ? 1 : 0,
                'source_type'             => $sumber,
                'source_version_id'       => isset($bahan['sumber_versi_id']) && (int) $bahan['sumber_versi_id'] > 0
                    ? (int) $bahan['sumber_versi_id']
                    : null,

                'lakip_id'          => $lk !== null ? (int) $lk['id'] : null,
                'opd_id'            => (int) ($r['opd_id'] ?? $opdId),
                'nama_opd'          => $r['nama_opd'] ?? null,
                'sasaran_id'        => isset($r['sasaran_id']) ? (int) $r['sasaran_id'] : null,
                'sasaran'           => $r['sasaran'] ?? null,
                'indikator_id'      => isset($r['indikator_id']) ? (int) $r['indikator_id'] : null,
                'indikator'         => $r['indikator_sasaran'] ?? null,
                'satuan'            => $r['satuan'] ?? null,
                'jenis_indikator'   => $r['jenis_indikator'] ?? null,
                'target'            => $r['target_tahun_ini'] ?? null,
                'target_hitung'     => $lk['target_hitung'] ?? null,
                'target_lalu'       => $lk['target_lalu'] ?? null,
                'capaian_lalu'      => $lk['capaian_lalu'] ?? null,
                'realisasi'         => $lk['capaian_tahun_ini'] ?? null,
                'capaian_hitung'    => $lk['capaian_hitung'] ?? null,
                'status_lakip'      => $lk['status'] ?? null,
                'created_at'        => $now,
            ]);

            $barisId = (int) $db->insertID();
            $jumlah++;

            foreach (($analisMap[$targetId] ?? []) as $iAnalis => $a) {
                $db->table('lakip_snapshot_analisis')->insert([
                    'snapshot_id'        => $snapshotId,
                    'snapshot_baris_id'  => $barisId,
                    'urutan'             => $iAnalis,
                    'sumber_analisis_id' => isset($a['id']) ? (int) $a['id'] : null,
                    'faktor_pendukung'   => $a['faktor_pendukung'] ?? null,
                    'faktor_penghambat'  => $a['faktor_penghambat'] ?? null,
                    'upaya_peningkatan'  => $a['upaya_peningkatan'] ?? null,
                    'created_at'         => $now,
                ]);
            }
        }

        // Efisiensi program disalin ke tabel snapshot sendiri. Menulis ulang ke
        // `lakip_efisiensi_program` mustahil: tabel itu punya
        // UNIQUE(program_id, tahun, opd_id), sehingga pembekuan kedua untuk
        // tahun yang sama pasti ditolak.
        foreach (($bahan['efisiensiRows'] ?? []) as $urutan => $p) {
            $db->table('lakip_snapshot_program')->insert([
                'snapshot_id' => $snapshotId,
                'urutan'      => $urutan,
                'program_id'  => isset($p['program_id']) ? (int) $p['program_id'] : null,
                'program'     => $p['nama_program'] ?? ($p['program'] ?? null),
                'anggaran'    => $p['anggaran'] ?? 0,
                'realisasi'   => $p['realisasi'] ?? 0,
                'efisiensi'   => $p['efisiensi'] ?? 0,
                'created_at'  => $now,
            ]);
        }

        $db->table('lakip_snapshot')->where('id', $snapshotId)->update(['jumlah_baris' => $jumlah]);

        return $snapshotId;
    }

    /* =========================================================
     * HELPER
     * =======================================================*/

    private function pastikanSiap(): void
    {
        if (! $this->siap()) {
            throw new RuntimeException(
                'Tabel snapshot LAKIP belum tersedia. Jalankan '
                . 'db/update_2026-08-18_iku_revisi_lakip_snapshot.sql terlebih dahulu.'
            );
        }
    }

    /** @return array{0:string,1:string,2:int} */
    private function bacaScope(array $scope): array
    {
        $tahun = (string) ($scope['tahun'] ?? '');
        $mode  = $this->modeSah((string) ($scope['mode'] ?? 'opd'));
        $opd   = $this->opdSah($mode, $scope['opdScope'] ?? null);

        if ($tahun === '') {
            throw new RuntimeException('Tahun LAKIP belum ditentukan.');
        }

        if ($mode === 'opd' && $opd <= 0) {
            // Snapshot lintas OPD tidak punya makna administratif: dokumen
            // LAKIP selalu milik satu unit kerja.
            throw new RuntimeException('Pilih satu OPD terlebih dahulu sebelum menyiapkan snapshot LAKIP.');
        }

        return [$tahun, $mode, $opd];
    }

    private function modeSah(string $mode): string
    {
        return $mode === 'kabupaten' ? 'kabupaten' : 'opd';
    }

    /** Mode kabupaten selalu opd_id 0, sejalan dengan pola tabel monev & addendum. */
    private function opdSah(string $mode, $opdId): int
    {
        return $this->modeSah($mode) === 'kabupaten' ? 0 : (int) ($opdId ?? 0);
    }
}

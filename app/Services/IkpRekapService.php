<?php

namespace App\Services;

use Config\Database;

/**
 * =====================================================================
 * Rekap IKP (Indikator Kinerja Prioritas) per OPD — satu sumber angka
 * =====================================================================
 *
 * Dipakai halaman OPD (AdminOpd\IkpController), lampiran PK, rekap
 * Kabupaten, monitoring Bupati, dan API eKin. MENGAPA satu service: angka
 * triwulan/capaian yang dilihat Bupati, yang dicetak di lampiran PK, dan yang
 * ditarik eKin HARUS sama persis dengan yang dilihat operator OPD. Rumusnya
 * sendiri ada di app/Helpers/ikp_helper.php (murni, teruji); service ini
 * hanya membaca DB lalu memanggil rumus itu.
 *
 * Aturan data:
 *  - IKP hidup di tingkat OPD × periode RPJMD (bukan per tahun PK). Periode
 *    dibaca dari rpjmd_misi.tahun_mulai/tahun_akhir.
 *  - Hanya IKP yang belum dihapus (dihapus_pada IS NULL) yang ikut.
 *  - Status warna memakai ambang dashboard_status_thresholds lewat
 *    ikp_status() — tidak ada rentang angka yang di-hardcode di sini.
 */
class IkpRekapService
{
    private $db;

    /** @var array{awal:int, akhir:int}|null */
    private static ?array $periodeCache = null;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::connect();
        helper('ikp');
    }

    /** Semua tabel inti IKP sudah ada? (instalasi yang belum menjalankan SQL tidak 500). */
    public function siap(): bool
    {
        foreach (['ikp', 'ikp_target_tahunan', 'ikp_bulanan', 'ikp_program_unggulan'] as $t) {
            if (! $this->db->tableExists($t)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Periode RPJMD yang berlaku: baris rpjmd_misi aktif dengan tahun_mulai
     * terbaru. Fallback 2025–2029 bila tabel/isi belum ada.
     *
     * @return array{awal:int, akhir:int}
     */
    public function periodeAktif(): array
    {
        if (self::$periodeCache !== null) {
            return self::$periodeCache;
        }
        $awal  = 2025;
        $akhir = 2029;
        try {
            if ($this->db->tableExists('rpjmd_misi')) {
                $b = $this->db->table('rpjmd_misi')->select('tahun_mulai, tahun_akhir');
                if ($this->db->fieldExists('dihentikan_pada', 'rpjmd_misi')) {
                    $b->where('dihentikan_pada', null);
                }
                $row = $b->orderBy('tahun_mulai', 'DESC')->orderBy('tahun_akhir', 'DESC')->limit(1)->get()->getRowArray();
                if ($row && (int) $row['tahun_mulai'] > 2000 && (int) $row['tahun_akhir'] >= (int) $row['tahun_mulai']) {
                    $awal  = (int) $row['tahun_mulai'];
                    $akhir = (int) $row['tahun_akhir'];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'IkpRekapService::periodeAktif: ' . $e->getMessage());
        }

        return self::$periodeCache = ['awal' => $awal, 'akhir' => $akhir];
    }

    /** Daftar tahun periode aktif, mis. [2025, 2026, 2027, 2028, 2029]. */
    public function tahunPeriode(): array
    {
        $p = $this->periodeAktif();

        return range($p['awal'], $p['akhir']);
    }

    /**
     * IKP aktif sebuah OPD pada periode aktif, beserta referensinya.
     *
     * Kolom tambahan pada tiap baris (selain kolom tabel `ikp`):
     *   pu_nama, pu_warna, pu_ikon, pu_slug   Program Unggulan
     *   satuan_nama, satuan_tipe              master satuan (bila satuan_id)
     *   satuan_label                          satuan_nama ?? satuan_teks
     *   sasaran_pembangunan_nama, misi_nama   referensi Lampiran II
     *   pj_nama                               nama pegawai penanggung jawab
     *
     * Filter: kategori (kunci IkpModel::KATEGORI), pu (id angka atau slug),
     * q (cari di output/indikator/program/outcome), ids (int[]).
     *
     * @return array<int, array<string, mixed>>
     */
    public function daftar(int $opdId, array $filter = []): array
    {
        if ($opdId <= 0 || ! $this->siap()) {
            return [];
        }
        $p = $this->periodeAktif();

        $b = $this->db->table('ikp i')
            ->select('i.*, pu.nama AS pu_nama, pu.warna AS pu_warna, pu.ikon AS pu_ikon, pu.slug AS pu_slug, pu.urutan AS pu_urutan')
            ->select('s.satuan AS satuan_nama, s.tipe AS satuan_tipe')
            ->join('ikp_program_unggulan pu', 'pu.id = i.program_unggulan_id', 'left')
            ->join('satuan s', 's.id = i.satuan_id', 'left')
            ->where('i.opd_id', $opdId)
            ->where('i.dihapus_pada', null)
            ->where('i.periode_awal <=', $p['akhir'])
            ->where('i.periode_akhir >=', $p['awal']);

        if ($this->db->tableExists('ikp_sasaran_pembangunan')) {
            $b->select('sp.nama AS sasaran_pembangunan_nama')
                ->join('ikp_sasaran_pembangunan sp', 'sp.id = i.sasaran_pembangunan_id', 'left');
        }
        if ($this->db->tableExists('rpjmd_misi')) {
            $b->select('m.misi AS misi_nama')->join('rpjmd_misi m', 'm.id = i.rpjmd_misi_id', 'left');
        }
        if ($this->db->tableExists('pegawai')) {
            $b->select('pg.nama_pegawai AS pj_nama')->join('pegawai pg', 'pg.id = i.pj_pegawai_id', 'left');
        }

        $kategori = (string) ($filter['kategori'] ?? '');
        if ($kategori !== '' && array_key_exists($kategori, \App\Models\Ikp\IkpModel::KATEGORI)) {
            $b->where('i.kategori', $kategori);
        }
        $pu = trim((string) ($filter['pu'] ?? ''));
        if ($pu !== '') {
            if ($pu === 'tanpa') {
                $b->where('i.program_unggulan_id', null);
            } elseif (ctype_digit($pu)) {
                $b->where('i.program_unggulan_id', (int) $pu);
            } else {
                $b->where('pu.slug', $pu);
            }
        }
        $q = ikp_rapikan_teks((string) ($filter['q'] ?? ''));
        if ($q !== '') {
            $q = mb_substr($q, 0, 100);
            $b->groupStart()
                ->like('i.output_prioritas', $q)
                ->orLike('i.indikator_outcome', $q)
                ->orLike('i.program_opd', $q)
                ->orLike('i.outcome', $q)
                ->orLike('i.bidang_urusan', $q)
                ->groupEnd();
        }
        if (! empty($filter['ids']) && is_array($filter['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filter['ids']), static fn ($v) => $v > 0));
            $b->whereIn('i.id', $ids === [] ? [0] : $ids);
        }

        $rows = $b->orderBy('i.urutan', 'ASC')->orderBy('i.id', 'ASC')->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['satuan_label'] = trim((string) ($r['satuan_nama'] ?? '')) !== ''
                ? (string) $r['satuan_nama']
                : (string) ($r['satuan_teks'] ?? '');
            foreach (['baseline', 'target_5_tahun'] as $k) {
                $r[$k] = $r[$k] === null ? null : (float) $r[$k];
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Satu IKP (aktif, belum dihapus) dengan kolom referensi seperti daftar().
     * null bila tidak ada / bukan milik OPD tsb.
     */
    public function satu(int $opdId, int $ikpId): ?array
    {
        $rows = $this->daftar($opdId, ['ids' => [$ikpId]]);

        return $rows[0] ?? null;
    }

    /**
     * Target tahunan untuk sekumpulan IKP: [ikp_id => [tahun => ['target'=>?float,'target_teks'=>?string]]].
     *
     * @param int[] $ikpIds
     */
    public function targetTahunan(array $ikpIds): array
    {
        $ikpIds = array_values(array_unique(array_filter(array_map('intval', $ikpIds))));
        if ($ikpIds === [] || ! $this->siap()) {
            return [];
        }
        $out = [];
        $rows = $this->db->table('ikp_target_tahunan')->select('ikp_id, tahun, target, target_teks')
            ->whereIn('ikp_id', $ikpIds)->get()->getResultArray();
        foreach ($rows as $r) {
            $out[(int) $r['ikp_id']][(int) $r['tahun']] = [
                'target'      => $r['target'] === null ? null : (float) $r['target'],
                'target_teks' => $r['target_teks'],
            ];
        }

        return $out;
    }

    /**
     * Baris bulanan sekumpulan IKP pada satu tahun:
     * [ikp_id => [1..12 => ['target','target_teks','realisasi','realisasi_teks','keterangan','bukti_url','realisasi_pada']]].
     * Bulan tanpa baris diisi null-null (selalu lengkap 1..12).
     *
     * @param int[] $ikpIds
     */
    public function bulanan(array $ikpIds, int $tahun): array
    {
        $ikpIds = array_values(array_unique(array_filter(array_map('intval', $ikpIds))));
        $kosong = [];
        for ($m = 1; $m <= 12; $m++) {
            $kosong[$m] = ['target' => null, 'target_teks' => null, 'realisasi' => null, 'realisasi_teks' => null,
                'keterangan' => null, 'bukti_url' => null, 'realisasi_pada' => null];
        }
        $out = [];
        foreach ($ikpIds as $id) {
            $out[$id] = $kosong;
        }
        if ($ikpIds === [] || ! $this->siap()) {
            return $out;
        }
        $rows = $this->db->table('ikp_bulanan')
            ->select('ikp_id, bulan, target, target_teks, realisasi, realisasi_teks, keterangan, bukti_url, realisasi_pada')
            ->whereIn('ikp_id', $ikpIds)->where('tahun', $tahun)
            ->get()->getResultArray();
        foreach ($rows as $r) {
            $m = (int) $r['bulan'];
            if ($m < 1 || $m > 12) {
                continue;
            }
            $out[(int) $r['ikp_id']][$m] = [
                'target'         => $r['target'] === null ? null : (float) $r['target'],
                'target_teks'    => $r['target_teks'],
                'realisasi'      => $r['realisasi'] === null ? null : (float) $r['realisasi'],
                'realisasi_teks' => $r['realisasi_teks'],
                'keterangan'     => $r['keterangan'],
                'bukti_url'      => $r['bukti_url'],
                'realisasi_pada' => $r['realisasi_pada'],
            ];
        }

        return $out;
    }

    /**
     * Rekap lengkap semua IKP aktif sebuah OPD untuk satu tahun.
     *
     * Tiap elemen:
     *  ikp                  baris daftar() (dengan pu_*, satuan_label, …)
     *  target_tahunan       ?float target tahun itu
     *  target_tahunan_teks  ?string
     *  target_periode       [tahun => ?float] seluruh tahun periode
     *  bulan                [1..12 => target, realisasi, keterangan, bukti_url]
     *  triwulan             [1..4 => target, realisasi, capaian (?float %),
     *                                status (kode), status_label, warna, bs, keterangan,
     *                                berjalan (bool), sampai_bulan (?int bulan terisi terakhir)]
     *  tahun_berjalan       persen, status, status_label, warna, bs, sampai_bulan, keterangan
     *  kelengkapan          tahunan (n dari jumlah tahun periode), bulanan (n dari 12),
     *                       tahunan_dari, realisasi (bulan terisi)
     *
     * "status" = kode ambang dashboard (critical|attention|near_target|achieved|
     * exceeded) atau kode non-angka (belum_ada_data|belum_dinilai|belum_valid).
     * "warna" = slug warna ambang (merah|oranye|kuning|hijau|biru|abu).
     *
     * @return array<int, array<string, mixed>>
     */
    public function rekapOpd(int $opdId, int $tahun, array $filter = []): array
    {
        $daftar = $this->daftar($opdId, $filter);
        if ($daftar === []) {
            return [];
        }
        $ids      = array_map(static fn ($r) => (int) $r['id'], $daftar);
        $tahunan  = $this->targetTahunan($ids);
        $bulanan  = $this->bulanan($ids, $tahun);
        $periode  = $this->tahunPeriode();

        $out = [];
        foreach ($daftar as $ikp) {
            $id    = (int) $ikp['id'];
            $out[] = $this->rakit($ikp, $tahun, $tahunan[$id] ?? [], $bulanan[$id] ?? [], $periode);
        }

        return $out;
    }

    /** Rekap satu IKP (bentuk sama dengan elemen rekapOpd). */
    public function rekapSatu(int $opdId, int $ikpId, int $tahun): ?array
    {
        $ikp = $this->satu($opdId, $ikpId);
        if ($ikp === null) {
            return null;
        }
        $tahunan = $this->targetTahunan([$ikpId]);
        $bulanan = $this->bulanan([$ikpId], $tahun);

        return $this->rakit($ikp, $tahun, $tahunan[$ikpId] ?? [], $bulanan[$ikpId] ?? [], $this->tahunPeriode());
    }

    /**
     * @param array<string, mixed>              $ikp
     * @param array<int, array<string, mixed>>  $tahunan [tahun => target,target_teks]
     * @param array<int, array<string, mixed>>  $bulan   [1..12 => …]
     * @param int[]                             $periode
     */
    private function rakit(array $ikp, int $tahun, array $tahunan, array $bulan, array $periode): array
    {
        $metode = (string) ($ikp['metode'] ?? '');

        $target = [];
        $real   = [];
        $isiBulan = [];
        for ($m = 1; $m <= 12; $m++) {
            $b            = $bulan[$m] ?? [];
            $target[$m]   = $b['target'] ?? null;
            $real[$m]     = $b['realisasi'] ?? null;
            $isiBulan[$m] = [
                'target'     => $target[$m],
                'realisasi'  => $real[$m],
                'keterangan' => $b['keterangan'] ?? null,
                'bukti_url'  => $b['bukti_url'] ?? null,
            ];
        }

        // Dibulatkan ke presisi kolom (DECIMAL 20,4) agar 1,2+1,2+1,2 tidak tampil 3,5999999.
        $bulat4   = static fn (?float $v): ?float => $v === null ? null : round($v, 4);
        $twTarget = array_map($bulat4, ikp_nilai_triwulan($target, $metode));
        $twReal   = array_map($bulat4, ikp_nilai_triwulan($real, $metode));
        $triwulan = [];
        for ($q = 1; $q <= 4; $q++) {
            $hasil = ikp_capaian($metode, $target, $real, 3 * $q - 2, 3 * $q);
            $st    = ikp_status($hasil);
            $triwulan[$q] = [
                'target'       => $twTarget[$q],
                'realisasi'    => $twReal[$q],
                'capaian'      => $hasil['status'] === 'calculated' ? $hasil['percentage'] : null,
                'status'       => $st['code'],
                'status_label' => $st['name'],
                'warna'        => $st['color'],
                'bs'           => $st['bs'],
                'keterangan'   => $hasil['calculation_description'],
                'berjalan'     => $hasil['bulan_terakhir'] !== null && $hasil['bulan_terakhir'] < 3 * $q,
                // Bulan terisi terakhir yang dipakai capaian — pada triwulan berjalan
                // capaian TIDAK sama dengan realisasi ÷ target triwulan penuh.
                'sampai_bulan' => $hasil['bulan_terakhir'],
            ];
        }

        $ytd = ikp_capaian($metode, $target, $real, 1, 12);
        $st  = ikp_status($ytd);

        $nTahunan = 0;
        $targetPeriode = [];
        foreach ($periode as $th) {
            $v = $tahunan[$th]['target'] ?? null;
            $targetPeriode[$th] = $v;
            if ($v !== null || trim((string) ($tahunan[$th]['target_teks'] ?? '')) !== '') {
                $nTahunan++;
            }
        }
        $nBulanan = count(array_filter($target, static fn ($v) => $v !== null));
        $nReal    = count(array_filter($real, static fn ($v) => $v !== null));

        return [
            'ikp'                 => $ikp,
            'target_tahunan'      => $tahunan[$tahun]['target'] ?? null,
            'target_tahunan_teks' => $tahunan[$tahun]['target_teks'] ?? null,
            'target_periode'      => $targetPeriode,
            'bulan'               => $isiBulan,
            'target_bulanan_total'=> $bulat4(ikp_nilai_tahunan($target, $metode)),
            'triwulan'            => $triwulan,
            'tahun_berjalan'      => [
                'persen'       => $ytd['status'] === 'calculated' ? $ytd['percentage'] : null,
                'status'       => $st['code'],
                'status_label' => $st['name'],
                'warna'        => $st['color'],
                'bs'           => $st['bs'],
                'kelompok'     => $st['kelompok'],
                'sampai_bulan' => $ytd['bulan_terakhir'],
                'keterangan'   => $ytd['calculation_description'],
            ],
            'kelengkapan'         => [
                'tahunan'      => $nTahunan,
                'tahunan_dari' => count($periode),
                'bulanan'      => $nBulanan,
                'realisasi'    => $nReal,
            ],
        ];
    }

    /**
     * Ringkasan satu OPD × tahun untuk kartu atas halaman & dasbor.
     *
     *  jumlah_ikp          IKP aktif
     *  per_kategori        [kategori => n]
     *  lengkap_breakdown   IKP dengan target tahunan penuh (semua tahun periode) DAN 12 target bulanan
     *  terisi_realisasi    IKP yang sudah punya ≥ 1 realisasi di tahun itu
     *  rata_capaian        rata-rata capaian tahun berjalan (hanya status calculated), ?float
     *  hijau|kuning|merah|abu  jumlah IKP per kelompok warna status tahun berjalan
     *  bulan_terakhir      bulan terisi terbesar di OPD itu (?int)
     *
     * @param array<int, array<string, mixed>>|null $rekap hasil rekapOpd() bila sudah ada (hemat query)
     */
    public function ringkasOpd(int $opdId, int $tahun, ?array $rekap = null): array
    {
        $rekap ??= $this->rekapOpd($opdId, $tahun);

        $hasil = [
            'jumlah_ikp'        => count($rekap),
            'per_kategori'      => array_fill_keys(array_keys(\App\Models\Ikp\IkpModel::KATEGORI), 0),
            'lengkap_breakdown' => 0,
            'terisi_realisasi'  => 0,
            'rata_capaian'      => null,
            'hijau'             => 0,
            'kuning'            => 0,
            'merah'             => 0,
            'abu'               => 0,
            'bulan_terakhir'    => null,
        ];
        $persen = [];
        foreach ($rekap as $r) {
            $k = (string) ($r['ikp']['kategori'] ?? '');
            if (isset($hasil['per_kategori'][$k])) {
                $hasil['per_kategori'][$k]++;
            }
            if ($r['kelengkapan']['tahunan'] >= $r['kelengkapan']['tahunan_dari'] && $r['kelengkapan']['bulanan'] >= 12) {
                $hasil['lengkap_breakdown']++;
            }
            if ($r['kelengkapan']['realisasi'] > 0) {
                $hasil['terisi_realisasi']++;
            }
            if ($r['tahun_berjalan']['persen'] !== null) {
                $persen[] = (float) $r['tahun_berjalan']['persen'];
            }
            $hasil[$r['tahun_berjalan']['kelompok']]++;
            $sb = $r['tahun_berjalan']['sampai_bulan'];
            if ($sb !== null && ($hasil['bulan_terakhir'] === null || $sb > $hasil['bulan_terakhir'])) {
                $hasil['bulan_terakhir'] = (int) $sb;
            }
        }
        if ($persen !== []) {
            $hasil['rata_capaian'] = round(array_sum($persen) / count($persen), 2);
        }

        return $hasil;
    }
}

<?php

namespace App\Controllers\Bupati;

use App\Controllers\BaseController;
use App\Services\KabupatenDashboardService;
use App\Services\OpdDashboardService;

/**
 * DASHBOARD EKSEKUTIF BUPATI — read-only.
 *
 * Memakai QUERY YANG SAMA dengan Dashboard Pengendalian Kinerja Kabupaten
 * (App\Services\KabupatenDashboardService) supaya angka yang dilihat Bupati
 * tidak pernah berbeda dari yang dilihat admin_kab. Yang berbeda hanya:
 *   1. area rute tautan tindak lanjut  -> /bupati (bukan /adminkab);
 *   2. tampilannya lebih ringkas       -> app/Views/bupati/dashboard.php;
 *   3. tidak ada satu pun endpoint tulis.
 *
 * Dua mode dalam satu halaman:
 *   MODE KABUPATEN  : filter Perangkat Daerah = Semua OPD
 *   MODE FOKUS OPD  : satu OPD dipilih; kartu & grafik berganti konteks TANPA
 *                     berpindah ke area /adminopd.
 *
 * KEAMANAN
 *   - grup rute dijaga AuthFilter (auth:bupati,admin);
 *   - ReadOnlyRoleFilter menolak POST/PUT/PATCH/DELETE dari role bupati secara
 *     global (kecuali dashboard/data yang murni pembacaan);
 *   - opd_id dari request SELALU divalidasi ke daftar OPD sah (anti-IDOR);
 *   - endpoint detail hanya mengembalikan data agregat kinerja, tanpa data
 *     pribadi selain nama/jabatan pejabat penanggung jawab yang memang tercetak
 *     pada dokumen PK.
 */
class DashboardController extends BaseController
{
    protected $helpers = ['cascading_label', 'capaian', 'dashboard_status', 'format'];

    private ?KabupatenDashboardService $kab = null;
    private ?OpdDashboardService $opd = null;

    private function kab(): KabupatenDashboardService
    {
        return $this->kab ??= new KabupatenDashboardService();
    }

    /** Layanan dashboard OPD dengan tautan diarahkan ke area /bupati. */
    private function opd(): OpdDashboardService
    {
        if ($this->opd === null) {
            $this->opd = new OpdDashboardService();
            $this->opd->setLinkArea('bupati');
        }

        return $this->opd;
    }

    /* ===================== HALAMAN ===================== */

    public function index()
    {
        $filter = $this->bacaFilter();
        $scope  = $filter['scope'];

        if (!$scope['allowed']) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Dashboard ini hanya untuk pengguna lintas Perangkat Daerah.');
        }

        $data = [
            'title'     => 'Dashboard Eksekutif Bupati',
            'scope'     => $scope,
            'tahun'     => $filter['tahun'],
            'triwulan'  => $filter['triwulan'],
            'misiId'    => $filter['misi_id'],
            'tahunList' => $this->kab()->getAvailableYears(),
            'misiList'  => $this->kab()->misiOptions($filter['tahun']),
            'dash'      => null,
            'fokus'     => null,
        ];

        if ($scope['mode'] === 'fokus_opd') {
            $data['fokus'] = $this->kab()->getOpdFocusDashboard(
                (int) $scope['opd_id'],
                $filter['tahun'],
                $filter['triwulan']
            );
        } else {
            $data['dash'] = $this->kab()->getKabupatenSummary(
                $filter['tahun'],
                $filter['triwulan'],
                $filter['misi_id']
            );
        }

        return view('bupati/dashboard', $data);
    }

    /* ===================== ENDPOINT JSON (baca saja) ===================== */

    /** GET|POST bupati/dashboard/data — payload penuh mode aktif. */
    public function data()
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        try {
            $data = $filter['scope']['mode'] === 'fokus_opd'
                ? $this->kab()->getOpdFocusDashboard((int) $filter['scope']['opd_id'], $filter['tahun'], $filter['triwulan'])
                : $this->kab()->getKabupatenSummary($filter['tahun'], $filter['triwulan'], $filter['misi_id']);

            return $this->sukses($data);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati data: ' . $e->getMessage());

            return $this->gagal('Gagal memuat data dashboard.', 500);
        }
    }

    /** GET bupati/dashboard/pk/(:num) — drill-down indikator PK Bupati. */
    public function pkDetail($id = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }
        if ((int) $id <= 0) {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            // Pencarian sudah dibatasi ke pk.jenis = 'bupati'; indikator PK OPD
            // tidak akan pernah terbuka lewat endpoint ini.
            $detail = $this->kab()->getBupatiIndicatorDetail((int) $id, $filter['tahun'], $filter['triwulan']);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati pk: ' . $e->getMessage());

            return $this->gagal('Gagal memuat detail indikator.', 500);
        }

        return $detail === null
            ? $this->gagal('Indikator PK Bupati tidak ditemukan.', 404)
            : $this->sukses($detail);
    }

    /** GET bupati/dashboard/opd/(:num) — ringkasan satu Perangkat Daerah. */
    public function opdDetail($id = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        $opdId = $this->opdSah((int) $id);
        if ($opdId === null) {
            return $this->gagal('Perangkat Daerah tidak dikenal.', 404);
        }

        try {
            $ringkas = $this->kab()->getOpdFocusDashboard($opdId, $filter['tahun'], $filter['triwulan']);
            $ringkas['url_fokus'] = $this->kab()->urlFokus($opdId, $filter['tahun'], $filter['triwulan']);

            return $this->sukses($ringkas);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati opd: ' . $e->getMessage());

            return $this->gagal('Gagal memuat ringkasan Perangkat Daerah.', 500);
        }
    }

    /**
     * GET bupati/dashboard/indikator/(:num) — detail satu indikator PK
     * Perangkat Daerah (hanya berlaku pada Mode Fokus OPD).
     */
    public function indikatorDetail($id = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        $opdId = $filter['scope']['opd_id'];
        $id    = (int) $id;
        if ($opdId === null || $id <= 0) {
            return $this->gagal('Pilih satu Perangkat Daerah lebih dulu.', 400);
        }

        try {
            $jenis  = $this->opd()->primaryJenis((int) $opdId, $filter['tahun'], 'bupati');
            $detail = $this->opd()->getIndicatorDetail((int) $opdId, $id, $filter['tahun'], $filter['triwulan'], $jenis);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati indikator: ' . $e->getMessage());

            return $this->gagal('Gagal memuat detail indikator.', 500);
        }

        // Pencarian di-scope pk.opd_id = OPD terpilih, jadi indikator OPD lain
        // tidak pernah ditemukan lewat endpoint ini.
        if ($detail === null) {
            return $this->gagal('Indikator tidak ditemukan pada Perangkat Daerah ini.', 404);
        }

        $detail['links'] = $this->opd()->moduleLinks(
            $filter['tahun'],
            (string) ($detail['pk_jenis'] ?? 'jpt'),
            (int) ($detail['opd_id'] ?? 0) ?: null
        );
        $detail['anggaran_teks']   = formatRupiah($detail['anggaran'] ?? 0);
        $detail['realisasi_teks']  = ($detail['realisasi'] ?? null) !== null ? formatRupiah($detail['realisasi']) : null;
        $detail['percentage_teks'] = ($detail['percentage'] ?? null) !== null ? capaianFormatPersen($detail['percentage']) : null;
        unset($detail['skala']);

        return $this->sukses($detail);
    }

    /** GET bupati/dashboard/status-opd/(:segment) — daftar OPD pada satu status. */
    public function statusOpd($code = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        $code = preg_replace('/[^a-z_]/', '', strtolower((string) $code));
        if ($code === '') {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            $statuses = $this->kab()->getOpdStatuses($filter['tahun'], $filter['triwulan'], $filter['misi_id']);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati status-opd: ' . $e->getMessage());

            return $this->gagal('Gagal memuat daftar Perangkat Daerah.', 500);
        }

        $daftar = array_values(array_filter($statuses, static function ($s) use ($code) {
            if ($code === 'semua') {
                return true;
            }
            if ($code === 'belum_update') {
                return (bool) $s['update']['belum_update']
                    && in_array($s['status']['code'], ['belum_valid', 'belum_ada_data'], true);
            }

            return $s['status']['code'] === $code;
        }));

        foreach ($daftar as $k => $s) {
            $daftar[$k]['url_fokus'] = $this->kab()->urlFokus((int) $s['opd_id'], $filter['tahun'], $filter['triwulan']);
        }

        return $this->sukses(['code' => $code, 'opd' => $daftar, 'total' => count($daftar)]);
    }

    /** GET bupati/dashboard/misi/(:num) — kontribusi satu Misi Bupati. */
    public function misiDetail($id = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        $misiId = (int) $id;
        if ($misiId <= 0) {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            $ringkas = $this->kab()->getKabupatenSummary($filter['tahun'], $filter['triwulan'], null);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati misi: ' . $e->getMessage());

            return $this->gagal('Gagal memuat kontribusi misi.', 500);
        }

        foreach ($ringkas['misi']['items'] as $m) {
            if ((int) $m['misi_id'] !== $misiId) {
                continue;
            }
            foreach ($m['opd'] as $k => $o) {
                $m['opd'][$k]['url_fokus'] = $this->kab()->urlFokus((int) $o['opd_id'], $filter['tahun'], $filter['triwulan']);
            }
            $m['indikator_bupati_daftar'] = array_values(array_filter(
                $ringkas['pk_bupati']['indikator'],
                static function ($i) use ($misiId) {
                    foreach ($i['misi'] ?? [] as $x) {
                        if ((int) $x['misi_id'] === $misiId) {
                            return true;
                        }
                    }

                    return false;
                }
            ));

            return $this->sukses($m);
        }

        return $this->gagal('Misi tidak ditemukan pada periode ini.', 404);
    }

    /** GET bupati/dashboard/anggaran-kinerja — sebaran penyerapan vs capaian. */
    public function anggaranKinerja()
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        try {
            $statuses = $this->kab()->getOpdStatuses($filter['tahun'], $filter['triwulan'], $filter['misi_id']);

            return $this->sukses($this->kab()->getBudgetPerformanceComparison($statuses));
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard bupati anggaran-kinerja: ' . $e->getMessage());

            return $this->gagal('Gagal memuat analisis anggaran dan kinerja.', 500);
        }
    }

    /* ===================== UTILITAS ===================== */

    /** opd_id yang benar-benar ada pada daftar OPD sah, atau null. */
    private function opdSah(int $opdId): ?int
    {
        $sah = array_map('intval', array_column($this->kab()->opdOptions(), 'id'));

        return in_array($opdId, $sah, true) ? $opdId : null;
    }

    /**
     * Baca & bersihkan filter global sekaligus tentukan mode.
     *
     * @return array{scope: array<string, mixed>, tahun: int, triwulan: int, misi_id: int|null}
     */
    private function bacaFilter(): array
    {
        $req = $this->request;

        // opd_id TIDAK pernah dipercaya mentah: resolveScope() hanya menerima id
        // yang ada pada daftar OPD sah (lihat KabupatenDashboardService).
        $opdRaw   = $req->getGet('opd_id') ?? $req->getPost('opd_id');
        $opdMinta = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
        $scope    = $this->kab()->resolveScope($opdMinta);

        $tahun = (int) ($req->getGet('tahun') ?? $req->getPost('tahun'));
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        $triwulan = (int) ($req->getGet('triwulan') ?? $req->getPost('triwulan'));
        if ($triwulan < 1 || $triwulan > 4) {
            $triwulan = dash_triwulan_berjalan($tahun);
        }

        $misiRaw = $req->getGet('misi_id') ?? $req->getPost('misi_id');
        $misiId  = ((int) $misiRaw) ?: null;
        if ($misiId !== null) {
            $sah = array_map('intval', array_column($this->kab()->misiOptions($tahun), 'id'));
            if (!in_array($misiId, $sah, true)) {
                $misiId = null;
            }
        }

        return ['scope' => $scope, 'tahun' => $tahun, 'triwulan' => $triwulan, 'misi_id' => $misiId];
    }

    /** @param array<string, mixed> $data */
    private function sukses(array $data)
    {
        return $this->response->setJSON([
            'status'   => 'success',
            'data'     => $data,
            'csrfHash' => function_exists('csrf_hash') ? csrf_hash() : null,
        ]);
    }

    private function gagal(string $pesan, int $kode)
    {
        return $this->response->setStatusCode($kode)->setJSON([
            'status'   => 'error',
            'message'  => $pesan,
            'csrfHash' => function_exists('csrf_hash') ? csrf_hash() : null,
        ]);
    }
}

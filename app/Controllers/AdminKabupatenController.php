<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\KabupatenDashboardService;

class AdminKabupatenController extends BaseController
{
    /**
     * Dashboard Pengendalian Kinerja tingkat Kabupaten.
     *
     * Dua mode dalam satu halaman: MODE KABUPATEN (filter OPD = semua) dan
     * MODE FOKUS OPD (satu OPD dipilih — kartu & grafik berganti konteks tanpa
     * berpindah halaman dan tetap di area /adminkab).
     *
     * Seluruh query ada di App\Services\KabupatenDashboardService. Controller
     * hanya membaca filter, memaksakan batas akses, lalu meneruskan hasilnya.
     */
    protected $helpers = ['cascading_label', 'capaian', 'dashboard_status', 'format'];

    private ?KabupatenDashboardService $dash = null;

    private function svc(): KabupatenDashboardService
    {
        return $this->dash ??= new KabupatenDashboardService();
    }

    public function dashboard()
    {
        $filter = $this->bacaFilter();
        $scope  = $filter['scope'];

        if (!$scope['allowed']) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Dashboard kabupaten hanya untuk pengguna lintas Perangkat Daerah.');
        }

        $data = [
            'title'     => 'Dashboard Kinerja Kabupaten',
            'scope'     => $scope,
            'tahun'     => $filter['tahun'],
            'triwulan'  => $filter['triwulan'],
            'misiId'    => $filter['misi_id'],
            'tahunList' => $this->svc()->getAvailableYears(),
            'misiList'  => $this->svc()->misiOptions($filter['tahun']),
            'dash'      => null,
            'fokus'     => null,
        ];

        if ($scope['mode'] === 'fokus_opd') {
            $data['fokus'] = $this->svc()->getOpdFocusDashboard(
                (int) $scope['opd_id'],
                $filter['tahun'],
                $filter['triwulan']
            );
        } else {
            $data['dash'] = $this->svc()->getKabupatenSummary(
                $filter['tahun'],
                $filter['triwulan'],
                $filter['misi_id']
            );
        }

        return view('adminKabupaten/dashboard', $data);
    }

    /** POST/GET adminkab/dashboard/data — payload penuh mode aktif. */
    public function getDashboardData()
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        try {
            $data = $filter['scope']['mode'] === 'fokus_opd'
                ? $this->svc()->getOpdFocusDashboard((int) $filter['scope']['opd_id'], $filter['tahun'], $filter['triwulan'])
                : $this->svc()->getKabupatenSummary($filter['tahun'], $filter['triwulan'], $filter['misi_id']);

            return $this->sukses($data);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard kabupaten data: ' . $e->getMessage());

            return $this->gagal('Gagal memuat data dashboard.', 500);
        }
    }

    /** GET adminkab/dashboard/pk-bupati/(:num) — drill-down indikator PK Bupati. */
    public function pkBupatiDetail($id = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }
        if ((int) $id <= 0) {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            $detail = $this->svc()->getBupatiIndicatorDetail((int) $id, $filter['tahun'], $filter['triwulan']);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard kabupaten pk-bupati: ' . $e->getMessage());

            return $this->gagal('Gagal memuat detail indikator.', 500);
        }

        // Pencarian sudah dibatasi ke pk.jenis = 'bupati'; indikator PK OPD
        // tidak akan pernah terbuka lewat endpoint ini.
        return $detail === null
            ? $this->gagal('Indikator PK Bupati tidak ditemukan.', 404)
            : $this->sukses($detail);
    }

    /** GET adminkab/dashboard/opd/(:num) — ringkasan satu OPD untuk drawer. */
    public function opdDetail($id = null)
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        $opdId = (int) $id;
        $sah   = array_map('intval', array_column($this->svc()->opdOptions(), 'id'));
        if (!in_array($opdId, $sah, true)) {
            return $this->gagal('Perangkat Daerah tidak dikenal.', 404);
        }

        try {
            $ringkas = $this->svc()->getOpdFocusDashboard($opdId, $filter['tahun'], $filter['triwulan']);
            $ringkas['url_fokus'] = $this->svc()->urlFokus($opdId, $filter['tahun'], $filter['triwulan']);

            return $this->sukses($ringkas);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard kabupaten opd: ' . $e->getMessage());

            return $this->gagal('Gagal memuat ringkasan Perangkat Daerah.', 500);
        }
    }

    /** GET adminkab/dashboard/status-opd/(:segment) — daftar OPD pada satu status. */
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
            $statuses = $this->svc()->getOpdStatuses($filter['tahun'], $filter['triwulan'], $filter['misi_id']);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard kabupaten status-opd: ' . $e->getMessage());

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
            $daftar[$k]['url_fokus'] = $this->svc()->urlFokus((int) $s['opd_id'], $filter['tahun'], $filter['triwulan']);
        }

        return $this->sukses(['code' => $code, 'opd' => $daftar, 'total' => count($daftar)]);
    }

    /** GET adminkab/dashboard/misi/(:num) — kontribusi satu Misi Bupati. */
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
            $ringkas = $this->svc()->getKabupatenSummary($filter['tahun'], $filter['triwulan'], null);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard kabupaten misi: ' . $e->getMessage());

            return $this->gagal('Gagal memuat kontribusi misi.', 500);
        }

        foreach ($ringkas['misi']['items'] as $m) {
            if ((int) $m['misi_id'] !== $misiId) {
                continue;
            }
            foreach ($m['opd'] as $k => $o) {
                $m['opd'][$k]['url_fokus'] = $this->svc()->urlFokus((int) $o['opd_id'], $filter['tahun'], $filter['triwulan']);
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

    /** GET adminkab/dashboard/anggaran-kinerja — data scatter anggaran vs kinerja. */
    public function anggaranKinerja()
    {
        $filter = $this->bacaFilter();
        if (!$filter['scope']['allowed']) {
            return $this->gagal('Tidak berhak.', 403);
        }

        try {
            $statuses = $this->svc()->getOpdStatuses($filter['tahun'], $filter['triwulan'], $filter['misi_id']);

            return $this->sukses($this->svc()->getBudgetPerformanceComparison($statuses));
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard kabupaten anggaran-kinerja: ' . $e->getMessage());

            return $this->gagal('Gagal memuat analisis anggaran dan kinerja.', 500);
        }
    }

    /* ===================== UTILITAS ===================== */

    /**
     * Baca & bersihkan filter global sekaligus tentukan mode.
     *
     * @return array{scope: array<string, mixed>, tahun: int, triwulan: int, misi_id: int|null}
     */
    private function bacaFilter(): array
    {
        $req = $this->request;

        $opdRaw   = $req->getGet('opd_id') ?? $req->getPost('opd_id');
        $opdMinta = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
        $scope    = $this->svc()->resolveScope($opdMinta);

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
            $sah = array_map('intval', array_column($this->svc()->misiOptions($tahun), 'id'));
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

    public function pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/pk_bupati');
    }

    public function tambah_pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/tambah_pk_bupati');
    }

    public function edit_pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/edit_pk_bupati');
    }

    public function save_pk_bupati()
    {
        return redirect()->to(base_url('adminkab/pk_bupati'));
    }

    public function evaluasi_inspektorat()
    {
        return view('adminKabupaten/evaluasi/evaluasi_inspektorat');
    }

    public function lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/lakip_kabupaten');
    }

    public function tambah_lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/tambah_lakip_kabupaten');
    }

    public function edit_lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/edit_lakip_kabupaten');
    }

    public function save_lakip_kabupaten()
    {
        return redirect()->to(base_url('adminkab/lakip_kabupaten'));
    }

    public function tentang_kami()
    {
        return view('adminKabupaten/tentang_kami');
    }

    public function edit_tentang_kami()
    {
        return view('adminKabupaten/edit_tentang_kami');
    }

    public function save_tentang_kami()
    {
        return redirect()->to(base_url('adminkab/tentang_kami'));
    }
}

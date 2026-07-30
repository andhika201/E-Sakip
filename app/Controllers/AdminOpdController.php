<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\OpdDashboardService;

/**
 * Dashboard Pengendalian Kinerja Perangkat Daerah.
 *
 * Halaman utamanya dirender penuh di server (MVC klasik, tanpa SPA); AJAX hanya
 * dipakai untuk isi drawer & grafik triwulanan. Seluruh query ada di
 * App\Services\OpdDashboardService — controller ini hanya membaca filter,
 * memaksakan batas akses, lalu menyerahkan hasilnya ke view/JSON.
 *
 * BATAS AKSES: opd_id untuk admin_opd & admin_kecamatan SELALU dari sesi;
 * parameter opd_id hanya dihormati untuk role 'admin' (super admin) dan
 * divalidasi ke daftar OPD yang sah. Semua endpoint JSON memakai lingkup yang
 * sama sehingga tidak bisa dipakai mengintip data OPD lain (IDOR).
 */
class AdminOpdController extends BaseController
{
    protected $helpers = ['cascading_label', 'capaian', 'dashboard_status', 'format'];

    protected $db;
    protected OpdDashboardService $dashboard;

    public function __construct()
    {
        $this->db        = \Config\Database::connect();
        $this->dashboard = new OpdDashboardService();
    }

    /* ===================== HALAMAN DASHBOARD ===================== */

    public function index()
    {
        $filter = $this->bacaFilter();
        $scope  = $filter['scope'];

        $data = [
            'title'      => 'Dashboard Kinerja - ' . ($scope['opd_nama'] ?? 'Perangkat Daerah'),
            'scope'      => $scope,
            'tahun'      => $filter['tahun'],
            'triwulan'   => $filter['triwulan'],
            'pejabatId'  => $filter['pejabat_id'],
            'tahunList'  => $this->dashboard->getAvailableYears($scope['opd_id']),
            'dash'       => null,
            'pejabatList' => [],
        ];

        if ($scope['opd_id'] === null) {
            // Super admin belum memilih OPD, atau user OPD tanpa opd_id di sesi.
            return view('adminOpd/dashboard', $data);
        }

        $data['dash'] = $this->dashboard->getSummary(
            $scope['opd_id'],
            $filter['tahun'],
            $filter['triwulan'],
            ['pejabat_id' => $filter['pejabat_id']]
        );
        $data['pejabatList'] = $this->dashboard->pejabatOptions(
            $scope['opd_id'],
            $filter['tahun'],
            $data['dash']['context']['jenis']
        );

        return view('adminOpd/dashboard', $data);
    }

    /* ===================== ENDPOINT JSON ===================== */

    /** POST adminopd/dashboard/data — seluruh ringkasan dalam satu payload. */
    public function data()
    {
        $filter = $this->bacaFilter();
        if ($filter['scope']['opd_id'] === null) {
            return $this->gagal('Perangkat Daerah belum ditentukan.', 400);
        }

        try {
            return $this->sukses($this->dashboard->getSummary(
                $filter['scope']['opd_id'],
                $filter['tahun'],
                $filter['triwulan'],
                ['pejabat_id' => $filter['pejabat_id']]
            ));
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard OPD data: ' . $e->getMessage());

            return $this->gagal('Gagal memuat data dashboard.', 500);
        }
    }

    /** GET adminopd/dashboard/indicator/(:num) — detail satu indikator (drawer & grafik). */
    public function indicator($id = null)
    {
        $filter = $this->bacaFilter();
        $opdId  = $filter['scope']['opd_id'];
        $id     = (int) $id;

        if ($opdId === null || $id <= 0) {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            $jenis  = $this->dashboard->primaryJenis($opdId, $filter['tahun'], (string) session()->get('role'));
            $detail = $this->dashboard->getIndicatorDetail($opdId, $id, $filter['tahun'], $filter['triwulan'], $jenis);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard OPD indicator: ' . $e->getMessage());

            return $this->gagal('Gagal memuat detail indikator.', 500);
        }

        // Indikator milik OPD lain tidak pernah ditemukan karena pencariannya
        // sudah di-scope pk.opd_id = OPD pengguna.
        if ($detail === null) {
            return $this->gagal('Indikator tidak ditemukan pada Perangkat Daerah ini.', 404);
        }

        return $this->sukses($this->siapkanDetail($detail, $filter));
    }

    /** GET adminopd/dashboard/status/(:segment) — indikator pada satu status. */
    public function status($code = null)
    {
        $filter = $this->bacaFilter();
        $opdId  = $filter['scope']['opd_id'];
        $code   = preg_replace('/[^a-z_]/', '', strtolower((string) $code));

        if ($opdId === null || $code === '') {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            $ringkas = $this->dashboard->getSummary(
                $opdId,
                $filter['tahun'],
                $filter['triwulan'],
                ['pejabat_id' => $filter['pejabat_id']]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard OPD status: ' . $e->getMessage());

            return $this->gagal('Gagal memuat daftar indikator.', 500);
        }

        $daftar = array_values(array_filter($ringkas['indicators'], static function ($i) use ($code) {
            if ($code === 'belum_valid') {
                return !$i['is_valid'];
            }
            if ($code === 'semua') {
                return true;
            }
            if ($code === 'belum_terverifikasi') {
                return $i['is_valid'] && ($i['verification']['code'] ?? '') !== 'verified';
            }

            return $i['is_valid'] && $i['status']['code'] === $code;
        }));

        return $this->sukses([
            'code'      => $code,
            'indicators' => $daftar,
            'total'     => count($daftar),
        ]);
    }

    /** GET adminopd/dashboard/program/(:num) — rincian satu program & realisasinya. */
    public function program($id = null)
    {
        $filter = $this->bacaFilter();
        $opdId  = $filter['scope']['opd_id'];
        $id     = (int) $id;

        if ($opdId === null || $id <= 0) {
            return $this->gagal('Parameter tidak sah.', 400);
        }

        try {
            $jenis  = $this->dashboard->primaryJenis($opdId, $filter['tahun'], (string) session()->get('role'));
            $detail = $this->dashboard->getProgramDetail($opdId, $id, $filter['tahun'], $filter['triwulan'], $jenis);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard OPD program: ' . $e->getMessage());

            return $this->gagal('Gagal memuat rincian program.', 500);
        }

        if ($detail === null) {
            return $this->gagal('Program tidak terkait Perjanjian Kinerja Perangkat Daerah ini.', 404);
        }

        return $this->sukses($detail);
    }

    /* ===================== HALAMAN LAIN (tetap) ===================== */

    /**
     * Tentang Kami — pakai view bersama (universal untuk semua role auth).
     */
    public function tentang_kami()
    {
        return view('adminKabupaten/tentang_kami');
    }

    /**
     * Evaluasi Kinerja: Evaluasi Inspektorat (stub "Segera Hadir") — versi OPD/Kecamatan.
     */
    public function evaluasi_inspektorat()
    {
        return view('adminOpd/evaluasi/evaluasi_inspektorat');
    }

    /* ===================== UTILITAS ===================== */

    /**
     * Baca & bersihkan seluruh filter global sekaligus tentukan lingkup OPD.
     *
     * @return array{scope: array<string, mixed>, tahun: int, triwulan: int, pejabat_id: int|null}
     */
    private function bacaFilter(): array
    {
        $req = $this->request;

        // opd_id dari request HANYA dipertimbangkan untuk super admin; untuk role
        // OPD nilainya diabaikan sepenuhnya oleh resolveScope().
        $opdRaw   = $req->getGet('opd_id') ?? $req->getPost('opd_id');
        $opdMinta = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
        $scope    = $this->dashboard->resolveScope($opdMinta);

        $tahunRaw = $req->getGet('tahun') ?? $req->getPost('tahun');
        $tahun    = (int) $tahunRaw;
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        $twRaw    = $req->getGet('triwulan') ?? $req->getPost('triwulan');
        $triwulan = (int) $twRaw;
        if ($triwulan < 1 || $triwulan > 4) {
            $triwulan = dash_triwulan_berjalan($tahun);
        }

        $pejabatRaw = $req->getGet('pejabat_id') ?? $req->getPost('pejabat_id');
        $pejabatId  = ((int) $pejabatRaw) ?: null;

        return [
            'scope'      => $scope,
            'tahun'      => $tahun,
            'triwulan'   => $triwulan,
            'pejabat_id' => $pejabatId,
        ];
    }

    /**
     * Rapikan detail indikator untuk drawer: tambahkan tautan modul tujuan
     * (dengan tahun dipertahankan) dan buang data mentah yang tidak dipakai.
     *
     * @param array<string, mixed> $detail
     * @param array<string, mixed> $filter
     *
     * @return array<string, mixed>
     */
    private function siapkanDetail(array $detail, array $filter): array
    {
        $detail['links'] = $this->dashboard->moduleLinks(
            (int) $filter['tahun'],
            (string) ($detail['pk_jenis'] ?? 'jpt'),
            (int) ($detail['opd_id'] ?? 0) ?: null
        );
        $detail['anggaran_teks']  = formatRupiah($detail['anggaran']);
        $detail['realisasi_teks'] = $detail['realisasi'] !== null ? formatRupiah($detail['realisasi']) : null;
        $detail['percentage_teks'] = $detail['percentage'] !== null ? capaianFormatPersen($detail['percentage']) : null;
        unset($detail['skala']);

        return $detail;
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

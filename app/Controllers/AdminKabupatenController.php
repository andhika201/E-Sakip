<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use CodeIgniter\HTTP\ResponseInterface;

class AdminKabupatenController extends BaseController
{
    protected $dashboardModel;
    
    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    public function index()
    {
        try {
            // Get comprehensive dashboard data
            $data = [
                'dashboard_data' => $this->dashboardModel->getDashboardData(),
                'summary_stats' => $this->dashboardModel->getSummaryStats()
            ];
            
            return view('adminKabupaten/dashboard', $data);
            
        } catch (\Exception $e) {
            // Log error and return view with default data
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            
            // Return view with default/fallback data
            $data = [
                'dashboard_data' => [
                    'rpjmd' => ['draft' => 0, 'selesai' => 0],
                    'rkpd' => ['draft' => 0, 'selesai' => 0],
                    'renstra' => ['draft' => 0, 'selesai' => 0],
                    'renja' => ['draft' => 0, 'selesai' => 0],
                    'iku' => ['draft' => 0, 'selesai' => 0],
                    'lakip_kabupaten' => ['draft' => 0, 'selesai' => 0],
                    'lakip_opd' => ['draft' => 0, 'selesai' => 0],
                    'opd_list' => [],
                    'available_years' => [date('Y')]
                ],
                'summary_stats' => [
                    'total_rpjmd' => 0,
                    'total_rkpd' => 0,
                    'total_renstra' => 0,
                    'total_renja' => 0,
                    'total_opd' => 0,
                    'active_year' => date('Y')
                ],
                'error_message' => 'Terjadi kesalahan saat memuat data dashboard.'
            ];

            return view('adminKabupaten/dashboard', $data);
        }
    }

    /**
     * AJAX endpoint untuk mendapatkan data dashboard berdasarkan filter
     */
    public function getDashboardData()
    {
        $opdId = $this->request->getPost('opd_id');
        $year = $this->request->getPost('year');
        
        try {
            if ($opdId || $year) {
                // Get filtered data
                $filteredData = $this->dashboardModel->getDashboardDataByOpdAndYear($opdId, $year);
                
                return $this->response->setJSON([
                    'status' => 'success',
                    'data' => $filteredData
                ]);
            } else {
                // Get all data
                $allData = $this->dashboardModel->getDashboardData();
                
                return $this->response->setJSON([
                    'status' => 'success',
                    'data' => $allData
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Dashboard AJAX error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memuat data.'
            ]);
        }
    }

    /**
     * Method untuk mendapatkan statistik ringkas (untuk widget atau API)
     */
    public function getStats()
    {
        try {
            $stats = $this->dashboardModel->getSummaryStats();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Stats error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal memuat statistik.'
            ]);
        }
    }

    // PK Bupati Methods
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

    // Lakip Kabupaten Methods
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

    // Tentang Kami Methods
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

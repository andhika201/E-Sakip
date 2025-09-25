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
            $dashboardData = $this->dashboardModel->getDashboardData();
            
            // Get summary statistics
            $summaryStats = $this->dashboardModel->getSummaryStats();
            
            $data = [
                'dashboard_data' => $dashboardData,
                'summary_stats' => $summaryStats,
                'title' => 'Dashboard'
            ];
            
            return view('adminKabupaten/dashboard', $data);
            
        } catch (\Exception $e) {
            // Handle error
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            
            $data = [
                'dashboard_data' => $this->getDefaultDashboardData(),
                'summary_stats' => $this->getDefaultSummaryStats(),
                'error_message' => 'Terjadi kesalahan saat memuat data dashboard.',
                'title' => 'Dashboard'
            ];
            
            return view('adminKabupaten/dashboard', $data);
        }
    }

    /**
     * AJAX endpoint untuk mendapatkan data dashboard berdasarkan filter
     */
    public function getDashboardData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $opdId = $this->request->getPost('opd_id');
            $year = $this->request->getPost('year');

            // Validate input - require at least one filter
            if (empty($opdId) && empty($year)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Silakan pilih Unit Kerja atau Tahun untuk filter'
                ]);
            }

            // Get filtered data with default 0 values
            $filteredData = $this->dashboardModel->getDashboardDataByOpdAndYear($opdId, $year);
            
            // Ensure all required data structure with default 0 values
            $defaultStructure = [
                'renstra' => ['draft' => 0, 'selesai' => 0],
                'renja' => ['draft' => 0, 'selesai' => 0],
                'iku_opd' => ['draft' => 0, 'selesai' => 0],
                'lakip_opd' => ['draft' => 0, 'selesai' => 0]
            ];
            
            // Merge with filtered data to ensure complete structure
            $filteredData = array_merge($defaultStructure, $filteredData);

            // Get OPD name if specified
            $opdName = 'Semua Unit Kerja';
            if ($opdId) {
                $opdData = $this->dashboardModel->getOpdById($opdId);
                $opdName = $opdData ? $opdData['nama_opd'] : 'Unit Kerja Tidak Dikenal';
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data berhasil difilter',
                'data' => $filteredData,
                'filter_info' => [
                    'opd_name' => $opdName,
                    'year' => $year ?: 'Semua Tahun'
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Dashboard filter error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memfilter data'
            ]);
        }
    }

    /**
     * Get default dashboard data when there's an error
     */
    private function getDefaultDashboardData()
    {
        return [
            'rpjmd' => ['draft' => 0, 'selesai' => 0],
            'rkpd' => ['draft' => 0, 'selesai' => 0],
            'renstra' => ['draft' => 0, 'selesai' => 0],
            'renja' => ['draft' => 0, 'selesai' => 0],
            'iku_opd' => ['draft' => 0, 'selesai' => 0],
            'lakip_kabupaten' => ['draft' => 0, 'selesai' => 0],
            'lakip_opd' => ['draft' => 0, 'selesai' => 0],
            'opd_list' => [],
            'available_years' => [date('Y')]
        ];
    }

    /**
     * Get default summary stats when there's an error
     */
    private function getDefaultSummaryStats()
    {
        return [
            'total_rpjmd' => 0,
            'total_renstra' => 0,
            'total_renja' => 0,
            'total_iku_opd' => 0,
            'total_lakip_opd' => 0,
            'total_opd' => 0,
            'active_year' => date('Y')
        ];
    }
    

    public function tentangKami()
    {

        return view('adminKabupaten/tentang_kami');
    }
}
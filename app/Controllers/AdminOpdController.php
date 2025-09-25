<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\DashboardModel;

class AdminOpdController extends BaseController
{
    protected $dashboardModel;
    
    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    public function index()
    {

         // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        
        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        try {

            $year = $this->request->getPost('year');

            // Get comprehensive dashboard data
            $dashboardData = $this->dashboardModel->getDashboardDataByOpdAndYear($opdId, $year);
            
            $data = [
                'dashboard_data' => $dashboardData,
                'title' => 'Dashboard'
            ];

            return view('adminOpd/dashboard', $data);
            
        } catch (\Exception $e) {
            // Handle error gracefully
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            
            $data = [
                'dashboard_data' => $this->getDefaultDashboardData(),
                'error_message' => 'Terjadi kesalahan saat memuat data dashboard.',
                'title' => 'Dashboard'
            ];
            
            return view('adminOpd/dashboard', $data);
        }
    }


    /**
     * Get default dashboard data when there's an error
     */
    private function getDefaultDashboardData()
    {
        return [
            'renstra' => ['draft' => 0, 'selesai' => 0],
            'renja' => ['draft' => 0, 'selesai' => 0],
            'iku_opd' => ['draft' => 0, 'selesai' => 0],
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
            'total_renstra' => 0,
            'total_renja' => 0,
            'total_iku' => 0,
            'total_lakip_opd' => 0,
            'total_opd' => 0,
            'active_year' => date('Y')
        ];
    }
}

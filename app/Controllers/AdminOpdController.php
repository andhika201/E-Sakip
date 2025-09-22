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
            
            return view('adminOpd/dashboard', $data);
            
        } catch (\Exception $e) {
            // Handle error gracefully
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            
            $data = [
                'dashboard_data' => $this->getDefaultDashboardData(),
                'summary_stats' => $this->getDefaultSummaryStats(),
                'error_message' => 'Terjadi kesalahan saat memuat data dashboard.',
                'title' => 'Dashboard'
            ];
            
            return view('adminOpd/dashboard', $data);
        }
    }
}

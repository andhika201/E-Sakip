<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AdminOpdController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $dashboardModel = new \App\Models\DashboardOpdModel();
        $stats = $dashboardModel->getAllStats();

        return view('adminOpd/dashboard', [
            'title'        => 'Dashboard e-SAKIP - Admin OPD',
            'renstraStats' => $stats['renstraStats'],
            'renjaStats'   => $stats['renjaStats'],
            'ikuStats'     => $stats['ikuStats'],
            'lakipStats'   => $stats['lakipStats'],
            'pkStats'      => $stats['pkStats'],
        ]);
    }
}

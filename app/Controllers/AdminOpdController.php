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
}

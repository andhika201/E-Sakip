<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DashboardKabupatenModel;

class AdminKabupatenController extends BaseController
{
    public function dashboard()
    {
        $dash = new DashboardKabupatenModel();

        $dashboard_data = $dash->getDashboardData(null, null);
        $summary_stats = $dash->getSummaryStats();

        return view('adminKabupaten/dashboard', [
            'title' => 'Dashboard e-SAKIP',
            'dashboard_data' => $dashboard_data,
            'summary_stats' => $summary_stats,
        ]);
    }

    public function getDashboardData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Bad request.',
                'csrfHash' => function_exists('csrf_hash') ? csrf_hash() : null,
            ]);
        }

        try {
            $opdId = $this->request->getPost('opd_id');
            $year = $this->request->getPost('year');

            $dash = new DashboardKabupatenModel();
            $data = $dash->getDashboardData($opdId, $year);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $data,
                'csrfHash' => function_exists('csrf_hash') ? csrf_hash() : null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard AJAX error: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Server error saat memuat data.',
                'csrfHash' => function_exists('csrf_hash') ? csrf_hash() : null,
            ]);
        }
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

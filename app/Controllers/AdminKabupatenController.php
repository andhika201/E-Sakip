<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use CodeIgniter\HTTP\ResponseInterface;

class AdminKabupatenController extends BaseController
{
    // protected $dashboardModel;
    
    // public function __construct()
    // {
    //     $this->dashboardModel = new DashboardModel();
    // }

    public function index()
    {
        return view('adminKabupaten/dashboard', $data);
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

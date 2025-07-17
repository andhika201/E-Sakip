<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class AdminOpd extends BaseController
{
    public function index()
    {
        return view('adminOpd/dashboard');
    }

    public function renstra()
    {
        return view('adminOpd/renstra/renstra');
    }
    public function tambah_renstra()
    {
        return view('adminOpd/renstra/tambah_renstra');
    }
    public function edit_renstra()
    {
        return view('adminOpd/renstra/edit_renstra');
    }


    public function renja()
    {
        return view('adminOpd/renja/renja');
    }
    public function tambah_renja()
    {
        return view('adminOpd/renja/tambah_renja');
    }
    public function edit_renja()
    {
        return view('adminOpd/renja/edit_renja');
    }

    // IKU Methods
    public function iku()
    {
        return view('adminOpd/iku/iku');
    }

    public function tambah_iku()
    {
        return view('adminOpd/iku/tambah_iku');
    }

    public function edit_iku()
    {
        return view('adminOpd/iku/edit_iku');
    }

    public function save_iku()
    {
        return redirect()->to(base_url('adminOpd/iku'));
    }
    

    public function pk_jpt()
    {
        return view('adminOpd/pk_jpt/pk_jpt');
    }

    public function pk_administrator()
    {
        return view('adminOpd/pk_administrator/pk_administrator');
    }

    public function pk_pengawas()
    {
        return view('adminOpd/pk_pengawas/pk_pengawas');
    }

    public function lakip_kabupaten()
    {
        return view('adminOpd/lakip_kabupaten/lakip_kabupaten');
    }
}

<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class AdminKabupatenController extends BaseController
{
    public function index()
    {
        return view('adminKabupaten/dashboard');
    }

    // RKT Methods
    public function rkt()
    {
        return view('adminKabupaten/rkt/rkt');
    }

    public function tambah_rkt()
    {
        // Load model untuk mendapatkan data sasaran RPJMD
        $rpjmdModel = new \App\Models\RpjmdModel();
        $sasaranRpjmd = $rpjmdModel->getAllSasaranWithPeriode();
        
        $data = [
            'sasaran_rpjmd' => $sasaranRpjmd
        ];
        
        return view('adminKabupaten/rkt/tambah_rkt', $data);
    }

    public function edit_rkt()
    {
        return view('adminKabupaten/rkt/edit_rkt');
    }

    public function save_rkt()
    {
        $rktModel = new \App\Models\RktModel();
        
        // Validasi input
        $validation = \Config\Services::validation();
        $validation->setRules([
            'rpjmd_sasaran_id' => 'required|integer',
            'sasaran_rkt.*.sasaran' => 'required',
            'sasaran_rkt.*.indikator_sasaran.*.indikator_sasaran' => 'required',
            'sasaran_rkt.*.indikator_sasaran.*.satuan' => 'required',
            'sasaran_rkt.*.indikator_sasaran.*.tahun' => 'required|integer',
            'sasaran_rkt.*.indikator_sasaran.*.target' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        // Ambil data dari form
        $data = [
            'rpjmd_sasaran_id' => $this->request->getPost('rpjmd_sasaran_id'),
            'sasaran_rkt' => $this->request->getPost('sasaran_rkt')
        ];

        // Simpan data
        if ($rktModel->saveRktWithIndikator($data)) {
            return redirect()->to(base_url('adminkab/rkt'))
                ->with('success', 'Data RKT berhasil disimpan');
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data RKT');
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

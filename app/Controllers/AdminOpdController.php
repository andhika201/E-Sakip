<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class AdminOpdController extends BaseController
{
    public function index()
    {
        return view('adminOpd/dashboard');
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
        $pkJptData = [
            [
                'tahun' => '2023',
                'indikator' => 'Angka Partisipasi Sekolah',
                'satuan' => '%',
                'target' => '98',
                'capaian_sebelumnya' => '95',
                'target_tahun_ini' => '98',
                'capaian_tahun_ini' => '96',
            ],
            [
                'tahun' => '2023',
                'indikator' => 'Angka Putus Sekolah',
                'satuan' => '%',
                'target' => '2',
                'capaian_sebelumnya' => '3',
                'target_tahun_ini' => '2',
                'capaian_tahun_ini' => '2.5',
            ]
        ];

        return view('adminOpd/pk_jpt', [
            'pkJptData' => $pkJptData
        ]);
    }

    public function pk_admin()
    {
        return view('adminOpd/pk_admin/pk-admin');
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

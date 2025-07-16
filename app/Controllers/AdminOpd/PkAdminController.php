<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PegawaiModel;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;

class PkAdminController extends BaseController
{

    protected $pegawaiModel;
    protected $programPkModel;
    protected $opdModel;


    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->programPkModel = new ProgramPkModel();
        $this->opdModel = new OpdModel();

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

        return view('adminOpd/pk_admin/pk-admin');
    }

    public function tambah(){

         // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        
        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Load the view for adding a new PK Admin
        // $pegawai = $this->pegawaiModel->getAllPegawai();
        $program = $this->programPkModel->getAllPrograms();

        $pegawaiOpd = $this->pegawaiModel->where('opd_id', $opdId)->findAll();
        
        // Pass the data to the view
        $data = [
            // 'pegawai' => $pegawai,
            'pegawaiOpd' => $pegawaiOpd,
            'program' => $program,
            'title' => 'Tambah PK Admin',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminOpd/pk_admin/tambah_pk_admin', $data);
    }
}

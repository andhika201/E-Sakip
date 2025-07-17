<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PegawaiModel;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
// use Mpdf\Mpdf;

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

    public function cetak()
    {
        // Gunakan path absolut ke file image
        $logoPath = FCPATH . 'assets/images/logo.png'; // FCPATH adalah path ke folder public
        
        // Atau alternatif lain:
        // $logoPath = realpath(FCPATH . 'assets/images/logo.png');
        
        // Pastikan file exists
        if (!file_exists($logoPath)) {
            // Handle jika file tidak ada
            log_message('error', 'Logo file tidak ditemukan: ' . $logoPath);
            $logoPath = ''; // atau gunakan placeholder image
        }
        
        $data = [
            'title' => 'Perjanjian Kinerja 2025',
            'logo_url' => $logoPath, // Gunakan path absolut
            'nama_pihak_kesatu' => 'MOUDY ARY NAZOLLA, S.STP., MH',
            'jabatan_pihak_kesatu' => 'KEPALA DINAS KOMUNIKASI DAN INFORMATIKA',
            'nama_pihak_kedua' => 'RIYANTO PAMUNGKAS',
            'jabatan_pihak_kedua' => 'BUPATI',
            'tahun' => '2025',
        ];

        $html = view('adminOpd/pk_admin/cetak', $data);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'defaultPageMode' => 'none',
            'useSubstitutions' => true, 
            'mirrorMargins' => true,
            'tempDir' => sys_get_temp_dir(),
            'curlTimeout' => 30,
            'curlExecutionTimeout' => 30,
            'allowedTags' => ['img'],
        ]);

        $css = '
            img {
                width: 60px;
                height: auto;
            }
        ';
        
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        $mpdf->WriteHTML($html);

        $this->response->setHeader('Content-Type', 'application/pdf');
        return $mpdf->Output('Perjanjian-Kinerja-2025.pdf', 'I');
    }

}

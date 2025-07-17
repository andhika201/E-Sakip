<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PegawaiModel;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
use App\Models\PkModel;
// use Mpdf\Mpdf;

class PkAdminController extends BaseController
{

    protected $pegawaiModel;
    protected $pkModel;
    protected $programPkModel;
    protected $opdModel;


    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->pkModel = new PkModel();
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

        // Get PK data filtered by user's OPD
        $pkData = $this->pkModel->getpkForTable($opdId);
        
        // Get current OPD info
        $currentOpd = $this->opdModel->find($opdId);
        if (!$currentOpd) {
            return redirect()->to('/login')->with('error', 'Data OPD tidak ditemukan');
        }

        // Load the view for PK Admin
        // Set title based on current OPD
        $titleSuffix = $currentOpd['nama_opd'];  // Group data by period
        
        $data = [
            'pk_data' => $pkData,
            'current_opd' => $currentOpd,
            'title' => 'Perjanjian Kinerja - ' . $titleSuffix
        ];

        return view('adminOpd/pk_admin/pk-admin', $data);
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
            'opd_user' => $opdId,
            'pegawaiOpd' => $pegawaiOpd,
            'program' => $program,
            'title' => 'Tambah PK Admin',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminOpd/pk_admin/tambah_pk_admin', $data);
    }

    public function save()
    {   
        $validation = \Config\Services::validation();

        $rules = [
            'pegawai_1_id' => 'required|numeric',
            'pegawai_2_id' => 'required|numeric',
        ];

        // Set rule ke validator
        $validation->setRules($rules);

        // Jalankan validasi dasar dulu
        if (!$validation->run($this->request->getPost())) {
            return redirect()->back()->withInput()->with('validation', $validation);
        }

        $programs = $this->request->getPost('program');
        if (is_array($programs)) {
            foreach ($programs as $i => $program) {
                if (empty($program['program_id'])) {
                    $errors["program[$i][program_id]"] = 'Program wajib dipilih.';
                }
                if (empty($program['anggaran'])) {
                    $errors["program[$i][anggaran]"] = 'Anggaran wajib diisi.';
                }
            }
        }

        $sasaranPk = $this->request->getPost('sasaran_pk');
        if (is_array($sasaranPk)) {
            foreach ($sasaranPk as $i => $sasaran) {
                if (empty($sasaran['sasaran'])) {
                    $errors["sasaran_pk[$i][sasaran]"] = 'Sasaran wajib diisi.';
                }
                if (isset($sasaran['indikator']) && is_array($sasaran['indikator'])) {
                    foreach ($sasaran['indikator'] as $j => $indikator) {
                        if (empty($indikator['indikator'])) {
                            $errors["sasaran_pk[$i][indikator][$j][indikator]"] = 'Indikator wajib diisi.';
                        }
                        if (empty($indikator['target'])) {
                            $errors["sasaran_pk[$i][indikator][$j][target]"] = 'Target wajib diisi.';
                        }
                    }
                }
            }
        }

        //Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');

        // Validate OPD ID exists
        if (!$opdId) {
            throw new \Exception('OPD ID tidak ditemukan dalam session. Silakan login ulang.');
        }

        // Get the form data
        $data = $this->request->getPost();
        $now = date('Y-m-d'); // Use Y-m-d format for date

        // Prepare data for saving
        $saveData = [
            'opd_id' => $opdId,
            'jenis'   => $data['jenis'],
            'pihak_1' => $data['pegawai_1_id'],
            'pihak_2' => $data['pegawai_2_id'],
            'tanggal' => $now,
            'sasaran_pk' => [],
            'program' => [],
        ];

        // Proses Sasaran dan Indikator
        if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
            foreach ($data['sasaran_pk'] as $sasaranItem) {
                $sasaranData = [
                    'sasaran' => $sasaranItem['sasaran'] ?? '',
                    'indikator' => [],
                ];

                if (isset($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                    foreach ($sasaranItem['indikator'] as $indikatorItem) {
                        $sasaranData['indikator'][] = [
                            'indikator' => $indikatorItem['indikator'] ?? '',
                            'target' => $indikatorItem['target'] ?? '',
                        ];
                    }
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }

            // Proses Program dan Anggaran
            if (isset($data['program']) && is_array($data['program'])) {
                foreach ($data['program'] as $programItem) {
                    $saveData['program'][] = [
                        'program_id' => $programItem['program_id'] ?? null,
                        'anggaran'   => $programItem['anggaran'] ?? 0,
                    ];
                }
            }
        }

        // Save to database
        $result = $this->pkModel->saveCompletePk($saveData);

        // Save the data to the database
        if ($result) {
            return redirect()->to('/adminopd/pk_admin')->with('success', 'Data PK Admin berhasil disimpan');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data PK Admin');
        }
    }

    public function cetak($id = null)
    {
        if (!$id) {
            return redirect()->to('/adminopd/pk_admin')->with('error', 'ID PK Admin tidak ditemukan');
        }

        // Gunakan path absolut ke file image
        $logoPath = FCPATH . 'assets/images/logo.png'; // FCPATH adalah path ke folder public
        
        $data = [
            'title' => 'Perjanjian Kinerja',
            'logo_url' => $logoPath, // Gunakan path absolut
            'nama_p1' => 'MOUDY ARY NAZOLLA, S.STP., MH',
            'jabatan_p1' => 'KEPALA DINAS KOMUNIKASI DAN INFORMATIKA',
            'nama_p2' => 'RIYANTO PAMUNGKAS',
            'jabatan_p2' => 'BUPATI',
            'nama_opd' => 'DINAS KOMUNIKASI DAN INFORMATIKA',
            'tahun' => '2025',
        ];

        $html_1 = view('adminOpd/pk_admin/cetak', $data);
        $html_2 = view('adminOpd/pk_admin/cetak-L', $data);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'FOLIO',
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
                width: 70px;
                height: auto;
            }
        ';

        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        $mpdf->WriteHTML($html_1);

        // Tambah halaman landscape
        $mpdf->AddPage('L');

        $mpdf->WriteHTML($html_2);

        $this->response->setHeader('Content-Type', 'application/pdf');
        return $mpdf->Output('Perjanjian-Kinerja-' . $data['tahun'] . '.pdf', 'I');
    }

}

<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PegawaiModel;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
use App\Models\Opd\PkModel;
use Mpdf\Mpdf;

class PkOpdController extends BaseController
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
        $pkData = $this->pkModel->getCompletePkByOpdId($opdId);
        
        // Get current OPD info
        $currentOpd = $this->opdModel->find($opdId);

        if (!$currentOpd) {
            return redirect()->to('/login')->with('error', 'Data OPD tidak ditemukan');
        }

        // Load the view for PK OPD
        // Set title based on current OPD
        $titleSuffix = $currentOpd['singkatan'];  // Group data by period
        
        $data = [
            'pk_data' => $pkData,
            'current_opd' => $currentOpd,
            'title' => 'Perjanjian Kinerja - ' . $titleSuffix
        ];

        // dd($data);

        return view('adminOpd/pk_opd/pk_opd', $data);
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

        return view('adminOpd/pk_opd/tambah_pk_opd', $data);
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
                    ];
                }
            }
        }
        
        // Save to database
        $success = $this->pkModel->saveCompletePk($saveData);

        // Save the data to the database
        if ($success) {
            return redirect()->to('/adminopd/pk_opd')->with('success', 'Data PK Admin berhasil disimpan');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data PK Admin');
        }
    }

    public function cetak($id = null)
    {   
        helper('format');
        
        if (!$id) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'ID PK OPD tidak ditemukan');
        }
        // Ambil data lengkap PK dari model
        $data = $this->pkModel->getPkById($id);
    
        if (!$data) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Data PK tidak ditemukan');
        }

        // Logo path (harus absolut)
        $data['logo_url'] = FCPATH . 'assets/images/logo.png';
        $tahun = date('Y', strtotime($data['tanggal']));
        
        // Buat halaman 1 dan 2
        $html_1 = view('adminOpd/pk_opd/cetak', $data);
        $html_2 = view('adminOpd/pk_opd/cetak-L', $data);

        // Init mpdf
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'FOLIO',
            'default_font_size' => 12,
            'mirrorMargins' => true,
            'tempDir' => sys_get_temp_dir(),
        ]);

        // Tambahkan CSS jika perlu
        $css = '
            img { width: 70px; height: auto; }
        ';

        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        $mpdf->WriteHTML($html_1);
        $mpdf->AddPage('L'); // Halaman landscape
        $mpdf->WriteHTML($html_2);

        $this->response->setHeader('Content-Type', 'application/pdf');
        return $mpdf->Output('Perjanjian-Kinerja-' . $data['jenis'] . '-' . $tahun . '.pdf', 'I');
    }

    public function edit($id = null)
    {
        // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        
        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!$id) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'ID PK OPD tidak ditemukan');
        }

        // Get the existing PK data
        $pkData = $this->pkModel->getCompletePkById($id);

        if (!$pkData) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Data PK tidak ditemukan');
        }

        // Ensure the PK belongs to the logged-in user's OPD
        if ($pkData['opd_id'] != $opdId) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Anda tidak memiliki izin untuk mengedit PK ini');
        }

        // Load the view for editing the PK OPD
        $program = $this->programPkModel->getAllPrograms();
        $pegawaiOpd = $this->pegawaiModel->where('opd_id', $opdId)->findAll();

        $data = [
            'pk' => $pkData,
            'program' => $program,
            'pegawaiOpd' => $pegawaiOpd,
            'title' => 'Edit PK OPD',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminOpd/pk_opd/edit_pk_opd', $data);
    }

    public function update()
    {
        $id = $this->request->getPost('pk_id');
        
        if (!$id) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'ID PK tidak ditemukan');
        }

        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Check if PK exists and belongs to user's OPD
        $existingPk = $this->pkModel->find($id);
        if (!$existingPk || $existingPk['opd_id'] != $opdId) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Data PK tidak ditemukan atau tidak memiliki izin akses');
        }

        // Validation
        $validation = \Config\Services::validation();

        $rules = [
            'jenis' => 'required|in_list[jpt,administrator,pengawas]',
            'pegawai_1_id' => 'required|numeric',
            'pegawai_2_id' => 'required|numeric',
        ];

        $validation->setRules($rules);

        if (!$validation->run($this->request->getPost())) {
            return redirect()->back()->withInput()->with('validation', $validation);
        }

        // Custom validation for programs and sasaran
        $errors = [];
        $programs = $this->request->getPost('program');
        $sasaranPk = $this->request->getPost('sasaran_pk');

        if (is_array($programs)) {
            foreach ($programs as $i => $program) {
                if (empty($program['program_id'])) {
                    $errors["program[$i][program_id]"] = 'Program wajib dipilih.';
                }
            }
        }

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

        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('validation_errors', $errors);
        }

        // Get form data
        $data = $this->request->getPost();
        
        // Prepare data for updating (dengan ID tracking seperti Renja)
        $updateData = [
            'jenis'   => $data['jenis'],
            'pihak_1' => $data['pegawai_1_id'],
            'pihak_2' => $data['pegawai_2_id'],
            'tanggal' => $existingPk['tanggal'], // Keep original date
            'sasaran_pk' => [],
            'program' => [],
        ];

        // Process Sasaran and Indikator dengan ID tracking
        if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
            foreach ($data['sasaran_pk'] as $sasaranItem) {
                $sasaranData = [
                    'sasaran' => $sasaranItem['sasaran'] ?? '',
                    'indikator' => [],
                ];

                // Include ID jika ada (untuk existing record)
                if (isset($sasaranItem['id']) && !empty($sasaranItem['id'])) {
                    $sasaranData['id'] = $sasaranItem['id'];
                }

                if (isset($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                    foreach ($sasaranItem['indikator'] as $indikatorItem) {
                        $indikatorData = [
                            'indikator' => $indikatorItem['indikator'] ?? '',
                            'target' => $indikatorItem['target'] ?? '',
                        ];

                        // Include ID jika ada (untuk existing record)
                        if (isset($indikatorItem['id']) && !empty($indikatorItem['id'])) {
                            $indikatorData['id'] = $indikatorItem['id'];
                        }

                        $sasaranData['indikator'][] = $indikatorData;
                    }
                }

                $updateData['sasaran_pk'][] = $sasaranData;
            }
        }

        // Process Programs dengan ID tracking
        if (isset($data['program']) && is_array($data['program'])) {
            foreach ($data['program'] as $programItem) {
                $programData = [
                    'program_id' => $programItem['program_id'] ?? null,
                ];

                // Include ID jika ada (untuk existing record)
                if (isset($programItem['id']) && !empty($programItem['id'])) {
                    $programData['id'] = $programItem['id'];
                }

                $updateData['program'][] = $programData;
            }
        }

        // Update in database
        try {
            $success = $this->pkModel->updateCompletePk($id, $updateData);
            
            if ($success) {
                return redirect()->to('/adminopd/pk_opd')->with('success', 'Data PK berhasil diperbarui');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data PK');
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if (!$id) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'ID PK OPD tidak ditemukan');
        }

        // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');

        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get the existing PK data
        $pkData = $this->pkModel->find($id);

        if (!$pkData) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Data PK tidak ditemukan');
        }

        // Ensure the PK belongs to the logged-in user's OPD
        if ($pkData['opd_id'] != $opdId) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Anda tidak memiliki izin untuk menghapus PK ini');
        }

        // Proceed to delete the PK
        try {
            $this->pkModel->delete($id);
            return redirect()->to('/adminopd/pk_opd')->with('success', 'Data PK berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->to('/adminopd/pk_opd')->with('error', 'Gagal menghapus data PK: ' . $e->getMessage());
        }
    }
}

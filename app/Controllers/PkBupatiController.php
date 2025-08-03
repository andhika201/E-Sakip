<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RpjmdModel;
use App\Models\ProgramPkModel;
use App\Models\PkBupatiModel;
use Mpdf\Mpdf;

class PkBupatiController extends BaseController
{
    protected $pkBupatiModel;
    protected $programPkModel;
    protected $misiRpjmd;

    public function __construct()
    {
        $this->misiRpjmd = new RpjmdModel();
        $this->pkBupatiModel = new PkBupatiModel();
        $this->programPkModel = new ProgramPkModel();
    }

    public function index()
    {
        // Get filter parameter
        $tahun = $this->request->getGet('tahun');
        
        // Get available years from database
        $availableYears = $this->pkBupatiModel->getAvailableYears();
        
        // Get PK data dengan filter tahun jika ada
        if ($tahun) {
            $pkData = $this->pkBupatiModel->getCompletePkByYear($tahun);
        } else {
            $pkData = $this->pkBupatiModel->getCompletePk();
        }
        
        // Load the view for PK Bupati
        $data = [
            'pk_data' => $pkData,
            'available_years' => $availableYears,
            'selected_year' => $tahun,
            'title' => 'Perjanjian Kinerja - Bupati'
        ];

        return view('adminKabupaten/pk_bupati/pk_bupati', $data);
    }

    public function tambah(){
        // Load the view for adding a new PK Bupati
        $program = $this->programPkModel->getAllPrograms();
        $misiRpjmd = $this->misiRpjmd->getAllMisiByStatus('selesai');

        // Pass the data to the view
        $data = [
            'program' => $program,
            'misiRpjmd' => $misiRpjmd,
            'title' => 'Tambah PK Bupati',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminKabupaten/pk_bupati/tambah_pk_bupati', $data);
    }

    public function save()
    {   
        $validation = \Config\Services::validation();

        $rules = [
            'nama_bupati' => 'required|min_length[3]|max_length[100]',
            'tanggal' => 'required|valid_date',
            'rpjmd_misi_id' => 'required|integer',
        ];

        // Set rule ke validator
        $validation->setRules($rules);

        // Jalankan validasi dasar dulu
        if (!$validation->run($this->request->getPost())) {
            return redirect()->back()->withInput()->with('validation', $validation);
        }

        $errors = [];

        $programs = $this->request->getPost('program');
        if (is_array($programs)) {
            foreach ($programs as $i => $program) {
                if (empty($program['program_id'])) {
                    $errors["program[$i][program_id]"] = 'Program wajib dipilih.';
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

        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        // Get the form data
        $data = $this->request->getPost();
        
        // Prepare data for saving sesuai ERD
        $saveData = [
            'nama'   => $data['nama_bupati'],
            'tanggal' => $data['tanggal'],
            'rpjmd_misi_id' => $data['rpjmd_misi_id'],
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
        }

        // Proses Program
        if (isset($data['program']) && is_array($data['program'])) {
            foreach ($data['program'] as $programItem) {
                $saveData['program'][] = [
                    'program_id' => $programItem['program_id'] ?? null,
                ];
            }
        }

        // Save to database
        $success = $this->pkBupatiModel->saveCompletePk($saveData);

        if ($success) {
            return redirect()->to('/adminkab/pk_bupati')->with('success', 'Data PK Bupati berhasil disimpan');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data PK Bupati');
        }
    }

    public function edit($id = null)
    {
        if (!$id) {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'ID PK Bupati tidak ditemukan');
        }

        $pkData = $this->pkBupatiModel->getPkById($id);
        if (!$pkData) {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'Data PK Bupati tidak ditemukan');
        }

        $program = $this->programPkModel->getAllPrograms();
        $misiRpjmd = $this->misiRpjmd->getAllMisiByStatus('selesai');
        
        $data = [
            'pk_data' => $pkData,
            'program' => $program,
            'misiRpjmd' => $misiRpjmd,
            'title' => 'Edit PK Bupati',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminKabupaten/pk_bupati/edit_pk_bupati', $data);
    }

    public function update($id = null)
    {
        if (!$id) {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'ID PK Bupati tidak ditemukan');
        }

        $validation = \Config\Services::validation();

        $rules = [
            'nama_bupati' => 'required|min_length[3]|max_length[100]',
            'tanggal' => 'required|valid_date',
            'rpjmd_misi_id' => 'required|integer',
        ];

        $validation->setRules($rules);

        if (!$validation->run($this->request->getPost())) {
            return redirect()->back()->withInput()->with('validation', $validation);
        }

        $errors = [];

        $programs = $this->request->getPost('program');
        if (is_array($programs)) {
            foreach ($programs as $i => $program) {
                if (empty($program['program_id'])) {
                    $errors["program[$i][program_id]"] = 'Program wajib dipilih.';
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

        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = $this->request->getPost();
        
        $saveData = [
            'nama'   => $data['nama_bupati'],
            'tanggal' => $data['tanggal'],
            'rpjmd_misi_id' => $data['rpjmd_misi_id'] ?? null,
            'sasaran_pk' => [],
            'program' => [],
        ];

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
        }

        if (isset($data['program']) && is_array($data['program'])) {
            foreach ($data['program'] as $programItem) {
                $saveData['program'][] = [
                    'program_id' => $programItem['program_id'] ?? null,
                ];
            }
        }

        $success = $this->pkBupatiModel->updateCompletePk($id, $saveData);

        if ($success) {
            return redirect()->to('/adminkab/pk_bupati')->with('success', 'Data PK Bupati berhasil diperbarui');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data PK Bupati');
        }
    }

    public function delete($id = null)
    {
        if (!$id) {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'ID PK Bupati tidak ditemukan');
        }

        $success = $this->pkBupatiModel->deleteCompletePk($id);

        if ($success) {
            return redirect()->to('/adminkab/pk_bupati')->with('success', 'Data PK Bupati berhasil dihapus');
        } else {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'Gagal menghapus data PK Bupati');
        }
    }

    public function cetak($id = null)
    {   
        helper('format');
        
        if (!$id) {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'ID PK Bupati tidak ditemukan');
        }
        // Ambil data lengkap PK dari model
        $data = $this->pkBupatiModel->getPkById($id);
    
        if (!$data) {
            return redirect()->to('/adminkab/pk_bupati')->with('error', 'Data PK tidak ditemukan');
        }

        // Logo path (harus absolut)
        $data['logo_url'] = FCPATH . 'assets/images/logo.png';
        $tahun = date('Y', strtotime($data['tanggal']));
        
        // Buat halaman 1 dan 2
        $html_1 = view('adminKabupaten/pk_bupati/cetak', $data);
        $html_2 = view('adminKabupaten/pk_bupati/cetak-L', $data);

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
        return $mpdf->Output('Perjanjian-Kinerja-Bupati-' . $tahun . '.pdf', 'I');
    }
}

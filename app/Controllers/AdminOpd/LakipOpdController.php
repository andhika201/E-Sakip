<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\LakipOpdModel;
use App\Models\Opd\IkuModel;
use App\Models\Opd\RenstraModel;

use App\Models\OpdModel;

class LakipOpdController extends BaseController
{
    protected $lakipModel;
    protected $ikuModel;
    protected $renstraModel;

    protected $opdModel;

    public function __construct()
    {
        $this->lakipModel = new LakipOpdModel();
        $this->opdModel = new OpdModel();
        $this->ikuModel = new IkuModel();
        $this->renstraModel = new RenstraModel();
        helper(['form', 'url']);

    }

    /**
     * Display list of LAKIP OPD
     */
    public function index()
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        $tahun = $this->request->getGet('tahun'); // dari filter tahun

        $role = $session->get('role');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // Build query based on OPD
        $query = $this->lakipModel->where('opd_id', $opdId);

        // Apply filters
        if (!empty($tahun)) {
            $query->where('YEAR(tanggal_laporan)', $tahun);
        }

        $renstraData = $this->renstraModel->getAllSasaranWithIndikatorAndTarget($opdId, $tahun);


        $lakip_data = ($role === 'admin_kab')
            ? $this->lakipModel->getRPJMDWithPrograms()
            : $this->lakipModel->getRenstra($opdId);

        // Get available years
        $availableYears = $this->lakipModel->getAvailableYears();

        // Get OPD info
        $opdInfo = $this->opdModel->find($opdId);

        // dd($lakip_data);

        $data = [
            'title' => 'LAKIP OPD - ' . ($opdInfo['nama_opd'] ?? 'Unknown'),
            'availableYears' => $availableYears,
            'opdInfo' => $opdInfo,
            'renstraData' => $renstraData,
            'role' => $role,
            'lakip' => $lakip_data,
            'filters' => ['tahun' => $tahun],

        ];

        return view('adminOpd/lakip/lakip', $data);
    }

    /**
     * Show form to create new LAKIP
     */
    public function tambah($indikatorId = null)
    {
        $opdId = session()->get('opd_id');
        $role = session()->get('role');
        $tahun = date('Y');


        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }


        // Ambil data indikator berdasarkan $indikatorId
        $indikator = null;
        if ($indikatorId) {
            $db = \Config\Database::connect();
            if ($role == 'admin_kab') {
                // Ambil dari tabel RPJMD
                $indikator = $db->table('rpjmd_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            } else {
                // Default admin_opd ambil dari tabel Renstra
                $indikator = $db->table('renstra_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            }
        }

        // Ambil daftar target berdasarkan indikator ini
        $targetList = $db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();

        $opdInfo = $this->opdModel->find($opdId);

        $data = [
            'title' => 'Tambah LAKIP OPD',
            'role' => $role,
            'indikator' => $indikator,
            'opdInfo' => $opdInfo,
            'targetList' => $targetList,

            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/lakip/tambah_lakip', $data);
    }

    /**
     * Store new LAKIP
     */
    public function save()
    {

        $session = session();
        $role = $session->get('role');

        // Ambil data dari form
        $renstraIndikatorId = $this->request->getPost('renstra_indikator_sasaran_id');
        $targetPrev = $this->request->getPost('target_lalu');
        $capaianPrev = $this->request->getPost('capaian_lalu');
        $capaianNow = $this->request->getPost('capaian_tahun_ini');

        // Validasi dasar
        if (empty($renstraIndikatorId)) {
            return redirect()->back()->with('error', 'Data indikator tidak valid.');
        }

        // Siapkan data untuk disimpan ke tabel `lakip`
        if ($role === 'admin_kab') {
            $data = [
                'renstra_indikator_id' => null,
                'rpjmd_indikator_id' => $renstraIndikatorId,
                'target_lalu' => $targetPrev ?? null,
                'capaian_lalu' => $capaianPrev ?? null,
                'capaian_tahun_ini' => $capaianNow ?? null,
            ];
        } else {
            $data = [
                'renstra_indikator_id' => $renstraIndikatorId,
                'rpjmd_indikator_id' => null,
                'target_lalu' => $targetPrev ?? null,
                'capaian_lalu' => $capaianPrev ?? null,
                'capaian_tahun_ini' => $capaianNow ?? null,
            ];
        }

        $this->lakipModel->insert($data);
        return redirect()->to('/adminopd/lakip')->with('success', 'Data berhasil disimpan.');

        
    }


    /**
     * Show form to edit LAKIP
     */
    public function edit($id)
    {
        $opdId = session()->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->where('id', $id)
            ->where('opd_id', $opdId)
            ->first();

        if (!$lakip) {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('error', 'LAKIP tidak ditemukan');
        }

        $opdInfo = $this->opdModel->find($opdId);

        $data = [
            'title' => 'Edit LAKIP OPD',
            'lakip' => $lakip,
            'opdInfo' => $opdInfo,
            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/lakip_opd/edit_lakip', $data);
    }

    /**
     * Update LAKIP
     */
    public function update($id)
    {
        $opdId = session()->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->where('id', $id)
            ->where('opd_id', $opdId)
            ->first();

        if (!$lakip) {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('error', 'LAKIP tidak ditemukan');
        }

        // Validation rules sesuai dengan struktur database
        $validationRules = [
            'judul_laporan' => [
                'rules' => 'required|min_length[3]|max_length[255]',
                'errors' => [
                    'required' => 'Judul laporan harus diisi',
                    'min_length' => 'Judul laporan minimal 3 karakter',
                    'max_length' => 'Judul laporan maksimal 255 karakter'
                ]
            ],
            'tanggal_laporan' => [
                'rules' => 'permit_empty|valid_date',
                'errors' => [
                    'valid_date' => 'Format tanggal tidak valid'
                ]
            ]
        ];

        // Add file validation only if file is uploaded
        $file = $this->request->getFile('file_laporan');
        if ($file && $file->isValid()) {
            $validationRules['file_laporan'] = [
                'rules' => 'max_size[file_laporan,51200]|ext_in[file_laporan,pdf,doc,docx,xls,xlsx]',
                'errors' => [
                    'max_size' => 'Ukuran file maksimal 50MB',
                    'ext_in' => 'File harus berformat PDF, DOC, DOCX, XLS, atau XLSX'
                ]
            ];
        }

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Prepare update data sesuai struktur database
        $updateData = [
            'judul' => $this->request->getPost('judul_laporan'), // Map form field to database field
            'tanggal_laporan' => $this->request->getPost('tanggal_laporan') ?: null
        ];

        try {
            // Update with file upload handling
            $result = $this->lakipModel->updateLakipWithFile($id, $updateData, $file);

            if ($result) {
                return redirect()->to('/adminopd/lakip_opd')
                    ->with('success', 'LAKIP berhasil diperbarui');
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal memperbarui data');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Delete LAKIP
     */
    public function delete($id)
    {
        $opdId = session()->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->where('id', $id)
            ->where('opd_id', $opdId)
            ->first();

        if (!$lakip) {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('error', 'Data LAKIP tidak ditemukan');
        }

        if ($this->lakipModel->deleteLakip($id)) {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('success', 'LAKIP berhasil dihapus');
        } else {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('error', 'Gagal menghapus LAKIP');
        }
    }
}

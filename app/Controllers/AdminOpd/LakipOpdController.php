<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\LakipOpdModel;
use App\Models\OpdModel;

class LakipOpdController extends BaseController
{
    protected $lakipModel;
    protected $opdModel;

    public function __construct()
    {
        $this->lakipModel = new LakipOpdModel();
        $this->opdModel = new OpdModel();
        helper(['form', 'url']);
    }

    /**
     * Display list of LAKIP OPD
     */
    public function index()
    {
        // Get OPD ID from session
        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // Get filters from request
        $tahun = $this->request->getVar('tahun');
        $status = $this->request->getVar('status');

        // Build query based on OPD
        $query = $this->lakipModel->where('opd_id', $opdId);

        // Apply filters
        if (!empty($tahun)) {
            $query->where('YEAR(tanggal_laporan)', $tahun);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $lakip = $query->orderBy('created_at', 'DESC')->findAll();

        // Get available years
        $availableYears = $this->lakipModel->getAvailableYearsByOpd($opdId);

        // Get OPD info
        $opdInfo = $this->opdModel->find($opdId);

        $data = [
            'title' => 'LAKIP OPD - ' . ($opdInfo['nama_opd'] ?? 'Unknown'),
            'lakips' => $lakip,
            'availableYears' => $availableYears,
            'filters' => [
                'tahun' => $tahun,
                'status' => $status
            ],
            'opdInfo' => $opdInfo
        ];

        return view('adminOpd/lakip_opd/lakip_opd', $data);
    }

    /**
     * Show form to create new LAKIP
     */
    public function tambah()
    {
        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $opdInfo = $this->opdModel->find($opdId);

        $data = [
            'title' => 'Tambah LAKIP OPD',
            'opdInfo' => $opdInfo,
            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/lakip_opd/tambah_lakip', $data);
    }

    /**
     * Store new LAKIP
     */
    public function save()
    {
        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
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
            ],
            'file_laporan' => [
                'rules' => 'uploaded[file_laporan]|max_size[file_laporan,51200]|ext_in[file_laporan,pdf,doc,docx,xls,xlsx]',
                'errors' => [
                    'uploaded' => 'File harus diupload',
                    'max_size' => 'Ukuran file maksimal 50MB',
                    'ext_in' => 'File harus berformat PDF, DOC, DOCX, XLS, atau XLSX'
                ]
            ]
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('file_laporan');

        // Prepare data for database sesuai struktur
        $data = [
            'opd_id' => $opdId,
            'judul' => $this->request->getPost('judul_laporan'), // Map form field to database field
            'tanggal_laporan' => $this->request->getPost('tanggal_laporan') ?: null,
            'status' => 'draft' // default status
        ];

        try {
            $result = $this->lakipModel->createLakipWithFile($data, $file);
            
            if ($result) {
                return redirect()->to('/adminopd/lakip_opd')
                               ->with('success', 'LAKIP berhasil disimpan');
            } else {
                return redirect()->back()
                               ->withInput()
                               ->with('error', 'Gagal menyimpan LAKIP');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
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

    /**
     * Update status via AJAX
     */
    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Session tidak valid'
            ]);
        }

        // Get JSON input
        $json = $this->request->getJSON(true);
        $id = $json['id'] ?? null;

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID harus diisi']);
        }

        $lakip = $this->lakipModel->where('id', $id)
                                 ->where('opd_id', $opdId)
                                 ->first();

        if (!$lakip) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'LAKIP tidak ditemukan'
            ]);
        }

        try {
            // Get current status
            $currentStatus = $lakip['status'] ?? 'draft';
            
            // Toggle status
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $this->lakipModel->updateStatus($id, $newStatus);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status berhasil diperbarui',
                'oldStatus' => $currentStatus,
                'newStatus' => $newStatus
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Download LAKIP file
     */
    public function download($id)
    {
        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->where('id', $id)
                                 ->where('opd_id', $opdId)
                                 ->first();
        
        if (!$lakip || empty($lakip['file'])) {
            return redirect()->to('/adminopd/lakip_opd')
                           ->with('error', 'File tidak ditemukan');
        }

        $filePath = WRITEPATH . 'uploads/lakip/opd/' . $lakip['file'];
        
        if (!file_exists($filePath)) {
            return redirect()->to('/adminopd/lakip_opd')
                           ->with('error', 'File tidak ditemukan di server');
        }

        // Get file extension from stored file
        $fileExtension = pathinfo($lakip['file'], PATHINFO_EXTENSION);
        
        // Clean judul for filename (remove special characters)
        $cleanJudul = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $lakip['judul']);
        $cleanJudul = preg_replace('/\s+/', '_', trim($cleanJudul));
        
        // Ensure filename doesn't exceed limits
        if (strlen($cleanJudul) > 100) {
            $cleanJudul = substr($cleanJudul, 0, 100);
        }
        
        // Create download filename with judul + extension
        $downloadName = $cleanJudul . '.' . $fileExtension;

        // Force download with custom filename
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filePath);
        exit;
    }

    /**
     * Get LAKIP statistics for dashboard
     */
    public function getStats()
    {
        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Session tidak valid'
            ]);
        }

        $stats = $this->lakipModel->getStatsByStatusAndOpd($opdId);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get available years from LAKIP data for this OPD
     */
    public function getAvailableYears()
    {
        $opdId = session()->get('opd_id');
        
        if (!$opdId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Session tidak valid'
            ]);
        }

        $years = $this->lakipModel->getAvailableYearsByOpd($opdId);
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $years
        ]);
    }
}

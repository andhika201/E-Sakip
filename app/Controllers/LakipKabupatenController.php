<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\LakipKabupatenModel;

class LakipKabupatenController extends BaseController
{
    protected $lakipModel;
    
    public function __construct()
    {
        $this->lakipModel = new LakipKabupatenModel();
        helper(['form', 'url']);
    }

    public function index()
    {
        // Get filter parameters
        $tahun = $this->request->getVar('tahun');
        $status = $this->request->getVar('status');

        // Build query with filters
        $builder = $this->lakipModel->orderBy('created_at', 'DESC');
        
        if ($tahun) {
            $builder = $builder->where('YEAR(tanggal_laporan)', $tahun);
        }
        
        if ($status) {
            $builder = $builder->where('status', $status);
        }

        // Get all data
        $lakips = $builder->findAll();

        $data = [
            'lakips' => $lakips,
            'availableYears' => $this->lakipModel->getAvailableYears(),
            'filters' => [
                'tahun' => $tahun,
                'status' => $status
            ]
        ];

        return view('adminKabupaten/lakip_kabupaten/lakip_kabupaten', $data);
    }

    public function tambah()
    {
        $data = [
            'validation' => \Config\Services::validation()
        ];
        
        return view('adminKabupaten/lakip_kabupaten/tambah_lakip', $data);
    }

    public function save()
    {
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

        // Handle file upload
        $file = $this->request->getFile('file_laporan');

        // Prepare data for database sesuai struktur
        $data = [
            'judul' => $this->request->getPost('judul_laporan'), // Map form field to database field
            'tanggal_laporan' => $this->request->getPost('tanggal_laporan') ?: null,
            'status' => 'draft' // default status
        ];

        try {
            $result = $this->lakipModel->createLakipWithFile($data, $file);
            
            if ($result) {
                return redirect()->to('/adminkab/lakip_kabupaten')
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

    public function edit($id)
    {
        $lakip = $this->lakipModel->find($id);
        
        if (!$lakip) {
            return redirect()->to('/adminkab/lakip_kabupaten')
                           ->with('error', 'Data LAKIP tidak ditemukan');
        }

        $data = [
            'lakip' => $lakip,
            'validation' => \Config\Services::validation()
        ];

        return view('adminKabupaten/lakip_kabupaten/edit_lakip', $data);
    }

    public function update($id)
    {
        $lakip = $this->lakipModel->find($id);
        
        if (!$lakip) {
            return redirect()->to('/adminkab/lakip_kabupaten')
                           ->with('error', 'Data LAKIP tidak ditemukan');
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
                return redirect()->to('/adminkab/lakip_kabupaten')
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

    public function delete($id)
    {
        $lakip = $this->lakipModel->find($id);
        
        if (!$lakip) {
            return redirect()->to('/adminkab/lakip_kabupaten')
                           ->with('error', 'Data LAKIP tidak ditemukan');
        }

        if ($this->lakipModel->deleteLakip($id)) {
            return redirect()->to('/adminkab/lakip_kabupaten')
                           ->with('success', 'LAKIP berhasil dihapus');
        } else {
            return redirect()->to('/adminkab/lakip_kabupaten')
                           ->with('error', 'Gagal menghapus LAKIP');
        }   
    }

    /**
     * Download file LAKIP
     */
    public function download($id)
    {
        $lakip = $this->lakipModel->find($id);
        
        if (!$lakip || empty($lakip['file'])) {
            return redirect()->to('/adminkab/lakip_kabupaten')
                           ->with('error', 'File tidak ditemukan');
        }

        $filePath = WRITEPATH . 'uploads/lakip/kabupaten/' . $lakip['file'];
        
        if (!file_exists($filePath)) {
            return redirect()->to('/adminkab/lakip_kabupaten')
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
     * Update status LAKIP
     */
    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }
        
        // Get JSON input
        $json = $this->request->getJSON(true);
        $id = $json['id'] ?? null;

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID harus diisi']);
        }
        
        try {
            // Get current status
            $currentLakip = $this->lakipModel->find($id);
            if (!$currentLakip) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status
            $currentStatus = $currentLakip['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->lakipModel->updateStatus($id, $newStatus);
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Status berhasil diupdate',
                    'oldStatus' => $currentStatus,
                    'newStatus' => $newStatus
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Gagal mengupdate status']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get LAKIP statistics for dashboard
     */
    public function getStats()
    {
        $stats = $this->lakipModel->getStatsByStatus();
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get available years from LAKIP data
     */
    public function getAvailableYears()
    {
        $years = $this->lakipModel->getAvailableYears();
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $years
        ]);
    }
}
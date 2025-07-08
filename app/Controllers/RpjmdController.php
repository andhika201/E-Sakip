<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RpjmdModel;

class RpjmdController extends BaseController
{
    protected $rpjmdModel;

    public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
    }

    // ==================== MAIN RPJMD VIEWS ====================

    public function index()
    {
        // Pagination setup
        $perPage = 5; // 5 misi per page
        $page = $this->request->getGet('page') ?? 1;
        $offset = ($page - 1) * $perPage;
        
        // Get all data without filter
        $allMisi = $this->rpjmdModel->getCompleteRpjmdStructure();
        
        // Get total count for pagination
        $totalMisi = count($allMisi);
        $totalPages = ceil($totalMisi / $perPage);
        
        // Get paginated misi data
        $data['rpjmd_data'] = array_slice($allMisi, $offset, $perPage);
        
        // Pagination info
        $data['current_page'] = $page;
        $data['total_pages'] = $totalPages;
        $data['per_page'] = $perPage;
        $data['total_records'] = $totalMisi;
        
        // Load summary statistics
        $data['rpjmd_summary'] = $this->rpjmdModel->getRpjmdSummary();
        
        // Get available years for table headers
        $availableYears = $this->rpjmdModel->getRpjmdSummary()['years_available'];
        $data['available_years'] = array_column($availableYears, 'tahun');
        sort($data['available_years']); // Sort years in ascending order
        
        // If no years available, use default range
        if (empty($data['available_years'])) {
            $data['available_years'] = [2025, 2026, 2027, 2028, 2029];
        }
        
        return view('adminKabupaten/rpjmd/rpjmd', $data);
    }

    public function tambah()
    {
        // Pass existing data for dropdowns
        $data['misi_list'] = $this->rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $this->rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $this->rpjmdModel->getAllSasaran();
        
        return view('adminKabupaten/rpjmd/tambah_rpjmd', $data);
    }

    public function edit($id = null)
    {
        if ($id === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ID tidak ditemukan');
        }
        
        // Get specific RPJMD data
        $data['misi'] = $this->rpjmdModel->getMisiById($id);
        
        if (!$data['misi']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }
        
        // Get complete structure for this misi
        $data['rpjmd_complete'] = $this->rpjmdModel->getCompleteRpjmdStructure();
        
        // Pass all data for dropdowns
        $data['misi_list'] = $this->rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $this->rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $this->rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $this->rpjmdModel->getAllIndikatorSasaran();
        
        return view('adminKabupaten/rpjmd/edit_rpjmd', $data);
    }

    // ==================== MISI METHODS ====================

    public function save()
    {
        try {
            $data = $this->request->getPost();
            
            // Check if it's create or update
            if (isset($data['id']) && !empty($data['id'])) {
                // Update existing
                $misiId = $data['id'];
                unset($data['id']);
                
                $result = $this->rpjmdModel->updateMisi($misiId, $data);
                
                if ($result) {
                    session()->setFlashdata('success', 'Data RPJMD berhasil diupdate');
                } else {
                    session()->setFlashdata('error', 'Gagal mengupdate data RPJMD');
                }
            } else {
                // Create new
                $misiId = $this->rpjmdModel->createMisi($data);
                
                if ($misiId) {
                    session()->setFlashdata('success', 'Data RPJMD berhasil ditambahkan');
                } else {
                    session()->setFlashdata('error', 'Gagal menambahkan data RPJMD');
                }
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('rpjmd'));
    }

    public function delete($id)
    {
        try {
            if (!$this->rpjmdModel->misiExists($id)) {
                session()->setFlashdata('error', 'Data RPJMD tidak ditemukan');
                return redirect()->to(base_url('rpjmd'));
            }
            
            $result = $this->rpjmdModel->deleteMisi($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Data RPJMD berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus data RPJMD');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    // ==================== TUJUAN METHODS ====================
    
    public function save_tujuan()
    {
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $this->rpjmdModel->updateTujuan($id, $data);
                $message = 'Tujuan berhasil diupdate';
            } else {
                // Create
                $result = $this->rpjmdModel->createTujuan($data);
                $message = 'Tujuan berhasil ditambahkan';
            }
            
            if ($result) {
                session()->setFlashdata('success', $message);
            } else {
                session()->setFlashdata('error', 'Operasi gagal');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    public function delete_tujuan($id)
    {
        try {
            $result = $this->rpjmdModel->deleteTujuan($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Tujuan berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus tujuan');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    // ==================== SASARAN METHODS ====================
    
    public function save_sasaran()
    {
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $this->rpjmdModel->updateSasaran($id, $data);
                $message = 'Sasaran berhasil diupdate';
            } else {
                // Create
                $result = $this->rpjmdModel->createSasaran($data);
                $message = 'Sasaran berhasil ditambahkan';
            }
            
            if ($result) {
                session()->setFlashdata('success', $message);
            } else {
                session()->setFlashdata('error', 'Operasi gagal');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    public function delete_sasaran($id)
    {
        try {
            $result = $this->rpjmdModel->deleteSasaran($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Sasaran berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus sasaran');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    // ==================== INDIKATOR SASARAN METHODS ====================
    
    public function save_indikator_sasaran()
    {
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $this->rpjmdModel->updateIndikatorSasaran($id, $data);
                $message = 'Indikator Sasaran berhasil diupdate';
            } else {
                // Create
                $result = $this->rpjmdModel->createIndikatorSasaran($data);
                $message = 'Indikator Sasaran berhasil ditambahkan';
            }
            
            if ($result) {
                session()->setFlashdata('success', $message);
            } else {
                session()->setFlashdata('error', 'Operasi gagal');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    public function delete_indikator_sasaran($id)
    {
        try {
            $result = $this->rpjmdModel->deleteIndikatorSasaran($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Indikator Sasaran berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus indikator sasaran');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    // ==================== TARGET TAHUNAN METHODS ====================
    
    public function save_target_tahunan()
    {
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $this->rpjmdModel->updateTargetTahunan($id, $data);
                $message = 'Target Tahunan berhasil diupdate';
            } else {
                // Create
                $result = $this->rpjmdModel->createTargetTahunan($data);
                $message = 'Target Tahunan berhasil ditambahkan';
            }
            
            if ($result) {
                session()->setFlashdata('success', $message);
            } else {
                session()->setFlashdata('error', 'Operasi gagal');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    public function delete_target_tahunan($id)
    {
        try {
            $result = $this->rpjmdModel->deleteTargetTahunan($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Target Tahunan berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus target tahunan');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    // ==================== COMPLETE RPJMD OPERATIONS ====================
    
    public function save_complete()
    {
        try {
            $data = $this->request->getPost();
            
            // Create complete RPJMD structure
            $misiId = $this->rpjmdModel->createCompleteRpjmd($data);
            
            if ($misiId) {
                session()->setFlashdata('success', 'RPJMD lengkap berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan RPJMD lengkap');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('rpjmd'));
    }

    // ==================== API METHODS FOR AJAX ====================
    
    public function get_tujuan_by_misi($misiId)
    {
        try {
            $tujuan = $this->rpjmdModel->getTujuanByMisiId($misiId);
            return $this->response->setJSON(['success' => true, 'data' => $tujuan]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function get_sasaran_by_tujuan($tujuanId)
    {
        try {
            $sasaran = $this->rpjmdModel->getSasaranByTujuanId($tujuanId);
            return $this->response->setJSON(['success' => true, 'data' => $sasaran]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function get_indikator_by_sasaran($sasaranId)
    {
        try {
            $indikator = $this->rpjmdModel->getIndikatorSasaranBySasaranId($sasaranId);
            return $this->response->setJSON(['success' => true, 'data' => $indikator]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ==================== ADDITIONAL UTILITY METHODS ====================

    public function search()
    {
        $keyword = $this->request->getPost('keyword');
        
        if (empty($keyword)) {
            return redirect()->to(base_url('rpjmd'));
        }
        
        try {
            $data['search_results'] = $this->rpjmdModel->searchRpjmd($keyword);
            $data['keyword'] = $keyword;
            
            return view('adminKabupaten/rpjmd/search_results', $data);
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
            return redirect()->to(base_url('rpjmd'));
        }
    }

    public function export()
    {
        // Method untuk export data RPJMD (bisa dikembangkan untuk PDF, Excel, dll)
        try {
            $data = $this->rpjmdModel->getCompleteRpjmdStructure();
            
            // Untuk sementara return JSON
            return $this->response->setJSON($data);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }
}

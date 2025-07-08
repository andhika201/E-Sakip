<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RpjmdModel;

class AdminKabupaten extends BaseController
{
    public function index()
    {
        return view('adminKabupaten/dashboard');
    }

    // RPJMD Methods
    public function rpjmd()
    {
        $rpjmdModel = new RpjmdModel();
        
        // Load all RPJMD data with complete structure
        $data['rpjmd_data'] = $rpjmdModel->getCompleteRpjmdStructure();
        
        // Load summary statistics
        $data['rpjmd_summary'] = $rpjmdModel->getRpjmdSummary();
        
        // Load all individual data for additional use
        $data['misi_list'] = $rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $rpjmdModel->getAllIndikatorSasaran();
        $data['target_tahunan_list'] = $rpjmdModel->getAllTargetTahunan();

        return view('adminKabupaten/rpjmd/rpjmd', $data);
    }

    public function tambah_rpjmd()
    {
        $rpjmdModel = new RpjmdModel();
        
        // Pass existing data for dropdowns
        $data['misi_list'] = $rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $rpjmdModel->getAllSasaran();
        
        return view('adminKabupaten/rpjmd/tambah_rpjmd', $data);
    }

    public function edit_rpjmd($id = null)
    {
        $rpjmdModel = new RpjmdModel();
        
        if ($id === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ID tidak ditemukan');
        }
        
        // Get specific RPJMD data
        $data['misi'] = $rpjmdModel->getMisiById($id);
        
        if (!$data['misi']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }
        
        // Get complete structure for this misi
        $data['rpjmd_complete'] = $rpjmdModel->getCompleteRpjmdStructure();
        
        // Pass all data for dropdowns
        $data['misi_list'] = $rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $rpjmdModel->getAllIndikatorSasaran();
        
        return view('adminKabupaten/rpjmd/edit_rpjmd', $data);
    }

    public function save_rpjmd()
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $data = $this->request->getPost();
            
            // Check if it's create or update
            if (isset($data['id']) && !empty($data['id'])) {
                // Update existing
                $misiId = $data['id'];
                unset($data['id']);
                
                $result = $rpjmdModel->updateMisi($misiId, $data);
                
                if ($result) {
                    session()->setFlashdata('success', 'Data RPJMD berhasil diupdate');
                } else {
                    session()->setFlashdata('error', 'Gagal mengupdate data RPJMD');
                }
            } else {
                // Create new
                $misiId = $rpjmdModel->createMisi($data);
                
                if ($misiId) {
                    session()->setFlashdata('success', 'Data RPJMD berhasil ditambahkan');
                } else {
                    session()->setFlashdata('error', 'Gagal menambahkan data RPJMD');
                }
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete_rpjmd($id)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            if (!$rpjmdModel->misiExists($id)) {
                session()->setFlashdata('error', 'Data RPJMD tidak ditemukan');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }
            
            $result = $rpjmdModel->deleteMisi($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Data RPJMD berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus data RPJMD');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== TUJUAN METHODS ====================
    
    public function save_tujuan()
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $rpjmdModel->updateTujuan($id, $data);
                $message = 'Tujuan berhasil diupdate';
            } else {
                // Create
                $result = $rpjmdModel->createTujuan($data);
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete_tujuan($id)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $result = $rpjmdModel->deleteTujuan($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Tujuan berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus tujuan');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== SASARAN METHODS ====================
    
    public function save_sasaran()
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $rpjmdModel->updateSasaran($id, $data);
                $message = 'Sasaran berhasil diupdate';
            } else {
                // Create
                $result = $rpjmdModel->createSasaran($data);
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete_sasaran($id)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $result = $rpjmdModel->deleteSasaran($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Sasaran berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus sasaran');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== INDIKATOR SASARAN METHODS ====================
    
    public function save_indikator_sasaran()
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $rpjmdModel->updateIndikatorSasaran($id, $data);
                $message = 'Indikator Sasaran berhasil diupdate';
            } else {
                // Create
                $result = $rpjmdModel->createIndikatorSasaran($data);
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete_indikator_sasaran($id)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $result = $rpjmdModel->deleteIndikatorSasaran($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Indikator Sasaran berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus indikator sasaran');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== TARGET TAHUNAN METHODS ====================
    
    public function save_target_tahunan()
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $data = $this->request->getPost();
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $id = $data['id'];
                unset($data['id']);
                $result = $rpjmdModel->updateTargetTahunan($id, $data);
                $message = 'Target Tahunan berhasil diupdate';
            } else {
                // Create
                $result = $rpjmdModel->createTargetTahunan($data);
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete_target_tahunan($id)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $result = $rpjmdModel->deleteTargetTahunan($id);
            
            if ($result) {
                session()->setFlashdata('success', 'Target Tahunan berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus target tahunan');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== COMPLETE RPJMD OPERATIONS ====================
    
    public function save_complete_rpjmd()
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $data = $this->request->getPost();
            
            // Create complete RPJMD structure
            $misiId = $rpjmdModel->createCompleteRpjmd($data);
            
            if ($misiId) {
                session()->setFlashdata('success', 'RPJMD lengkap berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan RPJMD lengkap');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== API METHODS FOR AJAX ====================
    
    public function get_tujuan_by_misi($misiId)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $tujuan = $rpjmdModel->getTujuanByMisiId($misiId);
            return $this->response->setJSON(['success' => true, 'data' => $tujuan]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function get_sasaran_by_tujuan($tujuanId)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $sasaran = $rpjmdModel->getSasaranByTujuanId($tujuanId);
            return $this->response->setJSON(['success' => true, 'data' => $sasaran]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function get_indikator_by_sasaran($sasaranId)
    {
        $rpjmdModel = new RpjmdModel();
        
        try {
            $indikator = $rpjmdModel->getIndikatorSasaranBySasaranId($sasaranId);
            return $this->response->setJSON(['success' => true, 'data' => $indikator]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }



    // RKT Methods
    public function rkt()
    {
        return view('adminKabupaten/rkt/rkt');
    }

    public function tambah_rkt()
    {
        return view('adminKabupaten/rkt/tambah_rkt');
    }

    public function edit_rkt()
    {
        return view('adminKabupaten/rkt/edit_rkt');
    }

    public function save_rkt()
    {
        return redirect()->to(base_url('adminkab/rkt'));
    }

    // PK Bupati Methods
    public function pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/pk_bupati');
    }

    public function tambah_pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/tambah_pk_bupati');
    }

    public function edit_pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/edit_pk_bupati');
    }

    public function save_pk_bupati()
    {
        return redirect()->to(base_url('adminkab/pk_bupati'));
    }

    // Lakip Kabupaten Methods
    public function lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/lakip_kabupaten');
    }

    public function tambah_lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/tambah_lakip_kabupaten');
    }

    public function edit_lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/edit_lakip_kabupaten');
    }

    public function save_lakip_kabupaten()
    {
        return redirect()->to(base_url('adminkab/lakip_kabupaten'));
    }

    // Tentang Kami Methods
    public function tentang_kami()
    {
        return view('adminKabupaten/tentang_kami');
    }

    public function edit_tentang_kami()
    {
        return view('adminKabupaten/edit_tentang_kami');
    }

    public function save_tentang_kami()
    {
        return redirect()->to(base_url('adminkab/tentang_kami'));
    }
}

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
        
        // Group data by period (tahun_mulai - tahun_akhir)
        $groupedData = [];
        foreach ($allMisi as $misi) {
            $periodKey = $misi['tahun_mulai'] . '-' . $misi['tahun_akhir'];
            
            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => $misi['tahun_mulai'],
                    'tahun_akhir' => $misi['tahun_akhir'],
                    'years' => range($misi['tahun_mulai'], $misi['tahun_akhir']),
                    'misi_data' => []
                ];
            }
            
            $groupedData[$periodKey]['misi_data'][] = $misi;
        }
        
        // Sort periods by tahun_mulai
        ksort($groupedData);
        
        // Get total count for pagination
        $totalMisi = count($allMisi);
        $totalPages = ceil($totalMisi / $perPage);
        
        // Pass grouped data to view
        $data['rpjmd_grouped'] = $groupedData;
        $data['rpjmd_data'] = array_slice($allMisi, $offset, $perPage); // Keep original for compatibility
        
        // Pagination info
        $data['current_page'] = $page;
        $data['total_pages'] = $totalPages;
        $data['per_page'] = $perPage;
        $data['total_records'] = $totalMisi;
        
        // Load summary statistics
        $data['rpjmd_summary'] = $this->rpjmdModel->getRpjmdSummary();
        
        // Get available years for table headers (for backward compatibility)
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
        
        // Find the parent misi ID for any given entity ID
        $misiId = $this->rpjmdModel->findMisiIdForAnyEntity($id);
        
        if (!$misiId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }
        
        // Get specific RPJMD data using the found misi ID
        $data['misi'] = $this->rpjmdModel->getMisiById($misiId);
        
        if (!$data['misi']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }
        
        // Get complete structure for this specific misi
        $completeData = $this->rpjmdModel->getCompleteRpjmdStructure();
        
        // Filter to get only the data for this specific misi
        $data['rpjmd_complete'] = null;
        foreach ($completeData as $misiData) {
            if ($misiData['id'] == $misiId) {
                $data['rpjmd_complete'] = $misiData;
                break;
            }
        }
        
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
            
            // Create new - Use createCompleteRpjmdNoTransaction for tambah form
            $formattedData = [
                'misi' => [
                    'misi' => $data['misi'],
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir']
                ],
                'tujuan' => $data['tujuan'] ?? []
            ];
            
            $misiId = $this->rpjmdModel->createCompleteRpjmdNoTransaction($formattedData);
            
            if ($misiId) {
                session()->setFlashdata('success', 'Data RPJMD berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RPJMD');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function update()
    {
        try {
            $data = $this->request->getPost();
            
            if (!isset($data['id']) || empty($data['id'])) {
                session()->setFlashdata('error', 'ID tidak ditemukan');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }
            
            $misiId = $data['id'];
            
            // Update misi data
            $misiData = [
                'misi' => $data['misi'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir']
            ];
            
            $result = $this->rpjmdModel->updateMisi($misiId, $misiData);
            
            
            if ($result) {
                // Process tujuan data
                if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                    foreach ($data['tujuan'] as $index => $tujuanData) {
                        if (!empty($tujuanData['tujuan_rpjmd'])) {
                            $tujuanInfo = [
                                'misi_id' => $misiId,
                                'tujuan_rpjmd' => $tujuanData['tujuan_rpjmd']
                            ];
                            
                            if (isset($tujuanData['id']) && !empty($tujuanData['id'])) {
                                // Update existing tujuan
                                $this->rpjmdModel->updateTujuan($tujuanData['id'], $tujuanInfo);
                                $tujuanId = $tujuanData['id'];
                            } else {
                                // Create new tujuan
                                $tujuanId = $this->rpjmdModel->createTujuan($tujuanInfo);
                            }
                            
                            // Process indikator tujuan
                            if (isset($tujuanData['indikator_tujuan']) && is_array($tujuanData['indikator_tujuan'])) {
                                foreach ($tujuanData['indikator_tujuan'] as $indikatorData) {
                                    if (!empty($indikatorData['indikator_tujuan'])) {
                                        $indikatorInfo = [
                                            'tujuan_id' => $tujuanId,
                                            'indikator_tujuan' => $indikatorData['indikator_tujuan']
                                        ];
                                        
                                        if (isset($indikatorData['id']) && !empty($indikatorData['id'])) {
                                            $this->rpjmdModel->updateIndikatorTujuan($indikatorData['id'], $indikatorInfo);
                                        } else {
                                            $this->rpjmdModel->createIndikatorTujuan($indikatorInfo);
                                        }
                                    }
                                }
                            }
                            
                            // Process sasaran data
                            if (isset($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                                foreach ($tujuanData['sasaran'] as $sasaranData) {
                                    if (!empty($sasaranData['sasaran_rpjmd'])) {
                                        $sasaranInfo = [
                                            'tujuan_id' => $tujuanId,
                                            'sasaran_rpjmd' => $sasaranData['sasaran_rpjmd']
                                        ];
                                        
                                        if (isset($sasaranData['id']) && !empty($sasaranData['id'])) {
                                            $this->rpjmdModel->updateSasaran($sasaranData['id'], $sasaranInfo);
                                            $sasaranId = $sasaranData['id'];
                                        } else {
                                            $sasaranId = $this->rpjmdModel->createSasaran($sasaranInfo);
                                        }
                                        
                                        // Process indikator sasaran
                                        if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                                            foreach ($sasaranData['indikator_sasaran'] as $indikatorSasaranData) {
                                                if (!empty($indikatorSasaranData['indikator_sasaran'])) {
                                                    $indikatorSasaranInfo = [
                                                        'sasaran_id' => $sasaranId,
                                                        'indikator_sasaran' => $indikatorSasaranData['indikator_sasaran'],
                                                        'strategi' => $indikatorSasaranData['strategi'] ?? '',
                                                        'satuan' => $indikatorSasaranData['satuan'] ?? ''
                                                    ];
                                                    
                                                    if (isset($indikatorSasaranData['id']) && !empty($indikatorSasaranData['id'])) {
                                                        $this->rpjmdModel->updateIndikatorSasaran($indikatorSasaranData['id'], $indikatorSasaranInfo);
                                                        $indikatorSasaranId = $indikatorSasaranData['id'];
                                                        
                                                        // Delete existing target tahunan for this indikator before creating new ones
                                                        $this->rpjmdModel->deleteTargetTahunanByIndikatorId($indikatorSasaranId);
                                                    } else {
                                                        $indikatorSasaranId = $this->rpjmdModel->createIndikatorSasaran($indikatorSasaranInfo);
                                                    }
                                                    
                                                    // Process target tahunan
                                                    if (isset($indikatorSasaranData['target_tahunan']) && is_array($indikatorSasaranData['target_tahunan'])) {
                                                        foreach ($indikatorSasaranData['target_tahunan'] as $targetData) {
                                                            // Create target tahunan if tahun exists (regardless of target_tahunan value)
                                                            if (isset($targetData['tahun']) && $targetData['tahun'] !== '') {
                                                                $targetInfo = [
                                                                    'indikator_sasaran_id' => $indikatorSasaranId,
                                                                    'tahun' => $targetData['tahun'],
                                                                    'target_tahunan' => $targetData['target_tahunan'] ?? ''
                                                                ];
                                                                
                                                                $this->rpjmdModel->createTargetTahunan($targetInfo);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                session()->setFlashdata('success', 'Data RPJMD berhasil diupdate');
            } else {
                session()->setFlashdata('error', 'Gagal mengupdate data RPJMD');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete($id)
    {
        try {
            if (!$this->rpjmdModel->misiExists($id)) {
                session()->setFlashdata('error', 'Data RPJMD tidak ditemukan');
                return redirect()->to(base_url('adminkab/rpjmd'));
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
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
        
        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== COMPLETE RPJMD OPERATIONS (LEGACY - NOT USED) ====================
    
    /**
     * Legacy method - use save() or update() instead
     * Kept for backward compatibility if needed
     */
    public function save_complete()
    {
        // Redirect to appropriate method based on whether ID exists
        $data = $this->request->getPost();
        
        if (isset($data['id']) && !empty($data['id'])) {
            // Has ID - redirect to update
            return $this->update();
        } else {
            // No ID - redirect to save (create)
            return $this->save();
        }
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

    // // ==================== ADDITIONAL UTILITY METHODS ====================

    // public function search()
    // {
    //     $keyword = $this->request->getPost('keyword');
        
    //     if (empty($keyword)) {
    //         return redirect()->to(base_url('rpjmd'));
    //     }
        
    //     try {
    //         $data['search_results'] = $this->rpjmdModel->searchRpjmd($keyword);
    //         $data['keyword'] = $keyword;
            
    //         return view('adminKabupaten/rpjmd/search_results', $data);
    //     } catch (\Exception $e) {
    //         session()->setFlashdata('error', 'Error: ' . $e->getMessage());
    //         return redirect()->to(base_url('rpjmd'));
    //     }
    // }

    // public function export()
    // {
    //     // Method untuk export data RPJMD (bisa dikembangkan untuk PDF, Excel, dll)
    //     try {
    //         $data = $this->rpjmdModel->getCompleteRpjmdStructure();
            
    //         // Untuk sementara return JSON
    //         return $this->response->setJSON($data);
    //     } catch (\Exception $e) {
    //         return $this->response->setJSON(['error' => $e->getMessage()]);
    //     }
    // }
}
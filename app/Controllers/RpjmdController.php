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
        // Get all data first
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
        
        // Pass ALL grouped data to view (let JavaScript handle filtering)
        $data['rpjmd_grouped'] = $groupedData;
        $data['rpjmd_data'] = $allMisi;
        
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
            
            // Create new - Use createCompleteRpjmdTransaction for tambah form
            $formattedData = [
                'misi' => [
                    'misi' => $data['misi'],
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                    'status' => $data['status'] ?? 'draft'
                ],
                'tujuan' => $data['tujuan'] ?? []
            ];
            
            $misiId = $this->rpjmdModel->createCompleteRpjmdTransaction($formattedData);
            
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

            // Debug logging - raw POST data
            $debugFile = WRITEPATH . 'debug_rpjmd_update.txt';
            file_put_contents($debugFile, "=== RPJMD UPDATE DEBUG - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
            file_put_contents($debugFile, "Raw POST data structure:\n" . print_r($data, true) . "\n", FILE_APPEND);
            
            if (!isset($data['id']) || empty($data['id'])) {
                file_put_contents($debugFile, "ERROR: ID tidak ditemukan dalam POST data\n", FILE_APPEND);
                session()->setFlashdata('error', 'ID tidak ditemukan');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }
            
            $misiId = $data['id'];
            file_put_contents($debugFile, "Processing update for Misi ID: {$misiId}\n", FILE_APPEND);
            
            $existingMisi = $this->rpjmdModel->getMisiById($misiId);

            if (!$existingMisi) {
                file_put_contents($debugFile, "ERROR: Misi dengan ID {$misiId} tidak ditemukan di database\n", FILE_APPEND);
                session()->setFlashdata('error', 'Data RPJMD tidak ditemukan di database.');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }
            
            file_put_contents($debugFile, "Existing misi data:\n" . print_r($existingMisi, true) . "\n", FILE_APPEND);

            // Analyze tujuan data structure in detail
            if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                file_put_contents($debugFile, "=== TUJUAN DATA ANALYSIS ===\n", FILE_APPEND);
                file_put_contents($debugFile, "Tujuan count: " . count($data['tujuan']) . "\n", FILE_APPEND);
                
                foreach ($data['tujuan'] as $tujuanIndex => $tujuanData) {
                    file_put_contents($debugFile, "\nTujuan [{$tujuanIndex}]:\n", FILE_APPEND);
                    file_put_contents($debugFile, "  - ID: " . ($tujuanData['id'] ?? 'NONE') . "\n", FILE_APPEND);
                    file_put_contents($debugFile, "  - Text: " . (isset($tujuanData['tujuan_rpjmd']) ? substr($tujuanData['tujuan_rpjmd'], 0, 50) . '...' : 'NONE') . "\n", FILE_APPEND);
                    
                    if (isset($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                        file_put_contents($debugFile, "  - Sasaran count: " . count($tujuanData['sasaran']) . "\n", FILE_APPEND);
                        
                        foreach ($tujuanData['sasaran'] as $sasaranIndex => $sasaranData) {
                            file_put_contents($debugFile, "    Sasaran [{$sasaranIndex}]:\n", FILE_APPEND);
                            file_put_contents($debugFile, "      - ID: " . ($sasaranData['id'] ?? 'NONE') . "\n", FILE_APPEND);
                            file_put_contents($debugFile, "      - Text: " . (isset($sasaranData['sasaran_rpjmd']) ? substr($sasaranData['sasaran_rpjmd'], 0, 30) . '...' : 'NONE') . "\n", FILE_APPEND);
                            
                            if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                                file_put_contents($debugFile, "      - Indikator Sasaran count: " . count($sasaranData['indikator_sasaran']) . "\n", FILE_APPEND);
                                
                                foreach ($sasaranData['indikator_sasaran'] as $indSasIndex => $indSasData) {
                                    file_put_contents($debugFile, "        Indikator Sasaran [{$indSasIndex}]:\n", FILE_APPEND);
                                    file_put_contents($debugFile, "          - ID: " . ($indSasData['id'] ?? 'NONE') . "\n", FILE_APPEND);
                                    file_put_contents($debugFile, "          - Text: " . (isset($indSasData['indikator_sasaran']) ? substr($indSasData['indikator_sasaran'], 0, 20) . '...' : 'NONE') . "\n", FILE_APPEND);
                                    
                                    if (isset($indSasData['target_tahunan']) && is_array($indSasData['target_tahunan'])) {
                                        file_put_contents($debugFile, "          - Target Tahunan count: " . count($indSasData['target_tahunan']) . "\n", FILE_APPEND);
                                        
                                        foreach ($indSasData['target_tahunan'] as $targetIndex => $targetData) {
                                            file_put_contents($debugFile, "            Target [{$targetIndex}]:\n", FILE_APPEND);
                                            file_put_contents($debugFile, "              - ID: " . ($targetData['id'] ?? 'NONE') . "\n", FILE_APPEND);
                                            file_put_contents($debugFile, "              - Tahun: " . ($targetData['tahun'] ?? 'NONE') . "\n", FILE_APPEND);
                                            file_put_contents($debugFile, "              - Target: " . ($targetData['target_tahunan'] ?? 'NONE') . "\n", FILE_APPEND);
                                        }
                                    } else {
                                        file_put_contents($debugFile, "          - No target_tahunan data\n", FILE_APPEND);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                file_put_contents($debugFile, "WARNING: No tujuan data found or not array\n", FILE_APPEND);
            }

            // Format data for updateCompleteRpjmdTransaction
            $formattedData = [
                'misi' => [
                    'misi' => $data['misi'],
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                    'status' => $data['status'] ?? $existingMisi['status'] ?? 'draft'
                ],
                'tujuan' => $data['tujuan'] ?? []
            ];
            
            file_put_contents($debugFile, "=== FORMATTED DATA FOR MODEL ===\n", FILE_APPEND);
            file_put_contents($debugFile, "Formatted data structure:\n" . print_r($formattedData, true) . "\n", FILE_APPEND);
            
            $result = $this->rpjmdModel->updateCompleteRpjmdTransaction($misiId, $formattedData);

            file_put_contents($debugFile, "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            file_put_contents($debugFile, "=== END UPDATE DEBUG ===\n\n", FILE_APPEND);

            if ($result) {
                session()->setFlashdata('success', 'Data RPJMD berhasil diupdate');
            } else {
                session()->setFlashdata('error', 'Gagal mengupdate data RPJMD');
            }
            
        } catch (\Exception $e) {
            $debugFile = WRITEPATH . 'debug_rpjmd_update.txt';
            file_put_contents($debugFile, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Stack trace:\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
            
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

    // ==================== STATUS MANAGEMENT ====================
    
    /**
     * Update status RPJMD (AJAX endpoint)
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
            $currentMisi = $this->rpjmdModel->getMisiById($id);
            if (!$currentMisi) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status
            $currentStatus = $currentMisi['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->rpjmdModel->updateMisiStatus($id, $newStatus);
            
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
}
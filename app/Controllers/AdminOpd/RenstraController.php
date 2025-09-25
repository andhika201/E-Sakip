<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\RenstraModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;

class RenstraController extends BaseController
{
    protected $renstraModel;
    protected $rpjmdModel;
    protected $opdModel;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
    }

    // ==================== MAIN RENSTRA VIEWS ====================

    public function index()
    {
        // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        
        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        // Get Renstra data filtered by user's OPD
        $renstraData = $this->renstraModel->getAllRenstra($opdId);
        
        // Get current OPD info
        $currentOpd = $this->opdModel->find($opdId);
        if (!$currentOpd) {
            return redirect()->to('/login')->with('error', 'Data OPD tidak ditemukan');
        }
        
        // Set title based on current OPD
        $titleSuffix = $currentOpd['nama_opd'];  // Group data by period

        $groupedData = [];
        foreach ($renstraData as $data) {
            $periodKey = $data['tahun_mulai'] . '-' . $data['tahun_akhir'];
            
            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                    'years' => range($data['tahun_mulai'], $data['tahun_akhir']),
                    'renstra_data' => []
                ];
            }
            
            $groupedData[$periodKey]['renstra_data'][] = $data;
        }
        
        // Sort periods
        ksort($groupedData);
        
        $data = [
            'renstra_data' => $renstraData,
            'grouped_data' => $groupedData,
            'current_opd' => $currentOpd,
            'title' => 'Rencana Strategis - ' . $titleSuffix
        ];

        return view('adminOpd/renstra/renstra', $data);
    }

    public function tambah_renstra()
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        // Get current OPD info
        $currentOpd = $this->opdModel->find($opdId);

        if (!$currentOpd) {
            return redirect()->to('/login')->with('error', 'Data OPD tidak ditemukan');
        }
        
        // Get RPJMD Sasaran from completed Misi only
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
        
        // Get satuan options
        $satuanOptions = [
            'Persen' => 'Persen (%)',
            'Orang' => 'Orang',
            'Unit' => 'Unit',
            'Dokumen' => 'Dokumen',
            'Kegiatan' => 'Kegiatan',
            'Rupiah' => 'Rupiah',
            'Index' => 'Index',
            'Nilai' => 'Nilai',
            'Predikat' => 'Predikat'
        ];
        
        $data = [
            'rpjmd_sasaran' => $rpjmdSasaran,
            'satuan_options' => $satuanOptions,
            'current_opd' => $currentOpd,
            'title' => 'Tambah Rencana Strategis - ' . $currentOpd['nama_opd']
        ];

        return view('adminOpd/renstra/tambah_renstra', $data);
    }

    public function edit($id = null)
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        // Get Renstra data filtered by user's OPD
        $currentRenstra = $this->renstraModel->getCompleteRenstraById($id, $opdId);

        if (!$currentRenstra) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Sasaran Renstra tidak ditemukan');
        }

        // Get RPJMD Sasaran from completed Misi only
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
        
        $data = [
            'renstra_data' => $currentRenstra,
            'rpjmd_sasaran' => $rpjmdSasaran,
            'title' => 'Edit Rencana Strategis'
        ];

        return view('adminOpd/renstra/edit_renstra', $data);
    }

    // ==================== DATA PROCESSING ====================

    public function save()
    {
        try {
            $data = $this->request->getPost();

             // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id');
            
            // Create new - Use createCompleteRpjmdTransaction for tambah form
            $formattedData = [
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'opd_id' => $opdId,
                'status' => 'draft',
                'sasaran_renstra' => $data['sasaran_renstra'] ?? [],
            ];

            $success = $this->renstraModel->createCompleteRenstra($formattedData);
            
            if ($success) {
                session()->setFlashdata('success', 'Data RENSTRA berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RENSTRA');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'RENSTRA Save Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan saat menyimpan data.');
            return redirect()->back()->withInput();
        }

        return redirect()->to(base_url('adminopd/renstra'));
    }

    
    public function update($id = null)
    {
        try {
            $data = $this->request->getPost();

            dd($data['sasaran_renstra']);
            // Validate required fields
            if (empty($data['rpjmd_sasaran_id']) || empty($data['tahun_mulai']) || empty($data['tahun_akhir'])) {
                throw new \Exception('Data tidak lengkap');
            }

            // Get OPD ID from session (logged in user's OPD)
            $session = session();
            $opdId = $session->get('opd_id');
            
            // Validate OPD ID exists
            if (!$opdId) {
                throw new \Exception('OPD ID tidak ditemukan dalam session. Silakan login ulang.');
            }

            // Prepare data for updating
            $updateData = [
                'opd_id' => $opdId,
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'sasaran' => $data['sasaran_renstra'][0]['sasaran'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'indikator_sasaran' => []
            ];
            
            // Process indikator sasaran (similar to save method)
            if (isset($data['sasaran_renstra']) && is_array($data['sasaran_renstra'])) {
                foreach ($data['sasaran_renstra'] as $sasaran) {
                    if (isset($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])) {
                        foreach ($sasaran['indikator_sasaran'] as $indikator) {
                            $indikatorData = [
                                'indikator_sasaran' => $indikator['indikator_sasaran'],
                                'satuan' => $indikator['satuan'],
                                'target_tahunan' => []
                            ];

                            if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                foreach ($indikator['target_tahunan'] as $target) {
                                    $indikatorData['target_tahunan'][] = [
                                        'tahun' => $target['tahun'],
                                        'target' => $target['target']
                                    ];
                                }
                            }

                            $updateData['indikator_sasaran'][] = $indikatorData;
                        }
                    }
                }
            }

            // Update in database
            $success = $this->renstraModel->updateCompleteRenstra($id, $updateData);

            if ($success) {
                return redirect()->to('/adminopd/renstra')->with('success', 'Data Renstra berhasil disimpan');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data Renstra');
            }
            
            if ($success) {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Data Renstra berhasil diupdate',
                        'redirect' => base_url('adminopd/renstra')
                    ]);
                } else {
                    return redirect()->to('adminopd/renstra')->with('success', 'Data Renstra berhasil diupdate');
                }
            } else {
                throw new \Exception('Gagal mengupdate data');
            }

        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/renstra'));
    }

    public function delete($id = null)
    {
        if (!$id) {
            return redirect()->back()->with('error', 'ID tidak valid');
        }

        try {
            // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id');
            
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Session expired. Silakan login ulang.');
            }

            // Verify that the Renstra exists and belongs to user's OPD
            $renstraData = $this->renstraModel->getSasaranById($id);
            
            if (!$renstraData) {
                return redirect()->back()->with('error', 'Data Renstra tidak ditemukan');
            }

            // Check OPD ownership
            if ($renstraData['opd_id'] != $opdId) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus data Renstra ini');
            }

            $success = $this->renstraModel->deleteCompleteRenstra($id);

            if ($success) {
                return redirect()->to(base_url('adminopd/renstra'))->with('success', 'Data Renstra berhasil dihapus');
            } else {
                return redirect()->back()->with('error', 'Gagal menghapus data');
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Update RENSTRA status via AJAX
     */
    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }
        
        // Get JSON input (like RENJA)
        $json = $this->request->getJSON(true);
        $id = $json['id'] ?? null;

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID harus diisi']);
        }
        
        try {
            // Check OPD session
            $session = session();
            $opdId = $session->get('opd_id');
            
            if (!$opdId) {
                return $this->response->setJSON(['success' => false, 'message' => 'Session expired. Silakan login ulang.']);
            }
            
            // Get current status
            $currentRenstra = $this->renstraModel->getRenstraById($id);
            if (!$currentRenstra) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status
            $currentStatus = $currentRenstra['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->renstraModel->updateRenstraStatus($id, $newStatus);
            
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


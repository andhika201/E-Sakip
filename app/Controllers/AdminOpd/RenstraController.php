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
        $renstraData = $this->renstraModel->getRenstraForTable($opdId);
        
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

    public function edit_renstra($id = null)
    {
        if (!$id) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Sasaran Renstra tidak ditemukan');
        }

        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get Renstra data filtered by user's OPD
        $renstraData = $this->renstraModel->getCompleteRenstraStructure($opdId);
        $currentRenstra = null;
        
        foreach ($renstraData as $data) {
            if ($data['sasaran_id'] == $id) {
                $currentRenstra = $data;
                break;
            }
        }

        if (!$currentRenstra) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Sasaran Renstra tidak ditemukan');
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
            'renstra_data' => $currentRenstra,
            'rpjmd_sasaran' => $rpjmdSasaran,
            'satuan_options' => $satuanOptions,
            'title' => 'Edit Rencana Strategis'
        ];

        return view('adminOpd/renstra/edit_renstra', $data);
    }

    // ==================== DATA PROCESSING ====================

    public function save()
    {

        try {
            $data = $this->request->getPost();
            
            // Validate required fields
            if (empty($data['rpjmd_sasaran_id']) || empty($data['tahun_mulai']) || empty($data['tahun_akhir'])) {
                throw new \Exception('Data tidak lengkap');
            }

            // Validate sasaran_renstra structure
            if (!isset($data['sasaran_renstra']) || !is_array($data['sasaran_renstra']) || empty($data['sasaran_renstra'])) {
                throw new \Exception('Data sasaran renstra tidak ditemukan atau tidak valid');
            }

            // Validate first sasaran item
            if (!isset($data['sasaran_renstra'][0]) || !isset($data['sasaran_renstra'][0]['sasaran'])) {
                throw new \Exception('Field sasaran pada sasaran renstra pertama tidak ditemukan');
            }

            // Get OPD ID from session (logged in user's OPD)
            $session = session();
            $opdId = $session->get('opd_id');
            
            // Validate OPD ID exists
            if (!$opdId) {
                throw new \Exception('OPD ID tidak ditemukan dalam session. Silakan login ulang.');
            }

            // Prepare data for saving
            $saveData = [
                'opd_id' => $opdId,
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'sasaran' => $data['sasaran_renstra'][0]['sasaran'] ?? '',
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'indikator_sasaran' => []
            ];

            // Process indikator sasaran
            if (isset($data['sasaran_renstra']) && is_array($data['sasaran_renstra'])) {
                foreach ($data['sasaran_renstra'] as $sasaran) {
                    if (isset($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])) {
                        foreach ($sasaran['indikator_sasaran'] as $indikator) {
                            $indikatorData = [
                                'indikator_sasaran' => $indikator['indikator_sasaran'],
                                'satuan' => $indikator['satuan'],
                                'target_tahunan' => []
                            ];

                            // Process target tahunan
                            if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                foreach ($indikator['target_tahunan'] as $target) {
                                    $indikatorData['target_tahunan'][] = [
                                        'tahun' => $target['tahun'],
                                        'target' => $target['target']
                                    ];
                                }
                            }

                            $saveData['indikator_sasaran'][] = $indikatorData;
                        }
                    }
                }
            }

            // Save to database
            $result = $this->renstraModel->saveCompleteRenstra($saveData);

            if ($result) {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Data Renstra berhasil disimpan',
                        'redirect' => base_url('adminopd/renstra')
                    ]);
                } else {
                    return redirect()->to('adminopd/renstra')->with('success', 'Data Renstra berhasil disimpan');
                }
            } else {
                throw new \Exception('Gagal menyimpan data');
            }

        } catch (\Exception $e) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            } else {
                return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
            }
        }
    }

    public function update($id = null)
    {
        try {
            $data = $this->request->getPost();
            
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
            $result = $this->renstraModel->updateCompleteRenstra($id, $updateData);

            if ($result) {
                return redirect()->to('/adminopd/pk_admin')->with('success', 'Data PK Admin berhasil disimpan');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data PK Admin');
            }
            
            if ($result) {
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
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            } else {
                return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
            }
        }
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'ID tidak valid'
            ]);
        }

        try {
            // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id');
            
            if (!$opdId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Session expired. Silakan login ulang.'
                ]);
            }

            // Verify that the Renstra exists and belongs to user's OPD
            $renstraData = $this->renstraModel->getSasaranById($id);
            
            if (!$renstraData) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Data Renstra tidak ditemukan'
                ]);
            }

            // Check OPD ownership
            if ($renstraData['opd_id'] != $opdId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menghapus data Renstra ini'
                ]);
            }
            //         'message' => 'Anda tidak memiliki akses untuk menghapus data ini'
            //     ]);
            // }

            $result = $this->renstraModel->deleteCompleteRenstra($id);

            if ($result) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Data Renstra berhasil dihapus',
                    'redirect' => base_url('adminopd/renstra')
                ]);
            } else {
                throw new \Exception('Gagal menghapus data');
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // ==================== AJAX DATA ENDPOINTS ====================

    public function getDataByOpd()
    {
        try {
            // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id');
            
            if (!$opdId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Session expired. Silakan login ulang.'
                ]);
            }

            // Get Renstra data filtered by user's OPD
            $renstraData = $this->renstraModel->getRenstraForTable($opdId);
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $renstraData
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function getRpjmdSasaran()
    {
        try {
            $rpjmdSasaran = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $rpjmdSasaran
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function getCurrentOpd()
    {
        try {
            // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id');
            
            if (!$opdId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Session expired. Silakan login ulang.'
                ]);
            }

            // Get current user's OPD data
            $opdData = $this->opdModel->find($opdId);
            
            if (!$opdData) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Data OPD tidak ditemukan'
                ]);
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $opdData
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}


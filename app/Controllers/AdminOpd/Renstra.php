<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RenstraModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;

class Renstra extends BaseController
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
        // Get all Renstra data for table display
        $renstraData = $this->renstraModel->getRenstraForTable();
        
        // Get OPD list for filter
        $opdList = $this->opdModel->getAllOpd();
        
        // Group data by period if needed
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
            'opd_list' => $opdList,
            'title' => 'Rencana Strategis'
        ];

        return view('adminOpd/renstra/renstra', $data);
    }

    public function tambah_renstra()
    {
        // Get RPJMD Sasaran for dropdown
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaran();
        
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
            'title' => 'Tambah Rencana Strategis'
        ];

        return view('adminOpd/renstra/tambah_renstra', $data);
    }

    public function edit_renstra($id = null)
    {
        if (!$id) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Sasaran Renstra tidak ditemukan');
        }

        // Get Renstra data by ID
        $renstraData = $this->renstraModel->getCompleteRenstraStructure();
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

        // Get RPJMD Sasaran for dropdown
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaran();
        
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
        if (!$this->request->isAJAX() && !$this->request->getMethod() === 'POST') {
            return redirect()->back()->with('error', 'Metode request tidak valid');
        }

        try {
            $data = $this->request->getPost();
            
            // Validate required fields
            if (empty($data['rpjmd_sasaran_id']) || empty($data['tahun_awal']) || empty($data['tahun_akhir'])) {
                throw new \Exception('Data tidak lengkap');
            }

            // Get OPD ID from session (assuming user is logged in)
            $session = session();
            $opdId = $session->get('opd_id') ?? 1; // Default to 1 if not set

            // Prepare data for saving
            $saveData = [
                'opd_id' => $opdId,
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'sasaran' => $data['sasaran_renstra'][0]['sasaran'] ?? '',
                'tahun_mulai' => $data['tahun_awal'],
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
        if (!$id) {
            return redirect()->back()->with('error', 'ID tidak valid');
        }

        if (!$this->request->isAJAX() && !$this->request->getMethod() === 'POST') {
            return redirect()->back()->with('error', 'Metode request tidak valid');
        }

        try {
            $data = $this->request->getPost();
            
            // Validate required fields
            if (empty($data['rpjmd_sasaran_id']) || empty($data['tahun_awal']) || empty($data['tahun_akhir'])) {
                throw new \Exception('Data tidak lengkap');
            }

            // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id') ?? 1;

            // Prepare data for updating
            $updateData = [
                'opd_id' => $opdId,
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'sasaran' => $data['sasaran_renstra'][0]['sasaran'] ?? '',
                'tahun_mulai' => $data['tahun_awal'],
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
            $result = $this->renstraModel->deleteCompleteRenstra($id);

            if ($result) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Data Renstra berhasil dihapus'
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

    public function getDataByOpd($opdId = null)
    {
        try {
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
            $rpjmdSasaran = $this->rpjmdModel->getAllSasaran();
            
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
}

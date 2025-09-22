<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\IkuOpdModel;
use App\Models\OpdModel;
use App\Models\Opd\RenstraModel;

class IkuOpdController extends BaseController
{
    protected $ikuOpdModel;
    protected $opdModel;
    protected $renstraModel;

    public function __construct()
    {
        $this->ikuOpdModel = new IkuOpdModel();
        $this->opdModel = new OpdModel();
        $this->renstraModel = new RenstraModel();
    }

    // public function index()
    // {
    //     // Get OPD ID from session (logged in user's OPD)
    //     $session = session();
    //     $opdId = $session->get('opd_id');
        
    //     // If no OPD ID in session, redirect to login or show error
    //     if (!$opdId) {
    //         return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
    //     }

    //     // Get IKU data using simple method like RENSTRA
    //     $ikuData = $this->ikuOpdModel->getCompleteIkuByOpd($opdId);
        
    //     // Group data by period like RENSTRA
    //     $groupedData = [];
    //     foreach ($ikuData as $item) {
    //         $periodKey = $item['tahun_mulai'] . '-' . $item['tahun_akhir'];
    //         if (!isset($groupedData[$periodKey])) {
    //             $groupedData[$periodKey] = [
    //                 'period' => $periodKey,
    //                 'years' => range($item['tahun_mulai'], $item['tahun_akhir'])
    //             ];
    //         }
    //     }
        
    //     $data = [
    //         'iku_data' => $ikuData,
    //         'grouped_data' => $groupedData,
    //         'title' => 'Data IKU OPD'
    //     ];

    //     return view('adminOpd/iku_opd/iku_opd', $data);
    // }

    public function index()
    {
        // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        
        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get IKU data filtered by user's OPD
        $ikuData = $this->ikuOpdModel->getCompleteIkuByOpd($opdId);

        // Get current OPD info
        $currentOpd = $this->opdModel->find($opdId);
        if (!$currentOpd) {
            return redirect()->to('/login')->with('error', 'Data OPD tidak ditemukan');
        }
        
        // Set title based on current OPD
        $titleSuffix = $currentOpd['nama_opd'];  // Group data by period

        $groupedData = [];
        foreach ($ikuData as $data) {
            $periodKey = $data['tahun_mulai'] . '-' . $data['tahun_akhir'];
            
            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                    'years' => range($data['tahun_mulai'], $data['tahun_akhir']),
                    'iku_data' => []
                ];
            }

            $groupedData[$periodKey]['iku_data'][] = $data;
        }
        
        // Sort periods
        ksort($groupedData);
        
        $data = [
            'iku_data' => $ikuData,
            'grouped_data' => $groupedData,
            'current_opd' => $currentOpd,
            'title' => 'Rencana Strategis - ' . $titleSuffix
        ];

        return view('adminOpd/iku_opd/iku_opd', $data);
    }

    public function tambah()
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get Renstra sasaran for this OPD to populate dropdown
        $renstraSasaran = $this->renstraModel->getRenstraSasaranByOpd($opdId);
        
        $data = [
            'renstra_sasaran' => $renstraSasaran,
            'title' => 'Tambah IKU'
        ];

        return view('adminOpd/iku_opd/tambah_iku_opd', $data);
    }

    public function save()
    {
        try {
            $data = $this->request->getPost();

             // Get OPD ID from session
            $session = session();
            $opdId = $session->get('opd_id');
            
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
            }
            
            // Create new - Use createCompleteIku for tambah form (like RENSTRA)
            $formattedData = [
                'renstra_sasaran_id' => $data['renstra_sasaran_id'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'opd_id' => $opdId,
                'status' => 'draft',
                'sasaran_iku' => $data['sasaran_iku'] ?? [],
            ];

            $success = $this->ikuOpdModel->createCompleteIku($formattedData);
            
            if ($success) {
                session()->setFlashdata('success', 'Data IKU berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data IKU');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'IKU Save Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return redirect()->to('adminopd/iku_opd');
    }

    public function edit($id)
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get detailed IKU data for editing (with indikator kinerja and target tahunan)
        $ikuData = $this->ikuOpdModel->getDetailedIkuById($id);
        
        if (!$ikuData) {
            return redirect()->to('adminopd/iku_opd')->with('error', 'Data IKU tidak ditemukan');
        }

        // Get Renstra sasaran for dropdown
        $renstraSasaran = $this->renstraModel->getRenstraSasaranByOpd($opdId);
        
        $data = [
            'iku_data' => $ikuData,
            'renstra_sasaran' => $renstraSasaran,
            'title' => 'Edit IKU'
        ];

        return view('adminOpd/iku_opd/edit_iku_opd', $data);
    }

    public function update($id = null)
    {
        try {
            $data = $this->request->getPost();

            // Validate required fields
            if (empty($data['renstra_sasaran_id']) || empty($data['tahun_mulai']) || empty($data['tahun_akhir'])) {
                throw new \Exception('Data tidak lengkap');
            }

            // Get OPD ID from session (logged in user's OPD)
            $session = session();
            $opdId = $session->get('opd_id');
            
            // Validate OPD ID exists
            if (!$opdId) {
                throw new \Exception('OPD ID tidak ditemukan dalam session. Silakan login ulang.');
            }

            // Prepare data for updating (following RENSTRA pattern)
            $updateData = [
                'opd_id' => $opdId,
                'renstra_sasaran_id' => $data['renstra_sasaran_id'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'status' => $data['status'] ?? 'draft',
                'sasaran_iku' => []
            ];
            
            // Process sasaran IKU (similar to RENSTRA's indikator_sasaran processing)
            if (isset($data['sasaran_iku']) && is_array($data['sasaran_iku'])) {
                foreach ($data['sasaran_iku'] as $sasaran) {
                    $sasaranData = [
                        'id' => $sasaran['id'] ?? null,
                        'sasaran' => $sasaran['sasaran'],
                        'indikator_kinerja' => []
                    ];

                    if (isset($sasaran['indikator_kinerja']) && is_array($sasaran['indikator_kinerja'])) {
                        foreach ($sasaran['indikator_kinerja'] as $indikator) {
                            $indikatorData = [
                                'id' => $indikator['id'] ?? null,
                                'indikator_kinerja' => $indikator['indikator_kinerja'],
                                'definisi_formulasi' => $indikator['definisi_formulasi'],
                                'satuan' => $indikator['satuan'],
                                'program_pendukung' => $indikator['program_pendukung'],
                                'target_tahunan' => []
                            ];

                            if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                foreach ($indikator['target_tahunan'] as $target) {
                                    $indikatorData['target_tahunan'][] = [
                                        'id' => $target['id'] ?? null,
                                        'tahun' => $target['tahun'],
                                        'target' => $target['target']
                                    ];
                                }
                            }

                            $sasaranData['indikator_kinerja'][] = $indikatorData;
                        }
                    }

                    $updateData['sasaran_iku'][] = $sasaranData;
                }
            }

            // Update in database (following RENSTRA pattern)
            $success = $this->ikuOpdModel->updateCompleteIku($id, $updateData);

            if ($success) {
                return redirect()->to('/adminopd/iku_opd')->with('success', 'Data IKU berhasil disimpan');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data IKU');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'IKU Update Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect()->to('adminopd/iku_opd');
    }

    public function delete($id)
    {
        try {
            $success = $this->ikuOpdModel->deleteIku($id);
            
            if ($success) {
                session()->setFlashdata('success', 'Data IKU berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus data IKU');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'IKU Delete Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return redirect()->to('adminopd/iku_opd');
    }

    public function detail($id)
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Get detailed IKU data
        $ikuData = $this->ikuOpdModel->getDetailedIkuById($id);
        
        if (!$ikuData) {
            return redirect()->to('adminopd/iku_opd')->with('error', 'Data IKU tidak ditemukan');
        }

        $data = [
            'iku_data' => $ikuData,
            'title' => 'Detail IKU'
        ];

        return view('adminOpd/iku_opd/detail_iku_opd', $data);
    }

    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }
        
        // Get JSON input (like RENSTRA)
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
            $currentIku = $this->ikuOpdModel->getIkuById($id);
            if (!$currentIku) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status (same as RENSTRA: draft ↔ selesai)
            $currentStatus = $currentIku['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->ikuOpdModel->updateStatus($id, $newStatus);
            
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
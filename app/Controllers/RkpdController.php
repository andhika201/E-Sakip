<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RpjmdModel;
use App\Models\RkpdModel;

class RkpdController extends BaseController
{
    protected $rpjmdModel;
    protected $rkpdModel;

    public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
        $this->rkpdModel = new RkpdModel();
    }

    public function index()
    {
        // Get all RKPD data (no server-side filtering)
        $rkpdData = $this->rkpdModel->getAllRkpd();
        
        // Get unique years for filter dropdown
        $availableYears = [];
        foreach ($rkpdData as $rkpd) {
            foreach ($rkpd['indikator'] as $indikator) {
                if (!empty($indikator['tahun']) && !in_array($indikator['tahun'], $availableYears)) {
                    $availableYears[] = $indikator['tahun'];
                }
            }
        }
        sort($availableYears);

        $data = [
            'title' => 'Rencana Kerja Tahunan',
            'rkpd_data' => $rkpdData,
            'available_years' => $availableYears
        ];
        
        return view('adminKabupaten/rkpd/rkpd', $data);
    }  
    
    public function tambah()
    {
        // Get RPJMD Sasaran from completed Misi only
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
        
        $data = [
            'rpjmd_sasaran' => $rpjmdSasaran,
            'title' => 'Tambah Kerja Tahunan',
            'validation' => \Config\Services::validation()
        ];

        return view('adminKabupaten/rkpd/tambah_rkpd', $data);
    }

    public function save()
    {
        try {
            $data = $this->request->getPost();
            
            // Create new - Use createCompleteRpjmdTransaction for tambah form
            $formattedData = [
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'status' => 'draft',
                'sasaran_rkpd' => $data['sasaran_rkpd'] ?? [],
            ];

            $success = $this->rkpdModel->createCompleteRkpd($formattedData);
            
            if ($success) {
                session()->setFlashdata('success', 'Data RKPD berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RKPD');
                return redirect()->back()->withInput();
            }
            
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rkpd'));
    }
    
    public function edit($id = null){

        if (!$id) {
            session()->setFlashdata('error', 'ID RKPD Sasaran tidak ditemukan');
            return redirect()->to(base_url('adminkab/rkpd'));
        }

        // Fetch the complete RKPD data for the RPJMD Sasaran ID
        $rkpdData = $this->rkpdModel->getRkpdById($id);

        // Get RPJMD Sasaran data
        $rpjmdSasaranData = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
        if (!$rpjmdSasaranData) {
            session()->setFlashdata('error', 'Data RPJMD Sasaran tidak ditemukan');
            return redirect()->to(base_url('adminkab/rkpd'));
        }

        $data = [
            'title' => 'Edit RKPD',
            'rkpd_data' => $rkpdData,
            'rkpd_sasaran_id' => $id,
            'rpjmd_sasaran' => $rpjmdSasaranData,
            'validation' => \Config\Services::validation()
        ];

        return view('adminKabupaten/rkpd/edit_rkpd', $data);
    }

    public function update(){

        try {
            $data = $this->request->getPost();
            
            if (!isset($data['rkpd_sasaran_id']) || empty($data['rkpd_sasaran_id'])) {
                session()->setFlashdata('error', 'ID RKPD Sasaran tidak ditemukan');
                return redirect()->back()->withInput();
            }

            $rkpdSasaranId = $data['rkpd_sasaran_id'];

            // Use the updateCompleteRkpd method
            $result = $this->rkpdModel->updateCompleteRkpd($rkpdSasaranId, $data);

           
            if ($result) {
                session()->setFlashdata('success', 'Data RKPD berhasil diupdate');
            } else {
                session()->setFlashdata('error', 'Gagal mengupdate data RKPD');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rkpd'));
    }

    public function delete($id)
    {
        try {
            // Verify that the RKPD exists and belongs to user's OPD
            $rkpdData = $this->rkpdModel->getRkpdById($id);

            if (!$rkpdData) {
                return redirect()->back()->with('error', 'Data RKPD tidak ditemukan');
            }

            $success = $this->rkpdModel->deleteCompleteRkpd($id);

            if ($success) {
                return redirect()->to(base_url('adminkab/rkpd'))->with('success', 'Data RKPD berhasil dihapus');
            } else {
                return redirect()->back()->with('error', 'Gagal menghapus data');
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
        
        return redirect()->to(base_url('adminkab/rkpd'));
    }

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
            $currentRkpd = $this->rkpdModel->getRkpdSasaranById($id);
            if (!$currentRkpd) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status
            $currentStatus = $currentRkpd['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->rkpdModel->updateRkpdStatus($id, $newStatus);
            
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

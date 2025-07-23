<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RpjmdModel;
use App\Models\RktModel;

class RktController extends BaseController
{
    protected $rpjmdModel;
    protected $rktModel;

     public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
        $this->rktModel = new RktModel();
    }

    public function index()
    {
        // Get all RKT data (no server-side filtering)
        $rktData = $this->rktModel->getAllRkt();
        
        // Get unique years for filter dropdown
        $availableYears = [];
        foreach ($rktData as $rkt) {
            foreach ($rkt['indikator'] as $indikator) {
                if (!empty($indikator['tahun']) && !in_array($indikator['tahun'], $availableYears)) {
                    $availableYears[] = $indikator['tahun'];
                }
            }
        }
        sort($availableYears);

        $data = [
            'title' => 'Rencana Kerja Tahunan',
            'rkt_data' => $rktData,
            'available_years' => $availableYears
        ];
        
        return view('adminKabupaten/rkt/rkt', $data);
    }    public function tambah()
    {
        // Get RPJMD Sasaran from completed Misi only
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
        
        $data = [
            'rpjmd_sasaran' => $rpjmdSasaran,
            'title' => 'Tambah Kerja Tahunan',
            'validation' => \Config\Services::validation()
        ];

        return view('adminKabupaten/rkt/tambah_rkt', $data);
    }

    public function save()
    {
        try {
            $data = $this->request->getPost();
            
            // Create new - Use createCompleteRpjmdTransaction for tambah form
            $formattedData = [
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'status' => 'draft',
                'sasaran_rkt' => $data['sasaran_rkt'] ?? [],
            ];

            $success = $this->rktModel->createCompleteRkt($formattedData);
            
            if ($success) {
                session()->setFlashdata('success', 'Data RKT berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RKT');
                return redirect()->back()->withInput();
            }
            
        } catch (\Exception $e) {
            log_message('error', 'RKT Save Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan saat menyimpan data.');
            return redirect()->back()->withInput();
        }

        return redirect()->to(base_url('adminkab/rkt'));
    }
    
    public function edit($id = null){

        if (!$id) {
            session()->setFlashdata('error', 'ID RKT Sasaran tidak ditemukan');
            return redirect()->to(base_url('adminkab/rkt'));
        }

        // Fetch the complete RKT data for the RPJMD Sasaran ID
        $rktData = $this->rktModel->getRktById($id);

        // Get RPJMD Sasaran data
        $rpjmdSasaranData = $this->rpjmdModel->getAllSasaranFromCompletedMisi();
        if (!$rpjmdSasaranData) {
            session()->setFlashdata('error', 'Data RPJMD Sasaran tidak ditemukan');
            return redirect()->to(base_url('adminkab/rkt'));
        }

        $data = [
            'title' => 'Edit RKT',
            'rkt_data' => $rktData,
            'rkt_sasaran_id' => $id, // Pass the RKT Sasaran ID to the view
            'rpjmd_sasaran' => $rpjmdSasaranData,
            'validation' => \Config\Services::validation()
        ];

        return view('adminKabupaten/rkt/edit_rkt', $data);
    }

    public function update(){

        try {
            $data = $this->request->getPost();
            
            // Debug: log the received data
            log_message('debug', 'Received form data: ' . json_encode($data));
            
            if (!isset($data['rkt_sasaran_id']) || empty($data['rkt_sasaran_id'])) {
                session()->setFlashdata('error', 'ID RKT Sasaran tidak ditemukan');
                return redirect()->back()->withInput();
            }

            $rktSasaranId = $data['rkt_sasaran_id'];

            // Use the updateCompleteRkt method
            $result = $this->rktModel->updateCompleteRkt($rktSasaranId, $data);

           
            if ($result) {
                session()->setFlashdata('success', 'Data RKT berhasil diupdate');
            } else {
                session()->setFlashdata('error', 'Gagal mengupdate data RKT');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rkt'));
    }

    public function delete(){

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
            $currentRkt = $this->rktModel->getRktSasaranById($id);
            if (!$currentRkt) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status
            $currentStatus = $currentRkt['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->rktModel->updateRktStatus($id, $newStatus);
            
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

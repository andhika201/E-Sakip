<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\RenstraModel;
use App\Models\Opd\RenjaModel;
use App\Models\OpdModel;

class RenjaController extends BaseController
{
    protected $renstraModel;
    protected $renjaModel;
    protected $opdModel;

     public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->renjaModel = new RenjaModel();
        $this->opdModel = new OpdModel();
    }

    public function index()
    {
        // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        
        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        
        // Get all RENJA data (no server-side filtering)
        $renjaData = $this->renjaModel->getAllRenja();
        
        // Get unique years for filter dropdown
        $availableYears = [];
        foreach ($renjaData as $renja) {
            foreach ($renja['indikator'] as $indikator) {
                if (!empty($indikator['tahun']) && !in_array($indikator['tahun'], $availableYears)) {
                    $availableYears[] = $indikator['tahun'];
                }
            }
        }
        sort($availableYears);

        $data = [
            'title' => 'Rencana Kerja Tahunan',
            'renja_data' => $renjaData,
            'available_years' => $availableYears
        ];
        
        return view('adminOpd/renja/renja', $data);
    }    
    
    
    public function tambah()
    {
        // Get RPJMD Sasaran from completed Misi only
        $renstraSasaran = $this->renstraModel->getAllSasaranFromCompletedMisi();
        
        $data = [
            'renstra_sasaran' => $renstraSasaran,
            'title' => 'Tambah Kerja Tahunan',
            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/renja/tambah_renja', $data);
    }

    public function save()
    {
        try {
            $data = $this->request->getPost();
            
            // Create new - Use createCompleteRenstra for tambah form
            $formattedData = [
                'renstra_sasaran_id' => $data['renstra_sasaran_id'],
                'status' => 'draft',
                'sasaran_renja' => $data['sasaran_renja'] ?? [],
            ];

            $success = $this->renjaModel->createCompleteRenja($formattedData);
            
            if ($success) {
                session()->setFlashdata('success', 'Data RENJA berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RENJA');
                return redirect()->back()->withInput();
            }
            
        } catch (\Exception $e) {
            log_message('error', 'RENJA Save Error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Terjadi kesalahan saat menyimpan data.');
            return redirect()->back()->withInput();
        }

        return redirect()->to(base_url('adminopd/renja'));
    }
    
    public function edit($id = null){

        if (!$id) {
            session()->setFlashdata('error', 'ID RENJA Sasaran tidak ditemukan');
            return redirect()->to(base_url('adminopd/renja'));
        }

        // Fetch the complete RENJA data for the RPJMD Sasaran ID
        $renjaData = $this->renjaModel->getRenjaById($id);

        // Get RPJMD Sasaran data
        $renstraSasaranData = $this->renstraModel->getAllSasaranFromCompletedMisi();
        if (!$renstraSasaranData) {
            session()->setFlashdata('error', 'Data RPJMD Sasaran tidak ditemukan');
            return redirect()->to(base_url('adminopd/renja'));
        }

        $data = [
            'title' => 'Edit RENJA',
            'renja_data' => $renjaData,
            'renja_sasaran_id' => $id, // Pass the RENJA Sasaran ID to the view
            'renstra_sasaran' => $renstraSasaranData,
            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/renja/edit_renja', $data);
    }

    public function update(){

        try {
            $data = $this->request->getPost();
            
            // Debug: log the received data
            log_message('debug', 'Received form data: ' . json_encode($data));
            
            if (!isset($data['renja_sasaran_id']) || empty($data['renja_sasaran_id'])) {
                session()->setFlashdata('error', 'ID RENJA Sasaran tidak ditemukan');
                return redirect()->back()->withInput();
            }

            $renjaSasaranId = $data['renja_sasaran_id'];

            // Use the updateCompleteRenja method
            $result = $this->renjaModel->updateCompleteRenja($renjaSasaranId, $data);

           
            if ($result) {
                session()->setFlashdata('success', 'Data RENJA berhasil diupdate');
            } else {
                session()->setFlashdata('error', 'Gagal mengupdate data RENJA');
            }
            
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/renja'));
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
            $currentRenja = $this->renjaModel->getRenjaSasaranById($id);
            if (!$currentRenja) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            
            // Toggle status
            $currentStatus = $currentRenja['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';
            
            $result = $this->renjaModel->updateRenjaStatus($id, $newStatus);
            
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

<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\RenstraModel;
use App\Models\Opd\IkuModel;
use App\Models\OpdModel;

class IkuController extends BaseController
{
    protected $renstraModel;
    protected $ikuModel;
    protected $opdModel;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->ikuModel = new IkuModel();
        $this->opdModel = new OpdModel();
    }

    public function index()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $ikuData = $this->ikuModel->where('opd_id', $opdId)->findAll();
        $data = [
            'title' => 'Indikator Kinerja Utama',
            'iku_data' => $ikuData
        ];
        return view('adminOpd/iku/iku', $data);
    }

    public function tambah()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $status = 'selesai';
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $renstraSasaran = $this->renstraModel->getAllRenstraByStatus($status, $opdId);
        $data = [
            'renstra_sasaran' => $renstraSasaran,
            'title' => 'Tambah IKU',
            'validation' => \Config\Services::validation()
        ];
        return view('adminOpd/iku/tambah_iku', $data);
    }

    public function save()
    {
        try {
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
            }
            // Loop over each sasaran_iku and call createCompleteIku for each
            $sasaranIkuList = $data['sasaran_iku'] ?? [];
            if (empty($sasaranIkuList)) {
                throw new \Exception('Data sasaran IKU tidak ditemukan');
            }
            foreach ($sasaranIkuList as $sasaranIku) {
                $ikuData = [
                    'opd_id' => $opdId,
                    'renstra_sasaran_id' => $data['renstra_sasaran_id'] ?? null,
                    'sasaran' => $sasaranIku['sasaran'] ?? null,
                    'status' => 'draft',
                    // tahun_mulai & tahun_akhir can be set from renstra_sasaran if needed
                ];
                // Prepare indikator array for model
                $ikuData['indikator'] = [];
                if (!empty($sasaranIku['indikator_kinerja']) && is_array($sasaranIku['indikator_kinerja'])) {
                    foreach ($sasaranIku['indikator_kinerja'] as $indikator) {
                        $indikatorData = [
                            'indikator_kinerja' => $indikator['indikator_kinerja'] ?? null,
                            'definisi_formulasi' => $indikator['definisi_formulasi'] ?? null,
                            'satuan' => $indikator['satuan'] ?? null,
                            'program_pendukung' => $indikator['program_pendukung'] ?? null,
                            'target_tahunan' => $indikator['target_tahunan'] ?? [],
                        ];
                        $ikuData['indikator'][] = $indikatorData;
                    }
                }
                $this->ikuModel->createCompleteIku($ikuData);
            }
            session()->setFlashdata('success', 'Data IKU berhasil ditambahkan');
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Gagal menambahkan data IKU: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
        return redirect()->to(base_url('adminopd/iku'));
    }

    public function edit($id)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $status = 'selesai';
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $ikuData = $this->ikuModel->find($id);
        $renstraSasaran = $this->renstraModel->getAllRenstraByStatus($status, $opdId);
        $data = [
            'title' => 'Edit IKU',
            'iku_data' => $ikuData,
            'renstra_sasaran' => $renstraSasaran,
            'validation' => \Config\Services::validation()
        ];
        return view('adminOpd/iku/edit_iku', $data);
    }

    public function update()
    {
        try {
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
            }
            $id = $data['id'] ?? null;
            if (!$id) {
                session()->setFlashdata('error', 'ID IKU tidak ditemukan');
                return redirect()->back()->withInput();
            }
            $updateData = [
                'opd_id' => $opdId,
                'renstra_sasaran_id' => $data['renstra_sasaran_id'] ?? null,
                'sasaran_iku' => $data['sasaran_iku'] ?? null,
                'indikator' => $data['indikator'] ?? null,
                'target' => $data['target'] ?? null,
                'satuan' => $data['satuan'] ?? null,
                'tahun' => $data['tahun'] ?? null,
            ];
            $this->ikuModel->update($id, $updateData);
            session()->setFlashdata('success', 'Data IKU berhasil diupdate');
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data IKU: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
        return redirect()->to(base_url('adminopd/iku'));
    }

    public function delete($id)
    {
        try {
            $this->ikuModel->delete($id);
            session()->setFlashdata('success', 'Data IKU berhasil dihapus');
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Gagal menghapus data IKU: ' . $e->getMessage());
        }
        return redirect()->to(base_url('adminopd/iku'));
    }
}

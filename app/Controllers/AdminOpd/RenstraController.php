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
        $opdId = $this->request->getGet('opd_id') ?? session()->get('opd_id');
        $status = $this->request->getGet('status');
        $periode = $this->request->getGet('periode');

        $renstraData = $this->renstraModel->getAllRenstra($opdId, null, $periode, $status);

        // Ambil daftar periode unik untuk dropdown filter
        $db = \Config\Database::connect();
        $periodeList = $db->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy('tahun_mulai, tahun_akhir')
            ->get()->getResultArray();

        // Grouped data untuk header tahun
        $grouped_data = [];
        foreach ($periodeList as $p) {
            $years = [];
            for ($y = $p['tahun_mulai']; $y <= $p['tahun_akhir']; $y++) {
                $years[] = $y;
            }
            $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
            $grouped_data[$key] = [
                'period' => $key,
                'years' => $years
            ];
        }

        return view('adminOpd/renstra/renstra', [
            'title' => 'RENSTRA',
            'renstra_data' => $renstraData,
            'grouped_data' => $grouped_data,
            'selected_opd' => $opdId,
            'selected_status' => $status,
            'selected_periode' => $periode
        ]);
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
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $currentRenstra = $this->renstraModel->getCompleteRenstraById($id, $opdId);
        if (!$currentRenstra) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Renstra tidak ditemukan');
        }

        // Untuk dropdown pilih sasaran RPJMD
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaranFromCompletedMisi();

        return view('adminOpd/renstra/edit_renstra', [
            'renstra_data' => $currentRenstra,
            'rpjmd_sasaran' => $rpjmdSasaran,
            'title' => 'Edit Rencana Strategis'
        ]);
    }


    // ==================== DATA PROCESSING ====================

    public function save()
    {
        try {
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');

            // Validasi
            if (empty($data['rpjmd_sasaran_id']) || empty($data['tujuan_renstra']) || empty($data['tahun_mulai']) || empty($data['tahun_akhir']) || empty($data['sasaran_renstra'])) {
                throw new \Exception('Data tidak lengkap');
            }

            // 1. Insert atau ambil tujuan
            $tujuanId = $this->renstraModel->createOrGetTujuan($data['rpjmd_sasaran_id'], trim($data['tujuan_renstra']));

            // 2. Loop setiap sasaran_renstra, simpan dengan renstra_tujuan_id
            $sasaranRenstra = [];
            foreach ($data['sasaran_renstra'] as $sasaranItem) {
                $sasaranRenstra[] = [
                    'renstra_tujuan_id' => $tujuanId,
                    'sasaran' => $sasaranItem['sasaran'],
                    'indikator_sasaran' => $sasaranItem['indikator_sasaran'] ?? []
                ];
            }

            $formattedData = [
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'opd_id' => $opdId,
                'status' => 'draft',
                'sasaran_renstra' => $sasaranRenstra,
            ];

            $success = $this->renstraModel->createCompleteRenstra($formattedData);

            if ($success) {
                session()->setFlashdata('success', 'Data RENSTRA berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RENSTRA');
            }

        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Terjadi kesalahan saat menyimpan data.');
            return redirect()->back()->withInput();
        }

        return redirect()->to(base_url('adminopd/renstra'));
    }


    public function update($id = null)
    {
        try {
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');

            if (empty($data['rpjmd_sasaran_id']) || empty($data['tahun_mulai']) || empty($data['tahun_akhir']) || empty($data['sasaran_renstra'])) {
                throw new \Exception('Data tidak lengkap');
            }

            $sasaranItem = $data['sasaran_renstra'][0];
            if (empty($sasaranItem['tujuan_renstra'])) {
                throw new \Exception('Tujuan Renstra wajib diisi');
            }

            $updateData = [
                'opd_id' => $opdId,
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'tujuan_renstra' => $sasaranItem['tujuan_renstra'],
                'sasaran' => $sasaranItem['sasaran'],
                'status' => $data['status'] ?? 'draft',
                'indikator_sasaran' => $sasaranItem['indikator_sasaran'] ?? []
            ];

            $success = $this->renstraModel->updateCompleteRenstra($id, $updateData);

            if ($success) {
                return redirect()->to('/adminopd/renstra')->with('success', 'Data Renstra berhasil disimpan');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data Renstra');
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


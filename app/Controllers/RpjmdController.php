<?php

namespace App\Controllers;

use App\Controllers\BaseController;
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
        // Ambil semua data lengkap (misi->tujuan->indikator_tujuan+target_tujuan, sasaran->indikator_sasaran+target)
        $allMisi = $this->rpjmdModel->getCompleteRpjmdStructure();

        // Kelompokkan per periode: tahun_mulai - tahun_akhir
        $groupedData = [];
        foreach ($allMisi as $misi) {
            $periodKey = $misi['tahun_mulai'] . '-' . $misi['tahun_akhir'];

            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => (int) $misi['tahun_mulai'],
                    'tahun_akhir' => (int) $misi['tahun_akhir'],
                    'years' => range((int) $misi['tahun_mulai'], (int) $misi['tahun_akhir']),
                    'misi_data' => [],
                ];
            }
            $groupedData[$periodKey]['misi_data'][] = $misi;
        }

        // Urutkan key periode
        ksort($groupedData);

        $data['rpjmd_grouped'] = $groupedData;
        $data['rpjmd_data'] = $allMisi;

        // Ringkasan
        $summary = $this->rpjmdModel->getRpjmdSummary();
        $data['rpjmd_summary'] = $summary;

        // Tahun yang tersedia (fallback kalau kosong)
        $availableYears = $summary['years_available'] ?? [];
        $data['available_years'] = array_map(static function ($row) {
            return (int) $row['tahun'];
        }, $availableYears);
        sort($data['available_years']);

        if (empty($data['available_years'])) {
            // fallback 5 tahun umum (bukan patokan header, hanya untuk kompatibilitas lama)
            $data['available_years'] = [2025, 2026, 2027, 2028, 2029];
        }

        return view('adminKabupaten/rpjmd/rpjmd', $data);
    }

    public function tambah()
    {
        // (Opsional) data dropdown; form sudah dinamis
        $data['misi_list'] = $this->rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $this->rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $this->rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $this->rpjmdModel->getAllIndikatorSasaran();

        return view('adminKabupaten/rpjmd/tambah_rpjmd', $data);
    }

    public function save()
    {
        try {
            $post = $this->request->getPost();

            // Debug (opsional)
            $debugFile = WRITEPATH . 'debug_rpjmd_save.txt';
            file_put_contents($debugFile, "=== RPJMD SAVE DEBUG - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
            file_put_contents($debugFile, "RAW POST:\n" . print_r($post, true) . "\n", FILE_APPEND);

            // Normalisasi minimal untuk Misi
            $misi = [
                'misi' => $post['misi'] ?? '',
                'tahun_mulai' => isset($post['tahun_mulai']) ? (int) $post['tahun_mulai'] : 0,
                'tahun_akhir' => isset($post['tahun_akhir']) ? (int) $post['tahun_akhir'] : 0,
                'status' => $post['status'] ?? 'draft',
            ];

            // Struktur tujuan (sudah nested sesuai name[] di form)
            $tujuan = is_array($post['tujuan'] ?? null) ? $post['tujuan'] : [];

            $formattedData = [
                'misi' => $misi,
                'tujuan' => $tujuan,
            ];

            file_put_contents($debugFile, "FORMATTED FOR MODEL:\n" . print_r($formattedData, true) . "\n", FILE_APPEND);

            $misiId = $this->rpjmdModel->createCompleteRpjmdTransaction($formattedData);

            if ($misiId) {
                session()->setFlashdata('success', 'Data RPJMD berhasil ditambahkan');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan data RPJMD');
            }
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function edit($id = null)
    {
        if ($id === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ID tidak ditemukan');
        }

        // Terima ID apa pun (misi/tujuan/sasaran/indikator/target), cari misi parent-nya
        $misiId = $this->rpjmdModel->findMisiIdForAnyEntity((int) $id);
        if (!$misiId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }

        $data['misi'] = $this->rpjmdModel->getMisiById($misiId);
        if (!$data['misi']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }

        // Ambil struktur lengkap & saring untuk misi ini
        $complete = $this->rpjmdModel->getCompleteRpjmdStructure();
        $data['rpjmd_complete'] = null;
        foreach ($complete as $misiData) {
            if ((int) $misiData['id'] === (int) $misiId) {
                $data['rpjmd_complete'] = $misiData;
                break;
            }
        }

        // (Opsional) data dropdown
        $data['misi_list'] = $this->rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $this->rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $this->rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $this->rpjmdModel->getAllIndikatorSasaran();

        return view('adminKabupaten/rpjmd/edit_rpjmd', $data);
    }

    public function update()
    {
        try {
            $data = $this->request->getPost();

            // Debug logging
            $debugFile = WRITEPATH . 'debug_rpjmd_update.txt';
            file_put_contents($debugFile, "=== RPJMD UPDATE DEBUG - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
            file_put_contents($debugFile, "RAW POST:\n" . print_r($data, true) . "\n", FILE_APPEND);

            if (empty($data['id'])) {
                file_put_contents($debugFile, "ERROR: ID kosong\n", FILE_APPEND);
                session()->setFlashdata('error', 'ID tidak ditemukan');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }

            // ID dari form bisa saja bukan misi_id (bisa tujuan/sasaran/indikator/target)
            $rawId = (int) $data['id'];
            $misiId = $this->rpjmdModel->findMisiIdForAnyEntity($rawId) ?? $rawId;

            $existingMisi = $this->rpjmdModel->getMisiById($misiId);
            if (!$existingMisi) {
                file_put_contents($debugFile, "ERROR: Misi tidak ada (ID: {$misiId})\n", FILE_APPEND);
                session()->setFlashdata('error', 'Data RPJMD tidak ditemukan di database.');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }

            // Susun formatted data sesuai model
            $formattedData = [
                'misi' => [
                    'misi' => $data['misi'] ?? $existingMisi['misi'],
                    'tahun_mulai' => isset($data['tahun_mulai'])
                        ? (int) $data['tahun_mulai']
                        : (int) $existingMisi['tahun_mulai'],
                    'tahun_akhir' => isset($data['tahun_akhir'])
                        ? (int) $data['tahun_akhir']
                        : (int) $existingMisi['tahun_akhir'],
                    'status' => $data['status'] ?? ($existingMisi['status'] ?? 'draft'),
                ],
                'tujuan' => is_array($data['tujuan'] ?? null) ? $data['tujuan'] : [],
            ];

            file_put_contents($debugFile, "FORMATTED FOR MODEL:\n" . print_r($formattedData, true) . "\n", FILE_APPEND);

            $ok = $this->rpjmdModel->updateCompleteRpjmdTransaction($misiId, $formattedData);

            file_put_contents($debugFile, "RESULT: " . ($ok ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);

            if ($ok) {
                session()->setFlashdata('success', 'Data RPJMD berhasil diupdate');
            } else {
                session()->setFlashdata('error', 'Gagal mengupdate data RPJMD');
            }
        } catch (\Throwable $e) {
            $debugFile = WRITEPATH . 'debug_rpjmd_update.txt';
            file_put_contents(
                $debugFile,
                "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n",
                FILE_APPEND
            );

            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    public function delete($id)
    {
        try {
            $id = (int) $id;

            // Kalau id yang dikirim bukan misi_id langsung, coba cari dulu misi parent-nya
            if (!$this->rpjmdModel->misiExists($id)) {
                $misiId = $this->rpjmdModel->findMisiIdForAnyEntity($id);
                if ($misiId) {
                    $id = $misiId;
                }
            }

            if (!$this->rpjmdModel->misiExists($id)) {
                session()->setFlashdata('error', 'Data RPJMD tidak ditemukan');
                return redirect()->to(base_url('adminkab/rpjmd'));
            }

            $res = $this->rpjmdModel->deleteMisi($id);
            if ($res) {
                session()->setFlashdata('success', 'Data RPJMD berhasil dihapus');
            } else {
                session()->setFlashdata('error', 'Gagal menghapus data RPJMD');
            }
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/rpjmd'));
    }

    // ==================== STATUS (AJAX) ====================

    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request',
            ]);
        }

        $json = $this->request->getJSON(true);
        $id = isset($json['id']) ? (int) $json['id'] : 0;

        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID harus diisi',
            ]);
        }

        try {
            $currentMisi = $this->rpjmdModel->getMisiById($id);
            if (!$currentMisi) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                ]);
            }

            $currentStatus = $currentMisi['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';

            $res = $this->rpjmdModel->updateMisiStatus($id, $newStatus);
            if ($res) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Status berhasil diupdate',
                    'oldStatus' => $currentStatus,
                    'newStatus' => $newStatus,
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal mengupdate status',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

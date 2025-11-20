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
    // ==================== INDEX RENSTRA ====================
    public function index()
    {
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // ambil filter dari query string
        $misi = trim($this->request->getGet('misi') ?? '');
        $tujuan = trim($this->request->getGet('tujuan') ?? '');
        $rpjmd = trim($this->request->getGet('rpjmd') ?? '');
        $periode = trim($this->request->getGet('periode') ?? '');
        $status = trim($this->request->getGet('status') ?? '');

        // ambil data renstra (flatten) + target sasaran + target tujuan
        $renstraData = $this->renstraModel->getFilteredRenstra(
            $opdId,
            $misi ?: null,
            $tujuan ?: null,
            $rpjmd ?: null,
            $status ?: null,
            $periode ?: null
        );

        $currentOpd = $this->opdModel->find($opdId);

        $data = [
            'title' => 'Rencana Strategis - ' . ($currentOpd['nama_opd'] ?? ''),
            'current_opd' => $currentOpd,
            'renstra_data' => $renstraData,
            'filters' => [
                'misi' => $misi,
                'tujuan' => $tujuan,
                'rpjmd' => $rpjmd,
                'periode' => $periode,
                'status' => $status,
            ],
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
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil struktur sasaran + indikator sasaran + target
        $renstra = $this->renstraModel->getCompleteRenstraById($id, $opdId);
        if (!$renstra) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Sasaran Renstra tidak ditemukan');
        }

        // Ambil data TUJUAN RENSTRA (tabel renstra_tujuan)
        $db = \Config\Database::connect();
        $tujuanRow = $db->table('renstra_tujuan')
            ->where('id', $renstra['renstra_tujuan_id'])
            ->get()
            ->getRowArray();

        // Ambil INDIKATOR TUJUAN + TARGET TUJUAN
        $indikatorTujuan = $this->renstraModel->getIndikatorTujuanByTujuanId($renstra['renstra_tujuan_id']);
        foreach ($indikatorTujuan as &$it) {
            $targetsT = $this->renstraModel->getTargetTujuanByIndikatorId($it['id']);
            $it['target_tahunan'] = $targetsT;
        }
        unset($it);

        // Ambil daftar SASARAN RPJMD untuk dropdown
        $rpjmdSasaran = $this->rpjmdModel->getAllSasaran();

        // ⭐ Tambahkan ini: daftar satuan
        $satuanOptions = [
            'Persen' => 'Persen (%)',
            'Orang' => 'Orang',
            'Unit' => 'Unit',
            'Dokumen' => 'Dokumen',
            'Kegiatan' => 'Kegiatan',
            'Rupiah' => 'Rupiah',
            'Index' => 'Index',
            'Nilai' => 'Nilai',
            'Predikat' => 'Predikat',
        ];

        $data = [
            'title' => 'Edit Renstra',
            'renstra_data' => $renstra,
            'renstra_tujuan' => $tujuanRow,
            'indikator_tujuan' => $indikatorTujuan,
            'rpjmd_sasaran' => $rpjmdSasaran,
            // ⭐ kirim ke view
            'satuan_options' => $satuanOptions,
        ];

        return view('adminOpd/renstra/edit_renstra', $data);
    }

    // ==================== DATA PROCESSING ====================

    public function save()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $opd_id = session()->get('opd_id'); // pastikan ini ada di session

            // 1️⃣ Simpan TUJUAN RENSTRA
            $tujuanData = [
                'rpjmd_sasaran_id' => $this->request->getPost('rpjmd_sasaran_id'),
                'tujuan' => $this->request->getPost('tujuan_renstra'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $db->table('renstra_tujuan')->insert($tujuanData);
            $tujuanId = $db->insertID();

            // 2️⃣ Simpan INDIKATOR TUJUAN
            $indikatorTujuanList = $this->request->getPost('indikator_tujuan');
            if (!empty($indikatorTujuanList)) {
                foreach ($indikatorTujuanList as $it) {
                    $indikatorData = [
                        'tujuan_id' => $tujuanId,
                        'indikator_tujuan' => $it['indikator_tujuan'] ?? null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $db->table('renstra_indikator_tujuan')->insert($indikatorData);
                    $indikatorTujuanId = $db->insertID();

                    // 3️⃣ Simpan TARGET TUJUAN
                    if (isset($it['target_tahunan'])) {
                        foreach ($it['target_tahunan'] as $target) {
                            $db->table('renstra_target_tujuan')->insert([
                                'indikator_tujuan_id' => $indikatorTujuanId,
                                'tahun' => $target['tahun'] ?? null,
                                'target_tahunan' => $target['target'] ?? null,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                }
            }

            // 4️⃣ Simpan SASARAN RENSTRA
            $sasaranRenstraList = $this->request->getPost('sasaran_renstra');
            if (!empty($sasaranRenstraList)) {
                foreach ($sasaranRenstraList as $sr) {
                    $sasaranData = [
                        'opd_id' => $opd_id,
                        'renstra_tujuan_id' => $tujuanId,
                        'sasaran' => $sr['sasaran'] ?? null,
                        'status' => 'draft',
                        'tahun_mulai' => $this->request->getPost('tahun_mulai'),
                        'tahun_akhir' => $this->request->getPost('tahun_akhir'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $db->table('renstra_sasaran')->insert($sasaranData);
                    $sasaranId = $db->insertID();

                    // 5️⃣ Simpan INDIKATOR SASARAN
                    if (isset($sr['indikator_sasaran'])) {
                        foreach ($sr['indikator_sasaran'] as $is) {
                            $indikatorSasaranData = [
                                'renstra_sasaran_id' => $sasaranId,
                                'indikator_sasaran' => $is['indikator_sasaran'] ?? null,
                                'satuan' => $is['satuan'] ?? null,
                                'jenis_indikator' => $is['jenis_indikator'] ?? null,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                            $db->table('renstra_indikator_sasaran')->insert($indikatorSasaranData);
                            $indikatorSasaranId = $db->insertID();

                            // 6️⃣ Simpan TARGET SASARAN
                            if (isset($is['target_tahunan'])) {
                                foreach ($is['target_tahunan'] as $target) {
                                    $db->table('renstra_target')->insert([
                                        'renstra_indikator_id' => $indikatorSasaranId,
                                        'tahun' => $target['tahun'] ?? null,
                                        'target' => $target['target'] ?? null,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaksi gagal');
            }

            return redirect()->to('adminopd/renstra')->with('success', 'Data Renstra berhasil disimpan!');
        } catch (\Throwable $e) {
            log_message('error', 'Renstra Save Error: ' . $e->getMessage());
            $db->transRollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
    public function update($id = null)
    {
        if (!$id) {
            return redirect()->back()->with('error', 'ID sasaran Renstra tidak valid');
        }

        $session = session();
        $opdId = (int) $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session OPD hilang, silakan login ulang');
        }

        try {
            $post = $this->request->getPost();

            // Validasi dasar
            if (
                empty($post['rpjmd_sasaran_id']) ||
                empty($post['tujuan_renstra']) ||
                empty($post['tahun_mulai']) ||
                empty($post['tahun_akhir'])
            ) {
                throw new \Exception('Data umum Renstra belum lengkap');
            }

            // -------------------------
            // SUSUN DATA SASARAN
            // -------------------------
            $sasaranText = '';
            $indikatorSasaranArr = [];

            if (!empty($post['sasaran_renstra']) && is_array($post['sasaran_renstra'])) {
                // ambil sasaran pertama (karena $id adalah 1 sasaran)
                $firstSasaran = reset($post['sasaran_renstra']);

                // antisipasi nama key berbeda
                $sasaranText = $firstSasaran['sasaran']
                    ?? ($firstSasaran['sasaran_renstra'] ?? '');

                // seluruh indikator_sasaran untuk sasaran ini
                if (!empty($firstSasaran['indikator_sasaran']) && is_array($firstSasaran['indikator_sasaran'])) {
                    foreach ($firstSasaran['indikator_sasaran'] as $indikator) {
                        // jangan diubah strukturnya, kirim apa adanya ke model
                        $indikatorSasaranArr[] = $indikator;
                    }
                }
            }

            if (trim($sasaranText) === '') {
                throw new \Exception('Sasaran Renstra tidak boleh kosong');
            }

            // -------------------------
            // SUSUN PAYLOAD UNTUK MODEL
            // -------------------------
            $payload = [
                'rpjmd_sasaran_id' => $post['rpjmd_sasaran_id'],
                'tujuan_renstra' => $post['tujuan_renstra'],
                'tahun_mulai' => $post['tahun_mulai'],
                'tahun_akhir' => $post['tahun_akhir'],
                'status' => $post['status'] ?? 'selesai',

                'sasaran' => $sasaranText,

                // indikator tujuan langsung pakai array dari form
                'indikator_tujuan' => $post['indikator_tujuan'] ?? [],

                // indikator sasaran hasil ekstrak di atas
                'indikator_sasaran' => $indikatorSasaranArr,
            ];

            // -------------------------
            // EKSEKUSI UPDATE LENGKAP
            // -------------------------
            $success = $this->renstraModel->updateRenstraFull((int) $id, $opdId, $payload);

            if (!$success) {
                throw new \Exception('Transaksi gagal, perubahan tidak tersimpan');
            }

            return redirect()->to(base_url('adminopd/renstra'))
                ->with('success', 'Data Renstra berhasil diperbarui');
        } catch (\Exception $e) {
            log_message('error', 'RENSTRA Update Error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
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
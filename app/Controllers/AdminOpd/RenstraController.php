<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\RenstraModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;
use App\Models\PkModel;


class RenstraController extends BaseController
{
    protected $renstraModel;
    protected $rpjmdModel;
    protected $opdModel;
    protected $pkModel;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
        $this->pkModel = new PkModel();
    }
    /* =========================================================
     *  HELPERS: Anti XSS / Script
     * =======================================================*/
    private function xssPattern(): string
    {
        // blok: <script>, javascript:, data:text/html, onerror=, <?php, <?
        return '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is';
    }

    private function xssRule(): string
    {
        return 'regex_match[/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is]';
    }

    private function isSafeText($val): bool
    {
        if ($val === null || $val === '')
            return true;
        return (bool) preg_match($this->xssPattern(), (string) $val);
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
        $satuan = $this->pkModel->getAllSatuan();


        $data = [
            'rpjmd_sasaran' => $rpjmdSasaran,
            'satuan_options' => $satuan,
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

        $satuan = $this->pkModel->getAllSatuan();
        // dd( $satuan);


        $data = [
            'title' => 'Edit Renstra',
            'renstra_data' => $renstra,
            'renstra_tujuan' => $tujuanRow,
            'indikator_tujuan' => $indikatorTujuan,
            'rpjmd_sasaran' => $rpjmdSasaran,
            'satuan_options' => $satuan,
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

            if (!$opd_id) {
                $db->transRollback();
                return redirect()->to('/login')->with('error', 'Session OPD hilang, silakan login ulang');
            }
            // ============================
            // VALIDASI (ANTI XSS/SCRIPT)
            // ============================
            $rx = $this->xssRule();

            $rules = [
                'rpjmd_sasaran_id' => 'required|integer',
                'tujuan_renstra' => 'required|string|max_length[5000]|' . $rx,
                'tahun_mulai' => 'required',
                'tahun_akhir' => 'required',
            ];

            $messages = [
                'tujuan_renstra' => [
                    'regex_match' => 'Tujuan Renstra terdeteksi mengandung script / input berbahaya.',
                ],
            ];

            if (!$this->validate($rules, $messages)) {
                $db->transRollback();
                return redirect()->back()
                    ->withInput()
                    ->with('error', implode(' | ', $this->validator->getErrors()));
            }

            $post = $this->request->getPost();

            // validasi array: indikator_tujuan
            if (!empty($post['indikator_tujuan']) && is_array($post['indikator_tujuan'])) {
                foreach ($post['indikator_tujuan'] as $it) {
                    if (!$this->isSafeText($it['indikator_tujuan'] ?? '')) {
                        $db->transRollback();
                        return redirect()->back()->withInput()->with('error', 'Indikator Tujuan mengandung script / input berbahaya.');
                    }
                    if (!empty($it['target_tahunan']) && is_array($it['target_tahunan'])) {
                        foreach ($it['target_tahunan'] as $t) {
                            if (!$this->isSafeText($t['target'] ?? '')) {
                                $db->transRollback();
                                return redirect()->back()->withInput()->with('error', 'Target Tujuan tahunan mengandung script / input berbahaya.');
                            }
                        }
                    }
                }
            }

            // validasi array: sasaran_renstra + indikator_sasaran
            if (!empty($post['sasaran_renstra']) && is_array($post['sasaran_renstra'])) {
                foreach ($post['sasaran_renstra'] as $sr) {
                    if (!$this->isSafeText($sr['sasaran'] ?? '')) {
                        $db->transRollback();
                        return redirect()->back()->withInput()->with('error', 'Sasaran Renstra mengandung script / input berbahaya.');
                    }

                    if (!empty($sr['indikator_sasaran']) && is_array($sr['indikator_sasaran'])) {
                        foreach ($sr['indikator_sasaran'] as $is) {
                            if (!$this->isSafeText($is['indikator_sasaran'] ?? '')) {
                                $db->transRollback();
                                return redirect()->back()->withInput()->with('error', 'Indikator Sasaran mengandung script / input berbahaya.');
                            }
                            if (!$this->isSafeText($is['satuan'] ?? '')) {
                                $db->transRollback();
                                return redirect()->back()->withInput()->with('error', 'Satuan mengandung script / input berbahaya.');
                            }
                            if (!$this->isSafeText($is['jenis_indikator'] ?? '')) {
                                $db->transRollback();
                                return redirect()->back()->withInput()->with('error', 'Jenis indikator mengandung script / input berbahaya.');
                            }

                            if (!empty($is['target_tahunan']) && is_array($is['target_tahunan'])) {
                                foreach ($is['target_tahunan'] as $t) {
                                    if (!$this->isSafeText($t['target'] ?? '')) {
                                        $db->transRollback();
                                        return redirect()->back()->withInput()->with('error', 'Target Sasaran tahunan mengandung script / input berbahaya.');
                                    }
                                }
                            }
                        }
                    }
                }
            }
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

            // ============================
            // VALIDASI UTAMA
            // ============================
            $rules = [
                'rpjmd_sasaran_id' => 'required|integer',
                'tujuan_renstra' => 'required|string|max_length[5000]',
                'tahun_mulai' => 'required|integer',
                'tahun_akhir' => 'required|integer',
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', implode(' | ', $this->validator->getErrors()));
            }

            if (empty($post['sasaran_renstra']) || !is_array($post['sasaran_renstra'])) {
                throw new \Exception('Data sasaran Renstra tidak valid');
            }

            // Ambil tujuan renstra lama (buat insert sasaran baru)
            $sasaranLama = $this->renstraModel->getRenstraById($id);
            if (!$sasaranLama) {
                throw new \Exception('Data sasaran lama tidak ditemukan');
            }

            $renstraTujuanId = (int) $sasaranLama['renstra_tujuan_id'];

            // ============================
            // PROSES UPDATE + INSERT
            // ============================
            foreach ($post['sasaran_renstra'] as $index => $sr) {

                $sasaranText = $sr['sasaran'] ?? '';
                if (trim($sasaranText) === '') {
                    continue;
                }

                $payload = [
                    'rpjmd_sasaran_id' => $post['rpjmd_sasaran_id'],
                    'tujuan_renstra' => $post['tujuan_renstra'],
                    'tahun_mulai' => $post['tahun_mulai'],
                    'tahun_akhir' => $post['tahun_akhir'],
                    'status' => $post['status'] ?? 'selesai',
                    'sasaran' => $sasaranText,
                    'indikator_tujuan' => $post['indikator_tujuan'] ?? [],
                    'indikator_sasaran' => $sr['indikator_sasaran'] ?? [],
                ];

                if ($index === 0) {
                    // ======================
                    // UPDATE sasaran lama
                    // ======================
                    $success = $this->renstraModel
                        ->updateRenstraFull((int) $id, $opdId, $payload);
                } else {
                    // ======================
                    // INSERT sasaran baru
                    // ======================
                    $success = $this->renstraModel
                        ->createCompleteRenstra([
                                'opd_id' => $opdId,
                                'renstra_tujuan_id' => $renstraTujuanId,
                                'tahun_mulai' => $post['tahun_mulai'],
                                'tahun_akhir' => $post['tahun_akhir'],
                                'status' => $post['status'] ?? 'draft',
                                'sasaran_renstra' => [
                                    [
                                        'sasaran' => $sasaranText,
                                        'indikator_sasaran' => $sr['indikator_sasaran'] ?? []
                                    ]
                                ]
                            ]);
                }

                if (!$success) {
                    throw new \Exception('Gagal menyimpan salah satu Sasaran Renstra');
                }
            }

            return redirect()->to(base_url('adminopd/renstra'))
                ->with('success', 'Data Renstra berhasil diperbarui dan sasaran baru berhasil ditambahkan');
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

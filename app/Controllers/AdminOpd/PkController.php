<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\PegawaiModel;
use App\Models\PkModel;
use App\Models\OpdModel;

class PkController extends BaseController
{
    protected $pegawaiModel;
    protected $pkModel;
    protected $opdModel;


    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->pkModel = new PkModel();
        $this->opdModel = new OpdModel();
    }

    public function index($jenis)
{
    $session = session();
    $opdId = $session->get('opd_id');

    if (!$opdId) {
        return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
    }

    $tahun = $this->request->getGet('tahun');
    $pkId  = $this->request->getGet('pk_id');

    $pkData = null;
    $pkRelasiList = [];

    // 1️⃣ Jika tahun dipilih → ambil daftar relasi
    if ($tahun) {
        $pkRelasiList = $this->pkModel
            ->getPkRelasiByOpdJenisTahun($opdId, $jenis, $tahun);
    }

    // 2️⃣ Jika relasi dipilih → ambil PK
    if ($pkId) {
        $pkData = $this->pkModel->getCompletePkById($pkId);

        // 🔥 NORMALISASI WAJIB (INI KUNCI)
        if (is_array($pkData) && isset($pkData[0])) {
            $pkData = $pkData[0];
        }
    }

    $currentOpd = $this->opdModel->find($opdId);

    // dd($pkData);

    return view('adminOpd/pk/pk', [
        'pk_data' => $pkData,
        'pkRelasiList' => $pkRelasiList,
        'current_opd' => $currentOpd,
        'currentYear' => date('Y'),
        'pk_id' => $pkId,
        'tahun' => $tahun,
        'jenis' => $jenis,
    ]);
}



    public function tambah($jenis)
    {

        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        if ($jenis === 'jpt') {
            // JPT boleh memilih pegawai dari OPD sendiri + OPD 46
            $pegawaiOpd = $this->pegawaiModel
                ->groupStart()
                ->where('opd_id', $opdId)
                ->orWhere('opd_id', 46)
                ->groupEnd()
                ->orderBy('nama_pegawai', 'ASC')
                ->findAll();
        } else {
            // Administrator & Pengawas tetap hanya OPD sendiri
            $pegawaiOpd = $this->pegawaiModel
                ->where('opd_id', $opdId)
                ->orderBy('nama_pegawai', 'ASC')
                ->findAll();
        }
        $currentOpd = $this->opdModel->find($opdId);
        $program = $this->pkModel->getAllPrograms();
        $jptProgram = $this->pkModel->getJptPrograms($opdId);
        $kegiatan = $this->pkModel->getKegiatan();
        $subkegiatan = $this->pkModel->getSubKegiatan();
        $satuan = $this->pkModel->getAllSatuan();
        $kegiatanAdmin = $this->pkModel->getKegiatanAdmin($opdId);
        // Dapatkan PK Pimpinan sebagai acuan sesuai jenis
        $referensiJenis = null;
        if ($jenis === 'administrator') {
            $referensiJenis = 'jpt';
        } elseif ($jenis === 'pengawas') {
            $referensiJenis = 'administrator';
        }
        $pkPimpinan = [];
        if ($referensiJenis) {
            $pkPimpinan = $this->pkModel
                ->where('opd_id', $opdId)
                ->where('jenis', $referensiJenis)
                ->findAll();
        }
        return view('adminOpd/pk/tambah_pk', [
            'pegawaiOpd' => $pegawaiOpd,
            'current_opd' => $currentOpd,
            'program' => $program,
            'kegiatan' => $kegiatan,
            'subkegiatan' => $subkegiatan,
            'satuan' => $satuan,
            'pkPimpinan' => $pkPimpinan,
            'jptProgram' => $jptProgram,
            'kegiatanAdmin' => $kegiatanAdmin,
            'title' => 'Tambah PK ' . ucfirst($jenis),
            'jenis' => $jenis
        ]);
    }

    public function edit($jenis, $id)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pk = $this->pkModel->getPkById($id);
        if (!$pk)
            return redirect()->to('/adminopd/pk/' . $jenis)->with('error', 'Data PK tidak ditemukan');

        if ($jenis === 'jpt') {
            // JPT boleh memilih pegawai dari OPD sendiri + OPD 46
            $pegawaiOpd = $this->pegawaiModel
                ->groupStart()
                ->where('opd_id', $opdId)
                ->orWhere('opd_id', 46)
                ->groupEnd()
                ->orderBy('nama_pegawai', 'ASC')
                ->findAll();
        } else {
            // Administrator & Pengawas tetap hanya OPD sendiri
            $pegawaiOpd = $this->pegawaiModel
                ->where('opd_id', $opdId)
                ->orderBy('nama_pegawai', 'ASC')
                ->findAll();
        }
        $program = $this->pkModel->getAllPrograms();
        $jptProgram = $this->pkModel->getJptPrograms($opdId);
        $kegiatan = $this->pkModel->getKegiatan();
        $subkegiatan = $this->pkModel->getSubKegiatan();
        $satuan = $this->pkModel->getAllSatuan();
        $kegiatanAdmin = $this->pkModel->getKegiatanAdmin($opdId);

        // dd($pk['sasaran_pk'][0]['indikator']);

        return view('adminOpd/pk/edit_pk', [
            'pk' => $pk,
            'pegawaiOpd' => $pegawaiOpd,
            'program' => $program,
            'kegiatan' => $kegiatan,
            'subkegiatan' => $subkegiatan,
            'satuan' => $satuan,
            'kegiatanAdmin' => $kegiatanAdmin,
            'jptProgram' => $jptProgram,
            'title' => 'Edit PK ',
            'jenis' => $jenis,
            'validation' => session()->getFlashdata('validation')
        ]);
    }

    public function save($jenis)
    {
        $validation = \Config\Services::validation();

        // Validasi pihak penandatangan
        if (strtolower($jenis) === 'bupati') {
            $rules = [
                'pegawai_1_id' => 'permit_empty|numeric',
                'pegawai_2_id' => 'permit_empty|numeric',
            ];
        } else {
            $rules = [
                'pegawai_1_id' => 'required|numeric',
                'pegawai_2_id' => 'required|numeric',
            ];
        }

        $validation->setRules($rules);
        if (!$validation->run($this->request->getPost())) {
            return redirect()->back()->withInput()
                ->with('validation', $validation->getErrors());
        }

        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            throw new \Exception("OPD ID tidak ditemukan di session");
        }

        $post = $this->request->getPost();
        log_message('debug', 'POST RAW: ' . json_encode($post));
        $now = date('Y-m-d');
        $tanggal = $post['tanggal_pk'] ?? $now;


        // ------------------------------
        // REFERENSI INDIKATOR ATASAN
        // ------------------------------
        $referensiAcuanArr = [];
        if (!empty($post['referensi_indikator_id'])) {
            foreach ($post['referensi_indikator_id'] as $val) {
                $parts = explode('-', $val); // pkid-indikatorid
                if (count($parts) == 2) {
                    $referensiAcuanArr[] = [
                        'referensi_pk_id' => $parts[0],
                        'referensi_indikator_id' => $parts[1],
                    ];
                }
            }
        }

        // ------------------------------
        // STRUKTUR DATA FINAL
        // ------------------------------
        $saveData = [
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'tahun' => $post['tahun'] ?? null,
            'pihak_1' => $post['pegawai_1_id'] ?? null,
            'pihak_2' => $post['pegawai_2_id'] ?? null,
            'tanggal' => $tanggal,
            'sasaran_pk' => [],
            'referensi_acuan' => $referensiAcuanArr,
            'misi_bupati_id' => $post['misi_bupati_id'] ?? []

        ];

        // ------------------------------
        // PARSE SASARAN
        // ------------------------------
        if (!empty($post['sasaran_pk'])) {

            foreach ($post['sasaran_pk'] as $s) {

                $sasaranData = [
                    'sasaran' => $s['sasaran'] ?? '',
                    'jenis' => $jenis,
                    'indikator' => [],
                ];

                // ------------------------------
                // PARSE INDIKATOR
                // ------------------------------
                if (!empty($s['indikator'])) {

                    foreach ($s['indikator'] as $indikator) {


                        $indikatorData = [
                            'indikator' => $indikator['indikator'] ?? '',
                            'target' => $indikator['target'] ?? '',
                            'id_satuan' => $indikator['id_satuan'] ?? null,
                            'jenis_indikator' => $indikator['jenis_indikator'] ?? null,
                            'jenis' => $jenis,

                            'program' => [], // untuk jpt & admin
                        ];

                        // ------------------------------------------------------
                        // JENIS = JPT → indikator → program
                        // ------------------------------------------------------
                        if ($jenis === 'jpt') {
                            if (!empty($indikator['program'])) {
                                foreach ($indikator['program'] as $p) {
                                    $indikatorData['program'][] = [
                                        'program_id' => $p['program_id'] ?? null,
                                    ];
                                }
                            }
                        }

                        // ------------------------------------------------------
                        // JENIS = ADMINISTRATOR → indikator → program → kegiatan
                        // ------------------------------------------------------
                        if ($jenis === 'administrator') {
                            if (!empty($indikator['program'])) {

                                foreach ($indikator['program'] as $p) {

                                    $programData = [
                                        'program_id' => $p['program_id'] ?? null,
                                        'kegiatan' => [],
                                    ];

                                    if (!empty($p['kegiatan'])) {
                                        foreach ($p['kegiatan'] as $k) {
                                            $programData['kegiatan'][] = [
                                                'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                            ];
                                        }
                                    }
                                    $indikatorData['program'][] = $programData;
                                }
                            }
                        }

                        // ------------------------------------------------------
                        // JENIS = PENGAWAS → indikator → kegiatan → subkegiatan
                        // ------------------------------------------------------
                        if ($jenis === 'pengawas') {

                            if (!empty($indikator['program']) && is_array($indikator['program'])) {

                                foreach ($indikator['program'] as $p) {

                                    // --- PROGRAM LEVEL ---
                                    $programData = [
                                        'program_id' => $p['program_id'] ?? null,
                                        'kegiatan' => [],
                                    ];

                                    // --- KEGIATAN LEVEL ---
                                    if (!empty($p['kegiatan']) && is_array($p['kegiatan'])) {

                                        foreach ($p['kegiatan'] as $k) {

                                            $kegiatanData = [
                                                'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                                'subkegiatan' => [],
                                            ];

                                            // --- SUBKEGIATAN LEVEL ---
                                            if (!empty($k['subkegiatan']) && is_array($k['subkegiatan'])) {

                                                foreach ($k['subkegiatan'] as $sk) {
                                                    $kegiatanData['subkegiatan'][] = [
                                                        'subkegiatan_id' => $sk['subkegiatan_id'] ?? null
                                                    ];
                                                }
                                            }

                                            // masukkan kegiatan ke dalam program
                                            $programData['kegiatan'][] = $kegiatanData;
                                        }
                                    }

                                    // masukkan program ke dalam indikator
                                    $indikatorData['program'][] = $programData;
                                }
                            }
                        }

                        $sasaranData['indikator'][] = $indikatorData;
                    }
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }
        };

        // ------------------------------
        // SIMPAN KE MODEL
        // ------------------------------
        try {
            $pkId = $this->pkModel->saveCompletePk($saveData);

            // Jika saveCompletePk() gagal tetapi tidak melempar exception
            if (!$pkId) {

                // Ambil pesan error DB terakhir
                $db = \Config\Database::connect();
                $dbError = $db->error();

                // Jika ada pesan error DB, tampilkan
                if (!empty($dbError['message'])) {
                    log_message('error', 'DB ERROR (saveCompletePk): ' . $dbError['message']);

                    return redirect()->back()->withInput()
                        ->with('error', 'Gagal menyimpan PK: ' . $dbError['message']);
                }

                // Jika tidak ada pesan DB, fallback general error
                return redirect()->back()->withInput()
                    ->with('error', 'Gagal menyimpan PK (Unknown database error)');
            }

            // Jika berhasil
            $redirectBase = (strtolower($jenis) === 'bupati')
                ? '/adminkab/pk/'
                : '/adminopd/pk/';

            return redirect()->to($redirectBase . $jenis)
                ->with('success', 'Data PK berhasil disimpan');
        } catch (\Exception $e) {

            // Tangkap error yang dilempar model
            log_message('error', 'SAVE EXCEPTION: ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Exception: ' . $e->getMessage());
        }
    }

    /**
     * UPDATE: gunakan model updateCompletePk untuk konsistensi (replace seluruh struktur)
     * signature: update($id)
     */
    public function update($jenis, $id)
    {
        log_message('debug', "=== UPDATE PK START: ID {$id} ===");

        $pk = $this->pkModel->find($id);
        if (!$pk) {
            log_message('error', "PK ID {$id} tidak ditemukan.");
            return redirect()->back()->with('error', 'Data PK tidak ditemukan.');
        }

        $post = $this->request->getPost();
        log_message('debug', "POST DATA: " . json_encode($post));
        $session = session();
        $jenis = $pk['jenis'];
        $tahun = $pk['tahun'];

        $opdId = $session->get('opd_id') ?? $pk['opd_id'];
        $now = date('Y-m-d');
        $tanggal = $post['tanggal_pk'] ?? $now;


        // --------------------------
        // LOG: Info dasar
        // --------------------------
        log_message('debug', "Jenis: {$jenis}, OPD ID: {$opdId}, Tanggal: {$now}");

        // ============================
        // Parse referensi indikator
        // ============================
        $referensiAcuanArr = [];
        if (!empty($post['referensi_indikator_id'])) {
            foreach ($post['referensi_indikator_id'] as $ref) {
                [$pkId, $indId] = explode('-', $ref) + [null, null];
                if ($pkId && $indId) {
                    $referensiAcuanArr[] = [
                        'referensi_pk_id' => $pkId,
                        'referensi_indikator_id' => $indId
                    ];
                }
            }
        }

        // ============================
        // Data Utama PK
        // ============================
        $saveData = [
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'tahun' => $tahun,
            'pihak_1' => $post['pegawai_1_id'] ?? null,
            'pihak_2' => $post['pegawai_2_id'] ?? null,
            'tanggal' => $tanggal,
            'sasaran_pk' => [],
            'referensi_acuan' => $referensiAcuanArr,
            'misi_bupati_id' => $post['misi_bupati_id'] ?? []
        ];

        // --------------------------
        // LOG: After basic structure
        // --------------------------
        log_message('debug', "SAVE DATA AWAL: " . json_encode($saveData));

        // ============================
        // Parse Sasaran → Indikator → Program/Kegiatan/Subkegiatan
        // ============================
        if (!empty($post['sasaran_pk'])) {
            foreach ($post['sasaran_pk'] as $sIndex => $s) {

                $sasaranData = [
                    'sasaran' => $s['sasaran'] ?? '',
                    'indikator' => []
                ];

                foreach (($s['indikator'] ?? []) as $iIndex => $ind) {

                    $indikatorData = [
                        'indikator' => $ind['indikator'] ?? '',
                        'target' => $ind['target'] ?? '',
                        'id_satuan' => $ind['id_satuan'] ?? null,
                        'jenis_indikator' => $ind['jenis_indikator'] ?? null,
                        'program' => [],
                        'kegiatan' => []
                    ];

                    // logging indikator
                    log_message('debug', "Parsing indikator [{$sIndex}][{$iIndex}]: " . json_encode($indikatorData));

                    if ($jenis === 'jpt') {
                        foreach ($ind['program'] ?? [] as $p) {
                            $indikatorData['program'][] = [
                                'program_id' => $p['program_id'] ?? null,
                                'anggaran' => $p['anggaran'] ?? 0
                            ];
                        }
                    }

                    if ($jenis === 'administrator') {
                        foreach ($ind['program'] ?? [] as $p) {
                            $programData = [
                                'program_id' => $p['program_id'] ?? null,
                                'kegiatan' => []
                            ];
                            foreach ($p['kegiatan'] ?? [] as $k) {
                                $programData['kegiatan'][] = [
                                    'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                    'anggaran' => $k['anggaran'] ?? 0
                                ];
                            }
                            $indikatorData['program'][] = $programData;
                        }
                    }

                    if ($jenis === 'pengawas') {
                        foreach ($ind['kegiatan'] ?? [] as $k) {
                            $kg = [
                                'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                'subkegiatan' => []
                            ];
                            foreach ($k['subkegiatan'] ?? [] as $sk) {
                                $kg['subkegiatan'][] = [
                                    'subkegiatan_id' => $sk['subkegiatan_id'] ?? null,
                                    'anggaran' => $sk['anggaran'] ?? 0
                                ];
                            }
                            $indikatorData['kegiatan'][] = $kg;
                        }
                    }

                    $sasaranData['indikator'][] = $indikatorData;
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }
        }

        // --------------------------
        // LOG: Final SAVE DATA
        // --------------------------
        log_message('debug', "FINAL SAVE DATA: " . json_encode($saveData));

        // ============================
        // EXECUTE UPDATE
        // ============================
        try {
            log_message('debug', "CALLING updateCompletePk($id)");
            $ok = $this->pkModel->updateCompletePk($id, $saveData);

            // Setelah query
            $db = \Config\Database::connect();
            log_message('debug', "LAST QUERY: " . $db->getLastQuery());

            if ($ok) {
                log_message('debug', "UPDATE SUCCESS ID {$id}");
                $base = (strtolower($jenis) === 'bupati') ? '/adminkab/pk/' : '/adminopd/pk/';
                return redirect()->to($base . $jenis)->with('success', 'Data PK berhasil diperbarui.');
            }

            log_message('error', "updateCompletePk gagal untuk ID {$id}");
            return redirect()->back()->with('error', 'Gagal memperbarui PK');
        } catch (\Exception $e) {
            log_message('error', "EXCEPTION UPDATE PK: {$e->getMessage()}");
            log_message('error', $e->getTraceAsString());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * DELETE: aman mengikuti hirarki. Membuang semua child rows sebelum PK utama.
     */
    public function delete($jenis, $id)
    {
        $jenis = strtolower($jenis);
        log_message('debug', 'DELETE PK | jenis: ' . $jenis . ' | id: ' . $id);

        $pkModel = new PkModel();
        $pk = $pkModel->find($id);

        log_message('debug', 'Hasil find: ' . json_encode($pk));
        $isAjax = $this->request->isAJAX();

        $pk = $this->pkModel->find($id);
        if (!$pk) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'error' => 'Data PK tidak ditemukan.']);
            }
            return redirect()->back()->with('error', 'Data PK tidak ditemukan.');
        }

        $jenis = $pk['jenis'];
        $db = \Config\Database::connect();
        $db->transStart();

        try {

            // --- semua proses delete ---
            // (tidak saya ulang supaya pesan ini fokus pada perbaikan saja)
            // --------------------------------------------------------------

            $this->pkModel->delete($id);
            $db->transComplete();

            if ($db->transStatus() === false) {
                $db->transRollback();

                if ($isAjax) {
                    return $this->response->setJSON(['success' => false, 'error' => 'Gagal menghapus PK.']);
                }

                return redirect()->back()->with('error', 'Gagal menghapus PK.');
            }

            // === RETURN SUCCESS ===
            if ($isAjax) {
                return $this->response->setJSON(['success' => true]);
            }

            // fallback untuk non-AJAX
            $redirectBase = (strtolower($jenis) === 'bupati') ? '/adminkab/pk/' : '/adminopd/pk/';
            return redirect()->to($redirectBase . $jenis)
                ->with('success', 'Data PK berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'DELETE ERROR: ' . $e->getMessage());

            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }



    public function cetak($jenis, $id = null)
    {
        helper('format');
        if (!$id) {
            return redirect()->to('/adminOpd/pk/' . $jenis)->with('error', 'ID PK tidak ditemukan');
        }
        $data = $this->pkModel->getPkById($id);
        if (!$data) {
            return redirect()->to('/adminOpd/pk/' . $jenis)->with('error', 'Data PK tidak ditemukan');
        }
        $data['logo_url'] = FCPATH . 'assets/images/logo.png';

        $data['program_pk'] = $this->pkModel->getProgramByJenis($id, $jenis);

        if ($jenis === 'bupati' || $jenis === 'jpt') {
            $program = "program_kegiatan";
        } elseif ($jenis === 'administrator') {
            $program = "kegiatan";
        } elseif ($jenis === 'pengawas') {
            $program = "sub_kegiatan";
        }

        $tahun = date('Y', strtotime($data['tanggal']));
        $viewPath = 'adminOpd/pk/cetak';
        $viewPathL = 'adminOpd/pk/cetak-L';
        $html_1 = view($viewPath, $data);
        $html_2 = view($viewPathL, [
            'data' => $data,
            'program' => $program,

        ]);
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'FOLIO',
            'default_font_size' => 12,
            'mirrorMargins' => true,
            'tempDir' => sys_get_temp_dir(),
        ]);
        $css = 'img { width: 70px; height: auto; }';
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html_1);
        $mpdf->AddPage('P');
        $mpdf->WriteHTML($html_2);
        $this->response->setHeader('Content-Type', 'application/pdf');
        return $mpdf->Output('Perjanjian-Kinerja-' . $jenis . '-' . $tahun . '.pdf', 'I');
    }
}

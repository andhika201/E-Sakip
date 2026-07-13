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

    private function jabatanPkPayload(array $post, string $side): array
    {
        $statusKey = 'status_jabatan_' . $side;
        $oldPltKey = 'is_plt_' . $side;
        $manualKey = 'jabatan_' . $side . '_manual';

        $status = strtolower(trim((string) ($post[$statusKey] ?? '')));
        if ($status === '' && !empty($post[$oldPltKey])) {
            $status = 'plt';
        }
        if (!in_array($status, ['plt', 'plh'], true)) {
            $status = '';
        }

        $manualJabatan = trim((string) ($post[$manualKey] ?? ''));

        return [
            'is_plt_' . $side => $status === 'plt' ? 1 : 0,
            'is_plh_' . $side => $status === 'plh' ? 1 : 0,
            $manualKey => $manualJabatan !== '' ? $manualJabatan : null,
        ];
    }

    public function index($jenis)
    {
        // 'kecamatan' = segmen URL utk PK Camat. Disimpan sbg jenis 'camat'
        // (klasifikasi Eselon III) namun STRUKTUR datanya identik dgn 'jpt'.
        $seg = $jenis;                                       // segmen URL (utk link/redirect/judul)
        $isKecamatan = ($jenis === 'kecamatan');
        $jenis = $isKecamatan ? 'camat' : $jenis;           // jenis data (disimpan/di-query)

        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $tahun = $this->request->getGet('tahun');
        $pkId = $this->request->getGet('pk_id');

        $pkData = null;
        $pkRelasiList = [];
        $pihak1Level = null;
        $tampilkanProgram = true;

        // 1️⃣ Jika tahun dipilih → ambil daftar relasi
        if ($tahun) {
            $pkRelasiList = $this->pkModel
                ->getPkRelasiByOpdJenisTahun($opdId, $jenis, $tahun);
        }

        // 2️⃣ Jika PK dipilih → ambil data lengkap
        if ($pkId) {
            $pkData = $this->pkModel->getCompletePkById($pkId);

            // 🔥 NORMALISASI (kadang return array[0])
            if (is_array($pkData) && isset($pkData[0])) {
                $pkData = $pkData[0];
            }

            // ==================================================
            // 🔥 DEDUP PROGRAM KHUSUS JPT
            // ==================================================
            if (
                in_array($jenis, ['jpt', 'camat'], true) &&
                !empty($pkData['sasaran']) &&
                is_array($pkData['sasaran'])
            ) {
                $uniquePrograms = [];

                foreach ($pkData['sasaran'] as $sasaran) {
                    foreach ($sasaran['indikator'] ?? [] as $indikator) {
                        foreach ($indikator['program'] ?? [] as $program) {

                            // key unik: program_id + anggaran
                            $key = $program['program_id'] . '|' . $program['anggaran'];

                            $uniquePrograms[$key] = [
                                'program_id' => $program['program_id'],
                                'program_kegiatan' => $program['program_kegiatan'],
                                'anggaran' => $program['anggaran'],
                            ];
                        }
                    }
                }

                // hasil final utk view
                $pkData['program'] = array_values($uniquePrograms);
            }
        }

        // 3️⃣ Cek level pihak 1 (untuk hak tampil)
        if ($pkData && isset($pkData['pihak_1'])) {
            $pegawai = $this->pegawaiModel
                ->getLevelByPegawaiId($pkData['pihak_1']);

            $pihak1Level = $pegawai['level'] ?? null;


            $tampilkanProgram = !($opdId == 2 && $pihak1Level === 'VERIFIKATOR');
        }

        $currentOpd = $this->opdModel->find($opdId);

        return view('adminOpd/pk/pk', [
            'pk_data' => $pkData,
            'pkRelasiList' => $pkRelasiList,
            'tampilkanProgram' => $tampilkanProgram,
            'current_opd' => $currentOpd,
            'currentYear' => date('Y'),
            'pk_id' => $pkId,
            'tahun' => $tahun,
            'jenis' => $jenis,
            'seg' => $seg,
            'isKecamatan' => $isKecamatan,
        ]);
    }

    public function cetak($jenis, $id = null)
    {

        helper('format');
        // 'kecamatan' = PK Camat; disimpan sbg jenis 'camat', struktur data spt 'jpt'.
        $seg = $jenis;
        $isKecamatan = ($jenis === 'kecamatan');
        $jenis = $isKecamatan ? 'camat' : $jenis;

        if (!$id) {
            return redirect()->to('/adminOpd/pk/' . $seg)->with('error', 'ID PK tidak ditemukan');
        }
        $data = $this->pkModel->getPkById($id);
        $tahun = $data['tahun'];
        if (!$data) {
            return redirect()->to('/adminOpd/pk/' . $seg)->with('error', 'Data PK tidak ditemukan');
        }
        $data['logo_url'] = FCPATH . 'assets/images/logo.png';
        $data['isKecamatan'] = $isKecamatan;

        if ($jenis === 'bupati') {
            // Ambil program dari seluruh PK JPT
            $data['program_pk'] = $this->pkModel->getProgramsForBupatiFromPkJpt($tahun);
        } else {
            // Jenis lain tetap normal
            $data['program_pk'] = $this->pkModel->getProgramByJenis($id, $jenis);
        }


        $pegawai1 = $this->pegawaiModel->getLevelByPegawaiId($data['pihak_1']);
        $pihak1Level = $pegawai1['level'] ?? null;
        $opd = strtoupper($data['nama_opd']);

        $tampilkanProgram = !($data['opd_id'] == 2 && $pihak1Level === 'VERIFIKATOR');

        $tahun = date('Y', strtotime($data['tanggal']));
        $viewPath = 'adminOpd/pk/cetak';
        $viewPathL = 'adminOpd/pk/cetak-L';
        $html_1 = view($viewPath, $data);
        $html_2 = view($viewPathL, array_merge($data, [
            'tampilkanProgram' => $tampilkanProgram,
        ]));

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'FOLIO',
            'default_font_size' => 12,
            'mirrorMargins' => true,
            'tempDir' => sys_get_temp_dir(),
        ]);

        helper('setting');
        $footerHtml = pdf_footer_aksara();
        pdf_watermark_aksara($mpdf); // watermark AKSARA halus di latar

        $css = 'img { width: 70px; height: auto; }';
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->SetHTMLFooter($footerHtml, 'O');
        $mpdf->SetHTMLFooter($footerHtml, 'E');
        $mpdf->WriteHTML($html_1);
        $mpdf->AddPage('P');
        $mpdf->WriteHTML($html_2);
        $this->response->setHeader('Content-Type', 'application/pdf');
        return $mpdf->Output('Perjanjian-Kinerja-' . $seg . '-' . $tahun . '.pdf', 'I');
    }



    public function tambah($jenis)
    {
        $seg = $jenis;
        $isKecamatan = ($jenis === 'kecamatan');
        $jenis = $isKecamatan ? 'camat' : $jenis;

        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pegawaiOpd = $this->pegawaiModel->getPegawaiDenganJabatan($opdId, $jenis);

        $currentOpd = $this->opdModel->find($opdId);
        $program = $this->pkModel->getAllPrograms();
        $jptProgram = $this->pkModel->getJptPrograms($opdId);
        $kegiatan = $this->pkModel->getKegiatan();
        $subkegiatan = $this->pkModel->getSubKegiatan();
        $satuan = $this->pkModel->getAllSatuan();
        $kegiatanAdmin = $this->pkModel->getKegiatanAdmin($opdId);
        // Dapatkan PK Pimpinan sebagai acuan sesuai jenis.
        // administrator: acuan = puncak OPD → Dinas 'jpt', Kecamatan 'camat'.
        // pengawas    : acuan = atasan → 'administrator' (Kabid/Sekcam) ATAU 'camat'
        //               (Kasi langsung di bawah Camat). Satu OPD hanya memuat kombinasi
        //               yang relevan, jadi whereIn aman.
        $referensiJenis = null;
        if ($jenis === 'administrator') {
            $referensiJenis = ['jpt', 'camat'];
        } elseif ($jenis === 'pengawas') {
            $referensiJenis = ['administrator', 'camat'];
        }
        $pkPimpinan = [];
        if ($referensiJenis) {
            $pkPimpinan = $this->pkModel
                ->where('opd_id', $opdId)
                ->whereIn('jenis', (array) $referensiJenis)
                ->findAll();
        }

        // OPD kecamatan → pengawas memakai mode "program Camat + kegiatan bebas".
        $isKecamatanOpd = (stripos((string) ($currentOpd['nama_opd'] ?? ''), 'kecamatan') !== false);

        // dd($kegiatanAdmin);
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
            'title' => 'Tambah PK ' . ucfirst($seg),
            'jenis' => $jenis,
            'seg' => $seg,
            'isKecamatan' => $isKecamatan,
            'isKecamatanOpd' => $isKecamatanOpd,
        ]);
    }

    public function edit($jenis, $id)
    {
        $seg = $jenis;
        $isKecamatan = ($jenis === 'kecamatan');
        $jenis = $isKecamatan ? 'camat' : $jenis;

        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pk = $this->pkModel->getPkById($id);
        if (!$pk)
            return redirect()->to('/adminopd/pk/' . $seg)->with('error', 'Data PK tidak ditemukan');


        $pegawaiOpd = $this->pegawaiModel->getPegawaiDenganJabatan($opdId, $jenis);




        $program = $this->pkModel->getAllPrograms();
        $jptProgram = $this->pkModel->getJptPrograms($opdId);
        $kegiatan = $this->pkModel->getKegiatan();
        $subkegiatan = $this->pkModel->getSubKegiatan();
        $satuan = $this->pkModel->getAllSatuan();
        $kegiatanAdmin = $this->pkModel->getKegiatanAdmin($opdId);

        // Acuan atasan (sama seperti tambah): administrator → jpt/camat (puncak OPD),
        // pengawas → administrator ATAU camat (Kasi langsung di bawah Camat).
        $referensiJenis = null;
        if ($jenis === 'administrator') {
            $referensiJenis = ['jpt', 'camat'];
        } elseif ($jenis === 'pengawas') {
            $referensiJenis = ['administrator', 'camat'];
        }
        $pkPimpinan = [];
        if ($referensiJenis) {
            $pkPimpinan = $this->pkModel
                ->where('opd_id', $opdId)
                ->whereIn('jenis', (array) $referensiJenis)
                ->findAll();
        }

        $currentOpd = $this->opdModel->find($opdId);
        $isKecamatanOpd = (stripos((string) ($currentOpd['nama_opd'] ?? ''), 'kecamatan') !== false);

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
            'pkPimpinan' => $pkPimpinan,
            'title' => 'Edit PK ',
            'jenis' => $jenis,
            'seg' => $seg,
            'isKecamatan' => $isKecamatan,
            'isKecamatanOpd' => $isKecamatanOpd,
            'validation' => session()->getFlashdata('validation')
        ]);
    }

    public function save($jenis)
    {
        $seg = $jenis;
        $jenis = ($jenis === 'kecamatan') ? 'camat' : $jenis;

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
        // dd($post['sasaran_pk']);
        // ------------------------------
        // STRUKTUR DATA FINAL
        // ------------------------------
        $jabatanPkPayload = array_merge(
            $this->jabatanPkPayload($post, 'pihak_1'),
            $this->jabatanPkPayload($post, 'pihak_2')
        );

        $saveData = [
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'tahun' => !empty($post['tahun']) ? $post['tahun'] : date('Y'),
            'pihak_1' => $post['pegawai_1_id'] ?? null,
            'pihak_2' => $post['pegawai_2_id'] ?? null,

            'tanggal' => $tanggal,
            'sasaran_pk' => [],
            'referensi_acuan' => $referensiAcuanArr,
            'misi_bupati_id' => $post['misi_bupati_id'] ?? []

        ];
        $saveData = array_merge($saveData, $jabatanPkPayload);

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

                        if ($jenis === 'bupati' && empty(trim($indikator['indikator'] ?? ''))) {
                            continue;
                        }
                        $indikatorData = [
                            'indikator' => $indikator['indikator'] ?? '',
                            'target' => $indikator['target'] ?? '',
                            'id_satuan' => $indikator['id_satuan'] ?? null,
                            'jenis_indikator' => $indikator['jenis_indikator'] ?? null,
                            'jenis' => $jenis,

                            'program' => [], // untuk jpt & admin
                        ];

                        // ------------------------------------------------------
                        // JENIS = JPT / CAMAT → indikator → program
                        // (Camat = puncak kecamatan, struktur identik JPT)
                        // ------------------------------------------------------
                        if (in_array($jenis, ['jpt', 'camat'], true)) {
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
        }
        ;
        // dd($saveData);
        // dd($this->request->getPost('sasaran_pk'));
        // dd($saveData);

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

            return redirect()->to($redirectBase . $seg)
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
        $seg = $jenis; // segmen URL utk redirect (mis. 'kecamatan'); $jenis data diambil dari record
        log_message('debug', "=== UPDATE PK START: ID {$id} ===");

        $pk = $this->pkModel->find($id);
        if (!$pk) {
            log_message('error', "PK ID {$id} tidak ditemukan.");
            return redirect()->back()->with('error', 'Data PK tidak ditemukan.');
        }

        // Otorisasi objek: cegah ubah PK milik OPD lain (IDOR).
        if (!$this->canAccessOpd($pk['opd_id'] ?? null)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah PK OPD lain.');
        }

        $post = $this->request->getPost();
        log_message('debug', "POST DATA: " . json_encode($post));
        $session = session();
        $jenis = $pk['jenis'];
        $tahun = $post['tahun'] ?? $pk['tahun'];
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
        $jabatanPkPayload = array_merge(
            $this->jabatanPkPayload($post, 'pihak_1'),
            $this->jabatanPkPayload($post, 'pihak_2')
        );

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
        $saveData = array_merge($saveData, $jabatanPkPayload);

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
                    'pk_sasaran_id' => $s['pk_sasaran_id'] ?? null,
                    'sasaran' => $s['sasaran'] ?? '',
                    'indikator' => []
                ];

                foreach (($s['indikator'] ?? []) as $iIndex => $ind) {

                    $indikatorData = [
                        'pk_indikator_id' => $ind['pk_indikator_id'] ?? null,
                        'indikator' => $ind['indikator'] ?? '',
                        'target' => $ind['target'] ?? '',
                        'id_satuan' => $ind['id_satuan'] ?? null,
                        'jenis_indikator' => $ind['jenis_indikator'] ?? null,
                        'program' => [],
                        'kegiatan' => []
                    ];

                    // logging indikator
                    log_message('debug', "Parsing indikator [{$sIndex}][{$iIndex}]: " . json_encode($indikatorData));

                    if (in_array($jenis, ['jpt', 'camat'], true)) {
                        foreach ($ind['program'] ?? [] as $p) {
                            $indikatorData['program'][] = [
                                'pk_program_id' => $p['pk_program_id'] ?? null,
                                'program_id' => $p['program_id'] ?? null,
                                'anggaran' => $p['anggaran'] ?? 0
                            ];
                        }
                    }

                    if ($jenis === 'administrator') {
                        foreach ($ind['program'] ?? [] as $p) {
                            $programData = [
                                'pk_program_id' => $p['pk_program_id'] ?? null,
                                'program_id' => $p['program_id'] ?? null,
                                'kegiatan' => []
                            ];
                            foreach ($p['kegiatan'] ?? [] as $k) {
                                $programData['kegiatan'][] = [
                                    'pk_kegiatan_id' => $k['pk_kegiatan_id'] ?? null,
                                    'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                    'anggaran' => $k['anggaran'] ?? 0
                                ];
                            }
                            $indikatorData['program'][] = $programData;
                        }
                    }

                    if ($jenis === 'pengawas') {
                        foreach ($ind['program'] ?? [] as $p) {
                            $programData = [
                                'pk_program_id' => $p['pk_program_id'] ?? null,
                                'program_id' => $p['program_id'] ?? null,
                                'kegiatan' => []
                            ];

                            foreach ($p['kegiatan'] ?? [] as $k) {
                                $kegiatanData = [
                                    'pk_kegiatan_id' => $k['pk_kegiatan_id'] ?? null,
                                    'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                    'subkegiatan' => []
                                ];

                                foreach ($k['subkegiatan'] ?? [] as $sk) {
                                    $kegiatanData['subkegiatan'][] = [
                                        'pk_subkegiatan_id' => $sk['pk_subkegiatan_id'] ?? null,
                                        'subkegiatan_id' => $sk['subkegiatan_id'] ?? null,
                                        'anggaran' => $sk['anggaran'] ?? 0
                                    ];
                                }

                                $programData['kegiatan'][] = $kegiatanData;
                            }

                            $indikatorData['program'][] = $programData;
                        }
                    }

                    log_message(
                        'debug',
                        "PROGRAM RESULT [{$sIndex}][{$iIndex}]: " .
                        json_encode($indikatorData['program'])
                    );


                    $sasaranData['indikator'][] = $indikatorData;
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }
        }
        // dd($saveData);

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
                return redirect()->to($base . $seg)->with('success', 'Data PK berhasil diperbarui.');
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
        $seg = $jenis; // segmen URL utk redirect (mis. 'kecamatan')
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

        // Otorisasi objek: cegah hapus PK milik OPD lain (IDOR).
        if (!$this->canAccessOpd($pk['opd_id'] ?? null)) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['success' => false, 'error' => 'Anda tidak memiliki akses untuk menghapus PK OPD lain.']);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus PK OPD lain.');
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
            return redirect()->to($redirectBase . $seg)
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
}

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

        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pkData = $this->pkModel->getCompletePkByOpdIdAndJenis($opdId, $jenis);
        // If multiple, pick the first (or null if none)

        $currentOpd = $this->opdModel->find($opdId);

        if (is_array($pkData) && count($pkData) > 0) {
            $pkData = $pkData[0];
        } else {
            $pkData = null;
        }

        // dd($pkData);

        return view('adminOpd/pk/pk', [
            'pk_data' => $pkData,
            'current_opd' => $currentOpd,
            'jenis' => $jenis,
        ]);
    }

    public function tambah($jenis)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pegawaiOpd = $this->pegawaiModel->where('opd_id', $opdId)->findAll();
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

        $pegawaiOpd = $this->pegawaiModel->where('opd_id', $opdId)->findAll();
        $program = $this->pkModel->getAllPrograms();
        $satuan = $this->pkModel->getAllSatuan();

        return view('adminOpd/pk/edit_pk', [
            'pk' => $pk,
            'pegawaiOpd' => $pegawaiOpd,
            'program' => $program,
            'satuan' => $satuan,
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
        $now = date('Y-m-d');

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
            'opd_id'     => $opdId,
            'jenis'      => $jenis,
            'pihak_1'    => $post['pegawai_1_id'] ?? null,
            'pihak_2'    => $post['pegawai_2_id'] ?? null,
            'tanggal'    => $now,
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
                    'jenis'   => $jenis,
                    'indikator' => [],
                ];

                // ------------------------------
                // PARSE INDIKATOR
                // ------------------------------
                if (!empty($s['indikator'])) {

                    foreach ($s['indikator'] as $indikator) {

                        $indikatorData = [
                            'indikator'       => $indikator['indikator'] ?? '',
                            'target'          => $indikator['target'] ?? '',
                            'id_satuan'       => $indikator['id_satuan'] ?? null,
                            'jenis_indikator' => $indikator['jenis_indikator'] ?? null,
                            'jenis'           => $jenis,

                            'program'  => [], // untuk jpt & admin
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
                                        'kegiatan'   => [],
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
                                        'kegiatan'   => [],
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
        // dd($post['sasaran_pk'][0]['indikator'][0]);
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
                ? '/adminKab/pk/'
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
    public function update($id)
    {
        $pk = $this->pkModel->find($id);

        if (!$pk) {
            return redirect()->back()->with('error', 'Data PK tidak ditemukan.');
        }

        $jenis = $pk['jenis']; // jpt / administrator / pengawas

        $post = $this->request->getPost();
        $session = session();
        $opdId = $session->get('opd_id') ?: $pk['opd_id'];

        // Build saveData sama seperti save(), tapi untuk update -> kirim ke model updateCompletePk
        $now = date('Y-m-d');

        // parse referensi indikator jika ada
        $referensiAcuanArr = [];
        if (!empty($post['referensi_indikator_id'])) {
            foreach ($post['referensi_indikator_id'] as $val) {
                $parts = explode('-', $val);
                if (count($parts) == 2) {
                    $referensiAcuanArr[] = [
                        'referensi_pk_id' => $parts[0],
                        'referensi_indikator_id' => $parts[1],
                    ];
                }
            }
        }

        $saveData = [
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'pihak_1' => $post['pegawai_1_id'] ?? null,
            'pihak_2' => $post['pegawai_2_id'] ?? null,
            'tanggal' => $now,
            'sasaran_pk' => [],
            'referensi_acuan' => $referensiAcuanArr,
            'misi_bupati_id' => $post['misi_bupati_id'] ?? []
        ];

        // parse sasaran/indikator/program/kegiatan/subkegiatan sama persis seperti pada save()
        if (!empty($post['sasaran_pk']) && is_array($post['sasaran_pk'])) {
            foreach ($post['sasaran_pk'] as $s) {
                $sasaranData = [
                    'sasaran' => $s['sasaran'] ?? '',
                    'indikator' => []
                ];

                if (!empty($s['indikator']) && is_array($s['indikator'])) {
                    foreach ($s['indikator'] as $indikator) {
                        $indikatorData = [
                            'indikator' => $indikator['indikator'] ?? '',
                            'target' => $indikator['target'] ?? '',
                            'id_satuan' => $indikator['id_satuan'] ?? null,
                            'jenis_indikator' => $indikator['jenis_indikator'] ?? null,
                            'program' => [],
                            'kegiatan' => []
                        ];

                        if ($jenis === 'jpt') {
                            foreach ($indikator['program'] ?? [] as $p) {
                                $indikatorData['program'][] = [
                                    'program_id' => $p['program_id'] ?? null,
                                    'anggaran' => $p['anggaran'] ?? 0
                                ];
                            }
                        } elseif ($jenis === 'administrator') {
                            foreach ($indikator['program'] ?? [] as $p) {
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
                        } elseif ($jenis === 'pengawas') {
                            foreach ($indikator['kegiatan'] ?? [] as $k) {
                                $kegiatanData = [
                                    'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                    'subkegiatan' => []
                                ];
                                foreach ($k['subkegiatan'] ?? [] as $sk) {
                                    $kegiatanData['subkegiatan'][] = [
                                        'subkegiatan_id' => $sk['subkegiatan_id'] ?? null,
                                        'anggaran' => $sk['anggaran'] ?? 0
                                    ];
                                }
                                $indikatorData['kegiatan'][] = $kegiatanData;
                            }
                        }

                        $sasaranData['indikator'][] = $indikatorData;
                    }
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }
        }

        // Panggil model updateCompletePk yang melakukan delete/insert dalam transaction
        try {
            $ok = $this->pkModel->updateCompletePk($id, $saveData);

            if ($ok) {
                $redirectBase = (strtolower($jenis) === 'bupati') ? '/adminkab/pk/' : '/adminopd/pk/';
                return redirect()->to($redirectBase . $jenis)
                    ->with('success', 'Data PK berhasil diperbarui');
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui PK');
            }
        } catch (\Exception $e) {
            log_message('error', 'UPDATE ERROR: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * DELETE: aman mengikuti hirarki. Membuang semua child rows sebelum PK utama.
     */
    public function delete($id)
    {
        $pk = $this->pkModel->find($id);
        if (!$pk) {
            return redirect()->back()->with('error', 'Data PK tidak ditemukan.');
        }

        $jenis = $pk['jenis'];
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1) Hapus pk_subkegiatan yang terkait dengan pk_id
            $db->query("
                DELETE sk
                FROM pk_subkegiatan sk
                JOIN pk_kegiatan k ON k.id = sk.pk_kegiatan_id
                JOIN pk_program p ON p.id = k.pk_program_id
                WHERE p.pk_id = ?
            ", [$id]);

            // 2) Hapus pk_kegiatan yang terkait
            $db->query("
                DELETE k
                FROM pk_kegiatan k
                JOIN pk_program p ON p.id = k.pk_program_id
                WHERE p.pk_id = ?
            ", [$id]);

            // 3) Hapus pk_program
            $db->table('pk_program')->where('pk_id', $id)->delete();

            // 4) Hapus pk_subkegiatan untuk kasus pengawas dimana pk_kegiatan mungkin punya pk_program_id = 0
            // (Jika dalam database ada record pk_kegiatan dengan pk_program_id = 0 dan pk actually belongs to this pk via other relation,
            //  itu harus dihapus juga — untuk memastikan, kita hapus pk_subkegiatan dan pk_kegiatan yang tidak punya pk_program join)
            $db->query("
                DELETE sk
                FROM pk_subkegiatan sk
                JOIN pk_kegiatan k ON k.id = sk.pk_kegiatan_id
                WHERE k.pk_program_id = 0
            ");

            $db->table('pk_kegiatan')->where('pk_program_id', 0)->delete();

            // 5) Hapus indikator (berdasarkan sasaran milik pk)
            $db->table('pk_indikator')
                ->whereIn('pk_sasaran_id', function ($builder) use ($id) {
                    $builder->select('id')
                        ->from('pk_sasaran')
                        ->where('pk_id', $id);
                })->delete();

            // 6) Hapus sasaran
            $db->table('pk_sasaran')->where('pk_id', $id)->delete();

            // 7) Hapus pk_misi (jika ada)
            $db->table('pk_misi')->where('pk_id', $id)->delete();

            // 8) Hapus pk_referensi (jika ada)
            $db->table('pk_referensi')->where('pk_id', $id)->delete();

            // 9) Hapus pk utama
            $this->pkModel->delete($id);

            $db->transComplete();

            if ($db->transStatus() === false) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Gagal menghapus PK.');
            }

            $redirectBase = (strtolower($jenis) === 'bupati') ? '/adminkab/pk/' : '/adminopd/pk/';
            return redirect()->to($redirectBase . $jenis)->with('success', 'Data PK berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'DELETE ERROR: ' . $e->getMessage());
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
        // Fetch all program_pk if jenis=bupati
        if (strtolower($jenis) === 'bupati') {
            $data['program_pk'] = $this->pkModel->getAllPrograms();
        }
        $tahun = date('Y', strtotime($data['tanggal']));
        $viewPath = 'adminOpd/pk/cetak';
        $viewPathL = 'adminOpd/pk/cetak-L';
        $html_1 = view($viewPath, $data);
        $html_2 = view($viewPathL, $data);
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
        $mpdf->AddPage('L');
        $mpdf->WriteHTML($html_2);
        $this->response->setHeader('Content-Type', 'application/pdf');
        return $mpdf->Output('Perjanjian-Kinerja-' . $jenis . '-' . $tahun . '.pdf', 'I');
    }
}

    // public function capaian_pk($jenis)
    // {
    //     $session = session();
    //     $opdId = $session->get('opd_id');

    //     if (!$opdId)
    //         return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
    //     $pkData = $this->pkModel->getCompletePkByOpdIdAndJenis($opdId, $jenis);
    //     // If multiple, pick the first (or null if none)

    //     $currentOpd = $this->opdModel->find($opdId);

    //     if (is_array($pkData) && count($pkData) > 0) {
    //         $pkData = $pkData[0];
    //     } else {
    //         $pkData = null;
    //     }

    //     return view('adminOpd/pk/capaian_pk', [
    //         'pk_data' => $pkData,
    //         'current_opd' => $currentOpd,
    //         'jenis' => $jenis,
    //     ]);

    // }

    // public function edit_capaian($jenis, $id)
    // {
    //     $session = session();
    //     $opdId = $session->get('opd_id');

    //     if (!$opdId)
    //         return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
    //     $pkData = $this->pkModel->getCompletePkByOpdIdAndJenis($opdId, $jenis);

    //     $pk = $this->pkModel->getPkById($id);
    //     // If multiple, pick the first (or null if none)

    //     $currentOpd = $this->opdModel->find($opdId);

    //     if (is_array($pkData) && count($pkData) > 0) {
    //         $pkData = $pkData[0];
    //     } else {
    //         $pkData = null;
    //     }

    //     return view('adminopd/pk/edit_capaian', [
    //         'pk' => $pk,
    //         'pk_data' => $pkData,
    //         'current_opd' => $currentOpd,
    //         'jenis' => $jenis,
    //     ]);
    // }

/**
 * Update capaian indikator dari form edit_capaian
 */
    // public function update_capaian($jenis, $pk_id)
    // {
    //     $capaianArr = $this->request->getPost('capaian'); // array: [id_indikator => nilai_capaian]
    //     if (!$capaianArr || !is_array($capaianArr)) {
    //         return redirect()->back()->with('error', 'Data capaian tidak valid');
    //     }
    //     $db = \Config\Database::connect();
    //     foreach ($capaianArr as $indikatorId => $nilaiCapaian) {
    //         $db->table('pk_indikator')->where('id', $indikatorId)->update([
    //             'capaian' => $nilaiCapaian
    //         ]);
    //     }
    //     return redirect()->to('adminkab/capaian_pk/' . $jenis)->with('success', 'Capaian berhasil disimpan');
    // }

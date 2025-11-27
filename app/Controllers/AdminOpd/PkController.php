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
        if (strtolower($jenis) === 'bupati') {
            return view('adminopd/pk/pk', [
                'pk_data' => $pkData,
                'current_opd' => $currentOpd,
                'jenis' => $jenis,
            ]);
        } else {
            return view('adminOpd/pk/pk', [
                'pk_data' => $pkData,
                'current_opd' => $currentOpd,
                'jenis' => $jenis,
            ]);
        }
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
        if (strtolower($jenis) === 'bupati') {
            return view('adminopd/pk/edit_pk', [
                'pk' => $pk,
                'pegawaiOpd' => $pegawaiOpd,
                'program' => $program,
                'satuan' => $satuan,
                'title' => 'Edit PK ',
                'jenis' => $jenis,
                'validation' => session()->getFlashdata('validation')
            ]);
        } else {
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
            return redirect()->back()->withInput()->with('validation', $validation->getErrors());
        }

        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            throw new \Exception("OPD ID tidak ditemukan di session");
        }

        $post = $this->request->getPost();
        $now = date('Y-m-d');

        // -----------------------------------
        // REF REFERENSI INDIKATOR
        // -----------------------------------
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

        // -----------------------------------
        // BENTUK STRUCTURE FINAL SAVE DATA
        // -----------------------------------
        $saveData = [
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'pihak_1' => $post['pegawai_1_id'] ?? null,
            'pihak_2' => $post['pegawai_2_id'] ?? null,
            'tanggal' => $now,
            'sasaran_pk' => [],
            'referensi_acuan' => $referensiAcuanArr,
            'misi_bupati_id' => $post['misi_bupati_id'] ?? [],
        ];

        // -----------------------------------
        // PARSE S A S A R A N
        // -----------------------------------
        if (!empty($post['sasaran_pk'])) {
            foreach ($post['sasaran_pk'] as $s) {

                $sasaranData = [
                    'sasaran' => $s['sasaran'] ?? '',
                    'indikator' => [],
                ];

                // -----------------------------------
                // PARSE I N D I K A T O R
                // -----------------------------------
                if (!empty($s['indikator'])) {
                    foreach ($s['indikator'] as $indikator) {

                        $indikatorData = [
                            'indikator' => $indikator['indikator'] ?? '',
                            'target' => $indikator['target'] ?? '',
                            'id_satuan' => $indikator['id_satuan'] ?? null,
                            'jenis_indikator' => $indikator['jenis_indikator'] ?? null,
                            'program' => [],
                            'kegiatan' => [],
                        ];

                        // -----------------------------------
                        // JENIS = JPT (INDIKATOR → PROGRAM)
                        // -----------------------------------
                        if ($jenis === 'jpt') {
                            if (!empty($indikator['program'])) {
                                foreach ($indikator['program'] as $p) {
                                    $indikatorData['program'][] = [
                                        'program_id' => $p['program_id'] ?? null,
                                        'anggaran' => $p['anggaran'] ?? 0,
                                    ];
                                }
                            }
                        }

                        // -----------------------------------
                        // JENIS = ADMINISTRATOR
                        // (INDIKATOR → PROGRAM → KEGIATAN)
                        // -----------------------------------
                        if ($jenis === 'administrator') {
                            if (!empty($indikator['program'])) {
                                foreach ($indikator['program'] as $p) {

                                    $programData = [
                                        'program_id' => $p['program_id'] ?? null,
                                        'kegiatan' => []
                                    ];

                                    if (!empty($p['kegiatan'])) {
                                        foreach ($p['kegiatan'] as $k) {
                                            $programData['kegiatan'][] = [
                                                'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                                'anggaran' => $k['anggaran'] ?? 0,
                                            ];
                                        }
                                    }

                                    $indikatorData['program'][] = $programData;
                                }
                            }
                        }

                        // -----------------------------------
                        // JENIS = PENGAWAS
                        // (INDIKATOR → KEGIATAN → SUBKEGIATAN)
                        // -----------------------------------
                        if ($jenis === 'pengawas') {
                            if (!empty($indikator['kegiatan'])) {
                                foreach ($indikator['kegiatan'] as $k) {

                                    $kegiatanData = [
                                        'kegiatan_id' => $k['kegiatan_id'] ?? null,
                                        'subkegiatan' => [],
                                    ];

                                    if (!empty($k['subkegiatan'])) {
                                        foreach ($k['subkegiatan'] as $sk) {
                                            $kegiatanData['subkegiatan'][] = [
                                                'subkegiatan_id' => $sk['subkegiatan_id'] ?? null,
                                                'anggaran' => $sk['anggaran'] ?? 0,
                                            ];
                                        }
                                    }

                                    $indikatorData['kegiatan'][] = $kegiatanData;
                                }
                            }
                        }

                        $sasaranData['indikator'][] = $indikatorData;
                    }
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }
        }

        dd($this->request->getPost()['sasaran_pk'][0]);

        // -----------------------------------
        // SIMPAN KE DB via MODEL
        // -----------------------------------
        try {
            $pkId = $this->pkModel->saveCompletePk($saveData);

            if ($pkId) {
                if ($jenis === 'bupati') {
                    return redirect()->to('/adminkab/pk/' . $jenis)
                        ->with('success', 'Data PK berhasil disimpan');
                } else {
                    return redirect()->to('/adminopd/pk/' . $jenis)
                        ->with('success', 'Data PK berhasil disimpan');
                }
            }

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan PK');

        } catch (\Exception $e) {

            log_message('error', 'SAVE ERROR: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }





    public function update($jenis, $id)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $data = $this->request->getPost();
        $now = date('Y-m-d');
        $updateData = [
            'id' => $id,
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'pihak_1' => $data['pegawai_1_id'] ?? null,
            'pihak_2' => $data['pegawai_2_id'] ?? null,
            'tanggal' => $now,
            'sasaran_pk' => [],
            'program' => [],
        ];
        if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
            foreach ($data['sasaran_pk'] as $sasaranItem) {
                $sasaranData = [
                    'sasaran' => $sasaranItem['sasaran'] ?? '',
                    'indikator' => [],
                ];
                if (isset($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                    foreach ($sasaranItem['indikator'] as $indikatorItem) {
                        $sasaranData['indikator'][] = [
                            'indikator' => $indikatorItem['indikator'] ?? '',
                            'target' => $indikatorItem['target'] ?? '',
                            'id_satuan' => $indikatorItem['id_satuan'] ?? null,
                            'jenis_indikator' => $indikatorItem['jenis_indikator'] ?? null,
                        ];
                    }
                }
                $updateData['sasaran_pk'][] = $sasaranData;
            }
        }
        // dd($updateData);
        // Untuk PK Bupati, program dan anggaran tidak perlu diisi
        if (strtolower($jenis) !== 'bupati' && isset($data['program']) && is_array($data['program'])) {
            foreach ($data['program'] as $programItem) {
                $updateData['program'][] = [
                    'program_id' => $programItem['program_id'] ?? null,
                    'anggaran' => $programItem['anggaran'] ?? 0,
                ];
            }
        }
        $success = $this->pkModel->updateCompletePk($id, $updateData);
        if ($success) {
            if (strtolower($jenis) === 'bupati') {
                return redirect()->to('/adminkab/pk/' . $jenis)->with('success', 'Data PK berhasil diperbarui');
            } else {
                return redirect()->to('/adminopd/pk/' . $jenis)->with('success', 'Data PK berhasil diperbarui');
            }
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data PK');
        }
    }

    public function delete($jenis, $id)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $isAjax = $this->request->isAJAX();
        if (!$opdId) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'error' => 'Silakan login terlebih dahulu']);
            }
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }
        $pk = $this->pkModel->find($id);
        if (!$pk) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'error' => 'Data PK tidak ditemukan']);
            }
            return redirect()->to('/adminopd/pk/' . $jenis)->with('error', 'Data PK tidak ditemukan');
        }
        // Delete related data (handled by model or manually)
        $this->pkModel->delete($id);
        if ($isAjax) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to('/adminopd/pk/' . $jenis)->with('success', 'Data PK berhasil dihapus');
    }

    public function capaian_pk($jenis)
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

        return view('adminOpd/pk/capaian_pk', [
            'pk_data' => $pkData,
            'current_opd' => $currentOpd,
            'jenis' => $jenis,
        ]);

    }

    public function edit_capaian($jenis, $id)
    {
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId)
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pkData = $this->pkModel->getCompletePkByOpdIdAndJenis($opdId, $jenis);

        $pk = $this->pkModel->getPkById($id);
        // If multiple, pick the first (or null if none)

        $currentOpd = $this->opdModel->find($opdId);

        if (is_array($pkData) && count($pkData) > 0) {
            $pkData = $pkData[0];
        } else {
            $pkData = null;
        }

        return view('adminopd/pk/edit_capaian', [
            'pk' => $pk,
            'pk_data' => $pkData,
            'current_opd' => $currentOpd,
            'jenis' => $jenis,
        ]);
    }
    public function cetak($jenis, $id = null)
    {
        helper('format');
        if (!$id) {
            return redirect()->to('/adminopd/pk/' . $jenis)->with('error', 'ID PK tidak ditemukan');
        }
        $data = $this->pkModel->getPkById($id);
        if (!$data) {
            return redirect()->to('/adminopd/pk/' . $jenis)->with('error', 'Data PK tidak ditemukan');
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

    /**
     * Update capaian indikator dari form edit_capaian
     */
    public function update_capaian($jenis, $pk_id)
    {
        $capaianArr = $this->request->getPost('capaian'); // array: [id_indikator => nilai_capaian]
        if (!$capaianArr || !is_array($capaianArr)) {
            return redirect()->back()->with('error', 'Data capaian tidak valid');
        }
        $db = \Config\Database::connect();
        foreach ($capaianArr as $indikatorId => $nilaiCapaian) {
            $db->table('pk_indikator')->where('id', $indikatorId)->update([
                'capaian' => $nilaiCapaian
            ]);
        }
        return redirect()->to('adminkab/capaian_pk/' . $jenis)->with('success', 'Capaian berhasil disimpan');
    }
}

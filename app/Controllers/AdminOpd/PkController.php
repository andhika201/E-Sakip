<?php
namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\PegawaiModel;
use App\Models\PkModel;
use App\Models\OpdModel;
use App\Models\RpjmdModel;


class PkController extends BaseController
{
    protected $pegawaiModel;
    protected $pkModel;
    protected $opdModel;
    protected $rpjmdModel;


    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->pkModel = new PkModel();
        $this->opdModel = new OpdModel();
        $this->rpjmdModel = new RpjmdModel();

    }

    public function index($jenis)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $tahun = $this->request->getGet('tahun') ?: date('Y');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil semua PK sesuai OPD, jenis, dan tahun
        $pkData = $this->pkModel->getCompletePkByOpdIdAndJenis($opdId, $jenis, $tahun);
        $currentOpd = $this->opdModel->find($opdId);

        // Ambil daftar misi berdasarkan PK (bukan dari sasaran lagi)
        $misiListById = [];
        if ($pkData) {
            // Kalau hasil dari model berupa array banyak PK
            if (isset($pkData[0])) {
                foreach ($pkData as $pk) {
                    if (!empty($pk['rpjmd_misi_id'])) {
                        $misi = $this->rpjmdModel->getMisiById($pk['rpjmd_misi_id']);
                        if ($misi) {
                            $misiListById[$pk['rpjmd_misi_id']] = $misi['misi'];
                        }
                    }
                }
            } else {
                // Kalau hasil dari model hanya 1 PK
                if (!empty($pkData['rpjmd_misi_id'])) {
                    $misi = $this->rpjmdModel->getMisiById($pkData['rpjmd_misi_id']);
                    if ($misi) {
                        $misiListById[$pkData['rpjmd_misi_id']] = $misi['misi'];
                    }
                }
            }
        }

        return view(strtolower($jenis) === 'bupati' ? 'adminopd/pk/pk' : 'adminOpd/pk/pk', [
            'pk_data' => $pkData,
            'current_opd' => $currentOpd,
            'tahun' => $tahun,
            'jenis' => $jenis,
            'misiListById' => $misiListById,
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
        $satuan = $this->pkModel->getAllSatuan();
        $misiList = $this->rpjmdModel->getAllMisi();

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

        if (strtolower($jenis) === 'bupati') {
            return view('adminopd/pk/tambah_pk', [
                'pegawaiOpd' => $pegawaiOpd,
                'program' => $program,
                'satuan' => $satuan,
                'pkPimpinan' => $pkPimpinan,
                'title' => 'Tambah PK ' . ucfirst($jenis),
                'jenis' => $jenis,
                'misiList' => $misiList,
            ]);
        } else {
            return view('adminOpd/pk/tambah_pk', [
                'pegawaiOpd' => $pegawaiOpd,
                'program' => $program,
                'satuan' => $satuan,
                'pkPimpinan' => $pkPimpinan,
                'title' => 'Tambah PK ' . ucfirst($jenis),
                'jenis' => $jenis,
                'misiList' => $misiList,
            ]);
        }
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
        $misiList = $this->rpjmdModel->getAllMisi();
        $satuan = $this->pkModel->getAllSatuan();

        // dd($pk);
        if (strtolower($jenis) === 'bupati') {
            return view('adminopd/pk/edit_pk', [
                'pk' => $pk,
                'pegawaiOpd' => $pegawaiOpd,
                'program' => $program,
                'satuan' => $satuan,
                'title' => 'Edit PK ',
                'jenis' => $jenis,
                'misiList' => $misiList,
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
                'misiList' => $misiList,
                'validation' => session()->getFlashdata('validation')
            ]);
        }
    }

    public function save($jenis)
    {
        $validation = \Config\Services::validation();
        // Validasi khusus untuk PK Bupati
        if (strtolower($jenis) === 'bupati') {
            $rules = [
                'pegawai_1_id' => 'permit_empty|numeric', // NIP boleh kosong
                'pegawai_2_id' => 'permit_empty|numeric', // pihak kedua boleh kosong
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
            throw new \Exception('OPD ID tidak ditemukan dalam session. Silakan login ulang.');
        }
        $data = $this->request->getPost();

        $now = date('Y-m-d');
        // Proses referensi indikator acuan: value = pkid-indikatorid
        $referensiIndikatorArr = [];
        if (isset($data['referensi_indikator_id']) && is_array($data['referensi_indikator_id'])) {
            foreach ($data['referensi_indikator_id'] as $val) {
                $parts = explode('-', $val);
                if (count($parts) == 2) {
                    $referensiIndikatorArr[] = [
                        'referensi_pk_id' => $parts[0],
                        'referensi_indikator_id' => $parts[1]
                    ];
                }
            }
        }
        $saveData = [
            'opd_id' => $opdId,
            'jenis' => $jenis,
            'tahun' => $data['tahun'],
            'pihak_1' => $data['pegawai_1_id'] ?? null,
            'pihak_2' => $data['pegawai_2_id'] ?? null,
            'tanggal' => $now,
            'rpjmd_misi_id' => $data['rpjmd_misi_id'],
            'sasaran_pk' => [],
            'referensi_acuan' => $referensiIndikatorArr,
        ];

        if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
            foreach ($data['sasaran_pk'] as $sasaranIndex => $sasaranItem) {
                $sasaranData = [
                    'sasaran' => $sasaranItem['sasaran'] ?? '',
                    'indikator' => [],
                ];

                if (isset($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                    foreach ($sasaranItem['indikator'] as $indikatorIndex => $indikatorItem) {
                        $indikatorData = [
                            'indikator' => $indikatorItem['indikator'] ?? '',
                            'target' => $indikatorItem['target'] ?? '',
                            'id_satuan' => $indikatorItem['id_satuan'] ?? null,
                            'jenis_indikator' => $indikatorItem['jenis_indikator'] ?? null,
                            'program' => [],
                        ];

                        // Proses program untuk setiap indikator
                        if (isset($indikatorItem['program']) && is_array($indikatorItem['program'])) {
                            foreach ($indikatorItem['program'] as $programItem) {
                                $indikatorData['program'][] = [
                                    'program_id' => $programItem['program_id'] ?? null,
                                    'anggaran' => $programItem['anggaran'] ?? 0,
                                ];
                            }
                        }

                        $sasaranData['indikator'][] = $indikatorData;
                    }
                }

                $saveData['sasaran_pk'][] = $sasaranData;
            }
        }

        // dd($saveData);

        // Untuk PK Bupati, program dan anggaran tidak perlu diisi

        try {
            // Panggil model untuk menyimpan data
            $pkId = $this->pkModel->saveCompletePk($saveData);

            if ($pkId) {
                if (strtolower($jenis) === 'bupati') {
                    return redirect()->to('/adminkab/pk/' . $jenis)->with('success', 'Data PK berhasil disimpan');
                } else {
                    return redirect()->to('/adminopd/pk/' . $jenis)->with('success', 'Data PK berhasil disimpan');
                }
            } else {
                log_message('error', 'Gagal menyimpan data PK: ' . print_r($saveData, true));
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data PK. Silakan coba lagi.');
            }
        } catch (\Exception $e) {
            log_message('error', 'Error saving PK: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
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
            'tahun' => $data['tahun'] ?? null,

        ];


        if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
            foreach ($data['sasaran_pk'] as $sasaranItem) {
                $sasaranData = [
                    'sasaran' => $sasaranItem['sasaran'] ?? '',
                    'indikator' => [],
                ];
                if (isset($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                    foreach ($sasaranItem['indikator'] as $indikatorIndex => $indikatorItem) {
                        $indikatorData = [
                            'indikator' => $indikatorItem['indikator'] ?? '',
                            'target' => $indikatorItem['target'] ?? '',
                            'id_satuan' => $indikatorItem['id_satuan'] ?? null,
                            'jenis_indikator' => $indikatorItem['jenis_indikator'] ?? null,
                            'program' => [],
                        ];

                        // Proses program untuk setiap indikator
                        if (isset($indikatorItem['program']) && is_array($indikatorItem['program'])) {
                            foreach ($indikatorItem['program'] as $programItem) {
                                $indikatorData['program'][] = [
                                    'program_id' => $programItem['program_id'] ?? null,
                                    'anggaran' => $programItem['anggaran'] ?? 0,
                                ];
                            }
                        }

                        $sasaranData['indikator'][] = $indikatorData;
                    }
                }
                $updateData['sasaran_pk'][] = $sasaranData;
            }
        }
        // Untuk PK Bupati, program dan anggaran tidak perlu diisi
        if (strtolower($jenis) !== 'bupati' && isset($data['program']) && is_array($data['program'])) {
            foreach ($data['program'] as $programItem) {
                $updateData['program'][] = [
                    'program_id' => $programItem['program_id'] ?? null,
                    'anggaran' => $programItem['anggaran'] ?? 0,
                ];
            }
        }
        // dd($updateData);

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

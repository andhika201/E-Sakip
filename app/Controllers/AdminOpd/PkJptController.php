<?php
namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\PegawaiModel;
use App\Models\ProgramPkModel;
use App\Models\PkModel;

class PkJptController extends BaseController
{
    protected $pegawaiModel;
    protected $pkModel;
    protected $programPkModel;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->pkModel = new PkModel();
        $this->programPkModel = new ProgramPkModel();
    }

    public function index()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pkData = $this->pkModel->getCompletePkByOpdIdAndJenis($opdId, 'jpt');
        // Kompatibilitas: isi sasaran_pk dari sasaran agar view tetap bisa akses satuan
        foreach ($pkData as &$pk) {
            if (isset($pk['sasaran'])) {
                $pk['sasaran_pk'] = $pk['sasaran'];
            }
        }
        return view('adminOpd/pk_jpt/pk-jpt', ['pk_data' => $pkData]);
    }

    public function tambah()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pegawaiOpd = $this->pegawaiModel->where('opd_id', $opdId)->findAll();
        $program = $this->programPkModel->getAllPrograms();
        return view('adminOpd/pk_jpt/tambah_pk_jpt', [
            'pegawaiOpd' => $pegawaiOpd,
            'program' => $program,
            'title' => 'Tambah PK Bupati',
        ]);
    }

    public function edit($id)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        $pk = $this->pkModel->getPkById($id);
        if (!$pk) return redirect()->to('/adminopd/pk_jpt')->with('error', 'Data PK tidak ditemukan');
        $pegawaiOpd = $this->pegawaiModel->where('opd_id', $opdId)->findAll();
        $program = $this->programPkModel->getAllPrograms();
        $satuanModel = new \App\Models\SatuanModel();
        $satuan = $satuanModel->getAllSatuan();
        return view('adminOpd/pk_jpt/edit_pk_jpt', [
            'pk' => $pk,
            'pegawaiOpd' => $pegawaiOpd,
            'program' => $program,
            'satuan' => $satuan,
            'title' => 'Edit PK JPT',
        ]);
    }

    public function update($id)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $data = $this->request->getPost();
        $now = date('Y-m-d');

        // Prepare data for updating
        $updateData = [
            'id' => $id,
            'opd_id' => $opdId,
            'jenis' => $data['jenis'],
            'pihak_1' => $data['pegawai_1_id'],
            'pihak_2' => $data['pegawai_2_id'],
            'tanggal' => $now,
            'sasaran_pk' => [],
            'program' => [],
        ];

        // Process Sasaran and Indikator
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
                        ];
                    }
                }
                $updateData['sasaran_pk'][] = $sasaranData;
            }
        }

        // Process Program
        if (isset($data['program']) && is_array($data['program'])) {
            foreach ($data['program'] as $programItem) {
                $updateData['program'][] = [
                    'program_id' => $programItem['program_id'] ?? null,
                    'anggaran' => $programItem['anggaran'] ?? 0,
                ];
            }
        }

        // Call model update method
        $success = $this->pkModel->updateCompletePk($id, $updateData);

        if ($success) {
            return redirect()->to('/adminopd/pk/jpt')->with('success', 'Data PK JPT berhasil diperbarui');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data PK JPT');
        }
    }
}

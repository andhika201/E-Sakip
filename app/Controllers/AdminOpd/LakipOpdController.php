<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\LakipOpdModel;
use App\Models\Opd\IkuModel;
use App\Models\Opd\RenstraModel;

use App\Models\OpdModel;
use App\Models\RpjmdModel;

class LakipOpdController extends BaseController
{
    protected $lakipModel;
    protected $ikuModel;
    protected $renstraModel;
    protected $RpjmdModel;
    protected $opdModel;
    protected $db;


    public function __construct()
    {
        $this->lakipModel = new LakipOpdModel();
        $this->opdModel = new OpdModel();
        $this->ikuModel = new IkuModel();
        $this->renstraModel = new RenstraModel();
        $this->RpjmdModel = new RpjmdModel();
        $this->db = \Config\Database::connect();

        helper(['form', 'url']);

    }

    /**
     * Display list of LAKIP OPD
     */
    public function index()
    {
        // Get OPD ID from session
        $session = session();
        $opdId = $session->get('opd_id');
        $tahun = $this->request->getGet('tahun'); // dari filter tahun

        $role = $session->get('role');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // Build query based on OPD
        $query = $this->lakipModel->where('opd_id', $opdId);

        // Apply filters
        if (!empty($tahun)) {
            $query->where('YEAR(tanggal_laporan)', $tahun);
        }

        $renstraData = $this->renstraModel->getAllSasaranWithIndikatorAndTarget($opdId, $tahun);
        $rpjmdData = $this->RpjmdModel->getSasaranWithIndikatorAndTarget();

        $lakip_data = ($role === 'admin_kab')
            ? $this->lakipModel->getRPJMD()
            : $this->lakipModel->getRenstra($opdId);

        // Get available years
        $availableYears = $this->lakipModel->getAvailableYears();

        // Get OPD info
        $opdInfo = $this->opdModel->find($opdId);


        $data = [
            'title' => 'LAKIP OPD - ' . ($opdInfo['nama_opd'] ?? 'Unknown'),
            'availableYears' => $availableYears,
            'opdInfo' => $opdInfo,
            'renstraData' => $renstraData,
            'rpjmdData' => $rpjmdData,
            'role' => $role,
            'lakip' => $lakip_data,
            'filters' => ['tahun' => $tahun],

        ];

        return view('adminOpd/lakip/lakip', $data);
    }

    /**
     * Show form to create new LAKIP
     */
    public function tambah($indikatorId = null)
    {
        $opdId = session()->get('opd_id');
        $role = session()->get('role');
        $tahun = date('Y');


        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }


        // Ambil data indikator berdasarkan $indikatorId
        $indikator = null;
        if ($indikatorId) {
            $db = \Config\Database::connect();
            if ($role == 'admin_kab') {
                // Ambil dari tabel RPJMD
                $indikator = $db->table('rpjmd_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            } else {
                // Default admin_opd ambil dari tabel Renstra
                $indikator = $db->table('renstra_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            }
        }

        $table = ($role === 'admin_kab')
            ? 'rpjmd_target'
            : 'renstra_target';

        $by = ($role === 'admin_kab')
            ? 'indikator_sasaran_id'
            : 'renstra_indikator_id';


        // Ambil daftar target berdasarkan indikator ini
        $targetList = $db->table($table)
            ->where($by, $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();

        $opdInfo = $this->opdModel->find($opdId);

        $data = [
            'title' => 'Tambah LAKIP OPD',
            'role' => $role,
            'indikator' => $indikator,
            'opdInfo' => $opdInfo,
            'targetList' => $targetList,

            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/lakip/tambah_lakip', $data);
    }

    /**
     * Store new LAKIP
     */
    public function save()
    {

        $session = session();
        $role = $session->get('role');

        // Ambil data dari form
        $Indikator_Sasaran_Id = ($role === 'admin_kab')
            ? $this->request->getPost('rpjmd_id')
            : $this->request->getPost('renstra_indikator_sasaran_id');
        $targetPrev = $this->request->getPost('target_lalu');
        $capaianPrev = $this->request->getPost('capaian_lalu');
        $capaianNow = $this->request->getPost('capaian_tahun_ini');

        // Validasi dasar
        if (empty($Indikator_Sasaran_Id)) {
            return redirect()->back()->with('error', 'Data indikator tidak valid.');
        }
        // Siapkan data untuk disimpan ke tabel `lakip`
        if ($role === 'admin_kab') {
            $data = [
                'renstra_indikator_id' => null,
                'rpjmd_indikator_id' => $Indikator_Sasaran_Id,
                'target_lalu' => $targetPrev ?? null,
                'capaian_lalu' => $capaianPrev ?? null,
                'capaian_tahun_ini' => $capaianNow ?? null,
            ];
        } else {
            $data = [
                'renstra_indikator_id' => $Indikator_Sasaran_Id,
                'rpjmd_indikator_id' => null,
                'target_lalu' => $targetPrev ?? null,
                'capaian_lalu' => $capaianPrev ?? null,
                'capaian_tahun_ini' => $capaianNow ?? null,
            ];
        }

        $this->lakipModel->insert($data);
        return redirect()->to('/adminopd/lakip')->with('success', 'Data berhasil disimpan.');


    }


    /**
     * Show form to edit LAKIP
     */
    public function edit($indikatorId)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');
        $tahun = date('Y');


        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // Ambil data indikator
        $table = ($role === 'admin_kab')
            ? 'rpjmd_indikator_sasaran'
            : 'renstra_indikator_sasaran';

        $indikator = $this->db->table($table)
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        $tableTarget = ($role === 'admin_kab')
            ? 'rpjmd_target'
            : 'renstra_target';

        $by = ($role === 'admin_kab')
            ? 'indikator_sasaran_id'
            : 'renstra_indikator_id';


        // Ambil daftar target berdasarkan indikator ini
        $targetList = $this->db->table($tableTarget)
            ->where($by, $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();


        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }


        $lakip = $this->lakipModel->getLakipDetail($indikatorId, $role);


        $data = [
            'indikator' => $indikator,
            'title' => 'Edit LAKIP OPD',
            'role' => $role,
            'lakip' => $lakip,
            'targetList' => $targetList,
            'validation' => \Config\Services::validation()
        ];

        return view('adminOpd/lakip/edit_lakip', $data);
    }

    /**
     * Update LAKIP
     */
    public function update()
    {
        $opdId = session()->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $data = $this->request->getPost();
        $lakipId = $data['lakip_id'] ?? null;

        if (!$lakipId) {
            session()->setFlashdata('error', 'ID IKU tidak ditemukan');
            return redirect()->back()->withInput();
        }

        try {
            // Update definisi IKU
            $updateData = [
                'target_lalu' => $data['target_lalu'] ?? null,
                'capaian_lalu' => $data['capaian_lalu'] ?? null,
                'capaian_tahun_ini' => $data['capaian_tahun_ini'] ?? null,
            ];
            $this->lakipModel->updateLakip($lakipId, $updateData, 'id');

            session()->setFlashdata('success', 'Data Lakip berhasil diperbarui');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data Lakip: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/lakip'));


    }

    /**
     * Delete LAKIP
     */
    public function delete($id)
    {
        $opdId = session()->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->where('id', $id)
            ->where('opd_id', $opdId)
            ->first();

        if (!$lakip) {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('error', 'Data LAKIP tidak ditemukan');
        }

        if ($this->lakipModel->deleteLakip($id)) {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('success', 'LAKIP berhasil dihapus');
        } else {
            return redirect()->to('/adminopd/lakip_opd')
                ->with('error', 'Gagal menghapus LAKIP');
        }
    }
}

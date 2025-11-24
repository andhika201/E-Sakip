<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\LakipOpdModel;
use App\Models\Opd\RenstraModel;
use App\Models\OpdModel;
use App\Models\RpjmdModel;

class LakipOpdController extends BaseController
{
    protected $lakipModel;
    protected $renstraModel;
    protected $rpjmdModel;
    protected $opdModel;
    protected $db;

    public function __construct()
    {
        $this->lakipModel = new LakipOpdModel();
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();

        helper(['form', 'url']);
    }

    /**
     * INDEX LAKIP
     * - admin_kab : punya filter mode (kabupaten/opd), opd, tahun
     * - admin_opd : filter tahun & status
     */
    public function index()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status');

        $availableYears = $this->lakipModel->getAvailableYears();

        $renstraData = [];
        $rpjmdData = [];
        $lakipData = [];
        $opdInfo = null;
        $opdList = [];
        $mode = 'opd';
        $selectedOpdId = null;

        if ($role === 'admin_kab') {
            // === ADMIN KABUPATEN ===
            $mode = $this->request->getGet('mode') ?: 'kabupaten'; // kabupaten | opd
            $selectedOpdId = $this->request->getGet('opd_id');

            // list OPD utk dropdown
            $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();

            if ($mode === 'kabupaten') {
                // mode kabupaten -> pakai RPJMD
                // asumsi getSasaranWithIndikatorAndTarget bisa menerima tahun (optional)
                $rpjmdData = $this->rpjmdModel->getSasaranWithIndikatorAndTarget($tahun);
                $lakipData = $this->lakipModel->getRPJMD(); // tanpa filter status
            } else {
                // mode OPD -> pilih OPD, pakai RENSTRA
                if (!empty($selectedOpdId)) {
                    $renstraData = $this->renstraModel
                        ->getAllSasaranWithIndikatorAndTarget($selectedOpdId, $tahun);
                    $lakipData = $this->lakipModel->getRenstra($selectedOpdId); // tanpa status
                    $opdInfo = $this->opdModel->find($selectedOpdId);
                }
            }

            $data = [
                'title' => 'LAKIP - Admin Kabupaten',
                'role' => $role,
                'mode' => $mode,
                'opdList' => $opdList,
                'selectedOpdId' => $selectedOpdId,
                'availableYears' => $availableYears,
                'renstraData' => $renstraData,
                'rpjmdData' => $rpjmdData,
                'lakip' => $lakipData,
                'opdInfo' => $opdInfo,
                'filters' => [
                    'tahun' => $tahun,
                    'status' => $status,
                ],
            ];
        } else {
            // === ADMIN OPD ===
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Session tidak valid');
            }

            $opdInfo = $this->opdModel->find($opdId);
            $renstraData = $this->renstraModel
                ->getAllSasaranWithIndikatorAndTarget($opdId, $tahun);
            $lakipData = $this->lakipModel->getRenstra($opdId, $status ?: null);

            $data = [
                'title' => 'LAKIP OPD - ' . ($opdInfo['nama_opd'] ?? ''),
                'role' => $role,
                'mode' => 'opd',
                'opdList' => $opdList,
                'selectedOpdId' => $opdId,
                'availableYears' => $availableYears,
                'renstraData' => $renstraData,
                'rpjmdData' => $rpjmdData,
                'lakip' => $lakipData,
                'opdInfo' => $opdInfo,
                'filters' => [
                    'tahun' => $tahun,
                    'status' => $status,
                ],
            ];
        }

        return view('adminOpd/lakip/lakip', $data);
    }

    /**
     * FORM TAMBAH LAKIP
     */
    public function tambah($indikatorId = null)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        // untuk admin_opd wajib ada opd_id
        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $tahun = date('Y');
        $db = $this->db;

        // Ambil data indikator
        if ($role === 'admin_kab') {
            $indikator = $db->table('rpjmd_indikator_sasaran')
                ->where('id', $indikatorId)
                ->get()
                ->getRowArray();
        } else {
            $indikator = $db->table('renstra_indikator_sasaran')
                ->where('id', $indikatorId)
                ->get()
                ->getRowArray();
        }

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // Ambil target tahun berjalan untuk indikator ini
        $tableTarget = ($role === 'admin_kab') ? 'rpjmd_target' : 'renstra_target';
        $byColumn = ($role === 'admin_kab') ? 'indikator_sasaran_id' : 'renstra_indikator_id';

        $targetList = $db->table($tableTarget)
            ->where($byColumn, $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();

        $opdInfo = null;
        if ($role === 'admin_opd' && $opdId) {
            $opdInfo = $this->opdModel->find($opdId);
        }

        $data = [
            'title' => 'Tambah LAKIP OPD',
            'role' => $role,
            'indikator' => $indikator,
            'opdInfo' => $opdInfo,
            'targetList' => $targetList,
            'validation' => \Config\Services::validation(),
        ];

        return view('adminOpd/lakip/tambah_lakip', $data);
    }

    /**
     * SIMPAN LAKIP
     */
    public function save()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // id indikator renstra/rpjmd
        $indikatorId = ($role === 'admin_kab')
            ? $this->request->getPost('rpjmd_id')
            : $this->request->getPost('renstra_indikator_sasaran_id');

        $targetPrev = $this->request->getPost('target_lalu');
        $capaianPrev = $this->request->getPost('capaian_lalu');
        $capaianNow = $this->request->getPost('capaian_tahun_ini');
        $status = $this->request->getPost('status') ?: 'proses';

        if (empty($indikatorId)) {
            return redirect()->back()->with('error', 'Data indikator tidak valid.')->withInput();
        }

        if ($role === 'admin_kab') {
            $data = [
                'renstra_indikator_id' => null,
                'rpjmd_indikator_id' => $indikatorId,
                'target_lalu' => $targetPrev ?: null,
                'capaian_lalu' => $capaianPrev ?: null,
                'capaian_tahun_ini' => $capaianNow ?: null,
                'status' => $status,
            ];
        } else {
            $data = [
                'renstra_indikator_id' => $indikatorId,
                'rpjmd_indikator_id' => null,
                'target_lalu' => $targetPrev ?: null,
                'capaian_lalu' => $capaianPrev ?: null,
                'capaian_tahun_ini' => $capaianNow ?: null,
                'status' => $status,
            ];
        }

        $this->lakipModel->insert($data);

        return redirect()->to(base_url('adminopd/lakip'))
            ->with('success', 'Data LAKIP berhasil disimpan.');
    }

    /**
     * FORM EDIT LAKIP
     */
    public function edit($indikatorId)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $tahun = date('Y');

        // tabel indikator
        $tableIndikator = ($role === 'admin_kab')
            ? 'rpjmd_indikator_sasaran'
            : 'renstra_indikator_sasaran';

        $indikator = $this->db->table($tableIndikator)
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // target tahun berjalan
        $tableTarget = ($role === 'admin_kab') ? 'rpjmd_target' : 'renstra_target';
        $byColumn = ($role === 'admin_kab') ? 'indikator_sasaran_id' : 'renstra_indikator_id';

        $targetList = $this->db->table($tableTarget)
            ->where($byColumn, $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();

        // data LAKIP utk indikator ini
        $lakip = $this->lakipModel->getLakipDetail($indikatorId, $role);

        $data = [
            'title' => 'Edit LAKIP OPD',
            'role' => $role,
            'indikator' => $indikator,
            'lakip' => $lakip,
            'targetList' => $targetList,
            'validation' => \Config\Services::validation(),
        ];

        return view('adminOpd/lakip/edit_lakip', $data);
    }

    /**
     * UPDATE LAKIP
     */
    public function update()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $data = $this->request->getPost();
        $lakipId = $data['lakip_id'] ?? null;

        if (!$lakipId) {
            session()->setFlashdata('error', 'ID LAKIP tidak ditemukan');
            return redirect()->back()->withInput();
        }

        try {
            $updateData = [
                'target_lalu' => $data['target_lalu'] ?? null,
                'capaian_lalu' => $data['capaian_lalu'] ?? null,
                'capaian_tahun_ini' => $data['capaian_tahun_ini'] ?? null,
            ];

            if (!empty($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $this->lakipModel->updateLakip((int) $lakipId, $updateData);

            session()->setFlashdata('success', 'Data LAKIP berhasil diperbarui');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data LAKIP: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/lakip'));
    }

    /**
     * HAPUS LAKIP
     */
    public function delete($id)
    {
        $session = session();
        $role = $session->get('role');

        if (!$role) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->find($id);

        if (!$lakip) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Data LAKIP tidak ditemukan');
        }

        if ($this->lakipModel->deleteLakip((int) $id)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('success', 'LAKIP berhasil dihapus');
        }

        return redirect()->to(base_url('adminopd/lakip'))
            ->with('error', 'Gagal menghapus LAKIP');
    }
    /**
     * UBAH STATUS LAKIP
     */
    public function status($id, $to)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // status yang diperbolehkan
        $allowedStatus = ['proses', 'siap'];
        if (!in_array($to, $allowedStatus)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Status tidak valid.');
        }

        // cek data lakip
        $lakip = $this->lakipModel->find($id);
        if (!$lakip) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Data LAKIP tidak ditemukan.');
        }

        // update status
        try {
            $this->lakipModel->updateLakip((int) $id, ['status' => $to]);
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('success', 'Status LAKIP berhasil diubah menjadi: ' . ucfirst($to));
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }
}

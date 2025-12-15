<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\LakipModel;
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
        $this->lakipModel = new LakipModel();
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();

        helper(['form', 'url']);
    }
    private function xssRule(): string
    {
        return 'regex_match[/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is]';
    }

    private function buildQs(?string $tahun, ?string $status, ?string $mode = null, ?int $opdId = null): string
    {
        $params = [];
        if (!empty($mode))
            $params['mode'] = $mode;
        if (!empty($opdId))
            $params['opd_id'] = $opdId;
        if (!empty($tahun))
            $params['tahun'] = $tahun;
        if (!empty($status))
            $params['status'] = $status;

        return empty($params) ? '' : ('?' . http_build_query($params));
    }

    /* =========================================================
     * INDEX
     * =======================================================*/
    public function index()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = (int) $session->get('opd_id');

        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status');

        $availableYears = $this->lakipModel->getAvailableYears();

        $mode = 'opd';
        $selectedOpdId = null;
        $opdInfo = null;
        $opdList = [];

        // OUTPUT untuk VIEW BARU kamu
        $dataSource = [];
        $lakipMap = [];
        $qsBase = '';

        if ($role === 'admin_kab') {
            // admin kab boleh mode kabupaten/opd
            $mode = $this->request->getGet('mode') ?: 'kabupaten';
            $selectedOpdId = $this->request->getGet('opd_id') ? (int) $this->request->getGet('opd_id') : null;
            $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();

            if ($mode === 'kabupaten') {
                // pakai LakipModel (flat rows)
                $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
                $lakipMapTarget = $this->lakipModel->getLakipMapRpjmd((string) $tahun, $status ?: null);

                // buat map by indikator_id agar cocok dengan view kamu (lakipMap[$indikatorId])
                foreach ($lakipMapTarget as $tId => $l) {
                    if (!empty($l['indikator_id'])) {
                        $lakipMap[(int) $l['indikator_id']] = $l;
                    }
                }

                $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'kabupaten');
                $qsBase = $this->buildQs((string) $tahun, $status, 'kabupaten', null);
            } else {
                // mode opd (admin_kab wajib pilih OPD)
                if (!empty($selectedOpdId)) {
                    $opdInfo = $this->opdModel->find($selectedOpdId);

                    $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $selectedOpdId);
                    $lakipMapTarget = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $selectedOpdId);

                    foreach ($lakipMapTarget as $tId => $l) {
                        if (!empty($l['indikator_id'])) {
                            $lakipMap[(int) $l['indikator_id']] = $l;
                        }
                    }

                    $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'opd');
                }

                $qsBase = $this->buildQs((string) $tahun, $status, 'opd', $selectedOpdId);
            }

            $data = [
                'title' => 'LAKIP - Admin Kabupaten',
                'role' => $role,
                'mode' => $mode,
                'opdList' => $opdList,
                'selectedOpdId' => $selectedOpdId,
                'opdInfo' => $opdInfo,
                'availableYears' => $availableYears,

                // ini yang dipakai view kamu
                'dataSource' => $dataSource,
                'lakipMap' => $lakipMap,
                'qsBase' => $qsBase,
                'tahunAktif' => (string) $tahun,

                'filters' => [
                    'tahun' => (string) $tahun,
                    'status' => $status,
                ],
            ];

        } else {
            // admin_opd
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Session tidak valid');
            }

            $opdInfo = $this->opdModel->find($opdId);

            $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdId);
            $lakipMapTarget = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $opdId);

            foreach ($lakipMapTarget as $tId => $l) {
                if (!empty($l['indikator_id'])) {
                    $lakipMap[(int) $l['indikator_id']] = $l;
                }
            }

            $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'opd');
            $qsBase = $this->buildQs((string) $tahun, $status);

            $data = [
                'title' => 'LAKIP OPD - ' . ($opdInfo['nama_opd'] ?? ''),
                'role' => $role,
                'mode' => 'opd',
                'opdInfo' => $opdInfo,
                'availableYears' => $availableYears,

                // ini yang dipakai view kamu
                'dataSource' => $dataSource,
                'lakipMap' => $lakipMap,
                'qsBase' => $qsBase,
                'tahunAktif' => (string) $tahun,

                'filters' => [
                    'tahun' => (string) $tahun,
                    'status' => $status,
                ],
            ];
        }

        return view('adminOpd/lakip/lakip', $data);
    }
    /* =========================================================
     * FORM TAMBAH (FIX redirect back)
     * =======================================================*/
    public function tambah($indikatorId = null)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = (int) $session->get('opd_id');

        if ($role !== 'admin_opd' || !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status');
        $qsBack = $this->buildQs((string) $tahun, $status);

        $indikatorId = (int) $indikatorId;
        if (!$indikatorId) {
            return redirect()->to(base_url('adminopd/lakip') . $qsBack)->with('error', 'Indikator tidak valid.');
        }

        // ambil target detail via MODEL (lebih aman)
        $target = $this->lakipModel->getRenstraTargetDetailByIndikatorAndYear($indikatorId, (string) $tahun);

        if (!$target) {
            return redirect()->to(base_url('adminopd/lakip') . $qsBack)
                ->with('error', 'Target tahun ' . $tahun . ' untuk indikator ini belum diisi.');
        }

        // validasi indikator milik OPD login
        $cekOpd = $this->db->table('renstra_target rt')
            ->select('rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.renstra_indikator_id', $indikatorId)
            ->where('rt.tahun', $tahun)
            ->get()->getRowArray();

        if ((int) ($cekOpd['opd_id'] ?? 0) !== $opdId) {
            return redirect()->to(base_url('adminopd/lakip') . $qsBack)
                ->with('error', 'Akses ditolak: indikator bukan milik OPD anda.');
        }

        // cegah dobel LAKIP
        $exist = $this->lakipModel->getLakipByRenstraTarget((int) $target['id']);
        if ($exist) {
            return redirect()->to(base_url('adminopd/lakip/edit/' . $indikatorId) . $qsBack)
                ->with('info', 'LAKIP sudah ada. Silakan edit.');
        }

        return view('adminOpd/lakip/tambah_lakip', [
            'title' => 'Tambah LAKIP',
            'role' => $role,
            'indikator' => [
                'id' => $indikatorId,
                'indikator_sasaran' => $target['indikator_sasaran'] ?? '',
                'satuan' => $target['satuan'] ?? '',
                'jenis_indikator' => $target['jenis_indikator'] ?? 'indikator positif',
                'sasaran' => $target['sasaran'] ?? '',
            ],
            'target' => $target, // rt.*
            'opdInfo' => $this->opdModel->find($opdId),
            'tahun' => (string) $tahun,
            'qsBase' => $qsBack,
            'validation' => \Config\Services::validation(),
        ]);
    }

    /* =========================================================
     * SIMPAN
     * =======================================================*/
    public function save()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }
        $rx = $this->xssRule();

        // ============================
        // VALIDASI (ANTI XSS/SCRIPT)
        // ============================
        $rules = [
            'status' => 'permit_empty|string|max_length[50]|' . $rx,
            'target_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_tahun_ini' => 'permit_empty|string|max_length[255]|' . $rx,
        ];

        // target id wajib tergantung role
        if ($role === 'admin_kab') {
            $rules['rpjmd_target_id'] = 'required|integer';
        } else {
            $rules['renstra_target_id'] = 'required|integer';
        }

        $messages = [
            'status' => ['regex_match' => 'Status mengandung script / input berbahaya.'],
            'target_lalu' => ['regex_match' => 'Target lalu mengandung script / input berbahaya.'],
            'capaian_lalu' => ['regex_match' => 'Capaian lalu mengandung script / input berbahaya.'],
            'capaian_tahun_ini' => ['regex_match' => 'Capaian tahun ini mengandung script / input berbahaya.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }
        $targetPrev = $this->request->getPost('target_lalu');
        $capaianPrev = $this->request->getPost('capaian_lalu');
        $capaianNow = $this->request->getPost('capaian_tahun_ini');
        $status = $this->request->getPost('status') ?: 'proses';

        if ($role === 'admin_kab') {
            $targetId = $this->request->getPost('rpjmd_target_id');
            if (empty($targetId)) {
                return redirect()->back()->with('error', 'Target RPJMD tidak valid.')->withInput();
            }

            $data = [
                'renstra_target_id' => null,
                'rpjmd_target_id' => (int) $targetId,
                'target_lalu' => $targetPrev ?: null,
                'capaian_lalu' => $capaianPrev ?: null,
                'capaian_tahun_ini' => $capaianNow ?: null,
                'status' => $status,
            ];
        } else {
            $targetId = $this->request->getPost('renstra_target_id');
            if (empty($targetId)) {
                return redirect()->back()->with('error', 'Target RENSTRA tidak valid.')->withInput();
            }

            $data = [
                'renstra_target_id' => (int) $targetId,
                'rpjmd_target_id' => null,
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

    /* =========================================================
     * FORM EDIT (FIX: wajib kirim tahun ke model)
     * =======================================================*/
    public function edit($indikatorId)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $tahun = $this->request->getGet('tahun') ?: date('Y');

        // indikator
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

        $target = $this->db->table($tableTarget)
            ->where($byColumn, $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();

        // ✅ FIX: kirim $tahun ke model
        $lakip = $this->lakipModel->getLakipDetail((int) $indikatorId, $role, (string) $tahun);

        return view('adminOpd/lakip/edit_lakip', [
            'title' => 'Edit LAKIP',
            'role' => $role,
            'indikator' => $indikator,
            'lakip' => $lakip,
            'target' => $target,
            'tahun' => (string) $tahun,
            'validation' => \Config\Services::validation(),
        ]);
    }
    /* =========================================================
     * UPDATE
     * =======================================================*/
    public function update()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }
        
        $rx = $this->xssRule();

        // ============================
        // VALIDASI (ANTI XSS/SCRIPT)
        // ============================
        $rules = [
            'lakip_id' => 'required|integer',
            'status' => 'permit_empty|string|max_length[50]|' . $rx,
            'target_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_tahun_ini' => 'permit_empty|string|max_length[255]|' . $rx,
        ];

        $messages = [
            'status' => ['regex_match' => 'Status mengandung script / input berbahaya.'],
            'target_lalu' => ['regex_match' => 'Target lalu mengandung script / input berbahaya.'],
            'capaian_lalu' => ['regex_match' => 'Capaian lalu mengandung script / input berbahaya.'],
            'capaian_tahun_ini' => ['regex_match' => 'Capaian tahun ini mengandung script / input berbahaya.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
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

    /* =========================================================
     * DELETE
     * =======================================================*/
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

    /* =========================================================
     * UBAH STATUS
     * =======================================================*/
    public function status($id, $to)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if ($role === 'admin_opd' && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $allowedStatus = ['proses', 'siap'];
        if (!in_array($to, $allowedStatus)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Status tidak valid.');
        }

        $lakip = $this->lakipModel->find($id);
        if (!$lakip) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Data LAKIP tidak ditemukan.');
        }

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

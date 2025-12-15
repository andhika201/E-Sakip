<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\LakipModel;
use App\Models\OpdModel;

class LakipController extends BaseController
{
    protected $lakipModel;
    protected $opdModel;
    protected $db;

    public function __construct()
    {
        $this->lakipModel = new LakipModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
    }

    public function index()
    {
        $session = session();
        $role = $session->get('role');
        if ($role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $mode = $this->request->getGet('mode') ?: 'kabupaten'; // kabupaten | opd
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id'); // boleh kosong = semua opd

        $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();
        $availableYears = $this->lakipModel->getAvailableYears();

        $rows = [];
        $lakipMap = [];

        if ($mode === 'opd') {
            $opdIdInt = (!empty($opdId) ? (int) $opdId : null);

            $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdIdInt);
            $lakipMap = $this->lakipModel->getLakipMapRenstra((string) $tahun, ($status ?: null), $opdIdInt);
        } else {
            $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
            $lakipMap = $this->lakipModel->getLakipMapRpjmd((string) $tahun, ($status ?: null));
        }

        return view('adminKabupaten/lakip/lakip', [
            'title' => 'LAKIP - Admin Kabupaten',
            'role' => $role,
            'mode' => $mode,
            'availableYears' => $availableYears,
            'opdList' => $opdList,
            'selectedOpdId' => $opdId,
            'filters' => ['tahun' => $tahun, 'status' => $status],
            'rows' => $rows,
            'lakipMap' => $lakipMap,
        ]);
    }

    public function tambah($targetId = null)
    {
        $session = session();
        $role = $session->get('role');
        if ($role !== 'admin_kab')
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getGet('opd_id');

        if (!$targetId)
            return redirect()->back()->with('error', 'Target tidak valid.');

        if ($mode === 'kabupaten') {
            $target = $this->db->table('rpjmd_target')->where('id', $targetId)->get()->getRowArray();
            if (!$target)
                return redirect()->back()->with('error', 'Target RPJMD tidak ditemukan.');

            $indikator = $this->db->table('rpjmd_indikator_sasaran')
                ->where('id', $target['indikator_sasaran_id'])
                ->get()->getRowArray();

            if (!$indikator)
                return redirect()->back()->with('error', 'Indikator RPJMD tidak ditemukan.');

            $opdInfo = null;
        } else {
            $target = $this->db->table('renstra_target')->where('id', $targetId)->get()->getRowArray();
            if (!$target)
                return redirect()->back()->with('error', 'Target RENSTRA tidak ditemukan.');

            $indikator = $this->db->table('renstra_indikator_sasaran')
                ->where('id', $target['renstra_indikator_id'])
                ->get()->getRowArray();

            if (!$indikator)
                return redirect()->back()->with('error', 'Indikator RENSTRA tidak ditemukan.');

            $opdInfo = $this->db->table('renstra_sasaran rs')
                ->select('o.*')
                ->join('opd o', 'o.id = rs.opd_id', 'left')
                ->where('rs.id', $indikator['renstra_sasaran_id'])
                ->get()->getRowArray();

            if (!empty($selectedOpdId) && !empty($opdInfo['id']) && (int) $opdInfo['id'] !== (int) $selectedOpdId) {
                return redirect()->back()->with('error', 'Target tidak sesuai OPD yang dipilih.');
            }
        }

        return view('adminKabupaten/lakip/tambah_lakip', [
            'title' => 'Tambah LAKIP',
            'role' => $role,
            'mode' => $mode,
            'tahun' => $tahun,
            'selectedOpdId' => $selectedOpdId,
            'indikator' => $indikator,
            'target' => $target,
            'opdInfo' => $opdInfo,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function save()
    {
        $session = session();
        $role = $session->get('role');
        if ($role !== 'admin_kab')
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getPost('mode') ?: 'kabupaten';
        $tahun = $this->request->getPost('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getPost('selected_opd_id') ?: '';

        $dataCommon = [
            'target_lalu' => $this->request->getPost('target_lalu') ?: null,
            'capaian_lalu' => $this->request->getPost('capaian_lalu') ?: null,
            'capaian_tahun_ini' => $this->request->getPost('capaian_tahun_ini') ?: null,
            'status' => 'proses',
        ];

        if ($mode === 'opd') {
            $renstraTargetId = (int) $this->request->getPost('renstra_target_id');
            if (!$renstraTargetId)
                return redirect()->back()->with('error', 'Target RENSTRA tidak valid.')->withInput();

            $exist = $this->lakipModel->getLakipByRenstraTarget($renstraTargetId);
            if ($exist)
                return redirect()->back()->with('error', 'LAKIP untuk target ini sudah ada. Silakan edit.')->withInput();

            $insert = array_merge($dataCommon, [
                'renstra_target_id' => $renstraTargetId,
                'rpjmd_target_id' => null,
            ]);
        } else {
            $rpjmdTargetId = (int) $this->request->getPost('rpjmd_target_id');
            if (!$rpjmdTargetId)
                return redirect()->back()->with('error', 'Target RPJMD tidak valid.')->withInput();

            $exist = $this->lakipModel->getLakipByRpjmdTarget($rpjmdTargetId);
            if ($exist)
                return redirect()->back()->with('error', 'LAKIP untuk target ini sudah ada. Silakan edit.')->withInput();

            $insert = array_merge($dataCommon, [
                'renstra_target_id' => null,
                'rpjmd_target_id' => $rpjmdTargetId,
            ]);
        }

        $this->lakipModel->insert($insert);

        $qs = '?mode=' . $mode . '&tahun=' . $tahun;
        if ($mode === 'opd')
            $qs .= '&opd_id=' . urlencode($selectedOpdId);

        return redirect()->to(base_url('adminkab/lakip') . $qs)->with('success', 'Data LAKIP berhasil disimpan.');
    }

    public function edit($indikatorId)
    {
        $session = session();
        $role = $session->get('role');
        if ($role !== 'admin_kab')
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getGet('opd_id') ?: '';

        if ($mode === 'opd') {
            $targetDetail = $this->lakipModel->getRenstraTargetDetailByIndikatorAndYear((int) $indikatorId, (string) $tahun);
            if (!$targetDetail)
                return redirect()->back()->with('error', 'Target RENSTRA tahun ' . $tahun . ' belum diisi.');

            $lakip = $this->lakipModel->getLakipByRenstraTarget((int) $targetDetail['id']);
            if (!$lakip) {
                // ✅ FIX: redirect ke tambah pakai TARGET_ID
                $qs = '?mode=opd&tahun=' . $tahun . '&opd_id=' . urlencode($selectedOpdId);
                return redirect()->to(base_url('adminkab/lakip/tambah/' . $targetDetail['id']) . $qs)
                    ->with('error', 'Data LAKIP belum ada. Silakan tambah.');
            }

            return view('adminKabupaten/lakip/edit_lakip', [
                'title' => 'Edit LAKIP (Mode OPD/RENSTRA)',
                'role' => $role,
                'mode' => $mode,
                'tahun' => $tahun,
                'selectedOpdId' => $selectedOpdId,
                'target' => $targetDetail,
                'lakip' => $lakip,
                'validation' => \Config\Services::validation(),
            ]);
        }

        $targetDetail = $this->lakipModel->getRpjmdTargetDetailByIndikatorAndYear((int) $indikatorId, (string) $tahun);
        if (!$targetDetail)
            return redirect()->back()->with('error', 'Target RPJMD tahun ' . $tahun . ' belum diisi.');

        $lakip = $this->lakipModel->getLakipByRpjmdTarget((int) $targetDetail['id']);
        if (!$lakip) {
            // ✅ FIX: redirect ke tambah pakai TARGET_ID
            $qs = '?mode=kabupaten&tahun=' . $tahun;
            return redirect()->to(base_url('adminkab/lakip/tambah/' . $targetDetail['id']) . $qs)
                ->with('error', 'Data LAKIP belum ada. Silakan tambah.');
        }

        return view('adminKabupaten/lakip/edit_lakip', [
            'title' => 'Edit LAKIP (Mode Kabupaten/RPJMD)',
            'role' => $role,
            'mode' => $mode,
            'tahun' => $tahun,
            'selectedOpdId' => $selectedOpdId,
            'target' => $targetDetail,
            'lakip' => $lakip,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function update()
    {
        $session = session();
        $role = $session->get('role');
        if ($role !== 'admin_kab')
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getPost('mode') ?: 'kabupaten';
        $tahun = $this->request->getPost('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getPost('selected_opd_id') ?: '';

        $lakipId = (int) ($this->request->getPost('lakip_id') ?? 0);
        if (!$lakipId)
            return redirect()->back()->with('error', 'ID LAKIP tidak ditemukan')->withInput();

        $updateData = [
            'target_lalu' => $this->request->getPost('target_lalu') ?: null,
            'capaian_lalu' => $this->request->getPost('capaian_lalu') ?: null,
            'capaian_tahun_ini' => $this->request->getPost('capaian_tahun_ini') ?: null,
            'status' => $this->request->getPost('status') ?: 'proses',
        ];

        $this->lakipModel->updateLakip($lakipId, $updateData);

        $qs = '?mode=' . $mode . '&tahun=' . $tahun;
        if ($mode === 'opd')
            $qs .= '&opd_id=' . urlencode($selectedOpdId);

        return redirect()->to(base_url('adminkab/lakip') . $qs)->with('success', 'Data LAKIP berhasil diperbarui');
    }

    public function status($id, $to)
    {
        $session = session();
        $role = $session->get('role');
        if ($role !== 'admin_kab')
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $allowed = ['proses', 'siap'];
        if (!in_array($to, $allowed, true))
            return redirect()->back()->with('error', 'Status tidak valid.');

        $this->lakipModel->updateLakip((int) $id, ['status' => $to]);
        return redirect()->back()->with('success', 'Status LAKIP diubah menjadi ' . ucfirst($to));
    }
}

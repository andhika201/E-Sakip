<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Opd\MonevModel;

class MonevController extends BaseController
{
    protected $db;
    protected $monev;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->monev = new MonevModel();
    }

    /** INDEX: Admin Kab – wajib pilih OPD, filter tahun (all/spesifik) */
    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        $tahunParam = (string) ($this->request->getGet('tahun') ?? 'all');
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all') ? null : (string) (int) $tahunParam;

        $opdId = (int) ($this->request->getGet('opd_id') ?? 0);

        // Dropdown OPD
        $opdList = $this->db->table('opd')->select('id, nama_opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $opdName = '';
        foreach ($opdList as $o) {
            if ((int) $o['id'] === $opdId) {
                $opdName = $o['nama_opd'];
                break;
            }
        }

        $monevList = [];
        if ($opdId > 0) {
            $monevList = $this->monev->getIndexDataAdminKab($tahun, $opdId);
        } else {
            session()->setFlashdata('error', 'Silakan pilih OPD terlebih dahulu.');
        }

        $tahunList = $this->monev->getAvailableYears();

        return view('adminKabupaten/monev/monev', [
            'monevList' => $monevList,
            'tahun' => $tahun ?? 'all',
            'tahunList' => $tahunList,
            'opdList' => $opdList,
            'opdFilter' => $opdId,
            'opdName' => $opdName,
        ]);
    }

    /** FORM TAMBAH: Admin Kab – butuh target_rencana_id & opd_id (dari filter) */
    public function tambah()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Anda tidak berhak.');
        }

        $targetId = (int) $this->request->getGet('target_rencana_id');
        $opdId = (int) $this->request->getGet('opd_id');
        $tahunQS = (string) ($this->request->getGet('tahun') ?? 'all');

        if ($targetId <= 0 || $opdId <= 0) {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Parameter tidak lengkap.');
        }

        // Detail target_rencana + RENSTRA, validasi kepemilikan OPD
        $target = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id AS target_id, tr.opd_id, tr.rencana_aksi, tr.penanggung_jawab,
                rt.id AS renstra_target_id, rt.tahun AS indikator_tahun, rt.target AS indikator_target,
                ris.indikator_sasaran, ris.satuan,
                rs.sasaran AS sasaran_renstra, rs.opd_id AS rs_opd_id
            ')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $targetId)
            ->get()->getRowArray();

        if (!$target) {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Target tidak ditemukan.');
        }
        if ((int) $target['rs_opd_id'] !== $opdId) {
            $q = http_build_query(['opd_id' => $opdId, 'tahun' => $tahunQS]);
            return redirect()->to(base_url('adminkab/monev') . '?' . $q)->with('error', 'Target bukan milik OPD yang dipilih.');
        }

        // Anti duplikat
        $existing = $this->monev->findByTargetAndOpd($targetId, $opdId);
        if ($existing) {
            $q = http_build_query(['opd_id' => $opdId, 'tahun' => $tahunQS]);
            return redirect()->to(base_url('adminkab/monev/edit/' . (int) $existing['id']) . '?' . $q)
                ->with('success', 'Data sudah ada. Silakan edit.');
        }

        return view('adminKabupaten/monev/tambah_monev', [
            'target' => $target,
            'opdId' => $opdId,
            'tahunQS' => $tahunQS,
        ]);
    }

    /** SAVE: Admin Kab – TOTAL manual */
    public function save()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Anda tidak berhak.');
        }

        $rules = [
            'opd_id' => 'required|integer',
            'target_rencana_id' => 'required|integer',
            'capaian_triwulan_1' => 'permit_empty|decimal',
            'capaian_triwulan_2' => 'permit_empty|decimal',
            'capaian_triwulan_3' => 'permit_empty|decimal',
            'capaian_triwulan_4' => 'permit_empty|decimal',
            'total' => 'required|decimal',
            'tahun_qs' => 'permit_empty|string',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $targetId = (int) $this->request->getPost('target_rencana_id');
        $tahunQS = (string) ($this->request->getPost('tahun_qs') ?? 'all');

        // Validasi target milik OPD
        $rt = $this->db->table('target_rencana AS tr')
            ->select('tr.id, rs.opd_id, rt.tahun')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $targetId)
            ->get()->getRowArray();

        if (!$rt || (int) $rt['opd_id'] !== $opdId) {
            $q = http_build_query(['opd_id' => $opdId, 'tahun' => $tahunQS]);
            return redirect()->to(base_url('adminkab/monev') . '?' . $q)->with('error', 'Target/OPD tidak cocok.');
        }

        $payload = [
            'capaian_triwulan_1' => (string) $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2' => (string) $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3' => (string) $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4' => (string) $this->request->getPost('capaian_triwulan_4'),
            'total' => (string) $this->request->getPost('total'),
        ];

        $this->monev->upsertForTarget($targetId, $opdId, $payload);

        $q = http_build_query(['opd_id' => $opdId, 'tahun' => ($tahunQS ?: ($rt['tahun'] ?? 'all'))]);
        return redirect()->to(base_url('adminkab/monev') . '?' . $q)->with('success', 'Data capaian berhasil disimpan.');
    }

    /** FORM EDIT: Admin Kab */
    public function edit($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Anda tidak berhak.');
        }

        $row = $this->db->table('monev AS m')
            ->select('
                m.*,
                tr.rencana_aksi, tr.penanggung_jawab, tr.opd_id AS tr_opd_id,
                rt.tahun AS indikator_tahun, rt.target AS indikator_target,
                ris.indikator_sasaran, ris.satuan,
                rs.sasaran AS sasaran_renstra, rs.opd_id AS rs_opd_id
            ')
            ->join('target_rencana AS tr', 'tr.id = m.target_rencana_id', 'left')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('m.id', (int) $id)
            ->get()->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Data tidak ditemukan.');
        }

        return view('adminKabupaten/monev/edit_monev', [
            'monev' => $row,
            'opdId' => (int) ($row['tr_opd_id'] ?? 0),
            'tahunQS' => (string) ($this->request->getGet('tahun') ?? 'all'),
        ]);
    }

    /** UPDATE: Admin Kab – TOTAL manual */
    public function update($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Anda tidak berhak.');
        }

        $row = $this->monev->find((int) $id);
        if (!$row) {
            return redirect()->to(base_url('adminkab/monev'))->with('error', 'Data tidak ditemukan.');
        }

        $rules = [
            'capaian_triwulan_1' => 'permit_empty|decimal',
            'capaian_triwulan_2' => 'permit_empty|decimal',
            'capaian_triwulan_3' => 'permit_empty|decimal',
            'capaian_triwulan_4' => 'permit_empty|decimal',
            'total' => 'required|decimal',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload = [
            'capaian_triwulan_1' => (string) $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2' => (string) $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3' => (string) $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4' => (string) $this->request->getPost('capaian_triwulan_4'),
            'total' => (string) $this->request->getPost('total'),
        ];

        $this->monev->update((int) $id, $payload);

        $opdId = (int) ($this->request->getGet('opd_id') ?? ($row['opd_id'] ?? 0));
        $tahunQS = (string) ($this->request->getGet('tahun') ?? 'all');

        $q = [];
        if ($opdId > 0)
            $q['opd_id'] = $opdId;
        if ($tahunQS !== '')
            $q['tahun'] = $tahunQS;

        $redir = base_url('adminkab/monev') . (!empty($q) ? ('?' . http_build_query($q)) : '');
        return redirect()->to($redir)->with('success', 'Data capaian berhasil diperbarui.');
    }
}

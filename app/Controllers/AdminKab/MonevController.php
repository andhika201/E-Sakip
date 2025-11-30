<?php

namespace App\Controllers\AdminKab;

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

    /**
     * INDEX MONEV (ADMIN KAB)
     * - mode = opd  → tampilan per OPD (RENSTRA)
     * - mode = kab  → tampilan kabupaten (RPJMD)
     */
    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))
                ->with('error', 'Tidak berhak mengakses halaman Monev.');
        }

        // mode: opd (default) / kab
        $modeParam = strtolower((string) ($this->request->getGet('mode') ?? 'opd'));
        $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';

        // filter tahun
        $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all')
            ? null
            : (string) (int) $tahunParam;

        // filter OPD (hanya dipakai kalau mode = opd)
        $opdIdParam = $this->request->getGet('opd_id') ?? 'all';
        $filterOpdId = ($opdIdParam === 'all' || $opdIdParam === '' || $opdIdParam === null)
            ? null
            : (int) $opdIdParam;

        // Ambil data sesuai mode
        if ($mode === 'kab') {
            // Mode RPJMD: target_rencana yang punya rpjmd_target_id
            $monevList = $this->monev->getIndexDataAdminKabModeKab($tahun);
        } else {
            // Mode OPD (RENSTRA)
            $monevList = $this->monev->getIndexDataAdminKabModeOpd($tahun, $filterOpdId);
        }

        // Daftar tahun dari renstra_target
        $tahunList = $this->monev->getAvailableYears();

        // Daftar OPD untuk dropdown
        $opdList = $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        return view('adminKabupaten/monev/monev', [
            'role' => $role,
            'mode' => $mode,
            'tahun' => $tahun ?? 'all',
            'tahunList' => $tahunList,
            'opdId' => $filterOpdId === null ? 'all' : (string) $filterOpdId,
            'opdList' => $opdList,
            'monevList' => $monevList,
        ]);
    }

    /**
     * FORM TAMBAH MONEV – ADMIN KAB
     * URL contoh:
     *   /adminkab/monev/tambah?target_rencana_id=123&mode=opd&tahun=2025&opd_id=all
     */
    public function tambah()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Tidak berhak.');
        }

        $targetId = (int) $this->request->getGet('target_rencana_id');
        $modeParam = strtolower((string) ($this->request->getGet('mode') ?? 'opd'));
        $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';
        $tahunParam = (string) ($this->request->getGet('tahun') ?? 'all');
        $opdFilter = (string) ($this->request->getGet('opd_id') ?? 'all');

        if ($targetId <= 0) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Parameter tidak valid.');
        }

        // Ambil target_rencana + relasi RENSTRA
        $target = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id AS target_id,
                tr.opd_id,
                tr.rencana_aksi,
                tr.penanggung_jawab,
                tr.rpjmd_target_id,

                rt.id     AS renstra_target_id,
                rt.tahun  AS indikator_tahun,
                rt.target AS indikator_target,

                ris.indikator_sasaran,
                ris.satuan,

                rs.sasaran AS sasaran_renstra,
                rs.opd_id AS rs_opd_id
            ')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $targetId)
            ->get()
            ->getRowArray();

        if (!$target) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Target tidak ditemukan.');
        }

        // Mode kab: wajib punya rpjmd_target_id
        if ($mode === 'kab' && empty($target['rpjmd_target_id'])) {
            return redirect()->to(base_url('adminkab/monev?mode=kab'))
                ->with('error', 'Target ini tidak terhubung dengan RPJMD.');
        }

        // Tentukan OPD untuk monev:
        // prioritas rs.opd_id → tr.opd_id
        $opdIdFromTarget = 0;
        if (!empty($target['rs_opd_id'])) {
            $opdIdFromTarget = (int) $target['rs_opd_id'];
        } elseif (!empty($target['opd_id'])) {
            $opdIdFromTarget = (int) $target['opd_id'];
        }

        // Jika monev sudah ada, langsung redirect ke edit
        $existing = $this->monev->findByTargetAndOpd($targetId, $opdIdFromTarget);
        if ($existing) {
            $query = http_build_query([
                'mode' => $mode,
                'tahun' => $tahunParam,
                'opd_id' => $opdFilter,
            ]);

            return redirect()->to(base_url('adminkab/monev/edit/' . (int) $existing['id'] . '?' . $query))
                ->with('success', 'Data Monev sudah ada. Silakan edit.');
        }

        return view('adminKabupaten/monev/tambah_monev', [
            'target' => $target,
            'mode' => $mode,
            'tahun' => $tahunParam,
            'opdFilter' => $opdFilter,
        ]);
    }

    /**
     * SIMPAN / UPSERT MONEV – ADMIN KAB
     */
    public function save()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Tidak berhak.');
        }

        $rules = [
            'target_rencana_id' => 'required|integer',
            'capaian_triwulan_1' => 'permit_empty|string',
            'capaian_triwulan_2' => 'permit_empty|string',
            'capaian_triwulan_3' => 'permit_empty|string',
            'capaian_triwulan_4' => 'permit_empty|string',
            'total' => 'permit_empty|integer',
            'mode' => 'permit_empty|string',
            'tahun' => 'permit_empty|string',
            'opd_filter' => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetId = (int) $this->request->getPost('target_rencana_id');
        $mode = strtolower((string) ($this->request->getPost('mode') ?? 'opd'));
        $tahunParam = (string) ($this->request->getPost('tahun') ?? 'all');
        $opdFilter = (string) ($this->request->getPost('opd_filter') ?? 'all');

        // Ambil target untuk tahu OPD & tahun
        $rt = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id,
                tr.opd_id,
                tr.rpjmd_target_id,
                rt.tahun,
                rs.opd_id AS rs_opd_id
            ')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $targetId)
            ->get()
            ->getRowArray();

        if (!$rt) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Target tidak ditemukan.');
        }

        // Mode kab: pastikan target memang RPJMD
        if ($mode === 'kab' && empty($rt['rpjmd_target_id'])) {
            return redirect()->to(base_url('adminkab/monev?mode=kab'))
                ->with('error', 'Target ini bukan target RPJMD.');
        }

        // Tentukan OPD untuk monev
        $opdIdFromTarget = 0;
        if (!empty($rt['rs_opd_id'])) {
            $opdIdFromTarget = (int) $rt['rs_opd_id'];
        } elseif (!empty($rt['opd_id'])) {
            $opdIdFromTarget = (int) $rt['opd_id'];
        }

        $payload = [
            'capaian_triwulan_1' => (string) $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2' => (string) $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3' => (string) $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4' => (string) $this->request->getPost('capaian_triwulan_4'),
        ];

        if ($this->request->getPost('total') !== null && $this->request->getPost('total') !== '') {
            $payload['total'] = (int) $this->request->getPost('total');
        }

        // Upsert monev
        $this->monev->upsertForTarget($targetId, $opdIdFromTarget, $payload);

        $query = http_build_query([
            'mode' => $mode,
            'tahun' => $tahunParam,
            'opd_id' => $opdFilter,
        ]);

        return redirect()->to(base_url('adminkab/monev?' . $query))
            ->with('success', 'Data capaian berhasil disimpan.');
    }

    /**
     * FORM EDIT MONEV – ADMIN KAB
     */
    public function edit($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Tidak berhak.');
        }

        $modeParam = strtolower((string) ($this->request->getGet('mode') ?? 'opd'));
        $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';
        $tahunParam = (string) ($this->request->getGet('tahun') ?? 'all');
        $opdFilter = (string) ($this->request->getGet('opd_id') ?? 'all');

        $row = $this->db->table('monev AS m')
            ->select('
                m.*,
                tr.rencana_aksi,
                tr.penanggung_jawab,
                tr.rpjmd_target_id,
                rt.tahun  AS indikator_tahun,
                rt.target AS indikator_target,
                ris.indikator_sasaran,
                ris.satuan,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id AS rs_opd_id
            ')
            ->join('target_rencana AS tr', 'tr.id = m.target_rencana_id', 'left')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('m.id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // Mode kab: baris ini harus punya rpjmd_target_id
        if ($mode === 'kab' && empty($row['rpjmd_target_id'])) {
            return redirect()->to(base_url('adminkab/monev?mode=kab'))
                ->with('error', 'Data ini bukan target RPJMD.');
        }

        return view('adminKabupaten/monev/edit_monev', [
            'monev' => $row,
            'mode' => $mode,
            'tahun' => $tahunParam,
            'opdFilter' => $opdFilter,
        ]);
    }

    /**
     * UPDATE MONEV – ADMIN KAB
     */
    public function update($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Tidak berhak.');
        }

        $rules = [
            'capaian_triwulan_1' => 'permit_empty|string',
            'capaian_triwulan_2' => 'permit_empty|string',
            'capaian_triwulan_3' => 'permit_empty|string',
            'capaian_triwulan_4' => 'permit_empty|string',
            'total' => 'permit_empty|integer',
            'mode' => 'permit_empty|string',
            'tahun' => 'permit_empty|string',
            'opd_filter' => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $mode = strtolower((string) ($this->request->getPost('mode') ?? 'opd'));
        $tahunParam = (string) ($this->request->getPost('tahun') ?? 'all');
        $opdFilter = (string) ($this->request->getPost('opd_filter') ?? 'all');

        $row = $this->monev->find((int) $id);
        if (!$row) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Data tidak ditemukan.');
        }

        $payload = [
            'capaian_triwulan_1' => (string) $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2' => (string) $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3' => (string) $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4' => (string) $this->request->getPost('capaian_triwulan_4'),
        ];

        if ($this->request->getPost('total') !== null && $this->request->getPost('total') !== '') {
            $payload['total'] = (int) $this->request->getPost('total');
        } else {
            $payload['total'] = null;
        }

        $this->monev->update((int) $id, $payload);

        $query = http_build_query([
            'mode' => $mode,
            'tahun' => $tahunParam,
            'opd_id' => $opdFilter,
        ]);

        return redirect()->to(base_url('adminkab/monev?' . $query))
            ->with('success', 'Data capaian berhasil diperbarui.');
    }
}

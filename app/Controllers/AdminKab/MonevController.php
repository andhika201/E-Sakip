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
     * Pastikan role = admin_kab
     */
    protected function ensureAdminKab(): bool
    {
        $session = session();
        if ($session->get('role') !== 'admin_kab') {
            return false;
        }
        return true;
    }

    /* =========================================================
     *  INDEX MONEV (ADMIN KAB) – MODE OPD & KAB
     * =======================================================*/
    public function index()
    {
        if (!$this->ensureAdminKab()) {
            return redirect()->to(base_url('/'))
                ->with('error', 'Tidak berhak mengakses halaman Monev.');
        }

        // mode: opd (RENSTRA) / kab (RPJMD)
        $modeParam = strtolower((string) $this->request->getGet('mode'));
        $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';

        // tahun: null = semua tahun
        $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all')
            ? null
            : (string) (int) $tahunParam;

        // filter opd hanya dipakai di mode OPD
        $opdParam = $this->request->getGet('opd_id') ?? 'all';
        $filterOpdId = ($opdParam === '' || $opdParam === null || $opdParam === 'all')
            ? null
            : (int) $opdParam;

        if ($mode === 'kab') {
            // MODE KABUPATEN – RPJMD
            $monevList = $this->monev->getIndexDataAdminKabModeKab($tahun);
        } else {
            // MODE OPD – RENSTRA
            $monevList = $this->monev->getIndexDataAdminKabModeOpd($tahun, $filterOpdId);
        }

        $tahunList = $this->monev->getAvailableYears();
        $opdList = $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        return view('adminKabupaten/monev/monev', [
            'mode' => $mode,
            'tahun' => $tahun ?? 'all',
            'tahunList' => $tahunList,
            'opdId' => $filterOpdId === null ? 'all' : (string) $filterOpdId,
            'opdList' => $opdList,
            'monevList' => $monevList,
        ]);
    }

    /* =========================================================
     *  FORM TAMBAH – ADMIN KAB, 2 MODE
     * =======================================================*/
    public function tambah()
    {
        if (!$this->ensureAdminKab()) {
            return redirect()->to(base_url('/'))
                ->with('error', 'Tidak berhak.');
        }

        $modeParam = strtolower((string) $this->request->getGet('mode'));
        $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';

        $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all')
            ? 'all'
            : (string) (int) $tahunParam;

        $opdParam = $this->request->getGet('opd_id') ?? 'all';
        $opdFilter = ($opdParam === '' || $opdParam === null || $opdParam === 'all')
            ? 'all'
            : (string) (int) $opdParam;

        $targetId = (int) $this->request->getGet('target_rencana_id');
        if ($targetId <= 0) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Parameter target tidak valid.');
        }

        if ($mode === 'kab') {
            // ===================== RPJMD =====================
            $target = $this->db->table('target_rencana AS tr')
                ->select('
                    tr.id AS target_id,
                    tr.opd_id,
                    tr.rencana_aksi,
                    tr.penanggung_jawab,
                    rpt.tahun AS indikator_tahun,
                    rpt.target_tahunan AS indikator_target,
                    rpis.indikator_sasaran,
                    rpis.satuan,
                    rps.sasaran_rpjmd AS sasaran_renstra
                ')
                ->join('rpjmd_target AS rpt', 'rpt.id = tr.rpjmd_target_id', 'left')
                ->join('rpjmd_indikator_sasaran AS rpis', 'rpis.id = rpt.indikator_sasaran_id', 'left')
                ->join('rpjmd_sasaran AS rps', 'rps.id = rpis.sasaran_id', 'left')
                ->where('tr.id', $targetId)
                ->get()
                ->getRowArray();

            if (!$target || empty($target['indikator_tahun'])) {
                return redirect()->to(base_url('adminkab/monev'))
                    ->with('error', 'Target RPJMD tidak ditemukan atau belum lengkap.');
            }

            // Cek apakah monev utk target ini sudah ada (abaikan opd_id)
            $existing = $this->db->table('monev')
                ->where('target_rencana_id', $targetId)
                ->get()
                ->getRowArray();
        } else {
            // ===================== RENSTRA =====================
            $target = $this->db->table('target_rencana AS tr')
                ->select('
                    tr.id AS target_id,
                    tr.opd_id,
                    tr.rencana_aksi,
                    tr.penanggung_jawab,
                    rt.tahun AS indikator_tahun,
                    rt.target AS indikator_target,
                    ris.indikator_sasaran,
                    ris.satuan,
                    rs.sasaran AS sasaran_renstra
                ')
                ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
                ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
                ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
                ->where('tr.id', $targetId)
                ->get()
                ->getRowArray();

            if (!$target || empty($target['indikator_tahun'])) {
                return redirect()->to(base_url('adminkab/monev'))
                    ->with('error', 'Target RENSTRA tidak ditemukan atau belum lengkap.');
            }

            // Per OPD
            $opdIdTarget = (int) ($target['opd_id'] ?? 0);
            $existing = $this->monev->findByTargetAndOpd($targetId, $opdIdTarget);
        }

        if ($existing) {
            $qs = http_build_query([
                'mode' => $mode,
                'tahun' => $tahun,
                'opd_id' => $opdFilter,
            ]);

            return redirect()->to(base_url('adminkab/monev/edit/' . (int) $existing['id'] . '?' . $qs))
                ->with('success', 'Data monev sudah ada, silakan edit.');
        }

        return view('adminKabupaten/monev/tambah_monev', [
            'mode' => $mode,
            'tahun' => $tahun,
            'opdFilter' => $opdFilter,
            'target' => $target,
        ]);
    }

    /* =========================================================
     *  SAVE – ADMIN KAB, 2 MODE
     * =======================================================*/
    public function save()
    {
        if (!$this->ensureAdminKab()) {
            return redirect()->to(base_url('/'))
                ->with('error', 'Tidak berhak.');
        }

        $mode = strtolower((string) $this->request->getPost('mode')) ?: 'opd';
        $tahun = (string) ($this->request->getPost('tahun') ?? 'all');
        $opdFilter = (string) ($this->request->getPost('opd_filter') ?? 'all');

        $rules = [
            'target_rencana_id' => 'required|integer',
            'capaian_triwulan_1' => 'permit_empty|numeric',
            'capaian_triwulan_2' => 'permit_empty|numeric',
            'capaian_triwulan_3' => 'permit_empty|numeric',
            'capaian_triwulan_4' => 'permit_empty|numeric',
            'total' => 'permit_empty|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetId = (int) $this->request->getPost('target_rencana_id');

        // data target rencana (selalu dibutuhkan)
        $target = $this->db->table('target_rencana')
            ->select('id, opd_id')
            ->where('id', $targetId)
            ->get()
            ->getRowArray();

        if (!$target) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Target tidak ditemukan.');
        }

        $payload = [
            'capaian_triwulan_1' => ($this->request->getPost('capaian_triwulan_1') !== '')
                ? $this->request->getPost('capaian_triwulan_1')
                : null,
            'capaian_triwulan_2' => ($this->request->getPost('capaian_triwulan_2') !== '')
                ? $this->request->getPost('capaian_triwulan_2')
                : null,
            'capaian_triwulan_3' => ($this->request->getPost('capaian_triwulan_3') !== '')
                ? $this->request->getPost('capaian_triwulan_3')
                : null,
            'capaian_triwulan_4' => ($this->request->getPost('capaian_triwulan_4') !== '')
                ? $this->request->getPost('capaian_triwulan_4')
                : null,
        ];

        if ($this->request->getPost('total') !== '' && $this->request->getPost('total') !== null) {
            $payload['total'] = (float) $this->request->getPost('total');
        }

        if ($mode === 'kab') {
            // ===================== SAVE MODE KAB (RPJMD) =====================
            // opd_id boleh NULL → simpan monev per target_rencana saja
            $existing = $this->db->table('monev')
                ->where('target_rencana_id', $targetId)
                ->get()
                ->getRowArray();

            $data = array_merge($payload, [
                'target_rencana_id' => $targetId,
                'opd_id' => null,
            ]);

            if ($existing) {
                $this->monev->update($existing['id'], $data);
            } else {
                $this->monev->insert($data);
            }
        } else {
            // ===================== SAVE MODE OPD (RENSTRA) =====================
            $opdId = (int) ($target['opd_id'] ?? 0);

            // Validasi FK OPD (supaya tidak error #1452)
            $opd = $this->db->table('opd')
                ->select('id')
                ->where('id', $opdId)
                ->get()
                ->getRowArray();

            if (!$opd) {
                return redirect()->to(base_url('adminkab/monev'))
                    ->with('error', 'OPD pada target tidak valid. Periksa kolom opd_id di target rencana.');
            }

            // upsert per (target_rencana_id, opd_id)
            $this->monev->upsertForTarget($targetId, $opdId, $payload);
        }

        return redirect()->to(base_url('adminkab/monev?' . http_build_query([
            'mode' => $mode,
            'tahun' => $tahun,
            'opd_id' => $opdFilter,
        ])))->with('success', 'Data capaian berhasil disimpan.');
    }

    /* =========================================================
     *  FORM EDIT – ADMIN KAB, SUPPORT 2 MODE
     * =======================================================*/
    public function edit($id)
    {
        if (!$this->ensureAdminKab()) {
            return redirect()->to(base_url('/'))
                ->with('error', 'Tidak berhak.');
        }

        $modeParam = strtolower((string) $this->request->getGet('mode'));
        $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';

        $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all')
            ? 'all'
            : (string) (int) $tahunParam;

        $opdParam = $this->request->getGet('opd_id') ?? 'all';
        $opdFilter = ($opdParam === '' || $opdParam === null || $opdParam === 'all')
            ? 'all'
            : (string) (int) $opdParam;

        $id = (int) $id;

        if ($mode === 'kab') {
            // Edit RPJMD
            $row = $this->db->table('monev AS m')
                ->select('
                    m.*,
                    tr.rencana_aksi,
                    tr.penanggung_jawab,
                    rpt.tahun AS indikator_tahun,
                    rpt.target_tahunan AS indikator_target,
                    rpis.indikator_sasaran,
                    rpis.satuan,
                    rps.sasaran_rpjmd AS sasaran_renstra
                ')
                ->join('target_rencana AS tr', 'tr.id = m.target_rencana_id', 'left')
                ->join('rpjmd_target AS rpt', 'rpt.id = tr.rpjmd_target_id', 'left')
                ->join('rpjmd_indikator_sasaran AS rpis', 'rpis.id = rpt.indikator_sasaran_id', 'left')
                ->join('rpjmd_sasaran AS rps', 'rps.id = rpis.sasaran_id', 'left')
                ->where('m.id', $id)
                ->get()
                ->getRowArray();
        } else {
            // Edit RENSTRA
            $row = $this->db->table('monev AS m')
                ->select('
                    m.*,
                    tr.rencana_aksi,
                    tr.penanggung_jawab,
                    rt.tahun AS indikator_tahun,
                    rt.target AS indikator_target,
                    ris.indikator_sasaran,
                    ris.satuan,
                    rs.sasaran AS sasaran_renstra
                ')
                ->join('target_rencana AS tr', 'tr.id = m.target_rencana_id', 'left')
                ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
                ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
                ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
                ->where('m.id', $id)
                ->get()
                ->getRowArray();
        }

        if (!$row) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Data monev tidak ditemukan.');
        }

        return view('adminKabupaten/monev/edit_monev', [
            'mode' => $mode,
            'tahun' => $tahun,
            'opdFilter' => $opdFilter,
            'monev' => $row,
        ]);
    }

    /* =========================================================
     *  UPDATE – ADMIN KAB
     * =======================================================*/
    public function update($id)
    {
        if (!$this->ensureAdminKab()) {
            return redirect()->to(base_url('/'))
                ->with('error', 'Tidak berhak.');
        }

        $mode = strtolower((string) $this->request->getPost('mode')) ?: 'opd';
        $tahun = (string) ($this->request->getPost('tahun') ?? 'all');
        $opdFilter = (string) ($this->request->getPost('opd_filter') ?? 'all');

        $id = (int) $id;

        $row = $this->monev->find($id);
        if (!$row) {
            return redirect()->to(base_url('adminkab/monev'))
                ->with('error', 'Data monev tidak ditemukan.');
        }

        $rules = [
            'capaian_triwulan_1' => 'permit_empty|numeric',
            'capaian_triwulan_2' => 'permit_empty|numeric',
            'capaian_triwulan_3' => 'permit_empty|numeric',
            'capaian_triwulan_4' => 'permit_empty|numeric',
            'total' => 'permit_empty|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload = [
            'capaian_triwulan_1' => ($this->request->getPost('capaian_triwulan_1') !== '')
                ? $this->request->getPost('capaian_triwulan_1')
                : null,
            'capaian_triwulan_2' => ($this->request->getPost('capaian_triwulan_2') !== '')
                ? $this->request->getPost('capaian_triwulan_2')
                : null,
            'capaian_triwulan_3' => ($this->request->getPost('capaian_triwulan_3') !== '')
                ? $this->request->getPost('capaian_triwulan_3')
                : null,
            'capaian_triwulan_4' => ($this->request->getPost('capaian_triwulan_4') !== '')
                ? $this->request->getPost('capaian_triwulan_4')
                : null,
            'total' => ($this->request->getPost('total') !== '')
                ? (float) $this->request->getPost('total')
                : null,
        ];

        $this->monev->update($id, $payload);

        return redirect()->to(base_url('adminkab/monev?' . http_build_query([
            'mode' => $mode,
            'tahun' => $tahun,
            'opd_id' => $opdFilter,
        ])))->with('success', 'Data capaian berhasil diperbarui.');
    }
}

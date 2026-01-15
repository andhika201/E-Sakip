<?php

namespace App\Controllers\AdminOpd;

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
    private function xssRule(): string
    {
        // blok: <script>, javascript:, data:text/html, onerror=, <?php, <?
        return 'regex_match[/^\d+(,\d+)?$/]';
    }
    /**
     * INDEX MONEV
     * - Jika role = admin_opd  → tampilkan halaman Monev OPD (adminOpd/monev/monev)
     * - Jika role = admin_kab  → tampilkan halaman Monev Kab (adminKabupaten/monev/index)
     */
    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        // ======================
        // MODE ADMIN OPD
        // ======================
        if ($role === 'admin_opd') {
            if ($opdId <= 0) {
                return redirect()->to(base_url('/'))
                    ->with('error', 'OPD tidak terdeteksi.');
            }

            $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
            $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all')
                ? null
                : (string) (int) $tahunParam;

            $monevList = $this->monev->getIndexDataAdminOpd((int) $opdId, $tahun);
            $tahunList = $this->monev->getAvailableYears();

            return view('adminOpd/monev/monev', [
                'monevList' => $monevList,
                'tahun' => $tahun ?? 'all',
                'tahunList' => $tahunList,
            ]);
        }

        // ======================
        // MODE ADMIN KABUPATEN
        // ======================
        if ($role === 'admin_kab') {
            // mode: 'opd' (default) atau 'kab'
            $modeParam = strtolower((string) ($this->request->getGet('mode') ?? 'opd'));
            $mode = in_array($modeParam, ['opd', 'kab'], true) ? $modeParam : 'opd';

            // filter tahun
            $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
            $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all')
                ? null
                : (string) (int) $tahunParam;

            // filter opd_id (hanya dipakai jika mode = opd)
            $opdIdParam = $this->request->getGet('opd_id') ?? 'all';
            $filterOpdId = ($opdIdParam === 'all' || $opdIdParam === '' || $opdIdParam === null)
                ? null
                : (int) $opdIdParam;

            if ($mode === 'kab') {
                // MODE KABUPATEN:
                // Data monev dari target_rencana yang punya rpjmd_target_id (kabupaten),
                // tanpa filter opd_id.
                $monevList = $this->monev->getIndexDataAdminKabModeKab($tahun);
            } else {
                // MODE OPD:
                // Data monev berdasarkan target_rencana semua OPD,
                // bisa difilter per opd_id & tahun.
                $monevList = $this->monev->getIndexDataAdminKabModeOpd($tahun, $filterOpdId);
            }

            $tahunList = $this->monev->getAvailableYears();

            // daftar OPD untuk dropdown (sesuaikan nama tabel/kolom jika beda)
            $opdList = $this->db->table('opd')
                ->select('id, nama_opd')
                ->orderBy('nama_opd', 'ASC')
                ->get()
                ->getResultArray();

            return view('adminKabupaten/monev/index', [
                'mode' => $mode,
                'tahun' => $tahun ?? 'all',
                'tahunList' => $tahunList,
                'opdId' => $opdIdParam,  // untuk set selected di dropdown
                'opdList' => $opdList,
                'monevList' => $monevList,
            ]);
        }

        // Role lain: tolak
        return redirect()->to(base_url('/'))->with('error', 'Tidak berhak mengakses halaman Monev.');
    }
    private function normalizeNumber(?string $val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        return (float) str_replace(',', '.', $val);
    }

    /**
     * FORM TAMBAH MONEV
     * Hanya untuk admin_opd
     */
    public function tambah()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Tidak berhak.');
        }

        $targetId = (int) $this->request->getGet('target_rencana_id');
        if ($targetId <= 0) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Parameter tidak valid.');
        }

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
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Target tidak ditemukan.');
        }
        if ((int) $target['rs_opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Target bukan milik OPD Anda.');
        }

        $existing = $this->monev->findByTargetAndOpd($targetId, $opdId);
        if ($existing) {
            return redirect()->to(base_url('adminopd/monev/edit/' . (int) $existing['id']))
                ->with('success', 'Data sudah ada. Silakan edit.');
        }

        return view('adminOpd/monev/tambah_monev', [
            'target' => $target,
        ]);
    }

    /**
     * SIMPAN (UPSERT) DATA MONEV - ADMIN OPD
     */
    public function save()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Tidak berhak.');
        }

        $rx = $this->xssRule();

        $rules = [
            'target_rencana_id' => 'required|integer',
            'capaian_triwulan_1' => 'permit_empty|string|max_length[5000]|' . $rx,
            'capaian_triwulan_2' => 'permit_empty|string|max_length[5000]|' . $rx,
            'capaian_triwulan_3' => 'permit_empty|string|max_length[5000]|' . $rx,
            'capaian_triwulan_4' => 'permit_empty|string|max_length[5000]|' . $rx,
            'total' => 'permit_empty|string|max_length[5000]|' . $rx,
        ];
        $messages = [
            'capaian_triwulan_1' => ['regex_match' => 'Capaian Triwulan 1 harus berupa angka (gunakan koma untuk desimal).'],
            'capaian_triwulan_2' => ['regex_match' => 'Capaian Triwulan 2 harus berupa angka (gunakan koma untuk desimal).'],
            'capaian_triwulan_3' => ['regex_match' => 'Capaian Triwulan 3 harus berupa angka (gunakan koma untuk desimal).'],
            'capaian_triwulan_4' => ['regex_match' => 'Capaian Triwulan 4 harus berupa angka (gunakan koma untuk desimal).'],
            'total' => ['regex_match' => 'Total harus berupa angka (gunakan koma untuk desimal).'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetId = (int) $this->request->getPost('target_rencana_id');

        $rt = $this->db->table('target_rencana AS tr')
            ->select('tr.id, rs.opd_id, rt.tahun')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $targetId)
            ->get()->getRowArray();

        if (!$rt || (int) $rt['opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Target/OPD tidak cocok.');
        }

        $payload = [
            'capaian_triwulan_1' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_1')),
            'capaian_triwulan_2' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_2')),
            'capaian_triwulan_3' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_3')),
            'capaian_triwulan_4' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_4')),
        ];

        $payload['total'] = $this->normalizeNumber(
            $this->request->getPost('total')
        );

        $this->monev->upsertForTarget($targetId, $opdId, $payload);

        return redirect()->to(base_url('adminopd/monev?tahun=' . urlencode($rt['tahun'])))
            ->with('success', 'Data capaian berhasil disimpan.');
    }

    /**
     * FORM EDIT MONEV - ADMIN OPD
     */
    public function edit($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Tidak berhak.');
        }

        $row = $this->db->table('monev AS m')
            ->select('
                m.*,
                tr.rencana_aksi, tr.penanggung_jawab,
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
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Data tidak ditemukan.');
        }
        if ((int) $row['opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Data bukan milik OPD Anda.');
        }

        return view('adminOpd/monev/edit_monev', [
            'monev' => $row,
        ]);
    }

    /**
     * UPDATE MONEV - ADMIN OPD
     */
    public function update($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminopd/monev'))->with('error', 'Tidak berhak.');
        }

        $row = $this->monev->find((int) $id);
        if (!$row || (int) $row['opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminopd/monev'))
                ->with('error', 'Data tidak ditemukan / bukan milik OPD Anda.');
        }

        $rx = $this->xssRule();

        $rules = [
            'capaian_triwulan_1' => 'permit_empty|string|max_length[5000]|' . $rx,
            'capaian_triwulan_2' => 'permit_empty|string|max_length[5000]|' . $rx,
            'capaian_triwulan_3' => 'permit_empty|string|max_length[5000]|' . $rx,
            'capaian_triwulan_4' => 'permit_empty|string|max_length[5000]|' . $rx,
            'total' => 'permit_empty|string|max_length[5000]|' . $rx
        ];
        $messages = [
            'capaian_triwulan_1' => ['regex_match' => 'Capaian Triwulan 1 harus berupa angka (gunakan koma untuk desimal).'],
            'capaian_triwulan_2' => ['regex_match' => 'Capaian Triwulan 2 harus berupa angka (gunakan koma untuk desimal).'],
            'capaian_triwulan_3' => ['regex_match' => 'Capaian Triwulan 3 harus berupa angka (gunakan koma untuk desimal).'],
            'capaian_triwulan_4' => ['regex_match' => 'Capaian Triwulan 4 harus berupa angka (gunakan koma untuk desimal).'],
            'total' => ['regex_match' => 'Total harus berupa angka (gunakan koma untuk desimal).'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $payload = [
            'capaian_triwulan_1' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_1')),
            'capaian_triwulan_2' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_2')),
            'capaian_triwulan_3' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_3')),
            'capaian_triwulan_4' => $this->normalizeNumber($this->request->getPost('capaian_triwulan_4')),
        ];


        $payload['total'] = $this->normalizeNumber(
            $this->request->getPost('total')
        );


        $this->monev->update((int) $id, $payload);

        return redirect()->to(base_url('adminopd/monev'))
            ->with('success', 'Data capaian berhasil diperbarui.');
    }


}

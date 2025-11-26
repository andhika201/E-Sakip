<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\TargetModel;
use Config\Database;

class TargetController extends BaseController
{
    protected TargetModel $targets;
    protected $db;

    public function __construct()
    {
        $this->targets = new TargetModel();
        $this->db = Database::connect();
    }

    /**
     * INDEX: list target rencana
     * - admin_opd: data RENSTRA untuk OPD sendiri
     * - admin_kab:
     *    - mode=opd       -> RENSTRA per OPD (filter OPD)
     *    - mode=kabupaten -> RPJMD (rpjmd_target_id)
     */
    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        $tahun = $this->request->getGet('tahun');

        // kalau belum ada di query string => pakai tahun sekarang
        if ($tahun === null || $tahun === '') {
            $tahun = date('Y');
        }

        $mode = $this->request->getGet('mode') ?? 'opd';


        $opdList = [];
        $opdFilter = null;
        $grouped = [];

        // ===================== ADMIN KABUPATEN =====================
        if ($role === 'admin_kab') {

            // Ambil daftar OPD untuk filter (mode opd)
            $opdList = $this->db->table('opd')
                ->select('id, nama_opd')
                ->orderBy('nama_opd', 'ASC')
                ->get()->getResultArray();

            if ($mode === 'kabupaten') {
                // MODE KABUPATEN: pakai data RPJMD (rpjmd_target_id)
                $raw = $this->targets->getTargetListByRpjmdKabupaten($tahun);
            } else {
                // MODE OPD: pakai data RENSTRA + target_rencana
                $opdFilter = (int) ($this->request->getGet('opd_id') ?? 0);
                if ($opdFilter <= 0) {
                    $opdFilter = null;
                }

                $raw = $this->targets->getTargetListByRenstraAdminKab($tahun, $opdFilter);
            }

            // Group per sasaran
            foreach ($raw as $row) {
                $sasaran = $row['sasaran_renstra'] ?? '-';
                $grouped[$sasaran][] = $row;
            }

            return view('adminOpd/target/index', [
                'grouped' => $grouped,
                'tahun' => $tahun,
                'tahunList' => $this->targets->getAvailableYears(),
                'role' => $role,
                'mode' => $mode,
                'opdList' => $opdList,
                'opdFilter' => $opdFilter,
            ]);
        }

        // ===================== ADMIN OPD ===========================
        $myOpdId = (int) ($session->get('opd_id') ?? 0);
        $raw = $this->targets->getTargetListByRenstra($tahun, $myOpdId);

        foreach ($raw as $row) {
            $sasaran = $row['sasaran_renstra'] ?? '-';
            $grouped[$sasaran][] = $row;
        }

        return view('adminOpd/target/target', [
            'grouped' => $grouped,
            'tahun' => $tahun,
            'tahunList' => $this->targets->getAvailableYears(),
            'role' => $role,
            'mode' => 'opd',
            'opdList' => [],
            'opdFilter' => null,
        ]);
    }

    /**
     * Form Tambah target RENSTRA (param ?rt=renstra_target_id)
     */
    public function tambah()
    {
        $rtId = (int) $this->request->getGet('rt'); // renstra_target_id
        if ($rtId <= 0) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Parameter tidak valid.');
        }

        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $sessOpd = (int) ($session->get('opd_id') ?? 0);
        $passOpd = (int) ($this->request->getGet('opd_id') ?? 0); // untuk admin_kab

        // Ambil info renstra_target + indikator + sasaran + opd pemilik indikator
        $rt = $this->db->table('renstra_target rt')
            ->select('
                rt.id AS renstra_target_id, rt.tahun, rt.target,
                ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan,
                rs.id AS renstra_sasaran_id, rs.sasaran AS sasaran_renstra, rs.opd_id
            ')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $rtId)
            ->get()->getRowArray();

        if (!$rt) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Target Renstra tidak ditemukan.');
        }

        // Tentukan OPD yang dipakai untuk simpan
        $opdToUse = $sessOpd;
        if ($role === 'admin_kab' && !$opdToUse) {
            $opdToUse = $passOpd;
        }

        if ($opdToUse <= 0) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'OPD belum dipilih.');
        }

        // Pastikan indikator memang milik OPD tersebut
        if ((int) $rt['opd_id'] !== (int) $opdToUse) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Indikator bukan milik OPD yang dipilih.');
        }

        // Anti duplikat
        $existing = $this->targets->existsFor($opdToUse, $rtId);
        if ($existing) {
            return redirect()->to(base_url('adminopd/target/edit/' . (int) $existing['id']))
                ->with('success', 'Data sudah ada. Silakan edit.');
        }

        // (Opsional) list RPJMD target untuk admin_kab jika ingin di-link
        $rpjmdTargets = $this->db->table('rpjmd_target')
            ->select('id, tahun, target_tahunan')
            ->orderBy('tahun', 'ASC')
            ->get()->getResultArray();

        return view('adminOpd/target/tambah_target', [
            'role' => $role,
            'opdIdToUse' => $opdToUse,
            'rt' => $rt,
            'rpjmdTargets' => $rpjmdTargets,
        ]);
    }

    /**
     * Simpan hasil Tambah
     */
    public function save()
    {
        $rules = [
            'renstra_target_id' => 'required|integer',
            'rencana_aksi' => 'required|string',
            'capaian' => 'permit_empty|string',
            'target_triwulan_1' => 'permit_empty|string',
            'target_triwulan_2' => 'permit_empty|string',
            'target_triwulan_3' => 'permit_empty|string',
            'target_triwulan_4' => 'permit_empty|string',
            'penanggung_jawab' => 'permit_empty|string',
            'rpjmd_target_id' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        // admin_kab tanpa opd di sesi → ambil dari form
        if ($role === 'admin_kab' && $opdId <= 0) {
            $opdId = (int) $this->request->getPost('opd_id');
        }

        $rtId = (int) $this->request->getPost('renstra_target_id');
        $rpjmdTargetId = (int) ($this->request->getPost('rpjmd_target_id') ?? 0);

        if ($opdId <= 0 || $rtId <= 0) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'OPD/Target Renstra tidak valid.');
        }

        // Validasi RT & kepemilikan OPD
        $rt = $this->db->table('renstra_target rt')
            ->select('rt.id, rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $rtId)
            ->get()->getRowArray();

        if (!$rt || (int) $rt['opd_id'] !== (int) $opdId) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Target Renstra/OPD tidak cocok.');
        }

        // Anti duplikat RENSTRA
        if ($this->targets->existsFor($opdId, $rtId)) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Data sudah ada untuk OPD & tahun ini.');
        }

        $data = [
            'opd_id' => $opdId,
            'renstra_target_id' => $rtId,
            'rencana_aksi' => $this->request->getPost('rencana_aksi'),
            'capaian' => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => $this->request->getPost('penanggung_jawab'),
        ];

        // Jika admin_kab mengisi rpjmd_target_id → simpan juga
        if ($rpjmdTargetId > 0) {
            $data['rpjmd_target_id'] = $rpjmdTargetId;
        }

        $this->targets->insert($data);

        return redirect()->to(base_url('adminopd/target'))
            ->with('success', 'Target rencana berhasil ditambahkan.');
    }

    /**
     * Form Edit target_rencana
     */
    public function edit($id)
    {
        $id = (int) $id;

        // Ambil target_rencana + info indikator
        $detail = $this->targets->getTargetDetail($id);

        if (!$detail) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        // Validasi akses OPD (selain admin_kab)
        if ($role !== 'admin_kab' && $myOpdId > 0) {
            if ((int) $detail['opd_id'] !== $myOpdId) {
                return redirect()->to(base_url('adminopd/target'))
                    ->with('error', 'Anda tidak berhak mengubah data ini.');
            }
        }

        // List RPJMD target (opsional untuk admin_kab)
        $rpjmdTargets = $this->db->table('rpjmd_target')
            ->select('id, tahun, target_tahunan')
            ->orderBy('tahun', 'ASC')
            ->get()->getResultArray();

        return view('adminOpd/target/edit_target', [
            'detail' => $detail,
            'role' => $role,
            'rpjmdTargets' => $rpjmdTargets,
        ]);
    }

    /**
     * Update target_rencana
     */
    public function update($id)
    {
        $id = (int) $id;

        $row = $this->db->table('target_rencana')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_kab' && $myOpdId > 0) {
            if ((int) $row['opd_id'] !== $myOpdId) {
                return redirect()->to(base_url('adminopd/target'))
                    ->with('error', 'Anda tidak berhak mengubah data ini.');
            }
        }

        $data = [
            'rencana_aksi' => $this->request->getPost('rencana_aksi'),
            'capaian' => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => $this->request->getPost('penanggung_jawab'),
        ];

        // Jika admin_kab boleh ubah link RPJMD
        if ($role === 'admin_kab') {
            $rpjmdTargetId = (int) ($this->request->getPost('rpjmd_target_id') ?? 0);
            $data['rpjmd_target_id'] = $rpjmdTargetId > 0 ? $rpjmdTargetId : null;
        }

        $this->targets->update($id, $data);

        return redirect()->to(base_url('adminopd/target'))
            ->with('success', 'Data berhasil diperbarui.');
    }
}

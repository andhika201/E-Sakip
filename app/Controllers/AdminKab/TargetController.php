<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\Opd\TargetModel;
use Config\Database;

class TargetController extends BaseController
{
    /**
     * @var TargetModel
     */
    protected $targets;

    /**
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;

    public function __construct()
    {
        $this->targets = new TargetModel();
        $this->db = Database::connect();
    }

    /* =========================================================
     *  INDEX: LIST TARGET & RENCANA (KHUSUS ADMIN_KAB)
     * =======================================================*/
    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        $tahun = $this->request->getGet('tahun');
        if ($tahun === null || $tahun === '') {
            $tahun = date('Y');
        }

        $mode = $this->request->getGet('mode') ?? 'opd';

        $opdList = [];
        $opdFilter = null;
        $grouped = [];

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))->with('error', 'Tidak punya akses.');
        }

        // Ambil daftar OPD
        $opdList = $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        if ($mode === 'kabupaten') {
            // MODE KABUPATEN: RPJMD
            $raw = $this->targets->getTargetListByRpjmdKabupaten($tahun);

        } else {
            // MODE OPD: RENSTRA
            // 👉 ambil nilai mentah dulu
            $opdIdRaw = $this->request->getGet('opd_id'); // bisa null / "" / "3"

            if ($opdIdRaw === null || $opdIdRaw === '') {
                // "Semua OPD"
                $opdFilter = null;
            } else {
                // OPD tertentu
                $opdFilter = (int) $opdIdRaw;
            }

            $raw = $this->targets->getTargetListByRenstraAdminKab($tahun, $opdFilter);
        }

        // ====== GROUPING DATA UNTUK VIEW ======
        $grouped = [];

        if ($mode === 'opd' && $opdFilter === null) {
            // SEMUA OPD: group = OPD -> Sasaran -> rows
            foreach ($raw as $row) {
                $opdName = $row['nama_opd'] ?? 'Tanpa OPD';
                $sasaran = $row['sasaran_renstra'] ?? '-';

                $grouped[$opdName][$sasaran][] = $row;
            }
        } else {
            // OPD tertentu atau mode kabupaten: group per sasaran saja (lama)
            foreach ($raw as $row) {
                $sasaran = $row['sasaran_renstra'] ?? '-';
                $grouped[$sasaran][] = $row;
            }
        }

        return view('adminKabupaten/target/target', [
            'grouped' => $grouped,
            'tahun' => $tahun,
            'tahunList' => $this->targets->getAvailableYears(),
            'role' => $role,
            'mode' => $mode,
            'opdList' => $opdList,
            'opdFilter' => $opdFilter,
            'showOpdCol' => ($mode === 'opd' && $opdFilter === null), // kita kirim ke view
        ]);
    }

    /* =========================================================
     *  FORM TAMBAH TARGET – ADMIN_KAB
     *  RENSTRA : /adminkab/target/tambah?rt={renstra_target_id}&opd_id={id_opd}
     *  RPJMD   : /adminkab/target/tambah?rpj={rpjmd_target_id}
     * =======================================================*/
    public function tambah()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))
                ->with('error', 'Anda tidak memiliki akses.');
        }

        // parameter dari URL
        $rtId = (int) $this->request->getGet('rt');      // renstra_target_id (mode OPD)
        $opdId = (int) $this->request->getGet('opd_id');  // opd_id (mode OPD)
        $rpjId = (int) $this->request->getGet('rpj');     // rpjmd_target_id (mode Kabupaten)

        /* ===================== MODE KABUPATEN (RPJMD) ===================== */
        if ($rpjId > 0 && $rtId <= 0 && $opdId <= 0) {

            $rpj = $this->db->table('rpjmd_target rpj')
                ->select("
                    rpj.id              AS rpjmd_target_id,
                    rpj.tahun           AS tahun,
                    rpj.target_tahunan,
                    ri.indikator_sasaran,
                    ri.satuan,
                    rs.sasaran_rpjmd    AS sasaran_renstra
                ")
                ->join('rpjmd_indikator_sasaran ri', 'ri.id = rpj.indikator_sasaran_id', 'left')
                ->join('rpjmd_sasaran rs', 'rs.id = ri.sasaran_id', 'left')
                ->where('rpj.id', $rpjId)
                ->get()
                ->getRowArray();

            if (!$rpj) {
                return redirect()->to(base_url('adminkab/target?mode=kabupaten'))
                    ->with('error', 'Target RPJMD tidak ditemukan.');
            }

            // Cek apakah sudah ada target_rencana untuk RPJMD ini
            $existing = $this->targets
                ->where('rpjmd_target_id', $rpjId)
                ->first();

            if ($existing) {
                return redirect()->to(base_url('adminkab/target/edit/' . (int) $existing['id']))
                    ->with('success', 'Target RPJMD sudah ada. Silakan edit.');
            }

            return view('adminKabupaten/target/tambah_target', [
                'mode' => 'kabupaten',
                'role' => $role,
                'rpj' => $rpj,
                'rt' => null,
                'opdIdToUse' => null,
                'rpjmdTargets' => [],   // tidak dipakai di mode ini
            ]);
        }

        /* ===================== MODE OPD (RENSTRA) ===================== */

        if ($rtId <= 0 || $opdId <= 0) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Parameter tidak valid.');
        }

        // Ambil info RENSTRA
        $rt = $this->db->table('renstra_target rt')
            ->select("
                rt.id    AS renstra_target_id,
                rt.tahun AS tahun,
                rt.target,
                ris.indikator_sasaran,
                ris.satuan,
                rs.id    AS renstra_sasaran_id,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id
            ")
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $rtId)
            ->get()
            ->getRowArray();

        if (!$rt) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Target Renstra tidak ditemukan.');
        }

        // Pastikan indikator memang milik OPD yang dipilih
        if ((int) $rt['opd_id'] !== (int) $opdId) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Indikator bukan milik OPD yang dipilih.');
        }

        // Cek duplikat kombinasi OPD + RENSTRA_TARGET
        $existing = $this->targets->existsFor($opdId, $rtId);
        if ($existing) {
            return redirect()->to(base_url('adminkab/target/edit/' . (int) $existing['id']))
                ->with('success', 'Data sudah ada. Silakan edit.');
        }

        // List target RPJMD (opsional linking)
        $rpjmdTargets = [];
        if ($this->db->tableExists('rpjmd_target')) {
            $rpjmdTargets = $this->db->table('rpjmd_target')
                ->select('id, tahun, target_tahunan')
                ->orderBy('tahun', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('adminKabupaten/target/tambah_target', [
            'mode' => 'opd',
            'role' => $role,
            'rpj' => null,
            'rt' => $rt,
            'opdIdToUse' => $opdId,
            'rpjmdTargets' => $rpjmdTargets,
        ]);
    }

    /* =========================================================
     *  SIMPAN HASIL TAMBAH – ADMIN_KAB
     * =======================================================*/
    public function save()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $mode = $this->request->getPost('mode') ?? 'opd';

        /* ===================== VALIDASI & SIMPAN MODE KABUPATEN ===================== */
        if ($mode === 'kabupaten') {

            $rules = [
                'rpjmd_target_id' => 'required|integer',
                'rencana_aksi' => 'required|string',
                'capaian' => 'permit_empty|string',
                'target_triwulan_1' => 'permit_empty|string',
                'target_triwulan_2' => 'permit_empty|string',
                'target_triwulan_3' => 'permit_empty|string',
                'target_triwulan_4' => 'permit_empty|string',
                'penanggung_jawab' => 'permit_empty|string',
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()
                    ->with('error', implode(' ', $this->validator->getErrors()));
            }

            $rpjmdTargetId = (int) $this->request->getPost('rpjmd_target_id');

            if ($rpjmdTargetId <= 0) {
                return redirect()->to(base_url('adminkab/target?mode=kabupaten'))
                    ->with('error', 'Target RPJMD tidak valid.');
            }

            // Cek duplikat per RPJMD target
            $existing = $this->targets
                ->where('rpjmd_target_id', $rpjmdTargetId)
                ->first();

            if ($existing) {
                return redirect()->to(base_url('adminkab/target/edit/' . (int) $existing['id']))
                    ->with('error', 'Target RPJMD sudah ada. Silakan edit.');
            }

            $data = [
                'rpjmd_target_id' => $rpjmdTargetId,
                'rencana_aksi' => $this->request->getPost('rencana_aksi'),
                'capaian' => $this->request->getPost('capaian'),
                'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
                'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
                'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
                'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
                'penanggung_jawab' => $this->request->getPost('penanggung_jawab'),
            ];

            $this->targets->insert($data);

            return redirect()->to(base_url('adminkab/target?mode=kabupaten'))
                ->with('success', 'Target rencana RPJMD berhasil ditambahkan.');
        }

        /* ===================== VALIDASI & SIMPAN MODE OPD (RENSTRA) ===================== */

        $rules = [
            'opd_id' => 'required|integer',
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

        $opdId = (int) $this->request->getPost('opd_id');
        $rtId = (int) $this->request->getPost('renstra_target_id');
        $rpjmdTargetId = (int) ($this->request->getPost('rpjmd_target_id') ?? 0);

        if ($opdId <= 0 || $rtId <= 0) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'OPD/Target Renstra tidak valid.');
        }

        // Validasi bahwa RENSTRA target memang milik OPD tsb
        $rt = $this->db->table('renstra_target rt')
            ->select('rt.id, rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $rtId)
            ->get()
            ->getRowArray();

        if (!$rt || (int) $rt['opd_id'] !== (int) $opdId) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Target Renstra/OPD tidak cocok.');
        }

        // Cek duplikat kombinasi OPD + RENSTRA_TARGET
        if ($this->targets->existsFor($opdId, $rtId)) {
            return redirect()->to(base_url('adminkab/target'))
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

        if ($rpjmdTargetId > 0) {
            $data['rpjmd_target_id'] = $rpjmdTargetId;
        }

        $this->targets->insert($data);

        return redirect()->to(base_url('adminkab/target'))
            ->with('success', 'Target rencana RENSTRA berhasil ditambahkan.');
    }


    public function edit($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $id = (int) $id;

        // Ambil dulu row dasar dari target_rencana
        $row = $this->db->table('target_rencana')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // Tentukan mode dari data: kalau ada rpjmd_target_id -> kabupaten
        $mode = (!empty($row['rpjmd_target_id'])) ? 'kabupaten' : 'opd';

        /* ===================== DETAIL MODE KABUPATEN (RPJMD) ===================== */
        if ($mode === 'kabupaten') {
            $detail = $this->db->table('target_rencana tr')
                ->select("
                tr.*,
                rpj.tahun           AS indikator_tahun,
                rpj.target_tahunan  AS indikator_target,
                ri.indikator_sasaran,
                ri.satuan
            ")
                ->join('rpjmd_target rpj', 'rpj.id = tr.rpjmd_target_id', 'left')
                ->join('rpjmd_indikator_sasaran ri', 'ri.id = rpj.indikator_sasaran_id', 'left')
                ->where('tr.id', $id)
                ->get()
                ->getRowArray();

            if (!$detail) {
                return redirect()->to(base_url('adminkab/target?mode=kabupaten'))
                    ->with('error', 'Detail RPJMD tidak ditemukan.');
            }

            // Mode kabupaten tidak butuh dropdown rpjmdTargets (bisa dikosongkan)
            $rpjmdTargets = [];
        }

        /* ===================== DETAIL MODE OPD (RENSTRA) ===================== */ else {
            $detail = $this->db->table('target_rencana tr')
                ->select("
                tr.*,
                rt.tahun    AS indikator_tahun,
                rt.target   AS indikator_target,
                ris.indikator_sasaran,
                ris.satuan
            ")
                ->join('renstra_target rt', 'rt.id = tr.renstra_target_id', 'left')
                ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
                ->where('tr.id', $id)
                ->get()
                ->getRowArray();

            if (!$detail) {
                return redirect()->to(base_url('adminkab/target'))
                    ->with('error', 'Detail Renstra tidak ditemukan.');
            }

            // Dropdown target RPJMD opsional untuk link
            $rpjmdTargets = [];
            if ($this->db->tableExists('rpjmd_target')) {
                $rpjmdTargets = $this->db->table('rpjmd_target')
                    ->select('id, tahun, target_tahunan')
                    ->orderBy('tahun', 'ASC')
                    ->get()
                    ->getResultArray();
            }
        }

        return view('adminKabupaten/target/edit_target', [
            'detail' => $detail,
            'mode' => $mode,
            'role' => $role,
            'rpjmdTargets' => $rpjmdTargets,
        ]);
    }

    /* =========================================================
     *  UPDATE TARGET – ADMIN_KAB (RENSTRA & RPJMD)
     * =======================================================*/
    public function update($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $id = (int) $id;

        $row = $this->db->table('target_rencana')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // Mode dari POST (kalau tidak ada, tebak dari data)
        $mode = $this->request->getPost('mode') ??
        (!empty($row['rpjmd_target_id']) ? 'kabupaten' : 'opd');

        /* ===================== UPDATE MODE KABUPATEN (RPJMD) ===================== */
        if ($mode === 'kabupaten') {
            $rules = [
                'rencana_aksi' => 'required|string',
                'capaian' => 'permit_empty|string',
                'target_triwulan_1' => 'permit_empty|string',
                'target_triwulan_2' => 'permit_empty|string',
                'target_triwulan_3' => 'permit_empty|string',
                'target_triwulan_4' => 'permit_empty|string',
                'penanggung_jawab' => 'permit_empty|string',
                // kalau mau izinkan ganti link RPJMD, aktifkan ini:
                'rpjmd_target_id' => 'permit_empty|integer',
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()
                    ->with('error', implode(' ', $this->validator->getErrors()));
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

            // kalau mau izinkan ganti target RPJMD:
            $rpjmdTargetId = (int) ($this->request->getPost('rpjmd_target_id') ?? $row['rpjmd_target_id']);
            if ($rpjmdTargetId > 0) {
                $data['rpjmd_target_id'] = $rpjmdTargetId;
            }

            $this->targets->update($id, $data);

            return redirect()->to(base_url('adminkab/target?mode=kabupaten'))
                ->with('success', 'Target rencana RPJMD berhasil diperbarui.');
        }

        /* ===================== UPDATE MODE OPD (RENSTRA) ===================== */
        $rules = [
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

        $data = [
            'rencana_aksi' => $this->request->getPost('rencana_aksi'),
            'capaian' => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => $this->request->getPost('penanggung_jawab'),
        ];

        // Admin_kab boleh ubah mapping ke RPJMD (opsional)
        $rpjmdTargetId = (int) ($this->request->getPost('rpjmd_target_id') ?? 0);
        $data['rpjmd_target_id'] = $rpjmdTargetId > 0 ? $rpjmdTargetId : null;

        $this->targets->update($id, $data);

        return redirect()->to(base_url('adminkab/target'))
            ->with('success', 'Target rencana RENSTRA berhasil diperbarui.');
    }
}

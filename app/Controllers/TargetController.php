<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
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

    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');

        // Khusus admin_kab
        if ($role !== 'admin_kab') {
            return redirect()->to(base_url('/'))
                ->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        // ---- Filter ----
        $tahunParam = trim((string) ($this->request->getGet('tahun') ?? '')); // '', 'all', '2025'
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all') ? null : (string) (int) $tahunParam;

        $opdIdParam = $this->request->getGet('opd_id');
        $opdId = ($opdIdParam === null || $opdIdParam === '') ? null : (int) $opdIdParam;

        $requireOpd = true;

        // Dropdown OPD
        $opdList = $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()->getResultArray();

        // Nama OPD terpilih (untuk kolom Satuan/OPD)
        $opdName = null;
        if (!empty($opdId)) {
            $rowOpd = $this->db->table('opd')
                ->select('nama_opd')
                ->where('id', $opdId)
                ->get()->getRowArray();
            $opdName = $rowOpd['nama_opd'] ?? null;
        }

        // Ambil data bila OPD dipilih
        $rows = [];
        if ($opdId !== null) {
            // PAKAI method baru khusus admin_kab (tidak mengganti method lama)
            $rows = $this->targets->getTargetListByRenstraAdminKab($tahun, $opdId);
        }

        // Grouping: Tujuan → Sasaran
        $grouped = [];
        foreach ($rows as $row) {
            $tujuan = $row['tujuan_rpjmd'] ?? '—';
            $sasaran = $row['sasaran_renstra'] ?? '—';
            $grouped[$tujuan][$sasaran][] = $row;
        }

        // Daftar tahun
        $tahunList = $this->targets->getAvailableYears();

        return view('adminKabupaten/target/target', [
            'grouped' => $grouped,
            'tahun' => ($tahunParam === '') ? '' : ($tahun ?? 'all'),
            'tahunList' => $tahunList,

            // khusus admin_kab
            'role' => 'admin_kab',
            'opdList' => $opdList,
            'opdFilter' => $opdId,
            'opdName' => $opdName,
            'requireOpd' => $requireOpd,
        ]);
    }

    /**
     * Tambah: gunakan ?rt={renstra_target_id} (&opd_id=... jika admin_kab)
     */
    // Tambah Target Rencana (UPDATE: admin_kab bisa memilih OPD di form)
    public function tambah()
    {
        $rtId = (int) $this->request->getGet('rt'); // renstra_target_id
        if ($rtId <= 0) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Parameter tidak valid.');
        }

        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $sessOpd = (int) ($session->get('opd_id') ?? 0);
        $passOpd = (int) ($this->request->getGet('opd_id') ?? 0); // dari index (admin_kab)
        $tahunQS = trim((string) ($this->request->getGet('tahun') ?? ''));

        // Info RENSTRA target + indikator + sasaran + OPD pemilik indikator
        $rt = $this->db->table('renstra_target AS rt')
            ->select('
            rt.id AS renstra_target_id, rt.tahun, rt.target,
            ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan,
            rs.id AS renstra_sasaran_id, rs.sasaran AS sasaran_renstra, rs.opd_id
        ')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $rtId)
            ->get()->getRowArray();

        if (!$rt) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Target Renstra tidak ditemukan.');
        }

        // Ambil daftar OPD untuk dropdown (khusus admin_kab)
        $opdList = [];
        if ($role === 'admin_kab') {
            $opdList = $this->db->table('opd')->select('id, nama_opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        }

        // Nilai awal OPD pada form:
        // - admin_opd: kunci ke session
        // - admin_kab: pakai dari filter jika ada, kalau tidak, biarkan kosong agar user memilih
        $opdToUse = ($role === 'admin_kab') ? ($passOpd ?: 0) : $sessOpd;

        // NOTE: Jangan validasi kepemilikan OPD di sini (GET) supaya admin_kab bisa memilih dulu.
        // Validasi kepemilikan dilakukan di save().

        return view('adminKabupaten/target/tambah_target', [
            'role' => $role,
            'opdIdToUse' => $opdToUse,  // nilai awal select
            'opdList' => $opdList,   // sumber dropdown
            'rt' => $rt,        // info indikator
            'tahunQS' => $tahunQS,   // keep filter saat kembali
        ]);
    }

    // Simpan hasil Tambah (tidak berubah besar, hanya pastikan ambil opd_id dari form utk admin_kab)
    public function save()
    {
        $rules = [
            'renstra_target_id' => 'required|integer',
            'opd_id' => 'required|integer', // wajib ada di form
            'rencana_aksi' => 'required|string',
            'capaian' => 'permit_empty|string',
            'target_triwulan_1' => 'permit_empty|string',
            'target_triwulan_2' => 'permit_empty|string',
            'target_triwulan_3' => 'permit_empty|string',
            'target_triwulan_4' => 'permit_empty|string',
            'penanggung_jawab' => 'permit_empty|string',
            'tahun_qs' => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $session = session();
        $role = (string) ($session->get('role') ?? '');

        // Ambil OPD: admin_opd dari session, admin_kab dari form
        $opdId = (int) ($session->get('opd_id') ?? 0);
        if ($role === 'admin_kab') {
            $opdId = (int) $this->request->getPost('opd_id');
        }

        $rtId = (int) $this->request->getPost('renstra_target_id');
        $tahunQS = trim((string) ($this->request->getPost('tahun_qs') ?? ''));

        if ($opdId <= 0 || $rtId <= 0) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'OPD/Target Renstra tidak valid.');
        }

        // Validasi RT & kepemilikan OPD (indikator harus milik OPD yg dipilih)
        $rt = $this->db->table('renstra_target AS rt')
            ->select('rt.id, rs.opd_id')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $rtId)
            ->get()->getRowArray();

        if (!$rt || (int) $rt['opd_id'] !== (int) $opdId) {
            $q = http_build_query(['opd_id' => $opdId, 'tahun' => $tahunQS ?: 'all']);
            return redirect()->to(base_url('adminKabupaten/target') . '?' . $q)
                ->with('error', 'Indikator bukan milik OPD yang dipilih.');
        }

        // Anti duplikat
        if ($this->targets->existsFor($opdId, $rtId)) {
            $q = http_build_query(['opd_id' => $opdId, 'tahun' => $tahunQS ?: 'all']);
            return redirect()->to(base_url('adminKabupaten/target') . '?' . $q)
                ->with('error', 'Data sudah ada untuk OPD & tahun ini.');
        }

        $data = [
            'opd_id' => $opdId,
            'renstra_target_id' => $rtId,
            'rencana_aksi' => (string) $this->request->getPost('rencana_aksi'),
            'capaian' => (string) $this->request->getPost('capaian'),
            'target_triwulan_1' => (string) $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => (string) $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => (string) $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => (string) $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => (string) $this->request->getPost('penanggung_jawab'),
        ];

        $this->targets->insert($data);

        $q = http_build_query(['opd_id' => $opdId, 'tahun' => $tahunQS ?: 'all']);
        return redirect()->to(base_url('adminkab/target') . '?' . $q)
            ->with('success', 'Target rencana berhasil ditambahkan.');
    }


    public function edit($id)
    {
        $id = (int) $id;

        // Ambil target_rencana + JOIN ke renstra_target → indikator renstra → sasaran (untuk info & validasi OPD)
        $detail = $this->db->table('target_rencana tr')
            ->select('
            tr.*,
            rt.id   AS renstra_target_id,
            rt.tahun,
            rt.target AS indikator_target,

            ris.indikator_sasaran,
            ris.satuan,

            rs.sasaran AS sasaran_renstra,
            rs.opd_id  AS pemilik_opd
        ')
            ->join('renstra_target rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $id)
            ->get()
            ->getRowArray();

        if (!$detail) {
            return redirect()->to(base_url('adminKabupaten/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // (Opsional) validasi akses OPD: selain admin_kab hanya boleh edit milik OPD sendiri
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_kab' && $myOpdId > 0) {
            if ((int) $detail['opd_id'] !== $myOpdId) {    // kolom tr.opd_id
                return redirect()->to(base_url('adminKabupaten/target'))
                    ->with('error', 'Anda tidak berhak mengubah data ini.');
            }
        }

        // Kirim ke view edit (gunakan $detail untuk semua informasi yang dibutuhkan)
        return view('adminKabupaten/target/edit_target', [
            'detail' => $detail,   // berisi kolom tr.* + info indikator (tahun/target/satuan/nama indikator)
        ]);
    }


    public function update($id)
    {
        $id = (int) $id;

        // Ambil dulu untuk validasi kepemilikan/eksistensi
        $row = $this->db->table('target_rencana')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return redirect()->to(base_url('adminkab/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // (Opsional) validasi akses OPD
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_kab' && $myOpdId > 0) {
            if ((int) $row['opd_id'] !== $myOpdId) {
                return redirect()->to(base_url('adminkab/target'))
                    ->with('error', 'Anda tidak berhak mengubah data ini.');
            }
        }

        // Validasi & ambil input
        $data = [
            'rencana_aksi' => $this->request->getPost('rencana_aksi'),
            'capaian' => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => $this->request->getPost('penanggung_jawab'),
        ];

        $this->targets->update($id, $data);

        return redirect()->to(base_url('adminkab/target'))
            ->with('success', 'Data berhasil diperbarui.');
    }

}
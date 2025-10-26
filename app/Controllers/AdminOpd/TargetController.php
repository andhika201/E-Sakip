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

    public function index()
    {
        $tahun = $this->request->getGet('tahun');
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        // Filter OPD hanya untuk admin_kab
        $opdFilter = null;
        $opdList = [];

        if ($role === 'admin_kab') {
            $opdFilter = $this->request->getGet('opd_id');
            $opdFilter = $opdFilter !== null ? (int) $opdFilter : null;

            $opdList = $this->db->table('opd')
                ->select('id, nama_opd')
                ->orderBy('nama_opd', 'ASC')
                ->get()->getResultArray();
        } else {
            $opdFilter = $myOpdId ?: null;
        }

        // Ambil data daftar (TR di-join spesifik ke OPD terpilih)
        $raw = $this->targets->getTargetListByRenstra($tahun, $opdFilter);

        // Grouping: Tujuan → Sasaran → (baris indikator per tahun)
        $grouped = [];
        foreach ($raw as $row) {
            $tujuan = $row['tujuan_rpjmd'] ?? '—';
            $sasaran = $row['sasaran_renstra'] ?? '—';
            $grouped[$tujuan][$sasaran][] = $row;
        }

        return view('adminOpd/target/target', [
            'grouped' => $grouped,
            'tahun' => $tahun,
            'tahunList' => $this->targets->getAvailableYears(),
            'role' => $role,
            'opdList' => $opdList,
            'opdFilter' => $opdFilter,
        ]);
    }

    /**
     * Tambah: gunakan ?rt={renstra_target_id} (&opd_id=... jika admin_kab)
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
            // admin_kab tanpa opd di sesi → pakai dari filter/link
            $opdToUse = $passOpd;
        }

        if ($opdToUse <= 0) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'OPD belum dipilih.');
        }

        // (opsional) Pastikan indikator memang milik OPD tersebut
        if ((int) $rt['opd_id'] !== (int) $opdToUse) {
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Indikator bukan milik OPD yang dipilih.');
        }

        // Anti duplikat: jika (opd_id, renstra_target_id) sudah ada → arahkan ke edit
        $existing = $this->targets->existsFor($opdToUse, $rtId);
        if ($existing) {
            return redirect()->to(base_url('adminopd/target/edit/' . (int) $existing['id']))
                ->with('success', 'Data sudah ada. Silakan edit.');
        }

        return view('adminOpd/target/tambah_target', [
            'role' => $role,
            'opdIdToUse' => $opdToUse,
            'rt' => $rt, // info indikator, satuan, tahun, target
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

        // Anti duplikat
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

        $this->targets->insert($data);

        return redirect()->to(base_url('adminopd/target'))
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
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // (Opsional) validasi akses OPD: selain admin_kab hanya boleh edit milik OPD sendiri
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_kab' && $myOpdId > 0) {
            if ((int) $detail['opd_id'] !== $myOpdId) {    // kolom tr.opd_id
                return redirect()->to(base_url('adminopd/target'))
                    ->with('error', 'Anda tidak berhak mengubah data ini.');
            }
        }

        // Kirim ke view edit (gunakan $detail untuk semua informasi yang dibutuhkan)
        return view('adminOpd/target/edit_target', [
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
            return redirect()->to(base_url('adminopd/target'))
                ->with('error', 'Data tidak ditemukan.');
        }

        // (Opsional) validasi akses OPD
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $myOpdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_kab' && $myOpdId > 0) {
            if ((int) $row['opd_id'] !== $myOpdId) {
                return redirect()->to(base_url('adminopd/target'))
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

        return redirect()->to(base_url('adminopd/target'))
            ->with('success', 'Data berhasil diperbarui.');
    }

}

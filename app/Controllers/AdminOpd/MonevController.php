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

    /** Listing Monev untuk ADMIN OPD */
    public function index()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak / OPD tidak terdeteksi.');
        }

        $tahunParam = trim((string) ($this->request->getGet('tahun') ?? 'all'));
        $tahun = ($tahunParam === '' || strtolower($tahunParam) === 'all') ? null : (string) (int) $tahunParam;

        // Ambil data index berbasis Target OPD (TR left join Monev)
        $monevList = $this->monev->getIndexDataAdminOpd($tahun, $opdId);
        $tahunList = $this->monev->getAvailableYears();

        return view('adminOpd/monev/monev', [
            'monevList' => $monevList,
            'tahun' => $tahun ?? 'all',
            'tahunList' => $tahunList,
        ]);
    }

    /** Form Tambah Monev untuk 1 Target Rencana */
    public function tambah()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Tidak berhak.');
        }

        $targetId = (int) $this->request->getGet('target_rencana_id');
        if ($targetId <= 0) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Parameter tidak valid.');
        }

        // Detail target_rencana + relasi RENSTRA (pastikan milik OPD login)
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
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Target tidak ditemukan.');
        }
        if ((int) $target['rs_opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Target bukan milik OPD Anda.');
        }

        // Cegah duplikat: jika sudah ada monev untuk (opd, target) → ke edit
        $existing = $this->monev->findByTargetAndOpd($targetId, $opdId);
        if ($existing) {
            return redirect()->to(base_url('adminOpd/monev/edit/' . (int) $existing['id']))
                ->with('success', 'Data sudah ada. Silakan edit.');
        }

        return view('adminOpd/monev/tambah_monev', [
            'target' => $target,
        ]);
    }

    /** Simpan hasil Tambah (insert / upsert) */
    public function save()
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Tidak berhak.');
        }

        $rules = [
            'target_rencana_id' => 'required|integer',
            'capaian_triwulan_1' => 'permit_empty|string',
            'capaian_triwulan_2' => 'permit_empty|string',
            'capaian_triwulan_3' => 'permit_empty|string',
            'capaian_triwulan_4' => 'permit_empty|string',
            'total' => 'permit_empty|integer',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetId = (int) $this->request->getPost('target_rencana_id');

        // Validasi target milik OPD + ambil tahun untuk redirect filter
        $rt = $this->db->table('target_rencana AS tr')
            ->select('tr.id, rs.opd_id, rt.tahun')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $targetId)
            ->get()->getRowArray();

        if (!$rt || (int) $rt['opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Target/OPD tidak cocok.');
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

        // Insert/Update per (opd_id, target_rencana_id)
        $this->monev->upsertForTarget($targetId, $opdId, $payload);

        return redirect()->to(base_url('adminopd/monev?tahun=' . urlencode($rt['tahun'])))
            ->with('success', 'Data capaian berhasil disimpan.');
    }

    /** Form Edit Monev */
    public function edit($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Tidak berhak.');
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
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Data tidak ditemukan.');
        }
        if ((int) $row['opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Data bukan milik OPD Anda.');
        }

        return view('adminOpd/monev/edit_monev', [
            'monev' => $row,
        ]);
    }

    /** Update data Monev */
    public function update($id)
    {
        $session = session();
        $role = (string) ($session->get('role') ?? '');
        $opdId = (int) ($session->get('opd_id') ?? 0);

        if ($role !== 'admin_opd' || $opdId <= 0) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Tidak berhak.');
        }

        $row = $this->monev->find((int) $id);
        if (!$row || (int) $row['opd_id'] !== $opdId) {
            return redirect()->to(base_url('adminOpd/monev'))->with('error', 'Data tidak ditemukan / bukan milik OPD Anda.');
        }

        $payload = [
            'capaian_triwulan_1' => (string) $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2' => (string) $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3' => (string) $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4' => (string) $this->request->getPost('capaian_triwulan_4'),
        ];
        // Hitung total jika kosong
        $payload['total'] = ($this->request->getPost('total') !== null && $this->request->getPost('total') !== '')
            ? (int) $this->request->getPost('total')
            : $this->monev->calcTotal(
                $payload['capaian_triwulan_1'],
                $payload['capaian_triwulan_2'],
                $payload['capaian_triwulan_3'],
                $payload['capaian_triwulan_4']
            );

        $this->monev->update((int) $id, $payload);

        return redirect()->to(base_url('adminopd/monev'))->with('success', 'Data capaian berhasil diperbarui.');
    }
}
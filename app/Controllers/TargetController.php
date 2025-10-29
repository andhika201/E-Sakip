<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\TargetModel;

class TargetController extends BaseController
{
    protected $TargetModel;

    public function __construct()
    {
        $this->TargetModel = new TargetModel();
    }

    public function index()
    {
        $tahun = $this->request->getGet('tahun');
        $role = session()->get('role');
        if ($role == 'admin_kab') {
            $raw = $this->TargetModel->getTargetListByRPJMD($tahun);

        } else {
            $raw = $this->TargetModel->getTargetListByRenja($tahun);

        }

        // Grouping: Tujuan > Sasaran > Indikator
        // Grouping: Tujuan RPJMD > Sasaran Renstra > Indikator
        $grouped = [];
        foreach ($raw as $row) {
            $tujuan = $row['tujuan_rpjmd'] ?? 'Belum ada Tujuan';
            $sasaran = $row['sasaran'] ?? 'Belum ada Sasaran';

            if (!isset($grouped[$tujuan])) {
                $grouped[$tujuan] = [];
            }
            if (!isset($grouped[$tujuan][$sasaran])) {
                $grouped[$tujuan][$sasaran] = [];
            }

            $grouped[$tujuan][$sasaran][] = $row;
        }

        $tahunList = $this->TargetModel->getAvailableYears();
        // dd($grouped);
        return view('adminKabupaten/target/target', [
            'grouped' => $grouped,
            'tahun' => $tahun,
            'tahunList' => $tahunList
        ]);
    }

    public function tambah()
    {
        $role = session()->get('role');
        $indikatorId = $this->request->getGet('indikator');
        $db = \Config\Database::connect();
        if ($role == 'admin_kab') {
            $table = 'rpjmd_indikator_sasaran';
            $indikator = $db->table($table)
            ->select("$table.*, 
            rpjmd_target.target_tahunan as target,
            rpjmd_target.tahun as tahun
            ") // ambil semua kolom + target_tahunan
            ->join('rpjmd_target', "rpjmd_target.indikator_sasaran_id = $table.id", 'left')
            ->where("$table.id", $indikatorId)
            ->get()
            ->getRowArray();

        } else {
            $table = 'renstra_indikator_sasaran';
            $indikator = $db->table($table)
            ->where('id', $indikatorId)
            ->get()->getRowArray();
        }
        

        


        if (!$indikator) {
            return redirect()->to(base_url('/adminopd/target'))->with('error', 'Indikator tidak ditemukan');
        }
        // dd($indikator['target']);
        return view('adminKabupaten/target/tambah_target', [
            'indikator' => $indikator,
            'role' => $role,
            'table' => $table
        ]);
    }

    public function save()
    {
        $data = [
            'renja_indikator_sasaran_id' => $this->request->getPost('renja_indikator_sasaran_id'),
            'rencana_aksi' => $this->request->getPost('rencana_aksi'),
            'capaian' => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => $this->request->getPost('penanggung_jawab')
        ];
        $this->TargetModel->insert($data);

        return redirect()->to(base_url('/adminkab/target'))->with('success', 'Data berhasil ditambahkan');
    }

    public function edit($id)
    {
        $db = \Config\Database::connect();
        $target = $this->TargetModel->find($id);

        if (!$target) {
            return redirect()->to(base_url('/adminkab/target'))->with('error', 'Data tidak ditemukan');
        }

        $indikator = $db->table('renja_indikator_sasaran')
            ->where('id', $target['renja_indikator_sasaran_id'])
            ->get()->getRowArray();

        return view('adminKabupaten/target/edit_target', [
            'target' => $target,
            'indikator' => $indikator
        ]);
    }

    public function update($id)
    {
        $data = [
            'rencana_aksi' => $this->request->getPost('rencana_aksi'),
            'capaian' => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab' => $this->request->getPost('penanggung_jawab')
        ];
        $this->TargetModel->update($id, $data);

        return redirect()->to(base_url('/adminkab/target'))->with('success', 'Data berhasil diperbarui');
    }
}

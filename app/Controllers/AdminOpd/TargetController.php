<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
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
        $raw = $this->TargetModel->getTargetListByRenja($tahun);

        // Grouping: Tujuan > Sasaran > Indikator
        $grouped = [];
        foreach ($raw as $row) {
            $tujuan = $row['tujuan_renstra'];
            $sasaran = $row['sasaran_renstra'];
            $indikator = $row['indikator_sasaran'];

            if (!isset($grouped[$tujuan])) $grouped[$tujuan] = [];
            if (!isset($grouped[$tujuan][$sasaran])) $grouped[$tujuan][$sasaran] = [];
            $grouped[$tujuan][$sasaran][] = $row;
        }

        $tahunList = $this->TargetModel->getAvailableYears();

        return view('adminOpd/target/target', [
            'grouped' => $grouped,
            'tahun' => $tahun,
            'tahunList' => $tahunList
        ]);
    }

    public function tambah()
    {
        // Ambil data renja_sasaran untuk dropdown
        $db = \Config\Database::connect();
        $renjaSasaran = $db->table('renja_sasaran')
            ->select('id, sasaran_renja')
            ->orderBy('sasaran_renja', 'ASC')
            ->get()->getResultArray();

        return view('adminOpd/target/tambah_target', [
            'renjaSasaran' => $renjaSasaran
        ]);
    }

    public function save()
    {
        $data = $this->request->getPost();
        $this->TargetModel->insert($data);

        return redirect()->to(base_url('/adminopd/target'))->with('success', 'Data berhasil ditambahkan');
    }

    public function edit($id)
    {
        $target = $this->TargetModel->find($id);
        return view('adminOpd/target/edit_target', ['target' => $target]);
    }

    public function update($id)
    {
        $data = $this->request->getPost();
        $this->TargetModel->update($id, $data);

        return redirect()->to(base_url('/adminopd/target'))->with('success', 'Data berhasil diperbarui');
    }

    public function delete($id)
    {
        $this->TargetModel->delete($id);

        return redirect()->to(base_url('/adminopd/target'))->with('success', 'Data berhasil dihapus');
    }
}

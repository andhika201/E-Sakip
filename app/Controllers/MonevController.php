<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\MonevModel;

class MonevController extends BaseController
{
     protected $MonevModel;

    public function __construct()
    {
        $this->MonevModel = new MonevModel();
    }

    public function index()
    {
        $tahun = $this->request->getGet('tahun');
        $monevList = $this->MonevModel->getMonevWithRelasi($tahun);
        $tahunList = $this->MonevModel->getAvailableYears();

        return view('adminKabupaten/monev/monev', [
            'monevList' => $monevList,
            'tahun' => $tahun,
            'tahunList' => $tahunList
        ]);
    }

    public function tambah()
    {
        $target_rencana_id = $this->request->getGet('target_rencana_id');
        $db = \Config\Database::connect();
        $target = $db->table('target_rencana')
            ->select('
                target_rencana.*,
                renja_indikator_sasaran.satuan,
                renja_indikator_sasaran.target as indikator_target,
                renja_indikator_sasaran.tahun as indikator_tahun,
                renja_sasaran.sasaran_renja,
                target_rencana.penanggung_jawab
            ')
            ->join('renja_indikator_sasaran', 'renja_indikator_sasaran.id = target_rencana.renja_indikator_sasaran_id', 'left')
            ->join('renja_sasaran', 'renja_sasaran.id = renja_indikator_sasaran.renja_sasaran_id', 'left')
            ->where('target_rencana.id', $target_rencana_id)
            ->get()->getRowArray();

        return view('adminKabupaten/monev/tambah_monev', [
            'target' => $target
        ]);
    }

    public function save()
    {
        $data = [
            'target_rencana_id'   => $this->request->getPost('target_rencana_id'),
            'tahun'               => $this->request->getPost('tahun'),
            'capaian_triwulan_1'  => $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2'  => $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3'  => $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4'  => $this->request->getPost('capaian_triwulan_4'),
            'total'               => $this->request->getPost('total'),
        ];
        $this->MonevModel->insert($data);

        return redirect()->to(base_url('adminkab/monev'))->with('success', 'Data capaian berhasil ditambahkan');
    }

    public function edit($id)
    {
        $db = \Config\Database::connect();
        $monev = $db->table('monev')
            ->select('
                monev.*,
                target_rencana.rencana_aksi,
                target_rencana.penanggung_jawab,
                renja_indikator_sasaran.satuan,
                renja_indikator_sasaran.target as indikator_target,
                renja_indikator_sasaran.tahun as indikator_tahun,
                renja_sasaran.sasaran_renja
            ')
            ->join('target_rencana', 'target_rencana.id = monev.target_rencana_id', 'left')
            ->join('renja_indikator_sasaran', 'renja_indikator_sasaran.id = target_rencana.renja_indikator_sasaran_id', 'left')
            ->join('renja_sasaran', 'renja_sasaran.id = renja_indikator_sasaran.renja_sasaran_id', 'left')
            ->where('monev.id', $id)
            ->get()->getRowArray();

        return view('adminKabupaten/monev/edit_monev', [
            'monev' => $monev
        ]);
    }

    public function update($id)
    {
        $data = [
            'capaian_triwulan_1'  => $this->request->getPost('capaian_triwulan_1'),
            'capaian_triwulan_2'  => $this->request->getPost('capaian_triwulan_2'),
            'capaian_triwulan_3'  => $this->request->getPost('capaian_triwulan_3'),
            'capaian_triwulan_4'  => $this->request->getPost('capaian_triwulan_4'),
            'total'               => $this->request->getPost('total'),
        ];
        $this->MonevModel->update($id, $data);

        return redirect()->to(base_url('adminkab/monev'))->with('success', 'Data capaian berhasil diperbarui');
    }
}

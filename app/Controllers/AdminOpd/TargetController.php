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
        if ($tahun) {
            $targets = $this->TargetModel->getByTahun($tahun);
        } else {
            $targets = $this->TargetModel->getAllTargetWithRelasi();
        }
        // $tahunList = $this->TargetModel->getAvailableYears(); // pastikan method ini ada di TargetModel

        return view('adminOpd/target/target', [
            'targets' => $targets,
            'tahun' => $tahun,
            // 'tahunList' => $tahunList // aktifkan jika method tersedia
        ]);
    }

    public function create()
    {
        // Ambil data relasi jika perlu untuk dropdown
        return view('adminOpd/target/tambah_target');
    }

    public function store()
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

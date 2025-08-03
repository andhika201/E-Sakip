<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\KegiatanPkModel;
use CodeIgniter\HTTP\ResponseInterface;

class KegiatanPkController extends BaseController
{
    protected $kegiatanPkModel;

    public function __construct()
    {
        $this->kegiatanPkModel = new KegiatanPkModel();
    }

    /**
     * Display list of kegiatans
     */
    public function index()
    {
        $data = [
            'title' => 'Manajemen Kegiatan OPD',
            'kegiatans' => $this->kegiatanPkModel->getAllKegiatans()
        ];

        return view('adminOpd/kegiatan_opd/kegiatan', $data);
    }

    /**
     * Show form for creating new kegiatan
     */
    public function tambah()
    {
        $data = [
            'title' => 'Tambah Kegiatan OPD',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminOpd/kegiatan_opd/tambah_kegiatan', $data);
    }

    /**
     * Store new kegiatan
     */
    public function save()
    {

        $opdId = session()->get('opd_id');
        if (!$opdId) {
            session()->setFlashdata('error', 'ID OPD tidak ditemukan');
            return redirect()->to('/adminopd/kegiatan_opd');
        }

        // Validation rules
        $rules = [
            'kegiatan' => 'required|min_length[3]',
            'anggaran'         => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                           ->withInput()
                           ->with('validation', $this->validator->getErrors());
        }

        // Prepare data
        $data = [
            'opd_id' => $opdId,
            'kegiatan' => $this->request->getPost('kegiatan'),
            'anggaran' => $this->request->getPost('anggaran')
        ];

        // Insert data
        if ($this->kegiatanPkModel->insert($data)) {
            session()->setFlashdata('success', 'Kegiatan OPD berhasil ditambahkan');
            return redirect()->to('/adminopd/kegiatan_opd');
        } else {
            session()->setFlashdata('error', 'Gagal menambahkan kegiatan OPD');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show form for editing kegiatan
     */
    public function edit($id)
    {
        $kegiatan = $this->kegiatanPkModel->getKegiatanById($id);

        if (!$kegiatan) {
            session()->setFlashdata('error', 'Kegiatan OPD tidak ditemukan');
            return redirect()->to('/adminopd/kegiatan_opd');
        }

        $data = [
            'title' => 'Edit Kegiatan OPD',
            'kegiatan' => $kegiatan,
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminOpd/kegiatan_opd/edit_kegiatan', $data);
    }

    /**
     * Update kegiatan
     */
    public function update($id)
    {
        // Check if kegiatan exists
        $kegiatan = $this->kegiatanPkModel->getKegiatanById($id);
        if (!$kegiatan) {
            session()->setFlashdata('error', 'Kegiatan OPD tidak ditemukan');
            return redirect()->to('/adminopd/kegiatan_opd');
        }

        // Validation rules
        $rules = [
            'kegiatan' => 'required|min_length[3]',
            'anggaran'         => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                           ->withInput()
                           ->with('validation', $this->validator->getErrors());
        }

        // Prepare data
        $data = [
            'kegiatan' => $this->request->getPost('kegiatan'),
            'anggaran'         => $this->request->getPost('anggaran')
        ];

        // Update data
        if ($this->kegiatanPkModel->update($id, $data)) {
            session()->setFlashdata('success', 'Kegiatan OPD berhasil diperbarui');
            return redirect()->to('/adminopd/kegiatan_opd');
        } else {
            session()->setFlashdata('error', 'Gagal memperbarui kegiatan OPD');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete kegiatan
     */
    public function delete($id)
    {
        $kegiatan = $this->kegiatanPkModel->getKegiatanById($id);
        
        if (!$kegiatan) {
            session()->setFlashdata('error', 'Kegiatan OPD tidak ditemukan');
            return redirect()->to('/adminopd/kegiatan_opd');
        }

        if ($this->kegiatanPkModel->delete($id)) {
            session()->setFlashdata('success', 'Kegiatan OPD berhasil dihapus');
        } else {
            session()->setFlashdata('error', 'Gagal menghapus kegiatan OPD');
        }

        return redirect()->to('/adminopd/kegiatan_opd');
    }

}

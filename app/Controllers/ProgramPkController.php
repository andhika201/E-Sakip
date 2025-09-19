<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProgramPkModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProgramPkController extends BaseController
{
    protected $programPkModel;

    public function __construct()
    {
        $this->programPkModel = new ProgramPkModel();
    }

    /**
     * Display list of programs
     */
    public function index()
    {
        $data = [
            'title' => 'Manajemen Program PK',
            'programs' => $this->programPkModel->getAllPrograms()
        ];

        return view('adminKabupaten/program_pk/program', $data);
    }

    /**
     * Show form for creating new program
     */
    public function tambah()
    {
        $data = [
            'title' => 'Tambah Program PK',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminKabupaten/program_pk/tambah_program', $data);
    }

    /**
     * Store new program
     */
    public function save()
    {
        // Validation rules
        $rules = [
            'program_kegiatan' => 'required|min_length[3]',
            'anggaran'         => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        // Prepare data
        $data = [
            'program_kegiatan' => $this->request->getPost('program_kegiatan'),
            'anggaran'         => $this->request->getPost('anggaran')
        ];

        // Insert data
        if ($this->programPkModel->insert($data)) {
            session()->setFlashdata('success', 'Program PK berhasil ditambahkan');
            return redirect()->to('/adminkab/program_pk');
        } else {
            session()->setFlashdata('error', 'Gagal menambahkan program PK');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show form for editing program
     */
    public function edit($id)
    {
        $program = $this->programPkModel->getProgramById($id);

        if (!$program) {
            session()->setFlashdata('error', 'Program PK tidak ditemukan');
            return redirect()->to('/adminkab/program_pk');
        }

        $data = [
            'title' => 'Edit Program PK',
            'program' => $program,
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminKabupaten/program_pk/edit_program', $data);
    }

    /**
     * Update program
     */
    public function update($id)
    {
        // Check if program exists
        $program = $this->programPkModel->getProgramById($id);
        if (!$program) {
            session()->setFlashdata('error', 'Program PK tidak ditemukan');
            return redirect()->to('/adminkab/program_pk');
        }

        // Validation rules
        $rules = [
            'program_kegiatan' => 'required|min_length[3]',
            'anggaran'         => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                           ->withInput()
                           ->with('validation', $this->validator->getErrors());
        }

        // Prepare data
        $data = [
            'program_kegiatan' => $this->request->getPost('program_kegiatan'),
            'anggaran'         => $this->request->getPost('anggaran')
        ];

        // Update data
        if ($this->programPkModel->update($id, $data)) {
            session()->setFlashdata('success', 'Program PK berhasil diperbarui');
            return redirect()->to('/adminkab/program_pk');
        } else {
            session()->setFlashdata('error', 'Gagal memperbarui program PK');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete program
     */
    public function delete($id)
    {
        $program = $this->programPkModel->getProgramById($id);
        
        if (!$program) {
            session()->setFlashdata('error', 'Program PK tidak ditemukan');
            return redirect()->to('/adminkab/program_pk');
        }

        if ($this->programPkModel->delete($id)) {
            session()->setFlashdata('success', 'Program PK berhasil dihapus');
        } else {
            session()->setFlashdata('error', 'Gagal menghapus program PK');
        }

        return redirect()->to('/adminkab/program_pk');
    }

}

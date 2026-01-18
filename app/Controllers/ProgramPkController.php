<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProgramPkModel;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

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


    public function import()
    {
        $data = [
            'title' => 'Import Program PK',
            'validation' => session()->getFlashdata('validation')
        ];

        return view('adminKabupaten/program_pk/import_program', $data);
    }


    public function processImport()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid');
        }

        $tahun = (int) $this->request->getPost('tahun_anggaran');
        if ($tahun <= 0) {
            return redirect()->back()->with('error', 'Tahun anggaran wajib diisi');
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $db = \Config\Database::connect();
        $tbProgram = $db->table('program_pk');
        $tbKegiatan = $db->table('kegiatan_pk');
        $tbSub = $db->table('sub_kegiatan_pk');

        $currentProgramId = null;
        $currentKegiatanId = null;

        $db->transStart();

        foreach ($rows as $i => $r) {

            // Lewati header awal (judul, OPD, urusan)
            if ($i < 3) {
                continue;
            }

            $A = trim((string) ($r['A'] ?? ''));
            $D = trim((string) ($r['D'] ?? ''));
            $E = trim((string) ($r['E'] ?? ''));
            $F = trim((string) ($r['F'] ?? ''));
            $G = trim((string) ($r['G'] ?? ''));

            if ($G === '') {
                continue;
            }

            // Ambil anggaran dari kolom K
            $cellJ = $sheet->getCell("K{$i}");
            $rawVal = $cellJ->getValue();

            if (is_numeric($rawVal)) {
                $anggaran = (int) round($rawVal);
            } else {
                $anggaran = (int) preg_replace('/\D/', '', (string) $cellJ->getFormattedValue());
            }

            /**
             * =========================
             * SKIP OPD & URUSAN
             * =========================
             */
            if ($D === '' || $D === '0') {
                continue;
            }

            /**
             * =========================
             * PROGRAM
             * =========================
             */
            if ($E === '' && $F === '') {

                $kodeProgram = $A . '.' . $D;

                $program = $tbProgram
                    ->where('kode_program', $kodeProgram)
                    ->where('tahun_anggaran', $tahun)
                    ->get()->getRow();

                if ($program) {
                    $currentProgramId = $program->id;

                    if ($anggaran > 0) {
                        $tbProgram->where('id', $currentProgramId)
                            ->update(['anggaran' => $anggaran]);
                    }
                } else {
                    $tbProgram->insert([
                        'kode_program' => $kodeProgram,
                        'program_kegiatan' => $G,
                        'tahun_anggaran' => $tahun,
                        'anggaran' => $anggaran
                    ]);
                    $currentProgramId = $db->insertID();
                }

                $currentKegiatanId = null;
                continue;
            }

            /**
             * =========================
             * KEGIATAN
             * =========================
             */
            if ($E !== '' && $F === '') {

                if (!$currentProgramId) {
                    continue;
                }

                $kodeKegiatan = $A . '.' . $D . '.' . $E;

                $kegiatan = $tbKegiatan
                    ->where('kode_kegiatan', $kodeKegiatan)
                    ->where('tahun_anggaran', $tahun)
                    ->get()->getRow();

                if ($kegiatan) {
                    $currentKegiatanId = $kegiatan->id;

                    if ($anggaran > 0) {
                        $tbKegiatan->where('id', $currentKegiatanId)
                            ->update(['anggaran' => $anggaran]);
                    }
                } else {
                    $tbKegiatan->insert([
                        'program_id' => $currentProgramId,
                        'kode_kegiatan' => $kodeKegiatan,
                        'kegiatan' => $G,
                        'tahun_anggaran' => $tahun,
                        'anggaran' => $anggaran
                    ]);
                    $currentKegiatanId = $db->insertID();
                }
                continue;
            }

            /**
             * =========================
             * SUB KEGIATAN
             * =========================
             */
            if ($E !== '' && $F !== '') {

                if (!$currentKegiatanId) {
                    continue;
                }

                $kodeSub = $A . '.' . $D . '.' . $E . '.' . $F;

                $sub = $tbSub
                    ->where('kode_sub_kegiatan', $kodeSub)
                    ->where('tahun_anggaran', $tahun)
                    ->get()->getRow();

                if ($sub) {
                    if ($anggaran > 0) {
                        $tbSub->where('id', $sub->id)
                            ->update(['anggaran' => $anggaran]);
                    }
                } else {
                    $tbSub->insert([
                        'kegiatan_id' => $currentKegiatanId,
                        'kode_sub_kegiatan' => $kodeSub,
                        'sub_kegiatan' => $G,
                        'tahun_anggaran' => $tahun,
                        'anggaran' => $anggaran
                    ]);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Import gagal, transaksi dibatalkan');
        }

        return redirect()->to('/adminkab/program_pk/import')
            ->with('success', 'Import Program, Kegiatan, dan Sub Kegiatan berhasil');
    }

    public function save()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $tahun = $this->request->getPost('tahun_anggaran');
        $programs = $this->request->getPost('program');

        foreach ($programs as $p) {

            $db->table('program_pk')->insert([
                'kode_program' => time(), // auto sementara
                'program_kegiatan' => $p['nama'],
                'tahun_anggaran' => $tahun,
                'anggaran' => $p['anggaran'],
            ]);

            $programId = $db->insertID();

            foreach ($p['kegiatan'] ?? [] as $k) {

                $db->table('kegiatan_pk')->insert([
                    'program_id' => $programId,
                    'kode_kegiatan' => time(),
                    'kegiatan' => $k['nama'],
                    'tahun_anggaran' => $tahun,
                    'anggaran' => $k['anggaran'],
                ]);

                $kegiatanId = $db->insertID();

                foreach ($k['sub'] ?? [] as $s) {
                    $db->table('sub_kegiatan_pk')->insert([
                        'kegiatan_id' => $kegiatanId,
                        'kode_sub_kegiatan' => time(),
                        'sub_kegiatan' => $s['nama'],
                        'tahun_anggaran' => $tahun,
                        'anggaran' => $s['anggaran'],
                    ]);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan data');
        }

        return redirect()->to('/adminkab/program_pk')->with('success', 'Data berhasil disimpan');
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

        $noScript = 'regex_match[#^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$#is]';

        // Validation rules
        $rules = [
            'program_kegiatan' => 'required|min_length[3]|max_length[500]|' . $noScript,
            'anggaran' => 'required|numeric'
        ];

        $messages = [
            'program_kegiatan' => [
                'regex_match' => 'Program/Kegiatan terdeteksi mengandung script / input berbahaya.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        // Prepare data
        $data = [
            'program_kegiatan' => $this->request->getPost('program_kegiatan'),
            'anggaran' => $this->request->getPost('anggaran')
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

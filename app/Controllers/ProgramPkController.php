<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProgramPkController extends BaseController
{
    protected $programPkModel;
    protected $OpdModel;

    public function __construct()
    {
        $this->programPkModel = new ProgramPkModel();
        $this->OpdModel = new OpdModel();
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
            'validation' => session()->getFlashdata('validation'),
            'opds' => $this->OpdModel->getAllOpd()
        ];

        return view('adminKabupaten/program_pk/tambah_program', $data);
    }


    public function import()
    {
        $data = [
            'title' => 'Import Program PK',
            'opds' => $this->OpdModel->findAll(),
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

        $ext = strtolower($file->getExtension());

        if (!in_array($ext, ['xls', 'xlsx'])) {
            return redirect()->back()->with('error', 'Format file harus Excel');
        }

        $tahun = (int) $this->request->getPost('tahun_anggaran');
        $opdId = (int) $this->request->getPost('opd_id');
        $jenisAnggaran = $this->request->getPost('jenis_anggaran');

        if ($tahun <= 0) {
            return redirect()->back()->with('error', 'Tahun anggaran wajib diisi');
        }

        if ($opdId <= 0) {
            return redirect()->back()->with('error', 'OPD wajib dipilih');
        }

        if (!$jenisAnggaran) {
            return redirect()->back()->with('error', 'Jenis anggaran wajib dipilih');
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

        $lastA = null;
        $lastD = null;
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

            if ($A !== '') {
                $lastA = $A;
            } else {
                $A = $lastA;
            }

            if ($D !== '') {
                $lastD = $D;
            } else {
                $D = $lastD;
            }
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
            if (empty($E) && empty($F)) {

                $kodeProgram = trim($A . '.' . $D, '.');
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
                        'opd_id' => $opdId,
                        'jenis_anggaran' => $jenisAnggaran,
                        'anggaran' => $anggaran,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
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
             */ elseif (!empty($E) && empty($F)) {

                if (!$currentProgramId) {
                    continue;
                }

                $kodeKegiatan = trim($A . '.' . $D . '.' . $E, '.');

                $kegiatan = $tbKegiatan
                    ->where('program_id', $currentProgramId)
                    ->where('kegiatan', $G)
                    ->where('tahun_anggaran', $tahun)
                    ->get()
                    ->getRow();

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
                        'jenis_anggaran' => $jenisAnggaran,
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
             */ elseif (!empty($E) && !empty($F)) {

                if (!$currentKegiatanId) {
                    continue;
                }

                $kodeSub = trim($A . '.' . $D . '.' . $E . '.' . $F, '.');

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
                        'jenis_anggaran' => $jenisAnggaran,
                        'anggaran' => $anggaran,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
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
        $opdId = $this->request->getPost('opd_id');
        $jenisAnggaran = $this->request->getPost('jenis_anggaran');
        $programs = $this->request->getPost('program');
        // dd($this->request->getPost());

        if (!$programs) {
            return redirect()->back()->with('error', 'Program tidak boleh kosong');
        }
        foreach ($programs as $p) {


            $anggaran = (int) $p['anggaran'];

            if ($anggaran <= 0) {
                throw new \Exception("Anggaran program tidak valid");
            }

            $db->table('program_pk')->insert([
                'kode_program' => uniqid('PRG-'),
                'opd_id' => $opdId,
                'program_kegiatan' => $p['nama'],
                'tahun_anggaran' => $tahun,
                'anggaran' => $p['anggaran'],
                'jenis_anggaran' => $jenisAnggaran,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $programId = $db->insertID();

            foreach ($p['kegiatan'] ?? [] as $k) {

                $db->table('kegiatan_pk')->insert([
                    'program_id' => $programId,
                    'kode_kegiatan' => uniqid('KEG-'),
                    'kegiatan' => $k['nama'],
                    'tahun_anggaran' => $tahun,
                    'anggaran' => $k['anggaran'],
                    'jenis_anggaran' => $jenisAnggaran,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $kegiatanId = $db->insertID();

                foreach ($k['sub'] ?? [] as $s) {
                    $db->table('sub_kegiatan_pk')->insert([
                        'kegiatan_id' => $kegiatanId,
                        'kode_sub_kegiatan' => uniqid('SUB-'),
                        'sub_kegiatan' => $s['nama'],
                        'tahun_anggaran' => $tahun,
                        'anggaran' => $s['anggaran'],
                        'jenis_anggaran' => $jenisAnggaran,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
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
        $program = $this->programPkModel->getProgramWithDetails($id);

        if (!$program) {
            session()->setFlashdata('error', 'Program PK tidak ditemukan');
            return redirect()->to('/adminkab/program_pk');
        }

        // dd($program);

        $data = [
            'title' => 'Edit Program PK',
            'program' => $program,
            'validation' => session()->getFlashdata('validation'),
            'opds' => $this->OpdModel->getAllOpd(),
        ];

        return view('adminKabupaten/program_pk/edit_program', $data);
    }

    /**
     * Update program
     */
    public function update($id)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $programs = $this->request->getPost('program');
        $tahun = $this->request->getPost('tahun_anggaran');
        $opdId = $this->request->getPost('opd_id');
        $jenisAnggaran = $this->request->getPost('jenis_anggaran');

        // dd($this->request->getPost());
        if (!$programs) {
            return redirect()->back()->with('error', 'Program tidak boleh kosong');
        }

        // update program utama
        $programData = $programs[0];

        $db->table('program_pk')
            ->where('id', $id)
            ->update([
                'program_kegiatan' => $programData['nama'],
                'anggaran' => $programData['anggaran'],
                'opd_id' => $opdId,
                'tahun_anggaran' => $tahun,
                'jenis_anggaran' => $jenisAnggaran
            ]);

        // hapus kegiatan lama
        $kegiatanIds = $db->table('kegiatan_pk')
            ->select('id')
            ->where('program_id', $id)
            ->get()
            ->getResultArray();

        $kegiatanIds = array_column($kegiatanIds, 'id');

        if (!empty($kegiatanIds)) {
            $db->table('sub_kegiatan_pk')
                ->whereIn('kegiatan_id', $kegiatanIds)
                ->delete();

            $db->table('kegiatan_pk')
                ->where('program_id', $id)
                ->delete();
        }

        // insert ulang kegiatan
        foreach ($programData['kegiatan'] ?? [] as $k) {

            $db->table('kegiatan_pk')->insert([
                'program_id' => $id,
                'kode_kegiatan' => uniqid('KEG-'),
                'kegiatan' => $k['nama'],
                'anggaran' => $k['anggaran'],
                'tahun_anggaran' => $tahun,
                'jenis_anggaran' => $jenisAnggaran
            ]);

            $kegiatanId = $db->insertID();

            foreach ($k['sub'] ?? [] as $s) {

                $db->table('sub_kegiatan_pk')->insert([
                    'kegiatan_id' => $kegiatanId,
                    'kode_sub_kegiatan' => uniqid('SUB-'),
                    'sub_kegiatan' => $s['nama'],
                    'anggaran' => $s['anggaran'],
                    'tahun_anggaran' => $tahun,
                    'jenis_anggaran' => $jenisAnggaran
                ]);

            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal memperbarui data');
        }

        return redirect()->to('/adminkab/program_pk')
            ->with('success', 'Program berhasil diperbarui');
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

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
        $level = $this->request->getGet('level') ?? 'program';

        // Master Program/Kegiatan/Sub dikelola PER TAHUN ANGGARAN.
        $years      = $this->programPkModel->getAvailableYears();
        $tahunParam = $this->request->getGet('tahun');
        $tahun      = ($tahunParam !== null && $tahunParam !== '')
            ? (int) $tahunParam
            : ($years[0] ?? (int) date('Y')); // default: tahun terbaru yang ada

        switch ($level) {

            case 'kegiatan':
                $dataList = $this->programPkModel->getAllKegiatan($tahun);
                break;

            case 'sub':
                $dataList = $this->programPkModel->getAllSubKegiatan($tahun);
                break;

            default:
                $dataList = $this->programPkModel->getAllPrograms($tahun);
                $level = 'program';
        }

        $data = [
            'title' => 'Manajemen Program PK',
            'level' => $level,
            'dataList' => $dataList,
            'tahun' => $tahun,
            'tahunList' => $years,
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


    /**
     * Unduh template Excel import (cocok dengan parser processImport):
     * baris 1-2 = judul & header (dilewati importer), data mulai baris 3.
     * Kolom: A=Kode1(Urusan.Bidang), D=Kode2(Program), E=Kode3(Kegiatan),
     *        F=Kode4(Sub), G=Uraian, K=Anggaran. Level ditentukan dari E/F.
     */
    public function template()
    {
        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Data');

        foreach (['A' => 18, 'B' => 6, 'C' => 6, 'D' => 14, 'E' => 14, 'F' => 12, 'G' => 60, 'H' => 6, 'I' => 6, 'J' => 6, 'K' => 22] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Baris 1: judul (dilewati importer)
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT PROGRAM / KEGIATAN / SUB KEGIATAN PK — FORMAT SIPD');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Baris 2: header kolom (juga dilewati importer; data mulai baris 3)
        $headers = [
            'A2' => 'KODE 1 (Urusan.Bidang)',
            'D2' => 'KODE 2 (Program)',
            'E2' => 'KODE 3 (Kegiatan)',
            'F2' => 'KODE 4 (Sub Keg.)',
            'G2' => 'URAIAN (Nama Program / Kegiatan / Sub Kegiatan)',
            'K2' => 'ANGGARAN (Rp)',
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }
        $sheet->getStyle('A2:K2')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A2:K2')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('00743E');
        $sheet->getStyle('A2:K2')->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A2:K2')->getAlignment()->setWrapText(true);

        // Baris 3+: contoh (Program -> Kegiatan -> Sub). A & D boleh dikosongkan
        // pada baris turunan (importer mewarisi nilai di atasnya / fill-down).
        $rows = [
            // [A, D, E, F, Uraian, Anggaran]
            ['1.01', '2.01', '', '', 'PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH', 1500000000],
            ['', '', '2.01', '', 'Perencanaan, Penganggaran, dan Evaluasi Kinerja Perangkat Daerah', 500000000],
            ['', '', '2.01', '01', 'Penyusunan Dokumen Perencanaan Perangkat Daerah', 200000000],
            ['', '', '2.01', '02', 'Evaluasi Kinerja Perangkat Daerah', 300000000],
            ['', '', '2.02', '', 'Administrasi Keuangan Perangkat Daerah', 800000000],
            ['', '', '2.02', '01', 'Penyediaan Gaji dan Tunjangan ASN', 800000000],
        ];
        $r = 3;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", $row[0]);
            $sheet->setCellValue("D{$r}", $row[1]);
            $sheet->setCellValueExplicit("E{$r}", $row[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$r}", $row[3], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("G{$r}", $row[4]);
            $sheet->setCellValueExplicit("K{$r}", $row[5], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $r++;
        }
        $lastRow = $r - 1;
        $sheet->getStyle("A3:K{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("A2:K2")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle("K3:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A3:F{$lastRow}")->getAlignment()->setHorizontal('center');

        // Sheet petunjuk
        $help = $ss->createSheet();
        $help->setTitle('Petunjuk');
        $help->getColumnDimension('A')->setWidth(100);
        $petunjuk = [
            'PETUNJUK PENGISIAN TEMPLATE IMPORT',
            '',
            '1. Baris 1 (judul) dan baris 2 (header) JANGAN dihapus — importer melewati 2 baris pertama.',
            '2. Mulai isi data pada baris ke-3.',
            '3. Kolom yang dibaca importer: A, D, E, F, G, dan K. Kolom B, C, H, I, J diabaikan.',
            '',
            'PENENTUAN LEVEL (berdasarkan kolom E & F):',
            '   • PROGRAM       : isi A & D. Kolom E dan F DIKOSONGKAN.',
            '   • KEGIATAN      : isi E. Kolom F DIKOSONGKAN. (A & D boleh kosong, mewarisi program di atasnya)',
            '   • SUB KEGIATAN  : isi E DAN F. (A & D boleh kosong, mewarisi di atasnya)',
            '',
            '4. Kolom G = nama Program/Kegiatan/Sub Kegiatan.',
            '5. Kolom K = anggaran (angka saja, tanpa "Rp" / titik / koma).',
            '6. Urutkan: Program diikuti Kegiatan-nya, lalu Sub Kegiatan-nya (seperti contoh di sheet "Data").',
            '7. Tahun Anggaran, Jenis Anggaran, dan OPD dipilih di halaman Import (bukan di file ini).',
            '8. Re-import pada tahun yang sama akan MEMPERBARUI data (berdasar kode), bukan menduplikasi.',
        ];
        $hr = 1;
        foreach ($petunjuk as $line) {
            $help->setCellValue("A{$hr}", $line);
            $hr++;
        }
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $help->getStyle('A7')->getFont()->setBold(true);
        $help->getStyle("A1:A{$hr}")->getAlignment()->setWrapText(true);

        $ss->setActiveSheetIndex(0);

        $filename = 'Template_Import_Program_PK.xlsx';
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $writer->save('php://output');
        exit;
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

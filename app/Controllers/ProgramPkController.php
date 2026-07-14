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

    private function normalizeMoney($value): int
    {
        $value = trim((string) $value);
        $value = preg_replace('/[,.]\d{1,2}$/', '', $value);
        return (int) preg_replace('/\D/', '', (string) $value);
    }

    private function hasMoneyValue($value): bool
    {
        return preg_match('/\d/', (string) $value) === 1;
    }

    private function normalizeProgramPayload($programs): array
    {
        if (empty($programs) || !is_array($programs)) {
            throw new \InvalidArgumentException('Program tidak boleh kosong');
        }

        $programs = array_values($programs);
        $normalized = [];

        foreach ($programs as $pIndex => $p) {
            $namaProgram = trim((string) ($p['nama'] ?? ''));
            $rawAnggaranProgram = $p['anggaran'] ?? '';
            $anggaranProgram = $this->normalizeMoney($rawAnggaranProgram);

            if ($namaProgram === '') {
                throw new \InvalidArgumentException('Nama program wajib diisi');
            }
            if (!$this->hasMoneyValue($rawAnggaranProgram)) {
                throw new \InvalidArgumentException('Anggaran program tidak valid');
            }

            $kegiatanPayload = $p['kegiatan'] ?? [];
            if (empty($kegiatanPayload) || !is_array($kegiatanPayload)) {
                throw new \InvalidArgumentException('Minimal harus ada 1 kegiatan');
            }

            $programData = [
                'id' => isset($p['id']) ? (int) $p['id'] : null,
                'nama' => $namaProgram,
                'anggaran' => $anggaranProgram,
                'kegiatan' => [],
            ];

            foreach ($kegiatanPayload as $k) {
                $namaKegiatan = trim((string) ($k['nama'] ?? ''));
                $rawAnggaranKegiatan = $k['anggaran'] ?? '';
                $anggaranKegiatan = $this->normalizeMoney($rawAnggaranKegiatan);

                if ($namaKegiatan === '') {
                    throw new \InvalidArgumentException('Nama kegiatan wajib diisi');
                }
                if (!$this->hasMoneyValue($rawAnggaranKegiatan)) {
                    throw new \InvalidArgumentException('Anggaran kegiatan tidak valid');
                }

                $kegiatanData = [
                    'id' => isset($k['id']) ? (int) $k['id'] : null,
                    'nama' => $namaKegiatan,
                    'anggaran' => $anggaranKegiatan,
                    'sub' => [],
                ];

                foreach (($k['sub'] ?? []) as $s) {
                    $namaSub = trim((string) ($s['nama'] ?? ''));
                    $rawAnggaranSub = $s['anggaran'] ?? '';
                    $anggaranSub = $this->normalizeMoney($rawAnggaranSub);

                    if ($namaSub === '') {
                        throw new \InvalidArgumentException('Nama sub kegiatan wajib diisi');
                    }
                    if (!$this->hasMoneyValue($rawAnggaranSub)) {
                        throw new \InvalidArgumentException('Anggaran sub kegiatan tidak valid');
                    }

                    $kegiatanData['sub'][] = [
                        'id' => isset($s['id']) ? (int) $s['id'] : null,
                        'nama' => $namaSub,
                        'anggaran' => $anggaranSub,
                    ];
                }

                $programData['kegiatan'][] = $kegiatanData;
            }

            $normalized[] = $programData;
        }

        return $normalized;
    }

    private function normalizeIds(array $ids): array
    {
        $ids = array_map(static fn ($id) => (int) $id, $ids);
        $ids = array_filter($ids, static fn ($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    private function deletePkSubkegiatanUsageBySubIds($db, array $subIds): void
    {
        $subIds = $this->normalizeIds($subIds);
        if (empty($subIds)) {
            return;
        }

        $db->table('pk_subkegiatan')
            ->whereIn('subkegiatan_id', $subIds)
            ->delete();
    }

    private function deletePkKegiatanUsageByKegiatanIds($db, array $kegiatanIds): void
    {
        $kegiatanIds = $this->normalizeIds($kegiatanIds);
        if (empty($kegiatanIds)) {
            return;
        }

        $pkKegiatanRows = $db->table('pk_kegiatan')
            ->select('id')
            ->whereIn('kegiatan_id', $kegiatanIds)
            ->get()
            ->getResultArray();
        $pkKegiatanIds = $this->normalizeIds(array_column($pkKegiatanRows, 'id'));

        if (!empty($pkKegiatanIds)) {
            $db->table('pk_subkegiatan')
                ->whereIn('pk_kegiatan_id', $pkKegiatanIds)
                ->delete();
            $db->table('pk_kegiatan')
                ->whereIn('id', $pkKegiatanIds)
                ->delete();
        }
    }

    private function deletePkProgramUsageByProgramIds($db, array $programIds): void
    {
        $programIds = $this->normalizeIds($programIds);
        if (empty($programIds)) {
            return;
        }

        $pkProgramRows = $db->table('pk_program')
            ->select('id')
            ->whereIn('program_id', $programIds)
            ->get()
            ->getResultArray();
        $pkProgramIds = $this->normalizeIds(array_column($pkProgramRows, 'id'));

        if (!empty($pkProgramIds)) {
            $pkKegiatanRows = $db->table('pk_kegiatan')
                ->select('id')
                ->whereIn('pk_program_id', $pkProgramIds)
                ->get()
                ->getResultArray();
            $pkKegiatanIds = $this->normalizeIds(array_column($pkKegiatanRows, 'id'));

            if (!empty($pkKegiatanIds)) {
                $db->table('pk_subkegiatan')
                    ->whereIn('pk_kegiatan_id', $pkKegiatanIds)
                    ->delete();
                $db->table('pk_kegiatan')
                    ->whereIn('id', $pkKegiatanIds)
                    ->delete();
            }

            $db->table('pk_program')
                ->whereIn('id', $pkProgramIds)
                ->delete();
        }
    }

    private function deleteProgramPkTree($db, int $programId): void
    {
        $kegiatanRows = $db->table('kegiatan_pk')
            ->select('id')
            ->where('program_id', $programId)
            ->get()
            ->getResultArray();
        $kegiatanIds = $this->normalizeIds(array_column($kegiatanRows, 'id'));

        if (!empty($kegiatanIds)) {
            $subRows = $db->table('sub_kegiatan_pk')
                ->select('id')
                ->whereIn('kegiatan_id', $kegiatanIds)
                ->get()
                ->getResultArray();
            $subIds = $this->normalizeIds(array_column($subRows, 'id'));

            $this->deletePkSubkegiatanUsageBySubIds($db, $subIds);
            $this->deletePkKegiatanUsageByKegiatanIds($db, $kegiatanIds);

            $db->table('sub_kegiatan_pk')
                ->whereIn('kegiatan_id', $kegiatanIds)
                ->delete();
            $db->table('kegiatan_pk')
                ->whereIn('id', $kegiatanIds)
                ->delete();
        }

        $this->deletePkProgramUsageByProgramIds($db, [$programId]);
        $db->table('program_pk')->where('id', $programId)->delete();
    }

    public function save()
    {
        $db = \Config\Database::connect();
        $transactionStarted = false;

        try {
            $tahun = (int) $this->request->getPost('tahun_anggaran');
            $opdId = (int) $this->request->getPost('opd_id');
            $jenisAnggaran = trim((string) $this->request->getPost('jenis_anggaran'));
            $programs = $this->normalizeProgramPayload($this->request->getPost('program'));

            if ($tahun <= 0) {
                throw new \InvalidArgumentException('Tahun anggaran wajib diisi');
            }
            if ($opdId <= 0) {
                throw new \InvalidArgumentException('OPD wajib dipilih');
            }
            if ($jenisAnggaran === '') {
                throw new \InvalidArgumentException('Jenis anggaran wajib dipilih');
            }

            $db->transException(true)->transBegin();
            $transactionStarted = true;

            foreach ($programs as $p) {
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

                foreach ($p['kegiatan'] as $k) {
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

                    foreach ($k['sub'] as $s) {
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

            $db->transCommit();
            $transactionStarted = false;

            return redirect()->to('/adminkab/program_pk')->with('success', 'Data berhasil disimpan');
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                $db->transRollback();
            }

            log_message('error', 'Gagal menyimpan Program PK: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
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
        $transactionStarted = false;

        try {
            $program = $this->programPkModel->getProgramById($id);
            if (!$program) {
                throw new \InvalidArgumentException('Program PK tidak ditemukan');
            }

            $programs = $this->normalizeProgramPayload($this->request->getPost('program'));
            $programData = $programs[0];
            $tahun = (int) $this->request->getPost('tahun_anggaran');
            $opdId = (int) $this->request->getPost('opd_id');
            $jenisAnggaran = trim((string) $this->request->getPost('jenis_anggaran'));

            if ($tahun <= 0) {
                throw new \InvalidArgumentException('Tahun anggaran wajib diisi');
            }
            if ($opdId <= 0) {
                throw new \InvalidArgumentException('OPD wajib dipilih');
            }
            if ($jenisAnggaran === '') {
                throw new \InvalidArgumentException('Jenis anggaran wajib dipilih');
            }

            $db->transException(true)->transBegin();
            $transactionStarted = true;
            $now = date('Y-m-d H:i:s');

            $db->table('program_pk')
                ->where('id', $id)
                ->update([
                    'program_kegiatan' => $programData['nama'],
                    'anggaran' => $programData['anggaran'],
                    'opd_id' => $opdId,
                    'tahun_anggaran' => $tahun,
                    'jenis_anggaran' => $jenisAnggaran,
                    'updated_at' => $now
                ]);

            $existingKegiatanRows = $db->table('kegiatan_pk')
                ->select('id')
                ->where('program_id', $id)
                ->get()
                ->getResultArray();
            $existingKegiatanIds = $this->normalizeIds(array_column($existingKegiatanRows, 'id'));
            $usedKegiatanIds = [];

            foreach ($programData['kegiatan'] as $k) {
                $kegiatanId = (int) ($k['id'] ?? 0);
                $kegiatanPayload = [
                    'kegiatan' => $k['nama'],
                    'anggaran' => $k['anggaran'],
                    'tahun_anggaran' => $tahun,
                    'jenis_anggaran' => $jenisAnggaran,
                    'updated_at' => $now
                ];

                if ($kegiatanId > 0 && in_array($kegiatanId, $existingKegiatanIds, true)) {
                    $db->table('kegiatan_pk')
                        ->where('id', $kegiatanId)
                        ->update($kegiatanPayload);
                } else {
                    $kegiatanPayload['program_id'] = $id;
                    $kegiatanPayload['kode_kegiatan'] = uniqid('KEG-');
                    $kegiatanPayload['created_at'] = $now;
                    $db->table('kegiatan_pk')->insert($kegiatanPayload);
                    $kegiatanId = (int) $db->insertID();
                }
                $usedKegiatanIds[] = $kegiatanId;

                $existingSubRows = $db->table('sub_kegiatan_pk')
                    ->select('id')
                    ->where('kegiatan_id', $kegiatanId)
                    ->get()
                    ->getResultArray();
                $existingSubIds = $this->normalizeIds(array_column($existingSubRows, 'id'));
                $usedSubIds = [];

                foreach ($k['sub'] as $s) {
                    $subId = (int) ($s['id'] ?? 0);
                    $subPayload = [
                        'sub_kegiatan' => $s['nama'],
                        'anggaran' => $s['anggaran'],
                        'tahun_anggaran' => $tahun,
                        'jenis_anggaran' => $jenisAnggaran,
                        'updated_at' => $now
                    ];

                    if ($subId > 0 && in_array($subId, $existingSubIds, true)) {
                        $db->table('sub_kegiatan_pk')
                            ->where('id', $subId)
                            ->update($subPayload);
                    } else {
                        $subPayload['kegiatan_id'] = $kegiatanId;
                        $subPayload['kode_sub_kegiatan'] = uniqid('SUB-');
                        $subPayload['created_at'] = $now;
                        $db->table('sub_kegiatan_pk')->insert($subPayload);
                        $subId = (int) $db->insertID();
                    }
                    $usedSubIds[] = $subId;
                }

                $deleteSubIds = array_diff($existingSubIds, $usedSubIds);
                if (!empty($deleteSubIds)) {
                    $this->deletePkSubkegiatanUsageBySubIds($db, $deleteSubIds);
                    $db->table('sub_kegiatan_pk')
                        ->whereIn('id', $deleteSubIds)
                        ->delete();
                }
            }

            $deleteKegiatanIds = array_diff($existingKegiatanIds, $usedKegiatanIds);
            if (!empty($deleteKegiatanIds)) {
                $subRows = $db->table('sub_kegiatan_pk')
                    ->select('id')
                    ->whereIn('kegiatan_id', $deleteKegiatanIds)
                    ->get()
                    ->getResultArray();
                $this->deletePkSubkegiatanUsageBySubIds($db, array_column($subRows, 'id'));
                $this->deletePkKegiatanUsageByKegiatanIds($db, $deleteKegiatanIds);

                $db->table('sub_kegiatan_pk')
                    ->whereIn('kegiatan_id', $deleteKegiatanIds)
                    ->delete();
                $db->table('kegiatan_pk')
                    ->whereIn('id', $deleteKegiatanIds)
                    ->delete();
            }

            $db->transCommit();
            $transactionStarted = false;

            return redirect()->to('/adminkab/program_pk')
                ->with('success', 'Program berhasil diperbarui');
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                $db->transRollback();
            }

            log_message('error', 'Gagal memperbarui Program PK ID ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
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

        $db = \Config\Database::connect();
        try {
            $db->transException(true)->transBegin();
            $this->deleteProgramPkTree($db, (int) $id);
            $db->transCommit();
            session()->setFlashdata('success', 'Program PK berhasil dihapus');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal menghapus Program PK ID ' . $id . ': ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal menghapus program PK: ' . $e->getMessage());
        }

        return redirect()->to('/adminkab/program_pk');
    }
}

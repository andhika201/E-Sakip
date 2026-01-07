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
        // Pastikan PhpSpreadsheet tersedia
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            session()->setFlashdata('error', 'PhpSpreadsheet belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet');
            return redirect()->back()->withInput();
        }

        try {
            // --- Validasi File Upload ---
            $file = $this->request->getFile('file');
            if (!$file || !$file->isValid()) {
                session()->setFlashdata('error', 'File tidak valid atau tidak ditemukan.');
                return redirect()->back()->withInput();
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['xlsx', 'xls'])) {
                session()->setFlashdata('error', 'Format file harus .xlsx atau .xls.');
                return redirect()->back()->withInput();
            }

            // --- Opsi dari form ---
            $sheetName = trim((string) ($this->request->getPost('sheet') ?? ''));
            $headerRow = max(1, (int) ($this->request->getPost('header_row') ?? 1));
            $useFilldown = $this->request->getPost('filldown') ? true : false;
            $dryrun = $this->request->getPost('dryrun') ? true : false;

            // --- Load Excel ---
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $sheet = $sheetName !== '' ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();

            if (!$sheet) {
                session()->setFlashdata('error', 'Sheet tidak ditemukan.');
                return redirect()->back()->withInput();
            }

            // Pakai toArray untuk A–G (struktur), L diambil dari cell langsung
            $rows = $sheet->toArray(null, true, true, true);

            // --- DB & Builder ---
            $db = \Config\Database::connect();
            $tbProgram = $db->table('program_pk');
            $tbKegiatan = $db->table('kegiatan_pk');
            $tbSub = $db->table('sub_kegiatan_pk');

            // --- Statistik ---
            $stats = [
                'program_created' => 0,
                'program_found' => 0,
                'kegiatan_created' => 0,
                'kegiatan_found' => 0,
                'sub_created' => 0,
                'sub_found' => 0,
                'rows_read' => 0,
                'rows_skipped' => 0,
                'pattern_program_rows' => 0,
                'pattern_kegiatan_rows' => 0,
                'pattern_sub_rows' => 0,
                'skipped_no_program_ctx' => 0,
                'skipped_no_kegiatan_ctx' => 0,
                'skipped_pattern_mismatch' => 0,
                'skipped_empty_or_noname' => 0,
            ];

            // --- Konteks berjalan (parent) ---
            $currentProgramId = null;
            $currentKegiatanId = null;

            // cache untuk fill-down (A–F)
            $last = ['A' => null, 'B' => null, 'C' => null, 'D' => null, 'E' => null, 'F' => null];

            if (!$dryrun) {
                $db->transStart();
            }

            foreach ($rows as $i => $r) {
                // Lewati header
                if ($i < $headerRow + 1) {
                    continue;
                }

                // --- Baca kolom A–F, G (uraian) ---
                $A = isset($r['A']) ? trim((string) $r['A']) : '';
                $B = isset($r['B']) ? trim((string) $r['B']) : '';
                $C = isset($r['C']) ? trim((string) $r['C']) : '';
                $D = isset($r['D']) ? trim((string) $r['D']) : '';
                $E = isset($r['E']) ? trim((string) $r['E']) : '';
                $F = isset($r['F']) ? trim((string) $r['F']) : '';
                $G = isset($r['G']) ? trim((string) $r['G']) : ''; // uraian/nama

                // --- Ambil nilai mentah dari kolom J (Rancangan APBD Rp) ---
                $anggaran = 0;
                $cellJ = $sheet->getCell("J{$i}");
                $rawVal = $cellJ->getValue();           // nilai mentah (bisa float / int)

                if (is_numeric($rawVal)) {
                    // Contoh: 89920779177 atau 8.9920779177E10 → bulatkan ke rupiah
                    $anggaran = (int) round($rawVal);
                } else {
                    // Cadangan: kalau formatnya string "89,920,779,177.00"
                    $formatted = (string) $cellJ->getFormattedValue();
                    $s = trim($formatted);

                    // buang spasi biasa & non-breaking space
                    $s = str_replace([" ", "\xC2\xA0"], '', $s);

                    // kalau akhiran ".00" → buang
                    if (substr($s, -3) === '.00') {
                        $s = substr($s, 0, -3);
                    }

                    // buang pemisah ribuan: koma & titik
                    $s = str_replace([',', '.'], '', $s);

                    // sisakan digit saja
                    $s = preg_replace('/[^0-9]/', '', $s);

                    $anggaran = ($s === '') ? 0 : (int) $s;
                }

                // --- Fill-down A..F (untuk file yang pakai merge) ---
                if ($useFilldown) {
                    foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $k) {
                        if (${$k} === '' && $last[$k] !== null) {
                            ${$k} = $last[$k];
                        }
                    }
                }
                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $k) {
                    if (${$k} !== '') {
                        $last[$k] = ${$k};
                    }
                }

                // --- Skip baris kosong / tanpa nama uraian ---
                if ($A . $B . $C . $D . $E . $F . $G === '' || $G === '') {
                    $stats['rows_skipped']++;
                    $stats['skipped_empty_or_noname']++;
                    continue;
                }

                // --- Flag terisi per kolom ---
                $hasA = ($A !== '');
                $hasB = ($B !== '');
                $hasC = ($C !== '');
                $hasD = ($D !== '');
                $hasE = ($E !== '');
                $hasF = ($F !== '');

                $stats['rows_read']++;

                // === ROLE PASTI ===
                // A-D terisi dan E dan F kosong itu program
                $isProgram = ($hasA && $hasB && $hasC && $hasD && !$hasE && !$hasF);
                // A-E terisi dan F kosong itu kegiatan
                $isKegiatan = ($hasA && $hasB && $hasC && $hasD && $hasE && !$hasF);
                // A-F terisi itu sub_kegiatan
                $isSub = ($hasA && $hasB && $hasC && $hasD && $hasE && $hasF);

                if ($isProgram)
                    $stats['pattern_program_rows']++;
                if ($isKegiatan)
                    $stats['pattern_kegiatan_rows']++;
                if ($isSub)
                    $stats['pattern_sub_rows']++;

                // 1) SUB KEGIATAN
                if ($isSub) {
                    if ($currentKegiatanId === null) {
                        $stats['rows_skipped']++;
                        $stats['skipped_no_kegiatan_ctx']++;
                        continue;
                    }

                    $subNama = $G;

                    if ($dryrun) {
                        if ($currentKegiatanId > 0) {
                            $exists = $tbSub->select('id')
                                ->where('kegiatan_id', $currentKegiatanId)
                                ->where('sub_kegiatan', $subNama)
                                ->get(1)->getRow();
                            if ($exists) {
                                $stats['sub_found']++;
                            } else {
                                $stats['sub_created']++;
                            }
                        } else {
                            $stats['sub_created']++;
                        }
                    } else {
                        $exists = $tbSub->select('id')
                            ->where('kegiatan_id', $currentKegiatanId)
                            ->where('sub_kegiatan', $subNama)
                            ->get(1)->getRow();
                        if ($exists) {
                            $stats['sub_found']++;
                            if ($anggaran > 0) {
                                $tbSub->where('id', (int) $exists->id)
                                    ->update(['anggaran' => $anggaran]);
                            }
                        } else {
                            $tbSub->insert([
                                'kegiatan_id' => $currentKegiatanId,
                                'sub_kegiatan' => $subNama,
                                'anggaran' => $anggaran,
                            ]);
                            $stats['sub_created']++;
                        }
                    }
                    continue;
                }

                // 2) KEGIATAN
                if ($isKegiatan) {
                    if ($currentProgramId === null) {
                        $stats['rows_skipped']++;
                        $stats['skipped_no_program_ctx']++;
                        continue;
                    }

                    $kegiatanNama = $G;

                    if ($dryrun) {
                        if ($currentProgramId > 0) {
                            $exists = $tbKegiatan->select('id')
                                ->where('program_id', $currentProgramId)
                                ->where('kegiatan', $kegiatanNama)
                                ->get(1)->getRow();
                            if ($exists) {
                                $currentKegiatanId = (int) $exists->id;
                                $stats['kegiatan_found']++;
                            } else {
                                $currentKegiatanId = -1;
                                $stats['kegiatan_created']++;
                            }
                        } else {
                            $currentKegiatanId = -1;
                            $stats['kegiatan_created']++;
                        }
                    } else {
                        $exists = $tbKegiatan->select('id')
                            ->where('program_id', $currentProgramId)
                            ->where('kegiatan', $kegiatanNama)
                            ->get(1)->getRow();
                        if ($exists) {
                            $currentKegiatanId = (int) $exists->id;
                            $stats['kegiatan_found']++;
                            if ($anggaran > 0) {
                                $tbKegiatan->where('id', $currentKegiatanId)
                                    ->update(['anggaran' => $anggaran]);
                            }
                        } else {
                            $tbKegiatan->insert([
                                'program_id' => $currentProgramId,
                                'kegiatan' => $kegiatanNama,
                                'anggaran' => $anggaran,
                            ]);
                            $currentKegiatanId = (int) $db->insertID();
                            $stats['kegiatan_created']++;
                        }
                    }
                    continue;
                }

                // 3) PROGRAM
                if ($isProgram) {
                    $programNama = $G;

                    if ($dryrun) {
                        $exists = $tbProgram->select('id')
                            ->where('program_kegiatan', $programNama)
                            ->get(1)->getRow();
                        if ($exists) {
                            $currentProgramId = (int) $exists->id;
                            $stats['program_found']++;
                        } else {
                            $currentProgramId = -1;
                            $stats['program_created']++;
                        }
                        $currentKegiatanId = null;
                    } else {
                        $exists = $tbProgram->select('id')
                            ->where('program_kegiatan', $programNama)
                            ->get(1)->getRow();
                        if ($exists) {
                            $currentProgramId = (int) $exists->id;
                            $stats['program_found']++;
                            if ($anggaran > 0) {
                                $tbProgram->where('id', $currentProgramId)
                                    ->update(['anggaran' => $anggaran]);
                            }
                        } else {
                            $tbProgram->insert([
                                'program_kegiatan' => $programNama,
                                'anggaran' => $anggaran,
                            ]);
                            $currentProgramId = (int) $db->insertID();
                            $stats['program_created']++;
                        }
                        $currentKegiatanId = null;
                    }
                    continue;
                }

                // pola kolom lain → skip
                $stats['rows_skipped']++;
                $stats['skipped_pattern_mismatch']++;
            }

            // --- Selesaikan transaksi ---
            if (!$dryrun) {
                $db->transComplete();
                if ($db->transStatus() === false) {
                    session()->setFlashdata('error', 'Import gagal. Transaksi dibatalkan.');
                    return redirect()->back()->withInput();
                }
            }

            $msg = ($dryrun ? '[SIMULASI] ' : '') .
                "Baris diproses: {$stats['rows_read']}, dilewati: {$stats['rows_skipped']}. " .
                "Program (baru: {$stats['program_created']}, ada: {$stats['program_found']}) — pola program: {$stats['pattern_program_rows']}. " .
                "Kegiatan (baru: {$stats['kegiatan_created']}, ada: {$stats['kegiatan_found']}) — pola kegiatan: {$stats['pattern_kegiatan_rows']}. " .
                "Subkegiatan (baru: {$stats['sub_created']}, ada: {$stats['sub_found']}) — pola sub: {$stats['pattern_sub_rows']}. " .
                "Skip: kosong/nama {$stats['skipped_empty_or_noname']}, tanpa program aktif {$stats['skipped_no_program_ctx']}, tanpa kegiatan aktif {$stats['skipped_no_kegiatan_ctx']}, pola kolom lain {$stats['skipped_pattern_mismatch']}.";

            session()->setFlashdata('success', $msg);
            return redirect()->to('/adminkab/program_pk/import');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Terjadi kesalahan: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }




    public function save()
    {
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

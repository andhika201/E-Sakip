<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Concerns\ImportMultiOpdTrait;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
use App\Services\OpdResolver;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProgramPkController extends BaseController
{
    /**
     * Cakupan import "Seluruh OPD": staging batch, resolusi OPD per unit, dan
     * pemetaan manual tanpa unggah ulang Excel.
     *
     * Pembacaan Excel-nya sendiri ada di App\Services\Lampiran8Parser dan
     * penulisan ke tabel produksi di App\Services\ProgramPkWriter, sehingga
     * kedua cakupan (per OPD & seluruh OPD) memakai logika hirarki yang sama.
     */
    use ImportMultiOpdTrait;

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
     * Unduh template Excel import (cocok dengan parser processImport).
     * Kolom: A=Kode Perangkat Daerah/Unit, B=Urusan, C=Bidang Urusan,
     *        D=Program, E=Kegiatan, F=Sub Kegiatan, G=Uraian, K=Anggaran.
     * Level ditentukan dari D/E/F pada baris itu sendiri.
     */
    public function template()
    {
        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Data');

        foreach (['A' => 24, 'B' => 8, 'C' => 10, 'D' => 10, 'E' => 12, 'F' => 12, 'G' => 60, 'H' => 6, 'I' => 6, 'J' => 6, 'K' => 22] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Baris 1: judul (dilewati importer)
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT PROGRAM / KEGIATAN / SUB KEGIATAN PK — FORMAT SIPD');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Baris 2: header kolom (juga dilewati importer; data mulai baris 3)
        $headers = [
            'A2' => 'KODE PERANGKAT DAERAH / UNIT',
            'B2' => 'URUSAN',
            'C2' => 'BIDANG URUSAN',
            'D2' => 'PROGRAM',
            'E2' => 'KEGIATAN',
            'F2' => 'SUB KEGIATAN',
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

        // Baris 3+: contoh (Program -> Kegiatan -> Sub). A/B/C boleh dikosongkan
        // pada baris turunan (importer mewarisi nilai di atasnya / fill-down),
        // tetapi D/E/F harus diisi sesuai levelnya karena justru kolom itulah
        // yang menentukan baris tersebut Program, Kegiatan, atau Sub Kegiatan.
        $rows = [
            // [A, B, C, D, E, F, Uraian, Anggaran]
            ['1.01.2.22.0.00.01.0000', '1', '01', '01', '', '', 'PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH', 1500000000],
            ['', '', '', '01', '2.01', '', 'Perencanaan, Penganggaran, dan Evaluasi Kinerja Perangkat Daerah', 500000000],
            ['', '', '', '01', '2.01', '0001', 'Penyusunan Dokumen Perencanaan Perangkat Daerah', 200000000],
            ['', '', '', '01', '2.01', '0002', 'Evaluasi Kinerja Perangkat Daerah', 300000000],
            ['', '', '', '01', '2.02', '', 'Administrasi Keuangan Perangkat Daerah', 800000000],
            ['', '', '', '01', '2.02', '0001', 'Penyediaan Gaji dan Tunjangan ASN', 800000000],
            ['', '2', '22', '02', '', '', 'PROGRAM PENGEMBANGAN KEBUDAYAAN', 400000000],
            ['', '2', '22', '02', '2.01', '', 'Pengelolaan Kebudayaan yang Masyarakat Pelakunya dalam Daerah Kabupaten/Kota', 400000000],
            ['', '2', '22', '02', '2.01', '0001', 'Pelindungan, Pengembangan, Pemanfaatan Objek Pemajuan Kebudayaan', 400000000],
        ];
        $r = 3;
        $textCols = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5];
        foreach ($rows as $row) {
            foreach ($textCols as $col => $idx) {
                // Ditulis sebagai teks agar nol di depan ("01", "0001") tidak hilang.
                $sheet->setCellValueExplicit("{$col}{$r}", $row[$idx], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $sheet->setCellValue("G{$r}", $row[6]);
            $sheet->setCellValueExplicit("K{$r}", $row[7], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
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
            '1. Baris 1 (judul) dan baris 2 (header) JANGAN dihapus — data dimulai pada baris ke-3.',
            '2. Kolom yang dibaca importer: A, B, C, D, E, F, G, dan K. Kolom H, I, J diabaikan.',
            '   A = Kode Perangkat Daerah/Unit   B = Urusan   C = Bidang Urusan',
            '   D = Program   E = Kegiatan   F = Sub Kegiatan   G = Uraian   K = Anggaran',
            '',
            'PENENTUAN LEVEL (berdasarkan kolom D, E, & F pada baris itu sendiri):',
            '   • PROGRAM       : isi D. Kolom E dan F DIKOSONGKAN.',
            '   • KEGIATAN      : isi D dan E. Kolom F DIKOSONGKAN.',
            '   • SUB KEGIATAN  : isi D, E, DAN F.',
            '   • Baris dengan D kosong (OPD / Urusan / Bidang Urusan / TOTAL) otomatis dilewati.',
            '',
            '3. Kolom A, B, dan C boleh dikosongkan pada baris turunan — nilainya mewarisi baris di atasnya.',
            '   Kolom D, E, dan F TIDAK diwarisi karena menjadi penentu level baris.',
            '4. Kode unik dibentuk dari jalur lengkap A+B+C+D (Program), +E (Kegiatan), +F (Sub Kegiatan).',
            '   Karena itu B dan C wajib benar: Program "1.1.2" (Pendidikan) dan "2.22.2" (Kebudayaan)',
            '   sama-sama berkode program "2" dan akan tertukar bila B/C dikosongkan.',
            '5. Nol di depan tidak masalah: "1"/"01", "2"/"02", dan "3"/"0003" dianggap kode yang sama.',
            '6. Kolom G = nama Program/Kegiatan/Sub Kegiatan.',
            '7. Kolom K = anggaran baris tersebut (angka saja, tanpa "Rp").',
            '8. Urutkan: Program diikuti Kegiatan-nya, lalu Sub Kegiatan-nya (seperti contoh di sheet "Data").',
            '9. Tahun Anggaran, Jenis Anggaran, dan OPD dipilih di halaman Import (bukan di file ini).',
            '10. Re-import pada tahun & jenis anggaran yang sama akan MEMPERBARUI data (berdasar kode), bukan menduplikasi.',
            '11. Centang "Simulasi saja" di halaman Import untuk mengecek hasil tanpa menyimpan ke database.',
        ];
        $hr = 1;
        foreach ($petunjuk as $line) {
            $help->setCellValue("A{$hr}", $line);
            $hr++;
        }
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $help->getStyle('A8')->getFont()->setBold(true);
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

    /**
     * Import Lampiran 8 APBD (format SIPD).
     *
     * Peta kolom: A = kode perangkat daerah/unit, B = urusan, C = bidang
     * urusan, D = program, E = kegiatan, F = sub kegiatan, G = uraian,
     * K = anggaran Rancangan APBD.
     *
     * Level baris ditentukan dari kolom D/E/F baris itu sendiri:
     *   D terisi, E & F kosong  -> Program
     *   D & E terisi, F kosong  -> Kegiatan
     *   D, E & F terisi         -> Sub Kegiatan
     *   D kosong                -> baris OPD/urusan/bidang urusan/total (dilewati)
     *
     * Kode hirarki dibentuk dari jalur SIPD lengkap (A+B+C+D ... +E ... +F),
     * bukan hanya A+D. Tanpa B dan C, "1.1.2" (Pengelolaan Pendidikan) dan
     * "2.22.2" (Pengembangan Kebudayaan) menghasilkan kode yang sama sehingga
     * anggaran program saling menimpa dan kegiatan masuk ke program yang salah.
     */
    public function processImport()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid');
        }

        $ext = strtolower($file->getExtension());

        if (!in_array($ext, ['xls', 'xlsx'], true)) {
            return redirect()->back()->with('error', 'Format file harus Excel');
        }

        $tahun         = (int) $this->request->getPost('tahun_anggaran');
        $opdId         = (int) $this->request->getPost('opd_id');
        $jenisAnggaran = trim((string) $this->request->getPost('jenis_anggaran'));
        $sheetName     = trim((string) $this->request->getPost('sheet'));
        $dryRun        = (bool) $this->request->getPost('dryrun');

        // Cakupan import: 'per_opd' (perilaku lama) atau 'seluruh' (OPD
        // ditentukan otomatis dari blok unit di Excel).
        $cakupan = $this->request->getPost('cakupan') === 'seluruh' ? 'seluruh' : 'per_opd';

        if ($tahun <= 0) {
            return redirect()->back()->with('error', 'Tahun anggaran wajib diisi');
        }

        // OPD hanya wajib pada cakupan per OPD. Pada cakupan seluruh OPD,
        // nilai kiriman form sengaja diabaikan supaya tidak ada OPD default.
        if ($cakupan === 'per_opd' && $opdId <= 0) {
            return redirect()->back()->with('error', 'OPD wajib dipilih');
        }
        if ($cakupan === 'seluruh') {
            $opdId = 0;
        }

        if ($jenisAnggaran === '') {
            return redirect()->back()->with('error', 'Jenis anggaran wajib dipilih');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca file import Program PK: ' . $e->getMessage());
            return redirect()->back()->with('error', 'File Excel tidak dapat dibaca: ' . $e->getMessage());
        }

        $sheet = $sheetName !== ''
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getActiveSheet();

        if (!$sheet) {
            return redirect()->back()->with('error', 'Sheet "' . $sheetName . '" tidak ditemukan pada file');
        }

        // Satu pembacaan file untuk kedua cakupan: hasilnya daftar unit
        // beserta Program/Kegiatan/Sub miliknya.
        try {
            $hasilParse = (new \App\Services\Lampiran8Parser())->parse($sheet);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mem-parse Lampiran 8: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Isi file tidak dapat dibaca: ' . $e->getMessage());
        }

        if (empty($hasilParse['units'])) {
            return redirect()->back()->with('error', 'Tidak ada blok unit / data Program yang terbaca pada file ini.');
        }

        $db = \Config\Database::connect();

        /* =========================================================
         * CAKUPAN: SELURUH OPD
         * =======================================================*/
        if ($cakupan === 'seluruh') {
            $db->transException(true)->transBegin();

            try {
                $ring = $this->importSeluruhOpd(
                    $db,
                    $hasilParse['units'],
                    $tahun,
                    $jenisAnggaran,
                    (string) $file->getClientName(),
                    $dryRun
                );

                if ($dryRun) {
                    $db->transRollback();
                } else {
                    $db->transCommit();
                }
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', 'Gagal import Program PK (seluruh OPD): ' . $e->getMessage());

                return redirect()->back()->with('error', 'Import gagal, transaksi dibatalkan: ' . $e->getMessage());
            }

            return $this->pesanHasilSeluruhOpd($ring, $dryRun);
        }

        /* =========================================================
         * CAKUPAN: PER OPD (perilaku lama, tidak diubah)
         * =======================================================*/
        $db->transException(true)->transBegin();

        try {
            $writer = new \App\Services\ProgramPkWriter($db);
            $stat   = \App\Services\ProgramPkWriter::statKosong();

            // Seluruh isi file masuk ke OPD yang dipilih pengguna, apa pun
            // blok unitnya — persis seperti sebelum ada pilihan cakupan.
            foreach ($hasilParse['units'] as $unit) {
                $writer->tulisUnit($unit, $opdId, $tahun, $jenisAnggaran, $stat);
            }

            if ($dryRun) {
                $db->transRollback();
            } else {
                $db->transCommit();
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal import Program PK: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Import gagal, transaksi dibatalkan: ' . $e->getMessage());
        }

        $ringkasan = sprintf(
            'Program %d baru / %d diperbarui, Kegiatan %d baru / %d diperbarui, Sub Kegiatan %d baru / %d diperbarui.',
            $stat['program_baru'],
            $stat['program_update'],
            $stat['kegiatan_baru'],
            $stat['kegiatan_update'],
            $stat['sub_baru'],
            $stat['sub_update']
        );

        if ($stat['program_auto'] > 0 || $stat['kegiatan_auto'] > 0) {
            $ringkasan .= ' ' . $stat['program_auto'] . ' program dan ' . $stat['kegiatan_auto']
                . ' kegiatan dibuat otomatis karena unit terkait tidak mencantumkan barisnya di file'
                . ' (anggarannya dihitung dari total rincian di bawahnya).';
        }

        if ($stat['lewat'] > 0) {
            $ringkasan .= ' ' . $stat['lewat'] . ' baris dilewati karena induknya tidak ditemukan.';
        }

        $pesan = $dryRun
            ? 'Simulasi selesai (tidak ada data yang disimpan). ' . $ringkasan
            : 'Import Program, Kegiatan, dan Sub Kegiatan berhasil. ' . $ringkasan;

        return redirect()->to('/adminkab/program_pk/import')->with('success', $pesan);
    }

    /* =========================================================
     * CAKUPAN SELURUH OPD — PESAN HASIL & PEMETAAN MANUAL
     * =======================================================*/

    /** Susun pesan hasil import/simulasi cakupan seluruh OPD. */
    private function pesanHasilSeluruhOpd(array $ring, bool $dryRun)
    {
        $stat = $ring['stat'];

        $ringkasan = sprintf(
            '%d unit terbaca — %d exact, %d alias, %d aturan induk, %d perlu mapping manual. '
            . 'Program %d, Kegiatan %d, Sub Kegiatan %d. Total anggaran Rp%s.',
            $ring['unit_total'],
            $ring['exact'],
            $ring['alias'],
            $ring['parent_rule'],
            $ring['pending'],
            $ring['program'],
            $ring['kegiatan'],
            $ring['sub'],
            number_format($ring['total_anggaran'], 0, ',', '.')
        );

        if ($dryRun) {
            $peringatan = '';
            if ($stat['lewat'] > 0) {
                $peringatan = ' ' . $stat['lewat'] . ' baris berpotensi dilewati karena induknya tidak ditemukan.';
            }

            return redirect()->to('/adminkab/program_pk/import')
                ->with('success', 'Simulasi selesai (tidak ada data yang disimpan). ' . $ringkasan . $peringatan);
        }

        $pesan = 'Import selesai. ' . $ringkasan
            . sprintf(
                ' Tersimpan: Program %d baru / %d diperbarui, Kegiatan %d baru / %d diperbarui, Sub Kegiatan %d baru / %d diperbarui.',
                $stat['program_baru'],
                $stat['program_update'],
                $stat['kegiatan_baru'],
                $stat['kegiatan_update'],
                $stat['sub_baru'],
                $stat['sub_update']
            );

        // Masih ada unit yang OPD-nya belum pasti -> arahkan ke halaman
        // mapping. Datanya sudah tersimpan di staging, tidak perlu unggah ulang.
        if ($ring['pending'] > 0 && !empty($ring['batch_id'])) {
            return redirect()->to('/adminkab/program_pk/mapping/' . (int) $ring['batch_id'])
                ->with('info', $pesan . ' ' . $ring['pending'] . ' unit menunggu pemetaan OPD di bawah ini.');
        }

        return redirect()->to('/adminkab/program_pk/import')->with('success', $pesan);
    }

    /** Daftar batch import yang masih menyisakan unit pending. */
    public function mappingIndex()
    {
        $db = \Config\Database::connect();
        if (!$this->stagingSiap($db)) {
            return redirect()->to('/adminkab/program_pk/import')
                ->with('error', 'Tabel staging import belum tersedia. Jalankan db/update_2026-08-12_import_multi_opd.sql.');
        }

        $batches = $db->table('import_batch')
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get()->getResultArray();

        return view('adminKabupaten/program_pk/mapping_batch', [
            'title'   => 'Pemetaan OPD Hasil Import',
            'batches' => $batches,
        ]);
    }

    /** Daftar unit dalam satu batch beserta status pemetaannya. */
    public function mapping($batchId = null)
    {
        $db = \Config\Database::connect();
        if (!$this->stagingSiap($db)) {
            return redirect()->to('/adminkab/program_pk/import')
                ->with('error', 'Tabel staging import belum tersedia. Jalankan db/update_2026-08-12_import_multi_opd.sql.');
        }

        $batchId = (int) $batchId;
        $batch   = $db->table('import_batch')->where('id', $batchId)->get()->getRowArray();
        if (!$batch) {
            return redirect()->to('/adminkab/program_pk/mapping')->with('error', 'Batch import tidak ditemukan.');
        }

        $units = $db->table('import_batch_unit')
            ->where('batch_id', $batchId)
            ->orderBy('mapping_status', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->get()->getResultArray();

        $resolver = new OpdResolver($db);
        foreach ($units as $i => $u) {
            // Nama OPD SELALU dari master, tidak pernah dari Excel.
            $units[$i]['nama_opd']       = $resolver->namaOpd($u['opd_id'] === null ? null : (int) $u['opd_id']);
            $units[$i]['nama_saran_opd'] = $resolver->namaOpd($u['saran_opd_id'] === null ? null : (int) $u['saran_opd_id']);
        }

        return view('adminKabupaten/program_pk/mapping_unit', [
            'title' => 'Pemetaan OPD — Batch #' . $batchId,
            'batch' => $batch,
            'units' => $units,
        ]);
    }

    /** Detail satu unit pending: pilih OPD + pratinjau hierarki. */
    public function mappingDetail($unitId = null)
    {
        $db = \Config\Database::connect();
        if (!$this->stagingSiap($db)) {
            return redirect()->to('/adminkab/program_pk/import')
                ->with('error', 'Tabel staging import belum tersedia.');
        }

        $unit = $this->unitDariStaging($db, (int) $unitId);
        if (!$unit) {
            return redirect()->to('/adminkab/program_pk/mapping')->with('error', 'Unit import tidak ditemukan.');
        }

        $batch    = $db->table('import_batch')->where('id', $unit['batch_id'])->get()->getRowArray();
        $resolver = new OpdResolver($db);

        return view('adminKabupaten/program_pk/mapping_detail', [
            'title'     => 'Pemetaan OPD — ' . $unit['nama_unit'],
            'unit'      => $unit,
            'batch'     => $batch,
            'preview'   => $this->pratinjauUnit($unit),
            'opds'      => $this->OpdModel->orderBy('nama_opd', 'ASC')->findAll(),
            'namaSaran' => $resolver->namaOpd($unit['saran_opd_id']),
        ]);
    }

    /**
     * Simpan pemetaan manual lalu finalisasi unit tersebut ke tabel produksi.
     * Satu mapping berlaku untuk SELURUH blok unit, bukan per Program.
     */
    public function mappingSave($unitId = null)
    {
        $db = \Config\Database::connect();
        if (!$this->stagingSiap($db)) {
            return redirect()->to('/adminkab/program_pk/import')
                ->with('error', 'Tabel staging import belum tersedia.');
        }

        $unitId = (int) $unitId;
        $unit   = $this->unitDariStaging($db, $unitId);
        if (!$unit) {
            return redirect()->to('/adminkab/program_pk/mapping')->with('error', 'Unit import tidak ditemukan.');
        }

        $kembali = '/adminkab/program_pk/mapping/' . $unit['batch_id'];

        if ($unit['mapping_status'] === 'imported') {
            return redirect()->to($kembali)->with('error', 'Unit ini sudah pernah diproses.');
        }

        $opdId = (int) ($this->request->getPost('opd_id') ?? 0);
        if ($opdId <= 0) {
            return redirect()->back()->with('error', 'Pilih OPD tujuan terlebih dahulu.');
        }
        // OPD wajib benar-benar ada di master — tidak menerima id sembarangan.
        if (!$this->OpdModel->find($opdId)) {
            return redirect()->back()->with('error', 'OPD tujuan tidak ditemukan pada master OPD.');
        }

        $batch = $db->table('import_batch')->where('id', $unit['batch_id'])->get()->getRowArray();
        if (!$batch) {
            return redirect()->to('/adminkab/program_pk/mapping')->with('error', 'Batch import tidak ditemukan.');
        }

        $simpanAlias = (bool) $this->request->getPost('simpan_alias');
        $userId      = (int) session()->get('user_id') ?: null;

        $db->transException(true)->transBegin();

        try {
            $writer = new \App\Services\ProgramPkWriter($db);
            $stat   = \App\Services\ProgramPkWriter::statKosong();

            $writer->tulisUnit(
                $unit,
                $opdId,
                (int) $batch['tahun'],
                (string) $batch['jenis_anggaran'],
                $stat
            );

            $db->table('import_batch_unit')->where('id', $unitId)->update([
                'opd_id'         => $opdId,
                'mapping_status' => 'imported',
                'mapping_method' => 'manual',
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            if ($simpanAlias) {
                (new OpdResolver($db))->simpanAlias($unit['nama_unit'], $unit['kode_unit'], $opdId, $userId);
            }

            // Batch dianggap selesai kalau tidak ada lagi unit yang menunggu.
            $sisa = (int) $db->table('import_batch_unit')
                ->where('batch_id', $unit['batch_id'])
                ->where('mapping_status', 'pending_mapping')
                ->countAllResults();

            $db->table('import_batch')->where('id', $unit['batch_id'])->update([
                'jumlah_pending' => $sisa,
                'status'         => $sisa > 0 ? 'pending_mapping' : 'selesai',
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Gagal finalisasi mapping unit ' . $unitId . ': ' . $e->getMessage());

            return redirect()->back()->with('error', 'Gagal memproses mapping: ' . $e->getMessage());
        }

        $nama = (new OpdResolver($db))->namaOpd($opdId);

        return redirect()->to($kembali)->with('success', sprintf(
            '%s dipetakan ke %s. Tersimpan: Program %d baru / %d diperbarui, Kegiatan %d baru / %d diperbarui, Sub Kegiatan %d baru / %d diperbarui.',
            $unit['nama_unit'],
            $nama,
            $stat['program_baru'],
            $stat['program_update'],
            $stat['kegiatan_baru'],
            $stat['kegiatan_update'],
            $stat['sub_baru'],
            $stat['sub_update']
        ));
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

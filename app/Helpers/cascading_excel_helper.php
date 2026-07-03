<?php

/**
 * Helper ekspor Cascading ke Excel (.xlsx) memakai PhpSpreadsheet.
 * Dipakai bersama oleh publik (UserController) & admin (AdminKab/AdminOpd CascadingController).
 * Tiga struktur mengikuti view cetak (flat, tanpa rowspan):
 *   - Kabupaten   : cascading_cetak (getMatrix)
 *   - OPD         : cascading_cetak (getCascadingMatrixByOpd)
 *   - Keseluruhan : cascading_cetak_keseluruhan (getKeseluruhanMatrix)
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!function_exists('_casc_txt')) {
    /** Bersihkan nilai sel: <br> -> newline, buang tag HTML, trim. */
    function _casc_txt($v): string
    {
        $s = (string) ($v ?? '');
        $s = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $s);
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim($s);
    }
}

if (!function_exists('cascading_excel_stream')) {
    /**
     * Bangun & kirim file .xlsx lalu exit.
     *
     * @param string   $title     judul di baris 1
     * @param string   $subtitle  keterangan di baris 2
     * @param string[] $headers   label kolom
     * @param array    $dataRows  array of array (baris data, sudah dibersihkan)
     * @param string   $filename  nama file unduhan
     */
    function cascading_excel_stream(string $title, string $subtitle, array $headers, array $dataRows, string $filename): void
    {
        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Cascading');

        $nCol     = max(1, count($headers));
        $lastCol  = Coordinate::stringFromColumnIndex($nCol);

        // --- Judul & subjudul ---
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $subtitle);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('555555');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Header kolom (baris 4) ---
        $hRow = 4;
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $hRow, (string) $h);
        }
        $headerRange = "A{$hRow}:{$lastCol}{$hRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00743E');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($hRow)->setRowHeight(28);

        // --- Data ---
        $r = $hRow + 1;
        foreach ($dataRows as $row) {
            $ci = 1;
            foreach ($row as $val) {
                $cell = Coordinate::stringFromColumnIndex($ci) . $r;
                $sheet->setCellValueExplicit($cell, (string) $val, DataType::TYPE_STRING);
                $ci++;
            }
            $r++;
        }
        $lastRow = max($hRow, $r - 1);

        // --- Border, wrap, lebar kolom ---
        $sheet->getStyle("A{$hRow}:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($lastRow > $hRow) {
            $sheet->getStyle("A" . ($hRow + 1) . ":{$lastCol}{$lastRow}")->getAlignment()
                ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
        for ($i = 1; $i <= $nCol; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(26);
        }

        $sheet->freezePane('A' . ($hRow + 1));
        $sheet->setAutoFilter($headerRange);

        // --- Kirim ---
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }
}

if (!function_exists('cascading_kab_excel')) {
    /** Cascading Kabupaten (getMatrix): Tujuan/Sasaran/Indikator/Satuan/Baseline/Target per tahun/Program/OPD. */
    function cascading_kab_excel(array $rows, array $years, string $periode): void
    {
        $headers = ['Tujuan RPJMD', 'Sasaran RPJMD', 'Indikator Sasaran', 'Satuan', 'Baseline'];
        foreach ($years as $y) {
            $headers[] = (string) $y;
        }
        $headers[] = 'Program';
        $headers[] = 'Perangkat Daerah';

        $data = [];
        foreach ($rows as $rw) {
            $row = [
                _casc_txt($rw['tujuan_rpjmd'] ?? '-'),
                _casc_txt($rw['sasaran_rpjmd'] ?? '-'),
                _casc_txt($rw['indikator_sasaran'] ?? '-'),
                _casc_txt($rw['satuan'] ?? '-'),
                _casc_txt($rw['baseline'] ?? '-'),
            ];
            foreach ($years as $y) {
                $row[] = _casc_txt($rw['targets'][$y] ?? '-');
            }
            $row[] = _casc_txt($rw['program_kegiatan'] ?? '-');
            $row[] = _casc_txt($rw['nama_opd'] ?? '-');
            $data[] = $row;
        }

        cascading_excel_stream(
            'Cascading Kinerja Kabupaten',
            'Matriks Cascading RPJMD → Program Perangkat Daerah · Periode ' . $periode,
            $headers,
            $data,
            'Cascading-Kabupaten-' . $periode . '.xlsx'
        );
    }
}

if (!function_exists('cascading_opd_excel')) {
    /** Cascading Perangkat Daerah (getCascadingMatrixByOpd): RPJMD → Renstra → Eselon II/III/IV. */
    function cascading_opd_excel(array $rows, string $periode, string $namaOpd = ''): void
    {
        $headers = [
            'Tujuan RPJMD', 'Sasaran RPJMD', 'Tujuan Renstra',
            'Sasaran ESS II', 'Indikator ESS II',
            'Sasaran ESS III', 'Indikator ESS III',
            'Sasaran ESS IV/JF', 'Indikator ESS IV',
        ];
        $data = [];
        foreach ($rows as $rw) {
            $data[] = [
                _casc_txt($rw['tujuan_rpjmd'] ?? '-'),
                _casc_txt($rw['sasaran_rpjmd'] ?? '-'),
                _casc_txt($rw['renstra_tujuan'] ?? '-'),
                _casc_txt($rw['renstra_sasaran'] ?? '-'),
                _casc_txt(!empty($rw['indikator_sasaran']) ? $rw['indikator_sasaran'] : '-'),
                _casc_txt(!empty($rw['es3_sasaran']) ? $rw['es3_sasaran'] : '-'),
                _casc_txt(!empty($rw['es3_indikator']) ? $rw['es3_indikator'] : '-'),
                _casc_txt(!empty($rw['es4_sasaran']) ? $rw['es4_sasaran'] : '-'),
                _casc_txt(!empty($rw['es4_indikator']) ? $rw['es4_indikator'] : '-'),
            ];
        }
        $sub   = 'Perangkat Daerah: ' . ($namaOpd !== '' ? $namaOpd : '-') . ' · Periode ' . $periode;
        $fname = 'Cascading-OPD-' . ($namaOpd !== '' ? preg_replace('/[^A-Za-z0-9]+/', '-', $namaOpd) . '-' : '') . $periode . '.xlsx';

        cascading_excel_stream('Cascading Kinerja Perangkat Daerah', $sub, $headers, $data, $fname);
    }
}

if (!function_exists('cascading_keseluruhan_excel')) {
    /** Cascading Keseluruhan (getKeseluruhanMatrix): RPJMD → Perangkat Daerah → Renstra. */
    function cascading_keseluruhan_excel(array $rows, string $periode): void
    {
        $headers = ['Tujuan RPJMD', 'Sasaran RPJMD', 'Perangkat Daerah', 'Tujuan Renstra', 'Sasaran Renstra', 'Indikator Renstra'];
        $data = [];
        foreach ($rows as $rw) {
            $data[] = [
                _casc_txt($rw['tujuan_rpjmd'] ?? '-'),
                _casc_txt($rw['sasaran_rpjmd'] ?? '-'),
                _casc_txt(!empty($rw['nama_opd']) ? $rw['nama_opd'] : '-'),
                _casc_txt(!empty($rw['renstra_tujuan']) ? $rw['renstra_tujuan'] : '-'),
                _casc_txt(!empty($rw['renstra_sasaran']) ? $rw['renstra_sasaran'] : '-'),
                _casc_txt(!empty($rw['renstra_indikator']) ? $rw['renstra_indikator'] : '-'),
            ];
        }
        cascading_excel_stream(
            'Cascading Kinerja Keseluruhan',
            'RPJMD Kabupaten → Renstra Perangkat Daerah · Periode ' . $periode,
            $headers,
            $data,
            'Cascading-Keseluruhan-' . $periode . '.xlsx'
        );
    }
}

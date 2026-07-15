<?php

/**
 * Helper ekspor LAKIP ke Excel (.xlsx) memakai PhpSpreadsheet.
 * Dipakai oleh AdminKab\LakipController & AdminOpd\LakipOpdController.
 *
 * Dua struktur mengikuti view cetak LAKIP:
 *   - Kabupaten/OPD (LakipController)     : rows flat + lakipMap keyed target_id
 *                                           -> adminKabupaten/lakip/lakip_cetak
 *   - OPD (LakipOpdController, dataSource) : dataSource grouped by sasaran +
 *                                           lakipMap keyed indikator_id
 *                                           -> adminOpd/lakip/lakip_cetak
 *
 * Kolom & logika perhitungan capaian dijaga sama persis dengan view cetak
 * (mengikuti pola cascading_excel_helper.php).
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!function_exists('_lakip_txt')) {
    /** Bersihkan nilai sel: <br> -> newline, buang tag HTML, decode entity, trim. */
    function _lakip_txt($v): string
    {
        $s = (string) ($v ?? '');
        $s = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $s);
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim($s);
    }
}

if (!function_exists('_lakip_status_label')) {
    /** Samakan label status dengan view cetak. */
    function _lakip_status_label($status): string
    {
        $status = strtolower(trim((string) $status));
        if ($status === 'selesai') {
            return 'Selesai';
        }
        if ($status === 'draft') {
            return 'Draft';
        }
        return $status !== '' ? ucfirst($status) : '-';
    }
}

if (!function_exists('lakip_excel_stream')) {
    /**
     * Bangun & kirim file .xlsx lalu exit.
     *
     * @param string   $title    judul di baris 1
     * @param array    $metaRows pasangan label => nilai (Unit, Mode, Tahun, Status)
     * @param string[] $headers  label kolom
     * @param array    $dataRows array of array (baris data, sudah dibersihkan)
     * @param string   $filename nama file unduhan
     */
    function lakip_excel_stream(string $title, array $metaRows, array $headers, array $dataRows, string $filename): void
    {
        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('LAKIP');

        $nCol    = max(1, count($headers));
        $lastCol = Coordinate::stringFromColumnIndex($nCol);

        // --- Judul ---
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // --- Instansi (opsional dari pengaturan) ---
        $instansi = trim((string) (function_exists('setting') ? setting('instansi', '') : ''));
        if ($instansi !== '') {
            $sheet->setCellValue('A2', $instansi);
            $sheet->mergeCells("A2:{$lastCol}2");
            $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('555555');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // --- Meta (Unit / Mode / Tahun / Status) ---
        $metaStart = 4;
        $r = $metaStart;
        foreach ($metaRows as $label => $val) {
            $sheet->setCellValueExplicit("A{$r}", (string) $label . ' : ' . _lakip_txt($val), DataType::TYPE_STRING);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setSize(9);
            $r++;
        }

        // --- Header kolom ---
        $hRow = $r + 1; // satu baris kosong sebelum header
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $hRow, (string) $h);
        }
        $headerRange = "A{$hRow}:{$lastCol}{$hRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00743E');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($hRow)->setRowHeight(30);

        // --- Data ---
        $dr = $hRow + 1;
        foreach ($dataRows as $row) {
            $ci = 1;
            foreach ($row as $val) {
                $cell = Coordinate::stringFromColumnIndex($ci) . $dr;
                $sheet->setCellValueExplicit($cell, (string) $val, DataType::TYPE_STRING);
                $ci++;
            }
            $dr++;
        }
        $lastRow = max($hRow, $dr - 1);

        // --- Border, wrap, alignment ---
        $sheet->getStyle("A{$hRow}:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($lastRow > $hRow) {
            $sheet->getStyle("A" . ($hRow + 1) . ":{$lastCol}{$lastRow}")->getAlignment()
                ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }

        // --- Lebar kolom: kolom teks lebar, kolom angka sempit ---
        for ($i = 1; $i <= $nCol; $i++) {
            $h = strtoupper((string) ($headers[$i - 1] ?? ''));
            if ($h === 'NO') {
                $w = 6;
            } elseif (in_array($h, ['SASARAN', 'INDIKATOR', 'OPD'], true)) {
                $w = 40;
            } elseif (strpos($h, 'SEBELUMNYA') !== false || strpos($h, 'CAPAIAN') !== false) {
                $w = 16;
            } else {
                $w = 12;
            }
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth($w);
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

if (!function_exists('lakip_kab_excel')) {
    /**
     * Export LAKIP untuk LakipController (AdminKab): rows flat + lakipMap keyed target_id.
     * Mengikuti adminKabupaten/lakip/lakip_cetak.php.
     *
     * @param array  $rows     hasil getIndexRpjmdTargets / getIndexRenstraTargets
     * @param array  $lakipMap key = target_id
     * @param string $mode     'kabupaten' | 'opd'
     * @param array  $meta     ['unit','tahun','status'] mentah dari controller
     */
    function lakip_kab_excel(array $rows, array $lakipMap, string $mode, array $meta): void
    {
        helper(['number', 'lakip']);

        $isOpd = ($mode === 'opd');

        $headers = ['NO'];
        if ($isOpd) {
            $headers[] = 'OPD';
        }
        $headers = array_merge($headers, [
            'SASARAN', 'INDIKATOR', 'SATUAN', 'TAHUN', 'TARGET',
            'TARGET TAHUN SEBELUMNYA', 'CAPAIAN TAHUN SEBELUMNYA',
            'CAPAIAN TAHUN INI', 'CAPAIAN (%)', 'STATUS',
        ]);

        $data = [];
        $no = 1;
        foreach ($rows as $r) {
            $targetId  = (int) ($r['target_id'] ?? 0);
            $lakipItem = $lakipMap[$targetId] ?? null;
            $jenis     = $r['jenis_indikator'] ?? 'indikator positif';
            $targetNow = $r['target_tahun_ini'] ?? null;
            $realNow   = $lakipItem['capaian_tahun_ini'] ?? null;
            $targetCalc = (isset($lakipItem['target_hitung']) && $lakipItem['target_hitung'] !== '') ? $lakipItem['target_hitung'] : $targetNow;
            $realCalc   = (isset($lakipItem['capaian_hitung']) && $lakipItem['capaian_hitung'] !== '') ? $lakipItem['capaian_hitung'] : $realNow;
            $persen     = hitungCapaianLakip($targetCalc, $realCalc, $jenis);

            $row = [$no++];
            if ($isOpd) {
                $row[] = _lakip_txt($r['nama_opd'] ?? '-');
            }
            $row = array_merge($row, [
                _lakip_txt($r['sasaran'] ?? '-'),
                _lakip_txt($r['indikator_sasaran'] ?? '-'),
                _lakip_txt($r['satuan'] ?? '-'),
                _lakip_txt($r['tahun'] ?? '-'),
                _lakip_txt(($targetNow !== null && $targetNow !== '') ? $targetNow : '-'),
                _lakip_txt(($lakipItem['target_lalu'] ?? '') !== '' ? $lakipItem['target_lalu'] : '-'),
                _lakip_txt(($lakipItem['capaian_lalu'] ?? '') !== '' ? $lakipItem['capaian_lalu'] : '-'),
                formatAtauRaw($lakipItem['capaian_tahun_ini'] ?? null, 2),
                $persen === null ? '-' : (formatAngkaID($persen, 2) . '%'),
                _lakip_status_label($lakipItem['status'] ?? null),
            ]);
            $data[] = $row;
        }

        $tahun    = (string) ($meta['tahun'] ?? '');
        $statusF  = (string) ($meta['status'] ?? '');
        $metaRows = [
            'Unit'   => $meta['unit'] ?? ($isOpd ? 'Seluruh OPD' : 'Kabupaten'),
            'Mode'   => $isOpd ? 'OPD (RENSTRA)' : 'Kabupaten (RPJMD)',
            'Tahun'  => $tahun !== '' ? $tahun : '-',
            'Status' => $statusF !== '' ? _lakip_status_label($statusF) : 'Semua Status',
        ];

        $safeUnit = preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($metaRows['Unit']));
        $filename = 'LAKIP-' . trim($safeUnit, '-') . '-' . ($tahun !== '' ? $tahun : 'semua') . '.xlsx';

        lakip_excel_stream(
            'LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH',
            $metaRows,
            $headers,
            $data,
            $filename
        );
    }
}

if (!function_exists('lakip_opd_excel')) {
    /**
     * Export LAKIP untuk LakipOpdController: dataSource grouped by sasaran +
     * lakipMap keyed indikator_id. Mengikuti adminOpd/lakip/lakip_cetak.php.
     *
     * @param array  $dataSource hasil groupIndexRowsBySasaran()
     * @param array  $lakipMap   key = indikator_id
     * @param array  $meta       ['unit','mode','tahun','status'] mentah dari controller
     */
    function lakip_opd_excel(array $dataSource, array $lakipMap, array $meta): void
    {
        helper(['number', 'lakip']);

        $headers = [
            'NO', 'SASARAN', 'INDIKATOR', 'SATUAN', 'TAHUN',
            'TARGET TAHUN SEBELUMNYA', 'CAPAIAN TAHUN SEBELUMNYA',
            'TARGET', 'CAPAIAN TAHUN INI', 'CAPAIAN (%)', 'STATUS',
        ];

        $tahunAktif = (string) ($meta['tahun'] ?? '');

        $data = [];
        $no = 1;
        foreach ($dataSource as $group) {
            $sasaranText   = $group['sasaran'] ?? ($group['sasaran_rpjmd'] ?? '-');
            $indikatorList = $group['indikator_sasaran'] ?? [];

            foreach ($indikatorList as $indikator) {
                $indikatorId = (int) ($indikator['id'] ?? $indikator['indikator_id'] ?? $indikator['renstra_indikator_id'] ?? 0);
                $lakipItem   = $indikatorId ? ($lakipMap[$indikatorId] ?? null) : null;

                $tahunRow = (string) (
                    $tahunAktif !== '' ? $tahunAktif :
                    ($indikator['tahun'] ?? $group['tahun'] ?? date('Y'))
                );

                // Lookup target tahun (samakan dgn view cetak OPD)
                $targetTahun = null;
                $targets = $indikator['target_tahunan'] ?? null;
                if (is_array($targets)) {
                    $isAssoc = array_keys($targets) !== range(0, count($targets) - 1);
                    if ($isAssoc) {
                        $targetTahun = $targets[$tahunRow] ?? $targets[(int) $tahunRow] ?? null;
                    } else {
                        foreach ($targets as $t) {
                            $th = (string) ($t['tahun'] ?? $t['indikator_tahun'] ?? '');
                            if ($th === $tahunRow) {
                                $targetTahun = $t['target'] ?? $t['target_tahunan'] ?? $t['target_tahun_ini'] ?? $t['nilai_target'] ?? null;
                                break;
                            }
                        }
                    }
                }
                if ($targetTahun === null) {
                    $targetTahun = $indikator['target_tahun_ini'] ?? $indikator['target'] ?? $group['target_tahun_ini'] ?? $group['target'] ?? null;
                }

                $jenis   = $indikator['jenis_indikator'] ?? ($group['jenis_indikator'] ?? 'indikator positif');
                $realNow = $lakipItem['capaian_tahun_ini'] ?? null;
                $targetCalc = (isset($lakipItem['target_hitung']) && $lakipItem['target_hitung'] !== '') ? $lakipItem['target_hitung'] : $targetTahun;
                $realCalc   = (isset($lakipItem['capaian_hitung']) && $lakipItem['capaian_hitung'] !== '') ? $lakipItem['capaian_hitung'] : $realNow;
                $persen     = hitungCapaianLakip($targetCalc, $realCalc, $jenis);

                $data[] = [
                    $no++,
                    _lakip_txt($sasaranText),
                    _lakip_txt($indikator['indikator_sasaran'] ?? '-'),
                    _lakip_txt($indikator['satuan'] ?? '-'),
                    _lakip_txt($tahunRow),
                    _lakip_txt(($lakipItem['target_lalu'] ?? '') !== '' ? $lakipItem['target_lalu'] : '-'),
                    _lakip_txt(($lakipItem['capaian_lalu'] ?? '') !== '' ? $lakipItem['capaian_lalu'] : '-'),
                    _lakip_txt(($targetTahun !== null && $targetTahun !== '') ? $targetTahun : '-'),
                    _lakip_txt($realNow !== null && $realNow !== '' ? $realNow : '-'),
                    $persen === null ? '-' : (formatAngkaID($persen, 2) . '%'),
                    _lakip_status_label($lakipItem['status'] ?? null),
                ];
            }
        }

        $mode    = (string) ($meta['mode'] ?? 'opd');
        $statusF = (string) ($meta['status'] ?? '');
        $metaRows = [
            'Unit'   => $meta['unit'] ?? 'Perangkat Daerah',
            'Mode'   => ($mode === 'kabupaten') ? 'Kabupaten (RPJMD)' : 'OPD (RENSTRA)',
            'Tahun'  => $tahunAktif !== '' ? $tahunAktif : '-',
            'Status' => $statusF !== '' ? _lakip_status_label($statusF) : 'Semua Status',
        ];

        $safeUnit = preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($metaRows['Unit']));
        $filename = 'LAKIP-' . trim($safeUnit, '-') . '-' . ($tahunAktif !== '' ? $tahunAktif : 'semua') . '.xlsx';

        lakip_excel_stream(
            'LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH',
            $metaRows,
            $headers,
            $data,
            $filename
        );
    }
}

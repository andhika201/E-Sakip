<?php
// pdf_td_gabung()/pdf_teks(): kolom induk (Visi/Misi/Tujuan/Sasaran) TANPA
// rowspan; rowspan Visi setinggi seluruh dokumen memaksa mPDF menyusutkan
// tabel sampai tak terbaca begitu bloknya lebih tinggi dari satu halaman.
helper('pdf');

$years = $period_data['years'] ?? [];
if (!is_array($years)) {
    $years = [];
}
$yearCount = max(1, count($years));

$statusFilter = strtolower(trim((string) ($status_filter ?? '')));
$statusLabelText = '';
if ($statusFilter === 'draft') {
    $statusLabelText = 'Draft';
} elseif ($statusFilter === 'selesai') {
    $statusLabelText = 'Selesai';
}

$periodeTxt = trim((string) ($periode ?? ''));
$subjudulParts = [];
if ($periodeTxt !== '') {
    $subjudulParts[] = 'Periode ' . $periodeTxt;
}
$subjudulParts[] = 'Kabupaten (RPJMD)';

// Terapkan filter status pada daftar misi (default: semua status).
$misiList = $period_data['misi_data'] ?? [];
if ($statusFilter === 'draft' || $statusFilter === 'selesai') {
    $misiList = array_values(array_filter($misiList, static function ($m) use ($statusFilter) {
        return strtolower(trim((string) ($m['status'] ?? 'draft'))) === $statusFilter;
    }));
}

$jenisLabelFn = static function ($v): string {
    $v = strtolower(trim((string) $v));
    if ($v === 'indikator positif') {
        return 'Indikator Positif';
    }
    if ($v === 'indikator negatif') {
        return 'Indikator Negatif';
    }
    return $v !== '' ? ucwords($v) : '-';
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 8px; }
        .filter-note {
            margin: 0 0 8px;
            font-size: 8px;
            color: #526158;
            text-align: right;
        }
        table.rpjmd-print-table {
            font-size: 6.7px;
            line-height: 1.14;
        }
        table.rpjmd-print-table thead { display: table-header-group; }
        /* Tanpa zebra: kolom gabungan (tanpa garis dalam) akan tampak belang bila baris diwarnai selang-seling. */
        table.rpjmd-print-table tbody tr:nth-child(even) td { background: #fff; }
        /* Isi kolom gabungan ditulis di baris tengah grup -> rata tengah agar konsisten. */
        table.rpjmd-print-table td.vm { vertical-align: middle; }
        table.rpjmd-print-table th,
        table.rpjmd-print-table td {
            padding: 2.4px 2.6px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: top;
        }
        table.rpjmd-print-table thead th {
            font-size: 6.4px;
            line-height: 1.1;
            padding: 2.6px 2px;
            vertical-align: middle;
        }
        .text-start { text-align: left; }
        .c { text-align: center; }
        .year-cell { text-align: center; white-space: nowrap; }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Rencana Pembangunan Jangka Menengah Daerah',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => '',
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php if ($statusLabelText !== ''): ?>
        <div class="filter-note">Status: <?= esc($statusLabelText) ?></div>
    <?php endif; ?>

    <table class="pdf-table rpjmd-print-table">
        <thead>
            <tr>
                <th rowspan="2">Visi</th>
                <th rowspan="2">Misi</th>
                <th rowspan="2">Tujuan</th>
                <th rowspan="2">Indikator</th>
                <th rowspan="2">Baseline</th>
                <th colspan="<?= $yearCount ?>">Target Tujuan per Tahun</th>
                <th rowspan="2">Sasaran</th>
                <th rowspan="2">Indikator Sasaran</th>
                <th rowspan="2">Baseline Sasaran</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Jenis Indikator</th>
                <th colspan="<?= $yearCount ?>">Target Capaian per Tahun</th>
                <th rowspan="2">Kondisi Akhir</th>
            </tr>
            <tr>
                <?php foreach ($years as $y): ?>
                    <th class="year-cell"><?= esc($y) ?></th>
                <?php endforeach; ?>
                <?php foreach ($years as $y): ?>
                    <th class="year-cell"><?= esc($y) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($misiList)): ?>
                <tr>
                    <td colspan="<?= 11 + (2 * $yearCount) ?>" class="c pdf-muted">Tidak ada data RPJMD untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php
                // ---------------------------------------------------------------
                // Ratakan Visi → Misi → Tujuan → (indikator tujuan ∥ sasaran →
                // indikator sasaran) menjadi daftar baris. Kolom induk lalu dicetak
                // lewat pdf_td_gabung() tanpa rowspan: rowspan Visi/Misi setinggi
                // seluruh dokumen memaksa mPDF menyusutkan tabel sampai tak terbaca
                // begitu bloknya lebih tinggi dari satu halaman.
                // ---------------------------------------------------------------
                $baris = [];
                foreach ($misiList as $mi => $misi) {
                    $visiKey = (string) ($misi['rpjmd_visi_id'] ?? ('t:' . ($misi['visi'] ?? '-')));
                    $adaBaris = false;

                    foreach ($misi['tujuan'] ?? [] as $ti => $tujuan) {
                        $leftRows = [];
                        if (!empty($tujuan['indikator_tujuan'])) {
                            foreach ($tujuan['indikator_tujuan'] as $it) {
                                $targets = [];
                                foreach ($it['target_tahunan_tujuan'] ?? [] as $t) {
                                    $targets[(string) $t['tahun']] = $t['target_tahunan'] ?? '-';
                                }
                                $leftRows[] = [
                                    'indikator' => $it['indikator_tujuan'] ?? '-',
                                    'baseline'  => $it['baseline'] ?? '-',
                                    'targets'   => $targets,
                                ];
                            }
                        } else {
                            $leftRows[] = ['indikator' => '-', 'baseline' => '-', 'targets' => []];
                        }

                        // Tiap baris kanan membawa posisi & tinggi grup sasarannya.
                        $rightRows = [];
                        if (!empty($tujuan['sasaran'])) {
                            foreach ($tujuan['sasaran'] as $sas) {
                                if (!empty($sas['indikator_sasaran'])) {
                                    $countIs = count($sas['indikator_sasaran']);
                                    foreach (array_values($sas['indikator_sasaran']) as $idx => $is) {
                                        $targets2 = [];
                                        foreach ($is['target_tahunan'] ?? [] as $t2) {
                                            $targets2[(string) $t2['tahun']] = $t2['target_tahunan'] ?? '-';
                                        }
                                        $rightRows[] = [
                                            'sasaran'         => ['text' => ($sas['sasaran_rpjmd'] ?? '-'), 'ke' => $idx, 'jumlah' => $countIs],
                                            'indikator'       => $is['indikator_sasaran'] ?? '-',
                                            'baseline'        => $is['baseline'] ?? '-',
                                            'satuan'          => $is['satuan'] ?? '-',
                                            'jenis_indikator' => $is['jenis_indikator'] ?? '-',
                                            'targets'         => $targets2,
                                        ];
                                    }
                                } else {
                                    $rightRows[] = [
                                        'sasaran'         => ['text' => ($sas['sasaran_rpjmd'] ?? '-'), 'ke' => 0, 'jumlah' => 1],
                                        'indikator'       => '-',
                                        'baseline'        => '-',
                                        'satuan'          => '-',
                                        'jenis_indikator' => '-',
                                        'targets'         => [],
                                    ];
                                }
                            }
                        } else {
                            $rightRows[] = [
                                'sasaran'         => ['text' => '-', 'ke' => 0, 'jumlah' => 1],
                                'indikator'       => '-',
                                'baseline'        => '-',
                                'satuan'          => '-',
                                'jenis_indikator' => '-',
                                'targets'         => [],
                            ];
                        }

                        $rowCount = max(count($leftRows), count($rightRows));
                        for ($r = 0; $r < $rowCount; $r++) {
                            $baris[] = [
                                'visi'    => $visiKey,
                                'misi'    => $mi,
                                'tujuan'  => $mi . '/' . $ti,
                                'misiRow' => $misi,
                                'tujuanText' => $tujuan['tujuan_rpjmd'] ?? '-',
                                'left'    => $leftRows[$r] ?? ['indikator' => '-', 'baseline' => '-', 'targets' => []],
                                // Baris pengisi (indikator tujuan lebih banyak dari indikator sasaran):
                                // sel sasaran tetap dicetak kosong supaya kolom tidak bergeser.
                                'right'   => $rightRows[$r] ?? ['sasaran' => ['text' => '', 'ke' => 0, 'jumlah' => 1], 'indikator' => '-', 'baseline' => '-', 'satuan' => '-', 'jenis_indikator' => '-', 'targets' => []],
                            ];
                            $adaBaris = true;
                        }
                    }

                    if (!$adaBaris) {
                        $baris[] = [
                            'visi'    => $visiKey,
                            'misi'    => $mi,
                            'tujuan'  => $mi . '/-',
                            'misiRow' => $misi,
                            'tujuanText' => '-',
                            'left'    => ['indikator' => '-', 'baseline' => '-', 'targets' => []],
                            'right'   => ['sasaran' => ['text' => '-', 'ke' => 0, 'jumlah' => 1], 'indikator' => '-', 'baseline' => '-', 'satuan' => '-', 'jenis_indikator' => '-', 'targets' => []],
                        ];
                    }
                }

                // Tinggi grup per kunci & penghitung posisi.
                $tinggi = ['visi' => [], 'misi' => [], 'tujuan' => []];
                foreach ($baris as $b) {
                    foreach ($tinggi as $kunci => $_) {
                        $tinggi[$kunci][$b[$kunci]] = ($tinggi[$kunci][$b[$kunci]] ?? 0) + 1;
                    }
                }
                $posisi   = ['visi' => [], 'misi' => [], 'tujuan' => []];
                $lastYear = !empty($years) ? (string) $years[array_key_last($years)] : null;
                ?>

                <?php foreach ($baris as $b): ?>
                    <?php
                    $ke = [];
                    foreach ($posisi as $kunci => $_) {
                        $ke[$kunci] = $posisi[$kunci][$b[$kunci]] = ($posisi[$kunci][$b[$kunci]] ?? -1) + 1;
                    }
                    $left  = $b['left'];
                    $right = $b['right'];
                    $kondisiAkhir = ($lastYear !== null) ? ($right['targets'][$lastYear] ?? '-') : '-';
                    ?>
                    <tr>
                        <?= pdf_td_gabung($ke['visi'], $tinggi['visi'][$b['visi']], pdf_teks($b['misiRow']['visi'] ?? '-'), 'text-start') ?>
                        <?= pdf_td_gabung($ke['misi'], $tinggi['misi'][$b['misi']], pdf_teks($b['misiRow']['misi'] ?? '-'), 'text-start') ?>
                        <?= pdf_td_gabung($ke['tujuan'], $tinggi['tujuan'][$b['tujuan']], pdf_teks($b['tujuanText']), 'text-start') ?>

                        <td class="text-start"><?= pdf_teks($left['indikator']) ?></td>
                        <td class="c"><?= esc($left['baseline'] ?? '-') ?></td>
                        <?php foreach ($years as $y): ?>
                            <td class="year-cell"><?= esc($left['targets'][(string) $y] ?? '-') ?></td>
                        <?php endforeach; ?>

                        <?= pdf_td_gabung((int) $right['sasaran']['ke'], (int) $right['sasaran']['jumlah'], pdf_teks($right['sasaran']['text']), 'text-start') ?>

                        <td class="text-start"><?= pdf_teks($right['indikator']) ?></td>
                        <td class="c"><?= esc($right['baseline'] ?? '-') ?></td>
                        <td class="c"><?= esc($right['satuan']) ?></td>
                        <td class="text-start"><?= esc($jenisLabelFn($right['jenis_indikator'] ?? '')) ?></td>
                        <?php foreach ($years as $y): ?>
                            <td class="year-cell"><?= esc($right['targets'][(string) $y] ?? '-') ?></td>
                        <?php endforeach; ?>
                        <td class="c"><?= esc($kondisiAkhir) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

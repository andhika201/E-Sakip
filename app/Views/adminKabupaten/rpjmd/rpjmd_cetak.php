<?php
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
        table.rpjmd-print-table tr { page-break-inside: avoid; }
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
                // Pra-hitung total rowspan per VISI (gabung sel VISI walau dipakai beberapa misi).
                $visiTotals = [];
                foreach ($misiList as $mPre) {
                    $rs = 0;
                    foreach ($mPre['tujuan'] ?? [] as $tPre) {
                        $itc = !empty($tPre['indikator_tujuan']) ? count($tPre['indikator_tujuan']) : 1;
                        $sasc = 0;
                        if (!empty($tPre['sasaran'])) {
                            foreach ($tPre['sasaran'] as $sPre) {
                                $sasc += !empty($sPre['indikator_sasaran']) ? count($sPre['indikator_sasaran']) : 1;
                            }
                        } else {
                            $sasc = 1;
                        }
                        $rs += max($itc, $sasc);
                    }
                    $vk = (string) ($mPre['rpjmd_visi_id'] ?? ('t:' . ($mPre['visi'] ?? '-')));
                    $visiTotals[$vk] = ($visiTotals[$vk] ?? 0) + $rs;
                }
                $visiPrinted = [];
                ?>

                <?php foreach ($misiList as $misi): ?>
                    <?php
                    // Siapkan struktur tujuan: flatten indikator tujuan & indikator sasaran jadi baris sejajar.
                    $preparedTujuan = [];
                    $misiRowspan = 0;

                    foreach ($misi['tujuan'] ?? [] as $tujuan) {
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

                        $rightRows = [];
                        if (!empty($tujuan['sasaran'])) {
                            foreach ($tujuan['sasaran'] as $sas) {
                                if (!empty($sas['indikator_sasaran'])) {
                                    $countIs = count($sas['indikator_sasaran']);
                                    foreach ($sas['indikator_sasaran'] as $idx => $is) {
                                        $targets2 = [];
                                        foreach ($is['target_tahunan'] ?? [] as $t2) {
                                            $targets2[(string) $t2['tahun']] = $t2['target_tahunan'] ?? '-';
                                        }
                                        $rightRows[] = [
                                            'sasaran'         => ($idx === 0) ? ['text' => ($sas['sasaran_rpjmd'] ?? '-'), 'rowspan' => $countIs] : null,
                                            'indikator'       => $is['indikator_sasaran'] ?? '-',
                                            'baseline'        => $is['baseline'] ?? '-',
                                            'satuan'          => $is['satuan'] ?? '-',
                                            'jenis_indikator' => $is['jenis_indikator'] ?? '-',
                                            'targets'         => $targets2,
                                        ];
                                    }
                                } else {
                                    $rightRows[] = [
                                        'sasaran'         => ['text' => ($sas['sasaran_rpjmd'] ?? '-'), 'rowspan' => 1],
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
                                'sasaran'         => ['text' => '-', 'rowspan' => 1],
                                'indikator'       => '-',
                                'baseline'        => '-',
                                'satuan'          => '-',
                                'jenis_indikator' => '-',
                                'targets'         => [],
                            ];
                        }

                        $rowCount = max(count($leftRows), count($rightRows));
                        $preparedTujuan[] = [
                            'tujuan'    => $tujuan,
                            'leftRows'  => $leftRows,
                            'rightRows' => $rightRows,
                            'rowCount'  => $rowCount,
                        ];
                        $misiRowspan += $rowCount;
                    }

                    if ($misiRowspan < 1) {
                        $misiRowspan = 1;
                        $preparedTujuan[] = [
                            'tujuan'    => ['tujuan_rpjmd' => '-'],
                            'leftRows'  => [['indikator' => '-', 'baseline' => '-', 'targets' => []]],
                            'rightRows' => [['sasaran' => ['text' => '-', 'rowspan' => 1], 'indikator' => '-', 'baseline' => '-', 'satuan' => '-', 'jenis_indikator' => '-', 'targets' => []]],
                            'rowCount'  => 1,
                        ];
                    }

                    $visiKey = (string) ($misi['rpjmd_visi_id'] ?? ('t:' . ($misi['visi'] ?? '-')));
                    $misiCellsPrinted = false;
                    ?>

                    <?php foreach ($preparedTujuan as $block): ?>
                        <?php
                        $tujuanText = $block['tujuan']['tujuan_rpjmd'] ?? '-';
                        $rowCount   = $block['rowCount'];
                        $leftRows   = $block['leftRows'];
                        $rightRows  = $block['rightRows'];
                        $tujuanCellsPrinted = false;
                        ?>
                        <?php for ($r = 0; $r < $rowCount; $r++): ?>
                            <?php
                            $left  = $leftRows[$r] ?? ['indikator' => '-', 'baseline' => '-', 'targets' => []];
                            $right = $rightRows[$r] ?? ['sasaran' => null, 'indikator' => '-', 'baseline' => '-', 'satuan' => '-', 'jenis_indikator' => '-', 'targets' => []];
                            ?>
                            <tr>
                                <?php if (!$misiCellsPrinted): ?>
                                    <?php if (!isset($visiPrinted[$visiKey])): ?>
                                        <td class="text-start" rowspan="<?= (int) ($visiTotals[$visiKey] ?? $misiRowspan) ?>"><?= esc($misi['visi'] ?? '-') ?></td>
                                        <?php $visiPrinted[$visiKey] = true; ?>
                                    <?php endif; ?>
                                    <td class="text-start" rowspan="<?= $misiRowspan ?>"><?= esc($misi['misi'] ?? '-') ?></td>
                                    <?php $misiCellsPrinted = true; ?>
                                <?php endif; ?>

                                <?php if (!$tujuanCellsPrinted): ?>
                                    <td class="text-start" rowspan="<?= $rowCount ?>"><?= esc($tujuanText) ?></td>
                                    <?php $tujuanCellsPrinted = true; ?>
                                <?php endif; ?>

                                <td class="text-start"><?= esc($left['indikator']) ?></td>
                                <td class="c"><?= esc($left['baseline'] ?? '-') ?></td>
                                <?php foreach ($years as $y): ?>
                                    <td class="year-cell"><?= esc($left['targets'][(string) $y] ?? '-') ?></td>
                                <?php endforeach; ?>

                                <?php if (!empty($right['sasaran'])): ?>
                                    <td class="text-start" rowspan="<?= (int) $right['sasaran']['rowspan'] ?>"><?= esc($right['sasaran']['text']) ?></td>
                                <?php endif; ?>

                                <td class="text-start"><?= esc($right['indikator']) ?></td>
                                <td class="c"><?= esc($right['baseline'] ?? '-') ?></td>
                                <td class="c"><?= esc($right['satuan']) ?></td>
                                <td class="text-start"><?= esc($jenisLabelFn($right['jenis_indikator'] ?? '')) ?></td>
                                <?php foreach ($years as $y): ?>
                                    <td class="year-cell"><?= esc($right['targets'][(string) $y] ?? '-') ?></td>
                                <?php endforeach; ?>

                                <?php
                                $lastYear = !empty($years) ? (string) $years[array_key_last($years)] : null;
                                $kondisiAkhir = ($lastYear !== null) ? ($right['targets'][$lastYear] ?? '-') : '-';
                                ?>
                                <td class="c"><?= esc($kondisiAkhir) ?></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

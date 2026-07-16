<?php
$mode = $mode ?? 'opd';
$showOpdCol = ($mode === 'opd');
$dataSource = $showOpdCol ? ($renstra_data ?? []) : ($rpjmd_data ?? []);

$years = [];
if (!empty($grouped_data) && isset($grouped_data[$selected_periode])) {
    $years = $grouped_data[$selected_periode]['years'] ?? [];
    if (!is_array($years)) {
        $years = [];
    }
}
$yearCount = max(1, count($years));

$periodeTxt = '';
if (!empty($grouped_data) && isset($grouped_data[$selected_periode])) {
    $periodeTxt = trim((string) ($grouped_data[$selected_periode]['period'] ?? $selected_periode));
} else {
    $periodeTxt = trim((string) ($selected_periode ?? ''));
}

$modeLabel = $showOpdCol ? 'Per OPD (RENSTRA)' : 'Kabupaten (RPJMD)';
$opdNameTxt = trim((string) ($opd_name ?? ''));

$subjudulParts = [];
if ($opdNameTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $opdNameTxt;
}
if ($periodeTxt !== '') {
    $subjudulParts[] = 'Periode ' . $periodeTxt;
}
$subjudulParts[] = $modeLabel;

// Cari IKU yang cocok utk 1 indikator (mode opd -> renstra_id, kabupaten -> rpjmd_id).
$findIku = function (int $indikatorId) use ($iku_data, $mode) {
    if (empty($iku_data)) {
        return null;
    }
    foreach ($iku_data as $iku) {
        if ($mode === 'opd' && (int) ($iku['renstra_id'] ?? 0) === $indikatorId) {
            return $iku;
        }
        if ($mode === 'kabupaten' && (int) ($iku['rpjmd_id'] ?? 0) === $indikatorId) {
            return $iku;
        }
    }
    return null;
};

// Pra-hitung rowspan OPD & Sasaran (1 baris per indikator).
$opdRowspan = [];
$sasaranRowspan = [];
foreach ($dataSource as $row) {
    $sasaranText = ($mode === 'kabupaten')
        ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
        : ($row['sasaran'] ?? '-');
    $opdName = $row['nama_opd'] ?? '-';
    $rowsForSasaran = count($row['indikator_sasaran'] ?? []);
    $rowsForSasaran = max(1, $rowsForSasaran);
    $sasKey = $opdName . '|' . $sasaranText;
    $sasaranRowspan[$sasKey] = ($sasaranRowspan[$sasKey] ?? 0) + $rowsForSasaran;
    $opdRowspan[$opdName] = ($opdRowspan[$opdName] ?? 0) + $rowsForSasaran;
}
$printedOpd = [];
$printedSasaran = [];

$totalCols = 8 + ($showOpdCol ? 1 : 0) + $yearCount;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 9px; }
        table.iku-print-table {
            font-size: 7.6px;
            line-height: 1.18;
        }
        table.iku-print-table thead { display: table-header-group; }
        table.iku-print-table tr { page-break-inside: avoid; }
        table.iku-print-table th,
        table.iku-print-table td {
            padding: 2.8px 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }
        table.iku-print-table thead th {
            font-size: 7px;
            line-height: 1.12;
            padding: 3px 2px;
        }
        .text-start { text-align: left; }
        .c { text-align: center; }
        .year-cell { text-align: center; white-space: nowrap; }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Indikator Kinerja Utama',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => $opdNameTxt !== '' ? strtoupper($opdNameTxt) : '',
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <table class="pdf-table iku-print-table">
        <thead>
            <tr>
                <th rowspan="2" style="width:3%;">No</th>
                <?php if ($showOpdCol): ?>
                    <th rowspan="2" style="width:11%;">OPD</th>
                <?php endif; ?>
                <th rowspan="2" style="width:12%;">Sasaran</th>
                <th rowspan="2" style="width:12%;">Indikator Sasaran</th>
                <th rowspan="2" style="width:13%;">Definisi Operasional</th>
                <th rowspan="2" style="width:13%;">Formula / Rumusan Perhitungan</th>
                <th rowspan="2" style="width:5%;">Satuan</th>
                <th colspan="<?= $yearCount ?>">Target Capaian per Tahun</th>
                <th rowspan="2" style="width:10%;">Sumber Data</th>
                <th rowspan="2" style="width:9%;">Penanggung Jawab</th>
            </tr>
            <tr>
                <?php foreach ($years as $year): ?>
                    <th class="year-cell"><?= esc($year) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($selected_periode) || empty($dataSource)): ?>
                <tr>
                    <td colspan="<?= $totalCols ?>" class="c pdf-muted">Tidak ada data IKU untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($dataSource as $row): ?>
                    <?php
                    $sasaranText = ($mode === 'kabupaten')
                        ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
                        : ($row['sasaran'] ?? '-');
                    $opdName = $row['nama_opd'] ?? '-';
                    $sasKey = $opdName . '|' . $sasaranText;
                    $indikators = $row['indikator_sasaran'] ?? [];
                    ?>
                    <?php foreach ($indikators as $indikator): ?>
                        <?php
                        $indikatorId = (int) ($indikator['id'] ?? 0);
                        $iku = $findIku($indikatorId);

                        $targetMap = [];
                        if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                            foreach ($indikator['target_tahunan'] as $tYear => $tVal) {
                                $targetMap[(int) $tYear] = $tVal;
                            }
                        }

                        $definisiText = trim((string) ($iku['definisi'] ?? ''));
                        if ($definisiText === '') {
                            $definisiText = trim((string) ($indikator['definisi_op'] ?? ''));
                        }
                        ?>
                        <tr>
                            <?php if ($showOpdCol): ?>
                                <?php if (!isset($printedOpd[$opdName])): ?>
                                    <td rowspan="<?= $opdRowspan[$opdName] ?? 1 ?>" class="c"><?= $no++ ?></td>
                                    <td rowspan="<?= $opdRowspan[$opdName] ?? 1 ?>" class="text-start"><?= esc($opdName) ?></td>
                                    <?php $printedOpd[$opdName] = true; ?>
                                <?php endif; ?>
                            <?php elseif (!isset($printedSasaran[$sasKey])): ?>
                                <td rowspan="<?= $sasaranRowspan[$sasKey] ?? 1 ?>" class="c"><?= $no++ ?></td>
                            <?php endif; ?>

                            <?php if (!isset($printedSasaran[$sasKey])): ?>
                                <td rowspan="<?= $sasaranRowspan[$sasKey] ?? 1 ?>" class="text-start"><?= esc($sasaranText) ?></td>
                                <?php $printedSasaran[$sasKey] = true; ?>
                            <?php endif; ?>

                            <td class="text-start"><?= esc($indikator['indikator_sasaran'] ?? '-') ?></td>
                            <td class="text-start"><?= esc($definisiText !== '' ? $definisiText : '-') ?></td>
                            <td class="text-start"><?= esc(trim((string) ($iku['rumusan_perhitungan'] ?? '')) !== '' ? $iku['rumusan_perhitungan'] : '-') ?></td>
                            <td class="c"><?= esc($indikator['satuan'] ?? '-') ?></td>

                            <?php foreach ($years as $year): ?>
                                <?php
                                $y = (int) $year;
                                $value = '-';
                                if (isset($targetMap[$y]) && $targetMap[$y] !== '' && $targetMap[$y] !== null) {
                                    $value = $targetMap[$y];
                                }
                                ?>
                                <td class="year-cell"><?= esc($value) ?></td>
                            <?php endforeach; ?>

                            <td class="text-start"><?= esc(trim((string) ($iku['sumber_data'] ?? '')) !== '' ? $iku['sumber_data'] : '-') ?></td>
                            <td class="text-start"><?= esc(trim((string) ($iku['penanggung_jawab'] ?? '')) !== '' ? $iku['penanggung_jawab'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

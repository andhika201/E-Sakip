<?php
$namaOpdTxt = trim((string) ($nama_opd ?? ''));
$periodeTxt = trim((string) ($selected_periode ?? ''));
$subjudulParts = [];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
if ($periodeTxt !== '') {
    $subjudulParts[] = 'Periode ' . $periodeTxt;
}

$totalYears = 0;
foreach (($grouped_data ?? []) as $p) {
    $totalYears += count($p['years'] ?? []);
}
$targetYearCols = max(1, $totalYears);
$fixedColsWidth = 71;
$yearColWidth = max(4.8, (100 - $fixedColsWidth) / $targetYearCols);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 9px; }
        table.iku-print-table {
            table-layout: fixed;
            font-size: 7.8px;
            line-height: 1.18;
            width: 100%;
        }
        table.iku-print-table thead {
            display: table-header-group;
        }
        table.iku-print-table tr {
            page-break-inside: avoid;
        }
        table.iku-print-table th,
        table.iku-print-table td {
            padding: 2.8px 3px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.iku-print-table thead th {
            font-size: 7px;
            line-height: 1.12;
            padding: 3px 2px;
        }
        .text-start { text-align: left; }
        .year-head,
        .year-cell {
            white-space: nowrap;
            word-break: keep-all;
            overflow-wrap: normal;
            text-align: center;
        }
        .year-head {
            font-size: 7.1px;
        }
        .year-cell {
            font-size: 7.6px;
        }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Indikator Kinerja Utama',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => strtoupper($nama_opd ?? ''),
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <table class="pdf-table iku-print-table">
        <colgroup>
            <col style="width:3%;">
            <col style="width:12%;">
            <col style="width:13%;">
            <col style="width:13%;">
            <col style="width:13%;">
            <col style="width:5%;">
            <?php for ($i = 0; $i < $targetYearCols; $i++): ?>
                <col style="width:<?= $yearColWidth ?>%;">
            <?php endfor; ?>
            <col style="width:10%;">
            <col style="width:9%;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Sasaran</th>
                <th rowspan="2">Indikator Sasaran</th>
                <th rowspan="2">Definisi Operasional</th>
                <th rowspan="2">Formula / Rumusan Perhitungan</th>
                <th rowspan="2">Satuan</th>
                <th colspan="<?= $targetYearCols ?>">Target Capaian per Tahun</th>
                <th rowspan="2">Sumber Data</th>
                <th rowspan="2">Penanggung Jawab</th>
            </tr>
            <tr>
                <?php if (!empty($grouped_data)): ?>
                    <?php foreach ($grouped_data as $dataPeriod): ?>
                        <?php foreach (($dataPeriod['years'] ?? []) as $year): ?>
                            <th class="year-head"><?= esc($year) ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th class="year-head">-</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $dataSource = (($role ?? '') === 'admin_kab') ? ($rpjmd_data ?? []) : ($renstra_data ?? []);
            ?>
            <?php if (empty($selected_periode) || empty($dataSource)): ?>
                <tr>
                    <td colspan="<?= 8 + max(1, $totalYears) ?>" class="c pdf-muted">Tidak ada data IKU untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($dataSource as $row): ?>
                    <?php
                    $sasaranText = (($role ?? '') === 'admin_kab')
                        ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
                        : ($row['sasaran'] ?? '-');

                    $indikators = $row['indikator_sasaran'] ?? [];
                    $sasRowspan = max(1, count($indikators));
                    $sasFirst = true;
                    ?>

                    <?php foreach ($indikators as $indikator): ?>
                        <?php
                        $iku = null;
                        if (!empty($iku_data)) {
                            foreach ($iku_data as $item) {
                                // Non admin_kab (admin_opd, admin_kecamatan, dst) memakai renstra_id.
                                if (
                                    (($role ?? '') === 'admin_kab' && ($item['rpjmd_id'] ?? null) == ($indikator['id'] ?? null)) ||
                                    (($role ?? '') !== 'admin_kab' && ($item['renstra_id'] ?? null) == ($indikator['id'] ?? null))
                                ) {
                                    $iku = $item;
                                    break;
                                }
                            }
                        }

                        $targetMap = [];
                        if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                            foreach ($indikator['target_tahunan'] as $key => $target) {
                                if (is_array($target)) {
                                    $tahun = isset($target['tahun']) ? (int) $target['tahun'] : (int) $key;
                                    $nilai = $target['target_tahunan']
                                        ?? ($target['target'] ?? ($target['nilai'] ?? null));
                                } else {
                                    $tahun = (int) $key;
                                    $nilai = $target;
                                }
                                if ($tahun) {
                                    $targetMap[$tahun] = $nilai;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <?php if ($sasFirst): ?>
                                <td rowspan="<?= $sasRowspan ?>" class="c"><?= $no++ ?></td>
                                <td rowspan="<?= $sasRowspan ?>" class="text-start"><?= esc($sasaranText) ?></td>
                                <?php $sasFirst = false; ?>
                            <?php endif; ?>

                            <td class="text-start"><?= esc($indikator['indikator_sasaran'] ?? '-') ?></td>
                            <td class="text-start"><?= esc($iku['definisi'] ?? '-') ?></td>
                            <td class="text-start"><?= esc(trim((string) ($iku['rumusan_perhitungan'] ?? '')) !== '' ? $iku['rumusan_perhitungan'] : '-') ?></td>
                            <td class="c"><?= esc($indikator['satuan'] ?? '-') ?></td>

                            <?php if (!empty($grouped_data)): ?>
                                <?php foreach ($grouped_data as $dataPeriod): ?>
                                    <?php foreach (($dataPeriod['years'] ?? []) as $year): ?>
                                        <?php
                                        $y = (int) $year;
                                        $value = '-';
                                        if (isset($targetMap[$y]) && $targetMap[$y] !== '' && $targetMap[$y] !== null) {
                                            $value = $targetMap[$y];
                                        }
                                        ?>
                                        <td class="year-cell"><?= esc($value) ?></td>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <td class="year-cell">-</td>
                            <?php endif; ?>

                            <td class="text-start"><?= esc(trim((string) ($iku['sumber_data'] ?? '')) !== '' ? $iku['sumber_data'] : '-') ?></td>
                            <td class="text-start"><?= esc(($iku['penanggung_jawab'] ?? '') !== '' ? $iku['penanggung_jawab'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

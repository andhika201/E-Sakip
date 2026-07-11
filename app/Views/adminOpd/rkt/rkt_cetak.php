<?php
helper('format_helper');

$namaOpdTxt = trim((string) ($currentOpd['nama_opd'] ?? ''));
$subjudulParts = [];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
if (($filter_tahun ?? 'all') !== 'all') {
    $subjudulParts[] = 'Tahun ' . $filter_tahun;
} else {
    $subjudulParts[] = 'Semua Tahun';
}

$filterLabels = [];
if (($filter_sasaran ?? 'all') !== 'all' && !empty($sasaranList ?? [])) {
    foreach ($sasaranList as $s) {
        if ((string) ($s['id'] ?? '') === (string) $filter_sasaran) {
            $filterLabels[] = 'Indikator Sasaran: ' . ($s['indikator_sasaran'] ?? '');
            break;
        }
    }
}
if (($filter_status ?? 'all') !== 'all') {
    $filterLabels[] = 'Status: ' . ucfirst((string) $filter_status);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 9px; }
        .filter-note {
            margin: 0 0 8px;
            font-size: 8.4px;
            color: #526158;
            text-align: right;
        }
        table.rkt-print-table {
            table-layout: fixed;
            width: 100%;
            font-size: 7.2px;
            line-height: 1.16;
        }
        table.rkt-print-table thead {
            display: table-header-group;
        }
        table.rkt-print-table tr {
            page-break-inside: avoid;
        }
        table.rkt-print-table th,
        table.rkt-print-table td {
            padding: 2.6px 3px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.rkt-print-table thead th {
            font-size: 6.7px;
            line-height: 1.1;
            padding: 3px 2px;
            white-space: nowrap;
        }
        .text-start { text-align: left; }
        .nowrap { white-space: nowrap; }
        .anggaran { text-align: right; white-space: nowrap; }
        .muted-box {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 8px;
            background: #dde5df;
            color: #516158;
            font-size: 6.9px;
        }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Renja / Rencana Kerja Tahunan (RKT)',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => strtoupper($currentOpd['nama_opd'] ?? ''),
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php if (!empty($filterLabels)): ?>
        <div class="filter-note"><?= esc(implode(' | ', $filterLabels)) ?></div>
    <?php endif; ?>

    <table class="pdf-table rkt-print-table">
        <colgroup>
            <col style="width:3.5%;">
            <col style="width:6%;">
            <col style="width:12%;">
            <col style="width:13%;">
            <col style="width:12%;">
            <col style="width:12%;">
            <col style="width:12%;">
            <col style="width:14%;">
            <col style="width:6.5%;">
            <col style="width:9%;">
        </colgroup>
        <thead>
            <tr>
                <th>NO</th>
                <th>TAHUN</th>
                <th>SASARAN</th>
                <th>INDIKATOR SASARAN</th>
                <th>PROGRAM</th>
                <th>KEGIATAN</th>
                <th>SUB KEGIATAN</th>
                <th>INDIKATOR SASARAN SUB KEGIATAN</th>
                <th>TARGET</th>
                <th>TARGET ANGGARAN</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $groupedBySasaran = [];
            foreach (($rktdata ?? []) as $ind) {
                $sasaran = $ind['sasaran'] ?? 'Tanpa Sasaran';
                $groupedBySasaran[$sasaran][] = $ind;
            }
            ?>
            <?php if (empty($rktdata)): ?>
                <tr>
                    <td colspan="10" class="c pdf-muted">Tidak ada data RKT untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($groupedBySasaran as $sasaranNama => $indikators): ?>
                    <?php
                    $sasaranRowspan = 0;
                    foreach ($indikators as $indTmp) {
                        if (!empty($indTmp['rkts'])) {
                            foreach ($indTmp['rkts'] as $rktTmp) {
                                if (!empty($rktTmp['kegiatan'])) {
                                    foreach ($rktTmp['kegiatan'] as $kTmp) {
                                        $subCount = count($kTmp['subkegiatan'] ?? []);
                                        $sasaranRowspan += ($subCount > 0 ? $subCount : 1);
                                    }
                                } else {
                                    $sasaranRowspan++;
                                }
                            }
                        } else {
                            $sasaranRowspan++;
                        }
                    }

                    $firstSasaranRow = true;
                    foreach ($indikators as $ind):
                        $selectedYear = $filter_tahun ?? 'all';
                        $displayYear = '-';
                        if ($selectedYear !== 'all') {
                            $displayYear = $selectedYear;
                        } else {
                            $targetYears = $ind['target_years'] ?? [];
                            $targetYears = array_values(array_unique(array_filter($targetYears)));
                            sort($targetYears);
                            if (!empty($targetYears)) {
                                $displayYear = count($targetYears) === 1
                                    ? $targetYears[0]
                                    : (reset($targetYears) . ' - ' . end($targetYears));
                            }
                        }

                        $totalSubRows = 0;
                        if (!empty($ind['rkts'])) {
                            foreach ($ind['rkts'] as $rktTmp) {
                                if (!empty($rktTmp['kegiatan'])) {
                                    foreach ($rktTmp['kegiatan'] as $kTmp) {
                                        $subCount = count($kTmp['subkegiatan'] ?? []);
                                        $totalSubRows += ($subCount > 0 ? $subCount : 1);
                                    }
                                } else {
                                    $totalSubRows++;
                                }
                            }
                        } else {
                            $totalSubRows = 1;
                        }

                        $firstIndicatorRow = true;

                        if (empty($ind['rkts'])): ?>
                            <tr>
                                <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= $no++ ?></td>
                                <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= esc($displayYear) ?></td>
                                <?php if ($firstSasaranRow): ?>
                                    <td rowspan="<?= $sasaranRowspan ?>" class="text-start"><?= esc($sasaranNama) ?></td>
                                    <?php $firstSasaranRow = false; ?>
                                <?php endif; ?>
                                <td rowspan="<?= $totalSubRows ?>" class="text-start"><?= esc($ind['indikator_sasaran']) ?></td>
                                <td class="text-start">-</td>
                                <td class="text-start">-</td>
                                <td class="text-start">-</td>
                                <td class="text-start">-</td>
                                <td class="c">-</td>
                                <td class="anggaran">-</td>
                            </tr>
                        <?php else:
                            foreach ($ind['rkts'] as $rkt):
                                $programRows = 0;
                                if (!empty($rkt['kegiatan'])) {
                                    foreach ($rkt['kegiatan'] as $kTmp) {
                                        $subCount = count($kTmp['subkegiatan'] ?? []);
                                        $programRows += ($subCount > 0 ? $subCount : 1);
                                    }
                                } else {
                                    $programRows = 1;
                                }

                                $firstProgramRow = true;

                                if (!empty($rkt['kegiatan'])):
                                    foreach ($rkt['kegiatan'] as $keg):
                                        $subCount = count($keg['subkegiatan'] ?? []);
                                        $kegRows = ($subCount > 0 ? $subCount : 1);
                                        $firstKegRow = true;

                                        if (!empty($keg['subkegiatan'])):
                                            foreach ($keg['subkegiatan'] as $sub): ?>
                                                <tr>
                                                    <?php if ($firstIndicatorRow): ?>
                                                        <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= $no++ ?></td>
                                                        <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= esc($displayYear) ?></td>
                                                        <?php if ($firstSasaranRow): ?>
                                                            <td rowspan="<?= $sasaranRowspan ?>" class="text-start"><?= esc($sasaranNama) ?></td>
                                                            <?php $firstSasaranRow = false; ?>
                                                        <?php endif; ?>
                                                        <td rowspan="<?= $totalSubRows ?>" class="text-start"><?= esc($ind['indikator_sasaran']) ?></td>
                                                        <?php $firstIndicatorRow = false; ?>
                                                    <?php endif; ?>

                                                    <?php if ($firstProgramRow): ?>
                                                        <td rowspan="<?= $programRows ?>" class="text-start"><?= esc($rkt['program_nama'] ?? '-') ?></td>
                                                        <?php $firstProgramRow = false; ?>
                                                    <?php endif; ?>

                                                    <?php if ($firstKegRow): ?>
                                                        <td rowspan="<?= $kegRows ?>" class="text-start"><?= esc($keg['kegiatan'] ?? '-') ?></td>
                                                        <?php $firstKegRow = false; ?>
                                                    <?php endif; ?>

                                                    <td class="text-start"><?= esc($sub['sub_kegiatan'] ?? '-') ?></td>
                                                    <td class="text-start"><?= esc($sub['indikator_sasaran_sub_kegiatan'] ?? '-') ?></td>
                                                    <td class="c"><?= esc($sub['target'] ?? '-') ?></td>
                                                    <td class="anggaran"><?= formatRupiah($sub['anggaran'] ?? 0) ?></td>
                                                </tr>
                                            <?php endforeach;
                                        else: ?>
                                            <tr>
                                                <?php if ($firstIndicatorRow): ?>
                                                    <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= $no++ ?></td>
                                                    <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= esc($displayYear) ?></td>
                                                    <?php if ($firstSasaranRow): ?>
                                                        <td rowspan="<?= $sasaranRowspan ?>" class="text-start"><?= esc($sasaranNama) ?></td>
                                                        <?php $firstSasaranRow = false; ?>
                                                    <?php endif; ?>
                                                    <td rowspan="<?= $totalSubRows ?>" class="text-start"><?= esc($ind['indikator_sasaran']) ?></td>
                                                    <?php $firstIndicatorRow = false; ?>
                                                <?php endif; ?>

                                                <?php if ($firstProgramRow): ?>
                                                    <td rowspan="<?= $programRows ?>" class="text-start"><?= esc($rkt['program_nama'] ?? '-') ?></td>
                                                    <?php $firstProgramRow = false; ?>
                                                <?php endif; ?>

                                                <td rowspan="<?= $kegRows ?>" class="text-start"><?= esc($keg['kegiatan'] ?? '-') ?></td>
                                                <td class="text-start">-</td>
                                                <td class="text-start">-</td>
                                                <td class="c">-</td>
                                                <td class="anggaran">-</td>
                                            </tr>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <tr>
                                        <?php if ($firstIndicatorRow): ?>
                                            <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= $no++ ?></td>
                                            <td rowspan="<?= $totalSubRows ?>" class="c nowrap"><?= esc($displayYear) ?></td>
                                            <?php if ($firstSasaranRow): ?>
                                                <td rowspan="<?= $sasaranRowspan ?>" class="text-start"><?= esc($sasaranNama) ?></td>
                                                <?php $firstSasaranRow = false; ?>
                                            <?php endif; ?>
                                            <td rowspan="<?= $totalSubRows ?>" class="text-start"><?= esc($ind['indikator_sasaran']) ?></td>
                                            <?php $firstIndicatorRow = false; ?>
                                        <?php endif; ?>

                                        <td class="text-start"><?= esc($rkt['program_nama'] ?? '-') ?></td>
                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="c">-</td>
                                        <td class="anggaran">-</td>
                                    </tr>
                                <?php endif;
                            endforeach;
                        endif;
                    endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

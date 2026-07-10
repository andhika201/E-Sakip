<?php
$namaOpdTxt = trim((string) ($nama_opd ?? ''));
$periodeTxt = trim((string) ($tahun_mulai ?? '-')) . ' - ' . trim((string) ($tahun_akhir ?? '-'));
$subjudulParts = [];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
$subjudulParts[] = 'Periode ' . $periodeTxt;

$filters = $filters ?? [];
$filterLabels = [];
if (!empty($filters['rpjmd'])) {
    $filterLabels[] = 'Sasaran RPJMD: ' . $filters['rpjmd'];
}
if (!empty($filters['status'])) {
    $filterLabels[] = 'Status: ' . ucfirst($filters['status']);
}

$jenisLabel = static function ($v): string {
    $v = strtolower(trim((string) $v));
    if ($v === 'positif') {
        return 'Positif';
    }
    if ($v === 'negatif') {
        return 'Negatif';
    }
    return $v !== '' ? ucfirst($v) : '';
};

$start = (int) ($tahun_mulai ?? 0);
$end = (int) ($tahun_akhir ?? 0);
$yearCount = ($start > 0 && $end >= $start) ? ($end - $start + 1) : 0;
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
            font-size: 8.6px;
            color: #526158;
            text-align: right;
        }
        table.renstra-print-table {
            table-layout: fixed;
            font-size: 7.8px;
            line-height: 1.18;
        }
        table.renstra-print-table thead {
            display: table-header-group;
        }
        table.renstra-print-table tr {
            page-break-inside: avoid;
        }
        table.renstra-print-table th,
        table.renstra-print-table td {
            padding: 2.8px 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }
        table.renstra-print-table thead th {
            font-size: 7px;
            line-height: 1.12;
            padding: 3px 2px;
        }
        .text-start { text-align: left; }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Renstra Perangkat Daerah',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => strtoupper($nama_opd ?? ''),
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php if (!empty($filterLabels)): ?>
        <div class="filter-note"><?= esc(implode(' | ', $filterLabels)) ?></div>
    <?php endif; ?>

    <table class="pdf-table renstra-print-table">
        <thead>
            <tr>
                <th rowspan="2" style="width:3%;">No</th>
                <th rowspan="2" style="width:12%;">Tujuan</th>
                <th rowspan="2" style="width:11%;">Indikator Tujuan</th>
                <th colspan="<?= $yearCount ?>" style="width:<?= max(10, $yearCount * 4) ?>%;">Target Tujuan Per Tahun</th>
                <th rowspan="2" style="width:11%;">Sasaran</th>
                <th rowspan="2" style="width:12%;">Indikator Sasaran</th>
                <th rowspan="2" style="width:6%;">Satuan</th>
                <th rowspan="2" style="width:6%;">Kondisi Awal</th>
                <th colspan="<?= $yearCount ?>" style="width:<?= max(10, $yearCount * 4) ?>%;">Target Sasaran Per Tahun</th>
                <th rowspan="2" style="width:6%;">Kondisi Akhir</th>
            </tr>
            <tr>
                <?php for ($y = $start; $y <= $end; $y++): ?>
                    <th><?= $y ?></th>
                <?php endfor; ?>
                <?php for ($y = $start; $y <= $end; $y++): ?>
                    <th><?= $y ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filters['periode'] ?? '') || empty($renstra_data)): ?>
                <tr>
                    <td colspan="<?= 8 + ($yearCount * 2) ?>" class="c pdf-muted">Tidak ada data Renstra untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($renstra_data as $tujuan): ?>
                    <?php
                    $flatSas = [];
                    foreach (($tujuan['sasaran'] ?? []) as $s) {
                        foreach (($s['indikator'] ?? []) as $is) {
                            $flatSas[] = [
                                'sasaran_id' => $s['sasaran_id'],
                                'sasaran'    => $s['sasaran'],
                                'status'     => $s['status'],
                                'indikator'  => $is['indikator'],
                                'satuan'     => $is['satuan'],
                                'baseline'   => $is['baseline'] ?? '',
                                'jenis'      => $is['jenis_indikator'] ?? '',
                                'targets'    => $is['targets'] ?? [],
                            ];
                        }
                    }

                    $itCount = count($tujuan['indikator_tujuan'] ?? []);
                    $sasCount = count($flatSas);
                    $totalRow = max($itCount, $sasCount, 1);
                    $sasRowspan = [];
                    foreach ($flatSas as $fs) {
                        $rid = $fs['sasaran_id'];
                        $sasRowspan[$rid] = ($sasRowspan[$rid] ?? 0) + 1;
                    }
                    $sasPrinted = [];
                    $rowPrinted = false;
                    ?>

                    <?php for ($i = 0; $i < $totalRow; $i++): ?>
                        <tr>
                            <?php if (!$rowPrinted): ?>
                                <td rowspan="<?= $totalRow ?>" class="c"><?= $no++ ?></td>
                                <td rowspan="<?= $totalRow ?>" class="text-start"><?= esc($tujuan['tujuan'] ?? '') ?></td>
                            <?php endif; ?>

                            <?php if ($i < $itCount): ?>
                                <?php $it = $tujuan['indikator_tujuan'][$i]; ?>
                                <td class="text-start"><?= esc($it['indikator_tujuan'] ?? '') ?></td>
                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                    <td class="c"><?= esc($it['targets'][$y] ?? '') ?></td>
                                <?php endfor; ?>
                            <?php else: ?>
                                <td></td>
                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                    <td></td>
                                <?php endfor; ?>
                            <?php endif; ?>

                            <?php if ($i < $sasCount): ?>
                                <?php
                                $ss = $flatSas[$i];
                                $sid = $ss['sasaran_id'];
                                $isFirstOfSasaran = !isset($sasPrinted[$sid]);
                                if ($isFirstOfSasaran) {
                                    $sasPrinted[$sid] = true;
                                }
                                $kondisiAkhir = $ss['targets'][$end] ?? '';
                                ?>

                                <?php if ($isFirstOfSasaran): ?>
                                    <td rowspan="<?= $sasRowspan[$sid] ?>" class="text-start"><?= esc($ss['sasaran']) ?></td>
                                <?php endif; ?>

                                <td class="text-start"><?= esc($ss['indikator']) ?></td>
                                <td class="c"><?= esc($ss['satuan']) ?></td>
                                <td class="c"><?= esc($ss['baseline']) ?></td>
                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                    <td class="c"><?= esc($ss['targets'][$y] ?? '') ?></td>
                                <?php endfor; ?>
                                <td class="c"><?= esc($kondisiAkhir) ?></td>
                            <?php else: ?>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                    <td></td>
                                <?php endfor; ?>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                        <?php $rowPrinted = true; ?>
                    <?php endfor; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

<?php
$namaOpdTxt = trim((string) ($nama_opd ?? ''));
$subjudulParts = ['Matriks Cascading RPJMD -> Renstra OPD'];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
$periodeTxt = trim((string) ($tahun_mulai ?? '-')) . ' – ' . trim((string) ($tahun_akhir ?? '-'));
// Indikator diberi kode "IK" secara default (admin_kab & adminOpd). Bisa di-override false.
$subjudulParts[] = 'Periode ' . $periodeTxt;
$showKode = $showKode ?? true;
$idk = $showKode ? '<b style="color:#00743e;">IK</b> ' : '';

$cleanPdfText = static function ($value): string {
    $text = html_entity_decode(strip_tags((string) ($value ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/', ' ', $text));
};

$estimatePdfRowWeight = static function (array $row) use ($cleanPdfText): int {
    $columns = [
        ['tujuan_rpjmd', 28],
        ['sasaran_rpjmd', 28],
        ['renstra_tujuan', 28],
        ['indikator_tujuan', 26],
        ['renstra_sasaran', 28],
        ['indikator_sasaran', 28],
        ['es3_sasaran', 34],
        ['es3_indikator', 38],
        ['es4_sasaran', 52],
        ['es4_indikator', 46],
    ];

    $maxLines = 1;
    foreach ($columns as [$field, $charsPerLine]) {
        $text = $cleanPdfText($row[$field] ?? '');
        if ($text === '') {
            continue;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        $maxLines = max($maxLines, (int) ceil($length / $charsPerLine));
    }

    return max(1, min(5, $maxLines));
};

$buildPdfPages = static function (array $sourceRows) use ($estimatePdfRowWeight): array {
    $pages = [];
    $current = [];
    $usedBudget = 0;
    $pageIndex = 0;

    foreach ($sourceRows as $row) {
        $budget = $pageIndex === 0 ? 22 : 34;
        $maxRows = $pageIndex === 0 ? 14 : 22;
        $weight = $estimatePdfRowWeight($row);

        if (!empty($current) && (count($current) >= $maxRows || $usedBudget + $weight > $budget)) {
            $pages[] = $current;
            $current = [];
            $usedBudget = 0;
            $pageIndex++;
        }

        $current[] = $row;
        $usedBudget += $weight;
    }

    if (!empty($current)) {
        $pages[] = $current;
    }

    return $pages;
};

$buildPdfMeta = static function (array $pageRows): array {
    $names = [
        'tujuan',
        'sasaran',
        'tujuan_renstra',
        'indikator_tujuan',
        'sasaran_renstra',
        'indikator',
        'es3',
        'es3_indikator',
        'es4',
    ];
    $rowspan = [];
    $firstShow = [];
    foreach ($names as $name) {
        $rowspan[$name] = [];
        $firstShow[$name] = [];
    }

    foreach ($pageRows as $index => $r) {
        $es3Key = !empty($r['es3_id']) ? $r['es3_id'] : null;
        $es3IndikatorKey = !empty($r['es3_id']) ? $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null) : null;
        $es4Key = !empty($r['es4_id']) ? $r['es4_id'] : null;
        $keys = [
            'tujuan' => $r['tujuan_id'] ?? ('empty_tujuan_' . $index),
            'sasaran' => $r['sasaran_id'] ?? ('empty_sasaran_' . $index),
            'tujuan_renstra' => $r['renstra_tujuan_id'] ?? ('empty_rt_' . $index),
            'indikator_tujuan' => $r['indikator_tujuan_id'] ?? ('empty_it_' . ($r['renstra_tujuan_id'] ?? $index)),
            'sasaran_renstra' => $r['renstra_sasaran_id'] ?? ('empty_rs_' . $index),
            'indikator' => $r['indikator_id'] ?? ('empty_ri_' . $index),
            'es3' => $es3Key,
            'es3_indikator' => $es3IndikatorKey,
            'es4' => $es4Key,
        ];

        foreach ($keys as $name => $key) {
            if ($key === null || $key === '') {
                continue;
            }

            if (!isset($rowspan[$name][$key])) {
                $rowspan[$name][$key] = 0;
                $firstShow[$name][$key] = $index;
            }
            $rowspan[$name][$key]++;
        }
    }

    return [$rowspan, $firstShow];
};

$pdfPages = empty($rows) ? [[]] : $buildPdfPages($rows);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        td.center, th.center { text-align: center; }
        .nowrap { white-space: nowrap; }
        td.dash { text-align: center; color: #aaa; }
        .pdf-continuation { text-align: right; color: #666; font-size: 7.2px; margin: 0 0 3px; }
        table.cascading-print-table {
            table-layout: fixed;
            font-size: 7.1px;
            line-height: 1.18;
            page-break-inside: auto;
        }
        table.cascading-print-table thead { display: table-header-group; }
        table.cascading-print-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table.cascading-print-table th,
        table.cascading-print-table td {
            padding: 2.4px 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.cascading-print-table thead th {
            font-size: 6.9px;
            line-height: 1.12;
            padding: 3px 2px;
            letter-spacing: 0;
        }
    </style>
</head>

<body>
    <?php $this->setData([ // param kop via data-share (include options tak diteruskan CI4)
        'judul'      => 'Cascading Kinerja Perangkat Daerah',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => strtoupper($nama_opd ?? ''),
        'logoOnly'   => false,   // tampilkan teks instansi (kop profesional)
        'hideAksara' => true,    // hanya lambang Kabupaten; AKSARA jadi watermark
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php foreach ($pdfPages as $pageIndex => $pageRows): ?>
        <?php if ($pageIndex > 0): ?>
            <pagebreak />
            <div class="pdf-continuation">Lanjutan tabel Cascading Kinerja Perangkat Daerah</div>
        <?php endif; ?>
        <?php [$pageRowspan, $pageFirstShow] = $buildPdfMeta($pageRows); ?>
        <table class="pdf-table cascading-print-table">
            <colgroup>
                <col style="width:7%;">
                <col style="width:7%;">
                <col style="width:8%;">
                <col style="width:7%;">
                <col style="width:8%;">
                <col style="width:8%;">
                <col style="width:10%;">
                <col style="width:12%;">
                <col style="width:18%;">
                <col style="width:15%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Tujuan RPJMD</th>
                    <th>Sasaran RPJMD</th>
                    <th>Tujuan Renstra</th>
                    <th>Indikator Tujuan</th>
                    <th><?= casc_relabel('Sasaran ESS II') ?></th>
                    <th><?= casc_relabel('Indikator ESS II') ?></th>
                    <th><?= casc_relabel('Sasaran ESS III') ?></th>
                    <th><?= casc_relabel('Indikator ESS III') ?></th>
                    <th><?= casc_relabel('Sasaran ESS IV/JF') ?></th>
                    <th><?= casc_relabel('Indikator ESS IV') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="10" class="c pdf-muted">Tidak ada data cascading OPD.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pageRows as $index => $r): ?>
                    <tr>
                        <?php if (($pageFirstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $pageRowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>">
                                <?= !empty($r['tujuan_rpjmd']) ? nl2br(esc($r['tujuan_rpjmd'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($pageFirstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $pageRowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>">
                                <?= !empty($r['sasaran_rpjmd']) ? nl2br(esc($r['sasaran_rpjmd'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($pageFirstShow['tujuan_renstra'][$r['renstra_tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $pageRowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?? 1 ?>">
                                <?= !empty($r['renstra_tujuan']) ? nl2br(esc($r['renstra_tujuan'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($pageFirstShow['indikator_tujuan'][$r['indikator_tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $pageRowspan['indikator_tujuan'][$r['indikator_tujuan_id']] ?? 1 ?>">
                                <?= !empty($r['indikator_tujuan']) ? $idk . nl2br(esc($r['indikator_tujuan'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($pageFirstShow['sasaran_renstra'][$r['renstra_sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $pageRowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?? 1 ?>">
                                <?= !empty($r['renstra_sasaran']) ? nl2br(esc($r['renstra_sasaran'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($pageFirstShow['indikator'][$r['indikator_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $pageRowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                <?= !empty($r['indikator_sasaran']) ? $idk . nl2br(esc($r['indikator_sasaran'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (empty($r['es3_id'])): ?>
                            <?php if (($pageFirstShow['indikator'][$r['indikator_id']] ?? -1) == $index): ?>
                                <td colspan="4" class="dash">-</td>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (($pageFirstShow['es3'][$r['es3_id']] ?? -1) == $index): ?>
                                <td rowspan="<?= $pageRowspan['es3'][$r['es3_id']] ?? 1 ?>">
                                    <?= !empty($r['es3_sasaran']) ? nl2br(esc($r['es3_sasaran'])) : '-' ?>
                                </td>
                            <?php endif; ?>

                            <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                            <?php if (($pageFirstShow['es3_indikator'][$key] ?? -1) == $index): ?>
                                <td rowspan="<?= $pageRowspan['es3_indikator'][$key] ?? 1 ?>">
                                    <?= !empty($r['es3_indikator']) ? $idk . nl2br(esc($r['es3_indikator'])) : '-' ?>
                                </td>
                            <?php endif; ?>

                            <?php if (empty($r['es4_id'])): ?>
                                <?php if (($pageFirstShow['es3_indikator'][$key] ?? -1) == $index): ?>
                                    <td colspan="2" class="dash">-</td>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (($pageFirstShow['es4'][$r['es4_id']] ?? -1) == $index): ?>
                                    <td rowspan="<?= $pageRowspan['es4'][$r['es4_id']] ?? 1 ?>">
                                        <?= !empty($r['es4_sasaran']) ? nl2br(esc($r['es4_sasaran'])) : '-' ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= !empty($r['es4_indikator']) ? $idk . nl2br(esc($r['es4_indikator'])) : '-' ?></td>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</body>

</html>

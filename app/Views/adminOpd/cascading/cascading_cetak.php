<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cascading Kinerja OPD</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            background: #fff;
        }

        /* ===== HEADER ===== */
        .doc-header {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 3px solid #1565c0;
        }
        .doc-header .kop-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0d47a1;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-header .kop-sub {
            font-size: 10pt;
            color: #1565c0;
            margin-top: 3px;
        }
        .doc-header .kop-periode {
            font-size: 9pt;
            color: #555;
            margin-top: 4px;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #555;
            padding: 5px 6px;
            vertical-align: middle;
            font-size: 8.5pt;
        }

        thead th {
            background-color: #1565c0;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
        }

        tbody tr:nth-child(even) td {
            background-color: #e3f2fd;
        }

        td {
            vertical-align: top;
            text-align: left;
        }

        td.center {
            text-align: center;
            vertical-align: middle;
        }

        .dash {
            text-align: center;
            color: #999;
        }

        /* ===== PAGE BREAK ===== */
        tr { page-break-inside: avoid; }

        /* ===== FOOTER ===== */
        .doc-footer {
            margin-top: 16px;
            font-size: 8pt;
            color: #777;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="doc-header">
        <div class="kop-title">Cascading Kinerja OPD</div>
        <div class="kop-sub">Matriks Cascading RPJMD &rarr; Renstra OPD</div>
        <div class="kop-periode">Periode: <?= esc($tahun_mulai ?? '-') ?> &ndash; <?= esc($tahun_akhir ?? '-') ?></div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th style="width:9%">Tujuan RPJMD</th>
                <th style="width:9%">Sasaran RPJMD</th>
                <th style="width:10%">Tujuan Renstra</th>
                <th style="width:10%">Sasaran ESS II</th>
                <th style="width:12%">Indikator ESS II</th>
                <th style="width:10%">Sasaran ESS III</th>
                <th style="width:12%">Indikator ESS III</th>
                <th style="width:10%">Sasaran ESS IV/JF</th>
                <th style="width:12%">Indikator ESS IV</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="9" class="center">Tidak ada data cascading OPD.</td>
            </tr>
            <?php else: ?>

            <?php foreach ($rows as $index => $r): ?>
            <tr>
                <!-- TUJUAN RPJMD -->
                <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>">
                    <?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <!-- SASARAN RPJMD -->
                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>">
                    <?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <!-- TUJUAN RENSTRA -->
                <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>">
                    <?= nl2br(esc($r['renstra_tujuan'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <!-- SASARAN ESS II (Renstra Sasaran) -->
                <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>">
                    <?= nl2br(esc($r['renstra_sasaran'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <!-- INDIKATOR SASARAN (ESS II) -->
                <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['indikator_sasaran'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <!-- ESS III -->
                <?php if (empty($r['es3_id'])): ?>
                    <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                    <td colspan="2" class="dash">-</td>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                    <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>">
                        <?= nl2br(esc($r['es3_sasaran'] ?? '-')) ?>
                    </td>
                    <?php endif; ?>

                    <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                    <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                    <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>">
                        <?= nl2br(esc($r['es3_indikator'] ?? '-')) ?>
                    </td>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ESS IV / JF -->
                <?php if (!empty($r['es3_id']) && empty($r['es4_id'])): ?>
                    <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                    <td colspan="2" class="dash">-</td>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                    <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>">
                        <?= nl2br(esc($r['es4_sasaran'] ?? '-')) ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <?= $r['es4_indikator'] ? nl2br(esc($r['es4_indikator'])) : '-' ?>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>

            <?php endif; ?>
        </tbody>
    </table>

    <div class="doc-footer">
        Dicetak pada: <?= date('d/m/Y H:i') ?>
    </div>

</body>
</html>

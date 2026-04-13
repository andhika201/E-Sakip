<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cascading Kinerja Kabupaten</title>
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
            border-bottom: 3px solid #2e7d32;
        }
        .doc-header .kop-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1b5e20;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-header .kop-sub {
            font-size: 10pt;
            color: #2e7d32;
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

        thead tr:first-child th {
            background-color: #1b5e20;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
        }

        thead tr:last-child th {
            background-color: #388e3c;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }

        tbody tr:nth-child(even) td {
            background-color: #f1f8e9;
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
        <div class="kop-title">Cascading Kinerja Kabupaten</div>
        <div class="kop-sub">Matriks Cascading RPJMD &rarr; Program OPD</div>
        <?php if (!empty($years)): ?>
        <div class="kop-periode">Periode: <?= esc(min($years)) ?> &ndash; <?= esc(max($years)) ?></div>
        <?php endif; ?>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width:14%">Tujuan RPJMD</th>
                <th rowspan="2" style="width:14%">Sasaran RPJMD</th>
                <th rowspan="2" style="width:16%">Indikator Sasaran</th>
                <th rowspan="2" style="width:6%">Satuan</th>
                <th rowspan="2" style="width:7%">Baseline</th>
                <th colspan="<?= count($years) ?>" style="text-align:center">Target Per Tahun</th>
                <th rowspan="2" style="width:16%">Program</th>
                <th rowspan="2" style="width:13%">OPD</th>
            </tr>
            <tr>
                <?php foreach ($years as $y): ?>
                <th style="width:<?= round(14 / count($years), 1) ?>%"><?= esc($y) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= 7 + count($years) ?>" class="center">Tidak ada data cascading.</td>
            </tr>
            <?php else: ?>

            <?php foreach ($rows as $index => $r): ?>
            <tr>
                <?php if (($firstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <?php if (($firstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <?php if (($firstShow['indikator'][$r['indikator_id']] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['indikator_sasaran'] ?? '-')) ?>
                </td>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="center">
                    <?= esc($r['satuan'] ?? '-') ?>
                </td>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="center">
                    <?= esc($r['baseline'] ?? '-') ?>
                </td>
                <?php foreach ($years as $y): ?>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="center">
                    <?= esc($r['targets'][$y] ?? '-') ?>
                </td>
                <?php endforeach; ?>
                <?php endif; ?>

                <td><?= nl2br(esc($r['program_kegiatan'] ?? '-')) ?></td>

                <?php
                $opdKey = $r['indikator_id'] . '-' . $r['nama_opd'];
                ?>
                <?php if (($firstShow['opd'][$opdKey] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['opd'][$opdKey] ?? 1 ?>">
                    <?= esc($r['nama_opd'] ?? '-') ?>
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

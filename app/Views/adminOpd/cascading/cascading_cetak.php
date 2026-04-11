<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Cascading OPD</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
        }
        .header-title {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
            text-align: center;
        }
        th {
            background-color: #d1e7dd;
        }
        h3, h4, p {
            margin: 5px 0;
            padding: 0;
            text-align: center;
        }
        .text-start {
            text-align: left;
        }
    </style>
</head>
<body>

    <div class="header-title">
        <h4>CASCADING KINERJA OPD</h4>
        <p>Periode: <?= esc($tahun_mulai) ?> - <?= esc($tahun_akhir) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tujuan RPJMD</th>
                <th>Sasaran RPJMD</th>
                <th>Tujuan RENSTRA</th>

                <!-- CSF ESS II Dihapus -->
                <th>Sasaran ESS II</th>
                <th>Indikator ESS II</th>

                <!-- CSF ESS III Dihapus -->
                <th>Sasaran ESS III</th>
                <th>Indikator ESS III</th>

                <th>Sasaran ESS IV / JF</th>
                <th>Indikator ESS IV</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $r): ?>
                <tr>
                    <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                        <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>">
                            <?= nl2br(esc($r['tujuan_rpjmd'])) ?>
                        </td>
                    <?php endif; ?>

                    <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                        <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>">
                            <?= nl2br(esc($r['sasaran_rpjmd'])) ?>
                        </td>
                    <?php endif; ?>

                    <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                        <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>">
                            <?= nl2br(esc($r['renstra_tujuan'])) ?>
                        </td>
                    <?php endif; ?>

                    <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                        <!-- CSF ESS II dihapus -->
                        <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>">
                            <?= nl2br(esc($r['renstra_sasaran'])) ?>
                        </td>
                    <?php endif; ?>

                    <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                        <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                            <?= nl2br(esc($r['indikator_sasaran'] ?? '-')) ?>
                        </td>
                    <?php endif; ?>

                    <?php if (empty($r['es3_id'])): ?>
                        <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                            <td colspan="2" style="text-align: center; color: #666;">-</td>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                            <!-- CSF ESS III dihapus -->
                            <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>">
                                <?= nl2br(esc($r['es3_sasaran'])) ?>
                            </td>
                        <?php endif; ?>

                        <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                        <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                            <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>">
                                <?= nl2br(esc($r['es3_indikator'])) ?>
                            </td>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($r['es3_id']) && empty($r['es4_id'])): ?>
                        <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                            <td colspan="2" style="text-align: center; color: #666;">-</td>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                            <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>">
                                <?= nl2br(esc($r['es4_sasaran'])) ?>
                            </td>
                        <?php endif; ?>
                        <td>
                            <?= $r['es4_indikator'] ? nl2br(esc($r['es4_indikator'])) : '-' ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>

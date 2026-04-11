<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Target & Rencana Aksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        h2, h3 {
            text-align: center;
            margin: 5px 0;
            color: #000;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }
        th {
            background-color: #d1e7dd;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

    <?php
    // mode bisa 'opd' atau 'kabupaten'
    $modeText = ($mode === 'opd') ? 'BERDASARKAN RENSTRA (OPD)' : 'BERDASARKAN RPJMD (KABUPATEN)';
    if ($mode === 'opd' && !empty($opdFilter)) {
        // Cari nama OPD jika ada filternya
        // Tapi kita bisa langsung loop dari $grouped jika isinya hanya satu OPD
        // Lebih seragam jika menampilkan string konstan.
    }
    ?>
    <h2>TARGET DAN RENCANA AKSI</h2>
    <h3><?= $modeText ?></h3>
    <h3>Tahun: <?= esc($tahun) ?></h3>

    <?php $showOpdCol = ($mode === 'opd' && $opdFilter === null); ?>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <?php if ($showOpdCol): ?>
                    <th rowspan="2">OPD</th>
                <?php endif; ?>
                <th rowspan="2">Sasaran</th>
                <th rowspan="2">Indikator</th>
                <th rowspan="2">Tahun</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Rencana Aksi</th>
                <th rowspan="2">Baseline /<br>Target Tahunan</th>
                <th colspan="4">Target Triwulan</th>
                <th rowspan="2">Penanggung Jawab</th>
            </tr>
            <tr>
                <th>I</th>
                <th>II</th>
                <th>III</th>
                <th>IV</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($grouped)): ?>
                <?php $no = 1; ?>
                
                <?php if ($mode === 'opd'): ?>
                    <!-- ===================== MODE RENSTRA ===================== -->
                    <?php if ($showOpdCol): ?>
                        <!-- RENSTRA + Semua OPD (group = OPD -> Sasaran) -->
                        <?php foreach ($grouped as $opdName => $sasGroups): ?>
                            <?php
                            $opdRowspan = 0;
                            foreach ($sasGroups as $rowsTmp) {
                                $opdRowspan += count($rowsTmp);
                            }
                            $printedOpd = false;
                            ?>

                            <?php foreach ($sasGroups as $sasaran => $rows): ?>
                                <?php
                                $sasRowspan = count($rows);
                                $printedSasaran = false;
                                ?>

                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>

                                        <!-- OPD -->
                                        <?php if (!$printedOpd): ?>
                                            <td rowspan="<?= $opdRowspan ?>" class="text-left">
                                                <?= esc($opdName) ?>
                                            </td>
                                            <?php $printedOpd = true; ?>
                                        <?php endif; ?>

                                        <!-- Sasaran -->
                                        <?php if (!$printedSasaran): ?>
                                            <td rowspan="<?= $sasRowspan ?>" class="text-left">
                                                <strong><?= esc($sasaran) ?></strong>
                                            </td>
                                            <?php $printedSasaran = true; ?>
                                        <?php endif; ?>

                                        <!-- Data -->
                                        <td class="text-left"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($row['satuan'] ?? '-') ?></td>
                                        <td class="text-left"><?= nl2br(esc($row['rencana_aksi'] ?? '-')) ?></td>
                                        <td class="text-center"><?= esc($row['indikator_target'] ?? '-') ?></td>
                                        
                                        <td class="text-center"><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                        
                                        <td class="text-left"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- RENSTRA + OPD tertentu (group = Sasaran) -->
                        <?php foreach ($grouped as $sasaran => $rows): ?>
                            <?php
                            $rowspan = count($rows);
                            $printed = false;
                            ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>

                                    <!-- Sasaran -->
                                    <?php if (!$printed): ?>
                                        <td rowspan="<?= $rowspan ?>" class="text-left">
                                            <strong><?= esc($sasaran) ?></strong>
                                        </td>
                                        <?php $printed = true; ?>
                                    <?php endif; ?>

                                    <!-- Data -->
                                    <td class="text-left"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc($row['satuan'] ?? '-') ?></td>
                                    <td class="text-left"><?= nl2br(esc($row['rencana_aksi'] ?? '-')) ?></td>
                                    <td class="text-center"><?= esc($row['indikator_target'] ?? '-') ?></td>
                                    
                                    <td class="text-center"><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                    
                                    <td class="text-left"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- ===================== MODE KABUPATEN (RPJMD) ===================== -->
                    <?php foreach ($grouped as $sasaran => $rows): ?>
                        <?php
                        $rowspan = count($rows);
                        $printed = false;
                        ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>

                                <!-- Sasaran RPJMD -->
                                <?php if (!$printed): ?>
                                    <td rowspan="<?= $rowspan ?>" class="text-left">
                                        <strong><?= esc($sasaran) ?></strong>
                                    </td>
                                    <?php $printed = true; ?>
                                <?php endif; ?>

                                <!-- Data -->
                                <td class="text-left"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($row['satuan'] ?? '-') ?></td>
                                <td class="text-left"><?= nl2br(esc($row['rencana_aksi'] ?? '-')) ?></td>
                                <td class="text-center"><?= esc($row['target_tahunan'] ?? $row['indikator_target'] ?? '-') ?></td>
                                
                                <td class="text-center"><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                <td class="text-center"><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                
                                <td class="text-left"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <tr>
                    <td colspan="<?= $showOpdCol ? 13 : 12 ?>" class="text-center" style="padding:20px;">
                        Data tidak ditemukan.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

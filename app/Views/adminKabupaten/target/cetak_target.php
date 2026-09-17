<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Target & Rencana Aksi</title>
    <?= $this->include('templates/pdf_style') ?>
    <style>
        td.text-center, th.text-center { text-align: center; }
        td.text-left, th.text-left { text-align: left; }
        /* Tanpa zebra: kolom gabungan (tanpa garis dalam) akan tampak belang bila baris diwarnai selang-seling. */
        table.pdf-table tbody tr:nth-child(even) td { background: #fff; }
        table.pdf-table td.vm { vertical-align: middle; }
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
    <?= $this->include('templates/pdf_kop', [
        'judul'    => 'Target dan Rencana Aksi',
        'subjudul' => $modeText . ' · Tahun ' . esc($tahun),
        'namaUnit' => 'Kabupaten Pringsewu',
    ]) ?>

    <?php
    $showOpdCol = ($mode === 'opd' && $opdFilter === null);
    // pdf_td_gabung()/pdf_teks(): kolom OPD/Sasaran TANPA rowspan supaya mPDF
    // bebas memotong halaman di baris mana pun (lihat app/Helpers/pdf_helper.php).
    helper('pdf');
    ?>

    <table class="pdf-table">
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
                <th rowspan="2">Target Tahunan</th>
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
                            $opdKe = 0; // posisi baris di dalam grup OPD
                            ?>

                            <?php foreach ($sasGroups as $sasaran => $rows): ?>
                                <?php $sasRowspan = count($rows); ?>

                                <?php foreach (array_values($rows) as $sasKe => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>

                                        <!-- OPD -->
                                        <?= pdf_td_gabung($opdKe++, $opdRowspan, pdf_teks($opdName), 'text-left') ?>

                                        <!-- Sasaran -->
                                        <?= pdf_td_gabung($sasKe, $sasRowspan, '<strong>' . pdf_teks($sasaran) . '</strong>', 'text-left') ?>

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
                            ?>
                            <?php foreach (array_values($rows) as $sasKe => $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>

                                    <!-- Sasaran -->
                                    <?= pdf_td_gabung($sasKe, $rowspan, '<strong>' . pdf_teks($sasaran) . '</strong>', 'text-left') ?>

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
                        ?>
                        <?php foreach (array_values($rows) as $sasKe => $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>

                                <!-- Sasaran RPJMD -->
                                <?= pdf_td_gabung($sasKe, $rowspan, '<strong>' . pdf_teks($sasaran) . '</strong>', 'text-left') ?>

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

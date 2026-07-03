<?php
$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$eselonLabel = function ($pkJenis) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Camat (Eselon III)', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    return $map[$pkJenis] ?? '-';
};
// Subjudul mengikuti filter eselon bila dipilih
$eselonTeks = (!empty($eselon)) ? $eselonLabel($eselon) : 'Eselon II / III / IV';
$judul    = $isBupati ? 'Monitoring Capaian Rencana Aksi Perjanjian Kinerja Bupati'
                      : 'Monitoring Capaian Rencana Aksi Perjanjian Kinerja ' . $eselonTeks;
$toNum = function ($v) {
    if ($v === null || $v === '') return null;
    $v = str_replace(',', '.', (string) $v);
    return is_numeric($v) ? (float) $v : null;
};
$pdfCols = $isOpd ? 12 : 11;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
</head>

<body>
    <?= $this->include('templates/pdf_kop', [
        'judul'    => $judul,
        'subjudul' => 'Tahun ' . esc($tahun ?? 'Semua'),
        'namaUnit' => strtoupper($namaUnit ?? 'Kabupaten Pringsewu'),
    ]) ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <?php if ($isOpd): ?><th rowspan="2">Pejabat (Eselon)</th><?php endif; ?>
                <th rowspan="2">Sasaran</th>
                <th rowspan="2">Indikator</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Target</th>
                <th colspan="4">Realisasi / Capaian Triwulan</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">%</th>
            </tr>
            <tr>
                <th class="c">I</th><th class="c">II</th><th class="c">III</th><th class="c">IV</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($grouped)): ?>
                <?php $no = 1; ?>
                <?php foreach ($grouped as $rows): ?>
                    <?php
                    $rowspan = count($rows);
                    $printed = false;
                    $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                    ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $target = $toNum($row['indikator_target'] ?? null);
                        $total  = $toNum($row['monev_total'] ?? null);
                        $pct    = ($target && $total !== null) ? round($total / $target * 100, 1) . '%' : '-';
                        ?>
                        <tr>
                            <td class="c"><?= $no++ ?></td>
                            <?php if (!$printed): ?>
                                <?php if ($isOpd): ?>
                                    <td rowspan="<?= $rowspan ?>"><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?><br><small>(<?= esc($eselonLabel($rows[0]['pk_jenis'] ?? '')) ?>)</small></td>
                                <?php endif; ?>
                                <td rowspan="<?= $rowspan ?>"><?= esc($sasaran) ?></td>
                                <?php $printed = true; ?>
                            <?php endif; ?>
                            <td><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['satuan'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['indikator_target'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['capaian_triwulan_1'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['capaian_triwulan_2'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['capaian_triwulan_3'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['capaian_triwulan_4'] ?? '-') ?></td>
                            <td class="c"><?= esc($row['monev_total'] ?? '-') ?></td>
                            <td class="c"><?= esc($pct) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="c pdf-muted" colspan="<?= $pdfCols ?>">Belum ada data Rencana Aksi / MONEV PK.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

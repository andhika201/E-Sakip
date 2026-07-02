<?php
$periodeTxt = !empty($years) ? (min($years) . ' – ' . max($years)) : '-';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        td.center, th.center { text-align: center; }
        .nowrap { white-space: nowrap; }
    </style>
</head>

<body>
    <?php $this->setData(['logoOnly' => true]); // kop logo saja (include options tak diteruskan CI4, harus via data share) ?>
    <?= $this->include('templates/pdf_kop', [
        'judul'    => 'Cascading Kinerja Kabupaten',
        'subjudul' => 'Matriks Cascading RPJMD → Program Perangkat Daerah · Periode ' . esc($periodeTxt),
        'namaUnit' => 'Kabupaten Pringsewu',
        'logoOnly' => true,
    ]) ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th rowspan="2" style="min-width:150px;">Tujuan RPJMD</th>
                <th rowspan="2" style="min-width:150px;">Sasaran RPJMD</th>
                <th rowspan="2" style="min-width:150px;">Indikator Sasaran</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Baseline</th>
                <th colspan="<?= count($years) ?>">Target Per Tahun</th>
                <th rowspan="2" style="min-width:170px;">Program</th>
                <th rowspan="2" style="min-width:150px;">OPD</th>
            </tr>
            <tr>
                <?php foreach ($years as $y): ?>
                    <th class="c nowrap"><?= esc($y) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= 7 + count($years) ?>" class="c pdf-muted">Tidak ada data cascading.</td>
                </tr>
            <?php else: ?>
                <?php // Nilai diulang tiap baris (tanpa rowspan) agar mpdf memecah tabel antar-halaman dengan benar. ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['indikator_sasaran'] ?? '-')) ?></td>
                        <td class="c nowrap"><?= esc($r['satuan'] ?? '-') ?></td>
                        <td class="c nowrap"><?= esc($r['baseline'] ?? '-') ?></td>
                        <?php foreach ($years as $y): ?>
                            <td class="c nowrap"><?= esc($r['targets'][$y] ?? '-') ?></td>
                        <?php endforeach; ?>
                        <td><?= nl2br(esc($r['program_kegiatan'] ?? '-')) ?></td>
                        <td><?= esc($r['nama_opd'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

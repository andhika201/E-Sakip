<?php
$periodeTxt = trim((string) ($tahun_mulai ?? '-')) . ' – ' . trim((string) ($tahun_akhir ?? '-'));
// Indikator diberi kode "IK" secara default (admin_kab & adminOpd). Bisa di-override false.
$showKode = $showKode ?? true;
$idk = $showKode ? '<b style="color:#00743e;">IK</b> ' : '';
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
    </style>
</head>

<body>
    <?php $this->setData(['logoOnly' => true]); // kop logo saja (include options tak diteruskan CI4, harus via data share) ?>
    <?= $this->include('templates/pdf_kop', [
        'judul'    => 'Cascading Kinerja Perangkat Daerah',
        'subjudul' => 'Matriks Cascading RPJMD → Renstra OPD · Periode ' . esc($periodeTxt),
        'namaUnit' => strtoupper($nama_opd ?? ''),
        'logoOnly' => true,
    ]) ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th style="min-width:120px;">Tujuan RPJMD</th>
                <th style="min-width:120px;">Sasaran RPJMD</th>
                <th style="min-width:120px;">Tujuan Renstra</th>
                <th style="min-width:120px;">Sasaran ESS II</th>
                <th style="min-width:120px;">Indikator ESS II</th>
                <th style="min-width:120px;">Sasaran ESS III</th>
                <th style="min-width:120px;">Indikator ESS III</th>
                <th style="min-width:120px;">Sasaran ESS IV/JF</th>
                <th style="min-width:120px;">Indikator ESS IV</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="9" class="c pdf-muted">Tidak ada data cascading OPD.</td>
                </tr>
            <?php else: ?>
                <?php // Nilai diulang tiap baris (tanpa rowspan) agar mpdf memecah tabel antar-halaman dengan benar. ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['renstra_tujuan'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['renstra_sasaran'] ?? '-')) ?></td>
                        <td><?= !empty($r['indikator_sasaran']) ? $idk . nl2br(esc($r['indikator_sasaran'])) : '-' ?></td>
                        <td><?= !empty($r['es3_sasaran']) ? nl2br(esc($r['es3_sasaran'])) : '-' ?></td>
                        <td><?= !empty($r['es3_indikator']) ? $idk . nl2br(esc($r['es3_indikator'])) : '-' ?></td>
                        <td><?= !empty($r['es4_sasaran']) ? nl2br(esc($r['es4_sasaran'])) : '-' ?></td>
                        <td><?= !empty($r['es4_indikator']) ? $idk . nl2br(esc($r['es4_indikator'])) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

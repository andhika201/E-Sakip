<?php
$periodeTxt = trim((string) ($tahun_mulai ?? '-')) . ' – ' . trim((string) ($tahun_akhir ?? '-'));
$opdKey = fn($r) => ($r['sasaran_id'] ?? 'x') . '|' . ($r['opd_id'] ?? 'x');
$rtKey  = fn($r) => $opdKey($r) . '|' . ($r['renstra_tujuan_id'] ?? 'x');
$rsKey  = fn($r) => $rtKey($r) . '|' . ($r['renstra_sasaran_id'] ?? 'x');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        td.center, th.center { text-align: center; }
        td.dash { text-align: center; color: #aaa; }
    </style>
</head>

<body>
    <?php $this->setData([ // param kop via data-share (include options tak diteruskan CI4)
        'judul'      => 'Cascading Kinerja Keseluruhan',
        'subjudul'   => 'RPJMD Kabupaten → Renstra Perangkat Daerah · Periode ' . $periodeTxt,
        'logoOnly'   => false,   // tampilkan teks instansi (kop profesional)
        'hideAksara' => true,    // hanya lambang Kabupaten; AKSARA jadi watermark
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th style="min-width:140px;">Tujuan RPJMD</th>
                <th style="min-width:140px;">Sasaran RPJMD</th>
                <th style="min-width:140px;">Perangkat Daerah</th>
                <th style="min-width:140px;">Tujuan Renstra</th>
                <th style="min-width:140px;">Sasaran Renstra</th>
                <th style="min-width:140px;">Indikator Renstra</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="c pdf-muted">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php // Nilai diulang tiap baris (tanpa rowspan) agar mpdf memecah tabel antar-halaman dengan benar. ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?></td>
                        <td><?= !empty($r['nama_opd']) ? nl2br(esc($r['nama_opd'])) : '-' ?></td>
                        <td><?= !empty($r['renstra_tujuan']) ? nl2br(esc($r['renstra_tujuan'])) : '-' ?></td>
                        <td><?= !empty($r['renstra_sasaran']) ? nl2br(esc($r['renstra_sasaran'])) : '-' ?></td>
                        <td><?= !empty($r['renstra_indikator']) ? '<b style="color:#00743e;">IK</b> ' . nl2br(esc($r['renstra_indikator'])) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

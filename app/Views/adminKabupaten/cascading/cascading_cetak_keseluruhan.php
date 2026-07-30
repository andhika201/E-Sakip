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

    <?php
    // Lebar kolom dipatok persen (12 kolom) supaya A3-L tetap terbaca dan
    // tidak ada jenjang yang terpotong. Nilai diulang tiap baris (tanpa
    // rowspan besar) agar mpdf memecah tabel antar-halaman dengan benar —
    // termasuk baris Pelaksana.
    $ik = static fn($v) => !empty($v) ? '<b style="color:#00743e;">IK</b> ' . nl2br(esc($v)) : '-';
    ?>
    <table class="pdf-table" style="table-layout:fixed;">
        <colgroup>
            <col style="width:9%;"><col style="width:9%;"><col style="width:9%;"><col style="width:8%;">
            <col style="width:8%;"><col style="width:8%;">
            <col style="width:8%;"><col style="width:8%;">
            <col style="width:8%;"><col style="width:8%;">
            <col style="width:8.5%;"><col style="width:8.5%;">
        </colgroup>
        <thead>
            <tr>
                <th>Tujuan RPJMD</th>
                <th>Sasaran RPJMD</th>
                <th>Perangkat Daerah</th>
                <th>Tujuan Renstra</th>
                <th>Sasaran ESS II</th>
                <th>Indikator ESS II</th>
                <th>Sasaran ESS III</th>
                <th>Indikator ESS III</th>
                <th>Sasaran ESS IV / JF</th>
                <th>Indikator ESS IV</th>
                <th><?= esc(casc_pelaksana_label('Sasaran ')) ?></th>
                <th><?= esc(casc_pelaksana_label('Indikator ')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="12" class="c pdf-muted">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?></td>
                        <td><?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?></td>
                        <td><?= !empty($r['nama_opd']) ? nl2br(esc($r['nama_opd'])) : '-' ?></td>
                        <td><?= !empty($r['renstra_tujuan']) ? nl2br(esc($r['renstra_tujuan'])) : '-' ?></td>
                        <td><?= !empty($r['renstra_sasaran']) ? nl2br(esc($r['renstra_sasaran'])) : '-' ?></td>
                        <td><?= $ik($r['renstra_indikator'] ?? null) ?></td>
                        <td><?= !empty($r['es3_sasaran']) ? nl2br(esc($r['es3_sasaran'])) : '-' ?></td>
                        <td><?= $ik($r['es3_indikator'] ?? null) ?></td>
                        <td><?= !empty($r['es4_sasaran']) ? nl2br(esc($r['es4_sasaran'])) : '-' ?></td>
                        <td><?= $ik($r['es4_indikator'] ?? null) ?></td>
                        <td><?= !empty($r['pelaksana_sasaran']) ? nl2br(esc($r['pelaksana_sasaran'])) : '-' ?></td>
                        <td><?= $ik($r['pelaksana_indikator'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

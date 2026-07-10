<?php
$namaOpdTxt = trim((string) ($nama_opd ?? ''));
$subjudulParts = ['Matriks Cascading RPJMD -> Renstra OPD'];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
$periodeTxt = trim((string) ($tahun_mulai ?? '-')) . ' – ' . trim((string) ($tahun_akhir ?? '-'));
// Indikator diberi kode "IK" secara default (admin_kab & adminOpd). Bisa di-override false.
$subjudulParts[] = 'Periode ' . $periodeTxt;
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
    <?php $this->setData([ // param kop via data-share (include options tak diteruskan CI4)
        'judul'      => 'Cascading Kinerja Perangkat Daerah',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => strtoupper($nama_opd ?? ''),
        'logoOnly'   => false,   // tampilkan teks instansi (kop profesional)
        'hideAksara' => true,    // hanya lambang Kabupaten; AKSARA jadi watermark
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th style="min-width:120px;">Tujuan RPJMD</th>
                <th style="min-width:120px;">Sasaran RPJMD</th>
                <th style="min-width:120px;">Tujuan Renstra</th>
                <th style="min-width:120px;">Indikator Tujuan</th>
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
                    <td colspan="10" class="c pdf-muted">Tidak ada data cascading OPD.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $r): ?>
                    <tr>
                        <?php if (($firstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>">
                                <?= !empty($r['tujuan_rpjmd']) ? nl2br(esc($r['tujuan_rpjmd'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($firstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>">
                                <?= !empty($r['sasaran_rpjmd']) ? nl2br(esc($r['sasaran_rpjmd'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?? 1 ?>">
                                <?= !empty($r['renstra_tujuan']) ? nl2br(esc($r['renstra_tujuan'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($firstShow['indikator_tujuan'][$r['indikator_tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['indikator_tujuan'][$r['indikator_tujuan_id']] ?? 1 ?>">
                                <?= !empty($r['indikator_tujuan']) ? $idk . nl2br(esc($r['indikator_tujuan'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?? 1 ?>">
                                <?= !empty($r['renstra_sasaran']) ? nl2br(esc($r['renstra_sasaran'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (($firstShow['indikator'][$r['indikator_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                <?= !empty($r['indikator_sasaran']) ? $idk . nl2br(esc($r['indikator_sasaran'])) : '-' ?>
                            </td>
                        <?php endif; ?>

                        <?php if (empty($r['es3_id'])): ?>
                            <?php if (($firstShow['indikator'][$r['indikator_id']] ?? -1) == $index): ?>
                                <td colspan="4" class="dash">-</td>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (($firstShow['es3'][$r['es3_id']] ?? -1) == $index): ?>
                                <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>">
                                    <?= !empty($r['es3_sasaran']) ? nl2br(esc($r['es3_sasaran'])) : '-' ?>
                                </td>
                            <?php endif; ?>

                            <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                            <?php if (($firstShow['es3_indikator'][$key] ?? -1) == $index): ?>
                                <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>">
                                    <?= !empty($r['es3_indikator']) ? $idk . nl2br(esc($r['es3_indikator'])) : '-' ?>
                                </td>
                            <?php endif; ?>

                            <?php if (empty($r['es4_id'])): ?>
                                <?php if (($firstShow['es3_indikator'][$key] ?? -1) == $index): ?>
                                    <td colspan="2" class="dash">-</td>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (($firstShow['es4'][$r['es4_id']] ?? -1) == $index): ?>
                                    <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>">
                                        <?= !empty($r['es4_sasaran']) ? nl2br(esc($r['es4_sasaran'])) : '-' ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= !empty($r['es4_indikator']) ? $idk . nl2br(esc($r['es4_indikator'])) : '-' ?></td>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

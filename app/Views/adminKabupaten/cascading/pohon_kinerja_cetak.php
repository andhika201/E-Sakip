<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 9pt;
        color: #222;
    }

    /* BOX - MISI: teal/cyan */
    .box-misi {
        background: #00b8a9;
        color: #fff;
        border-radius: 25px;
        padding: 12px 15px;
        text-align: center;
        font-weight: bold;
        font-size: 10pt;
    }

    /* BOX - TUJUAN: hijau tua */
    .box-tujuan {
        background-color: #00897b;
        color: #fff;
        border-radius: 8px;
        padding: 8px;
        text-align: center;
        font-size: 9pt;
    }

    /* BOX - INDIKATOR TUJUAN: hijau */
    .box-indikator {
        background: #43a047;
        color: #fff;
        border-radius: 6px;
        padding: 5px 8px;
        margin-top: 4px;
        font-size: 7.5pt;
        text-align: center;
    }

    /* BOX - SASARAN: coklat */
    .box-sasaran {
        background: #5d4037;
        color: #fff;
        border-radius: 8px;
        padding: 7px;
        text-align: center;
        font-size: 8pt;
    }

    /* BOX - INDIKATOR SASARAN: oranye */
    .box-iks {
        background: #e65100;
        color: #fff;
        border-radius: 6px;
        padding: 5px 6px;
        margin-top: 4px;
        font-size: 7.5pt;
        text-align: center;
    }

    /* BOX - CSF */
    .box-csf {
        background: #fff3e0;
        color: #e65100;
        border: 1px solid #ffb74d;
        border-radius: 6px;
        padding: 4px;
        font-size: 7pt;
        font-weight: bold;
        margin-bottom: 4px;
        text-align: center;
    }
</style>

<?php
/* ================================
   HITUNG TOTAL KOLOM SEMUA MISI
================================ */
$totalColsAll = 0;
$misiColMap = [];

foreach ($tree as $misiIndex => $misi) {
    $totalSasaranCols = 0;
    foreach ($misi['tujuan'] as $tujuan) {
        $totalSasaranCols += max(count($tujuan['sasaran']), 1);
    }
    $totalSasaranCols = max($totalSasaranCols, 1);
    $misiColMap[$misiIndex] = $totalSasaranCols;
    $totalColsAll += $totalSasaranCols;
}
?>

<h2 style="text-align:center; color:#333; margin-bottom:5px; font-size:14pt;">
    POHON KINERJA
</h2>
<p style="text-align:center; color:#666; font-size:10pt; margin-bottom:15px;">
    Periode <?= $tahun_mulai ?> - <?= $tahun_akhir ?>
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">

    <!-- ===================== ROW: SEMUA MISI ===================== -->
    <tr>
        <?php $misiNo = 0;
        foreach ($tree as $misiIndex => $misi): $misiNo++; ?>
            <td colspan="<?= $misiColMap[$misiIndex] ?>" align="center" style="padding:8px;">
                <div class="box-misi">
                    Misi <?= $misiNo ?><br>
                    <?= esc($misi['misi']) ?>
                </div>
            </td>
        <?php endforeach; ?>
    </tr>

    <!-- ===================== GARIS VERTIKAL: Misi → Tujuan ===================== -->
    <tr>
        <?php foreach ($tree as $misiIndex => $misi): ?>
            <td colspan="<?= $misiColMap[$misiIndex] ?>" align="center" style="padding:0;">
                <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                    <tr>
                        <td style="width:3px; height:25px; background-color:#888; font-size:1px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        <?php endforeach; ?>
    </tr>

    <!-- ===================== TUJUAN ===================== -->
    <tr>
        <?php foreach ($tree as $misiIndex => $misi): ?>
            <?php
            $tujuanSasaranMap = [];
            foreach ($misi['tujuan'] as $tIdx => $tujuan) {
                $tujuanSasaranMap[$tIdx] = max(count($tujuan['sasaran']), 1);
            }
            ?>
            <?php foreach ($misi['tujuan'] as $tIdx => $tujuan): ?>
                <td colspan="<?= $tujuanSasaranMap[$tIdx] ?>" align="center" valign="top" style="padding:5px;">
                    <div class="box-tujuan">
                        <?= esc($tujuan['tujuan_rpjmd']) ?>
                    </div>
                    <?php foreach ($tujuan['indikator_tujuan'] as $ikt): ?>
                        <div class="box-indikator">
                            <?= esc($ikt['indikator_tujuan']) ?>
                        </div>
                    <?php endforeach; ?>
                </td>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>

    <!-- ===================== GARIS VERTIKAL: Tujuan → cabang sasaran ===================== -->
    <tr>
        <?php foreach ($tree as $misi): ?>
            <?php foreach ($misi['tujuan'] as $tIdx => $tujuan): ?>
                <?php $sasCount = max(count($tujuan['sasaran']), 1); ?>
                <td colspan="<?= $sasCount ?>" align="center" style="padding:0;">
                    <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                        <tr>
                            <td style="width:3px; height:20px; background-color:#888; font-size:1px;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>

    <!-- ===================== GARIS HORIZONTAL CABANG KE SASARAN ===================== -->
    <tr>
        <?php foreach ($tree as $misi): ?>
            <?php foreach ($misi['tujuan'] as $tIdx => $tujuan): ?>
                <?php $sasCount = max(count($tujuan['sasaran']), 1); ?>

                <?php if ($sasCount === 1): ?>
                    <!-- 1 sasaran: garis lurus saja -->
                    <td align="center" style="padding:0; height:3px;">
                        <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                            <tr>
                                <td style="width:3px; height:3px; background-color:#888; font-size:1px;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                <?php else: ?>
                    <!-- Banyak sasaran: garis horizontal bercabang -->
                    <?php for ($s = 0; $s < $sasCount; $s++): ?>
                        <?php if ($s === 0): ?>
                            <!-- Kiri: setengah kanan ada garis -->
                            <td style="padding:0; height:3px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="50%" style="height:3px;"></td>
                                        <td width="50%" style="height:3px; background-color:#888; font-size:1px;">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        <?php elseif ($s === $sasCount - 1): ?>
                            <!-- Kanan: setengah kiri ada garis -->
                            <td style="padding:0; height:3px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="50%" style="height:3px; background-color:#888; font-size:1px;">&nbsp;</td>
                                        <td width="50%" style="height:3px;"></td>
                                    </tr>
                                </table>
                            </td>
                        <?php else: ?>
                            <!-- Tengah: garis penuh -->
                            <td style="padding:0; height:3px; background-color:#888; font-size:1px;">&nbsp;</td>
                        <?php endif; ?>
                    <?php endfor; ?>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>

    <!-- ===================== GARIS VERTIKAL TURUN KE MASING-MASING SASARAN ===================== -->
    <tr>
        <?php foreach ($tree as $misi): ?>
            <?php foreach ($misi['tujuan'] as $tujuan): ?>
                <?php $sasCount = max(count($tujuan['sasaran']), 1); ?>
                <?php for ($s = 0; $s < $sasCount; $s++): ?>
                    <td align="center" style="padding:0;">
                        <table cellpadding="0" cellspacing="0" style="margin:0 auto;">
                            <tr>
                                <td style="width:3px; height:15px; background-color:#888; font-size:1px;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                <?php endfor; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>

    <!-- ===================== SASARAN ===================== -->
    <tr>
        <?php foreach ($tree as $misi): ?>
            <?php foreach ($misi['tujuan'] as $tujuan): ?>

                <?php if (!empty($tujuan['sasaran'])): ?>
                    <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                        <td align="center" valign="top" style="padding:4px;">

                            <?php if (!empty($sasaran['csf'])): ?>
                                <div class="box-csf">CSF: <?= esc($sasaran['csf']) ?></div>
                            <?php endif; ?>

                            <div class="box-sasaran">
                                <?= esc($sasaran['sasaran_rpjmd']) ?>
                            </div>

                            <?php foreach ($sasaran['indikator_sasaran'] as $iks): ?>
                                <div class="box-iks">
                                    <?= esc($iks['indikator_sasaran']) ?>
                                </div>
                            <?php endforeach; ?>

                        </td>
                    <?php endforeach; ?>
                <?php else: ?>
                    <td align="center" valign="top">
                        <div class="box-sasaran" style="opacity:0.4;">-</div>
                    </td>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endforeach; ?>
    </tr>

</table>
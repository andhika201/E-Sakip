<?php

/**
 * Cetak PDF IKU standalone — Admin OPD / Kecamatan.
 *
 * @var array  $iku_data    sasaran + indikator + target
 * @var array  $years       tahun periode terpilih
 * @var string $periode_txt label periode (mis. "2025 - 2029")
 * @var string $nama_opd    nama OPD pemilik
 * @var bool   $lintas_opd  tampilkan kolom OPD (super admin lintas OPD)
 */
$namaOpdTxt = trim((string) ($nama_opd ?? ''));
$lintasOpd  = !empty($lintas_opd);

$subjudulParts = [];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
if (!empty($periode_txt)) {
    $subjudulParts[] = 'Periode ' . $periode_txt;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 9px; }
        table.iku-print-table {
            table-layout: fixed;
            font-size: 7.8px;
            line-height: 1.18;
            width: 100%;
        }
        table.iku-print-table thead { display: table-header-group; }
        table.iku-print-table tr { page-break-inside: avoid; }
        table.iku-print-table th,
        table.iku-print-table td {
            padding: 2.8px 3px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.iku-print-table thead th {
            font-size: 7.2px;
            line-height: 1.12;
            padding: 3px 2px;
        }
        .text-start { text-align: left; }
        .c { text-align: center; }
        .year-cell { text-align: center; white-space: nowrap; }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Indikator Kinerja Utama',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => $namaOpdTxt !== '' ? strtoupper($namaOpdTxt) : '',
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php $this->setData(['show_opd' => $lintasOpd]); ?>
    <?= $this->include('templates/iku/_tabel_cetak') ?>
</body>

</html>

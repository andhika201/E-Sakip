<?php

/**
 * Cetak PDF IKU standalone — Admin Kabupaten.
 *
 * @var string $mode        'kabupaten' | 'opd'
 * @var array  $iku_data    sasaran + indikator + target
 * @var array  $years       tahun periode terpilih
 * @var string $periode_txt label periode (mis. "2025 - 2029")
 * @var string $opd_name    nama OPD bila difilter ke satu OPD
 */
$mode       = $mode ?? 'kabupaten';
$showOpdCol = ($mode === 'opd');
$opdNameTxt = trim((string) ($opd_name ?? ''));

$subjudulParts = [];
if ($opdNameTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $opdNameTxt;
}
if (!empty($periode_txt)) {
    $subjudulParts[] = 'Periode ' . $periode_txt;
}
$subjudulParts[] = $showOpdCol ? 'Rekap Perangkat Daerah' : 'Pemerintah Kabupaten';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 9px; }
        table.iku-print-table {
            font-size: 7.6px;
            line-height: 1.18;
        }
        table.iku-print-table thead { display: table-header-group; }
        table.iku-print-table tr { page-break-inside: avoid; }
        table.iku-print-table th,
        table.iku-print-table td {
            padding: 2.8px 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }
        table.iku-print-table thead th {
            font-size: 7px;
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
        'namaUnit'   => $opdNameTxt !== '' ? strtoupper($opdNameTxt) : '',
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php $this->setData(['show_opd' => $showOpdCol]); ?>
    <?= $this->include('templates/iku/_tabel_cetak') ?>
</body>

</html>

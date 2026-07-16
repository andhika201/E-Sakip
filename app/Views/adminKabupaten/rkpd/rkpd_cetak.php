<?php
helper('format_helper');

$allRows = $rows ?? [];
$selectedOpd  = $filter_opd   ?? 'all';
$selectedYear = $filter_tahun ?? date('Y');
$opdTxt = ($selectedOpd === 'all') ? 'Semua OPD' : trim((string) ($currentOpdName ?? '-'));
$tahunTxt = ($selectedYear === 'all') ? 'Semua Tahun' : (string) $selectedYear;

$subjudulParts = [];
if ($opdTxt !== '') {
    $subjudulParts[] = ($selectedOpd === 'all') ? $opdTxt : 'Satuan Kerja: ' . $opdTxt;
}
$subjudulParts[] = 'Tahun ' . $tahunTxt;

/* Pra-kalkulasi rowspan (satu pass) — rows sudah ORDER BY nama_opd, s.id, i.id, p.id, k.id, sk.id */
$n = count($allRows);
$rsOpd  = array_fill(0, $n, 0);
$rsInd  = array_fill(0, $n, 0);
$rsProg = array_fill(0, $n, 0);
$rsKeg  = array_fill(0, $n, 0);

for ($i = 0; $i < $n; $i++) {
    $r = $allRows[$i];
    $opdCur  = $r['opd_id']           ?? 0;
    $indCur  = $r['indikator_id']      ?? 0;
    $progCur = $r['program_kegiatan']  ?? '-';
    $kegCur  = $r['nama_kegiatan']     ?? '-';
    $prev = $allRows[$i - 1] ?? null;

    if ($prev && ($prev['opd_id'] ?? 0) == $opdCur) {
        $rsOpd[$i] = 0;
    } else {
        $count = 1;
        for ($j = $i + 1; $j < $n && ($allRows[$j]['opd_id'] ?? 0) == $opdCur; $j++) {
            $count++;
        }
        $rsOpd[$i] = $count;
    }

    if ($prev && ($prev['opd_id'] ?? 0) == $opdCur && ($prev['indikator_id'] ?? 0) == $indCur) {
        $rsInd[$i] = 0;
    } else {
        $count = 1;
        for ($j = $i + 1; $j < $n
                && ($allRows[$j]['opd_id'] ?? 0) == $opdCur
                && ($allRows[$j]['indikator_id'] ?? 0) == $indCur; $j++) {
            $count++;
        }
        $rsInd[$i] = $count;
    }

    if ($prev && ($prev['opd_id'] ?? 0) == $opdCur
            && ($prev['indikator_id'] ?? 0) == $indCur
            && ($prev['program_kegiatan'] ?? '-') == $progCur) {
        $rsProg[$i] = 0;
    } else {
        $count = 1;
        for ($j = $i + 1; $j < $n
                && ($allRows[$j]['opd_id'] ?? 0) == $opdCur
                && ($allRows[$j]['indikator_id'] ?? 0) == $indCur
                && ($allRows[$j]['program_kegiatan'] ?? '-') == $progCur; $j++) {
            $count++;
        }
        $rsProg[$i] = $count;
    }

    if ($prev && ($prev['opd_id'] ?? 0) == $opdCur
            && ($prev['indikator_id'] ?? 0) == $indCur
            && ($prev['program_kegiatan'] ?? '-') == $progCur
            && ($prev['nama_kegiatan'] ?? '-') == $kegCur) {
        $rsKeg[$i] = 0;
    } else {
        $count = 1;
        for ($j = $i + 1; $j < $n
                && ($allRows[$j]['opd_id'] ?? 0) == $opdCur
                && ($allRows[$j]['indikator_id'] ?? 0) == $indCur
                && ($allRows[$j]['program_kegiatan'] ?? '-') == $progCur
                && ($allRows[$j]['nama_kegiatan'] ?? '-') == $kegCur; $j++) {
            $count++;
        }
        $rsKeg[$i] = $count;
    }
}

// Nomor urut per indikator.
$indNoMap = [];
$no = 1;
foreach ($allRows as $r) {
    $ik = ($r['opd_id'] ?? 0) . '_' . ($r['indikator_id'] ?? 0);
    if (!isset($indNoMap[$ik])) {
        $indNoMap[$ik] = $no++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 8px; }
        table.rkpd-print-table {
            font-size: 7.2px;
            line-height: 1.16;
        }
        table.rkpd-print-table thead { display: table-header-group; }
        table.rkpd-print-table tr { page-break-inside: avoid; }
        table.rkpd-print-table th,
        table.rkpd-print-table td {
            padding: 2.8px 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: top;
        }
        table.rkpd-print-table thead th {
            font-size: 6.8px;
            line-height: 1.1;
            padding: 3px 2px;
            vertical-align: middle;
        }
        .text-start { text-align: left; }
        .c { text-align: center; }
        .r { text-align: right; white-space: nowrap; }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Rencana Kerja Pemerintah Daerah',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => ($selectedOpd === 'all') ? '' : strtoupper($opdTxt),
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <table class="pdf-table rkpd-print-table">
        <thead>
            <tr>
                <th style="width:11%;">Satuan Kerja</th>
                <th style="width:3%;">No</th>
                <th style="width:4%;">Tahun</th>
                <th style="width:13%;">Sasaran</th>
                <th style="width:13%;">Indikator Sasaran</th>
                <th style="width:12%;">Program</th>
                <th style="width:12%;">Kegiatan</th>
                <th style="width:12%;">Sub Kegiatan</th>
                <th style="width:13%;">Indikator Sasaran Sub Kegiatan</th>
                <th style="width:5%;">Target</th>
                <th style="width:10%;">Target Anggaran</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($allRows)): ?>
                <tr>
                    <td colspan="11" class="c pdf-muted">
                        Tidak ada data RKPD untuk filter ini. (Hanya RKT berstatus <strong>selesai</strong> yang ditampilkan.)
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($allRows as $i => $row): ?>
                    <?php
                    $ik = ($row['opd_id'] ?? 0) . '_' . ($row['indikator_id'] ?? 0);
                    $anggar = $row['target_anggaran'] ?? 0;
                    ?>
                    <tr>
                        <?php if ($rsOpd[$i] > 0): ?>
                            <td rowspan="<?= $rsOpd[$i] ?>" class="text-start"><?= esc($row['nama_opd'] ?? '-') ?></td>
                        <?php endif; ?>

                        <?php if ($rsInd[$i] > 0): ?>
                            <td rowspan="<?= $rsInd[$i] ?>" class="c"><?= $indNoMap[$ik] ?></td>
                            <td rowspan="<?= $rsInd[$i] ?>" class="c"><?= esc($row['tahun'] ?? '-') ?></td>
                            <td rowspan="<?= $rsInd[$i] ?>" class="text-start"><?= esc($row['sasaran'] ?? '-') ?></td>
                            <td rowspan="<?= $rsInd[$i] ?>" class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                        <?php endif; ?>

                        <?php if ($rsProg[$i] > 0): ?>
                            <td rowspan="<?= $rsProg[$i] ?>" class="text-start"><?= esc($row['program_kegiatan'] ?? '-') ?></td>
                        <?php endif; ?>

                        <?php if ($rsKeg[$i] > 0): ?>
                            <td rowspan="<?= $rsKeg[$i] ?>" class="text-start"><?= esc($row['nama_kegiatan'] ?? '-') ?></td>
                        <?php endif; ?>

                        <td class="text-start"><?= esc($row['nama_subkegiatan'] ?? '-') ?></td>
                        <td class="text-start"><?= esc($row['indikator_sasaran_sub_kegiatan'] ?? '-') ?></td>
                        <td class="c"><?= esc($row['target'] ?? '-') ?></td>
                        <td class="r">
                            <?= function_exists('formatRupiah') ? formatRupiah($anggar) : 'Rp ' . number_format((float) $anggar, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

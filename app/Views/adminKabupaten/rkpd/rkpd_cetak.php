<?php
helper('format_helper');
// pdf_td_gabung()/pdf_teks(): kolom induk TANPA rowspan + pemenggalan token
// panjang. Rowspan setinggi satu OPD (ratusan baris) membuat mPDF memindah
// blok utuh ke halaman baru atau menyusutkan seluruh tabel sampai tak terbaca.
helper('pdf');

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
        /* Tanpa zebra: kolom gabungan (tanpa garis dalam) akan tampak belang bila baris diwarnai selang-seling. */
        table.rkpd-print-table tbody tr:nth-child(even) td { background: #fff; }
        /* Isi kolom gabungan ditulis di baris tengah grup -> rata tengah agar konsisten. */
        table.rkpd-print-table td.vm { vertical-align: middle; }
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
                <?php // Total lebar WAJIB tepat 100%: mPDF menjaga proporsi (keep_table_proportions),
                      // jadi total 108% dulu = tabel 8% lebih lebar dari kertas -> seluruhnya disusutkan. ?>
                <th style="width:10%;">Satuan Kerja</th>
                <th style="width:3%;">No</th>
                <th style="width:4%;">Tahun</th>
                <th style="width:12%;">Sasaran</th>
                <th style="width:12%;">Indikator Sasaran</th>
                <th style="width:11%;">Program</th>
                <th style="width:11%;">Kegiatan</th>
                <th style="width:11%;">Sub Kegiatan</th>
                <th style="width:12%;">Indikator Sasaran Sub Kegiatan</th>
                <th style="width:5%;">Target</th>
                <th style="width:9%;">Target Anggaran</th>
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
                <?php
                // Awal & tinggi grup yang sedang berjalan per jenjang; rs*[$i] > 0
                // hanya di baris awal grup (pra-kalkulasi di atas).
                $grup = ['opd' => [0, 1], 'ind' => [0, 1], 'prog' => [0, 1], 'keg' => [0, 1]];
                ?>
                <?php foreach ($allRows as $i => $row): ?>
                    <?php
                    $ik = ($row['opd_id'] ?? 0) . '_' . ($row['indikator_id'] ?? 0);
                    $anggar = $row['target_anggaran'] ?? 0;
                    foreach (['opd' => $rsOpd, 'ind' => $rsInd, 'prog' => $rsProg, 'keg' => $rsKeg] as $jenjang => $rs) {
                        if ($rs[$i] > 0) {
                            $grup[$jenjang] = [$i, $rs[$i]];
                        }
                    }
                    [$mulaiOpd, $tinggiOpd]   = $grup['opd'];
                    [$mulaiInd, $tinggiInd]   = $grup['ind'];
                    [$mulaiProg, $tinggiProg] = $grup['prog'];
                    [$mulaiKeg, $tinggiKeg]   = $grup['keg'];
                    ?>
                    <tr>
                        <?= pdf_td_gabung($i - $mulaiOpd, $tinggiOpd, pdf_teks($row['nama_opd'] ?? ''), 'text-start') ?>

                        <?= pdf_td_gabung($i - $mulaiInd, $tinggiInd, (string) ($indNoMap[$ik] ?? ''), 'c', '', 0) ?>
                        <?= pdf_td_gabung($i - $mulaiInd, $tinggiInd, esc($row['tahun'] ?? ''), 'c') ?>
                        <?= pdf_td_gabung($i - $mulaiInd, $tinggiInd, pdf_teks($row['sasaran'] ?? ''), 'text-start') ?>
                        <?= pdf_td_gabung($i - $mulaiInd, $tinggiInd, pdf_teks($row['indikator_sasaran'] ?? ''), 'text-start') ?>

                        <?= pdf_td_gabung($i - $mulaiProg, $tinggiProg, pdf_teks($row['program_kegiatan'] ?? ''), 'text-start') ?>

                        <?= pdf_td_gabung($i - $mulaiKeg, $tinggiKeg, pdf_teks($row['nama_kegiatan'] ?? ''), 'text-start') ?>

                        <td class="text-start"><?= pdf_teks($row['nama_subkegiatan'] ?? '') ?></td>
                        <td class="text-start"><?= pdf_teks($row['indikator_sasaran_sub_kegiatan'] ?? '') ?></td>
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

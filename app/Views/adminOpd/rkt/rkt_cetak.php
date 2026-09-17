<?php
helper('format_helper');
// pdf_td_gabung()/pdf_teks(): kolom induk TANPA rowspan + pemenggalan token
// panjang. Rowspan setinggi satu sasaran/indikator membuat mPDF menyusutkan
// seluruh tabel sampai tak terbaca begitu bloknya lebih tinggi dari satu
// halaman (RKT Dinkes pernah tercetak 2,6 pt).
helper('pdf');

$namaOpdTxt = trim((string) ($currentOpd['nama_opd'] ?? ''));
$subjudulParts = [];
if ($namaOpdTxt !== '') {
    $subjudulParts[] = 'Perangkat Daerah: ' . $namaOpdTxt;
}
if (($filter_tahun ?? 'all') !== 'all') {
    $subjudulParts[] = 'Tahun ' . $filter_tahun;
} else {
    $subjudulParts[] = 'Semua Tahun';
}

$filterLabels = [];
if (($filter_sasaran ?? 'all') !== 'all' && !empty($sasaranList ?? [])) {
    foreach ($sasaranList as $s) {
        if ((string) ($s['id'] ?? '') === (string) $filter_sasaran) {
            $filterLabels[] = 'Indikator Sasaran: ' . ($s['indikator_sasaran'] ?? '');
            break;
        }
    }
}
if (($filter_status ?? 'all') !== 'all') {
    $filterLabels[] = 'Status: ' . ucfirst((string) $filter_status);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        body { font-size: 9px; }
        .filter-note {
            margin: 0 0 8px;
            font-size: 8.4px;
            color: #526158;
            text-align: right;
        }
        table.rkt-print-table {
            table-layout: fixed;
            width: 100%;
            font-size: 7.2px;
            line-height: 1.16;
        }
        table.rkt-print-table thead {
            display: table-header-group;
        }
        /* Tanpa zebra: kolom gabungan (tanpa garis dalam) akan tampak belang bila baris diwarnai selang-seling. */
        table.rkt-print-table tbody tr:nth-child(even) td { background: #fff; }
        table.rkt-print-table th,
        table.rkt-print-table td {
            padding: 2.6px 3px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.rkt-print-table thead th {
            font-size: 6.7px;
            line-height: 1.1;
            padding: 3px 2px;
            /* Judul kolom boleh turun baris: nowrap memaksa lebar minimum kolom,
               dan begitu jumlahnya melebihi lebar kertas mPDF menyusutkan SELURUH tabel. */
        }
        .text-start { text-align: left; }
        .nowrap { white-space: nowrap; }
        .anggaran { text-align: right; white-space: nowrap; }
        .muted-box {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 8px;
            background: #dde5df;
            color: #516158;
            font-size: 6.9px;
        }
    </style>
</head>

<body>
    <?php $this->setData([
        'judul'      => 'Renja / Rencana Kerja Tahunan (RKT)',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => strtoupper($currentOpd['nama_opd'] ?? ''),
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php if (!empty($filterLabels)): ?>
        <div class="filter-note"><?= esc(implode(' | ', $filterLabels)) ?></div>
    <?php endif; ?>

    <table class="pdf-table rkt-print-table">
        <colgroup>
            <col style="width:3.5%;">
            <col style="width:6%;">
            <col style="width:12%;">
            <col style="width:13%;">
            <col style="width:12%;">
            <col style="width:12%;">
            <col style="width:12%;">
            <col style="width:14%;">
            <col style="width:6.5%;">
            <col style="width:9%;">
        </colgroup>
        <thead>
            <tr>
                <th>NO</th>
                <th>TAHUN</th>
                <th>SASARAN</th>
                <th>INDIKATOR SASARAN</th>
                <th>PROGRAM</th>
                <th>KEGIATAN</th>
                <th>SUB KEGIATAN</th>
                <th>INDIKATOR SASARAN SUB KEGIATAN</th>
                <th>TARGET</th>
                <th>TARGET ANGGARAN</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $groupedBySasaran = [];
            foreach (($rktdata ?? []) as $ind) {
                $sasaran = $ind['sasaran'] ?? 'Tanpa Sasaran';
                $groupedBySasaran[$sasaran][] = $ind;
            }

            // ---------------------------------------------------------------
            // Ratakan pohon Sasaran → Indikator → Program → Kegiatan → Sub
            // menjadi daftar baris daun. Tiap baris membawa "kunci grup" untuk
            // kolom induknya; posisi & tinggi grup dihitung sekali dari daftar
            // ini, lalu kolom induk dicetak lewat pdf_td_gabung() — tanpa
            // rowspan (lihat catatan helper('pdf') di atas).
            // ---------------------------------------------------------------
            $selectedYear = $filter_tahun ?? 'all';
            $tahunTampil = static function (array $ind) use ($selectedYear): string {
                if ($selectedYear !== 'all') {
                    return (string) $selectedYear;
                }
                $targetYears = array_values(array_unique(array_filter($ind['target_years'] ?? [])));
                sort($targetYears);
                if (empty($targetYears)) {
                    return '';
                }
                return count($targetYears) === 1
                    ? (string) $targetYears[0]
                    : (reset($targetYears) . ' - ' . end($targetYears));
            };

            $baris = [];
            $no    = 1;
            foreach ($groupedBySasaran as $sasaranNama => $indikators) {
                foreach ($indikators as $ind) {
                    $kIndikator = 'i' . $no; // unik: $no naik tiap indikator
                    $dasar = [
                        'sasaran'   => $sasaranNama,
                        'kSasaran'  => 's' . $sasaranNama,
                        'kIndikator'=> $kIndikator,
                        'no'        => $no++,
                        'tahun'     => $tahunTampil($ind),
                        'indikator' => $ind['indikator_sasaran'] ?? '',
                        'kProgram'  => $kIndikator . '/p',
                        'program'   => '',
                        'kKegiatan' => $kIndikator . '/p/k',
                        'kegiatan'  => '',
                        'sub'       => '',
                        'sub_ind'   => '',
                        'target'    => '',
                        'anggaran'  => null,
                    ];
                    if (empty($ind['rkts'])) {
                        $baris[] = $dasar;
                        continue;
                    }
                    foreach ($ind['rkts'] as $pi => $rkt) {
                        $b = $dasar;
                        $b['kProgram'] = $kIndikator . '/p' . $pi;
                        $b['program']  = $rkt['program_nama'] ?? '';
                        if (empty($rkt['kegiatan'])) {
                            $b['kKegiatan'] = $b['kProgram'] . '/k';
                            $baris[] = $b;
                            continue;
                        }
                        foreach ($rkt['kegiatan'] as $ki => $keg) {
                            $b['kKegiatan'] = $b['kProgram'] . '/k' . $ki;
                            $b['kegiatan']  = $keg['kegiatan'] ?? '';
                            if (empty($keg['subkegiatan'])) {
                                $baris[] = $b;
                                continue;
                            }
                            foreach ($keg['subkegiatan'] as $sub) {
                                $b['sub']      = $sub['sub_kegiatan'] ?? '';
                                $b['sub_ind']  = $sub['indikator_sasaran_sub_kegiatan'] ?? '';
                                $b['target']   = $sub['target'] ?? '';
                                $b['anggaran'] = $sub['anggaran'] ?? 0;
                                $baris[] = $b;
                            }
                        }
                    }
                }
            }

            // Tinggi tiap grup (jumlah baris daun) per kunci.
            $tinggi = ['kSasaran' => [], 'kIndikator' => [], 'kProgram' => [], 'kKegiatan' => []];
            foreach ($baris as $b) {
                foreach ($tinggi as $kunci => $_) {
                    $tinggi[$kunci][$b[$kunci]] = ($tinggi[$kunci][$b[$kunci]] ?? 0) + 1;
                }
            }
            $posisi = ['kSasaran' => [], 'kIndikator' => [], 'kProgram' => [], 'kKegiatan' => []];
            ?>
            <?php if (empty($baris)): ?>
                <tr>
                    <td colspan="10" class="c pdf-muted">Tidak ada data RKT untuk filter yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($baris as $b): ?>
                    <?php
                    // Posisi baris ini di dalam tiap grupnya (mulai 0).
                    $ke = [];
                    foreach ($posisi as $kunci => $_) {
                        $ke[$kunci] = $posisi[$kunci][$b[$kunci]] = ($posisi[$kunci][$b[$kunci]] ?? -1) + 1;
                    }
                    ?>
                    <tr>
                        <?= pdf_td_gabung($ke['kIndikator'], $tinggi['kIndikator'][$b['kIndikator']], (string) $b['no'], 'c nowrap', '', 0) ?>
                        <?= pdf_td_gabung($ke['kIndikator'], $tinggi['kIndikator'][$b['kIndikator']], esc($b['tahun']), 'c nowrap') ?>
                        <?= pdf_td_gabung($ke['kSasaran'], $tinggi['kSasaran'][$b['kSasaran']], pdf_teks($b['sasaran']), 'text-start') ?>
                        <?= pdf_td_gabung($ke['kIndikator'], $tinggi['kIndikator'][$b['kIndikator']], pdf_teks($b['indikator']), 'text-start') ?>
                        <?= pdf_td_gabung($ke['kProgram'], $tinggi['kProgram'][$b['kProgram']], pdf_teks($b['program']), 'text-start') ?>
                        <?= pdf_td_gabung($ke['kKegiatan'], $tinggi['kKegiatan'][$b['kKegiatan']], pdf_teks($b['kegiatan']), 'text-start') ?>
                        <td class="text-start"><?= pdf_teks($b['sub']) ?></td>
                        <td class="text-start"><?= pdf_teks($b['sub_ind']) ?></td>
                        <td class="c"><?= esc($b['target']) ?></td>
                        <td class="anggaran"><?= $b['anggaran'] !== null ? formatRupiah($b['anggaran']) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

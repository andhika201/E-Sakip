<?php
$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$isKab    = (($role ?? '') === 'admin_kab');
$judul    = ($isBupati || !$isKab) ? 'Target dan Rencana Aksi' : 'Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');
$showPejabat = $isOpd;

// Helper unit PK (Program / Kegiatan / Sub Kegiatan). Controller sudah memuatnya,
// pemanggilan ini hanya pengaman bila view dirender dari tempat lain.
helper('pk_unit');

// Judul kolom unit dikirim controller; nilai bawaan dipertahankan supaya view
// tetap aman bila dipanggil dari kode lama.
$labelUnitHeader   = $labelUnitHeader ?? pk_unit_header($eselon ?? null);
$unitHeaderGenerik = (bool) ($unitHeaderGenerik ?? false);

// ---------------------------------------------------------------------------
// Lebar kolom cetak (cabang PK OPD/Kecamatan).
// GOTCHA PDF: shrink_tables_to_fit=false + table-layout:fixed membuat <colgroup>
// satu-satunya kendali lebar. Karena itu jumlah <col> WAJIB sama dengan jumlah
// <th>, dan totalnya dinormalkan ke 100% untuk SETIAP kombinasi $showOpd /
// $showPejabat. Angka di bawah hanyalah bobot relatif, bukan persen final.
// ---------------------------------------------------------------------------
$bobotKolom = [];
$bobotKolom['no'] = 4;
if ($showOpd) {
    $bobotKolom['opd'] = 10;
}
if ($showPejabat) {
    $bobotKolom['pejabat'] = 13;
}
$bobotKolom['sasaran']     = 13;
$bobotKolom['indikator']   = 14;
$bobotKolom['tahun']       = 5;
$bobotKolom['satuan']      = 6;
$bobotKolom['unit']        = 15;
$bobotKolom['anggaran']    = 9;
$bobotKolom['renaksi']     = 13;
$bobotKolom['sub_renaksi'] = 13;
// Satuan milik SUB rencana aksi. WAJIB ikut didaftar di sini: peta ini yang
// menentukan lebar tiap <col> DAN colspan baris "belum ada data" — menambah
// <th> tanpa menambah bobotnya membuat kedua-duanya meleset satu kolom.
$bobotKolom['sub_satuan']  = 6;
$bobotKolom['tw1']         = 4;
$bobotKolom['tw2']         = 4;
$bobotKolom['tw3']         = 4;
$bobotKolom['tw4']         = 4;
$bobotKolom['pj']          = 10;

$totalBobotKolom = array_sum($bobotKolom) ?: 1;
// Dipakai juga oleh baris "belum ada data" supaya colspan tak pernah meleset.
$jumlahKolomOpd  = count($bobotKolom);

$eselonLabel = function ($pkJenis, $jabatanEselon = null, $jabatanNama = null) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Eselon III', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    $pkJenis = strtolower(trim((string) $pkJenis));
    if ($pkJenis !== '' && isset($map[$pkJenis])) {
        return $map[$pkJenis];
    }

    $formatNamaEselon = static function ($value) {
        $value = trim((string) $value);
        if ($value === '' || ctype_digit($value)) {
            return null;
        }
        if (preg_match('/^eselon\s+/i', $value)) {
            return $value;
        }
        return 'Eselon ' . $value;
    };

    $label = $formatNamaEselon($jabatanEselon);
    if ($label !== null) {
        return $label;
    }

    $jabatanText = strtolower(trim(preg_replace('/\s+/', ' ', (string) $jabatanNama)));
    if ($jabatanText !== '') {
        if (strpos($jabatanText, 'kepala sub') === 0) {
            return 'Eselon IV';
        }
        if (strpos($jabatanText, 'kepala bidang') === 0) {
            return 'Eselon III';
        }
        if ($jabatanText === 'sekretaris' || strpos($jabatanText, 'sekretaris dinas') === 0 || strpos($jabatanText, 'sekretaris badan') === 0) {
            return 'Eselon III';
        }
        if (in_array($jabatanText, ['inspektur', 'inspektur kabupaten', 'inspektur daerah', 'inspektur kabupaten pringsewu'], true) || strpos($jabatanText, 'kepala dinas') === 0 || strpos($jabatanText, 'kepala bagian') === 0) {
            return 'Eselon II';
        }
    }

    return '-';
};

$subjudulParts = [];
if (!empty($nama_opd ?? '')) {
    $subjudulParts[] = 'Perangkat Daerah: ' . trim((string) $nama_opd);
}
if (($tahun ?? 'all') !== 'all') {
    $subjudulParts[] = 'Tahun ' . $tahun;
} else {
    $subjudulParts[] = 'Semua Tahun';
}

$filterLabels = [];
if ($isOpd && !empty($eselon ?? null)) {
    $filterLabels[] = 'Eselon: ' . $eselonLabel($eselon);
}
if ($showOpd && !empty($opdFilter ?? null) && !empty($opdList ?? [])) {
    foreach ($opdList as $opd) {
        if ((int) ($opd['id'] ?? 0) === (int) $opdFilter) {
            $filterLabels[] = 'OPD: ' . ($opd['nama_opd'] ?? '');
            break;
        }
    }
}
if ($isOpd && !empty($pejabatId ?? null) && !empty($pejabatList ?? [])) {
    foreach ($pejabatList as $pj) {
        if ((int) ($pj['id'] ?? 0) === (int) $pejabatId) {
            $filterLabels[] = 'Pejabat: ' . (!empty($pj['jabatan']) ? $pj['jabatan'] : ($pj['nama'] ?? ''));
            break;
        }
    }
}

$splitAksi = function ($text) {
    $text = trim((string) $text);
    if ($text === '') return [];
    $lines = preg_split('/\r\n|\r|\n/', $text);
    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
};
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
        table.renaksi-print-table {
            table-layout: fixed;
            width: 100%;
            font-size: 7.2px;
            line-height: 1.16;
        }
        table.renaksi-print-table thead { display: table-header-group; }
        table.renaksi-print-table tr { page-break-inside: avoid; }
        table.renaksi-print-table th,
        table.renaksi-print-table td {
            padding: 2.6px 3px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.renaksi-print-table thead th {
            font-size: 6.8px;
            line-height: 1.1;
            padding: 3px 2px;
            white-space: nowrap;
        }
        .text-start { text-align: left; }
        .nowrap { white-space: nowrap; }
        /* Program & anggaran digabung setinggi indikator; tiap entri dipisah garis
           tipis supaya program ke-n mudah dibaca sejajar dengan anggaran ke-n. */
        .nopad { padding: 0 !important; }
        .stack-item { padding: 2.8px 3px; }
        .stack-sep { border-top: 0.5px dashed #9bbfa8; }
        .badge-lite {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 8px;
            background: #e9f6ee;
            color: #155d38;
            font-size: 6.8px;
        }
        .badge-manual {
            background: #fff3cd;
            color: #8a5a00;
        }
    </style>
</head>
<body>
<?php $this->setData([
    'judul'      => strtoupper($judul),
    'subjudul'   => implode(' - ', $subjudulParts),
    'namaUnit'   => strtoupper($nama_opd ?? ''),
    'logoOnly'   => false,
    'hideAksara' => true,
]); ?>
<?= $this->include('templates/pdf_kop') ?>

<?php if (!empty($filterLabels)): ?>
    <div class="filter-note"><?= esc(implode(' | ', $filterLabels)) ?></div>
<?php endif; ?>

<table class="pdf-table renaksi-print-table">
    <?php if ($isBupati): ?>
        <colgroup>
            <col style="width:4%;">
            <col style="width:20%;">
            <col style="width:18%;">
            <col style="width:7%;">
            <col style="width:7%;">
            <col style="width:10%;">
            <col style="width:26%;">
            <col style="width:8%;">
        </colgroup>
        <thead>
        <tr>
            <th>No</th>
            <th>Sasaran</th>
            <th>Indikator</th>
            <th>Tahun</th>
            <th>Satuan</th>
            <th>Target</th>
            <th>Perangkat Daerah Pendukung PK Bupati</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($grouped)): ?>
            <?php
            $no = 1;
            $normSas = static fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
            ?>
            <?php foreach ($grouped as $rows): ?>
                <?php
                $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                $sasTotal = count($rows);
                $autoOpds = ($autoPd ?? [])[$normSas($sasaran)] ?? [];
                if (empty($autoOpds)) {
                    foreach ($rows as $rr) {
                        $ik = $normSas($rr['indikator_sasaran'] ?? '');
                        if ($ik !== '' && !empty(($autoPd ?? [])[$ik])) { $autoOpds = $autoPd[$ik]; break; }
                    }
                }
                $pkSasaranId = (int) ($rows[0]['pk_sasaran_id'] ?? 0);
                $manualOpds  = ($manualPd ?? [])[$pkSasaranId] ?? [];
                $isManual    = !empty($manualOpds);
                $displayOpds = $isManual ? $manualOpds : $autoOpds;
                $sasPrinted = false;
                $pdPrinted = false;
                $noPrinted = false;
                ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php if (!$noPrinted): ?>
                            <td rowspan="<?= $sasTotal ?>" class="c nowrap"><?= $no ?></td>
                            <?php $noPrinted = true; ?>
                        <?php endif; ?>
                        <?php if (!$sasPrinted): ?>
                            <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                            <?php $sasPrinted = true; ?>
                        <?php endif; ?>
                        <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                        <td class="c nowrap"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                        <td class="c"><?= esc($row['satuan'] ?? '-') ?></td>
                        <td class="c"><?= esc($row['indikator_target'] ?? '-') ?></td>
                        <?php if (!$pdPrinted): ?>
                            <td rowspan="<?= $sasTotal ?>" class="text-start">
                                <?php if ($isManual): ?>
                                    <div class="mb-1"><span class="badge-lite badge-manual">Diatur manual</span></div>
                                <?php endif; ?>
                                <?php if (empty($displayOpds)): ?>
                                    <span class="pdf-muted">Belum ditetapkan</span>
                                <?php else: ?>
                                    <?php foreach ($displayOpds as $o): ?>
                                        <div class="mb-1">
                                            <strong><?= esc($o['nama']) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td rowspan="<?= $sasTotal ?>" class="c"><span class="pdf-muted">-</span></td>
                            <?php $pdPrinted = true; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php $no++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="c pdf-muted">Belum ada indikator PK Bupati untuk filter ini.</td></tr>
        <?php endif; ?>
        </tbody>
    <?php else: ?>
        <colgroup>
            <?php // Satu <col> untuk tiap <th>; lebar dinormalkan ke total 100%.
                  // Kolom terakhir mengambil sisa pembulatan agar totalnya persis 100%. ?>
            <?php $sisaLebar = 100.0; $kolKe = 0; ?>
            <?php foreach ($bobotKolom as $bobotKol): ?>
                <?php
                $kolKe++;
                $lebarKol  = ($kolKe === $jumlahKolomOpd)
                    ? $sisaLebar
                    : round($bobotKol * 100 / $totalBobotKolom, 3);
                $sisaLebar = round($sisaLebar - $lebarKol, 3);
                ?>
                <col style="width:<?= $lebarKol ?>%;">
            <?php endforeach; ?>
        </colgroup>
        <thead>
        <tr>
            <th rowspan="2">No</th>
            <?php if ($showOpd): ?><th rowspan="2">OPD</th><?php endif; ?>
            <?php if ($showPejabat): ?><th rowspan="2">Pejabat (Eselon)</th><?php endif; ?>
            <th rowspan="2">Sasaran</th>
            <th rowspan="2">Indikator</th>
            <th rowspan="2">Tahun</th>
            <th rowspan="2">Satuan</th>
            <th rowspan="2"><?= esc($labelUnitHeader) ?></th>
            <th rowspan="2">Anggaran</th>
            <th rowspan="2">Rencana Aksi</th>
            <th rowspan="2">Sub Rencana Aksi</th>
            <?php /* Satuan milik SUB — berbeda dari kolom Satuan di kiri yang
                     milik indikator. Tanpa ini angka triwulan tercetak telanjang. */ ?>
            <th rowspan="2">Satuan</th>
            <th colspan="4">Target Triwulan</th>
            <th rowspan="2">Penanggung Jawab</th>
        </tr>
        <tr><th>I</th><th>II</th><th>III</th><th>IV</th></tr>
        </thead>
        <tbody>
        <?php if (!empty($grouped)): ?>
            <?php
            $no = 1;
            // Tinggi 1 indikator = yang TERTINGGI antara jumlah baris rencana aksi
            // (tiap butir setinggi jumlah sub-nya, min 1) dan jumlah unit. Sisi yang
            // lebih pendek diregangkan lewat rowspan — sama dengan tampilan layar.
            $subMap     = $subMap ?? [];
            $programMap = $programMap ?? [];
            $barisFor = function ($row) use ($splitAksi, $subMap, $programMap) {
                $items = $splitAksi($row['rencana_aksi'] ?? '');
                $subs  = $subMap[(int) ($row['target_id'] ?? 0)] ?? [];
                $nUnit = count($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);

                if (empty($items)) {
                    return [[], [1], max(1, $nUnit), 1];
                }

                $perButir = [];
                foreach ($items as $k => $_) {
                    $perButir[$k] = max(1, count($subs[$k] ?? []));
                }
                $barisRenaksi = array_sum($perButir);

                return [$items, $perButir, max($barisRenaksi, $nUnit), $barisRenaksi];
            };

            // Pembagian tinggi indikator ke jumlah unit kini memakai pk_bagi_baris()
            // dari app/Helpers/pk_unit_helper.php (dulu closure bagiProgram lokal).
            $rupiah = function ($nilai) {
                if (function_exists('formatRupiah')) {
                    return formatRupiah($nilai);
                }
                return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
            };
            $opdTotals = [];
            if ($showOpd) {
                foreach ($grouped as $gr) {
                    $ok = $gr[0]['opd_id'] ?? ($gr[0]['nama_opd'] ?? '-');
                    $t  = 0;
                    foreach ($gr as $grRow) {
                        [, , $nBaris] = $barisFor($grRow);
                        $t += $nBaris;
                    }
                    $opdTotals[$ok] = ($opdTotals[$ok] ?? 0) + $t;
                }
            }
            $curOpdKey = null;
            ?>
            <?php foreach ($grouped as $rows): ?>
                <?php
                $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                $opdKey  = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
                $indCounts = [];
                $sasTotal = 0;
                foreach ($rows as $ri => $r) {
                    [, , $c] = $barisFor($r);
                    $indCounts[$ri] = $c;
                    $sasTotal += $c;
                }
                $newOpd = ($showOpd && $opdKey !== $curOpdKey);
                $sasPrinted = false;
                $noPrinted = false;
                ?>
                <?php foreach ($rows as $ri => $row): ?>
                    <?php
                    [$items, $barisButir, $n] = $barisFor($row);
                    $subsRow = $subMap[(int) ($row['target_id'] ?? 0)] ?? [];
                    $units   = array_values($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);
                    $barisRender = [];
                    foreach ($barisButir as $bk => $bJumlah) {
                        for ($bj = 0; $bj < $bJumlah; $bj++) {
                            $barisRender[] = [$bk, $bj];
                        }
                    }
                    [$spanUnit, $mulaiUnit] = pk_bagi_baris($units, $n);

                    // Baris rencana aksi dibagi rata dengan cara yang sama, supaya
                    // sisa tinggi ketika unit lebih banyak TIDAK jadi blok kosong.
                    [$spanBaris, $mulaiBaris, $spanButir] = pk_bagi_renaksi($barisRender, $n);
                    ?>
                    <?php for ($k = 0; $k < $n; $k++): ?>
                        <?php
                        $riBaris             = $mulaiBaris[$k] ?? null;
                        [$butirIdx, $subIdx] = $riBaris !== null ? $barisRender[$riBaris] : [null, null];
                        $spanRow             = $riBaris !== null ? ($spanBaris[$riBaris] ?? 1) : 1;
                        ?>
                        <tr>
                            <?php if (!$noPrinted): ?>
                                <td rowspan="<?= $sasTotal ?>" class="c nowrap"><?= $no ?></td>
                                <?php $noPrinted = true; ?>
                            <?php endif; ?>
                            <?php if ($showOpd && $newOpd): ?>
                                <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="text-start"><?= esc($row['nama_opd'] ?? '-') ?></td>
                                <?php $curOpdKey = $opdKey; $newOpd = false; ?>
                            <?php endif; ?>
                            <?php if (!$sasPrinted): ?>
                                <?php if ($showPejabat): ?>
                                    <td rowspan="<?= $sasTotal ?>" class="text-start">
                                        <div><strong><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?></strong></div>
                                        <span class="badge-lite"><?= esc($eselonLabel(!empty($eselon ?? null) ? $eselon : ($rows[0]['pk_jenis'] ?? ''), $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? '')) ?></span>
                                    </td>
                                <?php endif; ?>
                                <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                                <?php $sasPrinted = true; ?>
                            <?php endif; ?>
                            <?php if ($k === 0): ?>
                                <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                <td rowspan="<?= $n ?>" class="c nowrap"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                <td rowspan="<?= $n ?>" class="c"><?= esc($row['satuan'] ?? '-') ?></td>

                            <?php endif; ?>

                            <?php // Unit (Program/Kegiatan/Sub Kegiatan) & anggaran: tinggi indikator
                                  // dibagi rata lewat rowspan, jadi sejajar dan tanpa sel kosong. ?>
                            <?php if (empty($units)): ?>
                                <?php if ($k === 0): ?>
                                    <td rowspan="<?= $n ?>" class="c">-</td>
                                    <td rowspan="<?= $n ?>" class="c">-</td>
                                <?php endif; ?>
                            <?php elseif (isset($mulaiUnit[$k])): ?>
                                <?php
                                $ui   = $mulaiUnit[$k];
                                $unit = $units[$ui];
                                $span = $spanUnit[$ui] ?? 1;
                                // 'program' tetap dipakai sebagai alias 'nama' demi data lama.
                                $namaUnitSel = (string) ($unit['nama'] ?? ($unit['program'] ?? ''));
                                // Tandai tingkat unit bila tabel memuat campuran eselon, atau bila
                                // unit ini turun tingkat karena tingkat aslinya kosong.
                                $tandaiTingkat = ($unitHeaderGenerik || !empty($unit['fallback']))
                                    && !empty($unit['level_label']);
                                ?>
                                <td rowspan="<?= $span ?>" class="text-start">
                                    <?php if ($tandaiTingkat): ?>
                                        <div><span class="badge-lite"><?= esc($unit['level_label']) ?></span></div>
                                    <?php endif; ?>
                                    <?= esc(!empty($unit['kode']) ? '[' . $unit['kode'] . '] ' : '') ?><?= esc($namaUnitSel !== '' ? $namaUnitSel : '-') ?>
                                </td>
                                <td rowspan="<?= $span ?>" class="text-start nowrap">
                                    <?= esc($rupiah($unit['anggaran'] ?? 0)) ?>
                                </td>
                            <?php endif; ?>

                            <?php if ($butirIdx !== null): ?>
                                <?php // Rencana Aksi membentang setinggi seluruh sub rencana aksinya ?>
                                <?php if ($subIdx === 0): ?>
                                    <td rowspan="<?= $spanButir[$butirIdx] ?? 1 ?>" class="text-start">
                                        <?= ($items[$butirIdx] ?? '') !== '' ? esc(($butirIdx + 1) . '. ' . $items[$butirIdx]) : '-' ?>
                                    </td>
                                <?php endif; ?>

                                <?php $sub = $subsRow[$butirIdx][$subIdx] ?? null; ?>
                                <td rowspan="<?= $spanRow ?>" class="text-start">
                                    <?= $sub !== null ? esc(($subIdx + 1) . '. ' . $sub['teks']) : '-' ?>
                                </td>

                                <td rowspan="<?= $spanRow ?>">
                                    <?= ($sub !== null && ($sub['satuan'] ?? '') !== '') ? esc($sub['satuan']) : '-' ?>
                                </td>

                                <?php // Target Triwulan mengikuti SUB rencana aksi pada baris ini ?>
                                <?php foreach ([1, 2, 3, 4] as $q): ?>
                                    <?php $nilaiTw = $sub['tw'][$q] ?? null; ?>
                                    <td rowspan="<?= $spanRow ?>" class="c"><?= ($nilaiTw !== null && $nilaiTw !== '') ? esc($nilaiTw) : '-' ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($k === 0): ?>
                                <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                <?php endforeach; ?>
                <?php $no++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <?php // colspan ikut jumlah <col>/<th> yang benar-benar dicetak. ?>
                <td colspan="<?= $jumlahKolomOpd ?>" class="c pdf-muted">
                    Belum ada indikator PK OPD/Kecamatan untuk filter ini.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    <?php endif; ?>
</table>
</body>
</html>

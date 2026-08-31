<?php
helper('capaian');  // capaianFormatPersen() untuk kolom Capaian Total
helper('pk_unit');  // pk_unit_header() & pk_bagi_baris() untuk kolom unit anggaran

// Judul kolom unit anggaran (Program / Kegiatan / Sub Kegiatan) dikirim oleh
// controller. Cadangan dihitung dari filter eselon supaya cetak tetap benar
// bila dipanggil dari jalur lama yang belum mengirim variabel ini.
$labelUnitHeader   = $labelUnitHeader ?? pk_unit_header($eselon ?? null);
$unitHeaderGenerik = (bool) ($unitHeaderGenerik ?? false);

$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$isKab    = (($role ?? '') === 'admin_kab');
$judul    = ($isBupati || !$isKab) ? 'Monitoring Capaian Rencana Aksi' : 'Monitoring Capaian Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');
$showPejabat = $isOpd;

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

$toNum = function ($v) {
    if ($v === null || $v === '') return null;
    $v = str_replace(',', '.', (string) $v);
    return is_numeric($v) ? (float) $v : null;
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

$normSas = static fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
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
        table.monev-print-table {
            table-layout: fixed;
            width: 100%;
            font-size: 7px;
            line-height: 1.14;
        }
        table.monev-print-table thead { display: table-header-group; }
        table.monev-print-table tr { page-break-inside: avoid; }
        table.monev-print-table th,
        table.monev-print-table td {
            padding: 2.5px 3px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.monev-print-table thead th {
            font-size: 6.6px;
            line-height: 1.08;
            padding: 3px 2px;
            white-space: nowrap;
        }
        .text-start { text-align: left; }
        .nowrap { white-space: nowrap; }
        .badge-lite {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 8px;
            background: #e9f6ee;
            color: #155d38;
            font-size: 6.8px;
        }
        .summary-note {
            margin: 0 0 8px;
            font-size: 8px;
            color: #44544b;
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

<?php if (!empty($summary ?? [])): ?>
    <div class="summary-note">
        Total Rencana Aksi: <strong><?= (int) ($summary['renaksi'] ?? 0) ?></strong>
        | Sudah diisi Capaian: <strong><?= (int) ($summary['with_capaian'] ?? 0) ?></strong>
        | Rata-rata Realisasi: <strong><?= ($summary['avg_pct'] ?? null) !== null ? esc($summary['avg_pct']) . '%' : '-' ?></strong>
    </div>
<?php endif; ?>
<?php if (!empty($filterLabels)): ?>
    <div class="filter-note"><?= esc(implode(' | ', $filterLabels)) ?></div>
<?php endif; ?>

<?php
/*
 * LEBAR KOLOM CETAK.
 *
 * mpdf di proyek ini dipakai dengan shrink_tables_to_fit = false dan
 * table-layout: fixed, jadi <colgroup> adalah SATU-SATUNYA kendali lebar:
 * kolom berlebih tidak akan mengecil sendiri. Karena itu daftar di bawah
 * WAJIB berisi persis satu entri untuk tiap <th> baris pertama, dan
 * hasilnya dinormalkan ke 100% supaya kolom opsional (OPD / Pejabat)
 * tidak membuat tabel meluber.
 *
 * Kolom unit sengaja diberi porsi paling lega: nama sub kegiatan jauh
 * lebih panjang daripada nama program.
 */
$kolomLebar = ['no' => 2.5];
if ($showOpd) {
    $kolomLebar['opd'] = 7;
}
if ($showPejabat) {
    $kolomLebar['pejabat'] = 9;
}
$kolomLebar['sasaran']   = 9;
$kolomLebar['indikator'] = 9;
$kolomLebar['satuan']    = 3.5;
$kolomLebar['unit']      = 13;   // Program / Kegiatan / Sub Kegiatan
$kolomLebar['anggaran']  = 5;
foreach ([1, 2, 3, 4] as $q) {
    $kolomLebar['realisasi_' . $q] = 4;
}
$kolomLebar['renaksi'] = 8.5;
$kolomLebar['sub']     = 8.5;
// Satuan milik SUB. WAJIB ikut didaftar: peta ini yang menentukan lebar tiap
// <col> DAN colspan empty-state — menambah <th> tanpa menambah lebarnya
// membuat kedua-duanya meleset satu kolom.
$kolomLebar['subsat']  = 3.5;
foreach ([1, 2, 3, 4] as $q) {
    $kolomLebar['target_' . $q] = 2;
}
foreach ([1, 2, 3, 4] as $q) {
    $kolomLebar['capaian_' . $q] = 2;
}
$kolomLebar['capaian_total'] = 3;
$kolomLebar['pj']            = 6;

// Dipakai juga oleh empty-state di bawah supaya colspan-nya tidak pernah
// ketinggalan lagi saat jumlah kolom berubah.
$kolomTotal  = count($kolomLebar);
$lebarJumlah = array_sum($kolomLebar) ?: 1;
?>
<table class="pdf-table monev-print-table">
    <colgroup>
        <?php foreach ($kolomLebar as $lebarKolom): ?>
            <col style="width:<?= rtrim(rtrim(number_format($lebarKolom / $lebarJumlah * 100, 3, '.', ''), '0'), '.') ?>%;">
        <?php endforeach; ?>
    </colgroup>
    <thead>
    <tr>
        <th rowspan="2">No</th>
        <?php if ($showOpd): ?><th rowspan="2">OPD</th><?php endif; ?>
        <?php if ($showPejabat): ?><th rowspan="2">Pejabat (Eselon)</th><?php endif; ?>
        <th rowspan="2">Sasaran</th>
        <th rowspan="2">Indikator</th>
        <th rowspan="2">Satuan</th>
        <th rowspan="2"><?= esc($labelUnitHeader) ?></th>
        <th rowspan="2">Anggaran</th>
        <?php // Beda dari layar MONEV: cetak tidak punya sub-kolom Aksi, jadi colspan 4 (bukan 5) ?>
        <th colspan="4">Realisasi Anggaran Per Triwulan (Rp)</th>
        <th rowspan="2">Rencana Aksi</th>
        <th rowspan="2">Sub Rencana Aksi</th>
        <?php /* Satuan milik SUB. Ikut dicetak di sini juga: cetakan ini
                 menampilkan baris sub yang sama, dan target maupun capaian
                 triwulannya sama-sama tak berarti tanpa satuan. */ ?>
        <th rowspan="2">Satuan</th>
        <th colspan="4">Target Triwulan</th>
        <th colspan="4">Capaian Triwulan</th>
        <th rowspan="2">Capaian Total</th>
        <th rowspan="2"><?= $isBupati ? 'Penanggung Jawab Perangkat Daerah' : 'Penanggung Jawab' ?></th>
    </tr>
    <tr>
        <th>I</th><th>II</th><th>III</th><th>IV</th>
        <th>I</th><th>II</th><th>III</th><th>IV</th>
        <th>I</th><th>II</th><th>III</th><th>IV</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!empty($grouped)): ?>
        <?php
        $no = 1;
        // Baris MONEV mengikuti SUB rencana aksi; target & capaian triwulan per sub.
        $subMap     = $subMap ?? [];
        $monevSub   = $monevSub ?? [];
        $programMap = $programMap ?? [];
        $rupiah = function ($nilai) {
            if (function_exists('formatRupiah')) {
                return formatRupiah($nilai);
            }
            return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
        };
        $barisFor = function ($row) use ($splitAksi, $subMap, $programMap) {
            $items = $splitAksi($row['rencana_aksi'] ?? '');
            $subs  = $subMap[(int) ($row['target_id'] ?? 0)] ?? [];
            // Jumlah unit anggaran (Program / Kegiatan / Sub Kegiatan) indikator ini.
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

        // Pembagian tinggi indikator ke tiap unit memakai pk_bagi_baris()
        // dari helper pk_unit (dulu closure $bagiProgram di berkas ini).
        $opdTotals = [];
        if ($showOpd) {
            foreach ($grouped as $gr) {
                $ok = $gr[0]['opd_id'] ?? ($gr[0]['nama_opd'] ?? '-');
                $t  = 0;
                foreach ($gr as $grRow) {
                    [, , $nb] = $barisFor($grRow);
                    $t += $nb;
                }
                $opdTotals[$ok] = ($opdTotals[$ok] ?? 0) + $t;
            }
        }
        $curOpdKey = null;
        ?>
        <?php foreach ($grouped as $rows): ?>
            <?php
            $indCounts = [];
            $sasTotal  = 0;
            foreach ($rows as $ri => $r) {
                [, , $c] = $barisFor($r);
                $indCounts[$ri] = $c;
                $sasTotal += $c;
            }
            $printed = false;
            $pdPrinted = false;
            $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
            $autoOpds = $isBupati ? (($autoPd ?? [])[$normSas($sasaran)] ?? []) : [];
            if ($isBupati && empty($autoOpds)) {
                foreach ($rows as $rr) {
                    $ik = $normSas($rr['indikator_sasaran'] ?? '');
                    if ($ik !== '' && !empty(($autoPd ?? [])[$ik])) { $autoOpds = $autoPd[$ik]; break; }
                }
            }
            $opdKey = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
            $newOpd = ($showOpd && $opdKey !== $curOpdKey);
            ?>
            <?php foreach ($rows as $ri => $row): ?>
                <?php
                [$items, $barisButir, $n] = $barisFor($row);
                $targetId  = (int) ($row['target_id'] ?? 0);
                $subsRow   = $subMap[$targetId] ?? [];
                $capaian   = $monevSub[$targetId] ?? [];
                $units     = array_values($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);
                $realisasi = ($anggaranMap ?? [])[$targetId] ?? [];
                // ref_key ':0' = baris realisasi WARISAN, yaitu data lama yang
                // dicatat per indikator dan belum dirinci per unit.
                $warisan = $realisasi[':0'] ?? null;
                // Selama belum ada satu pun realisasi per unit, angka warisan
                // tetap ditampilkan sekali untuk seluruh indikator (seperti
                // cetak versi lama) supaya data lama tidak hilang dari PDF.
                $adaRealisasiUnit = false;
                foreach ($units as $u) {
                    if (isset($realisasi[$u['ref_key'] ?? ''])) {
                        $adaRealisasiUnit = true;
                        break;
                    }
                }
                $modeWarisan = !$adaRealisasiUnit;
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
                        <?php if ($showOpd): ?>
                            <?php if ($newOpd): ?>
                                <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="c nowrap"><?= $no ?></td>
                                <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="text-start"><?= esc($row['nama_opd'] ?? '-') ?></td>
                                <?php $curOpdKey = $opdKey; $no++; $newOpd = false; ?>
                            <?php endif; ?>
                        <?php elseif ($k === 0): ?>
                            <td rowspan="<?= $n ?>" class="c nowrap"><?= $no++ ?></td>
                        <?php endif; ?>

                        <?php if (!$printed): ?>
                            <?php if ($showPejabat): ?>
                                <td rowspan="<?= $sasTotal ?>" class="text-start">
                                    <div><strong><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?></strong></div>
                                    <span class="badge-lite"><?= esc($eselonLabel(!empty($eselon ?? null) ? $eselon : ($rows[0]['pk_jenis'] ?? ''), $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? '')) ?></span>
                                </td>
                            <?php endif; ?>
                            <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                            <?php $printed = true; ?>
                        <?php endif; ?>

                        <?php if ($k === 0): ?>
                            <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['satuan'] ?? '-') ?></td>
                        <?php endif; ?>

                        <?php // Unit (Program/Kegiatan/Sub Kegiatan), pagu, & realisasinya ikut PK, dibagi lewat rowspan ?>
                        <?php if (empty($units)): ?>
                            <?php if ($k === 0): ?>
                                <td rowspan="<?= $n ?>" class="c">-</td>
                                <td rowspan="<?= $n ?>" class="c">-</td>
                            <?php endif; ?>
                        <?php elseif (isset($mulaiUnit[$k])): ?>
                            <?php
                            $ui       = $mulaiUnit[$k];
                            $unit     = $units[$ui];
                            $span     = $spanUnit[$ui] ?? 1;
                            $kodeUnit = $unit['kode'] ?? null;
                            $unitNama = (string) ($unit['nama'] ?? ($unit['program'] ?? ''));
                            ?>
                            <td rowspan="<?= $span ?>" class="text-start">
                                <?php // Kode boleh kosong (kegiatan/sub kegiatan tanpa kode): jangan cetak kurung siku kosong ?>
                                <?= ($kodeUnit !== null && $kodeUnit !== '') ? esc('[' . $kodeUnit . '] ') : '' ?><?= $unitNama !== '' ? esc($unitNama) : '-' ?>
                                <?php // Tabel campuran eselon: sebutkan tingkat unitnya supaya tidak rancu ?>
                                <?php if ($unitHeaderGenerik && !empty($unit['level_label'])): ?>
                                    <span class="badge-lite"><?= esc($unit['level_label']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td rowspan="<?= $span ?>" class="text-start nowrap"><?= esc($rupiah($unit['anggaran'] ?? 0)) ?></td>
                            <?php // Realisasi anggaran: PER UNIT, setinggi unitnya ?>
                            <?php if (!$modeWarisan): ?>
                                <?php $realUnit = $realisasi[$unit['ref_key'] ?? ''] ?? null; ?>
                                <?php foreach ([1, 2, 3, 4] as $q): ?>
                                    <?php $rv = $realUnit['realisasi_triwulan_' . $q] ?? null; ?>
                                    <td rowspan="<?= $span ?>" class="text-start nowrap">
                                        <?= ($rv !== null && $rv !== '') ? esc($rupiah($rv)) : '-' ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php // Belum dirinci per unit: tampilkan baris warisan sekali untuk seluruh indikator ?>
                        <?php if ($modeWarisan && $k === 0): ?>
                            <?php foreach ([1, 2, 3, 4] as $q): ?>
                                <?php $rv = $warisan['realisasi_triwulan_' . $q] ?? null; ?>
                                <td rowspan="<?= $n ?>" class="text-start nowrap">
                                    <?= ($rv !== null && $rv !== '') ? esc($rupiah($rv)) : '-' ?>
                                </td>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($butirIdx !== null): ?>
                            <?php // Rencana Aksi membentang setinggi seluruh sub rencana aksinya ?>
                            <?php if ($subIdx === 0): ?>
                                <td rowspan="<?= $spanButir[$butirIdx] ?? 1 ?>" class="text-start">
                                    <?php $txt = $items[$butirIdx] ?? ''; ?>
                                    <?= ($txt !== '') ? esc(($butirIdx + 1) . '. ' . $txt) : '-' ?>
                                </td>
                            <?php endif; ?>

                            <?php
                            $sub   = $subsRow[$butirIdx][$subIdx] ?? null;
                            $subId = (int) ($sub['id'] ?? 0);
                            $cap   = $capaian[$subId] ?? null;
                            ?>
                            <td rowspan="<?= $spanRow ?>" class="text-start">
                                <?= $sub !== null ? esc(($subIdx + 1) . '. ' . $sub['teks']) : '-' ?>
                            </td>

                            <td rowspan="<?= $spanRow ?>">
                                <?= ($sub !== null && ($sub['satuan'] ?? '') !== '') ? esc($sub['satuan']) : '-' ?>
                            </td>

                            <?php // Target & capaian triwulan sama-sama mengikuti SUB rencana aksi ?>
                            <?php foreach ([1, 2, 3, 4] as $q): ?>
                                <?php $tw = $sub['tw'][$q] ?? null; ?>
                                <td rowspan="<?= $spanRow ?>" class="c"><?= ($tw !== null && $tw !== '') ? esc($tw) : '-' ?></td>
                            <?php endforeach; ?>
                            <?php foreach ([1, 2, 3, 4] as $q): ?>
                                <?php $cv = $cap['capaian_triwulan_' . $q] ?? null; ?>
                                <td rowspan="<?= $spanRow ?>" class="c"><?= ($cv !== null && $cv !== '') ? esc($cv) : '-' ?></td>
                            <?php endforeach; ?>
                            <?php // Capaian Total = persentase hasil hitungan server (monev.total) ?>
                            <td rowspan="<?= $spanRow ?>" class="c"><?= capaianFormatPersen($cap['total'] ?? null) ?></td>
                        <?php endif; ?>

                        <?php if ($k === 0): ?>
                            <?php if ($isBupati): ?>
                                <?php if (!$pdPrinted): ?>
                                    <td rowspan="<?= $sasTotal ?>" class="text-start">
                                        <?php if (empty($autoOpds)): ?>
                                            <span class="pdf-muted">Belum ditetapkan</span>
                                        <?php else: ?>
                                            <?php foreach ($autoOpds as $o): ?>
                                                <div class="mb-1"><strong><?= esc($o['nama']) ?></strong></div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <?php $pdPrinted = true; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                <?php endfor; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <?php // Jumlah kolom diambil dari daftar lebar di atas (sudah termasuk OPD/Pejabat opsional) ?>
            <td colspan="<?= (int) $kolomTotal ?>" class="c pdf-muted">
                Belum ada data Rencana Aksi / MONEV PK untuk filter ini.
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>

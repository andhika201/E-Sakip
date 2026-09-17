<?php
helper('capaian');  // capaianFormatPersen() untuk kolom Capaian Total
helper('pk_unit');  // pk_unit_header() & pk_bagi_baris() untuk kolom unit anggaran
// pdf_td_gabung()/pdf_teks(): kolom induk TANPA rowspan + pemenggalan token
// panjang. Rowspan setinggi satu sasaran/indikator membuat mPDF menyusutkan
// seluruh tabel sampai tak terbaca (MONEV Dinkes pernah tercetak 0,9 pt)
// begitu bloknya lebih tinggi dari satu halaman.
helper('pdf');

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
        /* Tanpa zebra: kolom gabungan (tanpa garis dalam) akan tampak belang bila baris diwarnai selang-seling. */
        table.monev-print-table tbody tr:nth-child(even) td { background: #fff; }
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
            /* Judul kolom boleh turun baris: nowrap memaksa lebar minimum kolom,
               dan begitu jumlahnya melebihi lebar kertas mPDF menyusutkan SELURUH tabel. */
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

// Kepala tabel (colgroup + thead) ditangkap sekali supaya bisa diulang:
// cetak lintas-OPD (admin_kab/bupati) dipecah menjadi SATU TABEL PER OPD.
// mPDF menahan seluruh sel satu tabel di memori sampai tabel selesai; satu
// tabel raksasa untuk semua OPD (>50 ribu sel) menghabiskan >600 MB.
ob_start();
?>
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
<?php
$kepalaTabel = ob_get_clean();
$bukaTabel   = '<table class="pdf-table monev-print-table">' . $kepalaTabel . '<tbody>';
?>
<?= $bukaTabel ?>
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
        // Semua kolom induk dicetak lewat pdf_td_gabung() (tanpa rowspan) —
        // lihat catatan helper('pdf') di atas.
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
        $opdKe     = 0; // posisi baris di dalam grup OPD (kolom No & OPD, admin_kab)
        ?>
        <?php foreach ($grouped as $rows): ?>
            <?php
            $sasTotal = 0;
            foreach ($rows as $r) {
                [, , $c] = $barisFor($r);
                $sasTotal += $c;
            }
            $sasaran = $rows[0]['sasaran_renstra'] ?? '';
            $autoOpds = $isBupati ? (($autoPd ?? [])[$normSas($sasaran)] ?? []) : [];
            if ($isBupati && empty($autoOpds)) {
                foreach ($rows as $rr) {
                    $ik = $normSas($rr['indikator_sasaran'] ?? '');
                    if ($ik !== '' && !empty(($autoPd ?? [])[$ik])) { $autoOpds = $autoPd[$ik]; break; }
                }
            }
            $opdKey = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
            if ($showOpd && $opdKey !== $curOpdKey) {
                if ($curOpdKey !== null) {
                    echo '</tbody></table>', $bukaTabel; // tabel baru per OPD
                }
                // Nomor urut admin_kab = per OPD (satu OPD satu nomor).
                $curOpdKey = $opdKey;
                $opdKe     = 0;
                $noOpd     = $no++;
            }
            $opdTotal = $opdTotals[$opdKey] ?? $sasTotal;
            $sasKe    = 0; // posisi baris di dalam grup sasaran

            $pejabatHtml = '';
            if ($showPejabat) {
                $pejabatHtml = '<div><strong>'
                    . pdf_teks(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? ''))
                    . '</strong></div><span class="badge-lite">'
                    . esc($eselonLabel(!empty($eselon ?? null) ? $eselon : ($rows[0]['pk_jenis'] ?? ''), $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? ''))
                    . '</span>';
            }

            $pdHtml = '';
            if ($isBupati) {
                if (empty($autoOpds)) {
                    $pdHtml = '<span class="pdf-muted">Belum ditetapkan</span>';
                } else {
                    foreach ($autoOpds as $o) {
                        $pdHtml .= '<div class="mb-1"><strong>' . pdf_teks($o['nama']) . '</strong></div>';
                    }
                }
            }
            ?>
            <?php foreach ($rows as $row): ?>
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
                $petaUnit = pdf_grup_per_baris($spanUnit, $mulaiUnit, $n);

                // Baris rencana aksi dibagi rata dengan cara yang sama, supaya
                // sisa tinggi ketika unit lebih banyak TIDAK jadi blok kosong.
                [$spanBaris, $mulaiBaris, $spanButir] = pk_bagi_renaksi($barisRender, $n);
                $petaSub = pdf_grup_per_baris($spanBaris, $mulaiBaris, $n);

                // Posisi tiap baris di dalam grup BUTIR rencana aksinya
                // (satu butir membentang setinggi seluruh sub-nya).
                $petaButir = [];
                $butirSkrg = null;
                $butirKe   = 0;
                for ($k = 0; $k < $n; $k++) {
                    $ri       = $petaSub[$k][0];
                    $butirIdx = $ri !== null ? ($barisRender[$ri][0] ?? null) : null;
                    if ($k === 0 || $butirIdx !== $butirSkrg) {
                        $butirSkrg = $butirIdx;
                        $butirKe   = 0;
                    } else {
                        $butirKe++;
                    }
                    $petaButir[$k] = [$butirIdx, $butirKe, (int) ($spanButir[$butirIdx] ?? 1)];
                }
                $noInd = $showOpd ? null : $no++;
                ?>
                <?php for ($k = 0; $k < $n; $k++): ?>
                    <?php
                    [$riBaris, $subKe, $subJumlah]      = $petaSub[$k];
                    [$butirIdx, $butirKe, $butirJumlah] = $petaButir[$k];
                    $subIdx = $riBaris !== null ? ($barisRender[$riBaris][1] ?? 0) : 0;
                    $sub    = ($butirIdx !== null) ? ($subsRow[$butirIdx][$subIdx] ?? null) : null;
                    $subId  = (int) ($sub['id'] ?? 0);
                    $cap    = $capaian[$subId] ?? null;
                    ?>
                    <tr>
                        <?php if ($showOpd): ?>
                            <?= pdf_td_gabung($opdKe, $opdTotal, (string) $noOpd, 'c nowrap', '', 0) ?>
                            <?= pdf_td_gabung($opdKe, $opdTotal, pdf_teks($row['nama_opd'] ?? ''), 'text-start') ?>
                        <?php else: ?>
                            <?= pdf_td_gabung($k, $n, (string) $noInd, 'c nowrap', '', 0) ?>
                        <?php endif; ?>

                        <?php if ($showPejabat): ?>
                            <?= pdf_td_gabung($sasKe, $sasTotal, $pejabatHtml, 'text-start') ?>
                        <?php endif; ?>
                        <?= pdf_td_gabung($sasKe, $sasTotal, pdf_teks($sasaran), 'text-start') ?>

                        <?= pdf_td_gabung($k, $n, pdf_teks($row['indikator_sasaran'] ?? ''), 'text-start') ?>
                        <?= pdf_td_gabung($k, $n, esc($row['satuan'] ?? ''), 'c') ?>

                        <?php // Unit (Program/Kegiatan/Sub Kegiatan), pagu, & realisasinya ikut PK,
                              // tinggi indikator dibagi rata antar unit ?>
                        <?php if (empty($units)): ?>
                            <?= pdf_td_gabung($k, $n, '', 'c') ?>
                            <?= pdf_td_gabung($k, $n, '', 'c') ?>
                        <?php else: ?>
                            <?php
                            [$ui, $unitKe, $unitJumlah] = $petaUnit[$k];
                            $unit     = $units[$ui] ?? [];
                            $kodeUnit = $unit['kode'] ?? null;
                            $unitNama = (string) ($unit['nama'] ?? ($unit['program'] ?? ''));
                            // Kode boleh kosong (kegiatan/sub kegiatan tanpa kode): jangan cetak kurung siku kosong.
                            // Tabel campuran eselon: sebutkan tingkat unitnya supaya tidak rancu.
                            $unitHtml = (($kodeUnit !== null && $kodeUnit !== '') ? pdf_teks('[' . $kodeUnit . '] ') : '')
                                . pdf_teks($unitNama)
                                . (($unitHeaderGenerik && !empty($unit['level_label'])) ? ' <span class="badge-lite">' . esc($unit['level_label']) . '</span>' : '');
                            ?>
                            <?= pdf_td_gabung($unitKe, $unitJumlah, $unitHtml, 'text-start') ?>
                            <?php // Tanpa nowrap: nominal boleh turun baris setelah "Rp" — lebar minimum
                                  // yang dipaksa nowrap membuat mPDF menyusutkan seluruh tabel. ?>
                            <?= pdf_td_gabung($unitKe, $unitJumlah, esc($rupiah($unit['anggaran'] ?? 0)), 'text-start') ?>
                            <?php // Realisasi anggaran: PER UNIT, setinggi unitnya ?>
                            <?php if (!$modeWarisan): ?>
                                <?php $realUnit = $realisasi[$unit['ref_key'] ?? ''] ?? null; ?>
                                <?php foreach ([1, 2, 3, 4] as $q): ?>
                                    <?php $rv = $realUnit['realisasi_triwulan_' . $q] ?? null; ?>
                                    <?= pdf_td_gabung($unitKe, $unitJumlah, ($rv !== null && $rv !== '') ? esc($rupiah($rv)) : '', 'text-start') ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php // Belum dirinci per unit: tampilkan baris warisan sekali untuk seluruh indikator ?>
                        <?php if ($modeWarisan): ?>
                            <?php foreach ([1, 2, 3, 4] as $q): ?>
                                <?php $rv = $warisan['realisasi_triwulan_' . $q] ?? null; ?>
                                <?= pdf_td_gabung($k, $n, ($rv !== null && $rv !== '') ? esc($rupiah($rv)) : '', 'text-start') ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php // Rencana Aksi membentang setinggi seluruh sub rencana aksinya ?>
                        <?= pdf_td_gabung($butirKe, $butirJumlah, ($items[$butirIdx] ?? '') !== '' ? pdf_teks(($butirIdx + 1) . '. ' . $items[$butirIdx]) : '', 'text-start') ?>

                        <?= pdf_td_gabung($subKe, $subJumlah, $sub !== null ? pdf_teks(($subIdx + 1) . '. ' . $sub['teks']) : '', 'text-start') ?>
                        <?= pdf_td_gabung($subKe, $subJumlah, ($sub !== null && ($sub['satuan'] ?? '') !== '') ? esc($sub['satuan']) : '') ?>

                        <?php // Target & capaian triwulan sama-sama mengikuti SUB rencana aksi ?>
                        <?php foreach ([1, 2, 3, 4] as $q): ?>
                            <?php $tw = $sub['tw'][$q] ?? null; ?>
                            <?= pdf_td_gabung($subKe, $subJumlah, ($tw !== null && $tw !== '') ? esc($tw) : '', 'c') ?>
                        <?php endforeach; ?>
                        <?php foreach ([1, 2, 3, 4] as $q): ?>
                            <?php $cv = $cap['capaian_triwulan_' . $q] ?? null; ?>
                            <?= pdf_td_gabung($subKe, $subJumlah, ($cv !== null && $cv !== '') ? esc($cv) : '', 'c') ?>
                        <?php endforeach; ?>
                        <?php // Capaian Total = persentase hasil hitungan server (monev.total) ?>
                        <?= pdf_td_gabung($subKe, $subJumlah, capaianFormatPersen($cap['total'] ?? null, ''), 'c') ?>

                        <?php if ($isBupati): ?>
                            <?= pdf_td_gabung($sasKe, $sasTotal, $pdHtml, 'text-start') ?>
                        <?php else: ?>
                            <?= pdf_td_gabung($k, $n, pdf_teks($row['penanggung_jawab'] ?? ''), 'text-start') ?>
                        <?php endif; ?>
                    </tr>
                    <?php $sasKe++; $opdKe++; ?>
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

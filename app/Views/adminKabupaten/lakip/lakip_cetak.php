<?php
helper(['number', 'lakip']);
// pdf_td_gabung()/pdf_teks(): kolom induk (No/OPD/Sasaran) TANPA rowspan.
// Rowspan setinggi satu OPD (mode OPD, seluruh OPD) membuat mPDF memindah
// atau menyusutkan blok utuh begitu lebih tinggi dari sisa halaman.
helper('pdf');

$filters = $filters ?? [];
$rows = $rows ?? [];
$lakipMap = $lakipMap ?? [];
$mode = $mode ?? 'kabupaten';
$tahun = (string) ($filters['tahun'] ?? '');
$statusFilter = (string) ($filters['status'] ?? '');
$unitName = $unitName ?? (($mode === 'opd') ? 'Seluruh OPD' : 'Kabupaten Pringsewu');
$modeLabel = ($mode === 'opd') ? 'OPD (RENSTRA)' : 'Kabupaten (RPJMD)';

$statusLabel = static function ($status) {
    $status = strtolower(trim((string) $status));
    if ($status === 'selesai') {
        return 'Selesai';
    }
    if ($status === 'draft') {
        return 'Draft';
    }
    return $status !== '' ? ucfirst($status) : '-';
};

$opdCounts = [];
$sasCounts = [];
if ($mode === 'opd') {
    foreach ($rows as $r) {
        $opdKey = (string) ($r['opd_id'] ?? '');
        $sasKey = $opdKey . '|' . (string) ($r['sasaran'] ?? '');
        $opdCounts[$opdKey] = ($opdCounts[$opdKey] ?? 0) + 1;
        $sasCounts[$sasKey] = ($sasCounts[$sasKey] ?? 0) + 1;
    }
} else {
    foreach ($rows as $r) {
        $sasKey = (string) ($r['sasaran'] ?? '');
        $sasCounts[$sasKey] = ($sasCounts[$sasKey] ?? 0) + 1;
    }
}
$opdKe = []; // posisi baris di dalam grup OPD / sasaran (mulai 0)
$sasKe = [];

// Lebar kolom (%) untuk kertas POTRAIT; total selalu 100% supaya mPDF tidak
// perlu menormalkan ulang. Mode OPD punya satu kolom ekstra (OPD).
$w = ($mode === 'opd')
    ? ['no' => 4, 'opd' => 13, 'sasaran' => 15, 'indikator' => 18, 'satuan' => 6, 'tahun' => 5, 'target' => 7, 'tlalu' => 8, 'clalu' => 8, 'cini' => 8, 'persen' => 8]
    : ['no' => 4, 'opd' => 0, 'sasaran' => 19, 'indikator' => 22, 'satuan' => 7, 'tahun' => 6, 'target' => 8, 'tlalu' => 9, 'clalu' => 9, 'cini' => 9, 'persen' => 7];
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
            font-size: 8.6px;
            color: #526158;
            text-align: right;
        }
        /* Kertas POTRAIT (A4 tegak): lebar cetak jauh lebih sempit dari
           landscape, jadi font & padding tabel dirapatkan dan teks panjang
           dibiarkan membungkus ke bawah. */
        table.lakip-print-table {
            font-size: 7px;
            line-height: 1.15;
        }
        table.lakip-print-table thead { display: table-header-group; }
        /* Tanpa zebra: kolom gabungan (tanpa garis dalam) akan tampak belang bila baris diwarnai selang-seling. */
        table.lakip-print-table tbody tr:nth-child(even) td { background: #fff; }
        table.lakip-print-table th,
        table.lakip-print-table td {
            padding: 2px 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }
        table.lakip-print-table thead th {
            font-size: 6.4px;
            line-height: 1.1;
            padding: 2px 2px;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        /* Judul dua tabel tambahan (Analisis Faktor & Efisiensi Program) */
        .addendum-judul {
            font-size: 11px;
            font-weight: bold;
            color: #00743e;
            margin: 0 0 2px;
            text-align: center;
        }
        .addendum-sub {
            font-size: 8.4px;
            color: #526158;
            text-align: center;
            margin: 0 0 6px;
        }

        /* ===== IDENTITAS DOKUMEN (pengganti KOP) =====
           Cetak LAKIP TIDAK memakai kop surat, logo instansi, watermark,
           header, maupun footer halaman. Dokumen langsung dimulai dari judul
           di bawah ini. Lihat catatan di AdminKab\LakipController::cetak(). */
        .lakip-doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #15311f;
            margin: 0 0 3px;
        }
        .lakip-doc-unit {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #15311f;
            margin: 0 0 2px;
        }
        .lakip-doc-sub {
            text-align: center;
            font-size: 9px;
            color: #555;
            margin: 0 0 10px;
        }
    </style>
</head>
<body>
    <?php
    // Identitas dokumen: judul + nama Kabupaten/OPD + tahun + konteks mode.
    $unitTxt  = trim((string) ($unitName ?? ''));
    $isUnitPd = ($mode === 'opd' && $unitTxt !== '' && $unitTxt !== 'Seluruh OPD');
    ?>
    <div class="lakip-doc-title">Laporan Akuntabilitas Kinerja Instansi Pemerintah</div>
    <?php if ($unitTxt !== ''): ?>
        <div class="lakip-doc-unit"><?= esc(($isUnitPd ? 'Perangkat Daerah: ' : '') . $unitTxt) ?></div>
    <?php endif; ?>
    <div class="lakip-doc-sub">
        Tahun <?= esc($tahun !== '' ? $tahun : '-') ?> &middot; Lingkup <?= esc($modeLabel) ?>
        <?php if (! empty($sumberLakip) && ($sumberLakip['sumber'] ?? '') === 'iku'): ?>
            &middot; IKU Version ID <?= (int) ($sumberLakip['versi']['id'] ?? 0) ?>
        <?php endif; ?>
    </div>

    <div class="filter-note">Status: <?= esc($statusFilter !== '' ? $statusLabel($statusFilter) : 'Semua Status') ?></div>

    <table class="pdf-table lakip-print-table">
        <thead>
            <tr>
                <th style="width: <?= $w['no'] ?>%;">NO</th>
                <?php if ($mode === 'opd'): ?>
                    <th style="width: <?= $w['opd'] ?>%;">OPD</th>
                <?php endif; ?>
                <th style="width: <?= $w['sasaran'] ?>%;">SASARAN</th>
                <th style="width: <?= $w['indikator'] ?>%;">INDIKATOR</th>
                <th style="width: <?= $w['satuan'] ?>%;">SATUAN</th>
                <th style="width: <?= $w['tahun'] ?>%;">TAHUN</th>
                <th style="width: <?= $w['target'] ?>%;">TARGET</th>
                <th style="width: <?= $w['tlalu'] ?>%;">TARGET TAHUN SEBELUMNYA</th>
                <th style="width: <?= $w['clalu'] ?>%;">CAPAIAN TAHUN SEBELUMNYA</th>
                <th style="width: <?= $w['cini'] ?>%;">CAPAIAN TAHUN INI</th>
                <th style="width: <?= $w['persen'] ?>%;">CAPAIAN (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= ($mode === 'opd') ? 11 : 10 ?>" class="text-center">
                        Data target belum tersedia untuk filter ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $targetId = (int) ($r['target_id'] ?? 0);
                    $lakipItem = $lakipMap[$targetId] ?? null;
                    $jenisIndikator = $r['jenis_indikator'] ?? 'indikator positif';
                    $targetNow = $r['target_tahun_ini'] ?? null;
                    $realisasiNow = $lakipItem['capaian_tahun_ini'] ?? null;
                    $targetCalc = (isset($lakipItem['target_hitung']) && $lakipItem['target_hitung'] !== '') ? $lakipItem['target_hitung'] : $targetNow;
                    $realisasiCalc = (isset($lakipItem['capaian_hitung']) && $lakipItem['capaian_hitung'] !== '') ? $lakipItem['capaian_hitung'] : $realisasiNow;
                    $capaianPersen = hitungCapaianLakip($targetCalc, $realisasiCalc, $jenisIndikator);

                    if ($mode === 'opd') {
                        $opdKey = (string) ($r['opd_id'] ?? '');
                        $sasKey = $opdKey . '|' . (string) ($r['sasaran'] ?? '');
                    } else {
                        $sasKey = (string) ($r['sasaran'] ?? '');
                    }
                    $sasKe[$sasKey] = isset($sasKe[$sasKey]) ? $sasKe[$sasKey] + 1 : 0;
                    if ($sasKe[$sasKey] === 0) {
                        $noSas[$sasKey] = $no++;
                    }
                    ?>
                    <tr>
                        <?= pdf_td_gabung($sasKe[$sasKey], $sasCounts[$sasKey] ?? 1, (string) $noSas[$sasKey], 'text-center', '', 0) ?>

                        <?php if ($mode === 'opd'): ?>
                            <?php $opdKe[$opdKey] = isset($opdKe[$opdKey]) ? $opdKe[$opdKey] + 1 : 0; ?>
                            <?= pdf_td_gabung($opdKe[$opdKey], $opdCounts[$opdKey] ?? 1, pdf_teks($r['nama_opd'] ?? ''), 'text-start') ?>
                        <?php endif; ?>

                        <?= pdf_td_gabung($sasKe[$sasKey], $sasCounts[$sasKey] ?? 1, pdf_teks($r['sasaran'] ?? ''), 'text-start') ?>

                        <td class="text-start"><?= pdf_teks($r['indikator_sasaran'] ?? '') ?></td>
                        <td class="text-center"><?= esc($r['satuan'] ?? '') ?></td>
                        <td class="text-center"><?= esc($r['tahun'] ?? '') ?></td>
                        <td class="text-center"><?= esc($targetNow ?? '') ?></td>
                        <td class="text-center"><?= esc($lakipItem['target_lalu'] ?? '') ?></td>
                        <td class="text-center"><?= esc($lakipItem['capaian_lalu'] ?? '') ?></td>
                        <td class="text-center"><?= ($lakipItem['capaian_tahun_ini'] ?? '') !== '' ? formatAtauRaw($lakipItem['capaian_tahun_ini'], 2) : '' ?></td>
                        <td class="text-center"><?= $capaianPersen === null ? '' : formatAngkaID($capaianPersen, 2) . '%' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php // Analisis Faktor Pencapaian Kinerja + Efisiensi Program dan Anggaran ?>
    <?= $this->include('lakip/addendum_cetak') ?>
</body>
</html>

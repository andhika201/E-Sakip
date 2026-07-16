<?php
helper(['number', 'lakip']);

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
$opdSeen = [];
$sasSeen = [];
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
        table.lakip-print-table {
            font-size: 8px;
            line-height: 1.18;
        }
        table.lakip-print-table thead { display: table-header-group; }
        table.lakip-print-table tr { page-break-inside: avoid; }
        table.lakip-print-table th,
        table.lakip-print-table td {
            padding: 3px 4px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }
        table.lakip-print-table thead th {
            font-size: 7.4px;
            line-height: 1.12;
            padding: 3px 3px;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
    </style>
</head>
<body>
    <?php
    $subjudulParts = [];
    $unitTxt = trim((string) ($unitName ?? ''));
    if ($unitTxt !== '') {
        $subjudulParts[] = ($mode === 'opd' && $unitTxt !== 'Seluruh OPD') ? 'Perangkat Daerah: ' . $unitTxt : $unitTxt;
    }
    $subjudulParts[] = 'Tahun ' . ($tahun !== '' ? $tahun : '-');
    $subjudulParts[] = $modeLabel;
    ?>
    <?php $this->setData([
        'judul'      => 'Laporan Akuntabilitas Kinerja Instansi Pemerintah Daerah',
        'subjudul'   => implode(' - ', $subjudulParts),
        'namaUnit'   => ($mode === 'opd' && $unitTxt !== 'Seluruh OPD') ? strtoupper($unitTxt) : '',
        'logoOnly'   => false,
        'hideAksara' => true,
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <div class="filter-note">Status: <?= esc($statusFilter !== '' ? $statusLabel($statusFilter) : 'Semua Status') ?></div>

    <table class="pdf-table lakip-print-table">
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <?php if ($mode === 'opd'): ?>
                    <th style="width: 14%;">OPD</th>
                <?php endif; ?>
                <th style="width: 17%;">SASARAN</th>
                <th style="width: 20%;">INDIKATOR</th>
                <th style="width: 7%;">SATUAN</th>
                <th style="width: 6%;">TAHUN</th>
                <th style="width: 8%;">TARGET</th>
                <th style="width: 9%;">TARGET TAHUN SEBELUMNYA</th>
                <th style="width: 9%;">CAPAIAN TAHUN SEBELUMNYA</th>
                <th style="width: 8%;">CAPAIAN TAHUN INI</th>
                <th style="width: 7%;">CAPAIAN (%)</th>
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
                    $sasFirst = empty($sasSeen[$sasKey]);
                    ?>
                    <tr>
                        <?php if ($sasFirst): ?>
                            <td rowspan="<?= $sasCounts[$sasKey] ?? 1 ?>" class="text-center"><?= $no++ ?></td>
                        <?php endif; ?>

                        <?php if ($mode === 'opd'): ?>
                            <?php if (empty($opdSeen[$opdKey])):
                                $opdSeen[$opdKey] = true; ?>
                                <td rowspan="<?= $opdCounts[$opdKey] ?? 1 ?>" class="text-start">
                                    <?= esc($r['nama_opd'] ?? '-') ?>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($sasFirst):
                            $sasSeen[$sasKey] = true; ?>
                            <td rowspan="<?= $sasCounts[$sasKey] ?? 1 ?>" class="text-start">
                                <?= esc($r['sasaran'] ?? '-') ?>
                            </td>
                        <?php endif; ?>

                        <td class="text-start"><?= esc($r['indikator_sasaran'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($r['satuan'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($r['tahun'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($targetNow ?? '-') ?></td>
                        <td class="text-center"><?= esc($lakipItem['target_lalu'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($lakipItem['capaian_lalu'] ?? '-') ?></td>
                        <td class="text-center"><?= formatAtauRaw($lakipItem['capaian_tahun_ini'] ?? null, 2) ?></td>
                        <td class="text-center"><?= $capaianPersen === null ? '-' : formatAngkaID($capaianPersen, 2) . '%' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

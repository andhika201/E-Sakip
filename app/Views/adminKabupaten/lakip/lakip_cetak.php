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
    <style>
        body { font-family: sans-serif; font-size: 9pt; color: #222; }
        h1 { margin: 0; text-align: center; font-size: 14pt; }
        .meta { margin: 10px 0 12px; width: 100%; font-size: 9pt; }
        .meta td { padding: 2px 4px; border: 0; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 0.6px solid #666; padding: 4px; vertical-align: middle; }
        table.report th { background: #d9ead3; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
    </style>
</head>
<body>
    <h1>LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH</h1>

    <table class="meta">
        <tr>
            <td style="width: 90px;">Unit</td>
            <td style="width: 8px;">:</td>
            <td><?= esc($unitName) ?></td>
        </tr>
        <tr>
            <td>Mode</td>
            <td>:</td>
            <td><?= esc($modeLabel) ?></td>
        </tr>
        <tr>
            <td>Tahun</td>
            <td>:</td>
            <td><?= esc($tahun !== '' ? $tahun : '-') ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td>:</td>
            <td><?= esc($statusFilter !== '' ? $statusLabel($statusFilter) : 'Semua Status') ?></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 28px;">NO</th>
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
                <th style="width: 7%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= ($mode === 'opd') ? 12 : 11 ?>" class="text-center">
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
                        <td class="text-center"><?= esc($statusLabel($lakipItem['status'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

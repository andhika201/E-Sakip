<?php
$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$isKab    = (($role ?? '') === 'admin_kab');
$judul    = ($isBupati || !$isKab) ? 'Monitoring Capaian Rencana Aksi' : 'Monitoring Capaian Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');
$showPejabat = $isOpd;

$eselonLabel = function ($pkJenis) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Camat (Eselon III)', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    return $map[$pkJenis] ?? '-';
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

<table class="pdf-table monev-print-table">
    <colgroup>
        <col style="width:3.5%;">
        <?php if ($showOpd): ?><col style="width:8.5%;"><?php endif; ?>
        <?php if ($showPejabat): ?><col style="width:11%;"><?php endif; ?>
        <col style="width:11%;">
        <col style="width:11%;">
        <col style="width:5.5%;">
        <col style="width:11%;">
        <col style="width:5.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:4.5%;">
        <col style="width:6%;">
        <col style="width:9%;">
    </colgroup>
    <thead>
    <tr>
        <th rowspan="2">No</th>
        <?php if ($showOpd): ?><th rowspan="2">OPD</th><?php endif; ?>
        <?php if ($showPejabat): ?><th rowspan="2">Pejabat (Eselon)</th><?php endif; ?>
        <th rowspan="2">Sasaran</th>
        <th rowspan="2">Indikator</th>
        <th rowspan="2">Satuan</th>
        <th rowspan="2">Rencana Aksi</th>
        <th rowspan="2">Baseline</th>
        <th colspan="4">Target Triwulan</th>
        <th colspan="4">Capaian Triwulan</th>
        <th rowspan="2">Capaian Total</th>
        <th rowspan="2"><?= $isBupati ? 'Penanggung Jawab Perangkat Daerah' : 'Penanggung Jawab' ?></th>
    </tr>
    <tr>
        <th>I</th><th>II</th><th>III</th><th>IV</th>
        <th>I</th><th>II</th><th>III</th><th>IV</th>
    </tr>
    </thead>
    <tbody>
    <?php if (!empty($grouped)): ?>
        <?php
        $no = 1;
        $opdTotals = [];
        if ($showOpd) {
            foreach ($grouped as $gr) {
                $ok = $gr[0]['opd_id'] ?? ($gr[0]['nama_opd'] ?? '-');
                $t  = 0;
                foreach ($gr as $grRow) {
                    $t += max(1, count($splitAksi($grRow['rencana_aksi'] ?? '')));
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
                $c = max(1, count($splitAksi($r['rencana_aksi'] ?? '')));
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
                <?php $items = $splitAksi($row['rencana_aksi'] ?? ''); $n = $indCounts[$ri]; ?>
                <?php for ($k = 0; $k < $n; $k++): ?>
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
                                    <span class="badge-lite"><?= esc($eselonLabel($rows[0]['pk_jenis'] ?? '')) ?></span>
                                </td>
                            <?php endif; ?>
                            <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                            <?php $printed = true; ?>
                        <?php endif; ?>

                        <?php if ($k === 0): ?>
                            <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['satuan'] ?? '-') ?></td>
                        <?php endif; ?>

                        <td class="text-start">
                            <?php $txt = $items[$k] ?? ''; echo ($txt !== '') ? esc($txt) : ($n === 1 ? '-' : ''); ?>
                        </td>

                        <?php if ($k === 0): ?>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['indikator_target'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['capaian_triwulan_1'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['capaian_triwulan_2'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['capaian_triwulan_3'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['capaian_triwulan_4'] ?? '-') ?></td>
                            <td rowspan="<?= $n ?>" class="c"><?= esc($row['monev_total'] ?? '-') ?></td>
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
            <td colspan="<?= 16 + ($showPejabat ? 1 : 0) + ($showOpd ? 1 : 0) ?>" class="c pdf-muted">
                Belum ada data Rencana Aksi / MONEV PK untuk filter ini.
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>

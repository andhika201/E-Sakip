<?php
$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$isKab    = (($role ?? '') === 'admin_kab');
$judul    = ($isBupati || !$isKab) ? 'Target dan Rencana Aksi' : 'Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');
$showPejabat = $isOpd;

$eselonLabel = function ($pkJenis) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Camat (Eselon III)', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    return $map[$pkJenis] ?? '-';
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
            <col style="width:4%;">
            <?php if ($showOpd): ?><col style="width:10%;"><?php endif; ?>
            <?php if ($showPejabat): ?><col style="width:13%;"><?php endif; ?>
            <col style="width:13%;">
            <col style="width:14%;">
            <col style="width:6%;">
            <col style="width:7%;">
            <col style="width:15%;">
            <col style="width:5%;">
            <col style="width:5%;">
            <col style="width:5%;">
            <col style="width:5%;">
            <col style="width:13%;">
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
            <th rowspan="2">Rencana Aksi</th>
            <th colspan="4">Target Triwulan</th>
            <th rowspan="2">Penanggung Jawab</th>
        </tr>
        <tr><th>I</th><th>II</th><th>III</th><th>IV</th></tr>
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
                $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                $opdKey  = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
                $indCounts = [];
                $sasTotal = 0;
                foreach ($rows as $ri => $r) {
                    $c = max(1, count($splitAksi($r['rencana_aksi'] ?? '')));
                    $indCounts[$ri] = $c;
                    $sasTotal += $c;
                }
                $newOpd = ($showOpd && $opdKey !== $curOpdKey);
                $sasPrinted = false;
                $noPrinted = false;
                ?>
                <?php foreach ($rows as $ri => $row): ?>
                    <?php $items = $splitAksi($row['rencana_aksi'] ?? ''); $n = $indCounts[$ri]; ?>
                    <?php for ($k = 0; $k < $n; $k++): ?>
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
                                        <span class="badge-lite"><?= esc($eselonLabel($rows[0]['pk_jenis'] ?? '')) ?></span>
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
                            <td class="text-start"><?= ($items[$k] ?? '') !== '' ? esc($items[$k]) : ($n === 1 ? '-' : '') ?></td>
                            <?php if ($k === 0): ?>
                                <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                <td rowspan="<?= $n ?>" class="c"><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                <?php endforeach; ?>
                <?php $no++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= 11 + ($showPejabat ? 1 : 0) + ($showOpd ? 1 : 0) ?>" class="c pdf-muted">
                    Belum ada indikator PK Eselon II/III/IV untuk filter ini.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    <?php endif; ?>
</table>
</body>
</html>

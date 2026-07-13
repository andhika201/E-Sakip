<?php
helper(['number', 'lakip']);

$filters = $filters ?? [];
$tahunAktif = (string) ($filters['tahun'] ?? '');
$statusFilter = (string) ($filters['status'] ?? '');
$dataSource = $dataSource ?? [];
$lakipMap = $lakipMap ?? [];
$unitName = $unitName ?? (($opdInfo['nama_opd'] ?? '') ?: 'Perangkat Daerah');
$modeLabel = (($mode ?? 'opd') === 'kabupaten') ? 'Kabupaten (RPJMD)' : 'OPD (RENSTRA)';

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
            <td><?= esc($tahunAktif !== '' ? $tahunAktif : '-') ?></td>
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
                <th style="width: 18%;">SASARAN</th>
                <th style="width: 22%;">INDIKATOR</th>
                <th style="width: 7%;">SATUAN</th>
                <th style="width: 6%;">TAHUN</th>
                <th style="width: 9%;">TARGET TAHUN SEBELUMNYA</th>
                <th style="width: 9%;">CAPAIAN TAHUN SEBELUMNYA</th>
                <th style="width: 8%;">TARGET</th>
                <th style="width: 8%;">CAPAIAN TAHUN INI</th>
                <th style="width: 7%;">CAPAIAN (%)</th>
                <th style="width: 7%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($dataSource as $row): ?>
                <?php
                $sasaranText = $row['sasaran'] ?? ($row['sasaran_rpjmd'] ?? '-');
                $indikatorList = $row['indikator_sasaran'] ?? [];
                $indikatorCount = count($indikatorList);
                $firstRow = true;
                ?>
                <?php foreach ($indikatorList as $indikator): ?>
                    <?php
                    $indikatorId = (int) ($indikator['id'] ?? $indikator['indikator_id'] ?? $indikator['renstra_indikator_id'] ?? 0);
                    $lakipItem = $indikatorId ? ($lakipMap[$indikatorId] ?? null) : null;

                    $tahunRow = (string) (
                        $tahunAktif !== '' ? $tahunAktif :
                        ($indikator['tahun'] ?? $row['tahun'] ?? date('Y'))
                    );

                    $targetTahun = null;
                    $targets = $indikator['target_tahunan'] ?? null;
                    if (is_array($targets)) {
                        $isAssoc = array_keys($targets) !== range(0, count($targets) - 1);
                        if ($isAssoc) {
                            $targetTahun = $targets[$tahunRow] ?? $targets[(int) $tahunRow] ?? null;
                        } else {
                            foreach ($targets as $t) {
                                $th = (string) ($t['tahun'] ?? $t['indikator_tahun'] ?? '');
                                if ($th === $tahunRow) {
                                    $targetTahun = $t['target']
                                        ?? $t['target_tahunan']
                                        ?? $t['target_tahun_ini']
                                        ?? $t['nilai_target']
                                        ?? null;
                                    break;
                                }
                            }
                        }
                    }

                    if ($targetTahun === null) {
                        $targetTahun = $indikator['target_tahun_ini']
                            ?? $indikator['target']
                            ?? $row['target_tahun_ini']
                            ?? $row['target']
                            ?? null;
                    }

                    $jenisIndikator = $indikator['jenis_indikator'] ?? ($row['jenis_indikator'] ?? 'indikator positif');
                    $realisasiNow = $lakipItem['capaian_tahun_ini'] ?? null;
                    $targetCalc = (isset($lakipItem['target_hitung']) && $lakipItem['target_hitung'] !== '') ? $lakipItem['target_hitung'] : $targetTahun;
                    $realisasiCalc = (isset($lakipItem['capaian_hitung']) && $lakipItem['capaian_hitung'] !== '') ? $lakipItem['capaian_hitung'] : $realisasiNow;
                    $capaianPersen = hitungCapaianLakip($targetCalc, $realisasiCalc, $jenisIndikator);
                    ?>
                    <tr>
                        <?php if ($firstRow): ?>
                            <td rowspan="<?= $indikatorCount ?>" class="text-center"><?= $no++ ?></td>
                            <td rowspan="<?= $indikatorCount ?>" class="text-start"><?= esc($sasaranText) ?></td>
                            <?php $firstRow = false; ?>
                        <?php endif; ?>
                        <td class="text-start"><?= esc($indikator['indikator_sasaran'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($indikator['satuan'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($tahunRow) ?></td>
                        <td class="text-center"><?= esc($lakipItem['target_lalu'] ?? '-') ?></td>
                        <td class="text-center"><?= esc($lakipItem['capaian_lalu'] ?? '-') ?></td>
                        <td class="text-center"><?= ($targetTahun !== null && $targetTahun !== '') ? esc((string) $targetTahun) : '-' ?></td>
                        <td class="text-center"><?= $realisasiNow !== null ? esc((string) $realisasiNow) : '-' ?></td>
                        <td class="text-center"><?= $capaianPersen === null ? '-' : formatAngkaID($capaianPersen, 2) . '%' ?></td>
                        <td class="text-center"><?= esc($statusLabel($lakipItem['status'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if (empty($dataSource)): ?>
                <tr>
                    <td colspan="11" class="text-center">Belum ada data sasaran / indikator pada filter ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

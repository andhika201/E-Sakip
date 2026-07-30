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
           di bawah ini. Lihat catatan di AdminOpd\LakipOpdController::cetak(). */
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
    $isUnitPd = ($unitTxt !== '' && ($mode ?? 'opd') !== 'kabupaten');
    ?>
    <div class="lakip-doc-title">Laporan Akuntabilitas Kinerja Instansi Pemerintah</div>
    <?php if ($unitTxt !== ''): ?>
        <div class="lakip-doc-unit"><?= esc(($isUnitPd ? 'Perangkat Daerah: ' : '') . $unitTxt) ?></div>
    <?php endif; ?>
    <div class="lakip-doc-sub">
        Tahun <?= esc($tahunAktif !== '' ? $tahunAktif : '-') ?> &middot; Lingkup <?= esc($modeLabel) ?>
    </div>

    <div class="filter-note">Status: <?= esc($statusFilter !== '' ? $statusLabel($statusFilter) : 'Semua Status') ?></div>

    <table class="pdf-table lakip-print-table">
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 18%;">SASARAN</th>
                <th style="width: 22%;">INDIKATOR</th>
                <th style="width: 7%;">SATUAN</th>
                <th style="width: 6%;">TAHUN</th>
                <th style="width: 9%;">TARGET TAHUN SEBELUMNYA</th>
                <th style="width: 9%;">CAPAIAN TAHUN SEBELUMNYA</th>
                <th style="width: 8%;">TARGET</th>
                <th style="width: 8%;">CAPAIAN TAHUN INI</th>
                <th style="width: 7%;">CAPAIAN (%)</th>
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
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if (empty($dataSource)): ?>
                <tr>
                    <td colspan="10" class="text-center">Belum ada data sasaran / indikator pada filter ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php // Analisis Faktor Pencapaian Kinerja + Efisiensi Program dan Anggaran ?>
    <?= $this->include('lakip/addendum_cetak') ?>
</body>
</html>

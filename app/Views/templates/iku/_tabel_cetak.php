<?php

/**
 * Tabel IKU standalone untuk cetak PDF (mPDF) — dipakai bersama admin kabupaten & OPD.
 *
 * @var array $iku_data daftar sasaran, tiap item punya key `indikator`
 * @var array $years    tahun-tahun periode terpilih
 * @var bool  $show_opd tampilkan kolom OPD
 */
$iku_data = $iku_data ?? [];
$years    = !empty($years) ? $years : [];
$show_opd = $show_opd ?? false;

$jumlahTahun = max(1, count($years));

// Nomor + kolom OPD digabung per blok OPD, sama seperti tampilan layar.
$barisPerOpd = [];
if ($show_opd) {
    foreach ($iku_data as $sasaran) {
        $namaOpd = $sasaran['nama_opd'] ?? '-';
        $barisPerOpd[$namaOpd] = ($barisPerOpd[$namaOpd] ?? 0) + max(1, count($sasaran['indikator'] ?? []));
    }
}

$opdTercetak = [];
$no          = 1;

// No + (OPD) + Sasaran + Indikator + Definisi + Formula + Satuan + tahun + Sumber + PJ
$totalKolom = 8 + ($show_opd ? 1 : 0) + $jumlahTahun;
?>

<table class="pdf-table iku-print-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:3%;">No</th>
            <?php if ($show_opd): ?>
                <th rowspan="2" style="width:11%;">OPD</th>
            <?php endif; ?>
            <th rowspan="2" style="width:13%;">Sasaran</th>
            <th rowspan="2" style="width:13%;">Indikator Kinerja Utama</th>
            <th rowspan="2" style="width:14%;">Definisi Operasional</th>
            <th rowspan="2" style="width:14%;">Formula / Rumusan Perhitungan</th>
            <th rowspan="2" style="width:5%;">Satuan</th>
            <th colspan="<?= $jumlahTahun ?>">Target Capaian per Tahun</th>
            <th rowspan="2" style="width:10%;">Sumber Data</th>
            <th rowspan="2" style="width:9%;">Penanggung Jawab</th>
        </tr>
        <tr>
            <?php if (empty($years)): ?>
                <th class="year-cell">-</th>
            <?php else: ?>
                <?php foreach ($years as $tahun): ?>
                    <th class="year-cell"><?= esc($tahun) ?></th>
                <?php endforeach; ?>
            <?php endif; ?>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($iku_data)): ?>
            <tr>
                <td colspan="<?= $totalKolom ?>" class="c pdf-muted">
                    Tidak ada data IKU untuk filter yang dipilih.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($iku_data as $sasaran): ?>
                <?php
                $indikators   = $sasaran['indikator'] ?? [];
                $barisSasaran = max(1, count($indikators));
                $namaOpd      = $sasaran['nama_opd'] ?? '-';
                $daftarBaris  = !empty($indikators) ? $indikators : [null];
                $barisPertama = true;
                ?>

                <?php foreach ($daftarBaris as $indikator): ?>
                    <tr>
                        <?php if ($show_opd): ?>
                            <?php if (!isset($opdTercetak[$namaOpd])): ?>
                                <td rowspan="<?= $barisPerOpd[$namaOpd] ?? $barisSasaran ?>" class="c"><?= $no++ ?></td>
                                <td rowspan="<?= $barisPerOpd[$namaOpd] ?? $barisSasaran ?>" class="text-start"><?= esc($namaOpd) ?></td>
                                <?php $opdTercetak[$namaOpd] = true; ?>
                            <?php endif; ?>
                        <?php elseif ($barisPertama): ?>
                            <td rowspan="<?= $barisSasaran ?>" class="c"><?= $no++ ?></td>
                        <?php endif; ?>

                        <?php if ($barisPertama): ?>
                            <td rowspan="<?= $barisSasaran ?>" class="text-start"><?= esc($sasaran['sasaran'] ?? '-') ?></td>
                            <?php $barisPertama = false; ?>
                        <?php endif; ?>

                        <?php if ($indikator === null): ?>
                            <td colspan="<?= 6 + $jumlahTahun ?>" class="c pdf-muted">Belum ada indikator.</td>
                        <?php else: ?>
                            <td class="text-start"><?= esc($indikator['indikator'] ?? '-') ?></td>
                            <td class="text-start"><?= esc(($indikator['definisi'] ?? '') !== '' ? $indikator['definisi'] : '-') ?></td>
                            <td class="text-start"><?= esc(($indikator['rumusan_perhitungan'] ?? '') !== '' ? $indikator['rumusan_perhitungan'] : '-') ?></td>
                            <td class="c"><?= esc(($indikator['satuan_nama'] ?? '') !== '' ? $indikator['satuan_nama'] : '-') ?></td>

                            <?php if (empty($years)): ?>
                                <td class="year-cell">-</td>
                            <?php else: ?>
                                <?php foreach ($years as $tahun): ?>
                                    <?php $nilai = $indikator['target'][(int) $tahun] ?? null; ?>
                                    <td class="year-cell"><?= esc(($nilai === null || $nilai === '') ? '-' : $nilai) ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <td class="text-start"><?= esc(($indikator['sumber_data'] ?? '') !== '' ? $indikator['sumber_data'] : '-') ?></td>
                            <td class="text-start"><?= esc(($indikator['penanggung_jawab'] ?? '') !== '' ? $indikator['penanggung_jawab'] : '-') ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

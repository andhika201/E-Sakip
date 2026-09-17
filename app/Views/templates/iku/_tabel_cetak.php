<?php

/**
 * Tabel IKU standalone untuk cetak PDF (mPDF) — dipakai bersama admin kabupaten & OPD.
 *
 * @var array $iku_data daftar sasaran, tiap item punya key `indikator`
 * @var array $years    tahun-tahun periode terpilih
 * @var bool  $show_opd tampilkan kolom OPD
 */
// pdf_td_gabung()/pdf_teks(): kolom induk (No/OPD/Sasaran) TANPA rowspan
// supaya mPDF bebas memotong halaman di baris mana pun (lihat app/Helpers/pdf_helper.php).
helper('pdf');

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

$opdKe = []; // posisi baris di dalam grup OPD (mulai 0)
$noOpd = [];
$no    = 1;

// No + (OPD) + Sasaran + Indikator + Definisi + Formula + Satuan + tahun + Sumber + PJ
$totalKolom = 8 + ($show_opd ? 1 : 0) + $jumlahTahun;

// Lebar kolom (%). Totalnya HARUS tepat 100%: mPDF menjaga proporsi, jadi
// total >100% menyusutkan seluruh tabel, sedangkan kolom tahun yang tidak
// diberi lebar (dulu) kebagian sisa yang terlalu sempit sampai angkanya
// patah per digit. Kolom tahun membagi rata sisa lebar.
$lebar = $show_opd
    ? ['no' => 3, 'opd' => 10, 'sasaran' => 12, 'iku' => 12, 'definisi' => 13, 'formula' => 13, 'satuan' => 5, 'sumber' => 9, 'pj' => 8]
    : ['no' => 3, 'sasaran' => 13, 'iku' => 13, 'definisi' => 14, 'formula' => 14, 'satuan' => 5, 'sumber' => 10, 'pj' => 9];
$lebarTahun = round((100 - array_sum($lebar)) / $jumlahTahun, 2);
?>

<table class="pdf-table iku-print-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:<?= $lebar['no'] ?>%;">No</th>
            <?php if ($show_opd): ?>
                <th rowspan="2" style="width:<?= $lebar['opd'] ?>%;">OPD</th>
            <?php endif; ?>
            <th rowspan="2" style="width:<?= $lebar['sasaran'] ?>%;">Sasaran</th>
            <th rowspan="2" style="width:<?= $lebar['iku'] ?>%;">Indikator Kinerja Utama</th>
            <th rowspan="2" style="width:<?= $lebar['definisi'] ?>%;">Definisi Operasional</th>
            <th rowspan="2" style="width:<?= $lebar['formula'] ?>%;">Formula / Rumusan Perhitungan</th>
            <th rowspan="2" style="width:<?= $lebar['satuan'] ?>%;">Satuan</th>
            <th colspan="<?= $jumlahTahun ?>">Target Capaian per Tahun</th>
            <th rowspan="2" style="width:<?= $lebar['sumber'] ?>%;">Sumber Data</th>
            <th rowspan="2" style="width:<?= $lebar['pj'] ?>%;">Penanggung Jawab</th>
        </tr>
        <tr>
            <?php if (empty($years)): ?>
                <th class="year-cell" style="width:<?= $lebarTahun ?>%;">-</th>
            <?php else: ?>
                <?php foreach ($years as $tahun): ?>
                    <th class="year-cell" style="width:<?= $lebarTahun ?>%;"><?= esc($tahun) ?></th>
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
                if ($show_opd) {
                    $noOpd[$namaOpd] ??= $no++;
                } else {
                    $noSasaran = $no++;
                }
                ?>

                <?php foreach (array_values($daftarBaris) as $sasKe => $indikator): ?>
                    <tr>
                        <?php if ($show_opd): ?>
                            <?php $opdKe[$namaOpd] = isset($opdKe[$namaOpd]) ? $opdKe[$namaOpd] + 1 : 0; ?>
                            <?= pdf_td_gabung($opdKe[$namaOpd], $barisPerOpd[$namaOpd] ?? $barisSasaran, (string) $noOpd[$namaOpd], 'c', '', 0) ?>
                            <?= pdf_td_gabung($opdKe[$namaOpd], $barisPerOpd[$namaOpd] ?? $barisSasaran, pdf_teks($namaOpd), 'text-start') ?>
                        <?php else: ?>
                            <?= pdf_td_gabung($sasKe, $barisSasaran, (string) $noSasaran, 'c', '', 0) ?>
                        <?php endif; ?>

                        <?= pdf_td_gabung($sasKe, $barisSasaran, pdf_teks($sasaran['sasaran'] ?? '-'), 'text-start') ?>

                        <?php if ($indikator === null): ?>
                            <td colspan="<?= 6 + $jumlahTahun ?>" class="c pdf-muted">Belum ada indikator.</td>
                        <?php else: ?>
                            <td class="text-start"><?= pdf_teks($indikator['indikator'] ?? '-') ?></td>
                            <td class="text-start"><?= pdf_teks(($indikator['definisi'] ?? '') !== '' ? $indikator['definisi'] : '-') ?></td>
                            <td class="text-start"><?= pdf_teks(($indikator['rumusan_perhitungan'] ?? '') !== '' ? $indikator['rumusan_perhitungan'] : '-') ?></td>
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

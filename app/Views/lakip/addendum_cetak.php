<?php
/**
 * Dua tabel tambahan LAKIP untuk CETAK PDF (mPDF).
 *
 * Dipakai bersama oleh adminKabupaten/lakip/lakip_cetak.php dan
 * adminOpd/lakip/lakip_cetak.php, disisipkan setelah tabel utama.
 *
 * Variabelnya sama dengan addendum_layar.php, minus tombol aksi.
 */

$indikatorRows = $indikatorRows ?? [];
$analisisMap   = $analisisMap ?? [];
$efisiensiRows = $efisiensiRows ?? [];
$scope         = $addendumScope ?? ['mode' => 'opd', 'tahun' => ''];
$tahunAddon    = (string) ($scope['tahun'] ?? '');

$rupiahCetak = static function ($nilai) {
    if ($nilai === null || $nilai === '') {
        return '-';
    }
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }

    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};

$daftarIndikator = [];
foreach ($indikatorRows as $r) {
    $tid = (int) ($r['target_id'] ?? 0);
    if ($tid <= 0 || isset($daftarIndikator[$tid])) {
        continue;
    }
    $daftarIndikator[$tid] = (string) ($r['indikator_sasaran'] ?? '-');
}

$adaAnalisis  = !empty($daftarIndikator);
$adaEfisiensi = !empty($efisiensiRows);
?>

<?php if ($adaAnalisis): ?>
    <?php // Mulai halaman baru supaya tabel tidak terpotong di tengah. ?>
    <div style="page-break-before: always;"></div>
    <h3 class="addendum-judul">ANALISIS FAKTOR PENCAPAIAN KINERJA</h3>
    <div class="addendum-sub">Tahun <?= esc($tahunAddon !== '' ? $tahunAddon : '-') ?></div>

    <table class="pdf-table lakip-print-table">
        <thead>
            <tr>
                <th style="width:4%;">NO</th>
                <th style="width:22%;">INDIKATOR</th>
                <th style="width:24%;">FAKTOR PENDUKUNG KEBERHASILAN/KEGAGALAN, PENURUNAN/PENINGKATAN KINERJA</th>
                <th style="width:25%;">FAKTOR PENGHAMBAT</th>
                <th style="width:25%;">UPAYA UNTUK MENINGKATKAN PENCAPAIAN KINERJA</th>
            </tr>
        </thead>
        <tbody>
            <?php $noA = 1; ?>
            <?php foreach ($daftarIndikator as $tid => $namaIndikator): ?>
                <?php
                $daftar = $analisisMap[$tid] ?? [];
                $jumlah = max(1, count($daftar));
                ?>
                <?php if (empty($daftar)): ?>
                    <tr>
                        <td class="text-center"><?= $noA++ ?></td>
                        <td class="text-start"><?= esc($namaIndikator) ?></td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar as $i => $a): ?>
                        <tr>
                            <?php if ($i === 0): ?>
                                <td class="text-center" rowspan="<?= $jumlah ?>"><?= $noA++ ?></td>
                                <td class="text-start" rowspan="<?= $jumlah ?>"><?= esc($namaIndikator) ?></td>
                            <?php endif; ?>
                            <td class="text-start"><?= nl2br(esc($a['faktor_pendukung'] ?? '')) ?: '-' ?></td>
                            <td class="text-start"><?= nl2br(esc($a['faktor_penghambat'] ?? '')) ?: '-' ?></td>
                            <td class="text-start"><?= nl2br(esc($a['upaya_peningkatan'] ?? '')) ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($adaEfisiensi): ?>
    <div style="page-break-before: always;"></div>
    <h3 class="addendum-judul">EFISIENSI PROGRAM DAN ANGGARAN</h3>
    <div class="addendum-sub">Tahun <?= esc($tahunAddon !== '' ? $tahunAddon : '-') ?></div>

    <table class="pdf-table lakip-print-table">
        <thead>
            <tr>
                <th style="width:5%;">NO</th>
                <th style="width:45%;">NAMA PROGRAM</th>
                <th style="width:17%;">ANGGARAN</th>
                <th style="width:17%;">REALISASI</th>
                <th style="width:16%;">EFISIENSI</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $noE = 1;
            $totalAnggaran = 0.0;
            $totalRealisasi = 0.0;
            $totalEfisiensi = 0.0;
            ?>
            <?php foreach ($efisiensiRows as $e): ?>
                <?php
                $totalAnggaran  += (float) ($e['anggaran'] ?? 0);
                $totalRealisasi += (float) ($e['realisasi'] ?? 0);
                $totalEfisiensi += (float) ($e['efisiensi'] ?? 0);
                ?>
                <tr>
                    <td class="text-center"><?= $noE++ ?></td>
                    <td class="text-start">
                        <?= esc(!empty($e['kode_program']) ? '[' . $e['kode_program'] . '] ' : '') ?><?= esc($e['program_kegiatan'] ?? '-') ?>
                    </td>
                    <td class="text-start"><?= esc($rupiahCetak($e['anggaran'])) ?></td>
                    <td class="text-start"><?= esc($rupiahCetak($e['realisasi'])) ?></td>
                    <td class="text-start"><?= esc($rupiahCetak($e['efisiensi'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" class="text-start"><strong>TOTAL</strong></td>
                <td class="text-start"><strong><?= esc($rupiahCetak($totalAnggaran)) ?></strong></td>
                <td class="text-start"><strong><?= esc($rupiahCetak($totalRealisasi)) ?></strong></td>
                <td class="text-start"><strong><?= esc($rupiahCetak($totalEfisiensi)) ?></strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

<?php
/**
 * PDF rekap triwulan IKP (mPDF, A4 mendatar) — AdminOpd\IkpController::cetak.
 * Kop: templates/pdf_kop (membaca $judul, $subjudul, $namaUnit dari data view).
 *
 * MENGAPA sel kategori berupa baris pemisah, bukan rowspan: mPDF mengecilkan
 * seluruh tabel bila ada rowspan besar yang terpotong halaman (arsitektur §13).
 *
 * @var array  $baris    IkpRekapService::rekapOpd()
 * @var array  $ringkas  IkpRekapService::ringkasOpd()
 * @var int    $tahun
 * @var array  $periode
 * @var string $kategori
 */
helper('dashboard_status');
$kategoriList  = \App\Models\Ikp\IkpModel::KATEGORI;
$metodeSingkat = ['sum' => 'Akumulasi', 'trend_naik' => 'Posisi, naik', 'trend_turun' => 'Posisi, turun', 'trend_flat' => 'Dipertahankan'];
$f   = static fn ($v) => ikp_fmt($v === null ? null : (float) $v, 2);
$pct = static function (?float $p, string $warna, string $label): string {
    $w = dash_color($warna);
    $teks = $p !== null ? capaianFormatPersen($p) : ($label === 'Belum Ada Data' ? '-' : $label);

    return '<td class="c" style="background:' . esc($w['soft'], 'attr') . ';color:' . esc($w['hex'], 'attr') . ';font-weight:bold">' . esc($teks) . '</td>';
};
$grup = [];
foreach ($baris as $r) {
    $grup[$r['ikp']['kategori']][] = $r;
}
$no = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <?= $this->include('templates/pdf_style') ?>
    <style>
        table.pdf-table.ikp-cetak { font-size: 7.4px; }
        table.pdf-table.ikp-cetak th { font-size: 7px; padding: 3px 2px; }
        table.pdf-table.ikp-cetak td { padding: 3px 3px; vertical-align: middle; }
        table.pdf-table.ikp-cetak td.grup { background: #e8f2ec; color: #0b5a33; font-weight: bold; font-size: 8px; }
        .kecil { font-size: 6.6px; color: #6b7a70; }
        .ringkas { width: 100%; border-collapse: collapse; margin: 2px 0 8px; }
        .ringkas td { border: 0.5px solid #cfd8d2; padding: 4px 6px; font-size: 8px; text-align: center; }
        .ringkas .n { font-size: 11px; font-weight: bold; color: #15311f; }
        .catatan { font-size: 7px; color: #555; margin-top: 6px; line-height: 1.5; }
    </style>
</head>
<body>
<?= $this->include('templates/pdf_kop') ?>

<table class="ringkas">
    <tr>
        <td><div class="n"><?= (int) $ringkas['jumlah_ikp'] ?></div>IKP dipantau</td>
        <td><div class="n"><?= (int) $ringkas['terisi_realisasi'] ?></div>sudah berealisasi</td>
        <td><div class="n"><?= $ringkas['rata_capaian'] === null ? '-' : esc(capaianFormatPersen($ringkas['rata_capaian'])) ?></div>capaian rata-rata</td>
        <td><div class="n" style="color:<?= dash_color('hijau')['hex'] ?>"><?= (int) $ringkas['hijau'] ?></div>tercapai / melampaui</td>
        <td><div class="n" style="color:<?= dash_color('kuning')['hex'] ?>"><?= (int) $ringkas['kuning'] ?></div>mendekati / perlu perhatian</td>
        <td><div class="n" style="color:<?= dash_color('merah')['hex'] ?>"><?= (int) $ringkas['merah'] ?></div>kritis</td>
        <td><div class="n" style="color:<?= dash_color('abu')['hex'] ?>"><?= (int) $ringkas['abu'] ?></div>belum dapat dinilai</td>
    </tr>
</table>

<table class="pdf-table ikp-cetak">
    <thead>
        <tr>
            <th rowspan="2" style="width:3%">No</th>
            <th rowspan="2" style="width:23%">Indikator Kinerja Prioritas</th>
            <th rowspan="2" style="width:6%">Satuan</th>
            <th rowspan="2" style="width:6%">Target <?= (int) $tahun ?></th>
            <?php for ($q = 1; $q <= 4; $q++): ?><th colspan="3">Triwulan <?= capaianRomawi($q) ?></th><?php endfor; ?>
            <th rowspan="2" style="width:7%">Capaian s.d. Bulan</th>
        </tr>
        <tr>
            <?php for ($q = 1; $q <= 4; $q++): ?><th>Target</th><th>Real.</th><th>%</th><?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($baris === []): ?>
            <tr><td colspan="17" class="c pdf-muted">Belum ada IKP.</td></tr>
        <?php endif; ?>
        <?php foreach ($kategoriList as $k => $label): if (empty($grup[$k])) { continue; } ?>
            <tr><td colspan="17" class="grup"><?= esc(strtoupper($label)) ?> (<?= count($grup[$k]) ?> IKP)</td></tr>
            <?php foreach ($grup[$k] as $r): $ikp = $r['ikp']; $tb = $r['tahun_berjalan']; $no++; ?>
                <tr>
                    <td class="c"><?= $no ?></td>
                    <td>
                        <?= esc($ikp['output_prioritas']) ?>
                        <div class="kecil"><?= esc(trim(($ikp['pu_nama'] ?? '') . ' · ' . ($metodeSingkat[$ikp['metode'] ?? ''] ?? 'metode belum dipilih'), ' ·')) ?></div>
                    </td>
                    <td class="c"><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '-') ?></td>
                    <td class="r"><?= esc($f($r['target_tahunan'])) ?></td>
                    <?php for ($q = 1; $q <= 4; $q++): $t = $r['triwulan'][$q]; ?>
                        <td class="r"><?= esc($f($t['target'])) ?></td>
                        <td class="r"><?= esc($f($t['realisasi'])) ?></td>
                        <?= $pct($t['capaian'], $t['warna'], $t['status_label']) ?>
                    <?php endfor; ?>
                    <?php $w = dash_color($tb['warna']); ?>
                    <td class="c" style="background:<?= esc($w['soft'], 'attr') ?>;color:<?= esc($w['hex'], 'attr') ?>;font-weight:bold">
                        <?= $tb['persen'] !== null ? esc(capaianFormatPersen($tb['persen'])) : '-' ?>
                        <div class="kecil"><?= esc($tb['sampai_bulan'] ? 's.d. ' . ikp_nama_bulan((int) $tb['sampai_bulan']) : $tb['status_label']) ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="catatan">
    Catatan: IKP berada pada periode RPJMD <?= (int) $periode['awal'] ?>–<?= (int) $periode['akhir'] ?>. Metode akumulasi menjumlahkan nilai bulanan;
    metode posisi memakai nilai bulan terakhir yang terisi. Capaian hanya menghitung bulan yang realisasinya sudah diisi
    (triwulan berjalan belum penuh). Warna mengikuti ambang status capaian Pengaturan Dashboard AKSARA.
</div>
</body>
</html>

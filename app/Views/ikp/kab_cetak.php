<?php
/**
 * PDF rekap IKP lintas Perangkat Daerah (A4 mendatar) — AdminKab\IkpController::cetak.
 * Memakai templates/pdf_style + pdf_kop seperti cetakan rumah lainnya.
 *
 * @var string $judul
 * @var string $subjudul
 * @var int    $tahun
 * @var int    $bulan
 * @var array  $baris
 * @var array  $total
 */
$persenTeks = static fn (?float $v) => $v === null ? '–' : ikp_fmt($v, 2) . '%';
$bl = ikp_nama_bulan($bulan, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <?= $this->include('templates/pdf_style') ?>
  <style>
    table.pdf-table.rekap td { vertical-align: middle; }
    .ringkas { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
    .ringkas td { border: 0.5px solid #cfd8d2; padding: 5px 8px; font-size: 9px; background: #f7faf8; }
    .ringkas b { font-size: 11px; color: #15311f; }
    .st { font-weight: bold; }
  </style>
</head>
<body>
<?= $this->include('templates/pdf_kop') ?>

<table class="ringkas">
  <tr>
    <td>IKP terdaftar<br><b><?= (int) $total['ikp'] ?></b> di <?= (int) $total['opd_ber_ikp'] ?> dari <?= (int) $total['opd'] ?> perangkat daerah</td>
    <td>Breakdown target lengkap<br><b><?= (int) $total['lengkap'] ?></b> IKP</td>
    <td>Rata-rata capaian s.d. <?= esc($bl) ?><br><b style="color: <?= esc($total['status_sd']['color_hex'], 'attr') ?>;"><?= esc($persenTeks($total['rata_sd'])) ?></b> (<?= esc($total['status_sd']['name']) ?>)</td>
    <td>Melapor realisasi <?= esc($bl) ?><br><b><?= (int) $total['lapor_opd'] ?></b> perangkat daerah · <?= (int) $total['lapor_ikp'] ?> IKP</td>
    <td>Sebaran status IKP<br>hijau <?= (int) $total['hijau'] ?> · kuning <?= (int) $total['kuning'] ?> · merah <?= (int) $total['merah'] ?> · belum dinilai <?= (int) $total['abu'] ?></td>
  </tr>
</table>

<table class="pdf-table rekap">
  <thead>
    <tr>
      <th style="width: 4%;">No</th>
      <th style="width: 30%;">Perangkat Daerah</th>
      <th style="width: 6%;">Jumlah IKP</th>
      <th style="width: 9%;">Breakdown lengkap</th>
      <th style="width: 9%;">Realisasi <?= esc($bl) ?></th>
      <th style="width: 9%;">Terisi s.d. <?= esc($bl) ?></th>
      <th style="width: 10%;">Rata-rata capaian</th>
      <th style="width: 11%;">Status</th>
      <th style="width: 12%;">Hijau / Kuning / Merah / Belum</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($baris as $n => $b): ?>
      <?php $a = $b['agregat']; $st = $a['status_sd']; ?>
      <tr>
        <td class="c"><?= $n + 1 ?></td>
        <td><?= esc($b['opd']['nama_opd']) ?><?= $a['tanpa_metode'] > 0 ? '<br><span class="pdf-muted">' . (int) $a['tanpa_metode'] . ' IKP belum memilih metode</span>' : '' ?></td>
        <td class="c"><?= $a['jumlah'] > 0 ? (int) $a['jumlah'] : '<span class="pdf-muted">belum ada</span>' ?></td>
        <td class="c"><?= $a['jumlah'] > 0 ? (int) $a['lengkap'] . ' / ' . (int) $a['jumlah'] : '–' ?></td>
        <td class="c"><?= $a['jumlah'] > 0 ? (int) $a['lapor_bulan'] . ' / ' . (int) $a['jumlah'] : '–' ?></td>
        <td class="c"><?= $a['sel_wajib'] > 0 ? round($a['sel_terisi'] / $a['sel_wajib'] * 100) . '%' : '–' ?></td>
        <td class="c st" style="color: <?= esc($st['color_hex'], 'attr') ?>;"><?= esc($persenTeks($a['rata_sd'])) ?></td>
        <td class="c"><?= esc($st['name']) ?></td>
        <td class="c"><?= $a['jumlah'] > 0 ? (int) $a['warna']['hijau'] . ' / ' . (int) $a['warna']['kuning'] . ' / ' . (int) $a['warna']['merah'] . ' / ' . (int) $a['warna']['abu'] : '–' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<p class="pdf-muted" style="font-size: 8px; margin-top: 6px;">
  Capaian dihitung hanya dari bulan yang realisasinya sudah diisi (Akumulasi: jumlah realisasi ÷ jumlah target; Posisi: bulan terisi terakhir),
  dengan rumus yang sama seperti halaman Kinerja Prioritas perangkat daerah. Warna status mengikuti ambang dasbor AKSARA.
</p>
</body>
</html>

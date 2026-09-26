<?php
/**
 * LAMPIRAN V PK Eselon II — Target BULANAN tahun PK + rekap TRIWULAN (MENDATAR).
 *
 * Triwulan tidak diisi manual: dihitung dari target bulanan dengan rumus yang
 * sama dengan halaman OPD (ikp_nilai_triwulan): Akumulasi -> jumlah bulan,
 * Posisi -> nilai bulan terakhir triwulan. IKP tanpa metode -> triwulan "-"
 * (tanpa metode tidak ada cara jujur merekapnya).
 */
helper('pdf');
$ikpRows = $lampIkp ?? [];
$metodeSingkat = [
    'sum'         => 'Akumulasi',
    'trend_naik'  => 'Posisi (naik)',
    'trend_turun' => 'Posisi (turun)',
    'trend_flat'  => 'Dipertahankan',
];
$bulanPendek = [1 => 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
$romawi      = [1 => 'I', 'II', 'III', 'IV'];
?>
<?= $this->include('ikp/lampiran_pk_gaya') ?>
<div class="lp-wrap">
  <p class="lp-label">Lampiran V :</p>
  <p class="lp-sub">Target Bulanan dan Triwulan Indikator Kinerja Prioritas (IKP) Tahun <?= (int) $lampTahun ?></p>
  <p class="lp-meta"><?= esc($lampNamaDok) ?> &middot; <?= esc($lampOpd['nama_opd']) ?></p>

  <table class="lp-t lp-kecil">
    <thead>
      <tr>
        <th rowspan="2" style="width: 3%;">NO.</th>
        <th rowspan="2" style="width: 18%;">OUTPUT PRIORITAS (INDIKATOR IKP)</th>
        <th rowspan="2" style="width: 7%;">SATUAN</th>
        <th rowspan="2" style="width: 6.5%;">METODE</th>
        <th rowspan="2" style="width: 5.5%;" class="lp-sorot">TARGET <?= (int) $lampTahun ?></th>
        <th colspan="12">TARGET BULANAN</th>
        <th colspan="4">TRIWULAN</th>
      </tr>
      <tr>
        <?php foreach ($bulanPendek as $b): ?><th style="width: 3.5%;"><?= $b ?></th><?php endforeach; ?>
        <?php foreach ($romawi as $r): ?><th style="width: 4.5%;"><?= $r ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($ikpRows === []): ?>
        <tr><td colspan="21" class="lp-kosong">Belum ada Indikator Kinerja Prioritas (IKP) aktif untuk perangkat daerah ini.</td></tr>
      <?php endif; ?>
      <?php foreach ($ikpRows as $i => $r): ?>
        <?php
        $id       = (int) $r['id'];
        $bulan    = $lampBulan[$id]['bulan'] ?? [];
        $tw       = $lampBulan[$id]['triwulan'] ?? [];
        $cellThn  = $lampTahunan[$id][(int) $lampTahun] ?? [];
        $thn      = ($cellThn['target'] ?? null) !== null
            ? ikp_fmt((float) $cellThn['target'])
            : (trim((string) ($cellThn['target_teks'] ?? '')) !== '' ? (string) $cellThn['target_teks'] : '-');
        ?>
        <tr>
          <td class="lp-c"><?= $i + 1 ?>.</td>
          <td><?= pdf_teks(ikp_rapikan_teks($r['output_prioritas'])) ?></td>
          <td class="lp-c"><?= pdf_teks($r['satuan_label'] !== '' ? $r['satuan_label'] : '-') ?></td>
          <td class="lp-c lp-mini"><?= esc($metodeSingkat[$r['metode'] ?? ''] ?? 'Belum dipilih') ?></td>
          <td class="lp-c lp-sorot"><?= pdf_teks($thn) ?></td>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <td class="lp-c"><?= esc(($bulan[$m] ?? null) !== null ? ikp_fmt((float) $bulan[$m]) : '-') ?></td>
          <?php endfor; ?>
          <?php for ($q = 1; $q <= 4; $q++): ?>
            <td class="lp-c lp-tw"><?= esc(($tw[$q] ?? null) !== null ? ikp_fmt((float) $tw[$q]) : '-') ?></td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="lp-legenda">
    Triwulan dihitung otomatis dari target bulanan: <b>Akumulasi</b> = jumlah tiga bulan; <b>Posisi</b>/<b>Dipertahankan</b> =
    nilai bulan terakhir triwulan. Tanda "-" = belum diisi, atau metode perhitungan belum dipilih perangkat daerah.
  </div>

  <?= $this->include('ikp/lampiran_pk_ttd') ?>
</div>

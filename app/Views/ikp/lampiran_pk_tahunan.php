<?php
/**
 * LAMPIRAN IV PK Eselon II — Target 5 tahun -> target TAHUNAN per IKP (MENDATAR).
 * Kolom tahun = periode RPJMD aktif; kolom tahun PK disorot.
 */
helper('pdf');
$ikpRows  = $lampIkp ?? [];
$periode  = $lampPeriode ?? [];
$metodeSingkat = [
    'sum'         => 'Akumulasi',
    'trend_naik'  => 'Posisi (naik)',
    'trend_turun' => 'Posisi (turun)',
    'trend_flat'  => 'Dipertahankan',
];
$nilaiTahun = static function (array $cell): string {
    if (($cell['target'] ?? null) !== null) {
        return ikp_fmt((float) $cell['target']);
    }
    $teks = trim((string) ($cell['target_teks'] ?? ''));

    return $teks !== '' ? $teks : '-';
};
$lebarTahun = $periode === [] ? 0 : (int) floor(45 / count($periode));
?>
<?= $this->include('ikp/lampiran_pk_gaya') ?>
<div class="lp-wrap">
  <p class="lp-label">Lampiran IV :</p>
  <p class="lp-sub">Target Tahunan Indikator Kinerja Prioritas (IKP)
    <?php if ($periode !== []): ?>Periode <?= (int) reset($periode) ?>–<?= (int) end($periode) ?><?php endif; ?></p>
  <p class="lp-meta"><?= esc($lampNamaDok) ?> &middot; <?= esc($lampOpd['nama_opd']) ?></p>

  <table class="lp-t">
    <thead>
      <tr>
        <th style="width: 4%;">NO.</th>
        <th style="width: 29%;">OUTPUT PRIORITAS (INDIKATOR IKP)</th>
        <th style="width: 8%;">SATUAN</th>
        <th style="width: 7%;">METODE</th>
        <th style="width: 7%;">TARGET 5 TAHUN</th>
        <?php foreach ($periode as $th): ?>
          <th style="width: <?= $lebarTahun ?>%;" class="<?= (int) $th === (int) $lampTahun ? 'lp-sorot' : '' ?>"><?= (int) $th ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($ikpRows === []): ?>
        <tr><td colspan="<?= 5 + count($periode) ?>" class="lp-kosong">Belum ada Indikator Kinerja Prioritas (IKP) aktif untuk perangkat daerah ini.</td></tr>
      <?php endif; ?>
      <?php foreach ($ikpRows as $i => $r): ?>
        <?php
        $id   = (int) $r['id'];
        $t5   = trim((string) ($r['target_5_tahun_teks'] ?? ''));
        $t5   = $t5 !== '' ? $t5 : ($r['target_5_tahun'] !== null ? ikp_fmt((float) $r['target_5_tahun']) : '-');
        ?>
        <tr>
          <td class="lp-c"><?= $i + 1 ?>.</td>
          <td><?= pdf_teks(ikp_rapikan_teks($r['output_prioritas'])) ?></td>
          <td class="lp-c"><?= pdf_teks($r['satuan_label'] !== '' ? $r['satuan_label'] : '-') ?></td>
          <td class="lp-c lp-mini"><?= esc($metodeSingkat[$r['metode'] ?? ''] ?? 'Belum dipilih') ?></td>
          <td class="lp-c"><?= pdf_teks($t5) ?></td>
          <?php foreach ($periode as $th): ?>
            <td class="lp-c<?= (int) $th === (int) $lampTahun ? ' lp-sorot' : '' ?>"><?= pdf_teks($nilaiTahun($lampTahunan[$id][$th] ?? [])) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="lp-legenda">
    Metode: <b>Akumulasi</b> = target tahunan dijumlahkan menuju target 5 tahun; <b>Posisi</b> = nilai capaian pada akhir tahun
    (tahun terakhir = target 5 tahun); <b>Dipertahankan</b> = nilai yang sama setiap tahun. Tanda "-" = target tahun itu belum diisi.
  </div>

  <?= $this->include('ikp/lampiran_pk_ttd') ?>
</div>

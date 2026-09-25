<?php
/**
 * LAMPIRAN III PK Eselon II — Rencana Inovasi Perangkat Daerah (halaman TEGAK).
 * Sumber: ikp_inovasi (opd, tahun PK), diisi di adminopd/ikp/inovasi.
 */
helper('pdf');
$inovasiRows = $lampInovasi ?? [];
?>
<?= $this->include('ikp/lampiran_pk_gaya') ?>
<div class="lp-wrap">
  <p class="lp-label">Lampiran III :</p>
  <p class="lp-sub">Rencana Inovasi Perangkat Daerah</p>
  <p class="lp-meta"><?= esc($lampNamaDok) ?> &middot; <?= esc($lampOpd['nama_opd']) ?></p>

  <table class="lp-t">
    <thead>
      <tr>
        <th style="width: 7%;">NO.</th>
        <th style="width: 33%;">RENCANA INOVASI</th>
        <th style="width: 60%;">DESKRIPSI</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($inovasiRows === []): ?>
        <tr><td colspan="3" class="lp-kosong">Belum ada rencana inovasi untuk tahun <?= (int) $lampTahun ?>.</td></tr>
      <?php endif; ?>
      <?php foreach ($inovasiRows as $i => $r): ?>
        <tr>
          <td class="lp-c"><?= $i + 1 ?>.</td>
          <td><b><?= pdf_teks($r['nama']) ?></b></td>
          <td>
            <?= nl2br(pdf_teks(trim((string) ($r['deskripsi'] ?? ''))), false) ?>
            <?php if (! empty($r['ikp_nama'])): ?>
              <div style="font-size: 8pt; font-style: italic; margin-top: 3px;">Mendukung IKP: <?= pdf_teks(ikp_rapikan_teks($r['ikp_nama'])) ?></div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?= $this->include('ikp/lampiran_pk_ttd') ?>
</div>

<?php
/**
 * LAMPIRAN II PK Eselon II — Indikator Kinerja Prioritas (IKP), halaman MENDATAR.
 *
 * Tata letak mengikuti draf Bapperida / DocxExportService prototipe Prioritas:
 * kolom Visi/Misi | 9 PROGRAM UNGGULAN | 10 SASARAN PEMBANGUNAN | OUTCOME |
 * INDIKATOR | PROGRAM OPD | OUTPUT PRIORITAS | SATUAN | TARGET 5 TAHUN |
 * BIDANG URUSAN; satu baris VISI, baris judul per MISI, dan kolom
 * pengelompokan 2–6 "digabung menurun" selama isinya sama.
 *
 * MENGAPA gabungan visual (pdf_td_gabung), bukan rowspan: rowspan panjang
 * membuat mPDF memindah/menyusutkan seluruh tabel (lihat pdf_helper.php).
 *
 * Data dari AdminOpd\IkpInovasiController::susunLampiran() (kunci lamp*).
 */
helper('pdf');
$ikpRows = $lampIkp ?? [];
$misiMap = $lampMisi ?? [];

// Kelompokkan per misi (urutan sudah diurutkan controller).
$grup = [];
foreach ($ikpRows as $r) {
    $mid = (int) ($r['rpjmd_misi_id'] ?? 0);
    $key = isset($misiMap[$mid]) ? $mid : 0;
    $grup[$key][] = $r;
}

/** Label kolom Program Unggulan: nama PU, atau kategori untuk IKP non-PU. */
$labelPu = static function (array $r) use ($lampKategori): string {
    if (trim((string) ($r['pu_nama'] ?? '')) !== '') {
        return (string) $r['pu_nama'];
    }
    $kat = (string) ($r['kategori'] ?? '');

    return $kat === 'program_unggulan' ? '' : ($lampKategori[$kat] ?? '');
};
$target5 = static function (array $r): string {
    $teks = trim((string) ($r['target_5_tahun_teks'] ?? ''));
    if ($teks !== '') {
        return $teks;
    }

    return $r['target_5_tahun'] !== null ? ikp_fmt((float) $r['target_5_tahun']) : '-';
};

/**
 * Hitung "lari" (run) nilai sama per kolom pengelompokan di dalam satu grup:
 * untuk tiap baris -> [ke, jumlah] seperti yang diminta pdf_td_gabung().
 *
 * @return array<int, array<string, array{0:int,1:int}>>
 */
$hitungLari = static function (array $baris, array $kolom): array {
    $posisi = [];
    foreach ($kolom as $k => $ambil) {
        $mulai = 0;
        $n     = count($baris);
        for ($i = 0; $i <= $n; $i++) {
            $nilai = $i < $n ? $ambil($baris[$i]) : null;
            $awal  = $i < $n ? $ambil($baris[$mulai]) : null;
            if ($i === $n || $nilai !== $awal) {
                $jumlah = $i - $mulai;
                for ($j = $mulai; $j < $i; $j++) {
                    $posisi[$j][$k] = [$j - $mulai, $jumlah];
                }
                $mulai = $i;
            }
        }
    }

    return $posisi;
};
$kolomGrup = [
    'pu'  => static fn ($r) => $labelPu($r),
    'sp'  => static fn ($r) => trim((string) ($r['sasaran_pembangunan_nama'] ?? '')),
    'out' => static fn ($r) => ikp_rapikan_teks($r['outcome'] ?? ''),
    'ind' => static fn ($r) => ikp_rapikan_teks($r['indikator_outcome'] ?? ''),
    'pro' => static fn ($r) => ikp_rapikan_teks($r['program_opd'] ?? ''),
];
?>
<?= $this->include('ikp/lampiran_pk_gaya') ?>
<div class="lp-wrap">
  <p class="lp-label">Lampiran II :</p>
  <p class="lp-sub">Indikator Kinerja Prioritas (IKP)</p>
  <p class="lp-sub">Pendukung Program Prioritas Kabupaten Pringsewu</p>
  <p class="lp-alias">(di dokumen PK disebut Indikator Kinerja Kunci (IKK) Pendukung Program Prioritas Kabupaten Pringsewu)</p>
  <p class="lp-meta"><?= esc($lampNamaDok) ?> &middot; <?= esc($lampOpd['nama_opd']) ?></p>

  <?php if (empty($lampPk)): ?>
    <div class="lp-catatan">
      <b>Catatan:</b> Perjanjian Kinerja Eselon II tahun <?= (int) $lampTahun ?> untuk perangkat daerah ini belum
      tersedia di AKSARA. Lampiran II–V dicetak sebagai <b>DRAF</b> — tanpa halaman perjanjian, Lampiran I, dan tanda tangan.
    </div>
  <?php endif; ?>

  <table class="lp-t">
    <thead>
      <tr>
        <th style="width: 4%;">VISI / MISI</th>
        <th style="width: 9%;">9 PROGRAM UNGGULAN</th>
        <th style="width: 10%;">10 SASARAN PEMBANGUNAN</th>
        <th style="width: 10%;">OUTCOME</th>
        <th style="width: 10%;">INDIKATOR</th>
        <th style="width: 11%;">PROGRAM OPD</th>
        <th style="width: 20%;">OUTPUT PRIORITAS</th>
        <th style="width: 7%;">SATUAN</th>
        <th style="width: 8%;">TARGET 5 TAHUN</th>
        <th style="width: 11%;">BIDANG URUSAN</th>
      </tr>
      <tr>
        <?php for ($c = 1; $c <= 10; $c++): ?><td class="lp-nomor"><?= $c ?></td><?php endfor; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (trim((string) ($lampVisi ?? '')) !== ''): ?>
        <tr><td colspan="10" class="lp-visi">VISI : <?= esc(mb_strtoupper($lampVisi, 'UTF-8')) ?></td></tr>
      <?php endif; ?>

      <?php if ($ikpRows === []): ?>
        <tr><td colspan="10" class="lp-kosong">Belum ada Indikator Kinerja Prioritas (IKP) aktif untuk perangkat daerah ini.</td></tr>
      <?php endif; ?>

      <?php foreach ($grup as $mid => $baris): ?>
        <tr>
          <td colspan="10" class="lp-banner">
            <?php if ($mid > 0): ?>
              MISI <?= (int) $misiMap[$mid]['nomor'] ?> : <?= esc($misiMap[$mid]['misi']) ?>
            <?php else: ?>
              <i>IKP yang belum dipetakan ke Misi RPJMD</i>
            <?php endif; ?>
          </td>
        </tr>
        <?php $lari = $hitungLari($baris, $kolomGrup); ?>
        <?php foreach ($baris as $i => $r): ?>
          <tr>
            <?= pdf_td_gabung($i, count($baris), '', '', '', 0) ?>
            <?php foreach ($kolomGrup as $k => $ambil): ?>
              <?php [$ke, $jml] = $lari[$i][$k]; ?>
              <?= pdf_td_gabung($ke, $jml, pdf_teks($ambil($r)), '', '', 12) ?>
            <?php endforeach; ?>
            <td><?= pdf_teks(ikp_rapikan_teks($r['output_prioritas'])) ?></td>
            <td class="lp-c"><?= pdf_teks($r['satuan_label'] !== '' ? $r['satuan_label'] : '-') ?></td>
            <td class="lp-c"><?= pdf_teks($target5($r)) ?></td>
            <td><?= pdf_teks(ikp_rapikan_teks($r['bidang_urusan'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?= $this->include('ikp/lampiran_pk_ttd') ?>
</div>

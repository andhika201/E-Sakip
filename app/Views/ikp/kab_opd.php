<?php
/**
 * Rincian IKP satu Perangkat Daerah — area Kabupaten (HANYA BACA).
 * Per IKP: target & realisasi 12 bulan, capaian per bulan, rekap triwulan
 * (IkpRekapService::rekapOpd) dan capaian s.d. bulan terpilih.
 *
 * @var int   $tahun
 * @var int   $bulan
 * @var int[] $tahunList
 * @var array $data     satu elemen AdminKab\IkpController::rekapLintas()
 * @var string $baseUrl
 */
$opd       = $data['opd'];
$a         = $data['agregat'];
$namaBulan = ikp_nama_bulan($bulan);
$qs        = static fn (array $x = []) => '?' . http_build_query(array_merge(['tahun' => $tahun, 'bulan' => $bulan], $x));
$persenTeks = static fn (?float $v) => $v === null ? '–' : ikp_fmt($v, 2) . '%';
$kategori  = \App\Models\Ikp\IkpModel::KATEGORI;
$metodeNm  = \App\Models\Ikp\IkpModel::METODE;
$romawi    = [1 => 'I', 'II', 'III', 'IV'];
$this->setVar('title', $title ?? 'Rincian IKP');
$this->setVar('shellCss', <<<'CSS'
.ikp-kartu { border:1px solid #e6ece8; border-radius:14px; background:#fff; margin-bottom:14px; box-shadow:0 6px 16px rgba(16,40,24,.04); }
.ikp-kartu .kepala { display:flex; gap:12px; align-items:flex-start; padding:14px 16px 10px; flex-wrap:wrap; }
.ikp-kartu .nomor { flex:0 0 auto; width:32px; height:32px; border-radius:10px; background:#e8f2ec; color:#00743e; font-weight:800; display:grid; place-items:center; font-size:.82rem; }
.ikp-kartu .judul { font-weight:700; color:#16321f; line-height:1.35; }
.ikp-kartu .meta { display:flex; flex-wrap:wrap; gap:4px 14px; font-size:.77rem; color:#6b7a70; margin-top:4px; }
.ikp-kartu .badge-pu { display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:700; color:#fff; border-radius:7px; padding:.25em .55em; }
.ikp-kartu .badge-kat { font-size:.7rem; font-weight:700; border-radius:7px; padding:.25em .55em; background:#eef2ef; color:#40524a; }
.ikp-kartu .skor { margin-left:auto; text-align:right; min-width:120px; }
.ikp-kartu .skor .pct { font-size:1.35rem; font-weight:800; line-height:1; }
.ikp-kartu .skor .lbl { font-size:.72rem; font-weight:700; }
.grid-bulan { font-size:.78rem; margin:0; }
.grid-bulan th, .grid-bulan td { text-align:center; padding:.3rem .35rem; white-space:nowrap; }
.grid-bulan thead th { background:#f3f7f4; color:#40524a; font-size:.7rem; text-transform:uppercase; border-bottom:1px solid #e0e7e2; }
.grid-bulan tbody th { text-align:left; background:#fbfcfb; color:#40524a; font-weight:700; font-size:.72rem; }
.grid-bulan td.lewat { background:#fafafa; color:#b5bdb8; }
.grid-bulan td.pilih, .grid-bulan th.pilih { box-shadow: inset 0 -2px 0 #00743e; }
.tw-baris { display:flex; flex-wrap:wrap; gap:8px; padding:10px 16px 14px; border-top:1px dashed #e6ece8; }
.tw-chip { border-radius:10px; padding:6px 10px; font-size:.76rem; line-height:1.3; border:1px solid transparent; min-width:150px; }
.tw-chip b { font-size:.8rem; }
.catatan-kecil { font-size:.78rem; color:#6b7a70; }
.grid-bulan tbody th, .grid-bulan thead th:first-child { position:sticky; left:0; z-index:1; }
.grid-bulan thead th:first-child { background:#f3f7f4; }
@media (max-width: 767.98px) {
  .ikp-kartu .skor { margin-left:0; text-align:left; }
  .tw-chip { flex:1 1 calc(50% - 8px); min-width:0; }
}
@media (max-width: 575.98px) { .panel { padding:14px 12px; } .dash-hero { padding:18px 16px; } }
CSS);
?>
<?= $this->include('templates/shell_atas') ?>
<?= $this->include('templates/dashboard_kit') ?>

<div class="mb-3">
  <a href="<?= base_url($baseUrl) . $qs() ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-arrow-left me-1"></i> Kembali ke rekap</a>
</div>

<div class="dash-hero mb-3">
  <div class="dh-ic"><i class="fas fa-building-columns"></i></div>
  <div class="flex-fill">
    <h2><?= esc($opd['nama_opd']) ?></h2>
    <p>Rincian Kinerja Prioritas (IKP) &middot; Tahun <?= (int) $tahun ?> &middot; capaian s.d. <strong><?= esc($namaBulan) ?></strong>
      &middot; <span class="dash-mode"><i class="fas fa-eye"></i> Hanya baca</span></p>
  </div>
</div>

<form method="get" class="dash-filter mb-3">
  <div class="row g-3 align-items-end">
    <div class="col-6 col-md-2">
      <label for="f-tahun" class="form-label mb-1">Tahun</label>
      <select name="tahun" id="f-tahun" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php foreach ($tahunList as $t): ?>
          <option value="<?= (int) $t ?>" <?= (int) $t === $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label for="f-bulan" class="form-label mb-1">Capaian s.d. bulan</label>
      <select name="bulan" id="f-bulan" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc(ikp_nama_bulan($m)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>
</form>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="kpi"><div class="kpi-title">IKP aktif</div><div class="kpi-num"><?= (int) $a['jumlah'] ?></div>
      <div class="kpi-sub"><?= (int) $a['lengkap'] ?> breakdown lengkap</div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi"><div class="kpi-title">Rata-rata capaian</div>
      <div class="kpi-num" style="color:<?= esc($a['status_sd']['color_hex'], 'attr') ?>"><?= esc($persenTeks($a['rata_sd'])) ?></div>
      <div class="kpi-sub"><span class="dot" style="background:<?= esc($a['status_sd']['color_hex'], 'attr') ?>"></span><?= esc($a['status_sd']['name']) ?></div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi"><div class="kpi-title">Realisasi <?= esc($namaBulan) ?></div>
      <div class="kpi-num"><?= (int) $a['lapor_bulan'] ?><span style="font-size:1rem;color:#6b7a70;"> / <?= (int) $a['jumlah'] ?></span></div>
      <div class="kpi-sub">IKP sudah berisi realisasi</div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi"><div class="kpi-title">Sebaran status</div>
      <div class="kpi-sub">
        <span class="dot" style="background:#0a8f50"></span><?= (int) $a['warna']['hijau'] ?> &nbsp;
        <span class="dot" style="background:#d9a520"></span><?= (int) $a['warna']['kuning'] ?> &nbsp;
        <span class="dot" style="background:#d64545"></span><?= (int) $a['warna']['merah'] ?> &nbsp;
        <span class="dot" style="background:#8a968f"></span><?= (int) $a['warna']['abu'] ?>
      </div>
      <?php if ($a['tanpa_metode'] > 0): ?>
        <div class="kpi-foot" style="color:#a86a26;"><i class="fas fa-triangle-exclamation me-1"></i><?= (int) $a['tanpa_metode'] ?> IKP belum memilih metode</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($data['ikp'] === []): ?>
  <div class="panel"><div class="empty"><div class="ic"><i class="fas fa-bullseye"></i></div>
    <p class="mb-1 fw-semibold">Belum ada IKP aktif</p>
    <p class="mb-0 small">Perangkat daerah ini belum mencatat Indikator Kinerja Prioritas untuk periode RPJMD berjalan.</p></div></div>
<?php endif; ?>

<?php foreach ($data['ikp'] as $n => $x): ?>
  <?php
  $r   = $x['rekap'];
  $ikp = $r['ikp'];
  $st  = $x['sd']['status'];
  $t5  = trim((string) ($ikp['target_5_tahun_teks'] ?? '')) !== '' ? $ikp['target_5_tahun_teks'] : ($ikp['target_5_tahun'] !== null ? ikp_fmt($ikp['target_5_tahun']) : '–');
  $tth = $r['target_tahunan'] !== null ? ikp_fmt($r['target_tahunan']) : (trim((string) ($r['target_tahunan_teks'] ?? '')) ?: '–');
  ?>
  <div class="ikp-kartu">
    <div class="kepala">
      <div class="nomor"><?= $n + 1 ?></div>
      <div class="flex-fill" style="min-width: 220px;">
        <div class="d-flex flex-wrap gap-1 mb-1">
          <?php if (! empty($ikp['pu_nama'])): ?>
            <span class="badge-pu" style="background:<?= esc($ikp['pu_warna'] ?: '#00743e', 'attr') ?>"><i class="fas <?= esc($ikp['pu_ikon'] ?: 'fa-star', 'attr') ?>"></i><?= esc($ikp['pu_nama']) ?></span>
          <?php endif; ?>
          <span class="badge-kat"><?= esc($kategori[$ikp['kategori']] ?? $ikp['kategori']) ?></span>
        </div>
        <div class="judul"><?= esc(ikp_rapikan_teks($ikp['output_prioritas'])) ?></div>
        <div class="meta">
          <span><i class="fas fa-ruler me-1"></i><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '–') ?></span>
          <span><i class="fas fa-calculator me-1"></i><?= esc($metodeNm[$ikp['metode'] ?? ''] ?? 'Metode belum dipilih') ?></span>
          <span><i class="fas fa-flag-checkered me-1"></i>Target 5 th: <?= esc($t5) ?></span>
          <span><i class="fas fa-bullseye me-1"></i>Target <?= (int) $tahun ?>: <?= esc($tth) ?></span>
          <?php if (! empty($ikp['pj_nama'])): ?><span><i class="fas fa-user-tie me-1"></i><?= esc($ikp['pj_nama']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="skor">
        <div class="pct" style="color:<?= esc($st['color_hex'], 'attr') ?>"><?= esc($persenTeks($x['sd']['persen'])) ?></div>
        <div class="lbl" style="color:<?= esc($st['color_hex'], 'attr') ?>"><?= esc($st['name']) ?></div>
        <div class="catatan-kecil">s.d. <?= esc($namaBulan) ?></div>
      </div>
    </div>
    <?php
    // IKP yang belum punya satu pun target bulanan maupun realisasi tahun ini
    // cukup diberi satu baris keterangan — kisi 12 bulan kosong hanya menambah panjang halaman.
    $adaIsi = false;
    for ($m = 1; $m <= 12; $m++) {
        if ($r['bulan'][$m]['target'] !== null || $r['bulan'][$m]['realisasi'] !== null) {
            $adaIsi = true;
            break;
        }
    }
    ?>
    <?php if (! $adaIsi): ?>
      <div class="tw-baris catatan-kecil">
        <i class="fas fa-hourglass-half me-1"></i>Target bulanan dan realisasi tahun <?= (int) $tahun ?> belum diisi perangkat daerah<?= ikp_metode_valid((string) ($ikp['metode'] ?? '')) ? '' : '; metode perhitungan juga belum dipilih' ?>.
      </div>
    <?php else: ?>
    <div class="px-3">
      <table class="table table-sm grid-bulan" data-no-paginate>
        <thead>
          <tr>
            <th style="text-align:left;"></th>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <th class="<?= $m === $bulan ? 'pilih' : '' ?>"><?= esc(ikp_nama_bulan($m, true)) ?></th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>Target</th>
            <?php for ($m = 1; $m <= 12; $m++): $v = $r['bulan'][$m]['target']; ?>
              <td class="<?= $m > $bulan ? 'lewat' : '' ?><?= $m === $bulan ? ' pilih' : '' ?>"><?= $v === null ? '–' : esc(ikp_fmt($v)) ?></td>
            <?php endfor; ?>
          </tr>
          <tr>
            <th>Realisasi</th>
            <?php for ($m = 1; $m <= 12; $m++): $v = $r['bulan'][$m]['realisasi']; ?>
              <td class="fw-semibold<?= $m > $bulan ? ' lewat' : '' ?><?= $m === $bulan ? ' pilih' : '' ?>"><?= $v === null ? '–' : esc(ikp_fmt($v)) ?></td>
            <?php endfor; ?>
          </tr>
          <tr>
            <th>Capaian</th>
            <?php for ($m = 1; $m <= 12; $m++): $p = $x['per_bulan'][$m]; ?>
              <?php if ($m > $bulan): ?>
                <td class="lewat">–</td>
              <?php elseif ($p === null): ?>
                <td class="text-muted<?= $m === $bulan ? ' pilih' : '' ?>">–</td>
              <?php else: $sp = ikp_status(['status' => 'calculated', 'percentage' => $p, 'error' => null]); ?>
                <td class="<?= $m === $bulan ? 'pilih' : '' ?>" style="background:<?= esc($sp['color_soft'], 'attr') ?>; color:<?= esc($sp['color_hex'], 'attr') ?>; font-weight:700;"><?= esc(ikp_fmt($p, 1)) ?>%</td>
              <?php endif; ?>
            <?php endfor; ?>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="tw-baris">
      <?php foreach ($r['triwulan'] as $q => $tw): ?>
        <?php $c = \App\Models\DashboardThresholdModel::COLORS[$tw['warna']] ?? \App\Models\DashboardThresholdModel::COLORS['abu']; ?>
        <div class="tw-chip" style="background:<?= esc($c['soft'], 'attr') ?>; border-color:<?= esc($c['soft'], 'attr') ?>;" title="<?= esc((string) $tw['keterangan'], 'attr') ?>">
          <div><b>TW <?= $romawi[$q] ?></b><?= ! empty($tw['berjalan']) ? ' <span class="text-muted">(berjalan' . (! empty($tw['sampai_bulan']) ? ', capaian s.d. ' . esc(ikp_nama_bulan((int) $tw['sampai_bulan'], true)) : '') . ')</span>' : '' ?></div>
          <div>Target <?= $tw['target'] === null ? '–' : esc(ikp_fmt($tw['target'])) ?> &middot; Realisasi <?= $tw['realisasi'] === null ? '–' : esc(ikp_fmt($tw['realisasi'])) ?></div>
          <div style="color:<?= esc($c['hex'], 'attr') ?>; font-weight:700;"><?= $tw['capaian'] === null ? esc($tw['status_label']) : esc(ikp_fmt((float) $tw['capaian'], 2)) . '% · ' . esc($tw['status_label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<div class="catatan-kecil mt-2">
  <i class="fas fa-circle-info me-1"></i> Kolom abu-abu = bulan setelah <?= esc($namaBulan) ?>. Capaian per bulan: Akumulasi = realisasi ÷ target bulan itu;
  Posisi = posisi bulan itu ÷ target posisinya. Triwulan dihitung otomatis dari data bulanan.
</div>

<script>
  // Di layar sempit kisi 12 bulan digeser: tampilkan bulan terpilih lebih dulu.
  // MENGAPA 'load', bukan DOMContentLoaded: pembungkus .table-responsive baru
  // dipasang skrip footer pada DOMContentLoaded yang terdaftar SETELAH skrip ini.
  window.addEventListener('load', function () {
    document.querySelectorAll('.grid-bulan').forEach(function (t) {
      var w = t.closest('.table-responsive'), th = t.querySelector('thead th.pilih');
      if (w && th && w.scrollWidth > w.clientWidth) { w.scrollLeft = Math.max(0, th.offsetLeft - w.clientWidth + th.offsetWidth + 8); }
    });
  });
</script>

<?= $this->include('templates/shell_bawah') ?>

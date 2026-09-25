<?php
/**
 * Monitoring IKP satu Perangkat Daerah — Bupati, hanya baca.
 * Per IKP: grafik target vs realisasi per bulan (Chart.js) + tabel triwulan.
 * Data: Bupati\IkpMonitoringController::opd (rekapLintas satu OPD).
 *
 * @var int   $tahun
 * @var int   $bulan
 * @var int[] $tahunList
 * @var array $data
 */
$opd        = $data['opd'];
$a          = $data['agregat'];
$namaBulan  = ikp_nama_bulan($bulan);
$qs         = static fn (array $x = []) => '?' . http_build_query(array_merge(['tahun' => $tahun, 'bulan' => $bulan], $x));
$persenTeks = static fn (?float $v) => $v === null ? '–' : ikp_fmt($v, 2) . '%';
$js         = static fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$metodeNm   = \App\Models\Ikp\IkpModel::METODE;
$romawi     = [1 => 'I', 'II', 'III', 'IV'];
$warnaAmbang = \App\Models\DashboardThresholdModel::COLORS;

// Data grafik: satu entri per IKP (angka mentah; format di sisi JS).
$grafik = [];
foreach ($data['ikp'] as $n => $x) {
    $t = [];
    $v = [];
    for ($m = 1; $m <= 12; $m++) {
        $t[] = $x['rekap']['bulan'][$m]['target'];
        $v[] = $m <= $bulan ? $x['rekap']['bulan'][$m]['realisasi'] : null;
    }
    $grafik[] = ['id' => 'g' . $n, 'target' => $t, 'realisasi' => $v, 'satuan' => $x['rekap']['ikp']['satuan_label']];
}
$this->setVar('title', $title ?? 'Monitoring IKP');
$this->setVar('shellCss', <<<'CSS'
.ikp-kartu { border:1px solid #e6ece8; border-radius:16px; background:#fff; padding:14px 16px; height:100%; box-shadow:0 6px 16px rgba(16,40,24,.04); display:flex; flex-direction:column; }
.ikp-kartu .judul { font-weight:700; color:#16321f; line-height:1.35; font-size:.92rem; }
.ikp-kartu .meta { display:flex; flex-wrap:wrap; gap:3px 12px; font-size:.75rem; color:#6b7a70; margin-top:4px; }
.ikp-kartu .badge-pu { display:inline-flex; align-items:center; gap:5px; font-size:.68rem; font-weight:700; color:#fff; border-radius:7px; padding:.22em .5em; }
.ikp-kartu .skor { text-align:right; flex:0 0 auto; }
.ikp-kartu .skor .pct { font-size:1.25rem; font-weight:800; line-height:1; }
.ikp-kartu .skor .lbl { font-size:.7rem; font-weight:700; }
.grafik-ikp { position:relative; height:210px; margin:10px 0 8px; }
.tw-tabel { font-size:.76rem; margin:0; }
.tw-tabel th, .tw-tabel td { text-align:center; padding:.28rem .3rem; white-space:nowrap; }
.tw-tabel thead th { background:#f3f7f4; color:#40524a; font-size:.68rem; text-transform:uppercase; }
.tw-tabel tbody th { text-align:left; color:#40524a; font-weight:700; background:#fbfcfb; }
@media (max-width: 575.98px) {
  .tw-tabel { font-size:.66rem; min-width:0 !important; } /* style.php: tabel ponsel min 500px */
  .tw-tabel th, .tw-tabel td { padding:.3rem .1rem !important; }
  .ikp-kartu { padding:12px; }
  .ikp-kartu .skor .pct { font-size:1.05rem; }
}
@media (max-width: 575.98px) { .panel { padding:14px 12px; } .dash-hero { padding:18px 16px; } }
CSS);
?>
<?= $this->include('templates/shell_atas') ?>
<?= $this->include('templates/dashboard_kit') ?>

<div class="mb-3">
  <a href="<?= base_url('bupati/ikp') . $qs() ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-arrow-left me-1"></i> Kembali ke monitoring</a>
</div>

<div class="dash-hero mb-3">
  <div class="dh-ic"><i class="fas fa-building-columns"></i></div>
  <div class="flex-fill">
    <h2><?= esc($opd['nama_opd']) ?></h2>
    <p>Kinerja Prioritas (IKP) &middot; Tahun <?= (int) $tahun ?> &middot; bulan <strong><?= esc($namaBulan) ?></strong>
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
      <label for="f-bulan" class="form-label mb-1">Bulan</label>
      <select name="bulan" id="f-bulan" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc(ikp_nama_bulan($m)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>
</form>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="kpi"><div class="kpi-title">IKP aktif</div><div class="kpi-num"><?= (int) $a['jumlah'] ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><div class="kpi-title">Capaian <?= esc(ikp_nama_bulan($bulan, true)) ?></div>
    <div class="kpi-num" style="color:<?= esc($a['status_bln']['color_hex'], 'attr') ?>"><?= esc($persenTeks($a['rata_bln'])) ?></div>
    <div class="kpi-sub"><?= esc($a['status_bln']['name']) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><div class="kpi-title">Capaian s.d. <?= esc(ikp_nama_bulan($bulan, true)) ?></div>
    <div class="kpi-num" style="color:<?= esc($a['status_sd']['color_hex'], 'attr') ?>"><?= esc($persenTeks($a['rata_sd'])) ?></div>
    <div class="kpi-sub"><?= esc($a['status_sd']['name']) ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><div class="kpi-title">Realisasi <?= esc($namaBulan) ?></div>
    <div class="kpi-num"><?= (int) $a['lapor_bulan'] ?><span style="font-size:1rem;color:#6b7a70;"> / <?= (int) $a['jumlah'] ?></span></div>
    <div class="kpi-sub">IKP sudah dilaporkan</div></div></div>
</div>

<?php if ($data['ikp'] === []): ?>
  <div class="panel"><div class="empty"><div class="ic"><i class="fas fa-bullseye"></i></div>
    <p class="mb-1 fw-semibold">Belum ada IKP aktif</p>
    <p class="mb-0 small">Perangkat daerah ini belum mencatat Indikator Kinerja Prioritas.</p></div></div>
<?php endif; ?>

<div class="row g-3">
  <?php foreach ($data['ikp'] as $n => $x): ?>
    <?php
    $r   = $x['rekap'];
    $ikp = $r['ikp'];
    $st  = $x['bln']['persen'] !== null ? $x['bln']['status'] : $x['sd']['status'];
    $pct = $x['bln']['persen'] ?? $x['sd']['persen'];
    ?>
    <div class="col-12 col-lg-6">
      <div class="ikp-kartu">
        <div class="d-flex gap-2 align-items-start">
          <div class="flex-fill">
            <?php if (! empty($ikp['pu_nama'])): ?>
              <span class="badge-pu mb-1" style="background:<?= esc($ikp['pu_warna'] ?: '#00743e', 'attr') ?>"><i class="fas <?= esc($ikp['pu_ikon'] ?: 'fa-star', 'attr') ?>"></i><?= esc($ikp['pu_nama']) ?></span>
            <?php endif; ?>
            <div class="judul"><?= $n + 1 ?>. <?= esc(ikp_rapikan_teks($ikp['output_prioritas'])) ?></div>
            <div class="meta">
              <span><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '–') ?></span>
              <span><?= esc($metodeNm[$ikp['metode'] ?? ''] ?? 'Metode belum dipilih') ?></span>
              <span>Target <?= (int) $tahun ?>: <?= $r['target_tahunan'] !== null ? esc(ikp_fmt($r['target_tahunan'])) : '–' ?></span>
            </div>
          </div>
          <div class="skor">
            <div class="pct" style="color:<?= esc($st['color_hex'], 'attr') ?>"><?= esc($persenTeks($pct)) ?></div>
            <div class="lbl" style="color:<?= esc($st['color_hex'], 'attr') ?>"><?= esc($st['name']) ?></div>
            <div class="text-muted" style="font-size:.68rem;"><?= $x['bln']['persen'] !== null ? esc(ikp_nama_bulan($bulan)) : 's.d. ' . esc(ikp_nama_bulan($bulan, true)) ?></div>
          </div>
        </div>
        <?php
        $adaIsi = false;
        for ($m = 1; $m <= 12; $m++) {
            if ($r['bulan'][$m]['target'] !== null || $r['bulan'][$m]['realisasi'] !== null) {
                $adaIsi = true;
                break;
            }
        }
        ?>
        <?php if (! $adaIsi): ?>
          <div class="text-muted small mt-3"><i class="fas fa-hourglass-half me-1"></i>Target bulanan dan realisasi tahun <?= (int) $tahun ?> belum diisi perangkat daerah.</div>
        <?php else: ?>
        <div class="grafik-ikp"><canvas id="g<?= $n ?>" aria-label="Grafik target dan realisasi bulanan"></canvas></div>
        <table class="table table-sm tw-tabel mt-auto" data-no-paginate>
          <thead><tr><th></th><?php foreach ($romawi as $q => $rm): ?><th>TW <?= $rm ?></th><?php endforeach; ?></tr></thead>
          <tbody>
            <tr><th>Target</th><?php foreach ($r['triwulan'] as $tw): ?><td><?= $tw['target'] === null ? '–' : esc(ikp_fmt($tw['target'])) ?></td><?php endforeach; ?></tr>
            <tr><th><span class="d-none d-sm-inline">Realisasi</span><span class="d-sm-none">Real.</span></th><?php foreach ($r['triwulan'] as $tw): ?><td class="fw-semibold"><?= $tw['realisasi'] === null ? '–' : esc(ikp_fmt($tw['realisasi'])) ?></td><?php endforeach; ?></tr>
            <tr><th><span class="d-none d-sm-inline">Capaian</span><span class="d-sm-none">Cap.</span></th>
              <?php foreach ($r['triwulan'] as $tw): $c = $warnaAmbang[$tw['warna']] ?? $warnaAmbang['abu']; ?>
                <td style="background:<?= esc($c['soft'], 'attr') ?>; color:<?= esc($c['hex'], 'attr') ?>; font-weight:700;" title="<?= esc($tw['status_label'] . ' — ' . (string) $tw['keterangan'], 'attr') ?>">
                  <?= $tw['capaian'] === null ? '–' : esc(ikp_fmt((float) $tw['capaian'], 1)) . '%' ?><?= ! empty($tw['berjalan']) ? '*' : '' ?>
                </td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($data['ikp'] !== []): ?>
  <p class="small text-muted mt-3 mb-0"><i class="fas fa-circle-info me-1"></i>Garis putus-putus = target bulanan; batang = realisasi yang sudah dilaporkan.
    Tanda * = triwulan masih berjalan (dihitung dari bulan yang sudah terisi).</p>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    var data = <?= $js($grafik) ?>;
    var bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    var fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    data.forEach(function (d) {
      var el = document.getElementById(d.id);
      if (!el) return;
      new Chart(el, {
        data: {
          labels: bulan,
          datasets: [
            { type: 'bar', label: 'Realisasi', data: d.realisasi, backgroundColor: 'rgba(10,143,80,.75)', borderRadius: 4, maxBarThickness: 22, order: 2 },
            { type: 'line', label: 'Target', data: d.target, borderColor: '#3f6296', backgroundColor: '#3f6296', borderDash: [6, 4],
              borderWidth: 2, pointRadius: 2.5, tension: .2, spanGaps: true, order: 1 }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + (c.parsed.y === null ? '–' : fmt.format(c.parsed.y) + (d.satuan ? ' ' + d.satuan : '')); } } }
          },
          scales: {
            y: { beginAtZero: true, ticks: { font: { size: 10 }, callback: function (v) { return fmt.format(v); } } },
            x: { ticks: { font: { size: 10 } } }
          }
        }
      });
    });
  });
</script>

<?= $this->include('templates/shell_bawah') ?>

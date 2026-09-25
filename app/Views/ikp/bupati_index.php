<?php
/**
 * MONITORING BULANAN KINERJA PRIORITAS (IKP) — Bupati, hanya baca.
 *
 * Sengaja ringkas seperti Dashboard Eksekutif: 4 kartu, satu kisi panas
 * OPD × bulan, 5 IKP perlu perhatian, ringkas per Program Unggulan.
 * Warna = ambang status dasbor (dashboard_status_thresholds), bukan angka
 * yang di-hardcode. Data: Bupati\IkpMonitoringController::index.
 *
 * @var int    $tahun
 * @var int    $bulan
 * @var string $jenis
 * @var int[]  $tahunList
 * @var array  $baris
 * @var array  $kartu
 * @var array  $perhatian
 * @var array  $perPu
 */
$namaBulan  = ikp_nama_bulan($bulan);
$qs         = static fn (array $x = []) => '?' . http_build_query(array_merge(['tahun' => $tahun, 'bulan' => $bulan], $x));
$persenTeks = static fn (?float $v) => $v === null ? '–' : ikp_fmt($v, 1) . '%';
$statusDari = static fn (?float $p) => ikp_status($p === null
    ? ['status' => 'incomplete', 'percentage' => null, 'error' => null]
    : ['status' => 'calculated', 'percentage' => $p, 'error' => null]);
$sorot = static fn (string $ikon, string $warna, string $teks) =>
    '<div class="sorot"><i class="fas ' . $ikon . '" style="color:' . esc($warna, 'attr') . '"></i><span>' . $teks . '</span></div>';
$this->setVar('title', $title ?? 'Monitoring Kinerja Prioritas (IKP)');
$this->setVar('shellCss', <<<'CSS'
.sorot { display:flex; gap:7px; align-items:flex-start; font-size:.79rem; color:#5d6b62; line-height:1.4; }
.sorot + .sorot { margin-top:5px; }
.sorot i { width:13px; text-align:center; margin-top:2px; flex:0 0 auto; font-size:.74rem; }
.panas { font-size:.8rem; margin:0; }
.panas thead th { background:#00713c; color:#fff; font-size:.68rem; text-transform:uppercase; letter-spacing:.3px; text-align:center; white-space:nowrap; vertical-align:middle; }
.panas thead th.kiri { text-align:left; }
.panas td { text-align:center; vertical-align:middle; padding:.35rem .3rem; white-space:nowrap; border-color:#fff; }
.panas td.opd { text-align:left; white-space:normal; min-width:210px; }
.panas td.opd a { color:#16321f; font-weight:600; text-decoration:none; }
.panas td.opd a:hover { color:#00743e; text-decoration:underline; }
.panas td.sel { font-weight:700; border-radius:6px; min-width:54px; }
.panas td.kosong { color:#b5bdb8; background:#f6f8f7; }
.panas td.sd { border-left:3px solid #fff; }
.urut-btn .btn { font-size:.75rem; }
.ikp-perhatian .ins-title { font-size:.84rem; }
.pu-mini { display:flex; gap:10px; align-items:center; padding:9px 10px; border:1px solid #eef2ef; border-radius:12px; background:#fcfdfc; }
.pu-mini + .pu-mini { margin-top:7px; }
.pu-mini .ic { width:32px; height:32px; border-radius:10px; display:grid; place-items:center; color:#fff; flex:0 0 auto; font-size:14px; }
.pu-mini .nm { font-weight:700; font-size:.82rem; color:#1d2b23; line-height:1.2; }
.pu-mini .sub { font-size:.72rem; color:#6b7a70; }
.pu-mini .pct { margin-left:auto; font-weight:800; font-size:.8rem; border-radius:8px; padding:.28em .55em; white-space:nowrap; }
.legenda-panas { display:flex; flex-wrap:wrap; gap:6px 14px; font-size:.74rem; color:#5d6b62; }
.panas td.opd, .panas thead th.kiri { position:sticky; left:0; z-index:1; }
.panas td.opd { background:#fff; }
.panas thead th.kiri { z-index:2; }
.geser-hint { display:none; font-size:.74rem; color:#6b7a70; margin-bottom:6px; }
@media (max-width: 767.98px) {
  .panas td.opd { min-width:128px; max-width:150px; font-size:.74rem; }
  .geser-hint { display:block; }
}
.legenda-panas span i { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:5px; vertical-align:-2px; }
@media (max-width: 575.98px) { .panel { padding:14px 12px; } .dash-hero { padding:18px 16px; } }
CSS);
$ambang = dash_threshold_rows();
?>
<?= $this->include('templates/shell_atas') ?>
<?= $this->include('templates/dashboard_kit') ?>

<div class="dash-hero mb-3">
  <div class="dh-ic"><i class="fas fa-bullseye"></i></div>
  <div class="flex-fill">
    <h2>Monitoring Bulanan Kinerja Prioritas (IKP)</h2>
    <p>
      Seluruh perangkat daerah &middot; Tahun <?= (int) $tahun ?> &middot; bulan <strong><?= esc($namaBulan) ?></strong>
      &middot; <span class="dash-mode"><i class="fas fa-eye"></i> Hanya baca</span>
    </p>
  </div>
</div>

<form method="get" class="dash-filter mb-4">
  <div class="row g-3 align-items-end">
    <div class="col-6 col-md-2">
      <label for="f-tahun" class="form-label mb-1">Tahun</label>
      <select name="tahun" id="f-tahun" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php foreach ($tahunList as $t): ?>
          <option value="<?= (int) $t ?>" <?= (int) $t === $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <label for="f-bulan" class="form-label mb-1">Bulan</label>
      <select name="bulan" id="f-bulan" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc(ikp_nama_bulan($m)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-12 col-md-4 col-lg-3">
      <label for="f-jenis" class="form-label mb-1">Cakupan</label>
      <select name="jenis" id="f-jenis" class="form-select" data-no-select2 onchange="this.form.submit()">
        <option value="" <?= $jenis === '' ? 'selected' : '' ?>>Perangkat Daerah &amp; Kecamatan</option>
        <option value="opd" <?= $jenis === 'opd' ? 'selected' : '' ?>>Perangkat Daerah saja</option>
        <option value="kecamatan" <?= $jenis === 'kecamatan' ? 'selected' : '' ?>>Kecamatan saja</option>
      </select>
    </div>
  </div>
</form>

<!-- ======================= EMPAT KARTU ======================= -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#0a8f50,#00743e);"><i class="fas fa-building-circle-check"></i></div>
        <div class="kpi-title">OPD Melapor</div>
      </div>
      <div class="kpi-num"><?= (int) $kartu['opd_lapor'] ?><span style="font-size:1rem;color:#6b7a70;"> / <?= (int) $kartu['opd_ber_ikp'] ?></span></div>
      <div class="kpi-sub">
        <?= $sorot('fa-calendar-check', '#0a8f50', 'sudah mengisi realisasi ' . esc($namaBulan)) ?>
        <?php $belumOpd = (int) $kartu['opd_ber_ikp'] - (int) $kartu['opd_lapor']; ?>
        <?php if ($belumOpd > 0): ?><?= $sorot('fa-hourglass-half', '#e07b39', $belumOpd . ' perangkat daerah belum melapor') ?><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#3f6296,#2f4d7a);"><i class="fas fa-gauge-high"></i></div>
        <div class="kpi-title">Rata-rata Capaian <?= esc(ikp_nama_bulan($bulan, true)) ?></div>
      </div>
      <div class="kpi-num" style="color:<?= esc($kartu['status_bln']['color_hex'], 'attr') ?>"><?= esc($persenTeks($kartu['rata_bln'])) ?></div>
      <div class="kpi-sub">
        <?= $sorot('fa-flag', $kartu['status_bln']['color_hex'], esc($kartu['status_bln']['name'])) ?>
        <?= $sorot('fa-list-ol', '#3f6296', (int) $kartu['terukur'] . ' dari ' . (int) $kartu['ikp'] . ' IKP terukur bulan ini') ?>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#d64545,#b13333);"><i class="fas fa-circle-exclamation"></i></div>
        <div class="kpi-title">IKP Merah</div>
      </div>
      <div class="kpi-num" style="color:#d64545;"><?= (int) $kartu['merah'] ?></div>
      <div class="kpi-sub">
        <?= $sorot('fa-circle-exclamation', '#d64545', 'capaian ' . esc($namaBulan) . ' kritis') ?>
        <?= $sorot('fa-triangle-exclamation', '#d9a520', (int) $kartu['kuning'] . ' IKP perlu perhatian / mendekati target') ?>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#8a968f,#6b7a70);"><i class="fas fa-list-check"></i></div>
        <div class="kpi-title">IKP Terdaftar</div>
      </div>
      <div class="kpi-num"><?= (int) $kartu['ikp'] ?></div>
      <div class="kpi-sub">
        <?= $sorot('fa-building', '#6b7a70', 'di ' . (int) $kartu['opd_ber_ikp'] . ' perangkat daerah') ?>
        <?= $sorot('fa-hourglass-half', '#8a968f', (int) $kartu['belum_nilai'] . ' belum dapat dinilai bulan ini') ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <!-- ======================= KISI PANAS ======================= -->
  <div class="col-12 col-xxl-8">
    <div class="panel">
      <div class="panel-head">
        <div>
          <h3>Capaian Bulanan per Perangkat Daerah</h3>
          <p>Rata-rata capaian IKP tiap bulan (Januari s.d. <?= esc($namaBulan) ?>). Klik nama untuk rincian grafik.</p>
        </div>
        <div class="btn-group urut-btn" role="group" aria-label="Urutkan">
          <button type="button" class="btn btn-outline-success active" data-urut="nama">A–Z</button>
          <button type="button" class="btn btn-outline-success" data-urut="bln">Terendah <?= esc(ikp_nama_bulan($bulan, true)) ?></button>
        </div>
      </div>
      <div class="geser-hint"><i class="fas fa-arrows-left-right me-1"></i>Geser tabel ke kanan untuk melihat bulan-bulan berikutnya.</div>
      <table class="table panas" id="kisi-panas" data-no-paginate>
        <thead>
          <tr>
            <th class="kiri">Perangkat Daerah</th>
            <th>IKP</th>
            <?php for ($m = 1; $m <= $bulan; $m++): ?><th><?= esc(ikp_nama_bulan($m, true)) ?></th><?php endfor; ?>
            <th>s.d. <?= esc(ikp_nama_bulan($bulan, true)) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($baris as $b): ?>
            <?php $a = $b['agregat']; ?>
            <tr data-nama="<?= esc(mb_strtolower($b['opd']['nama_opd']), 'attr') ?>"
                data-bln="<?= $a['per_bulan'][$bulan] === null ? 9999 : $a['per_bulan'][$bulan] ?>">
              <td class="opd">
                <a href="<?= base_url('bupati/ikp/opd/' . (int) $b['opd']['id']) . $qs() ?>"><?= esc($b['opd']['nama_opd']) ?></a>
              </td>
              <td class="text-muted"><?= $a['jumlah'] > 0 ? (int) $a['jumlah'] : '–' ?></td>
              <?php for ($m = 1; $m <= $bulan; $m++): $p = $a['per_bulan'][$m]; ?>
                <?php if ($p === null): ?>
                  <td class="sel kosong" title="Belum ada capaian terukur">–</td>
                <?php else: $s = $statusDari($p); ?>
                  <td class="sel" style="background:<?= esc($s['color_soft'], 'attr') ?>; color:<?= esc($s['color_hex'], 'attr') ?>;" title="<?= esc($s['name'], 'attr') ?>"><?= esc(ikp_fmt($p, 0)) ?>%</td>
                <?php endif; ?>
              <?php endfor; ?>
              <?php $s = $a['status_sd']; ?>
              <td class="sel sd" style="background:<?= esc($s['color_soft'], 'attr') ?>; color:<?= esc($s['color_hex'], 'attr') ?>;" title="<?= esc($s['name'], 'attr') ?>"><?= esc($persenTeks($a['rata_sd'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="legenda-panas mt-2">
        <?php foreach ($ambang as $t): $c = dash_color($t['color'] ?? null); ?>
          <span><i style="background:<?= esc($c['hex'], 'attr') ?>"></i><?= esc($t['name']) ?>
            (<?= $t['min_value'] === null ? '&lt; ' . esc(ikp_fmt((float) $t['max_value'])) : esc(ikp_fmt((float) $t['min_value'])) . ($t['max_value'] === null ? '+' : '–' . esc(ikp_fmt((float) $t['max_value']))) ?>%)</span>
        <?php endforeach; ?>
        <span><i style="background:#dfe4e1"></i>Belum ada data</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-xxl-4">
    <!-- ======================= 5 IKP PERLU PERHATIAN ======================= -->
    <div class="panel mb-3 ikp-perhatian" style="height:auto;">
      <div class="panel-head">
        <div>
          <h3><i class="fas fa-triangle-exclamation text-danger me-1"></i> 5 IKP Perlu Perhatian</h3>
          <p>Capaian <?= esc($namaBulan) ?> terendah, lalu IKP yang belum melapor.</p>
        </div>
      </div>
      <?php if ($perhatian === []): ?>
        <div class="empty"><div class="ic"><i class="fas fa-circle-check"></i></div><p class="mb-0 small">Tidak ada IKP merah/kuning atau yang belum melapor bulan ini.</p></div>
      <?php endif; ?>
      <?php foreach ($perhatian as $x): ?>
        <?php
        $ikp  = $x['rekap']['ikp'];
        $bl   = $x['rekap']['bulan'][$bulan];
        $st   = $x['bln']['persen'] !== null ? $x['bln']['status'] : dash_status_nonnumeric('belum_ada_data');
        ?>
        <div class="ins">
          <div class="ins-bar" style="background:<?= esc($st['color_hex'], 'attr') ?>"></div>
          <div class="ins-body">
            <div class="ins-title"><?= esc(mb_strimwidth(ikp_rapikan_teks($ikp['output_prioritas']), 0, 110, '…')) ?></div>
            <div class="ins-why">
              <a class="text-decoration-none" href="<?= base_url('bupati/ikp/opd/' . (int) $x['opd']['id']) . $qs() ?>"><?= esc($x['opd']['nama_opd']) ?></a>
              &middot; target <?= $bl['target'] === null ? '–' : esc(ikp_fmt($bl['target'])) ?>,
              realisasi <?= $bl['realisasi'] === null ? '<b>belum diisi</b>' : esc(ikp_fmt($bl['realisasi'])) ?>
              <?= $ikp['satuan_label'] !== '' ? esc($ikp['satuan_label']) : '' ?>
            </div>
          </div>
          <div class="ins-act">
            <span class="badge-soft" style="background:<?= esc($st['color_soft'], 'attr') ?>; color:<?= esc($st['color_hex'], 'attr') ?>;">
              <?= $x['bln']['persen'] !== null ? esc($persenTeks($x['bln']['persen'])) : 'Belum lapor' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ======================= PER PROGRAM UNGGULAN ======================= -->
    <div class="panel" style="height:auto;">
      <div class="panel-head">
        <div>
          <h3><i class="fas fa-shapes text-success me-1"></i> Per Program Unggulan</h3>
          <p>Rata-rata capaian IKP bulan <?= esc($namaBulan) ?>.</p>
        </div>
      </div>
      <?php foreach ($perPu as $p): ?>
        <?php $warnaPu = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $p['pu']['warna']) ? $p['pu']['warna'] : '#00743e'; $st = $p['status']; ?>
        <div class="pu-mini">
          <div class="ic" style="background:<?= esc($warnaPu, 'attr') ?>"><i class="fas <?= esc($p['pu']['ikon'] ?: 'fa-star', 'attr') ?>"></i></div>
          <div>
            <div class="nm"><?= esc($p['pu']['nama']) ?></div>
            <div class="sub"><?= (int) $p['jumlah'] ?> IKP &middot; <?= (int) $p['opd'] ?> OPD<?= $p['merah'] > 0 ? ' &middot; <span style="color:#d64545;font-weight:700;">' . (int) $p['merah'] . ' merah</span>' : '' ?></div>
          </div>
          <span class="pct" style="background:<?= esc($st['color_soft'], 'attr') ?>; color:<?= esc($st['color_hex'], 'attr') ?>;"><?= esc($persenTeks($p['rata'])) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<p class="small text-muted mb-0">
  <i class="fas fa-circle-info me-1"></i>
  Capaian bulan = realisasi dibanding target bulan itu (Akumulasi) atau posisi dibanding target posisinya (Posisi), dengan rumus
  yang sama seperti halaman perangkat daerah. Bulan tanpa realisasi tidak dihitung — tampil sebagai "–", bukan 0%.
</p>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var t = document.getElementById('kisi-panas');
    if (!t) return;
    document.querySelectorAll('[data-urut]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var k = btn.dataset.urut, tb = t.tBodies[0];
        var rows = Array.prototype.slice.call(tb.rows);
        rows.sort(function (a, b) {
          return k === 'nama' ? a.dataset.nama.localeCompare(b.dataset.nama, 'id') : parseFloat(a.dataset.bln) - parseFloat(b.dataset.bln);
        });
        rows.forEach(function (r) { tb.appendChild(r); });
        document.querySelectorAll('[data-urut]').forEach(function (b) { b.classList.toggle('active', b === btn); });
      });
    });
  });
</script>

<?= $this->include('templates/shell_bawah') ?>

<?php
/**
 * Dashboard Pengendalian Kinerja Perangkat Daerah.
 *
 * Halaman ini RINGKASAN + PINTU MASUK: tidak ada form input PK / Rencana Aksi /
 * MONEV / LAKIP di sini, dan tidak ada tabel besar. Rincian dibuka lewat drawer
 * sisi kanan atau tautan ke modul sumbernya.
 *
 * Data disiapkan App\Services\OpdDashboardService (lihat AdminOpdController).
 */
helper(['capaian', 'dashboard_status', 'format']);

$ctx        = $dash['context'] ?? [];
$adaOpd     = ($scope['opd_id'] ?? null) !== null;
$adaData    = $dash !== null && ($dash['pk']['indikator'] ?? 0) > 0;
$romawi     = ['', 'I', 'II', 'III', 'IV'];
$pkSegment  = $ctx['pk_segment'] ?? 'jpt';
$qsTahun    = '?tahun=' . (int) $tahun;

/** Query string filter, dipakai agar tautan mempertahankan periode terpilih. */
$filterQs = http_build_query(array_filter([
    'opd_id'     => ($scope['can_pick'] ?? false) ? ($scope['opd_id'] ?? null) : null,
    'tahun'      => $tahun,
    'triwulan'   => $triwulan,
    'pejabat_id' => $pejabatId ?: null,
]));

/** Bungkus JSON aman untuk ditanam di <script>. */
$js = static fn ($v) => json_encode(
    $v,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Dashboard Kinerja') ?></title>

  <?= $this->include('adminOpd/templates/style.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <?= $this->include('templates/dashboard_kit') ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">

      <!-- ======================= HEADER ======================= -->
      <div class="dash-hero mb-3">
        <div class="dh-ic"><i class="fas fa-gauge-high"></i></div>
        <div class="flex-fill">
          <h2>Dashboard Pengendalian Kinerja</h2>
          <p>
            <?= esc($scope['opd_nama'] ?? 'Perangkat Daerah belum dipilih') ?>
            <?php if ($adaData): ?>
              &middot; <?= esc($ctx['jenis_label'] ?? '') ?> &middot; Tahun <?= (int) $tahun ?> s.d. Triwulan <?= esc($romawi[$triwulan] ?? '') ?>
            <?php endif; ?>
          </p>
        </div>
        <?php if (!empty($ctx['last_update'])): ?>
          <div class="text-white-50 small text-end">
            <div>Pembaruan data terakhir</div>
            <div class="fw-semibold text-white"><?= esc(formatTanggal($ctx['last_update'])) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <!-- ======================= FILTER GLOBAL ======================= -->
      <form method="get" class="dash-filter mb-4">
        <div class="row g-3 align-items-end">
          <?php if (!empty($scope['can_pick'])): ?>
            <div class="col-12 col-md-4 col-lg-3">
              <label for="f-opd" class="form-label mb-1">Perangkat Daerah</label>
              <select name="opd_id" id="f-opd" class="form-select">
                <option value="">— Pilih Perangkat Daerah —</option>
                <?php foreach ($scope['opd_list'] as $o): ?>
                  <option value="<?= (int) $o['id'] ?>" <?= (int) $o['id'] === (int) ($scope['opd_id'] ?? 0) ? 'selected' : '' ?>>
                    <?= esc($o['nama_opd']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="col-6 col-md-3 col-lg-2">
            <label for="f-tahun" class="form-label mb-1">Tahun</label>
            <select name="tahun" id="f-tahun" class="form-select">
              <?php foreach ($tahunList as $t): ?>
                <option value="<?= (int) $t ?>" <?= (int) $t === (int) $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-md-3 col-lg-2">
            <label for="f-tw" class="form-label mb-1">Triwulan</label>
            <select name="triwulan" id="f-tw" class="form-select">
              <?php foreach ([1, 2, 3, 4] as $q): ?>
                <option value="<?= $q ?>" <?= $q === (int) $triwulan ? 'selected' : '' ?>>s.d. Triwulan <?= $romawi[$q] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (!empty($pejabatList)): ?>
            <div class="col-12 col-md-6 col-lg-3">
              <label for="f-pejabat" class="form-label mb-1">Pejabat / Unit</label>
              <select name="pejabat_id" id="f-pejabat" class="form-select">
                <option value="">Seluruh unit</option>
                <?php foreach ($pejabatList as $p): ?>
                  <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $pejabatId ? 'selected' : '' ?>>
                    <?= esc($p['jabatan'] ?: $p['nama']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="col-12 col-lg-2 d-grid">
            <button type="submit" class="btn btn-success"><i class="fas fa-filter me-1"></i> Terapkan</button>
          </div>
        </div>
      </form>

      <?php if (!$adaOpd): ?>
        <!-- ======================= BELUM ADA LINGKUP OPD ======================= -->
        <div class="panel">
          <div class="empty">
            <div class="ic"><i class="fas fa-building"></i></div>
            <?php if (!empty($scope['can_pick'])): ?>
              <p class="mb-1 fw-semibold">Pilih Perangkat Daerah terlebih dahulu</p>
              <p class="mb-0 small">Sebagai Super Admin, Anda dapat membuka dashboard kinerja setiap Perangkat Daerah lewat pilihan di atas.</p>
            <?php else: ?>
              <p class="mb-1 fw-semibold">Akun Anda belum tertaut ke Perangkat Daerah</p>
              <p class="mb-0 small">Hubungi Super Admin untuk menetapkan Perangkat Daerah pada akun ini.</p>
            <?php endif; ?>
          </div>
        </div>

      <?php elseif (!$adaData): ?>
        <!-- ======================= BELUM ADA PK ======================= -->
        <div class="panel">
          <div class="empty">
            <div class="ic"><i class="fas fa-file-signature"></i></div>
            <p class="mb-1 fw-semibold">Belum ada Perjanjian Kinerja <?= esc($ctx['jenis_label'] ?? '') ?> untuk tahun <?= (int) $tahun ?>.</p>
            <p class="mb-3 small">Dashboard mengambil indikator kinerja dari Perjanjian Kinerja pimpinan Perangkat Daerah.</p>
            <a href="<?= base_url('adminopd/pk/' . $pkSegment . $qsTahun) ?>" class="btn btn-success btn-sm">
              <i class="fas fa-arrow-right me-1"></i> Kelola Perjanjian Kinerja
            </a>
          </div>
        </div>

      <?php else: ?>
        <?php
          $pk       = $dash['pk'];
          $capaian  = $dash['capaian'];
          $anggaran = $dash['anggaran'];
          $wasp     = $dash['perhatian'];
          $misi     = $dash['misi'];
        ?>

        <!-- ======================= EMPAT KARTU RINGKASAN ======================= -->
        <div class="row g-3 mb-4">

          <!-- 1. PK & Kontribusi -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="pk">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#0a8f50,#00743e);"><i class="fas fa-file-signature"></i></div>
                <div class="kpi-title">PK &amp; Kontribusi</div>
              </div>
              <div>
                <div class="kpi-num"><?= (int) $pk['indikator'] ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Indikator PK</span></div>
                <div class="kpi-sub mt-2">
                  <div><span class="dot" style="background:#0a8f50"></span><?= (int) $pk['mendukung_misi'] ?> mendukung Misi Bupati</div>
                  <div><span class="dot" style="background:#e07b39"></span><?= (int) $pk['tanpa_renaksi'] ?> belum memiliki Rencana Aksi</div>
                </div>
              </div>
              <div class="kpi-foot">Lihat PK <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>

          <!-- 2. Capaian Kinerja OPD -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="capaian">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#3f6296,#2f4d7a);"><i class="fas fa-bullseye"></i></div>
                <div class="kpi-title">Capaian Kinerja OPD</div>
              </div>
              <div>
                <?php if ($capaian['can_compute']): ?>
                  <div class="kpi-num" style="color:<?= esc($capaian['status']['color_hex']) ?>">
                    <?= esc(capaianFormatPersen($capaian['total'])) ?>
                  </div>
                  <div class="kpi-sub mt-2">
                    <div><?= (int) $capaian['valid'] ?> dari <?= (int) $capaian['wajib'] ?> indikator valid</div>
                    <div class="fw-semibold" style="color:<?= esc($capaian['status']['color_hex']) ?>">
                      <?= esc($capaian['label']) ?><?= $capaian['belum_verifikasi'] > 0 ? ' — ' . (int) $capaian['belum_verifikasi'] . ' indikator belum diverifikasi' : '' ?>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="kpi-num sm text-muted">Belum dapat dihitung</div>
                  <div class="kpi-sub mt-2">
                    <div><?= (int) $capaian['valid'] ?> dari <?= (int) $capaian['wajib'] ?> indikator valid</div>
                    <div class="fw-semibold text-warning-emphasis"><?= (int) $capaian['belum_valid'] ?> indikator perlu dilengkapi</div>
                  </div>
                <?php endif; ?>
              </div>
              <div class="kpi-foot">Lihat rincian indikator <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>

          <!-- 3. Penyerapan Anggaran -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="anggaran">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#c98a3c,#a86a26);"><i class="fas fa-sack-dollar"></i></div>
                <div class="kpi-title">Penyerapan Anggaran</div>
              </div>
              <div>
                <div class="kpi-num<?= $anggaran['persen'] === null ? ' sm text-muted' : '' ?>">
                  <?= $anggaran['persen'] !== null ? esc(capaianFormatPersen($anggaran['persen'])) : 'Belum ada realisasi' ?>
                </div>
                <div class="kpi-sub mt-2">
                  <div><?= esc(formatRupiah($anggaran['realisasi'] ?? 0)) ?> dari <?= esc(formatRupiah($anggaran['anggaran'])) ?></div>
                  <div><?= (int) $anggaran['program_count'] ?> program pendukung PK</div>
                </div>
              </div>
              <div class="kpi-foot">Rincian program <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>

          <!-- 4. Perlu Perhatian -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="perhatian">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#d64545,#b13333);"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="kpi-title">Perlu Perhatian</div>
              </div>
              <div>
                <div class="kpi-num"><?= (int) $wasp['total'] ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Tindak Lanjut</span></div>
                <div class="kpi-sub mt-2">
                  <?php if ($wasp['kritis'] > 0): ?><div><span class="dot" style="background:#d64545"></span><?= (int) $wasp['kritis'] ?> indikator kritis</div><?php endif; ?>
                  <?php if ($wasp['monev_belum'] > 0): ?><div><span class="dot" style="background:#e07b39"></span><?= (int) $wasp['monev_belum'] ?> MONEV belum lengkap</div><?php endif; ?>
                  <?php if ($wasp['renaksi_belum'] > 0): ?><div><span class="dot" style="background:#d9a520"></span><?= (int) $wasp['renaksi_belum'] ?> Rencana Aksi belum ada</div><?php endif; ?>
                  <?php if ($wasp['anggaran_belum'] > 0): ?><div><span class="dot" style="background:#3f6296"></span><?= (int) $wasp['anggaran_belum'] ?> realisasi anggaran belum diperbarui</div><?php endif; ?>
                  <?php if ($wasp['total'] === 0): ?><div class="text-success fw-semibold">Tidak ada kondisi yang perlu ditindaklanjuti.</div><?php endif; ?>
                </div>
              </div>
              <div class="kpi-foot">Buka daftar prioritas <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>
        </div>

        <!-- ======================= DUA GRAFIK UTAMA ======================= -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-5">
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h3>Distribusi Status Indikator</h3>
                  <p><?= esc($dash['status_distribution']['caption']) ?></p>
                </div>
              </div>
              <div class="chart-box"><canvas id="chartStatus"></canvas></div>
              <div id="legendStatus" class="d-flex flex-wrap gap-2 justify-content-center mt-3"></div>
              <?php if (!($ctx['verifikasi']['available'] ?? false)): ?>
                <p class="text-muted mt-3 mb-0" style="font-size:.74rem;">
                  <i class="fas fa-circle-info me-1"></i><?= esc($ctx['verifikasi']['note']) ?>
                </p>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h3>Target vs Capaian Triwulanan</h3>
                  <p>Satu indikator ditampilkan pada satu waktu — satuan &amp; metode perhitungannya berbeda-beda.</p>
                </div>
                <div style="min-width: 240px; flex: 1 1 240px;">
                  <select id="serialPicker" class="form-select form-select-sm" data-no-select2 aria-label="Pilih indikator"></select>
                </div>
              </div>
              <div class="chart-box"><canvas id="chartTriwulan"></canvas></div>
              <p id="serialInfo" class="text-muted mt-3 mb-0" style="font-size:.76rem;"></p>
            </div>
          </div>
        </div>

        <!-- ======================= PRIORITAS & MISI ======================= -->
        <div class="row g-3">
          <div class="col-12 col-xl-6">
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h3>Prioritas Tindak Lanjut</h3>
                  <p>Disusun otomatis dari aturan kelengkapan &amp; capaian data.</p>
                </div>
                <?php if (count($dash['insights']) > 5): ?>
                  <button type="button" class="btn btn-sm btn-outline-success" data-drawer="perhatian">
                    Lihat semua (<?= count($dash['insights']) ?>)
                  </button>
                <?php endif; ?>
              </div>

              <?php if ($dash['insights'] === []): ?>
                <div class="empty">
                  <div class="ic"><i class="fas fa-circle-check"></i></div>
                  <p class="mb-0 small">Seluruh indikator lengkap dan tidak ada kondisi yang perlu ditindaklanjuti.</p>
                </div>
              <?php else: ?>
                <?php foreach (array_slice($dash['insights'], 0, 5) as $ins): ?>
                  <div class="ins">
                    <div class="ins-bar" style="background: <?= esc($ins['color']['hex']) ?>"></div>
                    <div class="ins-body">
                      <div class="ins-title"><?= esc($ins['judul']) ?></div>
                      <div class="ins-why"><?= esc($ins['alasan']) ?></div>
                      <span class="badge-soft mt-2" style="background: <?= esc($ins['color']['soft']) ?>; color: <?= esc($ins['color']['hex']) ?>;">
                        <?= esc($ins['status']) ?>
                      </span>
                    </div>
                    <div class="ins-act">
                      <a href="<?= esc($ins['url']) ?>" class="btn btn-sm btn-outline-success"><?= esc($ins['tombol']) ?></a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-12 col-xl-6">
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h3>Kontribusi terhadap Misi Bupati</h3>
                  <p>Ditarik dari keterkaitan Perjanjian Kinerja &amp; Misi RPJMD.</p>
                </div>
              </div>

              <?php if ($misi['items'] === [] && $misi['tanpa_misi'] === []): ?>
                <div class="empty">
                  <div class="ic"><i class="fas fa-diagram-project"></i></div>
                  <p class="mb-0 small">Belum ada indikator yang dapat dipetakan ke Misi Bupati.</p>
                </div>
              <?php else: ?>
                <?php foreach ($misi['items'] as $m): ?>
                  <div class="misi-item">
                    <button type="button" class="misi-btn" data-drawer="misi" data-misi="<?= (int) $m['misi_id'] ?>">
                      <span class="misi-no"><?= (int) $m['nomor'] ?></span>
                      <span class="flex-fill">
                        <span class="d-block fw-bold" style="font-size:.86rem;color:#1d2b23;line-height:1.35;">Misi <?= (int) $m['nomor'] ?></span>
                        <span class="d-block text-muted" style="font-size:.79rem;line-height:1.4;"><?= esc($m['misi']) ?></span>
                        <span class="d-block mt-2" style="font-size:.77rem;color:#5d6b62;">
                          <strong><?= (int) $m['indikator'] ?></strong> indikator PK mendukung misi ini &middot;
                          <?= (int) $m['valid'] ?> valid &middot; <?= (int) $m['belum'] ?> belum lengkap
                          <?php if ($m['sumber'] === 'renstra'): ?>
                            <span class="badge-soft ms-1" style="background:#f1f3f2;color:#6b7a70;">keterkaitan via Renstra</span>
                          <?php endif; ?>
                        </span>
                      </span>
                      <i class="fas fa-chevron-right text-muted mt-1" style="font-size:.7rem"></i>
                    </button>
                  </div>
                <?php endforeach; ?>

                <?php if ($misi['tanpa_misi'] !== []): ?>
                  <div class="misi-item">
                    <button type="button" class="misi-btn" data-drawer="misi" data-misi="0">
                      <span class="misi-no" style="background:#f1f3f2;color:#8a968f;"><i class="fas fa-question"></i></span>
                      <span class="flex-fill">
                        <span class="d-block fw-bold" style="font-size:.86rem;color:#1d2b23;">Belum memiliki keterkaitan Misi Bupati</span>
                        <span class="d-block mt-1" style="font-size:.77rem;color:#5d6b62;">
                          <strong><?= count($misi['tanpa_misi']) ?></strong> indikator PK belum terpetakan ke Misi RPJMD.
                        </span>
                      </span>
                      <i class="fas fa-chevron-right text-muted mt-1" style="font-size:.7rem"></i>
                    </button>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
  </div>

  <!-- ======================= DRAWER ======================= -->
  <div class="offcanvas offcanvas-end dash-drawer" tabindex="-1" id="dashDrawer" aria-labelledby="dashDrawerTitle">
    <div class="offcanvas-header">
      <div>
        <h5 class="offcanvas-title mb-0" id="dashDrawerTitle">Rincian</h5>
        <small id="dashDrawerSub" class="text-white-50"></small>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body" id="dashDrawerBody"></div>
  </div>

  <?php if ($adaData): ?>
    <script>
      /* Data dashboard ditanam sebagai JSON — halaman utama tidak memanggil
         AJAX saat dimuat; AJAX hanya dipakai untuk drawer detail indikator. */
      window.DASH = <?= $js([
        'indicators'  => $dash['indicators'],
        'insights'    => $dash['insights'],
        'series'      => $dash['chart_series'],
        'distribusi'  => $dash['status_distribution'],
        'anggaran'    => $dash['anggaran'],
        'misi'        => $dash['misi'],
        'capaian'     => $dash['capaian'],
        'pk'          => $dash['pk'],
        'context'     => $dash['context'],
        'urls'        => [
          'indicator' => base_url('adminopd/dashboard/indicator'),
          'status'    => base_url('adminopd/dashboard/status'),
          'pk'        => base_url('adminopd/pk/' . $pkSegment . $qsTahun),
          'renaksi'   => base_url('adminopd/target_renaksi' . $qsTahun),
          'monev'     => base_url('adminopd/monev' . $qsTahun),
          'lakip'     => base_url('adminopd/lakip' . $qsTahun),
        ],
        'filterQs'    => $filterQs,
      ]) ?>;
    </script>
    <script>
      (function () {
        'use strict';
        var D = window.DASH;
        var rp = function (n) {
          if (n === null || n === undefined) return '-';
          return 'Rp ' + Number(n).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        };
        var pct = function (n) {
          if (n === null || n === undefined || isNaN(n)) return '-';
          var s = Number(n).toFixed(2).replace('.', ',');
          return s.replace(/,00$/, '') + '%';
        };
        var esc = function (s) {
          return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        };
        var romawi = ['', 'I', 'II', 'III', 'IV'];

        /* ---------------- Grafik 1: distribusi status ---------------- */
        var segs = D.distribusi.segments || [];
        var elStatus = document.getElementById('chartStatus');
        var chartStatus = null;
        if (elStatus) {
          if (!segs.length) {
            elStatus.parentNode.innerHTML =
              '<div class="empty"><div class="ic"><i class="fas fa-chart-pie"></i></div>' +
              '<p class="mb-0 small">Belum ada indikator yang dapat dikelompokkan statusnya.</p></div>';
          } else {
            chartStatus = new Chart(elStatus, {
              type: 'doughnut',
              data: {
                labels: segs.map(function (s) { return s.name; }),
                datasets: [{ data: segs.map(function (s) { return s.count; }), backgroundColor: segs.map(function (s) { return s.color; }), borderWidth: 0 }]
              },
              options: {
                responsive: true, maintainAspectRatio: false, cutout: '64%',
                plugins: {
                  legend: { display: false },
                  tooltip: {
                    backgroundColor: '#15311f', padding: 10, cornerRadius: 8, displayColors: false,
                    callbacks: { label: function (c) { return c.label + ': ' + c.parsed + ' indikator'; } }
                  }
                },
                onClick: function (evt, items) {
                  if (!items.length) return;
                  bukaStatus(segs[items[0].index].code, segs[items[0].index].name);
                }
              }
            });

            document.getElementById('legendStatus').innerHTML = segs.map(function (s) {
              return '<button type="button" class="btn btn-sm btn-light border" data-status="' + esc(s.code) + '" ' +
                'style="font-size:.74rem;font-weight:600;">' +
                '<span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:' + esc(s.color) + ';margin-right:6px;"></span>' +
                esc(s.name) + ' (' + s.count + ')</button>';
            }).join('');
            document.querySelectorAll('#legendStatus [data-status]').forEach(function (b) {
              b.addEventListener('click', function () { bukaStatus(b.getAttribute('data-status'), b.textContent.trim()); });
            });
          }
        }

        /* ---------------- Grafik 2: target vs capaian triwulanan ---------------- */
        var series = D.series || [];
        var picker = document.getElementById('serialPicker');
        var elTw = document.getElementById('chartTriwulan');
        var chartTw = null;

        function pilihanBawaan() {
          // 1) indikator kritis, 2) pendukung Misi Bupati, 3) indikator valid pertama
          var i = series.findIndex(function (s) { return s.status === 'critical'; });
          if (i < 0) i = series.findIndex(function (s) { return s.misi; });
          if (i < 0) i = series.findIndex(function (s) { return s.is_valid; });
          return i < 0 ? 0 : i;
        }

        function gambarSeri(idx) {
          var s = series[idx];
          if (!s) return;
          var d = s.series;
          var info = document.getElementById('serialInfo');
          if (chartTw) chartTw.destroy();

          chartTw = new Chart(elTw, {
            type: 'bar',
            data: {
              labels: ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'],
              datasets: [
                { label: 'Target', data: d.target, backgroundColor: 'rgba(63,98,150,.75)', borderRadius: 6, maxBarThickness: 34 },
                { label: 'Capaian', data: d.capaian, backgroundColor: 'rgba(10,143,80,.85)', borderRadius: 6, maxBarThickness: 34 }
              ]
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              scales: { y: { beginAtZero: true, grid: { color: '#eef2ef' } }, x: { grid: { display: false } } },
              plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                  backgroundColor: '#15311f', padding: 10, cornerRadius: 8,
                  callbacks: {
                    label: function (c) {
                      // Untuk satuan predikat, tooltip menampilkan kode aslinya
                      // (mis. "WTP"), bukan skor yang dipakai memposisikan batang.
                      var arr = c.datasetIndex === 0 ? d.label_target : d.label_capaian;
                      var teks = arr[c.dataIndex];
                      if (teks === null || teks === undefined) return c.dataset.label + ': belum diisi';
                      return c.dataset.label + ': ' + teks + (s.satuan ? ' ' + s.satuan : '');
                    }
                  }
                }
              }
            }
          });

          if (info) {
            info.innerHTML = 'Metode: <strong>' + esc(d.metode_nama) + '</strong>' +
              (s.satuan ? ' &middot; Satuan: <strong>' + esc(s.satuan) + '</strong>' : '') +
              (d.predikat ? ' &middot; nilai predikat dipetakan ke skala satuan untuk posisi grafik' : '') +
              (s.is_valid ? '' : ' &middot; <span class="text-warning-emphasis">indikator ini belum dapat dihitung</span>');
          }
        }

        if (picker && elTw) {
          if (!series.length) {
            elTw.parentNode.innerHTML =
              '<div class="empty"><div class="ic"><i class="fas fa-chart-column"></i></div>' +
              '<p class="mb-0 small">Belum ada Rencana Aksi bertarget triwulanan untuk ditampilkan.</p></div>';
            picker.style.display = 'none';
          } else {
            var grup = {};
            series.forEach(function (s, i) { (grup[s.indikator] = grup[s.indikator] || []).push(i); });
            picker.innerHTML = Object.keys(grup).map(function (nama) {
              return '<optgroup label="' + esc(nama) + '">' + grup[nama].map(function (i) {
                return '<option value="' + i + '">' + esc(series[i].label || nama) + '</option>';
              }).join('') + '</optgroup>';
            }).join('');
            var bawaan = pilihanBawaan();
            picker.value = String(bawaan);
            gambarSeri(bawaan);
            picker.addEventListener('change', function () { gambarSeri(parseInt(picker.value, 10)); });
          }
        }

        /* ---------------- Drawer ---------------- */
        var drawerEl = document.getElementById('dashDrawer');
        var drawer = new bootstrap.Offcanvas(drawerEl);
        var body = document.getElementById('dashDrawerBody');
        var judul = document.getElementById('dashDrawerTitle');
        var sub = document.getElementById('dashDrawerSub');

        function buka(t, s, html) {
          judul.textContent = t;
          sub.textContent = s || '';
          body.innerHTML = html;
          drawer.show();
        }
        function memuat(t, s) {
          buka(t, s, '<div class="drawer-section"><div class="skel" style="width:70%"></div><div class="skel"></div><div class="skel" style="width:85%"></div></div>');
        }

        function badge(st) {
          return '<span class="badge-soft" style="background:' + esc(st.color_soft) + ';color:' + esc(st.color_hex) + ';">' +
            (st.icon ? '<i class="fas ' + esc(st.icon) + '"></i>' : '') + esc(st.name) + '</span>';
        }

        /** Satu kartu indikator di dalam drawer (bukan tabel besar). */
        function kartuIndikator(i) {
          var aksi =
            '<a class="btn btn-sm btn-outline-success" href="' + esc(D.urls.pk) + '">Lihat PK</a> ' +
            '<a class="btn btn-sm btn-outline-success" href="' + esc(D.urls.renaksi) + '">Rencana Aksi</a> ' +
            '<a class="btn btn-sm btn-outline-success" href="' + esc(D.urls.monev) + '">MONEV</a>';

          return '<div class="ind-card" data-indikator="' + i.indikator_id + '" role="button">' +
            '<div class="d-flex justify-content-between gap-2 align-items-start">' +
              '<div class="fw-bold" style="font-size:.87rem;line-height:1.35;">' + esc(i.indikator) + '</div>' +
              '<div class="ind-pct">' + (i.percentage_teks || '-') + '</div>' +
            '</div>' +
            '<div class="ind-meta">' +
              '<span>Target: <strong>' + esc(i.target_tahunan || '-') + '</strong> ' + esc(i.satuan || '') + '</span>' +
              (i.capaian_terakhir ? '<span>Capaian TW ' + romawi[i.capaian_terakhir.triwulan] + ': <strong>' + esc(i.capaian_terakhir.nilai) + '</strong></span>' : '<span>Capaian: belum ada</span>') +
              '<span>Metode: ' + esc(i.metode_nama) + '</span>' +
            '</div>' +
            '<div class="d-flex flex-wrap gap-2 mt-2">' + badge(i.status) +
              '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + (i.is_valid ? 'Valid' : 'Belum valid') + '</span>' +
              '<span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">' + esc(i.verification.label) + '</span>' +
            '</div>' +
            (i.reason ? '<div class="ins-why mt-2"><i class="fas fa-circle-info me-1"></i>' + esc(i.reason) + '</div>' : '') +
            '<div class="mt-2 d-flex flex-wrap gap-1">' + aksi + '</div>' +
          '</div>';
        }

        function daftarIndikator(list, kosong) {
          if (!list.length) {
            return '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas fa-inbox"></i></div>' +
              '<p class="mb-0 small">' + esc(kosong) + '</p></div></div>';
          }
          return list.map(kartuIndikator).join('');
        }

        /* -- Drawer: daftar indikator per status (AJAX, agar selalu segar) -- */
        function bukaStatus(code, nama) {
          memuat('Indikator: ' + nama, 'Tahun ' + D.context.tahun + ' s.d. Triwulan ' + romawi[D.context.triwulan]);
          fetch(D.urls.status + '/' + encodeURIComponent(code) + '?' + D.filterQs, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(function (r) { return r.json(); })
            .then(function (j) {
              if (j.status !== 'success') throw new Error(j.message || 'Gagal memuat');
              body.innerHTML = daftarIndikator(j.data.indicators, 'Tidak ada indikator pada status ini.');
            })
            .catch(function (e) {
              body.innerHTML = '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas fa-triangle-exclamation"></i></div>' +
                '<p class="mb-0 small">' + esc(e.message) + '</p></div></div>';
            });
        }

        /* -- Drawer: detail satu indikator (AJAX) -- */
        function bukaIndikator(id) {
          memuat('Detail Indikator', 'Memuat…');
          fetch(D.urls.indicator + '/' + encodeURIComponent(id) + '?' + D.filterQs, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(function (r) { return r.json(); })
            .then(function (j) {
              if (j.status !== 'success') throw new Error(j.message || 'Gagal memuat');
              var d = j.data;
              var tw = ['I', 'II', 'III', 'IV'];
              var barisTw = d.rows.map(function (b) {
                var sel = tw.map(function (r, k) {
                  var t = b.targets[k + 1], c = b.capaian[k + 1];
                  return '<div class="d-flex justify-content-between" style="font-size:.79rem;padding:3px 0;border-bottom:1px dashed #eef2ef;">' +
                    '<span class="text-muted">TW ' + r + '</span>' +
                    '<span>Target <strong>' + esc(t === null || t === '' ? '-' : t) + '</strong> &middot; ' +
                    'Capaian <strong>' + esc(c === null || c === '' ? '-' : c) + '</strong></span></div>';
                }).join('');
                return '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">' +
                  esc(b.label || 'Rencana Aksi') + '</div>' + sel +
                  (b.validity.reason ? '<div class="ins-why mt-2"><i class="fas fa-circle-info me-1"></i>' + esc(b.validity.reason) + '</div>' : '') +
                  '</div>';
              }).join('');

              var misiHtml = d.misi.length
                ? d.misi.map(function (m) {
                    return '<div style="font-size:.8rem;">&bull; ' + esc(m.misi) +
                      (m.sumber === 'renstra' ? ' <span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">via Renstra</span>' : '') + '</div>';
                  }).join('')
                : '<div class="text-muted" style="font-size:.8rem;">Belum memiliki keterkaitan Misi Bupati.</div>';

              var programHtml = d.programs.length
                ? d.programs.map(function (p) {
                    return '<div class="d-flex justify-content-between gap-2" style="font-size:.79rem;padding:3px 0;">' +
                      '<span>' + esc(p.kode ? p.kode + ' — ' : '') + esc(p.nama) + '</span>' +
                      '<span class="text-nowrap">' + rp(p.anggaran) + '</span></div>';
                  }).join('')
                : '<div class="text-muted" style="font-size:.8rem;">Belum ada program pendukung pada PK.</div>';

              judul.textContent = 'Detail Indikator';
              sub.textContent = d.sasaran;
              body.innerHTML =
                '<div class="drawer-section">' +
                  '<div class="fw-bold mb-2" style="font-size:.92rem;line-height:1.4;">' + esc(d.indikator) + '</div>' +
                  '<div class="d-flex flex-wrap gap-2 mb-3">' + badge(d.status) +
                    '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + (d.validity.is_valid ? 'Valid' : 'Belum valid') + '</span>' +
                    '<span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">' + esc(d.verification.label) + '</span>' +
                  '</div>' +
                  '<dl class="drawer-dl mb-0">' +
                    '<dt>Sasaran PK</dt><dd>' + esc(d.sasaran) + '</dd>' +
                    '<dt>Satuan</dt><dd>' + esc(d.satuan || '-') + '</dd>' +
                    '<dt>Target tahunan</dt><dd>' + esc(d.target_tahunan || '-') + '</dd>' +
                    '<dt>Metode perhitungan</dt><dd>' + esc(d.rows.length ? (d.rows[0].metode ? d.rows[0].metode : 'belum dipilih') : '-') + '</dd>' +
                    '<dt>Capaian total</dt><dd>' + (d.percentage_teks || 'belum dapat dihitung') + '</dd>' +
                    '<dt>Rencana Aksi</dt><dd>' + d.renaksi_count + ' rencana &middot; ' + d.sub_count + ' sub rencana</dd>' +
                    '<dt>Penanggung jawab</dt><dd>' + esc(d.penanggung_jawab || d.pejabat_jabatan || '-') + '</dd>' +
                    '<dt>Anggaran</dt><dd>' + esc(d.anggaran_teks) + '</dd>' +
                    '<dt>Realisasi anggaran</dt><dd>' + (d.realisasi_teks || 'belum dilaporkan') + '</dd>' +
                    '<dt>Pembaruan terakhir</dt><dd>' + esc(d.updated_at || '-') + '</dd>' +
                  '</dl>' +
                  (d.validity.reason ? '<div class="ins-why mt-3"><i class="fas fa-circle-info me-1"></i>' + esc(d.validity.reason) + '</div>' : '') +
                '</div>' +
                '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">Misi Bupati yang didukung</div>' + misiHtml + '</div>' +
                barisTw +
                '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">Program &amp; anggaran</div>' + programHtml + '</div>' +
                '<div class="drawer-section d-flex flex-wrap gap-2">' +
                  '<a class="btn btn-sm btn-success" href="' + esc(d.links.pk) + '">Lihat Perjanjian Kinerja</a>' +
                  '<a class="btn btn-sm btn-outline-success" href="' + esc(d.links.renaksi) + '">Target &amp; Rencana Aksi</a>' +
                  '<a class="btn btn-sm btn-outline-success" href="' + esc(d.links.monev) + '">MONEV</a>' +
                  '<a class="btn btn-sm btn-outline-success" href="' + esc(d.links.lakip) + '">LAKIP</a>' +
                '</div>';
            })
            .catch(function (e) {
              body.innerHTML = '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas fa-triangle-exclamation"></i></div>' +
                '<p class="mb-0 small">' + esc(e.message) + '</p></div></div>';
            });
        }

        /* -- Drawer dari kartu ringkasan (data sudah tertanam, tanpa AJAX) -- */
        var drawerBuilder = {
          pk: function () {
            var head = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
              '<dt>Dokumen PK</dt><dd>' + D.pk.pk_count + ' dokumen &middot; ' + esc(D.pk.jenis_label) + '</dd>' +
              '<dt>Indikator PK</dt><dd>' + D.pk.indikator + '</dd>' +
              '<dt>Mendukung Misi Bupati</dt><dd>' + D.pk.mendukung_misi + '</dd>' +
              '<dt>Belum ada Rencana Aksi</dt><dd>' + D.pk.tanpa_renaksi + '</dd>' +
              '<dt>Indikator pendukung</dt><dd>' + D.pk.pendukung.indikator + ' (Administrator &amp; Pengawas, tidak masuk capaian OPD)</dd>' +
              '</dl><a class="btn btn-sm btn-success mt-3" href="' + esc(D.urls.pk) + '">Lihat Perjanjian Kinerja</a></div>';
            return { t: 'Perjanjian Kinerja', s: D.pk.jenis_label, html: head + daftarIndikator(D.indicators, 'Belum ada indikator PK.') };
          },
          capaian: function () {
            var c = D.capaian;
            var ringkas = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
              '<dt>Capaian Total OPD</dt><dd>' + (c.can_compute ? pct(c.total) : 'belum dapat dihitung') + '</dd>' +
              '<dt>Indikator valid</dt><dd>' + c.valid + ' dari ' + c.wajib + '</dd>' +
              '<dt>Status nilai</dt><dd>' + esc(c.label) + '</dd>' +
              '</dl><p class="text-muted mt-3 mb-0" style="font-size:.75rem;">' + esc(c.verifikasi.note) + '</p></div>';
            var bermasalah = D.indicators.filter(function (i) { return !i.is_valid; });
            var tombol = bermasalah.length
              ? '<div class="drawer-section"><button type="button" class="btn btn-sm btn-outline-danger" data-filter="belum_valid">' +
                'Lihat ' + bermasalah.length + ' indikator bermasalah</button></div>'
              : '';
            return { t: 'Rincian Indikator', s: c.valid + ' dari ' + c.wajib + ' indikator valid', html: ringkas + tombol + daftarIndikator(D.indicators, 'Belum ada indikator PK.') };
          },
          anggaran: function () {
            var a = D.anggaran;
            var ringkas = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
              '<dt>Pagu anggaran</dt><dd>' + rp(a.anggaran) + '</dd>' +
              '<dt>Realisasi s.d. TW ' + romawi[a.triwulan] + '</dt><dd>' + (a.realisasi === null ? 'belum dilaporkan' : rp(a.realisasi)) + '</dd>' +
              '<dt>Penyerapan</dt><dd>' + (a.persen === null ? '-' : pct(a.persen)) + '</dd>' +
              '</dl><p class="text-muted mt-3 mb-0" style="font-size:.75rem;">' +
              'Realisasi kosong berarti <strong>belum dilaporkan</strong> — tidak dianggap 0. Efisiensi anggaran tetap diinput manual di modul LAKIP.</p>' +
              (a.program_lain_count
                ? '<div class="alert alert-warning mt-3 mb-0 py-2 px-3" style="font-size:.76rem;">' +
                  '<i class="fas fa-triangle-exclamation me-1"></i>' + a.program_lain_count +
                  ' program yang tertaut pada PK tercatat milik Perangkat Daerah lain (pagu ' + rp(a.anggaran_lain) +
                  '). Pagu tersebut <strong>tidak dijumlahkan</strong>; perbaiki pilihannya lewat modul Perjanjian Kinerja.</div>'
                : '') +
              '</div>';

            var semuaProgram = a.programs.concat(a.program_lain || []);
            var daftar = semuaProgram.length ? semuaProgram.map(function (p) {
              var serap = (p.anggaran > 0 && p.realisasi !== null) ? (p.realisasi / p.anggaran * 100) : null;
              var statusTeks = { belum_dilaporkan: 'Realisasi belum dilaporkan', sebagian: 'Realisasi belum lengkap', lengkap: 'Realisasi lengkap' }[p.realisasi_status] || '-';
              return '<div class="ind-card"' + (p.milik_opd_lain ? ' style="border-color:#f0d9a8;background:#fffdf6;"' : '') + '>' +
                '<div class="fw-bold" style="font-size:.85rem;line-height:1.35;">' + esc(p.kode ? p.kode + ' — ' : '') + esc(p.nama) +
                (p.milik_opd_lain ? ' <span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">milik PD lain — tidak dihitung</span>' : '') + '</div>' +
                '<div class="ind-meta">' +
                  '<span>Anggaran: <strong>' + rp(p.anggaran) + '</strong></span>' +
                  '<span>Realisasi: <strong>' + (p.realisasi === null ? 'belum dilaporkan' : rp(p.realisasi)) + '</strong></span>' +
                  '<span>Penyerapan: <strong>' + (serap === null ? '-' : pct(serap)) + '</strong></span>' +
                '</div>' +
                '<div class="ind-meta"><span>Mendukung: ' + p.indikator.map(function (x) { return esc(x.nama); }).join('; ') + '</span></div>' +
                '<div class="mt-2 d-flex flex-wrap gap-2 align-items-center">' +
                  '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + esc(statusTeks) + '</span>' +
                  '<a class="btn btn-sm btn-outline-success" href="' + esc(D.urls.monev) + '">MONEV Anggaran</a>' +
                  '<a class="btn btn-sm btn-outline-success" href="' + esc(D.urls.lakip) + '">LAKIP</a>' +
                '</div></div>';
            }).join('') :
              '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas fa-inbox"></i></div>' +
              '<p class="mb-0 small">Belum ada program pendukung pada Perjanjian Kinerja.</p></div></div>';

            return { t: 'Penyerapan Anggaran', s: a.program_count + ' program pendukung', html: ringkas + daftar };
          },
          perhatian: function () {
            var html = D.insights.length ? D.insights.map(function (i) {
              return '<div class="ins" style="background:#fff;">' +
                '<div class="ins-bar" style="background:' + esc(i.color.hex) + '"></div>' +
                '<div class="ins-body"><div class="ins-title">' + esc(i.judul) + '</div>' +
                '<div class="ins-why">' + esc(i.alasan) + '</div>' +
                '<span class="badge-soft mt-2" style="background:' + esc(i.color.soft) + ';color:' + esc(i.color.hex) + ';">' + esc(i.status) + '</span></div>' +
                '<div class="ins-act"><a class="btn btn-sm btn-outline-success" href="' + esc(i.url) + '">' + esc(i.tombol) + '</a></div></div>';
            }).join('') :
              '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas fa-circle-check"></i></div>' +
              '<p class="mb-0 small">Tidak ada kondisi yang perlu ditindaklanjuti.</p></div></div>';
            return { t: 'Prioritas Tindak Lanjut', s: D.insights.length + ' kondisi', html: '<div class="drawer-section">' + html + '</div>' };
          }
        };

        function bukaMisi(misiId) {
          var judulMisi, daftar;
          if (misiId === 0) {
            judulMisi = 'Belum memiliki keterkaitan Misi Bupati';
            daftar = D.misi.tanpa_misi;
          } else {
            var m = D.misi.items.filter(function (x) { return x.misi_id === misiId; })[0];
            if (!m) return;
            judulMisi = 'Misi ' + m.nomor;
            daftar = m.daftar;
            var kepala = '<div class="drawer-section">' +
              '<div class="fw-bold mb-1" style="font-size:.88rem;line-height:1.4;">' + esc(m.misi) + '</div>' +
              '<div class="ind-meta"><span>' + m.indikator + ' indikator PK</span><span>' + m.valid + ' valid</span><span>' + m.belum + ' belum lengkap</span></div>' +
              (m.sumber === 'renstra'
                ? '<p class="text-muted mt-2 mb-0" style="font-size:.75rem;">Keterkaitan ditelusuri lewat rantai Renstra OPD → sasaran RPJMD → misi, karena PK ini belum dipetakan langsung ke Misi RPJMD.</p>'
                : '<p class="text-muted mt-2 mb-0" style="font-size:.75rem;">Keterkaitan diambil dari pemetaan Misi pada dokumen Perjanjian Kinerja.</p>') +
              '<a class="btn btn-sm btn-success mt-3" href="' + esc(D.urls.pk) + '">Lihat PK terkait</a></div>';
            buka(judulMisi, m.indikator + ' indikator PK mendukung misi ini', kepala + daftarIndikator(daftar, 'Belum ada indikator.'));
            return;
          }
          buka(judulMisi, daftar.length + ' indikator', daftarIndikator(daftar, 'Tidak ada indikator.'));
        }

        /* ---------------- Perutean klik ---------------- */
        document.addEventListener('click', function (e) {
          var t = e.target.closest('[data-drawer]');
          if (t) {
            var jenis = t.getAttribute('data-drawer');
            if (jenis === 'misi') { bukaMisi(parseInt(t.getAttribute('data-misi'), 10)); return; }
            var b = drawerBuilder[jenis];
            if (b) { var r = b(); buka(r.t, r.s, r.html); }
            return;
          }

          var f = e.target.closest('[data-filter]');
          if (f) { bukaStatus(f.getAttribute('data-filter'), 'Belum Valid'); return; }

          // Klik kartu indikator di dalam drawer -> detail indikator.
          var card = e.target.closest('.ind-card[data-indikator]');
          if (card && !e.target.closest('a')) {
            bukaIndikator(card.getAttribute('data-indikator'));
          }
        });
      })();
    </script>
  <?php endif; ?>
</body>

</html>

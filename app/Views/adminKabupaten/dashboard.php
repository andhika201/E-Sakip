<?php $page = 'dashboard'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard e-SAKIP</title>

  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <?php if (function_exists('csrf_token')): ?>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
  <?php endif; ?>

  <style>
    .card-mini h4 { font-weight: 700; }
    .card-mini small { color: #6c757d; }
    .chart-box { height: 150px; }
    .btn-loading { pointer-events: none; opacity: .75; }

    /* ===== Dashboard modern ===== */
    .dash-hero {
      background: linear-gradient(120deg, #00803f 0%, #00642f 100%);
      color: #fff;
      border-radius: 18px;
      padding: 26px 28px;
      display: flex;
      align-items: center;
      gap: 18px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 14px 34px rgba(0, 116, 62, .22);
    }
    .dash-hero::after {
      content: '';
      position: absolute;
      right: -30px; top: -40px;
      width: 170px; height: 170px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .08);
    }
    .dash-hero .dh-ic {
      width: 60px; height: 60px;
      border-radius: 16px;
      background: rgba(255, 255, 255, .16);
      display: grid; place-items: center;
      font-size: 26px; flex: 0 0 auto;
    }
    .dash-hero h2 { font-weight: 800; margin: 0 0 4px; font-size: clamp(1.2rem, 2.4vw, 1.5rem); }
    .dash-hero p { margin: 0; opacity: .88; font-size: .9rem; }

    .stat-card {
      background: #fff;
      border: 1px solid #ebefec;
      border-radius: 16px;
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      height: 100%;
      box-shadow: 0 8px 22px rgba(16, 40, 24, .06);
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(16, 40, 24, .12); }
    .stat-card .sc-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      display: grid; place-items: center;
      color: #fff; font-size: 22px; flex: 0 0 auto;
    }
    .stat-card .sc-num { font-size: 1.7rem; font-weight: 800; line-height: 1; color: #15311f; }
    .stat-card .sc-label { font-size: .82rem; color: #6b7a70; margin-top: 3px; }
    .sc-green { background: linear-gradient(135deg, #0a8f50, #00743e); }
    .sc-lime  { background: linear-gradient(135deg, #84c225, #6eab11); }
    .sc-blue  { background: linear-gradient(135deg, #3f6296, #2f4d7a); }
    .sc-amber { background: linear-gradient(135deg, #c98a3c, #a86a26); }

    @media (max-width: 575px) {
      .dash-hero { padding: 20px; gap: 14px; }
      .dash-hero .dh-ic { width: 48px; height: 48px; font-size: 20px; }
      .stat-card { padding: 14px 16px; gap: 12px; }
      .stat-card .sc-icon { width: 44px; height: 44px; font-size: 18px; }
      .stat-card .sc-num { font-size: 1.4rem; }
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <div id="content-wrapper" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>

    <main class="flex-fill p-4 mt-2" id="main-content">
      <div class="dash-hero mb-4">
        <div class="dh-ic"><i class="fas fa-gauge-high"></i></div>
        <div>
          <h2>Selamat Datang di e-SAKIP</h2>
          <p>Sistem Akuntabilitas Kinerja Instansi Pemerintah &mdash; Admin Kabupaten</p>
        </div>
      </div>

      <?php if (isset($error_message)): ?>
        <div class="alert alert-warning" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?= esc($error_message) ?>
        </div>
      <?php endif; ?>

      <?php if (isset($summary_stats)): ?>
        <div class="row g-3 mb-4">
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="sc-icon sc-green"><i class="fas fa-landmark"></i></div>
              <div>
                <div class="sc-num"><?= (int) ($summary_stats['total_rpjmd'] ?? 0) ?></div>
                <div class="sc-label">Total RPJMD</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="sc-icon sc-lime"><i class="fas fa-diagram-project"></i></div>
              <div>
                <div class="sc-num"><?= (int) ($summary_stats['total_renstra'] ?? 0) ?></div>
                <div class="sc-label">Total RENSTRA</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="sc-icon sc-amber"><i class="fas fa-list-check"></i></div>
              <div>
                <div class="sc-num"><?= (int) ($summary_stats['total_rkt'] ?? 0) ?></div>
                <div class="sc-label">Total RKT</div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="sc-icon sc-blue"><i class="fas fa-building"></i></div>
              <div>
                <div class="sc-num"><?= (int) ($summary_stats['total_opd'] ?? 0) ?></div>
                <div class="sc-label">Total OPD</div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($pk_rollup)): ?>
        <div class="bg-white rounded shadow p-4 mb-4">
          <p class="fw-semibold text-dark mb-3">
            <i class="fas fa-file-signature me-2 text-success"></i>Realisasi Perjanjian Kinerja <span class="text-muted small">(lewat Rencana Aksi &amp; MONEV)</span>
          </p>
          <div class="row g-3">
            <?php
            $pkCards = [
              ['key' => 'bupati', 'label' => 'PK Bupati', 'url' => base_url('adminkab/monev')],
              ['key' => 'es2', 'label' => 'Eselon II', 'url' => base_url('adminkab/monev_pk/es3?eselon=jpt')],
              ['key' => 'es3', 'label' => 'Eselon III', 'url' => base_url('adminkab/monev_pk/es3?eselon=administrator')],
              ['key' => 'es4', 'label' => 'Eselon IV', 'url' => base_url('adminkab/monev_pk/es3?eselon=pengawas')],
            ];
            ?>
            <?php foreach ($pkCards as $c): ?>
              <?php $d = $pk_rollup[$c['key']] ?? ['indikator' => 0, 'renaksi' => 0, 'capaian' => 0]; ?>
              <div class="col-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><?= esc($c['label']) ?></span>
                    <a href="<?= $c['url'] ?>" class="btn btn-outline-success btn-sm">
                      <i class="fas fa-chart-line me-1"></i> Pantau
                    </a>
                  </div>
                  <div class="row text-center">
                    <div class="col">
                      <div class="h5 mb-0 fw-bold text-dark"><?= (int) ($d['indikator'] ?? 0) ?></div>
                      <small class="text-muted">Indikator</small>
                    </div>
                    <div class="col">
                      <div class="h5 mb-0 fw-bold text-primary"><?= (int) ($d['renaksi'] ?? 0) ?></div>
                      <small class="text-muted">Rencana Aksi</small>
                    </div>
                    <div class="col">
                      <div class="h5 mb-0 fw-bold text-success"><?= (int) ($d['capaian'] ?? 0) ?></div>
                      <small class="text-muted">Ada Capaian</small>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (setting('ai_dashboard_enabled', '1') === '1'): ?>
        <?= $this->include('templates/ai_widget') ?>
      <?php endif; ?>

      <div class="row g-4">
        <!-- RPJMD -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">RPJMD Kabupaten</p>
            <div class="chart-box"><canvas id="rpjmdChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draft</td>
                  <td id="rpjmdDraft" class="text-end"><?= (int) ($dashboard_data['rpjmd']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="rpjmdSelesai" class="text-end"><?= (int) ($dashboard_data['rpjmd']['selesai'] ?? 0) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RKPD (pakai data dari RKT) -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">RKPD</p>
            <div class="chart-box"><canvas id="rkpdChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draft</td>
                  <td id="rkpdDraft" class="text-end"><?= (int) ($dashboard_data['rkpd']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="rkpdSelesai" class="text-end"><?= (int) ($dashboard_data['rkpd']['selesai'] ?? 0) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- LAKIP Kabupaten -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">LAKIP Kabupaten</p>
            <div class="chart-box"><canvas id="lakipKabChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draft</td>
                  <td id="lakipKabDraft" class="text-end">
                    <?= (int) ($dashboard_data['lakip_kabupaten']['draft'] ?? 0) ?>
                  </td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="lakipKabSelesai" class="text-end">
                    <?= (int) ($dashboard_data['lakip_kabupaten']['selesai'] ?? 0) ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Filter OPD & Tahun -->
        <div class="col-12">
          <div class="bg-white rounded shadow p-4 mb-1">
            <h2 class="h5 fw-bold text-dark mb-3">Data SAKIP Per Unit Kerja</h2>
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-2">
              <div class="d-flex gap-2 flex-fill">
                <select class="form-select" id="filterOpd">
                  <option value="">Semua Unit Kerja</option>
                  <?php foreach (($dashboard_data['opd_list'] ?? []) as $opd): ?>
                    <option value="<?= (int) $opd['id'] ?>"><?= esc($opd['nama_opd']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select id="filterYear" class="form-select">
                  <option value="">Semua Tahun</option>
                  <?php foreach (($dashboard_data['available_years'] ?? []) as $y): ?>
                    <option value="<?= esc($y) ?>"><?= esc($y) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnReset">Reset</button>
                <button class="btn btn-primary" id="btnTampilkan">Tampilkan</button>
              </div>
            </div>
            <small class="text-muted">
              Filter ini mempengaruhi grafik RENSTRA, RKT, IKU, dan LAKIP (Kab/OPD sesuai sumber data).
            </small>
          </div>
        </div>

        <!-- RENSTRA -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">RENSTRA Kabupaten/OPD</p>
            <div class="chart-box"><canvas id="renstraChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draft</td>
                  <td id="renstraDraft" class="text-end"><?= (int) ($dashboard_data['renstra']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="renstraSelesai" class="text-end"><?= (int) ($dashboard_data['renstra']['selesai'] ?? 0) ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RKT -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">RKT</p>
            <div class="chart-box"><canvas id="rktChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draft</td>
                  <td id="rktDraft" class="text-end"><?= (int) ($dashboard_data['rkt']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="rktSelesai" class="text-end"><?= (int) ($dashboard_data['rkt']['selesai'] ?? 0) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- IKU -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">IKU Tahun Ini</p>
            <div class="chart-box"><canvas id="ikuChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Selesai</td>
                  <td id="ikuSelesai" class="text-end"><?= (int) ($dashboard_data['iku']['selesai'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Draft</td>
                  <td id="ikuDraft" class="text-end"><?= (int) ($dashboard_data['iku']['draft'] ?? 0) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- LAKIP OPD -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">LAKIP OPD</p>
            <div class="chart-box"><canvas id="lakipOpdChart"></canvas></div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draft</td>
                  <td id="lakipOpdDraft" class="text-end"><?= (int) ($dashboard_data['lakip_opd']['draft'] ?? 0) ?>
                  </td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="lakipOpdSelesai" class="text-end"><?= (int) ($dashboard_data['lakip_opd']['selesai'] ?? 0) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
  </div>

  <script>
    const dataPHP = <?= json_encode($dashboard_data ?? [], JSON_UNESCAPED_UNICODE) ?>;

    // Plugin: teks total di tengah doughnut
    Chart.register({
      id: 'centerText',
      afterDraw(chart) {
        const { ctx, chartArea } = chart;
        if (!chartArea) return;
        const ds = (chart.data.datasets[0] || {}).data || [];
        const total = ds.reduce((a, b) => a + (parseInt(b, 10) || 0), 0);
        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top + chartArea.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#15311f';
        ctx.font = '700 22px Inter, "Segoe UI", sans-serif';
        ctx.fillText(total, cx, cy - 5);
        ctx.fillStyle = '#9aa6a0';
        ctx.font = '700 9px Inter, "Segoe UI", sans-serif';
        ctx.fillText('TOTAL', cx, cy + 13);
        ctx.restore();
      }
    });

    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      layout: { padding: 4 },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#15311f',
          padding: 10,
          cornerRadius: 8,
          titleColor: '#fff',
          bodyColor: '#e8efe9',
          displayColors: false
        }
      },
      elements: { arc: { borderRadius: 6, borderWidth: 0, hoverOffset: 8 } }
    };

    function getVal(obj, key, def = 0) {
      if (!obj || typeof obj !== 'object') return def;
      const v = obj[key];
      const n = parseInt(v, 10);
      return isNaN(n) ? def : n;
    }

    const charts = {
      rpjmd: new Chart(document.getElementById('rpjmdChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.rpjmd || {}, 'selesai'),
              getVal(dataPHP.rpjmd || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      }),
      rkpd: new Chart(document.getElementById('rkpdChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.rkpd || {}, 'selesai'),
              getVal(dataPHP.rkpd || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      }),
      lakip_kabupaten: new Chart(document.getElementById('lakipKabChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.lakip_kabupaten || {}, 'selesai'),
              getVal(dataPHP.lakip_kabupaten || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      }),
      renstra: new Chart(document.getElementById('renstraChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.renstra || {}, 'selesai'),
              getVal(dataPHP.renstra || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      }),
      rkt: new Chart(document.getElementById('rktChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.rkt || {}, 'selesai'),
              getVal(dataPHP.rkt || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      }),
      iku: new Chart(document.getElementById('ikuChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.iku || {}, 'selesai'),
              getVal(dataPHP.iku || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      }),
      lakip_opd: new Chart(document.getElementById('lakipOpdChart'), {
        type: 'doughnut',
        data: {
          labels: ['Selesai', 'Draft'],
          datasets: [{
            data: [
              getVal(dataPHP.lakip_opd || {}, 'selesai'),
              getVal(dataPHP.lakip_opd || {}, 'draft')
            ],
            backgroundColor: ['#0a8f50', '#e9b949'],
            borderWidth: 0
          }]
        },
        options: chartOptions
      })
    };

    function notify(msg, t = 'info') {
      const cls = t === 'success' ? 'alert-success'
        : (t === 'error' ? 'alert-danger' : 'alert-info');
      const n = document.createElement('div');
      n.className = `alert ${cls} alert-dismissible fade show position-fixed`;
      n.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
      n.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
      document.body.appendChild(n);
      setTimeout(() => { n.remove(); }, 3500);
    }

    function updateSection(key, data) {
      if (!data) return;

      switch (key) {
        case 'rpjmd':
        case 'rkpd':
        case 'renstra':
        case 'rkt': {
          const obj = data[key] || {};
          const selesai = getVal(obj, 'selesai');
          const draft = getVal(obj, 'draft');

          charts[key].data.datasets[0].data = [selesai, draft];
          charts[key].update();

          const idMap = {
            rpjmd: ['rpjmdDraft', 'rpjmdSelesai'],
            rkpd: ['rkpdDraft', 'rkpdSelesai'],
            renstra: ['renstraDraft', 'renstraSelesai'],
            rkt: ['rktDraft', 'rktSelesai']
          };
          const ids = idMap[key];
          if (ids) {
            const draftEl = document.getElementById(ids[0]);
            const selesaiEl = document.getElementById(ids[1]);
            if (draftEl) draftEl.textContent = draft;
            if (selesaiEl) selesaiEl.textContent = selesai;
          }
          break;
        }

        case 'iku': {
          const obj = data.iku || {};
          const selesai = getVal(obj, 'selesai');
          const draft   = getVal(obj, 'draft');

          charts.iku.data.datasets[0].data = [selesai, draft];
          charts.iku.update();

          const sCell = document.getElementById('ikuSelesai');
          const dCell = document.getElementById('ikuDraft');
          if (sCell) sCell.textContent = selesai;
          if (dCell) dCell.textContent = draft;
          break;
        }

        case 'lakip_kabupaten': {
          const obj = data.lakip_kabupaten || {};
          const selesai = getVal(obj, 'selesai');
          const draft   = getVal(obj, 'draft');

          charts.lakip_kabupaten.data.datasets[0].data = [selesai, draft];
          charts.lakip_kabupaten.update();

          const dCell = document.getElementById('lakipKabDraft');
          const sCell = document.getElementById('lakipKabSelesai');
          if (dCell) dCell.textContent = draft;
          if (sCell) sCell.textContent = selesai;
          break;
        }

        case 'lakip_opd': {
          const obj = data.lakip_opd || {};
          const selesai = getVal(obj, 'selesai');
          const draft   = getVal(obj, 'draft');

          charts.lakip_opd.data.datasets[0].data = [selesai, draft];
          charts.lakip_opd.update();

          const dCell = document.getElementById('lakipOpdDraft');
          const sCell = document.getElementById('lakipOpdSelesai');
          if (dCell) dCell.textContent = draft;
          if (sCell) sCell.textContent = selesai;
          break;
        }
      }
    }

    const selOpd = document.getElementById('filterOpd');
    const selYr = document.getElementById('filterYear');
    const btnGo = document.getElementById('btnTampilkan');
    const btnRs = document.getElementById('btnReset');

    btnGo.addEventListener('click', async () => {
      const opdId = selOpd.value;
      const year = selYr.value;

      // CI4: csrf_token() = nama field, csrf_hash() = nilainya
      const csrfName = document.querySelector('meta[name="csrf-token"]')?.content;
      const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;

      const parts = [];
      if (csrfName && csrfHash) {
        parts.push(encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash));
      }
      parts.push('opd_id=' + encodeURIComponent(opdId || ''));
      parts.push('year=' + encodeURIComponent(year || ''));
      const body = parts.join('&');

      const oldText = btnGo.textContent;
      btnGo.classList.add('btn-loading');
      btnGo.textContent = 'Memuat...';

      try {
        // ✅ route kamu: $routes->post('dashboard/data', 'AdminKabupatenController::getDashboardData')
        const res = await fetch('<?= base_url('adminkab/dashboard/data') ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body
        });

        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (!ct.includes('application/json')) {
          // biasanya 404 / redirect login / error HTML
          const txt = await res.text();
          console.error('Non-JSON response:', txt);
          notify("Gagal memuat data (response tidak valid).", 'error');
          return;
        }

        const json = await res.json();

        if (json.status === 'success') {
          const d = json.data || {};
          updateSection('rpjmd', d);
          updateSection('rkpd', d);
          updateSection('renstra', d);
          updateSection('rkt', d);
          updateSection('iku', d);
          updateSection('lakip_kabupaten', d);
          updateSection('lakip_opd', d);

          // ✅ update CSRF hash agar request berikutnya aman
          if (json.csrfHash) {
            const metaHash = document.querySelector('meta[name="csrf-hash"]');
            if (metaHash) metaHash.setAttribute('content', json.csrfHash);
          }

          notify('Data berhasil difilter.', 'success');
        } else {
          notify(json.message || 'Terjadi kesalahan.', 'error');
        }
      } catch (e) {
        console.error(e);
        notify('Gagal memuat data.', 'error');
      } finally {
        btnGo.classList.remove('btn-loading');
        btnGo.textContent = oldText;
      }
    });

    btnRs.addEventListener('click', () => {
      selOpd.value = '';
      selYr.value = '';
    });
  </script>
</body>

</html>

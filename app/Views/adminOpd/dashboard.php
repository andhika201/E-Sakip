<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Dashboard e-SAKIP - Admin OPD') ?></title>

  <!-- Style utama -->
  <?= $this->include('adminOpd/templates/style.php'); ?>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
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

  <!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <!-- Navbar/Header -->
    <?= $this->include('adminOpd/templates/header.php'); ?>

    <!-- Sidebar -->
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="flex-fill p-4 mt-2">
      <div class="dash-hero mb-4">
        <div class="dh-ic"><i class="fas fa-gauge-high"></i></div>
        <div>
          <h2>Selamat Datang di e-SAKIP</h2>
          <p>Sistem Akuntabilitas Kinerja Instansi Pemerintah &mdash; Admin OPD</p>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="sc-icon sc-green"><i class="fas fa-diagram-project"></i></div>
            <div>
              <div class="sc-num"><?= (int) ($renstraStats['selesai'] ?? 0) ?></div>
              <div class="sc-label">RENSTRA Selesai</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="sc-icon sc-lime"><i class="fas fa-list-check"></i></div>
            <div>
              <div class="sc-num"><?= (int) ($renjaStats['selesai'] ?? 0) ?></div>
              <div class="sc-label">RENJA Selesai</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="sc-icon sc-amber"><i class="fas fa-bullseye"></i></div>
            <div>
              <div class="sc-num"><?= (int) ($ikuStats['selesai'] ?? 0) ?></div>
              <div class="sc-label">IKU Selesai</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <div class="sc-icon sc-blue"><i class="fas fa-file-contract"></i></div>
            <div>
              <div class="sc-num"><?= (int) ($lakipStats['selesai'] ?? 0) ?></div>
              <div class="sc-label">LAKIP Selesai</div>
            </div>
          </div>
        </div>
      </div>

      <?php if (setting('ai_dashboard_enabled', '1') === '1'): ?>
        <?= $this->include('templates/ai_widget') ?>
      <?php endif; ?>

      <div class="row g-4">
        <!-- RENSTRA -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">RENSTRA</p>
            <div style="height: 150px;">
              <canvas id="renstraChart"></canvas>
            </div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                  <th class="text-center">Ceklis</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draf</td>
                  <td class="text-end"><?= esc($renstraStats['draft'] ?? 0) ?></td>
                  <td class="text-center">—</td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td class="text-end"><?= esc($renstraStats['selesai'] ?? 0) ?></td>
                  <td class="text-center <?= ($renstraStats['selesai'] ?? 0) > 0 ? 'text-success' : '' ?>">
                    <?= ($renstraStats['selesai'] ?? 0) > 0 ? '<i class="fas fa-check-circle"></i>' : '—' ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RENJA / RKT -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">RENJA (RKT)</p>
            <div style="height: 150px;">
              <canvas id="renjaChart"></canvas>
            </div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                  <th class="text-center">Ceklis</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Draf</td>
                  <td class="text-end"><?= esc($renjaStats['draft'] ?? 0) ?></td>
                  <td class="text-center">—</td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td class="text-end"><?= esc($renjaStats['selesai'] ?? 0) ?></td>
                  <td class="text-center <?= ($renjaStats['selesai'] ?? 0) > 0 ? 'text-success' : '' ?>">
                    <?= ($renjaStats['selesai'] ?? 0) > 0 ? '<i class="fas fa-check-circle"></i>' : '—' ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- IKU -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">IKU Aktif</p>
            <div style="height: 150px;">
              <canvas id="ikuChart"></canvas>
            </div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                  <th class="text-center">Ceklis</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Selesai</td>
                  <td class="text-end"><?= esc($ikuStats['selesai'] ?? 0) ?></td>
                  <td class="text-center <?= ($ikuStats['selesai'] ?? 0) > 0 ? 'text-success' : '' ?>">
                    <?= ($ikuStats['selesai'] ?? 0) > 0 ? '<i class="fas fa-check-circle"></i>' : '—' ?>
                  </td>
                </tr>
                <tr>
                  <td>Draft</td>
                  <td class="text-end"><?= esc($ikuStats['draft'] ?? 0) ?></td>
                  <td class="text-center">—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!--  PK -->
        <!-- <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">Perjanjian Kinerja (PK)</p>
            <div style="height: 150px;">
              <canvas id="pkChart"></canvas>
            </div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                  <th class="text-center">Ceklis</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Ditandatangani</td>
                  <td class="text-end"><?= esc($pkStats['ditandatangani'] ?? 0) ?></td>
                  <td class="text-center <?= ($pkStats['ditandatangani'] ?? 0) > 0 ? 'text-success' : '' ?>">
                    <?= ($pkStats['ditandatangani'] ?? 0) > 0 ? '<i class="fas fa-check-circle"></i>' : '—' ?>
                  </td>
                </tr>
                <tr>
                  <td>Belum</td>
                  <td class="text-end"><?= esc($pkStats['belum'] ?? 0) ?></td>
                  <td class="text-center">—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>  -->

        <!-- LAKIP -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="bg-white rounded shadow p-4 h-100">
            <p class="fw-semibold text-dark mb-2">LAKIP Tahun Ini</p>
            <div style="height: 150px;">
              <canvas id="lakipChart"></canvas>
            </div>
            <table class="table table-sm table-bordered mt-3 mb-0">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th class="text-end">Jumlah</th>
                  <th class="text-center">Ceklis</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Selesai</td>
                  <td class="text-end"><?= esc($lakipStats['selesai'] ?? 0) ?></td>
                  <td class="text-center <?= ($lakipStats['selesai'] ?? 0) > 0 ? 'text-success' : '' ?>">
                    <?= ($lakipStats['selesai'] ?? 0) > 0 ? '<i class="fas fa-check-circle"></i>' : '—' ?>
                  </td>
                </tr>
                <tr>
                  <td>Draft</td>
                  <td class="text-end"><?= esc($lakipStats['draft'] ?? 0) ?></td>
                  <td class="text-center">—</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>

    <!-- Footer -->
    <?= $this->include('adminOpd/templates/footer.php'); ?>

  </div> <!-- End Content Wrapper -->

  <script>
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

    // Data dari PHP (fallback 0 kalau tidak ada)
    const renstraSelesai = <?= (int) ($renstraStats['selesai'] ?? 0) ?>;
    const renstraDraft = <?= (int) ($renstraStats['draft'] ?? 0) ?>;

    const renjaSelesai = <?= (int) ($renjaStats['selesai'] ?? 0) ?>;
    const renjaDraft = <?= (int) ($renjaStats['draft'] ?? 0) ?>;

    const ikuSelesai = <?= (int) ($ikuStats['selesai'] ?? 0) ?>;
    const ikuDraft   = <?= (int) ($ikuStats['draft']   ?? 0) ?>;

    const pkTtd   = <?= (int) ($pkStats['ditandatangani'] ?? 0) ?>;
    const pkBelum = <?= (int) ($pkStats['belum'] ?? 0) ?>;

    const lakipSelesai = <?= (int) ($lakipStats['selesai'] ?? 0) ?>;
    const lakipDraft   = <?= (int) ($lakipStats['draft']   ?? 0) ?>;

    new Chart(document.getElementById('renstraChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draf'],
        datasets: [{
          data: [renstraSelesai, renstraDraft],
          backgroundColor: ['#0a8f50', '#e9b949'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('renjaChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draf'],
        datasets: [{
          data: [renjaSelesai, renjaDraft],
          backgroundColor: ['#0a8f50', '#e9b949'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('ikuChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: [ikuSelesai, ikuDraft],
          backgroundColor: ['#0a8f50', '#e9b949'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('lakipChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: [lakipSelesai, lakipDraft],
          backgroundColor: ['#0a8f50', '#e9b949'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });
  </script>
</body>

</html>
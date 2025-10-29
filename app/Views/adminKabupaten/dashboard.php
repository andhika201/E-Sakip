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
    .card-mini h4 {
      font-weight: 700
    }

    .card-mini small {
      color: #6c757d
    }

    .chart-box {
      height: 150px
    }

    .btn-loading {
      pointer-events: none;
      opacity: .75
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <div id="content-wrapper" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>

    <main class="flex-fill p-4 mt-2" id="main-content">
      <div class="bg-white rounded shadow p-4 mb-3">
        <h2 class="h3 fw-bold text-dark mb-1">Selamat Datang di e-SAKIP</h2>
        <p class="text-muted mb-3">Sistem Akuntabilitas Kinerja Instansi Pemerintah — Admin Kabupaten</p>

        <?php if (isset($error_message)): ?>
          <div class="alert alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= esc($error_message) ?>
          </div>
        <?php endif; ?>

        <?php if (isset($summary_stats)): ?>
          <div class="row g-3 mt-2">
            <div class="col-6 col-md-3 card-mini text-center">
              <h4 class="text-primary mb-0"><?= (int) ($summary_stats['total_rpjmd'] ?? 0) ?></h4>
              <small>Total RPJMD</small>
            </div>
            <div class="col-6 col-md-3 card-mini text-center">
              <h4 class="text-success mb-0"><?= (int) ($summary_stats['total_renstra'] ?? 0) ?></h4>
              <small>Total RENSTRA</small>
            </div>
            <div class="col-6 col-md-3 card-mini text-center">
              <h4 class="text-warning mb-0"><?= (int) ($summary_stats['total_rkt'] ?? 0) ?></h4>
              <small>Total RKT</small>
            </div>
            <div class="col-6 col-md-3 card-mini text-center">
              <h4 class="text-info mb-0"><?= (int) ($summary_stats['total_opd'] ?? 0) ?></h4>
              <small>Total OPD</small>
            </div>
          </div>
        <?php endif; ?>
      </div>

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
                  <td>Draf</td>
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

        <!-- RKPD -->
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
                    <?= (int) ($dashboard_data['lakip_kabupaten']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="lakipKabSelesai" class="text-end">
                    <?= (int) ($dashboard_data['lakip_kabupaten']['selesai'] ?? 0) ?></td>
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
            <small class="text-muted">Filter ini mempengaruhi grafik RENSTRA, RKT, IKU, dan LAKIP (Kab/OPD sesuai sumber
              data).</small>
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
                  <td>Draf</td>
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
                  <td>Draft</td>
                  <td id="ikuDraft" class="text-end"><?= (int) ($dashboard_data['iku']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="ikuSelesai" class="text-end"><?= (int) ($dashboard_data['iku']['selesai'] ?? 0) ?></td>
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
                  <td id="lakipOpdDraft" class="text-end"><?= (int) ($dashboard_data['lakip_opd']['draft'] ?? 0) ?></td>
                </tr>
                <tr>
                  <td>Selesai</td>
                  <td id="lakipOpdSelesai" class="text-end"><?= (int) ($dashboard_data['lakip_opd']['selesai'] ?? 0) ?>
                  </td>
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

    const chartOptions = { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } };
    function makeDataset(key) { const s = dataPHP[key] || { selesai: 0, draft: 0 }; return [parseInt(s.selesai || 0), parseInt(s.draft || 0)]; }

    const charts = {
      rpjmd: new Chart(document.getElementById('rpjmdChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('rpjmd'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions }),
      rkpd: new Chart(document.getElementById('rkpdChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('rkpd'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions }),
      lakip_kabupaten: new Chart(document.getElementById('lakipKabChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('lakip_kabupaten'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions }),
      renstra: new Chart(document.getElementById('renstraChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('renstra'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions }),
      rkt: new Chart(document.getElementById('rktChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('rkt'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions }),
      iku: new Chart(document.getElementById('ikuChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('iku'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions }),
      lakip_opd: new Chart(document.getElementById('lakipOpdChart'), { type: 'doughnut', data: { labels: ['Selesai', 'Draft'], datasets: [{ data: makeDataset('lakip_opd'), backgroundColor: ['#198754', '#ffc107'], borderWidth: 0 }] }, options: chartOptions })
    };

    function setCounts(prefix, draf, selesai) { const d = document.getElementById(prefix + 'Draft'); const s = document.getElementById(prefix + 'Selesai'); if (d) d.textContent = draf; if (s) s.textContent = selesai; }
    function updateSection(key, data) {
      const selesai = parseInt(data[key]?.selesai || 0), draf = parseInt(data[key]?.draft || 0);
      charts[key].data.datasets[0].data = [selesai, draf]; charts[key].update();
      const map = { rpjmd: 'rpjmd', rkpd: 'rkpd', renstra: 'renstra', rkt: 'rkt', iku: 'iku', lakip_kabupaten: 'lakipKab', lakip_opd: 'lakipOpd' };
      if (map[key]) setCounts(map[key], draf, selesai);
    }
    function notify(msg, t = 'info') { const cls = t === 'success' ? 'alert-success' : (t === 'error' ? 'alert-danger' : 'alert-info'); const n = document.createElement('div'); n.className = `alert ${cls} alert-dismissible fade show position-fixed`; n.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;'; n.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`; document.body.appendChild(n); setTimeout(() => { n.remove() }, 3500); }

    const selOpd = document.getElementById('filterOpd'), selYr = document.getElementById('filterYear'), btnGo = document.getElementById('btnTampilkan'), btnRs = document.getElementById('btnReset');

    btnGo.addEventListener('click', async () => {
      const opdId = selOpd.value, year = selYr.value;
      const token = document.querySelector('meta[name="csrf-token"]')?.content, hash = document.querySelector('meta[name="csrf-hash"]')?.content;
      const parts = []; if (token && hash) parts.push(encodeURIComponent(token) + '=' + encodeURIComponent(hash));
      parts.push('opd_id=' + encodeURIComponent(opdId || '')); parts.push('year=' + encodeURIComponent(year || '')); const body = parts.join('&');

      btnGo.classList.add('btn-loading'); const old = btnGo.textContent; btnGo.textContent = 'Memuat...';
      try {
        const res = await fetch('<?= base_url('adminkab/getDashboardData') ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body });
        const json = await res.json();
        if (json.status === 'success') { const d = json.data || {}; updateSection('rpjmd', d); updateSection('rkpd', d); updateSection('renstra', d); updateSection('rkt', d); updateSection('iku', d); updateSection('lakip_kabupaten', d); updateSection('lakip_opd', d); notify('Data berhasil difilter.', 'success'); }
        else notify(json.message || 'Terjadi kesalahan.', 'error');
      } catch (e) { console.error(e); notify('Gagal memuat data.', 'error'); }
      finally { btnGo.classList.remove('btn-loading'); btnGo.textContent = old; }
    });
    btnRs.addEventListener('click', () => { selOpd.value = ''; selYr.value = ''; });
  </script>
</body>

</html>
<?php $page = 'dashboard'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard e-SAKIP</title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

  <!-- Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Main Content -->
  <main class="flex-fill p-4 mt-2" id="main-content">
    <div class="bg-white rounded shadow p-4 mb-3">
      <h2 class="h3 fw-bold text-dark mb-3">Selamat Datang di e-SAKIP</h2>
      <p class="text-muted">Sistem Akuntabilitas Kinerja Instansi Pemerintah - Admin Kabupaten</p>
      
      <?php if (isset($error_message)): ?>
        <div class="alert alert-warning" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?= esc($error_message) ?>
        </div>
      <?php endif; ?>
      
      <!-- Summary Stats -->
      <?php if (isset($summary_stats)): ?>
      <div class="row g-3 mt-2">
        <div class="col-6 col-md-3">
          <div class="text-center">
            <h4 class="fw-bold text-primary mb-0"><?= $summary_stats['total_rpjmd'] ?></h4>
            <small class="text-muted">Total RPJMD</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-center">
            <h4 class="fw-bold text-success mb-0"><?= $summary_stats['total_renstra'] ?></h4>
            <small class="text-muted">Total RENSTRA</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-center">
            <h4 class="fw-bold text-warning mb-0"><?= $summary_stats['total_renja'] ?></h4>
            <small class="text-muted">Total RENJA</small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-center">
            <h4 class="fw-bold text-info mb-0"><?= $summary_stats['total_opd'] ?></h4>
            <small class="text-muted">Total OPD</small>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="row g-4">
      <!-- RPJMD -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">RPJMD Kabupaten</p>
          <div style="height: 150px;"><canvas id="rpjmdChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['rpjmd']['draft']) ? $dashboard_data['rpjmd']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['rpjmd']['selesai']) ? $dashboard_data['rpjmd']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>


      <!-- RKPD Aktif -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">RKPD</p>
          <div style="height: 150px;"><canvas id="rkpdChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['rkpd']['draft']) ? $dashboard_data['rkpd']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['rkpd']['selesai']) ? $dashboard_data['rkpd']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- LAKIP -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">LAKIP</p>
          <div style="height: 150px;"><canvas id="lakipKabChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['lakip_kabupaten']['draft']) ? $dashboard_data['lakip_kabupaten']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['lakip_kabupaten']['selesai']) ? $dashboard_data['lakip_kabupaten']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white rounded shadow p-4 mb-3">
        <h2 class="h3 fw-bold text-dark mb-3">Data SAKIP Untuk Tiap Unit Kerja</h2>
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex-fill">
                <select class="form-select" id="filterOpd" onchange="filterByUnitKerja()">
                    <option value="">Pilih Unit Kerja</option>
                    <?php if (isset($dashboard_data['opd_list'])): ?>
                        <?php foreach ($dashboard_data['opd_list'] as $opd): ?>
                            <option value="<?= $opd['id'] ?>"><?= esc($opd['nama_opd']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select name="tahun" id="tahun" class="form-select">
                    <option value="">Pilih Tahun</option>
                    <?php if (isset($dashboard_data['available_years'])): ?>
                        <?php foreach ($dashboard_data['available_years'] as $year): ?>
                            <option value="<?= $year ?>"><?= $year ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <button class="btn btn-primary" onclick="filterByUnitKerja()">Tampilkan</button>
            </div>
          </div>
      </div>

      <!-- RENSTRA -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">RENSTRA Kabupaten</p>
          <div style="height: 150px;"><canvas id="renstraChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['renstra']['draft']) ? $dashboard_data['renstra']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['renstra']['selesai']) ? $dashboard_data['renstra']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>


      <!-- RENJA -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">RENJA</p>
          <div style="height: 150px;"><canvas id="renjaChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['renja']['draft']) ? $dashboard_data['renja']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['renja']['selesai']) ? $dashboard_data['renja']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- IKU -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">IKU Tahun Ini</p>
          <div style="height: 150px;"><canvas id="ikuChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['iku']['draft']) ? $dashboard_data['iku']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['iku']['selesai']) ? $dashboard_data['iku']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- LAKIP OPD -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="bg-white rounded shadow p-4 h-100">
          <p class="fw-semibold text-dark mb-2">LAKIP OPD</p>
          <div style="height: 150px;"><canvas id="lakipOpdChart"></canvas></div>
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
                <td class="text-end"><?= isset($dashboard_data['lakip_opd']['draft']) ? $dashboard_data['lakip_opd']['draft'] : 0 ?></td>
              </tr>
              <tr>
                <td>Selesai</td>
                <td class="text-end"><?= isset($dashboard_data['lakip_opd']['selesai']) ? $dashboard_data['lakip_opd']['selesai'] : 0 ?></td>
              </tr>
            </tbody>
          </table>
        </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>
  </div> <!-- End Content Wrapper -->

  <script>
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: { legend: { display: false } }
    };

    // Get data from PHP
    const dashboardData = <?= json_encode($dashboard_data ?? []) ?>;

    // Function to safely get chart data
    function getChartData(dataKey, defaultDraft = 0, defaultSelesai = 0) {
      const data = dashboardData[dataKey] || {};
      return [
        data.selesai || defaultSelesai,
        data.draft || defaultDraft
      ];
    }

    // Initialize charts with dynamic data
    new Chart(document.getElementById('rpjmdChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draf'],
        datasets: [{
          data: getChartData('rpjmd'),
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('rkpdChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: getChartData('rkpd'),
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('lakipKabChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: getChartData('lakip_kabupaten'),
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('renstraChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draf'],
        datasets: [{
          data: getChartData('renstra'),
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('renjaChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: getChartData('renja'),
          backgroundColor: ['#198754', '#ffc107'],
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
          data: getChartData('iku'),
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    new Chart(document.getElementById('lakipOpdChart'), {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: getChartData('lakip_opd'),
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: chartOptions
    });

    // Function to filter data by unit kerja and year
    function filterByUnitKerja() {
      const opdId = document.getElementById('filterOpd').value;
      const year = document.getElementById('tahun').value;
      
      if (!opdId && !year) {
        alert('Silakan pilih Unit Kerja atau Tahun untuk filter');
        return;
      }

      // Show loading state
      const button = event.target;
      const originalText = button.textContent;
      button.textContent = 'Loading...';
      button.disabled = true;

      // Make AJAX request
      fetch('<?= base_url('adminkab/getDashboardData') ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: `opd_id=${opdId}&year=${year}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          updateChartsWithFilteredData(data.data);
          updateTablesWithFilteredData(data.data);
          
          // Show success message
          showNotification('Data berhasil difilter', 'success');
        } else {
          showNotification(data.message || 'Terjadi kesalahan', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat memuat data', 'error');
      })
      .finally(() => {
        // Reset button state
        button.textContent = originalText;
        button.disabled = false;
      });
    }

    // Function to update charts with filtered data
    function updateChartsWithFilteredData(filteredData) {
      // This function can be expanded to update specific charts
      // For now, we'll show a message that filtering is applied
      console.log('Filtered data received:', filteredData);
    }

    // Function to update tables with filtered data
    function updateTablesWithFilteredData(filteredData) {
      // Update table data - can be expanded based on specific requirements
      console.log('Updating tables with filtered data:', filteredData);
    }

    // Function to show notifications
    function showNotification(message, type = 'info') {
      const alertClass = type === 'success' ? 'alert-success' : 
                        type === 'error' ? 'alert-danger' : 'alert-info';
      
      const notification = document.createElement('div');
      notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
      notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      
      document.body.appendChild(notification);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
        }
      }, 5000);
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Dashboard loaded with data:', dashboardData);
    });
  </script>
</body>
</html>
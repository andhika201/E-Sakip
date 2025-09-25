<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

 <!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Main Content -->
<main class="flex-fill p-4 mt-2" id="main-content">
  <div class="bg-white rounded shadow p-4 mb-3 welcome-section">
    <h2 class="h3 fw-bold mb-3">Selamat Datang di AKSARA</h2>
    <p class="mb-0 opacity-75">Sistem Akuntabilitas Kinerja Instansi Pemerintah - Admin OPD</p>
  </div>

  <div class="row g-4">
    <!-- RENSTRA -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="bg-white rounded shadow p-4 h-100">
        <p class="fw-semibold text-dark mb-2">RENSTRA</p>
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
              <td>Draf</td>
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

    <!-- IKU OPD -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="bg-white rounded shadow p-4 h-100">
        <p class="fw-semibold text-dark mb-2">IKU OPD</p>
        <div style="height: 150px;"><canvas id="ikuOpdChart"></canvas></div>
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
              <td class="text-end"><?= isset($dashboard_data['iku_opd']['draft']) ? $dashboard_data['iku_opd']['draft'] : 0 ?></td>
            </tr>
            <tr>
              <td>Selesai</td>
              <td class="text-end"><?= isset($dashboard_data['iku_opd']['selesai']) ? $dashboard_data['iku_opd']['selesai'] : 0 ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  </div>
</main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
  </div> <!-- End Content Wrapper -->

<script>
// Get dashboard data from PHP
const dashboardData = <?= json_encode($dashboard_data ?? []) ?>;

console.log('Dashboard Data:', dashboardData);

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: { 
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: function(context) {
          const total = context.dataset.data.reduce((a, b) => a + b, 0);
          if (total === 0) return context.label + ': 0 dokumen';
          const percentage = Math.round((context.parsed / total) * 100);
          return context.label + ': ' + context.parsed + ' dokumen (' + percentage + '%)';
        }
      }
    }
  }
};

// Function to safely get chart data
function getChartData(dataKey, defaultDraft = 0, defaultSelesai = 0) {
  const data = dashboardData[dataKey] || {};
  return [
    data.selesai || defaultSelesai,
    data.draft || defaultDraft
  ];
}

// Function to create or show empty chart
function createChart(canvasId, dataKey, title) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) {
    console.error('Canvas not found:', canvasId);
    return;
  }
  
  const chartData = getChartData(dataKey);
  const total = chartData.reduce((a, b) => a + b, 0);
  
  if (total > 0) {
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draft'],
        datasets: [{
          data: chartData,
          backgroundColor: ['#28a745', '#ffc107'],
          borderColor: '#ffffff',
          borderWidth: 2
        }]
      },
      options: chartOptions
    });
  } else {
    // Show empty state message
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#f8f9fa';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#6c757d';
    ctx.font = '14px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('Tidak ada data', canvas.width/2, canvas.height/2);
  }
}

// Initialize charts
createChart('renstraChart', 'renstra', 'RENSTRA');
createChart('renjaChart', 'renja', 'RENJA');
createChart('ikuOpdChart', 'iku_opd', 'IKU OPD');

// Filter functions
function filterByUnitKerja() {
  const tahun = document.getElementById('tahun').value;
  
  if (!tahun) {
    alert('Silakan pilih tahun terlebih dahulu');
    return;
  }
  
  // Create form for POST request
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = window.location.pathname;
  
  const yearInput = document.createElement('input');
  yearInput.type = 'hidden';
  yearInput.name = 'year';
  yearInput.value = tahun;
  
  form.appendChild(yearInput);
  document.body.appendChild(form);
  form.submit();
}

function resetFilter() {
  window.location.href = window.location.pathname;
}

</script>
</body>
</html>
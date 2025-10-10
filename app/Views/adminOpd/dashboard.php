<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard e-SAKIP</title>
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
  <div class="bg-white rounded shadow p-4 mb-3">
    <h2 class="h3 fw-bold text-dark mb-3">Selamat Datang di e-SAKIP</h2>
    <p class="text-muted">Sistem Akuntabilitas Kinerja Instansi Pemerintah - Admin Kabupaten</p>
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
              <th class="text-center">Ceklis</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Draf</td>
              <td class="text-end">1</td>
              <td class="text-center">—</td>
            </tr>
            <tr>
              <td>Selesai</td>
              <td class="text-end">1</td>
              <td class="text-center text-success"><i class="fas fa-check-circle"></i></td>
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
              <th class="text-center">Ceklis</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Draf</td>
              <td class="text-end">2</td>
              <td class="text-center">—</td>
            </tr>
            <tr>
              <td>Selesai</td>
              <td class="text-end">1</td>
              <td class="text-center text-success"><i class="fas fa-check-circle"></i></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- IKU -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="bg-white rounded shadow p-4 h-100">
        <p class="fw-semibold text-dark mb-2">IKU Aktif</p>
        <div style="height: 150px;"><canvas id="ikuChart"></canvas></div>
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
              <td>Tercapai</td>
              <td class="text-end">8</td>
              <td class="text-center text-success"><i class="fas fa-check-circle"></i></td>
            </tr>
            <tr>
              <td>Belum</td>
              <td class="text-end">4</td>
              <td class="text-center">—</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PK -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="bg-white rounded shadow p-4 h-100">
        <p class="fw-semibold text-dark mb-2">Perjanjian Kinerja (PK)</p>
        <div style="height: 150px;"><canvas id="pkChart"></canvas></div>
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
              <td class="text-end">5</td>
              <td class="text-center text-success"><i class="fas fa-check-circle"></i></td>
            </tr>
            <tr>
              <td>Belum</td>
              <td class="text-end">2</td>
              <td class="text-center">—</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- LAKIP -->
    <div class="col-12 col-md-6 col-lg-4">
      <div class="bg-white rounded shadow p-4 h-100">
        <p class="fw-semibold text-dark mb-2">LAKIP Tahun Ini</p>
        <div style="height: 150px;"><canvas id="lakipChart"></canvas></div>
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
              <td>Siap</td>
              <td class="text-end">1</td>
              <td class="text-center text-success"><i class="fas fa-check-circle"></i></td>
            </tr>
            <tr>
              <td>Proses</td>
              <td class="text-end">0</td>
              <td class="text-center">—</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
  </div> <!-- End Content Wrapper -->

<script>
  const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: { legend: { display: false } }
};

new Chart(document.getElementById('renstraChart'), {
  type: 'doughnut',
  data: {
    labels: ['Selesai', 'Draf'],
    datasets: [{
      data: [1, 1],
      backgroundColor: ['#198754', '#ffc107'],
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
      data: [1, 2],
      backgroundColor: ['#198754', '#ffc107'],
      borderWidth: 0
    }]
  },
  options: chartOptions
});

new Chart(document.getElementById('ikuChart'), {
  type: 'doughnut',
  data: {
    labels: ['Tercapai', 'Belum'],
    datasets: [{
      data: [8, 4],
      backgroundColor: ['#0dcaf0', '#dc3545'],
      borderWidth: 0
    }]
  },
  options: chartOptions
});

new Chart(document.getElementById('pkChart'), {
  type: 'doughnut',
  data: {
    labels: ['Ditandatangani', 'Belum'],
    datasets: [{
      data: [5, 2],
      backgroundColor: ['#20c997', '#ffc107'],
      borderWidth: 0
    }]
  },
  options: chartOptions
});

new Chart(document.getElementById('lakipChart'), {
  type: 'doughnut',
  data: {
    labels: ['Siap', 'Proses'],
    datasets: [{
      data: [1, 0],
      backgroundColor: ['#6f42c1', '#adb5bd'],
      borderWidth: 0
    }]
  },
  options: chartOptions
});
</script>
</body>
</html>
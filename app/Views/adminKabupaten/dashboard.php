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

  <!-- Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Main Content -->
  <main class="flex-fill p-4 mt-2" id="main-content">
    <div class="bg-white rounded shadow p-4">
      <h2 class="h3 fw-bold text-dark mb-3">Selamat Datang di e-SAKIP</h2>
      <p class="text-muted">Sistem Akuntabilitas Kinerja Instansi Pemerintah - Admin Kabupaten</p>
    </div>

    <div class="row g-4 mt-2">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="bg-white rounded shadow p-4">
          <p class="text-dark fw-semibold mb-2">RPJMD Kabupaten</p>
          <div style="height: 150px;">
            <canvas id="rpjmdChart"></canvas>
          </div>
          <div class="mt-3 d-flex justify-content-between small text-muted">
            <div>Draf: 1</div>
            <div>Selesai: 1</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="bg-white rounded shadow p-4">
          <p class="small text-muted mb-1">IKU Aktif</p>
          <h3 class="h2 fw-bold text-dark mb-0">12</h3>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="bg-white rounded shadow p-4">
          <p class="small text-muted mb-1">LAKIP Tahun Ini</p>
          <h3 class="h2 fw-bold text-dark mb-0">1</h3>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="bg-white rounded shadow p-4">
          <p class="small text-muted mb-1">Jumlah Pengguna</p>
          <h3 class="h2 fw-bold text-dark mb-0">5</h3>
        </div>
      </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <script>
    const rpjmdCtx = document.getElementById('rpjmdChart').getContext('2d');
    new Chart(rpjmdCtx, {
      type: 'doughnut',
      data: {
        labels: ['Selesai', 'Draf'],
        datasets: [{
          data: [1, 1],
          backgroundColor: ['#198754', '#ffc107'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });
  </script>
</body>
</html>

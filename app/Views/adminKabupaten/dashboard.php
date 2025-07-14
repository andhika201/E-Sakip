<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard e-SAKIP</title>
  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

 <?php include 'adminKabupaten/templates/style.php'; ?>
<button class="toggle-btn" onclick="toggleSidebar()">☰</button>
<div class="wrapper d-flex">
  <?php include 'adminKabupaten/templates/sidebar.php'; ?>
  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
  <div class="main-area d-flex flex-column min-vh-100 flex-grow-1" id="main-area">
    <?php include 'adminKabupaten/templates/header.php'; ?>
    <main class="content flex-fill" id="main-content">
      <div class="container-fluid">
        <h1 class="mb-4">Selamat Datang di Dashboard</h1>
        <p class="text-muted">Sistem Akuntabilitas Kinerja Instansi Pemerintah - Admin Kabupaten</p>

        <div class="row g-4 mt-2">
          <div class="col-12 col-md-6 col-lg-3">
            <div class="bg-white rounded shadow p-4">
              <p class="text-dark mb-1">Total RPJMD</p>
              <h3 class="h2 fw-bold text-dark mb-0">2</h3>
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
      </div>
    </main>
    <?php include 'adminKabupaten/templates/footer.php'; ?>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;

    sidebar.classList.toggle('sidebar-hidden');
    overlay.classList.toggle('d-none');
    body.classList.toggle('sidebar-open');
  }
</script>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</body>
</html>

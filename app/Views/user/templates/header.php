<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - e-SAKIP Kabupaten Pringsewu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  
 <?php
    $current_uri = service('uri')->getSegment(1);
    $is_dashboard = ($current_uri === '' || $current_uri === 'dashboard'); // sesuaikan jika dashboard-mu di '/'
  ?>

  <style>
    html, body {
      height: 100%;
      margin: 0;
    }

    <?php if ($is_dashboard): ?>
    body {
      background: url("<?= base_url('assets/images/logo1.jpg') ?>") no-repeat center center fixed;
      background-size: cover;
      display: flex;
      flex-direction: column;
    }
    <?php else: ?>
    body {
      background-color: #f5f5f5; /* default putih/abu */
      display: flex;
      flex-direction: column;
    }
    <?php endif; ?>

    .header-section {
      padding: 10px 0;
      background-color: #00743e;
      color: white;
    }

    .navbar-section {
      background-color: #6eab11;
    }

    .nav-link {
      color: white !important;
      font-weight: 500;
    }

    .nav-link:hover {
      text-decoration: underline;
    }

    .dropdown-menu {
      background-color: #ffffff;
      border: none;
    }

    .dropdown-item {
      color: #00743e;
      font-weight: 500;
    }

    .dropdown-item:hover {
      color: #ffffff !important;
      background-color: #005c31;
    }

    .admin-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
    }

    .admin-profile img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
    }

    .main-content {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 2rem;
      margin-top: auto;
      margin-bottom: auto;
      border-radius: 20px;
      max-width: 960px;
      margin-left: auto;
      margin-right: auto;
    }

    footer {
      background-color: #00743e;
      color: white;
      padding: 1rem 0;
      margin-top: auto;
    }

    .footer-section a {
      color: white;
    }

    .footer-section a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header class="header-section">
    <div class="container-fluid d-flex justify-content-between align-items-center px-4">
      <div class="d-flex align-items-center">
        <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" width="40" height="40" class="rounded me-2" />
        <h6 class="mb-0 fw-bold">e-SAKIP KABUPATEN PRINGSEWU</h6>
      </div>
      <div class="admin-profile">
        <span>User</span>
        <img src="<?= base_url('assets/images/profile.png') ?>" alt="User Profile" />
      </div>
    </div>
  </header>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-section">
    <div class="container-fluid px-4">
      <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" href="<?= base_url('dashboard') ?>">Beranda</a>
          </li>

          <!-- Dropdown Kinerja Pemerintah Kabupaten -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="kabupatenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Kinerja Pemerintah Kabupaten
            </a>
            <ul class="dropdown-menu" aria-labelledby="kabupatenDropdown">
              <li><a class="dropdown-item" href="<?= base_url('rpjmd') ?>">RPJMD</a></li>
              <li><a class="dropdown-item" href="<?= base_url('pk_bupati') ?>">PK-Bupati</a></li>
              <li><a class="dropdown-item" href="<?= base_url('rkt') ?>">RKT</a></li>
              <li><a class="dropdown-item" href="<?= base_url('lakip_kabupaten') ?>">LAKIP</a></li>
            </ul>
          </li>

          <!-- Dropdown Kinerja Perangkat Daerah -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="perangkatDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Kinerja Perangkat Daerah
            </a>
            <ul class="dropdown-menu" aria-labelledby="perangkatDropdown">
              <li><a class="dropdown-item" href="<?= base_url('renja') ?>">RENJA</a></li>
              <li><a class="dropdown-item" href="<?= base_url('renstra') ?>">RENSTRA</a></li>
              <li><a class="dropdown-item" href="<?= base_url('lakip_opd') ?>">LAKIP</a></li>
              <li><a class="dropdown-item" href="<?= base_url('iku_opd') ?>">IKU</a></li>
              <li><a class="dropdown-item" href="<?= base_url('pk_pimpinan') ?>">PK Pimpinan</a></li>
              <li><a class="dropdown-item" href="<?= base_url('pk_administrator') ?>">PK Administrator</a></li>
              <li><a class="dropdown-item" href="<?= base_url('pk_pengawas') ?>">PK Pengawas</a></li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="<?= base_url('tentang_kami') ?>">Tentang Kami</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

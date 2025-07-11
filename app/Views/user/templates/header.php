
<!-- Header -->
<header class="bg-success px-4 py-2 shadow-sm border-bottom border-success-subtle position-relative" style="z-index: 20;">
  <div class="d-flex align-items-center justify-content-between">
    <!-- Left Side -->
    <div class="d-flex align-items-center">
      <img src="<?= base_url('assets/images/sakipLogo-light.png') ?>" alt="sakipLogo" width="auto" height="50" class="rounded me-2" />
    </div>

    <!-- Right Side -->
    <div class="d-flex align-items-center">
      <div class="d-flex align-items-center">
        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
          <i class="fas fa-user text-success"></i>
        </div>
        <div>
          <p class="small fw-medium text-white mb-0">User</p>
          <p class="small text-white-50 mb-0" style="font-size: 0.75rem;">Viewer</p>
        </div>
      </div>
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

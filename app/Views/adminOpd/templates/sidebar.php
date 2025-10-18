<style>
  /* Sidebar yang menggeser konten */
  .sidebar {
    transition: margin-left 0.3s ease;
    margin-left: -240px; /* Hidden by default */
    width: 240px; /* Kurangi lebar sidebar sedikit */
    flex-shrink: 0;
    z-index: 1000;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
  }
  
  .sidebar-show {
    margin-left: 0; /* Show sidebar */
  }
  
  /* Content wrapper adjustment */
  .content-wrapper {
    flex: 1;
    transition: margin-left 0.3s ease;
    margin-left: 0;
    min-height: 100vh;
  }
  
  .content-wrapper.sidebar-open {
    margin-left: 240px; /* Sesuai dengan lebar sidebar */
  }
  
  /* Custom hover effects with secondary green */
  .sidebar-nav-link:hover {
    background-color: rgba(110, 171, 17, 0.1) !important;
    color: #6eab11 !important;
    border-color: transparent !important;
  }
  
  .sidebar-logout-link:hover {
    background-color: #f5c6cb !important;
    color: #721c24 !important;
    border-color: transparent !important;
  }
  
  /* Overlay untuk mobile */
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .sidebar-overlay.show {
    display: block;
    opacity: 1;
  }
  
  /* Desktop behavior */
  @media (min-width: 769px) {
    .sidebar-overlay {
      display: none !important;
    }
  }
  
  /* Responsive behavior untuk mobile */
  @media (max-width: 768px) {
    .sidebar {
      z-index: 1001;
    }
    
    .content-wrapper.sidebar-open {
      margin-left: 0 !important; /* Di mobile, content tidak bergeser */
    }
  }
</style>

<!-- Overlay untuk mobile -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar bg-white position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between" style="box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
  <!-- Atas: Logo + Navigasi -->
  <div>
    <div class="p-3 border-bottom">
      <h2 class="h5 fw-bold text-dark mb-0">Admin OPD</h2>
    </div>
    <nav class="p-3">
      <div class="d-grid gap-2">
        <a href="<?= base_url('adminopd/dashboard') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">Dashboard</a>
        <a href="<?= base_url('adminopd/renstra') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">Renstra</a>
        <a href="<?= base_url('adminopd/renja') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">Renja</a>
        <a href="<?= base_url('adminopd/iku') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">IKU</a>
        <div class="dropdown">
          <button class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="dropdownPkJpt" data-bs-toggle="dropdown" aria-expanded="false">
            <span>PK JPT</span>
          </button>
          <ul class="dropdown-menu w-100" aria-labelledby="dropdownPkJpt">
            <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/jpt') ?>">Input PK JPT</a></li>
            <li><a class="dropdown-item" href="<?= base_url('adminopd/capaian_pk/jpt') ?>">Capaian PK JPT</a></li>
          </ul>
        </div>
        <div class="dropdown">
          <button class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="dropdownPkJpt" data-bs-toggle="dropdown" aria-expanded="false">
            <span>PK Administrator</span>
          </button>
          <ul class="dropdown-menu w-100" aria-labelledby="dropdownPkJpt">
            <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/administrator') ?>">Input PK Administrator</a></li>
            <li><a class="dropdown-item" href="<?= base_url('adminopd/capaian_pk/administrator') ?>">Capaian PK Administrator</a></li>
          </ul>
        </div>
        <div class="dropdown">
          <button class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="dropdownPkJpt" data-bs-toggle="dropdown" aria-expanded="false">
            <span>PK Pengawas</span>
          </button>
          <ul class="dropdown-menu w-100" aria-labelledby="dropdownPkJpt">
            <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/pengawas') ?>">Input PK Pengawas</a></li>
            <li><a class="dropdown-item" href="<?= base_url('adminopd/capaian_pk/pengawas') ?>">Capaian PK Pengawas</a></li>
          </ul>
        </div>
        <div class="dropdown">
          <button class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="dropdownPkJpt" data-bs-toggle="dropdown" aria-expanded="false">
            <span>Pengukuran Kinerja</span>
          </button>
          <ul class="dropdown-menu w-100" aria-labelledby="dropdownPkJpt">
            <li><a class="dropdown-item" href="<?= base_url('adminopd/target') ?>">Target & Rencana Aksi</a></li>
            <li><a class="dropdown-item" href="<?= base_url('adminopd/monev') ?>">MONEV</a></li>
             <li><a class="dropdown-item" href="<?= base_url('adminopd/lakip') ?>">LAKIP OPD</a></li>
          </ul>
        </div>
        <!-- <a href="<?= base_url('adminopd/lakip') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">LAKIP OPD</a> -->
      </div>
    </nav>
  </div>

  <!-- Bawah: Tombol Keluar -->
  <div class="p-3 border-top">
    <a href="<?= base_url('/logout') ?>" class="btn btn-outline-danger text-start px-3 py-2 text-danger border-0 rounded d-flex align-items-center sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i>
      <span>Keluar</span>
    </a>
  </div>
</div>
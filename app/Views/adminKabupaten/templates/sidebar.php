<style>
  .sidebar {
    width: 240px;
    background-color: #1e1e2d;
    color: #fff;
    transition: transform 0.3s ease;
    z-index: 1040;
  }

  .sidebar-hidden {
    transform: translateX(-100%);
  }

  .sidebar a {
    color: #cfd8dc;
    text-decoration: none;
    padding: 12px 20px;
    display: block;
    border-radius: 4px;
    font-weight: 500;
    transition: background 0.3s, color 0.3s;
  }

  .sidebar a:hover {
    background-color: #2d2d44;
    color: #fff;
  }

  .sidebar .active {
    background-color: #00743e;
    color: #fff !important;
  }

  /* Hover effects khusus */
  .sidebar-nav-link:hover {
    background-color: rgba(110, 171, 17, 0.1) !important;
    color: #6eab11 !important;
  }

  .sidebar-logout-link:hover {
    background-color: #f5c6cb !important;
    color: #721c24 !important;
  }

  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      height: 100vh;
      transform: translateX(-100%);
    }

    body.sidebar-open .sidebar {
      transform: translateX(0);
    }
  }
</style>

<!-- Sidebar -->
<div id="sidebar" class="sidebar position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between sidebar-hidden">
  <div class="p-3 border-bottom text-center">
    <h4 class="text-white">e-SAKIP</h4>
  </div>

  <nav class="flex-grow-1 px-3 py-4">
    <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= (isset($page) && $page == 'dashboard' ? 'active' : '') ?>">
      <i class="fas fa-tachometer-alt me-2"></i> Dashboard
    </a>
     <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= (isset($page) && $page == 'rpjmd' ? 'active' : '') ?>">
      <i class="fas fa-book me-2"></i> RPJMD Kabupaten
    </a>
   <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= (isset($page) && $page == 'rkt' ? 'active' : '') ?>">
      <i class="fas fa-tasks me-2"></i> RKT
    </a>
     <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= (isset($page) && $page == 'pk_bupati' ? 'active' : '') ?>">
      <i class="fas fa-file-alt me-2"></i> PK Bupati
    </a>
    <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= (isset($page) && $page == 'lakip_kabupaten' ? 'active' : '') ?>">
      <i class="fas fa-chart-line me-2"></i> LAKIP Kabupaten
    </a>
    <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= (isset($page) && $page == 'tentang_kami' ? 'active' : '') ?>">
      <i class="fas fa-info-circle me-2"></i> Tentang Kami
    </a>
  </nav>

  <div class="p-3 border-top">
    <a href="<?= base_url('logout') ?>" class="sidebar-link text-danger sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i> Keluar
    </a>
  </div>
</div>

<style>
  .sidebar {
    transition: transform 0.3s ease;
  }
  .sidebar-hidden {
    transform: translateX(-100%);
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
</style>

<!-- Sidebar -->
<div id="sidebar" class="sidebar bg-white position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between sidebar-hidden shadow">
  <!-- Logo dan Navigasi -->
  <div>
    <div class="p-3 border-bottom">
      <h2 class="h5 fw-bold text-dark mb-0">Admin Kabupaten</h2>
    </div>
    <nav class="p-3">
      <div class="d-grid gap-2">
        <a href="<?= base_url('adminkab/dashboard') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">Dashboard</a>
        <a href="<?= base_url('adminkab/rpjmd') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">RPJMD Kabupaten</a>
        <a href="<?= base_url('adminkab/rkt') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">RKT</a>
        <a href="<?= base_url('adminkab/pk_bupati') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">PK Bupati</a>
        <a href="<?= base_url('adminkab/lakip_kabupaten') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">LAKIP Kabupaten</a>
        <a href="<?= base_url('adminkab/tentang_kami') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">Tentang Kami</a>
      </div>
    </nav>
  </div>

  <!-- Tombol Logout -->
  <div class="p-3 border-top">
    <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger text-start px-3 py-2 text-danger border-0 rounded d-flex align-items-center sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i>
      <span>Keluar</span>
    </a>
  </div>
</div>

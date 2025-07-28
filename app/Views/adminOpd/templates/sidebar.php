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
<div id="sidebar" class="sidebar bg-white position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between sidebar-hidden" style="width: 256px; z-index: 40; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
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
        <a href="<?= base_url('adminopd/pk_jpt') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">PK JPT</a>
        <a href="<?= base_url('adminopd/pk_admin') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">PK Administrator</a>
        <a href="<?= base_url('adminopd/pk_pengawas') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">PK Pengawas</a>
        <a href="<?= base_url('adminopd/lakip_opd') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link">LAKIP OPD</a>
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
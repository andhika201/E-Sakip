<style>
  /* Sidebar yang menggeser konten */
  .sidebar {
    transition: margin-left 0.3s ease;
    margin-left: -240px;
    width: 240px;
    flex-shrink: 0;
    z-index: 1000;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
  }

  .sidebar-show {
    margin-left: 0;
  }

  .content-wrapper {
    flex: 1;
    transition: margin-left 0.3s ease;
    margin-left: 0;
    min-height: 100vh;
  }

  .content-wrapper.sidebar-open {
    margin-left: 240px;
  }

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

  .sidebar nav {
    overflow-y: auto;
  }

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

  @media (min-width: 769px) {
    .sidebar-overlay { display: none !important; }
  }

  @media (max-width: 768px) {
    .sidebar { z-index: 1001; }
    .content-wrapper.sidebar-open { margin-left: 0 !important; }
  }
</style>

<!-- Overlay untuk mobile -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar bg-white position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between"
  style="box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
  <!-- Atas: Logo + Navigasi -->
  <div class="d-flex flex-column" style="min-height:0; flex:1;">
    <div class="p-3 border-bottom">
      <?php $r = session()->get('role'); ?>
      <h2 class="h5 fw-bold text-dark mb-0">
        <?= $r === 'admin' ? 'Super Admin' : ($r === 'admin_opd' ? 'Admin OPD' : 'Admin Kabupaten') ?>
      </h2>
    </div>
    <nav class="p-3">
      <div class="d-grid gap-2">
        <?= $this->include('templates/admin_menu') ?>
      </div>
    </nav>
  </div>

  <!-- Bawah: Ganti Password + Tombol Keluar -->
  <div class="p-3 border-top">
    <a href="<?= base_url('change-password') ?>"
      class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded d-flex align-items-center mb-2 sidebar-nav-link">
      <i class="fas fa-lock me-2"></i>
      <span>Ganti Password</span>
    </a>
    <a href="<?= base_url('logout') ?>"
      class="btn btn-outline-danger text-start px-3 py-2 text-danger border-0 rounded d-flex align-items-center sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i>
      <span>Keluar</span>
    </a>
  </div>
</div>

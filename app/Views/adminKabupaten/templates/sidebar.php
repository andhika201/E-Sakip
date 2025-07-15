<style>
  .sidebar {
    transition: transform 0.3s ease;
  }
  .sidebar-hidden {
    transform: translateX(-100%);
  }

  /* Hover styling */
  .sidebar-nav-link:hover {
    background-color: rgba(110, 171, 17, 0.1) !important;
    color: #6eab11 !important;
    border-color: transparent !important;
  }

  .sidebar-nav-link.active {
    background-color: #d4edda !important;
    color: #28a745 !important;
    font-weight: 600;
  }

  .sidebar-logout-link:hover {
    background-color: #f8d7da !important;
    color: #721c24 !important;
  }
</style>

<!-- Sidebar -->
<div id="sidebar" class="sidebar bg-white position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between sidebar-hidden" style="width: 256px; z-index: 40; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
  <!-- Bagian Atas: Logo Sistem -->
  <div>
    <div class="p-3 border-bottom text-center">
      <img src="<?= base_url('assets/logo_prisma.png') ?>" alt="Logo PRISMA" class="img-fluid" style="height: 50px;">
      <h2 class="h6 fw-bold text-dark mt-2 mb-0">PRISMA</h2>
      <p class="small text-muted">Pringsewu Smart Accountability</p>
    </div>

    <!-- Navigasi -->
    <nav class="p-3">
      <div class="d-grid gap-2">
        <a href="<?= base_url('adminkab/dashboard') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link <?= (isset($page) && $page == 'dashboard' ? 'active' : '') ?>">Dashboard</a>

        <a href="<?= base_url('adminkab/rpjmd') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link <?= (isset($page) && $page == 'rpjmd' ? 'active' : '') ?>">RPJMD Kabupaten</a>

        <a href="<?= base_url('adminkab/rkt') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link <?= (isset($page) && $page == 'rkt' ? 'active' : '') ?>">RKT</a>

        <a href="<?= base_url('adminkab/pk_bupati') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link <?= (isset($page) && $page == 'pk_bupati' ? 'active' : '') ?>">PK Bupati</a>

        <a href="<?= base_url('adminkab/lakip_kabupaten') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link <?= (isset($page) && $page == 'lakip_kabupaten' ? 'active' : '') ?>">LAKIP Kabupaten</a>

        <a href="<?= base_url('adminkab/tentang_kami') ?>" class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link <?= (isset($page) && $page == 'tentang_kami' ? 'active' : '') ?>">Tentang Kami</a>
      </div>
    </nav>
  </div>

  <!-- Bagian Bawah: Logout -->
  <div class="p-3 border-top">
    <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger text-start px-3 py-2 text-danger border-0 rounded d-flex align-items-center sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i>
      <span>Keluar</span>
    </a>
  </div>
</div>

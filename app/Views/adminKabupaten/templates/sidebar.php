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
<div id="sidebar" class="sidebar position-fixed top-0 start-0 h-100 d-flex flex-column justify-content-between sidebar-hidden">
  <div class="p-3 border-bottom text-center">
    <h4 class="text-white">e-SAKIP</h4>
  </div>
  <nav class="flex-grow-1 px-3 py-4">
    <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
    <a href="<?= base_url('adminkab/rpjmd') ?>" class="sidebar-link"><i class="fas fa-book me-2"></i> RPJMD Kabupaten</a>
    <a href="<?= base_url('adminkab/rkt') ?>" class="sidebar-link"><i class="fas fa-tasks me-2"></i> RKT</a>
    <a href="<?= base_url('adminkab/pk_bupati') ?>" class="sidebar-link"><i class="fas fa-file-alt me-2"></i> PK Bupati</a>
    <a href="<?= base_url('adminkab/lakip_kabupaten') ?>" class="sidebar-link"><i class="fas fa-chart-line me-2"></i> LAKIP Kabupaten</a>
    <a href="<?= base_url('adminkab/tentang_kami') ?>" class="sidebar-link"><i class="fas fa-info-circle me-2"></i> Tentang Kami</a>
  </nav>
  <div class="p-3 border-top">
    <a href="<?= base_url('logout') ?>" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a>
  </div>
</div> 

  <!-- Tombol Logout -->
  <div class="p-3 border-top">
    <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger text-start px-3 py-2 text-danger border-0 rounded d-flex align-items-center sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i>
      <span>Keluar</span>
    </a>
  </div>
</div>

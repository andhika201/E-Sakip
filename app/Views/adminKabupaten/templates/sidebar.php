<div id="sidebar" class="sidebar sidebar-hidden">
  <div class="p-3 border-bottom text-center">
    <h4 class="text-white">e-SAKIP</h4>
  </div>
  <nav class="flex-grow-1 px-3 py-4">
    <a href="<?= base_url('adminkab/dashboard') ?>" class="sidebar-link <?= ($page == 'dashboard' ? 'active' : '') ?>"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
    <a href="<?= base_url('adminkab/rpjmd') ?>" class="sidebar-link <?= ($page == 'rpjmd' ? 'active' : '') ?>"><i class="fas fa-book me-2"></i> RPJMD Kabupaten</a>
    <a href="<?= base_url('adminkab/rkt') ?>" class="sidebar-link"><i class="fas fa-tasks me-2"></i> RKT</a>
    <a href="<?= base_url('adminkab/pk_bupati') ?>" class="sidebar-link"><i class="fas fa-file-alt me-2"></i> PK Bupati</a>
    <a href="<?= base_url('adminkab/lakip_kabupaten') ?>" class="sidebar-link"><i class="fas fa-chart-line me-2"></i> LAKIP Kabupaten</a>
    <a href="<?= base_url('adminkab/tentang_kami') ?>" class="sidebar-link"><i class="fas fa-info-circle me-2"></i> Tentang Kami</a>
  </nav>
  <div class="p-3 border-top">
    <a href="<?= base_url('logout') ?>" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a>
  </div>
</div>

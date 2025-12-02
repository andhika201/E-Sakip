<!-- Navbar/Header -->
<header class="bg-success px-4 py-3 shadow-sm border-bottom border-success-subtle position-relative" style="z-index: 20;">
  <meta name="csrf-token" content="<?= csrf_hash(); ?>">
  <div class="d-flex align-items-center justify-content-between">
    <!-- Left Side -->
    <div class="d-flex align-items-center">
      <button onclick="toggleSidebar()" class="btn btn-link text-white fs-4 p-0 me-3 border-0">
        <i class="fas fa-bars"></i>
      </button>
      <div>
        <h1 class="h4 fw-bold text-white mb-0">Dashboard Admin</h1>
        <p class="small text-white-50 mb-0">Sistem Akuntabilitas Kinerja Instansi Pemerintah</p>
      </div>
    </div>

    <!-- Right Side -->
    <div class="d-flex align-items-center">
      <div class="d-flex align-items-center">
        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
          <i class="fas fa-user text-success"></i>
        </div>
        <div>
          <p class="small fw-medium text-white mb-0">Admin OPD</p>
          <p class="small text-white-50 mb-0" style="font-size: 0.75rem;">Administrator</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Script Toggle Sidebar -->
<script>
  let sidebarOpen = false;

  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const overlay = document.getElementById('sidebar-overlay');
    const isMobile = window.innerWidth <= 768;

    sidebarOpen = !sidebarOpen;

    if (sidebarOpen) {
      sidebar.classList.add('sidebar-show');

      if (!isMobile) {
        // Desktop behavior - geser content saja
        if (mainContent) mainContent.classList.add('sidebar-open');
      } else {
        // Mobile behavior - tampilkan overlay
        if (overlay) overlay.classList.add('show');
      }
    } else {
      sidebar.classList.remove('sidebar-show');

      if (!isMobile) {
        // Desktop behavior
        if (mainContent) mainContent.classList.remove('sidebar-open');
      } else {
        // Mobile behavior
        if (overlay) overlay.classList.remove('show');
      }
    }
  }

  // Handle window resize
  window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const overlay = document.getElementById('sidebar-overlay');
    const isMobile = window.innerWidth <= 768;

    if (sidebarOpen) {
      if (isMobile) {
        // Switch to mobile mode
        if (mainContent) mainContent.classList.remove('sidebar-open');
        if (overlay) overlay.classList.add('show');
      } else {
        // Switch to desktop mode
        if (overlay) overlay.classList.remove('show');
        if (mainContent) mainContent.classList.add('sidebar-open');
      }
    }
  });

  // Close sidebar on mobile when clicking outside
  document.addEventListener('click', function(event) {
    if (window.innerWidth <= 768 && sidebarOpen) {
      const sidebar = document.getElementById('sidebar');
      const toggleButton = event.target.closest('[onclick="toggleSidebar()"]');

      if (!sidebar.contains(event.target) && !toggleButton) {
        toggleSidebar();
      }
    }
  });
</script>
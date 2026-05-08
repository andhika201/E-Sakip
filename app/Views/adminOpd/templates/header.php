<!-- app/views/adminOpd/templates/header.php -->
<!-- Navbar/Header -->
<header class="bg-success px-3 px-md-4 py-2 py-md-3 shadow-sm border-bottom border-success-subtle"
  style="z-index: 1070; position: sticky; top: 0;">
  <meta name="csrf-token" content="<?= csrf_hash(); ?>">
  <meta name="csrf-name" content="<?= csrf_token() ?>">
  <meta name="csrf-hash" content="<?= csrf_hash() ?>">
  <div class="d-flex align-items-center justify-content-between gap-2">

    <!-- Left Side: Toggle + Title -->
    <div class="d-flex align-items-center gap-2 gap-md-3 overflow-hidden">
      <button onclick="toggleSidebar()"
        class="btn btn-link text-white fs-5 p-0 border-0 flex-shrink-0"
        style="line-height:1; min-width:36px;"
        title="Toggle Sidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="overflow-hidden">
        <h1 class="fw-bold text-white mb-0 text-truncate"
            style="font-size: clamp(0.9rem, 2.5vw, 1.25rem); line-height: 1.2;">
          Dashboard Admin
        </h1>
        <p class="text-white-50 mb-0 d-none d-sm-block"
           style="font-size: clamp(0.65rem, 1.5vw, 0.8rem);">
          Sistem Akuntabilitas Kinerja Instansi Pemerintah
        </p>
      </div>
    </div>

    <!-- Right Side: User Info -->
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
      <div class="bg-white rounded-circle d-flex align-items-center justify-content-center"
        style="width: 34px; height: 34px; min-width: 34px;">
        <i class="fas fa-user text-success" style="font-size: 14px;"></i>
      </div>
      <div class="d-none d-md-block text-end">
        <p class="small fw-semibold text-white mb-0" style="line-height: 1.2;">Admin OPD</p>
        <p class="text-white-50 mb-0" style="font-size: 0.7rem; line-height: 1.2;">Administrator</p>
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
  window.addEventListener('resize', function () {
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
  document.addEventListener('click', function (event) {
    if (window.innerWidth <= 768 && sidebarOpen) {
      const sidebar = document.getElementById('sidebar');
      const toggleButton = event.target.closest('[onclick="toggleSidebar()"]');

      if (!sidebar.contains(event.target) && !toggleButton) {
        toggleSidebar();
      }
    }
  });
</script>
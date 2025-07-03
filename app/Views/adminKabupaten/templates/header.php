<!-- Navbar/Header -->
<header class="bg-success px-4 py-3 shadow-sm border-bottom border-success-subtle position-relative" style="z-index: 20;">
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
      <div class="position-relative me-4">
        <input type="text" placeholder="Cari data..." class="form-control ps-5" style="width: 250px;" />
        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
      </div>
      <div class="position-relative me-4">
        <i class="fas fa-bell text-white fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">2</span>
      </div>
      <div class="d-flex align-items-center">
        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
          <i class="fas fa-user text-success"></i>
        </div>
        <div>
          <p class="small fw-medium text-white mb-0">Admin Kabupaten</p>
          <p class="small text-white-50 mb-0" style="font-size: 0.75rem;">Administrator</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Overlay saat sidebar muncul -->
<div id="overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" style="z-index: 30;" onclick="toggleSidebar()"></div>

<!-- Script Toggle Sidebar -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('sidebar-hidden');
        overlay.classList.toggle('d-none');
    }
</script>
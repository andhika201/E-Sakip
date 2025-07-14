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
      <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
        <i class="fas fa-user text-success"></i>
      </div>
      <div>
        <p class="small fw-medium text-white mb-0">Admin Kabupaten</p>
        <p class="small text-white-50 mb-0" style="font-size: 0.75rem;">Administrator</p>
      </div>
    </div>
  </div>
</header>

<!-- Overlay -->
<div id="overlay" class="overlay d-none" onclick="toggleSidebar()"></div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;

    sidebar.classList.toggle('sidebar-hidden');
    overlay.classList.toggle('d-none');
    body.classList.toggle('sidebar-open');
  }

  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) toggleSidebar();
    });
  });
</script>

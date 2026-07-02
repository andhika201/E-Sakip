<?php
/**
 * CHROME ADMIN TERPADU — header + sidebar + toggle dalam satu file.
 * Satu sumber untuk semua role (super admin / admin_kab / admin_opd)
 * agar formatnya konsisten. Dipanggil dari header.php tiap role.
 */
$role   = session()->get('role');
$rLabel = $role === 'admin' ? 'Super Admin'
        : ($role === 'admin_opd' ? 'Admin OPD'
        : ($role === 'admin_kab' ? 'Admin Kabupaten' : 'Pengguna'));
$rName  = session('username') ?: 'Pengguna';
?>
<style>
  /* ===== Sidebar + overlay (terpadu) ===== */
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    height: 100vh;
    margin-left: -240px;
    z-index: 1040;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: margin-left .3s ease;
  }
  .sidebar.sidebar-show { margin-left: 0; }
  .sidebar nav { overflow-y: auto; }

  .content-wrapper { transition: margin-left .3s ease; min-height: 100vh; }
  .content-wrapper.sidebar-open { margin-left: 240px; }

  .sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .5);
    z-index: 1035;
    display: none;
    opacity: 0;
    transition: opacity .3s ease;
  }
  .sidebar-overlay.show { display: block; opacity: 1; }

  @media (min-width: 769px) {
    .sidebar-overlay { display: none !important; }
  }
  @media (max-width: 768px) {
    .sidebar { width: 230px; margin-left: -230px; z-index: 1090; }
    .sidebar-overlay { z-index: 1085; }
    .content-wrapper.sidebar-open { margin-left: 0 !important; }
  }
  @media (max-width: 360px) {
    .sidebar { width: 200px; margin-left: -200px; }
  }
</style>

<!-- ===== HEADER ===== -->
<header id="main-header" class="bg-success px-3 px-md-4 shadow-sm" style="position: sticky; top: 0; z-index: 1030;">
  <meta name="csrf-token" content="<?= csrf_hash(); ?>">
  <meta name="csrf-name" content="<?= csrf_token() ?>">
  <meta name="csrf-hash" content="<?= csrf_hash() ?>">

  <div class="d-flex align-items-center justify-content-between gap-2" style="min-height: 66px;">
    <!-- Kiri: toggle + judul -->
    <div class="d-flex align-items-center gap-2 gap-md-3 overflow-hidden">
      <button type="button" onclick="toggleSidebar()" class="btn sidebar-burger flex-shrink-0" title="Buka/Tutup Sidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="overflow-hidden">
        <h1 class="fw-bold text-white mb-0 text-truncate" style="font-size: clamp(.95rem, 2.5vw, 1.25rem); line-height: 1.2;">
          Dashboard Admin
        </h1>
        <p class="text-white-50 mb-0 d-none d-sm-block text-truncate" style="font-size: clamp(.65rem, 1.5vw, .8rem);">
          Sistem Akuntabilitas Kinerja Instansi Pemerintah
        </p>
      </div>
    </div>

    <!-- Kanan: info user -->
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
      <div class="text-end d-none d-sm-block">
        <p class="small fw-semibold text-white mb-0" style="line-height: 1.2;"><?= esc($rName) ?></p>
        <p class="text-white-50 mb-0" style="font-size: .7rem; line-height: 1.2;"><?= esc($rLabel) ?></p>
      </div>
      <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
        <i class="fas fa-user text-success"></i>
      </div>
    </div>
  </div>
</header>

<!-- ===== OVERLAY (mobile) ===== -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<div id="sidebar" class="sidebar bg-white d-flex flex-column justify-content-between">
  <div class="d-flex flex-column" style="min-height: 0; flex: 1;">
    <div class="p-3 border-bottom">
      <h2 class="h5 fw-bold text-dark mb-0"><?= esc($rLabel) ?></h2>
    </div>
    <nav class="p-3">
      <div class="d-grid gap-2">
        <?= $this->include('templates/admin_menu') ?>
      </div>
    </nav>
  </div>
  <div class="p-3 border-top">
    <a href="<?= base_url('profile') ?>"
      class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded d-flex align-items-center mb-2 sidebar-nav-link">
      <i class="fas fa-user-circle me-2"></i><span>Profil Saya</span>
    </a>
    <a href="<?= base_url('change-password') ?>"
      class="btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded d-flex align-items-center mb-2 sidebar-nav-link">
      <i class="fas fa-lock me-2"></i><span>Ganti Password</span>
    </a>
    <a href="<?= base_url('logout') ?>"
      class="btn btn-outline-danger text-start px-3 py-2 text-danger border-0 rounded d-flex align-items-center sidebar-logout-link">
      <i class="fas fa-sign-out-alt me-2"></i><span>Keluar</span>
    </a>
  </div>
</div>

<!-- ===== TOGGLE SCRIPT (robust, berbasis class) ===== -->
<script>
  (function () {
    function els() {
      return {
        sidebar: document.getElementById('sidebar'),
        overlay: document.getElementById('sidebar-overlay'),
        content: document.querySelector('.content-wrapper') || document.getElementById('main-content')
      };
    }
    function isMobile() { return window.innerWidth <= 768; }
    function openSidebar() {
      var e = els();
      if (!e.sidebar) return;
      e.sidebar.classList.add('sidebar-show');
      if (isMobile()) { if (e.overlay) e.overlay.classList.add('show'); }
      else if (e.content) e.content.classList.add('sidebar-open');
    }
    function closeSidebar() {
      var e = els();
      if (!e.sidebar) return;
      e.sidebar.classList.remove('sidebar-show');
      if (e.overlay) e.overlay.classList.remove('show');
      if (e.content) e.content.classList.remove('sidebar-open');
    }
    function toggleSidebar() {
      var s = document.getElementById('sidebar');
      if (!s) return;
      if (s.classList.contains('sidebar-show')) closeSidebar();
      else openSidebar();
    }
    window.toggleSidebar = toggleSidebar;
    window.closeSidebar = closeSidebar;

    document.addEventListener('click', function (event) {
      if (!isMobile()) return;
      var s = document.getElementById('sidebar');
      if (!s || !s.classList.contains('sidebar-show')) return;
      if (s.contains(event.target)) return;
      if (event.target.closest('.sidebar-burger')) return;
      closeSidebar();
    });

    window.addEventListener('resize', function () {
      var e = els();
      if (!e.sidebar || !e.sidebar.classList.contains('sidebar-show')) return;
      if (isMobile()) {
        if (e.content) e.content.classList.remove('sidebar-open');
        if (e.overlay) e.overlay.classList.add('show');
      } else {
        if (e.overlay) e.overlay.classList.remove('show');
        if (e.content) e.content.classList.add('sidebar-open');
      }
    });
  })();
</script>

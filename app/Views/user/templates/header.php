<!-- Header -->
<header class="site-header">
  <div class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between" style="min-height: 100px;">
      <!-- Left Side -->
      <div class="d-flex align-items-center">
        <span class="hdr-logo">
          <img src="<?= base_url(setting('app_logo', 'assets/images/LogoTentang.png')) ?>" alt="<?= esc(setting('app_name', 'e-SAKIP')) ?>" />
        </span>
      </div>

      <!-- Right Side -->
      <div class="dropdown">
        <button class="hdr-user" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="hdr-avatar"><i class="fas fa-user"></i></span>
          <span class="hdr-user-meta">
            <span class="hdr-user-name">User</span>
            <span class="hdr-user-role">Viewer</span>
          </span>
          <i class="fas fa-chevron-down hdr-caret"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
          <li><a class="dropdown-item" href="<?= base_url('login') ?>"><i class="fas fa-right-to-bracket me-2"></i>Login</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>

<!-- Navbar -->
<?php
  $seg = service('uri')->getSegment(1);
  $isHome = ($seg === '' || $seg === 'dashboard');
  $grpKab = ['rpjmd', 'rkpd', 'cascading_kabupaten', 'pohon_kinerja_kabupaten', 'pk_bupati', 'lakip_kabupaten'];
  $grpPd  = ['renstra', 'cascading_opd', 'pohon_kinerja_opd', 'rkt', 'lakip_opd', 'iku_opd', 'pk_pimpinan', 'pk_administrator', 'pk_pengawas'];
  $isKab = in_array($seg, $grpKab, true);
  $isPd  = in_array($seg, $grpPd, true);
?>
<nav class="navbar navbar-expand-lg navbar-section">
  <div class="container-fluid px-4">
    <button class="navbar-toggler nav-burger" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
      aria-controls="navbarContent" aria-expanded="false" aria-label="Buka menu">
      <i class="fas fa-bars"></i>
    </button>
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= $isHome ? ' active' : '' ?>" <?= $isHome ? 'aria-current="page"' : '' ?>
            href="<?= base_url('dashboard') ?>"><i class="fas fa-house"></i><span>Beranda</span></a>
        </li>

        <!-- Dropdown Kinerja Pemerintah Kabupaten -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= $isKab ? ' active' : '' ?>" href="#" id="kabupatenDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-landmark"></i><span>Kinerja Pemerintah Kabupaten</span>
          </a>
          <ul class="dropdown-menu" aria-labelledby="kabupatenDropdown">
            <li><a class="dropdown-item<?= $seg === 'rpjmd' ? ' active' : '' ?>" href="<?= base_url('rpjmd') ?>">RPJMD</a></li>
            <li><a class="dropdown-item<?= $seg === 'rkpd' ? ' active' : '' ?>" href="<?= base_url('rkpd') ?>">RKPD</a></li>
            <li><a class="dropdown-item<?= $seg === 'cascading_kabupaten' ? ' active' : '' ?>" href="<?= base_url('cascading_kabupaten') ?>">CASCADING KABUPATEN</a></li>
            <li><a class="dropdown-item<?= $seg === 'pohon_kinerja_kabupaten' ? ' active' : '' ?>" href="<?= base_url('pohon_kinerja_kabupaten') ?>">POHON KINERJA KABUPATEN</a></li>
            <li><a class="dropdown-item<?= $seg === 'pk_bupati' ? ' active' : '' ?>" href="<?= base_url('pk_bupati') ?>">PK BUPATI</a></li>
            <li><a class="dropdown-item<?= $seg === 'lakip_kabupaten' ? ' active' : '' ?>" href="<?= base_url('lakip_kabupaten') ?>">LAKIP</a></li>
          </ul>
        </li>

        <!-- Dropdown Kinerja Perangkat Daerah -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= $isPd ? ' active' : '' ?>" href="#" id="perangkatDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-building"></i><span>Kinerja Perangkat Daerah</span>
          </a>
          <ul class="dropdown-menu" aria-labelledby="perangkatDropdown">
            <li><a class="dropdown-item<?= $seg === 'renstra' ? ' active' : '' ?>" href="<?= base_url('renstra') ?>">RENSTRA</a></li>
            <li><a class="dropdown-item<?= $seg === 'cascading_opd' ? ' active' : '' ?>" href="<?= base_url('cascading_opd') ?>">CASCADING PERANGKAT DAERAH</a></li>
            <li><a class="dropdown-item<?= $seg === 'pohon_kinerja_opd' ? ' active' : '' ?>" href="<?= base_url('pohon_kinerja_opd') ?>">POHON KINERJA PERANGKAT DAERAH</a></li>
            <li><a class="dropdown-item<?= $seg === 'rkt' ? ' active' : '' ?>" href="<?= base_url('rkt') ?>">RKT</a></li>
            <li><a class="dropdown-item<?= $seg === 'lakip_opd' ? ' active' : '' ?>" href="<?= base_url('lakip_opd') ?>">LAKIP</a></li>
            <li><a class="dropdown-item<?= $seg === 'iku_opd' ? ' active' : '' ?>" href="<?= base_url('iku_opd') ?>">IKU</a></li>
            <li><a class="dropdown-item<?= $seg === 'pk_pimpinan' ? ' active' : '' ?>" href="<?= base_url('pk_pimpinan') ?>">PK JPT</a></li>
            <li><a class="dropdown-item<?= $seg === 'pk_administrator' ? ' active' : '' ?>" href="<?= base_url('pk_administrator') ?>">PK ADMINISTRATOR</a></li>
            <li><a class="dropdown-item<?= $seg === 'pk_pengawas' ? ' active' : '' ?>" href="<?= base_url('pk_pengawas') ?>">PK PENGAWAS</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= ($seg === 'tentang_kami') ? ' active' : '' ?>"
            <?= ($seg === 'tentang_kami') ? 'aria-current="page"' : '' ?>
            href="<?= base_url('tentang_kami') ?>"><i class="fas fa-circle-info"></i><span>Tentang Kami</span></a>
        </li>
      </ul>
    </div>
  </div>
</nav>
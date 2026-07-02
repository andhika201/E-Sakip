<?php
/**
 * Menu sidebar admin TERPADU — satu sumber untuk semua role.
 * Tiap item di-gate user_can(); tiap user otomatis hanya melihat menunya.
 * Super admin (bypass) melihat semua. Dipakai oleh sidebar adminKabupaten & adminOpd.
 */
helper('rbac');
$role    = session()->get('role');
$dashUrl = in_array($role, ['admin_opd', 'admin_kecamatan'], true) ? base_url('adminopd/dashboard') : base_url('adminkab/dashboard');
$linkCls = 'btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded sidebar-nav-link';
$ddBtn   = 'btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded dropdown-toggle d-flex justify-content-between align-items-center sidebar-nav-link';

$canKab = user_can('rpjmd.view') || user_can('rkpd.view') || user_can('iku_kab.view')
    || user_can('pk_bupati.view') || user_can('program_pk.view')
    || user_can('target_kab.view') || user_can('monev_kab.view') || user_can('lakip_kab.view')
    || user_can('cascading_kab.view');
$canOpd = user_can('renstra.view') || user_can('rkt_opd.view') || user_can('iku_opd.view')
    || user_can('pk_opd.view') || user_can('target_opd.view') || user_can('monev_opd.view')
    || user_can('lakip_opd.view') || user_can('cascading_opd.view');
?>

<style>
  /* Dropdown sidebar: tampil INLINE (mendorong menu di bawahnya turun),
     bukan overlay mengambang yang menutupi menu lain. */
  #sidebar .dropdown-menu {
    position: static !important;
    transform: none !important;
    inset: auto !important;
    float: none;
    width: 100%;
    margin: 2px 0 6px !important;
    padding: 4px;
    border: 1px solid #eef2ee;
    border-left: 3px solid #00743e;
    border-radius: 8px;
    background: #f7faf7;
    box-shadow: none;
  }
  /* Label panjang membungkus (tidak terpotong) + item rapi */
  #sidebar .dropdown-menu .dropdown-item {
    white-space: normal;
    overflow-wrap: anywhere;
    line-height: 1.3;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: .85rem;
  }
  #sidebar .dropdown-menu .dropdown-item + .dropdown-item { margin-top: 2px; }
  #sidebar .dropdown-menu .dropdown-header {
    white-space: normal;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #6b7a70;
  }
  #sidebar .dropdown-menu .dropdown-divider { margin: 4px 0; }
  /* Putar caret saat dropdown terbuka */
  #sidebar .dropdown-toggle::after { transition: transform .2s ease; }
  #sidebar .dropdown-toggle[aria-expanded="true"]::after { transform: rotate(180deg); }
</style>

<?php if (user_can('dashboard.view')): ?>
  <a href="<?= $dashUrl ?>" class="<?= $linkCls ?>"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
<?php endif; ?>

<?php /* ===================== KABUPATEN ===================== */ ?>
<?php if ($canKab): ?>
  <div class="sidebar-section">Kabupaten</div>
<?php endif; ?>
<?php
$canRencanaKab = user_can('rpjmd.view') || user_can('rkpd.view') || user_can('iku_kab.view')
    || user_can('cascading_kab.view') || user_can('pk_bupati.view');
?>
<?php if ($canRencanaKab): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddRencanaKab" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-clipboard-list"></i> Perencanaan Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddRencanaKab">
      <?php if (user_can('rpjmd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/rpjmd') ?>">RPJMD</a></li><?php endif; ?>
      <?php if (user_can('rkpd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/rkpd') ?>">RKPD</a></li><?php endif; ?>
      <?php if (user_can('iku_kab.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/iku') ?>">IKU</a></li><?php endif; ?>
      <?php if (user_can('cascading_kab.view')): ?>
        <li><a class="dropdown-item" href="<?= base_url('adminkab/cascading?view=pohon') ?>">Pohon Kinerja</a></li>
        <li><a class="dropdown-item" href="<?= base_url('adminkab/cascading?view=tabel') ?>">Cascading</a></li>
      <?php endif; ?>
      <?php if (user_can('pk_bupati.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/pk/bupati') ?>">Perjanjian Kinerja Bupati</a></li><?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('target_kab.view') || user_can('monev_kab.view') || user_can('pk_bupati.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddUkurKab" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-chart-line"></i> Pengukuran Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddUkurKab">
      <?php if (user_can('pk_bupati.view')): ?>
        <li><a class="dropdown-item" href="<?= base_url('adminkab/target_renaksi') ?>">Target Rencana Aksi</a></li>
        <li><a class="dropdown-item" href="<?= base_url('adminkab/monev_pk/es3') ?>">Monitoring Capaian Rencana Aksi</a></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('lakip_kab.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddLaporKab" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-file-lines"></i> Pelaporan Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddLaporKab">
      <li><a class="dropdown-item" href="<?= base_url('adminkab/lakip') ?>">LAKIP</a></li>
    </ul>
  </div>
<?php endif; ?>
<?php if ($role === 'admin_kab' || $role === 'admin' || $role === 'admin_inspektorat'): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddEvalKab" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-clipboard-check"></i> Evaluasi Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddEvalKab">
      <li><a class="dropdown-item" href="<?= base_url('adminkab/evaluasi_inspektorat') ?>">Evaluasi Inspektorat</a></li>
    </ul>
  </div>
<?php endif; ?>

<?php /* ===================== PERANGKAT DAERAH (OPD) ===================== */ ?>
<?php if ($canOpd): ?>
  <div class="sidebar-section">Perangkat Daerah</div>
<?php endif; ?>
<?php
$canRencanaOpd = user_can('renstra.view') || user_can('rkt_opd.view') || user_can('iku_opd.view')
    || user_can('cascading_opd.view') || user_can('pk_opd.view');
?>
<?php if ($canRencanaOpd): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddRencanaOpd" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-clipboard-list"></i> Perencanaan Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddRencanaOpd">
      <?php if (user_can('renstra.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/renstra') ?>">Renstra</a></li><?php endif; ?>
      <?php if (user_can('rkt_opd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/rkt') ?>">Renja/RKT</a></li><?php endif; ?>
      <?php if (user_can('iku_opd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/iku') ?>">IKU</a></li><?php endif; ?>
      <?php if (user_can('cascading_opd.view')): ?>
        <li><a class="dropdown-item" href="<?= base_url('adminopd/cascading?view=pohon') ?>">Pohon Kinerja</a></li>
        <li><a class="dropdown-item" href="<?= base_url('adminopd/cascading?view=tabel') ?>">Cascading</a></li>
      <?php endif; ?>
      <?php if (user_can('pk_opd.view')): ?>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Perjanjian Kinerja</h6></li>
        <?php if ($role !== 'admin_kecamatan'): // PK Eselon II/III/IV utk OPD (bukan kecamatan) ?>
          <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/jpt') ?>">PK JPT (Eselon II)</a></li>
          <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/administrator') ?>">PK Administrator (Eselon III)</a></li>
          <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/pengawas') ?>">PK Pengawas (Eselon IV)</a></li>
        <?php endif; ?>
        <?php if ($role === 'admin_kecamatan' || $role === 'admin'): // PK Kecamatan hanya utk admin kecamatan (+ super admin) ?>
          <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/kecamatan') ?>">PK Kecamatan</a></li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('target_opd.view') || user_can('monev_opd.view') || user_can('pk_opd.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddUkurOpd" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-chart-line"></i> Pengukuran Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddUkurOpd">
      <?php if (user_can('pk_opd.view')): ?>
        <li><a class="dropdown-item" href="<?= base_url('adminopd/target_renaksi') ?>">Target dan Rencana Aksi</a></li>
        <li><a class="dropdown-item" href="<?= base_url('adminopd/monev') ?>">Monitoring Capaian Rencana Aksi</a></li>
      <?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('lakip_opd.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddLaporOpd" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-file-lines"></i> Pelaporan Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddLaporOpd">
      <li><a class="dropdown-item" href="<?= base_url('adminopd/lakip') ?>">LAKIP</a></li>
    </ul>
  </div>
<?php endif; ?>
<?php if ($role === 'admin_opd' || $role === 'admin_kecamatan'): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddEvalOpd" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-clipboard-check"></i> Evaluasi Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddEvalOpd">
      <li><a class="dropdown-item" href="<?= base_url('adminopd/evaluasi_inspektorat') ?>">Evaluasi Inspektorat</a></li>
    </ul>
  </div>
<?php endif; ?>

<?php /* ===================== SUPER ADMIN ===================== */ ?>
<?php if ($role === 'admin'): ?>
  <div class="sidebar-section">Super Admin</div>
  <a href="<?= base_url('adminkab/master') ?>" class="<?= $linkCls ?>"><i class="fas fa-database"></i><span>Master Data</span></a>
  <a href="<?= base_url('adminkab/program_pk') ?>" class="<?= $linkCls ?>"><i class="fas fa-list-ol"></i><span>Program &amp; Kegiatan PK</span></a>
  <a href="<?= base_url('adminkab/log-aktivitas') ?>" class="<?= $linkCls ?>"><i class="fas fa-clock-rotate-left"></i><span>Log Aktivitas</span></a>
  <a href="<?= base_url('adminkab/pengaturan') ?>" class="<?= $linkCls ?>"><i class="fas fa-gear"></i><span>Pengaturan Aplikasi</span></a>
<?php endif; ?>

<?php if (user_can('tentang_kami.view')): ?>
  <div class="sidebar-section">Lainnya</div>
  <a href="<?= base_url(($role === 'admin_opd' ? 'adminopd' : 'adminkab') . '/tentang_kami') ?>" class="<?= $linkCls ?>"><i class="fas fa-circle-info"></i><span>Tentang Kami</span></a>
<?php endif; ?>

<script>
  // Tandai menu sidebar yang sedang aktif sesuai URL
  document.addEventListener('DOMContentLoaded', function () {
    var path = location.pathname.replace(/\/+$/, '');
    // Parameter 'view' membedakan menu dgn path sama (mis. Cascading vs Pohon Kinerja).
    var curView = new URLSearchParams(location.search).get('view');
    document.querySelectorAll('#sidebar a.sidebar-nav-link, #sidebar .dropdown-item').forEach(function (a) {
      try {
        var u = new URL(a.href);
        var href = u.pathname.replace(/\/+$/, '');
        if (!href || path !== href) return;
        // Bila link punya ?view=, hanya aktif jika view cocok (default 'tabel').
        var lView = u.searchParams.get('view');
        if (lView !== null && lView !== (curView || 'tabel')) return;
        a.classList.add('active');
        var dd = a.closest('.dropdown');
        if (dd) {
          var btn = dd.querySelector('.dropdown-toggle');
          if (btn) btn.classList.add('active');
        }
      } catch (e) {}
    });
  });
</script>

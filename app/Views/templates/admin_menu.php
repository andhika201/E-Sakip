<?php
/**
 * Menu sidebar admin TERPADU — satu sumber untuk semua role.
 * Tiap item di-gate user_can(); tiap user otomatis hanya melihat menunya.
 * Super admin (bypass) melihat semua. Dipakai oleh sidebar adminKabupaten & adminOpd.
 */
helper('rbac');
$role    = session()->get('role');
$dashUrl = $role === 'admin_opd' ? base_url('adminopd/dashboard') : base_url('adminkab/dashboard');
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
        <li><a class="dropdown-item" href="<?= base_url('adminkab/cascading') ?>#pohon">Pohon Kinerja</a></li>
        <li><a class="dropdown-item" href="<?= base_url('adminkab/cascading') ?>">Cascading</a></li>
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
<?php if ($role === 'admin_kab' || $role === 'admin'): ?>
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
<?php if (user_can('renstra.view')): ?>
  <a href="<?= base_url('adminopd/renstra') ?>" class="<?= $linkCls ?>"><i class="fas fa-diagram-project"></i><span>Renstra</span></a>
<?php endif; ?>
<?php if (user_can('rkt_opd.view')): ?>
  <a href="<?= base_url('adminopd/rkt') ?>" class="<?= $linkCls ?>"><i class="fas fa-list-check"></i><span>Renja/RKT</span></a>
<?php endif; ?>
<?php if (user_can('iku_opd.view')): ?>
  <a href="<?= base_url('adminopd/iku') ?>" class="<?= $linkCls ?>"><i class="fas fa-bullseye"></i><span>IKU OPD</span></a>
<?php endif; ?>
<?php if (user_can('pk_opd.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddPkOpd" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-file-signature"></i> Perjanjian Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddPkOpd">
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/jpt') ?>">PK JPT</a></li>
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/kecamatan') ?>">PK Kecamatan</a></li>
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/administrator') ?>">PK Administrator</a></li>
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/pengawas') ?>">PK Pengawas</a></li>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('target_opd.view') || user_can('monev_opd.view') || user_can('lakip_opd.view') || user_can('pk_opd.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddUkurOpd" data-bs-toggle="dropdown" aria-expanded="false"><span><i class="fas fa-chart-line"></i> Pengukuran Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddUkurOpd">
      <?php if (user_can('pk_opd.view')): ?>
        <li><a class="dropdown-item" href="<?= base_url('adminopd/target_renaksi') ?>">Target & Rencana Aksi</a></li>
        <li><a class="dropdown-item" href="<?= base_url('adminopd/monev') ?>">MONEV</a></li>
      <?php endif; ?>
      <?php if (user_can('lakip_opd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/lakip') ?>">LAKIP OPD</a></li><?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('cascading_opd.view')): ?>
  <a href="<?= base_url('adminopd/cascading') ?>" class="<?= $linkCls ?>"><i class="fas fa-sitemap"></i><span>Pohon Kinerja &amp; Cascading (OPD)</span></a>
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
    document.querySelectorAll('#sidebar a.sidebar-nav-link, #sidebar .dropdown-item').forEach(function (a) {
      try {
        var href = new URL(a.href).pathname.replace(/\/+$/, '');
        if (href && path === href) {
          a.classList.add('active');
          var dd = a.closest('.dropdown');
          if (dd) {
            var btn = dd.querySelector('.dropdown-toggle');
            if (btn) btn.classList.add('active');
          }
        }
      } catch (e) {}
    });
  });
</script>

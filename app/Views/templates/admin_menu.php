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
$ddBtn   = 'btn btn-outline-secondary text-start px-3 py-2 text-dark border-0 rounded dropdown-toggle d-flex justify-content-between align-items-center';

$canKab = user_can('rpjmd.view') || user_can('rkpd.view') || user_can('iku_kab.view')
    || user_can('pk_bupati.view') || user_can('program_pk.view')
    || user_can('target_kab.view') || user_can('monev_kab.view') || user_can('lakip_kab.view')
    || user_can('cascading_kab.view');
$canOpd = user_can('renstra.view') || user_can('rkt_opd.view') || user_can('iku_opd.view')
    || user_can('pk_opd.view') || user_can('target_opd.view') || user_can('monev_opd.view')
    || user_can('lakip_opd.view') || user_can('cascading_opd.view');
?>

<?php if (user_can('dashboard.view')): ?>
  <a href="<?= $dashUrl ?>" class="<?= $linkCls ?>">Dashboard</a>
<?php endif; ?>

<?php /* ===================== KABUPATEN ===================== */ ?>
<?php if ($canKab): ?>
  <div class="text-uppercase text-muted fw-bold px-2 mt-2 mb-1" style="font-size:.72rem;">Kabupaten</div>
<?php endif; ?>
<?php if (user_can('rpjmd.view')): ?>
  <a href="<?= base_url('adminkab/rpjmd') ?>" class="<?= $linkCls ?>">RPJMD Kabupaten</a>
<?php endif; ?>
<?php if (user_can('rkpd.view')): ?>
  <a href="<?= base_url('adminkab/rkpd') ?>" class="<?= $linkCls ?>">RKPD</a>
<?php endif; ?>
<?php if (user_can('iku_kab.view')): ?>
  <a href="<?= base_url('adminkab/iku') ?>" class="<?= $linkCls ?>">IKU Kabupaten</a>
<?php endif; ?>
<?php if (user_can('pk_bupati.view') || user_can('program_pk.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddPkKab" data-bs-toggle="dropdown" aria-expanded="false"><span>Perjanjian Kerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddPkKab">
      <?php if (user_can('pk_bupati.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/pk/bupati') ?>">PK Bupati</a></li><?php endif; ?>
      <?php if (user_can('program_pk.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/program_pk') ?>">Program PK</a></li><?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('target_kab.view') || user_can('monev_kab.view') || user_can('lakip_kab.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddUkurKab" data-bs-toggle="dropdown" aria-expanded="false"><span>Pengukuran Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddUkurKab">
      <?php if (user_can('target_kab.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/target') ?>">Target & Rencana Aksi</a></li><?php endif; ?>
      <?php if (user_can('monev_kab.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/monev') ?>">MONEV</a></li><?php endif; ?>
      <?php if (user_can('lakip_kab.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminkab/lakip') ?>">LAKIP Kabupaten</a></li><?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('cascading_kab.view')): ?>
  <a href="<?= base_url('adminkab/cascading') ?>" class="<?= $linkCls ?>">Pohon Kinerja &amp; Cascading</a>
<?php endif; ?>

<?php /* ===================== PERANGKAT DAERAH (OPD) ===================== */ ?>
<?php if ($canOpd): ?>
  <div class="text-uppercase text-muted fw-bold px-2 mt-2 mb-1" style="font-size:.72rem;">Perangkat Daerah</div>
<?php endif; ?>
<?php if (user_can('renstra.view')): ?>
  <a href="<?= base_url('adminopd/renstra') ?>" class="<?= $linkCls ?>">Renstra</a>
<?php endif; ?>
<?php if (user_can('rkt_opd.view')): ?>
  <a href="<?= base_url('adminopd/rkt') ?>" class="<?= $linkCls ?>">Renja/RKT</a>
<?php endif; ?>
<?php if (user_can('iku_opd.view')): ?>
  <a href="<?= base_url('adminopd/iku') ?>" class="<?= $linkCls ?>">IKU OPD</a>
<?php endif; ?>
<?php if (user_can('pk_opd.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddPkOpd" data-bs-toggle="dropdown" aria-expanded="false"><span>Perjanjian Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddPkOpd">
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/jpt') ?>">PK JPT &amp; Kecamatan</a></li>
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/administrator') ?>">PK Administrator</a></li>
      <li><a class="dropdown-item" href="<?= base_url('adminopd/pk/pengawas') ?>">PK Pengawas</a></li>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('target_opd.view') || user_can('monev_opd.view') || user_can('lakip_opd.view')): ?>
  <div class="dropdown">
    <button class="<?= $ddBtn ?>" type="button" id="ddUkurOpd" data-bs-toggle="dropdown" aria-expanded="false"><span>Pengukuran Kinerja</span></button>
    <ul class="dropdown-menu w-100" aria-labelledby="ddUkurOpd">
      <?php if (user_can('target_opd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/target') ?>">Target & Rencana Aksi</a></li><?php endif; ?>
      <?php if (user_can('monev_opd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/monev') ?>">MONEV</a></li><?php endif; ?>
      <?php if (user_can('lakip_opd.view')): ?><li><a class="dropdown-item" href="<?= base_url('adminopd/lakip') ?>">LAKIP OPD</a></li><?php endif; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (user_can('cascading_opd.view')): ?>
  <a href="<?= base_url('adminopd/cascading') ?>" class="<?= $linkCls ?>">Pohon Kinerja &amp; Cascading (OPD)</a>
<?php endif; ?>

<?php /* ===================== SUPER ADMIN ===================== */ ?>
<?php if ($role === 'admin'): ?>
  <div class="text-uppercase text-muted fw-bold px-2 mt-2 mb-1" style="font-size:.72rem;">Super Admin</div>
  <a href="<?= base_url('adminkab/master') ?>" class="<?= $linkCls ?>">Master Data</a>
  <a href="<?= base_url('adminkab/log-aktivitas') ?>" class="<?= $linkCls ?>">Log Aktivitas</a>
<?php endif; ?>

<?php if (user_can('tentang_kami.view')): ?>
  <div class="text-uppercase text-muted fw-bold px-2 mt-2 mb-1" style="font-size:.72rem;">Lainnya</div>
  <a href="<?= base_url('adminkab/tentang_kami') ?>" class="<?= $linkCls ?>">Tentang Kami</a>
<?php endif; ?>

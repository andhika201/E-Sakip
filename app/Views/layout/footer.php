<?php
$sn_code = $sn ?? 'ESAKIP-2025-001';
$show_full = $full ?? false;
?>
<!-- Footer Global Unification -->
<footer class="<?= $show_full ? 'footer-section' : 'bg-success text-white' ?> py-2 mt-auto" <?= $show_full ? 'style="background-color: #00743e; color: white; padding: 1.5rem 0 1rem !important;"' : '' ?>>
  <div class="container-fluid px-4">
    
    <?php if ($show_full): ?>
    <div class="row align-items-center mb-3">
      <div class="col-md-8 small">
        <div class="fw-bold">Pemerintah Kabupaten Pringsewu</div>
        <div>Komplek Perkantoran Pemerintah</div>
        <div>Daerah Pringsewu, Lampung</div>
      </div>
      <div class="col-md-4 small text-md-end mt-3 mt-md-0">
        <div>Phone: +62-729-7531-567</div>
        <div>Email: diskominfo@pringsewukab.go.id</div>
        <div class="mt-2">
          <a href="#" class="me-2 text-white"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="me-2 text-white"><i class="fab fa-instagram"></i></a>
          <a href="#" class="me-2 text-white"><i class="fab fa-twitter"></i></a>
          <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
    </div>
    <hr style="border-color: rgba(255,255,255,0.2);">
    <?php endif; ?>
    
    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 <?= $show_full ? 'mt-2' : '' ?>">
      <!-- Kiri: Copyright -->
      <p class="small mb-0 <?= $show_full ? 'opacity-75' : 'text-white-75' ?>">
        &copy; <?= date('Y') ?> Pemerintah Kabupaten Pringsewu. All rights reserved.
      </p>
      
      <!-- Kanan: Logo DevTech + Serial -->
      <div class="d-flex align-items-center gap-3">
        <img src="<?= base_url('assets/images/devtech.png') ?>"
             alt="DevTech"
             style="height: 85px; width: auto; object-fit: contain; filter: brightness(0) invert(1); opacity: 0.95;">
        <div class="text-end">
          <div style="font-size: 0.85rem; opacity: 0.85; line-height: 1.3;">Powered by</div>
          <div style="font-size: 0.9rem; opacity: 0.9; line-height: 1.3; letter-spacing: 0.05em; font-weight: 700;">SN: <?= esc($sn_code) ?></div>
        </div>
      </div>
    </div>
    
  </div>
</footer>

<!-- Meta SEO Lengkap -->
<meta name="description" content="e-SAKIP Kabupaten Pringsewu. Sistem Informasi Akuntabilitas Kinerja Instansi Pemerintah Kabupaten Pringsewu. Akses data RPJMD, RKPD, LAKIP, dan Kinerja secara transparan." />
<meta name="keywords" content="e-sakip, pringsewu, kabupaten pringsewu, akuntabilitas, kinerja, rpjmd, rkpd, lakip, diskominfo pringsewu, pemerintahan" />
<meta name="author" content="DevTech - Dinas Komunikasi dan Informatika Kabupaten Pringsewu" />
<meta name="robots" content="index, follow" />

<!-- Open Graph / Social Media -->
<meta property="og:title" content="e-SAKIP Kabupaten Pringsewu" />
<meta property="og:description" content="Sistem Informasi Akuntabilitas Kinerja Instansi Pemerintah Kabupaten Pringsewu." />
<meta property="og:image" content="<?= base_url('assets/images/devtech.png') ?>" />
<meta property="og:type" content="website" />

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

<?php
  $current_uri = service('uri')->getSegment(1);
  $is_dashboard = ($current_uri === '' || $current_uri === 'dashboard');
?>

<style>
    html, body {
      height: 100%;
      margin: 0;
    }

    <?php if ($is_dashboard): ?>
    body {
      background: url("<?= base_url('assets/images/Pringsewu.jpg') ?>") no-repeat center center fixed;
      background-size: cover;
      display: flex;
      flex-direction: column;
    }
    <?php else: ?>
    body {
      background-color: #f5f5f5; /* default putih/abu */
      display: flex;
      flex-direction: column;
    }
    <?php endif; ?>

    .header-section {
      padding: 10px 0;
      background-color: #00743e;
      color: white;
    }

    .navbar-section {
      background-color: #6eab11;
    }

    .nav-link {
      color: white !important;
      font-weight: 500;
    }

    .nav-link:hover {
      text-decoration: underline;
    }

    .dropdown-menu {
      background-color: #ffffff;
      border: none;
    }

    .dropdown-item {
      color: #00743e;
      font-weight: 500;
    }

    .dropdown-item:hover {
      color: #ffffff !important;
      background-color: #005c31;
    }

    .admin-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
    }

    .admin-profile img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
    }

    .main-content {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 2rem;
      margin-top: auto;
      margin-bottom: auto;
      border-radius: 20px;
      max-width: 960px;
      margin-left: auto;
      margin-right: auto;
    }

    footer {
      background-color: #00743e;
      color: white;
      padding: 1rem 0;
      margin-top: auto;
    }

    .footer-section a {
      color: white;
    }

    .footer-section a:hover {
      text-decoration: underline;
    }
</style>
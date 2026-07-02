<!-- Meta SEO (dari Pengaturan Aplikasi) -->
<meta name="description" content="<?= esc(setting('seo_description', 'e-SAKIP Kabupaten Pringsewu — Sistem Akuntabilitas Kinerja Instansi Pemerintah.')) ?>" />
<meta name="keywords" content="<?= esc(setting('seo_keywords', 'e-sakip, pringsewu, akuntabilitas, kinerja')) ?>" />
<meta name="author" content="<?= esc(setting('seo_author', 'DevTech - Diskominfo Kabupaten Pringsewu')) ?>" />
<meta name="robots" content="index, follow" />

<!-- Favicon -->
<link rel="icon" href="<?= base_url(setting('favicon', 'assets/images/sakipLogo.png')) ?>" />
<link rel="apple-touch-icon" href="<?= base_url(setting('favicon', 'assets/images/sakipLogo.png')) ?>" />

<!-- Open Graph / Social Media -->
<meta property="og:title" content="<?= esc(setting('app_name', 'e-SAKIP') . ' — ' . setting('instansi', 'Kabupaten Pringsewu')) ?>" />
<meta property="og:description" content="<?= esc(setting('seo_description', '')) ?>" />
<meta property="og:image" content="<?= base_url(setting('app_logo', 'assets/images/LogoTentang.png')) ?>" />
<meta property="og:type" content="website" />

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
<!-- Inter font (tampilan modern) -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

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

<!-- ============================================================
     E-SAKIP PUBLIC — Design Kit Profesional (global, semua halaman)
     ============================================================ -->
<style>
    :root {
        --brand: #00743e;
        --brand-dark: #005c31;
        --brand-2: #6eab11;
        --ink: #24302a;
        --muted: #6b7a70;
    }

    body {
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--ink);
        -webkit-font-smoothing: antialiased;
    }

    <?php if (!$is_dashboard): ?>
    body { background-color: #eef2ee; }
    <?php endif; ?>

    /* ---- Warna brand konsisten ---- */
    .text-success { color: var(--brand) !important; }
    .bg-success { background-color: var(--brand) !important; }
    .btn-success {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
    }
    .btn-success:hover, .btn-success:focus {
        background-color: var(--brand-dark) !important;
        border-color: var(--brand-dark) !important;
    }
    .btn-outline-success { color: var(--brand) !important; border-color: var(--brand) !important; }
    .btn-outline-success:hover { background: var(--brand) !important; color: #fff !important; }
    .badge.bg-success { background-color: var(--brand) !important; }

    /* ---- Kartu konten halaman ---- */
    main .bg-white {
        border-radius: 18px !important;
        box-shadow: 0 12px 34px rgba(16, 40, 24, .08) !important;
        border: 1px solid #e9efea;
    }

    /* ---- Judul halaman (aksen garis gradien) ---- */
    main .bg-white > h4.text-success.text-center,
    main .bg-white > h2.text-success.text-center,
    main .bg-white > h4.text-center[style*="00743e"] {
        position: relative;
        font-weight: 800 !important;
        letter-spacing: .3px;
        color: #15311f !important;
        padding-bottom: 16px;
        margin-bottom: 26px !important;
    }
    main .bg-white > h4.text-success.text-center::after,
    main .bg-white > h2.text-success.text-center::after,
    main .bg-white > h4.text-center[style*="00743e"]::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 76px;
        height: 4px;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--brand), var(--brand-2));
    }

    /* ---- Form controls ---- */
    main .form-select, main .form-control {
        border-radius: 10px;
        border-color: #d6dfd9;
    }
    main .form-select:focus, main .form-control:focus {
        border-color: var(--brand-2);
        box-shadow: 0 0 0 .2rem rgba(110, 171, 17, .18);
    }

    /* ---- Tabel ---- */
    main .table-responsive {
        border: 1px solid #e3e8e4;
        border-radius: 14px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: 0 6px 20px rgba(16, 40, 24, .05);
    }
    main table.table { margin-bottom: 0; }
    main table.table > :not(caption) > * > * { padding: .62rem .65rem; }
    main .table thead.table-success th {
        background: linear-gradient(180deg, #00844b 0%, var(--brand) 100%) !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, .16) !important;
        font-weight: 600;
        font-size: .72rem;
        letter-spacing: .3px;
        text-transform: uppercase;
        vertical-align: middle;
    }
    main .table tbody td { vertical-align: middle; border-color: #e8ede9; color: #344039; }
    main .table tbody tr:hover td { background: #f1f8f3 !important; }

    /* ---- Alert / empty state ---- */
    main .alert { border: 1px solid transparent; border-radius: 14px; font-weight: 500; }
    main .alert-warning { background: #fff8e8; border-color: #f3e2b8; color: #8a6310; }
    main .alert-info { background: #eaf4f1; border-color: #cfe6dd; color: #1d6b54; }

    /* ---- Header modern ---- */
    .site-header {
        position: relative;
        z-index: 1045; /* di atas navbar sticky (1030) agar dropdown user tidak tertutup */
        background: linear-gradient(120deg, #00803f 0%, #00642f 100%);
        box-shadow: 0 2px 14px rgba(0, 0, 0, .12);
    }
    .site-header::after {
        content: '';
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 3px;
        background: linear-gradient(90deg, #6eab11, #9bd34a, #6eab11);
    }
    .hdr-logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border-radius: 16px;
        padding: 6px 16px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, .18);
    }
    .hdr-logo img { height: 78px; width: auto; object-fit: contain; display: block; }
    .hdr-user {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 999px;
        padding: 6px 14px 6px 6px;
        color: #fff;
        cursor: pointer;
        transition: background .15s ease;
    }
    .hdr-user:hover { background: rgba(255, 255, 255, .2); }
    .hdr-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: #fff;
        display: grid; place-items: center;
        color: #00743e;
        flex: 0 0 auto;
    }
    .hdr-user-meta { display: flex; flex-direction: column; line-height: 1.1; text-align: left; }
    .hdr-user-name { font-weight: 600; font-size: .9rem; }
    .hdr-user-role { font-size: .72rem; opacity: .82; }
    .hdr-caret { font-size: .7rem; opacity: .85; }

    /* ---- Navbar modern (sticky + pill) ---- */
    .navbar-section {
        position: sticky;
        top: 0;
        z-index: 1030;
        background: linear-gradient(90deg, #6eab11 0%, #5f9a0e 100%);
        box-shadow: 0 4px 16px rgba(0, 0, 0, .12);
    }
    .navbar-section .nav-link {
        color: #fff !important;
        font-weight: 600;
        border-radius: 999px;
        padding: .45rem 1rem !important;
        margin: 0 2px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background .15s ease, color .15s ease;
    }
    .navbar-section .nav-link i { font-size: .82rem; opacity: .92; }
    .navbar-section .nav-link:hover {
        text-decoration: none !important;
        background: rgba(255, 255, 255, .18);
    }
    .navbar-section .nav-link.active {
        background: #fff;
        color: #00743e !important;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .14);
    }
    .navbar-section .nav-link.active i { opacity: 1; }

    /* Hamburger publik — hanya tampil di < lg (mobile/tablet) */
    .navbar-section .nav-burger {
        width: 44px;
        height: 44px;
        border: 1px solid rgba(255, 255, 255, .45);
        border-radius: 11px;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: background .15s ease, transform .12s ease;
    }
    .navbar-section .nav-burger:hover { background: rgba(255, 255, 255, .26); }
    .navbar-section .nav-burger:focus { outline: none; box-shadow: 0 0 0 .2rem rgba(255, 255, 255, .28); }
    .navbar-section .nav-burger:active { transform: scale(.94); }
    .navbar-section .nav-burger i { font-size: 1.1rem; }
    @media (max-width: 991.98px) {
        .navbar-section .nav-burger { display: inline-flex; }

        /* Menu mobile rapi: panel + item full-width */
        .navbar-section .navbar-collapse {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 14px;
            padding: 8px;
            margin-top: 12px;
        }
        .navbar-section .navbar-nav { width: 100%; }
        .navbar-section .nav-item { width: 100%; }
        .navbar-section .nav-link {
            width: 100%;
            border-radius: 10px;
            justify-content: flex-start;
            padding: .65rem .9rem !important;
            margin: 1px 0;
        }
        .navbar-section .nav-link.dropdown-toggle::after { margin-left: auto; }
        .navbar-section .dropdown-menu {
            background: rgba(255, 255, 255, .98);
            border: none;
            border-radius: 10px;
            box-shadow: none;
            width: 100%;
            margin: 2px 0 6px;
            padding: 6px;
        }
        .navbar-section .dropdown-item {
            border-radius: 8px;
            padding: .55rem .8rem .55rem 2.4rem;
            font-weight: 500;
            white-space: normal;
        }
    }
    @media (min-width: 992px) {
        .navbar-section .nav-burger { display: none !important; }
    }
    .dropdown-menu { border-radius: 12px; box-shadow: 0 12px 30px rgba(0, 0, 0, .14); padding: .4rem; }
    .dropdown-item { border-radius: 8px; padding: .5rem .8rem; }
    .dropdown-item.active, .dropdown-item:active {
        background: var(--brand) !important;
        color: #fff !important;
    }

    /* ---- Pagination tabel ---- */
    .js-pager {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 16px;
    }
    .js-pager .pg-info { font-size: .82rem; color: var(--muted); }
    .js-pager .pg-nav { display: inline-flex; gap: 4px; align-items: center; flex-wrap: wrap; }
    .js-pager .pg-btn {
        min-width: 34px;
        height: 34px;
        padding: 0 8px;
        border: 1px solid #dbe3dd;
        background: #fff;
        color: #2f3d35;
        border-radius: 9px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .12s ease;
    }
    .js-pager .pg-btn:hover:not(:disabled):not(.active) { border-color: var(--brand-2); color: var(--brand); }
    .js-pager .pg-btn.active { background: var(--brand); border-color: var(--brand); color: #fff; }
    .js-pager .pg-btn:disabled { opacity: .45; cursor: default; }
    .js-pager .pg-dots { padding: 0 4px; color: var(--muted); }
    .js-pager .pg-size-wrap { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; color: var(--muted); }
    .js-pager .pg-size { width: auto; border-radius: 8px; }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
  rel="stylesheet" />
<!-- Inter font -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<!-- Favicon -->
<link rel="icon" href="<?= base_url(setting('favicon', 'assets/images/sakipLogo.png')) ?>" />

<style>
  /* Custom green color scheme */
  :root {
    --bs-success: #00743e;
    --bs-success-rgb: 0, 116, 62;
    --secondary-green: #6eab11;
    --secondary-green-rgb: 110, 171, 17;
  }

  /* Primary green color (#00743e) */
  .bg-success {
    background-color: #00743e !important;
  }

  .text-success {
    color: #00743e !important;
  }

  .btn-success {
    background-color: #00743e !important;
    border-color: #00743e !important;
  }

  .btn-success:hover {
    background-color: #005a30 !important;
    border-color: #004a26 !important;
  }

  .table-success {
    background-color: rgba(0, 116, 62, 0.2) !important;
  }

  .badge.bg-success {
    background-color: #00743e !important;
  }

  .border-success-subtle {
    border-color: rgba(0, 116, 62, 0.3) !important;
  }

  /* Secondary green color (#6eab11) */
  .bg-secondary-green {
    background-color: #6eab11 !important;
  }

  .text-secondary-green {
    color: #6eab11 !important;
  }

  .btn-secondary-green {
    background-color: #6eab11 !important;
    border-color: #6eab11 !important;
    color: white !important;
  }

  .btn-secondary-green:hover {
    background-color: #5a8d0e !important;
    border-color: #4d760c !important;
    color: white !important;
  }

  .table-secondary-green {
    background-color: rgba(110, 171, 17, 0.2) !important;
  }

  .badge.bg-secondary-green {
    background-color: #6eab11 !important;
  }

  /* Custom hover effects for sidebar links */
  .btn-outline-secondary:hover {
    background-color: rgba(110, 171, 17, 0.1) !important;
    color: #6eab11 !important;
    border-color: transparent !important;
  }

  .btn-outline-danger:hover {
    background-color: #f5c6cb !important;
    color: #721c24 !important;
    border-color: transparent !important;
  }

  /* Custom Select2 styles */
  .select2-container--default .select2-selection--single {
    height: 38px;
    line-height: 36px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding-left: 12px;
    padding-right: 20px;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
  }

  .select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
  }

  .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    padding: 8px 12px;
  }

  .select2-results__option--highlighted {
    background-color: #00743e !important;
    color: white !important;
  }

  .select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #00743e !important;
    color: white !important;
  }

  .table-rkt {
    border-collapse: separate;
    border-spacing: 0;
  }

  .table-rkt th,
  .table-rkt td {
    border: 1px solid #e3e6ea !important;
  }

  .table-rkt tbody tr:hover {
    background-color: #f8f9fa;
  }

  .table-rkt thead th {
    border-bottom: 2px solid #cfd4da !important;
  }

  .sub-item {
    margin-left: 20px;
  }

  .kegiatan-item {
    margin-left: 10px;
  }

  .btn-sm {
    margin: 2px;
  }

  /* ===== RESPONSIVE LAYOUT ===== */

  /* Header: sembunyikan subtitle di layar sangat kecil */
  @media (max-width: 480px) {
    header .text-white-50 {
      display: none;
    }
    header h1.h4 {
      font-size: 1rem !important;
    }
    header .px-4 {
      padding-left: 0.75rem !important;
      padding-right: 0.75rem !important;
    }
  }

  /* Main content: kurangi padding di mobile */
  @media (max-width: 768px) {
    main.flex-fill {
      padding: 0.75rem !important;
    }
    .bg-white.rounded.shadow.p-4 {
      padding: 1rem !important;
    }
  }

  /* Tabel: scroll horizontal di mobile */
  @media (max-width: 768px) {
    .table-responsive-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    table {
      min-width: 500px;
    }
  }

  /* Card grid: 1 kolom di hp kecil */
  @media (max-width: 576px) {
    .row.g-4 > [class*="col-"] {
      width: 100% !important;
      max-width: 100% !important;
    }
  }

  /* Footer: center text di mobile */
  @media (max-width: 576px) {
    footer.bg-success .col-md-4.text-end {
      text-align: center !important;
      margin-top: 0.5rem;
    }
  }
</style>

<!-- ============================================================
     E-SAKIP ADMIN — Design Kit Profesional (global)
     ============================================================ -->
<style>
  :root { --brand: #00743e; --brand-2: #0a8f50; --lime: #6eab11; --ink: #24302a; --muted: #6b7a70; }

  body {
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: var(--ink);
    -webkit-font-smoothing: antialiased;
  }

  /* ---- Header ---- */
  header.bg-success {
    background: linear-gradient(120deg, #00803f 0%, #00642f 100%) !important;
    position: relative;
  }
  header.bg-success::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--lime), #9bd34a, var(--lime));
  }

  /* Tombol hamburger (toggle sidebar) */
  .sidebar-burger {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, .14) !important;
    border: 1px solid rgba(255, 255, 255, .28) !important;
    color: #fff !important;
    padding: 0 !important;
    transition: background .15s ease, transform .12s ease;
  }
  .sidebar-burger:hover { background: rgba(255, 255, 255, .26) !important; }
  .sidebar-burger:focus { outline: none; box-shadow: 0 0 0 .2rem rgba(255, 255, 255, .28) !important; }
  .sidebar-burger:active { transform: scale(.94); }
  .sidebar-burger i { font-size: 1.05rem; line-height: 1; }

  /* ---- Sidebar ---- */
  #sidebar { box-shadow: 0 0 40px rgba(0, 0, 0, .10) !important; border-right: 1px solid #eef1ee; }
  .sidebar-section {
    text-transform: uppercase;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .6px;
    color: #9aa6a0;
    padding: 14px 8px 4px;
  }
  #sidebar .sidebar-nav-link {
    display: flex;
    align-items: center;
    gap: 11px;
    font-weight: 500;
    font-size: .88rem;
    color: #3a4a40 !important;
  }
  #sidebar .sidebar-nav-link > span { flex: 1 1 auto; }
  #sidebar .sidebar-nav-link i { width: 18px; text-align: center; font-size: .9rem; opacity: .9; }
  #sidebar .sidebar-nav-link:hover { background: #eef5ea !important; color: var(--brand) !important; }
  #sidebar .sidebar-nav-link.active {
    background: linear-gradient(135deg, var(--brand-2), var(--brand)) !important;
    color: #fff !important;
    box-shadow: 0 6px 14px rgba(0, 116, 62, .22);
  }
  #sidebar .sidebar-nav-link.active i { opacity: 1; }
  #sidebar .dropdown-menu { border: 1px solid #eef1ee; border-radius: 10px; box-shadow: 0 12px 28px rgba(0, 0, 0, .12); padding: .35rem; }
  #sidebar .dropdown-item { border-radius: 7px; font-size: .84rem; color: #3a4a40; padding: .45rem .7rem; }
  #sidebar .dropdown-item:hover { background: #eef5ea; color: var(--brand); }
  #sidebar .dropdown-item.active { background: #e9f3ed; color: var(--brand); font-weight: 600; }
  #sidebar nav::-webkit-scrollbar { width: 6px; }
  #sidebar nav::-webkit-scrollbar-thumb { background: #cdd9d2; border-radius: 6px; }

  /* ---- Kartu konten ---- */
  main .bg-white {
    border-radius: 16px !important;
    box-shadow: 0 10px 30px rgba(16, 40, 24, .07) !important;
    border: 1px solid #ebefec;
  }

  /* ---- Judul halaman ---- */
  main .bg-white > h2.text-success.text-center,
  main .bg-white > h3.text-success.text-center,
  main .bg-white > h4.text-success.text-center {
    position: relative;
    font-weight: 800 !important;
    color: #15311f !important;
    padding-bottom: 14px;
    margin-bottom: 24px !important;
  }
  main .bg-white > h2.text-success.text-center::after,
  main .bg-white > h3.text-success.text-center::after,
  main .bg-white > h4.text-success.text-center::after {
    content: '';
    position: absolute;
    left: 50%; bottom: 0;
    transform: translateX(-50%);
    width: 72px; height: 4px;
    border-radius: 4px;
    background: linear-gradient(90deg, var(--brand), var(--lime));
  }

  /* ---- Form ---- */
  main .form-control, main .form-select { border-radius: 9px; }
  main .form-control:focus, main .form-select:focus {
    border-color: var(--lime);
    box-shadow: 0 0 0 .2rem rgba(110, 171, 17, .18);
  }

  /* ---- Tabel ---- */
  main .table-responsive, main .table-responsive-wrapper {
    border: 1px solid #e3e8e4;
    border-radius: 14px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    box-shadow: 0 6px 20px rgba(16, 40, 24, .05);
  }
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
  main .table tbody td { vertical-align: middle; }
  main .table tbody tr:hover td { background: #f1f8f3; }

  /* ---- Tombol & alert ---- */
  .btn { border-radius: 9px; }
  .btn-success { box-shadow: 0 4px 10px rgba(0, 116, 62, .18); }
  main .alert { border-radius: 12px; }
</style>

<!-- ============================================================
     E-SAKIP — Polish Profesional v2 (penyempurnaan global)
     ============================================================ -->
<style>
  /* Seleksi teks & scrollbar halus (global) */
  ::selection { background: rgba(0, 116, 62, .15); }
  * { scrollbar-width: thin; scrollbar-color: #c6d4cc transparent; }
  *::-webkit-scrollbar { width: 9px; height: 9px; }
  *::-webkit-scrollbar-thumb { background: #cbd7cf; border-radius: 8px; border: 2px solid transparent; background-clip: content-box; }
  *::-webkit-scrollbar-thumb:hover { background: #aabeb2; background-clip: content-box; }
  *::-webkit-scrollbar-track { background: transparent; }

  /* Tombol — konsisten, halus, ada umpan balik tekan */
  .btn { font-weight: 600; border-radius: 10px; transition: transform .08s ease, box-shadow .15s ease, background-color .15s ease, border-color .15s ease; }
  .btn:active { transform: translateY(1px); }
  .btn-sm { border-radius: 8px; }
  .btn-success { box-shadow: 0 4px 12px rgba(0, 116, 62, .20); }
  .btn-success:hover { box-shadow: 0 6px 16px rgba(0, 116, 62, .28); }
  .btn-outline-secondary { border-color: #d8ded9; color: #4a5a50; }

  /* Label & input seragam */
  .form-label { font-weight: 600; font-size: .85rem; color: #384a3f; margin-bottom: .3rem; }
  .form-control, .form-select { border-color: #dce4de; }
  .form-control::placeholder { color: #9fada4; }
  .input-group-text { background: #f2f6f3; border-color: #dce4de; color: #4a5a50; }

  /* Select2 (tema bootstrap-5) — samakan tinggi & fokus dengan form-select */
  .select2-container { width: 100% !important; }
  .select2-container--bootstrap-5 .select2-selection { min-height: 40px; border-color: #dce4de; border-radius: 9px; }
  .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  .select2-container--bootstrap-5.select2-container--open .select2-selection { border-color: var(--lime); box-shadow: 0 0 0 .2rem rgba(110, 171, 17, .16); }
  .select2-container--bootstrap-5 .select2-results__option--highlighted { background-color: var(--brand) !important; color: #fff !important; }
  .select2-container--bootstrap-5 .select2-dropdown { border-radius: 10px; box-shadow: 0 14px 34px rgba(16, 40, 24, .14); }

  /* Badge lebih rapi */
  .badge { font-weight: 600; letter-spacing: .2px; border-radius: 7px; padding: .36em .6em; }

  /* Alert — aksen garis kiri, lebih formal */
  main .alert { border: 0; border-left: 4px solid transparent; border-radius: 12px; box-shadow: 0 4px 14px rgba(16, 40, 24, .05); }
  main .alert-success { border-left-color: var(--brand); background: #eaf5ee; color: #14532d; }
  main .alert-danger  { border-left-color: #dc3545; background: #fdeded; color: #842029; }
  main .alert-warning { border-left-color: #eaa60a; background: #fff8e8; color: #6a5104; }
  main .alert-info    { border-left-color: #0d9488; background: #e9f7f5; color: #0c5a52; }

  /* Tabel — sedikit lebih terbaca */
  main .table > :not(caption) > * > * { padding: .6rem .7rem; }
  main .table tbody td { color: #33433a; }
  main .table thead th { letter-spacing: .4px; }
  /* Baris tabel SERAGAM: nonaktifkan hover & zebra striping (permintaan user) */
  main .table { --bs-table-hover-bg: transparent !important; --bs-table-striped-bg: transparent !important; --bs-table-accent-bg: transparent !important; }
  main table tbody tr:hover > td,
  main table tbody tr:hover > th,
  main table tbody tr:nth-child(even) > td,
  main table tbody tr:nth-child(even) > th { background-color: transparent !important; }

  /* Modal */
  .modal-content { border: 0; border-radius: 16px; box-shadow: 0 24px 60px rgba(16, 40, 24, .20); }
  .modal-header, .modal-footer { border-color: #eef1ee; }

  /* Kartu konten */
  main .bg-white { border-color: #eaefeb; }
</style>
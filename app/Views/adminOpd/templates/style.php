<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
  rel="stylesheet" />

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
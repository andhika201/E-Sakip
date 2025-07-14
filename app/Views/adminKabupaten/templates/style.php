<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
  .sidebar {
    transition: all 0.3s ease;
    width: 256px;
  }

  .sidebar-hidden {
    transform: translateX(-100%);
  }

  .sidebar-nav-link:hover {
    background-color: rgba(110, 171, 17, 0.1) !important;
    color: #6eab11 !important;
    border-color: transparent !important;
  }

  .sidebar-logout-link:hover {
    background-color: #f5c6cb !important;
    color: #721c24 !important;
    border-color: transparent !important;
  }

  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      z-index: 1040;
      height: 100vh;
    }
  }
</style>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
  body.sidebar-open #main-content {
    margin-left: 240px;
    filter: blur(2px);
    transition: margin-left 0.3s ease, filter 0.3s ease;
  }

  body:not(.sidebar-open) #main-content {
    margin-left: 0;
    filter: none;
  }

  .sidebar {
    transition: all 0.3s ease;
    width: 240px;
    background-color: #1e1e2d;
    color: #fff;
  }

  .sidebar a {
    color: #cfd8dc;
    text-decoration: none;
    padding: 12px 20px;
    display: block;
    border-radius: 4px;
    font-weight: 500;
    transition: background 0.3s, color 0.3s;
  }

  .sidebar a:hover {
    background-color: #2d2d44;
    color: #fff;
  }

  .sidebar .active {
    background-color: #00743e;
    color: #fff !important;
  }

  .sidebar-hidden {
    transform: translateX(-100%);
  }

  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      height: 100vh;
      z-index: 1040;
    }
    body.sidebar-open #main-content {
      margin-left: 0;
    }
  }
</style>
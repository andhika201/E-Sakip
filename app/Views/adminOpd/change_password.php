<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ganti Password - e-SAKIP</title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <!-- Navbar/Header -->
    <?= $this->include('adminOpd/templates/header.php'); ?>

    <!-- Konten Utama -->
    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">GANTI PASSWORD</h2>

        <!-- Alert -->
        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="row g-4">

          <!-- Info -->
          <div class="col-12">
            <div class="bg-light p-4 rounded">
              <h3 class="h5 fw-semibold text-success mb-2">
                <i class="fas fa-info-circle me-2"></i>Informasi
              </h3>
              <p class="text-dark mb-0">
                Untuk keamanan akun Anda, gunakan password yang kuat dengan kombinasi huruf besar, huruf kecil, angka, dan minimal 6 karakter.
              </p>
            </div>
          </div>

          <!-- Form -->
          <div class="col-12">
            <div class="bg-primary bg-opacity-10 p-4 rounded">
              <h3 class="h5 fw-semibold text-success mb-3">
                <i class="fas fa-lock me-2"></i>Ubah Password
              </h3>

              <form method="POST" action="<?= base_url('change-password/update') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Password Lama</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-key text-secondary"></i></span>
                    <input type="password" name="current_password" class="form-control" placeholder="Masukkan password lama" required>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Password Baru</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-lock text-secondary"></i></span>
                    <input type="password" name="new_password" class="form-control" placeholder="Masukkan password baru" required minlength="6">
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-lock-open text-secondary"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required minlength="6">
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-success px-4">
                    <i class="fas fa-save me-1"></i> Simpan Password
                  </button>
                  <a href="javascript:history.back()" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                  </a>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>

  </div> <!-- End Content Wrapper -->

</body>
</html>

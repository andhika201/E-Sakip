<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Tambah LAKIP OPD' ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah LAKIP OPD</h2>

      <form id="lakip-form" method="POST" action="<?= base_url('adminopd/lakip/save') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <?php if ($role == 'admin_kab'): ?>
          <input type="hidden" name="rpjmd_id" value="<?= esc($indikator['id']) ?>">
        <?php else: ?>
          <input type="hidden" name="renstra_indikator_sasaran_id" value="<?= esc($indikator['id']) ?>">
        <?php endif; ?>


        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('validation')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
              <?php foreach (session()->getFlashdata('validation') as $error): ?>
                <li><?= $error ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Informasi Indikator -->
        <div class="mb-4">
          <label class="form-label fw-bold">Indikator</label>
          <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
        </div>

        <!-- Input Target & Capaian -->
        <div class="row mb-3">
          <div class="col-md-4 mb-3 mb-md-0">
            <label for="target_lalu" class="form-label">Target Tahun Sebelumnya</label>
            <input name="target_lalu" id="target_lalu" class="form-control"
              placeholder="Masukkan target tahun sebelumnya" required>
          </div>

          <div class="col-md-4 mb-3 mb-md-0">
            <label for="capaian_lalu" class="form-label">Capaian Tahun Sebelumnya</label>
            <input name="capaian_lalu" id="capaian_lalu" class="form-control"
              placeholder="Masukkan capaian tahun sebelumnya" required>
          </div>

        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="capaian_tahun_ini" class="form-label">Capaian Tahun Ini</label>
            <input name="capaian_tahun_ini" id="capaian_tahun_ini" class="form-control"
              placeholder="Masukkan capaian tahun ini" required>
          </div>

          <div class="col-md-4">
            <label for="target_tahun_ini" class="form-label">Target Tahun Ini</label>
            <input type="text" class="form-control" value="<?= esc($targetList['target']) ?>" readonly>
          </div>
        </div>


        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/lakip') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>

</html>
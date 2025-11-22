<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Edit LAKIP OPD') ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
  <!-- Sidebar -->
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit LAKIP OPD</h2>

      <form id="lakip-form" method="POST" action="<?= base_url('adminopd/lakip/update') ?>"
        enctype="multipart/form-data">
        <?= csrf_field() ?>

        <?php if ($role === 'admin_kab'): ?>
          <input type="hidden" name="rpjmd_id" value="<?= esc($indikator['id']) ?>">
        <?php else: ?>
          <input type="hidden" name="renstra_indikator_sasaran_id" value="<?= esc($indikator['id']) ?>">
        <?php endif; ?>

        <input type="hidden" name="lakip_id" value="<?= esc($lakip['id'] ?? '') ?>">

        <!-- Informasi Indikator -->
        <div class="row mb-3">
          <div class="col-md-8 mb-3 mb-md-0">
            <label class="form-label fw-bold">Indikator</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran'] ?? '-') ?>" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Satuan</label>
            <input type="text" class="form-control" value="<?= esc($indikator['satuan'] ?? '-') ?>" readonly>
          </div>
        </div>

        <!-- Input Target & Capaian Tahun Sebelumnya -->
        <div class="row mb-3">
          <div class="col-md-4 mb-3 mb-md-0">
            <label for="target_lalu" class="form-label">Target Tahun Sebelumnya</label>
            <input type="text" name="target_lalu" id="target_lalu" class="form-control"
              value="<?= esc($lakip['target_lalu'] ?? '') ?>" required>
          </div>

          <div class="col-md-4 mb-3 mb-md-0">
            <label for="capaian_lalu" class="form-label">Capaian Tahun Sebelumnya</label>
            <input type="text" name="capaian_lalu" id="capaian_lalu" class="form-control"
              value="<?= esc($lakip['capaian_lalu'] ?? '') ?>" required>
          </div>
        </div>

        <!-- Input Capaian Tahun Ini & Target Tahun Ini -->
        <div class="row mb-3">
          <div class="col-md-4 mb-3 mb-md-0">
            <label for="capaian_tahun_ini" class="form-label">Capaian Tahun Ini</label>
            <input type="text" name="capaian_tahun_ini" id="capaian_tahun_ini" class="form-control"
              value="<?= esc($lakip['capaian_tahun_ini'] ?? '') ?>" required>
          </div>

          <div class="col-md-4 mb-3 mb-md-0">
            <label for="target_tahun_ini" class="form-label">Target Tahun Ini</label>
            <input type="text" id="target_tahun_ini" class="form-control"
              value="<?= esc(($role === 'admin_kab') ? ($targetList['target_tahunan'] ?? '-') : ($targetList['target'] ?? '-')) ?>"
              readonly>
          </div>

          <div class="col-md-4">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
              <?php $status = $lakip['status'] ?? 'draft'; ?>
              <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Draft</option>
              <option value="selesai" <?= ($status === 'selesai') ? 'selected' : '' ?>>Selesai</option>
            </select>
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
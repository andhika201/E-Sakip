<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Tambah LAKIP OPD') ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
  <!-- Sidebar -->
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

  <?php
  // ================= NORMALISASI DATA TARGET =================
  // $target dikirim dari controller
  $tahunTarget = $target['tahun'] ?? ($tahun ?? date('Y'));
  $jenisIndikator = $indikator['jenis_indikator'] ?? '-';

  if ($role === 'admin_kab') {
    // RPJMD
    $nilaiTarget = $target['target_tahunan'] ?? $target['target'] ?? null;
  } else {
    // RENSTRA
    $nilaiTarget = $target['target'] ?? $target['target_tahunan'] ?? null;
  }
  ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">
        Tambah LAKIP OPD
      </h2>

      <form id="lakip-form" method="POST" action="<?= base_url('adminopd/lakip/save') ?>">
        <?= csrf_field() ?>

        <?php if ($role === 'admin_kab'): ?>
          <!-- simpan ID TARGET RPJMD -->
          <input type="hidden" name="rpjmd_target_id" value="<?= esc($target['id'] ?? '') ?>">
        <?php else: ?>
          <!-- simpan ID TARGET RENSTRA -->
          <input type="hidden" name="renstra_target_id" value="<?= esc($target['id'] ?? '') ?>">
        <?php endif; ?>

        <!-- Flash Messages (validation) -->
        <?php if (isset($validation) && $validation->getErrors()): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
              <?php foreach ($validation->getErrors() as $error): ?>
                <li><?= esc($error) ?></li>
              <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Info OPD (admin_opd) -->
        <?php if (!empty($opdInfo['nama_opd'])): ?>
          <div class="mb-3">
            <label class="form-label fw-bold">OPD</label>
            <input type="text" class="form-control" value="<?= esc($opdInfo['nama_opd']) ?>" readonly>
          </div>
        <?php endif; ?>

        <!-- Informasi Indikator -->
        <div class="row mb-3">
          <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label fw-bold">Indikator</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran'] ?? '-') ?>" readonly>
          </div>
          <div class="col-md-3 mb-3 mb-md-0">
            <label class="form-label fw-bold">Satuan</label>
            <input type="text" class="form-control" value="<?= esc($indikator['satuan'] ?? '-') ?>" readonly>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">Jenis Indikator</label>
            <input type="text" class="form-control" value="<?= esc(ucfirst($jenisIndikator)) ?>" readonly>
          </div>
        </div>

        <!-- Tahun & Target Tahun Ini -->
        <div class="row mb-3">
          <div class="col-md-3 mb-3 mb-md-0">
            <label class="form-label fw-bold">Tahun</label>
            <input type="text" class="form-control" value="<?= esc($tahunTarget) ?>" readonly>
          </div>
          <div class="col-md-3 mb-3 mb-md-0">
            <label for="target_tahun_ini" class="form-label fw-bold">Target Tahun Ini</label>
            <input type="text" id="target_tahun_ini" class="form-control"
              value="<?= esc($nilaiTarget !== null ? $nilaiTarget : '-') ?>" readonly>
          </div>
          <div class="col-md-3 mb-3 mb-md-0">
            <label for="capaian_tahun_ini" class="form-label fw-bold">Capaian Tahun Ini</label>
            <input type="text" name="capaian_tahun_ini" id="capaian_tahun_ini" class="form-control"
              placeholder="Masukkan capaian tahun ini" value="<?= old('capaian_tahun_ini') ?>">
          </div>
        </div>

        <!-- Target & Capaian Tahun Sebelumnya -->
        <div class="row mb-3">
          <div class="col-md-4 mb-3 mb-md-0">
            <label for="target_lalu" class="form-label">Target Tahun Sebelumnya</label>
            <input type="text" name="target_lalu" id="target_lalu" class="form-control"
              placeholder="Masukkan target tahun sebelumnya" value="<?= old('target_lalu') ?>">
          </div>

          <div class="col-md-4 mb-3 mb-md-0">
            <label for="capaian_lalu" class="form-label">Capaian Tahun Sebelumnya</label>
            <input type="text" name="capaian_lalu" id="capaian_lalu" class="form-control"
              placeholder="Masukkan capaian tahun sebelumnya" value="<?= old('capaian_lalu') ?>">
          </div>
        </div>

        <!-- Capaian Tahun Ini -->
        <!-- <div class="row mb-3">
          <div class="col-md-4 mb-3 mb-md-0">
            <label for="capaian_tahun_ini" class="form-label">Capaian Tahun Ini</label>
            
          </div>
        </div> -->

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
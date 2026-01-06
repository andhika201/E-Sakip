<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'Edit LAKIP') ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>

  <style>
    .page-card {
      max-width: 1200px;
    }

    .label-req::after {
      content: " *";
      color: #dc3545;
    }

    .badge-mode {
      font-size: .85rem;
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <?php
  $mode = $mode ?? 'kabupaten';
  $tahun = $tahun ?? date('Y');

  $q = [];
  if (!empty($mode))
    $q['mode'] = $mode;
  if (!empty($selectedOpdId))
    $q['opd_id'] = $selectedOpdId;
  if (!empty($tahun))
    $q['tahun'] = $tahun;
  $backUrl = base_url('adminkab/lakip') . (!empty($q) ? ('?' . http_build_query($q)) : '');

  $targetTahunIni = '-';
  if (!empty($target)) {
    if ($mode === 'kabupaten') {
      $targetTahunIni = $target['target_tahunan'] ?? '-';
    } else {
      $targetTahunIni = $target['target'] ?? '-';
    }
  }

  $modeLabel = ($mode === 'kabupaten') ? 'Mode Kabupaten (RPJMD)' : 'Mode OPD (RENSTRA)';
  ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4 w-100 page-card">

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <h2 class="h3 fw-bold mb-0" style="color:#00743e;">Edit LAKIP</h2>
        <span class="badge bg-success-subtle text-success border border-success badge-mode">
          <?= esc($modeLabel) ?>
        </span>
      </div>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>
          <?= esc(session()->getFlashdata('error')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('validation')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>
          <ul class="mb-0">
            <?php foreach (session()->getFlashdata('validation') as $e): ?>
              <li><?= esc($e) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= base_url('adminkab/lakip/update') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- context -->
        <input type="hidden" name="mode" value="<?= esc($mode) ?>">
        <input type="hidden" name="tahun" value="<?= esc($tahun) ?>">
        <?php if (!empty($selectedOpdId)): ?>
          <input type="hidden" name="opd_id" value="<?= esc($selectedOpdId) ?>">
        <?php endif; ?>

        <!-- ID LAKIP -->
        <input type="hidden" name="lakip_id" value="<?= esc($lakip['id'] ?? '') ?>">

        <!-- (optional) target id context -->
        <?php if ($mode === 'kabupaten'): ?>
          <input type="hidden" name="rpjmd_target_id" value="<?= esc($target['id'] ?? '') ?>">
        <?php else: ?>
          <input type="hidden" name="renstra_target_id" value="<?= esc($target['id'] ?? '') ?>">
        <?php endif; ?>

        <!-- Informasi indikator -->
        <div class="row g-3 mb-3">
          <div class="col-md-7">
            <label class="form-label fw-bold">Indikator</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran'] ?? '-') ?>" readonly>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-bold">Satuan</label>
            <input type="text" class="form-control" value="<?= esc($indikator['satuan'] ?? '-') ?>" readonly>
          </div>

          <div class="col-md-2">
            <label class="form-label fw-bold">Tahun</label>
            <input type="text" class="form-control" value="<?= esc($tahun) ?>" readonly>
          </div>
        </div>

        <!-- Target tahun ini -->
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label fw-bold">Target Tahun Ini</label>
            <input type="text" class="form-control" value="<?= esc($targetTahunIni) ?>" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Status</label>
            <?php $st = old('status', $lakip['status'] ?? 'proses'); ?>
            <select name="status" class="form-select">
              <option value="proses" <?= ($st === 'proses') ? 'selected' : '' ?>>Proses</option>
              <option value="siap" <?= ($st === 'siap') ? 'selected' : '' ?>>Siap</option>
            </select>
          </div>
        </div>

        <!-- Input LAKIP -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="target_lalu" class="form-label label-req">Target Tahun Sebelumnya</label>
            <input type="text" name="target_lalu" id="target_lalu" class="form-control"
              value="<?= old('target_lalu', $lakip['target_lalu'] ?? '') ?>" required>
          </div>

          <div class="col-md-6">
            <label for="capaian_lalu" class="form-label label-req">Capaian Tahun Sebelumnya</label>
            <input type="text" name="capaian_lalu" id="capaian_lalu" class="form-control"
              value="<?= old('capaian_lalu', $lakip['capaian_lalu'] ?? '') ?>" required>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="capaian_tahun_ini" class="form-label label-req">Capaian Tahun Ini</label>
            <input type="text" name="capaian_tahun_ini" id="capaian_tahun_ini" class="form-control"
              value="<?= old('capaian_tahun_ini', $lakip['capaian_tahun_ini'] ?? '') ?>" required>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= $backUrl ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan
          </button>
        </div>

      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Edit LAKIP OPD' ?></title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit LAKIP OPD</h2>

      <form id="lakip-form" method="POST" action="<?= base_url('adminopd/lakip_opd/update/' . ($lakip['id'] ?? '')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('validation')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                  <?php foreach (session()->getFlashdata('validation') as $error) : ?>
                    <li><?= $error ?></li>
                  <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

      <section>
        <div id="lakip-container">
          <div class="lakip-item bg-light border rounded p-3 mb-3" data-index="0">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium h5">Laporan Kinerja</label>
            </div>
            <div class="row mb-3">
              <div class="col-md-8">
                <label class="form-label">Judul Laporan</label>
                <input name="judul_laporan" class="form-control border-secondary" placeholder="Masukkan Judul Laporan" value="<?= old('judul_laporan', $lakip['judul'] ?? '') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Tanggal Laporan</label>
                <input name="tanggal_laporan" type="date" class="form-control border-secondary" value="<?= old('tanggal_laporan', $lakip['tanggal_laporan'] ?? '') ?>">
              </div>
            </div>
            <div class="mb-3">
                <label class="form-label">File Laporan</label>
                <?php if (!empty($lakip['file'])): ?>
                    <div class="mb-2">
                        <small class="text-muted">File saat ini: <?= esc($lakip['file']) ?></small>
                        <a href="<?= base_url('adminopd/lakip_opd/download/' . $lakip['id']) ?>" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                <?php endif; ?>
                <input type="file" name="file_laporan" class="form-control border-secondary" accept=".pdf,.doc,.docx,.xls,.xlsx">
                <small class="text-muted">File format: PDF, DOC, DOCX, XLS, XLSX. Kosongkan jika tidak ingin mengubah file.</small>
            </div>
          </div>
        </div>
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminopd/lakip_opd') ?>" class="btn btn-secondary">
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
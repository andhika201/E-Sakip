<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Edit Kegiatan PK' ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit Kegiatan PK</h2>

      <form id="kegiatan-pk-form" method="POST" action="<?= base_url('adminopd/kegiatan_opd/update/' . ($kegiatan['id'] ?? '')) ?>">
        <?= csrf_field() ?>

        <!-- Hidden field for kegiatan ID -->
        <input type="hidden" name="id" value="<?= $kegiatan['id'] ?? '' ?>">

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

        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

      <!-- Kegiatan PK Data -->
      <section>
        <div class="kegiatan-pk-container">
          <div class="kegiatan-pk-item bg-light border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium">Edit Kegiatan PK</label>
            </div>
            <div class="row mb-3">
              <div class="col-md-8">
                <label class="form-label">Kegiatan</label>
                <textarea name="kegiatan" class="form-control border-secondary" rows="3" placeholder="Masukkan Kegiatan Kegiatan" required><?= esc($kegiatan['kegiatan'] ?? '') ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label">Anggaran (Rp)</label>
                <input type="number" name="anggaran" class="form-control anggaran-input border-secondary" placeholder="0" min="0" step="1" value="<?= isset($kegiatan['anggaran']) ? intval($kegiatan['anggaran']) : '' ?>" required>
                <small class="text-muted">Contoh: 1000000 untuk Rp 1.000.000</small>
                <?php if (isset($kegiatan['anggaran']) && $kegiatan['anggaran']): ?>
                  <small class="text-info d-block">Anggaran Saat Ini: <?= 'Rp ' . number_format($kegiatan['anggaran'], 0, ',', '.') ?></small>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminopd/kegiatan_opd') ?>" class="btn btn-secondary">
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

  <script>

    // Alert untuk reload halaman
    function reloadPageAlert() {
      if (confirm('Yakin ingin me-reload halaman? Data yang belum disimpan akan hilang!')) {
        location.reload();
      }
    }
    // Contoh pemakaian: panggil reloadPageAlert() pada event tertentu

    // Alert untuk tombol kembali
    document.querySelector('a.btn-secondary').addEventListener('click', function(e) {
      if (!confirm('Yakin ingin kembali? Data yang belum disimpan akan hilang!')) {
        e.preventDefault();
      }
    });

    // Alert untuk tombol simpan
    document.getElementById('kegiatan-pk-form').addEventListener('submit', function(e) {
      const kegiatanKegiatan = document.querySelector('textarea[name="kegiatan"]').value.trim();
      const anggaran = document.querySelector('input[name="anggaran"]').value;
      
      let isValid = true;
      
      if (!kegiatanKegiatan) {
        alert('Kegiatan Kegiatan tidak boleh kosong');
        isValid = false;
      }
      
      if (!anggaran || parseFloat(anggaran) <= 0) {
        alert('Anggaran harus lebih dari 0');
        isValid = false;
      }
      
      if (!isValid) {
        e.preventDefault();
        return;
      }
      
      if (!confirm('Yakin ingin memperbarui data kegiatan PK?')) {
        e.preventDefault();
      }
    });
  </script>
</body>
</html>
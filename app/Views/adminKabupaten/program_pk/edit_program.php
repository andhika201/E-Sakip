<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Edit Program PK' ?></title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit Program PK</h2>

      <form id="program-pk-form" method="POST" action="<?= base_url('adminkab/program_pk/save') ?>">
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

      <!-- Daftar Program PK -->
      <section>
        <div id="program-pk-container">
          <!-- Program PK 1 -->
          <div class="program-pk-item bg-light border rounded p-3 mb-3" data-index="0">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium">Program PK</label>
            </div>
            <div class="row mb-3">
              <div class="col-md-8">
                <label class="form-label">Program atau Kegiatan</label>
                <textarea name="program_pk[0][program_kegiatan]" class="form-control border-secondary" rows="3" placeholder="Masukkan Program Kegiatan" required></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label">Anggaran (Rp)</label>
                <input type="number" name="program_pk[0][anggaran]" class="form-control anggaran-input border-secondary" placeholder="0" min="0" step="1000" required>
                <small class="text-muted">Contoh: 1000000 untuk Rp 1.000.000</small>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminkab/program_pk') ?>" class="btn btn-secondary">
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
    document.getElementById('program-pk-form').addEventListener('submit', function(e) {
      const items = document.querySelectorAll('.program-pk-item');
      let isValid = true;
      items.forEach((item, index) => {
        const programKegiatan = item.querySelector('textarea').value.trim();
        const anggaran = item.querySelector('input[type="number"]').value;
        if (!programKegiatan) {
          alert(`Program Kegiatan ${index + 1} tidak boleh kosong`);
          isValid = false;
          return;
        }
        if (!anggaran || parseFloat(anggaran) <= 0) {
          alert(`Anggaran Program ${index + 1} harus lebih dari 0`);
          isValid = false;
          return;
        }
      });
      if (!isValid) {
        e.preventDefault();
        return;
      }
      if (!confirm('Yakin ingin menyimpan data program PK?')) {
        e.preventDefault();
      } else {
        alert('Data program PK berhasil disimpan!');
      }
    });
  </script>
</body>
</html>
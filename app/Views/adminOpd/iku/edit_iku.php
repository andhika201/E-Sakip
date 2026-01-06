<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit IKU</title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <style>
    .alert {
      transition: all 0.3s ease;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
  <!-- Sidebar -->
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 900px;">
      <h2 class="h3 fw-bold text-center mb-4 text-success">Edit Indikator Kinerja Utama (IKU)</h2>

      <!-- Alert -->
      <div id="alert-container">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>
      </div>

      <form action="<?= base_url('adminopd/iku/update') ?>" method="post">
        <?= csrf_field() ?>

        <!-- ID IKU untuk update -->
        <input type="hidden" name="iku_id" value="<?= esc($iku_data['id'] ?? '') ?>">

        <div class="row mb-3">
          <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label">Indikator</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran'] ?? '-') ?>" readonly>
          </div>
          <div class="col-md-6">
            <label for="definisi" class="form-label">Definisi Operasional</label>
            <input type="text" name="definisi" id="definisi" class="form-control"
              value="<?= esc($iku_data['definisi'] ?? '') ?>" required>
          </div>
        </div>

        <!-- Program Pendukung -->
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Program Pendukung</label>
            <div id="program-container">
              <?php if (!empty($iku_data['program_pendukung'])): ?>
                <?php foreach ($iku_data['program_pendukung'] as $p): ?>
                  <div class="input-group mb-2">
                    <!-- ID program lama -->
                    <input type="hidden" name="program_id[]" value="<?= esc($p['id']) ?>">
                    <input type="text" name="program_pendukung[]" class="form-control" value="<?= esc($p['program']) ?>"
                      placeholder="Isi program pendukung">
                    <button type="button" class="btn btn-outline-danger remove-program">-</button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Jika belum ada program, tampilkan satu input kosong -->
                <div class="input-group mb-2">
                  <input type="hidden" name="program_id[]" value="">
                  <input type="text" name="program_pendukung[]" class="form-control" placeholder="Isi program pendukung">
                  <button type="button" class="btn btn-outline-success add-program">+</button>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($iku_data['program_pendukung'])): ?>
              <!-- Tombol tambah di bawah jika program sudah ada -->
              <button type="button" class="btn btn-sm btn-outline-success mt-2 add-program">
                + Tambah Program
              </button>
            <?php endif; ?>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/iku') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>

  <script>
    document.addEventListener("click", function (e) {
      // Tambah program
      if (e.target.classList.contains("add-program")) {
        const container = document.getElementById("program-container");
        const div = document.createElement("div");
        div.classList.add("input-group", "mb-2");
        div.innerHTML = `
                <input type="hidden" name="program_id[]" value="">
                <input type="text" name="program_pendukung[]" class="form-control"
                       placeholder="Isi program pendukung">
                <button type="button" class="btn btn-outline-danger remove-program">-</button>
            `;
        container.appendChild(div);
      }

      // Hapus program (hapus baris input)
      if (e.target.classList.contains("remove-program")) {
        e.target.closest(".input-group").remove();
      }
    });
  </script>

</body>

</html>
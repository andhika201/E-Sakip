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
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 900px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit IKU</h2>

      <!-- Alert Container -->
      <div id="alert-container">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
      </div>

      <form action="<?= base_url('adminopd/iku/update/' . $indikator['id']) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="renja_indikator_sasaran_id" value="<?= esc($indikator['id']) ?>">
        <input type="hidden" name="iku_id" value="<?= esc($iku_data['id']) ?>">
        <div class="row mb-3">
          <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label">Indikator</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
          </div>
          <div class="col-md-6">
            <label for="definisi" class="form-label">Definisi Operasional</label>
            <input type="text" name="definisi" id="definisi" class="form-control"
              value="<?= esc($iku_data['definisi']) ?>" required>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-12">
            <label for="program_pendukung" class="form-label">Program Pendukung</label>
            <div id="program-container">
              <?php if (!empty($iku_data['program_pendukung'])): ?>
                <?php foreach ($iku_data['program_pendukung'] as $p): ?>
                  <div class="input-group mb-2">
                    <input type="text" name="program_pendukung[]" value="<?= esc($p) ?>" class="form-control"
                      placeholder="Masukkan Program">
                    <button type="button" class="btn btn-danger btn-remove">Hapus</button>
                                      <button type="button" class="btn btn-outline-success add-program">+</button>

                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="input-group mb-2">
                  <input type="text" name="program_pendukung[]" class="form-control" placeholder="Isi program pendukung">
                </div>
              <?php endif; ?>
            </div>

          </div>
        </div>

        <script>
          document.addEventListener("click", function (e) {
            if (e.target.classList.contains("add-program")) {
              const container = document.getElementById("program-container");
              const div = document.createElement("div");
              div.classList.add("input-group", "mb-2");
              div.innerHTML = `
            <input type="text" name="program_pendukung[]" class="form-control" placeholder="Isi program pendukung">
            <button type="button" class="btn btn-outline-danger remove-program">-</button>
        `;
              container.appendChild(div);
            }

            if (e.target.classList.contains("remove-program")) {
              e.target.closest(".input-group").remove();
            }
          });
        </script>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/iku') ?>" class="btn btn-secondary">
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
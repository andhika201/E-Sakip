<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah IKU - Admin Kabupaten</title>

  <?= $this->include('adminKabupaten/templates/style.php'); ?>

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

  <!-- Header & Sidebar -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 900px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">
        Tambah Indikator Kinerja Utama (IKU)
        <?php if (($mode ?? 'opd') === 'kabupaten'): ?>
          <small class="d-block fs-6 text-muted">Mode: Kabupaten (RPJMD)</small>
        <?php else: ?>
          <small class="d-block fs-6 text-muted">Mode: OPD (RENSTRA)</small>
        <?php endif; ?>
      </h2>

      <!-- ALERT -->
      <div id="alert-container">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
          </div>
        <?php endif; ?>
      </div>

      <form action="<?= base_url('adminkab/iku/save') ?>" method="post">
        <?= csrf_field() ?>

        <!-- kirim mode ke controller (opd / kabupaten) -->
        <input type="hidden" name="mode" value="<?= esc($mode ?? 'opd') ?>">

        <?php if (($mode ?? 'opd') === 'kabupaten'): ?>
          <!-- indikator RPJMD -->
          <input type="hidden" name="rpjmd_id" value="<?= esc($indikator['id']) ?>">
        <?php else: ?>
          <!-- indikator RENSTRA -->
          <input type="hidden" name="renstra_id" value="<?= esc($indikator['id']) ?>">
        <?php endif; ?>

        <!-- Indikator & Definisi -->
        <div class="row mb-3">
          <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label fw-semibold">Indikator Sasaran</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran'] ?? '-') ?>" readonly>
          </div>

          <div class="col-md-6">
            <label for="definisi" class="form-label fw-semibold">Definisi Operasional</label>
            <input type="text" name="definisi" id="definisi" class="form-control"
              placeholder="Tulis definisi operasional IKU" required>
          </div>
        </div>

        <!-- Program Pendukung -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Program Pendukung</label>
          <div id="program-container">
            <div class="input-group mb-2">
              <input type="text" name="program_pendukung[]" class="form-control" placeholder="Isi program pendukung">
              <button type="button" class="btn btn-outline-success add-program">+</button>
            </div>
          </div>
          <small class="text-muted">
            Tambahkan satu atau beberapa program yang mendukung pencapaian IKU ini.
          </small>
        </div>

        <!-- Tombol -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminkab/iku?mode=' . ($mode ?? 'opd')) ?>" class="btn btn-secondary">
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
    // Tambah / hapus input program pendukung
    document.addEventListener("click", function (e) {
      if (e.target.classList.contains("add-program")) {
        const container = document.getElementById("program-container");
        const div = document.createElement("div");
        div.classList.add("input-group", "mb-2");
        div.innerHTML = `
                <input type="text" name="program_pendukung[]" class="form-control"
                       placeholder="Isi program pendukung">
                <button type="button" class="btn btn-outline-danger remove-program">-</button>
            `;
        container.appendChild(div);
      }

      if (e.target.classList.contains("remove-program")) {
        e.target.closest(".input-group").remove();
      }
    });
  </script>

</body>

</html>
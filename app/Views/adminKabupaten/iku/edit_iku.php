<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit IKU</title>

  <?= $this->include('adminKabupaten/templates/style.php'); ?>

  <style>
    .alert {
      transition: 0.3s ease;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">

    <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">

      <h2 class="h3 fw-bold text-center mb-4 text-success">
        ✏️ Edit Indikator Kinerja Utama (IKU)
      </h2>

      <!-- Flash message -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>


      <!-- ======================= FORM EDIT IKU ======================= -->
      <form action="<?= base_url('adminkab/iku/update') ?>" method="post">
        <?= csrf_field() ?>

        <!-- Mode -->
        <input type="hidden" name="mode" value="<?= esc($mode) ?>">

        <!-- ID IKU -->
        <input type="hidden" name="iku_id" value="<?= esc($iku_data['id']) ?>">

        <!-- Indikator ID sesuai mode -->
        <?php if ($mode === 'kabupaten'): ?>
          <input type="hidden" name="rpjmd_id" value="<?= esc($indikator['id']) ?>">
        <?php else: ?>
          <input type="hidden" name="renstra_id" value="<?= esc($indikator['id']) ?>">
        <?php endif; ?>


        <!-- Indikator & Definisi -->
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Indikator</label>
            <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Definisi Operasional</label>
            <input type="text" name="definisi" class="form-control" value="<?= esc($iku_data['definisi']) ?>" required>
          </div>
        </div>


        <!-- ======================= PROGRAM PENDUKUNG ======================= -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Program Pendukung</label>

          <div id="program-container">

            <?php if (!empty($iku_data['program_pendukung'])): ?>
              <?php foreach ($iku_data['program_pendukung'] as $p): ?>
                <div class="input-group mb-2">
                  <input type="hidden" name="program_id[]" value="<?= esc($p['id']) ?>">
                  <input type="text" name="program_pendukung[]" value="<?= esc($p['program']) ?>" class="form-control"
                    placeholder="Isi program pendukung">

                  <button type="button" class="btn btn-outline-danger remove-program">
                    Hapus
                  </button>
                </div>
              <?php endforeach; ?>

            <?php else: ?>
              <!-- Jika tidak punya program pendukung -->
              <div class="input-group mb-2">
                <input type="hidden" name="program_id[]" value="">
                <input type="text" name="program_pendukung[]" class="form-control" placeholder="Isi program pendukung">

                <button type="button" class="btn btn-outline-danger remove-program">
                  Hapus
                </button>
              </div>
            <?php endif; ?>

          </div>

          <button type="button" id="add-program" class="btn btn-outline-success btn-sm mt-2">
            <i class="fas fa-plus me-1"></i> Tambah Program
          </button>
        </div>


        <!-- ======================= BUTTONS ======================= -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminkab/iku?mode=' . $mode) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>

          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>

      </form>
    </div>
  </main>


  <?= $this->include('adminKabupaten/templates/footer.php'); ?>


  <!-- ======================= JS: Tambah / Hapus Program ======================= -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {

      const container = document.getElementById("program-container");
      const addBtn = document.getElementById("add-program");

      addBtn.addEventListener("click", () => {
        const div = document.createElement("div");
        div.classList.add("input-group", "mb-2");

        div.innerHTML = `
            <input type="hidden" name="program_id[]" value="">
            <input type="text" name="program_pendukung[]" class="form-control"
                   placeholder="Isi program pendukung">
            <button type="button" class="btn btn-outline-danger remove-program">Hapus</button>
        `;

        container.appendChild(div);
      });

      document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-program")) {
          e.target.closest(".input-group").remove();
        }
      });
    });
  </script>


</body>

</html>
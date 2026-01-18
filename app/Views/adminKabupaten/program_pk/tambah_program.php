<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'Tambah Program PK' ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/program-pk-form.css') ?>">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="container-fluid p-4">
    <div class="card shadow-sm mx-auto" style="max-width:1200px;">
      <div class="card-body">

        <h3 class="text-center fw-bold text-success mb-4">Tambah Program PK</h3>

        <form method="post" action="<?= base_url('adminkab/program_pk/save') ?>">
          <?= csrf_field() ?>

          <!-- Tahun -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Tahun Anggaran</label>
            <select name="tahun_anggaran" class="form-select" required>
              <option value="">-- Pilih Tahun --</option>
              <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <hr>

          <!-- PROGRAM CONTAINER -->
          <div id="program-container"></div>

          <button type="button" id="add-program" class="btn btn-outline-success mb-4">
            + Tambah Program
          </button>

          <div class="text-end">
            <button type="submit" class="btn btn-success px-4">
              Simpan
            </button>
          </div>

        </form>

      </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <script src="<?= base_url('assets/js/adminKabupaten/pk/program-pk-form.js') ?>"></script>
</body>

</html>
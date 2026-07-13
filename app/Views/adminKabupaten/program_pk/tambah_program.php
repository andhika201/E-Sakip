<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'Tambah Program PK' ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/program-pk-form.css') ?>">
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="container-fluid p-4">
    <div class="card shadow-sm mx-auto" style="max-width:1200px;">
      <div class="card-body">

        <h3 class="text-center fw-bold text-success mb-4">Tambah Program PK</h3>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php
        $oldOpdId = old('opd_id');
        $oldTahun = old('tahun_anggaran');
        $oldJenisAnggaran = old('jenis_anggaran');
        $oldProgramData = old('program');
        $oldProgramData = is_array($oldProgramData) ? array_values($oldProgramData) : [];
        ?>

        <form method="post" action="<?= base_url('adminkab/program_pk/save') ?>">
          <?= csrf_field() ?>
          <div class="mb-4">
            <label class="form-label fw-semibold">OPD</label>
            <select name="opd_id" class="form-select" required>
              <option value="">-- Pilih OPD --</option>
              <?php foreach ($opds as $opd): ?>
                <option value="<?= $opd['id'] ?>" <?= ((string) $oldOpdId === (string) $opd['id']) ? 'selected' : '' ?>>
                  <?= esc($opd['nama_opd']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Tahun -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Tahun Anggaran</label>
            <select name="tahun_anggaran" class="form-select" required>
              <option value="">-- Pilih Tahun --</option>
              <?php for ($y = date('Y') - 1; $y <= date('Y') + 3; $y++): ?>
                <option value="<?= $y ?>" <?= ((string) $oldTahun === (string) $y) ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Jenis Anggaran</label>
            <select name="jenis_anggaran" class="form-select" required>
              <option value="">-- Pilih Jenis Anggaran --</option>
              <option value="murni" <?= ($oldJenisAnggaran === 'murni') ? 'selected' : '' ?>>APBD Murni</option>
              <option value="perubahan" <?= ($oldJenisAnggaran === 'perubahan') ? 'selected' : '' ?>>APBD Perubahan</option>
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

  <?php if (!empty($oldProgramData)): ?>
    <script>
      const programData = <?= json_encode($oldProgramData, JSON_UNESCAPED_UNICODE) ?>;
    </script>
  <?php endif; ?>
  <script src="<?= base_url('assets/js/adminKabupaten/pk/program-pk-form.js?v=' . time()) ?>"></script>
    </div>
</body>

</html>
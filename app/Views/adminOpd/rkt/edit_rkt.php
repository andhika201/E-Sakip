<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Edit RENJA (RKT)') ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <!-- Override SETELAH Select2 -->
  <style>
    .select2-container {
      width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
      height: 38px;
      padding: 6px 12px;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      display: flex;
      align-items: center;
      background-color: #fff;
    }

    .select2-selection__rendered {
      padding-left: 0 !important;
      color: #495057;
    }

    .select2-selection__arrow {
      height: 100% !important;
    }

    .select2-dropdown {
      border-radius: 0.375rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
    }

    .select2-results__option--highlighted {
      background-color: #00743e !important;
      color: #fff;
    }

    .program-item {
      background-color: #f3faf7;
      border: 1px solid #d9eee6;
      border-left: 4px solid #2e8b6f;

    }

    .kegiatan-item {
      background-color: #f6f9fc;
      border: 1px solid #dde7f0;
      border-left: 4px solid #4c7fb8;

    }

    .subkeg-item {
      background-color: #fafafa;
      border: 1px dashed #e0e0e0;
      border-left: 4px solid #b0b0b0;

    }

    .program-item,
    .kegiatan-item,
    .subkeg-item {
      border-radius: 8px;
    }

    /* Judul section lebih tegas */
    .program-item>.row>.col-md-6>label,
    .kegiatan-item label,
    .subkeg-item label {
      font-weight: 600;
    }

    /* Hover effect halus (optional tapi cakep) */
    .program-item:hover {
      background-color: #eef8f4;
    }

    .kegiatan-item:hover {
      background-color: #f2f7fb;
    }

    .subkeg-item:hover {
      background-color: #f5f5f5;
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminOpd/templates/header.php'); ?>
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Edit RENJA (RKT)</h2>

      <!-- Flash -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('success') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/rkt/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="indikator_id" value="<?= esc($indikator['id']) ?>">
        <input type="hidden" name="tahun" value="<?= esc($tahun) ?>">

        <!-- INDIKATOR & TAHUN (SAMA DENGAN TAMBAH) -->
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label text-uppercase small fw-semibold text-muted mb-1">Indikator</label>
            <div class="input-group shadow-sm rounded-3">
              <span class="input-group-text bg-success text-white border-0">
                <i class="fas fa-bullseye"></i>
              </span>
              <input type="text" class="form-control border-0 bg-light"
                value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label text-uppercase small fw-semibold text-muted mb-1">Tahun Rencana</label>
            <div class="input-group shadow-sm rounded-3">
              <span class="input-group-text bg-primary text-white border-0">
                <i class="fas fa-calendar-alt"></i>
              </span>
              <input type="number" class="form-control border-0 bg-light" value="<?= esc($tahun) ?>" readonly>
            </div>
          </div>
        </div>

        <!-- ================= PROGRAM ================= -->
        <section>
          <h2 class="h5 fw-semibold mb-3">Daftar Program</h2>

          <div class="program-container">

            <?php foreach ($rktPrograms as $pIndex => $prog): ?>
              <div class="program-item border rounded p-3 mb-4">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Program</label>
                    <select class="form-select select2 program-select border-secondary">
                      <option value="">Pilih Program</option>
                      <?php foreach ($program as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $prog['program_id'] ? 'selected' : '' ?>>
                          <?= esc($p['program_kegiatan']) ?> - Rp
                          <?= number_format($p['anggaran'], 0, ',', '.') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="remove-program btn btn-outline-danger btn-sm">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>

                <div class="kegiatan-container">
                  <?php foreach ($prog['kegiatan'] as $keg): ?>
                    <div class="kegiatan-item border rounded p-3 mb-3">
                      <div class="row">
                        <div class="col-md-6">
                          <label class="form-label">Kegiatan</label>
                          <select class="form-select select2 kegiatan-select border-secondary">
                            <option value="">Pilih Kegiatan</option>
                            <?php foreach ($kegiatanPk as $k): ?>
                              <option value="<?= $k['id'] ?>" data-anggaran="<?= $k['anggaran'] ?>"
                                <?= $k['id'] == $keg['kegiatan_id'] ? 'selected' : '' ?>>
                                <?= esc($k['kegiatan']) ?> - Rp 
                                <?= number_format($k['anggaran'], 0, ',', '.') ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                          <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </div>

                      <div class="subkeg-container">
                        <?php foreach ($keg['subkegiatan'] as $sub): ?>
                          <div class="subkeg-item border rounded p-3 mb-3">
                            <div class="row">
                              <div class="col-md-6">
                                <label class="form-label">Sub Kegiatan</label>
                                <select class="form-select select2 subkeg-select border-secondary">
                                  <option value="">Pilih Sub Kegiatan</option>
                                  <?php foreach ($subKegiatanPk as $sk): ?>
                                    <option value="<?= $sk['id'] ?>" data-anggaran="<?= $sk['anggaran'] ?>"
                                      <?= $sk['id'] == $sub['sub_kegiatan_id'] ? 'selected' : '' ?>>
                                      <?= esc($sk['sub_kegiatan']) ?> - Rp
                                      <?= number_format($sk['anggaran'], 0, ',', '.') ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>

                              <div class="col-md-3">
                                <label class="form-label">Anggaran</label>
                                <input type="text" class="form-control anggaran-input"
                                  value="<?= number_format($sub['anggaran'], 0, ',', '.') ?>" readonly>
                              </div>

                              <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="remove-subkeg btn btn-outline-danger btn-sm">
                                  <i class="fas fa-trash"></i>
                                </button>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>

                      <div class="d-flex justify-content-end">
                        <button type="button" class="add-subkeg btn btn-success btn-sm">
                          <i class="fas fa-plus"></i> Tambah Sub Kegiatan
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Tambah Kegiatan
                  </button>
                </div>
              </div>
            <?php endforeach; ?>

          </div>

          <div class="d-flex justify-content-end">
            <button type="button" class="add-program btn btn-primary btn-sm">
              <i class="fas fa-plus"></i> Tambah Program
            </button>
          </div>
        </section>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/rkt') ?>" class="btn btn-secondary">Kembali</a>
          <button type="submit" class="btn btn-success">Simpan Perubahan</button>
        </div>
      </form>

    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="<?= base_url('assets/js/adminOpd/rkt/rkt.js') ?>"></script>

</body>

</html>
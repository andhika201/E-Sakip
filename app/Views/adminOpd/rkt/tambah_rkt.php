<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title) ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RENJA</h2>

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/rkt/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="indikator_id" value="<?= esc($indikator['id']) ?>">

        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label">Indikator</label>
          <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
        </div>

        <!-- ================= PROGRAM ================= -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-semibold">Daftar Program</h2>
          </div>

          <div id="program-container">
            <!-- Default Program (initially present per Opsi A) -->
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Program 1</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm" style="display:none;">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program</label>
                <select name="program[0][program_id]" class="form-select select-program" required>
                  <option value="">-- Pilih Program --</option>
                  <?php foreach ($program as $prog): ?>
                    <option value="<?= esc($prog['id']) ?>"><?= esc($prog['program_kegiatan']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- ================= KEGIATAN ================= -->
              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium">Kegiatan</h4>
                </div>

                <div class="kegiatan-container">
                  <!-- Default Kegiatan 1 -->
                  <div class="kegiatan-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Kegiatan 1</label>
                      <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Nama Kegiatan</label>
                      <textarea name="program[0][kegiatan][0][nama_kegiatan]" class="form-control nama-kegiatan" rows="2"
                        placeholder="Masukkan nama kegiatan" required></textarea>
                    </div>

                    <!-- ================= SUBKEGIATAN ================= -->
                    <div class="subkegiatan-section">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="h6 fw-medium">Sub Kegiatan</h5>
                      </div>

                      <div class="subkegiatan-container">
                        <!-- Default Subkegiatan 1 -->
                        <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Sub Kegiatan 1</label>
                            <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>

                          <div class="row mb-3">
                            <div class="col-md-8">
                              <label class="form-label">Nama Sub Kegiatan</label>
                              <textarea name="program[0][kegiatan][0][subkegiatan][0][nama_subkegiatan]"
                                class="form-control nama-subkegiatan" rows="2" placeholder="Masukkan nama sub kegiatan"
                                required></textarea>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Target Anggaran</label>
                              <!-- visible display + hidden numeric -->
                              <input type="text" class="form-control target-display" placeholder="Masukkan nilai anggaran" required />
                              <input type="hidden" class="target-hidden" name="program[0][kegiatan][0][subkegiatan][0][target_anggaran]" value="">
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Tombol Tambah Subkegiatan -->
                      <div class="d-flex justify-content-end">
                        <button type="button" class="add-subkegiatan btn btn-info btn-sm">
                          <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tombol Tambah Kegiatan -->
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Tambah Program -->
          <div class="d-flex justify-content-end">
            <button type="button" id="add-program" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Program
            </button>
          </div>

          <!-- ================= HIDDEN TEMPLATES (OPSI B: di dalam section) ================= -->
          <template id="tpl-program">
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Program X</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program</label>
                <select class="form-select select-program">
                  <!-- options diisi oleh JS -->
                </select>
              </div>

              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium">Kegiatan</h4>
                </div>

                <div class="kegiatan-container"></div>

                <div class="d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </template>

          <template id="tpl-kegiatan">
            <div class="kegiatan-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Kegiatan X</label>
                <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Nama Kegiatan</label>
                <textarea class="form-control nama-kegiatan" rows="2" placeholder="Masukkan nama kegiatan"></textarea>
              </div>

              <div class="subkegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="h6 fw-medium">Sub Kegiatan</h5>
                </div>

                <div class="subkegiatan-container"></div>

                <div class="d-flex justify-content-end">
                  <button type="button" class="add-subkegiatan btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </template>

          <template id="tpl-subkegiatan">
            <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sub Kegiatan X</label>
                <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="row mb-3">
                <div class="col-md-8">
                  <label class="form-label">Nama Sub Kegiatan</label>
                  <textarea class="form-control nama-subkegiatan" rows="2" placeholder="Masukkan nama sub kegiatan"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Target Anggaran</label>
                  <input type="text" class="form-control target-display" placeholder="Masukkan nilai anggaran" />
                  <input type="hidden" class="target-hidden" value="">
                </div>
              </div>
            </div>
          </template>
        </section>

        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/rkt') ?>" class="btn btn-secondary">
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

  <script>
    const daftarProgram = <?= json_encode($program) ?>;
  </script>
  <script src="<?= base_url('/assets/js/adminOpd/renja/renja-form.js') ?>"></script>
</body>
</html>

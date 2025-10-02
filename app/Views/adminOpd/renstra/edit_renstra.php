<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Renstra e-SAKIP</title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <style>
    .alert { transition: all 0.3s ease; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .target-years-container .col-md-2 { margin-bottom: 10px; }
  </style>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <?= $this->include('adminOpd/templates/header.php'); ?>
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit Renstra</h2>
      <div id="alert-container">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
      </div>
      <form id="renstra-form" method="POST" action="<?= base_url('adminopd/renstra/update/' . ($renstra_data['renstra_tujuan_id'] ?? '')) ?>">
        <?= csrf_field() ?>
        <!-- Informasi Umum -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Renstra</h2>
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Sasaran RPJMD Terkait</label>
              <select name="rpjmd_sasaran_id" id="rpjmd_sasaran_select" class="form-select mb-3" required>
                <option value="">Pilih Sasaran RPJMD</option>
                <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" <?= isset($renstra_data['rpjmd_sasaran_id']) && $sasaran['id'] == $renstra_data['rpjmd_sasaran_id'] ? 'selected' : '' ?>>
                    <?= esc($sasaran['sasaran_rpjmd'] ?? $sasaran['sasaran']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Ketik untuk mencari sasaran RPJMD...</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tujuan Renstra</label>
              <input type="text" name="tujuan_renstra" id="tujuan_renstra_input" class="form-control mb-3"
                value="<?= esc($renstra_data['tujuan'] ?? $renstra_data['renstra_tujuan'] ?? '') ?>" placeholder="Ketik tujuan renstra" required autocomplete="off">
              <small class="text-muted">Ketik tujuan renstra sesuai kebutuhan Anda.</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tahun Mulai</label>
              <input type="number" name="tahun_mulai" id="tahun_mulai" class="form-control mb-3"
                value="<?= esc($renstra_data['tahun_mulai'] ?? '') ?>" placeholder="Contoh: 2025" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tahun Akhir</label>
              <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control mb-3"
                value="<?= esc($renstra_data['tahun_akhir'] ?? '') ?>" readonly required>
            </div>
          </div>
        </section>
        <!-- Daftar Sasaran Renstra -->
        <section>
          <h2 class="h5 fw-semibold mb-3">Daftar Sasaran Renstra</h2>
          <div id="sasaran-renstra-container">
            <?php if (isset($renstra_data['sasaran_renstra']) && is_array($renstra_data['sasaran_renstra'])): ?>
              <?php foreach ($renstra_data['sasaran_renstra'] as $sasaranIndex => $sasaran): ?>
                <div class="sasaran-renstra-item bg-light border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium sasaran-title">Sasaran Renstra <?= $sasaranIndex + 1 ?></label>
                    <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  <input type="hidden" name="sasaran_renstra[<?= $sasaranIndex ?>][id]" value="<?= $sasaran['id'] ?? '' ?>">
                  <div class="row mb-3">
                    <div class="col-md-12">
                      <label class="form-label">Sasaran Renstra</label>
                      <textarea name="sasaran_renstra[<?= $sasaranIndex ?>][sasaran]" class="form-control" required><?= esc($sasaran['sasaran'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <!-- Indikator Sasaran -->
                  <div class="indikator-sasaran-section">
                    <h6 class="fw-semibold mb-2">Indikator Sasaran</h6>
                    <div class="indikator-sasaran-container">
                      <?php if (isset($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])): ?>
                        <?php foreach ($sasaran['indikator_sasaran'] as $indikatorIndex => $indikator): ?>
                          <div class="indikator-sasaran-item bg-white border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                              <label class="fw-medium">Indikator Sasaran <?= ($sasaranIndex+1) . '.' . ($indikatorIndex+1) ?></label>
                              <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                            <input type="hidden" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][id]" value="<?= $indikator['id'] ?? '' ?>">
                            <div class="row mb-2">
                              <div class="col-md-6">
                                <label class="form-label">Indikator Sasaran</label>
                                <input type="text" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][indikator_sasaran]" class="form-control" value="<?= esc($indikator['indikator_sasaran'] ?? '') ?>" required>
                              </div>
                              <div class="col-md-6">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][satuan]" class="form-control" value="<?= esc($indikator['satuan'] ?? '') ?>" required>
                              </div>
                            </div>
                            <!-- Target Tahunan -->
                            <div class="target-years-container row">
                              <?php if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])): ?>
                                <?php foreach ($indikator['target_tahunan'] as $targetIndex => $target): ?>
                                  <div class="col-md-2">
                                    <label class="form-label">Tahun <?= esc($target['tahun'] ?? '') ?></label>
                                    <input type="hidden" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][id]" value="<?= $target['id'] ?? '' ?>">
                                    <input type="number" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][tahun]" class="form-control tahun-target" value="<?= esc($target['tahun'] ?? '') ?>" readonly>
                                    <input type="text" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][target]" class="form-control mt-1" value="<?= esc($target['target'] ?? '') ?>" placeholder="Target">
                                  </div>
                                <?php endforeach; ?>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-end">
                      <button type="button" class="add-indikator-sasaran btn btn-info btn-sm"><i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran</button>
                    </div>
                  </div> <!-- End Indikator Sasaran Section -->
                </div> <!-- End Sasaran Renstra -->
              <?php endforeach; ?>
            <?php endif; ?>
          </div> <!-- End Sasaran Renstra Container -->
          <div class="text-end mt-3">
            <button type="button" id="add-sasaran-renstra" class="btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran Renstra
            </button>
          </div>
        </section>
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/renstra') ?>" class="btn btn-secondary">
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
  <script src="<?= base_url('assets/js/adminOpd/renstra/renstra-form.js') ?>"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.getElementById('tahun_mulai').addEventListener('input', function () {
        const mulai = parseInt(this.value);
        if (!isNaN(mulai)) {
          document.getElementById('tahun_akhir').value = mulai + 4;
        } else {
          document.getElementById('tahun_akhir').value = '';
        }
        updateTahunTarget();
      });
      function updateTahunTarget() {
        const tahunMulai = parseInt(document.getElementById('tahun_mulai').value);
        const tahunAkhir = parseInt(document.getElementById('tahun_akhir').value);
        if (!isNaN(tahunMulai) && !isNaN(tahunAkhir)) {
          document.querySelectorAll('.sasaran-renstra-item').forEach(function (sasaranItem) {
            sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach(function (indikatorItem) {
              indikatorItem.querySelectorAll('.tahun-target').forEach(function (input, idx) {
                const tahun = tahunMulai + idx;
                if (tahun <= tahunAkhir) input.value = tahun;
                else input.value = '';
              });
            });
          });
        }
      }
      document.getElementById('tahun_akhir').addEventListener('input', updateTahunTarget);
      updateTahunTarget();
    });
  </script>
</body>
</html>
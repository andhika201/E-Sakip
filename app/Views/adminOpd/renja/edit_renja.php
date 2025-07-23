<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo esc($title); ?></title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit RENJA</h2>

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/renja/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="rpjmd_sasaran_id" value="<?= isset($renja_data['rpjmd_sasaran_id']) ? $renja_data['rpjmd_sasaran_id'] : '' ?>">
        <input type="hidden" name="renja_sasaran_id" value="<?= isset($renja_sasaran_id) ? $renja_sasaran_id : '' ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran RENSTRA Terkait RENJA ini</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RENSTRA</label>
            <select name="rpjmd_sasaran_id" id="rpjmd-sasaran-select" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RENSTRA</option>
              <?php if (isset($rpjmd_sasaran) && !empty($rpjmd_sasaran)): ?>
                <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" 
                    <?= (isset($renja_data['rpjmd_sasaran_id']) && $renja_data['rpjmd_sasaran_id'] == $sasaran['id']) ? 'selected' : '' ?>
                    data-tahun-mulai="<?= $sasaran['tahun_mulai'] ?>" data-tahun-akhir="<?= $sasaran['tahun_akhir'] ?>">
                    <?= esc($sasaran['sasaran_rpjmd']) ?> (Periode: <?= $sasaran['tahun_mulai'] ?>-<?= $sasaran['tahun_akhir'] ?>)
                  </option> 
                <?php endforeach; ?>
              <?php else: ?>
                <option value="" disabled>Tidak ada sasaran RENSTRA yang tersedia</option>
              <?php endif; ?>
            </select>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran RENJA -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran RENJA</h2>
        </div>

        <div id="sasaran-renja-container">
          <?php if (isset($renja_data['sasaran_renja']) && !empty($renja_data['sasaran_renja'])): ?>
            <?php foreach ($renja_data['sasaran_renja'] as $sasaranIndex => $sasaranRenja): ?>
              <!-- Sasaran RENJA <?= $sasaranIndex + 1 ?> -->
              <div class="sasaran-renja-item bg-light border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <label class="fw-medium">Sasaran RENJA <?= $sasaranIndex + 1 ?></label>
                  <button type="button" class="remove-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                
                <input type="hidden" name="sasaran_renja[<?= $sasaranIndex ?>][id]" value="<?= $sasaranRenja['id'] ?>">
                
                <div class="row mb-3">
                  <div class="col-md-12">
                    <label class="form-label">Sasaran RENJA</label>
                    <textarea name="sasaran_renja[<?= $sasaranIndex ?>][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RENJA" required><?= esc($sasaranRenja['sasaran']) ?></textarea>
                  </div>
                </div>

                <!-- Indikator Sasaran RENJA -->
                <div class="indikator-sasaran-renja-section">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="h5 fw-medium">Indikator Sasaran RENJA</h4>
                  </div>

                  <div class="indikator-sasaran-renja-container">
                    <?php if (isset($sasaranRenja['indikator']) && !empty($sasaranRenja['indikator'])): ?>
                      <?php foreach ($sasaranRenja['indikator'] as $indikatorIndex => $indikator): ?>
                        <!-- Indikator Sasaran RENJA <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?> -->
                        <div class="indikator-sasaran-renja-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Indikator Sasaran <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?></label>
                            <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                          </div>
                          
                          <input type="hidden" name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][id]" value="<?= $indikator['id'] ?>">
                          
                          <div class="mb-3">
                            <label class="form-label">Indikator Sasaran</label>
                            <textarea name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required><?= esc($indikator['indikator_sasaran']) ?></textarea>
                          </div>

                          <div class="row mb-3">
                            <div class="col-md-4">
                              <label class="form-label">Satuan</label>
                              <select name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][satuan]" class="form-select" required>
                                <option value="">Pilih Satuan</option>
                                <option value="Unit" <?= $indikator['satuan'] == 'Unit' ? 'selected' : '' ?>>Unit</option>
                                <option value="Nilai" <?= $indikator['satuan'] == 'Nilai' ? 'selected' : '' ?>>Nilai</option>
                                <option value="Persen" <?= $indikator['satuan'] == 'Persen' ? 'selected' : '' ?>>Persen</option>
                                <option value="Predikat" <?= $indikator['satuan'] == 'Predikat' ? 'selected' : '' ?>>Predikat</option>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Tahun Target</label>
                              <select name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][tahun]" class="form-select tahun-select" required>
                                <option value="">Pilih Tahun</option>
                                <?php 
                                // Get tahun range from selected RENSTRA Sasaran
                                $selectedSasaran = null;
                                if (isset($rpjmd_sasaran) && isset($renja_data['rpjmd_sasaran_id'])) {
                                  foreach ($rpjmd_sasaran as $sasaran) {
                                    if ($sasaran['id'] == $renja_data['rpjmd_sasaran_id']) {
                                      $selectedSasaran = $sasaran;
                                      break;
                                    }
                                  }
                                }
                                
                                if ($selectedSasaran):
                                  for ($year = $selectedSasaran['tahun_mulai']; $year <= $selectedSasaran['tahun_akhir']; $year++):
                                ?>
                                  <option value="<?= $year ?>" <?= $indikator['tahun'] == $year ? 'selected' : '' ?>><?= $year ?></option>
                                <?php 
                                  endfor;
                                else:
                                ?>
                                  <option value="<?= $indikator['tahun'] ?>" selected><?= $indikator['tahun'] ?></option>
                                <?php endif; ?>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Target</label>
                              <input type="text" name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target]" class="form-control" placeholder="Nilai target" value="<?= esc($indikator['target']) ?>" required>
                            </div>
                          </div>
                        </div> <!-- End Indikator Sasaran RENJA -->
                      <?php endforeach; ?>
                    <?php else: ?>
                      <!-- Default Indikator Sasaran RENJA jika tidak ada data -->
                      <div class="indikator-sasaran-renja-item border rounded p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <label class="fw-medium">Indikator Sasaran <?= $sasaranIndex + 1 ?>.1</label>
                          <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        
                        <div class="mb-3">
                          <label class="form-label">Indikator Sasaran</label>
                          <textarea name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                        </div>

                        <div class="row mb-3">
                          <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][0][satuan]" class="form-select" required>
                              <option value="">Pilih Satuan</option>
                              <option value="Unit">Unit</option>
                              <option value="Nilai">Nilai</option>
                              <option value="Persen">Persen</option>
                              <option value="Predikat">Predikat</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Tahun Target</label>
                            <select name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                              <option value="">Pilih Sasaran RENSTRA terlebih dahulu</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Target</label>
                            <input type="text" name="sasaran_renja[<?= $sasaranIndex ?>][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                          </div>
                        </div>
                      </div> <!-- End Indikator Sasaran RENJA -->
                    <?php endif; ?>
                  </div> <!-- End Indikator Sasaran RENJA Container -->
                </div> <!-- End Indikator Sasaran RENJA Section -->

                <!-- Tombol Tambah Indikator Sasaran -->
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-indikator-sasaran-renja btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                  </button>
                </div>

              </div> <!-- End Sasaran RENJA -->
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Default Sasaran RENJA jika tidak ada data -->
            <div class="sasaran-renja-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sasaran RENJA 1</label>
                <button type="button" class="remove-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran RENJA</label>
                  <textarea name="sasaran_renja[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RENJA" required></textarea>
                </div>
              </div>

              <!-- Indikator Sasaran RENJA -->
              <div class="indikator-sasaran-renja-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h5 fw-medium">Indikator Sasaran RENJA</h4>
                </div>

                <div class="indikator-sasaran-renja-container">
                  <!-- Indikator Sasaran RENJA 1.1 -->
                  <div class="indikator-sasaran-renja-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Indikator Sasaran 1.1</label>
                      <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Indikator Sasaran</label>
                      <textarea name="sasaran_renja[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                    </div>

                    <div class="row mb-3">
                      <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select id="satuanSelect" name="sasaran_renja[0][indikator_sasaran][0][satuan]" class="form-select" required>
                          <option value="">Pilih Satuan</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tahun Target</label>
                        <select name="sasaran_renja[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                          <option value="">Pilih Sasaran RENSTRA terlebih dahulu</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <input type="text" name="sasaran_renja[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                      </div>
                    </div>
                  </div> <!-- End Indikator Sasaran RENJA -->
                </div> <!-- End Indikator Sasaran RENJA Container -->
              </div> <!-- End Indikator Sasaran RENJA Section -->

              <!-- Tombol Tambah Indikator Sasaran -->
              <div class="d-flex justify-content-end">
                <button type="button" class="add-indikator-sasaran-renja btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
              </div>

            </div> <!-- End Sasaran RENJA -->
          <?php endif; ?>
        </div> <!-- End Sasaran RENJA Container -->

        <!-- Tombol Tambah Sasaran RENJA -->
        <div class="d-flex justify-content-end">
          <button type="button" id="add-sasaran-renja" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RENJA
          </button>
        </div>

      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminopd/renja') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Update
        </button>
      </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>

  <script src="<?= base_url('/assets/js/adminOpd/renja/renja-form.js')?>"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const select = document.getElementById('satuanSelect');
      if (select) {
        select.innerHTML = generateSatuanOptions();
      }
    });
  </script>
</body>
</html>
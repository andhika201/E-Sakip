<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo esc($title); ?></title>
  <!-- Style -->
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit RKT</h2>

      <form id="rkt-form" method="POST" action="<?= base_url('adminkab/rkt/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="rpjmd_sasaran_id" value="<?= isset($rkt_data['rpjmd_sasaran_id']) ? $rkt_data['rpjmd_sasaran_id'] : '' ?>">
        <input type="hidden" name="rkt_sasaran_id" value="<?= isset($rkt_sasaran_id) ? $rkt_sasaran_id : '' ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran RPJMD Terkait RKT ini</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RPJMD</label>
            <select name="rpjmd_sasaran_id" id="rpjmd-sasaran-select" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RPJMD</option>
              <?php if (isset($rpjmd_sasaran) && !empty($rpjmd_sasaran)): ?>
                <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" 
                    <?= (isset($rkt_data['rpjmd_sasaran_id']) && $rkt_data['rpjmd_sasaran_id'] == $sasaran['id']) ? 'selected' : '' ?>
                    data-tahun-mulai="<?= $sasaran['tahun_mulai'] ?>" data-tahun-akhir="<?= $sasaran['tahun_akhir'] ?>">
                    <?= esc($sasaran['sasaran_rpjmd']) ?> (Periode: <?= $sasaran['tahun_mulai'] ?>-<?= $sasaran['tahun_akhir'] ?>)
                  </option> 
                <?php endforeach; ?>
              <?php else: ?>
                <option value="" disabled>Tidak ada sasaran RPJMD yang tersedia</option>
              <?php endif; ?>
            </select>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran RKT -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran RKT</h2>
        </div>

        <div id="sasaran-rkt-container">
          <?php if (isset($rkt_data['sasaran_rkt']) && !empty($rkt_data['sasaran_rkt'])): ?>
            <?php foreach ($rkt_data['sasaran_rkt'] as $sasaranIndex => $sasaranRkt): ?>
              <!-- Sasaran RKT <?= $sasaranIndex + 1 ?> -->
              <div class="sasaran-rkt-item bg-light border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <label class="fw-medium">Sasaran RKT <?= $sasaranIndex + 1 ?></label>
                  <button type="button" class="remove-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                
                <input type="hidden" name="sasaran_rkt[<?= $sasaranIndex ?>][id]" value="<?= $sasaranRkt['id'] ?>">
                
                <div class="row mb-3">
                  <div class="col-md-12">
                    <label class="form-label">Sasaran RKT</label>
                    <textarea name="sasaran_rkt[<?= $sasaranIndex ?>][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RKT" required><?= esc($sasaranRkt['sasaran']) ?></textarea>
                  </div>
                </div>

                <!-- Indikator Sasaran RKT -->
                <div class="indikator-sasaran-rkt-section">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="h5 fw-medium">Indikator Sasaran RKT</h4>
                  </div>

                  <div class="indikator-sasaran-rkt-container">
                    <?php if (isset($sasaranRkt['indikator']) && !empty($sasaranRkt['indikator'])): ?>
                      <?php foreach ($sasaranRkt['indikator'] as $indikatorIndex => $indikator): ?>
                        <!-- Indikator Sasaran RKT <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?> -->
                        <div class="indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Indikator Sasaran <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?></label>
                            <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                          </div>
                          
                          <input type="hidden" name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][id]" value="<?= $indikator['id'] ?>">
                          
                          <div class="mb-3">
                            <label class="form-label">Indikator Sasaran</label>
                            <textarea name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required><?= esc($indikator['indikator_sasaran']) ?></textarea>
                          </div>

                          <div class="row mb-3">
                            <div class="col-md-4">
                              <label class="form-label">Satuan</label>
                              <select name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][satuan]" class="form-select" required>
                                <option value="">Pilih Satuan</option>
                                <option value="Unit" <?= $indikator['satuan'] == 'Unit' ? 'selected' : '' ?>>Unit</option>
                                <option value="Nilai" <?= $indikator['satuan'] == 'Nilai' ? 'selected' : '' ?>>Nilai</option>
                                <option value="Persen" <?= $indikator['satuan'] == 'Persen' ? 'selected' : '' ?>>Persen</option>
                                <option value="Predikat" <?= $indikator['satuan'] == 'Predikat' ? 'selected' : '' ?>>Predikat</option>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Tahun Target</label>
                              <select name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][tahun]" class="form-select tahun-select" required>
                                <option value="">Pilih Tahun</option>
                                <?php 
                                // Get tahun range from selected RPJMD Sasaran
                                $selectedSasaran = null;
                                if (isset($rpjmd_sasaran) && isset($rkt_data['rpjmd_sasaran_id'])) {
                                  foreach ($rpjmd_sasaran as $sasaran) {
                                    if ($sasaran['id'] == $rkt_data['rpjmd_sasaran_id']) {
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
                              <input type="text" name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target]" class="form-control" placeholder="Nilai target" value="<?= esc($indikator['target']) ?>" required>
                            </div>
                          </div>
                        </div> <!-- End Indikator Sasaran RKT -->
                      <?php endforeach; ?>
                    <?php else: ?>
                      <!-- Default Indikator Sasaran RKT jika tidak ada data -->
                      <div class="indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <label class="fw-medium">Indikator Sasaran <?= $sasaranIndex + 1 ?>.1</label>
                          <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        
                        <div class="mb-3">
                          <label class="form-label">Indikator Sasaran</label>
                          <textarea name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                        </div>

                        <div class="row mb-3">
                          <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][0][satuan]" class="form-select" required>
                              <option value="">Pilih Satuan</option>
                              <option value="Unit">Unit</option>
                              <option value="Nilai">Nilai</option>
                              <option value="Persen">Persen</option>
                              <option value="Predikat">Predikat</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Tahun Target</label>
                            <select name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                              <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Target</label>
                            <input type="text" name="sasaran_rkt[<?= $sasaranIndex ?>][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                          </div>
                        </div>
                      </div> <!-- End Indikator Sasaran RKT -->
                    <?php endif; ?>
                  </div> <!-- End Indikator Sasaran RKT Container -->
                </div> <!-- End Indikator Sasaran RKT Section -->

                <!-- Tombol Tambah Indikator Sasaran -->
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                  </button>
                </div>

              </div> <!-- End Sasaran RKT -->
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Default Sasaran RKT jika tidak ada data -->
            <div class="sasaran-rkt-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sasaran RKT 1</label>
                <button type="button" class="remove-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran RKT</label>
                  <textarea name="sasaran_rkt[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RKT" required></textarea>
                </div>
              </div>

              <!-- Indikator Sasaran RKT -->
              <div class="indikator-sasaran-rkt-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h5 fw-medium">Indikator Sasaran RKT</h4>
                </div>

                <div class="indikator-sasaran-rkt-container">
                  <!-- Indikator Sasaran RKT 1.1 -->
                  <div class="indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Indikator Sasaran 1.1</label>
                      <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Indikator Sasaran</label>
                      <textarea name="sasaran_rkt[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                    </div>

                    <div class="row mb-3">
                      <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select id="satuanSelect" name="sasaran_rkt[0][indikator_sasaran][0][satuan]" class="form-select" required>
                          <option value="">Pilih Satuan</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tahun Target</label>
                        <select name="sasaran_rkt[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                          <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <input type="text" name="sasaran_rkt[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                      </div>
                    </div>
                  </div> <!-- End Indikator Sasaran RKT -->
                </div> <!-- End Indikator Sasaran RKT Container -->
              </div> <!-- End Indikator Sasaran RKT Section -->

              <!-- Tombol Tambah Indikator Sasaran -->
              <div class="d-flex justify-content-end">
                <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
              </div>

            </div> <!-- End Sasaran RKT -->
          <?php endif; ?>
        </div> <!-- End Sasaran RKT Container -->

        <!-- Tombol Tambah Sasaran RKT -->
        <div class="d-flex justify-content-end">
          <button type="button" id="add-sasaran-rkt" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RKT
          </button>
        </div>

      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminkab/rkt') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Update
        </button>
      </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <script src="<?= base_url('/assets/js/adminKabupaten/rkt/rkt-form.js')?>"></script>

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
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit RKPD</h2>

      <form id="rkpd-form" method="POST" action="<?= base_url('adminkab/rkpd/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="rpjmd_sasaran_id" value="<?= isset($rkpd_data['rpjmd_sasaran_id']) ? $rkpd_data['rpjmd_sasaran_id'] : '' ?>">
        <input type="hidden" name="rkpd_sasaran_id" value="<?= isset($rkpd_sasaran_id) ? $rkpd_sasaran_id : '' ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran RPJMD Terkait RKPD ini</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RPJMD</label>
            <select name="rpjmd_sasaran_id" id="rpjmd-sasaran-select" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RPJMD</option>
              <?php if (isset($rpjmd_sasaran) && !empty($rpjmd_sasaran)): ?>
                <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" 
                    <?= (isset($rkpd_data['rpjmd_sasaran_id']) && $rkpd_data['rpjmd_sasaran_id'] == $sasaran['id']) ? 'selected' : '' ?>
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

      <!-- Daftar Sasaran RKPD -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran RKPD</h2>
        </div>

        <div id="sasaran-rkpd-container">
          <?php if (isset($rkpd_data['sasaran_rkpd']) && !empty($rkpd_data['sasaran_rkpd'])): ?>
            <?php foreach ($rkpd_data['sasaran_rkpd'] as $sasaranIndex => $sasaranRkpd): ?>
              <!-- Sasaran RKPD <?= $sasaranIndex + 1 ?> -->
              <div class="sasaran-rkpd-item bg-light border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <label class="fw-medium">Sasaran RKPD <?= $sasaranIndex + 1 ?></label>
                  <button type="button" class="remove-sasaran-rkpd btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                
                <input type="hidden" name="sasaran_rkpd[<?= $sasaranIndex ?>][id]" value="<?= $sasaranRkpd['id'] ?>">
                
                <div class="row mb-3">
                  <div class="col-md-12">
                    <label class="form-label">Sasaran RKPD</label>
                    <textarea name="sasaran_rkpd[<?= $sasaranIndex ?>][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RKPD" required><?= esc($sasaranRkpd['sasaran']) ?></textarea>
                  </div>
                </div>

                <!-- Indikator Sasaran RKPD -->
                <div class="indikator-sasaran-rkpd-section">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="h5 fw-medium">Indikator Sasaran RKPD</h4>
                  </div>

                  <div class="indikator-sasaran-rkpd-container">
                    <?php if (isset($sasaranRkpd['indikator']) && !empty($sasaranRkpd['indikator'])): ?>
                      <?php foreach ($sasaranRkpd['indikator'] as $indikatorIndex => $indikator): ?>
                        <!-- Indikator Sasaran RKPD <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?> -->
                        <div class="indikator-sasaran-rkpd-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Indikator Sasaran <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?></label>
                            <button type="button" class="remove-indikator-sasaran-rkpd btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                          </div>
                          
                          <input type="hidden" name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][id]" value="<?= $indikator['id'] ?>">
                          
                          <div class="mb-3">
                            <label class="form-label">Indikator Sasaran</label>
                            <textarea name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required><?= esc($indikator['indikator_sasaran']) ?></textarea>
                          </div>

                          <div class="row mb-3">
                            <div class="col-md-4">
                              <label class="form-label">Satuan</label>
                              <select name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][satuan]" class="form-select satuan-select" data-selected="<?= esc($indikator['satuan']) ?>" required>
                                <option value="">Pilih Satuan</option>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Tahun Target</label>
                              <select name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][tahun]" class="form-select tahun-select" required>
                                <option value="">Pilih Tahun</option>
                                <?php 
                                // Get tahun range from selected RPJMD Sasaran
                                $selectedSasaran = null;
                                if (isset($rpjmd_sasaran) && isset($rkpd_data['rpjmd_sasaran_id'])) {
                                  foreach ($rpjmd_sasaran as $sasaran) {
                                    if ($sasaran['id'] == $rkpd_data['rpjmd_sasaran_id']) {
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
                              <input type="text" name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target]" class="form-control" placeholder="Nilai target" value="<?= esc($indikator['target']) ?>" required>
                            </div>
                          </div>
                        </div> <!-- End Indikator Sasaran RKPD -->
                      <?php endforeach; ?>
                    <?php else: ?>
                      <!-- Default Indikator Sasaran RKPD jika tidak ada data -->
                      <div class="indikator-sasaran-rkpd-item border rounded p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <label class="fw-medium">Indikator Sasaran <?= $sasaranIndex + 1 ?>.1</label>
                          <button type="button" class="remove-indikator-sasaran-rkpd btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        
                        <div class="mb-3">
                          <label class="form-label">Indikator Sasaran</label>
                          <textarea name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                        </div>

                        <div class="row mb-3">
                          <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
                              <option value="">Pilih Satuan</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Tahun Target</label>
                            <select name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                              <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Target</label>
                            <input type="text" name="sasaran_rkpd[<?= $sasaranIndex ?>][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                          </div>
                        </div>
                      </div> <!-- End Indikator Sasaran RKPD -->
                    <?php endif; ?>
                  </div> <!-- End Indikator Sasaran RKPD Container -->
                </div> <!-- End Indikator Sasaran RKPD Section -->

                <!-- Tombol Tambah Indikator Sasaran -->
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-indikator-sasaran-rkpd btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                  </button>
                </div>

              </div> <!-- End Sasaran RKPD -->
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Default Sasaran RKPD jika tidak ada data -->
            <div class="sasaran-rkpd-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sasaran RKPD 1</label>
                <button type="button" class="remove-sasaran-rkpd btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran RKPD</label>
                  <textarea name="sasaran_rkpd[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RKPD" required></textarea>
                </div>
              </div>

              <!-- Indikator Sasaran RKPD -->
              <div class="indikator-sasaran-rkpd-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h5 fw-medium">Indikator Sasaran RKPD</h4>
                </div>

                <div class="indikator-sasaran-rkpd-container">
                  <!-- Indikator Sasaran RKPD 1.1 -->
                  <div class="indikator-sasaran-rkpd-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Indikator Sasaran 1.1</label>
                      <button type="button" class="remove-indikator-sasaran-rkpd btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Indikator Sasaran</label>
                      <textarea name="sasaran_rkpd[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                    </div>

                    <div class="row mb-3">
                      <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select name="sasaran_rkpd[0][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
                          <option value="">Pilih Satuan</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tahun Target</label>
                        <select name="sasaran_rkpd[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                          <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <input type="text" name="sasaran_rkpd[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                      </div>
                    </div>
                  </div> <!-- End Indikator Sasaran RKPD -->
                </div> <!-- End Indikator Sasaran RKPD Container -->
              </div> <!-- End Indikator Sasaran RKPD Section -->

              <!-- Tombol Tambah Indikator Sasaran -->
              <div class="d-flex justify-content-end">
                <button type="button" class="add-indikator-sasaran-rkpd btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
              </div>

            </div> <!-- End Sasaran RKPD -->
          <?php endif; ?>
        </div> <!-- End Sasaran RKPD Container -->

        <!-- Tombol Tambah Sasaran RKPD -->
        <div class="d-flex justify-content-end">
          <button type="button" id="add-sasaran-rkpd" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RKPD
          </button>
        </div>

      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminkab/rkpd') ?>" class="btn btn-secondary">
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

  <script src="<?= base_url('/assets/js/adminKabupaten/rkpd/rkpd-form.js')?>"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Populate all satuan selects with options from JavaScript helper
      document.querySelectorAll('.satuan-select').forEach(select => {
        const selectedValue = select.getAttribute('data-selected') || '';
        select.innerHTML = generateSatuanOptions();
        if (selectedValue) {
          select.value = selectedValue;
        }
      });
    });
  </script>
</body>
</html>
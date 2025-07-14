<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Renstra e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah Renstra</h2>

      <form id="renstra-form" method="POST" action="<?= base_url('adminopd/renstra/save') ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Informasi Umum Renstra</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RPJMD Terkait</label>
            <select name="rpjmd_sasaran_id" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RPJMD</option>
              <!-- Options akan diisi dari database -->
              <option value="1">Meningkatnya kualitas pelayanan publik</option>
              <option value="2">Meningkatnya transparansi pengelolaan keuangan</option>
              <option value="3">Meningkatnya kompetensi ASN</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tahun Awal</label>
            <input type="number" name="tahun_awal" id="tahun_awal" class="form-control mb-3" min="2020" max="2050" placeholder="Contoh: 2025" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tahun Akhir</label>
            <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control mb-3" readonly required>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran Renstra -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran Renstra</h2>
          <button type="button" id="add-sasaran-renstra" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran Renstra
          </button>
        </div>

        <div id="sasaran-renstra-container">
          <!-- Sasaran Renstra 1 -->
          <div class="sasaran-renstra-item bg-light border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium">Sasaran Renstra 1</label>
              <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Sasaran Renstra</label>
                <textarea name="sasaran_renstra[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran renstra" required></textarea>
              </div>
            </div>

            <!-- Indikator Sasaran -->
            <div class="indikator-sasaran-section">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h5 fw-medium">Indikator Sasaran</h4>
                <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
              </div>

              <div class="indikator-sasaran-container">
                <!-- Indikator Sasaran 1.1 -->
                <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator Sasaran 1.1</label>
                    <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Indikator Sasaran</label>
                    <textarea name="sasaran_renstra[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Satuan</label>
                      <select name="sasaran_renstra[0][indikator_sasaran][0][satuan]" class="form-control" required>
                        <option value="">Pilih Satuan</option>
                        <option value="Persen">Persen</option>
                        <option value="Nilai">Nilai</option>
                        <option value="Predikat">Predikat</option>
                        <option value="Unit">Unit</option>
                      </select>
                    </div>
                  </div>

                  <!-- Target Tahunan -->
                  <div class="target-section">
                    <h5 class="fw-medium mb-3">Target Tahunan</h5>
                    <div class="target-container">
                      <!-- Target akan diisi otomatis berdasarkan tahun awal dan akhir -->
                      <div class="target-years-container">
                        <div class="row g-2 mb-2">
                          <div class="col-md-2">
                            <label class="form-label fw-medium">2025</label>
                            <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][target]" class="form-control form-control-sm" placeholder="Target 2025" required>
                            <input type="hidden" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][tahun]" value="2025">
                          </div>
                          <div class="col-md-2">
                            <label class="form-label fw-medium">2026</label>
                            <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][target]" class="form-control form-control-sm" placeholder="Nilai Target" required>
                            <input type="hidden" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][tahun]" value="2026">
                          </div>
                          <div class="col-md-2">
                            <label class="form-label fw-medium">2027</label>
                            <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][target]" class="form-control form-control-sm" placeholder="Nilai Target" required>
                            <input type="hidden" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][tahun]" value="2027">
                          </div>
                          <div class="col-md-2">
                            <label class="form-label fw-medium">2028</label>
                            <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][target]" class="form-control form-control-sm" placeholder="Nilai Target" required>
                            <input type="hidden" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][tahun]" value="2028">
                          </div>
                          <div class="col-md-2">
                            <label class="form-label fw-medium">2029</label>
                            <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][target]" class="form-control form-control-sm" placeholder="Nilai Target" required>
                            <input type="hidden" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][tahun]" value="2029">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- End Indikator Sasaran -->
              </div> <!-- End Indikator Sasaran Container -->
            </div> <!-- End Indikator Sasaran Section -->
          </div> <!-- End Sasaran Renstra -->
        </div> <!-- End Sasaran Renstra Container -->
      </section>

      <!-- Tombol Aksi -->
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

  <script src="<?= base_url('assets/js/adminOpd/renstra-form.js') ?>"></script>
</body>
</html>

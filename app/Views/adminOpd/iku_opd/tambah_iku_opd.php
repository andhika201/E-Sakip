<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah IKU OPD</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah IKU</h2>

      <!-- Alert Container -->
      <div id="alert-container"></div>

      <form id="iku-form" method="POST" action="<?= base_url('adminopd/iku_opd/save') ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Informasi Umum IKU</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran Renstra</label>
            <select name="renstra_sasaran_id" id="renstra-sasaran-select" class="form-select mb-3" required>
              <option value="">Pilih Sasaran Renstra</option>
              <?php if (!empty($renstra_sasaran)): ?>
                <?php foreach ($renstra_sasaran as $renstra): ?>
                  <option value="<?= $renstra['id'] ?>"><?= esc($renstra['sasaran_renstra']) ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tahun Mulai</label>
            <input type="number" name="tahun_mulai" id="tahun_mulai" class="form-control mb-3" placeholder="Contoh: 2025" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tahun Akhir</label>
            <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control mb-3" readonly required>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran IKU -->
      <section>
        <div class="mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran IKU</h2>
        </div>

        <div id="sasaran-iku-container">
          <!-- Sasaran IKU 1 -->
          <div class="sasaran-iku-item bg-light border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium">Sasaran IKU 1</label>
              <button type="button" class="remove-sasaran-iku btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Sasaran IKU</label>
                <textarea name="sasaran_iku[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran IKU" required></textarea>
              </div>
            </div>

            <!-- Indikator Kinerja -->
            <div class="indikator-kinerja-section">
              <div class="mb-3">
                <h4 class="h5 fw-medium">Indikator Kinerja</h4>
              </div>

              <div class="indikator-kinerja-container">
                <!-- Indikator Kinerja 1.1 -->
                <div class="indikator-kinerja-item border rounded p-3 bg-white mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator Kinerja 1.1</label>
                    <button type="button" class="remove-indikator-kinerja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Indikator Kinerja</label>
                    <textarea name="sasaran_iku[0][indikator_kinerja][0][indikator_kinerja]" class="form-control" rows="2" placeholder="Masukkan indikator kinerja" required></textarea>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Definisi & Formulasi</label>
                    <textarea name="sasaran_iku[0][indikator_kinerja][0][definisi_formulasi]" class="form-control" rows="3" placeholder="Masukkan definisi dan formulasi indikator" required></textarea>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Satuan</label>
                      <select name="sasaran_iku[0][indikator_kinerja][0][satuan]" class="form-control satuan-select" required>
                        <option value="">Pilih Satuan</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Program Pendukung</label>
                      <textarea name="sasaran_iku[0][indikator_kinerja][0][program_pendukung]" class="form-control" rows="2" placeholder="Masukkan program pendukung" required></textarea>
                    </div>
                  </div>

                  <!-- Target Tahunan -->
                  <div class="target-section">
                    <h5 class="fw-medium mb-3">Target Tahunan</h5>

                    <div class="target-container">
                      <!-- Target Tahun 1 -->
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][0][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][0][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      
                      <!-- Target Tahun 2 -->
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][1][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][1][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      
                      <!-- Target Tahun 3 -->
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][2][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][2][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      
                      <!-- Target Tahun 4 -->
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][3][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][3][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      
                      <!-- Target Tahun 5 -->
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][4][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][4][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- End Indikator Kinerja -->
              </div> <!-- End Indikator Kinerja Container -->
              
              <div class="d-flex justify-content-end mt-3">
                <button type="button" class="add-indikator-kinerja btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Kinerja
                </button>
              </div>
            </div> <!-- End Indikator Kinerja Section -->
          </div> <!-- End Sasaran IKU -->
        </div> <!-- End Sasaran IKU Container -->
        
        <div class="d-flex justify-content-end mt-3">
          <button type="button" id="add-sasaran-iku" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran IKU
          </button>
        </div>
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminopd/iku_opd') ?>" class="btn btn-secondary">
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

  <!-- Load IKU Form JavaScript -->
  <script src="<?= base_url('assets/js/adminOpd/iku_opd/ikuOpd-form.js') ?>"></script>
</body>
</html>
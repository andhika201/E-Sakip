<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit RPJMD - e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit RPJMD</h2>

      <form id="rpjmd-form" method="POST" action="<?= base_url('adminkab/rpjmd/save') ?>">
        <!-- Hidden fields untuk mode edit -->
        <input type="hidden" name="id" value="<?= isset($misi['id']) ? $misi['id'] : '' ?>">
        <input type="hidden" name="mode" value="edit">

        <!-- Informasi Umum Misi -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Misi</h2>
          <div class="row">
            <div class="col-md-8">
              <label class="form-label">Misi RPJMD</label>
              <textarea name="misi[misi]" class="form-control mb-3" rows="2" placeholder="Contoh: Mewujudkan pembangunan berkelanjutan yang berpusat pada masyarakat" required><?= isset($misi['misi']) ? esc($misi['misi']) : '' ?></textarea>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Mulai</label>
              <input type="number" name="misi[tahun_mulai]" id="periode_start" class="form-control mb-3" value="<?= isset($misi['tahun_mulai']) ? $misi['tahun_mulai'] : '' ?>" placeholder="Contoh: 2025" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Akhir</label>
              <input type="number" name="misi[tahun_akhir]" id="periode_end" class="form-control mb-3" value="<?= isset($misi['tahun_akhir']) ? $misi['tahun_akhir'] : '' ?>" placeholder="" readonly>
            </div>
          </div>
        </section>

        <!-- Daftar Tujuan -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 fw-semibold">Daftar Tujuan</h2>
          </div>

          <div id="tujuan-container">
            <!-- Tujuan 1 -->
            <div class="tujuan-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Tujuan 1</label>
                <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              <div class="mb-3">
                <label class="form-label">Tujuan RPJMD</label>
                <textarea name="tujuan[0][tujuan_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required>Terwujudnya kehidupan masyarakat yang agamis, berbudaya dan demokratis</textarea>
              </div>

              <!-- Indikator Tujuan -->
              <div class="indikator-tujuan-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="fw-medium">Indikator Tujuan</h3>
                </div>

                <div class="indikator-tujuan-container">
                  <!-- Indikator Tujuan 1.1 -->
                  <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Indikator Tujuan 1.1</label>
                      <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Indikator</label>
                      <input type="text" name="tujuan[0][indikator_tujuan][0][indikator]" class="form-control" value="Indeks Demokrasi Indonesia (IDI)" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
                    </div>
                  </div>
                </div>
                <!-- Tombol Tambah Indikator Tujuan di bawah container -->
                <div class="d-flex justify-content-end mt-2">
                  <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
                  </button>
                </div>
              </div>

              <!-- Sasaran -->
              <div class="sasaran-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
                </div>

                <div class="sasaran-container">
                  <!-- Sasaran 1.1 -->
                  <div class="sasaran-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Sasaran 1.1</label>
                      <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Sasaran RPJMD</label>
                      <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required>Meningkatkan kualitas kehidupan beragama dan toleransi antar umat beragama</textarea>
                    </div>

                    <!-- Indikator Sasaran -->
                    <div class="indikator-sasaran-section">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-medium">Indikator Sasaran</h4>
                      </div>

                      <div class="indikator-sasaran-container">
                        <!-- Indikator Sasaran 1.1.1 -->
                        <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Indikator Sasaran 1.1.1</label>
                            <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                          </div>
                          
                          <div class="row">
                            <div class="col-md-8">
                              <label class="form-label">Indikator</label>
                              <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" value="Jumlah tempat ibadah yang aktif" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Satuan</label>
                              <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-control mb-3" value="Unit" placeholder="Contoh: Persen, Jumlah, Indeks" required>
                            </div>
                          </div>

                          <!-- Strategi -->
                          <div class="mb-3">
                            <label class="form-label">Strategi</label>
                            <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required>Meningkatkan fasilitas tempat ibadah dan pembinaan kegiatan keagamaan</textarea>
                          </div>

                          <!-- Target 5 Tahunan -->
                          <div class="target-section">
                            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                            <div class="target-container">
                              <div class="target-item row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                  <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" value="2019" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                </div>
                                <div class="col">
                                  <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target_tahunan]" value="150" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                                </div>
                              </div>
                              <div class="target-item row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                  <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" value="2020" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                </div>
                                <div class="col">
                                  <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target_tahunan]" value="155" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                                </div>
                              </div>
                              <div class="target-item row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                  <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][tahun]" value="2021" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                </div>
                                <div class="col">
                                  <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][target_tahunan]" value="160" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                                </div>
                              </div>
                              <div class="target-item row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                  <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][tahun]" value="2022" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                </div>
                                <div class="col">
                                  <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][target_tahunan]" value="165" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                                </div>
                              </div>
                              <div class="target-item row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                  <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][tahun]" value="2024" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                </div>
                                <div class="col">
                                  <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][target_tahunan]" value="170" class="form-control form-control-sm" placeholder="Contoh: 85" required>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div> <!-- End Indikator Sasaran -->
                      </div> <!-- End Indikator Sasaran Container -->
                      <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                      <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                          <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                        </button>
                      </div>
                    </div> <!-- End Indikator Sasaran Section -->
                  </div> <!-- End Sasaran -->
                </div> <!-- End Sasaran Container -->
                <!-- Tombol Tambah Sasaran di bawah container -->
                <div class="d-flex justify-content-end mt-2">
                  <button type="button" class="add-sasaran btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Sasaran
                  </button>
                </div>
              </div> <!-- End Sasaran Section -->
            </div> <!-- End Tujuan -->
          </div> <!-- End Tujuan Container -->
          
          <!-- Tombol Tambah Tujuan di bawah, tapi di luar #tujuan-container -->
          <div class="d-flex justify-content-end mt-2">
            <button type="button" id="add-tujuan" class="btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Tujuan
            </button>
          </div>
        </section>

        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminkab/rpjmd') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- Include JavaScript eksternal -->
  <script src="<?= base_url('assets/js/adminKabupaten/rpjmd/rpjmd-form.js') ?>"></script>
  
  <script>
    // Inisialisasi data untuk edit form
    document.addEventListener('DOMContentLoaded', function() {
      // Update periode pada load
      updatePeriodeTahun();
      
      // Inisialisasi form dengan data yang sudah ada
      updateLabels();
      updateFormNames();
    });
  </script>
</body>
</html>

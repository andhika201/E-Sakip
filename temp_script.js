<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah RPJMD e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RPJMD</h2>

      <form id="rpjmd-form" method="POST" action="<?= base_url('adminkab/save_rpjmd') ?>">

      <!-- Informasi Umum Misi -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Informasi Umum Misi</h2>
        <div class="row">
          <div class="col-md-8">
            <label class="form-label">Misi RPJMD</label>
            <textarea name="misi_rpjmd" class="form-control mb-3" rows="2" placeholder="Contoh: Mewujudkan pembangunan berkelanjutan yang berpusat pada masyarakat" required></textarea>
          </div>
          <div class="col-md-2">
            <label class="form-label">Periode Mulai</label>
            <input type="number" name="periode_start" id="periode_start" class="form-control mb-3" value="2025" placeholder="Contoh: 2025" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Periode Akhir</label>
            <input type="number" name="periode_end" id="periode_end" class="form-control mb-3" value="2030" placeholder="Otomatis terisi" readonly>
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
              <textarea name="tujuan[0][tujuan_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required></textarea>
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
                    <input type="text" name="tujuan[0][indikator_tujuan][0][indikator]" class="form-control" value="" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
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
                    <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
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
                            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator]" class="form-control mb-3" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-control mb-3" value="" placeholder="Contoh: Persen, Jumlah, Indeks" required>
                          </div>
                        </div>

                        <!-- Strategi -->
                        <div class="mb-3">
                          <label class="form-label">Strategi</label>
                          <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                        </div>

                        <!-- Target 5 Tahunan -->
                        <div class="target-section">
                          <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                          <div class="target-container">
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" value="2025" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                              </div>
                            </div>
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" value="2026" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                              </div>
                            </div>
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][tahun]" value="2027" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                              </div>
                            </div>
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][tahun]" value="2028" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                              </div>
                            </div>
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][tahun]" value="2029" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 85" required>
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
          <i class="fas fa-save me-1"></i> Simpan
        </button>
      </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <script>
    // Fungsi untuk mengupdate penomoran label secara real-time
    function updateLabels() {
      document.querySelectorAll('.tujuan-item').forEach((tujuanItem, tujuanIndex) => {
        const tujuanNumber = tujuanIndex + 1;
        
        // Update label tujuan
        const tujuanLabel = tujuanItem.querySelector('label');
        if (tujuanLabel) {
          tujuanLabel.textContent = `Tujuan ${tujuanNumber}`;
        }

        // Update indikator tujuan labels
        tujuanItem.querySelectorAll('.indikator-tujuan-item').forEach((indikatorTujuanItem, indikatorTujuanIndex) => {
          const indikatorNumber = indikatorTujuanIndex + 1;
          const indikatorLabel = indikatorTujuanItem.querySelector('label');
          if (indikatorLabel) {
            indikatorLabel.textContent = `Indikator Tujuan ${tujuanNumber}.${indikatorNumber}`;
          }
        });

        // Update sasaran labels
        tujuanItem.querySelectorAll('.sasaran-item').forEach((sasaranItem, sasaranIndex) => {
          const sasaranNumber = sasaranIndex + 1;
          const sasaranLabel = sasaranItem.querySelector('label');
          if (sasaranLabel) {
            sasaranLabel.textContent = `Sasaran ${tujuanNumber}.${sasaranNumber}`;
          }

          // Update indikator sasaran labels
          sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorSasaranItem, indikatorSasaranIndex) => {
            const indikatorSasaranNumber = indikatorSasaranIndex + 1;
            const indikatorSasaranLabel = indikatorSasaranItem.querySelector('label');
            if (indikatorSasaranLabel) {
              indikatorSasaranLabel.textContent = `Indikator Sasaran ${tujuanNumber}.${sasaranNumber}.${indikatorSasaranNumber}`;
            }
          });
        });
      });
    }

    // Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
    function updateFormNames() {
      document.querySelectorAll('.tujuan-item').forEach((tujuanItem, tujuanIndex) => {
        // Update tujuan names
        const tujuanTextarea = tujuanItem.querySelector('textarea[name*="tujuan_rpjmd"], textarea:not([name])');
        if (tujuanTextarea) {
          tujuanTextarea.name = `tujuan[${tujuanIndex}][tujuan_rpjmd]`;
        }

        // Update indikator tujuan names
        tujuanItem.querySelectorAll('.indikator-tujuan-item').forEach((indikatorTujuanItem, indikatorTujuanIndex) => {
          const indikatorInput = indikatorTujuanItem.querySelector('input[name*="indikator"], input:not([name])');
          if (indikatorInput) {
            indikatorInput.name = `tujuan[${tujuanIndex}][indikator_tujuan][${indikatorTujuanIndex}][indikator]`;
          }
        });

        // Update sasaran names
        tujuanItem.querySelectorAll('.sasaran-item').forEach((sasaranItem, sasaranIndex) => {
          const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran_rpjmd"], textarea:not([name])');
          if (sasaranTextarea) {
            sasaranTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][sasaran_rpjmd]`;
          }

          // Update indikator sasaran names
          sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorSasaranItem, indikatorSasaranIndex) => {
            const indikatorInput = indikatorSasaranItem.querySelector('input[name*="indikator"], input:not([name])');
            const satuanInput = indikatorSasaranItem.querySelector('input[name*="satuan"], .row input:not([name]):last-of-type');
            const strategiTextarea = indikatorSasaranItem.querySelector('textarea[name*="strategi"], textarea:not([name])');
            
            if (indikatorInput) {
              indikatorInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][indikator]`;
            }
            if (satuanInput) {
              satuanInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][satuan]`;
            }
            if (strategiTextarea) {
              strategiTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][strategi]`;
            }

            // Update target names for 5-year plan
            indikatorSasaranItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
              const tahunInput = targetItem.querySelector('.tahun-target');
              const targetInput = targetItem.querySelector('.col input[name*="target"]');
              
              if (tahunInput) {
                tahunInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][tahun]`;
              }
              if (targetInput) {
                targetInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][target]`;
              }
            });
          });
        });
      });
    }

    // Tambah Tujuan Baru
    document.getElementById('add-tujuan').addEventListener('click', () => {
      const tujuanContainer = document.getElementById('tujuan-container');
      
      const newTujuan = document.createElement('div');
      newTujuan.className = 'tujuan-item bg-light border rounded p-3 mb-3';
      newTujuan.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Tujuan</label>
          <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        <div class="mb-3">
          <label class="form-label">Tujuan RPJMD</label>
          <textarea class="form-control" rows="2" placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required></textarea>
        </div>

        <div class="indikator-tujuan-section mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Indikator Tujuan</h3>
          </div>
          <div class="indikator-tujuan-container">
            <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Indikator Tujuan</label>
                <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              <div class="mb-3">
                <label class="form-label">Indikator</label>
                <input type="text" class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
            </button>
          </div>
        </div>

        <div class="sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
          </div>
          <div class="sasaran-container">
            <div class="sasaran-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sasaran</label>
                <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              <div class="mb-3">
                <label class="form-label">Sasaran RPJMD</label>
                <textarea class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
              </div>

              <div class="indikator-sasaran-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="fw-medium">Indikator Sasaran</h4>
                </div>
                <div class="indikator-sasaran-container">
                  <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Indikator Sasaran</label>
                      <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-8">
                        <label class="form-label">Indikator</label>
                        <input type="text" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Strategi</label>
                      <textarea class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                    </div>

                    <div class="target-section">
                      <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                      <div class="target-container">
                        <div class="target-item row g-2 align-items-center mb-2">
                          <div class="col-auto">
                            <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                          </div>
                          <div class="col">
                            <input type="text" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                          </div>
                        </div>
                        <div class="target-item row g-2 align-items-center mb-2">
                          <div class="col-auto">
                            <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                          </div>
                          <div class="col">
                            <input type="text" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                          </div>
                        </div>
                        <div class="target-item row g-2 align-items-center mb-2">
                          <div class="col-auto">
                            <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                          </div>
                          <div class="col">
                            <input type="text" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                          </div>
                        </div>
                        <div class="target-item row g-2 align-items-center mb-2">
                          <div class="col-auto">
                            <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                          </div>
                          <div class="col">
                            <input type="text" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                          </div>
                        </div>
                        <div class="target-item row g-2 align-items-center mb-2">
                          <div class="col-auto">
                            <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                          </div>
                          <div class="col">
                            <input type="text" class="form-control form-control-sm" placeholder="Contoh: 85" required>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                  <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="add-sasaran btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran
            </button>
          </div>
        </div>
      `;
      
      tujuanContainer.appendChild(newTujuan);
      updateLabels();
      updateFormNames();
      updateTargetYears(); // Update tahun target untuk elemen baru
    });

    // Fungsi untuk menambahkan indikator tujuan
    function addIndikatorTujuanToTujuan(tujuanElement) {
      const indikatorContainer = tujuanElement.querySelector('.indikator-tujuan-container');
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-tujuan-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Tujuan</label>
          <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        <div class="mb-3">
          <label class="form-label">Indikator</label>
          <input type="text" class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      updateLabels();
      updateFormNames();
    }

    // Fungsi untuk menambahkan sasaran
    function addSasaranToTujuan(tujuanElement) {
      const sasaranContainer = tujuanElement.querySelector('.sasaran-container');
      
      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-item border rounded p-3 bg-white mb-3';
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran</label>
          <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        <div class="mb-3">
          <label class="form-label">Sasaran RPJMD</label>
          <textarea class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
        </div>

        <div class="indikator-sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-medium">Indikator Sasaran</h4>
          </div>
          <div class="indikator-sasaran-container">
            <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Indikator Sasaran</label>
                <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <div class="row">
                <div class="col-md-8">
                  <label class="form-label">Indikator</label>
                  <input type="text" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Satuan</label>
                  <input type="text" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Strategi</label>
                <textarea class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
              </div>

              <div class="target-section">
                <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                <div class="target-container">
                  <div class="target-item row g-2 align-items-center mb-2">
                    <div class="col-auto">
                      <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                    </div>
                    <div class="col">
                      <input type="text" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                    </div>
                  </div>
                  <div class="target-item row g-2 align-items-center mb-2">
                    <div class="col-auto">
                      <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                    </div>
                    <div class="col">
                      <input type="text" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                    </div>
                  </div>
                  <div class="target-item row g-2 align-items-center mb-2">
                    <div class="col-auto">
                      <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                    </div>
                    <div class="col">
                      <input type="text" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                    </div>
                  </div>
                  <div class="target-item row g-2 align-items-center mb-2">
                    <div class="col-auto">
                      <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                    </div>
                    <div class="col">
                      <input type="text" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                    </div>
                  </div>
                  <div class="target-item row g-2 align-items-center mb-2">
                    <div class="col-auto">
                      <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                    </div>
                    <div class="col">
                      <input type="text" class="form-control form-control-sm" placeholder="Contoh: 85" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
      updateTargetYears(); // Update tahun target untuk elemen baru
    }

    // Fungsi untuk menambahkan indikator sasaran
    function addIndikatorSasaranToSasaran(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-container');
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-sasaran-item border rounded p-3 bg-light mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Sasaran</label>
          <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row">
          <div class="col-md-8">
            <label class="form-label">Indikator</label>
            <input type="text" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <input type="text" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
          </div>
        </div>

        <!-- Strategi -->
        <div class="mb-3">
          <label class="form-label">Strategi</label>
          <textarea class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
        </div>

        <div class="target-section">
          <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
          <div class="target-container">
            <div class="target-item row g-2 align-items-center mb-2">
              <div class="col-auto">
                <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
              </div>
              <div class="col">
                <input type="text" class="form-control form-control-sm" placeholder="Contoh: 75" required>
              </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
              <div class="col-auto">
                <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
              </div>
              <div class="col">
                <input type="text" class="form-control form-control-sm" placeholder="Contoh: 77" required>
              </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
              <div class="col-auto">
                <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
              </div>
              <div class="col">
                <input type="text" class="form-control form-control-sm" placeholder="Contoh: 79" required>
              </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
              <div class="col-auto">
                <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
              </div>
              <div class="col">
                <input type="text" class="form-control form-control-sm" placeholder="Contoh: 81" required>
              </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
              <div class="col-auto">
                <input type="number" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
              </div>
              <div class="col">
                <input type="text" class="form-control form-control-sm" placeholder="Contoh: 85" required>
              </div>
            </div>
          </div>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      
      updateLabels();
      updateFormNames();
      updateTargetYears(); // Update tahun target untuk elemen baru
    }

    // Event delegation untuk semua tombol
    document.addEventListener('click', function(e) {
      // Tombol tambah indikator tujuan
      if (e.target.classList.contains('add-indikator-tujuan') || e.target.closest('.add-indikator-tujuan')) {
        const tujuanItem = e.target.closest('.tujuan-item');
        addIndikatorTujuanToTujuan(tujuanItem);
      }
      
      // Tombol tambah sasaran
      if (e.target.classList.contains('add-sasaran') || e.target.closest('.add-sasaran')) {
        const tujuanItem = e.target.closest('.tujuan-item');
        addSasaranToTujuan(tujuanItem);
      }
      
      // Tombol tambah indikator sasaran
      if (e.target.classList.contains('add-indikator-sasaran') || e.target.closest('.add-indikator-sasaran')) {
        const sasaranItem = e.target.closest('.sasaran-item');
        addIndikatorSasaranToSasaran(sasaranItem);
      }
      
      // Tombol hapus tujuan
      if (e.target.classList.contains('remove-tujuan') || e.target.closest('.remove-tujuan')) {
        if (confirm('Hapus tujuan ini dan semua indikator serta sasarannya?')) {
          e.target.closest('.tujuan-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator tujuan
      if (e.target.classList.contains('remove-indikator-tujuan') || e.target.closest('.remove-indikator-tujuan')) {
        if (confirm('Hapus indikator tujuan ini?')) {
          e.target.closest('.indikator-tujuan-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus sasaran
      if (e.target.classList.contains('remove-sasaran') || e.target.closest('.remove-sasaran')) {
        if (confirm('Hapus sasaran ini dan semua indikator sasarannya?')) {
          e.target.closest('.sasaran-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator sasaran
      if (e.target.classList.contains('remove-indikator-sasaran') || e.target.closest('.remove-indikator-sasaran')) {
        if (confirm('Hapus indikator sasaran ini dan target 5 tahunannya?')) {
          e.target.closest('.indikator-sasaran-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
    });

    // Fungsi untuk mengupdate tahun target berdasarkan periode start
    function updateTargetYears() {
      const periodeStart = parseInt(document.getElementById('periode_start').value);
      if (periodeStart && !isNaN(periodeStart)) {
        // Update semua tahun target untuk setiap indikator sasaran
        document.querySelectorAll('.indikator-sasaran-item').forEach((indikatorItem) => {
          const targetItems = indikatorItem.querySelectorAll('.target-item');
          targetItems.forEach((targetItem, index) => {
            const tahunInput = targetItem.querySelector('.tahun-target');
            if (tahunInput) {
              tahunInput.value = periodeStart + index;
            }
          });
        });
      }
    }

    // Event listener untuk auto-fill periode akhir dan update target years
    document.addEventListener('input', function(e) {
      if (e.target.id === 'periode_start') {
        const periodeStart = parseInt(e.target.value);
        if (periodeStart && !isNaN(periodeStart)) {
          // Auto-fill periode akhir
          document.getElementById('periode_end').value = periodeStart + 4;
          // Update semua tahun target
          updateTargetYears();
        }
      }
    });

    // Initialize pada load
    document.addEventListener('DOMContentLoaded', function() {
      updateLabels();
      updateFormNames();
      updateTargetYears(); // Initialize target years pada load
    });
  </script>
</body>
</html>
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
            <textarea name="misi_rpjmd" class="form-control mb-3" rows="2" required></textarea>
          </div>
          <div class="col-md-2">
            <label class="form-label">Periode Mulai</label>
            <input type="number" name="periode_start" class="form-control mb-3" value="2025" placeholder="Tahun" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Periode Akhir</label>
            <input type="number" name="periode_end" class="form-control mb-3" value="2030" placeholder="Tahun" required>
          </div>
        </div>
      </section>

      <!-- Daftar Tujuan -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 fw-semibold">Daftar Tujuan</h2>
          <button type="button" id="add-tujuan" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Tujuan
          </button>
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
              <textarea name="tujuan[0][tujuan_rpjmd]" class="form-control" rows="2" required></textarea>
            </div>

            <!-- Indikator Tujuan -->
            <div class="indikator-tujuan-section mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-medium">Indikator Tujuan</h3>
                <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
                </button>
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
                    <input type="text" name="tujuan[0][indikator_tujuan][0][indikator]" class="form-control" value="Indeks Demokrasi Indonesia (IDI)" required>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sasaran -->
            <div class="sasaran-section">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
                <button type="button" class="add-sasaran btn btn-success btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Sasaran
                </button>
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
                    <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" required></textarea>
                  </div>

                  <!-- Indikator Sasaran -->
                  <div class="indikator-sasaran-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h4 class="fw-medium">Indikator Sasaran</h4>
                      <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                      </button>
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
                            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator]" class="form-control mb-3" value="Jumlah tempat ibadah yang aktif" required>
                          </div>
                          <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-control mb-3" value="Unit" placeholder="Masukkan satuan" required>
                          </div>
                        </div>

                        <!-- Strategi -->
                        <div class="mb-3">
                          <label class="form-label">Strategi</label>
                          <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Masukkan strategi untuk mencapai indikator sasaran" required></textarea>
                        </div>

                        <!-- Target Tahunan -->
                        <div class="target-section">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-medium">Target Tahunan</h5>
                            <button type="button" class="add-target btn btn-secondary btn-sm">
                              <i class="fas fa-plus me-1"></i> Tambah Target Tahun
                            </button>
                          </div>

                          <div class="target-container">
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" value="2019" class="form-control form-control-sm" style="width: 80px;" placeholder="Tahun" required>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target]" value="150" class="form-control form-control-sm" placeholder="Target" required>
                              </div>
                              <div class="col-auto">
                                <button type="button" class="remove-target btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
                              </div>
                            </div>

                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" value="2020" class="form-control form-control-sm" style="width: 80px;" placeholder="Tahun" required>
                              </div>
                              <div class="col">
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target]" value="155" class="form-control form-control-sm" placeholder="Target" required>
                              </div>
                              <div class="col-auto">
                                <button type="button" class="remove-target btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div> <!-- End Indikator Sasaran -->
                    </div> <!-- End Indikator Sasaran Container -->
                  </div> <!-- End Indikator Sasaran Section -->
                </div> <!-- End Sasaran -->
              </div> <!-- End Sasaran Container -->
            </div> <!-- End Sasaran Section -->
          </div> <!-- End Tujuan -->
        </div> <!-- End Tujuan Container -->
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

            // Update target tahunan names
            indikatorSasaranItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
              const tahunInput = targetItem.querySelector('input[name*="tahun"], input[type="number"]');
              const targetInput = targetItem.querySelector('input[name*="target"], .col input');
              
              if (tahunInput) {
                tahunInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][tahun]`;
              }
              if (targetInput && !targetInput.name.includes('tahun')) {
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
          <textarea class="form-control" rows="2" placeholder="Masukkan tujuan RPJMD" required></textarea>
        </div>

        <div class="indikator-tujuan-section mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Indikator Tujuan</h3>
            <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
            </button>
          </div>
          <div class="indikator-tujuan-container"></div>
        </div>

        <div class="sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
            <button type="button" class="add-sasaran btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran
            </button>
          </div>
          <div class="sasaran-container"></div>
        </div>
      `;
      
      tujuanContainer.appendChild(newTujuan);
      updateLabels();
      updateFormNames();
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
          <input type="text" class="form-control" placeholder="Masukkan indikator tujuan" required>
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
          <textarea class="form-control" rows="2" placeholder="Masukkan sasaran RPJMD" required></textarea>
        </div>

        <div class="indikator-sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-medium">Indikator Sasaran</h4>
            <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
          <div class="indikator-sasaran-container"></div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
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
            <input type="text" class="form-control mb-3" placeholder="Masukkan indikator sasaran" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <input type="text" class="form-control mb-3" placeholder="Masukkan satuan" required>
          </div>
        </div>

        <!-- Strategi -->
        <div class="mb-3">
          <label class="form-label">Strategi</label>
          <textarea class="form-control mb-3" rows="3" placeholder="Masukkan strategi untuk mencapai indikator sasaran" required></textarea>
        </div>

        <div class="target-section">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="fw-medium">Target Tahunan</h5>
            <button type="button" class="add-target btn btn-secondary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Target Tahun
            </button>
          </div>
          <div class="target-container"></div>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      updateLabels();
      updateFormNames();
    }

    // Fungsi untuk menambahkan target tahunan
    function addTargetToIndikatorSasaran(indikatorElement) {
      const targetContainer = indikatorElement.querySelector('.target-container');
      
      const newTarget = document.createElement('div');
      newTarget.className = 'target-item row g-2 align-items-center mb-2';
      newTarget.innerHTML = `
        <div class="col-auto">
          <input type="number" class="form-control form-control-sm" placeholder="Tahun" style="width: 80px;" required>
        </div>
        <div class="col">
          <input type="text" class="form-control form-control-sm" placeholder="Target" required>
        </div>
        <div class="col-auto">
          <button type="button" class="remove-target btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
        </div>
      `;
      
      targetContainer.appendChild(newTarget);
      updateFormNames();
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
      
      // Tombol tambah target tahunan
      if (e.target.classList.contains('add-target') || e.target.closest('.add-target')) {
        const indikatorItem = e.target.closest('.indikator-sasaran-item');
        addTargetToIndikatorSasaran(indikatorItem);
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
        if (confirm('Hapus indikator sasaran ini dan semua target tahunannya?')) {
          e.target.closest('.indikator-sasaran-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus target tahunan
      if (e.target.classList.contains('remove-target') || e.target.closest('.remove-target')) {
        e.target.closest('.target-item').remove();
        updateFormNames();
      }
    });

    // Initialize pada load
    document.addEventListener('DOMContentLoaded', function() {
      updateLabels();
      updateFormNames();
    });
  </script>
</body>
</html>
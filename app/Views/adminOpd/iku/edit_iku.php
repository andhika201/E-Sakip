<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit IKU e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit IKU</h2>

      <form id="iku-form" method="POST" action="<?= base_url('adminopd/iku/save') ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran Renstra Terkait IKU ini</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran Renstra</label>
            <select name="renstra_sasaran_id" class="form-select mb-3" required>
              <option value="">Pilih Sasaran Renstra</option>
              <!-- Options akan diisi dari database -->
              <option value="1">Meningkatnya kualitas pelayanan publik</option>
              <option value="2">Meningkatnya transparansi pengelolaan keuangan</option>
              <option value="3">Meningkatnya kompetensi ASN</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran IKU -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran IKU</h2>
          <button type="button" id="add-sasaran-iku" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran IKU
          </button>
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
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h5 fw-medium">Indikator Kinerja</h4>
                <button type="button" class="add-indikator-kinerja btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Kinerja
                </button>
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
                      <select name="sasaran_iku[0][indikator_kinerja][0][satuan]" class="form-control" required>
                        <option value="">Pilih Satuan</option>
                        <!-- Options akan diisi dari database -->
                        <option value="Persen">Persen</option>
                        <option value="Nilai">Nilai</option>
                        <option value="Predikat">Predikat</option>
                        <option value="Unit">Unit</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Program Pendukung</label>
                      <textarea name="sasaran_iku[0][indikator_kinerja][0][program_pendukung]" class="form-control" rows="2" placeholder="Masukkan program pendukung" required></textarea>
                    </div>
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
                          <input type="number" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][0][tahun]" value="2025" class="form-control form-control-sm" style="width: 80px;" placeholder="Tahun" required>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_iku[0][indikator_kinerja][0][target_tahunan][0][target]" class="form-control form-control-sm" placeholder="Target" required>
                        </div>
                        <div class="col-auto">
                          <button type="button" class="remove-target btn btn-outline-danger btn-sm"><i class="fas fa-times"></i></button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- End Indikator Kinerja -->
              </div> <!-- End Indikator Kinerja Container -->
            </div> <!-- End Indikator Kinerja Section -->
          </div> <!-- End Sasaran IKU -->
        </div> <!-- End Sasaran IKU Container -->
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminopd/iku') ?>" class="btn btn-secondary">
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
    // Fungsi untuk mengupdate penomoran label secara real-time
    function updateLabels() {
      document.querySelectorAll('.sasaran-iku-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;
        
        // Update label sasaran IKU
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
          sasaranLabel.textContent = `Sasaran IKU ${sasaranNumber}`;
        }

        // Update indikator kinerja labels
        sasaranItem.querySelectorAll('.indikator-kinerja-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorNumber = indikatorIndex + 1;
          const indikatorLabel = indikatorItem.querySelector('label');
          if (indikatorLabel) {
            indikatorLabel.textContent = `Indikator Kinerja ${sasaranNumber}.${indikatorNumber}`;
          }
        });
      });
    }

    // Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
    function updateFormNames() {
      document.querySelectorAll('.sasaran-iku-item').forEach((sasaranItem, sasaranIndex) => {
        // Update sasaran IKU names
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
        
        if (sasaranTextarea) {
          sasaranTextarea.name = `sasaran_iku[${sasaranIndex}][sasaran]`;
        }

        // Update indikator kinerja names
        sasaranItem.querySelectorAll('.indikator-kinerja-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_kinerja"]');
          const definisiTextarea = indikatorItem.querySelector('textarea[name*="definisi_formulasi"]');
          const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
          const programTextarea = indikatorItem.querySelector('textarea[name*="program_pendukung"]');
          
          if (indikatorTextarea) {
            indikatorTextarea.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][indikator_kinerja]`;
          }
          if (definisiTextarea) {
            definisiTextarea.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][definisi_formulasi]`;
          }
          if (satuanSelect) {
            satuanSelect.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][satuan]`;
          }
          if (programTextarea) {
            programTextarea.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][program_pendukung]`;
          }

          // Update target tahunan names
          indikatorItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
            const tahunInput = targetItem.querySelector('input[name*="tahun"], input[type="number"]');
            const targetInput = targetItem.querySelector('input[name*="target"]:not([name*="tahun"])');
            
            if (tahunInput) {
              tahunInput.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][tahun]`;
            }
            if (targetInput) {
              targetInput.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][target]`;
            }
          });
        });
      });
    }

    // Tambah Sasaran IKU Baru
    document.getElementById('add-sasaran-iku').addEventListener('click', () => {
      const sasaranContainer = document.getElementById('sasaran-iku-container');
      
      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-iku-item bg-light border rounded p-3 mb-3';
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran IKU</label>
          <button type="button" class="remove-sasaran-iku btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran IKU</label>
            <textarea class="form-control" rows="2" placeholder="Masukkan sasaran IKU" required></textarea>
          </div>
        </div>

        <div class="indikator-kinerja-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 fw-medium">Indikator Kinerja</h4>
            <button type="button" class="add-indikator-kinerja btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Kinerja
            </button>
          </div>
          <div class="indikator-kinerja-container"></div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
    });

    // Fungsi untuk menambahkan indikator kinerja
    function addIndikatorKinerja(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-kinerja-container');
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-kinerja-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Kinerja</label>
          <button type="button" class="remove-indikator-kinerja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Indikator Kinerja</label>
          <textarea class="form-control" rows="2" placeholder="Masukkan indikator kinerja" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Definisi & Formulasi</label>
          <textarea class="form-control" rows="3" placeholder="Masukkan definisi dan formulasi indikator" required></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Satuan</label>
            <select class="form-control" required>
              <option value="">Pilih Satuan</option>
              <option value="Persen">Persen</option>
              <option value="Nilai">Nilai</option>
              <option value="Predikat">Predikat</option>
              <option value="Unit">Unit</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Program Pendukung</label>
            <textarea class="form-control" rows="2" placeholder="Masukkan program pendukung" required></textarea>
          </div>
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
    function addTargetToIndikatorKinerja(indikatorElement) {
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
      // Tombol tambah indikator kinerja
      if (e.target.classList.contains('add-indikator-kinerja') || e.target.closest('.add-indikator-kinerja')) {
        const sasaranItem = e.target.closest('.sasaran-iku-item');
        addIndikatorKinerja(sasaranItem);
      }
      
      // Tombol tambah target tahunan
      if (e.target.classList.contains('add-target') || e.target.closest('.add-target')) {
        const indikatorItem = e.target.closest('.indikator-kinerja-item');
        addTargetToIndikatorKinerja(indikatorItem);
      }
      
      // Tombol hapus sasaran IKU
      if (e.target.classList.contains('remove-sasaran-iku') || e.target.closest('.remove-sasaran-iku')) {
        if (confirm('Hapus sasaran IKU ini dan semua indikator kinerjanya?')) {
          e.target.closest('.sasaran-iku-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator kinerja
      if (e.target.classList.contains('remove-indikator-kinerja') || e.target.closest('.remove-indikator-kinerja')) {
        if (confirm('Hapus indikator kinerja ini dan semua target tahunannya?')) {
          e.target.closest('.indikator-kinerja-item').remove();
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
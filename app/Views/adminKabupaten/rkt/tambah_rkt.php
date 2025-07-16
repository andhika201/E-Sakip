<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah RKT e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RKT</h2>

      <form id="rkt-form" method="POST" action="<?= base_url('adminkab/rkt/save') ?>">
        <?= csrf_field() ?>

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran RPJMD Terkait RKT ini</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RPJMD</label>
            <select name="rpjmd_sasaran_id" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RPJMD</option>
              <!-- Options akan diisi dari database -->
              <option value="1">Meningkatnya kualitas pelayanan publik</option>
              <option value="2">Meningkatnya transparansi pengelolaan keuangan</option>
              <option value="3">Meningkatnya kompetensi ASN</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran RKT -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran RKT</h2>
          <button type="button" id="add-sasaran-rkt" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RKT
          </button>
        </div>

        <div id="sasaran-rkt-container">
          <!-- Sasaran RKT 1 -->
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
                <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
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
                      <select name="sasaran_rkt[0][indikator_sasaran][0][satuan]" class="form-control" placeholder="Unit/Persen/dll" required>
                        <option value="">Pilih Satuan</option>
                        <!-- Options akan diisi dari database -->
                        <option value="1">Persen</option>
                        <option value="2">Nilai</option>
                        <option value="3">Predikat</option>
                        <option value="4">Unit</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Tahun Target</label>
                      <select name="sasaran_rkt[0][indikator_sasaran][0][tahun]" class="form-select" required>
                        <option value="">Pilih Tahun</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
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
          </div> <!-- End Sasaran RKT -->
        </div> <!-- End Sasaran RKT Container -->
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminkab/rkt') ?>" class="btn btn-secondary">
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
   </div> <!-- End Content Wrapper -->


  <script>
    // Fungsi untuk mengupdate penomoran label secara real-time
    function updateLabels() {
      document.querySelectorAll('.sasaran-rkt-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;
        
        // Update label sasaran RKT
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
          sasaranLabel.textContent = `Sasaran RKT ${sasaranNumber}`;
        }

        // Update indikator sasaran RKT labels
        sasaranItem.querySelectorAll('.indikator-sasaran-rkt-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorNumber = indikatorIndex + 1;
          const indikatorLabel = indikatorItem.querySelector('label');
          if (indikatorLabel) {
            indikatorLabel.textContent = `Indikator Sasaran ${sasaranNumber}.${indikatorNumber}`;
          }
        });
      });
    }

    // Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
    function updateFormNames() {
      document.querySelectorAll('.sasaran-rkt-item').forEach((sasaranItem, sasaranIndex) => {
        // Update sasaran RKT names
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
        
        if (sasaranTextarea) {
          sasaranTextarea.name = `sasaran_rkt[${sasaranIndex}][sasaran]`;
        }

        // Update indikator sasaran RKT names
        sasaranItem.querySelectorAll('.indikator-sasaran-rkt-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_sasaran"]');
          const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
          const tahunSelect = indikatorItem.querySelector('select[name*="tahun"]');
          const targetInput = indikatorItem.querySelector('input[name*="target"]');
          
          if (indikatorTextarea) {
            indikatorTextarea.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][indikator_sasaran]`;
          }
          if (satuanSelect) {
            satuanSelect.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][satuan]`;
          }
          if (tahunSelect) {
            tahunSelect.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][tahun]`;
          }
          if (targetInput) {
            targetInput.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][target]`;
          }
        });
      });
    }

    // Tambah Sasaran RKT Baru
    document.getElementById('add-sasaran-rkt').addEventListener('click', () => {
      const sasaranContainer = document.getElementById('sasaran-rkt-container');
      
      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-rkt-item bg-light border rounded p-3 mb-3';
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran RKT</label>
          <button type="button" class="remove-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran RKT</label>
            <textarea class="form-control" rows="2" placeholder="Masukkan sasaran RKT" required></textarea>
          </div>
        </div>

        <div class="indikator-sasaran-rkt-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 fw-medium">Indikator Sasaran RKT</h4>
            <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
          <div class="indikator-sasaran-rkt-container"></div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
    });

    // Fungsi untuk menambahkan indikator sasaran RKT
    function addIndikatorSasaranRKT(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-rkt-container');
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Sasaran</label>
          <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Indikator Sasaran</label>
          <textarea class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <select class="form-control" required>
              <option value="">Pilih Satuan</option>
              <option value="1">Persen</option>
              <option value="2">Nilai</option>
              <option value="3">Predikat</option>
              <option value="4">Unit</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tahun Target</label>
            <select class="form-select" required>
              <option value="">Pilih Tahun</option>
              <option value="2025">2025</option>
              <option value="2026">2026</option>
              <option value="2027">2027</option>
              <option value="2028">2028</option>
              <option value="2029">2029</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Target</label>
            <input type="text" class="form-control" placeholder="Nilai target" required>
          </div>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      updateLabels();
      updateFormNames();
    }

    // Event delegation untuk semua tombol
    document.addEventListener('click', function(e) {
      // Tombol tambah indikator sasaran RKT
      if (e.target.classList.contains('add-indikator-sasaran-rkt') || e.target.closest('.add-indikator-sasaran-rkt')) {
        const sasaranItem = e.target.closest('.sasaran-rkt-item');
        addIndikatorSasaranRKT(sasaranItem);
      }
      
      // Tombol hapus sasaran RKT
      if (e.target.classList.contains('remove-sasaran-rkt') || e.target.closest('.remove-sasaran-rkt')) {
        if (confirm('Hapus sasaran RKT ini dan semua indikator sasarannya?')) {
          e.target.closest('.sasaran-rkt-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator sasaran RKT
      if (e.target.classList.contains('remove-indikator-sasaran-rkt') || e.target.closest('.remove-indikator-sasaran-rkt')) {
        if (confirm('Hapus indikator sasaran ini?')) {
          e.target.closest('.indikator-sasaran-rkt-item').remove();
          updateLabels();
          updateFormNames();
        }
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
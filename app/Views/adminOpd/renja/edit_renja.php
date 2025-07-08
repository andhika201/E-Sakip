<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit RENJA e-SAKIP</title>
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

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/renja/save') ?>">

        <!-- Informasi Umum -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Sasaran RENSTRA Terkait RENJA Ini</h2>
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Sasaran RENSTRA</label>
              <select name="renstra_sasaran_id" class="form-select mb-3" required>
                <option value="">Pilih Sasaran RENSTRA</option>
                <!-- Options akan diisi dari database -->
                <option value="1">Meningkatnya kualitas pelayanan publik</option>
                <option value="2">Meningkatnya transparansi pengelolaan keuangan</option>
                <option value="3">Meningkatnya kompetensi ASN</option>
              </select>
            </div>
          </div>
        </section>

        <!-- Daftar Sasaran renja -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-semibold">Daftar Sasaran RENJA</h2>

          </div>

          <div id="sasaran-renja-container">
            <!-- Sasaran RENJA 1 -->
            <div class="sasaran-renja-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sasaran RENJA 1</label>
                <button type="button" class="remove-sasaran-renja btn btn-outline-danger btn-sm"><i
                    class="fas fa-trash"></i></button>
              </div>

              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran RENJA</label>
                  <textarea name="renja_sasaran[0][sasaran]" class="form-control" rows="2"
                    placeholder="Masukkan sasaran RENJA" required></textarea>
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
                      <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i
                          class="fas fa-trash"></i></button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Indikator Sasaran</label>
                      <textarea name="renja_sasaran[0][indikator_sasaran][0][indikator_sasaran]" class="form-control"
                        rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                    </div>

                    <div class="row mb-3">
                      <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select name="renja_sasaran[0][indikator_sasaran][0][satuan]" class="form-control" required>
                          <option value="">Pilih Satuan</option>
                          <!-- Options akan diisi dari database -->
                          <option value="Persen">Persen</option>
                          <option value="Nilai">Nilai</option>
                          <option value="Predikat">Predikat</option>
                          <option value="Unit">Unit</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tahun Target</label>
                        <input type="number" name="renja_sasaran[0][indikator_sasaran][0][tahun]" class="form-control"
                          min="2020" max="2050" placeholder="Contoh: 2025" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <input type="text" name="renja_sasaran[0][indikator_sasaran][0][target]" class="form-control"
                          placeholder="Nilai target" required>
                      </div>
                    </div>
                  </div> <!-- End Indikator Sasaran RENJA -->
                </div> <!-- End Indikator Sasaran RENJA Container -->
              </div> <!-- End Indikator Sasaran RENJA Section -->
              <button type="button" class="add-indikator-sasaran-renja btn btn-info btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
              </button>
            </div> <!-- End Sasaran renja -->
          </div> <!-- End Sasaran renja Container -->
        </section>
        <div class="text-end mb-5">
          <button type="button" id="add-sasaran-renja" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RENJA
          </button>
        </div>


        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/renja') ?>" class="btn btn-secondary">
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
      document.querySelectorAll('.sasaran-renja-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;

        // Update label sasaran RENJA
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
          sasaranLabel.textContent = `Sasaran RENJA ${sasaranNumber}`;
        }

        // Update indikator sasaran RENJA labels
        sasaranItem.querySelectorAll('.indikator-sasaran-renja-item').forEach((indikatorItem, indikatorIndex) => {
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
      document.querySelectorAll('.sasaran-renja-item').forEach((sasaranItem, sasaranIndex) => {
        // Update sasaran RENJA names
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');

        if (sasaranTextarea) {
          sasaranTextarea.name = `renja_sasaran[${sasaranIndex}][sasaran]`;
        }

        // Update indikator sasaran RENJA names
        sasaranItem.querySelectorAll('.indikator-sasaran-renja-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_sasaran"]');
          const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
          const tahunInput = indikatorItem.querySelector('input[name*="tahun"], input[type="number"]');
          const targetInput = indikatorItem.querySelector('input[name*="target"]');

          if (indikatorTextarea) {
            indikatorTextarea.name = `renja_sasaran[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][indikator_sasaran]`;
          }
          if (satuanSelect) {
            satuanSelect.name = `renja_sasaran[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][satuan]`;
          }
          if (tahunInput) {
            tahunInput.name = `renja_sasaran[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][tahun]`;
          }
          if (targetInput && !targetInput.name.includes('tahun')) {
            targetInput.name = `renja_sasaran[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][target]`;
          }
        });
      });
    }

    // Tambah Sasaran RENJA Baru
    document.getElementById('add-sasaran-renja').addEventListener('click', () => {
      const sasaranContainer = document.getElementById('sasaran-renja-container');

      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-renja-item bg-light border rounded p-3 mb-3';
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran RENJA</label>
          <button type="button" class="remove-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran RENJA</label>
            <textarea class="form-control" rows="2" placeholder="Masukkan sasaran RENJA" required></textarea>
          </div>
        </div>

        <div class="indikator-sasaran-renja-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 fw-medium">Indikator Sasaran RENJA</h4>
            <button type="button" class="add-indikator-sasaran-renja btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
          <div class="indikator-sasaran-renja-container"></div>
        </div>
      `;

      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
    });

    // Fungsi untuk menambahkan indikator sasaran RENJA
    function addIndikatorSasaranrenja(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-renja-container');

      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-sasaran-renja-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Sasaran</label>
          <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
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
              <option value="Persen">Persen</option>
              <option value="Nilai">Nilai</option>
              <option value="Predikat">Predikat</option>
              <option value="Unit">Unit</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tahun Target</label>
            <input type="number" class="form-control" min="2020" max="2050" placeholder="Contoh: 2025" required>
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
    document.addEventListener('click', function (e) {
      // Tombol tambah indikator sasaran RENJA
      if (e.target.classList.contains('add-indikator-sasaran-renja') || e.target.closest('.add-indikator-sasaran-renja')) {
        const sasaranItem = e.target.closest('.sasaran-renja-item');
        addIndikatorSasaranrenja(sasaranItem);
      }

      // Tombol hapus sasaran RENJA
      if (e.target.classList.contains('remove-sasaran-renja') || e.target.closest('.remove-sasaran-renja')) {
        if (confirm('Hapus sasaran RENJA ini dan semua indikator sasarannya?')) {
          e.target.closest('.sasaran-renja-item').remove();
          updateLabels();
          updateFormNames();
        }
      }

      // Tombol hapus indikator sasaran RENJA
      if (e.target.classList.contains('remove-indikator-sasaran-renja') || e.target.closest('.remove-indikator-sasaran-renja')) {
        if (confirm('Hapus indikator sasaran ini?')) {
          e.target.closest('.indikator-sasaran-renja-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
    });

    // Initialize pada load
    document.addEventListener('DOMContentLoaded', function () {
      updateLabels();
      updateFormNames();
    });
  </script>
</body>

</html>
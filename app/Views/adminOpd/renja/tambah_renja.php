<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title) ?></title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RENJA</h2>

       <!-- Alert Container -->
      <div id="alert-container"></div>

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/renja/save') ?>">
        <?= csrf_field() ?>

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran RENSTRA Terkait RENJA ini</h2>
        <div class="row">
          <div class="col-md-6">
              <label class="form-label">Sasaran RENSTRA Terkait</label>
              <select name="renstra_sasaran_id" id="renstra-sasaran-select" class="form-select mb-3" required>
                <option value="">Pilih Sasaran RENSTRA</option>
                <?php if (isset($renstra_sasaran) && !empty($renstra_sasaran)): ?>
                  <?php foreach ($renstra_sasaran as $sasaran): ?>
                    <option value="<?= $sasaran['id'] ?>" data-tahun-mulai="<?= $sasaran['tahun_mulai'] ?>" data-tahun-akhir="<?= $sasaran['tahun_akhir'] ?>">
                      <?= esc($sasaran['sasaran']) ?> (Periode: <?= $sasaran['tahun_mulai'] ?>-<?= $sasaran['tahun_akhir'] ?>)
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="" disabled>Tidak ada sasaran RENSTRA yang tersedia</option>
                <?php endif; ?>
              </select>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran RENJA -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran RENJA</h2>
        </div>

        <div id="sasaran-renja-container">
          <!-- Sasaran RENJA 1 -->
          <div class="sasaran-renja-item bg-light border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium">Sasaran RENJA 1</label>
              <button type="button" class="remove-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Sasaran RENJA</label>
                <textarea name="sasaran_renja[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RENJA" required></textarea>
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
                    <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Indikator Sasaran</label>
                    <textarea name="sasaran_renja[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label class="form-label">Satuan</label>
                      <select name="sasaran_renja[0][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
                        <option value="">Pilih Satuan</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Tahun Target</label>
                      <select name="sasaran_renja[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                        <option value="">Pilih Sasaran RENSTRA terlebih dahulu</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Target</label>
                      <input type="text" name="sasaran_renja[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                    </div>
                  </div>
                </div> <!-- End Indikator Sasaran RENJA -->
              </div> <!-- End Indikator Sasaran RENJA Container -->
            </div> <!-- End Indikator Sasaran RENJA Section -->

            <!-- Tombol Tambah Indikator Sasaran -->
            <div class="d-flex justify-content-end">
              <button type="button" class="add-indikator-sasaran-renja btn btn-info btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
              </button>
            </div>

          </div> <!-- End Sasaran RENJA -->
        </div> <!-- End Sasaran RENJA Container -->

        <!-- Tombol Tambah Sasaran RENJA -->
        <div class="d-flex justify-content-end">
          <button type="button" id="add-sasaran-renja" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RENJA
          </button>
        </div>

      </section>

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
    $(document).ready(function() {
      // Initialize Select2 for existing elements
      initializeSelect2();
      
      // Function to initialize Select2 on elements
      function initializeSelect2() {
        // Initialize Select2 for RENSTRA Sasaran dropdown
        $('#renstra-sasaran-select').select2({
          theme: 'bootstrap-5',
          placeholder: "Pilih atau ketik untuk mencari sasaran RENSTRA ...",
          allowClear: true,
          width: '100%'
        });
      }
      
      // Handle selection change
      $('#renstra-sasaran-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        
        if (selectedOption.val()) {
          console.log('Selected Sasaran:', {
            id: selectedOption.val(),
            sasaran: selectedOption.text()
          });
          
          // Show selected info (optional)
          showSelectedInfo(selectedOption.text());
          
          // Trigger tahun dropdown update
          if (typeof updateAllTahunDropdowns === 'function') {
            updateAllTahunDropdowns();
          }
        }
      });
      
      // Function to show selected sasaran info
      function showSelectedInfo(sasaran) {
        var infoHtml = '<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">' +
        '<strong>Sasaran RENSTRA Terpilih:</strong> ' + sasaran +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>';
        
        $('#alert-container').html(infoHtml);
      }
    });
    
    document.addEventListener('DOMContentLoaded', function() {
      // Isi semua dropdown satuan dengan class 'satuan-select'
      const satuanSelects = document.querySelectorAll('.satuan-select');
      satuanSelects.forEach(select => {
        if (typeof generateSatuanOptions === 'function') {
          select.innerHTML = generateSatuanOptions();
        }
      });
    });
    </script>

  <script src="<?= base_url('/assets/js/adminOpd/renja/renja-form.js')?>"></script>
</body>
</html>
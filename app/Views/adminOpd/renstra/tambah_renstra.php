<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah Renstra e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <style>
    .alert {
      transition: all 0.3s ease;
    }
    
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    .target-years-container .col-md-2 {
      margin-bottom: 10px;
    }

  </style>
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

      <!-- Alert Container -->
      <div id="alert-container"></div>

      <form id="renstra-form" method="POST" action="<?= base_url('adminopd/renstra/save') ?>">

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Informasi Umum Renstra</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RPJMD Terkait</label>
            <select name="rpjmd_sasaran_id" id="rpjmd-sasaran-select" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RPJMD</option>
              <?php if (isset($rpjmd_sasaran) && !empty($rpjmd_sasaran)): ?>
                <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" data-tahun-mulai="<?= $sasaran['tahun_mulai'] ?>" data-tahun-akhir="<?= $sasaran['tahun_akhir'] ?>">
                    <?= esc($sasaran['sasaran_rpjmd']) ?> (Periode: <?= $sasaran['tahun_mulai'] ?>-<?= $sasaran['tahun_akhir'] ?>)
                  </option>
                <?php endforeach; ?>
              <?php else: ?>  
                <option value="" disabled>Tidak ada sasaran RPJMD yang tersedia</option>
              <?php endif; ?>
            </select>
            <small class="text-muted">Ketik untuk mencari sasaran RPJMD...</small>
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

      <!-- Daftar Sasaran Renstra -->
      <section>
        <h2 class="h5 fw-semibold mb-3">Daftar Sasaran Renstra</h2>

        <div id="sasaran-renstra-container">
          <!-- Sasaran Renstra 1 -->
          <div class="sasaran-renstra-item bg-light border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium sasaran-title">Sasaran Renstra 1</label>
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
              <h4 class="h5 fw-medium mb-3">Indikator Sasaran</h4>

              <div class="indikator-sasaran-container">
                <!-- Indikator Sasaran 1.1 -->
                <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium indikator-title">Indikator Sasaran 1.1</label>
                    <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Indikator Sasaran</label>
                    <textarea name="sasaran_renstra[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Satuan</label>
                      <select name="sasaran_renstra[0][indikator_sasaran][0][satuan]" id="satuanSelect" class="form-control satuan-select" required>
                        <option value="">Pilih Satuan</option>
                      </select>
                    </div>
                  </div>
                  
                  <!-- Target Tahunan -->
                  <div class="target-section">
                    <h5 class="fw-medium mb-3">Target Tahunan</h5>
                    <div class="target-container">
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][tahun]" value="" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][target]" value="" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- End Indikator Sasaran -->
              </div> <!-- End Indikator Sasaran Container -->
              
              <!-- Tombol Tambah Indikator Sasaran -->
              <div class="text-end mt-3">
                <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
              </div>
            </div> <!-- End Indikator Sasaran Section -->
          </div> <!-- End Sasaran Renstra -->
        </div> <!-- End Sasaran Renstra Container -->
        
        <!-- Tombol Tambah Sasaran Renstra -->
        <div class="text-end mt-3">
          <button type="button" id="add-sasaran-renstra" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran Renstra
          </button>
        </div>
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
  <script src="<?= base_url('assets/js/adminOpd/renstra/renstra-form.js') ?>"></script>
  
  <script>
    $(document).ready(function() {
      // Initialize Select2 for existing elements
      initializeSelect2();

      // Function to initialize Select2 on elements
      function initializeSelect2() {
        // Initialize Select2 for RPJMD Sasaran dropdown
        $('#rpjmd-sasaran-select').select2({
          theme: 'bootstrap-5',
          placeholder: "Pilih atau ketik untuk mencari sasaran RPJMD...",
          allowClear: true,
          width: '100%'
        });
      }

      // Re-initialize Select2 when new elements are added
      $(document).on('click', '.add-indikator-sasaran, #add-sasaran-renstra', function() {
        setTimeout(function() {
          initializeSelect2();
        }, 100);
      });

      // Handle selection change
      $('#rpjmd-sasaran-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        
        if (selectedOption.val()) {
          
          // Show selected info (optional)
          showSelectedInfo(selectedOption.text());
        }
      });

      // Function to show selected sasaran info
      function showSelectedInfo(sasaran) {
        var infoHtml = '<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">' +
          '<strong>Sasaran RPJMD Terpilih:</strong> ' + sasaran +
          '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>';
        
        $('#alert-container').html(infoHtml);
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      const select = document.getElementById('satuanSelect');
      if (select && typeof generateSatuanOptions === 'function') {
        select.innerHTML = generateSatuanOptions();
      }
    });
  </script>
</body>
</html>

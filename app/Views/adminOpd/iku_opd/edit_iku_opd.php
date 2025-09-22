<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit IKU OPD e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .is-invalid {
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
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

    /* Custom Select2 styles */
    .select2-container--default .select2-selection--single {
      height: 38px;
      line-height: 36px;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      padding-left: 12px;
      padding-right: 20px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px;
    }
    
    .select2-dropdown {
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
    }
    
    .select2-search--dropdown .select2-search__field {
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      padding: 8px 12px;
    }
    
    .select2-results__option--highlighted {
      background-color: #007bff;
      color: white;
    }
    
    .select2-container--default .select2-results__option[aria-selected=true] {
      background-color: #6c757d;
      color: white;
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit IKU OPD</h2>

      <!-- Alert Container -->
      <div id="alert-container"></div>

      <form id="iku-opd-form" method="POST" action="<?= base_url('adminopd/iku_opd/update/' . ($iku_data['sasaran_id'] ?? '')) ?>">
      <?= csrf_field() ?>

        <!-- Hidden fields untuk mode edit -->
        <input type="hidden" name="mode" value="edit">

        <!-- Informasi Umum -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum IKU OPD</h2>
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Sasaran Renstra Terkait</label>
              <select name="renstra_sasaran_id" id="renstra_sasaran_select" class="form-select mb-3" required>
                <option value="">Pilih Sasaran Renstra</option>
                <?php if (isset($renstra_sasaran) && !empty($renstra_sasaran)): ?>
                  <?php foreach ($renstra_sasaran as $sasaran): ?>
                    <option value="<?= $sasaran['id'] ?>" <?= $sasaran['id'] == $iku_data['renstra_sasaran_id'] ? 'selected' : '' ?>>
                      <?= esc($sasaran['sasaran_renstra']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="" disabled>Tidak ada sasaran renstra yang tersedia</option>
                <?php endif; ?>
              </select>
              <small class="text-muted">Ketik untuk mencari sasaran renstra...</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tahun Mulai</label>
              <input type="number" name="tahun_mulai" id="tahun_mulai" class="form-control mb-3" value="<?= esc($iku_data['tahun_mulai']) ?>" placeholder="Contoh: 2025" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tahun Akhir</label>
              <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control mb-3" value="<?= esc($iku_data['tahun_akhir'] ?? '') ?>" readonly required>
            </div>
          </div>
        </section>

        <!-- Daftar Sasaran IKU OPD -->
        <section>
          <h2 class="h5 fw-semibold mb-3">Daftar Sasaran IKU OPD</h2>

          <div id="sasaran-iku-container">
            <!-- Sasaran IKU OPD from Database -->
            <?php $sasaranIndex = 0; ?>
            <div class="sasaran-iku-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium sasaran-title">Sasaran IKU OPD 1</label>
                <button type="button" class="remove-sasaran-iku btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <!-- Hidden field for sasaran ID -->
              <input type="hidden" name="sasaran_iku[<?= $sasaranIndex ?>][id]" value="<?= $iku_data['sasaran_id'] ?? '' ?>">
              
              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran IKU OPD</label>
                  <textarea name="sasaran_iku[<?= $sasaranIndex ?>][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran IKU OPD" required><?= esc($iku_data['sasaran'] ?? '') ?></textarea>
                </div>
              </div>

              <!-- Indikator Kinerja -->
              <div class="indikator-kinerja-section">
                <h4 class="h5 fw-medium mb-3">Indikator Kinerja</h4>

                <div class="indikator-kinerja-container">
                  <?php if (isset($iku_data['indikator_kinerja']) && !empty($iku_data['indikator_kinerja'])): ?>
                    <?php foreach ($iku_data['indikator_kinerja'] as $indikatorIndex => $indikator): ?>
                      <!-- Indikator Kinerja 1.<?= $indikatorIndex + 1 ?> -->
                      <div class="indikator-kinerja-item border rounded p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <label class="fw-medium indikator-title">Indikator Kinerja 1.<?= $indikatorIndex + 1 ?></label>
                          <button type="button" class="remove-indikator-kinerja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        
                        <!-- Hidden field for indikator ID -->
                        <input type="hidden" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][id]" value="<?= $indikator['indikator_id'] ?? '' ?>">
                        
                        <div class="mb-3">
                          <label class="form-label">Indikator Kinerja</label>
                          <textarea name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][indikator_kinerja]" class="form-control" rows="2" placeholder="Masukkan indikator kinerja" required><?= esc($indikator['indikator_kinerja'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Definisi Formulasi</label>
                          <textarea name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][definisi_formulasi]" class="form-control" rows="3" placeholder="Masukkan definisi formulasi indikator" required><?= esc($indikator['definisi_formulasi'] ?? '') ?></textarea>
                        </div>

                        <div class="row mb-3">
                          <div class="col-md-6">
                            <label class="form-label">Satuan</label>
                              <select name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][satuan]" class="form-control satuan-select" data-selected="<?= esc($indikator['satuan'] ?? '') ?>" required>
                              <option value="">Pilih Satuan</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Program Pendukung</label>
                            <textarea name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][program_pendukung]" class="form-control" rows="2" placeholder="Masukkan program pendukung" required><?= esc($indikator['program_pendukung'] ?? '') ?></textarea>
                          </div>
                        </div>
                        
                        <!-- Target Tahunan -->
                        <div class="target-section">
                          <h5 class="fw-medium mb-3">Target Tahunan</h5>
                          <div class="target-container">
                            <?php if (isset($indikator['target_tahunan']) && !empty($indikator['target_tahunan'])): ?>
                              <?php foreach ($indikator['target_tahunan'] as $targetIndex => $target): ?>
                                <div class="target-item row g-2 align-items-center mb-2">
                                  <div class="col-auto">
                                    <input type="number" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][tahun]" value="<?= esc($target['tahun'] ?? '') ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                  </div>
                                  <div class="col">
                                    <input type="text" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][target]" value="<?= esc($target['target'] ?? '') ?>" class="form-control form-control-sm" placeholder="Target <?= esc($target['tahun'] ?? '') ?>" required>
                                  </div>
                                  <!-- Hidden field for target ID -->
                                  <input type="hidden" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][id]" value="<?= $target['target_id'] ?? '' ?>">
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <!-- Default 5 targets if none exist -->
                              <?php for ($i = 0; $i < 5; $i++): ?>
                                <div class="target-item row g-2 align-items-center mb-2">
                                  <div class="col-auto">
                                    <input type="number" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][target_tahunan][<?= $i ?>][tahun]" value="<?= (esc($iku_data['tahun_mulai'] ?? 2025) + $i) ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                  </div>
                                  <div class="col">
                                    <input type="text" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][target_tahunan][<?= $i ?>][target]" value="" class="form-control form-control-sm" placeholder="Target <?= (esc($iku_data['tahun_mulai'] ?? 2025) + $i) ?>" required>
                                  </div>
                                  <!-- Hidden field for target ID -->
                                  <input type="hidden" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][<?= $indikatorIndex ?>][target_tahunan][<?= $i ?>][id]" value="">
                                </div>
                              <?php endfor; ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div> <!-- End Indikator Kinerja -->
                    <?php endforeach; ?>
                  <?php else: ?>
                    <!-- Default indikator if none exist -->
                    <div class="indikator-kinerja-item border rounded p-3 bg-white mb-3">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium indikator-title">Indikator Kinerja 1.1</label>
                        <button type="button" class="remove-indikator-kinerja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">Indikator Kinerja</label>
                        <textarea name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][indikator_kinerja]" class="form-control" rows="2" placeholder="Masukkan indikator kinerja" required></textarea>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Definisi Formulasi</label>
                        <textarea name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][definisi_formulasi]" class="form-control" rows="3" placeholder="Masukkan definisi operasional indikator" required></textarea>
                      </div>

                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label class="form-label">Satuan</label>
                          <select name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][satuan]" class="form-control satuan-select" required>
                            <option value="">Pilih Satuan</option>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Program Pendukung</label>
                          <textarea name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][program_pendukung]" class="form-control" rows="2" placeholder="Masukkan program pendukung" required></textarea>
                        </div>
                      </div>
                      
                      <!-- Target Tahunan -->
                      <div class="target-section">
                        <h5 class="fw-medium mb-3">Target Tahunan</h5>
                        <div class="target-container">
                          <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][target_tahunan][<?= $i ?>][tahun]" value="<?= (esc($iku_data['tahun_mulai'] ?? 2025) + $i) ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][target_tahunan][<?= $i ?>][target]" value="" class="form-control form-control-sm" placeholder="Target <?= (esc($iku_data['tahun_mulai'] ?? 2025) + $i) ?>" required>
                              </div>
                              <!-- Hidden field for target ID -->
                              <input type="hidden" name="sasaran_iku[<?= $sasaranIndex ?>][indikator_kinerja][0][target_tahunan][<?= $i ?>][id]" value="">
                            </div>
                          <?php endfor; ?>
                        </div>
                      </div>
                    </div> <!-- End Default Indikator Kinerja -->
                  <?php endif; ?>
                </div> <!-- End Indikator Kinerja Container -->
                
                <!-- Tombol Tambah Indikator Kinerja -->
                <div class="text-end mt-3">
                  <button type="button" class="add-indikator-kinerja btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Kinerja
                  </button>
                </div>
              </div> <!-- End Indikator Kinerja Section -->
            </div> <!-- End Sasaran IKU OPD -->
          </div> <!-- End Sasaran IKU OPD Container -->
          
          <!-- Tombol Tambah Sasaran IKU OPD -->
          <div class="text-end mt-3">
            <button type="button" id="add-sasaran-iku" class="btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran IKU OPD
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

  <!-- jQuery (required for Select2) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Initialize Select2 for existing elements
      initializeSelect2();

      // Function to initialize Select2 on elements
      function initializeSelect2() {
        // Initialize Select2 for Renstra Sasaran dropdown
        $('#renstra_sasaran_select').select2({
          placeholder: "Pilih atau ketik untuk mencari sasaran renstra...",
          allowClear: true,
          width: '100%'
        });
      }

      // Re-initialize Select2 when new elements are added
      $(document).on('click', '.add-indikator-kinerja, #add-sasaran-iku', function() {
        setTimeout(function() {
          initializeSelect2();
        }, 100);
      });

      // Handle selection change
      $('#renstra_sasaran_select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        
        if (selectedOption.val()) {
          console.log('Selected Sasaran:', {
            id: selectedOption.val(),
            sasaran: selectedOption.text()
          });
          
          // Show selected info (optional)
          showSelectedInfo(selectedOption.text());
        }
      });

      // Function to show selected sasaran info
      function showSelectedInfo(sasaran) {
        var infoHtml = '<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">' +
          '<strong>Sasaran Renstra Terpilih:</strong> ' + sasaran +
          '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>';
        
        $('#alert-container').html(infoHtml);
      }
    });
  </script>

  <script src="<?= base_url('assets/js/adminOpd/iku_opd/ikuOpd-form.js') ?>"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Populate all satuan selects with options from JavaScript helper
      document.querySelectorAll('.satuan-select').forEach(select => {
        const selectedValue = select.getAttribute('data-selected') || '';
        select.innerHTML = generateSatuanOptions();
        if (selectedValue) {
          select.value = selectedValue;
        }
      });
      
      // Initialize tahun akhir berdasarkan tahun mulai (untuk edit mode)
      const tahunMulaiField = document.getElementById('tahun_mulai');
      const tahunAkhirField = document.getElementById('tahun_akhir');
      
      if (tahunMulaiField && tahunAkhirField && tahunMulaiField.value) {
        const tahunMulai = parseInt(tahunMulaiField.value);
        if (tahunMulai && !isNaN(tahunMulai)) {
          // Set tahun akhir (IKU OPD biasanya 5 tahun)
          tahunAkhirField.value = tahunMulai + 4;
        }
      }
    });
  </script>
</body>
</html>
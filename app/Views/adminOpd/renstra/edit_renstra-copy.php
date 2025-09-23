<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Renstra e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit Renstra</h2>

      <!-- Alert Container -->
      <div id="alert-container"></div>

      <form id="renstra-form" method="POST"
        action="<?= base_url('adminopd/renstra/update/' . ($renstra_data['sasaran_id'] ?? '')) ?>">
        <?= csrf_field() ?>

        <!-- Hidden fields untuk mode edit -->
        <input type="hidden" name="mode" value="edit">
        <input type="hidden" name="renstra_tujuan_id" value="<?= esc($renstra_data['renstra_tujuan_id'] ?? '') ?>">

        <!-- Informasi Umum -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Renstra</h2>
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Sasaran RPJMD Terkait</label>
              <select name="rpjmd_sasaran_id" id="rpjmd_sasaran_select" class="form-select mb-3" required>
                <option value="">Pilih Sasaran RPJMD</option>
                <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" <?= isset($renstra_data['rpjmd_sasaran_id']) && $sasaran['id'] == $renstra_data['rpjmd_sasaran_id'] ? 'selected' : '' ?>>
                    <?= esc($sasaran['sasaran_rpjmd'] ?? $sasaran['sasaran']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Ketik untuk mencari sasaran RPJMD...</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tujuan Renstra</label>
              <input type="text" name="tujuan_renstra" id="tujuan_renstra_input" class="form-control mb-3"
                value="<?= esc($renstra_data['tujuan'] ?? $renstra_data['renstra_tujuan'] ?? '') ?>" placeholder="Ketik tujuan renstra" required autocomplete="off">
              <small class="text-muted">Ketik tujuan renstra sesuai kebutuhan Anda.</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tahun Mulai</label>
              <input type="number" name="tahun_mulai" id="tahun_mulai" class="form-control mb-3"
                value="<?= esc($renstra_data['tahun_mulai'] ?? '') ?>" placeholder="Contoh: 2025" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tahun Akhir</label>
              <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control mb-3"
                value="<?= esc($renstra_data['tahun_akhir'] ?? '') ?>" readonly required>
            </div>
          </div>
        </section>

        <!-- Daftar Sasaran Renstra -->
        <section>
          <h2 class="h5 fw-semibold mb-3">Daftar Sasaran Renstra</h2>
          <div id="sasaran-renstra-container">
            <?php if (isset($renstra_data['sasaran_renstra']) && is_array($renstra_data['sasaran_renstra'])): ?>
              <?php foreach ($renstra_data['sasaran_renstra'] as $sasaranIndex => $sasaran): ?>
                <div class="sasaran-renstra-item bg-light border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium sasaran-title">Sasaran Renstra <?= $sasaranIndex + 1 ?></label>
                    <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-12">
                      <label class="form-label">Sasaran Renstra</label>
                      <textarea name="sasaran_renstra[<?= $sasaranIndex ?>][sasaran]" class="form-control" rows="2" required><?= esc($sasaran['sasaran'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <!-- Indikator Sasaran -->
                  <div class="indikator-sasaran-section">
                    <h4 class="h5 fw-medium mb-3">Indikator Sasaran</h4>
                    <div class="indikator-sasaran-container">
                      <?php if (isset($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])): ?>
                        <?php foreach ($sasaran['indikator_sasaran'] as $indikatorIndex => $indikator): ?>
                          <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="fw-medium indikator-title">Indikator Sasaran <?= $sasaranIndex + 1 ?>.<?= $indikatorIndex + 1 ?></label>
                              <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Indikator Sasaran</label>
                              <textarea name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][indikator_sasaran]" class="form-control" rows="2" required><?= esc($indikator['indikator_sasaran'] ?? '') ?></textarea>
                            </div>
                            <div class="row mb-3">
                              <div class="col-md-6">
                                <label class="form-label">Satuan</label>
                                <select name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][satuan]" class="form-select satuan-select" data-selected="<?= esc($indikator['satuan'] ?? '') ?>">
                                  <option value="">Pilih Satuan</option>
                                  <?php if (isset($satuan_options) && is_array($satuan_options)): ?>
                                    <?php foreach ($satuan_options as $key => $label): ?>
                                      <option value="<?= $key ?>" <?= ($indikator['satuan'] ?? '') == $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                  <?php endif; ?>
                                </select>
                              </div>
                            </div>
                            <!-- Target Tahunan -->
                            <div class="target-section">
                              <h5 class="fw-medium mb-3">Target Tahunan</h5>
                              <div class="target-container">
                                <?php if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])): ?>
                                  <?php foreach ($indikator['target_tahunan'] as $targetIndex => $target): ?>
                                    <div class="target-item row g-2 align-items-center mb-2">
                                      <div class="col-md-2">
                                        <label class="form-label">Tahun</label>
                                        <input type="number" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][tahun]" class="form-control tahun-target" value="<?= esc($target['tahun'] ?? '') ?>" readonly required>
                                      </div>
                                      <div class="col-md-4">
                                        <label class="form-label">Target</label>
                                        <input type="text" name="sasaran_renstra[<?= $sasaranIndex ?>][indikator_sasaran][<?= $indikatorIndex ?>][target_tahunan][<?= $targetIndex ?>][target]" class="form-control" value="<?= esc($target['target'] ?? '') ?>" required>
                                      </div>
                                    </div>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div> <!-- End Indikator Sasaran -->
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <!-- Tombol Tambah Indikator Sasaran -->
                      <div class="text-end mt-3">
                        <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                          <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                        </button>
                      </div>
                    </div> <!-- End Indikator Sasaran Container -->
                  </div> <!-- End Indikator Sasaran Section -->
                </div> <!-- End Sasaran Renstra -->
              <?php endforeach; ?>
            <?php endif; ?>
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

  <!-- jQuery (required for Select2) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    $(document).ready(function () {
      // Initialize Select2 for existing elements
      initializeSelect2();

      // Function to initialize Select2 on elements
      function initializeSelect2() {
        // Initialize Select2 for RPJMD Sasaran dropdown
        $('#rpjmd_sasaran_select').select2({
          placeholder: "Pilih atau ketik untuk mencari sasaran RPJMD...",
          allowClear: true,
          width: '100%'
        });
      }

      // Re-initialize Select2 when new elements are added
      $(document).on('click', '.add-indikator-sasaran, #add-sasaran-renstra', function () {
        setTimeout(function () {
          initializeSelect2();
        }, 100);
      });

      // Handle selection change
      $('#rpjmd_sasaran_select').on('change', function () {
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
          '<strong>Sasaran RPJMD Terpilih:</strong> ' + sasaran +
          '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
          '</div>';

        $('#alert-container').html(infoHtml);
      }
    });
  </script>

  <script src="<?= base_url('assets/js/adminOpd/renstra/renstra-form.js') ?>"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
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
          // Set tahun akhir (Renstra biasanya 5 tahun)
          tahunAkhirField.value = tahunMulai + 4;
        }
      }
    });
  </script>
</body>

</html>
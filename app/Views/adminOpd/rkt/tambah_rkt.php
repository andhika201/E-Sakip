<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title) ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <!-- Override SETELAH Select2 -->
  <style>
    .select2-container {
      width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
      height: 38px;
      padding: 6px 12px;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      display: flex;
      align-items: center;
      background-color: #fff;
    }

    .select2-selection__rendered {
      padding-left: 0 !important;
      color: #495057;
    }

    .select2-selection__arrow {
      height: 100% !important;
    }

    .select2-dropdown {
      border-radius: 0.375rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
    }

    .select2-results__option--highlighted {
      background-color: #00743e !important;
      color: #fff;
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include(
    $role === 'admin_kab'
    ? 'adminKabupaten/templates/header.php'
    : 'adminOpd/templates/header.php'
  ); ?>

  <?= $this->include(
    $role === 'admin_kab'
    ? 'adminKabupaten/templates/sidebar.php'
    : 'adminOpd/templates/sidebar.php'
  ); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Tambah RENJA (RKT)</h2>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
      <?php endif; ?>

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/rkt/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="indikator_id" value="<?= esc($indikator['id']) ?>">

        <!-- INDIKATOR & TAHUN -->
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label text-uppercase small fw-semibold text-muted mb-1">Indikator</label>
            <div class="input-group shadow-sm rounded-3">
              <span class="input-group-text bg-success text-white border-0">
                <i class="fas fa-bullseye"></i>
              </span>
              <input type="text" class="form-control border-0 bg-light"
                value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label text-uppercase small fw-semibold text-muted mb-1">Tahun Rencana</label>
            <div class="input-group shadow-sm rounded-3">
              <span class="input-group-text bg-primary text-white border-0">
                <i class="fas fa-calendar-alt"></i>
              </span>
              <input type="number" name="tahun" class="form-control border-0 bg-light"
                value="<?= esc($tahun ?? date('Y')) ?>" readonly>
            </div>
          </div>
        </div>

        <!-- ================= PROGRAM ================= -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-semibold mb-0">Daftar Program</h2>
          </div>

          <div id="program-container">

            <!-- PROGRAM PERTAMA (DEFAULT) -->
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium mb-0 program-title">Program 1</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm" style="display:none;">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program PK</label>
                <select name="program[0][program_id]" class="form-select select2 select-program" required>
                  <option value="">-- Pilih Program --</option>
                  <?php foreach ($program as $prog): ?>
                    <option value="<?= esc($prog['id']) ?>">
                      <?= esc($prog['program_kegiatan']) ?>— Rp <?= number_format($prog['anggaran'], 0, ',', '.') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>


              <!-- KEGIATAN -->
              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium mb-0">Kegiatan</h4>
                </div>

                <div class="kegiatan-container">

                  <!-- KEGIATAN 1 -->
                  <div class="kegiatan-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium mb-0 kegiatan-title">Kegiatan 1</label>
                      <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Pilih Kegiatan PK</label>
                      <select name="program[0][kegiatan][0][kegiatan_id]" class="form-select select2 select-kegiatan"
                        required>
                        <option value="">-- Pilih Kegiatan --</option>
                      </select>
                    </div>

                    <!-- SUB KEGIATAN -->
                    <div class="subkegiatan-section">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="h6 fw-medium mb-0">Sub Kegiatan</h5>
                      </div>

                      <div class="subkegiatan-container">

                        <!-- SUB KEGIATAN 1 -->
                        <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium mb-0 sub-title">Sub Kegiatan 1</label>
                            <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>

                          <div class="row mb-3">
                            <div class="col-md-8">
                              <label class="form-label">Pilih Sub Kegiatan PK</label>
                              <select name="program[0][kegiatan][0][subkegiatan][0][sub_kegiatan_id]"
                                class="form-select select2 select-subkegiatan" required>
                                <option value="">-- Pilih Sub Kegiatan --</option>
                              </select>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Target Anggaran</label>
                              <input type="text" class="form-control target-display" placeholder="-" readonly>
                              <!-- tidak dikirim ke server, hanya tampilan -->
                              <input type="hidden" class="target-hidden" value="">
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex justify-content-end">
                        <button type="button" class="add-subkegiatan btn btn-info btn-sm">
                          <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                        </button>
                      </div>
                    </div> <!-- /subkegiatan-section -->
                  </div> <!-- /kegiatan-item -->
                </div> <!-- /kegiatan-container -->

                <div class="d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                  </button>
                </div>
              </div> <!-- /kegiatan-section -->
            </div> <!-- /program-item -->
          </div> <!-- /program-container -->

          <div class="d-flex justify-content-end">
            <button type="button" id="add-program" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Program
            </button>
          </div>

          <!-- =================== TEMPLATES =================== -->
          <template id="tpl-program">
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium mb-0 program-title">Program X</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program PK</label>
                <select class="form-select select-program">
                  <option value="">-- Pilih Program --</option>
                </select>
              </div>

              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium mb-0">Kegiatan</h4>
                </div>
                <div class="kegiatan-container"></div>
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </template>

          <template id="tpl-kegiatan">
            <div class="kegiatan-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium mb-0 kegiatan-title">Kegiatan X</label>
                <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Kegiatan PK</label>
                <select class="form-select select-kegiatan">
                  <option value="">-- Pilih Kegiatan --</option>
                </select>
              </div>

              <div class="subkegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="h6 fw-medium mb-0">Sub Kegiatan</h5>
                </div>
                <div class="subkegiatan-container"></div>
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-subkegiatan btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </template>

          <template id="tpl-subkegiatan">
            <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium mb-0 sub-title">Sub Kegiatan X</label>
                <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="row mb-3">
                <div class="col-md-8">
                  <label class="form-label">Pilih Sub Kegiatan PK</label>
                  <select class="form-select select-subkegiatan">
                    <option value="">-- Pilih Sub Kegiatan --</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Target Anggaran</label>
                  <input type="text" class="form-control target-display" placeholder="-" readonly>
                  <input type="hidden" class="target-hidden" value="">
                </div>
              </div>
            </div>
          </template>
        </section>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/rkt') ?>" class="btn btn-secondary">
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- Data master dikirim ke JS -->
  <script>
    const PROGRAMS = <?= json_encode($program) ?>;
    const KEGIATAN = <?= json_encode($kegiatanPk) ?>;
    const SUBS = <?= json_encode($subKegiatanPk) ?>;
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {

      /* =====================================================
       * INIT SELECT2
       * ===================================================== */
      function initSelect2(context = document) {
        $(context).find('.select2').each(function () {
          if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
          }

          $(this).select2({
            width: '100%',
            dropdownParent: $('body')
          });
        });
      }

      initSelect2();


      /* =====================================================
       * HELPER RUPIAH
       * ===================================================== */
      function toIntegerString(num) {
        if (num === null || num === undefined || num === '') return '';
        const n = Number(num);
        if (!Number.isFinite(n)) return '';
        return Math.round(n).toString();
      }

      function formatRupiah(num) {
        const s = toIntegerString(num);
        if (!s) return '';
        return s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }


      /* =====================================================
       * FILL DROPDOWN
       * ===================================================== */
      function fillProgramOptions(select) {
        select.innerHTML = '<option value="">-- Pilih Program --</option>';

        PROGRAMS.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;
          const anggaran = p.anggaran ? formatRupiah(p.anggaran) : '-';
          opt.textContent = `${p.program_kegiatan} — Rp ${anggaran}`;
          select.appendChild(opt);
        });
      }

      function fillKegiatanOptions(select, programId, currentValue = '') {
        select.innerHTML = '<option value="">-- Pilih Kegiatan --</option>';
        if (!programId) return;

        KEGIATAN
          .filter(k => String(k.program_id) === String(programId))
          .forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.id;
            opt.textContent = k.kegiatan;
            if (String(currentValue) === String(k.id)) opt.selected = true;
            select.appendChild(opt);
          });
      }

      function fillSubOptions(select, kegiatanId, currentValue = '') {
        select.innerHTML = '<option value="">-- Pilih Sub Kegiatan --</option>';
        if (!kegiatanId) return;

        SUBS
          .filter(s => String(s.kegiatan_id) === String(kegiatanId))
          .forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.sub_kegiatan;
            opt.dataset.anggaran = s.anggaran;
            if (String(currentValue) === String(s.id)) opt.selected = true;
            select.appendChild(opt);
          });
      }


      /* =====================================================
       * EVENT SELECT (KHUSUS SELECT2)
       * ===================================================== */
      $(document).on('change', '.select-program', function () {
        const programId = $(this).val();
        const programItem = this.closest('.program-item');

        $(programItem).find('.select-kegiatan').each(function () {
          fillKegiatanOptions(this, programId);
          $(this).trigger('change.select2');
        });
      });

      $(document).on('change', '.select-kegiatan', function () {
        const kegiatanId = $(this).val();
        const kegiatanItem = this.closest('.kegiatan-item');

        $(kegiatanItem).find('.select-subkegiatan').each(function () {
          fillSubOptions(this, kegiatanId);
          $(this).trigger('change.select2');
        });
      });

      $(document).on('change', '.select-subkegiatan', function () {
        const opt = this.selectedOptions[0];
        const anggaranRaw = opt ? opt.dataset.anggaran : '';

        const sItem = this.closest('.subkegiatan-item');
        const disp = sItem.querySelector('.target-display');
        const hid = sItem.querySelector('.target-hidden');

        if (anggaranRaw) {
          const intStr = toIntegerString(anggaranRaw);
          disp.value = 'Rp ' + formatRupiah(intStr);
          hid.value = intStr;
        } else {
          disp.value = '-';
          hid.value = '';
        }
      });


      /* =====================================================
       * INIT DEFAULT (PROGRAM PERTAMA)
       * ===================================================== */
      (function initFirst() {
        const firstProg = document.querySelector('.program-item');
        if (!firstProg) return;

        const selProg = firstProg.querySelector('.select-program');
        const selKeg = firstProg.querySelector('.select-kegiatan');
        const selSub = firstProg.querySelector('.select-subkegiatan');

        fillKegiatanOptions(selKeg, selProg.value);
        fillSubOptions(selSub, selKeg.value);
      })();

    });
  </script>

</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Edit RENJA (RKT)') ?></title>
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

  <?= $this->include('adminOpd/templates/header.php'); ?>
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Edit RENJA (RKT)</h2>

      <!-- Flash -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('success') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form id="rkt-edit-form" method="POST" action="<?= base_url('adminopd/rkt/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="indikator_id" value="<?= esc($indikator['id']) ?>">
        <input type="hidden" name="tahun" value="<?= esc($tahun ?? date('Y')) ?>">

        <!-- Indikator & Tahun -->
        <div class="row g-3 mb-4">
          <div class="col-md-8">
            <label class="form-label text-uppercase small fw-semibold text-muted mb-1">
              Indikator Sasaran Renstra
            </label>
            <div class="input-group shadow-sm rounded-3">
              <span class="input-group-text bg-success text-white border-0">
                <i class="fas fa-bullseye"></i>
              </span>
              <input type="text" class="form-control border-0 bg-light"
                value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
            </div>
            <div class="form-text">
              Sasaran: <?= esc($indikator['sasaran'] ?? '-') ?>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label text-uppercase small fw-semibold text-muted mb-1">
              Tahun Rencana
            </label>
            <div class="input-group shadow-sm rounded-3">
              <span class="input-group-text bg-primary text-white border-0">
                <i class="fas fa-calendar-alt"></i>
              </span>
              <input type="number" class="form-control border-0 bg-light" value="<?= esc($tahun ?? date('Y')) ?>"
                readonly>
            </div>
          </div>
        </div>

        <!-- ================== PROGRAM / KEGIATAN / SUB ================== -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-semibold mb-0">Daftar Program PK</h2>
          </div>

          <div id="program-container"></div>

          <div class="d-flex justify-content-end mt-2">
            <button type="button" id="add-program" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Program
            </button>
          </div>

          <!-- Template Program -->
          <template id="tpl-program">
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Program X</label>
                <button type="button" class="btn btn-outline-danger btn-sm remove-program">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program PK</label>
                <select class="form-select select2 select-program">
                  <option value="">-- Pilih Program --</option>
                </select>
              </div>

              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium">Kegiatan</h4>
                </div>

                <div class="kegiatan-container"></div>

                <div class="d-flex justify-content-end">
                  <button type="button" class="btn btn-success btn-sm add-kegiatan">
                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </template>

          <!-- Template Kegiatan -->
          <template id="tpl-kegiatan">
            <div class="kegiatan-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Kegiatan X</label>
                <button type="button" class="btn btn-outline-danger btn-sm remove-kegiatan">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Kegiatan PK</label>
                <select class="form-select select2 select-kegiatan">
                  <option value="">-- Pilih Kegiatan --</option>
                </select>
              </div>

              <div class="subkegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="h6 fw-medium">Sub Kegiatan</h5>
                </div>

                <div class="subkegiatan-container"></div>

                <div class="d-flex justify-content-end">
                  <button type="button" class="btn btn-info btn-sm add-subkegiatan">
                    <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </template>

          <!-- Template Sub Kegiatan -->
          <template id="tpl-subkegiatan">
            <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sub Kegiatan X</label>
                <button type="button" class="btn btn-outline-danger btn-sm remove-subkegiatan">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="row mb-3">
                <div class="col-md-8">
                  <label class="form-label">Pilih Sub Kegiatan PK</label>
                  <select class="form-select select2 select-subkegiatan">
                    <option value="">-- Pilih Sub Kegiatan --</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Target Anggaran</label>
                  <input type="text" class="form-control target-display" placeholder="-" readonly>
                </div>
              </div>
            </div>
          </template>
        </section>

        <!-- Tombol -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/rkt') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    function initSelect2(context = document) {
      $(context).find('.select2').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
          $(this).select2('destroy');
        }

        $(this).select2({
          width: '100%',
          minimumResultsForSearch: 0, // search tetap aktif
          dropdownParent: $('body')
        });
      });
    }

    $(document).ready(function () {
      initSelect2();
    });
  </script>

  <script>
    // data master & data existing dari PHP
    const daftarProgram = <?= json_encode($program) ?>;
    const daftarKegiatan = <?= json_encode($kegiatanPk) ?>;
    const daftarSub = <?= json_encode($subKegiatanPk) ?>;
    const existingPrograms = <?= json_encode($rktPrograms) ?>;

    document.addEventListener('DOMContentLoaded', function () {
      const programContainer = document.getElementById('program-container');
      const tplProgram = document.getElementById('tpl-program');
      const tplKegiatan = document.getElementById('tpl-kegiatan');
      const tplSub = document.getElementById('tpl-subkegiatan');
      const form = document.getElementById('rkt-edit-form');

      const cloneTemplate = (tpl) => tpl.content.firstElementChild.cloneNode(true);

      function fillProgramOptions(selectEl) {
        if (!selectEl) return;

        selectEl.innerHTML = '<option value="">-- Pilih Program --</option>';

        daftarProgram.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;

          const anggaran = p.anggaran ? formatRupiah(p.anggaran) : '-';
          opt.textContent = `${p.program_kegiatan} — ${anggaran}`;

          // simpan nilai mentah (kalau nanti perlu validasi total)
          opt.dataset.anggaran = p.anggaran ?? '';

          selectEl.appendChild(opt);
        });
      }


      function fillKegiatanOptions(selectEl, programId) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">-- Pilih Kegiatan --</option>';
        if (!programId) return;
        daftarKegiatan
          .filter(k => String(k.program_id) === String(programId))
          .forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.id;
            opt.textContent = k.kegiatan;
            selectEl.appendChild(opt);
          });
      }

      function fillSubOptions(selectEl, kegiatanId) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">-- Pilih Sub Kegiatan --</option>';
        if (!kegiatanId) return;
        daftarSub
          .filter(s => String(s.kegiatan_id) === String(kegiatanId))
          .forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.sub_kegiatan;
            opt.dataset.anggaran = s.anggaran || '';
            selectEl.appendChild(opt);
          });
      }

      // ==== FIX FORMAT RUPIAH (sama seperti view tambah) ====
      function toIntegerString(num) {
        if (num === null || num === undefined || num === '') return '';
        const n = Number(num);
        if (!Number.isFinite(n)) return '';
        return Math.round(n).toString(); // buang desimal kalau ada
      }

      function formatRupiah(num) {
        const s = toIntegerString(num);
        if (!s) return '-';
        return 'Rp ' + s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }

      function setTargetFromSubSelect(selEl) {
        const selected = selEl.selectedOptions[0];
        const anggaranRaw = selected ? selected.dataset.anggaran : '';
        const sItem = selEl.closest('.subkegiatan-item');
        const disp = sItem.querySelector('.target-display');

        if (!disp) return;

        if (anggaranRaw) {
          const intStr = toIntegerString(anggaranRaw);
          disp.value = formatRupiah(intStr);
        } else {
          disp.value = '-';
        }
      }
      // =====================================================

      function updateNamesAndLabels() {
        const programs = Array.from(programContainer.querySelectorAll('.program-item'));
        programs.forEach((pEl, pIdx) => {
          const lblP = pEl.querySelector('label.fw-medium');
          if (lblP) lblP.textContent = `Program ${pIdx + 1}`;

          const selProg = pEl.querySelector('.select-program');
          if (selProg) selProg.name = `program[${pIdx}][program_id]`;

          const kegiatans = Array.from(pEl.querySelectorAll('.kegiatan-item'));
          kegiatans.forEach((kEl, kIdx) => {
            const lblK = kEl.querySelector('label.fw-medium');
            if (lblK) lblK.textContent = `Kegiatan ${pIdx + 1}.${kIdx + 1}`;

            const selK = kEl.querySelector('.select-kegiatan');
            if (selK) selK.name = `program[${pIdx}][kegiatan][${kIdx}][kegiatan_id]`;

            const subs = Array.from(kEl.querySelectorAll('.subkegiatan-item'));
            subs.forEach((sEl, sIdx) => {
              const lblS = sEl.querySelector('label.fw-medium');
              if (lblS) lblS.textContent = `Sub ${pIdx + 1}.${kIdx + 1}.${sIdx + 1}`;

              const selS = sEl.querySelector('.select-subkegiatan');
              if (selS) selS.name =
                `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][sub_kegiatan_id]`;
            });
          });
        });

        // minimal 1 program/kegiatan/sub -> kontrol tombol delete
        programs.forEach(pEl => {
          const rmP = pEl.querySelector('.remove-program');
          rmP.style.display = programs.length > 1 ? '' : 'none';

          const kegiatans = Array.from(pEl.querySelectorAll('.kegiatan-item'));
          kegiatans.forEach(kEl => {
            const rmK = kEl.querySelector('.remove-kegiatan');
            rmK.style.display = kegiatans.length > 1 ? '' : 'none';

            const subs = Array.from(kEl.querySelectorAll('.subkegiatan-item'));
            subs.forEach(sEl => {
              const rmS = sEl.querySelector('.remove-subkegiatan');
              rmS.style.display = subs.length > 1 ? '' : 'none';
            });
          });
        });
      }

      function createSubNode() {
        return cloneTemplate(tplSub);
      }

      function createKegiatanNode() {
        const kNode = cloneTemplate(tplKegiatan);
        const subContainer = kNode.querySelector('.subkegiatan-container');
        const sNode = createSubNode();
        subContainer.appendChild(sNode);
        return kNode;
      }

      function createProgramNodeEmpty() {
        const pNode = cloneTemplate(tplProgram);
        const selProg = pNode.querySelector('.select-program');
        fillProgramOptions(selProg);
        return pNode;
      }

      function addProgram() {
        const pNode = createProgramNodeEmpty();
        const kegContainer = pNode.querySelector('.kegiatan-container');
        const kNode = createKegiatanNode();
        kegContainer.appendChild(kNode);
        programContainer.appendChild(pNode);
        updateNamesAndLabels();
        return pNode;
      }

      // ------------------ INIT dari existingPrograms ------------------
      (function init() {
        if (Array.isArray(existingPrograms) && existingPrograms.length) {
          existingPrograms.forEach(pData => {
            const pNode = createProgramNodeEmpty();
            const selProg = pNode.querySelector('.select-program');
            selProg.value = pData.program_id || '';

            const kegContainer = pNode.querySelector('.kegiatan-container');
            kegContainer.innerHTML = '';

            const kList = pData.kegiatan || [];
            if (kList.length) {
              kList.forEach(kData => {
                const kNode = createKegiatanNode();
                const selK = kNode.querySelector('.select-kegiatan');
                fillKegiatanOptions(selK, selProg.value);
                selK.value = kData.kegiatan_id || '';

                const subContainer = kNode.querySelector('.subkegiatan-container');
                subContainer.innerHTML = '';

                const sList = kData.subkegiatan || [];
                if (sList.length) {
                  sList.forEach(sData => {
                    const sNode = createSubNode();
                    const selS = sNode.querySelector('.select-subkegiatan');
                    fillSubOptions(selS, selK.value);
                    selS.value = sData.sub_kegiatan_id || '';
                    setTargetFromSubSelect(selS);
                    subContainer.appendChild(sNode);
                  });
                } else {
                  const sNode = createSubNode();
                  const selS = sNode.querySelector('.select-subkegiatan');
                  fillSubOptions(selS, selK.value);
                  subContainer.appendChild(sNode);
                }

                kegContainer.appendChild(kNode);
              });
            } else {
              const kNode = createKegiatanNode();
              const selK = kNode.querySelector('.select-kegiatan');
              fillKegiatanOptions(selK, selProg.value);
              kegContainer.appendChild(kNode);
            }

            programContainer.appendChild(pNode);
          });
        } else {
          // belum ada data -> 1 program default
          addProgram();
        }

        updateNamesAndLabels();
      })();

      // ------------------ Click handler tambah/hapus ------------------
      document.addEventListener('click', function (e) {
        if (e.target.closest('#add-program')) {
          e.preventDefault();
          addProgram();
          return;
        }

        const addK = e.target.closest('.add-kegiatan');
        if (addK) {
          e.preventDefault();
          const pItem = addK.closest('.program-item');
          const kegContainer = pItem.querySelector('.kegiatan-container');
          const kNode = createKegiatanNode();
          const selProg = pItem.querySelector('.select-program');
          const selK = kNode.querySelector('.select-kegiatan');
          fillKegiatanOptions(selK, selProg.value);
          kegContainer.appendChild(kNode);
          updateNamesAndLabels();
          return;
        }

        const addS = e.target.closest('.add-subkegiatan');
        if (addS) {
          e.preventDefault();
          const kItem = addS.closest('.kegiatan-item');
          const subContainer = kItem.querySelector('.subkegiatan-container');
          const sNode = createSubNode();
          const selK = kItem.querySelector('.select-kegiatan');
          const selS = sNode.querySelector('.select-subkegiatan');
          fillSubOptions(selS, selK.value);
          subContainer.appendChild(sNode);
          updateNamesAndLabels();
          return;
        }

        const rmP = e.target.closest('.remove-program');
        if (rmP) {
          e.preventDefault();
          const programs = programContainer.querySelectorAll('.program-item');
          if (programs.length <= 1) {
            alert('Minimal 1 program.');
            return;
          }
          rmP.closest('.program-item').remove();
          updateNamesAndLabels();
          return;
        }

        const rmK = e.target.closest('.remove-kegiatan');
        if (rmK) {
          e.preventDefault();
          const pItem = rmK.closest('.program-item');
          const kegItems = pItem.querySelectorAll('.kegiatan-item');
          if (kegItems.length <= 1) {
            alert('Minimal 1 kegiatan per program.');
            return;
          }
          rmK.closest('.kegiatan-item').remove();
          updateNamesAndLabels();
          return;
        }

        const rmS = e.target.closest('.remove-subkegiatan');
        if (rmS) {
          e.preventDefault();
          const kItem = rmS.closest('.kegiatan-item');
          const subItems = kItem.querySelectorAll('.subkegiatan-item');
          if (subItems.length <= 1) {
            alert('Minimal 1 sub kegiatan per kegiatan.');
            return;
          }
          rmS.closest('.subkegiatan-item').remove();
          updateNamesAndLabels();
          return;
        }
      });

      // ------------------ Change handler select ------------------
      document.addEventListener('change', function (e) {
        const selProg = e.target.closest('.select-program');
        if (selProg) {
          const pItem = selProg.closest('.program-item');
          const kegItems = pItem.querySelectorAll('.kegiatan-item');
          kegItems.forEach(kEl => {
            const selK = kEl.querySelector('.select-kegiatan');
            fillKegiatanOptions(selK, selProg.value);

            const subItems = kEl.querySelectorAll('.subkegiatan-item');
            subItems.forEach(sEl => {
              const selS = sEl.querySelector('.select-subkegiatan');
              fillSubOptions(selS, selK.value);
              const disp = sEl.querySelector('.target-display');
              if (disp) disp.value = '-';
            });
          });
          return;
        }

        const selK = e.target.closest('.select-kegiatan');
        if (selK) {
          const kItem = selK.closest('.kegiatan-item');
          const subItems = kItem.querySelectorAll('.subkegiatan-item');
          subItems.forEach(sEl => {
            const selS = sEl.querySelector('.select-subkegiatan');
            fillSubOptions(selS, selK.value);
            const disp = sEl.querySelector('.target-display');
            if (disp) disp.value = '-';
          });
          return;
        }

        const selS = e.target.closest('.select-subkegiatan');
        if (selS) {
          setTargetFromSubSelect(selS);
          return;
        }
      });

      // sebelum submit, pastikan name sudah konsisten
      form.addEventListener('submit', function () {
        updateNamesAndLabels();
      });
    });
  </script>

</body>

</html>
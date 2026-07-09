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
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

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

  <script>
    // assets/js/adminopd/renja/renja-form.js
    (function () {
      "use strict";

      function initRenjaForm() {
        const programContainer = document.getElementById('program-container');
        const addProgramBtn = document.getElementById('add-program');
        const tplProgram = document.getElementById('tpl-program');
        const tplKegiatan = document.getElementById('tpl-kegiatan');
        const tplSub = document.getElementById('tpl-subkegiatan');
        const form = document.querySelector('form');

        if (!programContainer) return;

        // ---------- helpers ----------
        function cloneTemplate(tpl) {
          return tpl.content.firstElementChild.cloneNode(true);
        }

        function fillProgramOptions(selectEl) {
          if (!selectEl) return;
          selectEl.innerHTML = '<option value="">-- Pilih Program --</option>';
          if (typeof daftarProgram !== 'undefined' && Array.isArray(daftarProgram)) {
            daftarProgram.forEach(p => {
              const opt = document.createElement('option');
              opt.value = p.id;
              opt.textContent = p.program_kegiatan || ('Program ' + p.id);
              selectEl.appendChild(opt);
            });
          }
        }

        // rupiah helpers
        function digitsOnly(v) {
          if (v === null || v === undefined) return '';
          return String(v).replace(/[^\d]/g, '');
        }
        function formatRupiah(num) {
          const s = digitsOnly(num);
          if (!s) return '';
          return s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // ---------- naming & labels ----------
        function updateNamesAndLabels() {
          const programEls = Array.from(programContainer.querySelectorAll('.program-item'));
          programEls.forEach((pEl, pIdx) => {
            // program label
            const lblP = pEl.querySelector('label.fw-medium');
            if (lblP) lblP.textContent = `Program ${pIdx + 1}`;

            // ensure hidden program id field exists (for new ones left empty)
            let hidProg = pEl.querySelector('input[name^="program["][name$="[id]"]');
            if (!hidProg) {
              hidProg = document.createElement('input');
              hidProg.type = 'hidden';
              pEl.prepend(hidProg);
            }
            hidProg.name = `program[${pIdx}][id]`;

            // select program
            const select = pEl.querySelector('.select-program, select');
            if (select) select.name = `program[${pIdx}][program_id]`;

            // kegiatan
            const kegiatanEls = Array.from(pEl.querySelectorAll('.kegiatan-item'));
            kegiatanEls.forEach((kEl, kIdx) => {
              const lblK = kEl.querySelector('label.fw-medium');
              if (lblK) lblK.textContent = `Kegiatan ${pIdx + 1}.${kIdx + 1}`;

              // hidden kegiatan id
              let hidK = kEl.querySelector('input[name*="[kegiatan]"][name$="[id]"]');
              if (!hidK) {
                hidK = document.createElement('input');
                hidK.type = 'hidden';
                kEl.prepend(hidK);
              }
              hidK.name = `program[${pIdx}][kegiatan][${kIdx}][id]`;

              // nama kegiatan
              const namaK = kEl.querySelector('.nama-kegiatan, textarea');
              if (namaK) namaK.name = `program[${pIdx}][kegiatan][${kIdx}][nama_kegiatan]`;

              // subkegiatan
              const subEls = Array.from(kEl.querySelectorAll('.subkegiatan-item'));
              subEls.forEach((sEl, sIdx) => {
                const lblS = sEl.querySelector('label.fw-medium');
                if (lblS) lblS.textContent = `Sub ${pIdx + 1}.${kIdx + 1}.${sIdx + 1}`;

                // hidden sub id
                let hidS = sEl.querySelector('input[name*="[subkegiatan]"][name$="[id]"]');
                if (!hidS) {
                  hidS = document.createElement('input');
                  hidS.type = 'hidden';
                  sEl.prepend(hidS);
                }
                hidS.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][id]`;

                // nama sub
                const namaS = sEl.querySelector('.nama-subkegiatan, textarea');
                if (namaS) namaS.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][nama_subkegiatan]`;

                // hidden target
                const hiddenT = sEl.querySelector('.target-hidden');
                if (hiddenT) hiddenT.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][target_anggaran]`;

                // target display data-index
                const disp = sEl.querySelector('.target-display');
                if (disp) disp.setAttribute('data-index', `${pIdx}-${kIdx}-${sIdx}`);
              });
            });
          });

          // show/hide remove buttons based on counts
          const progCount = programContainer.querySelectorAll('.program-item').length;
          programContainer.querySelectorAll('.program-item').forEach(p => {
            const remP = p.querySelector('.remove-program');
            if (remP) remP.style.display = progCount > 1 ? '' : 'none';

            const kCount = p.querySelectorAll('.kegiatan-item').length;
            p.querySelectorAll('.kegiatan-item').forEach(k => {
              const remK = k.querySelector('.remove-kegiatan');
              if (remK) remK.style.display = kCount > 1 ? '' : 'none';
            });
          });

          programContainer.querySelectorAll('.kegiatan-item').forEach(k => {
            const sCount = k.querySelectorAll('.subkegiatan-item').length;
            k.querySelectorAll('.subkegiatan-item').forEach(s => {
              const remS = s.querySelector('.remove-subkegiatan');
              if (remS) remS.style.display = sCount > 1 ? '' : 'none';
            });
          });
        }

        // ---------- node creators ----------
        function createProgramNode() {
          const node = cloneTemplate(tplProgram);
          const select = node.querySelector('select');
          fillProgramOptions(select);

          // ensure it has at least 1 kegiatan + 1 sub
          const kegiatanContainer = node.querySelector('.kegiatan-container');
          if (kegiatanContainer) {
            kegiatanContainer.innerHTML = '';
            const kNode = createKegiatanNode();
            kegiatanContainer.appendChild(kNode);
          }

          return node;
        }

        function createKegiatanNode() {
          const node = cloneTemplate(tplKegiatan);
          // ensure one sub inside
          const subContainer = node.querySelector('.subkegiatan-container');
          if (subContainer) {
            subContainer.innerHTML = '';
            const sNode = createSubNode();
            subContainer.appendChild(sNode);
          }
          return node;
        }

        function createSubNode() {
          const node = cloneTemplate(tplSub);
          // clear values
          const disp = node.querySelector('.target-display');
          const hid = node.querySelector('.target-hidden');
          if (disp) disp.value = '';
          if (hid) hid.value = '';
          return node;
        }

        // ---------- add functions ----------
        function addProgram() {
          const n = createProgramNode();
          programContainer.appendChild(n);
          updateNamesAndLabels();
          return n;
        }

        function addKegiatanTo(container) {
          const node = createKegiatanNode();
          container.appendChild(node);
          updateNamesAndLabels();
          return node;
        }

        function addSubTo(container) {
          const node = createSubNode();
          container.appendChild(node);
          updateNamesAndLabels();
          return node;
        }

        // ---------- hydrate existing (server-rendered) ----------
        (function hydrate() {
          const existingPrograms = programContainer.querySelectorAll('.program-item');
          if (!existingPrograms.length) {
            // ensure at least 1 program on empty form
            addProgram();
            return;
          }
          // fill selects and ensure min children
          existingPrograms.forEach(pEl => {
            const select = pEl.querySelector('select');
            if (select) fillProgramOptions(select);

            const kc = pEl.querySelector('.kegiatan-container');
            const kEls = kc ? kc.querySelectorAll('.kegiatan-item') : [];
            if (!kEls.length) {
              if (kc) addKegiatanTo(kc);
            } else {
              kEls.forEach(kEl => {
                const sc = kEl.querySelector('.subkegiatan-container');
                const sEls = sc ? sc.querySelectorAll('.subkegiatan-item') : [];
                if (!sEls.length && sc) addSubTo(sc);
              });
            }
          });

          updateNamesAndLabels();
          // format existing display from hidden values if present
          programContainer.querySelectorAll('.subkegiatan-item').forEach(sEl => {
            const display = sEl.querySelector('.target-display');
            const hidden = sEl.querySelector('.target-hidden');
            if (display && hidden && hidden.value) {
              display.value = 'Rp ' + formatRupiah(hidden.value);
            }
          });
        })();

        // ---------- event delegation ----------
        document.addEventListener('click', function (e) {
          // add program
          if (e.target.closest('#add-program')) {
            e.preventDefault();
            addProgram();
            return;
          }

          // add kegiatan in program
          const addK = e.target.closest('.add-kegiatan');
          if (addK) {
            e.preventDefault();
            const pItem = addK.closest('.program-item');
            const kc = pItem.querySelector('.kegiatan-container');
            addKegiatanTo(kc);
            return;
          }

          // add sub in kegiatan
          const addS = e.target.closest('.add-subkegiatan');
          if (addS) {
            e.preventDefault();
            const kItem = addS.closest('.kegiatan-item');
            const sc = kItem.querySelector('.subkegiatan-container');
            addSubTo(sc);
            return;
          }

          // remove program
          const remP = e.target.closest('.remove-program');
          if (remP) {
            e.preventDefault();
            const progCount = programContainer.querySelectorAll('.program-item').length;
            if (progCount > 1) {
              const pItem = remP.closest('.program-item');
              pItem.remove();
              updateNamesAndLabels();
            }
            return;
          }

          // remove kegiatan
          const remK = e.target.closest('.remove-kegiatan');
          if (remK) {
            e.preventDefault();
            const pItem = remK.closest('.program-item');
            const kCount = pItem.querySelectorAll('.kegiatan-item').length;
            if (kCount > 1) {
              const kItem = remK.closest('.kegiatan-item');
              kItem.remove();
              updateNamesAndLabels();
            }
            return;
          }

          // remove sub
          const remS = e.target.closest('.remove-subkegiatan');
          if (remS) {
            e.preventDefault();
            const kItem = remS.closest('.kegiatan-item');
            const sCount = kItem.querySelectorAll('.subkegiatan-item').length;
            if (sCount > 1) {
              const sItem = remS.closest('.subkegiatan-item');
              sItem.remove();
              updateNamesAndLabels();
            }
            return;
          }
        });

        // ---------- input handlers for rupiah ----------
        document.addEventListener('input', function (e) {
          const t = e.target;
          if (!t.classList) return;
          if (t.classList.contains('target-display')) {
            const raw = digitsOnly(t.value);
            const sItem = t.closest('.subkegiatan-item');
            if (sItem) {
              const hidden = sItem.querySelector('.target-hidden');
              if (hidden) hidden.value = raw || '';
            }
            t.value = raw ? 'Rp ' + formatRupiah(raw) : '';
          }
        });

        // focusin: show raw digits (no Rp)
        document.addEventListener('focusin', function (e) {
          const t = e.target;
          if (t.classList && t.classList.contains('target-display')) {
            const sItem = t.closest('.subkegiatan-item');
            const hidden = sItem ? sItem.querySelector('.target-hidden') : null;
            const raw = hidden ? (hidden.value || '') : '';
            t.value = raw ? formatRupiah(raw) : '';
          }
        });

        // focusout: format to Rp ...
        document.addEventListener('focusout', function (e) {
          const t = e.target;
          if (t.classList && t.classList.contains('target-display')) {
            const sItem = t.closest('.subkegiatan-item');
            const hidden = sItem ? sItem.querySelector('.target-hidden') : null;
            const raw = hidden ? (hidden.value || '') : '';
            t.value = raw ? 'Rp ' + formatRupiah(raw) : '';
          }
        });

        // ---------- before submit: sync hidden values & names (optional simple validation) ----------
        if (form) {
          form.addEventListener('submit', function (ev) {
            // sync names & labels
            updateNamesAndLabels();

            // sync hidden target values from display
            programContainer.querySelectorAll('.subkegiatan-item').forEach(sEl => {
              const display = sEl.querySelector('.target-display');
              const hidden = sEl.querySelector('.target-hidden');
              if (display && hidden) hidden.value = digitsOnly(display.value) || hidden.value || '';
            });

            // quick validation: ensure at least 1 program/kegiatan/sub exist (should be ok by UI rules)
            const programEls = programContainer.querySelectorAll('.program-item');
            if (!programEls.length) {
              ev.preventDefault();
              alert('Minimal harus ada 1 program.');
              return false;
            }

            // allow submit
          });
        }

        // initial
        updateNamesAndLabels();
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRenjaForm);
      } else {
        initRenjaForm();
      }
    })();
  </script>
    </div>
</body>
</html>
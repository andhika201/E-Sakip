<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title) ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RENJA</h2>

      <form id="renja-form" method="POST" action="<?= base_url('adminopd/rkt/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="indikator_id" value="<?= esc($indikator['id']) ?>">

        <div class="col-md-6 mb-3 mb-md-0">
          <label class="form-label">Indikator</label>
          <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Tahun</label>
          <input type="number" name="tahun" class="form-control" placeholder="Masukkan Tahun"
            value="<?= isset($rkt['tahun']) ? esc($rkt['tahun']) : date('Y') ?>" required>
        </div>
        <!-- ================= PROGRAM ================= -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-semibold">Daftar Program</h2>
          </div>

          <div id="program-container">
            <!-- Default Program (initially present per Opsi A) -->
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Program 1</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm" style="display:none;">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program</label>
                <select name="program[0][program_id]" class="form-select select-program" required>
                  <option value="">-- Pilih Program --</option>
                  <?php foreach ($program as $prog): ?>
                    <option value="<?= esc($prog['id']) ?>"><?= esc($prog['program_kegiatan']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- ================= KEGIATAN ================= -->
              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium">Kegiatan</h4>
                </div>

                <div class="kegiatan-container">
                  <!-- Default Kegiatan 1 -->
                  <div class="kegiatan-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Kegiatan 1</label>
                      <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Nama Kegiatan</label>
                      <textarea name="program[0][kegiatan][0][nama_kegiatan]" class="form-control nama-kegiatan" rows="2"
                        placeholder="Masukkan nama kegiatan" required></textarea>
                    </div>

                    <!-- ================= SUBKEGIATAN ================= -->
                    <div class="subkegiatan-section">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="h6 fw-medium">Sub Kegiatan</h5>
                      </div>

                      <div class="subkegiatan-container">
                        <!-- Default Subkegiatan 1 -->
                        <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Sub Kegiatan 1</label>
                            <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>

                          <div class="row mb-3">
                            <div class="col-md-8">
                              <label class="form-label">Nama Sub Kegiatan</label>
                              <textarea name="program[0][kegiatan][0][subkegiatan][0][nama_subkegiatan]"
                                class="form-control nama-subkegiatan" rows="2" placeholder="Masukkan nama sub kegiatan"
                                required></textarea>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Target Anggaran</label>
                              <!-- visible display + hidden numeric -->
                              <input type="text" class="form-control target-display" placeholder="Masukkan nilai anggaran" required />
                              <input type="hidden" class="target-hidden" name="program[0][kegiatan][0][subkegiatan][0][target_anggaran]" value="">
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Tombol Tambah Subkegiatan -->
                      <div class="d-flex justify-content-end">
                        <button type="button" class="add-subkegiatan btn btn-info btn-sm">
                          <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tombol Tambah Kegiatan -->
                <div class="d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Tambah Program -->
          <div class="d-flex justify-content-end">
            <button type="button" id="add-program" class="btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Program
            </button>
          </div>

          <!-- ================= HIDDEN TEMPLATES (OPSI B: di dalam section) ================= -->
          <template id="tpl-program">
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Program X</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program</label>
                <select class="form-select select-program">
                  <!-- options diisi oleh JS -->
                </select>
              </div>

              <div class="kegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h6 fw-medium">Kegiatan</h4>
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
                <label class="fw-medium">Kegiatan X</label>
                <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Nama Kegiatan</label>
                <textarea class="form-control nama-kegiatan" rows="2" placeholder="Masukkan nama kegiatan"></textarea>
              </div>

              <div class="subkegiatan-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="h6 fw-medium">Sub Kegiatan</h5>
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
                <label class="fw-medium">Sub Kegiatan X</label>
                <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="row mb-3">
                <div class="col-md-8">
                  <label class="form-label">Nama Sub Kegiatan</label>
                  <textarea class="form-control nama-subkegiatan" rows="2" placeholder="Masukkan nama sub kegiatan"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Target Anggaran</label>
                  <input type="text" class="form-control target-display" placeholder="Masukkan nilai anggaran" />
                  <input type="hidden" class="target-hidden" value="">
                </div>
              </div>
            </div>
          </template>
        </section>

        <!-- Tombol Aksi -->
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

  <script>
    const daftarProgram = <?= json_encode($program) ?>;
  </script>
  <script>
      // assets/js/adminOpd/renja/renja-form.js
document.addEventListener('DOMContentLoaded', function () {
  const programContainer = document.getElementById('program-container');
  const addProgramBtn = document.getElementById('add-program');
  const tplProgram = document.getElementById('tpl-program');
  const tplKegiatan = document.getElementById('tpl-kegiatan');
  const tplSub = document.getElementById('tpl-subkegiatan');
  const form = document.querySelector('form');

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
    kegiatanContainer.innerHTML = '';
    const kNode = createKegiatanNode();
    kegiatanContainer.appendChild(kNode);

    return node;
  }

  function createKegiatanNode() {
    const node = cloneTemplate(tplKegiatan);
    // ensure one sub inside
    const subContainer = node.querySelector('.subkegiatan-container');
    subContainer.innerHTML = '';
    const sNode = createSubNode();
    subContainer.appendChild(sNode);
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
        addKegiatanTo(kc);
      } else {
        kEls.forEach(kEl => {
          const sc = kEl.querySelector('.subkegiatan-container');
          const sEls = sc ? sc.querySelectorAll('.subkegiatan-item') : [];
          if (!sEls.length) addSubTo(sc);
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
});

  </script>
</body>
</html>

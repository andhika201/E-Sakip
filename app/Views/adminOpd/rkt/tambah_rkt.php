<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title) ?></title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
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

            <!-- PROGRAM PERTAMA -->
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium mb-0 program-title">Program 1</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm" style="display:none;">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Pilih Program PK</label>
                <select name="program[0][program_id]" class="form-select select-program" required>
                  <option value="">-- Pilih Program --</option>
                  <?php foreach ($program as $prog): ?>
                    <option value="<?= esc($prog['id']) ?>"><?= esc($prog['program_kegiatan']) ?></option>
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
                      <select name="program[0][kegiatan][0][kegiatan_id]" class="form-select select-kegiatan" required>
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
                                class="form-select select-subkegiatan" required>
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

  <script>
    const PROGRAMS = <?= json_encode($program) ?>;
    const KEGIATAN = <?= json_encode($kegiatanPk) ?>;
    const SUBS = <?= json_encode($subKegiatanPk) ?>;
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const programContainer = document.getElementById('program-container');
      const tplProgram = document.getElementById('tpl-program');
      const tplKegiatan = document.getElementById('tpl-kegiatan');
      const tplSub = document.getElementById('tpl-subkegiatan');

      const clone = (tpl) => tpl.content.firstElementChild.cloneNode(true);

      // >>> FIX FORMAT RUPIAH (tidak menambah 0 lagi)
      function toIntegerString(num) {
        if (num === null || num === undefined || num === '') return '';
        const n = Number(num);
        if (!Number.isFinite(n)) return '';
        // buang desimal, jadikan string
        return Math.round(n).toString();
      }

      function formatRupiah(num) {
        const s = toIntegerString(num);
        if (!s) return '';
        return s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }
      // <<<

      function fillProgramOptions(select) {
        select.innerHTML = '<option value="">-- Pilih Program --</option>';
        PROGRAMS.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = p.program_kegiatan;
          select.appendChild(opt);
        });
      }

      function fillKegiatanOptions(select, programId, currentValue = '') {
        select.innerHTML = '<option value="">-- Pilih Kegiatan --</option>';
        if (!programId) return;

        KEGIATAN.filter(k => String(k.program_id) === String(programId))
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

        SUBS.filter(s => String(s.kegiatan_id) === String(kegiatanId))
          .forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.sub_kegiatan;
            opt.dataset.anggaran = s.anggaran; // bisa "90000000.00"
            if (String(currentValue) === String(s.id)) opt.selected = true;
            select.appendChild(opt);
          });
      }

      // Re-index semua nama & label setelah tambah/hapus
      function reindexAll() {
        const programs = programContainer.querySelectorAll('.program-item');

        programs.forEach((pEl, pIdx) => {
          const pTitle = pEl.querySelector('.program-title');
          if (pTitle) pTitle.textContent = `Program ${pIdx + 1}`;

          const selProg = pEl.querySelector('.select-program');
          if (selProg) selProg.name = `program[${pIdx}][program_id]`;

          const kegiatanItems = pEl.querySelectorAll('.kegiatan-item');
          kegiatanItems.forEach((kEl, kIdx) => {
            const kTitle = kEl.querySelector('.kegiatan-title');
            if (kTitle) kTitle.textContent = `Kegiatan ${pIdx + 1}.${kIdx + 1}`;

            const selKeg = kEl.querySelector('.select-kegiatan');
            if (selKeg) {
              selKeg.name = `program[${pIdx}][kegiatan][${kIdx}][kegiatan_id]`;
            }

            const subItems = kEl.querySelectorAll('.subkegiatan-item');
            subItems.forEach((sEl, sIdx) => {
              const sTitle = sEl.querySelector('.sub-title');
              if (sTitle) sTitle.textContent = `Sub ${pIdx + 1}.${kIdx + 1}.${sIdx + 1}`;

              const selSub = sEl.querySelector('.select-subkegiatan');
              if (selSub) {
                selSub.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][sub_kegiatan_id]`;
              }
            });
          });
        });

        // tombol hapus program hanya muncul kalau > 1
        if (programs.length > 1) {
          programs.forEach(p => {
            const btn = p.querySelector('.remove-program');
            if (btn) btn.style.display = '';
          });
        } else {
          const btn = programs[0]?.querySelector('.remove-program');
          if (btn) btn.style.display = 'none';
        }
      }

      function addProgram() {
        const pNode = clone(tplProgram);
        const selProg = pNode.querySelector('.select-program');
        fillProgramOptions(selProg);

        // program baru otomatis punya 1 kegiatan + 1 sub
        const kc = pNode.querySelector('.kegiatan-container');
        const kNode = clone(tplKegiatan);
        kc.appendChild(kNode);

        const sc = kNode.querySelector('.subkegiatan-container');
        const sNode = clone(tplSub);
        sc.appendChild(sNode);

        const disp = sNode.querySelector('.target-display');
        const hid = sNode.querySelector('.target-hidden');
        disp.value = '-'; hid.value = '';

        programContainer.appendChild(pNode);
        reindexAll();
      }

      function addKegiatanTo(programItem) {
        const kc = programItem.querySelector('.kegiatan-container');
        const kNode = clone(tplKegiatan);

        const sc = kNode.querySelector('.subkegiatan-container');
        const sNode = clone(tplSub);
        sc.appendChild(sNode);

        const disp = sNode.querySelector('.target-display');
        const hid = sNode.querySelector('.target-hidden');
        disp.value = '-'; hid.value = '';

        kc.appendChild(kNode);
        reindexAll();

        const selProg = programItem.querySelector('.select-program');
        const selKeg = kNode.querySelector('.select-kegiatan');
        fillKegiatanOptions(selKeg, selProg.value);
        const selSub = sNode.querySelector('.select-subkegiatan');
        fillSubOptions(selSub, selKeg.value);
      }

      function addSubTo(kegiatanItem) {
        const sc = kegiatanItem.querySelector('.subkegiatan-container');
        const sNode = clone(tplSub);

        const disp = sNode.querySelector('.target-display');
        const hid = sNode.querySelector('.target-hidden');
        disp.value = '-'; hid.value = '';

        sc.appendChild(sNode);
        reindexAll();

        const selKeg = kegiatanItem.querySelector('.select-kegiatan');
        const selSub = sNode.querySelector('.select-subkegiatan');
        fillSubOptions(selSub, selKeg.value);
      }

      // INIT pertama
      (function initFirst() {
        const firstProg = programContainer.querySelector('.program-item');
        if (!firstProg) return;

        reindexAll();

        const selProg = firstProg.querySelector('.select-program');
        const selKeg = firstProg.querySelector('.select-kegiatan');
        const selSub = firstProg.querySelector('.select-subkegiatan');

        fillKegiatanOptions(selKeg, selProg.value);
        fillSubOptions(selSub, selKeg.value);
      })();

      // EVENT CLICK
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
          addKegiatanTo(pItem);
          return;
        }

        const addS = e.target.closest('.add-subkegiatan');
        if (addS) {
          e.preventDefault();
          const kItem = addS.closest('.kegiatan-item');
          addSubTo(kItem);
          return;
        }

        const remP = e.target.closest('.remove-program');
        if (remP) {
          e.preventDefault();
          const allProg = programContainer.querySelectorAll('.program-item');
          if (allProg.length <= 1) return;
          remP.closest('.program-item').remove();
          reindexAll();
          return;
        }

        const remK = e.target.closest('.remove-kegiatan');
        if (remK) {
          e.preventDefault();
          const pItem = remK.closest('.program-item');
          const allK = pItem.querySelectorAll('.kegiatan-item');
          if (allK.length <= 1) return;
          remK.closest('.kegiatan-item').remove();
          reindexAll();
          return;
        }

        const remS = e.target.closest('.remove-subkegiatan');
        if (remS) {
          e.preventDefault();
          const kItem = remS.closest('.kegiatan-item');
          const allS = kItem.querySelectorAll('.subkegiatan-item');
          if (allS.length <= 1) return;
          remS.closest('.subkegiatan-item').remove();
          reindexAll();
          return;
        }
      });

      // EVENT CHANGE (select)
      document.addEventListener('change', function (e) {
        const el = e.target;

        if (el.classList.contains('select-program')) {
          const pItem = el.closest('.program-item');
          const programId = el.value;

          pItem.querySelectorAll('.select-kegiatan').forEach(selKeg => {
            const current = selKeg.value;
            fillKegiatanOptions(selKeg, programId, current);

            const kItem = selKeg.closest('.kegiatan-item');
            kItem.querySelectorAll('.select-subkegiatan').forEach(selSub => {
              const currentSub = selSub.value;
              fillSubOptions(selSub, selKeg.value, currentSub);

              const sItem = selSub.closest('.subkegiatan-item');
              const disp = sItem.querySelector('.target-display');
              const hid = sItem.querySelector('.target-hidden');
              disp.value = '-';
              hid.value = '';
            });
          });
        }

        if (el.classList.contains('select-kegiatan')) {
          const kItem = el.closest('.kegiatan-item');
          const kegiatanId = el.value;

          kItem.querySelectorAll('.select-subkegiatan').forEach(selSub => {
            const currentSub = selSub.value;
            fillSubOptions(selSub, kegiatanId, currentSub);

            const sItem = selSub.closest('.subkegiatan-item');
            const disp = sItem.querySelector('.target-display');
            const hid = sItem.querySelector('.target-hidden');
            disp.value = '-';
            hid.value = '';
          });
        }

        if (el.classList.contains('select-subkegiatan')) {
          const sItem = el.closest('.subkegiatan-item');
          const opt = el.selectedOptions[0];
          const anggaranRaw = opt ? opt.dataset.anggaran : '';
          const disp = sItem.querySelector('.target-display');
          const hid = sItem.querySelector('.target-hidden');

          if (anggaranRaw) {
            const intStr = toIntegerString(anggaranRaw); // "90000000.00" -> "90000000"
            disp.value = 'Rp ' + formatRupiah(intStr);
            hid.value = intStr; // kalau nanti mau dipakai ke server, sudah bersih
          } else {
            disp.value = '-';
            hid.value = '';
          }
        }
      });
    });
  </script>
</body>

</html>
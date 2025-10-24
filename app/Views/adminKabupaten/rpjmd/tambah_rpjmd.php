<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah RPJMD e-SAKIP</title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <!-- Header + Sidebar -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Tambah RPJMD</h2>

      <form id="rpjmd-form" method="POST" action="<?= base_url('adminkab/rpjmd/save') ?>">
        <?= csrf_field() ?>

        <!-- Informasi Umum Misi -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Misi</h2>
          <div class="row">
            <div class="col-md-8">
              <label class="form-label">Misi RPJMD</label>
              <textarea name="misi" class="form-control mb-3" rows="2"
                placeholder="Contoh: Mewujudkan pembangunan berkelanjutan yang berpusat pada masyarakat"
                required></textarea>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Mulai</label>
              <input type="number" name="tahun_mulai" id="periode_start" class="form-control mb-3" value="2025"
                required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Akhir</label>
              <input type="number" name="tahun_akhir" id="periode_end" class="form-control mb-3" value="2029" readonly>
            </div>
          </div>
        </section>

        <!-- Daftar Tujuan -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 fw-semibold">Daftar Tujuan</h2>
          </div>

          <div id="tujuan-container">
            <!-- Tujuan 1 (default) -->
            <div class="tujuan-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="h6 fw-medium tujuan-label">Tujuan 1</label>
                <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="mb-3">
                <label class="form-label">Tujuan RPJMD</label>
                <textarea name="tujuan[0][tujuan_rpjmd]" class="form-control" rows="2"
                  placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel"
                  required></textarea>
              </div>

              <!-- Indikator Tujuan -->
              <div class="indikator-tujuan-section mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="fw-medium">Indikator Tujuan</h3>
                </div>

                <div class="indikator-tujuan-container">
                  <!-- Indikator Tujuan 1.1 (default) -->
                  <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium indikator-tujuan-label">Indikator Tujuan 1.1</label>
                      <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Indikator</label>
                      <input type="text" name="tujuan[0][indikator_tujuan][0][indikator_tujuan]" class="form-control"
                        placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
                    </div>

                    <!-- Target 5 Tahunan (Indikator Tujuan) -->
                    <div class="target-tujuan-section">
                      <h5 class="fw-medium mb-3">Target 5 Tahunan (Indikator Tujuan)</h5>
                      <div class="target-tujuan-container">
                        <!-- terisi 5 baris tahun sesuai periode oleh JS saat load -->
                        <?php for ($i = 0; $i < 5; $i++): ?>
                          <div class="target-item row g-2 align-items-center mb-2">
                            <div class="col-auto">
                              <input type="number"
                                name="tujuan[0][indikator_tujuan][0][target_tahunan_tujuan][<?= $i ?>][tahun]"
                                value="<?= 2025 + $i ?>" class="form-control form-control-sm tahun-target-tujuan"
                                style="width:80px;" readonly>
                            </div>
                            <div class="col">
                              <input type="text"
                                name="tujuan[0][indikator_tujuan][0][target_tahunan_tujuan][<?= $i ?>][target_tahunan]"
                                class="form-control form-control-sm" placeholder="Target <?= 2025 + $i ?>">
                            </div>
                          </div>
                        <?php endfor; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-2">
                  <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
                  </button>
                </div>
              </div>
              <!-- /Indikator Tujuan -->

              <!-- Sasaran -->
              <div class="sasaran-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
                </div>

                <div class="sasaran-container">
                  <!-- Sasaran 1.1 (default) -->
                  <div class="sasaran-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium sasaran-label">Sasaran 1.1</label>
                      <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Sasaran RPJMD</label>
                      <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2"
                        placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat"
                        required></textarea>
                    </div>

                    <!-- Indikator Sasaran -->
                    <div class="indikator-sasaran-section">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-medium">Indikator Sasaran</h4>
                      </div>

                      <div class="indikator-sasaran-container">
                        <!-- Indikator Sasaran 1.1.1 (default) -->
                        <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium indikator-sasaran-label">Indikator Sasaran 1.1.1</label>
                            <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>

                          <div class="row">
                            <div class="col-md-8">
                              <label class="form-label">Indikator</label>
                              <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]"
                                class="form-control mb-3"
                                placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan"
                                required>
                            </div>
                            <div class="col-md-4">
                              <label class="form-label">Satuan</label>
                              <select name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]"
                                class="form-select satuan-select mb-3" required></select>
                            </div>
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Definisi Operasional</label>
                            <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][definisi_op]"
                              class="form-control mb-3" rows="3"
                              placeholder="Contoh: Meningkatkan kapasitas SDM, digitalisasi, monev" required></textarea>
                          </div>

                          <!-- Target 5 Tahunan (Indikator Sasaran) -->
                          <div class="target-section">
                            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                            <div class="target-container">
                              <?php for ($i = 0; $i < 5; $i++): ?>
                                <div class="target-item row g-2 align-items-center mb-2">
                                  <div class="col-auto">
                                    <input type="number"
                                      name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $i ?>][tahun]"
                                      value="<?= 2025 + $i ?>" class="form-control form-control-sm tahun-target"
                                      style="width:80px;" readonly>
                                  </div>
                                  <div class="col">
                                    <input type="text"
                                      name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $i ?>][target_tahunan]"
                                      class="form-control form-control-sm" placeholder="Contoh: <?= 75 + $i * 2 ?>" required>
                                  </div>
                                </div>
                              <?php endfor; ?>
                            </div>
                          </div>
                          <!-- /Target 5 Tahunan (Indikator Sasaran) -->
                        </div>
                      </div>

                      <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                          <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                        </button>
                      </div>
                    </div>
                    <!-- /Indikator Sasaran -->
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-2">
                  <button type="button" class="add-sasaran btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Sasaran
                  </button>
                </div>
              </div>
              <!-- /Sasaran -->
            </div>
          </div>

          <!-- Tambah Tujuan -->
          <div class="d-flex justify-content-end mt-2">
            <button type="button" id="add-tujuan" class="btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Tujuan
            </button>
          </div>
        </section>

        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminkab/rpjmd') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- JS untuk form (tanpa dependensi eksternal agar tidak duplikat handler) -->
  <script>
    // ===== Helpers =====
    function getYears() {
      const start = parseInt(document.getElementById('periode_start').value || '2025', 10);
      const end = parseInt(document.getElementById('periode_end').value || (start + 4), 10);
      const arr = [];
      for (let y = start; y <= end; y++) arr.push(y);
      return arr;
    }

    function setPeriodeEndFromStart() {
      const startEl = document.getElementById('periode_start');
      const endEl = document.getElementById('periode_end');
      const start = parseInt(startEl.value || '2025', 10);
      endEl.value = start + 4;
    }

    function generateSatuanOptions() {
      // Jika ada helper global, gunakan itu
      if (typeof window.generateSatuanOptions === 'function' && window.__USE_GLOBAL_SATUAN__) {
        return window.generateSatuanOptions();
      }
      // fallback
      const units = ['', '%', 'Orang', 'Unit', 'Lembaga', 'Kegiatan', 'Km', 'Ha', 'PPM', 'Indeks', 'Nilai', 'Rp'];
      return units.map(u => `<option value="${u}">${u ? u : 'Pilih Satuan'}</option>`).join('');
    }

    function fillAllSatuan() {
      document.querySelectorAll('.satuan-select').forEach(sel => {
        sel.innerHTML = generateSatuanOptions();
      });
    }

    // ===== Templates =====
    function indikatorTujuanTemplate(tjIdx, itIdx, years) {
      return `
      <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium indikator-tujuan-label">Indikator Tujuan ${tjIdx + 1}.${itIdx + 1}</label>
          <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm">
            <i class="fas fa-trash"></i>
          </button>
        </div>
        <div class="mb-3">
          <label class="form-label">Indikator</label>
          <input type="text"
                 name="tujuan[${tjIdx}][indikator_tujuan][${itIdx}][indikator_tujuan]"
                 class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
        </div>
        <div class="target-tujuan-section">
          <h5 class="fw-medium mb-3">Target 5 Tahunan (Indikator Tujuan)</h5>
          <div class="target-tujuan-container">
            ${years.map((y, i) => `
              <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                  <input type="number"
                         name="tujuan[${tjIdx}][indikator_tujuan][${itIdx}][target_tahunan_tujuan][${i}][tahun]"
                         value="${y}" class="form-control form-control-sm tahun-target-tujuan" style="width:80px;" readonly>
                </div>
                <div class="col">
                  <input type="text"
                         name="tujuan[${tjIdx}][indikator_tujuan][${itIdx}][target_tahunan_tujuan][${i}][target_tahunan]"
                         class="form-control form-control-sm" placeholder="Target ${y}">
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>`;
    }

    function indikatorSasaranTemplate(tjIdx, ssIdx, isIdx, years) {
      return `
      <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium indikator-sasaran-label">Indikator Sasaran ${tjIdx + 1}.${ssIdx + 1}.${isIdx + 1}</label>
          <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <div class="row">
          <div class="col-md-8">
            <label class="form-label">Indikator</label>
            <input type="text"
                   name="tujuan[${tjIdx}][sasaran][${ssIdx}][indikator_sasaran][${isIdx}][indikator_sasaran]"
                   class="form-control mb-3"
                   placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <select name="tujuan[${tjIdx}][sasaran][${ssIdx}][indikator_sasaran][${isIdx}][satuan]"
                    class="form-select satuan-select mb-3" required></select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Definisi Operasional</label>
          <textarea name="tujuan[${tjIdx}][sasaran][${ssIdx}][indikator_sasaran][${isIdx}][definisi_op]"
                    class="form-control mb-3" rows="3"
                    placeholder="Contoh: Meningkatkan kapasitas SDM, digitalisasi, monev" required></textarea>
        </div>

        <div class="target-section">
          <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
          <div class="target-container">
            ${years.map((y, i) => `
              <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                  <input type="number"
                         name="tujuan[${tjIdx}][sasaran][${ssIdx}][indikator_sasaran][${isIdx}][target_tahunan][${i}][tahun]"
                         value="${y}" class="form-control form-control-sm tahun-target" style="width:80px;" readonly>
                </div>
                <div class="col">
                  <input type="text"
                         name="tujuan[${tjIdx}][sasaran][${ssIdx}][indikator_sasaran][${isIdx}][target_tahunan][${i}][target_tahunan]"
                         class="form-control form-control-sm" placeholder="Contoh: 75">
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>`;
    }

    function sasaranItemTemplate(tjIdx, ssIdx, years) {
      return `
      <div class="sasaran-item border rounded p-3 bg-white mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium sasaran-label">Sasaran ${tjIdx + 1}.${ssIdx + 1}</label>
          <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm">
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <div class="mb-3">
          <label class="form-label">Sasaran RPJMD</label>
          <textarea name="tujuan[${tjIdx}][sasaran][${ssIdx}][sasaran_rpjmd]" class="form-control" rows="2"
                    placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
        </div>

        <div class="indikator-sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-medium">Indikator Sasaran</h4>
          </div>

          <div class="indikator-sasaran-container">
            ${indikatorSasaranTemplate(tjIdx, ssIdx, 0, years)}
          </div>

          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
        </div>
      </div>`;
    }

    function tujuanItemTemplate(tjIdx, years) {
      return `
      <div class="tujuan-item bg-light border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="h6 fw-medium tujuan-label">Tujuan ${tjIdx + 1}</label>
          <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm">
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <div class="mb-3">
          <label class="form-label">Tujuan RPJMD</label>
          <textarea name="tujuan[${tjIdx}][tujuan_rpjmd]" class="form-control" rows="2"
                    placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required></textarea>
        </div>

        <div class="indikator-tujuan-section mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Indikator Tujuan</h3>
          </div>

          <div class="indikator-tujuan-container">
            ${indikatorTujuanTemplate(tjIdx, 0, years)}
          </div>

          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
            </button>
          </div>
        </div>

        <div class="sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
          </div>

          <div class="sasaran-container">
            ${sasaranItemTemplate(tjIdx, 0, years)}
          </div>

          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="add-sasaran btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran
            </button>
          </div>
        </div>
      </div>`;
    }

    // ===== Indexing + Labels =====
    function updateLabels() {
      document.querySelectorAll('#tujuan-container .tujuan-item').forEach((tItem, tjIdx) => {
        tItem.querySelector('.tujuan-label').textContent = `Tujuan ${tjIdx + 1}`;

        // indikator tujuan labels
        tItem.querySelectorAll('.indikator-tujuan-item').forEach((itItem, itIdx) => {
          const lbl = itItem.querySelector('.indikator-tujuan-label');
          if (lbl) lbl.textContent = `Indikator Tujuan ${tjIdx + 1}.${itIdx + 1}`;
        });

        // sasaran + indikator sasaran labels
        tItem.querySelectorAll('.sasaran-item').forEach((sItem, ssIdx) => {
          const sasLbl = sItem.querySelector('.sasaran-label');
          if (sasLbl) sasLbl.textContent = `Sasaran ${tjIdx + 1}.${ssIdx + 1}`;

          sItem.querySelectorAll('.indikator-sasaran-item').forEach((isItem, isIdx) => {
            const lbl = isItem.querySelector('.indikator-sasaran-label');
            if (lbl) lbl.textContent = `Indikator Sasaran ${tjIdx + 1}.${ssIdx + 1}.${isIdx + 1}`;
          });
        });
      });
    }

    // ===== Refresh Years in existing inputs =====
    function refreshAllYears() {
      const years = getYears();

      // Indikator tujuan
      document.querySelectorAll('.target-tujuan-container').forEach(container => {
        const tahunInputs = container.querySelectorAll('.tahun-target-tujuan');
        const targetInputs = container.querySelectorAll('input[name*="[target_tahunan_tujuan]"][name$="[target_tahunan]"]');
        for (let i = 0; i < tahunInputs.length && i < years.length; i++) {
          tahunInputs[i].value = years[i];
        }
        for (let i = 0; i < targetInputs.length && i < years.length; i++) {
          targetInputs[i].setAttribute('placeholder', 'Target ' + years[i]);
        }
      });

      // Indikator sasaran
      document.querySelectorAll('.target-container').forEach(container => {
        const tahunInputs = container.querySelectorAll('.tahun-target');
        for (let i = 0; i < tahunInputs.length && i < years.length; i++) {
          tahunInputs[i].value = years[i];
        }
      });
    }

    // ===== Event Handlers (delegation) =====
    document.addEventListener('DOMContentLoaded', () => {
      // Lock periode akhir sesuai periode mulai
      setPeriodeEndFromStart();
      // Isi satuan
      fillAllSatuan();
      // Refresh tahun awal
      refreshAllYears();
      // Update labels
      updateLabels();

      // Perubahan periode mulai => set akhir + refresh
      document.getElementById('periode_start').addEventListener('input', () => {
        setPeriodeEndFromStart();
        refreshAllYears();
      });

      // Tambah Tujuan
      document.getElementById('add-tujuan').addEventListener('click', () => {
        const container = document.getElementById('tujuan-container');
        const tjIdx = container.querySelectorAll('.tujuan-item').length;
        const html = tujuanItemTemplate(tjIdx, getYears());
        container.insertAdjacentHTML('beforeend', html);
        fillAllSatuan();
        updateLabels();
      });

      // Delegasi klik (hapus/tambah indikator/sasaran)
      document.body.addEventListener('click', (e) => {
        // Hapus tujuan
        if (e.target.closest('.remove-tujuan')) {
          const item = e.target.closest('.tujuan-item');
          item.parentNode.removeChild(item);
          updateLabels();
          return;
        }

        // Tambah indikator tujuan
        if (e.target.closest('.add-indikator-tujuan')) {
          const tujuanItem = e.target.closest('.tujuan-item');
          const tjIdx = Array.from(document.querySelectorAll('#tujuan-container .tujuan-item')).indexOf(tujuanItem);
          const container = tujuanItem.querySelector('.indikator-tujuan-container');
          const itIdx = container.querySelectorAll('.indikator-tujuan-item').length;
          container.insertAdjacentHTML('beforeend', indikatorTujuanTemplate(tjIdx, itIdx, getYears()));
          updateLabels();
          return;
        }

        // Hapus indikator tujuan
        if (e.target.closest('.remove-indikator-tujuan')) {
          const item = e.target.closest('.indikator-tujuan-item');
          item.parentNode.removeChild(item);
          updateLabels();
          return;
        }

        // Tambah sasaran
        if (e.target.closest('.add-sasaran')) {
          const tujuanItem = e.target.closest('.tujuan-item');
          const tjIdx = Array.from(document.querySelectorAll('#tujuan-container .tujuan-item')).indexOf(tujuanItem);
          const sContainer = tujuanItem.querySelector('.sasaran-container');
          const ssIdx = sContainer.querySelectorAll('.sasaran-item').length;
          sContainer.insertAdjacentHTML('beforeend', sasaranItemTemplate(tjIdx, ssIdx, getYears()));
          fillAllSatuan();
          updateLabels();
          return;
        }

        // Hapus sasaran
        if (e.target.closest('.remove-sasaran')) {
          const item = e.target.closest('.sasaran-item');
          item.parentNode.removeChild(item);
          updateLabels();
          return;
        }

        // Tambah indikator sasaran
        if (e.target.closest('.add-indikator-sasaran')) {
          const sasaranItem = e.target.closest('.sasaran-item');
          const tujuanItem = e.target.closest('.tujuan-item');
          const tjIdx = Array.from(document.querySelectorAll('#tujuan-container .tujuan-item')).indexOf(tujuanItem);
          const ssIdx = Array.from(tujuanItem.querySelectorAll('.sasaran-item')).indexOf(sasaranItem);
          const container = sasaranItem.querySelector('.indikator-sasaran-container');
          const isIdx = container.querySelectorAll('.indikator-sasaran-item').length;
          container.insertAdjacentHTML('beforeend', indikatorSasaranTemplate(tjIdx, ssIdx, isIdx, getYears()));
          fillAllSatuan();
          updateLabels();
          return;
        }

        // Hapus indikator sasaran
        if (e.target.closest('.remove-indikator-sasaran')) {
          const item = e.target.closest('.indikator-sasaran-item');
          item.parentNode.removeChild(item);
          updateLabels();
          return;
        }
      });
    });
  </script>
</body>

</html>
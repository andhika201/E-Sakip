<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Edit Renstra e-SAKIP') ?></title>
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
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit Renstra</h2>

      <div id="alert-container">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
      </div>

      <form id="renstra-form" method="POST"
        action="<?= base_url('adminopd/renstra/update/' . ($renstra_data['sasaran_id'] ?? '')) ?>">
        <?= csrf_field() ?>

        <!-- ================= INFORMASI UMUM ================= -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Renstra</h2>
          <div class="row">

            <!-- Sasaran RPJMD yang dituju -->
            <div class="col-md-8">
              <label class="form-label">Sasaran RPJMD yang Dituju</label>
              <select name="rpjmd_sasaran_id" id="rpjmd_sasaran_select" class="form-select select23" required>
                <option value="">Pilih Sasaran RPJMD</option>
                <?php if (!empty($rpjmd_sasaran)): ?>
                  <?php foreach ($rpjmd_sasaran as $s): ?>
                    <?php
                    $selected = '';
                    if (
                      !empty($renstra_tujuan['rpjmd_sasaran_id']) &&
                      $renstra_tujuan['rpjmd_sasaran_id'] == $s['id']
                    ) {
                      $selected = 'selected';
                    }
                    ?>
                    <option value="<?= esc($s['id']) ?>" <?= $selected ?>>
                      <?= esc($s['sasaran_rpjmd'] ?? $s['sasaran']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <small class="text-muted">
                Pilih Sasaran RPJMD yang menjadi rujukan Tujuan Renstra.
              </small>
            </div>

            <!-- Tujuan Renstra -->
            <div class="col-md-4">
              <label class="form-label">Tujuan Renstra</label>
              <input type="text" name="tujuan_renstra" id="tujuan_renstra_input" class="form-control mb-3"
                value="<?= esc($renstra_tujuan['tujuan'] ?? '') ?>" placeholder="Ketik tujuan renstra" required
                autocomplete="off">
              <small class="text-muted">Isi tujuan Renstra yang ingin dicapai.</small>
            </div>

            <!-- Tahun -->
            <div class="col-md-2 mt-2">
              <label class="form-label">Tahun Mulai</label>
              <input type="number" name="tahun_mulai" id="tahun_mulai" class="form-control mb-3"
                value="<?= esc($renstra_data['tahun_mulai'] ?? '') ?>" placeholder="Contoh: 2025" required>
            </div>
            <div class="col-md-2 mt-2">
              <label class="form-label">Tahun Akhir</label>
              <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control mb-3"
                value="<?= esc($renstra_data['tahun_akhir'] ?? '') ?>" readonly required>
            </div>
          </div>
        </section>

        <!-- ================= INDIKATOR TUJUAN ================= -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Indikator Tujuan Renstra</h2>

          <div id="indikator-tujuan-container">
            <?php if (!empty($indikator_tujuan)): ?>
              <?php foreach ($indikator_tujuan as $idx => $it): ?>
                <div class="indikator-tujuan-item border rounded p-3 bg-light mb-3" data-indeks="<?= $idx ?>">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator Tujuan <?= $idx + 1 ?></label>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-indikator-tujuan">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Indikator Tujuan</label>
                    <textarea name="indikator_tujuan[<?= $idx ?>][indikator_tujuan]" class="form-control" rows="2"
                      required><?= esc($it['indikator_tujuan'] ?? '') ?></textarea>
                  </div>

                  <!-- Target Tujuan Tahunan -->
                  <div class="target-section">
                    <h5 class="fw-medium mb-3">Target Tujuan per Tahun</h5>
                    <div class="target-container">
                      <?php
                      $targetsT = $it['target_tahunan'] ?? [];
                      if (!empty($targetsT)):
                        foreach ($targetsT as $tIndex => $t): ?>
                          <div class="target-item row g-2 align-items-center mb-2">
                            <div class="col-auto">
                              <input type="number"
                                name="indikator_tujuan[<?= $idx ?>][target_tahunan][<?= $tIndex ?>][tahun]"
                                class="form-control form-control-sm tahun-target-tujuan" style="width: 80px;"
                                value="<?= esc($t['tahun'] ?? '') ?>" readonly>
                            </div>
                            <div class="col">
                              <input type="text"
                                name="indikator_tujuan[<?= $idx ?>][target_tahunan][<?= $tIndex ?>][target]"
                                class="form-control form-control-sm" value="<?= esc($t['target_tahunan'] ?? '') ?>"
                                placeholder="Target">
                            </div>
                          </div>
                        <?php endforeach;
                      else:
                        for ($i = 0; $i < 5; $i++): ?>
                          <div class="target-item row g-2 align-items-center mb-2">
                            <div class="col-auto">
                              <input type="number"
                                name="indikator_tujuan[<?= $idx ?>][target_tahunan][<?= $i ?>][tahun]"
                                class="form-control form-control-sm tahun-target-tujuan" style="width: 80px;" readonly>
                            </div>
                            <div class="col">
                              <input type="text"
                                name="indikator_tujuan[<?= $idx ?>][target_tahunan][<?= $i ?>][target]"
                                class="form-control form-control-sm" placeholder="Target">
                            </div>
                          </div>
                      <?php endfor;
                      endif;
                      ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- Kalau belum ada indikator tujuan, buat 1 default -->
              <div class="indikator-tujuan-item border rounded p-3 bg-light mb-3" data-indeks="0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <label class="fw-medium">Indikator Tujuan 1</label>
                  <button type="button" class="btn btn-outline-danger btn-sm remove-indikator-tujuan">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>

                <div class="mb-3">
                  <label class="form-label">Indikator Tujuan</label>
                  <textarea name="indikator_tujuan[0][indikator_tujuan]" class="form-control" rows="2"
                    required></textarea>
                </div>

                <div class="row mb-3">
                  <div class="col-md-4">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="indikator_tujuan[0][satuan]" class="form-control"
                      placeholder="Contoh: Nilai, Persen, Unit">
                  </div>
                </div>

                <div class="target-section">
                  <h5 class="fw-medium mb-3">Target Tujuan per Tahun</h5>
                  <div class="target-container">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                      <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                          <input type="number" name="indikator_tujuan[0][target_tahunan][<?= $i ?>][tahun]"
                            class="form-control form-control-sm tahun-target-tujuan" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                          <input type="text" name="indikator_tujuan[0][target_tahunan][<?= $i ?>][target]"
                            class="form-control form-control-sm" placeholder="Target">
                        </div>
                      </div>
                    <?php endfor; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Tombol Tambah Indikator Tujuan -->
          <div class="text-end mt-3">
            <button type="button" id="add-indikator-tujuan" class="btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
            </button>
          </div>
        </section>

        <!-- ================= SASARAN RENSTRA ================= -->
        <section>
          <h2 class="h5 fw-semibold mb-3">Daftar Sasaran Renstra</h2>

          <div id="sasaran-renstra-container">
            <?php
            // Untuk edit ini, kita fokus 1 sasaran (sasaran_id)
            $sIndex = 0;
            ?>
            <div class="sasaran-renstra-item bg-light border rounded p-3 mb-3" data-sasaran-index="<?= $sIndex ?>">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium sasaran-title">Sasaran Renstra</label>
                <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran Renstra</label>
                  <textarea name="sasaran_renstra[<?= $sIndex ?>][sasaran]" class="form-control" rows="2"
                    required><?= esc($renstra_data['sasaran'] ?? '') ?></textarea>
                </div>
              </div>

              <!-- Indikator Sasaran -->
              <div class="indikator-sasaran-section">
                <h4 class="h5 fw-medium mb-3">Indikator Sasaran</h4>

                <?php
                $indikatorList = $renstra_data['indikator_sasaran'] ?? [];
                $nextInd = count($indikatorList);
                if ($nextInd < 1) {
                  $nextInd = 1;
                }
                ?>

                <div class="indikator-sasaran-container" data-next-indikator-index="<?= $nextInd ?>">
                  <?php if (!empty($indikatorList)): ?>
                    <?php foreach ($indikatorList as $iIdx => $ind): ?>
                      <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3" data-indeks="<?= $iIdx ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <label class="fw-medium indikator-title">
                            Indikator Sasaran <?= ($sIndex + 1) . '.' . ($iIdx + 1) ?>
                          </label>
                          <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Indikator Sasaran</label>
                          <textarea
                            name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][indikator_sasaran]"
                            class="form-control" rows="2" required><?= esc($ind['indikator_sasaran'] ?? '') ?></textarea>
                        </div>

                        <div class="row mb-3">
                          <div class="col-md-6">
                            <label class="form-label">Satuan</label>
                            <select
                              name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][satuan]"
                              class="form-select select2 satuan-select"
                              required>
                              <option value="">Pilih Satuan</option>
                              <?php if (!empty($satuan_options)): ?>
                                <?php foreach ($satuan_options as $key => $label): ?>
                                  <option
                                    value="<?= esc($key) ?>"
                                    <?= (isset($ind['satuan']) && $ind['satuan'] == $key) ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                  </option>
                                <?php endforeach; ?>
                              <?php endif; ?>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Jenis Indikator</label>
                            <select
                              name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][jenis_indikator]"
                              class="form-select select2"
                              required>
                              <option value="">Pilih Jenis Indikator</option>
                              <option value="positif"
                                <?= (isset($ind['jenis_indikator']) && $ind['jenis_indikator'] === 'positif') ? 'selected' : '' ?>>
                                Indikator Positif (naik = baik)
                              </option>
                              <option value="negatif"
                                <?= (isset($ind['jenis_indikator']) && $ind['jenis_indikator'] === 'negatif') ? 'selected' : '' ?>>
                                Indikator Negatif (turun = baik)
                              </option>
                            </select>
                          </div>
                        </div>

                        <!-- Target Tahunan -->
                        <div class="target-section">
                          <h5 class="fw-medium mb-3">Target Sasaran per Tahun</h5>
                          <div class="target-container">
                            <?php
                            $targetsS = $ind['target_tahunan'] ?? [];
                            if (!empty($targetsS)):
                              foreach ($targetsS as $tIdx => $t): ?>
                                <div class="target-item row g-2 align-items-center mb-2">
                                  <div class="col-auto">
                                    <input type="number"
                                      name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][target_tahunan][<?= $tIdx ?>][tahun]"
                                      class="form-control form-control-sm tahun-target" style="width: 80px;"
                                      value="<?= esc($t['tahun'] ?? '') ?>" readonly>
                                  </div>
                                  <div class="col">
                                    <input type="text"
                                      name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][target_tahunan][<?= $tIdx ?>][target]"
                                      class="form-control form-control-sm" value="<?= esc($t['target'] ?? '') ?>"
                                      placeholder="Target" required>
                                  </div>
                                </div>
                              <?php endforeach;
                            else:
                              for ($i = 0; $i < 5; $i++): ?>
                                <div class="target-item row g-2 align-items-center mb-2">
                                  <div class="col-auto">
                                    <input type="number"
                                      name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][target_tahunan][<?= $i ?>][tahun]"
                                      class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                  </div>
                                  <div class="col">
                                    <input type="text"
                                      name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][<?= $iIdx ?>][target_tahunan][<?= $i ?>][target]"
                                      class="form-control form-control-sm" placeholder="Target" required>
                                  </div>
                                </div>
                            <?php endfor;
                            endif;
                            ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <!-- Kalau tidak ada indikator, buat default 1 -->
                    <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3" data-indeks="0">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium indikator-title">Indikator Sasaran 1.1</label>
                        <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Indikator Sasaran</label>
                        <textarea name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][0][indikator_sasaran]"
                          class="form-control" rows="2" required></textarea>
                      </div>

                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label class="form-label">Satuan</label>
                          <select
                            name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][0][satuan]"
                            class="form-select"
                            required>
                            <option value="">Pilih Satuan</option>
                            <?php if (!empty($satuan_options)): ?>
                              <?php foreach ($satuan_options as $key => $label): ?>
                                <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Jenis Indikator</label>
                          <select
                            name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][0][jenis_indikator]"
                            class="form-select"
                            required>
                            <option value="">Pilih Jenis Indikator</option>
                            <option value="positif">Indikator Positif (naik = baik)</option>
                            <option value="negatif">Indikator Negatif (turun = baik)</option>
                          </select>
                        </div>
                      </div>

                      <div class="target-section">
                        <h5 class="fw-medium mb-3">Target Sasaran per Tahun</h5>
                        <div class="target-container">
                          <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="target-item row g-2 align-items-center mb-2">
                              <div class="col-auto">
                                <input type="number"
                                  name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][0][target_tahunan][<?= $i ?>][tahun]"
                                  class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                              </div>
                              <div class="col">
                                <input type="text"
                                  name="sasaran_renstra[<?= $sIndex ?>][indikator_sasaran][0][target_tahunan][<?= $i ?>][target]"
                                  class="form-control form-control-sm" placeholder="Target" required>
                              </div>
                            </div>
                          <?php endfor; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Tombol Tambah Indikator Sasaran -->
                <div class="text-end mt-3">
                  <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Tambah Sasaran Renstra -->
          <div class="text-end mt-3">
            <button type="button" id="add-sasaran-renstra" class="btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran Renstra
            </button>
          </div>
        </section>

        <!-- ================= TOMBOL AKSI ================= -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminopd/renstra') ?>" class="btn btn-secondary">
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
      $(context).find('.select2').each(function() {
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

    $(document).ready(function() {
      initSelect2();
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tahunMulaiInput = document.getElementById('tahun_mulai');
      const tahunAkhirInput = document.getElementById('tahun_akhir');

      const indikatorTujuanContainer = document.getElementById('indikator-tujuan-container');
      const addIndikatorTujuanBtn = document.getElementById('add-indikator-tujuan');

      const sasaranContainer = document.getElementById('sasaran-renstra-container');
      const addSasaranBtn = document.getElementById('add-sasaran-renstra');

      // ============================
      // FUNGSI: UPDATE TAHUN TARGET
      // ============================
      function updateTahunTarget() {
        const mulai = parseInt(tahunMulaiInput?.value);
        const akhir = parseInt(tahunAkhirInput?.value);

        if (isNaN(mulai) || isNaN(akhir)) return;

        // Target Tujuan
        if (indikatorTujuanContainer) {
          indikatorTujuanContainer.querySelectorAll('.indikator-tujuan-item').forEach(item => {
            const tahunInputs = item.querySelectorAll('.tahun-target-tujuan');
            tahunInputs.forEach((input, idx) => {
              const tahun = mulai + idx;
              input.value = (tahun <= akhir) ? tahun : '';
            });
          });
        }

        // Target Sasaran
        if (sasaranContainer) {
          sasaranContainer.querySelectorAll('.sasaran-renstra-item').forEach(sasaranItem => {
            sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach(indItem => {
              const tahunInputs = indItem.querySelectorAll('.tahun-target');
              tahunInputs.forEach((input, idx) => {
                const tahun = mulai + idx;
                input.value = (tahun <= akhir) ? tahun : '';
              });
            });
          });
        }
      }

      // ============================
      // OTOMATIS TAHUN AKHIR
      // ============================
      if (tahunMulaiInput && tahunAkhirInput) {
        tahunMulaiInput.addEventListener('input', function() {
          const mulai = parseInt(this.value);
          if (!isNaN(mulai)) {
            tahunAkhirInput.value = mulai + 4;
          } else {
            tahunAkhirInput.value = '';
          }
          updateTahunTarget();
        });

        tahunAkhirInput.addEventListener('input', updateTahunTarget);

        // Panggil sekali di awal
        updateTahunTarget();
      }

      // ============================
      // TEMPLATE BUILDER
      // ============================

      function buildIndikatorTujuanHTML(idx) {
        return `
      <div class="indikator-tujuan-item border rounded p-3 bg-light mb-3" data-indeks="${idx}">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Tujuan ${idx + 1}</label>
          <button type="button" class="btn btn-outline-danger btn-sm remove-indikator-tujuan">
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <div class="mb-3">
          <label class="form-label">Indikator Tujuan</label>
          <textarea name="indikator_tujuan[${idx}][indikator_tujuan]"
                    class="form-control"
                    rows="2"
                    required></textarea>
        </div>

        <div class="target-section">
          <h5 class="fw-medium mb-3">Target Tujuan per Tahun</h5>
          <div class="target-container">
            ${[0, 1, 2, 3, 4].map(i => `
              <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                  <input type="number"
                         name="indikator_tujuan[${idx}][target_tahunan][${i}][tahun]"
                         class="form-control form-control-sm tahun-target-tujuan"
                         style="width: 80px;"
                         readonly>
                </div>
                <div class="col">
                  <input type="text"
                         name="indikator_tujuan[${idx}][target_tahunan][${i}][target]"
                         class="form-control form-control-sm"
                         placeholder="Target">
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;
      }

      function buildIndikatorSasaranHTML(sIndex, iIndex) {
        return `
      <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3" data-indeks="${iIndex}">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium indikator-title">Indikator Sasaran ${sIndex + 1}.${iIndex + 1}</label>
          <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <div class="mb-3">
          <label class="form-label">Indikator Sasaran</label>
          <textarea name="sasaran_renstra[${sIndex}][indikator_sasaran][${iIndex}][indikator_sasaran]"
                    class="form-control"
                    rows="2"
                    required></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Satuan</label>
            <select
              name="sasaran_renstra[${sIndex}][indikator_sasaran][${iIndex}][satuan]"
              class="form-select"
              required
            >
              <option value="">Pilih Satuan</option>
              <?php if (!empty($satuan_options)): ?>
                <?php foreach ($satuan_options as $key => $label): ?>
                  <option value="<?= esc($key) ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Jenis Indikator</label>
            <select
              name="sasaran_renstra[${sIndex}][indikator_sasaran][${iIndex}][jenis_indikator]"
              class="form-select"
              required
            >
              <option value="">Pilih Jenis Indikator</option>
              <option value="positif">Indikator Positif (naik = baik)</option>
              <option value="negatif">Indikator Negatif (turun = baik)</option>
            </select>
          </div>
        </div>

        <div class="target-section">
          <h5 class="fw-medium mb-3">Target Sasaran per Tahun</h5>
          <div class="target-container">
            ${[0, 1, 2, 3, 4].map(i => `
              <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                  <input type="number"
                         name="sasaran_renstra[${sIndex}][indikator_sasaran][${iIndex}][target_tahunan][${i}][tahun]"
                         class="form-control form-control-sm tahun-target"
                         style="width: 80px;"
                         readonly>
                </div>
                <div class="col">
                  <input type="text"
                         name="sasaran_renstra[${sIndex}][indikator_sasaran][${iIndex}][target_tahunan][${i}][target]"
                         class="form-control form-control-sm"
                         placeholder="Target"
                         required>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;
      }

      function buildSasaranHTML(sIndex) {
        return `
      <div class="sasaran-renstra-item bg-light border rounded p-3 mb-3" data-sasaran-index="${sIndex}">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium sasaran-title">Sasaran Renstra ${sIndex + 1}</label>
          <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm">
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran Renstra</label>
            <textarea name="sasaran_renstra[${sIndex}][sasaran]"
                      class="form-control"
                      rows="2"
                      placeholder="Masukkan sasaran renstra"
                      required></textarea>
          </div>
        </div>

        <div class="indikator-sasaran-section">
          <h4 class="h5 fw-medium mb-3">Indikator Sasaran</h4>

          <div class="indikator-sasaran-container" data-next-indikator-index="1">
            ${buildIndikatorSasaranHTML(sIndex, 0)}
          </div>

          <div class="text-end mt-3">
            <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
        </div>
      </div>
    `;
      }

      // ============================
      // LOGIKA: TAMBAH INDIKATOR TUJUAN
      // ============================
      if (addIndikatorTujuanBtn && indikatorTujuanContainer) {
        addIndikatorTujuanBtn.addEventListener('click', function() {
          // Hitung index berikut
          let nextIndex = 0;
          indikatorTujuanContainer.querySelectorAll('.indikator-tujuan-item').forEach(item => {
            const idx = parseInt(item.getAttribute('data-indeks'));
            if (!isNaN(idx) && idx >= nextIndex) nextIndex = idx + 1;
          });

          indikatorTujuanContainer.insertAdjacentHTML('beforeend', buildIndikatorTujuanHTML(nextIndex));
          updateTahunTarget();
        });

        indikatorTujuanContainer.addEventListener('click', function(e) {
          if (e.target.closest('.remove-indikator-tujuan')) {
            const item = e.target.closest('.indikator-tujuan-item');
            if (!item) return;
            item.remove();

            // Kalau habis semua, tambah satu default
            if (indikatorTujuanContainer.querySelectorAll('.indikator-tujuan-item').length === 0) {
              indikatorTujuanContainer.insertAdjacentHTML('beforeend', buildIndikatorTujuanHTML(0));
            }
            updateTahunTarget();
          }
        });
      }

      // ============================
      // LOGIKA: TAMBAH / HAPUS SASARAN + INDIKATOR SASARAN
      // ============================
      if (addSasaranBtn && sasaranContainer) {
        addSasaranBtn.addEventListener('click', function() {
          let nextSIndex = 0;
          sasaranContainer.querySelectorAll('.sasaran-renstra-item').forEach(item => {
            const sIdx = parseInt(item.getAttribute('data-sasaran-index'));
            if (!isNaN(sIdx) && sIdx >= nextSIndex) nextSIndex = sIdx + 1;
          });

          sasaranContainer.insertAdjacentHTML('beforeend', buildSasaranHTML(nextSIndex));
          updateTahunTarget();
        });

        sasaranContainer.addEventListener('click', function(e) {
          // Hapus sasaran
          if (e.target.closest('.remove-sasaran-renstra')) {
            const item = e.target.closest('.sasaran-renstra-item');
            if (!item) return;
            item.remove();

            if (sasaranContainer.querySelectorAll('.sasaran-renstra-item').length === 0) {
              sasaranContainer.insertAdjacentHTML('beforeend', buildSasaranHTML(0));
            }
            updateTahunTarget();
          }

          // Tambah indikator sasaran
          if (e.target.closest('.add-indikator-sasaran')) {
            const sasaranItem = e.target.closest('.sasaran-renstra-item');
            if (!sasaranItem) return;

            const sIndex = parseInt(sasaranItem.getAttribute('data-sasaran-index'));
            const indikContainer = sasaranItem.querySelector('.indikator-sasaran-container');
            if (!indikContainer) return;

            let nextIdx = parseInt(indikContainer.getAttribute('data-next-indikator-index')) || 0;

            indikContainer.insertAdjacentHTML('beforeend', buildIndikatorSasaranHTML(sIndex, nextIdx));
            indikContainer.setAttribute('data-next-indikator-index', nextIdx + 1);

            updateTahunTarget();
          }

          // Hapus indikator sasaran
          if (e.target.closest('.remove-indikator-sasaran')) {
            const indItem = e.target.closest('.indikator-sasaran-item');
            if (!indItem) return;

            const indikContainer = indItem.closest('.indikator-sasaran-container');
            indItem.remove();

            if (indikContainer && indikContainer.querySelectorAll('.indikator-sasaran-item').length === 0) {
              // kalau kosong, buat satu default
              const sasaranItem = indikContainer.closest('.sasaran-renstra-item');
              const sIndex = parseInt(sasaranItem.getAttribute('data-sasaran-index'));
              indikContainer.insertAdjacentHTML('beforeend', buildIndikatorSasaranHTML(sIndex, 0));
              indikContainer.setAttribute('data-next-indikator-index', 1);
            }

            updateTahunTarget();
          }
        });
      }

      // Inisialisasi tahun di awal
      updateTahunTarget();
    });
  </script>
</body>

</html>
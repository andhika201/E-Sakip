<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit RPJMD - e-SAKIP</title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .tahun-target,
    .tahun-target-tujuan {
      width: 90px
    }

    .indikator-tujuan-item,
    .indikator-sasaran-item {
      position: relative
    }

    .btn-icon {
      display: inline-flex;
      align-items: center;
      gap: .4rem
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:1200px">
      <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e">Edit RPJMD</h2>

      <?php
      $tmulai = isset($misi['tahun_mulai']) ? (int) $misi['tahun_mulai'] : 2025;
      $takhir = isset($misi['tahun_akhir']) ? (int) $misi['tahun_akhir'] : ($tmulai + 4);
      ?>

      <form id="rpjmd-form" method="POST" action="<?= base_url('adminkab/rpjmd/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $misi['id'] ?? '' ?>">
        <input type="hidden" name="mode" value="edit">

        <!-- Informasi Umum -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Misi</h2>
          <div class="row">
            <div class="col-md-8">
              <label class="form-label">Misi RPJMD</label>
              <textarea name="misi" class="form-control mb-3" rows="2"
                required><?= esc($misi['misi'] ?? '') ?></textarea>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Mulai</label>
              <input type="number" id="periode_start" name="tahun_mulai" class="form-control mb-3"
                value="<?= $tmulai ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Akhir</label>
              <input type="number" id="periode_end" name="tahun_akhir" class="form-control mb-3" value="<?= $takhir ?>"
                readonly>
            </div>
          </div>
        </section>

        <!-- Daftar Tujuan -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 fw-semibold">Daftar Tujuan</h2>
          </div>

          <div id="tujuan-container">
            <?php if (!empty($rpjmd_complete['tujuan'])): ?>
              <?php foreach ($rpjmd_complete['tujuan'] as $ti => $tu): ?>
                <div class="tujuan-item bg-light border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="h6 fw-medium tujuan-label">Tujuan <?= $ti + 1 ?></label>
                    <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i
                        class="fas fa-trash"></i></button>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Tujuan RPJMD</label>
                    <input type="hidden" name="tujuan[<?= $ti ?>][id]" value="<?= $tu['id'] ?? '' ?>">
                    <textarea name="tujuan[<?= $ti ?>][tujuan_rpjmd]" class="form-control" rows="2"
                      required><?= esc($tu['tujuan_rpjmd'] ?? '') ?></textarea>
                  </div>

                  <!-- Indikator Tujuan -->
                  <div class="indikator-tujuan-section mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h3 class="fw-medium">Indikator Tujuan</h3>
                    </div>
                    <div class="indikator-tujuan-container">
                      <?php if (!empty($tu['indikator_tujuan'])): ?>
                        <?php foreach ($tu['indikator_tujuan'] as $ij => $it): ?>
                          <?php
                          $map = [];
                          foreach (($it['target_tahunan_tujuan'] ?? []) as $r) {
                            $map[(int) $r['tahun']] = $r;
                          }
                          $years = range($tmulai, $takhir);
                          ?>
                          <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="fw-medium indikator-tujuan-label">Indikator Tujuan
                                <?= ($ti + 1) . '.' . ($ij + 1) ?></label>
                              <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i
                                  class="fas fa-trash"></i></button>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Indikator</label>
                              <input type="hidden" name="tujuan[<?= $ti ?>][indikator_tujuan][<?= $ij ?>][id]"
                                value="<?= $it['id'] ?? '' ?>">
                              <input type="text" name="tujuan[<?= $ti ?>][indikator_tujuan][<?= $ij ?>][indikator_tujuan]"
                                class="form-control" value="<?= esc($it['indikator_tujuan'] ?? '') ?>" required>
                            </div>
                            <div class="target-tujuan-section">
                              <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                              <div class="target-tujuan-container">
                                <?php foreach ($years as $k => $y):
                                  $row = $map[$y] ?? null; ?>
                                  <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                      <input type="number" class="form-control form-control-sm tahun-target-tujuan"
                                        name="tujuan[<?= $ti ?>][indikator_tujuan][<?= $ij ?>][target_tahunan_tujuan][<?= $k ?>][tahun]"
                                        value="<?= $y ?>" readonly>
                                    </div>
                                    <div class="col">
                                      <input type="text" class="form-control form-control-sm"
                                        name="tujuan[<?= $ti ?>][indikator_tujuan][<?= $ij ?>][target_tahunan_tujuan][<?= $k ?>][target_tahunan]"
                                        value="<?= esc($row['target_tahunan'] ?? '') ?>" placeholder="Target <?= $y ?>">
                                    </div>
                                    <input type="hidden"
                                      name="tujuan[<?= $ti ?>][indikator_tujuan][<?= $ij ?>][target_tahunan_tujuan][<?= $k ?>][id]"
                                      value="<?= esc($row['id'] ?? '') ?>">
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <!-- default satu indikator tujuan -->
                        <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium indikator-tujuan-label">Indikator Tujuan <?= ($ti + 1) ?>.1</label>
                            <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i
                                class="fas fa-trash"></i></button>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Indikator</label>
                            <input type="text" name="tujuan[<?= $ti ?>][indikator_tujuan][0][indikator_tujuan]"
                              class="form-control" required>
                          </div>
                          <div class="target-tujuan-section">
                            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                            <div class="target-tujuan-container">
                              <?php foreach (range($tmulai, $takhir) as $k => $y): ?>
                                <div class="target-item row g-2 align-items-center mb-2">
                                  <div class="col-auto">
                                    <input type="number" class="form-control form-control-sm tahun-target-tujuan"
                                      name="tujuan[<?= $ti ?>][indikator_tujuan][0][target_tahunan_tujuan][<?= $k ?>][tahun]"
                                      value="<?= $y ?>" readonly>
                                  </div>
                                  <div class="col">
                                    <input type="text" class="form-control form-control-sm"
                                      name="tujuan[<?= $ti ?>][indikator_tujuan][0][target_tahunan_tujuan][<?= $k ?>][target_tahunan]"
                                      placeholder="Target <?= $y ?>">
                                  </div>
                                  <input type="hidden"
                                    name="tujuan[<?= $ti ?>][indikator_tujuan][0][target_tahunan_tujuan][<?= $k ?>][id]"
                                    value="">
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                      <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm btn-icon">
                        <i class="fas fa-plus"></i><span>Tambah Indikator Tujuan</span>
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
                      <?php if (!empty($tu['sasaran'])): ?>
                        <?php foreach ($tu['sasaran'] as $si => $sa): ?>
                          <div class="sasaran-item border rounded p-3 bg-white mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="fw-medium sasaran-label">Sasaran <?= ($ti + 1) . '.' . ($si + 1) ?></label>
                              <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i
                                  class="fas fa-trash"></i></button>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Sasaran RPJMD</label>
                              <input type="hidden" name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][id]"
                                value="<?= $sa['id'] ?? '' ?>">
                              <textarea name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][sasaran_rpjmd]" class="form-control"
                                rows="2" required><?= esc($sa['sasaran_rpjmd'] ?? '') ?></textarea>
                            </div>

                            <div class="indikator-sasaran-section">
                              <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-medium">Indikator Sasaran</h4>
                              </div>

                              <div class="indikator-sasaran-container">
                                <?php if (!empty($sa['indikator_sasaran'])): ?>
                                  <?php foreach ($sa['indikator_sasaran'] as $ii => $is): ?>
                                    <?php
                                    $imap = [];
                                    foreach (($is['target_tahunan'] ?? []) as $r) {
                                      $imap[(int) $r['tahun']] = $r;
                                    }
                                    $years = range($tmulai, $takhir);
                                    ?>
                                    <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                      <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium indikator-sasaran-label">Indikator Sasaran
                                          <?= ($ti + 1) . '.' . ($si + 1) . '.' . ($ii + 1) ?></label>
                                        <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i
                                            class="fas fa-trash"></i></button>
                                      </div>

                                      <div class="row">
                                        <div class="col-md-8">
                                          <label class="form-label">Indikator</label>
                                          <input type="hidden"
                                            name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][id]"
                                            value="<?= $is['id'] ?? '' ?>">
                                          <input type="text" class="form-control mb-3"
                                            name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][indikator_sasaran]"
                                            value="<?= esc($is['indikator_sasaran'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                          <label class="form-label">Satuan</label>
                                          <select class="form-select satuan-select mb-3"
                                            name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][satuan]"
                                            data-selected="<?= esc($is['satuan'] ?? '') ?>" required>
                                            <option value="">Pilih Satuan</option>
                                          </select>
                                        </div>
                                      </div>

                                      <div class="mb-3">
                                        <label class="form-label">Definisi Operasional</label>
                                        <textarea class="form-control mb-3"
                                          name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][definisi_op]"
                                          rows="3" required><?= esc($is['definisi_op'] ?? '') ?></textarea>
                                      </div>

                                      <div class="target-section">
                                        <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                                        <div class="target-container">
                                          <?php foreach ($years as $k => $y):
                                            $rr = $imap[$y] ?? null; ?>
                                            <div class="target-item row g-2 align-items-center mb-2">
                                              <div class="col-auto">
                                                <input type="number" class="form-control form-control-sm tahun-target"
                                                  name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][target_tahunan][<?= $k ?>][tahun]"
                                                  value="<?= $y ?>" readonly>
                                              </div>
                                              <div class="col">
                                                <input type="text" class="form-control form-control-sm"
                                                  name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][target_tahunan][<?= $k ?>][target_tahunan]"
                                                  value="<?= esc($rr['target_tahunan'] ?? '') ?>"
                                                  placeholder="Contoh: <?= 70 + ($k * 2) ?>">
                                              </div>
                                              <input type="hidden"
                                                name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][target_tahunan][<?= $k ?>][id]"
                                                value="<?= esc($rr['id'] ?? '') ?>">
                                            </div>
                                          <?php endforeach; ?>
                                        </div>
                                      </div>
                                    </div>
                                  <?php endforeach; ?>
                                <?php else: ?>
                                  <!-- default satu indikator sasaran -->
                                  <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                      <label class="fw-medium indikator-sasaran-label">Indikator Sasaran
                                        <?= ($ti + 1) . '.' . ($si + 1) ?>.1</label>
                                      <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i
                                          class="fas fa-trash"></i></button>
                                    </div>

                                    <div class="row">
                                      <div class="col-md-8">
                                        <label class="form-label">Indikator</label>
                                        <input type="text" class="form-control mb-3"
                                          name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][indikator_sasaran]"
                                          required>
                                      </div>
                                      <div class="col-md-4">
                                        <label class="form-label">Satuan</label>
                                        <select class="form-select satuan-select mb-3"
                                          name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][satuan]" required>
                                          <option value="">Pilih Satuan</option>
                                        </select>
                                      </div>
                                    </div>

                                    <div class="mb-3">
                                      <label class="form-label">Definisi Operasional</label>
                                      <textarea class="form-control mb-3"
                                        name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][definisi_op]" rows="3"
                                        required></textarea>
                                    </div>

                                    <div class="target-section">
                                      <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                                      <div class="target-container">
                                        <?php foreach (range($tmulai, $takhir) as $k => $y): ?>
                                          <div class="target-item row g-2 align-items-center mb-2">
                                            <div class="col-auto">
                                              <input type="number" class="form-control form-control-sm tahun-target"
                                                name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][target_tahunan][<?= $k ?>][tahun]"
                                                value="<?= $y ?>" readonly>
                                            </div>
                                            <div class="col">
                                              <input type="text" class="form-control form-control-sm"
                                                name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][target_tahunan][<?= $k ?>][target_tahunan]"
                                                placeholder="Contoh: <?= 70 + ($k * 2) ?>">
                                            </div>
                                            <input type="hidden"
                                              name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][target_tahunan][<?= $k ?>][id]"
                                              value="">
                                          </div>
                                        <?php endforeach; ?>
                                      </div>
                                    </div>
                                  </div>
                                <?php endif; ?>
                              </div>

                              <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="add-indikator-sasaran btn btn-info btn-sm btn-icon">
                                  <i class="fas fa-plus"></i><span>Tambah Indikator Sasaran</span>
                                </button>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <!-- default satu sasaran -->
                        <div class="sasaran-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium sasaran-label">Sasaran <?= ($ti + 1) ?>.1</label>
                            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i
                                class="fas fa-trash"></i></button>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Sasaran RPJMD</label>
                            <textarea name="tujuan[<?= $ti ?>][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2"
                              required></textarea>
                          </div>

                          <div class="indikator-sasaran-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <h4 class="fw-medium">Indikator Sasaran</h4>
                            </div>

                            <div class="indikator-sasaran-container">
                              <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                  <label class="fw-medium indikator-sasaran-label">Indikator Sasaran
                                    <?= ($ti + 1) ?>.1.1</label>
                                  <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i
                                      class="fas fa-trash"></i></button>
                                </div>

                                <div class="row">
                                  <div class="col-md-8">
                                    <label class="form-label">Indikator</label>
                                    <input type="text" class="form-control mb-3"
                                      name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][indikator_sasaran]" required>
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label">Satuan</label>
                                    <select class="form-select satuan-select mb-3"
                                      name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][satuan]" required>
                                      <option value="">Pilih Satuan</option>
                                    </select>
                                  </div>
                                </div>

                                <div class="mb-3">
                                  <label class="form-label">Definisi Operasional</label>
                                  <textarea class="form-control mb-3"
                                    name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][definisi_op]" rows="3"
                                    required></textarea>
                                </div>

                                <div class="target-section">
                                  <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                                  <div class="target-container">
                                    <?php foreach (range($tmulai, $takhir) as $k => $y): ?>
                                      <div class="target-item row g-2 align-items-center mb-2">
                                        <div class="col-auto">
                                          <input type="number" class="form-control form-control-sm tahun-target"
                                            name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $k ?>][tahun]"
                                            value="<?= $y ?>" readonly>
                                        </div>
                                        <div class="col">
                                          <input type="text" class="form-control form-control-sm"
                                            name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $k ?>][target_tahunan]"
                                            placeholder="Contoh: <?= 70 + ($k * 2) ?>">
                                        </div>
                                        <input type="hidden"
                                          name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $k ?>][id]"
                                          value="">
                                      </div>
                                    <?php endforeach; ?>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                              <button type="button" class="add-indikator-sasaran btn btn-info btn-sm btn-icon">
                                <i class="fas fa-plus"></i><span>Tambah Indikator Sasaran</span>
                              </button>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end mt-2">
                      <button type="button" class="add-sasaran btn btn-success btn-sm btn-icon">
                        <i class="fas fa-plus"></i><span>Tambah Sasaran</span>
                      </button>
                    </div>
                  </div>
                  <!-- /Sasaran -->
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="d-flex justify-content-end mt-2">
            <button type="button" id="add-tujuan" class="btn btn-success btn-sm btn-icon">
              <i class="fas fa-plus"></i><span>Tambah Tujuan</span>
            </button>
          </div>
        </section>

        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminkab/rpjmd') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>
            Kembali</a>
          <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- JS: semua tombol dibuat berfungsi -->
  <script>
    // Fallback opsi Satuan
    function generateSatuanOptions() {
      if (typeof window.generateSatuanOptions === 'function' && window.__SatuanHelperReady__) {
        return window.generateSatuanOptions();
      }
      const items = ['', '%', 'Orang', 'Unit', 'Kegiatan', 'Dokumen', 'Nilai', 'Indeks'];
      return items.map(v => v ? `<option value="${v}">${v}</option>` : '<option value="">Pilih Satuan</option>').join('');
    }

    function getYears() {
      const s = parseInt(document.getElementById('periode_start').value || '2025', 10);
      const e = parseInt(document.getElementById('periode_end').value || (s + 4), 10);
      const out = []; for (let y = s; y <= e; y++) out.push(y); return out;
    }

    function templateTarget(namePrefix, years, isTujuan = false) {
      const key = isTujuan ? 'target_tahunan_tujuan' : 'target_tahunan';
      return years.map((y, k) => `
        <div class="target-item row g-2 align-items-center mb-2">
          <div class="col-auto">
            <input type="number" class="form-control form-control-sm ${isTujuan ? 'tahun-target-tujuan' : 'tahun-target'}"
                   name="${namePrefix}[${key}][${k}][tahun]" value="${y}" readonly>
          </div>
          <div class="col">
            <input type="text" class="form-control form-control-sm"
                   name="${namePrefix}[${key}][${k}][target_tahunan]" placeholder="Target ${y}">
          </div>
          <input type="hidden" name="${namePrefix}[${key}][${k}][id]" value="">
        </div>
      `).join('');
    }

    function newIndikatorTujuanHTML(ti, ij) {
      const years = getYears();
      const p = `tujuan[${ti}][indikator_tujuan][${ij}]`;
      return `
        <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium indikator-tujuan-label">Indikator Tujuan ${ti + 1}.${ij + 1}</label>
            <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
          </div>
          <div class="mb-3">
            <label class="form-label">Indikator</label>
            <input type="text" name="${p}[indikator_tujuan]" class="form-control" required>
          </div>
          <div class="target-tujuan-section">
            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
            <div class="target-tujuan-container">
              ${templateTarget(p, years, true)}
            </div>
          </div>
        </div>`;
    }

    function newIndikatorSasaranHTML(ti, si, ii) {
      const years = getYears();
      const p = `tujuan[${ti}][sasaran][${si}][indikator_sasaran][${ii}]`;
      return `
        <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium indikator-sasaran-label">Indikator Sasaran ${ti + 1}.${si + 1}.${ii + 1}</label>
            <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
          </div>
          <div class="row">
            <div class="col-md-8">
              <label class="form-label">Indikator</label>
              <input type="text" name="${p}[indikator_sasaran]" class="form-control mb-3" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Satuan</label>
              <select name="${p}[satuan]" class="form-select satuan-select mb-3" required>
                ${generateSatuanOptions()}
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Definisi Operasional</label>
            <textarea name="${p}[definisi_op]" class="form-control mb-3" rows="3" required></textarea>
          </div>
          <div class="target-section">
            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
            <div class="target-container">
              ${templateTarget(p, years, false)}
            </div>
          </div>
        </div>`;
    }

    function newSasaranHTML(ti, si) {
      const p = `tujuan[${ti}][sasaran][${si}]`;
      return `
        <div class="sasaran-item border rounded p-3 bg-white mb-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium sasaran-label">Sasaran ${ti + 1}.${si + 1}</label>
            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
          </div>
          <div class="mb-3">
            <label class="form-label">Sasaran RPJMD</label>
            <textarea name="${p}[sasaran_rpjmd]" class="form-control" rows="2" required></textarea>
          </div>

          <div class="indikator-sasaran-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="fw-medium">Indikator Sasaran</h4>
            </div>
            <div class="indikator-sasaran-container">
              ${newIndikatorSasaranHTML(ti, si, 0)}
            </div>
            <div class="d-flex justify-content-end mt-2">
              <button type="button" class="add-indikator-sasaran btn btn-info btn-sm btn-icon">
                <i class="fas fa-plus"></i><span>Tambah Indikator Sasaran</span>
              </button>
            </div>
          </div>
        </div>`;
    }

    function newTujuanHTML(ti) {
      const p = `tujuan[${ti}]`;
      const years = getYears();
      return `
        <div class="tujuan-item bg-light border rounded p-3 mb-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="h6 fw-medium tujuan-label">Tujuan ${ti + 1}</label>
            <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
          </div>
          <div class="mb-3">
            <label class="form-label">Tujuan RPJMD</label>
            <textarea name="${p}[tujuan_rpjmd]" class="form-control" rows="2" required></textarea>
          </div>

          <div class="indikator-tujuan-section mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="fw-medium">Indikator Tujuan</h3>
            </div>
            <div class="indikator-tujuan-container">
              ${newIndikatorTujuanHTML(ti, 0)}
            </div>
            <div class="d-flex justify-content-end mt-2">
              <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm btn-icon">
                <i class="fas fa-plus"></i><span>Tambah Indikator Tujuan</span>
              </button>
            </div>
          </div>

          <div class="sasaran-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
            </div>
            <div class="sasaran-container">
              ${newSasaranHTML(ti, 0)}
            </div>
            <div class="d-flex justify-content-end mt-2">
              <button type="button" class="add-sasaran btn btn-success btn-sm btn-icon">
                <i class="fas fa-plus"></i><span>Tambah Sasaran</span>
              </button>
            </div>
          </div>
        </div>`;
    }

    // Inisialisasi dropdown satuan yang sudah ada
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.satuan-select').forEach(sel => {
        const cur = sel.getAttribute('data-selected') || '';
        sel.innerHTML = generateSatuanOptions();
        if (cur) sel.value = cur;
      });

      // sinkron periode
      const s = document.getElementById('periode_start');
      const e = document.getElementById('periode_end');
      s.addEventListener('input', () => { e.value = (parseInt(s.value || '2025', 10) + 4); refreshYears(); });

      function refreshYears() {
        const years = getYears();
        document.querySelectorAll('.target-tujuan-container').forEach(cont => {
          cont.querySelectorAll('.tahun-target-tujuan').forEach((inp, k) => { if (years[k] != null) inp.value = years[k]; });
        });
        document.querySelectorAll('.target-container').forEach(cont => {
          cont.querySelectorAll('.tahun-target').forEach((inp, k) => { if (years[k] != null) inp.value = years[k]; });
        });
      }
    });

    // === Event Delegation (SEMUA TOMBOL BERFUNGSI) ===
    document.addEventListener('click', function (ev) {
      // Tambah Tujuan
      if (ev.target.closest('#add-tujuan')) {
        ev.preventDefault(); ev.stopImmediatePropagation();
        const wrap = document.getElementById('tujuan-container');
        const ti = wrap.querySelectorAll('.tujuan-item').length;
        wrap.insertAdjacentHTML('beforeend', newTujuanHTML(ti));
        return;
      }

      // Hapus Tujuan
      if (ev.target.closest('.remove-tujuan')) {
        ev.preventDefault();
        ev.target.closest('.tujuan-item').remove();
        return;
      }

      // Tambah Indikator Tujuan
      if (ev.target.closest('.add-indikator-tujuan')) {
        ev.preventDefault(); ev.stopImmediatePropagation();
        const tujuan = ev.target.closest('.tujuan-item');
        const ti = Array.from(document.querySelectorAll('#tujuan-container .tujuan-item')).indexOf(tujuan);
        const cont = tujuan.querySelector('.indikator-tujuan-container');
        const ij = cont.querySelectorAll('.indikator-tujuan-item').length;
        cont.insertAdjacentHTML('beforeend', newIndikatorTujuanHTML(ti, ij));
        return;
      }

      // Hapus Indikator Tujuan
      if (ev.target.closest('.remove-indikator-tujuan')) {
        ev.preventDefault();
        ev.target.closest('.indikator-tujuan-item').remove();
        return;
      }

      // Tambah Sasaran
      if (ev.target.closest('.add-sasaran')) {
        ev.preventDefault(); ev.stopImmediatePropagation();
        const tujuan = ev.target.closest('.tujuan-item');
        const ti = Array.from(document.querySelectorAll('#tujuan-container .tujuan-item')).indexOf(tujuan);
        const cont = tujuan.querySelector('.sasaran-container');
        const si = cont.querySelectorAll('.sasaran-item').length;
        cont.insertAdjacentHTML('beforeend', newSasaranHTML(ti, si));
        return;
      }

      // Hapus Sasaran
      if (ev.target.closest('.remove-sasaran')) {
        ev.preventDefault();
        ev.target.closest('.sasaran-item').remove();
        return;
      }

      // Tambah Indikator Sasaran
      if (ev.target.closest('.add-indikator-sasaran')) {
        ev.preventDefault(); ev.stopImmediatePropagation();
        const sasaran = ev.target.closest('.sasaran-item');
        const tujuan = ev.target.closest('.tujuan-item');
        const ti = Array.from(document.querySelectorAll('#tujuan-container .tujuan-item')).indexOf(tujuan);
        const si = Array.from(tujuan.querySelectorAll('.sasaran-item')).indexOf(sasaran);
        const cont = sasaran.querySelector('.indikator-sasaran-container');
        const ii = cont.querySelectorAll('.indikator-sasaran-item').length;
        cont.insertAdjacentHTML('beforeend', newIndikatorSasaranHTML(ti, si, ii));
        return;
      }

      // Hapus Indikator Sasaran
      if (ev.target.closest('.remove-indikator-sasaran')) {
        ev.preventDefault();
        ev.target.closest('.indikator-sasaran-item').remove();
        return;
      }
    });
  </script>
</body>

</html>
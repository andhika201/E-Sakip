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
              <input type="number" id="periode_end" name="tahun_akhir" class="form-control mb-3" value="<?= $takhir ?>">
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
                    <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm">
                      <i class="fas fa-trash"></i>
                    </button>
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
                              <label class="fw-medium indikator-tujuan-label">
                                Indikator Tujuan <?= ($ti + 1) . '.' . ($ij + 1) ?>
                              </label>
                              <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                              </button>
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
                            <label class="fw-medium indikator-tujuan-label">
                              Indikator Tujuan <?= ($ti + 1) ?>.1
                            </label>
                            <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm">
                              <i class="fas fa-trash"></i>
                            </button>
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
                              <label class="fw-medium sasaran-label">
                                Sasaran <?= ($ti + 1) . '.' . ($si + 1) ?>
                              </label>
                              <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                              </button>
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
                                    $jenis = strtolower(trim($is['jenis_indikator'] ?? ''));
                                    ?>
                                    <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                      <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium indikator-sasaran-label">
                                          Indikator Sasaran <?= ($ti + 1) . '.' . ($si + 1) . '.' . ($ii + 1) ?>
                                        </label>
                                        <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
                                          <i class="fas fa-trash"></i>
                                        </button>
                                      </div>

                                      <div class="row">
                                        <div class="col-md-4">
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
                                          <select class="form-select select2 satuan-select mb-3"
                                            name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][satuan]"
                                            data-selected="<?= esc($is['satuan'] ?? '') ?>" required>
                                            <option value="">Pilih Satuan</option>
                                          </select>
                                        </div>
                                        <div class="col-md-4">
                                          <label class="form-label">Jenis Indikator</label>
                                          <select class="form-select select2 mb-3"
                                            name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][<?= $ii ?>][jenis_indikator]"
                                            required>
                                            <option value="">Pilih Jenis Indikator</option>
                                            <option value="indikator positif" <?= $jenis === 'indikator positif' || $jenis === 'positif' ? 'selected' : '' ?>>
                                              Indikator Positif
                                            </option>
                                            <option value="indikator negatif" <?= $jenis === 'indikator negatif' || $jenis === 'negatif' ? 'selected' : '' ?>>
                                              Indikator Negatif
                                            </option>
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
                                      <label class="fw-medium indikator-sasaran-label">
                                        Indikator Sasaran <?= ($ti + 1) . '.' . ($si + 1) ?>.1
                                      </label>
                                      <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                      </button>
                                    </div>

                                    <div class="row">
                                      <div class="col-md-4">
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
                                      <div class="col-md-4">
                                        <label class="form-label">Jenis Indikator</label>
                                        <select class="form-select mb-3"
                                          name="tujuan[<?= $ti ?>][sasaran][<?= $si ?>][indikator_sasaran][0][jenis_indikator]"
                                          required>
                                          <option value="">Pilih Jenis Indikator</option>
                                          <option value="indikator positif">Indikator Positif</option>
                                          <option value="indikator negatif">Indikator Negatif</option>
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
                            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm">
                              <i class="fas fa-trash"></i>
                            </button>
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
                                  <label class="fw-medium indikator-sasaran-label">
                                    Indikator Sasaran <?= ($ti + 1) ?>.1.1
                                  </label>
                                  <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                  </button>
                                </div>

                                <div class="row">
                                  <div class="col-md-4">
                                    <label class="form-label">Indikator</label>
                                    <input type="text" class="form-control mb-3"
                                      name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][indikator_sasaran]" required>
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label">Satuan</label>
                                    <select class="form-select select2  satuan-select mb-3"
                                      name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][satuan]" required>
                                      <option value="">Pilih Satuan</option>
                                    </select>
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label">Jenis Indikator</label>
                                    <select class="form-select select2  mb-3"
                                      name="tujuan[<?= $ti ?>][sasaran][0][indikator_sasaran][0][jenis_indikator]" required>
                                      <option value="">Pilih Jenis Indikator</option>
                                      <option value="indikator positif">Indikator Positif</option>
                                      <option value="indikator negatif">Indikator Negatif</option>
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
          <a href="<?= base_url('adminkab/rpjmd') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

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
    /* =================== KONFIG =================== */
    const PERIODE_TAHUN = 5;

    /* =================== GLOBAL STORE =================== */
    window.__TARGET_STORE__ = window.__TARGET_STORE__ || {};

    /* =================== UTIL =================== */
    function generateSatuanOptions() {
      const items = ['', '%', 'Orang', 'Unit', 'Kegiatan', 'Dokumen', 'Nilai', 'Indeks'];
      return items.map(v =>
        v ? `<option value="${v}">${v}</option>` : '<option value="">Pilih Satuan</option>'
      ).join('');
    }

    function getYears() {
      const start = parseInt(document.getElementById('periode_start').value || '2025', 10);
      return Array.from({ length: PERIODE_TAHUN }, (_, i) => start + i);
    }

    /* =================== CORE (STATEFUL) =================== */
    function syncTargetContainer(container, namePrefix, isTujuan = false) {
      const key = isTujuan ? 'target_tahunan_tujuan' : 'target_tahunan';
      const years = getYears();

      const storeKey = namePrefix + ':' + key;
      window.__TARGET_STORE__[storeKey] = window.__TARGET_STORE__[storeKey] || {};
      const store = window.__TARGET_STORE__[storeKey];

      // simpan SEMUA target yang pernah ada
      container.querySelectorAll('.target-item').forEach(item => {
        const tahun = parseInt(item.querySelector('input[name$="[tahun]"]').value, 10);
        store[tahun] = {
          target: item.querySelector('input[name$="[target_tahunan]"]').value,
          id: item.querySelector('input[name$="[id]"]')?.value || ''
        };
      });

      // reset tampilan
      container.innerHTML = '';

      // render sesuai periode aktif
      years.forEach((y, i) => {
        const prev = store[y] || {};
        container.insertAdjacentHTML('beforeend', `
      <div class="target-item row g-2 align-items-center mb-2">
        <div class="col-auto">
          <input type="number"
            class="form-control form-control-sm ${isTujuan ? 'tahun-target-tujuan' : 'tahun-target'}"
            name="${namePrefix}[${key}][${i}][tahun]"
            value="${y}" readonly>
        </div>
        <div class="col">
          <input type="text"
            class="form-control form-control-sm"
            name="${namePrefix}[${key}][${i}][target_tahunan]"
            value="${prev.target || ''}"
            placeholder="Target ${y}">
        </div>
        <input type="hidden"
          name="${namePrefix}[${key}][${i}][id]"
          value="${prev.id || ''}">
      </div>
    `);
      });
    }

    /* =================== REFRESH (FIX PREFIX) =================== */
    function refreshYears() {
      // indikator tujuan
      document.querySelectorAll('.indikator-tujuan-item').forEach(item => {
        const cont = item.querySelector('.target-tujuan-container');
        if (!cont) return;

        const firstInput = cont.querySelector('input[name$="[tahun]"]');
        if (!firstInput) return;

        const prefix = firstInput.name.replace(/\[target_tahunan_tujuan\].*$/, '');
        syncTargetContainer(cont, prefix, true);
      });

      // indikator sasaran
      document.querySelectorAll('.indikator-sasaran-item').forEach(item => {
        const cont = item.querySelector('.target-container');
        if (!cont) return;

        const firstInput = cont.querySelector('input[name$="[tahun]"]');
        if (!firstInput) return;

        const prefix = firstInput.name.replace(/\[target_tahunan\].*$/, '');
        syncTargetContainer(cont, prefix, false);
      });
    }

    /* =================== INIT =================== */
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.satuan-select').forEach(sel => {
        const cur = sel.dataset.selected || '';
        sel.innerHTML = generateSatuanOptions();
        if (cur) sel.value = cur;
      });

      const s = document.getElementById('periode_start');
      const e = document.getElementById('periode_end');

      s.addEventListener('input', () => {
        e.value = parseInt(s.value || '2025', 10) + (PERIODE_TAHUN - 1);
        refreshYears();
      });
    });
  </script>

</body>

</html>
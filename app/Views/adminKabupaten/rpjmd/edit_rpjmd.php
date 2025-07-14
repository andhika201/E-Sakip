<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit RPJMD - e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit RPJMD</h2>

      <form id="rpjmd-form" method="POST" action="<?= base_url('adminkab/rpjmd/update') ?>">
        <?= csrf_field() ?>
        <!-- Hidden fields untuk mode edit -->
        <input type="hidden" name="id" value="<?= isset($misi['id']) ? $misi['id'] : '' ?>">
        <input type="hidden" name="mode" value="edit">

        <!-- Informasi Umum Misi -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum Misi</h2>
          <div class="row">
            <div class="col-md-8">
              <label class="form-label">Misi RPJMD</label>
              <textarea name="misi" class="form-control mb-3" rows="2" placeholder="Contoh: Mewujudkan pembangunan berkelanjutan yang berpusat pada masyarakat" required><?= isset($misi['misi']) ? esc($misi['misi']) : '' ?></textarea>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Mulai</label>
              <input type="number" name="tahun_mulai" id="periode_start" class="form-control mb-3" value="<?= isset($misi['tahun_mulai']) ? $misi['tahun_mulai'] : '' ?>" placeholder="Contoh: 2025" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Periode Akhir</label>
              <input type="number" name="tahun_akhir" id="periode_end" class="form-control mb-3" value="<?= isset($misi['tahun_akhir']) ? $misi['tahun_akhir'] : '' ?>" placeholder="" readonly>
            </div>
          </div>
        </section>

        <!-- Daftar Tujuan -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 fw-semibold">Daftar Tujuan</h2>
          </div>

          <div id="tujuan-container">
            <?php if (isset($rpjmd_complete['tujuan']) && !empty($rpjmd_complete['tujuan'])): ?>
              <?php foreach ($rpjmd_complete['tujuan'] as $tujuan_index => $tujuan): ?>
                <!-- Tujuan <?= $tujuan_index + 1 ?> -->
                <div class="tujuan-item bg-light border rounded p-3 mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="h6 fw-medium">Tujuan <?= $tujuan_index + 1 ?></label>
                    <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Tujuan RPJMD</label>
                    <input type="hidden" name="tujuan[<?= $tujuan_index ?>][id]" value="<?= isset($tujuan['id']) ? $tujuan['id'] : '' ?>">
                    <textarea name="tujuan[<?= $tujuan_index ?>][tujuan_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required><?= esc($tujuan['tujuan_rpjmd'] ?? '') ?></textarea>
                  </div>

                  <!-- Indikator Tujuan -->
                  <div class="indikator-tujuan-section mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h3 class="fw-medium">Indikator Tujuan</h3>
                    </div>

                    <div class="indikator-tujuan-container">
                      <?php if (isset($tujuan['indikator_tujuan']) && !empty($tujuan['indikator_tujuan'])): ?>
                        <?php foreach ($tujuan['indikator_tujuan'] as $indikator_tujuan_index => $indikator_tujuan): ?>
                          <!-- Indikator Tujuan <?= $tujuan_index + 1 ?>.<?= $indikator_tujuan_index + 1 ?> -->
                          <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="fw-medium">Indikator Tujuan <?= $tujuan_index + 1 ?>.<?= $indikator_tujuan_index + 1 ?></label>
                              <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Indikator</label>
                              <input type="hidden" name="tujuan[<?= $tujuan_index ?>][indikator_tujuan][<?= $indikator_tujuan_index ?>][id]" value="<?= isset($indikator_tujuan['id']) ? $indikator_tujuan['id'] : '' ?>">
                              <input type="text" name="tujuan[<?= $tujuan_index ?>][indikator_tujuan][<?= $indikator_tujuan_index ?>][indikator_tujuan]" class="form-control" value="<?= esc($indikator_tujuan['indikator_tujuan'] ?? '') ?>" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <!-- Default indikator tujuan if none exist -->
                        <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Indikator Tujuan <?= $tujuan_index + 1 ?>.1</label>
                            <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Indikator</label>
                            <input type="text" name="tujuan[<?= $tujuan_index ?>][indikator_tujuan][0][indikator_tujuan]" class="form-control" value="" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                    <!-- Tombol Tambah Indikator Tujuan di bawah container -->
                    <div class="d-flex justify-content-end mt-2">
                      <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
                      </button>
                    </div>
                  </div>

                  <!-- Sasaran -->
                  <div class="sasaran-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
                    </div>

                    <div class="sasaran-container">
                      <?php if (isset($tujuan['sasaran']) && !empty($tujuan['sasaran'])): ?>
                        <?php foreach ($tujuan['sasaran'] as $sasaran_index => $sasaran): ?>
                          <!-- Sasaran <?= $tujuan_index + 1 ?>.<?= $sasaran_index + 1 ?> -->
                          <div class="sasaran-item border rounded p-3 bg-white mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="fw-medium">Sasaran <?= $tujuan_index + 1 ?>.<?= $sasaran_index + 1 ?></label>
                              <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Sasaran RPJMD</label>
                              <input type="hidden" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][id]" value="<?= isset($sasaran['id']) ? $sasaran['id'] : '' ?>">
                              <textarea name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required><?= esc($sasaran['sasaran_rpjmd'] ?? '') ?></textarea>
                            </div>

                            <!-- Indikator Sasaran -->
                            <div class="indikator-sasaran-section">
                              <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-medium">Indikator Sasaran</h4>
                              </div>

                              <div class="indikator-sasaran-container">
                                <?php if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])): ?>
                                  <?php foreach ($sasaran['indikator_sasaran'] as $indikator_sasaran_index => $indikator_sasaran): ?>
                                    <!-- Indikator Sasaran <?= $tujuan_index + 1 ?>.<?= $sasaran_index + 1 ?>.<?= $indikator_sasaran_index + 1 ?> -->
                                    <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                      <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium">Indikator Sasaran <?= $tujuan_index + 1 ?>.<?= $sasaran_index + 1 ?>.<?= $indikator_sasaran_index + 1 ?></label>
                                        <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                      </div>
                                      
                                      <div class="row">
                                        <div class="col-md-8">
                                          <label class="form-label">Indikator</label>
                                          <input type="hidden" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][id]" value="<?= isset($indikator_sasaran['id']) ? $indikator_sasaran['id'] : '' ?>">
                                          <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][indikator_sasaran]" class="form-control mb-3" value="<?= esc($indikator_sasaran['indikator_sasaran'] ?? '') ?>" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                        </div>
                                        <div class="col-md-4">
                                          <label class="form-label">Satuan</label>
                                          <select name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][satuan]" class="form-select mb-3" required>
                                            <option value="">Pilih Satuan</option>
                                            <option value="Unit" <?= isset($indikator_sasaran['satuan']) && $indikator_sasaran['satuan'] == 'Unit' ? 'selected' : '' ?>>Unit</option>
                                            <option value="Nilai" <?= isset($indikator_sasaran['satuan']) && $indikator_sasaran['satuan'] == 'Nilai' ? 'selected' : '' ?>>Nilai</option>
                                            <option value="Persen" <?= isset($indikator_sasaran['satuan']) && $indikator_sasaran['satuan'] == 'Persen' ? 'selected' : '' ?>>Persen</option>
                                            <option value="Predikat" <?= isset($indikator_sasaran['satuan']) && $indikator_sasaran['satuan'] == 'Predikat' ? 'selected' : '' ?>>Predikat</option>
                                          </select>
                                        </div>
                                      </div>

                                      <!-- Strategi -->
                                      <div class="mb-3">
                                        <label class="form-label">Strategi</label>
                                        <textarea name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required><?= esc($indikator_sasaran['strategi'] ?? '') ?></textarea>
                                      </div>

                                      <!-- Target 5 Tahunan -->
                                      <div class="target-section">
                                        <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                                        <div class="target-container">
                                          <?php if (isset($indikator_sasaran['target_tahunan']) && !empty($indikator_sasaran['target_tahunan'])): ?>
                                            <?php foreach ($indikator_sasaran['target_tahunan'] as $target_index => $target): ?>
                                              <div class="target-item row g-2 align-items-center mb-2">
                                                <div class="col-auto">
                                                  <input type="hidden" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][target_tahunan][<?= $target_index ?>][id]" value="<?= isset($target['id']) ? $target['id'] : '' ?>">
                                                  <input type="number" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][target_tahunan][<?= $target_index ?>][tahun]" value="<?= esc($target['tahun'] ?? '') ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                                </div>
                                                <div class="col">
                                                  <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][target_tahunan][<?= $target_index ?>][target_tahunan]" value="<?= esc($target['target_tahunan'] ?? '') ?>" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                                                </div>
                                              </div>
                                            <?php endforeach; ?>
                                          <?php else: ?>
                                            <!-- Default 5-year targets if none exist -->
                                            <?php 
                                            $start_year = isset($misi['tahun_mulai']) ? (int)$misi['tahun_mulai'] : 2025;
                                            for ($i = 0; $i < 5; $i++): 
                                            ?>
                                              <div class="target-item row g-2 align-items-center mb-2">
                                                <div class="col-auto">
                                                  <input type="number" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][target_tahunan][<?= $i ?>][tahun]" value="<?= $start_year + $i ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                                </div>
                                                <div class="col">
                                                  <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][<?= $indikator_sasaran_index ?>][target_tahunan][<?= $i ?>][target_tahunan]" value="" class="form-control form-control-sm" placeholder="Contoh: <?= 75 + $i * 2 ?>" required>
                                                </div>
                                              </div>
                                            <?php endfor; ?>
                                          <?php endif; ?>
                                        </div>
                                      </div>
                                    </div> <!-- End Indikator Sasaran -->
                                  <?php endforeach; ?>
                                <?php else: ?>
                                  <!-- Default indikator sasaran if none exist -->
                                  <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                      <label class="fw-medium">Indikator Sasaran <?= $tujuan_index + 1 ?>.<?= $sasaran_index + 1 ?>.1</label>
                                      <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </div>
                                    
                                    <div class="row">
                                      <div class="col-md-8">
                                        <label class="form-label">Indikator</label>
                                        <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                      </div>
                                      <div class="col-md-4">
                                        <label class="form-label">Satuan</label>
                                        <select name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][0][satuan]" class="form-select mb-3" required>
                                          <option value="">Pilih Satuan</option>
                                          <option value="Unit">Unit</option>
                                          <option value="Nilai">Nilai</option>
                                          <option value="Persen">Persen</option>
                                          <option value="Predikat">Predikat</option>
                                        </select>
                                      </div>
                                    </div>

                                    <!-- Strategi -->
                                    <div class="mb-3">
                                      <label class="form-label">Strategi</label>
                                      <textarea name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                                    </div>

                                    <!-- Target 5 Tahunan -->
                                    <div class="target-section">
                                      <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                                      <div class="target-container">
                                        <?php 
                                        $start_year = isset($misi['tahun_mulai']) ? (int)$misi['tahun_mulai'] : 2025;
                                        for ($i = 0; $i < 5; $i++): 
                                        ?>
                                          <div class="target-item row g-2 align-items-center mb-2">
                                            <div class="col-auto">
                                              <input type="number" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][0][target_tahunan][<?= $i ?>][tahun]" value="<?= $start_year + $i ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                            </div>
                                            <div class="col">
                                              <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][<?= $sasaran_index ?>][indikator_sasaran][0][target_tahunan][<?= $i ?>][target_tahunan]" value="" class="form-control form-control-sm" placeholder="Contoh: <?= 75 + $i * 2 ?>" required>
                                            </div>
                                          </div>
                                        <?php endfor; ?>
                                      </div>
                                    </div>
                                  </div>
                                <?php endif; ?>
                              </div> <!-- End Indikator Sasaran Container -->
                              <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                              <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                                </button>
                              </div>
                            </div> <!-- End Indikator Sasaran Section -->
                          </div> <!-- End Sasaran -->
                        <?php endforeach; ?>
                      <?php else: ?>
                        <!-- Default sasaran if none exist -->
                        <div class="sasaran-item border rounded p-3 bg-white mb-3">
                          <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Sasaran <?= $tujuan_index + 1 ?>.1</label>
                            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Sasaran RPJMD</label>
                            <textarea name="tujuan[<?= $tujuan_index ?>][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
                          </div>

                          <!-- Indikator Sasaran -->
                          <div class="indikator-sasaran-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <h4 class="fw-medium">Indikator Sasaran</h4>
                            </div>

                            <div class="indikator-sasaran-container">
                              <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                  <label class="fw-medium">Indikator Sasaran <?= $tujuan_index + 1 ?>.1.1</label>
                                  <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </div>
                                
                                <div class="row">
                                  <div class="col-md-8">
                                    <label class="form-label">Indikator</label>
                                    <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label">Satuan</label>
                                    <select name="tujuan[<?= $tujuan_index ?>][sasaran][0][indikator_sasaran][0][satuan]" class="form-select mb-3" required>
                                      <option value="">Pilih Satuan</option>
                                      <option value="Unit">Unit</option>
                                      <option value="Nilai">Nilai</option>
                                      <option value="Persen">Persen</option>
                                      <option value="Predikat">Predikat</option>
                                    </select>
                                  </div>
                                </div>

                                <!-- Strategi -->
                                <div class="mb-3">
                                  <label class="form-label">Strategi</label>
                                  <textarea name="tujuan[<?= $tujuan_index ?>][sasaran][0][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                                </div>

                                <!-- Target 5 Tahunan -->
                                <div class="target-section">
                                  <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                                  <div class="target-container">
                                    <?php 
                                    $start_year = isset($misi['tahun_mulai']) ? (int)$misi['tahun_mulai'] : 2025;
                                    for ($i = 0; $i < 5; $i++): 
                                    ?>
                                      <div class="target-item row g-2 align-items-center mb-2">
                                        <div class="col-auto">
                                          <input type="number" name="tujuan[<?= $tujuan_index ?>][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $i ?>][tahun]" value="<?= $start_year + $i ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                        </div>
                                        <div class="col">
                                          <input type="text" name="tujuan[<?= $tujuan_index ?>][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $i ?>][target_tahunan]" value="" class="form-control form-control-sm" placeholder="Contoh: <?= 75 + $i * 2 ?>" required>
                                        </div>
                                      </div>
                                    <?php endfor; ?>
                                  </div>
                                </div>
                              </div>
                            </div> <!-- End Indikator Sasaran Container -->
                            <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                            <div class="d-flex justify-content-end mt-2">
                              <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                              </button>
                            </div>
                          </div> <!-- End Indikator Sasaran Section -->
                        </div>
                      <?php endif; ?>
                    </div> <!-- End Sasaran Container -->
                    <!-- Tombol Tambah Sasaran di bawah container -->
                    <div class="d-flex justify-content-end mt-2">
                      <button type="button" class="add-sasaran btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Sasaran
                      </button>
                    </div>
                  </div> <!-- End Sasaran Section -->
                </div> <!-- End Tujuan -->
              <?php endforeach; ?>
            <?php else: ?>
              <!-- Default tujuan if none exist -->
              <div class="tujuan-item bg-light border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <label class="fw-medium">Tujuan 1</label>
                  <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                  <label class="form-label">Tujuan RPJMD</label>
                  <textarea name="tujuan[0][tujuan_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required></textarea>
                </div>

                <!-- Indikator Tujuan -->
                <div class="indikator-tujuan-section mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-medium">Indikator Tujuan</h3>
                  </div>

                  <div class="indikator-tujuan-container">
                    <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium">Indikator Tujuan 1.1</label>
                        <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Indikator</label>
                        <input type="text" name="tujuan[0][indikator_tujuan][0][indikator_tujuan]" class="form-control" value="" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
                      </div>
                    </div>
                  </div>
                  <!-- Tombol Tambah Indikator Tujuan di bawah container -->
                  <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
                      <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
                    </button>
                  </div>
                </div>

                <!-- Sasaran -->
                <div class="sasaran-section">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
                  </div>

                  <div class="sasaran-container">
                    <div class="sasaran-item border rounded p-3 bg-white mb-3">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium">Sasaran 1.1</label>
                        <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Sasaran RPJMD</label>
                        <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
                      </div>

                      <!-- Indikator Sasaran -->
                      <div class="indikator-sasaran-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <h4 class="fw-medium">Indikator Sasaran</h4>
                        </div>

                        <div class="indikator-sasaran-container">
                          <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                              <label class="fw-medium">Indikator Sasaran 1.1.1</label>
                              <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                            
                            <div class="row">
                              <div class="col-md-8">
                                <label class="form-label">Indikator</label>
                                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                              </div>
                              <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <select name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-select mb-3" required>
                                  <option value="">Pilih Satuan</option>
                                  <option value="Unit">Unit</option>
                                  <option value="Nilai">Nilai</option>
                                  <option value="Persen">Persen</option>
                                  <option value="Predikat">Predikat</option>
                                </select>
                              </div>
                            </div>

                            <!-- Strategi -->
                            <div class="mb-3">
                              <label class="form-label">Strategi</label>
                              <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                            </div>

                            <!-- Target 5 Tahunan -->
                            <div class="target-section">
                              <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                              <div class="target-container">
                                <?php 
                                $start_year = isset($misi['tahun_mulai']) ? (int)$misi['tahun_mulai'] : 2025;
                                for ($i = 0; $i < 5; $i++): 
                                ?>
                                  <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                      <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $i ?>][tahun]" value="<?= $start_year + $i ?>" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                    </div>
                                    <div class="col">
                                      <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][<?= $i ?>][target_tahunan]" value="" class="form-control form-control-sm" placeholder="Contoh: <?= 75 + $i * 2 ?>" required>
                                    </div>
                                  </div>
                                <?php endfor; ?>
                              </div>
                            </div>
                          </div>
                        </div> <!-- End Indikator Sasaran Container -->
                        <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                        <div class="d-flex justify-content-end mt-2">
                          <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                          </button>
                        </div>
                      </div> <!-- End Indikator Sasaran Section -->
                    </div>
                  </div> <!-- End Sasaran Container -->
                  <!-- Tombol Tambah Sasaran di bawah container -->
                  <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-sasaran btn btn-success btn-sm">
                      <i class="fas fa-plus me-1"></i> Tambah Sasaran
                    </button>
                  </div>
                </div> <!-- End Sasaran Section -->
              </div>
            <?php endif; ?>
          </div> <!-- End Tujuan Container -->
          
          <!-- Tombol Tambah Tujuan di bawah, tapi di luar #tujuan-container -->
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
            <i class="fas fa-save me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- Include JavaScript eksternal -->
  <script src="<?= base_url('assets/js/adminKabupaten/rpjmd-form.js') ?>"></script>
  
  <script>
    // Inisialisasi data untuk edit form
    document.addEventListener('DOMContentLoaded', function() {
      // Update periode pada load
      updatePeriodeTahun();
      
      // Inisialisasi form dengan data yang sudah ada
      updateLabels();
      updateFormNames();
      
      // Update visibility tombol delete berdasarkan jumlah item saat ini
      updateDeleteButtonVisibility();
    });
  </script>
</body>
</html>

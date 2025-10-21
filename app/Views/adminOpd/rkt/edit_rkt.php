<!-- app/Views/adminOpd/rkt/edit_rkt.php -->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Edit RKT - <?= esc($indicator['indikator_sasaran'] ?? 'Edit') ?></title>
  <?= $this->include('adminOpd/templates/style.php') ?>
</head>
<body class="bg-light">
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
  <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

  <main class="p-4">
    <div class="container">
      <h3>Edit RKT — <?= esc($indicator['indikator_sasaran']) ?></h3>

      <form id="edit-rkt-form" method="POST" action="<?= base_url('adminopd/rkt/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="indikator_id" value="<?= esc($indicator['id']) ?>">

        <div class="mb-3">
          <label class="form-label">Indikator</label>
          <input class="form-control" readonly value="<?= esc($indicator['indikator_sasaran']) ?>">
        </div>

        <!-- Deleted IDs collected by JS when user removes existing items -->
        <div id="deleted-ids-container"></div>

        <!-- Program container -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5>Daftar Program</h5>
          </div>

          <div id="program-container">
            <!-- Render existing programs from $indicator['rkts'] -->
            <?php if (!empty($indicator['rkts'])): ?>
              <?php foreach ($indicator['rkts'] as $pIndex => $rkt): ?>
                <div class="program-item bg-light border rounded p-3 mb-3" data-existing-id="<?= esc($rkt['id']) ?>">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="fw-medium">Program <?= $pIndex + 1 ?></label>
                    <button type="button" class="remove-program btn btn-outline-danger btn-sm">Hapus Program</button>
                  </div>

                  <input type="hidden" name="program[<?= $pIndex ?>][id]" value="<?= esc($rkt['id']) ?>">
                  <div class="mb-3">
                    <label class="form-label">Pilih Program</label>
                    <select name="program[<?= $pIndex ?>][program_id]" class="form-select select-program" required>
                      <option value="">-- Pilih Program --</option>
                      <?php foreach ($programs as $prog): ?>
                        <option value="<?= esc($prog['id']) ?>" <?= (isset($rkt['program_id']) && $prog['id']==$rkt['program_id']) ? 'selected':'' ?>><?= esc($prog['program_kegiatan']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="kegiatan-container">
                    <?php if (!empty($rkt['kegiatan'])): ?>
                      <?php foreach ($rkt['kegiatan'] as $kIndex => $keg): ?>
                        <div class="kegiatan-item border rounded p-3 bg-white mb-3" data-existing-id="<?= esc($keg['id']) ?>">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="fw-medium">Kegiatan <?= ($pIndex+1) .'.'.($kIndex+1) ?></label>
                            <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">Hapus Kegiatan</button>
                          </div>

                          <input type="hidden" name="program[<?= $pIndex ?>][kegiatan][<?= $kIndex ?>][id]" value="<?= esc($keg['id']) ?>">
                          <div class="mb-2">
                            <label class="form-label">Nama Kegiatan</label>
                            <textarea name="program[<?= $pIndex ?>][kegiatan][<?= $kIndex ?>][nama_kegiatan]" class="form-control nama-kegiatan" rows="2" required><?= esc($keg['nama_kegiatan']) ?></textarea>
                          </div>

                          <div class="subkegiatan-container">
                            <?php if (!empty($keg['subkegiatan'])): ?>
                              <?php foreach ($keg['subkegiatan'] as $sIndex => $sub): ?>
                                <div class="subkegiatan-item border rounded p-2 mb-2" data-existing-id="<?= esc($sub['id']) ?>">
                                  <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="fw-medium">Sub <?= ($pIndex+1).'.'.($kIndex+1).'.'.($sIndex+1) ?></label>
                                    <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">Hapus Sub</button>
                                  </div>

                                  <input type="hidden" name="program[<?= $pIndex ?>][kegiatan][<?= $kIndex ?>][subkegiatan][<?= $sIndex ?>][id]" value="<?= esc($sub['id']) ?>">
                                  <div class="mb-2">
                                    <label class="form-label">Nama Sub Kegiatan</label>
                                    <textarea name="program[<?= $pIndex ?>][kegiatan][<?= $kIndex ?>][subkegiatan][<?= $sIndex ?>][nama_subkegiatan]" class="form-control nama-subkegiatan" rows="1" required><?= esc($sub['nama_subkegiatan']) ?></textarea>
                                  </div>

                                  <div class="mb-2">
                                    <label class="form-label">Target Anggaran</label>
                                    <input type="text" class="form-control target-display" value="<?= $sub['target_anggaran'] ? 'Rp '.number_format($sub['target_anggaran'],0,',','.') : '' ?>">
                                    <input type="hidden" class="target-hidden" name="program[<?= $pIndex ?>][kegiatan][<?= $kIndex ?>][subkegiatan][<?= $sIndex ?>][target_anggaran]" value="<?= esc($sub['target_anggaran']) ?>">
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </div>

                          <div class="mt-2 d-flex justify-content-end">
                            <button type="button" class="add-subkegiatan btn btn-info btn-sm">Tambah Sub Kegiatan</button>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>

                  <div class="mt-2 d-flex justify-content-end">
                    <button type="button" class="add-kegiatan btn btn-success btn-sm">Tambah Kegiatan</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- If no program present, show one empty program block (for UI) -->
              <div class="program-item bg-light border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="fw-medium">Program 1</label>
                  <button type="button" class="remove-program btn btn-outline-danger btn-sm" style="display:none">Hapus Program</button>
                </div>

                <input type="hidden" name="program[0][id]" value="">
                <div class="mb-3">
                  <label class="form-label">Pilih Program</label>
                  <select name="program[0][program_id]" class="form-select select-program" required>
                    <option value="">-- Pilih Program --</option>
                    <?php foreach ($programs as $prog): ?>
                      <option value="<?= esc($prog['id']) ?>"><?= esc($prog['program_kegiatan']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="kegiatan-container">
                  <div class="kegiatan-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="fw-medium">Kegiatan 1</label>
                      <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">Hapus Kegiatan</button>
                    </div>

                    <input type="hidden" name="program[0][kegiatan][0][id]" value="">
                    <div class="mb-2">
                      <label class="form-label">Nama Kegiatan</label>
                      <textarea name="program[0][kegiatan][0][nama_kegiatan]" class="form-control nama-kegiatan" rows="2" required></textarea>
                    </div>

                    <div class="subkegiatan-container">
                      <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <label class="fw-medium">Sub Kegiatan 1</label>
                          <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">Hapus Sub</button>
                        </div>

                        <input type="hidden" name="program[0][kegiatan][0][subkegiatan][0][id]" value="">
                        <div class="mb-2">
                          <label class="form-label">Nama Sub Kegiatan</label>
                          <textarea name="program[0][kegiatan][0][subkegiatan][0][nama_subkegiatan]" class="form-control nama-subkegiatan" rows="2" required></textarea>
                        </div>

                        <div class="mb-2">
                          <label class="form-label">Target Anggaran</label>
                          <input type="text" class="form-control target-display" />
                          <input type="hidden" class="target-hidden" name="program[0][kegiatan][0][subkegiatan][0][target_anggaran]" value="">
                        </div>
                      </div>
                    </div>

                    <div class="mt-2 d-flex justify-content-end">
                      <button type="button" class="add-subkegiatan btn btn-info btn-sm">Tambah Sub Kegiatan</button>
                    </div>
                  </div>
                </div>

                <div class="mt-2 d-flex justify-content-end">
                  <button type="button" class="add-kegiatan btn btn-success btn-sm">Tambah Kegiatan</button>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="d-flex justify-content-end mt-2">
            <button type="button" id="add-program" class="btn btn-primary btn-sm">Tambah Program</button>
          </div>

          <!-- templates (hidden) -->
          <template id="tpl-program">
            <div class="program-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="fw-medium">Program X</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm">Hapus Program</button>
              </div>
              <input type="hidden" name="program[__p__][id]" value="">
              <div class="mb-3">
                <label class="form-label">Pilih Program</label>
                <select class="form-select select-program">
                  <option value="">-- Pilih Program --</option>
                </select>
              </div>

              <div class="kegiatan-container"></div>

              <div class="mt-2 d-flex justify-content-end">
                <button type="button" class="add-kegiatan btn btn-success btn-sm">Tambah Kegiatan</button>
              </div>
            </template>

          <template id="tpl-kegiatan">
            <div class="kegiatan-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="fw-medium">Kegiatan X</label>
                <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">Hapus Kegiatan</button>
              </div>
              <input type="hidden" name="program[__p__][kegiatan][__k__][id]" value="">
              <div class="mb-2">
                <label class="form-label">Nama Kegiatan</label>
                <textarea class="form-control nama-kegiatan" rows="2" placeholder="Masukkan nama kegiatan"></textarea>
              </div>

              <div class="subkegiatan-container"></div>

              <div class="mt-2 d-flex justify-content-end">
                <button type="button" class="add-subkegiatan btn btn-info btn-sm">Tambah Sub Kegiatan</button>
              </div>
            </template>

          <template id="tpl-subkegiatan">
            <div class="subkegiatan-item border rounded p-3 bg-light mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sub X</label>
                <button type="button" class="remove-subkegiatan btn btn-outline-danger btn-sm">Hapus Sub</button>
              </div>
              <input type="hidden" name="program[__p__][kegiatan][__k__][subkegiatan][__s__][id]" value="">
              <div class="mb-2">
                <label class="form-label">Nama Sub Kegiatan</label>
                <textarea class="form-control nama-subkegiatan" rows="2"></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label">Target Anggaran</label>
                <input type="text" class="form-control target-display" />
                <input type="hidden" class="target-hidden" name="program[__p__][kegiatan][__k__][subkegiatan][__s__][target_anggaran]" value="">
              </div>
            </template>

        </section>

        <div class="d-flex justify-content-between mt-3">
          <a href="<?= base_url('adminopd/rkt') ?>" class="btn btn-secondary">Kembali</a>
          <button type="submit" class="btn btn-success">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php') ?>

  <script>const daftarProgram = <?= json_encode($programs) ?>;</script>
  <script src="<?= base_url('/assets/js/adminOpd/renja/renja-edit.js') ?>"></script>
</body>
</html>

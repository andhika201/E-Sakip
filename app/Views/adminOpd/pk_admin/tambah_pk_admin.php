<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah PK e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4">
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah PK</h2>

      <form id="rpjmd-form" method="POST" action="<?= base_url('adminopd/pk_admin/save') ?>">
        <?= csrf_field() ?>

        <!-- Informasi Umum Misi -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum PK</h2>
          <div class="col">
            <div class="col-md-8">
              <label class="form-label">Pihak 1</label>
                <select name="rpjmd_sasaran_id" id="rpjmd_sasaran_select" class="form-select mb-3" required>
                    <option value="">Pilih Pihak Kesatu</option>
                    <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                        <?php foreach ($pegawaiOpd as $pegawai): ?>
                        <option value="<?= $pegawai['id'] ?>">
                            <?= esc($pegawai['nama_pegawai']) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak Pegawai yang tersedia</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">Pihak 2</label>
                <select name="rpjmd_sasaran_id" id="rpjmd_sasaran_select" class="form-select mb-3" required>
                    <option value="">Pilih Pihak Kesatu</option>
                    <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                        <?php foreach ($pegawaiOpd as $pegawai): ?>
                        <option value="<?= $pegawai['id'] ?>">
                            <?= esc($pegawai['nama_pegawai']) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak Pegawai yang tersedia</option>
                    <?php endif; ?>
                </select>
            </div>
          </div>
        </section>

      <section>
        <!-- Sasaran -->
        <div class="sasaran-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-medium h4">Sasaran Terkait PK Ini</h3>
            </div>

            <div class="sasaran-container">
            <!-- Sasaran 1.1 -->
                <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium h5">Sasaran 1</label>
                    <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                    <div class="mb-3">
                    <label class="form-label">Sasaran PK</label>
                    <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control border-secondary" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
                    </div>

                    <!-- Indikator Sasaran -->
                    <div class="indikator-sasaran-section">
                        <div class="indikator-sasaran-container">
                            <!-- Indikator Sasaran 1.1.1 -->
                            <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="fw-medium">Indikator 1.1</label>
                                    <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label">Indikator</label>
                                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3 border-secondary" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Target</label>
                                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3 border-secondary" value="" placeholder="Nilai target" required>
                                    </div>
                                </div>
                            </div> <!-- End Indikator Sasaran -->
                        </div> <!-- End Indikator Sasaran Container -->
                        <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Sasaran
                            </button>
                        </div>
                    </div> <!-- End Indikator Sasaran Section -->
                </div> <!-- End Sasaran -->
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-indikator-sasaran btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator
                    </button>
                </div>
            </div> <!-- End Sasaran Container -->
        </div> <!-- End Sasaran Section -->


        <!-- Program PK -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Program Pk</h3>
        </div>

        <div class="program-container">
        <!-- Program -->
            <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Program 1</label>
                    <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Program</label>
                            <select name="rpjmd_sasaran_id" id="rpjmd_sasaran_select" class="form-select mb-3" required>
                                <option value="">Pilih Pihak Kesatu</option>
                                <?php if (isset($program) && !empty($program)): ?>
                                    <?php foreach ($program as $programItem): ?>
                                    <option value="<?= $programItem['id'] ?>">
                                        <?= esc($programItem['program_kegiatan']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak Program yang tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran</label>
                            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3 border-secondary" value="" placeholder="Anggaran" required readonly>
                        </div>
                    </div>
                </div>
            </div> <!-- End Sasaran -->
            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="add-indikator-sasaran btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Program
                </button>
            </div>
        </div> <!-- End Sasaran Container -->
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

  <?= $this->include('adminOpd/templates/footer.php'); ?>

  <!-- JavaScript Funtion For Handling RPJMD Form-->
  <script src="<?= base_url('assets/js/adminOpd/pk/pk-form.js') ?>"></script>
</body>
</html>
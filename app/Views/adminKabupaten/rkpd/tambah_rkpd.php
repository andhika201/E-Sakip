<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah RKPD e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RKPD</h2>

      <!--Alert Container-->
      <div id="alert-container"></div>

      <form id="rkpd-form" method="POST" action="<?= base_url('adminkab/rkpd/save') ?>">
        <?= csrf_field() ?>

        <!-- Informasi Umum -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Sasaran RPJMD Terkait RKPD ini</h2>
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Sasaran RPJMD Terkait</label>
              <select name="rpjmd_sasaran_id" id="rpjmd-sasaran-select" class="form-select mb-3" required>
                <option value="">Pilih Sasaran RPJMD</option>
                <?php if (isset($rpjmd_sasaran) && !empty($rpjmd_sasaran)): ?>
                  <?php foreach ($rpjmd_sasaran as $sasaran): ?>
                    <option value="<?= $sasaran['id'] ?>" data-tahun-mulai="<?= $sasaran['tahun_mulai'] ?>"
                      data-tahun-akhir="<?= $sasaran['tahun_akhir'] ?>">
                      <?= esc($sasaran['sasaran_rpjmd']) ?> (Periode:
                      <?= $sasaran['tahun_mulai'] ?>-<?= $sasaran['tahun_akhir'] ?>)
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="" disabled>Tidak ada sasaran RPJMD yang tersedia</option>
                <?php endif; ?>
              </select>
            </div>
          </div>
        </section>

        <!-- Daftar Sasaran RKPD -->
        <section>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-semibold">Daftar Sasaran RKPD</h2>
          </div>

          <div id="sasaran-rkpd-container">
            <!-- Sasaran RKPD 1 -->
            <div class="sasaran-rkpd-item bg-light border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Sasaran RKPD 1</label>
                <button type="button" class="remove-sasaran-rkpd btn btn-outline-danger btn-sm"><i
                    class="fas fa-trash"></i></button>
              </div>

              <div class="row mb-3">
                <div class="col-md-12">
                  <label class="form-label">Sasaran RKPD</label>
                  <textarea name="sasaran_rkpd[0][sasaran]" class="form-control" rows="2"
                    placeholder="Masukkan sasaran RKPD" required></textarea>
                </div>
              </div>

              <!-- Indikator Sasaran RKPD -->
              <div class="indikator-sasaran-rkpd-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="h5 fw-medium">Indikator Sasaran RKPD</h4>
                </div>

                <div class="indikator-sasaran-rkpd-container">
                  <!-- Indikator Sasaran RKPD 1.1 -->
                  <div class="indikator-sasaran-rkpd-item border rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="fw-medium">Indikator Sasaran 1.1</label>
                      <button type="button" class="remove-indikator-sasaran-rkpd btn btn-outline-danger btn-sm"><i
                          class="fas fa-trash"></i></button>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Indikator Sasaran</label>
                      <textarea name="sasaran_rkpd[0][indikator_sasaran][0][indikator_sasaran]" class="form-control"
                        rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                    </div>

                    <div class="row mb-3">
                      <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select name="sasaran_rkpd[0][indikator_sasaran][0][satuan]" class="form-select satuan-select"
                          required>
                          <option value="">Pilih Satuan</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Tahun Target</label>
                        <select name="sasaran_rkpd[0][indikator_sasaran][0][tahun]" class="form-select tahun-select"
                          required>
                          <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <input type="text" name="sasaran_rkpd[0][indikator_sasaran][0][target]" class="form-control"
                          placeholder="Nilai target" required>
                      </div>
                    </div>
                  </div> <!-- End Indikator Sasaran RKPD -->
                </div> <!-- End Indikator Sasaran RKPD Container -->
              </div> <!-- End Indikator Sasaran RKPD Section -->

              <!-- Tombol Tambah Indikator Sasaran -->
              <div class="d-flex justify-content-end">
                <button type="button" class="add-indikator-sasaran-rkpd btn btn-info btn-sm">
                  <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                </button>
              </div>

            </div> <!-- End Sasaran RKPD -->
          </div> <!-- End Sasaran RKPD Container -->

          <!-- Tombol Tambah Sasaran RKPD -->
          <div class="d-flex justify-content-end">
            <button type="button" id="add-sasaran-rkpd" class="btn btn-success btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Sasaran RKPD
            </button>
          </div>

        </section>

        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between mt-4">
          <a href="<?= base_url('adminkab/rkpd') ?>" class="btn btn-secondary">
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

  <script src="<?= base_url('/assets/js/adminKabupaten/rkpd/rkpd-form.js') ?>"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Isi semua dropdown satuan dengan class 'satuan-select'
      const satuanSelects = document.querySelectorAll('.satuan-select');
      satuanSelects.forEach(select => {
        if (typeof generateSatuanOptions === 'function') {
          select.innerHTML = generateSatuanOptions();
        }
      });
    });
  </script>
</body>

</html>
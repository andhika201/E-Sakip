<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit PK Bupati</title>
  <!-- Style -->
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4 row">
    <form id="rpjmd-form" method="POST" action="<?= base_url('adminkab/pk_bupati/update/' . $pk_data['pk_id']) ?>">
    <?= csrf_field() ?>
    
    <div class="bg-white rounded shadow-sm p-4 mb-4" style="width: 100%; max-width: 1200px;">
        <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit PK Bupati</h2>

        <!-- Informasi Umum Misi -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum PK</h2>
          <div class="col-md-12 mb-3">
            <div class="row col-gap-3">
                <div class="col-md-8">
                    <label class="form-label">Nama Bupati</label>
                    <input type="text" name="nama_bupati" class="form-control mb-3 border-secondary" 
                           value="<?= esc($pk_data['nama']) ?>" placeholder="Masukkan nama Bupati" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal PK</label>
                    <input type="date" name="tanggal" class="form-control mb-3 border-secondary" 
                           value="<?= $pk_data['tanggal'] ?>" required>
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Misi RPJMD</label>
                <select name="rpjmd_misi_id" class="form-select mb-3 border-secondary" required>
                    <option value="">Pilih Misi RPJMD</option>
                    <?php if (isset($misiRpjmd) && !empty($misiRpjmd)): ?>
                        <?php foreach ($misiRpjmd as $misi): ?>
                        <option value="<?= $misi['id'] ?>" 
                                <?= (isset($pk_data['rpjmd_misi_id']) && $misi['id'] == $pk_data['rpjmd_misi_id']) ? 'selected' : '' ?>>
                            <?= esc($misi['misi']) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tidak ada Misi RPJMD yang tersedia</option>
                    <?php endif; ?>
                </select>
            </div>
          </div>
        </section>
    </div>
    
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
      <section>
        <!-- Sasaran -->
        <div class="sasaran-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-medium h4">Sasaran Terkait PK Ini</h3>
            </div>

            <div class="sasaran-container">
            <?php if (isset($pk_data['sasaran_pk']) && !empty($pk_data['sasaran_pk'])): ?>
                <?php foreach ($pk_data['sasaran_pk'] as $sasaranIndex => $sasaran): ?>
                <!-- Sasaran <?= $sasaranIndex + 1 ?> -->
                <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium h5">Sasaran <?= $sasaranIndex + 1 ?></label>
                        <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                        <div class="mb-3">
                        <label class="form-label">Sasaran PK</label>
                        <textarea name="sasaran_pk[<?= $sasaranIndex ?>][sasaran]" class="form-control border-secondary" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required><?= esc($sasaran['sasaran']) ?></textarea>
                    </div>

                    <!-- Indikator -->
                    <div class="indikator-section">
                        <div class="indikator-container">
                            <?php if (isset($sasaran['indikator']) && !empty($sasaran['indikator'])): ?>
                                <?php foreach ($sasaran['indikator'] as $indikatorIndex => $indikator): ?>
                                <!-- Indikator <?= $indikatorIndex + 1 ?>-->
                                <div class="indikator-item border rounded p-3 bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium">Indikator <?= $indikatorIndex + 1 ?></label>
                                        <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label class="form-label">Indikator</label>
                                            <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][<?= $indikatorIndex ?>][indikator]" 
                                                   class="form-control mb-3 border-secondary" 
                                                   value="<?= esc($indikator['indikator']) ?>" 
                                                   placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Target</label>
                                            <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][<?= $indikatorIndex ?>][target]" 
                                                   class="form-control mb-3 border-secondary" 
                                                   value="<?= esc($indikator['target']) ?>" 
                                                   placeholder="Nilai target" required>
                                        </div>
                                    </div>
                                </div> <!-- End Indikator Sasaran -->
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Template Indikator jika tidak ada data -->
                                <div class="indikator-item border rounded p-3 bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium">Indikator</label>
                                        <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label class="form-label">Indikator</label>
                                            <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][0][indikator]" 
                                                   class="form-control mb-3 border-secondary" value="" 
                                                   placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Target</label>
                                            <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][0][target]" 
                                                   class="form-control mb-3 border-secondary" value="" 
                                                   placeholder="Nilai target" required>
                                        </div>
                                    </div>
                                </div> <!-- End Indikator Sasaran -->
                            <?php endif; ?>
                        </div> <!-- End Indikator Sasaran Container -->
                        <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="add-indikator btn btn-info btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Indikator
                            </button>
                        </div>
                    </div> <!-- End Indikator Sasaran Section -->
                </div> <!-- End Sasaran -->
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Template sasaran jika tidak ada data -->
                <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium h5">Sasaran</label>
                        <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </div>
                        <div class="mb-3">
                        <label class="form-label">Sasaran PK</label>
                        <textarea name="sasaran_pk[0][sasaran]" class="form-control border-secondary" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
                    </div>

                    <!-- Indikator -->
                    <div class="indikator-section">
                        <div class="indikator-container">
                            <!-- Indikator-->
                            <div class="indikator-item border rounded p-3 bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="fw-medium">Indikator</label>
                                    <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label">Indikator</label>
                                        <input type="text" name="sasaran_pk[0][indikator][0][indikator]" class="form-control mb-3 border-secondary" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Target</label>
                                        <input type="text" name="sasaran_pk[0][indikator][0][target]" class="form-control mb-3 border-secondary" value="" placeholder="Nilai target" required>
                                    </div>
                                </div>
                            </div> <!-- End Indikator Sasaran -->
                        </div> <!-- End Indikator Sasaran Container -->
                        <!-- Tombol Tambah Indikator Sasaran di bawah container -->
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="add-indikator btn btn-info btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Indikator
                            </button>
                        </div>
                    </div> <!-- End Indikator Sasaran Section -->
                </div> <!-- End Sasaran -->
            <?php endif; ?>
            </div> <!-- End Sasaran Container -->
            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="add-sasaran btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah Sasaran
                </button>
            </div>
        </div> <!-- End Sasaran Section -->


        <!-- Program PK -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Program Pk</h3>
        </div>

        <div class="program-container">
        <?php if (isset($pk_data['program_pk']) && !empty($pk_data['program_pk'])): ?>
            <?php foreach ($pk_data['program_pk'] as $programIndex => $selectedProgram): ?>
            <!-- Program <?= $programIndex + 1 ?> -->
            <div class="program-item border border-secondary rounded p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Program <?= $programIndex + 1 ?></label>
                    <button type="button" class="remove-program btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Program</label>
                            <select name="program[<?= $programIndex ?>][program_id]" class="form-select program-select mb-3" required>
                                <option value="">Pilih Program</option>
                                <?php if (isset($program) && !empty($program)): ?>
                                    <?php foreach ($program as $programItem): ?>
                                    <option value="<?= $programItem['id'] ?>" 
                                            data-anggaran="<?= $programItem['anggaran'] ?>"
                                            <?= (isset($selectedProgram['program_id']) && $programItem['id'] == $selectedProgram['program_id']) ? 'selected' : '' ?>>
                                        <?= esc($programItem['program_kegiatan']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada Program yang tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran</label>
                            <input type="text" class="form-control mb-3 border-secondary anggaran-display" 
                                   value="<?= 'Rp ' . number_format($selectedProgram['anggaran'], 0, ',', '.') ?>" 
                                   placeholder="Anggaran" readonly>
                        </div>
                    </div>
                </div>
            </div> <!-- End Program -->
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Template program jika tidak ada data -->
            <div class="program-item border border-secondary rounded p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Program 1</label>
                    <button type="button" class="remove-program btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Program</label>
                            <select name="program[0][program_id]" class="form-select program-select mb-3" required>
                                <option value="">Pilih Program</option>
                                <?php if (isset($program) && !empty($program)): ?>
                                    <?php foreach ($program as $programItem): ?>
                                    <option value="<?= $programItem['id'] ?>" data-anggaran="<?= $programItem['anggaran'] ?>">
                                        <?= esc($programItem['program_kegiatan']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada Program yang tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran</label>
                            <input type="text" class="form-control mb-3 border-secondary anggaran-display" value="" placeholder="Anggaran" readonly>
                        </div>
                    </div>
                </div>
            </div> <!-- End Program -->
        <?php endif; ?>
        </div> <!-- End Program Container -->
        <div class="d-flex justify-content-end mt-2">
                <button type="button" class="add-program btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Program
                </button>
            </div>
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminkab/pk_bupati') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Update
        </button>
      </div>
    </div>
    </form>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- JavaScript Funtion For Handling PK Form-->
  <script src="<?= base_url('assets/js/adminKabupaten/pk_bupati/pk-form.js') ?>"></script>

  <script>
    // Auto-fill anggaran saat program dipilih
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('program-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const anggaranInput = e.target.closest('.row').querySelector('input[name*="anggaran"], .anggaran-display');
            
            if (selectedOption.value !== '') {
                const anggaran = selectedOption.getAttribute('data-anggaran');
                anggaranInput.value = formatRupiah(anggaran);
            } else {
                anggaranInput.value = '';
            }
        }
    });

    // Fungsi format ke Rupiah
    function formatRupiah(angka) {
        return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
    }
  </script>
</body>
</html>


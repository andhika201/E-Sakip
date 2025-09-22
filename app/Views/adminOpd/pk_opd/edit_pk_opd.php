<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit PK OPD</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill d-flex justify-content-center p-4 mt-4 row">
    <div class="bg-white rounded shadow-sm p-4 mb-4" style="width: 100%; max-width: 1200px;">
        <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit PK OPD</h2>

        <form id="rpjmd-form" method="POST" action="<?= base_url('adminopd/pk_opd/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="pk_id" value="<?= $pk['pk_id'] ?>">
        
        <!-- Informasi Umum Misi -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum PK</h2>
          <div class="col">

            <label class="form-label fw-bold">Jenis PK</label>
            <select name="jenis" id="jenis-pk" class="form-select jenis-pk-select mb-3 border-secondary" required>
                <option value="">Pilih Jenis PK</option>
                <option value="jpt" <?= ($pk['jenis'] == 'jpt') ? 'selected' : '' ?>>PK Jabatan Pimpinan Tinggi</option>
                <option value="administrator" <?= ($pk['jenis'] == 'administrator') ? 'selected' : '' ?>>PK Administrator</option>
                <option value="pengawas" <?= ($pk['jenis'] == 'pengawas') ? 'selected' : '' ?>>PK Pejabat Pengawas</option>
            </select>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Pihak Kesatu</label>
                        <select name="pegawai_1_id" id="p1_select" class="form-select mb-3 border-secondary pegawai-select" data-target="nip-p1" required>
                            <option value="">Pilih Pihak Kesatu</option>
                            <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                                <?php foreach ($pegawaiOpd as $pegawai): ?>
                                <option value="<?= $pegawai['id'] ?>" data-nip="<?= $pegawai['nip_pegawai'] ?>" <?= ($pk['pihak_1'] == $pegawai['id']) ? 'selected' : '' ?>>
                                    <?= esc($pegawai['nama_pegawai']) ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak Ada Pegawai yang tersedia</option>
                            <?php endif; ?>
                        </select>
                    </div> 
                     <div class="col-md-4">
                        <label class="form-label">NIP Pegawai</label>
                        <input type="text" name="nip-p1" class="form-control mb-3 border-secondary" value="" placeholder="NIP" required readonly>
                    </div>                  
                </div>
            </div>

            <div class="col-md-12">
              <div class="row">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Pihak Kedua</label>
                        <select name="pegawai_2_id" id="p2_select" class="form-select mb-3 border-secondary pegawai-select" data-target="nip-p2" required>
                            <option value="">Pilih Pihak Kedua</option>
                            <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                                <?php foreach ($pegawaiOpd as $pegawai): ?>
                                <option value="<?= $pegawai['id'] ?>" data-nip="<?= $pegawai['nip_pegawai'] ?>" <?= ($pk['pihak_2'] == $pegawai['id']) ? 'selected' : '' ?>>
                                    <?= esc($pegawai['nama_pegawai']) ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak Ada Pegawai yang tersedia</option>
                            <?php endif; ?>
                        </select>
                    </div> 
                     <div class="col-md-4">
                        <label class="form-label">NIP Pegawai</label>
                        <input type="text" name="nip-p2" class="form-control mb-3 border-secondary" value="" placeholder="NIP" required readonly>
                    </div>                  
                </div>
            </div>
          </div>
        </section>

        <!-- Sasaran -->
        <section>
        <div class="sasaran-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-medium h4">Sasaran Terkait PK Ini</h3>
            </div>

            <div class="sasaran-container">
                <?php if (!empty($pk['sasaran_pk'])): ?>
                    <?php foreach ($pk['sasaran_pk'] as $sasaranIndex => $sasaran): ?>
                        <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                            <!-- Hidden ID field untuk tracking existing sasaran -->
                            <input type="hidden" name="sasaran_pk[<?= $sasaranIndex ?>][id]" value="<?= isset($sasaran['id']) ? $sasaran['id'] : '' ?>">
                            
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
                                    <?php if (!empty($sasaran['indikator'])): ?>
                                        <?php foreach ($sasaran['indikator'] as $indikatorIndex => $indikator): ?>
                                            <div class="indikator-item border rounded p-3 bg-light mb-3">
                                                <!-- Hidden ID field untuk tracking existing indikator -->
                                                <input type="hidden" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][<?= $indikatorIndex ?>][id]" value="<?= isset($indikator['id']) ? $indikator['id'] : '' ?>">
                                                
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <label class="fw-medium">Indikator <?= $indikatorIndex + 1 ?></label>
                                                    <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <label class="form-label">Indikator</label>
                                                        <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][<?= $indikatorIndex ?>][indikator]" class="form-control mb-3 border-secondary" value="<?= esc($indikator['indikator']) ?>" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Target</label>
                                                        <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][<?= $indikatorIndex ?>][target]" class="form-control mb-3 border-secondary" value="<?= esc($indikator['target']) ?>" placeholder="Nilai target" required>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Default indikator kosong jika tidak ada -->
                                        <div class="indikator-item border rounded p-3 bg-light mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <label class="fw-medium">Indikator 1</label>
                                                <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <label class="form-label">Indikator</label>
                                                    <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][0][indikator]" class="form-control mb-3 border-secondary" value="" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Target</label>
                                                    <input type="text" name="sasaran_pk[<?= $sasaranIndex ?>][indikator][0][target]" class="form-control mb-3 border-secondary" value="" placeholder="Nilai target" required>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button" class="add-indikator btn btn-info btn-sm">
                                        <i class="fas fa-plus me-1"></i> Tambah Indikator
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default sasaran kosong jika tidak ada data -->
                    <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium h5">Sasaran 1</label>
                            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sasaran PK</label>
                            <textarea name="sasaran_pk[0][sasaran]" class="form-control border-secondary" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
                        </div>

                        <!-- Indikator -->
                        <div class="indikator-section">
                            <div class="indikator-container">
                                <div class="indikator-item border rounded p-3 bg-light mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium">Indikator 1</label>
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
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="add-indikator btn btn-info btn-sm">
                                    <i class="fas fa-plus me-1"></i> Tambah Indikator
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-sasaran btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Sasaran
                    </button>
                </div>
            </div> <!-- End Sasaran Container -->
        </div> <!-- End Sasaran Section -->


        <!-- Program PK -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-medium">Program Pk</h3>
        </div>

        <div class="program-container">
            <?php if (!empty($pk['program'])): ?>
                <?php foreach ($pk['program'] as $programIndex => $programData): ?>
                    <div class="program-item border border-secondary rounded p-3 bg-white mb-3">
                        <!-- Hidden ID field untuk tracking existing program -->
                        <input type="hidden" name="program[<?= $programIndex ?>][id]" value="<?= isset($programData['pk_program_id']) ? $programData['pk_program_id'] : '' ?>">
                        
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
                                            <option value="<?= $programItem['id'] ?>" data-anggaran="<?= $programItem['anggaran'] ?>" <?= ($programData['program_id'] == $programItem['id']) ? 'selected' : '' ?>>
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
                                    <input type="text" name="program[<?= $programIndex ?>][anggaran]" class="form-control mb-3 border-secondary" value="<?= isset($programData['anggaran']) ? 'Rp ' . number_format($programData['anggaran'], 0, ',', '.') : '' ?>" placeholder="Anggaran" required readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default program kosong jika tidak ada data -->
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
                                <input type="text" name="program[0][anggaran]" class="form-control mb-3 border-secondary" value="" placeholder="Anggaran" required readonly>
                            </div>
                        </div>
                    </div>
                </div>
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
        <a href="<?= base_url('adminopd/pk_admin') ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Simpan
        </button>
      </div>
        </section>
      </form>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>

  <!-- JavaScript Funtion For Handling RPJMD Form-->
  <script src="<?= base_url('assets/js/adminOpd/pk/pk-form.js') ?>"></script>

  <script>
    // Auto-fill anggaran saat program dipilih
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('program-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const anggaranInput = e.target.closest('.row').querySelector('input[name*="anggaran"]');
            
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

    const pegawaiSelects = document.querySelectorAll('.pegawai-select');

    pegawaiSelects.forEach(function(select) {
        select.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const nip = selectedOption.getAttribute('data-nip');
            const targetName = this.getAttribute('data-target');
            const nipInput = document.querySelector(`input[name="${targetName}"]`);

            if (nipInput) {
                nipInput.value = nip || '';
            }
        });
    });

    // Auto-fill NIP pada saat halaman dimuat (untuk edit form)
    document.addEventListener('DOMContentLoaded', function() {
        pegawaiSelects.forEach(function(select) {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                const nip = selectedOption.getAttribute('data-nip');
                const targetName = select.getAttribute('data-target');
                const nipInput = document.querySelector(`input[name="${targetName}"]`);

                if (nipInput && nip) {
                    nipInput.value = nip;
                }
            }
        });

        // Auto-populate anggaran untuk form edit yang sudah ada data
        document.querySelectorAll('.program-select').forEach(function(select) {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.value !== '') {
                const anggaran = selectedOption.getAttribute('data-anggaran');
                const anggaranInput = select.closest('.row').querySelector('input[name*="anggaran"]');
                if (anggaranInput && anggaran && !anggaranInput.value) {
                    anggaranInput.value = formatRupiah(anggaran);
                }
            }
        });
    });
</script>
</body>
</html>


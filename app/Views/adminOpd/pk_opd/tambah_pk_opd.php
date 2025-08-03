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
  <main class="flex-fill d-flex justify-content-center p-4 mt-4 row">
    <div class="bg-white rounded shadow-sm p-4 mb-4" style="width: 100%; max-width: 1200px;">
        <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah PK</h2>

        <form id="pk-opd-form" method="POST" action="<?= base_url('adminopd/pk_opd/save') ?>">
        <?= csrf_field() ?>
        
        <!-- Informasi Umum PK -->
        <section class="mb-4">
          <h2 class="h5 fw-semibold mb-3">Informasi Umum PK</h2>
          <div class="col">

            <label class="form-label fw-bold">Jenis PK</label>
            <select name="jenis" id="jenis-pk" class="form-select jenis-pk-select mb-3 border-secondary" required>
                <option value="">Pilih Jenis PK</option>
                <option value="JPT">PK Jabatan Pimpinan Tinggi</option>
                <option value="Administrator">PK Administrator</option>
                <option value="Pengawas">PK Pejabat Pengawas</option>
            </select>

            <!-- Field dinamis berdasarkan jenis PK -->
            <div id="dynamic-fields"></div>

            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold">Tanggal PK</label>
                <input type="date" name="tanggal" class="form-control border-secondary" required>
            </div>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Pihak Kesatu</label>
                        <select name="pihak_1" id="p1_select" class="form-select mb-3 border-secondary pegawai-select" data-target="nip-p1" required>
                            <option value="">Pilih Pihak Kesatu</option>
                            <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                                <?php foreach ($pegawaiOpd as $pegawai): ?>
                                <option value="<?= $pegawai['id'] ?>" data-nip="<?= $pegawai['nip_pegawai'] ?>">
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
                        <select name="pihak_2" id="p2_select" class="form-select mb-3 border-secondary pegawai-select" data-target="nip-p2" required>
                            <option value="">Pilih Pihak Kedua</option>
                            <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                                <?php foreach ($pegawaiOpd as $pegawai): ?>
                                <option value="<?= $pegawai['id'] ?>" data-nip="<?= $pegawai['nip_pegawai'] ?>">
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
    </div>
    
    <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
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
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-sasaran btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Sasaran
                    </button>
                </div>
            </div> <!-- End Sasaran Container -->
        </div> <!-- End Sasaran Section -->


        <!-- Program PK / Kegiatan OPD -->
        <div id="program-kegiatan-section" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-medium" id="program-kegiatan-title">Program/Kegiatan</h3>
            </div>

            <div id="program-kegiatan-container">
                <!-- Program/Kegiatan items will be dynamically added here -->
            </div>
            
            <div class="d-flex justify-content-end mt-2">
                <button type="button" id="add-program-kegiatan" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> <span id="add-button-text">Tambah Program</span>
                </button>
            </div>
        </div>
      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminopd/pk_opd') ?>" class="btn btn-secondary">
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

  <!-- JavaScript Function For Handling PK OPD Form-->
  <script src="<?= base_url('assets/js/adminOpd/pk/pk-opd-form.js') ?>"></script>

  <script>
    // Data untuk form dinamis
    const formData = {
        pegawaiOpd: <?= json_encode($pegawaiOpd ?? []) ?>,
        program: <?= json_encode($program ?? []) ?>,
        kegiatan: <?= json_encode($kegiatan ?? []) ?>,
        rpjmdMisiSasaran: <?= json_encode($rpjmdMisiSasaran ?? []) ?>,
        pkJptSasaran: <?= json_encode($pkJptSasaran ?? []) ?>,
        pkAdministratorSasaran: <?= json_encode($pkAdministratorSasaran ?? []) ?>
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Handle jenis PK change
        document.getElementById('jenis-pk').addEventListener('change', function() {
            const jenis = this.value;
            updateDynamicFields(jenis);
            updateProgramKegiatanSection(jenis);
        });

        // Auto-fill NIP when pegawai selected
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('pegawai-select')) {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const nip = selectedOption.getAttribute('data-nip');
                const targetName = e.target.getAttribute('data-target');
                const nipInput = document.querySelector(`input[name="${targetName}"]`);

                if (nipInput) {
                    nipInput.value = nip || '';
                }
            }

            // Auto-fill anggaran when program selected
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
    });

    function updateDynamicFields(jenis) {
        const dynamicFieldsDiv = document.getElementById('dynamic-fields');
        dynamicFieldsDiv.innerHTML = '';

        if (jenis === 'JPT') {
            // Show RPJMD Misi selection
            dynamicFieldsDiv.innerHTML = `
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Pilih Sasaran Misi RPJMD</label>
                    <select name="rpjmd_misi_id" class="form-select border-secondary" required>
                        <option value="">Pilih Sasaran Misi RPJMD</option>
                        ${formData.rpjmdMisiSasaran.map(item => 
                            `<option value="${item.misi_id}">${item.misi} - ${item.sasaran}</option>`
                        ).join('')}
                    </select>
                </div>
            `;
        } else if (jenis === 'Administrator') {
            // Show PK JPT selection
            dynamicFieldsDiv.innerHTML = `
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Pilih Sasaran PK JPT</label>
                    <select name="parent_jpt_id" class="form-select border-secondary" required>
                        <option value="">Pilih Sasaran PK JPT</option>
                        ${formData.pkJptSasaran.map(item => 
                            `<option value="${item.pk_id}">${item.sasaran} (${item.pihak_1_nama} - ${item.pihak_2_nama})</option>`
                        ).join('')}
                    </select>
                </div>
            `;
        } else if (jenis === 'Pengawas') {
            // Show PK Administrator selection
            dynamicFieldsDiv.innerHTML = `
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Pilih Sasaran PK Administrator</label>
                    <select name="parent_admin_id" class="form-select border-secondary" required>
                        <option value="">Pilih Sasaran PK Administrator</option>
                        ${formData.pkAdministratorSasaran.map(item => 
                            `<option value="${item.pk_id}">${item.sasaran} (${item.pihak_1_nama} - ${item.pihak_2_nama})</option>`
                        ).join('')}
                    </select>
                </div>
            `;
        }
    }

    function updateProgramKegiatanSection(jenis) {
        const section = document.getElementById('program-kegiatan-section');
        const title = document.getElementById('program-kegiatan-title');
        const addButtonText = document.getElementById('add-button-text');
        const container = document.getElementById('program-kegiatan-container');

        if (!jenis) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        container.innerHTML = '';

        if (jenis === 'Pengawas') {
            title.textContent = 'Kegiatan OPD';
            addButtonText.textContent = 'Tambah Kegiatan';
            addKegiatanItem(0);
        } else {
            title.textContent = 'Program PK';
            addButtonText.textContent = 'Tambah Program';
            addProgramItem(0);
        }
    }

    function addProgramItem(index) {
        const container = document.getElementById('program-kegiatan-container');
        const programHtml = `
            <div class="program-item border border-secondary rounded p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Program ${index + 1}</label>
                    <button type="button" class="remove-item btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Program</label>
                            <select name="program[${index}][program_id]" class="form-select program-select mb-3" required>
                                <option value="">Pilih Program</option>
                                ${formData.program.map(item => 
                                    `<option value="${item.id}" data-anggaran="${item.anggaran}">${item.program_kegiatan}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran</label>
                            <input type="text" name="program[${index}][anggaran]" class="form-control mb-3 border-secondary" placeholder="Anggaran" readonly>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', programHtml);
    }

    function addKegiatanItem(index) {
        const container = document.getElementById('program-kegiatan-container');
        const kegiatanHtml = `
            <div class="kegiatan-item border border-secondary rounded p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Kegiatan ${index + 1}</label>
                    <button type="button" class="remove-item btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Kegiatan</label>
                            <select name="kegiatan[${index}][kegiatan_id]" class="form-select kegiatan-select mb-3" required>
                                <option value="">Pilih Kegiatan</option>
                                ${formData.kegiatan.map(item => 
                                    `<option value="${item.id}" data-anggaran="${item.anggaran}">${item.nama_kegiatan}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran</label>
                            <input type="text" name="kegiatan[${index}][anggaran]" class="form-control mb-3 border-secondary" placeholder="Anggaran" readonly>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', kegiatanHtml);
    }

    // Event listeners
    document.addEventListener('click', function(e) {
        if (e.target.closest('#add-program-kegiatan')) {
            const jenis = document.getElementById('jenis-pk').value;
            const container = document.getElementById('program-kegiatan-container');
            const currentItems = container.children.length;
            
            if (jenis === 'Pengawas') {
                addKegiatanItem(currentItems);
            } else {
                addProgramItem(currentItems);
            }
        }

        if (e.target.closest('.remove-item')) {
            e.target.closest('.program-item, .kegiatan-item').remove();
        }
    });

    // Auto-fill anggaran for kegiatan
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('kegiatan-select')) {
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

    // Format rupiah function
    function formatRupiah(angka) {
        return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
    }
  </script>
</body>
</html>


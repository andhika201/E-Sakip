<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tambah RKT e-SAKIP</title>
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
      <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah RKT</h2>

      <!-- Error Messages -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Success Messages -->
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= session()->getFlashdata('success') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Validation Errors -->
      <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong>Terdapat kesalahan:</strong>
          <ul class="mb-0 mt-2">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
              <li><?= esc($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form id="rkt-form" method="POST" action="<?= base_url('adminkab/rkt/save') ?>">
        <?= csrf_field() ?>

      <!-- Informasi Umum -->
      <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran RPJMD Terkait RKT ini</h2>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Sasaran RPJMD</label>
            <select name="rpjmd_sasaran_id" id="rpjmd-sasaran-select" class="form-select mb-3" required>
              <option value="">Pilih Sasaran RPJMD</option>
              <?php if (isset($sasaran_rpjmd) && is_array($sasaran_rpjmd)): ?>
                <?php foreach ($sasaran_rpjmd as $sasaran): ?>
                  <option value="<?= $sasaran['id'] ?>" 
                          data-tahun-mulai="<?= $sasaran['tahun_mulai'] ?>" 
                          data-tahun-akhir="<?= $sasaran['tahun_akhir'] ?>">
                    <?= esc($sasaran['sasaran_rpjmd']) ?> (Periode: <?= $sasaran['tahun_mulai'] ?>-<?= $sasaran['tahun_akhir'] ?>)
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>
      </section>

      <!-- Daftar Sasaran RKT -->
      <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-semibold">Daftar Sasaran RKT</h2>
        </div>

        <div id="sasaran-rkt-container">
          <!-- Sasaran RKT 1 -->
          <div class="sasaran-rkt-item bg-light border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="fw-medium">Sasaran RKT 1</label>
              <button type="button" class="remove-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Sasaran RKT</label>
                <textarea name="sasaran_rkt[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RKT" required></textarea>
              </div>
            </div>

            <!-- Indikator Sasaran RKT -->
            <div class="indikator-sasaran-rkt-section">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h5 fw-medium">Indikator Sasaran RKT</h4>
              </div>

              <div class="indikator-sasaran-rkt-container">
                <!-- Indikator Sasaran RKT 1.1 -->
                <div class="indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator Sasaran 1.1</label>
                    <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Indikator Sasaran</label>
                    <textarea name="sasaran_rkt[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label class="form-label">Satuan</label>
                      <select name="sasaran_rkt[0][indikator_sasaran][0][satuan]" class="form-select" required>
                        <option value="">Pilih Satuan</option>
                        <option value="Unit">Unit</option>
                        <option value="Nilai">Nilai</option>
                        <option value="Persen">Persen</option>
                        <option value="Predikat">Predikat</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Tahun Target</label>
                      <select name="sasaran_rkt[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                        <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Target</label>
                      <input type="text" name="sasaran_rkt[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                    </div>
                  </div>
                </div> <!-- End Indikator Sasaran RKT -->
              </div> <!-- End Indikator Sasaran RKT Container -->
            </div> <!-- End Indikator Sasaran RKT Section -->

            <!-- Tombol Tambah Indikator Sasaran -->
            <div class="d-flex justify-content-end">
              <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
              </button>
            </div>

          </div> <!-- End Sasaran RKT -->
        </div> <!-- End Sasaran RKT Container -->

        <!-- Tombol Tambah Sasaran RKT -->
        <div class="d-flex justify-content-end">
          <button type="button" id="add-sasaran-rkt" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran RKT
          </button>
        </div>

      </section>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-content-between mt-4">
        <a href="<?= base_url('adminkab/rkt') ?>" class="btn btn-secondary">
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

  <script>
    // Fungsi helper untuk menghasilkan option satuan
    // Untuk mengubah pilihan satuan, cukup edit array satuanOptions di bawah ini
    // Semua template akan otomatis menggunakan pilihan yang sama
    function generateSatuanOptions() {
      const satuanOptions = [
        { value: "", text: "Pilih Satuan" },
        { value: "Unit", text: "Unit" },
        { value: "Nilai", text: "Nilai" },
        { value: "Persen", text: "Persen" },
        { value: "Predikat", text: "Predikat" }
      ];
      
      return satuanOptions.map(option => 
        `<option value="${option.value}">${option.text}</option>`
      ).join('\n                ');
    }

    // Fungsi helper untuk menghasilkan option tahun berdasarkan periode RPJMD
    function generateTahunOptions(tahunMulai = null, tahunAkhir = null) {
      if (!tahunMulai || !tahunAkhir) {
        return '<option value="">Pilih Sasaran RPJMD terlebih dahulu</option>';
      }
      
      let options = '<option value="">Pilih Tahun</option>';
      for (let tahun = parseInt(tahunMulai); tahun <= parseInt(tahunAkhir); tahun++) {
        options += `<option value="${tahun}">${tahun}</option>`;
      }
      return options;
    }

    // Fungsi untuk mengupdate semua dropdown tahun
    function updateAllTahunDropdowns() {
      const sasaranSelect = document.getElementById('rpjmd-sasaran-select');
      const selectedOption = sasaranSelect.options[sasaranSelect.selectedIndex];
      
      let tahunMulai = null;
      let tahunAkhir = null;
      
      if (selectedOption && selectedOption.value) {
        tahunMulai = selectedOption.getAttribute('data-tahun-mulai');
        tahunAkhir = selectedOption.getAttribute('data-tahun-akhir');
      }
      
      // Update semua dropdown tahun yang ada
      document.querySelectorAll('select[name*="tahun"]').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = generateTahunOptions(tahunMulai, tahunAkhir);
        
        // Restore value jika masih valid
        if (currentValue && tahunMulai && tahunAkhir) {
          const currentTahun = parseInt(currentValue);
          const mulai = parseInt(tahunMulai);
          const akhir = parseInt(tahunAkhir);
          
          if (currentTahun >= mulai && currentTahun <= akhir) {
            select.value = currentValue;
          }
        }
      });
    }

    // Fungsi untuk mengupdate penomoran label secara real-time
    function updateLabels() {
      document.querySelectorAll('.sasaran-rkt-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;
        
        // Update label sasaran RKT
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
          sasaranLabel.textContent = `Sasaran RKT ${sasaranNumber}`;
        }

        // Update indikator sasaran RKT labels
        sasaranItem.querySelectorAll('.indikator-sasaran-rkt-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorNumber = indikatorIndex + 1;
          const indikatorLabel = indikatorItem.querySelector('label');
          if (indikatorLabel) {
            indikatorLabel.textContent = `Indikator Sasaran ${sasaranNumber}.${indikatorNumber}`;
          }
        });
      });
    }

    // Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
    function updateFormNames() {
      document.querySelectorAll('.sasaran-rkt-item').forEach((sasaranItem, sasaranIndex) => {
        // Update sasaran RKT names
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
        
        if (sasaranTextarea) {
          sasaranTextarea.name = `sasaran_rkt[${sasaranIndex}][sasaran]`;
        }

        // Update indikator sasaran RKT names
        sasaranItem.querySelectorAll('.indikator-sasaran-rkt-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_sasaran"]');
          const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
          const tahunSelect = indikatorItem.querySelector('select[name*="tahun"]');
          const targetInput = indikatorItem.querySelector('input[name*="target"]');
          
          if (indikatorTextarea) {
            indikatorTextarea.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][indikator_sasaran]`;
          }
          if (satuanSelect) {
            satuanSelect.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][satuan]`;
          }
          if (tahunSelect) {
            tahunSelect.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][tahun]`;
          }
          if (targetInput) {
            targetInput.name = `sasaran_rkt[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][target]`;
          }
        });
      });
    }

    // Tambah Sasaran RKT Baru
    document.getElementById('add-sasaran-rkt').addEventListener('click', () => {
      const sasaranContainer = document.getElementById('sasaran-rkt-container');
      
      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-rkt-item bg-light border rounded p-3 mb-3';
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran RKT</label>
          <button type="button" class="remove-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran RKT</label>
            <textarea class="form-control" rows="2" placeholder="Masukkan sasaran RKT" required></textarea>
          </div>
        </div>

        <div class="indikator-sasaran-rkt-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 fw-medium">Indikator Sasaran RKT</h4>
          </div>
          <div class="indikator-sasaran-rkt-container">
            <div class="indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Indikator Sasaran</label>
                <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Indikator Sasaran</label>
                <textarea class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Satuan</label>
                  <select class="form-select" required>
                    ` + generateSatuanOptions() + `
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tahun Target</label>
                  <select class="form-select tahun-select" required>
                    <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Target</label>
                  <input type="text" class="form-control" placeholder="Nilai target" required>
                </div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end">
            <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
      
      // Update dropdown tahun untuk sasaran baru
      updateAllTahunDropdowns();
    });

    // Fungsi untuk menambahkan indikator sasaran RKT
    function addIndikatorSasaranRKT(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-rkt-container');
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-sasaran-rkt-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Sasaran</label>
          <button type="button" class="remove-indikator-sasaran-rkt btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Indikator Sasaran</label>
          <textarea class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <select class="form-select" required>
              ` + generateSatuanOptions() + `
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tahun Target</label>
            <select class="form-select tahun-select" required>
              <option value="">Pilih Sasaran RPJMD terlebih dahulu</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Target</label>
            <input type="text" class="form-control" placeholder="Nilai target" required>
          </div>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      updateLabels();
      updateFormNames();
      
      // Update dropdown tahun untuk indikator baru
      updateAllTahunDropdowns();
    }

    // Event listener untuk perubahan sasaran RPJMD
    document.getElementById('rpjmd-sasaran-select').addEventListener('change', function() {
      updateAllTahunDropdowns();
    });

    // Event delegation untuk semua tombol
    document.addEventListener('click', function(e) {
      // Tombol tambah indikator sasaran RKT
      if (e.target.classList.contains('add-indikator-sasaran-rkt') || e.target.closest('.add-indikator-sasaran-rkt')) {
        const sasaranItem = e.target.closest('.sasaran-rkt-item');
        addIndikatorSasaranRKT(sasaranItem);
      }
      
      // Tombol hapus sasaran RKT
      if (e.target.classList.contains('remove-sasaran-rkt') || e.target.closest('.remove-sasaran-rkt')) {
        if (confirm('Hapus sasaran RKT ini dan semua indikator sasarannya?')) {
          e.target.closest('.sasaran-rkt-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator sasaran RKT
      if (e.target.classList.contains('remove-indikator-sasaran-rkt') || e.target.closest('.remove-indikator-sasaran-rkt')) {
        if (confirm('Hapus indikator sasaran ini?')) {
          e.target.closest('.indikator-sasaran-rkt-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
    });

    // Initialize pada load
    document.addEventListener('DOMContentLoaded', function() {
      updateLabels();
      updateFormNames();
      
      // Initialize dropdown tahun
      updateAllTahunDropdowns();
    });

    // Form validation dan submit handler
    document.getElementById('rkt-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validasi RPJMD Sasaran
      const rpjmdSasaranId = document.querySelector('select[name="rpjmd_sasaran_id"]').value;
      if (!rpjmdSasaranId) {
        alert('Pilih Sasaran RPJMD terlebih dahulu');
        return false;
      }

      // Validasi minimal 1 sasaran RKT
      const sasaranItems = document.querySelectorAll('.sasaran-rkt-item');
      if (sasaranItems.length === 0) {
        alert('Minimal harus ada 1 Sasaran RKT');
        return false;
      }

      // Validasi setiap sasaran RKT harus diisi
      let isValid = true;
      sasaranItems.forEach((sasaran, index) => {
        const sasaranText = sasaran.querySelector('textarea[name*="sasaran"]');
        if (!sasaranText || !sasaranText.value.trim()) {
          alert(`Sasaran RKT ${index + 1} harus diisi`);
          isValid = false;
          return;
        }

        // Validasi minimal 1 indikator sasaran per sasaran RKT
        const indikatorItems = sasaran.querySelectorAll('.indikator-sasaran-rkt-item');
        if (indikatorItems.length === 0) {
          alert(`Sasaran RKT ${index + 1} harus memiliki minimal 1 Indikator Sasaran`);
          isValid = false;
          return;
        }

        // Validasi setiap indikator sasaran harus diisi lengkap
        indikatorItems.forEach((indikator, indikatorIndex) => {
          const indikatorText = indikator.querySelector('textarea[name*="indikator_sasaran"]');
          const satuan = indikator.querySelector('select[name*="satuan"]');
          const tahun = indikator.querySelector('select[name*="tahun"]');
          const target = indikator.querySelector('input[name*="target"]');

          if (!indikatorText || !indikatorText.value.trim()) {
            alert(`Indikator Sasaran ${index + 1}.${indikatorIndex + 1} harus diisi`);
            isValid = false;
            return;
          }
          if (!satuan || !satuan.value) {
            alert(`Satuan untuk Indikator Sasaran ${index + 1}.${indikatorIndex + 1} harus dipilih`);
            isValid = false;
            return;
          }
          if (!tahun || !tahun.value) {
            alert(`Tahun untuk Indikator Sasaran ${index + 1}.${indikatorIndex + 1} harus dipilih`);
            isValid = false;
            return;
          }
          
          // Validasi tahun harus dalam periode RPJMD
          const sasaranSelect = document.getElementById('rpjmd-sasaran-select');
          const selectedOption = sasaranSelect.options[sasaranSelect.selectedIndex];
          
          if (selectedOption && selectedOption.value) {
            const tahunMulai = parseInt(selectedOption.getAttribute('data-tahun-mulai'));
            const tahunAkhir = parseInt(selectedOption.getAttribute('data-tahun-akhir'));
            const selectedTahun = parseInt(tahun.value);
            
            if (selectedTahun < tahunMulai || selectedTahun > tahunAkhir) {
              alert(`Tahun untuk Indikator Sasaran ${index + 1}.${indikatorIndex + 1} harus dalam periode ${tahunMulai}-${tahunAkhir}`);
              isValid = false;
              return;
            }
          }
          if (!target || !target.value.trim()) {
            alert(`Target untuk Indikator Sasaran ${index + 1}.${indikatorIndex + 1} harus diisi`);
            isValid = false;
            return;
          }
        });
      });

      if (!isValid) {
        return false;
      }

      // Konfirmasi sebelum submit
      const confirmed = confirm('Apakah Anda yakin ingin menyimpan data RKT ini?');
      if (confirmed) {
        this.submit();
      }
    });
  </script>
</body>
</html>
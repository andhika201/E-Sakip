  // Fungsi helper untuk menghasilkan option satuan
  // Semua template akan otomatis menggunakan pilihan yang sama
  function generateSatuanOptions() {
      const satuanOptions = [
          { value: "", text: "Pilih Satuan" },
          { value: "Persen", text: "Persen (%)" },
          { value: "Orang", text: "Orang" },
          { value: "Unit", text: "Unit" },
          { value: "Dokumen", text: "Dokumen" },
          { value: "Kegiatan", text: "Kegiatan" },
          { value: "Rupiah", text: "Rupiah" },
          { value: "Index", text: "Index" },
          { value: "Nilai", text: "Nilai" },
          { value: "Predikat", text: "Predikat" }
      ];
      
      return satuanOptions.map(option => 
          `<option value="${option.value}">${option.text}</option>`
      ).join('\n                        ');
  }

    // Fungsi helper untuk menghasilkan option tahun berdasarkan periode RENSTRA
    function generateTahunOptions(tahunMulai = null, tahunAkhir = null) {
      if (!tahunMulai || !tahunAkhir) {
        return '<option value="">Pilih Sasaran RENSTRA terlebih dahulu</option>';
      }
      
      let options = '<option value="">Pilih Tahun</option>';
      for (let tahun = parseInt(tahunMulai); tahun <= parseInt(tahunAkhir); tahun++) {
        options += `<option value="${tahun}">${tahun}</option>`;
      }
      return options;
    }

    // Fungsi untuk mengupdate semua dropdown tahun
    function updateAllTahunDropdowns() {
      const sasaranSelect = document.getElementById('renstra-sasaran-select');
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
      document.querySelectorAll('.sasaran-renja-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;
        
        // Update label Sasaran RENJA
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
          sasaranLabel.textContent = `Sasaran RENJA ${sasaranNumber}`;
        }

        // Update indikator Sasaran RENJA labels
        sasaranItem.querySelectorAll('.indikator-sasaran-renja-item').forEach((indikatorItem, indikatorIndex) => {
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
      document.querySelectorAll('.sasaran-renja-item').forEach((sasaranItem, sasaranIndex) => {
        // Update Sasaran RENJA names
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
        
        if (sasaranTextarea) {
          sasaranTextarea.name = `sasaran_renja[${sasaranIndex}][sasaran]`;
        }

        // Update indikator Sasaran RENJA names
        sasaranItem.querySelectorAll('.indikator-sasaran-renja-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_sasaran"]');
          const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
          const tahunSelect = indikatorItem.querySelector('select[name*="tahun"]');
          const targetInput = indikatorItem.querySelector('input[name*="target"]');
          
          if (indikatorTextarea) {
            indikatorTextarea.name = `sasaran_renja[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][indikator_sasaran]`;
          }
          if (satuanSelect) {
            satuanSelect.name = `sasaran_renja[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][satuan]`;
          }
          if (tahunSelect) {
            tahunSelect.name = `sasaran_renja[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][tahun]`;
          }
          if (targetInput) {
            targetInput.name = `sasaran_renja[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][target]`;
          }
        });
      });
    }

    // Tambah Sasaran RENJA Baru
    document.getElementById('add-sasaran-renja').addEventListener('click', function() {
      const sasaranContainer = document.getElementById('sasaran-renja-container');
      
      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-renja-item bg-light border rounded p-3 mb-3';
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran RENJA</label>
          <button type="button" class="remove-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran RENJA</label>
            <textarea name="sasaran_renja[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan Sasaran RENJA" required></textarea>
          </div>
        </div>

        <div class="indikator-sasaran-renja-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 fw-medium">Indikator Sasaran RENJA</h4>
          </div>
          <div class="indikator-sasaran-renja-container">
            <div class="indikator-sasaran-renja-item border rounded p-3 bg-white mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Indikator Sasaran</label>
                <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Indikator Sasaran</label>
                <textarea name="sasaran_renja[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Satuan</label>
                  <select name="sasaran_renja[0][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
                    ${generateSatuanOptions()}
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tahun Target</label>
                  <select name="sasaran_renja[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
                    <option value="">Pilih Sasaran RENSTRA terlebih dahulu</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Target</label>
                  <input type="text" name="sasaran_renja[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
                </div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end">
            <button type="button" class="add-indikator-sasaran-renja btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
      
      // Populate satuan selects untuk sasaran baru
      populateAllSatuanSelects();
      
      // Update dropdown tahun untuk sasaran baru
      updateAllTahunDropdowns();
    });

    // Fungsi untuk menambahkan indikator Sasaran RENJA
    function addIndikatorSasaranRENJA(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-renja-container');
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-sasaran-renja-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Sasaran</label>
          <button type="button" class="remove-indikator-sasaran-renja btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Indikator Sasaran</label>
          <textarea name="sasaran_renja[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <select name="sasaran_renja[0][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
                ${generateSatuanOptions()}
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tahun Target</label>
            <select name="sasaran_renja[0][indikator_sasaran][0][tahun]" class="form-select tahun-select" required>
              <option value="">Pilih Sasaran RENSTRA terlebih dahulu</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Target</label>
            <input type="text" name="sasaran_renja[0][indikator_sasaran][0][target]" class="form-control" placeholder="Nilai target" required>
          </div>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      updateLabels();
      updateFormNames();
      
      // Populate satuan selects untuk indikator baru
      populateAllSatuanSelects();
      
      // Update dropdown tahun untuk indikator baru
      updateAllTahunDropdowns();
    }

    // Event delegation untuk semua tombol
    document.addEventListener('click', function(e) {
     
      // Tombol tambah indikator Sasaran RENJA
      if (e.target.classList.contains('add-indikator-sasaran-renja') || e.target.closest('.add-indikator-sasaran-renja')) {
        const sasaranItem = e.target.closest('.sasaran-renja-item');
        addIndikatorSasaranRENJA(sasaranItem);
      }
      
      // Tombol hapus Sasaran RENJA
      if (e.target.classList.contains('remove-sasaran-renja') || e.target.closest('.remove-sasaran-renja')) {
        if (confirm('Hapus Sasaran RENJA ini dan semua indikator sasarannya?')) {
          e.target.closest('.sasaran-renja-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator Sasaran RENJA
      if (e.target.classList.contains('remove-indikator-sasaran-renja') || e.target.closest('.remove-indikator-sasaran-renja')) {
        if (confirm('Hapus indikator sasaran ini?')) {
          e.target.closest('.indikator-sasaran-renja-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
    });

    // Fungsi untuk populate semua select satuan
    function populateAllSatuanSelects() {
      document.querySelectorAll('.satuan-select').forEach(select => {
        const currentValue = select.value;
        const selectedValue = select.getAttribute('data-selected') || currentValue;
        select.innerHTML = generateSatuanOptions();
        if (selectedValue) {
          select.value = selectedValue;
        }
      });
    }

    // Initialize pada load
    document.addEventListener('DOMContentLoaded', function() {
      updateLabels();
      updateFormNames();
      
      // Initialize dropdown satuan
      populateAllSatuanSelects();
      
      // Initialize dropdown tahun
      updateAllTahunDropdowns();
    });

    // Form validation dan submit handler
    document.getElementById('renja-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validasi RENSTRA Sasaran
      const renstraSasaranId = document.querySelector('select[name="renstra_sasaran_id"]').value;
      if (!renstraSasaranId) {
        alert('Pilih Sasaran RENSTRA terlebih dahulu');
        return false;
      }

      // Validasi minimal 1 Sasaran RENJA
      const sasaranItems = document.querySelectorAll('.sasaran-renja-item');
      if (sasaranItems.length === 0) {
        alert('Minimal harus ada 1 Sasaran RENJA');
        return false;
      }

      // Validasi setiap Sasaran RENJA harus diisi
      let isValid = true;
      sasaranItems.forEach((sasaran, index) => {
        const sasaranText = sasaran.querySelector('textarea[name*="sasaran"]');
        if (!sasaranText || !sasaranText.value.trim()) {
          alert(`Sasaran RENJA ${index + 1} harus diisi`);
          isValid = false;
          return;
        }

        // Validasi minimal 1 indikator sasaran per Sasaran RENJA
        const indikatorItems = sasaran.querySelectorAll('.indikator-sasaran-renja-item');
        if (indikatorItems.length === 0) {
          alert(`Sasaran RENJA ${index + 1} harus memiliki minimal 1 Indikator Sasaran`);
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

          // Validasi tahun harus dalam periode RENSTRA
          const sasaranSelect = document.getElementById('renstra-sasaran-select');
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
      const confirmed = confirm('Apakah Anda yakin ingin menyimpan data RENJA ini?');
      if (confirmed) {
        this.submit();
      }
    });
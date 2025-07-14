  // Fungsi helper untuk menghasilkan option satuan
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
    ).join('\n                        ');
  }
      
    // Fungsi untuk mengupdate tahun akhir otomatis
    document.getElementById('tahun_awal').addEventListener('input', function() {
      const tahunAwal = parseInt(this.value);
      if (tahunAwal && !isNaN(tahunAwal)) {
        const tahunAkhir = tahunAwal + 4; // Jarak 5 tahun (2025-2029)
        document.getElementById('tahun_akhir').value = tahunAkhir;
        
        // Update semua target tahunan
        updateAllTargetYears(tahunAwal, tahunAkhir);
      } else {
        document.getElementById('tahun_akhir').value = '';
      }
    });

    // Fungsi untuk mengupdate semua target tahunan
    function updateAllTargetYears(tahunAwal, tahunAkhir) {
      if (!tahunAwal || isNaN(tahunAwal)) return;
      
      document.querySelectorAll('.target-years-container').forEach(container => {
        const yearColumns = container.querySelector('.row');
        yearColumns.innerHTML = '';
        
        for (let i = 0; i < 5; i++) {
          const tahun = tahunAwal + i;
          const colDiv = document.createElement('div');
          colDiv.className = 'col-md-2';
          colDiv.innerHTML = `
            <label class="form-label fw-medium">${tahun}</label>
            <input type="text" class="form-control form-control-sm" placeholder="Target ${tahun}" required>
            <input type="hidden" value="${tahun}">
          `;
          yearColumns.appendChild(colDiv);
        }
        
        // Update form names setelah perubahan tahun
        updateFormNames();
      });
    }

    // Fungsi untuk mengupdate penomoran label secara real-time
    function updateLabels() {
      document.querySelectorAll('.sasaran-renstra-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;
        
        // Update label sasaran renstra
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
          sasaranLabel.textContent = `Sasaran Renstra ${sasaranNumber}`;
        }

        // Update indikator sasaran labels
        sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorItem, indikatorIndex) => {
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
      document.querySelectorAll('.sasaran-renstra-item').forEach((sasaranItem, sasaranIndex) => {
        // Update sasaran renstra names
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
        
        if (sasaranTextarea) {
          sasaranTextarea.name = `sasaran_renstra[${sasaranIndex}][sasaran]`;
        }

        // Update indikator sasaran names
        sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorItem, indikatorIndex) => {
          const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_sasaran"]');
          const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
          
          if (indikatorTextarea) {
            indikatorTextarea.name = `sasaran_renstra[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][indikator_sasaran]`;
          }
          if (satuanSelect) {
            satuanSelect.name = `sasaran_renstra[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][satuan]`;
          }

          // Update target tahunan names
          indikatorItem.querySelectorAll('.target-years-container .col-md-2').forEach((targetCol, targetIndex) => {
            const targetInput = targetCol.querySelector('input[type="text"]');
            const tahunInput = targetCol.querySelector('input[type="hidden"]');
            
            if (targetInput) {
              targetInput.name = `sasaran_renstra[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][target_tahunan][${targetIndex}][target]`;
            }
            if (tahunInput) {
              tahunInput.name = `sasaran_renstra[${sasaranIndex}][indikator_sasaran][${indikatorIndex}][target_tahunan][${targetIndex}][tahun]`;
            }
          });
        });
      });
    }

    // Tambah Sasaran Renstra Baru
    document.getElementById('add-sasaran-renstra').addEventListener('click', () => {
      const sasaranContainer = document.getElementById('sasaran-renstra-container');
      const tahunAwal = parseInt(document.getElementById('tahun_awal').value);
      
      if (!tahunAwal || isNaN(tahunAwal)) {
        alert('Silakan isi tahun awal terlebih dahulu');
        return;
      }
      
      const newSasaran = document.createElement('div');
      newSasaran.className = 'sasaran-renstra-item bg-light border rounded p-3 mb-3';
      
      // Generate target years HTML
      let targetYearsHTML = '';
      for (let i = 0; i < 5; i++) {
        const tahun = tahunAwal + i;
        targetYearsHTML += `
          <div class="col-md-2">
            <label class="form-label fw-medium">${tahun}</label>
            <input type="text" class="form-control form-control-sm" placeholder="Target ${tahun}" required>
            <input type="hidden" value="${tahun}">
          </div>
        `;
      }
      
      newSasaran.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Sasaran Renstra</label>
          <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label">Sasaran Renstra</label>
            <textarea class="form-control" rows="2" placeholder="Masukkan sasaran renstra" required></textarea>
          </div>
        </div>

        <div class="indikator-sasaran-section">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="h5 fw-medium">Indikator Sasaran</h4>
            <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
              <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
            </button>
          </div>
          <div class="indikator-sasaran-container"></div>
        </div>
      `;
      
      sasaranContainer.appendChild(newSasaran);
      updateLabels();
      updateFormNames();
    });

    // Fungsi untuk menambahkan indikator sasaran
    function addIndikatorSasaran(sasaranElement) {
      const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-container');
      const tahunAwal = parseInt(document.getElementById('tahun_awal').value);
      
      if (!tahunAwal || isNaN(tahunAwal)) {
        alert('Silakan isi tahun awal terlebih dahulu');
        return;
      }
      
      // Generate target years HTML
      let targetYearsHTML = '';
      for (let i = 0; i < 5; i++) {
        const tahun = tahunAwal + i;
        targetYearsHTML += `
          <div class="col-md-2">
            <label class="form-label fw-medium">${tahun}</label>
            <input type="text" class="form-control form-control-sm" placeholder="Target ${tahun}" required>
            <input type="hidden" value="${tahun}">
          </div>
        `;
      }
      
      const newIndikator = document.createElement('div');
      newIndikator.className = 'indikator-sasaran-item border rounded p-3 bg-white mb-3';
      newIndikator.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
          <label class="fw-medium">Indikator Sasaran</label>
          <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Indikator Sasaran</label>
          <textarea class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Satuan</label>
            <select class="form-control" required>
              <option value="">Pilih Satuan</option>
              <option value="Persen">Persen</option>
              <option value="Nilai">Nilai</option>
              <option value="Predikat">Predikat</option>
              <option value="Unit">Unit</option>
            </select>
          </div>
        </div>

        <div class="target-section">
          <h5 class="fw-medium mb-3">Target Tahunan</h5>
          <div class="target-container">
            <div class="target-years-container">
              <div class="row g-2 mb-2">
                ${targetYearsHTML}
              </div>
            </div>
          </div>
        </div>
      `;
      
      indikatorContainer.appendChild(newIndikator);
      updateLabels();
      updateFormNames();
    }

    // Event delegation untuk semua tombol
    document.addEventListener('click', function(e) {
      // Tombol tambah indikator sasaran
      if (e.target.classList.contains('add-indikator-sasaran') || e.target.closest('.add-indikator-sasaran')) {
        const sasaranItem = e.target.closest('.sasaran-renstra-item');
        addIndikatorSasaran(sasaranItem);
      }
      
      // Tombol hapus sasaran renstra
      if (e.target.classList.contains('remove-sasaran-renstra') || e.target.closest('.remove-sasaran-renstra')) {
        if (confirm('Hapus sasaran renstra ini dan semua indikator sasarannya?')) {
          e.target.closest('.sasaran-renstra-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
      
      // Tombol hapus indikator sasaran
      if (e.target.classList.contains('remove-indikator-sasaran') || e.target.closest('.remove-indikator-sasaran')) {
        if (confirm('Hapus indikator sasaran ini?')) {
          e.target.closest('.indikator-sasaran-item').remove();
          updateLabels();
          updateFormNames();
        }
      }
    });

    // Initialize pada load
    document.addEventListener('DOMContentLoaded', function() {
      updateLabels();
      updateFormNames();
      
      // Set default tahun akhir
      const tahunAwal = document.getElementById('tahun_awal').value;
      if (tahunAwal) {
        document.getElementById('tahun_akhir').value = parseInt(tahunAwal) + 4;
      }
    });

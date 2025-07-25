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

$(document).ready(function() {
  // Initialize Select2 for existing elements
  initializeSelect2();
  
  // Initialize satuan selects
  populateAllSatuanSelects();

  // Function to initialize Select2 on elements
  function initializeSelect2() {
    // Initialize Select2 for RPJMD Sasaran dropdown
    $('#rpjmd_sasaran_select').select2({
      theme: 'bootstrap-5',
      placeholder: "Pilih atau ketik untuk mencari sasaran RPJMD...",
      allowClear: true,
      width: '100%'
    });
  }

  // Re-initialize Select2 when new elements are added
  $(document).on('click', '.add-indikator-sasaran-rkt, #add-sasaran-rkt', function() {
    setTimeout(function() {
      initializeSelect2();
    }, 100);
  });

  // Handle selection change
  $('#rpjmd_sasaran_select').on('change', function() {
    var selectedOption = $(this).find('option:selected');
    
    if (selectedOption.val()) {
      
      // Show selected info (optional)
      showSelectedInfo(selectedOption.text());
      
      // Trigger tahun dropdown update
      if (typeof updateAllTahunDropdowns === 'function') {
        updateAllTahunDropdowns();
      }
    }
  });

  // Function to show selected sasaran info
  function showSelectedInfo(sasaran) {
    var infoHtml = '<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">' +
      '<strong>Sasaran RPJMD Terpilih:</strong> ' + sasaran +
      '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
    '</div>';
    
    $('#alert-container').html(infoHtml);
  }
});


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
  const sasaranSelect = document.getElementById('rpjmd_sasaran_select');
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
        <textarea name="sasaran_rkt[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran RKT" required></textarea>
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
            <textarea name="sasaran_rkt[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Satuan</label>
              <select name="sasaran_rkt[0][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
                <option value="">Pilih Satuan</option>
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
  
  // Populate satuan selects untuk sasaran baru
  populateAllSatuanSelects();
  
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
      <textarea name="sasaran_rkt[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
    </div>

    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label">Satuan</label>
        <select name="sasaran_rkt[0][indikator_sasaran][0][satuan]" class="form-select satuan-select" required>
            <option value="">Pilih Satuan</option>
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
document.getElementById('rkpd-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  // Validasi RPJMD Sasaran
  const rpjmdSasaranId = document.querySelector('select[name="rpjmd_sasaran_id"]').value;
  if (!rpjmdSasaranId) {
    alert('Pilih Sasaran RPJMD terlebih dahulu');
    return false;
  }

  // Validasi minimal 1 sasaran RKPD
  const sasaranItems = document.querySelectorAll('.sasaran-rkpd-item');
  if (sasaranItems.length === 0) {
    alert('Minimal harus ada 1 Sasaran RKPD');
    return false;
  }

  // Validasi setiap sasaran RKPD harus diisi
  let isValid = true;
  sasaranItems.forEach((sasaran, index) => {
    const sasaranText = sasaran.querySelector('textarea[name*="sasaran"]');
    if (!sasaranText || !sasaranText.value.trim()) {
      alert(`Sasaran RKPD ${index + 1} harus diisi`);
      isValid = false;
      return;
    }

    // Validasi minimal 1 indikator sasaran per sasaran RKPD
    const indikatorItems = sasaran.querySelectorAll('.indikator-sasaran-rkpd-item');
    if (indikatorItems.length === 0) {
      alert(`Sasaran RKPD ${index + 1} harus memiliki minimal 1 Indikator Sasaran`);
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
  const confirmed = confirm('Apakah Anda yakin ingin menyimpan data RKPD ini?');
  if (confirmed) {
    this.submit();
  }
});
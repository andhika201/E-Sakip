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
        <button type="button" class="add-indikator-sasaran-rkt btn btn-info btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
        </button>
        </div>
        <div class="indikator-sasaran-rkt-container"></div>
    </div>
    `;
    
    sasaranContainer.appendChild(newSasaran);
    updateLabels();
    updateFormNames();
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
        <select class="form-control" required>
            <option value="">Pilih Satuan</option>
            <option value="1">Persen</option>
            <option value="2">Nilai</option>
            <option value="3">Predikat</option>
            <option value="4">Unit</option>
        </select>
        </div>
        <div class="col-md-4">
        <label class="form-label">Tahun Target</label>
        <select class="form-select" required>
            <option value="">Pilih Tahun</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
            <option value="2027">2027</option>
            <option value="2028">2028</option>
            <option value="2029">2029</option>
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
});
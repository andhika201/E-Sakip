// Fungsi helper untuk menghasilkan option satuan
// Untuk mengubah pilihan satuan, cukup edit array satuanOptions di bawah ini
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
    // Initialize satuan options untuk form yang sudah ada
    populateAllSatuanSelects();
    
    // Initialize Select2 for existing elements
    initializeSelect2();

    // Function to initialize Select2 on elements
    function initializeSelect2() {
        // Initialize Select2 for Renstra Sasaran dropdown (IKU menggunakan renstra_sasaran_id)
        $('#renstra-sasaran-select').select2({
            theme: 'bootstrap-5',
            placeholder: "Pilih atau ketik untuk mencari sasaran Renstra...",
            allowClear: true,
            width: '100%'
        });
    }

    // Re-initialize Select2 when new elements are added
    $(document).on('click', '.add-indikator-kinerja, #add-sasaran-iku', function() {
        setTimeout(function() {
            initializeSelect2();
        }, 100);
    });

    // Handle selection change
    $('#renstra-sasaran-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        
        if (selectedOption.val()) {
            // Show selected info (optional)
            showSelectedInfo(selectedOption.text());
        }
    });

    // Function to show selected sasaran info
    function showSelectedInfo(sasaran) {
        var infoHtml = '<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">' +
            '<strong>Sasaran Renstra Terpilih:</strong> ' + sasaran +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>';
        
        $('#alert-container').html(infoHtml);
    }
});

// Fungsi untuk mengupdate penomoran label secara real-time
function updateLabels() {
    document.querySelectorAll('.sasaran-iku-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranTitle = sasaranItem.querySelector('.sasaran-title');
        if (sasaranTitle) {
            sasaranTitle.textContent = `Sasaran IKU ${sasaranIndex + 1}`;
        }
        
        sasaranItem.querySelectorAll('.indikator-kinerja-item').forEach((indikatorItem, indikatorIndex) => {
            const indikatorTitle = indikatorItem.querySelector('.indikator-title');
            if (indikatorTitle) {
                indikatorTitle.textContent = `Indikator Kinerja ${sasaranIndex + 1}.${indikatorIndex + 1}`;
            }
        });
    });
    
    // Update visibility tombol delete setelah update labels
    updateDeleteButtonVisibility();
}

// Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
function updateFormNames() {
    document.querySelectorAll('.sasaran-iku-item').forEach((sasaranItem, sasaranIndex) => {
        // Update sasaran IKU names - SAMA SEPERTI RENSTRA
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
        const sasaranIdInput = sasaranItem.querySelector('input[name*="[id]"]');
        
        if (sasaranTextarea) {
            sasaranTextarea.name = `sasaran_iku[${sasaranIndex}][sasaran]`;
        }
        if (sasaranIdInput) {
            sasaranIdInput.name = `sasaran_iku[${sasaranIndex}][id]`;
        }

        // Update indikator kinerja names - SAMA SEPERTI RENSTRA
        sasaranItem.querySelectorAll('.indikator-kinerja-item').forEach((indikatorItem, indikatorIndex) => {
            const indikatorTextarea = indikatorItem.querySelector('textarea[name*="indikator_kinerja"]');
            const definisiTextarea = indikatorItem.querySelector('textarea[name*="definisi_formulasi"]');
            const satuanSelect = indikatorItem.querySelector('select[name*="satuan"]');
            const programTextarea = indikatorItem.querySelector('textarea[name*="program_pendukung"]');
            const idInput = indikatorItem.querySelector('input[name*="[id]"]');
            
            if (indikatorTextarea) {
                indikatorTextarea.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][indikator_kinerja]`;
            }
            if (definisiTextarea) {
                definisiTextarea.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][definisi_formulasi]`;
            }
            if (satuanSelect) {
                satuanSelect.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][satuan]`;
            }
            if (programTextarea) {
                programTextarea.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][program_pendukung]`;
            }
            if (idInput) {
                idInput.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][id]`;
            }

            // Update nama field target tahunan - SAMA SEPERTI RENSTRA
            indikatorItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
                const targetInput = targetItem.querySelector('input[type="text"]');
                const tahunInput = targetItem.querySelector('.tahun-target');
                const targetIdInput = targetItem.querySelector('input[name*="[id]"]');
                
                if (targetInput) {
                    targetInput.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][target]`;
                }
                if (tahunInput) {
                    tahunInput.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][tahun]`;
                }
                if (targetIdInput) {
                    targetIdInput.name = `sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][id]`;
                }
            });
        });
    });
}

// Fungsi untuk mengontrol visibility tombol delete berdasarkan jumlah item
function updateDeleteButtonVisibility() {
    // Kontrol tombol delete sasaran IKU
    const sasaranItems = document.querySelectorAll('.sasaran-iku-item');
    sasaranItems.forEach(item => {
        const deleteBtn = item.querySelector('.remove-sasaran-iku');
        if (deleteBtn) {
            deleteBtn.style.display = sasaranItems.length > 1 ? 'inline-block' : 'none';
        }
    });

    // Kontrol tombol delete indikator kinerja
    document.querySelectorAll('.sasaran-iku-item').forEach(sasaranItem => {
        const indikatorItems = sasaranItem.querySelectorAll('.indikator-kinerja-item');
        indikatorItems.forEach(item => {
            const deleteBtn = item.querySelector('.remove-indikator-kinerja');
            if (deleteBtn) {
                deleteBtn.style.display = indikatorItems.length > 1 ? 'inline-block' : 'none';
            }
        });
    });
}

// Tambah Sasaran IKU Baru
document.addEventListener('DOMContentLoaded', function() {
    const addSasaranBtn = document.getElementById('add-sasaran-iku');
    if (addSasaranBtn) {
        addSasaranBtn.addEventListener('click', () => {
            const sasaranContainer = document.getElementById('sasaran-iku-container');
            const tahunMulaiInput = document.getElementById('tahun_mulai');
            const tahunAwal = parseInt(tahunMulaiInput ? tahunMulaiInput.value : '');
            
            if (!tahunAwal || isNaN(tahunAwal)) {
                alert('Silakan isi tahun mulai terlebih dahulu');
                return;
            }
            
            const sasaranCount = sasaranContainer.children.length;
            const newSasaranHtml = createNewSasaranTemplate(sasaranCount, tahunAwal);
            
            sasaranContainer.insertAdjacentHTML('beforeend', newSasaranHtml);
            
            updateLabels();
            updateFormNames();
            updateTargetYears(); // Update tahun target untuk elemen baru
            updateDeleteButtonVisibility();
            
            // Initialize select untuk elemen baru
            populateAllSatuanSelects();
        });
    }
});

// Handle click events dengan event delegation
document.addEventListener('click', function(e) {
    // Tambah indikator kinerja
    if (e.target.classList.contains('add-indikator-kinerja')) {
        const sasaranItem = e.target.closest('.sasaran-iku-item');
        const indikatorContainer = sasaranItem.querySelector('.indikator-kinerja-container');
        const tahunMulaiInput = document.getElementById('tahun_mulai');
        const tahunAwal = parseInt(tahunMulaiInput ? tahunMulaiInput.value : '');
        
        if (!tahunAwal || isNaN(tahunAwal)) {
            alert('Silakan isi tahun mulai terlebih dahulu');
            return;
        }
        
        const sasaranIndex = Array.from(document.querySelectorAll('.sasaran-iku-item')).indexOf(sasaranItem);
        const indikatorCount = indikatorContainer.children.length;
        const newIndikatorHtml = createNewIndikatorTemplate(sasaranIndex, indikatorCount, tahunAwal);
        
        indikatorContainer.insertAdjacentHTML('beforeend', newIndikatorHtml);
        
        updateLabels();
        updateFormNames();
        updateTargetYears(); // Update tahun target untuk elemen baru
        updateDeleteButtonVisibility();
        
        // Initialize select untuk elemen baru
        populateAllSatuanSelects();
    }
    
    // Hapus sasaran IKU - SAMA SEPERTI RENSTRA
    else if (e.target.classList.contains('remove-sasaran-iku') || e.target.closest('.remove-sasaran-iku')) {
        const sasaranItems = document.querySelectorAll('.sasaran-iku-item');
        if (sasaranItems.length <= 1) {
            alert('Minimal harus ada 1 Sasaran IKU!');
            return;
        }
        
        if (confirm('Hapus sasaran IKU ini dan semua indikator kinerjanya?')) {
            e.target.closest('.sasaran-iku-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
    
    // Hapus indikator kinerja - SAMA SEPERTI RENSTRA
    else if (e.target.classList.contains('remove-indikator-kinerja') || e.target.closest('.remove-indikator-kinerja')) {
        const sasaranItem = e.target.closest('.sasaran-iku-item');
        const indikatorItems = sasaranItem.querySelectorAll('.indikator-kinerja-item');
        
        if (indikatorItems.length <= 1) {
            alert('Minimal harus ada 1 Indikator Kinerja per Sasaran IKU!');
            return;
        }
        
        if (confirm('Yakin ingin menghapus indikator kinerja ini?')) {
            e.target.closest('.indikator-kinerja-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
});

// Fungsi untuk mengupdate tahun target berdasarkan tahun mulai
function updateTargetYears() {
    const tahunMulai = parseInt(document.getElementById('tahun_mulai').value);
    if (tahunMulai && !isNaN(tahunMulai)) {
        // Loop through setiap indikator kinerja secara terpisah
        document.querySelectorAll('.indikator-kinerja-item').forEach(indikatorItem => {
            const targetItems = indikatorItem.querySelectorAll('.target-item');
            
            // Untuk setiap indikator, reset tahun dari tahun mulai
            targetItems.forEach((targetItem, targetIndex) => {
                const tahunInput = targetItem.querySelector('.tahun-target');
                const targetInput = targetItem.querySelector('input[type="text"]');
                const tahun = tahunMulai + targetIndex; // targetIndex dimulai dari 0 untuk setiap indikator
                
                if (tahunInput) {
                    tahunInput.value = tahun;
                }
                if (targetInput) {
                    targetInput.placeholder = `Target ${tahun}`;
                }
            });
        });
    }
}

// Fungsi untuk initialize tahun akhir berdasarkan tahun mulai yang sudah ada (untuk edit mode)
function initializeTahunAkhir() {
    const tahunMulaiField = document.getElementById('tahun_mulai');
    const tahunAkhirField = document.getElementById('tahun_akhir');
    
    if (tahunMulaiField && tahunMulaiField.value && tahunAkhirField && !tahunAkhirField.value) {
        const tahunMulai = parseInt(tahunMulaiField.value);
        if (!isNaN(tahunMulai)) {
            tahunAkhirField.value = tahunMulai + 4; // 5 tahun periode
        }
    }
}

// Event listener untuk auto-fill tahun akhir dan update target years
document.addEventListener('input', function(e) {
    if (e.target.id === 'tahun_mulai') {
        const tahunMulai = parseInt(e.target.value);
        const tahunAkhirField = document.getElementById('tahun_akhir');
        
        if (tahunMulai && !isNaN(tahunMulai) && tahunAkhirField) {
            // Auto-fill tahun akhir (IKU biasanya 5 tahun seperti Renstra)
            tahunAkhirField.value = tahunMulai + 4;
            
            // Update semua target years
            updateTargetYears();
        }
    }
});

// Form change tracking
let formHasChanges = false;
let isSubmitting = false;

// Initialize components saat document ready
document.addEventListener('DOMContentLoaded', function() {
    updateLabels();
    updateFormNames();
    updateDeleteButtonVisibility();
    
    // Initialize tahun akhir for edit mode
    initializeTahunAkhir();
    
    updateTargetYears(); // Initialize target years pada load
});

// Template untuk sasaran IKU baru
function createNewSasaranTemplate(index, tahunAwal) {
    const satuanOptions = generateSatuanOptions();
    
    return `
    <div class="sasaran-iku-item bg-light border rounded p-3 mb-3">
        <input type="hidden" name="sasaran_iku[${index}][id]" value="">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium sasaran-title">Sasaran IKU ${index + 1}</label>
            <button type="button" class="remove-sasaran-iku btn btn-outline-danger btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Sasaran IKU</label>
                <textarea name="sasaran_iku[${index}][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran IKU" required></textarea>
            </div>
        </div>

        <!-- Indikator Kinerja -->
        <div class="indikator-kinerja-section">
            <div class="mb-3">
                <h4 class="h5 fw-medium">Indikator Kinerja</h4>
            </div>

            <div class="indikator-kinerja-container">
                ${createNewIndikatorTemplate(index, 0, tahunAwal)}
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="add-indikator-kinerja btn btn-info btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Indikator Kinerja
                </button>
            </div>
        </div>
    </div>`;
}

// Template untuk indikator kinerja baru
function createNewIndikatorTemplate(sasaranIndex, indikatorIndex, tahunAwal) {
    const satuanOptions = generateSatuanOptions();
    
    const targetsHtml = Array.from({length: 5}, (_, i) => `
        <div class="target-item row g-2 align-items-center mb-2">
            <input type="hidden" name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${i}][id]" value="">
            <div class="col-auto">
                <input type="number" name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${i}][tahun]" value="${tahunAwal + i}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
                <input type="text" name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${i}][target]" value="" class="form-control form-control-sm" placeholder="Target ${tahunAwal + i}" required>
            </div>
        </div>
    `).join('');
    
    return `
    <div class="indikator-kinerja-item border rounded p-3 bg-white mb-3">
        <input type="hidden" name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][id]" value="">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium indikator-title">Indikator Kinerja ${sasaranIndex + 1}.${indikatorIndex + 1}</label>
            <button type="button" class="remove-indikator-kinerja btn btn-outline-danger btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Indikator Kinerja</label>
            <textarea name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][indikator_kinerja]" class="form-control" rows="2" placeholder="Masukkan indikator kinerja" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Definisi & Formulasi</label>
            <textarea name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][definisi_formulasi]" class="form-control" rows="3" placeholder="Masukkan definisi dan formulasi indikator" required></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Satuan</label>
                <select name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][satuan]" class="form-control satuan-select" required>
                    ${satuanOptions}
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Program Pendukung</label>
                <textarea name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][program_pendukung]" class="form-control" rows="2" placeholder="Masukkan program pendukung" required></textarea>
            </div>
        </div>

        <!-- Target Tahunan -->
        <div class="target-section">
            <h5 class="fw-medium mb-3">Target Tahunan</h5>
            <div class="target-container">
                ${targetsHtml}
            </div>
        </div>
    </div>`;
}

// Form validation dan submit handler (simplified like RENSTRA)
document.getElementById('ikuOpdForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Basic form validation
    const renstraSasaranId = document.querySelector('select[name="renstra_sasaran_id"]');
    const tahunMulai = document.querySelector('input[name="tahun_mulai"]');
    const tahunAkhir = document.querySelector('input[name="tahun_akhir"]');
    
    if (!renstraSasaranId || !renstraSasaranId.value) {
        alert('Silakan pilih Sasaran Renstra terlebih dahulu');
        renstraSasaranId.focus();
        return;
    }
    
    if (!tahunMulai || !tahunMulai.value || !tahunAkhir || !tahunAkhir.value) {
        alert('Silakan lengkapi tahun mulai dan tahun akhir');
        if (tahunMulai) tahunMulai.focus();
        return;
    }
    
    // Final form names update before submit
    updateFormNames();
    
    // Submit form
    isSubmitting = true;
    this.submit();
});

// ============================
// EDIT MODE FUNCTIONS
// ============================

// Function to populate form with existing IKU data
function populateFormWithData(data) {
    console.log('Populating form with data:', data);
    
    // Populate basic information using jQuery
    if (data.renstra_sasaran_id) {
        $('#renstra-sasaran-select').val(data.renstra_sasaran_id).trigger('change');
    }
    
    if (data.tahun_mulai) {
        $('#tahun_mulai').val(data.tahun_mulai).trigger('change');
    }
    
    if (data.tahun_akhir) {
        $('#tahun_akhir').val(data.tahun_akhir);
    }
    
    // Clear existing sasaran items first (keep only one template)
    const container = $('#sasaran-iku-container');
    const existingItems = container.find('.sasaran-iku-item');
    
    // Remove all except first
    existingItems.slice(1).remove();
    
    // Populate sasaran IKU data
    if (data.sasaran_iku && data.sasaran_iku.length > 0) {
        data.sasaran_iku.forEach((sasaran, sasaranIndex) => {
            if (sasaranIndex === 0) {
                // Use existing first sasaran item
                populateSasaranIku(existingItems.first()[0], sasaran, sasaranIndex);
            } else {
                // Add new sasaran item for additional data
                const newSasaranItem = createSasaranIkuTemplate(sasaranIndex);
                container.append(newSasaranItem);
                populateSasaranIku(newSasaranItem, sasaran, sasaranIndex);
            }
        });
    }
    
    // Re-populate satuan selects and initialize new Select2 instances
    populateAllSatuanSelects();
    initializeNewSelect2Elements();
    
    // Update form state
    updateLabels();
    updateFormNames();
    updateDeleteButtonVisibility();
}

// Function to populate individual sasaran IKU
function populateSasaranIku(sasaranElement, sasaranData, sasaranIndex) {
    // Set sasaran text and ID
    const sasaranTextarea = sasaranElement.querySelector(`textarea[name="sasaran_iku[${sasaranIndex}][sasaran]"]`);
    const sasaranIdInput = sasaranElement.querySelector(`input[name="sasaran_iku[${sasaranIndex}][id]"]`);
    
    if (sasaranTextarea && sasaranData.sasaran) {
        sasaranTextarea.value = sasaranData.sasaran;
    }
    if (sasaranIdInput && sasaranData.id) {
        sasaranIdInput.value = sasaranData.id;
    }
    
    // Handle indikator kinerja
    const indikatorContainer = sasaranElement.querySelector('.indikator-kinerja-container');
    const existingIndikators = indikatorContainer.querySelectorAll('.indikator-kinerja-item');
    
    // Clear existing indikators except first one
    for (let i = existingIndikators.length - 1; i > 0; i--) {
        existingIndikators[i].remove();
    }
    
    if (sasaranData.indikator_kinerja && sasaranData.indikator_kinerja.length > 0) {
        sasaranData.indikator_kinerja.forEach((indikator, indikatorIndex) => {
            if (indikatorIndex === 0) {
                // Use existing first indikator
                populateIndikatorKinerja(existingIndikators[0], indikator, sasaranIndex, indikatorIndex);
            } else {
                // Add new indikator for additional data
                const newIndikatorItem = createIndikatorKinerjaTemplate(sasaranIndex, indikatorIndex);
                indikatorContainer.appendChild(newIndikatorItem);
                populateIndikatorKinerja(newIndikatorItem, indikator, sasaranIndex, indikatorIndex);
            }
        });
    }
}

// Function to populate individual indikator kinerja
function populateIndikatorKinerja(indikatorElement, indikatorData, sasaranIndex, indikatorIndex) {
    // Set basic fields with ID
    const fields = {
        'indikator_kinerja': indikatorData.indikator_kinerja,
        'definisi_formulasi': indikatorData.definisi_formulasi,
        'satuan': indikatorData.satuan,
        'program_pendukung': indikatorData.program_pendukung
    };
    
    // Set ID field
    const idInput = indikatorElement.querySelector(`input[name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][id]"]`);
    if (idInput && indikatorData.id) {
        idInput.value = indikatorData.id;
    }
    
    Object.keys(fields).forEach(fieldName => {
        const element = indikatorElement.querySelector(`[name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][${fieldName}]"]`);
        if (element && fields[fieldName]) {
            element.value = fields[fieldName];
            
            // Special handling for satuan select
            if (fieldName === 'satuan') {
                element.setAttribute('data-selected', fields[fieldName]);
            }
        }
    });
    
    // Populate target tahunan
    if (indikatorData.target_tahunan && indikatorData.target_tahunan.length > 0) {
        indikatorData.target_tahunan.forEach((target, targetIndex) => {
            const tahunInput = indikatorElement.querySelector(`input[name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][tahun]"]`);
            const targetInput = indikatorElement.querySelector(`input[name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][target]"]`);
            const targetIdInput = indikatorElement.querySelector(`input[name="sasaran_iku[${sasaranIndex}][indikator_kinerja][${indikatorIndex}][target_tahunan][${targetIndex}][id]"]`);
            
            if (tahunInput && target.tahun) {
                tahunInput.value = target.tahun;
            }
            if (targetInput && target.target) {
                targetInput.value = target.target;
            }
            if (targetIdInput && target.id) {
                targetIdInput.value = target.id;
            }
        });
    }
}

// Helper functions to create templates for edit mode
function createSasaranIkuTemplate(index) {
    const tahunMulaiInput = document.getElementById('tahun_mulai');
    const tahunAwal = parseInt(tahunMulaiInput ? tahunMulaiInput.value : '');
    
    const tempContainer = document.createElement('div');
    tempContainer.innerHTML = createNewSasaranTemplate(index, tahunAwal);
    return tempContainer.firstElementChild;
}

function createIndikatorKinerjaTemplate(sasaranIndex, indikatorIndex) {
    const tahunMulaiInput = document.getElementById('tahun_mulai');
    const tahunAwal = parseInt(tahunMulaiInput ? tahunMulaiInput.value : '');
    
    const tempContainer = document.createElement('div');
    tempContainer.innerHTML = createNewIndikatorTemplate(sasaranIndex, indikatorIndex, tahunAwal);
    return tempContainer.firstElementChild;
}

// Function to initialize new Select2 elements
function initializeNewSelect2Elements() {
    // Reinitialize Select2 on any new elements
    $('.satuan-select:not(.select2-hidden-accessible)').select2({
        theme: 'bootstrap-5',
        placeholder: "Pilih Satuan...",
        allowClear: true,
        width: '100%'
    });
}

// Initialize edit mode if data is available
$(document).ready(function() {
    // Check if we're in edit mode and have data
    if (typeof window.ikuData !== 'undefined' && window.ikuData && window.isEditMode) {
        // Populate form with existing data
        setTimeout(function() {
            populateFormWithData(window.ikuData);
        }, 100); // Small delay to ensure all elements are initialized
    }
});
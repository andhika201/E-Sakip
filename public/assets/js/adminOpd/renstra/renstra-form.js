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

// Fungsi untuk mengupdate penomoran label secara real-time
function updateLabels() {
    document.querySelectorAll('.sasaran-renstra-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranTitle = sasaranItem.querySelector('.sasaran-title');
        if (sasaranTitle) {
            sasaranTitle.textContent = `Sasaran Renstra ${sasaranIndex + 1}`;
        }
        
        sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorItem, indikatorIndex) => {
            const indikatorTitle = indikatorItem.querySelector('.indikator-title');
            if (indikatorTitle) {
                indikatorTitle.textContent = `Indikator Sasaran ${sasaranIndex + 1}.${indikatorIndex + 1}`;
            }
        });
    });
    
    // Update visibility tombol delete setelah update labels
    updateDeleteButtonVisibility();
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

            // Update nama field target tahunan
            indikatorItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
                const targetInput = targetItem.querySelector('input[type="text"]');
                const tahunInput = targetItem.querySelector('.tahun-target');
                
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
document.addEventListener('DOMContentLoaded', function() {
    const addSasaranBtn = document.getElementById('add-sasaran-renstra');
    if (addSasaranBtn) {
        addSasaranBtn.addEventListener('click', () => {
            const sasaranContainer = document.getElementById('sasaran-renstra-container');
            const tahunMulaiInput = document.getElementById('tahun_mulai');
            const tahunAwal = parseInt(tahunMulaiInput ? tahunMulaiInput.value : '');
            
            if (!tahunAwal || isNaN(tahunAwal)) {
                alert('Silakan isi tahun mulai terlebih dahulu');
                return;
            }
            
            const newSasaran = document.createElement('div');
            newSasaran.className = 'sasaran-renstra-item bg-light border rounded p-3 mb-3';
            newSasaran.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium sasaran-title">Sasaran Renstra</label>
                <button type="button" class="remove-sasaran-renstra btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Sasaran Renstra</label>
                    <textarea name="sasaran_renstra[0][sasaran]" class="form-control" rows="2" placeholder="Masukkan sasaran renstra" required></textarea>
                </div>
            </div>

            <div class="indikator-sasaran-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="h5 fw-medium">Indikator Sasaran</h4>
                </div>
                <div class="indikator-sasaran-container">
                    <div class="indikator-sasaran-item border rounded p-3 bg-white mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium indikator-title">Indikator Sasaran</label>
                            <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Indikator Sasaran</label>
                            <textarea name="sasaran_renstra[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Satuan</label>
                                <select name="sasaran_renstra[0][indikator_sasaran][0][satuan]" class="form-control satuan-select" required>
                                    ${generateSatuanOptions()}
                                </select>
                            </div>
                        </div>

                        <div class="target-section">
                            <h5 class="fw-medium mb-3">Target Tahunan</h5>
                            <div class="target-container">
                                <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][tahun]" value="${tahunAwal}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal}" required>
                                    </div>
                                </div>
                                <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][tahun]" value="${tahunAwal + 1}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 1}" required>
                                    </div>
                                </div>
                                <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][tahun]" value="${tahunAwal + 2}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 2}" required>
                                    </div>
                                </div>
                                <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][tahun]" value="${tahunAwal + 3}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 3}" required>
                                    </div>
                                </div>
                                <div class="target-item row g-2 align-items-center mb-2">
                                    <div class="col-auto">
                                        <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][tahun]" value="${tahunAwal + 4}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 4}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-indikator-sasaran btn btn-info btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Indikator Sasaran
                    </button>
                </div>
            </div>
            `;
            
            sasaranContainer.appendChild(newSasaran);
            updateLabels();
            updateFormNames();
            updateTargetYears(); // Update tahun target untuk elemen baru
            
            // Trigger Select2 initialization for new elements
            if (typeof $ !== 'undefined' && $.fn.select2) {
                setTimeout(function() {
                    $('.satuan-select').each(function() {
                        if (!$(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2({
                                placeholder: "Pilih atau ketik untuk mencari satuan...",
                                allowClear: true,
                                width: '100%'
                            });
                        }
                    });
                }, 100);
            }
        });
    }
});

// Fungsi untuk menambahkan indikator sasaran
function addIndikatorSasaranToSasaran(sasaranElement) {
    const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-container');
    const tahunMulaiInput = document.getElementById('tahun_mulai');
    const tahunAwal = parseInt(tahunMulaiInput ? tahunMulaiInput.value : '');
    
    if (!tahunAwal || isNaN(tahunAwal)) {
        alert('Silakan isi tahun mulai terlebih dahulu');
        return;
    }
    
    const newIndikator = document.createElement('div');
    newIndikator.className = 'indikator-sasaran-item border rounded p-3 bg-white mb-3';
    newIndikator.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="fw-medium indikator-title">Indikator Sasaran</label>
        <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Indikator Sasaran</label>
        <textarea name="sasaran_renstra[0][indikator_sasaran][0][indikator_sasaran]" class="form-control" rows="2" placeholder="Masukkan indikator sasaran" required></textarea>
    </div>        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Satuan</label>
                <select name="sasaran_renstra[0][indikator_sasaran][0][satuan]" class="form-control satuan-select" required>
                    ${generateSatuanOptions()}
                </select>
            </div>
        </div>

    <div class="target-section">
        <h5 class="fw-medium mb-3">Target Tahunan</h5>
        <div class="target-container">
            <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][tahun]" value="${tahunAwal}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][0][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal}" required>
                </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][tahun]" value="${tahunAwal + 1}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][1][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 1}" required>
                </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][tahun]" value="${tahunAwal + 2}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][2][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 2}" required>
                </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][tahun]" value="${tahunAwal + 3}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][3][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 3}" required>
                </div>
            </div>
            <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][tahun]" value="${tahunAwal + 4}" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="sasaran_renstra[0][indikator_sasaran][0][target_tahunan][4][target]" class="form-control form-control-sm" placeholder="Target ${tahunAwal + 4}" required>
                </div>
            </div>
        </div>
    </div>
    `;
    
    indikatorContainer.appendChild(newIndikator);
    
    updateLabels();
    updateFormNames();
    updateTargetYears(); // Update tahun target untuk elemen baru
    
    // Trigger Select2 initialization for new elements
    if (typeof $ !== 'undefined' && $.fn.select2) {
        setTimeout(function() {
            $('.satuan-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: "Pilih atau ketik untuk mencari satuan...",
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        }, 100);
    }
    
    // Trigger Select2 initialization for new elements
    if (typeof $ !== 'undefined' && $.fn.select2) {
        setTimeout(function() {
            $('.satuan-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: "Pilih atau ketik untuk mencari satuan...",
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        }, 100);
    }
}

// Event delegation untuk semua tombol
document.addEventListener('click', function(e) {
    // Tombol tambah indikator sasaran
    if (e.target.classList.contains('add-indikator-sasaran') || e.target.closest('.add-indikator-sasaran')) {
        const sasaranItem = e.target.closest('.sasaran-renstra-item');
        addIndikatorSasaranToSasaran(sasaranItem);
    }
    
    // Tombol hapus sasaran renstra
    if (e.target.classList.contains('remove-sasaran-renstra') || e.target.closest('.remove-sasaran-renstra')) {
        const sasaranItems = document.querySelectorAll('.sasaran-renstra-item');
        if (sasaranItems.length <= 1) {
            alert('Minimal harus ada 1 Sasaran Renstra!');
            return;
        }
        
        if (confirm('Hapus sasaran renstra ini dan semua indikator sasarannya?')) {
            e.target.closest('.sasaran-renstra-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
    
    // Tombol hapus indikator sasaran
    if (e.target.classList.contains('remove-indikator-sasaran') || e.target.closest('.remove-indikator-sasaran')) {
        const sasaranItem = e.target.closest('.sasaran-renstra-item');
        const indikatorItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
        
        if (indikatorItems.length <= 1) {
            alert('Minimal harus ada 1 Indikator Sasaran per Sasaran Renstra!');
            return;
        }
        
        if (confirm('Hapus indikator sasaran ini?')) {
            e.target.closest('.indikator-sasaran-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
});

// Fungsi untuk mengupdate tahun target berdasarkan tahun mulai
function updateTargetYears() {
    const tahunMulai = parseInt(document.getElementById('tahun_mulai').value);
    if (tahunMulai && !isNaN(tahunMulai)) {
        // Loop through setiap indikator sasaran secara terpisah
        document.querySelectorAll('.indikator-sasaran-item').forEach(indikatorItem => {
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

// Event listener untuk auto-fill tahun akhir dan update target years
document.addEventListener('input', function(e) {
    if (e.target.id === 'tahun_mulai') {
        const tahunMulai = parseInt(e.target.value);
        const tahunAkhirField = document.getElementById('tahun_akhir');
        
        if (tahunMulai && !isNaN(tahunMulai) && tahunAkhirField) {
            // Auto-fill tahun akhir (Renstra biasanya 5 tahun)
            tahunAkhirField.value = tahunMulai + 4;
            
            // Update semua target years
            updateTargetYears();
        }
    }
});

// Form change tracking
let formHasChanges = false;
let isSubmitting = false;

// Track form changes
function trackFormChanges() {
    const form = document.getElementById('renstra-form');
    
    // Track input changes (input, textarea, select)
    form.addEventListener('input', function(e) {
        if (!isSubmitting) {
            formHasChanges = true;
            console.log('Form changed (input):', e.target.name);
        }
    });
    
    // Track textarea changes specifically
    form.addEventListener('change', function(e) {
        if (!isSubmitting) {
            formHasChanges = true;
            console.log('Form changed (change):', e.target.name);
        }
    });
    
    // Track dynamic additions/removals
    form.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-indikator-sasaran') || 
            e.target.classList.contains('remove-indikator-sasaran') ||
            e.target.classList.contains('remove-sasaran-renstra') ||
            e.target.closest('.add-indikator-sasaran') ||
            e.target.closest('.remove-indikator-sasaran') ||
            e.target.closest('.remove-sasaran-renstra')) {
            
            if (!isSubmitting) {
                formHasChanges = true;
                console.log('Form changed (dynamic)');
            }
        }
    });
    
    // Track form submission
    form.addEventListener('submit', function(e) {
        isSubmitting = true;
        console.log('Form submission started, disabling change tracking');
    });
}

// Warn user about unsaved changes before leaving page
function setupUnloadWarning() {
    // Warning saat user meninggalkan halaman (reload, close tab, navigate away)
    window.addEventListener('beforeunload', function(e) {
        if (hasFormActuallyChanged() && !isSubmitting) {
            console.log('User attempting to leave page with unsaved changes');
            const message = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    });
    
    // Warning saat user menekan tombol "Kembali"
    const backButton = document.querySelector('a[href*="renstra"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            if (hasFormActuallyChanged() && !isSubmitting) {
                console.log('User clicked back button with unsaved changes');
                if (!confirm('Anda memiliki perubahan yang belum disimpan. Yakin ingin kembali?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
}

// Setup confirmation untuk tombol save
function setupSaveConfirmation() {
    const saveButton = document.querySelector('button[type="submit"]');
    if (saveButton) {
        saveButton.addEventListener('click', function(e) {
            console.log('Save button clicked');
            if (hasFormActuallyChanged()) {
                const confirmed = confirm('Simpan perubahan Renstra ini?');
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
}

// Fungsi untuk mendeteksi apakah form benar-benar berubah dari state awal
function hasFormActuallyChanged() {
    // Bisa dikembangkan untuk membandingkan dengan data awal
    // Untuk sekarang, gunakan flag sederhana
    return formHasChanges;
}

// Fungsi untuk reset form change tracking
function resetFormChangeTracking() {
    formHasChanges = false;
    isSubmitting = false;
    console.log('Form change tracking reset');
}

// Fungsi untuk setup keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+S untuk save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            console.log('Ctrl+S pressed - submitting form');
            const form = document.getElementById('renstra-form');
            if (form) {
                form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        }
        
        // Escape untuk cancel/back
        if (e.key === 'Escape') {
            console.log('Escape pressed');
            if (hasFormActuallyChanged()) {
                if (confirm('Batalkan perubahan dan kembali ke daftar Renstra?')) {
                    window.location.href = '/adminopd/renstra';
                }
            } else {
                window.location.href = '/adminopd/renstra';
            }
        }
    });
}

// Initialize pada load
document.addEventListener('DOMContentLoaded', function() {
    updateLabels();
    updateFormNames();
    updateTargetYears(); // Initialize target years pada load
    
    // Setup form change tracking and warnings
    trackFormChanges();
    setupUnloadWarning();
    setupSaveConfirmation();
    setupKeyboardShortcuts();
    
    console.log('Renstra form protection initialized with keyboard shortcuts');
    
    // Show initial instruction to user
    setTimeout(() => {
        console.log('💡 Tips: Gunakan Ctrl+S untuk save cepat, ESC untuk cancel');
    }, 1000);
});

// Fungsi untuk mengontrol visibility tombol delete berdasarkan jumlah item
function updateDeleteButtonVisibility() {
    // Kontrol tombol delete sasaran renstra
    const sasaranItems = document.querySelectorAll('.sasaran-renstra-item');
    sasaranItems.forEach(item => {
        const deleteBtn = item.querySelector('.remove-sasaran-renstra');
        if (deleteBtn) {
            deleteBtn.style.display = sasaranItems.length > 1 ? 'inline-block' : 'none';
        }
    });

    // Kontrol tombol delete indikator sasaran
    document.querySelectorAll('.sasaran-renstra-item').forEach(sasaranItem => {
        const indikatorItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
        indikatorItems.forEach(item => {
            const deleteBtn = item.querySelector('.remove-indikator-sasaran');
            if (deleteBtn) {
                deleteBtn.style.display = indikatorItems.length > 1 ? 'inline-block' : 'none';
            }
        });
    });
}

// Fungsi validasi form untuk memastikan minimal requirement
function validateMinimalRequirements() {
    const errors = [];
    
    // Minimal harus ada 1 sasaran renstra
    const sasaranItems = document.querySelectorAll('.sasaran-renstra-item');
    if (sasaranItems.length === 0) {
        errors.push('Minimal harus ada 1 Sasaran Renstra');
    }

    // Setiap sasaran harus memiliki minimal 1 indikator sasaran
    sasaranItems.forEach((sasaranItem, sasaranIndex) => {
        const indikatorItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
        
        if (indikatorItems.length === 0) {
            errors.push(`Sasaran Renstra ${sasaranIndex + 1} harus memiliki minimal 1 Indikator Sasaran`);
        }
    });

    return errors;
}

// Form submission handler
document.getElementById('renstra-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('Renstra form submission started...');
    
    // Update form names before validation
    updateFormNames();
    
    // Validasi minimal requirements
    const minimalErrors = validateMinimalRequirements();
    if (minimalErrors.length > 0) {
        alert('Validasi gagal:\n' + minimalErrors.join('\n'));
        return false;
    }
    
    // Basic validation
    const rpjmdSasaranId = document.querySelector('select[name="rpjmd_sasaran_id"]');
    const tahunMulai = document.querySelector('input[name="tahun_mulai"]');
    const tahunAkhir = document.querySelector('input[name="tahun_akhir"]');
    
    console.log('Basic validation fields:', {
        rpjmdSasaranId: rpjmdSasaranId ? rpjmdSasaranId.value : 'NOT FOUND',
        tahunMulai: tahunMulai ? tahunMulai.value : 'NOT FOUND',
        tahunAkhir: tahunAkhir ? tahunAkhir.value : 'NOT FOUND'
    });
    
    if (!rpjmdSasaranId || !rpjmdSasaranId.value) {
        alert('Pilih Sasaran RPJMD terkait');
        rpjmdSasaranId.focus();
        return false;
    }
    
    if (!tahunMulai || !tahunMulai.value || !tahunAkhir || !tahunAkhir.value) {
        alert('Tahun mulai dan tahun akhir harus diisi');
        if (tahunMulai) tahunMulai.focus();
        return false;
    }
    
    // Check if at least one sasaran exists with content
    const sasaranItems = document.querySelectorAll('.sasaran-renstra-item');
    let hasValidSasaran = false;
    
    sasaranItems.forEach(item => {
        const sasaranText = item.querySelector('textarea[name*="[sasaran]"]');
        if (sasaranText && sasaranText.value.trim()) {
            hasValidSasaran = true;
        }
    });
    
    if (!hasValidSasaran) {
        alert('Minimal harus ada 1 Sasaran Renstra yang diisi');
        return false;
    }
    
    console.log('Form validation passed, submitting...');
    
    // Set submission flag
    isSubmitting = true;
    
    // Submit form normally (or via AJAX)
    this.submit();
});

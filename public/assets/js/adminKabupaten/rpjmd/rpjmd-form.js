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
    const satuanSelects = document.querySelectorAll('.satuan-select');
    
    satuanSelects.forEach(select => {
        const currentValue = select.value;
        const selectedValue = select.getAttribute('data-selected') || currentValue;
        select.innerHTML = generateSatuanOptions();
        if (selectedValue) {
            select.value = selectedValue;
        }
    });
}


// Fungsi untuk mengupdate penomoran label secara real-time
function updateLabels() {
    document.querySelectorAll('.tujuan-item').forEach((tujuanItem, tujuanIndex) => {
    const tujuanNumber = tujuanIndex + 1;
    
    // Update label tujuan
    const tujuanLabel = tujuanItem.querySelector('label');
    if (tujuanLabel) {
        tujuanLabel.textContent = `Tujuan ${tujuanNumber}`;
    }

    // Update indikator tujuan labels
    tujuanItem.querySelectorAll('.indikator-tujuan-item').forEach((indikatorTujuanItem, indikatorTujuanIndex) => {
        const indikatorNumber = indikatorTujuanIndex + 1;
        const indikatorLabel = indikatorTujuanItem.querySelector('label');
        if (indikatorLabel) {
        indikatorLabel.textContent = `Indikator Tujuan ${tujuanNumber}.${indikatorNumber}`;
        }
    });

    // Update sasaran labels
    tujuanItem.querySelectorAll('.sasaran-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranNumber = sasaranIndex + 1;
        const sasaranLabel = sasaranItem.querySelector('label');
        if (sasaranLabel) {
        sasaranLabel.textContent = `Sasaran ${tujuanNumber}.${sasaranNumber}`;
        }

        // Update indikator sasaran labels
        sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorSasaranItem, indikatorSasaranIndex) => {
        const indikatorSasaranNumber = indikatorSasaranIndex + 1;
        const indikatorSasaranLabel = indikatorSasaranItem.querySelector('label');
        if (indikatorSasaranLabel) {
            indikatorSasaranLabel.textContent = `Indikator Sasaran ${tujuanNumber}.${sasaranNumber}.${indikatorSasaranNumber}`;
        }
        });
    });
    });
    
    // Update visibility tombol delete setelah update labels
    updateDeleteButtonVisibility();
}

// Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
function updateFormNames() {
    document.querySelectorAll('.tujuan-item').forEach((tujuanItem, tujuanIndex) => {
        // Update tujuan names
        const tujuanTextarea = tujuanItem.querySelector('textarea[name*="tujuan_rpjmd"]');
        const tujuanIdInput = tujuanItem.querySelector('input[type="hidden"][name*="tujuan"][name*="[id]"]');
        
        if (tujuanTextarea) {
            tujuanTextarea.name = `tujuan[${tujuanIndex}][tujuan_rpjmd]`;
        }
        if (tujuanIdInput) {
            tujuanIdInput.name = `tujuan[${tujuanIndex}][id]`;
        }

        // Update indikator tujuan names
        tujuanItem.querySelectorAll('.indikator-tujuan-item').forEach((indikatorTujuanItem, indikatorTujuanIndex) => {
            const indikatorInput = indikatorTujuanItem.querySelector('input[name*="indikator_tujuan"]:not([type="hidden"])');
            const indikatorIdInput = indikatorTujuanItem.querySelector('input[type="hidden"][name*="indikator_tujuan"][name*="[id]"]');
            
            if (indikatorInput) {
                indikatorInput.name = `tujuan[${tujuanIndex}][indikator_tujuan][${indikatorTujuanIndex}][indikator_tujuan]`;
            }
            if (indikatorIdInput) {
                indikatorIdInput.name = `tujuan[${tujuanIndex}][indikator_tujuan][${indikatorTujuanIndex}][id]`;
            }
        });

        // Update sasaran names
        tujuanItem.querySelectorAll('.sasaran-item').forEach((sasaranItem, sasaranIndex) => {
            const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran_rpjmd"]');
            const sasaranIdInput = sasaranItem.querySelector('input[type="hidden"][name*="sasaran"][name*="[id]"]:not([name*="indikator"])');
            
            if (sasaranTextarea) {
                sasaranTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][sasaran_rpjmd]`;
            }
            if (sasaranIdInput) {
                sasaranIdInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][id]`;
            }

            // Update indikator sasaran names
            sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorSasaranItem, indikatorSasaranIndex) => {
                const indikatorInput = indikatorSasaranItem.querySelector('input[name*="indikator_sasaran"]:not([type="hidden"])');
                const satuanSelect = indikatorSasaranItem.querySelector('select[name*="satuan"]');
                const definisiOpTextarea = indikatorSasaranItem.querySelector('textarea[name*="definisi_op"]');
                const indikatorIdInput = indikatorSasaranItem.querySelector('input[type="hidden"][name*="indikator_sasaran"][name*="[id]"]:not([name*="target"])');
                
                if (indikatorInput) {
                    indikatorInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][indikator_sasaran]`;
                }
                if (satuanSelect) {
                    satuanSelect.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][satuan]`;
                }
                if (definisiOpTextarea) {
                    definisiOpTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][definisi_op]`;
                }
                if (indikatorIdInput) {
                    indikatorIdInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][id]`;
                }

                // Fixed approach: More specific selectors untuk target fields
                indikatorSasaranItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
                    // Lebih spesifik untuk menghindari konflik selector
                    const tahunInput = targetItem.querySelector('input[type="number"].tahun-target');
                    const targetInput = targetItem.querySelector('input[type="text"][name*="target_tahunan"]:not([type="hidden"]):not([type="number"])');
                    const targetIdInput = targetItem.querySelector('input[type="hidden"][name*="target_tahunan"][name*="[id]"]');
                    
                    console.log(`Updating target ${targetIndex} in indikator ${indikatorSasaranIndex}:`, {
                        tahunInput: tahunInput ? tahunInput.name : 'not found',
                        targetInput: targetInput ? targetInput.name : 'not found',
                        targetIdInput: targetIdInput ? targetIdInput.name : 'not found'
                    });
                    
                    if (tahunInput) {
                        tahunInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][tahun]`;
                    }
                    if (targetInput) {
                        targetInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][target_tahunan]`;
                    }
                    if (targetIdInput) {
                        targetIdInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][id]`;
                    }
                });
            });
        });
    });
}

// Tambah Tujuan Baru
document.getElementById('add-tujuan').addEventListener('click', () => {
    const tujuanContainer = document.getElementById('tujuan-container');
    
    const newTujuan = document.createElement('div');
    newTujuan.className = 'tujuan-item bg-light border rounded p-3 mb-3';
    newTujuan.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="h6 fw-medium">Tujuan</label>
        <button type="button" class="remove-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
    </div>
    <div class="mb-3">
        <label class="form-label">Tujuan RPJMD</label>
        <textarea name="tujuan[0][tujuan_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel" required></textarea>
    </div>

    <div class="indikator-tujuan-section mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-medium">Indikator Tujuan</h3>
        </div>
        <div class="indikator-tujuan-container">
        <div class="indikator-tujuan-item border rounded p-3 bg-white mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium">Indikator Tujuan</label>
            <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            <div class="mb-3">
            <label class="form-label">Indikator</label>
            <input type="text" name="tujuan[0][indikator_tujuan][0][indikator_tujuan]" class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
            </div>
        </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
        <button type="button" class="add-indikator-tujuan btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Indikator Tujuan
        </button>
        </div>
    </div>

    <div class="sasaran-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-medium">Sasaran Terkait Tujuan Ini</h3>
        </div>
        <div class="sasaran-container">
        <div class="sasaran-item border rounded p-3 bg-white mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium">Sasaran</label>
            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            <div class="mb-3">
            <label class="form-label">Sasaran RPJMD</label>
            <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
            </div>

            <div class="indikator-sasaran-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-medium">Indikator Sasaran</h4>
            </div>
            <div class="indikator-sasaran-container">
                <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator Sasaran</label>
                    <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                    <label class="form-label">Indikator</label>
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
                    </div>
                    <div class="col-md-4">
                    <label class="form-label">Satuan</label>
                    <select name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-select satuan-select mb-3" required>
                        <option value="">Pilih Satuan</option>
                    </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Definisi Operasional</label>
                    <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][definisi_op]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                </div>

                <div class="target-section">
                    <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                    <div class="target-container">
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][id]" value="">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][id]" value="">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][id]" value="">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][id]" value="">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][id]" value="">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 85" required>
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
        </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
        <button type="button" class="add-sasaran btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i> Tambah Sasaran
        </button>
        </div>
    </div>
    `;
    
    tujuanContainer.appendChild(newTujuan);
    
    // Update labels dan form names
    updateLabels();
    updateFormNames();
    
    // Update target years untuk tujuan baru
    updateTargetYears();
    
    // Populate satuan select untuk tujuan baru saja
    const newSatuanSelects = newTujuan.querySelectorAll('.satuan-select');
    newSatuanSelects.forEach(select => {
        select.innerHTML = generateSatuanOptions();
    });
    
    // Update delete button visibility
    updateDeleteButtonVisibility();
});

// Fungsi untuk menambahkan indikator tujuan
function addIndikatorTujuanToTujuan(tujuanElement) {
    const indikatorContainer = tujuanElement.querySelector('.indikator-tujuan-container');
    
    const newIndikator = document.createElement('div');
    newIndikator.className = 'indikator-tujuan-item border rounded p-3 bg-white mb-3';
    newIndikator.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="fw-medium">Indikator Tujuan</label>
        <button type="button" class="remove-indikator-tujuan btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
    </div>
    <div class="mb-3">
        <label class="form-label">Indikator</label>
        <input type="text" name="tujuan[0][indikator_tujuan][0][indikator_tujuan]" class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
    </div>
    `;
    
    indikatorContainer.appendChild(newIndikator);
    updateLabels();
    updateFormNames();
}

// Fungsi untuk menambahkan sasaran
function addSasaranToTujuan(tujuanElement) {
    const sasaranContainer = tujuanElement.querySelector('.sasaran-container');
    
    const newSasaran = document.createElement('div');
    newSasaran.className = 'sasaran-item border rounded p-3 bg-white mb-3';
    newSasaran.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="fw-medium">Sasaran</label>
        <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
    </div>
    <div class="mb-3">
        <label class="form-label">Sasaran RPJMD</label>
        <textarea name="tujuan[0][sasaran][0][sasaran_rpjmd]" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
    </div>

    <div class="indikator-sasaran-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-medium">Indikator Sasaran</h4>
        </div>
        <div class="indikator-sasaran-container">
        <div class="indikator-sasaran-item border rounded p-3 bg-light mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="fw-medium">Indikator Sasaran</label>
            <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            
            <div class="row">
            <div class="col-md-8">
                <label class="form-label">Indikator</label>
                <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Satuan</label>
                <select name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-select satuan-select mb-3" required>
                    <option value="">Pilih Satuan</option>
                </select>
            </div>
            </div>

            <div class="mb-3">
            <label class="form-label">Definisi Operasional</label>
            <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][definisi_op]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
            </div>

            <div class="target-section">
            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
            <div class="target-container">
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][id]" value="">
                    <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][id]" value="">
                    <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][id]" value="">
                    <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][id]" value="">
                    <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][id]" value="">
                    <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 85" required>
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
    
    // Update labels dan form names
    updateLabels();
    updateFormNames();
    
    // Update target years untuk sasaran baru
    updateTargetYears();
    
    // Populate satuan select untuk sasaran baru saja
    const newSatuanSelects = newSasaran.querySelectorAll('.satuan-select');
    newSatuanSelects.forEach(select => {
        select.innerHTML = generateSatuanOptions();
    });
    
    // Update delete button visibility
    updateDeleteButtonVisibility();
}

// Fungsi untuk menambahkan indikator sasaran
function addIndikatorSasaranToSasaran(sasaranElement) {
    console.log('=== ADDING INDIKATOR SASARAN ===');
    const indikatorContainer = sasaranElement.querySelector('.indikator-sasaran-container');
    
    const newIndikator = document.createElement('div');
    newIndikator.className = 'indikator-sasaran-item border rounded p-3 bg-light mb-3';
    newIndikator.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="fw-medium">Indikator Sasaran</label>
        <button type="button" class="remove-indikator-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
    </div>
    
    <div class="row">
        <div class="col-md-8">
        <label class="form-label">Indikator</label>
        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
        </div>
        <div class="col-md-4">
        <label class="form-label">Satuan</label>
        <select name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-select satuan-select mb-3" required>
            <option value="">Pilih Satuan</option>
        </select>
        </div>
    </div>

    <!-- Definisi Operasional -->
    <div class="mb-3">
        <label class="form-label">Definisi Operasional</label>
        <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][definisi_op]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
    </div>

    <div class="target-section">
        <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
        <div class="target-container">
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][id]" value="">
            <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 75" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][id]" value="">
            <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 77" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][id]" value="">
            <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 79" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][id]" value="">
            <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 81" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="hidden" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][id]" value="">
            <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][4][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 85" required>
            </div>
        </div>
        </div>
    </div>
    `;
    
    indikatorContainer.appendChild(newIndikator);
    
    console.log('New indikator sasaran element added');
    
    // Update labels dan form names
    updateLabels();
    updateFormNames();
    
    // Update target years untuk indikator baru
    updateTargetYears();
    
    console.log('=== AFTER ADDING INDIKATOR SASARAN ===');
    // Debug: Log final structure
    const allIndikatorItems = sasaranElement.querySelectorAll('.indikator-sasaran-item');
    allIndikatorItems.forEach((item, index) => {
        const targetItems = item.querySelectorAll('.target-item');
        console.log(`Final indikator ${index} has ${targetItems.length} target items`);
        targetItems.forEach((target, targetIndex) => {
            const tahunInput = target.querySelector('input[type="number"].tahun-target');
            const targetInput = target.querySelector('input[type="text"][name*="target_tahunan"]');
            console.log(`  Target ${targetIndex}: tahun=${tahunInput?.value}, name=${targetInput?.name}`);
        });
    });
    
    // Populate satuan select untuk indikator baru saja
    const newSatuanSelect = newIndikator.querySelector('.satuan-select');
    if (newSatuanSelect) {
        newSatuanSelect.innerHTML = generateSatuanOptions();
    }
    
    // Update delete button visibility
    updateDeleteButtonVisibility();
}

// Event delegation untuk semua tombol
document.addEventListener('click', function(e) {
    // Tombol tambah indikator tujuan
    if (e.target.classList.contains('add-indikator-tujuan') || e.target.closest('.add-indikator-tujuan')) {
    const tujuanItem = e.target.closest('.tujuan-item');
    addIndikatorTujuanToTujuan(tujuanItem);
    }
    
    // Tombol tambah sasaran
    if (e.target.classList.contains('add-sasaran') || e.target.closest('.add-sasaran')) {
    const tujuanItem = e.target.closest('.tujuan-item');
    addSasaranToTujuan(tujuanItem);
    }
    
    // Tombol tambah indikator sasaran
    if (e.target.classList.contains('add-indikator-sasaran') || e.target.closest('.add-indikator-sasaran')) {
        console.log('=== ADDING NEW INDIKATOR SASARAN ===');
        const sasaranItem = e.target.closest('.sasaran-item');
        const currentIndikatorCount = sasaranItem.querySelectorAll('.indikator-sasaran-item').length;
        console.log('Current indikator count before add:', currentIndikatorCount);
        
        addIndikatorSasaranToSasaran(sasaranItem);
        
        const newIndikatorCount = sasaranItem.querySelectorAll('.indikator-sasaran-item').length;
        console.log('New indikator count after add:', newIndikatorCount);
    }
    
    // Tombol hapus tujuan
    if (e.target.classList.contains('remove-tujuan') || e.target.closest('.remove-tujuan')) {
        const tujuanItems = document.querySelectorAll('.tujuan-item');
        if (tujuanItems.length <= 1) {
            alert('Minimal harus ada 1 Tujuan RPJMD. Tidak dapat menghapus tujuan terakhir.');
            return;
        }
        if (confirm('Hapus tujuan ini dan semua indikator serta sasarannya?')) {
            e.target.closest('.tujuan-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
    
    // Tombol hapus indikator tujuan
    if (e.target.classList.contains('remove-indikator-tujuan') || e.target.closest('.remove-indikator-tujuan')) {
        const tujuanItem = e.target.closest('.tujuan-item');
        const indikatorTujuanItems = tujuanItem.querySelectorAll('.indikator-tujuan-item');
        if (indikatorTujuanItems.length <= 1) {
            alert('Minimal harus ada 1 Indikator Tujuan. Tidak dapat menghapus indikator terakhir.');
            return;
        }
        if (confirm('Hapus indikator tujuan ini?')) {
            e.target.closest('.indikator-tujuan-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
    
    // Tombol hapus sasaran
    if (e.target.classList.contains('remove-sasaran') || e.target.closest('.remove-sasaran')) {
        const tujuanItem = e.target.closest('.tujuan-item');
        const sasaranItems = tujuanItem.querySelectorAll('.sasaran-item');
        if (sasaranItems.length <= 1) {
            alert('Minimal harus ada 1 Sasaran. Tidak dapat menghapus sasaran terakhir.');
            return;
        }
        if (confirm('Hapus sasaran ini dan semua indikator sasarannya?')) {
            e.target.closest('.sasaran-item').remove();
            updateLabels();
            updateFormNames();
        }
    }
    
    // Tombol hapus indikator sasaran - mengikuti pendekatan RENSTRA yang sederhana
    if (e.target.classList.contains('remove-indikator-sasaran') || e.target.closest('.remove-indikator-sasaran')) {
        const sasaranItem = e.target.closest('.sasaran-item');
        const indikatorSasaranItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
        
        if (indikatorSasaranItems.length <= 1) {
            alert('Minimal harus ada 1 Indikator Sasaran per Sasaran!');
            return;
        }
        
        if (confirm('Hapus indikator sasaran ini dan target 5 tahunannya?')) {
            console.log('=== BEFORE INDIKATOR SASARAN REMOVAL ===');
            console.log('Current indikator count:', indikatorSasaranItems.length);
            
            // Debug: Log target field structure before removal
            indikatorSasaranItems.forEach((item, index) => {
                const targetItems = item.querySelectorAll('.target-item');
                console.log(`Indikator ${index} has ${targetItems.length} target items`);
                targetItems.forEach((target, targetIndex) => {
                    const tahunInput = target.querySelector('input[type="number"].tahun-target');
                    const targetInput = target.querySelector('input[type="text"][name*="target_tahunan"]');
                    console.log(`  Target ${targetIndex}: tahun=${tahunInput?.value}, target=${targetInput?.value}`);
                });
            });
            
            e.target.closest('.indikator-sasaran-item').remove();
            
            console.log('=== AFTER INDIKATOR SASARAN REMOVAL ===');
            const remainingItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
            console.log('Remaining indikator count:', remainingItems.length);
            
            // RENSTRA approach: Always update after removal
            updateLabels();
            updateFormNames();
            updateTargetYears(); // Re-apply years after re-indexing
            updateDeleteButtonVisibility();
            
            console.log('=== AFTER REINDEXING ===');
            // Debug: Log target field structure after reindexing
            remainingItems.forEach((item, index) => {
                const targetItems = item.querySelectorAll('.target-item');
                console.log(`Reindexed indikator ${index} has ${targetItems.length} target items`);
                targetItems.forEach((target, targetIndex) => {
                    const tahunInput = target.querySelector('input[type="number"].tahun-target');
                    const targetInput = target.querySelector('input[type="text"][name*="target_tahunan"]');
                    console.log(`  Target ${targetIndex}: tahun=${tahunInput?.value}, target=${targetInput?.value}, name=${targetInput?.name}`);
                });
            });
        }
    }
});

// Fungsi untuk mengupdate tahun target berdasarkan periode start
function updateTargetYears() {
    const periodeStart = parseInt(document.getElementById('periode_start').value);
    if (periodeStart && !isNaN(periodeStart)) {
        console.log('Updating target years with periode start:', periodeStart);
        
        // Update semua tahun target untuk setiap indikator sasaran
        document.querySelectorAll('.indikator-sasaran-item').forEach((indikatorItem, indikatorIndex) => {
            const targetItems = indikatorItem.querySelectorAll('.target-item');
            console.log(`Processing indikator ${indikatorIndex} with ${targetItems.length} target items`);
            
            targetItems.forEach((targetItem, targetIndex) => {
                // Gunakan selector yang sangat spesifik untuk field tahun
                const tahunInput = targetItem.querySelector('input[type="number"].tahun-target');
                
                if (tahunInput) {
                    const newYear = periodeStart + targetIndex;
                    console.log(`Setting year for target ${targetIndex}: ${newYear}`);
                    tahunInput.value = newYear;
                } else {
                    console.warn(`Tahun input not found for target ${targetIndex} in indikator ${indikatorIndex}`);
                }
            });
        });
    } else {
        console.log('Invalid periode start value:', document.getElementById('periode_start').value);
    }
}

// Event listener untuk auto-fill periode akhir dan update target years
document.addEventListener('input', function(e) {
    if (e.target.id === 'periode_start') {
    const periodeStart = parseInt(e.target.value);
    if (periodeStart && !isNaN(periodeStart)) {
        // Auto-fill periode akhir
        document.getElementById('periode_end').value = periodeStart + 4;
        // Update semua tahun target
        updateTargetYears();
    }
    }
});

// Form change tracking
let formHasChanges = false;
let isSubmitting = false;

// Track form changes
function trackFormChanges() {
    const form = document.getElementById('rpjmd-form');
    
    // Track input changes (input, textarea, select)
    form.addEventListener('input', function(e) {
        if (!isSubmitting) {
            formHasChanges = true;
            console.log('Form changed via input:', e.target.name || e.target.id);
        }
    });
    
    // Track textarea changes specifically
    form.addEventListener('change', function(e) {
        if (!isSubmitting) {
            formHasChanges = true;
            console.log('Form changed via change:', e.target.name || e.target.id);
        }
    });
    
    // Track dynamic additions/removals
    form.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-tujuan') || 
            e.target.classList.contains('add-sasaran') || 
            e.target.classList.contains('add-indikator-tujuan') || 
            e.target.classList.contains('add-indikator-sasaran') ||
            e.target.classList.contains('remove-tujuan') ||
            e.target.classList.contains('remove-sasaran') ||
            e.target.classList.contains('remove-indikator-tujuan') ||
            e.target.classList.contains('remove-indikator-sasaran') ||
            e.target.closest('.add-tujuan') ||
            e.target.closest('.add-sasaran') ||
            e.target.closest('.add-indikator-tujuan') ||
            e.target.closest('.add-indikator-sasaran') ||
            e.target.closest('.remove-tujuan') ||
            e.target.closest('.remove-sasaran') ||
            e.target.closest('.remove-indikator-tujuan') ||
            e.target.closest('.remove-indikator-sasaran')) {
            
            if (!isSubmitting) {
                formHasChanges = true;
                console.log('Form changed via button click:', e.target.className);
            }
        }
    });
    
    // Track form submission
    form.addEventListener('submit', function(e) {
        isSubmitting = true;
        console.log('Form is being submitted, disabling warnings');
    });
}

// Warn user about unsaved changes before leaving page
function setupUnloadWarning() {
    // Warning saat user meninggalkan halaman (reload, close tab, navigate away)
    window.addEventListener('beforeunload', function(e) {
        if (hasFormActuallyChanged() && !isSubmitting) {
            const message = 'Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?';
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    });
    
    // Warning saat user menekan tombol "Kembali"
    const backButton = document.querySelector('a[href*="rpjmd"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            if (hasFormActuallyChanged() && !isSubmitting) {
                const confirmed = confirm('Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin kembali ke halaman sebelumnya?\n\nSemua perubahan akan hilang.');
                if (!confirmed) {
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
                const confirmed = confirm('Apakah Anda yakin ingin menyimpan perubahan pada form RPJMD ini?');
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
        // Ctrl+S untuk save (mencegah save browser default)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            if (hasFormActuallyChanged()) {
                const confirmed = confirm('Apakah Anda ingin menyimpan form dengan shortcut Ctrl+S?');
                if (confirmed) {
                    document.getElementById('rpjmd-form').dispatchEvent(new Event('submit'));
                }
            } else {
                alert('Tidak ada perubahan untuk disimpan.');
            }
        }
        
        // Esc untuk peringatan keluar
        if (e.key === 'Escape') {
            if (hasFormActuallyChanged()) {
                const confirmed = confirm('Anda memiliki perubahan yang belum disimpan. Apakah Anda ingin meninggalkan halaman ini?');
                if (confirmed) {
                    window.history.back();
                }
            }
        }
    });
}

// Initialize pada load - mengikuti pendekatan RENSTRA
document.addEventListener('DOMContentLoaded', function() {
    updateLabels();
    
    // RENSTRA approach: ALWAYS call updateFormNames on load
    // This ensures consistent form structure for both edit and create modes
    updateFormNames();
    
    updateTargetYears(); // Initialize target years pada load
    
    // Setup form change tracking and warnings
    trackFormChanges();
    setupUnloadWarning();
    setupSaveConfirmation();
    setupKeyboardShortcuts();
    
    console.log('RPJMD form initialized with RENSTRA approach - consistent reindexing');
    
    // Show initial instruction to user
    setTimeout(() => {
        console.log('💡 Tips: Gunakan Ctrl+S untuk menyimpan atau Esc jika ingin keluar');
    }, 1000);
});

// Fungsi untuk mengontrol visibility tombol delete berdasarkan jumlah item
function updateDeleteButtonVisibility() {
    // Kontrol tombol delete tujuan
    const tujuanItems = document.querySelectorAll('.tujuan-item');
    tujuanItems.forEach(item => {
        const deleteBtn = item.querySelector('.remove-tujuan');
        if (deleteBtn) {
            deleteBtn.style.display = tujuanItems.length <= 1 ? 'none' : 'inline-block';
        }
    });

    // Kontrol tombol delete indikator tujuan
    document.querySelectorAll('.tujuan-item').forEach(tujuanItem => {
        const indikatorTujuanItems = tujuanItem.querySelectorAll('.indikator-tujuan-item');
        indikatorTujuanItems.forEach(item => {
            const deleteBtn = item.querySelector('.remove-indikator-tujuan');
            if (deleteBtn) {
                deleteBtn.style.display = indikatorTujuanItems.length <= 1 ? 'none' : 'inline-block';
            }
        });
    });

    // Kontrol tombol delete sasaran
    document.querySelectorAll('.tujuan-item').forEach(tujuanItem => {
        const sasaranItems = tujuanItem.querySelectorAll('.sasaran-item');
        sasaranItems.forEach(item => {
            const deleteBtn = item.querySelector('.remove-sasaran');
            if (deleteBtn) {
                deleteBtn.style.display = sasaranItems.length <= 1 ? 'none' : 'inline-block';
            }
        });
    });

    // Kontrol tombol delete indikator sasaran
    document.querySelectorAll('.sasaran-item').forEach(sasaranItem => {
        const indikatorSasaranItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
        indikatorSasaranItems.forEach(item => {
            const deleteBtn = item.querySelector('.remove-indikator-sasaran');
            if (deleteBtn) {
                deleteBtn.style.display = indikatorSasaranItems.length <= 1 ? 'none' : 'inline-block';
            }
        });
    });
}

// Fungsi validasi form untuk memastikan minimal requirement
function validateMinimalRequirements() {
    const errors = [];
    
    // Minimal harus ada 1 tujuan
    const tujuanItems = document.querySelectorAll('.tujuan-item');
    if (tujuanItems.length === 0) {
        errors.push('Harus ada minimal 1 Tujuan RPJMD');
    }

    // Setiap tujuan harus memiliki minimal 1 indikator tujuan dan 1 sasaran
    tujuanItems.forEach((tujuanItem, tujuanIndex) => {
        const indikatorTujuanItems = tujuanItem.querySelectorAll('.indikator-tujuan-item');
        if (indikatorTujuanItems.length === 0) {
            errors.push(`Tujuan ${tujuanIndex + 1} harus memiliki minimal 1 Indikator Tujuan`);
        }

        const sasaranItems = tujuanItem.querySelectorAll('.sasaran-item');
        if (sasaranItems.length === 0) {
            errors.push(`Tujuan ${tujuanIndex + 1} harus memiliki minimal 1 Sasaran`);
        }

        // Setiap sasaran harus memiliki minimal 1 indikator sasaran
        sasaranItems.forEach((sasaranItem, sasaranIndex) => {
            const indikatorSasaranItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
            if (indikatorSasaranItems.length === 0) {
                errors.push(`Sasaran ${tujuanIndex + 1}.${sasaranIndex + 1} harus memiliki minimal 1 Indikator Sasaran`);
            }
        });
    });

    return errors;
}

// Form submission handler - mengikuti pendekatan RENSTRA yang lebih sederhana
document.getElementById('rpjmd-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('Form submission started...');
    
    // RENSTRA approach: Always update form names before submission
    updateFormNames();
    
    // Validasi minimal requirements
    const minimalErrors = validateMinimalRequirements();
    if (minimalErrors.length > 0) {
        alert('Validasi gagal:\n\n' + minimalErrors.join('\n'));
        return;
    }
    
    // Basic validation
    const misi = document.querySelector('textarea[name="misi"]');
    const tahunMulai = document.querySelector('input[name="tahun_mulai"]');
    const tahunAkhir = document.querySelector('input[name="tahun_akhir"]');
    
    console.log('Basic validation fields:', {
        misi: misi ? misi.value.trim() : 'NOT FOUND',
        tahunMulai: tahunMulai ? tahunMulai.value : 'NOT FOUND',
        tahunAkhir: tahunAkhir ? tahunAkhir.value : 'NOT FOUND'
    });
    
    if (!misi || !misi.value.trim()) {
        alert('Misi RPJMD harus diisi');
        return false;
    }
    
    if (!tahunMulai || !tahunMulai.value || !tahunAkhir || !tahunAkhir.value) {
        alert('Periode tahun harus diisi');
        return false;
    }
    
    // Check if at least one tujuan exists
    const tujuanItems = document.querySelectorAll('.tujuan-item');
    console.log('Found tujuan items:', tujuanItems.length);
    
    if (tujuanItems.length === 0) {
        alert('Minimal harus ada satu tujuan');
        return false;
    }
    
    // Simplified validation - just check if tujuan text exists
    let isValid = true;
    tujuanItems.forEach((tujuan, index) => {
        const tujuanText = tujuan.querySelector('textarea[name*="tujuan_rpjmd"]');
        console.log(`Tujuan ${index + 1}:`, {
            found: !!tujuanText,
            name: tujuanText ? tujuanText.name : 'none',
            value: tujuanText ? tujuanText.value.trim() : 'none'
        });
        
        if (!tujuanText || !tujuanText.value.trim()) {
            alert(`Tujuan ${index + 1} harus diisi`);
            isValid = false;
            return;
        }
    });
    
    if (!isValid) {
        console.log('Validation failed, stopping submission');
        return false;
    }
    
    console.log('Validation passed, submitting form...');
    console.log('Form action:', this.action);
    console.log('Form method:', this.method);
    
    // Konfirmasi sebelum submit - RENSTRA style
    if (hasFormActuallyChanged() && !isSubmitting) {
        const modeInput = document.querySelector('input[name="mode"]');
        const isEditMode = modeInput && modeInput.value === 'edit';
        
        const confirmMessage = isEditMode 
            ? 'Apakah Anda yakin ingin menyimpan perubahan pada data RPJMD ini?' 
            : 'Apakah Anda yakin ingin menyimpan data RPJMD baru ini?';
            
        const confirmed = confirm(confirmMessage);
        if (!confirmed) {
            console.log('User cancelled form submission');
            return false;
        }
        
        // Set flag bahwa form sedang disubmit
        isSubmitting = true;
    }
    
    // Debug: Log form structure for verification
    console.log('=== FINAL FORM STRUCTURE VERIFICATION ===');
    
    // Check ID fields for edit mode
    const modeInput = document.querySelector('input[name="mode"]');
    if (modeInput && modeInput.value === 'edit') {
        console.log('Edit mode - checking ID preservation:');
        
        const allIdFields = document.querySelectorAll('input[type="hidden"][name*="[id]"]');
        console.log('Total ID fields found:', allIdFields.length);
        
        let idCount = 0;
        allIdFields.forEach((input) => {
            if (input.value) {
                idCount++;
                console.log(`ID preserved: ${input.name} = ${input.value}`);
            }
        });
        
        console.log(`Total IDs with values: ${idCount}`);
    }
    
    // If validation passes, clear form changes flag and submit the form
    formHasChanges = false;
    this.submit();
});

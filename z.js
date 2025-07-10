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
}

// Fungsi untuk mengupdate nama form setelah penghapusan atau penambahan
function updateFormNames() {
    document.querySelectorAll('.tujuan-item').forEach((tujuanItem, tujuanIndex) => {
    // Update tujuan names
    const tujuanTextarea = tujuanItem.querySelector('textarea[name*="tujuan_rpjmd"]');
    if (tujuanTextarea) {
        tujuanTextarea.name = `tujuan[${tujuanIndex}][tujuan_rpjmd]`;
    }

    // Update indikator tujuan names
    tujuanItem.querySelectorAll('.indikator-tujuan-item').forEach((indikatorTujuanItem, indikatorTujuanIndex) => {
        const indikatorInput = indikatorTujuanItem.querySelector('input[name*="indikator"]');
        if (indikatorInput) {
        indikatorInput.name = `tujuan[${tujuanIndex}][indikator_tujuan][${indikatorTujuanIndex}][indikator]`;
        }
    });

    // Update sasaran names
    tujuanItem.querySelectorAll('.sasaran-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran_rpjmd"]');
        if (sasaranTextarea) {
        sasaranTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][sasaran_rpjmd]`;
        }

        // Update indikator sasaran names
        sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorSasaranItem, indikatorSasaranIndex) => {
        const indikatorInput = indikatorSasaranItem.querySelector('input[name*="indikator_sasaran"], input[name*="placeholder_indikator_sasaran"]');
        const satuanInput = indikatorSasaranItem.querySelector('input[name*="satuan"], input[name*="placeholder_satuan"]');
        const strategiTextarea = indikatorSasaranItem.querySelector('textarea[name*="strategi"], textarea[name*="placeholder_strategi"]');
        
        if (indikatorInput) {
            indikatorInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][indikator_sasaran]`;
        }
        if (satuanInput) {
            satuanInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][satuan]`;
        }
        if (strategiTextarea) {
            strategiTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][strategi]`;
        }

        // Update target names for 5-year plan
        indikatorSasaranItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
            const tahunInput = targetItem.querySelector('.tahun-target');
            const targetInput = targetItem.querySelector('input[name*="target_tahunan"], input[name*="placeholder_target"], input:not(.tahun-target)');
            
            if (tahunInput) {
            tahunInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][tahun]`;
            }
            if (targetInput && !targetInput.classList.contains('tahun-target')) {
            targetInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][target_tahunan]`;
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
        <label class="fw-medium">Tujuan</label>
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
            <input type="text" name="tujuan[0][indikator_tujuan][0][indikator]" class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
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
                    <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Strategi</label>
                    <textarea name="tujuan[0][sasaran][0][indikator_sasaran][0][strategi]" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
                </div>

                <div class="target-section">
                    <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
                    <div class="target-container">
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][0][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][1][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][2][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
                        <input type="number" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][tahun]" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                        </div>
                        <div class="col">
                        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][target_tahunan][3][target_tahunan]" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                        </div>
                    </div>
                    <div class="target-item row g-2 align-items-center mb-2">
                        <div class="col-auto">
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
    updateLabels();
    updateFormNames();
    updateTargetYears(); // Update tahun target untuk elemen baru
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
        <input type="text" name="placeholder_name" class="form-control" placeholder="Contoh: Indeks Kepuasan Masyarakat (IKM)" required>
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
        <textarea name="placeholder_name" class="form-control" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat" required></textarea>
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
                <input type="text" name="placeholder_indikator_sasaran" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Satuan</label>
                <input type="text" name="placeholder_satuan" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
            </div>
            </div>

            <div class="mb-3">
            <label class="form-label">Strategi</label>
            <textarea name="placeholder_strategi" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
            </div>

            <div class="target-section">
            <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
            <div class="target-container">
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="placeholder_target_tahunan" class="form-control form-control-sm" placeholder="Contoh: 75" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="placeholder_target_tahunan" class="form-control form-control-sm" placeholder="Contoh: 77" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="placeholder_target_tahunan" class="form-control form-control-sm" placeholder="Contoh: 79" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="placeholder_target_tahunan" class="form-control form-control-sm" placeholder="Contoh: 81" required>
                </div>
                </div>
                <div class="target-item row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
                </div>
                <div class="col">
                    <input type="text" name="placeholder_target_tahunan" class="form-control form-control-sm" placeholder="Contoh: 85" required>
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
}

// Fungsi untuk menambahkan indikator sasaran
function addIndikatorSasaranToSasaran(sasaranElement) {
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
        <input type="text" name="placeholder_indikator_sasaran" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
        </div>
        <div class="col-md-4">
        <label class="form-label">Satuan</label>
        <input type="text" name="placeholder_satuan" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
        </div>
    </div>

    <!-- Strategi -->
    <div class="mb-3">
        <label class="form-label">Strategi</label>
        <textarea name="placeholder_strategi" class="form-control mb-3" rows="3" placeholder="Contoh: Meningkatkan kapasitas SDM aparatur, digitalisasi pelayanan, dan penerapan sistem monitoring evaluasi" required></textarea>
    </div>

    <div class="target-section">
        <h5 class="fw-medium mb-3">Target 5 Tahunan</h5>
        <div class="target-container">
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="placeholder_target" class="form-control form-control-sm" placeholder="Contoh: 75" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="placeholder_target" class="form-control form-control-sm" placeholder="Contoh: 77" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="placeholder_target" class="form-control form-control-sm" placeholder="Contoh: 79" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="placeholder_target" class="form-control form-control-sm" placeholder="Contoh: 81" required>
            </div>
        </div>
        <div class="target-item row g-2 align-items-center mb-2">
            <div class="col-auto">
            <input type="number" name="placeholder_tahun" class="form-control form-control-sm tahun-target" style="width: 80px;" readonly>
            </div>
            <div class="col">
            <input type="text" name="placeholder_target" class="form-control form-control-sm" placeholder="Contoh: 85" required>
            </div>
        </div>
        </div>
    </div>
    `;
    
    indikatorContainer.appendChild(newIndikator);
    
    updateLabels();
    updateFormNames();
    updateTargetYears(); // Update tahun target untuk elemen baru
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
    const sasaranItem = e.target.closest('.sasaran-item');
    addIndikatorSasaranToSasaran(sasaranItem);
    }
    
    // Tombol hapus tujuan
    if (e.target.classList.contains('remove-tujuan') || e.target.closest('.remove-tujuan')) {
    if (confirm('Hapus tujuan ini dan semua indikator serta sasarannya?')) {
        e.target.closest('.tujuan-item').remove();
        updateLabels();
        updateFormNames();
    }
    }
    
    // Tombol hapus indikator tujuan
    if (e.target.classList.contains('remove-indikator-tujuan') || e.target.closest('.remove-indikator-tujuan')) {
    if (confirm('Hapus indikator tujuan ini?')) {
        e.target.closest('.indikator-tujuan-item').remove();
        updateLabels();
        updateFormNames();
    }
    }
    
    // Tombol hapus sasaran
    if (e.target.classList.contains('remove-sasaran') || e.target.closest('.remove-sasaran')) {
    if (confirm('Hapus sasaran ini dan semua indikator sasarannya?')) {
        e.target.closest('.sasaran-item').remove();
        updateLabels();
        updateFormNames();
    }
    }
    
    // Tombol hapus indikator sasaran
    if (e.target.classList.contains('remove-indikator-sasaran') || e.target.closest('.remove-indikator-sasaran')) {
    if (confirm('Hapus indikator sasaran ini dan target 5 tahunannya?')) {
        e.target.closest('.indikator-sasaran-item').remove();
        updateLabels();
        updateFormNames();
    }
    }
});

// Fungsi untuk mengupdate tahun target berdasarkan periode start
function updateTargetYears() {
    const periodeStart = parseInt(document.getElementById('periode_start').value);
    if (periodeStart && !isNaN(periodeStart)) {
    // Update semua tahun target untuk setiap indikator sasaran
    document.querySelectorAll('.indikator-sasaran-item').forEach((indikatorItem) => {
        const targetItems = indikatorItem.querySelectorAll('.target-item');
        targetItems.forEach((targetItem, index) => {
        const tahunInput = targetItem.querySelector('.tahun-target');
        if (tahunInput) {
            tahunInput.value = periodeStart + index;
        }
        });
    });
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

// Initialize pada load
document.addEventListener('DOMContentLoaded', function() {
    updateLabels();
    updateFormNames();
    updateTargetYears(); // Initialize target years pada load
});

// Form submission handler
document.getElementById('rpjmd-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Debug: Log semua input target tahunan
    console.log('=== DEBUG TARGET TAHUNAN ===');
    document.querySelectorAll('input[name*="target_tahunan"]').forEach((input, index) => {
        console.log(`Target ${index}: name="${input.name}", value="${input.value}"`);
    });
    
    // Basic validation
    const misi = document.querySelector('textarea[name="misi[misi]"]').value.trim();
    const tahunMulai = document.querySelector('input[name="misi[tahun_mulai]"]').value;
    const tahunAkhir = document.querySelector('input[name="misi[tahun_akhir]"]').value;
    
    if (!misi) {
        alert('Misi RPJMD harus diisi');
        return false;
    }
    
    if (!tahunMulai || !tahunAkhir) {
        alert('Periode tahun harus diisi');
        return false;
    }
    
    // Check if at least one tujuan exists
    const tujuanItems = document.querySelectorAll('.tujuan-item');
    if (tujuanItems.length === 0) {
        alert('Minimal harus ada satu tujuan');
        return false;
    }
    
    // Validate each tujuan has required fields
    let isValid = true;
    tujuanItems.forEach((tujuan, index) => {
        const tujuanText = tujuan.querySelector('textarea[name*="tujuan_rpjmd"]');
        if (!tujuanText || !tujuanText.value.trim()) {
            alert(`Tujuan ${index + 1} harus diisi`);
            isValid = false;
            return;
        }
        
        const sasaranItems = tujuan.querySelectorAll('.sasaran-item');
        if (sasaranItems.length === 0) {
            alert(`Tujuan ${index + 1} harus memiliki minimal satu sasaran`);
            isValid = false;
            return;
        }
        
        sasaranItems.forEach((sasaran, sasaranIndex) => {
            const sasaranText = sasaran.querySelector('textarea[name*="sasaran_rpjmd"]');
            if (!sasaranText || !sasaranText.value.trim()) {
                alert(`Sasaran ${index + 1}.${sasaranIndex + 1} harus diisi`);
                isValid = false;
                return;
            }
            
            const indikatorItems = sasaran.querySelectorAll('.indikator-sasaran-item');
            if (indikatorItems.length === 0) {
                alert(`Sasaran ${index + 1}.${sasaranIndex + 1} harus memiliki minimal satu indikator`);
                isValid = false;
                return;
            }
        });
    });
    
    if (!isValid) {
        return false;
    }
    
    // If validation passes, submit the form
    this.submit();
});

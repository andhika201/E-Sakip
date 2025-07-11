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
    if (tujuanTextarea) {
        tujuanTextarea.name = `tujuan[${tujuanIndex}][tujuan_rpjmd]`;
    }
    
    // Update tujuan hidden ID if exists
    const tujuanIdInput = tujuanItem.querySelector('input[type="hidden"][name*="id"]');
    if (tujuanIdInput) {
        tujuanIdInput.name = `tujuan[${tujuanIndex}][id]`;
    }

    // Update indikator tujuan names
    tujuanItem.querySelectorAll('.indikator-tujuan-item').forEach((indikatorTujuanItem, indikatorTujuanIndex) => {
        const indikatorInput = indikatorTujuanItem.querySelector('input.form-control');
        if (indikatorInput) {
        indikatorInput.name = `tujuan[${tujuanIndex}][indikator_tujuan][${indikatorTujuanIndex}][indikator_tujuan]`;
        }
        
        // Update indikator tujuan hidden ID if exists
        const indikatorIdInput = indikatorTujuanItem.querySelector('input[type="hidden"][name*="id"]');
        if (indikatorIdInput) {
        indikatorIdInput.name = `tujuan[${tujuanIndex}][indikator_tujuan][${indikatorTujuanIndex}][id]`;
        }
    });

    // Update sasaran names
    tujuanItem.querySelectorAll('.sasaran-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran_rpjmd"]');
        if (sasaranTextarea) {
        sasaranTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][sasaran_rpjmd]`;
        }
        
        // Update sasaran hidden ID if exists
        const sasaranIdInput = sasaranItem.querySelector('input[type="hidden"][name*="id"]');
        if (sasaranIdInput) {
        sasaranIdInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][id]`;
        }

        // Update indikator sasaran names
        sasaranItem.querySelectorAll('.indikator-sasaran-item').forEach((indikatorSasaranItem, indikatorSasaranIndex) => {
        const indikatorInput = indikatorSasaranItem.querySelector('input[name*="indikator_sasaran"]');
        const satuanInput = indikatorSasaranItem.querySelector('input[name*="satuan"]');
        const strategiTextarea = indikatorSasaranItem.querySelector('textarea[name*="strategi"]');
        
        if (indikatorInput) {
            indikatorInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][indikator_sasaran]`;
        }
        if (satuanInput) {
            satuanInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][satuan]`;
        }
        if (strategiTextarea) {
            strategiTextarea.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][strategi]`;
        }
        
        // Update indikator sasaran hidden ID if exists
        const indikatorSasaranIdInput = indikatorSasaranItem.querySelector('input[type="hidden"][name*="id"]');
        if (indikatorSasaranIdInput) {
            indikatorSasaranIdInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][id]`;
        }

        // Update target names for 5-year plan
        indikatorSasaranItem.querySelectorAll('.target-item').forEach((targetItem, targetIndex) => {
            const tahunInput = targetItem.querySelector('.tahun-target');
            const targetInput = targetItem.querySelector('input[name*="target_tahunan"]:not(.tahun-target)');
            
            if (tahunInput) {
            tahunInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][tahun]`;
            }
            if (targetInput) {
            targetInput.name = `tujuan[${tujuanIndex}][sasaran][${sasaranIndex}][indikator_sasaran][${indikatorSasaranIndex}][target_tahunan][${targetIndex}][target_tahunan]`;
            }
            
            // Update target tahunan hidden ID if exists
            const targetIdInput = targetItem.querySelector('input[type="hidden"][name*="id"]');
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
        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][indikator_sasaran]" class="form-control mb-3" placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan" required>
        </div>
        <div class="col-md-4">
        <label class="form-label">Satuan</label>
        <input type="text" name="tujuan[0][sasaran][0][indikator_sasaran][0][satuan]" class="form-control mb-3" placeholder="Contoh: Persen, Jumlah, Indeks" required>
        </div>
    </div>

    <!-- Strategi -->
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
    
    // Tombol hapus indikator sasaran
    if (e.target.classList.contains('remove-indikator-sasaran') || e.target.closest('.remove-indikator-sasaran')) {
        const sasaranItem = e.target.closest('.sasaran-item');
        const indikatorSasaranItems = sasaranItem.querySelectorAll('.indikator-sasaran-item');
        if (indikatorSasaranItems.length <= 1) {
            alert('Minimal harus ada 1 Indikator Sasaran. Tidak dapat menghapus indikator terakhir.');
            return;
        }
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

// Form change tracking
let formHasChanges = false;

// Track form changes
function trackFormChanges() {
    const form = document.getElementById('rpjmd-form');
    
    // Track input changes
    form.addEventListener('input', function(e) {
        formHasChanges = true;
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
            e.target.classList.contains('remove-indikator-sasaran')) {
            
            formHasChanges = true;
        }
    });
}

// Warn user about unsaved changes before leaving page
function setupUnloadWarning() {
    window.addEventListener('beforeunload', function(e) {
        if (formHasChanges) {
            const message = 'Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?';
            e.returnValue = message;
            return message;
        }
    });
}

// Initialize pada load
document.addEventListener('DOMContentLoaded', function() {
    updateLabels();
    updateFormNames();
    updateTargetYears(); // Initialize target years pada load
    
    // Setup form change tracking and unload warning
    trackFormChanges();
    setupUnloadWarning();
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

// Form submission handler
document.getElementById('rpjmd-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('Form submission started...');
    
    // Update form names before validation
    updateFormNames();
    
    // Validasi minimal requirements
    const minimalErrors = validateMinimalRequirements();
    if (minimalErrors.length > 0) {
        alert('Validasi gagal:\n\n' + minimalErrors.join('\n'));
        return;
    }
    
    // Basic validation only - simplified for debugging
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
    
    // Debug: Check if ID fields are present for edit mode
    const modeInput = document.querySelector('input[name="mode"]');
    if (modeInput && modeInput.value === 'edit') {
        console.log('=== EDIT MODE - CHECKING ID FIELDS ===');
        
        // Check misi ID
        const misiId = document.querySelector('input[name="id"]');
        console.log('Misi ID:', misiId ? misiId.value : 'NOT FOUND');
        
        // Check tujuan IDs
        document.querySelectorAll('input[name*="tujuan"][name*="[id]"]').forEach((input, index) => {
            console.log(`Tujuan ID ${index}:`, input.name, '=', input.value);
        });
        
        // Check indikator tujuan IDs
        document.querySelectorAll('input[name*="indikator_tujuan"][name*="[id]"]').forEach((input, index) => {
            console.log(`Indikator Tujuan ID ${index}:`, input.name, '=', input.value);
        });
        
        // Check sasaran IDs
        document.querySelectorAll('input[name*="sasaran"][name*="[id]"]').forEach((input, index) => {
            console.log(`Sasaran ID ${index}:`, input.name, '=', input.value);
        });
        
        // Check indikator sasaran IDs
        document.querySelectorAll('input[name*="indikator_sasaran"][name*="[id]"]').forEach((input, index) => {
            console.log(`Indikator Sasaran ID ${index}:`, input.name, '=', input.value);
        });
        
        // Check target tahunan IDs
        document.querySelectorAll('input[name*="target_tahunan"][name*="[id]"]').forEach((input, index) => {
            console.log(`Target Tahunan ID ${index}:`, input.name, '=', input.value);
        });
    }
    
    // If validation passes, clear form changes flag and submit the form
    formHasChanges = false;
    this.submit();
});

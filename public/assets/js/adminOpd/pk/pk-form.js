document.addEventListener('DOMContentLoaded', function() {
    // Get form reference for all functions
    const form = document.getElementById('rpjmd-form');
    if (!form) {
        console.error('Form with ID "rpjmd-form" not found.');
        return;
    }
    
    // Inisialisasi: Update nama form saat halaman dimuat (untuk form edit)
    updateLabels();
    updateFormNames();
    
    // Fungsi untuk mengupdate penomoran label secara real-time (seperti Renja)
    function updateLabels() {
        document.querySelectorAll('.sasaran-container .sasaran-item').forEach((sasaranItem, sasaranIndex) => {
            const sasaranNumber = sasaranIndex + 1;
            
            // Update label Sasaran PK
            const sasaranLabel = sasaranItem.querySelector('label.fw-medium.h5');
            if (sasaranLabel) {
                sasaranLabel.textContent = `Sasaran ${sasaranNumber}`;
            }

            // Update indikator PK labels
            sasaranItem.querySelectorAll('.indikator-item').forEach((indikatorItem, indikatorIndex) => {
                const indikatorNumber = indikatorIndex + 1;
                const indikatorLabel = indikatorItem.querySelector('label.fw-medium');
                if (indikatorLabel && (indikatorLabel.textContent.startsWith('Indikator') || indikatorLabel.textContent === 'Indikator')) {
                    indikatorLabel.textContent = `Indikator ${sasaranNumber}.${indikatorNumber}`;
                }
            });
        });

        // Update program labels
        document.querySelectorAll('.program-container .program-item').forEach((programItem, programIndex) => {
            const programNumber = programIndex + 1;
            const programLabel = programItem.querySelector('label.fw-medium');
            if (programLabel && (programLabel.textContent.startsWith('Program') || programLabel.textContent === 'Program')) {
                programLabel.textContent = `Program ${programNumber}`;
            }
        });
    }
    
    // Update nama form untuk indikator dan program (dengan ID tracking seperti Renja)
    function updateFormNames() {
        // Update nama untuk sasaran dan indikator
        document.querySelectorAll('.sasaran-container .sasaran-item').forEach((sasaranItem, sasaranIndex) => {
            // Update nama sasaran
            const sasaranTextarea = sasaranItem.querySelector('textarea[name*="sasaran"]');
            if (sasaranTextarea) {
                sasaranTextarea.name = `sasaran_pk[${sasaranIndex}][sasaran]`;
            }

            // Update nama ID sasaran (untuk existing records)
            const sasaranIdInput = sasaranItem.querySelector('input[type="hidden"][name*="id"]');
            if (sasaranIdInput) {
                sasaranIdInput.name = `sasaran_pk[${sasaranIndex}][id]`;
            }

            // Update nama untuk indikator dalam sasaran ini
            const indikatorItems = sasaranItem.querySelectorAll('.indikator-item');
            indikatorItems.forEach((indikator, indikatorIndex) => {
                // Update nama ID indikator (untuk existing records)
                const indikatorIdInput = indikator.querySelector('input[type="hidden"][name*="id"]');
                if (indikatorIdInput) {
                    indikatorIdInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][id]`;
                }

                const indikatorInput = indikator.querySelector('input[name*="indikator"]:not([type="hidden"])');
                const targetInput = indikator.querySelector('input[name*="target"]:not([type="hidden"])');
                
                if (indikatorInput) {
                    indikatorInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][indikator]`;
                }

                if (targetInput) {
                    targetInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][target]`;
                }
            });
        });

        // Update nama untuk program
        document.querySelectorAll('.program-container .program-item').forEach((program, index) => {
            // Update nama ID program (untuk existing records)
            const programIdInput = program.querySelector('input[type="hidden"][name*="id"]');
            if (programIdInput) {
                programIdInput.name = `program[${index}][id]`;
            }

            const programSelect = program.querySelector('select[name*="program_id"]');
            const anggaranInput = program.querySelector('input[name*="anggaran"]:not([type="hidden"])');
            
            if (programSelect) {
                programSelect.name = `program[${index}][program_id]`;
            }
            
            if (anggaranInput) {
                anggaranInput.name = `program[${index}][anggaran]`;
            }
        });
    }

    // ============ ADD FUNCTIONS ============

    // Tambah Sasaran Baru
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-sasaran') || e.target.closest('.add-sasaran')) {
            const sasaranContainer = document.querySelector('.sasaran-container');
            const newSasaran = document.createElement('div');
            newSasaran.className = 'sasaran-item border border-secondary rounded p-3 bg-white mb-3';
            
            // Use template with placeholder indices that will be updated by updateFormNames()
            newSasaran.innerHTML = `
                <!-- Tidak ada hidden ID field karena ini new record -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium h5">Sasaran</label>
                    <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sasaran PK</label>
                    <textarea name="sasaran_pk[0][sasaran]" class="form-control border-secondary" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima" required></textarea>
                </div>

                <!-- Indikator -->
                <div class="indikator-section">
                    <div class="indikator-container">
                        <div class="indikator-item border rounded p-3 bg-light mb-3">
                            <!-- Tidak ada hidden ID field karena ini new record -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-medium">Indikator</label>
                                <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label">Indikator</label>
                                    <input type="text" name="sasaran_pk[0][indikator][0][indikator]" class="form-control mb-3 border-secondary" placeholder="Contoh: Persentase tingkat kepuasan" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Target</label>
                                    <input type="text" name="sasaran_pk[0][indikator][0][target]" class="form-control mb-3 border-secondary" placeholder="Nilai target" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="add-indikator btn btn-info btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Indikator
                        </button>
                    </div>
                </div>
            `;
            
            sasaranContainer.appendChild(newSasaran);
            updateLabels();
            updateFormNames();
        }
    });

    // Tambah Indikator ke Sasaran
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-indikator') || e.target.closest('.add-indikator')) {
            const indikatorSection = e.target.closest('.indikator-section');
            const indikatorContainer = indikatorSection.querySelector('.indikator-container');
            
            const newIndikator = document.createElement('div');
            newIndikator.className = 'indikator-item border rounded p-3 bg-light mb-3';
            
            // Use template with placeholder indices that will be updated by updateFormNames()
            newIndikator.innerHTML = `
                <!-- Tidak ada hidden ID field karena ini new record -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator</label>
                    <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label">Indikator</label>
                        <input type="text" name="sasaran_pk[0][indikator][0][indikator]" class="form-control mb-3 border-secondary" placeholder="Masukkan indikator" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <input type="text" name="sasaran_pk[0][indikator][0][target]" class="form-control mb-3 border-secondary" placeholder="Nilai target" required>
                    </div>
                </div>
            `;
            
            indikatorContainer.appendChild(newIndikator);
            updateLabels();
            updateFormNames();
        }
    });

    // Tambah Program
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-program') || e.target.closest('.add-program')) {
            const programContainer = document.querySelector('.program-container');
            
            // Get existing program select options
            const existingProgramSelect = document.querySelector('.program-select');
            const programOptions = existingProgramSelect ? existingProgramSelect.innerHTML : '<option value="">Tidak ada program tersedia</option>';
            
            const newProgram = document.createElement('div');
            newProgram.className = 'program-item border border-secondary rounded p-3 bg-white mb-3';
            
            // Use template with placeholder indices that will be updated by updateFormNames()
            newProgram.innerHTML = `
                <!-- Tidak ada hidden ID field karena ini new record -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Program</label>
                    <button type="button" class="remove-program btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <label class="form-label">Program</label>
                            <select name="program[0][program_id]" class="form-select program-select mb-3" required>
                                ${programOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran</label>
                            <input type="text" name="program[0][anggaran]" class="form-control mb-3 border-secondary" placeholder="Anggaran" required readonly>
                        </div>
                    </div>
                </div>
            `;
            
            programContainer.appendChild(newProgram);
            updateLabels();
            updateFormNames();
        }
    });

    // ============ REMOVE FUNCTIONS ============

    // Event delegation untuk semua tombol hapus
    document.addEventListener('click', function(e) {
        // Hapus Sasaran
        if (e.target.classList.contains('remove-sasaran') || e.target.closest('.remove-sasaran')) {
            const sasaranContainer = document.querySelector('.sasaran-container');
            if (sasaranContainer.children.length > 1) {
                if (confirm('Yakin ingin menghapus sasaran ini?')) {
                    e.target.closest('.sasaran-item').remove();
                    updateLabels();
                    updateFormNames();
                }
            } else {
                alert('Minimal harus ada 1 sasaran!');
            }
        }

        // Hapus Indikator
        if (e.target.classList.contains('remove-indikator') || e.target.closest('.remove-indikator')) {
            const indikatorContainer = e.target.closest('.indikator-container');
            if (indikatorContainer.children.length > 1) {
                if (confirm('Yakin ingin menghapus indikator ini?')) {
                    e.target.closest('.indikator-item').remove();
                    updateLabels();
                    updateFormNames();
                }
            } else {
                alert('Minimal harus ada 1 indikator!');
            }
        }

        // Hapus Program
        if (e.target.classList.contains('remove-program') || e.target.closest('.remove-program')) {
            const programContainer = document.querySelector('.program-container');
            if (programContainer.children.length > 1) {
                if (confirm('Yakin ingin menghapus program ini?')) {
                    e.target.closest('.program-item').remove();
                    updateLabels();
                    updateFormNames();
                }
            } else {
                alert('Minimal harus ada 1 program!');
            }
        }
    });

    // ============ AUTO-FILL FUNCTIONS ============

    // Auto-fill NIP saat memilih pegawai (gunakan event delegation)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('pegawai-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const nip = selectedOption.getAttribute('data-nip');
            const targetName = e.target.getAttribute('data-target');
            const nipInput = document.querySelector(`input[name="${targetName}"]`);

            if (nipInput) {
                nipInput.value = nip || '';
            }
        }

        // Auto-fill anggaran saat memilih program (gunakan event delegation untuk element dinamis)
        if (e.target.classList.contains('program-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const anggaranInput = e.target.closest('.row').querySelector('input[name*="anggaran"]');
            
            if (selectedOption.value !== '') {
                const anggaran = selectedOption.getAttribute('data-anggaran');
                anggaranInput.value = formatRupiah(anggaran);
            } else {
                anggaranInput.value = '';
            }
        }
    });

    // Format Rupiah
    function formatRupiah(angka) {
        if (!angka) return '';
        return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
    }

    // Auto-fill untuk element yang sudah ada saat halaman dimuat
    setTimeout(function() {
        // Auto-fill NIP untuk pegawai yang sudah terpilih
        document.querySelectorAll('.pegawai-select').forEach(function(select) {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const nip = selectedOption.getAttribute('data-nip');
                const targetName = select.getAttribute('data-target');
                const nipInput = document.querySelector(`input[name="${targetName}"]`);

                if (nipInput && nip && !nipInput.value) {
                    nipInput.value = nip;
                }
            }
        });

        // Auto-fill anggaran untuk program yang sudah terpilih
        document.querySelectorAll('.program-select').forEach(function(select) {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const anggaran = selectedOption.getAttribute('data-anggaran');
                const anggaranInput = select.closest('.row').querySelector('input[name*="anggaran"]');
                
                if (anggaranInput && anggaran && !anggaranInput.value) {
                    anggaranInput.value = formatRupiah(anggaran);
                }
            }
        });
    }, 100); // Small delay untuk memastikan DOM sudah ready
});
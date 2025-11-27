/**
 * PK Form Handler
 * Script untuk mengelola form dinamis PK (Sasaran, Indikator, Program) pada halaman input/edit PK.
 * - Penambahan dan penghapusan field dinamis
 * - Auto-fill NIP dan Anggaran
 * - Update nama input agar sesuai struktur array PHP
 * - Format Rupiah
 */
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan updateFormNames dijalankan sebelum submit form
    const pkForm = document.getElementById('pk-form');
    if (pkForm) {
        pkForm.addEventListener('submit', function(e) {
            updateFormNames();
        });
    }
    /**
 * Helper: Update semua nama input agar sesuai struktur array PHP
 */
function updateFormNames() {
    // Sasaran dan Indikator
    document.querySelectorAll('.sasaran-container .sasaran-item').forEach((sasaranItem, sasaranIndex) => {
        const sasaranTextarea = sasaranItem.querySelector('textarea[name*="[sasaran]"]');
        if (sasaranTextarea) {
            sasaranTextarea.name = `sasaran_pk[${sasaranIndex}][sasaran]`;
        }
        
        const indikatorItems = sasaranItem.querySelectorAll('.indikator-item');
        indikatorItems.forEach((indikator, indikatorIndex) => {
            // Update input indikator
            const indikatorInput = indikator.querySelector('input[name*="[indikator]"]');
            const targetInput = indikator.querySelector('input[name*="[target]"]');
            const satuanSelect = indikator.querySelector('select[name*="[id_satuan]"]');
            const jenisSelect = indikator.querySelector('select[name*="[jenis_indikator]"]');
            
            if (indikatorInput) {
                indikatorInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][indikator]`;
            }
            if (targetInput) {
                targetInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][target]`;
            }
            if (satuanSelect) {
                satuanSelect.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][id_satuan]`;
            }
            if (jenisSelect) {
                jenisSelect.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][jenis_indikator]`;
            }
            
            // Update program untuk setiap indikator
            const programItems = indikator.querySelectorAll('.program-item');
            programItems.forEach((program, programIndex) => {
                const programSelect = program.querySelector('select[name*="[program_id]"]');
                const anggaranInput = program.querySelector('input[name*="[anggaran]"]');
                
                if (programSelect) {
                    programSelect.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][program][${programIndex}][program_id]`;
                }
                if (anggaranInput) {
                    anggaranInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][program][${programIndex}][anggaran]`;
                }
            });

            // Update kegiatan untuk setiap program
            programItems.forEach((program, programIndex) => {
                const kegiatanItems = program.querySelectorAll('.kegiatan-item');
                kegiatanItems.forEach((kegiatan, kegiatanIndex) => {
                    const kegiatanSelect = kegiatan.querySelector('select[name*="[kegiatan_id]"]');
                    if (kegiatanSelect) {
                        kegiatanSelect.name =
                            `sasaran_pk[${sasaranIndex}]`
                            + `[indikator][${indikatorIndex}]`
                            + `[program][${programIndex}]`
                            + `[kegiatan][${kegiatanIndex}][kegiatan_id]`;
                    }
                });
            });
        });
    });
}
    /**
     * Tambah Sasaran Baru
     * @event click .add-sasaran
     */
    document.querySelector('.add-sasaran').addEventListener('click', function() {
        const sasaranContainer = document.querySelector('.sasaran-container');
        const sasaranCount = sasaranContainer.querySelectorAll('.sasaran-item').length + 1;
        const newSasaran = document.createElement('div');
        newSasaran.className = 'sasaran-item border border-secondary rounded p-3 bg-white mb-3';
        newSasaran.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium h5">Sasaran ${sasaranCount}</label>
                <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            <div class="mb-3">
                <label class="form-label">Sasaran PK</label>
                <textarea name="sasaran_pk[0][sasaran]" class="form-control border-secondary" rows="2" placeholder="Contoh: Terwujudnya pelayanan publik yang prima" required></textarea>
            </div>
            <div class="indikator-section">
                <div class="indikator-container">
                    <div class="indikator-item border rounded p-3 bg-light mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium">Indikator</label>
                            <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label">Indikator</label>
                                <input type="text" name="sasaran_pk[0][indikator][0][indikator]" class="form-control mb-3 border-secondary" placeholder="Contoh: Persentase tingkat kepuasan" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Target</label>
                                <input type="text" name="sasaran_pk[0][indikator][0][target]" class="form-control mb-3 border-secondary" placeholder="Nilai target" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <select name="sasaran_pk[0][indikator][0][id_satuan]" class="form-select mb-3 border-secondary" required>
                                    ${window.satuanDropdownTemplate}
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jenis Indikator</label>
                                <select name="sasaran_pk[0][indikator][0][jenis_indikator]"
                                    class="form-select mb-3 border-secondary" required>
                                    <option value="">Pilih Jenis Indikator</option>
                                    <option value="Indikator Positif">Indikator Positif</option>
                                    <option value="Indikator Negatif">Indikator Negatif</option>
                                </select>
                            </div>
                        </div>
                        <div class="kegiatan-container">
                            <div class="kegiatan-item border rounded p-3 bg-white mb-3">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Kegiatan</label>
                                        <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][kegiatan_id]" class="form-select kegiatan-select border-secondary" required>
                                            ${window.kegiatanAdminDropdownTemplate}
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            
                                <div class="subkeg-container">
                                    <div class="subkeg-item border rounded bg-light p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Sub Kegiatan</label>
                                                <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][subkegiatan_id]" class="form-select subkeg-select border-secondary" required>
                                                    ${window.subkegiatanDropdownTemplate || ''}
                                                </select>
                                            </div>

                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="button"
                                                    class="remove-subkeg btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button"
                                        class="add-subkeg btn btn-success btn-sm">
                                        <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="add-kegiatan btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                            </button>
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
        updateFormNames();
    });

    /**
     * Tambah Indikator ke Sasaran
     * @event click .add-indikator
     */
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-indikator') || e.target.closest('.add-indikator')) {
            const indikatorSection = e.target.closest('.indikator-section');
            if (!indikatorSection) return;
            const indikatorContainer = indikatorSection.querySelector('.indikator-container');
            if (!indikatorContainer) return;
            const indikatorCount = indikatorContainer.querySelectorAll('.indikator-item').length + 1;
            const newIndikator = document.createElement('div');
            newIndikator.className = 'indikator-item border rounded p-3 bg-light mb-3';
            newIndikator.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium">Indikator ${indikatorCount}</label>
                    <button type="button" class="remove-indikator btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Indikator</label>
                        <input type="text" name="sasaran_pk[0][indikator][0][indikator]" class="form-control mb-3 border-secondary" placeholder="Masukkan indikator" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Target</label>
                        <input type="text" name="sasaran_pk[0][indikator][0][target]" class="form-control mb-3 border-secondary" placeholder="Nilai target" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select name="sasaran_pk[0][indikator][0][id_satuan]" class="form-select mb-3 border-secondary" required>
                            ${window.satuanDropdownTemplate}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Indikator</label>
                        <select name="sasaran_pk[0][indikator][0][jenis_indikator]"
                            class="form-select mb-3 border-secondary" required>
                            <option value="">Pilih Jenis Indikator</option>
                            <option value="Indikator Positif">Indikator Positif</option>
                            <option value="Indikator Negatif">Indikator Negatif</option>
                        </select>
                    </div>
                </div>

                <div class="kegiatan-container">
                    <div class="kegiatan-item border rounded p-3 bg-white mb-3">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kegiatan</label>
                                <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][kegiatan_id]" class="form-select kegiatan-select border-secondary" required>
                                    ${window.kegiatanAdminDropdownTemplate}
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    
                        <div class="subkeg-container">
                            <div class="subkeg-item border rounded bg-light p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Sub Kegiatan</label>
                                        <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][subkegiatan_id]" class="form-select subkeg-select border-secondary" required>
                                            ${window.subkegiatanDropdownTemplate || ''}
                                        </select>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button"
                                            class="remove-subkeg btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button"
                                class="add-subkeg btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="add-kegiatan btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                    </button>
                </div>
            `;
            indikatorContainer.appendChild(newIndikator);
            updateFormNames();
        }
    });

    // Tambah Kegiatan pada setiap indikator template baru
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-kegiatan') || e.target.closest('.add-kegiatan')) {
            const indikatorItem = e.target.closest('.indikator-item');
            if (!indikatorItem) return;
            const kegiatanContainer = indikatorItem.querySelector('.kegiatan-container');
            if (!kegiatanContainer) return;
            const kegiatanCount = kegiatanContainer.querySelectorAll('.kegiatan-item').length + 1;
            // Buat template kegiatan-item baru
            const newKegiatan = document.createElement('div');
            newKegiatan.className = 'kegiatan-item border rounded p-3 bg-white mb-4';
            newKegiatan.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Kegiatan</label>
                    <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][kegiatan_id]" class="form-select kegiatan-select border-secondary" required>
                        ${window.kegiatanAdminDropdownTemplate}
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="remove-kegiatan btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        
            <div class="subkeg-container">
                <div class="subkeg-item border rounded bg-light p-3 mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Sub Kegiatan</label>
                            <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][subkegiatan_id]" class="form-select subkeg-select border-secondary" required>
                                ${window.subkegiatanDropdownTemplate || ''}
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button"
                                class="remove-subkeg btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="button"
                    class="add-subkeg btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                </button>
            </div>
            `;
            kegiatanContainer.appendChild(newKegiatan);
            updateFormNames();
        }
    });

    // Tambah Subkegiatan pada setiap kegiatan baru
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-subkeg') || e.target.closest('.add-subkeg')) {
            const kegiatanItem = e.target.closest('.kegiatan-item');
            if (!kegiatanItem) return;
            const subkegContainer = kegiatanItem.querySelector('.subkeg-container');
            if (!subkegContainer) return;
            const kegiatanCount = subkegContainer.querySelectorAll('.subkeg-item').length + 1;
            
            const newSubkeg = document.createElement('div');
            newSubkeg.className = 'subkeg-item border rounded bg-light p-3';
            newSubkeg.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Sub Kegiatan</label>
                    <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][subkegiatan_id]" class="form-select subkeg-select border-secondary" required>
                        ${window.subkegiatanDropdownTemplate || ''}
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="button"
                        class="remove-subkeg btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            `;
            subkegContainer.appendChild(newSubkeg);
            updateFormNames();
        }
    });

    /**
     * Event Delegation: Hapus Sasaran, Indikator, Program, kegiatan
     * @event click .remove-sasaran, .remove-indikator, .remove-program, .remove-kegiatan
     */
    document.addEventListener('click', function(e) {
        // Hapus Sasaran
        if (e.target.classList.contains('remove-sasaran') || e.target.closest('.remove-sasaran')) {
            const sasaranContainer = document.querySelector('.sasaran-container');
            if (sasaranContainer.children.length > 1) {
                if (confirm('Yakin ingin menghapus sasaran ini?')) {
                    e.target.closest('.sasaran-item').remove();
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
                    updateFormNames();
                }
            } else {
                alert('Minimal harus ada 1 indikator!');
            }
        }
        
        // Hapus Kegiatan pada setiap indikator
        if (e.target.classList.contains('remove-kegiatan') || e.target.closest('.remove-kegiatan')) {
            const indikatorItem = e.target.closest('.indikator-item');
            if (!indikatorItem) return;
            const programContainer = indikatorItem.querySelector('.kegiatan-container');
            if (!programContainer) return;
            const programItems = programContainer.querySelectorAll('.kegiatan-item');
            if (programItems.length > 1) {
                if (confirm('Yakin ingin menghapus kegiatan ini?')) {
                    e.target.closest('.kegiatan-item').remove();
                    updateFormNames();
                }
            } else {
                // Jika hanya satu, kosongkan saja
                const firstProgram = programContainer.querySelector('.kegiatan-item');
                if (firstProgram) {
                    firstProgram.querySelectorAll('input, select').forEach(el => {
                        if (el.tagName === 'INPUT') el.value = '';
                        if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    });
                }
            }
        }

        // Hapus subkegiatan
        if (e.target.classList.contains('remove-subkeg') || e.target.closest('.remove-subkeg')) {
            const subContainer = e.target.closest('.subkeg-container');
            const subItem = e.target.closest('.subkeg-item');

            if (subContainer.querySelectorAll('.subkeg-item').length > 1) {
                subItem.remove();
                updateFormNames();
            } else {
                // Jika hanya satu subkegiatan, kosongkan nilai
                subItem.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
            }
        }


    });
    // Tambah dan hapus program pada setiap indikator
    document.addEventListener('DOMContentLoaded', function () {
    // Handler tambah program
    document.body.addEventListener('click', function (e) {
        if (e.target.classList.contains('add-kegiatan') || e.target.closest('.add-kegiatan')) {
        const indikatorItem = e.target.closest('.indikator-item');
        if (!indikatorItem) return;
        
        const kegiatanContainer = indikatorItem.querySelector('.kegiatan-container');
        if (!kegiatanContainer) return;
        
        // Clone program item pertama sebagai template
        const template = kegiatanContainer.querySelector('.kegiatan-item').cloneNode(true);
        
        // Kosongkan nilai
        template.querySelector('select').selectedIndex = 0;
        template.querySelector('input').value = '';
        
        // Tambahkan program baru
        kegiatanContainer.appendChild(template);
        
        // Update nama form
        updateFormNames();
    }
        // Handler hapus kegiatan
        if (e.target.classList.contains('remove-kegiatan') || e.target.closest('.remove-kegiatan')) {
        const kegiatanItem = e.target.closest('.kegiatan-item');
        const kegiatanContainer = kegiatanItem.closest('.kegiatan-container');
        
        if (kegiatanContainer.querySelectorAll('.kegiatan-item').length > 1) {
            kegiatanItem.remove();
            updateFormNames();
        } else {
            // Jika hanya satu kegiatan, kosongkan nilai
            kegiatanItem.querySelector('select').selectedIndex = 0;
            kegiatanItem.querySelector('input').value = '';
        }
    }
    });
});

    /**
     * Auto-fill NIP saat memilih pegawai
     * @event change .pegawai-select
     */
    const pegawaiSelects = document.querySelectorAll('.pegawai-select');
    pegawaiSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const nip = selectedOption.getAttribute('data-nip');
            const targetName = this.getAttribute('data-target');
            const nipInput = document.querySelector(`input[name="${targetName}"]`);
            if (nipInput) {
                nipInput.value = nip || '';
            }
        });
    });

        /**
         * Auto-fill anggaran saat memilih program (event delegation, per program-item)
         */
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('kegiatan-select')) {
                // Cari program-item terdekat
                const kegiatanItem = e.target.closest('.kegiatan-item');
                if (!kegiatanItem) return;
                const anggaranInput = kegiatanItem.querySelector('input[name*="anggaran"]');
                const selectedOption = e.target.options[e.target.selectedIndex];
                if (selectedOption && selectedOption.value !== '') {
                    const anggaran = selectedOption.getAttribute('data-anggaran');
                    anggaranInput.value = formatRupiah(anggaran);
                } else {
                    anggaranInput.value = '';
                }
            }
        });

    // Inisialisasi auto-fill untuk elemen yang sudah ada
    document.querySelectorAll('.kegiatan-select').forEach(select => {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.value !== '') {
        const anggaran = selectedOption.getAttribute('data-anggaran');
        const parentItem = select.closest('.indikator-item') || select.closest('.kegiatan-item');
        const anggaranInput = parentItem.querySelector('input[name*="anggaran"]');
        anggaranInput.value = formatRupiah(anggaran);
    }
    });



    /**
     * Helper: Format angka ke Rupiah
     * @param {string|number} angka
     * @returns {string}
     */
    function formatRupiah(angka) {
        return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
    }
    });

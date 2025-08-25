
/**
 * PK Form Handler
 * Script untuk mengelola form dinamis PK (Sasaran, Indikator, Program) pada halaman input/edit PK.
 * - Penambahan dan penghapusan field dinamis
 * - Auto-fill NIP dan Anggaran
 * - Update nama input agar sesuai struktur array PHP
 * - Format Rupiah
 */
document.addEventListener('DOMContentLoaded', function() {

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
                const indikatorInput = indikator.querySelector('input[name*="[indikator]"]');
                const targetInput = indikator.querySelector('input[name*="[target]"]');
                const satuanSelect = indikator.querySelector('select[name*="[id_satuan]"]');
                if (indikatorInput) {
                    indikatorInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][indikator]`;
                }
                if (targetInput) {
                    targetInput.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][target]`;
                }
                if (satuanSelect) {
                    satuanSelect.name = `sasaran_pk[${sasaranIndex}][indikator][${indikatorIndex}][id_satuan]`;
                }
            });
        });
        // Program
        document.querySelectorAll('.program-container .program-item').forEach((program, index) => {
            const programSelect = program.querySelector('select[name*="program_id"]');
            const anggaranInput = program.querySelector('input[name*="anggaran"]');
            if (programSelect) {
                programSelect.name = `program[${index}][program_id]`;
            }
            if (anggaranInput) {
                anggaranInput.name = `program[${index}][anggaran]`;
            }
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
            const indikatorContainer = e.target.closest('.indikator-section').querySelector('.indikator-container');
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
            `;
            indikatorContainer.appendChild(newIndikator);
            updateFormNames();
        }
    });

    /**
     * Tambah Program Baru
     * @event click .add-program
     */
    document.querySelector('.add-program').addEventListener('click', function() {
        const programContainer = document.querySelector('.program-container');
        const programCount = programContainer.querySelectorAll('.program-item').length + 1;
        const newProgram = document.createElement('div');
        newProgram.className = 'program-item border border-secondary rounded p-3 bg-white mb-3';
        newProgram.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="fw-medium">Program ${programCount}</label>
                <button type="button" class="remove-program btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </div>
            <div class="mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <label class="form-label">Program</label>
                        <select name="program[0][program_id]" class="form-select program-select mb-3" required>
                            ${document.querySelector('.program-select').innerHTML}
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
        updateFormNames();
    });

    /**
     * Event Delegation: Hapus Sasaran, Indikator, Program
     * @event click .remove-sasaran, .remove-indikator, .remove-program
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
        // Hapus Program
        if (e.target.classList.contains('remove-program') || e.target.closest('.remove-program')) {
            const programContainer = document.querySelector('.program-container');
            if (programContainer.children.length > 1) {
                if (confirm('Yakin ingin menghapus program ini?')) {
                    e.target.closest('.program-item').remove();
                    updateFormNames();
                }
            } else {
                alert('Minimal harus ada 1 program!');
            }
        }
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
     * Auto-fill anggaran saat memilih program
     * @event change .program-select
     */
    document.addEventListener('change', function(e) {
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

    /**
     * Helper: Format angka ke Rupiah
     * @param {string|number} angka
     * @returns {string}
     */
    function formatRupiah(angka) {
        return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
    }
});

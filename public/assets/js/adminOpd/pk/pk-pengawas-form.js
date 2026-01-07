/**
 * PK PENGAWAS FORM HANDLER
 * Compatible with current HTML structure
 * SAFE for dynamic add/remove
 */

(function () {

    /* ===============================
     * 1. UPDATE NAME ATTRIBUTES
     * =============================== */
    function updateFormNames() {

        document.querySelectorAll('.sasaran-container .sasaran-item')
            .forEach((sasaranItem, sIdx) => {

                const sasaranTextarea = sasaranItem.querySelector('textarea');
                if (sasaranTextarea) {
                    sasaranTextarea.name = `sasaran_pk[${sIdx}][sasaran]`;
                }

                sasaranItem.querySelectorAll('.indikator-item')
                    .forEach((indikatorItem, iIdx) => {

                        indikatorItem.querySelectorAll('input, select')
                            .forEach(el => {

                                if (el.classList.contains('indikator-input')) {
                                    el.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][indikator]`;
                                }

                                if (el.classList.contains('target-input')) {
                                    el.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][target]`;
                                }

                                if (el.classList.contains('satuan-select')) {
                                    el.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][id_satuan]`;
                                }

                                if (el.classList.contains('jenis-indikator-select')) {
                                    el.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][jenis_indikator]`;
                                }
                            });

                        indikatorItem.querySelectorAll('.kegiatan-item')
                            .forEach((kegiatanItem, kIdx) => {

                                const programHidden = kegiatanItem.querySelector('.program-id-hidden');
                                if (programHidden) {
                                    programHidden.name =
                                        `sasaran_pk[${sIdx}][indikator][${iIdx}][program][${kIdx}][program_id]`;
                                }

                                const kegiatanSelect = kegiatanItem.querySelector('.kegiatan-select');
                                if (kegiatanSelect) {
                                    kegiatanSelect.name =
                                        `sasaran_pk[${sIdx}][indikator][${iIdx}][program][${kIdx}][kegiatan][0][kegiatan_id]`;
                                }

                                kegiatanItem.querySelectorAll('.subkeg-item')
                                    .forEach((subItem, subIdx) => {

                                        const subSelect = subItem.querySelector('.subkeg-select');
                                        if (subSelect) {
                                            subSelect.name =
                                                `sasaran_pk[${sIdx}][indikator][${iIdx}][program][${kIdx}][kegiatan][0][subkegiatan][${subIdx}][subkegiatan_id]`;
                                        }
                                    });
                            });
                    });
            });
    }

    /* ===============================
     * 2. SET PROGRAM ID FROM KEGIATAN
     * =============================== */
    $(document).on('change', '.kegiatan-select', function () {
        const programId = $(this).find(':selected').data('program') || '';
        $(this).closest('.kegiatan-item')
            .find('.program-id-hidden')
            .val(programId);
    });

    /**
     * Tambah Sasaran Baru
     * @event click .add-sasaran
     */
    document.querySelector('.add-sasaran').addEventListener('click', function () {
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
                            <div class="kegiatan-item border rounded p-3 bg-white mb-4">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Kegiatan</label>

                                        <input type="hidden" name="sasaran_pk[0][indikator][0][program][0][program_id]" class="program-id-hidden">
                                        <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][kegiatan_id]" class="form-select kegiatan-select border-secondary" required>
                                            ${window.kegiatanAdminDropdownTemplate}
                                        </select>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button"
                                            class="remove-kegiatan btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="subkeg-container">
                                    <div class="subkeg-item border rounded bg-light p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Sub Kegiatan</label>
                                                <select
                                                    name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][subkegiatan_id]"
                                                    class="form-select subkeg-select border-secondary"
                                                    required>
                                                    ${window.subkegiatanDropdownTemplate || ''}
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Anggaran</label>
                                                <input type="text"
                                                    name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][anggaran]"
                                                    class="form-control mb-3 border-secondary" value=""
                                                    placeholder="Anggaran" readonly>
                                                <input type="hidden" name="kegiatan[0][id_indikator]"
                                                value="">
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
                            <button type="button" class="add-kegiatan btn btn-primary btn-sm">
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
    document.addEventListener('click', function (e) {
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
                                <input type="hidden" name="sasaran_pk[0][indikator][0][program][0][program_id]" class="program-id-hidden">
                                <select name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][kegiatan_id]" class="form-select kegiatan-select border-secondary" required>
                                    ${window.kegiatanAdminDropdownTemplate}
                                </select>
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button"
                                    class="remove-kegiatan btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="subkeg-container">
                            <div class="subkeg-item border rounded bg-light p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Sub Kegiatan</label>
                                        <select
                                            name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][subkegiatan_id]"
                                            class="form-select subkeg-select border-secondary"
                                            required>
                                            ${window.subkegiatanDropdownTemplate || ''}
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Anggaran</label>
                                        <input type="text"
                                            name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][anggaran]"
                                            class="form-control mb-3 border-secondary" value=""
                                            placeholder="Anggaran" readonly>
                                        <input type="hidden" name="kegiatan[0][id_indikator]"
                                        value="">
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
    document.addEventListener('click', function (e) {
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
                    <input type="hidden" name="sasaran_pk[0][indikator][0][program][0][program_id]" class="program-id-hidden">
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
                        <div class="col-md-3">
                            <label class="form-label">Anggaran</label>
                            <input type="text"
                                name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][anggaran]"
                                class="form-control mb-3 border-secondary" value=""
                                placeholder="Anggaran" readonly>
                            <input type="hidden" name="kegiatan[0][id_indikator]"
                            value="">
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
    document.addEventListener('click', function (e) {
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
                <div class="col-md-3">
                    <label class="form-label">Anggaran</label>
                    <input type="text"
                        name="sasaran_pk[0][indikator][0][program][0][kegiatan][0][subkegiatan][0][anggaran]"
                        class="form-control mb-3 border-secondary" value=""
                        placeholder="Anggaran" readonly>
                    
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

    $(document).on(
        'click',
        '.remove-sasaran, .remove-indikator, .remove-kegiatan, .remove-subkeg',
        function () {

            const btn = $(this);

            let item = null;
            let selector = null;

            if (btn.hasClass('remove-sasaran')) {
                item = btn.closest('.sasaran-item');
                selector = '.sasaran-item';
            }

            if (btn.hasClass('remove-indikator')) {
                item = btn.closest('.indikator-item');
                selector = '.indikator-item';
            }

            if (btn.hasClass('remove-kegiatan')) {
                item = btn.closest('.kegiatan-item');
                selector = '.kegiatan-item';
            }

            if (btn.hasClass('remove-subkeg')) {
                item = btn.closest('.subkeg-item');
                selector = '.subkeg-item';
            }

            if (!item || !selector) return;

            const siblings = item.siblings(selector);

            // jika tinggal satu → reset saja
            if (siblings.length === 0) {
                item.find('input, textarea').val('');
                item.find('select').prop('selectedIndex', 0);
                return;
            }

            if (confirm('Yakin ingin menghapus data ini?')) {
                item.remove();
                updateFormNames();
            }
        }
    );


    /* ===============================
     * 5. SUBMIT SAFETY
     * =============================== */
    $('#pk-form').on('submit', function () {
        updateFormNames();
    });

    // AUTO-FILL ANGGARAN (EVENT DELEGATION - WAJIB UNTUK ELEMEN DINAMIS)
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('subkeg-select')) {
            // Cari program-item terdekat
            const subkegItem = e.target.closest('.subkeg-item');
            if (!subkegItem) return;
            const anggaranInput = subkegItem.querySelector('input[name*="anggaran"]');
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
    document.querySelectorAll('.subkeg-select').forEach(select => {
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value !== '') {
            const anggaran = selectedOption.getAttribute('data-anggaran');
            const parentItem = select.closest('.kegiatan-item') || select.closest('.subkeg-item');
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

})();
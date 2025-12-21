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

    /* ===============================
     * 3. ADD HANDLERS
     * =============================== */

    // ADD SASARAN
    $(document).on('click', '.add-sasaran', function () {
        const container = $('.sasaran-container');
        const clone = container.find('.sasaran-item').first().clone(true);

        clone.find('input, textarea').val('');
        clone.find('select').prop('selectedIndex', 0);

        container.append(clone);
        updateFormNames();
    });

    // ADD INDIKATOR
    $(document).on('click', '.add-indikator', function () {
        const indikatorContainer = $(this)
            .closest('.indikator-section')
            .find('.indikator-container');

        const clone = indikatorContainer.find('.indikator-item').first().clone(true);

        clone.find('input').val('');
        clone.find('select').prop('selectedIndex', 0);

        indikatorContainer.append(clone);
        updateFormNames();
    });

    // ADD KEGIATAN
    $(document).on('click', '.add-kegiatan', function () {
        const indikatorItem = $(this).closest('.indikator-item');
        const kegiatanContainer = indikatorItem.find('.kegiatan-container');

        const clone = kegiatanContainer.find('.kegiatan-item').first().clone(true);

        clone.find('input').val('');
        clone.find('select').prop('selectedIndex', 0);

        kegiatanContainer.append(clone);
        clone.find('.kegiatan-select').trigger('change');

        updateFormNames();
    });

    // ADD SUB KEGIATAN
    $(document).on('click', '.add-subkeg', function () {
        const kegiatanItem = $(this).closest('.kegiatan-item');
        const subContainer = kegiatanItem.find('.subkeg-container');

        const clone = subContainer.find('.subkeg-item').first().clone(true);
        clone.find('select').prop('selectedIndex', 0);

        subContainer.append(clone);
        updateFormNames();
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

    // AUTO-FILL NIP (EVENT DELEGATION - WAJIB UNTUK ELEMEN DINAMIS)
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('pegawai-select')) {

        const selectedOption = e.target.options[e.target.selectedIndex];
        if (!selectedOption) return;

        const nip = selectedOption.getAttribute('data-nip') || '';
        const targetName = e.target.getAttribute('data-target');

        if (!targetName) return;

        const nipInput = document.querySelector(`input[name="${targetName}"]`);
        if (nipInput) {
            nipInput.value = nip;
        }
    }
});
})();

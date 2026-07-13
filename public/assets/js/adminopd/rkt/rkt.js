/**
 * RKT dynamic form handler for tambah/edit.
 */
document.addEventListener('DOMContentLoaded', () => {
    const qs = (selector, context = document) => context.querySelector(selector);
    const qsa = (selector, context = document) => Array.from(context.querySelectorAll(selector));
    const form = document.getElementById('renja-form');

    if (!form) {
        return;
    }

    function formatRupiahNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        const number = parseInt(String(value).replace(/[^0-9-]/g, ''), 10);
        if (Number.isNaN(number)) {
            return '';
        }

        return 'Rp ' + number.toLocaleString('id-ID');
    }

    function stripSelect2Artifacts(root) {
        qsa('.select2-container', root).forEach((el) => el.remove());
        qsa('[data-select2-id]', root).forEach((el) => el.removeAttribute('data-select2-id'));
        qsa('select.select2-hidden-accessible', root).forEach((select) => {
            select.classList.remove('select2-hidden-accessible');
            select.removeAttribute('data-select2-id');
            select.removeAttribute('aria-hidden');
            select.removeAttribute('tabindex');
        });
    }

    function initSelect2(context = document) {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }

        jQuery(context).find('select.select2').each(function () {
            const $select = jQuery(this);

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.next('.select2-container').remove();
            $select.select2({
                width: '100%',
                minimumResultsForSearch: 0,
                dropdownParent: $select.parent(),
            });
        });
    }

    function clearFormControls(root) {
        stripSelect2Artifacts(root);

        qsa('input, textarea, select', root).forEach((el) => {
            const tag = el.tagName.toLowerCase();

            if (tag === 'select') {
                el.selectedIndex = 0;
                return;
            }

            el.value = '';
        });
    }

    function cloneTemplate(selector) {
        const source = qs(selector, form);
        if (!source) {
            return null;
        }

        const clone = source.cloneNode(true);
        stripSelect2Artifacts(clone);
        return clone;
    }

    const templateProgram = cloneTemplate('.program-item');
    const templateKegiatan = cloneTemplate('.kegiatan-item');
    const templateSubkeg = cloneTemplate('.subkeg-item');

    if (templateProgram) {
        qsa('.kegiatan-item', templateProgram).forEach((item, index) => {
            if (index > 0) item.remove();
        });
        qsa('.subkeg-item', templateProgram).forEach((item, index) => {
            if (index > 0) item.remove();
        });
        clearFormControls(templateProgram);
    }

    if (templateKegiatan) {
        qsa('.subkeg-item', templateKegiatan).forEach((item, index) => {
            if (index > 0) item.remove();
        });
        clearFormControls(templateKegiatan);
    }

    if (templateSubkeg) {
        clearFormControls(templateSubkeg);
    }

    function updateNameRkt() {
        qsa('.program-item', form).forEach((programItem, programIndex) => {
            const programSelect = qs('.program-select', programItem);
            if (programSelect) {
                programSelect.name = `program[${programIndex}][program_id]`;
            }

            qsa('.kegiatan-item', programItem).forEach((kegiatanItem, kegiatanIndex) => {
                const kegiatanSelect = qs('.kegiatan-select', kegiatanItem);
                if (kegiatanSelect) {
                    kegiatanSelect.name = `program[${programIndex}][kegiatan][${kegiatanIndex}][kegiatan_id]`;
                }

                qsa('.subkeg-item', kegiatanItem).forEach((subItem, subIndex) => {
                    const subSelect = qs('.subkeg-select', subItem);
                    if (subSelect) {
                        subSelect.name = `program[${programIndex}][kegiatan][${kegiatanIndex}][subkegiatan][${subIndex}][subkegiatan_id]`;
                    }

                    const anggaranSub = qs('.anggaran-input', subItem);
                    if (anggaranSub) {
                        anggaranSub.name = `program[${programIndex}][kegiatan][${kegiatanIndex}][subkegiatan][${subIndex}][anggaran]`;
                    }

                    const indikatorSubkeg = qs('.id_indikator_sasaran_sub_kegiatan_input', subItem);
                    if (indikatorSubkeg) {
                        indikatorSubkeg.name = `program[${programIndex}][kegiatan][${kegiatanIndex}][subkegiatan][${subIndex}][indikator_sasaran_sub_kegiatan]`;
                    }

                    const targetSubkeg = qs('.target_input', subItem);
                    if (targetSubkeg) {
                        targetSubkeg.name = `program[${programIndex}][kegiatan][${kegiatanIndex}][subkegiatan][${subIndex}][target]`;
                    }
                });
            });
        });
    }

    function setSubkegAnggaran(select) {
        const selected = select.options[select.selectedIndex];
        const subkegItem = select.closest('.subkeg-item');
        const anggaranInput = subkegItem ? qs('.anggaran-input', subkegItem) : null;

        if (!anggaranInput) {
            return;
        }

        anggaranInput.value = selected && selected.dataset.anggaran
            ? formatRupiahNumber(selected.dataset.anggaran)
            : '';
    }

    function appendClone(container, template) {
        if (!container || !template) {
            return;
        }

        const clone = template.cloneNode(true);
        stripSelect2Artifacts(clone);
        clearFormControls(clone);
        container.appendChild(clone);
        updateNameRkt();
        initSelect2(clone);
    }

    form.addEventListener('click', (event) => {
        const addProgram = event.target.closest('.add-program');
        const addKegiatan = event.target.closest('.add-kegiatan');
        const addSubkeg = event.target.closest('.add-subkeg');
        const removeButton = event.target.closest('.remove-program, .remove-kegiatan, .remove-subkeg');

        if (addProgram) {
            event.preventDefault();
            appendClone(qs('.program-container', form), templateProgram);
            return;
        }

        if (addKegiatan) {
            event.preventDefault();
            const programItem = addKegiatan.closest('.program-item');
            appendClone(qs('.kegiatan-container', programItem), templateKegiatan);
            return;
        }

        if (addSubkeg) {
            event.preventDefault();
            const kegiatanItem = addSubkeg.closest('.kegiatan-item');
            appendClone(qs('.subkeg-container', kegiatanItem), templateSubkeg);
            return;
        }

        if (removeButton) {
            event.preventDefault();

            const type = removeButton.classList.contains('remove-program')
                ? 'program'
                : (removeButton.classList.contains('remove-kegiatan') ? 'kegiatan' : 'subkeg');
            const item = removeButton.closest(`.${type}-item`);
            const parent = item ? item.parentElement : null;

            if (!item || !parent) {
                return;
            }

            const siblings = qsa(`:scope > .${type}-item`, parent);
            if (siblings.length > 1) {
                item.remove();
            } else {
                clearFormControls(item);
                initSelect2(item);
            }

            updateNameRkt();
        }
    });

    form.addEventListener('change', (event) => {
        if (event.target.matches('.subkeg-select')) {
            setSubkegAnggaran(event.target);
        }
    });

    updateNameRkt();
    qsa('.subkeg-select', form).forEach(setSubkegAnggaran);
    initSelect2();

    form.addEventListener('submit', () => {
        updateNameRkt();
    });
});
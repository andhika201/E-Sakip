/**
 * PK PENGAWAS FORM HANDLER (CLEAN)
 * Struktur: Sasaran → Indikator → Program(hidden) → Kegiatan → Subkegiatan
 * 100% Vanilla JS, aman untuk dynamic add/remove
 */

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('pk-form');
    if (form) {
        form.addEventListener('submit', () => {
            updateFormNames();
        });
    }

});

/* =========================================================
   ALIAS supaya script lain tidak error
========================================================= */
function updateFormNames() {
    updateFormNamesPengawas();
}

/* =========================================================
   RENAME FIELD sesuai struktur controller
========================================================= */
function updateFormNamesPengawas() {

    document.querySelectorAll('.sasaran-container .sasaran-item').forEach((sasaranItem, sIndex) => {

        const sasaran = sasaranItem.querySelector('textarea');
        if (sasaran) sasaran.name = `sasaran_pk[${sIndex}][sasaran]`;

        sasaranItem.querySelectorAll('.indikator-item').forEach((indikatorItem, iIndex) => {

            setName(indikatorItem, 'indikator-input', `sasaran_pk[${sIndex}][indikator][${iIndex}][indikator]`);
            setName(indikatorItem, 'indikator-target', `sasaran_pk[${sIndex}][indikator][${iIndex}][target]`);
            setName(indikatorItem, 'satuan-select', `sasaran_pk[${sIndex}][indikator][${iIndex}][id_satuan]`);
            setName(indikatorItem, 'jenis-indikator-select', `sasaran_pk[${sIndex}][indikator][${iIndex}][jenis_indikator]`);

            indikatorItem.querySelectorAll('.kegiatan-item').forEach((kegiatanItem, pIndex) => {

                const hidden = kegiatanItem.querySelector('.program-id-hidden');
                if (hidden) {
                    hidden.name = `sasaran_pk[${sIndex}][indikator][${iIndex}][program][${pIndex}][program_id]`;
                }

                const kegiatan = kegiatanItem.querySelector('.kegiatan-select');
                if (kegiatan) {
                    kegiatan.name = `sasaran_pk[${sIndex}][indikator][${iIndex}][program][${pIndex}][kegiatan][0][kegiatan_id]`;
                }

                kegiatanItem.querySelectorAll('.subkeg-item').forEach((subItem, skIndex) => {

                    const sub = subItem.querySelector('.subkeg-select');
                    if (sub) {
                        sub.name = `sasaran_pk[${sIndex}][indikator][${iIndex}][program][${pIndex}][kegiatan][0][subkegiatan][${skIndex}][subkegiatan_id]`;
                    }

                    const anggaran = subItem.querySelector('input[type="text"]');
                    if (anggaran) {
                        anggaran.name = `sasaran_pk[${sIndex}][indikator][${iIndex}][program][${pIndex}][kegiatan][0][subkegiatan][${skIndex}][anggaran]`;
                    }

                });

            });

        });

    });

}

function setName(scope, cls, name) {
    const el = scope.querySelector('.' + cls);
    if (el) el.name = name;
}

/* =========================================================
   AUTOFILL PROGRAM_ID dari kegiatan
========================================================= */
document.addEventListener('change', e => {
    if (e.target.classList.contains('kegiatan-select')) {
        const opt = e.target.options[e.target.selectedIndex];
        const pid = opt?.dataset.program || '';
        const wrapper = e.target.closest('.kegiatan-item');
        const hidden = wrapper.querySelector('.program-id-hidden');
        if (hidden) hidden.value = pid;
    }
});

/* =========================================================
   AUTOFILL ANGGARAN dari subkegiatan
========================================================= */
document.addEventListener('change', e => {
    if (e.target.classList.contains('subkeg-select')) {
        const opt = e.target.options[e.target.selectedIndex];
        const val = opt?.dataset.anggaran || '';
        const input = e.target.closest('.subkeg-item').querySelector('input[type="text"]');
        if (input) input.value = val ? formatRupiah(val) : '';
    }
});

/* =========================================================
   ADD & REMOVE BUTTONS
========================================================= */
document.addEventListener('click', e => {

    // Tambah Subkegiatan
    if (e.target.closest('.add-subkeg')) {
        const kegiatan = e.target.closest('.kegiatan-item');
        const container = kegiatan.querySelector('.subkeg-container');
        const tpl = container.querySelector('.subkeg-item').cloneNode(true);
        tpl.querySelector('select').selectedIndex = 0;
        tpl.querySelector('input').value = '';
        container.appendChild(tpl);
    }

    // Hapus Subkegiatan
    if (e.target.closest('.remove-subkeg')) {
        const item = e.target.closest('.subkeg-item');
        const parent = item.parentElement;
        if (parent.children.length > 1) item.remove();
    }

    // Hapus Kegiatan
    if (e.target.closest('.remove-kegiatan')) {
        const item = e.target.closest('.kegiatan-item');
        const parent = item.parentElement;
        if (parent.children.length > 1) item.remove();
    }

    // Hapus Indikator
    if (e.target.closest('.remove-indikator')) {
        const item = e.target.closest('.indikator-item');
        const parent = item.parentElement;
        if (parent.children.length > 1) item.remove();
    }

    // Hapus Sasaran
    if (e.target.closest('.remove-sasaran')) {
        const item = e.target.closest('.sasaran-item');
        const parent = item.parentElement;
        if (parent.children.length > 1) item.remove();
    }

});

/* =====================================================
   ADD SASARAN
===================================================== */
document.addEventListener('click', function (e) {
    if (e.target.closest('.add-sasaran')) {
        const container = document.querySelector('.sasaran-container');
        const tpl = container.querySelector('.sasaran-item').cloneNode(true);

        tpl.querySelectorAll('input, textarea').forEach(el => el.value = '');
        tpl.querySelectorAll('select').forEach(el => el.selectedIndex = 0);

        // reset nested: hanya 1 indikator
        tpl.querySelectorAll('.indikator-item').forEach((el, i) => {
            if (i > 0) el.remove();
        });

        container.appendChild(tpl);
        updateFormNames();
    }
});

/* =====================================================
   ADD INDIKATOR
===================================================== */
document.addEventListener('click', function (e) {
    if (e.target.closest('.add-indikator')) {
        const sasaran = e.target.closest('.sasaran-item');
        const container = sasaran.querySelector('.indikator-container');
        const tpl = container.querySelector('.indikator-item').cloneNode(true);

        tpl.querySelectorAll('input').forEach(el => el.value = '');
        tpl.querySelectorAll('select').forEach(el => el.selectedIndex = 0);

        // reset kegiatan
        tpl.querySelectorAll('.kegiatan-item').forEach((el, i) => {
            if (i > 0) el.remove();
        });

        container.appendChild(tpl);
        updateFormNames();
    }
});

/* =====================================================
   ADD KEGIATAN
===================================================== */
document.addEventListener('click', function (e) {
    if (e.target.closest('.add-kegiatan')) {
        const indikator = e.target.closest('.indikator-item');
        const container = indikator.querySelector('.kegiatan-container');
        const tpl = container.querySelector('.kegiatan-item').cloneNode(true);

        tpl.querySelectorAll('input').forEach(el => el.value = '');
        tpl.querySelectorAll('select').forEach(el => el.selectedIndex = 0);

        // reset subkegiatan
        tpl.querySelectorAll('.subkeg-item').forEach((el, i) => {
            if (i > 0) el.remove();
        });

        container.appendChild(tpl);
        updateFormNames();
    }
});


/* =========================================================
   FORMAT
========================================================= */
function formatRupiah(n) {
    return 'Rp ' + parseInt(n || 0).toLocaleString('id-ID');
}

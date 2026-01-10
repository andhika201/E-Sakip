/**
 * pk-bupati-form.js
 * FINAL – stabil untuk hosting
 * Fokus: Sasaran & Indikator (tanpa Program / Kegiatan)
 */

document.addEventListener('DOMContentLoaded', function () {

    const sasaranContainer = document.querySelector('.sasaran-container');
    if (!sasaranContainer) return;

    /* ===============================
     * UPDATE NAME ATTRIBUTES
     * =============================== */
    function updateFormNames() {
        document.querySelectorAll('.sasaran-item').forEach((sasaranItem, sIdx) => {

            const sasaranTextarea = sasaranItem.querySelector('textarea[name*="[sasaran]"]');
            if (sasaranTextarea) {
                sasaranTextarea.name = `sasaran_pk[${sIdx}][sasaran]`;
            }

            sasaranItem.querySelectorAll('.indikator-item').forEach((indikatorItem, iIdx) => {

                const indikatorInput = indikatorItem.querySelector('input[name*="[indikator]"]');
                const targetInput    = indikatorItem.querySelector('input[name*="[target]"]');
                const satuanSelect   = indikatorItem.querySelector('select[name*="[id_satuan]"]');
                const jenisSelect    = indikatorItem.querySelector('select[name*="[jenis_indikator]"]');

                if (indikatorInput)
                    indikatorInput.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][indikator]`;

                if (targetInput)
                    targetInput.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][target]`;

                if (satuanSelect)
                    satuanSelect.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][id_satuan]`;

                if (jenisSelect)
                    jenisSelect.name = `sasaran_pk[${sIdx}][indikator][${iIdx}][jenis_indikator]`;
            });
        });
    }

    updateFormNames();

    /* ===============================
     * ADD / REMOVE HANDLER (DELEGATION)
     * =============================== */
    document.addEventListener('click', function (e) {

        /* ---------- ADD SASARAN ---------- */
        if (e.target.closest('.add-sasaran')) {
            e.preventDefault();

            const template = document.querySelector('.sasaran-item');
            if (!template) return;

            const clone = template.cloneNode(true);

            clone.querySelectorAll('input, textarea').forEach(el => el.value = '');
            clone.querySelectorAll('select').forEach(el => el.selectedIndex = 0);

            sasaranContainer.appendChild(clone);
            updateFormNames();
            return;
        }

        /* ---------- ADD INDIKATOR ---------- */
        if (e.target.closest('.add-indikator')) {
            e.preventDefault();

            const indikatorSection   = e.target.closest('.indikator-section');
            const indikatorContainer = indikatorSection?.querySelector('.indikator-container');
            const template           = indikatorContainer?.querySelector('.indikator-item');

            if (!indikatorContainer || !template) return;

            const clone = template.cloneNode(true);

            clone.querySelectorAll('input').forEach(el => el.value = '');
            clone.querySelectorAll('select').forEach(el => el.selectedIndex = 0);

            indikatorContainer.appendChild(clone);
            updateFormNames();
            return;
        }

        /* ---------- REMOVE SASARAN ---------- */
        if (e.target.closest('.remove-sasaran')) {
            e.preventDefault();

            const item = e.target.closest('.sasaran-item');
            if (!item) return;

            if (sasaranContainer.querySelectorAll('.sasaran-item').length <= 1) {
                alert('Minimal harus ada 1 sasaran!');
                return;
            }

            if (confirm('Yakin ingin menghapus sasaran ini?')) {
                item.remove();
                updateFormNames();
            }
            return;
        }

        /* ---------- REMOVE INDIKATOR ---------- */
        if (e.target.closest('.remove-indikator')) {
            e.preventDefault();

            const indikatorItem      = e.target.closest('.indikator-item');
            const indikatorContainer = e.target.closest('.indikator-container');

            if (!indikatorItem || !indikatorContainer) return;

            if (indikatorContainer.querySelectorAll('.indikator-item').length <= 1) {
                alert('Minimal harus ada 1 indikator!');
                return;
            }

            if (confirm('Yakin ingin menghapus indikator ini?')) {
                indikatorItem.remove();
                updateFormNames();
            }
            return;
        }
    });

    /* ===============================
     * AUTO-FILL NIP
     * =============================== */
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('pegawai-select')) return;

        const selected = e.target.options[e.target.selectedIndex];
        const target   = e.target.dataset.target;
        if (!selected || !target) return;

        const nipInput = document.querySelector(`input[name="${target}"]`);
        if (nipInput) nipInput.value = selected.dataset.nip || '';
    });

    /* ===============================
     * BEFORE SUBMIT
     * =============================== */
    const form = document.getElementById('pk-form');
    if (form) {
        form.addEventListener('submit', function () {
            updateFormNames();
        });
    }
});

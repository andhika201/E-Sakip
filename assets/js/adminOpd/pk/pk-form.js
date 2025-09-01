// PK Form JS - e-SAKIP
// ---------------------------------------------
// 1. Misi Bupati (jenis=jpt)
document.addEventListener('DOMContentLoaded', function () {
    // Misi Bupati Checkbox
    const misiCheckboxes = document.querySelectorAll('input[name="misi_bupati_id[]"]');
    const misiContainer = document.getElementById('selected-misi-container');
    if (misiCheckboxes.length && misiContainer) {
        misiCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const selected = Array.from(misiCheckboxes)
                    .filter(c => c.checked)
                    .map(c => {
                        const label = document.querySelector('label[for="' + c.id + '"]');
                        return label ? label.textContent.trim() : '';
                    });
                misiContainer.innerHTML = selected.length ? '<b>Misi Bupati Terpilih:</b><br>' + selected.join('<br>') : '';
            });
        });
    }

    // Indikator Acuan Checkbox
    const indikatorCheckboxes = document.querySelectorAll('input[name="referensi_indikator_id[]"]');
    const indikatorContainer = document.getElementById('selected-indikator-container');
    if (indikatorCheckboxes.length && indikatorContainer) {
        indikatorCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const selected = Array.from(indikatorCheckboxes)
                    .filter(c => c.checked)
                    .map(c => {
                        const label = document.querySelector('label[for="' + c.id + '"]');
                        return label ? label.textContent.trim() : '';
                    });
                indikatorContainer.innerHTML = selected.length ? '<b>Indikator Acuan Terpilih:</b><br>' + selected.join('<br>') : '';
            });
        });
    }

    // Satuan Dropdown Template (untuk JS dinamis)
    window.satuanDropdownTemplate = `<option value="">Pilih Satuan</option>
    ${window.satuanOptions || ''}`;
    // Jika ingin menambah logic dinamis lain, tambahkan di sini

    window.addEventListener('change', function (e) {
        if (e.target.matches('.program-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            // Find anggaran input inside the same indikator-item
            let anggaranInput = null;
            let parent = e.target.closest('.indikator-item');
            if (parent) {
                anggaranInput = parent.querySelector('input[name*="[anggaran]"]');
            }
            if (anggaranInput) {
                anggaranInput.value = selectedOption ? selectedOption.dataset.anggaran : '';
            }
        }
    });
});

// ---------------------------------------------
// End PK Form JS


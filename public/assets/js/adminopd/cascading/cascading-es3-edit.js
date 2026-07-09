/**
 * Helper form EDIT Es3 (dipakai halaman edit penuh maupun modal AJAX di cascading).
 * Fungsi global agar tetap tersedia meski form dimuat via AJAX ke dalam modal.
 */

// Indikator BARU tak punya id -> disisipkan sebagai INSERT oleh updateEs3.
// Key unik "new_*" agar tidak bentrok dgn indeks indikator lama.
let es3EditNewIdx = 0;
function addIndikatorEs3Edit() {
    const key = 'new_' + (es3EditNewIdx++);
    const html = `
        <div class="indikator-es3">
            <input type="text" name="indikator[${key}][nama]" class="form-control"
                placeholder="Masukkan indikator ESS III">
            <button type="button" class="btn btn-delete btn-delete-indikator"
                data-es4-count="0" onclick="hapusIndikatorEs3(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>`;
    document.getElementById('indikator-container').insertAdjacentHTML('beforeend', html);
}

// Hapus indikator; bila masih punya Es4 anak -> konfirmasi (akan dihapus berantai saat Update).
function hapusIndikatorEs3(btn) {
    const cnt = parseInt(btn.getAttribute('data-es4-count') || '0', 10);
    if (cnt > 0) {
        const ok = confirm(
            'Indikator ini memiliki ' + cnt + ' Sasaran Eselon IV di bawahnya.\n' +
            'Menghapus indikator ini akan MENGHAPUS seluruh Es4 tersebut saat Anda menekan Update.\n\nLanjutkan?'
        );
        if (!ok) return;
    }
    btn.closest('.indikator-es3').remove();
}

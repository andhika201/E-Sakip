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
    const baris = btn.closest('.indikator-es3');
    const cnt = parseInt(btn.getAttribute('data-es4-count') || '0', 10);
    if (cnt === 0) {
        baris.remove();
        return;
    }
    // Punya anak Es4 -> dampaknya berantai, jadi dirinci lewat dialog bersama
    // (app/Views/templates/konfirmasi.php).
    Konfirmasi.hapus({
        judul: 'Hapus Indikator Eselon III',
        pesan: 'Indikator ini dikeluarkan dari formulir. Perubahan baru berlaku setelah Anda menekan Update.',
        nama: (baris.querySelector('input[type="text"], textarea') || {}).value || '',
        rincian: [cnt + ' Sasaran Eselon IV di bawahnya ikut terhapus saat Update ditekan']
    }).then(function (ya) {
        if (ya) { baris.remove(); }
    });
}

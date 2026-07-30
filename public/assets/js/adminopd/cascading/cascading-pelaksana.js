/**
 * Helper form jenjang PELAKSANA (di bawah Eselon IV / JF).
 *
 * Fungsi-fungsi ini global karena dipakai form yang dimuat ke dalam modal
 * lewat AJAX — sama seperti cascading-es3-edit.js & cascading-es4.js.
 *
 * Indikator Pelaksana dikirim sebagai `indikator[i][id]` + `indikator[i][nama]`
 * supaya id lama dipertahankan saat Update (konsisten dengan jenjang di atasnya).
 */

// Tambah baris indikator pada form EDIT Pelaksana.
// Tanpa `id` -> server memperlakukannya sebagai indikator baru.
function addIndikatorEditPelaksana() {
    const html = `
        <div class="indikator-pel">
            <input type="text" name="indikator[][nama]" class="form-control"
                placeholder="Masukkan indikator Pelaksana">
            <button type="button" class="btn btn-delete btn-delete-indikator"
                onclick="this.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>`;
    document.getElementById('indikator-container').insertAdjacentHTML('beforeend', html);
}

// Hapus baris indikator Pelaksana. Pelaksana adalah jenjang TERAKHIR,
// jadi tidak ada anak yang ikut terhapus — cukup buang barisnya.
function hapusIndikatorPelaksana(btn) {
    btn.closest('.indikator-pel').remove();
}

/* =========================================================
 * TAMBAH SASARAN PELAKSANA LAIN dari dalam form Edit.
 *
 * Menggantikan tombol "+" yang dulu ada di kolom Aksi Pelaksana pada tabel
 * Cascading: tombol itu tetap muncul walau datanya sudah ada dan berulang di
 * tiap Sasaran Pelaksana, sehingga tidak konsisten dengan Aksi ESS III/ESS IV
 * (yang hanya berisi edit + hapus). Sekarang pola Pelaksana kembar dengan
 * addSasaranBaruEs4(): sasaran sejenis ditambahkan dari dalam form,
 * dikirim sebagai `sasaran_baru[i][nama]` + `sasaran_baru[i][indikator][j][nama]`,
 * lalu digantungkan server ke INDUK yang sama (lihat updatePelaksana()).
 * =======================================================*/

let sasaranBaruIndexPel = 0;

// Label jenjang diambil dari data-label wadahnya, karena untuk role
// admin_kecamatan istilahnya "Staf Pelaksana" (lihat casc_pelaksana_label()).
function labelPelaksana() {
    const wadah = document.getElementById('sasaran-baru-container-pel');
    return (wadah && wadah.dataset.label) ? wadah.dataset.label : 'Pelaksana';
}

function addSasaranBaruPelaksana() {
    const wadah = document.getElementById('sasaran-baru-container-pel');
    if (!wadah) return;

    const label = labelPelaksana();
    const idx = sasaranBaruIndexPel;
    const html = `
        <div class="pel-group mb-3 p-3 border rounded bg-light">
            <label class="fw-bold mb-2">Sasaran ${label} (Baru)</label>
            <input type="text" name="sasaran_baru[${idx}][nama]" class="form-control mb-2"
                placeholder="Masukkan Sasaran ${label}" required>
            <div class="indikator-container" id="indikator-baru-pel-${idx}"></div>
            <div class="mt-2 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="addIndikatorBaruPelaksana(${idx})">
                    + Tambah Indikator ${label}
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="this.closest('.pel-group').remove()">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        </div>`;
    wadah.insertAdjacentHTML('beforeend', html);
    sasaranBaruIndexPel++;
}

function addIndikatorBaruPelaksana(idx) {
    const indIdx = Date.now();
    const html = `
        <div class="indikator-pel d-flex gap-2 mt-2">
            <input type="text" name="sasaran_baru[${idx}][indikator][${indIdx}][nama]" class="form-control"
                placeholder="Masukkan indikator ${labelPelaksana()}">
            <button type="button" class="btn btn-delete btn-delete-indikator"
                onclick="this.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>`;
    const wadah = document.getElementById(`indikator-baru-pel-${idx}`);
    if (wadah) wadah.insertAdjacentHTML('beforeend', html);
}

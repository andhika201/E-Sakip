let es4Index = 0;

function addEs4() {

    let html = `
        <div class="es3-group">

        <div class="level-title">
        Sasaran ESS IV
        </div>

        <input type="text"
        name="sasaran[${es4Index}][nama]"
        class="form-control mb-2"
        placeholder="Masukkan Sasaran ESS IV"
        required>

        <div class="indikator-container"
        id="indikator-container-${es4Index}">
        </div>

        <div class="mt-2 d-flex gap-2">

        <button type="button"
        class="btn btn-sm btn-outline-success"
        onclick="addIndikatorEs4(${es4Index})">

        + Tambah Indikator ESS IV

        </button>

        <button type="button"
        class="btn btn-delete btn-delete-sasaran"
        onclick="this.closest('.es3-group').remove()">

        <i class="fas fa-trash"></i>

        </button>

        </div>

        </div>
        `;

    document
        .getElementById("es4-container")
        .insertAdjacentHTML("beforeend", html);

    es4Index++;

}


function addIndikatorEs4(es4) {

    let idx = Date.now();

    let html = `
        <div class="indikator-es3">

        <input type="text"
        name="sasaran[${es4}][indikator][${idx}][nama]"
        class="form-control"
        placeholder="Masukkan indikator ESS IV">

        <button type="button"
        class="btn btn-delete btn-delete-indikator"
        onclick="this.parentElement.remove()">

        <i class="fas fa-trash"></i>

        </button>

        </div>
        `;

    document
        .getElementById(`indikator-container-${es4}`)
        .insertAdjacentHTML("beforeend", html);

}

function addIndikatorEditEs4() {

    let html = `
        <div class="indikator-es4">

        <input type="text"
        name="indikator[][nama]"
        class="form-control"
        placeholder="Masukkan indikator ESS IV">

        <button type="button"
        class="btn btn-delete btn-delete-indikator"
        onclick="this.parentElement.remove()">

        <i class="fas fa-trash"></i>

        </button>

        </div>
        `;

    document
        .getElementById("indikator-container")
        .insertAdjacentHTML("beforeend", html);

}
/* =========================================================
 * Sejak ada jenjang PELAKSANA di bawah Eselon IV, indikator
 * ES IV di-update DI TEMPAT (id dipertahankan) — bukan
 * dihapus-lalu-disisipkan-ulang. Karena itu baris indikator
 * kini mengirim `indikator[i][id]`, dan penghapusannya lewat
 * hapusIndikatorEs4() agar bisa memperingatkan efek berantai.
 * Pola ini kembar dengan hapusIndikatorEs3().
 * =======================================================*/

// Hapus indikator ES IV; bila masih punya Pelaksana anak -> konfirmasi
// (Pelaksana akan ikut terhapus saat Update ditekan).
function hapusIndikatorEs4(btn) {
    const cnt = parseInt(btn.getAttribute('data-pelaksana-count') || '0', 10);
    if (cnt > 0) {
        const ok = confirm(
            'Indikator ini memiliki ' + cnt + ' Sasaran Pelaksana di bawahnya.\n' +
            'Menghapus indikator ini akan MENGHAPUS seluruh Pelaksana tersebut saat Anda menekan Update.\n\nLanjutkan?'
        );
        if (!ok) return;
    }
    btn.closest('.indikator-es4').remove();
}

let sasaranBaruIndexEs4 = 0;

function addSasaranBaruEs4() {
    const html = `
        <div class="es4-group mb-3 p-3 border rounded bg-light">
            <label class="fw-bold mb-2">Sasaran ESS IV (Baru)</label>
            <input type="text" name="sasaran_baru[${sasaranBaruIndexEs4}][nama]" class="form-control mb-2"
                placeholder="Masukkan Sasaran ESS IV" required>
            <div class="indikator-container" id="indikator-baru-container-${sasaranBaruIndexEs4}"></div>
            <div class="mt-2 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success"
                    onclick="addIndikatorBaruEs4(${sasaranBaruIndexEs4})">
                    + Tambah Indikator ESS IV
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="this.closest('.es4-group').remove()">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </div>
        </div>`;
    document.getElementById('sasaran-baru-container').insertAdjacentHTML('beforeend', html);
    sasaranBaruIndexEs4++;
}

function addIndikatorBaruEs4(idx) {
    const indIdx = Date.now();
    const html = `
        <div class="indikator-es4 d-flex gap-2 mt-2">
            <input type="text" name="sasaran_baru[${idx}][indikator][${indIdx}][nama]" class="form-control"
                placeholder="Masukkan indikator ESS IV">
            <button type="button" class="btn btn-delete btn-delete-indikator"
                onclick="this.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>`;
    document.getElementById(`indikator-baru-container-${idx}`).insertAdjacentHTML('beforeend', html);
}

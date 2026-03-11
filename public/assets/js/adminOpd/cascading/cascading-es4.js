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
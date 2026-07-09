let es3Index = 0;

function addEs3() {

    let html = `
        <div class="es3-group">

        <div class="level-title">Sasaran ESS III</div>

        <input type="text"
        name="sasaran[${es3Index}][nama]"
        class="form-control mb-2"
        placeholder="Masukkan Sasaran ESS III"
        required>

        <div class="indikator-container"
        id="indikator-container-${es3Index}">
        </div>

        <div class="mt-2 d-flex gap-2">

        <button type="button"
        class="btn btn-sm btn-outline-success"
        onclick="addIndikatorEs3(${es3Index})">

        + Tambah Indikator ESS III

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
        .getElementById("es3-container")
        .insertAdjacentHTML("beforeend", html);

    es3Index++;

}


function addIndikatorEs3(es3) {

    let indikatorIndex = Date.now();

    let html = `
        <div class="indikator-es3">

        <input type="text"
        name="sasaran[${es3}][indikator][${indikatorIndex}][nama]"
        class="form-control"
        placeholder="Masukkan indikator ESS III">

        <button type="button"
        class="btn btn-delete btn-delete-indikator"
        onclick="this.parentElement.remove()">

        <i class="fas fa-trash"></i>

        </button>

        </div>
        `;

    document
        .getElementById(`indikator-container-${es3}`)
        .insertAdjacentHTML("beforeend", html);

}

function addIndikator() {

    let html = `
        <div class="indikator-es3">

        <input type="text"
        name="indikator[][nama]"
        class="form-control"
        placeholder="Masukkan indikator ESS III">

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
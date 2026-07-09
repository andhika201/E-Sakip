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

        <div id="indikator-container-${es3Index}" class="indikator-row"></div>

        <button type="button"
        class="btn btn-sm btn-outline-success mt-2"
        onclick="addIndikatorEs3(${es3Index})">

        + Tambah Indikator ESS III

        </button>

        <button type="button"
        class="btn btn-sm btn-danger btn-delete"
        onclick="this.parentElement.remove()"
        title="Hapus">

        <i class="fas fa-trash"></i>

        </button>

        </div>
        `;

    document.getElementById("es3-container")
        .insertAdjacentHTML("beforeend", html);

    es3Index++;

}


function addIndikatorEs3(es3) {

    let indikatorIndex = Date.now();

    let html = `
        <div class="indikator-es3 card shadow-sm p-3">

        <div class="level-title">Indikator ESS III</div>

        <input type="text"
        name="sasaran[${es3}][indikator][${indikatorIndex}][nama]"
        class="form-control mb-2"
        placeholder="Masukkan indikator ESS III">

        <div id="es4-container-${es3}-${indikatorIndex}"></div>

        <button type="button"
        class="btn btn-sm btn-outline-primary mt-2"
        onclick="addEs4(${es3}, ${indikatorIndex})">

        + Tambah Sasaran ESS IV

        <button type="button"
        class="btn btn-sm btn-danger btn-delete"
        onclick="this.parentElement.remove()"
        title="Hapus">

        <i class="fas fa-trash"></i>

        </button>

        </div>
        `;

    document.getElementById(`indikator-container-${es3}`)
        .insertAdjacentHTML("beforeend", html);

}


function addEs4(es3, indikator) {

    let es4Index = Date.now();

    let html = `
        <div class="es4-group">

        <div class="level-title">Sasaran ESS IV</div>

        <input type="text"
        name="sasaran[${es3}][indikator][${indikator}][sasaran][${es4Index}][nama]"
        class="form-control mb-2"
        placeholder="Masukkan sasaran ESS IV">

        <div id="indikator-es4-container-${es3}-${indikator}-${es4Index}"></div>

        <button type="button"
        class="btn btn-sm btn-outline-primary mt-2"
        onclick="addIndikatorEs4(${es3}, ${indikator}, ${es4Index})">

        + Tambah Indikator ESS IV

        <button type="button"
        class="btn btn-sm btn-danger btn-delete"
        onclick="this.parentElement.remove()"
        title="Hapus">

        <i class="fas fa-trash"></i>

        </button>

        </div>
        `;

    document
        .getElementById(`es4-container-${es3}-${indikator}`)
        ?.insertAdjacentHTML("beforeend", html);

}

function addIndikatorEs4(es3, indikator, es4) {

    let indikatorIndex = Date.now();

    let html = `
        <div class="indikator-es4">

        <div class="level-title">Indikator ESS IV</div>

        <input type="text"
        name="sasaran[${es3}][indikator][${indikator}][sasaran][${es4}][indikator][${indikatorIndex}][nama]"
        class="form-control mb-2"
        placeholder="Masukkan indikator ESS IV">

        </div>
        `;

    document
        .getElementById(`indikator-es4-container-${es3}-${indikator}-${es4}`)
        ?.insertAdjacentHTML("beforeend", html);

}
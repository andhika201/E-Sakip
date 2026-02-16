function fetchProgramByGroup(group) {

    let opdSelect = group.querySelector('.opd-select');
    let opdId = opdSelect.value;
    let tahun = document.getElementById('tahun').value;

    if (!opdId || !tahun) {

        group.dataset.programList = "[]";

        group.querySelector('.program-container')
            .innerHTML = '';

        return;
    }

    fetch(`${BASE_URL}/adminkab/cascading/get-pk-program-by-opd?opd_id=${opdId}&tahun=${tahun}`)
        .then(res => res.json())
        .then(data => {

            group.dataset.programList = JSON.stringify(data);

            group.querySelector('.program-container')
                .innerHTML = '';

        });

}



let opdIndex = 0;

window.addOpdGroup = function () {

    let opdContainer = document.getElementById('opd-container');

    let html = `
    <div class="card mb-3 opd-group shadow-sm">

        <div class="card-body">

            <div class="row mb-2">

                <div class="col-md-10">

                    <label>OPD</label>
                    <select name="opd[${opdIndex}][id]"
                            class="form-select opd-select"
                            data-index="${opdIndex}"
                            required>

                        <option value="">-- Pilih OPD --</option>
                        ${opdOptions}

                    </select>

                </div>

                <div class="col-md-2 d-flex align-items-end">

                    <button type="button"
                            class="btn btn-danger w-100 remove-opd">
                        -
                    </button>

                </div>

            </div>

            <div class="program-container mb-2"></div>

            <button type="button"
                    class="btn btn-sm btn-outline-success add-program"
                    data-index="${opdIndex}">
                + Tambah Program
            </button>

        </div>

    </div>
    `;

    opdContainer.insertAdjacentHTML('beforeend', html);

    opdIndex++;

}

document.addEventListener('change', function (e) {

    if (e.target.classList.contains('opd-select')) {

        let group = e.target.closest('.opd-group');
        fetchProgramByGroup(group);

    }

});

document.addEventListener('click', function (e) {

    if (e.target.classList.contains('remove-program')) {
        e.target.closest('.input-group').remove();
    }

    if (e.target.classList.contains('remove-opd')) {
        e.target.closest('.opd-group').remove();
    }

});

document.addEventListener('click', function (e) {

    if (e.target.classList.contains('add-program')) {

        let group = e.target.closest('.opd-group');

        let idx = group.querySelector('.opd-select').dataset.index;

        let container = group.querySelector('.program-container');

        let list = JSON.parse(group.dataset.programList || "[]");

        let tahun = document.getElementById('tahun').value;

        if (!tahun) {
            alert("Pilih Tahun terlebih dahulu!");
            return;
        }

        if (list.length === 0) {
            alert("Tidak ada Program Kegiatan untuk OPD ini di tahun " + tahun);
            return;
        }

        let opt = '<option value="">-- Pilih Program --</option>';

        list.forEach(p => {
            opt += `<option value="${p.id}">
                    ${p.program_kegiatan}
                </option>`;
        });

        // 🔥 CREATE ELEMENT BARU
        let wrapper = document.createElement('div');
        wrapper.classList.add('input-group', 'mb-2');

        wrapper.innerHTML = `
        <select name="opd[${idx}][program][]"
                class="form-select"
                required>
            ${opt}
        </select>

        <button type="button"
                class="btn btn-danger remove-program">
            -
        </button>
    `;

        // 🔥 TAMBAH TANPA RESET DOM LAMA
        container.appendChild(wrapper);

    }



});

document.getElementById('tahun')
    .addEventListener('change', function () {

        document.querySelectorAll('.opd-group')
            .forEach(group => {
                fetchProgramByGroup(group);
            });

    });
window.onload = function () {
    addOpdGroup();
}

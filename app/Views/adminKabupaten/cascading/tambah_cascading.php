<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Cascading</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .opd-group {
            margin-bottom: 20px;
        }

        .program-container {
            margin-top: 10px;
            padding-left: 15px;
            border-left: 3px solid #e9ecef;
        }

        .program-container select {
            margin-top: 6px;
        }
    </style>
</head>

<body class="bg-light">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="p-4">

        <div class="bg-white rounded shadow-sm p-4" style="max-width:800px;margin:auto">

            <h4 class="text-success mb-4">Tambah Mapping Cascading</h4>

            <form action="<?= base_url('adminkab/cascading/save') ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="indikator_id" value="<?= $indikator['id'] ?>">

                <div class="mb-3">
                    <label>Indikator</label>
                    <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>"
                        readonly>
                </div>

                <div class="mb-3">
                    <label>Tahun</label>
                    <?php $currentYear = date('Y'); ?>

                    <select name="tahun" id="tahun" class="form-select">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= ($selected_tahun == $y) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="mb-3">
                    <label>OPD & Program Pendukung</label>

                    <div id="opd-container"></div>

                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addOpdGroup()">

                        + Tambah OPD

                    </button>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <a href="<?= base_url('adminkab/cascading?periode=' . $periode) ?>"
                        class="btn btn-secondary">Kembali</a>
                </div>

            </form>

        </div>

    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>


    <script>
        let opdOptions = `
        <?php foreach ($opd_list as $o): ?>
        <option value="<?= $o['id'] ?>">
        <?= esc($o['nama_opd']) ?>
        </option>
        <?php endforeach; ?>
        `;
    </script>

    <script>
        const BASE_URL = "<?= base_url() ?>";
    </script>
    <script>
        const EXISTING_MAPPING = <?= json_encode($existing_mapping ?? []) ?>;
    </script>

    <script>
        function fetchProgramByGroup(group) {
            let opdSelect = group.querySelector('.opd-select');
            let opdId = opdSelect.value;
            let tahun = document.getElementById('tahun').value;

            if (!opdId || !tahun) {
                group.dataset.programList = "[]";
                group.querySelector('.program-container').innerHTML = '';
                return;
            }

            fetch(`${BASE_URL}/adminkab/cascading/get-pk-program-by-opd?opd_id=${opdId}&tahun=${tahun}`)
                .then(res => res.json())
                .then(data => {
                    group.dataset.programList = JSON.stringify(data);
                    group.querySelector('.program-container').innerHTML = '';
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
                                \${opdOptions}
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
                    opt += `<option value="\${p.id}">
                            \${p.program_kegiatan}
                        </option>`;
                });

                let wrapper = document.createElement('div');
                wrapper.classList.add('input-group', 'mb-2','program-item');
                wrapper.innerHTML = `
                <select name="opd[\${idx}][program][]"
                        class="form-select"
                        required>
                    \${opt}
                </select>
                <button type="button"
                        class="btn btn-danger remove-program">
                    -
                </button>
                `;

                if (!container.querySelector('.program-label')) {
                    container.insertAdjacentHTML(
                        "afterbegin",
                        `<label class="form-label mt-2 program-label">Program</label>`
                    );
                }
                container.appendChild(wrapper);
            }
        });

        document.getElementById('tahun').addEventListener('change', function () {
            document.querySelectorAll('.opd-group').forEach(group => {
                fetchProgramByGroup(group);
            });
        });

        window.onload = async function () {
            if (!EXISTING_MAPPING) return;
            for (let opdId in EXISTING_MAPPING) {
                addOpdGroup();
                let lastGroup = document.querySelectorAll('.opd-group');
                let group = lastGroup[lastGroup.length - 1];
                let opdSelect = group.querySelector('.opd-select');
                opdSelect.value = opdId;
                await loadExistingPrograms(opdSelect, EXISTING_MAPPING[opdId]);
            }
        }

        async function loadExistingPrograms(opdSelect, programs) {
            let opdId = opdSelect.value;
            let tahun = document.getElementById('tahun').value;
            let group = opdSelect.closest('.opd-group');
            let container = group.querySelector('.program-container');

            let res = await fetch(
                `\${BASE_URL}/adminkab/cascading/get-pk-program-by-opd?opd_id=\${opdId}&tahun=\${tahun}`
            );
            let data = await res.json();
            group.dataset.programList = JSON.stringify(data);
            container.innerHTML = '';

            programs.forEach(pid => {
                let options = '<option value="">-- Pilih Program --</option>';
                data.forEach(p => {
                    options += `
                        <option value="\${p.id}" \${p.id == pid ? 'selected' : ''}>
                            \${p.program_kegiatan}
                        </option>
                    `;
                });

                container.innerHTML += `
                    <div class="input-group mb-2">
                        <select name="opd[\${opdSelect.dataset.index}][program][]"
                                class="form-select"
                                required>
                            \${options}
                        </select>
                        <button type="button"
                                class="btn btn-danger remove-program">
                            -
                        </button>
                    </div>
                `;
            });
        }
    </script>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Cascading</title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        .cascading-container {
            width: 95%;
            max-width: 2000px;
        }

        .es3-group {
            border: 2px solid #198754;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8fff9;
            position: relative;
        }

        .indikator-es3 {
            border-left: 4px solid #198754;
            background: #ffffff;
            padding: 12px;
            margin-top: 12px;
            border-radius: 6px;
            min-height: 120px;
            flex: 0 0 calc(33.333% - 10px);
            max-width: calc(33.333% - 10px);
            min-width: 320px;
            position: relative;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);

        }

        .es4-group {
            border-left: 4px solid #0d6efd;
            background: #f5f9ff;
            padding: 12px;
            margin-top: 12px;
            margin-left: 20px;
            border-radius: 6px;
            position: relative;
        }

        .indikator-es4 {
            border-left: 4px dashed #0d6efd;
            padding: 10px;
            margin-left: 20px;
            margin-top: 8px;
            position: relative;
        }

        .level-title {
            font-weight: 600;
            font-size: 14px;
            color: #495057;
            margin-bottom: 6px;
        }

        .indikator-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 10px;

            overflow-x: auto;
            padding-bottom: 10px;
        }

        .btn-delete {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .btn-delete:hover {
            transform: scale(1.05);
        }

        .indikator-es3 input,
        .es4-group input {
            padding-right: 35px;
        }

        @media (max-width:1400px) {
            .indikator-es3 {
                flex: 0 0 calc(50% - 10px);
                max-width: calc(50% - 10px);
            }
        }

        @media (max-width:900px) {
            .indikator-es3 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>

</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="p-4 flex-grow-1">

        <div class="bg-white rounded shadow-sm p-4 mx-auto cascading-container">

            <h4 class="text-success mb-4">
                Tambah Cascading
            </h4>
            <form action="<?= base_url('adminopd/cascading/save') ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="renstra_indikator_sasaran_id" value="<?= $indikator['id'] ?>">

                <!-- TUJUAN RENSTRA -->

                <div class="mb-3">

                    <label>Tujuan RENSTRA</label>

                    <input type="text" class="form-control" value="<?= esc($indikator['tujuan_renstra']) ?>" readonly>

                </div>

                <!-- SASARAN ESS II -->

                <div class="mb-3">

                    <label><?= casc_relabel('Sasaran ESS II') ?></label>

                    <input type="text" class="form-control" value="<?= esc($indikator['sasaran_es2']) ?>" readonly>

                </div>

                <!-- INDIKATOR ESS II -->

                <div class="mb-3">

                    <label><?= casc_relabel('Indikator ESS II') ?></label>

                    <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>"
                        readonly>

                </div>

                <hr>

                <div class="mb-3">

                    <label class="fw-bold">
                        <?= casc_relabel('Sasaran ESS III') ?>
                    </label>

                    <div id="es3-container"></div>

                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addEs3()">

                        + <?= casc_relabel('Tambah Sasaran ESS III') ?>

                    </button>

                </div>

                <div class="mt-4">

                    <button type="submit" class="btn btn-success">

                        Simpan

                    </button>

                    <a href="<?= base_url('adminopd/cascading?periode=' . $periode) ?>" class="btn btn-secondary">

                        Kembali

                    </a>

                </div>

            </form>

        </div>

    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>

    <script>
        let es3Index = 0;

        function addEs3() {
            let html = `
                <div class="es3-group">
                    <div class="level-title"><?= casc_relabel('Sasaran ESS III') ?></div>
                    <input type="text"
                        name="sasaran[${es3Index}][nama]"
                        class="form-control mb-2"
                        placeholder="<?= casc_relabel('Masukkan Sasaran ESS III') ?>"
                        required>
                    <div id="indikator-container-${es3Index}" class="indikator-row"></div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-success"
                            onclick="addIndikatorEs3(${es3Index})">
                            + <?= casc_relabel('Tambah Indikator ESS III') ?>
                        </button>
                        <button type="button"
                            class="btn btn-sm btn-danger btn-delete"
                            onclick="this.closest('.es3-group').remove()"
                            title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            document.getElementById("es3-container").insertAdjacentHTML("beforeend", html);
            es3Index++;
        }

        function addIndikatorEs3(es3) {
            let indikatorIndex = Date.now();
            let html = `
                <div class="indikator-es3 card shadow-sm p-3">
                    <div class="level-title"><?= casc_relabel('Indikator ESS III') ?></div>
                    <input type="text"
                        name="sasaran[${es3}][indikator][${indikatorIndex}][nama]"
                        class="form-control mb-2"
                        placeholder="<?= casc_relabel('Masukkan indikator ESS III') ?>">
                    <div id="es4-container-${es3}-${indikatorIndex}"></div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            onclick="addEs4(${es3}, ${indikatorIndex})">
                            + <?= casc_relabel('Tambah Sasaran ESS IV') ?>
                        </button>
                        <button type="button"
                            class="btn btn-sm btn-danger btn-delete"
                            onclick="this.closest('.indikator-es3').remove()"
                            title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            document.getElementById(`indikator-container-${es3}`).insertAdjacentHTML("beforeend", html);
        }

        function addEs4(es3, indikator) {
            let es4Index = Date.now();
            let html = `
                <div class="es4-group">
                    <div class="level-title"><?= casc_relabel('Sasaran ESS IV') ?></div>
                    <input type="text"
                        name="sasaran[${es3}][indikator][${indikator}][sasaran][${es4Index}][nama]"
                        class="form-control mb-2"
                        placeholder="<?= casc_relabel('Masukkan sasaran ESS IV') ?>">
                    <div id="indikator-es4-container-${es3}-${indikator}-${es4Index}"></div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            onclick="addIndikatorEs4(${es3}, ${indikator}, ${es4Index})">
                            + <?= casc_relabel('Tambah Indikator ESS IV') ?>
                        </button>
                        <button type="button"
                            class="btn btn-sm btn-danger btn-delete"
                            onclick="this.closest('.es4-group').remove()"
                            title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            document.getElementById(`es4-container-${es3}-${indikator}`)?.insertAdjacentHTML("beforeend", html);
        }

        function addIndikatorEs4(es3, indikator, es4) {
            let indikatorIndex = Date.now();
            let html = `
                <div class="indikator-es4">
                    <div class="level-title"><?= casc_relabel('Indikator ESS IV') ?></div>
                    <input type="text"
                        name="sasaran[${es3}][indikator][${indikator}][sasaran][${es4}][indikator][${indikatorIndex}][nama]"
                        class="form-control mb-2"
                        placeholder="<?= casc_relabel('Masukkan indikator ESS IV') ?>">
                </div>
            `;
            document.getElementById(`indikator-es4-container-${es3}-${indikator}-${es4}`)?.insertAdjacentHTML("beforeend", html);
        }
    </script>
    </div>
</body>

</html>
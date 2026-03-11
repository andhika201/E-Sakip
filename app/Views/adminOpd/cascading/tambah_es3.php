<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Cascading</title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        .cascading-container {
            max-width: 900px;
            margin: auto;
        }

        /* GROUP SASARAN */

        .es3-group {
            border: 1px solid #e3e6ea;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 18px;
            background: #fafafa;
        }

        /* TITLE */

        .level-title {
            font-weight: 600;
            font-size: 14px;
            color: #495057;
            margin-bottom: 6px;
        }

        /* INDIKATOR AREA */

        .indikator-container {
            margin-top: 10px;
            padding-left: 18px;
            border-left: 3px solid #dfe3e7;
        }

        /* INDIKATOR ITEM */

        .indikator-es3 {
            background: #ffffff;
            border: 1px solid #e5e7ea;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* INPUT */

        .indikator-es3 input {
            flex: 1;
        }

        /* DELETE BUTTON */

        .btn-delete {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            padding: 0;
        }

        /* DELETE INDIKATOR */

        .btn-delete-indikator {
            background: #fff5f5;
            border: 1px solid #ffc9c9;
            color: #e03131;
        }

        .btn-delete-indikator:hover {
            background: #ffe3e3;
        }

        /* DELETE SASARAN */

        .btn-delete-sasaran {
            background: #fff4e6;
            border: 1px solid #ffd8a8;
            color: #d9480f;
        }

        .btn-delete-sasaran:hover {
            background: #ffe8cc;
        }
    </style>

</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="p-4 flex-grow-1">

        <div class="bg-white rounded shadow-sm p-4 mx-auto cascading-container">

            <h4 class="text-success mb-4">
                Tambah Cascading Esselon III
            </h4>
            <form action="<?= base_url('adminopd/cascading/save-es3') ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="renstra_indikator_sasaran_id" value="<?= $indikator['id'] ?>">

                <!-- TUJUAN RENSTRA -->

                <div class="mb-3">

                    <label>Tujuan RENSTRA</label>

                    <input type="text" class="form-control" value="<?= esc($indikator['tujuan_renstra']) ?>" readonly>

                </div>

                <!-- SASARAN ESS II -->

                <div class="mb-3">

                    <label>Sasaran ESS II</label>

                    <input type="text" class="form-control" value="<?= esc($indikator['sasaran_es2']) ?>" readonly>

                </div>

                <!-- INDIKATOR ESS II -->

                <div class="mb-3">

                    <label>Indikator ESS II</label>

                    <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>"
                        readonly>

                </div>

                <hr>

                <div class="mb-3">

                    <label class="fw-bold">
                        Sasaran ESS III
                    </label>

                    <div id="es3-container"></div>

                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addEs3()">

                        + Tambah Sasaran ESS III

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

    <script src="<?= base_url('assets/js/adminOpd/cascading/cascading-es3.js') ?>"></script>
</body>

</html>
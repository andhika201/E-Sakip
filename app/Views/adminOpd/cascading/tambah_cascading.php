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

    <script src="<?= base_url('assets/js/adminOpd/cascading/cascading.js') ?>"></script>
</body>

</html>
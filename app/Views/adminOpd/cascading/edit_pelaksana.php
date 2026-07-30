<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(casc_pelaksana_label('Edit Cascading ')) ?></title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        .cascading-container { max-width: 900px; margin: auto; }
        .indikator-container { margin-top: 10px; padding-left: 18px; border-left: 3px solid #dfe3e7; }
        .indikator-pel {
            background: #fff;
            border: 1px solid #e5e7ea;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .indikator-pel input[type="text"] { flex: 1; }
        .btn-delete {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 6px; padding: 0;
        }
        .btn-delete-indikator { background: #fff5f5; border: 1px solid #ffc9c9; color: #e03131; }
        .btn-delete-indikator:hover { background: #ffe3e3; }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="p-4 flex-grow-1">
            <div class="bg-white rounded shadow-sm p-4 mx-auto cascading-container">
                <h4 class="text-success mb-4"><?= esc(casc_pelaksana_label('Edit Cascading ')) ?></h4>
                <?= $this->include('adminOpd/cascading/_form_pelaksana') ?>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>

        <script src="<?= base_url('assets/js/adminopd/cascading/cascading-pelaksana.js') ?>"></script>
    </div>
</body>

</html>

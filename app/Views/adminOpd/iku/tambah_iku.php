<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Tambah IKU') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            display: flex;
            align-items: center;
            background-color: #fff;
        }
        .select2-selection__rendered { padding-left: 0 !important; color: #495057; }
        .select2-selection__arrow { height: 100% !important; }
        .select2-results__option--highlighted { background-color: #00743e !important; color: #fff; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
                <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah IKU</h2>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php
                $this->setData([
                    'iku'        => null,
                    'action_url' => base_url('adminopd/iku/save'),
                    'back_url'   => base_url('adminopd/iku'),
                ]);
                ?>
                <?= $this->include('templates/iku/_form') ?>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>

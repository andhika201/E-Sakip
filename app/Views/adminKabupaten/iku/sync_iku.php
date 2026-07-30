<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Sync IKU dari RPJMD') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .iku-table th,
        .iku-table td { vertical-align: middle; }
        .iku-table td.text-start { min-width: 180px; }
        .table-wrap { max-height: 65vh; overflow: auto; }
        .iku-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4">
                <h2 class="h4 fw-bold text-success text-center mb-1">Sync IKU dari RPJMD</h2>
                <p class="text-center text-muted small mb-4">
                    Pilih sasaran &amp; indikator RPJMD yang mau dijadikan IKU Pemerintah Kabupaten.
                    Indikator yang sudah ada di IKU tidak akan ditimpa.
                </p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php
                $this->setData([
                    'sumber_label' => 'RPJMD',
                    'action_url'   => base_url('adminkab/iku/sync/simpan'),
                    'back_url'     => base_url('adminkab/iku?mode=kabupaten'),
                    'filter_url'   => base_url('adminkab/iku/sync'),
                ]);
                ?>
                <?= $this->include('templates/iku/_sync') ?>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Evaluasi Inspektorat - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-1">Evaluasi Inspektorat</h2>
                <p class="text-center text-muted small mb-4">Evaluasi kinerja oleh Inspektorat.</p>

                <div class="text-center text-muted py-5">
                    <i class="fas fa-clipboard-check fa-3x mb-3 text-success opacity-50"></i>
                    <h5 class="fw-semibold">Segera Hadir</h5>
                    <p class="mb-0">Modul Evaluasi Inspektorat sedang disiapkan.</p>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>

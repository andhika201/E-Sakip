<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-ban text-danger" style="font-size: 5rem;"></i>
            </div>
            <h1 class="display-4 text-danger mb-3">403</h1>
            <h2 class="h4 text-dark mb-3">Akses Ditolak</h2>
            <p class="text-muted mb-4">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.<br>
                Silakan hubungi administrator jika Anda memerlukan akses.
            </p>
            
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mx-auto" style="max-width: 500px;">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-center gap-3">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
                <a href="/login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
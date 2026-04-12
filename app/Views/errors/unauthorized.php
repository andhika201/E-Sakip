<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Option 1: Include in HTML -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: #dc3545;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-head {
            font-size: 1.5rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 10px;
        }
        .error-text {
            color: #6c757d;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<div class="error-container">
    <div class="error-code">403</div>
    <div class="error-head">Akses Ditolak!</div>
    <div class="error-text">
        Maaf, Anda tidak memiliki izin untuk mengakses halaman ini dengan peran (role) Anda saat ini.
    </div>
    <a href="javascript:history.back()" class="btn btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-success">
        <i class="bi bi-house-door"></i> Ke Dashboard Utama
    </a>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verifikasi 2FA - e-SAKIP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f1f5f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card-2fa { width: 100%; max-width: 400px; border: none; border-radius: 1rem; box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1); }
    .logo { max-width: 150px; height: auto; }
    .code-input { letter-spacing: .5rem; font-size: 1.5rem; text-align: center; }
    .btn-green { background:#28a745; border-color:#28a745; color:#fff; }
    .btn-green:hover { background:#218838; }
  </style>
</head>

<body>
  <div class="card card-2fa p-4">
    <div class="text-center mb-3">
      <img src="<?= base_url('assets/images/logoHeader.png') ?>" alt="AKSARA" class="logo mb-2">
      <h5 class="fw-bold text-success mb-1">Verifikasi Dua Faktor</h5>
      <p class="text-muted small mb-0">Masukkan 6 digit kode dari aplikasi authenticator Anda.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('2fa/verify') ?>">
      <?= csrf_field() ?>
      <input type="text" name="code" class="form-control code-input mb-3"
        inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
        placeholder="------" required autofocus>
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-green py-2 fw-semibold">Verifikasi</button>
        <a href="<?= base_url('logout') ?>" class="btn btn-outline-secondary btn-sm">Batal &amp; keluar</a>
      </div>
    </form>
  </div>
</body>

</html>

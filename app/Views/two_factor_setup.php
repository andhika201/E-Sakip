<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aktifkan 2FA - e-SAKIP</title>
  <?= $this->include($tpl . '/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include($tpl . '/templates/sidebar.php'); ?>

  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
    <?= $this->include($tpl . '/templates/header.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-2">
      <div class="bg-white rounded shadow p-4" style="width:100%; max-width:620px;">
        <h2 class="h4 fw-bold text-success text-center mb-1">🔐 Aktifkan Autentikasi Dua Faktor</h2>
        <p class="text-muted text-center small mb-4">Gunakan aplikasi <strong>Google Authenticator</strong> / Authy / Microsoft Authenticator.</p>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <ol class="small text-dark mb-3">
          <li>Buka aplikasi authenticator di HP Anda.</li>
          <li>Pindai QR di bawah, atau masukkan kode rahasia secara manual.</li>
          <li>Masukkan 6 digit kode yang muncul untuk konfirmasi.</li>
        </ol>

        <div class="text-center mb-3">
          <div id="qrcode" class="d-inline-block p-2 bg-white border rounded"></div>
        </div>

        <div class="mb-3 text-center">
          <div class="text-muted small">Kode rahasia (manual):</div>
          <code class="fs-6 user-select-all"><?= esc($secret) ?></code>
        </div>

        <form method="post" action="<?= base_url('2fa/enable') ?>" class="mx-auto" style="max-width:320px;">
          <?= csrf_field() ?>
          <label class="form-label fw-semibold">Kode Verifikasi (6 digit)</label>
          <input type="text" name="code" class="form-control form-control-lg text-center"
            inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
            placeholder="______" required autofocus>
          <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Aktifkan</button>
            <a href="<?= base_url('change-password') ?>" class="btn btn-outline-secondary">Batal</a>
          </div>
        </form>
      </div>
    </main>

    <?= $this->include($tpl . '/templates/footer.php'); ?>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function () {
      var uri = <?= json_encode($otpauth) ?>;
      if (window.QRCode) {
        new QRCode(document.getElementById('qrcode'), { text: uri, width: 200, height: 200 });
      } else {
        document.getElementById('qrcode').innerHTML =
          '<div class="text-muted small">QR tidak dapat dimuat. Masukkan kode rahasia secara manual.</div>';
      }
    })();
  </script>
</body>

</html>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verifikasi 2FA — e-SAKIP Kabupaten Pringsewu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --brand: #00743e; --brand-2: #0a8f50; }
    * { box-sizing: border-box; }

    body {
      font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 24px;
      color: #24302a;
    }
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url('<?= base_url('assets/images/Pringsewu.jpg') ?>');
      background-size: cover;
      background-position: center;
      filter: blur(8px);
      transform: scale(1.06);
      z-index: -2;
    }
    body::after {
      content: '';
      position: fixed;
      inset: 0;
      background: linear-gradient(135deg, rgba(0, 116, 62, .55) 0%, rgba(1, 55, 30, .82) 100%);
      z-index: -1;
    }

    .auth-card {
      width: 100%;
      max-width: 440px;
      padding: 2.4rem 2.2rem;
      border-radius: 22px;
      background: #fff;
      box-shadow: 0 30px 70px rgba(0, 0, 0, .32);
      animation: cardUp .5s ease both;
    }
    @keyframes cardUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .auth-icon {
      width: 64px;
      height: 64px;
      margin: 0 auto 14px;
      border-radius: 18px;
      display: grid;
      place-items: center;
      color: #fff;
      font-size: 26px;
      background: linear-gradient(135deg, var(--brand-2), var(--brand));
      box-shadow: 0 10px 22px rgba(0, 116, 62, .3);
    }
    .auth-head { text-align: center; margin-bottom: 1.4rem; }
    .auth-head h1 { font-weight: 800; font-size: 1.4rem; color: #15311f; margin: 0 0 6px; }
    .auth-head p { font-size: .88rem; color: #6b7a70; margin: 0; }

    .otp-group {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin: 6px 0 20px;
    }
    .otp-box {
      width: 50px;
      height: 58px;
      text-align: center;
      font-size: 1.5rem;
      font-weight: 700;
      border: 1.5px solid #d8e0da;
      border-radius: 12px;
      color: #15311f;
      outline: none;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .otp-box:focus { border-color: var(--brand); box-shadow: 0 0 0 .2rem rgba(0, 116, 62, .15); }

    .btn-auth {
      width: 100%;
      padding: .85rem;
      font-weight: 700;
      font-size: 1rem;
      border: none;
      border-radius: 12px;
      color: #fff;
      background: linear-gradient(135deg, var(--brand-2) 0%, var(--brand) 100%);
      box-shadow: 0 10px 22px rgba(0, 116, 62, .3);
      cursor: pointer;
      transition: transform .18s ease, box-shadow .18s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
    }
    .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(0, 116, 62, .38); }

    .btn-cancel {
      display: block;
      text-align: center;
      margin-top: 12px;
      font-size: .85rem;
      color: #8a958d;
      text-decoration: none;
    }
    .btn-cancel:hover { color: #00743e; }

    .auth-card .alert { border-radius: 12px; font-size: .88rem; }

    @media (max-width: 420px) {
      .auth-card { padding: 2rem 1.4rem; }
      .otp-box { width: 42px; height: 52px; font-size: 1.3rem; }
      .otp-group { gap: 7px; }
    }
  </style>
</head>

<body>
  <div class="auth-card">
    <div class="auth-icon"><i class="fas fa-shield-halved"></i></div>

    <div class="auth-head">
      <h1>Verifikasi Dua Faktor</h1>
      <p>Masukkan 6 digit kode dari aplikasi <strong>authenticator</strong> Anda.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('2fa/verify') ?>" id="otpForm">
      <?= csrf_field() ?>
      <input type="hidden" name="code" id="otpCode">

      <div class="otp-group">
        <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" autofocus>
        <input class="otp-box" inputmode="numeric" maxlength="1">
        <input class="otp-box" inputmode="numeric" maxlength="1">
        <input class="otp-box" inputmode="numeric" maxlength="1">
        <input class="otp-box" inputmode="numeric" maxlength="1">
        <input class="otp-box" inputmode="numeric" maxlength="1">
      </div>

      <button type="submit" class="btn-auth">
        <i class="fas fa-circle-check"></i> Verifikasi
      </button>
      <a href="<?= base_url('logout') ?>" class="btn-cancel">Batal &amp; keluar</a>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var boxes = Array.prototype.slice.call(document.querySelectorAll('.otp-box'));
      var hidden = document.getElementById('otpCode');
      var form = document.getElementById('otpForm');

      function sync() {
        hidden.value = boxes.map(function (b) { return b.value; }).join('');
      }

      boxes.forEach(function (box, idx) {
        box.addEventListener('input', function () {
          this.value = this.value.replace(/\D/g, '').slice(0, 1);
          if (this.value && idx < boxes.length - 1) boxes[idx + 1].focus();
          sync();
        });
        box.addEventListener('keydown', function (e) {
          if (e.key === 'Backspace' && !this.value && idx > 0) {
            boxes[idx - 1].focus();
          }
        });
        box.addEventListener('paste', function (e) {
          e.preventDefault();
          var data = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
          for (var i = 0; i < data.length && i < boxes.length; i++) {
            boxes[i].value = data[i];
          }
          sync();
          boxes[Math.min(data.length, boxes.length - 1)].focus();
        });
      });

      form.addEventListener('submit', function (e) {
        sync();
        if (hidden.value.length !== 6) {
          e.preventDefault();
          var empty = boxes.find(function (b) { return !b.value; });
          (empty || boxes[0]).focus();
        }
      });
    })();
  </script>
</body>

</html>

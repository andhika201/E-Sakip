<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aktifkan 2FA - e-SAKIP</title>
  <?= $this->include($tpl . '/templates/style.php'); ?>
  <style>
    .tfa-wrap { width: 100%; max-width: 640px; }
    .tfa-head {
      display: flex; align-items: center; gap: 14px;
      padding-bottom: 16px; margin-bottom: 20px; border-bottom: 1px solid #e8ece9;
    }
    .tfa-head .tfa-ic {
      flex: 0 0 auto; width: 52px; height: 52px; border-radius: 14px;
      display: grid; place-items: center; color: #fff; font-size: 22px;
      background: linear-gradient(135deg, #0a8f50, #00743e);
      box-shadow: 0 8px 18px rgba(0, 116, 62, .28);
    }
    .tfa-head h2 { margin: 0; font-weight: 800; font-size: 1.25rem; color: #16321f; }
    .tfa-head p { margin: 3px 0 0; font-size: .85rem; color: #6b7a70; }

    .tfa-steps { list-style: none; counter-reset: s; padding: 0; margin: 0 0 22px; display: flex; flex-direction: column; gap: 13px; }
    .tfa-steps li { counter-increment: s; display: flex; gap: 12px; align-items: flex-start; font-size: .9rem; color: #3a4a40; }
    .tfa-steps li::before {
      content: counter(s); flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%;
      background: #e9f3ed; color: #00743e; font-weight: 700; font-size: .8rem;
      display: grid; place-items: center; margin-top: 1px;
    }

    .tfa-qr {
      display: inline-block; padding: 12px; background: #fff;
      border: 1px solid #e3e8e4; border-radius: 16px; box-shadow: 0 6px 18px rgba(16, 40, 24, .07);
    }
    .tfa-secret {
      display: inline-flex; align-items: center; gap: 10px;
      background: #f6f9f7; border: 1px dashed #cdd9d2; border-radius: 10px; padding: 8px 8px 8px 14px;
    }
    .tfa-secret code { font-size: 1rem; letter-spacing: 1px; color: #15311f; }
    .tfa-secret .tfa-copy {
      border: none; background: #00743e; color: #fff; width: 32px; height: 32px;
      border-radius: 8px; cursor: pointer; transition: background .15s ease;
    }
    .tfa-secret .tfa-copy:hover { background: #005c31; }

    .tfa-code-input { letter-spacing: .6rem; font-weight: 700; text-align: center; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include($tpl . '/templates/sidebar.php'); ?>

  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
    <?= $this->include($tpl . '/templates/header.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-2">
      <div class="bg-white rounded shadow p-4 tfa-wrap">

        <div class="tfa-head">
          <div class="tfa-ic"><i class="fas fa-shield-halved"></i></div>
          <div>
            <h2>Aktifkan Autentikasi Dua Faktor</h2>
            <p>Gunakan Google Authenticator, Authy, atau Microsoft Authenticator.</p>
          </div>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <ol class="tfa-steps">
          <li>Buka aplikasi authenticator di ponsel Anda.</li>
          <li>Pindai QR di bawah, atau masukkan kode rahasia secara manual.</li>
          <li>Masukkan 6 digit kode yang muncul untuk konfirmasi.</li>
        </ol>

        <div class="text-center mb-3">
          <div id="qrcode" class="tfa-qr"></div>
        </div>

        <div class="mb-4 text-center">
          <div class="text-muted small mb-2">Kode rahasia (manual)</div>
          <div class="tfa-secret">
            <code id="tfaSecret"><?= esc($secret) ?></code>
            <button type="button" class="tfa-copy" onclick="copySecret()" title="Salin kode">
              <i class="fas fa-copy" id="copyIcon"></i>
            </button>
          </div>
        </div>

        <form method="post" action="<?= base_url('2fa/enable') ?>" class="mx-auto" style="max-width:340px;">
          <?= csrf_field() ?>
          <label class="form-label fw-semibold">Kode Verifikasi (6 digit)</label>
          <input type="text" name="code" class="form-control form-control-lg tfa-code-input"
            inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
            placeholder="------" required autofocus>
          <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Aktifkan 2FA</button>
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
          '<div class="text-muted small p-3">QR tidak dapat dimuat. Masukkan kode rahasia secara manual.</div>';
      }
    })();

    function copySecret() {
      var txt = document.getElementById('tfaSecret').textContent.trim();
      var icon = document.getElementById('copyIcon');
      var done = function () { icon.classList.replace('fa-copy', 'fa-check'); setTimeout(function () { icon.classList.replace('fa-check', 'fa-copy'); }, 1500); };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(txt).then(done).catch(function () {});
      } else {
        var t = document.createElement('textarea');
        t.value = txt; document.body.appendChild(t); t.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(t);
      }
    }
  </script>
</body>

</html>

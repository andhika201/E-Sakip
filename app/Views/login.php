<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <link rel="icon" href="<?= base_url(setting('favicon', 'assets/images/sakipLogo.png')) ?>" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        :root { --brand: #00743e; --brand-2: #0a8f50; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            color: #24302a;
        }

        /* Foto blur */
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
        /* Overlay gradien hijau (kontras + brand) */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(0, 116, 62, .55) 0%, rgba(1, 55, 30, .82) 100%);
            z-index: -1;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
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

        .logo-container { text-align: center; margin-bottom: 1rem; }
        .logo {
            width: 150px;
            max-width: 60%;
            height: auto;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }

        .login-head { text-align: center; margin-bottom: 1.6rem; }
        .login-head h1 {
            font-weight: 800;
            font-size: 1.5rem;
            color: #15311f;
            margin: 0 0 5px;
            letter-spacing: .2px;
        }
        .login-head p { font-size: .88rem; color: #6b7a70; margin: 0; }

        /* Fields */
        .login-field { position: relative; margin-bottom: 14px; }
        .login-field .lf-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa6a0;
            font-size: .9rem;
        }
        .login-field input {
            width: 100%;
            padding: .82rem 1rem .82rem 2.7rem;
            border: 1.5px solid #d8e0da;
            border-radius: 12px;
            font-size: .95rem;
            color: #24302a;
            transition: border-color .15s ease, box-shadow .15s ease;
            outline: none;
        }
        .login-field input::placeholder { color: #aab4ae; }
        .login-field input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 .2rem rgba(0, 116, 62, .15);
        }
        .login-field .lf-toggle {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9aa6a0;
            width: 38px;
            height: 38px;
            border-radius: 9px;
            cursor: pointer;
            transition: .15s;
        }
        .login-field .lf-toggle:hover { color: var(--brand); background: #f0f5f1; }

        .btn-login {
            width: 100%;
            padding: .85rem;
            margin-top: 4px;
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
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(0, 116, 62, .38); }

        .login-card .alert { border-radius: 12px; font-size: .88rem; }

        /* Footer DevTech */
        .login-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 8px 22px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
            background: rgba(0, 0, 0, .35);
            backdrop-filter: blur(3px);
            z-index: 5;
        }
        .login-footer img { height: 56px; width: auto; object-fit: contain; filter: brightness(0) invert(1); opacity: .95; }
        .login-footer .pw-label { font-size: .8rem; opacity: .9; line-height: 1.3; text-shadow: 1px 1px 2px rgba(0, 0, 0, .8); }
        .login-footer .pw-sn { font-size: .85rem; font-weight: 700; letter-spacing: .04em; line-height: 1.3; text-shadow: 1px 1px 2px rgba(0, 0, 0, .8); }

        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.5rem; }
            .logo { width: 130px; }
            .login-footer img { height: 46px; }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="<?= base_url(setting('app_logo', 'assets/images/LogoTentang.png')) ?>" alt="<?= esc(setting('app_name', 'e-SAKIP')) ?>" class="logo">
        </div>

        <div class="login-head">
            <h1>Selamat Datang</h1>
            <p>Masuk ke akun <strong><?= esc(setting('app_name', 'e-SAKIP')) ?></strong> <?= esc(setting('instansi', 'Kabupaten Pringsewu')) ?></p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('login/authenticate') ?>">
            <?= csrf_field() ?>

            <div class="login-field">
                <i class="fas fa-user lf-icon"></i>
                <input type="text" name="username" placeholder="Username" required autofocus>
            </div>

            <div class="login-field">
                <i class="fas fa-lock lf-icon"></i>
                <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                <button type="button" class="lf-toggle" onclick="togglePw()" aria-label="Tampilkan password">
                    <i class="fas fa-eye" id="pwEye"></i>
                </button>
            </div>

            <!-- Google reCAPTCHA -->
            <!--<div class="mb-3 d-flex justify-content-center">-->
            <!--    <div class="g-recaptcha" data-sitekey="<?= env('RECAPTCHA_SITE_KEY') ?>"></div>-->
            <!--</div>-->

            <button type="submit" class="btn-login">
                <i class="fas fa-right-to-bracket"></i> Log In
            </button>
        </form>
    </div>

    <!-- Login Page Footer (DevTech + SN) -->
    <div class="login-footer">
        <img src="<?= base_url(setting('dev_logo', 'assets/images/devtech.png')) ?>" alt="<?= esc(setting('dev_name', 'DevTech')) ?>">
        <div class="text-end text-white">
            <div class="pw-label">Powered by</div>
            <div class="pw-sn">SN: <?= esc(setting('serial_number', 'ESAKIP-2025-001')) ?></div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePw() {
            var i = document.getElementById('loginPassword');
            var e = document.getElementById('pwEye');
            if (i.type === 'password') {
                i.type = 'text';
                e.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                i.type = 'password';
                e.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>

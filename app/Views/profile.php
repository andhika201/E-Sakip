<?php
$uname    = $user['username'] ?? 'Pengguna';
$inisial  = strtoupper(mb_substr($uname, 0, 1));
$aktif    = !empty($user['is_active']);
$bergabung = !empty($user['created_at']) ? date('d-m-Y', strtotime($user['created_at'])) : '-';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil Saya - e-SAKIP</title>
    <?= $this->include($prefix . '/templates/style.php'); ?>
    <style>
        .pf-hero {
            background: linear-gradient(120deg, #00803f 0%, #00642f 100%);
            color: #fff; border-radius: 16px; padding: 24px 26px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: 0 12px 30px rgba(0, 116, 62, .2);
        }
        .pf-ava {
            width: 76px; height: 76px; border-radius: 50%; flex: 0 0 auto;
            background: rgba(255, 255, 255, .18); display: grid; place-items: center;
            font-size: 34px; font-weight: 800;
        }
        .pf-hero h2 { margin: 0; font-weight: 800; font-size: 1.5rem; }
        .pf-hero p { margin: 2px 0 0; opacity: .9; }
        .pf-table th { width: 200px; color: #5d6b62; font-weight: 600; }
        .pf-table td, .pf-table th { padding: 11px 8px; border-bottom: 1px solid #eef2ef; vertical-align: middle; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include($prefix . '/templates/sidebar.php'); ?>

    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include($prefix . '/templates/header.php'); ?>

        <main class="flex-fill p-4 mt-2">

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- HERO -->
            <div class="pf-hero mb-4">
                <div class="pf-ava"><?= esc($inisial) ?></div>
                <div>
                    <h2><?= esc($uname) ?></h2>
                    <p><i class="fas fa-user-shield me-1"></i> <?= esc($roleLabel) ?> &middot; <?= esc($namaOpd) ?></p>
                </div>
            </div>

            <div class="row g-4">
                <!-- INFO AKUN -->
                <div class="col-12 col-lg-7">
                    <div class="bg-white rounded shadow p-4 h-100">
                        <h3 class="h5 fw-bold text-success mb-3"><i class="fas fa-id-card me-2"></i>Informasi Akun</h3>
                        <table class="table pf-table mb-0">
                            <tbody>
                                <tr><th>Username</th><td><?= esc($uname) ?></td></tr>
                                <tr><th>Email</th><td><?= esc($user['email'] ?? '-') ?></td></tr>
                                <tr><th>Peran (Role)</th><td><span class="badge bg-success"><?= esc($roleLabel) ?></span></td></tr>
                                <tr><th>Unit Kerja</th><td><?= esc($namaOpd) ?></td></tr>
                                <tr>
                                    <th>Status Akun</th>
                                    <td>
                                        <?php if ($aktif): ?>
                                            <span class="badge bg-success"><i class="fas fa-circle-check me-1"></i>Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Autentikasi 2 Faktor</th>
                                    <td>
                                        <?php if (!empty($twofaEnabled)): ?>
                                            <span class="badge bg-success"><i class="fas fa-shield-halved me-1"></i>Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Belum aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr><th>Bergabung</th><td><?= esc($bergabung) ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- KEAMANAN -->
                <div class="col-12 col-lg-5">
                    <div class="bg-white rounded shadow p-4 h-100">
                        <h3 class="h5 fw-bold text-success mb-3"><i class="fas fa-lock me-2"></i>Keamanan</h3>
                        <p class="text-muted small">Kelola kata sandi dan autentikasi dua faktor untuk menjaga keamanan akun Anda.</p>
                        <a href="<?= base_url('change-password') ?>" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-key me-1"></i> Ganti Password
                        </a>
                        <div class="row g-0">
                            <?= $this->include('templates/two_factor_card', ['twofaEnabled' => $twofaEnabled ?? false]); ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <?= $this->include($prefix . '/templates/footer.php'); ?>

    </div>

</body>

</html>

<?php

/**
 * Kotak masuk permintaan perbaikan LAKIP dari OPD.
 *
 * Bentuknya sengaja meniru daftar verifikasi revisi IKU: operator kabupaten
 * memutuskan dua hal yang sama (setujui / tolak beralasan) di dua dokumen
 * yang berbeda, jadi tata letaknya tidak perlu berbeda.
 */
$title      = $title      ?? 'Permintaan Perbaikan LAKIP';
$permintaan = $permintaan ?? [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title) ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="mx-auto" style="width:100%; max-width:1200px;">

                <div class="bg-white rounded shadow-sm p-4">

                    <h2 class="h4 fw-bold text-success mb-1">
                        <i class="fa-solid fa-inbox me-1"></i> Permintaan Perbaikan LAKIP
                    </h2>
                    <p class="text-muted small mb-4">
                        LAKIP yang sudah disahkan terkunci. Bila OPD menemukan salah ketik, mereka
                        mengajukan permintaan di sini &mdash; Anda yang memutuskan membuka atau tidak.
                    </p>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif; ?>

                    <?php if ($permintaan === []): ?>
                        <div class="alert alert-light border text-center py-4 mb-0">
                            <i class="fa-solid fa-circle-check text-success fs-4 d-block mb-2"></i>
                            Tidak ada permintaan yang menunggu keputusan.
                        </div>
                    <?php else: ?>
                        <?php foreach ($permintaan as $p): ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div class="fw-semibold">
                                            <?= esc($p['nama_opd'] ?? ($p['mode'] === 'kabupaten' ? 'Pemerintah Kabupaten' : 'OPD ' . $p['opd_id'])) ?>
                                            &middot; LAKIP <?= esc($p['tahun']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            Diajukan <?= esc(date('d/m/Y H:i', strtotime($p['diminta_pada']))) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark">Menunggu keputusan</span>
                                </div>

                                <div class="bg-light border rounded p-2 mb-3 small">
                                    <span class="text-muted">Alasan yang disampaikan:</span><br>
                                    <em><?= esc($p['alasan']) ?></em>
                                </div>

                                <div class="row g-2">
                                    <?php /* SETUJUI — tanggapan opsional */ ?>
                                    <div class="col-md-6">
                                        <form method="post"
                                              action="<?= base_url('adminkab/lakip/permintaan/' . (int) $p['id'] . '/setujui') ?>"
                                              onsubmit="return confirm('Setujui pembukaan LAKIP <?= esc($p['tahun']) ?>?\n\nOPD akan bisa menyunting angka tahun itu sampai mereka mengesahkannya ulang.');">
                                            <?= csrf_field() ?>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="tanggapan" class="form-control"
                                                       placeholder="Catatan (opsional)">
                                                <button class="btn btn-success">
                                                    <i class="fa-solid fa-lock-open me-1"></i>Setujui
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <?php /* TOLAK — tanggapan WAJIB, ditegakkan juga di controller */ ?>
                                    <div class="col-md-6">
                                        <form method="post"
                                              action="<?= base_url('adminkab/lakip/permintaan/' . (int) $p['id'] . '/tolak') ?>">
                                            <?= csrf_field() ?>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="tanggapan" class="form-control" required
                                                       placeholder="Alasan penolakan (wajib)">
                                                <button class="btn btn-outline-danger">
                                                    <i class="fa-solid fa-ban me-1"></i>Tolak
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

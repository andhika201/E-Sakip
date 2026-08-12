<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Pemetaan OPD Hasil Import' ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
                <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Pemetaan OPD Hasil Import</h2>

                <?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $kunci => $warna): ?>
                    <?php if (session()->getFlashdata($kunci)): ?>
                        <div class="alert alert-<?= $warna ?> alert-dismissible fade show">
                            <?= esc(session()->getFlashdata($kunci)) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <p class="text-muted small">
                    Daftar unggahan Lampiran 8 dengan cakupan <strong>Seluruh OPD</strong>. Batch yang masih
                    berstatus <em>pending mapping</em> memiliki unit yang OPD-nya belum bisa ditentukan otomatis —
                    datanya sudah tersimpan sementara sehingga Anda tidak perlu mengunggah ulang Excel.
                </p>

                <?php if (empty($batches)): ?>
                    <div class="alert alert-secondary mb-0">Belum ada batch import.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">#</th>
                                    <th>File</th>
                                    <th class="text-center" style="width:90px">Tahun</th>
                                    <th class="text-center" style="width:110px">Jenis</th>
                                    <th class="text-center" style="width:80px">Unit</th>
                                    <th class="text-center" style="width:110px">Pending</th>
                                    <th class="text-center" style="width:140px">Status</th>
                                    <th class="text-center" style="width:150px">Waktu</th>
                                    <th class="text-center" style="width:90px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batches as $b): ?>
                                    <tr>
                                        <td><?= (int) $b['id'] ?></td>
                                        <td><?= esc($b['nama_file'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($b['tahun']) ?></td>
                                        <td class="text-center"><?= esc($b['jenis_anggaran']) ?></td>
                                        <td class="text-center"><?= (int) $b['jumlah_unit'] ?></td>
                                        <td class="text-center">
                                            <?php if ((int) $b['jumlah_pending'] > 0): ?>
                                                <span class="badge bg-warning text-dark"><?= (int) $b['jumlah_pending'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $b['status'] === 'selesai' ? 'success' : 'warning text-dark' ?>">
                                                <?= esc($b['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center small"><?= esc($b['created_at']) ?></td>
                                        <td class="text-center">
                                            <a href="<?= base_url('adminkab/program_pk/mapping/' . (int) $b['id']) ?>"
                                               class="btn btn-sm btn-outline-success">Buka</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="<?= base_url('adminkab/program_pk/import') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Import
                    </a>
                </div>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

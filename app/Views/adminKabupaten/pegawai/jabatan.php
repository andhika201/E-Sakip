<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Kelola Jabatan') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .jabatan-table thead th { position: sticky; top: 0; z-index: 2; white-space: nowrap; }
        .table-wrap { max-height: 72vh; overflow: auto; }
        .jabatan-table td { vertical-align: middle; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                    <h2 class="h4 fw-bold text-success mb-0">🪪 Kelola Jabatan</h2>
                    <a href="<?= base_url('adminkab/pegawai') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Pegawai
                    </a>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-5">
                        <select name="opd_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua OPD</option>
                            <?php foreach ($opdList as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>"
                                    <?= ((int) ($filters['opd_id'] ?? 0) === (int) $opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <p class="text-muted small">Ubah nama jabatan, OPD, atau eselon, lalu klik tombol simpan pada baris terkait.</p>

                <!-- Form per-jabatan (HTML5 form attribute, agar markup tabel tetap valid) -->
                <?php foreach ($jabatanList as $j): ?>
                    <form id="jabForm<?= (int) $j['id'] ?>"
                        action="<?= base_url('adminkab/pegawai/jabatan/update/' . (int) $j['id']) ?>"
                        method="post" class="d-none"><?= csrf_field() ?></form>
                <?php endforeach; ?>

                <div class="table-responsive table-wrap">
                    <table class="table table-bordered align-middle small jabatan-table">
                        <thead class="table-success text-dark text-center">
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Nama Jabatan</th>
                                <th style="width:280px;">OPD</th>
                                <th style="width:100px;">Eselon</th>
                                <th style="width:90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jabatanList)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada jabatan.</td></tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($jabatanList as $j): ?>
                                    <?php $fid = 'jabForm' . (int) $j['id']; ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td>
                                            <input type="text" form="<?= $fid ?>" name="nama_jabatan"
                                                class="form-control form-control-sm"
                                                value="<?= esc($j['nama_jabatan']) ?>" required>
                                        </td>
                                        <td>
                                            <select form="<?= $fid ?>" name="opd_id" class="form-select form-select-sm">
                                                <option value="">- Tanpa OPD -</option>
                                                <?php foreach ($opdList as $opd): ?>
                                                    <option value="<?= (int) $opd['id'] ?>"
                                                        <?= ((int) ($j['opd_id'] ?? 0) === (int) $opd['id']) ? 'selected' : '' ?>>
                                                        <?= esc($opd['nama_opd']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" form="<?= $fid ?>" name="eselon"
                                                class="form-control form-control-sm"
                                                value="<?= esc($j['eselon'] ?? '') ?>" min="0">
                                        </td>
                                        <td class="text-center">
                                            <button type="submit" form="<?= $fid ?>" class="btn btn-success btn-sm" title="Simpan">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

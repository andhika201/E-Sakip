<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Log Aktivitas') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .log-table thead th { position: sticky; top: 0; z-index: 2; white-space: nowrap; }
        .log-table td { vertical-align: middle; font-size: .85rem; }
        .table-wrap { max-height: 64vh; overflow: auto; }
        .desc-cell { max-width: 360px; word-break: break-word; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h2 class="h4 fw-bold text-success mb-0">📝 Log Aktivitas Pengguna</h2>
                        <small class="text-muted">Total <?= (int) ($total ?? 0) ?> aktivitas tercatat.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('adminkab/log-aktivitas/pdf') . (!empty(array_filter($filters)) ? '?' . http_build_query(array_filter($filters)) : '') ?>"
                            target="_blank" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                        <a href="<?= base_url('adminkab/master') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Master Data
                        </a>
                    </div>
                </div>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- FILTER -->
                <form method="get" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari user / deskripsi / IP..."
                            value="<?= esc($filters['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="action" class="form-select form-select-sm">
                            <option value="">Semua Aksi</option>
                            <?php foreach (($actions ?? []) as $a): ?>
                                <option value="<?= esc($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= esc($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="module" class="form-select form-select-sm">
                            <option value="">Semua Modul</option>
                            <?php foreach (($modules ?? []) as $m): ?>
                                <option value="<?= esc($m) ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= esc($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="user" class="form-select form-select-sm">
                            <option value="">Semua User</option>
                            <?php foreach (($users ?? []) as $u): ?>
                                <option value="<?= esc($u) ?>" <?= ($filters['user'] ?? '') === $u ? 'selected' : '' ?>><?= esc($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($filters['from'] ?? '') ?>" title="Dari tanggal">
                        <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($filters['to'] ?? '') ?>" title="Sampai tanggal">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-success btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
                        <a href="<?= base_url('adminkab/log-aktivitas') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <form action="<?= base_url('adminkab/log-aktivitas/clear') ?>" method="post" class="ms-auto"
                            onsubmit="return confirm('Hapus log lebih lama dari 90 hari?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="days" value="90">
                            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-broom me-1"></i> Bersihkan log > 90 hari</button>
                        </form>
                    </div>
                </form>

                <div class="table-responsive table-wrap">
                    <table class="table table-bordered table-striped log-table mb-0">
                        <thead class="table-success text-dark text-center">
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Aksi</th>
                                <th>Modul</th>
                                <th>Deskripsi</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">Belum ada aktivitas.</td></tr>
                            <?php else: ?>
                                <?php
                                $badgeMap = [
                                    'login'       => 'bg-success',
                                    'logout'      => 'bg-secondary',
                                    'login_gagal' => 'bg-danger',
                                    'hapus'       => 'bg-danger',
                                    'ubah'        => 'bg-warning text-dark',
                                    'ubah status' => 'bg-warning text-dark',
                                    'simpan'      => 'bg-info text-dark',
                                    'sinkron'     => 'bg-primary',
                                    'import'      => 'bg-primary',
                                ];
                                ?>
                                <?php foreach ($logs as $l): ?>
                                    <?php $bd = $badgeMap[$l['action']] ?? 'bg-secondary'; ?>
                                    <tr>
                                        <td class="text-nowrap"><?= esc($l['created_at']) ?></td>
                                        <td><?= esc($l['username'] ?? '-') ?></td>
                                        <td><?= esc($l['role'] ?? '-') ?></td>
                                        <td><span class="badge <?= $bd ?>"><?= esc($l['action'] ?? '-') ?></span></td>
                                        <td><?= esc($l['module'] ?? '-') ?></td>
                                        <td class="desc-cell"><?= esc($l['description'] ?? '-') ?></td>
                                        <td class="text-nowrap"><?= esc($l['ip_address'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($pager)): ?>
                    <div class="mt-3">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

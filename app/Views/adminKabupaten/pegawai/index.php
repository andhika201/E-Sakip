<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Manajemen Pegawai') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .pegawai-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            white-space: nowrap;
        }
        .table-wrap {
            max-height: 70vh;
            overflow: auto;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                    <h2 class="h4 fw-bold text-success mb-0">👥 Manajemen Pegawai</h2>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('adminkab/pegawai/jabatan') ?>" class="btn btn-outline-success">
                            <i class="fas fa-id-badge me-1"></i> Kelola Jabatan
                        </a>
                        <?php if (session()->get('role') === 'admin'): ?>
                            <a href="<?= base_url('adminkab/pegawai/sync') ?>" class="btn btn-success">
                                <i class="fas fa-sync-alt me-1"></i> Sinkron SIMPEG/SIKASN
                            </a>
                        <?php endif; ?>
                    </div>
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

                <!-- FILTER -->
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
                    <div class="col-md-5">
                        <input type="text" name="q" class="form-control" placeholder="Cari nama / NIP pegawai..."
                            value="<?= esc($filters['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-success"><i class="fas fa-search me-1"></i> Cari</button>
                    </div>
                </form>

                <div class="table-responsive table-wrap">
                    <table class="table table-bordered table-striped align-middle small pegawai-table">
                        <thead class="table-success text-dark text-center">
                            <tr>
                                <th>No</th>
                                <th>Nama Pegawai</th>
                                <th>NIP</th>
                                <th>OPD</th>
                                <th>Jabatan</th>
                                <th>Pangkat / Gol.</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pegawai)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Tidak ada data pegawai untuk filter ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($pegawai as $p): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= esc($p['nama_pegawai']) ?></td>
                                        <td><?= esc($p['nip_pegawai']) ?></td>
                                        <td><?= esc($p['nama_opd'] ?? '-') ?></td>
                                        <td><?= esc($p['nama_jabatan'] ?? '-') ?></td>
                                        <td>
                                            <?= esc($p['nama_pangkat'] ?? '-') ?>
                                            <?php if (!empty($p['golongan'])): ?>
                                                <span class="text-muted">(<?= esc($p['golongan']) ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('adminkab/pegawai/edit/' . (int) $p['id']) ?>"
                                                class="btn btn-warning btn-sm" title="Ubah jabatan & OPD">
                                                <i class="fas fa-edit"></i>
                                            </a>
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

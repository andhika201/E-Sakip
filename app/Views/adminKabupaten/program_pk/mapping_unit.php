<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Pemetaan OPD' ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1300px;">
                <h2 class="h3 fw-bold text-center mb-1" style="color: #00743e;">Pemetaan OPD Hasil Import</h2>
                <p class="text-center text-muted small mb-4">
                    Batch #<?= (int) $batch['id'] ?> &middot; <?= esc($batch['nama_file'] ?? '-') ?> &middot;
                    Tahun <?= esc($batch['tahun']) ?> &middot; <?= esc($batch['jenis_anggaran']) ?>
                </p>

                <?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $kunci => $warna): ?>
                    <?php if (session()->getFlashdata($kunci)): ?>
                        <div class="alert alert-<?= $warna ?> alert-dismissible fade show">
                            <?= esc(session()->getFlashdata($kunci)) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php
                $pending = array_values(array_filter($units, static fn ($u) => $u['mapping_status'] === 'pending_mapping'));
                $selesai = array_values(array_filter($units, static fn ($u) => $u['mapping_status'] !== 'pending_mapping'));
                $rupiah  = static fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
                $badge   = static function (?string $m): string {
                    return [
                        'exact'       => '<span class="badge bg-success">exact</span>',
                        'alias'       => '<span class="badge bg-info text-dark">alias</span>',
                        'parent_rule' => '<span class="badge bg-primary">aturan induk</span>',
                        'manual'      => '<span class="badge bg-dark">manual</span>',
                    ][$m] ?? '<span class="badge bg-secondary">-</span>';
                };
                ?>

                <div class="alert alert-<?= $pending ? 'warning' : 'success' ?>">
                    <strong><?= count($selesai) ?> unit berhasil</strong> diproses,
                    <strong><?= count($pending) ?> unit perlu mapping</strong>.
                    <?php if ($pending): ?>
                        Data unit pending tersimpan sementara — cukup pilih OPD-nya, tidak perlu unggah ulang Excel.
                    <?php endif; ?>
                </div>

                <?php if ($pending): ?>
                    <h3 class="h6 fw-bold mb-2">Perlu Pemetaan Manual</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:190px">Kode Unit</th>
                                    <th>Nama Unit (Excel)</th>
                                    <th class="text-center" style="width:80px">Program</th>
                                    <th class="text-center" style="width:90px">Kegiatan</th>
                                    <th class="text-center" style="width:80px">Sub</th>
                                    <th class="text-end" style="width:170px">Total Anggaran</th>
                                    <th class="text-center" style="width:120px">Status</th>
                                    <th class="text-center" style="width:110px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending as $u): ?>
                                    <tr>
                                        <td class="small"><?= esc($u['kode_unit_excel']) ?></td>
                                        <td>
                                            <?= esc($u['nama_unit_excel']) ?>
                                            <?php if (!empty($u['nama_saran_opd'])): ?>
                                                <div class="small text-muted">
                                                    Saran: <?= esc($u['nama_saran_opd']) ?>
                                                    (<?= (int) $u['saran_skor'] ?>% mirip) — perlu konfirmasi
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= (int) $u['jumlah_program'] ?></td>
                                        <td class="text-center"><?= (int) $u['jumlah_kegiatan'] ?></td>
                                        <td class="text-center"><?= (int) $u['jumlah_sub'] ?></td>
                                        <td class="text-end"><?= $rupiah($u['total_anggaran']) ?></td>
                                        <td class="text-center"><span class="badge bg-warning text-dark">pending</span></td>
                                        <td class="text-center">
                                            <a href="<?= base_url('adminkab/program_pk/mapping/unit/' . (int) $u['id']) ?>"
                                               class="btn btn-sm btn-success">Mapping</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h3 class="h6 fw-bold mb-2">Unit yang Sudah Diproses</h3>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:190px">Kode Unit</th>
                                <th>Nama Unit (Excel)</th>
                                <th>OPD (master)</th>
                                <th class="text-center" style="width:120px">Metode</th>
                                <th class="text-center" style="width:70px">P</th>
                                <th class="text-center" style="width:70px">K</th>
                                <th class="text-center" style="width:70px">S</th>
                                <th class="text-end" style="width:170px">Total Anggaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selesai as $u): ?>
                                <tr>
                                    <td class="small"><?= esc($u['kode_unit_excel']) ?></td>
                                    <td class="small"><?= esc($u['nama_unit_excel']) ?></td>
                                    <td class="small fw-semibold"><?= esc($u['nama_opd'] ?: '-') ?></td>
                                    <td class="text-center"><?= $badge($u['mapping_method']) ?></td>
                                    <td class="text-center"><?= (int) $u['jumlah_program'] ?></td>
                                    <td class="text-center"><?= (int) $u['jumlah_kegiatan'] ?></td>
                                    <td class="text-center"><?= (int) $u['jumlah_sub'] ?></td>
                                    <td class="text-end"><?= $rupiah($u['total_anggaran']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <a href="<?= base_url('adminkab/program_pk/mapping') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Daftar Batch
                    </a>
                    <a href="<?= base_url('adminkab/program_pk') ?>" class="btn btn-outline-success">
                        Lihat Master Program PK
                    </a>
                </div>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

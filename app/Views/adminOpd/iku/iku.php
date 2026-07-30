<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'IKU - e-SAKIP') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        .iku-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            white-space: nowrap;
        }
        .iku-table th,
        .iku-table td { vertical-align: middle; }
        .iku-table td.text-start { min-width: 160px; }
        .iku-table tbody tr:hover { background-color: #f3faf5; }
        .table-wrap { max-height: 70vh; overflow: auto; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4">
                <h2 class="h4 fw-bold text-success text-center mb-4">
                    Indikator Kinerja Utama (IKU)
                </h2>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-secondary mb-1">Periode</label>
                        <select name="periode" class="form-select" onchange="this.form.submit()">
                            <?php if (empty($grouped_data)): ?>
                                <option value="">-- Belum ada data --</option>
                            <?php else: ?>
                                <?php foreach ($grouped_data as $key => $periode): ?>
                                    <option value="<?= esc($key) ?>" <?= ($selected_periode === $key) ? 'selected' : '' ?>>
                                        <?= esc($periode['period']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="text-muted small">
                        Sasaran, indikator, satuan, dan target IKU diinput langsung di sini — tidak lagi mengikuti Renstra.
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (user_can('iku_opd.create')): ?>
                            <a href="<?= base_url('adminopd/iku/tambah') ?>" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i> Tambah IKU
                            </a>
                            <?php if (empty($is_lintas_opd)): ?>
                                <a href="<?= base_url('adminopd/iku/sync') ?>" class="btn btn-outline-success"
                                   title="Ambil sasaran, indikator, dan target dari Renstra OPD ini">
                                    <i class="fas fa-sync-alt me-1"></i> Sync dari Renstra
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($selected_periode)): ?>
                            <a href="<?= base_url('adminopd/iku/cetak?' . http_build_query(['periode' => $selected_periode])) ?>"
                               target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $this->setData([
                    'show_opd'   => !empty($is_lintas_opd),
                    'base_url'   => 'adminopd/iku',
                    'perm'       => 'iku_opd',
                    'can_manage' => true,
                    'query'      => '',
                ]);
                ?>
                <?= $this->include('templates/iku/_tabel') ?>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>

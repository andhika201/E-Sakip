<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'IKU - e-SAKIP') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
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
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h4 fw-bold text-success text-center mb-4">
                Indikator Kinerja Utama (IKU) - Admin Kabupaten
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

            <!-- ================= FILTER ================= -->
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-secondary mb-1">Mode Tampilan</label>
                    <select name="mode" class="form-select" onchange="this.form.submit()">
                        <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>
                            IKU Pemerintah Kabupaten
                        </option>
                        <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>
                            Rekap IKU Perangkat Daerah
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
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

                <?php if ($mode === 'opd'): ?>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary mb-1">Filter OPD</label>
                        <select name="opd_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua OPD</option>
                            <?php foreach ($opdList ?? [] as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>"
                                    <?= ($opdFilter !== null && (int) $opdFilter === (int) $opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="text-muted small">
                    <?php if ($mode === 'kabupaten'): ?>
                        IKU tingkat kabupaten — sasaran, indikator, satuan, dan targetnya diinput langsung di sini.
                    <?php else: ?>
                        Rekap IKU seluruh Perangkat Daerah (hanya lihat). Penyuntingan dilakukan lewat akun OPD terkait.
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($mode === 'kabupaten' && user_can('iku_kab.create')): ?>
                        <a href="<?= base_url('adminkab/iku/tambah') ?>" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i> Tambah IKU
                        </a>
                        <a href="<?= base_url('adminkab/iku/sync') ?>" class="btn btn-outline-success"
                           title="Ambil sasaran, indikator, dan target dari RPJMD">
                            <i class="fas fa-sync-alt me-1"></i> Sync dari RPJMD
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($selected_periode)): ?>
                        <a href="<?= base_url('adminkab/iku/cetak?' . http_build_query(array_filter([
                            'mode'    => $mode,
                            'opd_id'  => $opdFilter ?? '',
                            'periode' => $selected_periode,
                        ], static fn($v) => $v !== '' && $v !== null))) ?>"
                           target="_blank" class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $this->setData([
                'show_opd'   => ($mode === 'opd'),
                'base_url'   => 'adminkab/iku',
                'perm'       => 'iku_kab',
                'can_manage' => ($mode === 'kabupaten'),
                'query'      => '?mode=' . $mode,
            ]);
            ?>
            <?= $this->include('templates/iku/_tabel') ?>

        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</div>
</body>

</html>

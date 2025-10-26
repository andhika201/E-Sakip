<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Target & Rencana - e-SAKIP</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .table-responsive thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #d1e7dd;
        }

        .table thead th,
        .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Target & Rencana</h2>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <?php
                $requireOpd = $requireOpd ?? true;
                $tahunSelected = (isset($tahun) && $tahun !== '' && $tahun !== 'all') ? (string) $tahun : null;
                $colCount = 15; // Satuan/OPD + 14 kolom lainnya
                ?>

                <!-- FILTER (admin_kab) -->
                <form method="get" action="<?= current_url(); ?>" class="row g-2 align-items-end mb-4">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Tahun</label>
                        <select name="tahun" class="form-select">
                            <option value="all" <?= $tahunSelected === null ? 'selected' : '' ?>>Semua Tahun</option>
                            <?php foreach ($tahunList as $t):
                                $year = is_array($t) ? ($t['tahun'] ?? null) : $t;
                                if ($year === null)
                                    continue; ?>
                                <option value="<?= esc($year) ?>" <?= ($tahunSelected === (string) $year) ? 'selected' : '' ?>>
                                    <?= esc($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <label class="form-label mb-1">OPD <span class="text-danger">*</span></label>
                        <select name="opd_id" class="form-select" required>
                            <option value="">-- Pilih OPD --</option>
                            <?php foreach (($opdList ?? []) as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>" <?= (isset($opdFilter) && (string) $opdFilter === (string) $opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>

                    <div class="col-6 col-md-2">
                        <a class="btn btn-outline-secondary w-100" href="<?= current_url(); ?>">Reset</a>
                    </div>
                </form>

                <?php if ($requireOpd && empty($opdFilter)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        Silakan <strong>pilih OPD</strong> terlebih dahulu untuk menampilkan data.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small">
                            <thead class="table-success fw-bold text-dark">
                                <tr>
                                    <th rowspan="2" class="border p-2 align-middle">Satuan/OPD</th>
                                    <th rowspan="2" class="border p-2 align-middle">Tujuan</th>
                                    <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                                    <th rowspan="2" class="border p-2 align-middle">Indikator</th>
                                    <th rowspan="2" class="border p-2 align-middle">Tahun</th>
                                    <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                                    <th rowspan="2" class="border p-2 align-middle">Target (Renstra)</th>
                                    <th rowspan="2" class="border p-2 align-middle">Rencana Aksi</th>
                                    <th rowspan="2" class="border p-2 align-middle">Baseline (Capaian)</th>
                                    <th colspan="4" class="border p-2 align-middle">Target Triwulan</th>
                                    <th rowspan="2" class="border p-2 align-middle">Penanggung Jawab</th>
                                    <th rowspan="2" class="border p-2 align-middle">Aksi</th>
                                </tr>
                                <tr>
                                    <th>I</th>
                                    <th>II</th>
                                    <th>III</th>
                                    <th>IV</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($grouped)): ?>
                                    <?php
                                    // Total baris untuk rowspan kolom OPD
                                    $totalRowsForOpd = 0;
                                    foreach ($grouped as $sasaranArr) {
                                        foreach ($sasaranArr as $rows) {
                                            $totalRowsForOpd += count($rows);
                                        }
                                    }
                                    $opdPrinted = false;
                                    ?>

                                    <?php foreach ($grouped as $tujuan => $sasaranArr): ?>
                                        <?php
                                        $tujuanRowspan = 0;
                                        foreach ($sasaranArr as $rows) {
                                            $tujuanRowspan += count($rows);
                                        }
                                        $tujuanPrinted = false;
                                        ?>
                                        <?php foreach ($sasaranArr as $sasaran => $rows): ?>
                                            <?php $sasaranRowspan = count($rows);
                                            $sasaranPrinted = false; ?>
                                            <?php foreach ($rows as $row): ?>
                                                <tr>
                                                    <?php if (!$opdPrinted): ?>
                                                        <td rowspan="<?= (int) $totalRowsForOpd ?>" class="text-start">
                                                            <?= esc($opdName ?? '-') ?>
                                                        </td>
                                                        <?php $opdPrinted = true; ?>
                                                    <?php endif; ?>

                                                    <?php if (!$tujuanPrinted): ?>
                                                        <td rowspan="<?= (int) $tujuanRowspan ?>" class="text-start">
                                                            <?= esc($tujuan) ?>
                                                        </td>
                                                        <?php $tujuanPrinted = true; ?>
                                                    <?php endif; ?>

                                                    <?php if (!$sasaranPrinted): ?>
                                                        <td rowspan="<?= (int) $sasaranRowspan ?>" class="text-start">
                                                            <?= esc($sasaran) ?>
                                                        </td>
                                                        <?php $sasaranPrinted = true; ?>
                                                    <?php endif; ?>

                                                    <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                    <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                    <td><?= esc($row['satuan'] ?? '-') ?></td>
                                                    <td><?= esc($row['indikator_target'] ?? '-') ?></td>
                                                    <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                                    <td><?= esc($row['capaian'] ?? '-') ?></td>
                                                    <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                                    <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                                    <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                                    <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                                    <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                                    <td>
                                                        <?php if (empty($row['target_id'])): ?>
                                                            <?php
                                                            $addUrl = base_url('adminKabupaten/target/tambah?rt=' . (int) ($row['renstra_target_id'] ?? 0));
                                                            if (!empty($opdFilter))
                                                                $addUrl .= '&opd_id=' . (int) $opdFilter;
                                                            if ($tahunSelected !== null)
                                                                $addUrl .= '&tahun=' . rawurlencode($tahunSelected);
                                                            ?>
                                                            <a href="<?= $addUrl ?>" class="btn btn-sm btn-success">
                                                                <i class="bi bi-plus-lg"></i> Tambah
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?= base_url('adminKabupaten/target/edit/' . (int) $row['target_id']) ?>"
                                                                class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil-square"></i> Edit
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= $colCount ?>" class="text-muted">Data tidak ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>
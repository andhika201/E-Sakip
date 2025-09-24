<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Target & Rencana - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include('adminOpd/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Target & Rencana</h2>

                <!-- Filter -->
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div class="d-flex gap-2 flex-fill">
                        <select class="form-select" onchange="window.location.href='?tahun='+this.value">
                            <option value="">TAHUN</option>
                            <?php foreach ($tahunList as $t): ?>
                                <option value="<?= esc($t['tahun']) ?>" <?= ($tahun == $t['tahun']) ? 'selected' : '' ?>>
                                    <?= esc($t['tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- <a href="" class="btn btn-success d-flex align-items-center">
                            <i class="fas fa-filter me-2"></i> FILTER
                        </a> -->
                    </div>
                    <!-- <div>
                        <a href="<?= base_url('adminopd/target/tambah') ?>"
                            class="btn btn-success d-flex align-items-center">
                            <i class="fas fa-plus me-1"></i> TAMBAH
                        </a>
                    </div> -->
                </div>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-success fw-bold text-dark">
                            <tr>
                                <th rowspan="2" class="border p-2 align-middle">Tujuan</th>
                                <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                                <th rowspan="2" class="border p-2 align-middle">Indikator</th>
                                <th rowspan="2" class="border p-2 align-middle">Tahun Renja</th>
                                <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                                <th rowspan="2" class="border p-2 align-middle">Target</th>
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
                                <?php foreach ($grouped as $tujuan => $sasaranArr): ?>
                                    <?php $tujuanRowspan = 0;
                                    foreach ($sasaranArr as $indikatorArr)
                                        $tujuanRowspan += count($indikatorArr); ?>
                                    <?php $tujuanPrinted = false; ?>
                                    <?php foreach ($sasaranArr as $sasaran => $indikatorArr): ?>
                                        <?php $sasaranRowspan = count($indikatorArr); ?>
                                        <?php $sasaranPrinted = false; ?>
                                        <?php foreach ($indikatorArr as $row): ?>
                                            <tr>
                                                <?php if (!$tujuanPrinted): ?>
                                                    <td rowspan="<?= $tujuanRowspan ?>"><?= esc($tujuan) ?></td>
                                                    <?php $tujuanPrinted = true; ?>
                                                <?php endif; ?>
                                                <?php if (!$sasaranPrinted): ?>
                                                    <td rowspan="<?= $sasaranRowspan ?>"><?= esc($sasaran) ?></td>
                                                    <?php $sasaranPrinted = true; ?>
                                                <?php endif; ?>
                                                <td><?= esc($row['indikator_sasaran']) ?: '-'?></td>
                                                <td><?= esc($row['indikator_tahun']) ?: '-' ?></td>
                                                <td><?= esc($row['satuan']) ?: '-' ?></td>
                                                <td><?= esc($row['indikator_target']) ?: '-' ?></td>
                                                <td><?= esc($row['rencana_aksi']) ?: '-' ?></td>
                                                <td><?= esc($row['capaian']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_1']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_2']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_3']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_4']) ?: '-' ?></td>
                                                <td><?= esc($row['penanggung_jawab']) ?: '-' ?></td>
                                                <td>
                                                    <?php if (empty($row['rencana_aksi']) && empty($row['capaian']) && empty($row['target_triwulan_1']) && empty($row['target_triwulan_2']) && empty($row['target_triwulan_3']) && empty($row['target_triwulan_4']) && empty($row['penanggung_jawab'])): ?>
                                                        <a href="<?= base_url('adminopd/target/tambah?indikator=' . $row['indikator_id']) ?>"
                                                            class="btn btn-sm btn-success">Tambah</a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminopd/target/edit/' . $row['target_id']) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="15">Data tidak ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monev - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include('adminKabupaten/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Monev</h2>

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
                        <a href="<?= base_url('adminkab/iku/tambah') ?>"
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
                                <th rowspan="2" class="border p-2 align-middle">Rencana Aksi</th>
                                <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                                <th rowspan="2" class="border p-2 align-middle">Baseline (Capaian)</th>
                                <th rowspan="2" class="border p-2 align-middle">Tahun</th>
                                <th rowspan="2" class="border p-2 align-middle">Target 2025</th>
                                <th colspan="4" class="border p-2 align-middle">Target Triwulan</th>
                                <th colspan="4" class="border p-2 align-middle">Capaian 2025</th>
                                <th rowspan="2" class="border p-2 align-middle">Capaian Total</th>
                                <th rowspan="2" class="border p-2 align-middle">Penanggung Jawab</th>
                                <th rowspan="2" class="border p-2 align-middle">Aksi</th>
                            </tr>
                            <tr>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($monevList)): ?>
                                <?php
                                // Grouping: Tujuan > Sasaran > Indikator
                                $grouped = [];
                                foreach ($monevList as $row) {
                                    $tujuan = $row['tujuan_rpjmd'];
                                    $sasaran = $row['sasaran_renstra']; // atau 'sasaran_rpjmd' jika ingin pakai sasaran RPJMD
                                                $indikator = $row['indikator_sasaran'];
                                    if (!isset($grouped[$tujuan]))
                                        $grouped[$tujuan] = [];
                                    if (!isset($grouped[$tujuan][$sasaran]))
                                        $grouped[$tujuan][$sasaran] = [];
                                    $grouped[$tujuan][$sasaran][] = $row;
                                }
                                foreach ($grouped as $tujuan => $sasaranArr):
                                    $tujuanRowspan = 0;
                                    foreach ($sasaranArr as $indikatorArr)
                                        $tujuanRowspan += count($indikatorArr);
                                    $tujuanPrinted = false;
                                    foreach ($sasaranArr as $sasaran => $indikatorArr):
                                        $sasaranRowspan = count($indikatorArr);
                                        $sasaranPrinted = false;
                                        foreach ($indikatorArr as $row):
                                            ?>
                                            <tr>
                                                <?php if (!$tujuanPrinted): ?>
                                                    <td rowspan="<?= $tujuanRowspan ?>"><?= esc($tujuan) ?></td>
                                                    <?php $tujuanPrinted = true; ?>
                                                <?php endif; ?>
                                                <?php if (!$sasaranPrinted): ?>
                                                    <td rowspan="<?= $sasaranRowspan ?>"><?= esc($sasaran) ?></td>
                                                    <?php $sasaranPrinted = true; ?>
                                                <?php endif; ?>
                                                <td><?= esc($row['indikator_sasaran']) ?></td>
                                                <td><?= esc($row['rencana_aksi']) ?: '-' ?></td>
                                                <td><?= esc($row['satuan']) ?: '-' ?></td>
                                                <td><?= esc($row['capaian']) ?: '-' ?></td>
                                                <td><?= esc($row['indikator_tahun']) ?: '-' ?></td>
                                                <td><?= esc($row['indikator_target']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_1']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_2']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_3']) ?: '-' ?></td>
                                                <td><?= esc($row['target_triwulan_4']) ?: '-' ?></td>
                                                <td><?= esc($row['capaian_triwulan_1']) ?: '-' ?></td>
                                                <td><?= esc($row['capaian_triwulan_2']) ?: '-' ?></td>
                                                <td><?= esc($row['capaian_triwulan_3']) ?: '-' ?></td>
                                                <td><?= esc($row['capaian_triwulan_4']) ?: '-' ?></td>
                                                <td>
                                                    <?php
                                                    // Tampilkan total capaian hanya dari field total di tabel monev
                                                    echo (isset($row['total']) && is_numeric($row['total'])) ? number_format($row['total'], 2) : '-';
                                                    ?>
                                                </td>
                                                <td><?= esc($row['penanggung_jawab']) ?: '-' ?></td>
                                                <td>
                                                    <?php
                                                    // Sesuaikan aksi berdasarkan relasi target_rencana dan monev
                                                    $isCapaianKosong = empty($row['capaian_triwulan_1']) && empty($row['capaian_triwulan_2']) && empty($row['capaian_triwulan_3']) && empty($row['capaian_triwulan_4']);
                                                    // Gunakan id target_rencana dari field 'target_id' sesuai rancangan relasi
                                                    $targetRencanaId = isset($row['target_id']) ? $row['target_id'] : (isset($row['target_rencana_id']) ? $row['target_rencana_id'] : null);
                                                    if ($isCapaianKosong && $targetRencanaId):
                                                        ?>
                                        <a href="<?= base_url('adminkab/monev/tambah?target_rencana_id=' . $targetRencanaId) ?>"
                                                            class="btn btn-sm btn-success">Tambah</a>
                                                    <?php elseif (isset($row['monev_id'])): ?>
                                                        <a href="<?= base_url('adminkab/monev/edit/' . $row['monev_id']) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endforeach; endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="19">Data tidak ditemukan.</td>
                                </tr>
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
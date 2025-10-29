<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monev - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
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

        <!-- Navbar/Header -->
        <?= $this->include('adminOpd/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Monev</h2>

                <!-- Filter -->
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div class="d-flex gap-2 flex-fill">
                        <?php $tahunVal = (string) ($tahun ?? 'all'); ?>
                        <select class="form-select"
                            onchange="window.location.href='?tahun='+encodeURIComponent(this.value)">
                            <option value="all" <?= ($tahunVal === 'all' ? 'selected' : '') ?>>Semua Tahun</option>
                            <?php foreach ($tahunList as $t):
                                $yy = is_array($t) ? ($t['tahun'] ?? null) : $t;
                                if ($yy === null)
                                    continue; ?>
                                <option value="<?= esc($yy) ?>" <?= ((string) $tahunVal === (string) $yy) ? 'selected' : '' ?>>
                                    <?= esc($yy) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                                <th rowspan="2" class="border p-2 align-middle">Target (Renstra)</th>
                                <th colspan="4" class="border p-2 align-middle">Target Triwulan</th>
                                <th colspan="4" class="border p-2 align-middle">Capaian Triwulan</th>
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
                                    $tujuan = $row['tujuan_rpjmd'] ?? '—';
                                    $sasaran = $row['sasaran_renstra'] ?? '—';
                                    $grouped[$tujuan][$sasaran][] = $row;
                                }
                                foreach ($grouped as $tujuan => $sasaranArr):
                                    $tujuanRowspan = 0;
                                    foreach ($sasaranArr as $indikatorArr) {
                                        $tujuanRowspan += count($indikatorArr);
                                    }
                                    $tujuanPrinted = false;

                                    foreach ($sasaranArr as $sasaran => $indikatorArr):
                                        $sasaranRowspan = count($indikatorArr);
                                        $sasaranPrinted = false;

                                        foreach ($indikatorArr as $row):
                                            ?>
                                            <tr>
                                                <?php if (!$tujuanPrinted): ?>
                                                    <td rowspan="<?= (int) $tujuanRowspan ?>"><?= esc($tujuan) ?></td>
                                                    <?php $tujuanPrinted = true; ?>
                                                <?php endif; ?>
                                                <?php if (!$sasaranPrinted): ?>
                                                    <td rowspan="<?= (int) $sasaranRowspan ?>"><?= esc($sasaran) ?></td>
                                                    <?php $sasaranPrinted = true; ?>
                                                <?php endif; ?>

                                                <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                                <td><?= esc($row['satuan'] ?? '-') ?></td>
                                                <td><?= esc($row['target_capaian'] ?? '-') ?></td>
                                                <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                <td><?= esc($row['indikator_target'] ?? '-') ?></td>

                                                <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>

                                                <td><?= esc($row['capaian_triwulan_1'] ?? '-') ?></td>
                                                <td><?= esc($row['capaian_triwulan_2'] ?? '-') ?></td>
                                                <td><?= esc($row['capaian_triwulan_3'] ?? '-') ?></td>
                                                <td><?= esc($row['capaian_triwulan_4'] ?? '-') ?></td>

                                                <td>
                                                    <?php
                                                    $tot = $row['monev_total'] ?? null;
                                                    echo ($tot !== null && $tot !== '' && is_numeric($tot)) ? number_format((float) $tot, 0) : '-';
                                                    ?>
                                                </td>

                                                <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                                <td>
                                                    <?php
                                                    $hasMonev = !empty($row['monev_id']);
                                                    $targetRencanaId = $row['target_id'] ?? ($row['target_rencana_id'] ?? null);
                                                    if (!$hasMonev && $targetRencanaId):
                                                        ?>
                                                        <a href="<?= base_url('adminopd/monev/tambah?target_rencana_id=' . (int) $targetRencanaId) ?>"
                                                            class="btn btn-sm btn-success">Tambah</a>
                                                    <?php elseif ($hasMonev): ?>
                                                        <a href="<?= base_url('adminopd/monev/edit/' . (int) $row['monev_id']) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endforeach; endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="19" class="text-muted">Data tidak ditemukan.</td>
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
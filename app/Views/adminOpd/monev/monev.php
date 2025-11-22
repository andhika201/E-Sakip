<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monev - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <!-- Header & Sidebar -->
        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Monev</h2>

                <!-- Alert -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <!-- Filter -->
                <form method="get" class="row g-2 mb-4 align-items-center">
                    <div class="col-md-3">
                        <?php $tahunVal = (string) ($tahun ?? 'all'); ?>
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                            <option value="">Tahun</option>
                            <?php foreach ($tahunList as $t): ?>
                                <?php $yy = is_array($t) ? ($t['tahun'] ?? null) : $t; ?>
                                <?php if ($yy === null)
                                    continue; ?>
                                <option value="<?= esc($yy) ?>" <?= ($tahunVal !== 'all' && (string) $tahunVal === (string) $yy) ? 'selected' : '' ?>>
                                    <?= esc($yy) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-success fw-bold text-dark">
                            <tr>
                                <th rowspan="2" class="border p-2 align-middle">No</th>
                                <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                                <th rowspan="2" class="border p-2 align-middle">Indikator</th>
                                <th rowspan="2" class="border p-2 align-middle">Tahun</th>
                                <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                                <th rowspan="2" class="border p-2 align-middle">Rencana Aksi</th>
                                <th rowspan="2" class="border p-2 align-middle">Baseline (Capaian)</th>
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
                                // Grouping per Sasaran RENSTRA (mirip view target)
                                $grouped = [];
                                foreach ($monevList as $row) {
                                    $sasaran = $row['sasaran_renstra'] ?? '—';
                                    $grouped[$sasaran][] = $row;
                                }

                                $no = 1;
                                foreach ($grouped as $sasaran => $rows):
                                    $rowspan = count($rows);
                                    $printed = false;
                                    foreach ($rows as $row):
                                        ?>
                                        <tr>
                                            <!-- No -->
                                            <td><?= $no++ ?></td>

                                            <!-- Sasaran (rowspan) -->
                                            <?php if (!$printed): ?>
                                                <td rowspan="<?= (int) $rowspan ?>" class="text-start">
                                                    <?= esc($sasaran) ?>
                                                </td>
                                                <?php $printed = true; ?>
                                            <?php endif; ?>

                                            <!-- Indikator & basic info -->
                                            <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                            <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                            <td><?= esc($row['satuan'] ?? '-') ?></td>

                                            <!-- Target & baseline -->
                                            <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                            <td><?= esc($row['target_capaian'] ?? '-') ?></td>

                                            <!-- Target Triwulan -->
                                            <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>

                                            <!-- Capaian Triwulan -->
                                            <td><?= esc($row['capaian_triwulan_1'] ?? '-') ?></td>
                                            <td><?= esc($row['capaian_triwulan_2'] ?? '-') ?></td>
                                            <td><?= esc($row['capaian_triwulan_3'] ?? '-') ?></td>
                                            <td><?= esc($row['capaian_triwulan_4'] ?? '-') ?></td>

                                            <!-- Total -->
                                            <td>
                                                <?php
                                                $tot = $row['monev_total'] ?? null;
                                                echo ($tot !== null && $tot !== '' && is_numeric($tot))
                                                    ? number_format((float) $tot, 0)
                                                    : '-';
                                                ?>
                                            </td>

                                            <!-- PJ -->
                                            <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                            <!-- Aksi -->
                                            <td>
                                                <?php
                                                $hasMonev = !empty($row['monev_id']);
                                                $targetRencanaId = $row['target_id'] ?? ($row['target_rencana_id'] ?? null);
                                                ?>
                                                <?php if (!$hasMonev && $targetRencanaId): ?>
                                                    <a href="<?= base_url('adminopd/monev/tambah?target_rencana_id=' . (int) $targetRencanaId) ?>"
                                                        class="btn btn-primary btn-sm" title="Tambah Target">
                                                        <i class="fas fa-plus"></i></a>
                                                        <?php elseif ($hasMonev): ?>
                                                            <a href="<?= base_url('adminopd/monev/edit/' . (int) $row['monev_id']) ?>"
                                                                class="btn btn-warning btn-sm" title="Edit Monev">
                                                                <i class="fas fa-edit"></i></a>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="18" class="text-muted">Data tidak ditemukan.</td>
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
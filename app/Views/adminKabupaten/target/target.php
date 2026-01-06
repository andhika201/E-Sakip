<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Target & Rencana - e-SAKIP</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h4 fw-bold text-success text-center mb-4">Target &amp; Rencana</h2>

            <!-- Flash message -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>

            <!-- FILTER -->
            <form method="get" class="row g-2 mb-4 align-items-center">

                <!-- Tahun -->
                <div class="col-md-3">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($tahunList as $t): ?>
                            <option value="<?= esc($t['tahun']) ?>"
                                <?= ($tahun == $t['tahun']) ? 'selected' : '' ?>>
                                <?= esc($t['tahun']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mode tampilan -->
                <div class="col-md-3">
                    <select name="mode" class="form-select" onchange="this.form.submit()">
                        <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>
                            Tampilan per OPD (RENSTRA)
                        </option>
                        <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>
                            Tampilan Kabupaten (RPJMD)
                        </option>
                    </select>
                </div>

                <!-- Filter OPD (hanya kalau mode = opd) -->
                <?php if ($mode === 'opd'): ?>
                    <div class="col-md-3">
                        <select name="opd_id" class="form-select" onchange="this.form.submit()">
                            <option value="" <?= ($opdFilter === null) ? 'selected' : '' ?>>Semua OPD</option>
                            <?php foreach ($opdList as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>"
                                    <?= ($opdFilter !== null && (int)$opdFilter === (int)$opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

            </form>

            <?php
            // kolom OPD hanya untuk mode RENSTRA + Semua OPD
            $showOpdCol = ($mode === 'opd' && $opdFilter === null);
            ?>

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle small">
                    <thead class="table-success fw-bold text-dark">
                    <tr>
                        <th rowspan="2" class="border p-2 align-middle">No</th>
                        <?php if ($showOpdCol): ?>
                            <th rowspan="2" class="border p-2 align-middle">OPD</th>
                        <?php endif; ?>
                        <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                        <th rowspan="2" class="border p-2 align-middle">Indikator</th>
                        <th rowspan="2" class="border p-2 align-middle">Tahun</th>
                        <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                        <th rowspan="2" class="border p-2 align-middle">Rencana Aksi</th>
                        <th rowspan="2" class="border p-2 align-middle">Baseline / Target Tahunan</th>
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
                        <?php $no = 1; ?>

                        <?php if ($mode === 'opd'): ?>
                            <!-- ===================== MODE RENSTRA ===================== -->

                            <?php if ($showOpdCol): ?>
                                <!-- RENSTRA + Semua OPD (group = OPD -> Sasaran) -->
                                <?php foreach ($grouped as $opdName => $sasGroups): ?>
                                    <?php
                                    // total baris per OPD untuk rowspan OPD
                                    $opdRowspan = 0;
                                    foreach ($sasGroups as $rowsTmp) {
                                        $opdRowspan += count($rowsTmp);
                                    }
                                    $printedOpd = false;
                                    ?>

                                    <?php foreach ($sasGroups as $sasaran => $rows): ?>
                                        <?php
                                        $sasRowspan     = count($rows);
                                        $printedSasaran = false;
                                        ?>

                                        <?php foreach ($rows as $row): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>

                                                <!-- OPD -->
                                                <?php if (!$printedOpd): ?>
                                                    <td rowspan="<?= $opdRowspan ?>" class="text-start">
                                                        <?= esc($opdName) ?>
                                                    </td>
                                                    <?php $printedOpd = true; ?>
                                                <?php endif; ?>

                                                <!-- Sasaran -->
                                                <?php if (!$printedSasaran): ?>
                                                    <td rowspan="<?= $sasRowspan ?>" class="text-start fw-semibold">
                                                        <?= esc($sasaran) ?>
                                                    </td>
                                                    <?php $printedSasaran = true; ?>
                                                <?php endif; ?>

                                                <!-- Indikator & info -->
                                                <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                <td><?= esc($row['satuan'] ?? '-') ?></td>
                                                <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                                <td><?= esc($row['indikator_target'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                                <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                                <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                                <!-- Aksi (RENSTRA) -->
                                                <td>
                                                    <?php if (empty($row['target_id'])): ?>
                                                        <?php
                                                        $opdForRow = (int) ($row['opd_id'] ?? 0);
                                                        $addUrl = base_url(
                                                            'adminkab/target/tambah'
                                                            . '?rt=' . (int) ($row['renstra_target_id'] ?? 0)
                                                            . '&opd_id=' . $opdForRow
                                                        );
                                                        ?>
                                                        <a href="<?= $addUrl ?>" class="btn btn-primary btn-sm"
                                                           title="Tambah Target">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminkab/target/edit/' . (int)$row['target_id']) ?>"
                                                           class="btn btn-warning btn-sm" title="Edit Target">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <!-- RENSTRA + OPD tertentu (group = Sasaran) -->
                                <?php foreach ($grouped as $sasaran => $rows): ?>
                                    <?php
                                    $rowspan = count($rows);
                                    $printed = false;
                                    ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>

                                            <!-- Sasaran -->
                                            <?php if (!$printed): ?>
                                                <td rowspan="<?= $rowspan ?>" class="text-start fw-semibold">
                                                    <?= esc($sasaran) ?>
                                                </td>
                                                <?php $printed = true; ?>
                                            <?php endif; ?>

                                            <!-- Indikator & info -->
                                            <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                            <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                            <td><?= esc($row['satuan'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                            <td><?= esc($row['indikator_target'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                            <!-- Aksi (RENSTRA) -->
                                            <td>
                                                <?php if (empty($row['target_id'])): ?>
                                                    <?php
                                                    $opdForRow = (int) ($row['opd_id'] ?? 0);
                                                    $addUrl = base_url(
                                                        'adminkab/target/tambah'
                                                        . '?rt=' . (int) ($row['renstra_target_id'] ?? 0)
                                                        . '&opd_id=' . $opdForRow
                                                    );
                                                    ?>
                                                    <a href="<?= $addUrl ?>" class="btn btn-primary btn-sm"
                                                       title="Tambah Target">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= base_url('adminkab/target/edit/' . (int)$row['target_id']) ?>"
                                                       class="btn btn-warning btn-sm" title="Edit Target">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- ===================== MODE KABUPATEN (RPJMD) ===================== -->
                            <?php foreach ($grouped as $sasaran => $rows): ?>
                                <?php
                                $rowspan = count($rows);
                                $printed = false;
                                ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>

                                        <!-- Sasaran RPJMD -->
                                        <?php if (!$printed): ?>
                                            <td rowspan="<?= $rowspan ?>" class="text-start fw-semibold">
                                                <?= esc($sasaran) ?>
                                            </td>
                                            <?php $printed = true; ?>
                                        <?php endif; ?>

                                        <!-- Indikator & info RPJMD -->
                                        <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                        <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                        <td><?= esc($row['satuan'] ?? '-') ?></td>
                                        <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                        <td><?= esc($row['target_tahunan'] ?? $row['indikator_target'] ?? '-') ?></td>

                                        <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                        <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                        <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                        <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>

                                        <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                        <!-- Aksi (RPJMD, pakai route yang sama) -->
                                        <td>
                                            <?php if (empty($row['target_id'])): ?>
                                                <?php
                                                $addUrl = base_url(
                                                    'adminkab/target/tambah'
                                                    . '?rpj=' . (int) ($row['rpjmd_target_id'] ?? 0)
                                                );
                                                ?>
                                                <a href="<?= $addUrl ?>" class="btn btn-primary btn-sm"
                                                   title="Tambah Target RPJMD">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= base_url('adminkab/target/edit/' . (int)$row['target_id']) ?>"
                                                   class="btn btn-warning btn-sm" title="Edit Target RPJMD">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $showOpdCol ? 14 : 13 ?>" class="text-muted">Data tidak ditemukan.</td>
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

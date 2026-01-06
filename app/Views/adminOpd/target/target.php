<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Target & Rencana - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Target &amp; Rencana</h2>

                <!-- Alert -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <!-- Filter -->
                <form method="get" class="row g-2 mb-4 align-items-center">

                    <!-- Tahun (semua role) -->
                    <div class="col-md-3">
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                            <option value="">Tahun</option>
                            <?php foreach ($tahunList as $t): ?>
                                <option value="<?= esc($t['tahun']) ?>" <?= ($tahun == $t['tahun']) ? 'selected' : '' ?>>
                                    <?= esc($t['tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter khusus admin_kab -->
                    <?php if (($role ?? '') === 'admin_kab'): ?>
                        <!-- Mode tampilan -->
                        <div class="col-md-3">
                            <select name="mode" class="form-select" onchange="this.form.submit()">
                                <option value="opd" <?= ($mode ?? 'opd') === 'opd' ? 'selected' : '' ?>>
                                    Tampilan per OPD (RENSTRA)
                                </option>
                                <option value="kabupaten" <?= ($mode ?? '') === 'kabupaten' ? 'selected' : '' ?>>
                                    Tampilan Kabupaten (RPJMD)
                                </option>
                            </select>
                        </div>

                        <!-- Filter OPD hanya jika mode = opd -->
                        <?php if (($mode ?? 'opd') === 'opd'): ?>
                            <div class="col-md-3">
                                <select name="opd_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Semua OPD</option>
                                    <?php foreach (($opdList ?? []) as $opd): ?>
                                        <option value="<?= (int) $opd['id'] ?>" <?= ((int) ($opdFilter ?? 0) === (int) $opd['id']) ? 'selected' : '' ?>>
                                            <?= esc($opd['nama_opd']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

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
                                <th rowspan="2" class="border p-2 align-middle">Baseline</th>
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
                                <?php foreach ($grouped as $sasaran => $rows): ?>
                                    <?php
                                    $rowspan = count($rows);
                                    $printed = false;
                                    ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <!-- No -->
                                            <td><?= $no++ ?></td>

                                            <!-- Sasaran (rowspan) -->
                                            <?php if (!$printed): ?>
                                                <td rowspan="<?= $rowspan ?>" class="text-start">
                                                    <?= esc($sasaran) ?>
                                                </td>
                                                <?php $printed = true; ?>
                                            <?php endif; ?>

                                            <!-- Indikator & basic info -->
                                            <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                            <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                            <td><?= esc($row['satuan'] ?? '-') ?></td>

                                            <!-- Target Rencana -->
                                            <td class="text-start"><?= esc($row['rencana_aksi'] ?? '-') ?></td>
                                            <td><?= esc($row['capaian'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                            <td><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                            <!-- Aksi -->
                                            <td>
                                                <?php
                                                $isKabupatenMode = (($role ?? '') === 'admin_kab' && ($mode ?? '') === 'kabupaten');
                                                ?>

                                                <?php if ($isKabupatenMode): ?>
                                                    <!-- Mode kabupaten (RPJMD) → read only dulu -->
                                                    <span class="text-muted">-</span>
                                                <?php else: ?>
                                                    <?php if (empty($row['target_id'])): ?>
                                                        <?php
                                                        $addUrl = base_url('adminopd/target/tambah?rt=' . (int) ($row['renstra_target_id'] ?? 0));
                                                        if (($role ?? '') === 'admin_kab' && !empty($opdFilter)) {
                                                            $addUrl .= '&opd_id=' . (int) $opdFilter;
                                                        }
                                                        ?>
                                                        <a href="<?= $addUrl ?>" class="btn btn-primary btn-sm" title="Tambah Target">
                                                            <i class="fas fa-plus"></i></a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminopd/target/edit/' . (int) $row['target_id']) ?>"
                                                            class="btn btn-warning btn-sm" title="Edit Target">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="13" class="text-muted">Data tidak ditemukan.</td>
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
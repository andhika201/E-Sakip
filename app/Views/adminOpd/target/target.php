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
                <h2 class="h3 fw-bold text-success text-center mb-4">Target & Rencana</h2>

                <!-- Alert -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <!-- Filter -->
                <form method="get" class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div class="d-flex gap-2 flex-fill">
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                            <option value="">TAHUN</option>
                            <?php foreach ($tahunList as $t): ?>
                                <option value="<?= esc($t['tahun']) ?>" <?= ($tahun == $t['tahun']) ? 'selected' : '' ?>>
                                    <?= esc($t['tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (($role ?? '') === 'admin_kab'): ?>
                            <select name="opd_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua OPD</option>
                                <?php foreach (($opdList ?? []) as $opd): ?>
                                    <option value="<?= (int) $opd['id'] ?>" <?= ((int) ($opdFilter ?? 0) === (int) $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <noscript>
                        <div><button class="btn btn-success" type="submit"><i class="fas fa-filter me-1"></i>
                                Terapkan</button></div>
                    </noscript>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-success fw-bold text-dark">
                            <tr>
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
                                <?php foreach ($grouped as $tujuan => $sasaranArr): ?>
                                    <?php $tujuanRowspan = 0;
                                    foreach ($sasaranArr as $rows) {
                                        $tujuanRowspan += count($rows);
                                    } ?>
                                    <?php $tujuanPrinted = false; ?>
                                    <?php foreach ($sasaranArr as $sasaran => $rows): ?>
                                        <?php $sasaranRowspan = count($rows);
                                        $sasaranPrinted = false; ?>
                                        <?php foreach ($rows as $row): ?>
                                            <tr>
                                                <?php if (!$tujuanPrinted): ?>
                                                    <td rowspan="<?= $tujuanRowspan ?>" class="text-start"><?= esc($tujuan) ?></td>
                                                    <?php $tujuanPrinted = true; ?>
                                                <?php endif; ?>

                                                <?php if (!$sasaranPrinted): ?>
                                                    <td rowspan="<?= $sasaranRowspan ?>" class="text-start"><?= esc($sasaran) ?></td>
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
                                                        $addUrl = base_url('adminopd/target/tambah?rt=' . (int) $row['renstra_target_id']);
                                                        if (($role ?? '') === 'admin_kab' && !empty($opdFilter)) {
                                                            $addUrl .= '&opd_id=' . (int) $opdFilter;
                                                        }
                                                        ?>
                                                        <a href="<?= $addUrl ?>" class="btn btn-sm btn-success">Tambah</a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminopd/target/edit/' . (int) $row['target_id']) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="15" class="text-muted">Data tidak ditemukan.</td>
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
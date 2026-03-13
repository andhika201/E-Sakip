<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING') ?></title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <?php if (function_exists('csrf_token')): ?>
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php endif; ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">
                    Cascading
                </h2>
                <!-- FLASH MESSAGE -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php
                $filters = $filters ?? [
                    'periode' => ''
                ];
                ?>

                <!-- ====================== FILTER ====================== -->

                <form method="GET" action="<?= base_url('adminopd/cascading') ?>"
                    class="d-flex flex-column flex-md-row gap-2 mb-4 align-items-center">
                    <select name="periode" class="form-select" style="flex:1;">
                        <option value="">-- Pilih Periode --</option>
                        <?php foreach ($periode_master ?? [] as $p): ?>
                            <?php
                            $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
                            ?>
                            <option value="<?= $key ?>" <?= ($filters['periode'] == $key) ? 'selected' : '' ?>>
                                <?= $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="d-flex gap-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-search"></i>
                            Tampilkan
                        </button>
                        <a href="<?= base_url('adminopd/cascading') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </a>
                    </div>

                </form>

                <!-- ====================== DATA ====================== -->

                <?php if (empty($filters['periode'])): ?>
                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu
                        untuk menampilkan data Cascading.
                    </div>
                <?php elseif (empty($rows)): ?>
                    <div class="alert alert-info text-center p-4">
                        📁 Belum ada data RENSTRA untuk OPD ini.
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small">
                            <thead class="table-success text-center">
                                <tr>
                                    <th>Tujuan RPJMD</th>
                                    <th>Sasaran RPJMD</th>
                                    <th>Tujuan RENSTRA</th>

                                    <th>Sasaran ESS II</th>
                                    <th>Indikator ESS II</th>

                                    <th>Sasaran ESS III</th>
                                    <th>Indikator ESS III</th>

                                    <th>Sasaran ESS IV / JF</th>
                                    <th>Indikator ESS IV</th>

                                    <th width="90">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($rows as $index => $r): ?>
                                    <tr>
                                        <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>">
                                                <?= esc($r['tujuan_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>">
                                                <?= esc($r['sasaran_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>">
                                                <?= esc($r['renstra_tujuan']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>">
                                                <?= esc($r['renstra_sasaran']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                                                <?= esc($r['indikator_sasaran'] ?? '-') ?>
                                            </td>
                                        <?php endif; ?>
                                        <?php if (empty($r['es3_id'])): ?>
                                            <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                                <td colspan="2" class="text-center">
                                                    <a href="<?= base_url('adminopd/cascading/tambah-es3/' . $r['indikator_id']) ?>"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                                                <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>">
                                                    <?= esc($r['es3_sasaran']) ?>
                                                </td>
                                            <?php endif; ?>

                                            <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                                            <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>">
                                                    <?= esc($r['es3_indikator']) ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (!empty($r['es3_id']) && empty($r['es4_id'])): ?>
                                            <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                <td colspan="2" class="text-center">
                                                    <a href="<?= base_url('adminopd/cascading/tambah-es4/' . $r['es3_indikator_id']) ?>"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                                                <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>">
                                                    <?= esc($r['es4_sasaran']) ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?= $r['es4_indikator'] ?? '-' ?>
                                            </td>
                                        <?php endif; ?>

                                        <td>
                                            <?php if (!empty($r['es4_id'])): ?>
                                                <!-- JIKA SUDAH ADA ES4 -->
                                                <a href="<?= base_url('adminopd/cascading/edit-es4/' . $r['es4_id']) ?>"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="<?= base_url('adminopd/cascading/delete-es4/' . $r['es4_id']) ?>"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </a>

                                            <?php elseif (!empty($r['es3_id'])): ?>

                                                <!-- JIKA HANYA ADA ES3 -->
                                                <a href="<?= base_url('adminopd/cascading/edit-es3/' . $r['es3_id']) ?>"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="<?= base_url('adminopd/cascading/delete-es3/' . $r['es3_id']) ?>"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </a>

                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

</body>

</html>
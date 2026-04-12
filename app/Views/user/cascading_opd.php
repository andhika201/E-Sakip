<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING OPD') ?></title>
    <?= $this->include('user/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('user/templates/header'); ?>

    <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center my-5">
        <div class="container-fluid" style="max-width: 1700px;">
            <div class="bg-white p-4 rounded shadow-sm">
                <h4 class="fw-bold text-center text-success mb-4 text-uppercase">
                    CASCADING & POHON KINERJA PERANGKAT DAERAH
                </h4>

                <?php
                $filters = $filters ?? [
                    'periode' => '',
                    'opd_id' => ''
                ];
                ?>

                <!-- ====================== FILTER ====================== -->
                <div class="row justify-content-center mb-4">
                    <div class="col-12 col-xl-10">
                        <form method="GET" action="<?= base_url('cascading_opd') ?>" class="row g-2 justify-content-center align-items-center">
                            
                            <!-- Filter OPD -->
                            <div class="col-12 col-md-5">
                                <select name="opd_id" class="form-select w-100" onchange="this.form.submit()">
                                    <option value="">-- Pilih Perangkat Daerah --</option>
                                    <?php foreach ($opdList as $opd): ?>
                                        <option value="<?= $opd['id'] ?>" <?= ($filters['opd_id'] == $opd['id']) ? 'selected' : '' ?>>
                                            <?= esc($opd['nama_opd']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter Periode -->
                            <div class="col-12 col-md-4">
                                <select name="periode" class="form-select w-100" onchange="this.form.submit()">
                                    <option value="">-- Pilih Periode --</option>
                                    <?php foreach ($periode_master ?? [] as $p): ?>
                                        <?php $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir']; ?>
                                        <option value="<?= $key ?>" <?= ($filters['periode'] == $key) ? 'selected' : '' ?>>
                                            <?= $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-auto d-flex gap-2">
                                <noscript><button type="submit" class="btn btn-success w-100"><i class="fas fa-filter me-1"></i> Filter</button></noscript>
                                <a href="<?= base_url('cascading_opd') ?>" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- ====================== DATA ====================== -->

                <?php if (empty($filters['periode']) || empty($filters['opd_id'])): ?>
                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Perangkat Daerah</strong> dan <strong>Periode</strong> terlebih dahulu.
                    </div>
                <?php elseif (empty($rows)): ?>
                    <div class="alert alert-info text-center p-4">
                        📁 Belum ada data Cascading untuk filter yang dipilih.
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small table-hover">
                            <thead class="table-success text-center">
                                <tr>
                                    <th>Tujuan RPJMD</th>
                                    <th>Sasaran RPJMD</th>
                                    <th>Tujuan RENSTRA</th>

                                    <th>CSF ESS II</th>
                                    <th>Sasaran ESS II</th>
                                    <th>Indikator ESS II</th>

                                    <th>CSF ESS III</th>
                                    <th>Sasaran ESS III</th>
                                    <th>Indikator ESS III</th>

                                    <th>Sasaran ESS IV / JF</th>
                                    <th>Indikator ESS IV</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($rows as $index => $r): ?>
                                    <tr>
                                        <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>" class="text-start">
                                                <?= esc($r['tujuan_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>" class="text-start">
                                                <?= esc($r['sasaran_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>" class="text-start">
                                                <?= esc($r['renstra_tujuan']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="p-2 text-start">
                                                <?= esc($r['csf_es2'] ?? '-') ?>
                                            </td>
                                            <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="text-start">
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
                                                <td colspan="3" class="text-center text-muted">-</td>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                                                <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="p-2 text-start">
                                                    <?= esc($r['csf_es3'] ?? '-') ?>
                                                </td>
                                                <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-start">
                                                    <?= esc($r['es3_sasaran']) ?>
                                                </td>
                                            <?php endif; ?>

                                            <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                                            <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>" class="text-start">
                                                    <?= esc($r['es3_indikator']) ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (!empty($r['es3_id']) && empty($r['es4_id'])): ?>
                                            <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                <td colspan="2" class="text-center text-muted">-</td>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                                                <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                                    <?= esc($r['es4_sasaran']) ?>
                                                </td>
                                            <?php endif; ?>
                                            <td class="text-start">
                                                <?= $r['es4_indikator'] ?? '-' ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($filters['periode']) && !empty($filters['opd_id']) && !empty($rows)): ?>
                <div class="d-flex gap-2 justify-content-center mt-4">
                    <a href="<?= base_url('cascading_opd/cetak?periode=' . $filters['periode'] . '&opd_id=' . $filters['opd_id']) ?>" class="btn btn-danger" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> Cetak Cascading
                    </a>
                    <a href="<?= base_url('cascading_opd/cetak-pohon?periode=' . $filters['periode'] . '&opd_id=' . $filters['opd_id']) ?>" class="btn btn-primary text-white" target="_blank">
                        <i class="fas fa-sitemap me-1"></i> Cetak Pohon Kinerja
                    </a>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
    
    <?= $this->include('user/templates/footer'); ?>
</body>
</html>

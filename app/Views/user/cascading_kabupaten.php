<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING KABUPATEN') ?></title>
    <?= $this->include('user/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('user/templates/header'); ?>

    <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center my-5">
        <div class="container-fluid" style="max-width: 1700px;">
            <div class="bg-white p-4 rounded shadow-sm">
                <h4 class="fw-bold text-center text-success mb-4 text-uppercase">CASCADING & POHON KINERJA KABUPATEN</h4>

                <?php
                $filters = $filters ?? [
                    'periode' => '',
                ];
                ?>

                <!-- ===================== FORM FILTER ===================== -->
                <div class="row justify-content-center mb-4">
                    <div class="col-12 col-md-8 col-lg-6">
                        <form id="filterForm" method="GET" action="<?= base_url('cascading_kabupaten') ?>" class="d-flex flex-column flex-md-row gap-2 align-items-center w-100">
                            <!-- Periode -->
                            <select id="periodeFilter" name="periode" class="form-select w-100" onchange="this.form.submit()">
                                <option value="">-- Pilih Periode --</option>
                                <?php
                                $periodeList = [];
                                if (!empty($periode_master ?? [])) {
                                    foreach ($periode_master as $p) {
                                        $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
                                        $periodeList[$key] = $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'];
                                    }
                                }
                                foreach ($periodeList as $key => $label): ?>
                                    <option value="<?= esc($key) ?>" <?= ($filters['periode'] === $key) ? 'selected' : '' ?>>
                                        <?= esc($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <noscript><button type="submit" class="btn btn-success ms-2">Filter</button></noscript>
                            <a href="<?= base_url('cascading_kabupaten') ?>" class="btn btn-outline-secondary ms-2 w-auto">
                                Reset
                            </a>
                        </form>
                    </div>
                </div>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($filters['periode'])): ?>

                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu untuk menampilkan data Cascading.
                    </div>

                <?php elseif (empty($rows)): ?>

                    <div class="alert alert-info text-center p-4">
                        📁 Tidak ada data Cascading untuk periode yang dipilih.
                    </div>

                <?php else: ?>

                    <?php
                    [$start, $end] = explode('-', $filters['periode']);
                    $start = (int) trim($start);
                    $end = (int) trim($end);
                    $yearCount = $end - $start + 1;
                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small table-hover">
                            <thead class="table-success text-center">
                                <tr>
                                    <th rowspan="2">Tujuan</th>
                                    <th rowspan="2">CSF</th>
                                    <th rowspan="2">Sasaran</th>
                                    <th rowspan="2">Indikator</th>
                                    <th rowspan="2">Satuan</th>
                                    <th rowspan="2">Baseline</th>

                                    <th colspan="<?= count($years) ?>">Target</th>

                                    <th rowspan="2">Program</th>
                                    <th rowspan="2">OPD</th>
                                </tr>

                                <tr>
                                    <?php foreach ($years as $y): ?>
                                        <th><?= $y ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($rows as $index => $r): ?>
                                    <tr>

                                        <!-- TUJUAN -->
                                        <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>" class="text-start">
                                                <?= esc($r['tujuan_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <!-- CSF -->
                                        <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="p-2 text-start" style="min-width:180px;">
                                                <?= esc($r['csf'] ?? '-') ?>
                                            </td>
                                        <?php endif; ?>

                                        <!-- SASARAN -->
                                        <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="text-start">
                                                <?= esc($r['sasaran_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <!-- INDIKATOR -->
                                        <?php if ($firstShow['indikator'][$r['indikator_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                                                <?= esc($r['indikator_sasaran']) ?>
                                            </td>

                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                <?= esc($r['satuan']) ?>
                                            </td>

                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                <?= esc($r['baseline']) ?>
                                            </td>

                                            <?php foreach ($years as $y): ?>
                                                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                    <?= esc($r['targets'][$y] ?? '-') ?>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <!-- PROGRAM -->
                                        <td class="text-start">
                                            <?= $r['program_kegiatan'] ?? '-' ?>
                                        </td>

                                        <!-- OPD -->
                                        <?php
                                        $key = $r['indikator_id'] . '-' . $r['nama_opd'];
                                        ?>

                                        <?php if ($firstShow['opd'][$key] == $index): ?>
                                            <td rowspan="<?= $rowspan['opd'][$key] ?? 1 ?>" class="text-start">
                                                <?= esc($r['nama_opd']) ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($filters['periode']) && !empty($rows)): ?>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="<?= base_url('cascading_kabupaten/cetak?periode=' . $filters['periode']) ?>" class="btn btn-danger" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i> Cetak Cascading
                        </a>

                        <a href="<?= base_url('cascading_kabupaten/cetak-pohon?periode=' . $filters['periode']) ?>" class="btn btn-primary" target="_blank">
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

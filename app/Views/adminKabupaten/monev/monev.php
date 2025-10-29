<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monev - e-SAKIP</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
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

        .alert-soft {
            background: #fff7e6;
            border: 1px solid #ffe2a6;
            color: #7a5d2f;
        }

        .border-dashed {
            border-style: dashed !important;
        }

        .placeholder-card {
            color: #6c757d;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Monev</h2>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <!-- FILTER -->
                <form method="get" action="<?= current_url(); ?>" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-5">
                        <label class="form-label mb-1">OPD <span class="text-danger">*</span></label>
                        <select name="opd_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Pilih OPD</option>
                            <?php foreach (($opdList ?? []) as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>" <?= ((string) ($opdFilter ?? '') === (string) $opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Tahun</label>
                        <?php $tahunVal = (string) ($tahun ?? 'all'); ?>
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
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

                    <div class="col-6 col-md-2">
                        <a class="btn btn-outline-secondary w-100" href="<?= base_url('adminkab/monev') ?>">Reset</a>
                    </div>
                </form>

                <?php if (empty($opdFilter)): ?>
                    <div class="text-center py-5 placeholder-card border border-dashed rounded-3">
                        <i class="fas fa-filter fa-2x mb-2"></i>
                        <div class="fw-semibold">Belum ada filter OPD</div>
                        <div class="small">Gunakan pilihan OPD di atas untuk menampilkan tabel.</div>
                    </div>
                <?php else: ?>

                    <?php
                    // hanya tampilkan baris yang punya data di tabel monev
                    $monevOnly = array_values(array_filter(($monevList ?? []), function ($r) {
                        return !empty($r['monev_id']); // pastikan join menamai PK monev sebagai monev_id
                    }));
                    ?>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered text-center align-middle small">
                            <thead class="table-success fw-bold text-dark">
                                <tr>
                                    <th rowspan="2" class="border p-2 align-middle">Satuan Kerja</th>
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
                                <?php if (!empty($monevOnly)): ?>
                                    <?php
                                    // nama OPD terpilih
                                    $opdName = '';
                                    foreach (($opdList ?? []) as $o) {
                                        if ((int) $o['id'] === (int) $opdFilter) {
                                            $opdName = $o['nama_opd'];
                                            break;
                                        }
                                    }

                                    // Grouping: Tujuan → Sasaran → rows (hanya monevOnly)
                                    $grouped = [];
                                    foreach ($monevOnly as $row) {
                                        $tujuan = $row['tujuan_rpjmd'] ?? '—';
                                        $sasaran = $row['sasaran_renstra'] ?? '—';
                                        $grouped[$tujuan][$sasaran][] = $row;
                                    }

                                    // total baris untuk rowspan OPD
                                    $opdRowspan = count($monevOnly);
                                    $opdPrinted = false;

                                    foreach ($grouped as $tujuan => $sasaranArr):
                                        $tujuanRowspan = 0;
                                        foreach ($sasaranArr as $rows) {
                                            $tujuanRowspan += count($rows);
                                        }
                                        $tujuanPrinted = false;

                                        foreach ($sasaranArr as $sasaran => $rows):
                                            $sasaranRowspan = count($rows);
                                            $sasaranPrinted = false;

                                            foreach ($rows as $row): ?>
                                                <tr>
                                                    <?php if (!$opdPrinted): ?>
                                                        <td rowspan="<?= (int) $opdRowspan ?>" class="text-start fw-semibold">
                                                            <?= esc($opdName ?: '-') ?>
                                                        </td>
                                                        <?php $opdPrinted = true; ?>
                                                    <?php endif; ?>

                                                    <?php if (!$tujuanPrinted): ?>
                                                        <td rowspan="<?= (int) $tujuanRowspan ?>" class="text-start"><?= esc($tujuan) ?></td>
                                                        <?php $tujuanPrinted = true; ?>
                                                    <?php endif; ?>

                                                    <?php if (!$sasaranPrinted): ?>
                                                        <td rowspan="<?= (int) $sasaranRowspan ?>" class="text-start"><?= esc($sasaran) ?></td>
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
                                                        <?php $tot = $row['monev_total'] ?? null;
                                                        echo ($tot !== null && $tot !== '' && is_numeric($tot)) ? number_format((float) $tot, 0) : '-'; ?>
                                                    </td>

                                                    <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                                    <td>
                                                        <a href="<?= base_url('adminkab/monev/edit/' . (int) $row['monev_id'] . '?' . http_build_query(['opd_id' => (int) $opdFilter, 'tahun' => (string) $tahunVal])) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endforeach; endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="20" class="text-muted">Belum ada data Monev tersimpan untuk kriteria yang
                                            dipilih.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monev - e-SAKIP</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <!-- Header & Sidebar -->
        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

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

                <?php
                // Normalisasi nilai filter
                $tahunVal = (string) ($tahun ?? 'all');        // 'all' atau angka tahun
                $modeVal = $mode ?? 'opd';                    // 'opd' atau 'kab'
                $opdVal = $opdId ?? 'all';                   // 'all' / id opd
                
                // Kolom OPD hanya muncul jika:
                // - mode = opd, dan
                // - filter OPD = "Semua OPD"
                $hasOpdColumn = (
                    $modeVal === 'opd'
                    && ($opdVal === 'all' || $opdVal === '' || $opdVal === null)
                );
                ?>

                <!-- Filter -->
                <form method="get" class="row g-2 mb-4 align-items-center">

                    <!-- Tahun -->
                    <div class="col-md-3">
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

                    <!-- Mode tampilan -->
                    <div class="col-md-3">
                        <select name="mode" class="form-select" onchange="this.form.submit()">
                            <option value="opd" <?= $modeVal === 'opd' ? 'selected' : '' ?>>
                                Tampilan per OPD (RENSTRA)
                            </option>
                            <option value="kab" <?= $modeVal === 'kab' ? 'selected' : '' ?>>
                                Tampilan Kabupaten (RPJMD)
                            </option>
                        </select>
                    </div>

                    <!-- Filter OPD hanya jika mode = opd -->
                    <?php if ($modeVal === 'opd'): ?>
                        <div class="col-md-4">
                            <select name="opd_id" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= ($opdVal === 'all' ? 'selected' : '') ?>>Semua OPD</option>
                                <?php foreach (($opdList ?? []) as $opd): ?>
                                    <option value="<?= (int) $opd['id'] ?>" <?= ((string) $opdVal === (string) $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                </form>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-success fw-bold text-dark">
                            <tr>
                                <th rowspan="2" class="border p-2 align-middle">No</th>

                                <?php if ($hasOpdColumn): ?>
                                    <th rowspan="2" class="border p-2 align-middle">OPD</th>
                                <?php endif; ?>

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
                                // Grouping per Sasaran (baik RENSTRA maupun RPJMD)
                                $grouped = [];
                                foreach ($monevList as $row) {
                                    $sasaran = $row['sasaran_renstra'] ?? '—';
                                    $grouped[$sasaran][] = $row;
                                }

                                $no = 1;

                                // Parameter dasar untuk query string tombol tambah/edit
                                $baseQuery = [
                                    'mode' => $modeVal,
                                    'tahun' => $tahunVal,
                                ];
                                if ($modeVal === 'opd') {
                                    $baseQuery['opd_id'] = $opdVal;
                                }

                                foreach ($grouped as $sasaran => $rows):
                                    $rowspan = count($rows);
                                    $printedSasaran = false;

                                    foreach ($rows as $row):
                                        $targetRencanaId = $row['target_id'] ?? ($row['target_rencana_id'] ?? null);
                                        $hasMonev = !empty($row['monev_id']);
                                        ?>
                                        <tr>
                                            <!-- No -->
                                            <td><?= $no++ ?></td>

                                            <!-- OPD (hanya kalau filter = Semua OPD & mode opd) -->
                                            <?php if ($hasOpdColumn): ?>
                                                <td class="text-start">
                                                    <?= esc($row['nama_opd'] ?? '-') ?>
                                                </td>
                                            <?php endif; ?>

                                            <!-- Sasaran (rowspan) -->
                                            <?php if (!$printedSasaran): ?>
                                                <td rowspan="<?= (int) $rowspan ?>" class="text-start">
                                                    <?= esc($sasaran) ?>
                                                </td>
                                                <?php $printedSasaran = true; ?>
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

                                            <!-- Penanggung Jawab -->
                                            <td class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>

                                            <!-- Aksi -->
                                            <td>
                                                <?php if (!$hasMonev && $targetRencanaId): ?>
                                                    <?php
                                                    $qsTambah = $baseQuery + [
                                                        'target_rencana_id' => (int) $targetRencanaId,
                                                    ];
                                                    ?>
                                                    <a href="<?= base_url('adminkab/monev/tambah?' . http_build_query($qsTambah)) ?>"
                                                        class="btn btn-primary btn-sm" title="Tambah Monev">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                <?php elseif ($hasMonev): ?>
                                                    <?php $qsEdit = $baseQuery; ?>
                                                    <a href="<?= base_url('adminkab/monev/edit/' . (int) $row['monev_id'] . '?' . http_build_query($qsEdit)) ?>"
                                                        class="btn btn-warning btn-sm" title="Edit Monev">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $hasOpdColumn ? 19 : 18 ?>" class="text-muted">
                                        Data tidak ditemukan.
                                    </td>
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
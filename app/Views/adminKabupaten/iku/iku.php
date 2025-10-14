<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IKU - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
        <!-- Navbar/Header -->
        <?= $this->include('adminKabupaten/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4">
                <h2 class="h4 fw-bold text-success text-center mb-4">
                    Indikator Kinerja Utama (IKU)
                </h2>

                <!-- 🔽 Filter Periode -->
                <form method="get" class="mb-4">
                    <div class="row align-items-end g-3">
                        <div class="col-md-6">
                            <label for="periode" class="form-label fw-semibold text-secondary">
                                Periode Renstra
                            </label>
                            <select name="periode" id="periode" class="form-select" required>
                                <option value="">-- Pilih Periode Renstra --</option>
                                <?php foreach ($grouped_data ?? [] as $key => $periode): ?>
                                    <option value="<?= esc($key) ?>" <?= ($selected_periode ?? '') === $key ? 'selected' : '' ?>>
                                        <?= esc($periode['period']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-funnel me-1"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= base_url('adminkab/iku') ?>" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- 🔹 Jika belum pilih periode -->
                <?php if (empty($selected_periode)): ?>
                    <div class="text-center py-5 my-4">
                        <i class="bi bi-calendar2-week text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-secondary">Silakan pilih periode Renstra terlebih dahulu.</h5>
                    </div>

                <?php else: ?>
                    <!-- 🔹 Jika sudah pilih periode, tampilkan tabel -->
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped align-middle small text-center">
                            <thead class="table-success text-dark">
                                <tr>
                                    <th rowspan="2" class="align-middle">No</th>
                                    <th rowspan="2" class="align-middle">Sasaran</th>
                                    <th rowspan="2" class="align-middle">Indikator Sasaran</th>
                                    <th rowspan="2" class="align-middle">Satuan</th>

                                    <?php if (!empty($grouped_data)): ?>
                                        <?php
                                        $totalYears = array_sum(array_map(fn($p) => count($p['years']), $grouped_data));
                                        ?>
                                        <th colspan="<?= $totalYears ?>" class="text-center align-middle">
                                            Target Capaian per Tahun
                                        </th>
                                    <?php else: ?>
                                        <th colspan="5" class="align-middle">Target Capaian per Tahun</th>
                                    <?php endif; ?>

                                    <th rowspan="2" class="align-middle">Definisi Operasional</th>
                                    <th rowspan="2" class="align-middle">Program Pendukung</th>
                                    <th rowspan="2" class="align-middle">Aksi</th>
                                </tr>
                                <tr>
                                    <?php if (!empty($grouped_data)): ?>
                                        <?php foreach ($grouped_data as $period => $dataPeriod): ?>
                                            <?php foreach ($dataPeriod['years'] as $year): ?>
                                                <th class="align-middle"><?= esc($year) ?></th>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $no = 1;
                                $dataSource = $renstra_data ?? [];
                                ?>

                                <?php foreach ($dataSource as $row): ?>
                                    <?php
                                    $sasaranText = ($role === 'admin_kab') ? ($row['rpjmd_sasaran'] ?? $row['sasaran'] ?? '-') : ($row['sasaran'] ?? '-');
                                    $indikatorCount = count($row['indikator_sasaran'] ?? []);
                                    $firstRow = true;
                                    ?>

                                    <?php foreach ($row['indikator_sasaran'] ?? [] as $indikator): ?>
                                        <?php
                                        $iku = null;
                                        foreach ($iku_data ?? [] as $item) {
                                            if (
                                                ($role === 'admin_kab' && (
                                                    ($item['rpjmd_id'] ?? null) == $indikator['id'] ||
                                                    ($item['renstra_id'] ?? null) == $indikator['id']
                                                )) ||
                                                ($role === 'admin_opd' && ($item['renstra_id'] ?? null) == $indikator['id'])
                                            ) {
                                                $iku = $item;
                                                break;
                                            }
                                        }
                                        // dd($iku);
                                        ?>
                                        <tr>
                                            <?php if ($firstRow): ?>
                                                <td rowspan="<?= $indikatorCount ?>" class="align-middle"><?= $no++ ?></td>
                                                <td rowspan="<?= $indikatorCount ?>" class="align-middle text-start">
                                                    <?= esc($sasaranText) ?>
                                                </td>
                                                <?php $firstRow = false; ?>
                                            <?php endif; ?>

                                            <td><?= esc($indikator['indikator_sasaran']) ?></td>
                                            <td><?= esc($indikator['satuan']) ?></td>

                                            <?php foreach ($grouped_data as $period): ?>
                                                <?php foreach ($period['years'] as $year): ?>
                                                    <?php
                                                    $targetValue = '-';
                                                    if (!empty($indikator['target_tahunan'])) {
                                                        foreach ($indikator['target_tahunan'] as $target) {
                                                            if ($target['tahun'] == $year) {
                                                                $targetValue = $target['target_tahunan'];
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <td><?= esc($targetValue) ?></td>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                            <td class="text-start"><?= esc($iku['definisi'] ?? '-') ?></td>
                                            <td class="text-start">
                                                <?php if (!empty($iku['program_pendukung'])): ?>
                                                    <?= nl2br(esc(implode("\n", $iku['program_pendukung']))) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($indikator['id'])): ?>
                                                    <?php if (empty($iku['definisi'])): ?>
                                                        <a href="<?= base_url('adminkab/iku/tambah/' . $indikator['id']) ?>"
                                                            class="btn btn-sm btn-success" title="Tambah IKU">
                                                            <i class="bi bi-plus-circle"></i> Tambah
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminkab/iku/edit/' . $indikator['id']) ?>"
                                                            class="btn btn-sm btn-warning text-dark" title="Edit IKU">
                                                            <i class="bi bi-pencil-square"></i> Edit
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
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
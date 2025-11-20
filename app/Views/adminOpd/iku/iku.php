<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IKU - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Header & Sidebar -->
        <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
        <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4">
                <h2 class="h4 fw-bold text-success text-center mb-4">
                    Indikator Kinerja Utama (IKU)
                </h2>

                <!-- Filter Periode -->
                <form method="get" class="mb-4">
                    <div class="row align-items-end g-3">
                        <div class="col-md-6">
                            <label for="periode" class="form-label fw-semibold text-secondary">
                                Periode
                            </label>
                            <select name="periode" id="periode" class="form-select" required>
                                <option value="">-- Pilih Periode --</option>
                                <?php if (!empty($grouped_data)): ?>
                                    <?php foreach ($grouped_data as $key => $periode): ?>
                                        <option value="<?= esc($key) ?>" <?= ($selected_periode ?? '') === $key ? 'selected' : '' ?>>
                                            <?= esc($periode['period']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-funnel me-1"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= base_url('adminopd/iku') ?>" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <?php if (empty($selected_periode)): ?>

                    <div class="text-center py-5 my-4">
                        <i class="bi bi-calendar2-week text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-secondary">Silakan pilih periode Renstra terlebih dahulu.</h5>
                    </div>

                <?php else: ?>

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
                                        $totalYears = 0;
                                        foreach ($grouped_data as $p) {
                                            $totalYears += count($p['years']);
                                        }
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
                                        <?php foreach ($grouped_data as $periodKey => $dataPeriod): ?>
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
                                $dataSource = ($role === 'admin_kab') ? ($rpjmd_data ?? []) : ($renstra_data ?? []);
                                ?>

                                <?php foreach ($dataSource as $row): ?>
                                    <?php
                                    $sasaranText = ($role === 'admin_kab')
                                        ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
                                        : ($row['sasaran'] ?? '-');

                                    $indikators = $row['indikator_sasaran'] ?? [];
                                    $indikatorCount = count($indikators);
                                    $firstRow = true;
                                    ?>

                                    <?php foreach ($indikators as $indikator): ?>
                                        <?php
                                        // Cari IKU yang terkait indikator ini
                                        $iku = null;
                                        if (!empty($iku_data)) {
                                            foreach ($iku_data as $item) {
                                                if (
                                                    ($role === 'admin_kab' && ($item['rpjmd_id'] ?? null) == $indikator['id']) ||
                                                    ($role === 'admin_opd' && ($item['renstra_id'] ?? null) == $indikator['id'])
                                                ) {
                                                    $iku = $item;
                                                    break;
                                                }
                                            }
                                        }

                                        // Build map target per tahun
                                        $targetMap = [];
                                        if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                            foreach ($indikator['target_tahunan'] as $key => $target) {
                                                if (is_array($target)) {
                                                    $tahun = isset($target['tahun']) ? (int) $target['tahun'] : (int) $key;
                                                    $nilai = $target['target_tahunan']
                                                        ?? ($target['target'] ?? ($target['nilai'] ?? null));
                                                } else {
                                                    $tahun = (int) $key;
                                                    $nilai = $target;
                                                }
                                                if ($tahun) {
                                                    $targetMap[$tahun] = $nilai;
                                                }
                                            }
                                        }

                                        // Ambil list program pendukung (bisa kosong / banyak)
                                        $programList = [];
                                        if (!empty($iku['program_pendukung']) && is_array($iku['program_pendukung'])) {
                                            $programList = $iku['program_pendukung'];
                                        }

                                        // Kalau tidak ada program, buat 1 baris kosong supaya struktur tabel tetap rapi
                                        if (empty($programList)) {
                                            $programList = [null];
                                        }

                                        ?>

                                        <?php foreach ($programList as $pIndex => $programName): ?>
                                            <tr>
                                                <?php if ($firstRow && $pIndex === 0): ?>
                                                    <!-- No & Sasaran hanya di baris pertama sasaran -->
                                                    <td class="align-middle"><?= $no++ ?></td>
                                                    <td class="align-middle text-start"><?= esc($sasaranText) ?></td>
                                                    <?php $firstRow = false; ?>
                                                <?php else: ?>
                                                    <!-- Baris berikutnya dikosongkan -->
                                                    <td></td>
                                                    <td></td>
                                                <?php endif; ?>

                                                <?php if ($pIndex === 0): ?>
                                                    <!-- Indikator & Satuan hanya di baris pertama indikator -->
                                                    <td class="text-start"><?= esc($indikator['indikator_sasaran']) ?></td>
                                                    <td><?= esc($indikator['satuan']) ?></td>

                                                    <!-- Target per tahun hanya di baris pertama indikator -->
                                                    <?php if (!empty($grouped_data)): ?>
                                                        <?php foreach ($grouped_data as $periodKey => $dataPeriod): ?>
                                                            <?php foreach ($dataPeriod['years'] as $year): ?>
                                                                <?php
                                                                $y = (int) $year;
                                                                $value = '-';
                                                                if (isset($targetMap[$y]) && $targetMap[$y] !== '' && $targetMap[$y] !== null) {
                                                                    $value = $targetMap[$y];
                                                                }
                                                                ?>
                                                                <td><?= esc($value) ?></td>
                                                            <?php endforeach; ?>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>

                                                    <!-- Definisi IKU hanya di baris pertama indikator -->
                                                    <td class="text-start"><?= esc($iku['definisi'] ?? '-') ?></td>
                                                <?php else: ?>
                                                    <!-- Baris program berikutnya: kolom indikator, satuan, target, definisi dikosongkan -->
                                                    <td></td>
                                                    <td></td>
                                                    <?php if (!empty($grouped_data)): ?>
                                                        <?php foreach ($grouped_data as $periodKey => $dataPeriod): ?>
                                                            <?php foreach ($dataPeriod['years'] as $year): ?>
                                                                <td></td>
                                                            <?php endforeach; ?>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    <td></td>
                                                <?php endif; ?>

                                                <!-- Program Pendukung: 1 program per baris -->
                                                <td class="text-start">
                                                    <?php if (!empty($programName)): ?>
                                                        <?= esc($programName) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Aksi hanya di baris pertama indikator -->
                                                <?php if ($pIndex === 0): ?>
                                                    <td>
                                                        <?php if (!empty($indikator['id'])): ?>
                                                            <?php if (empty($iku['definisi'])): ?>
                                                                <a href="<?= base_url('adminopd/iku/tambah/' . $indikator['id']) ?>"
                                                                    class="btn btn-sm btn-success" title="Tambah IKU">
                                                                    <i class="bi bi-plus-circle"></i> Tambah
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="<?= base_url('adminopd/iku/edit/' . $indikator['id']) ?>"
                                                                    class="btn btn-sm btn-warning text-dark" title="Edit IKU">
                                                                    <i class="bi bi-pencil-square"></i> Edit
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php else: ?>
                                                    <td></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>

                                    <?php endforeach; ?>
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
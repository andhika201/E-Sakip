<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IKU - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column">
        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">IKU</h2>

                <!-- Tabel IKU -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center align-middle small"
                        style="border-collapse:collapse;">
                        <thead class="table-success fw-bold text-dark">
                            <tr>
                                <th rowspan="2" class="border p-2 align-middle">No</th>
                                <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                                <th rowspan="2" class="border p-2 align-middle">Indikator Sasaran</th>
                                <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                                <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                                    <?php
                                    $totalYears = 0;
                                    foreach ($grouped_data as $periodData) {
                                        $totalYears += count($periodData['years']);
                                    }
                                    ?>
                                    <th colspan="<?= $totalYears ?>" class="border p-2 text-center">Target Capaian per Tahun
                                    </th>
                                <?php else: ?>
                                    <th colspan="5" class="border p-2">Target Capaian per Tahun</th>
                                <?php endif; ?>
                                <th rowspan="2" class="border p-2 align-middle">Definisi Operasional</th>
                                <th rowspan="2" class="border p-2 align-middle">Program Pendukung</th>
                                <th rowspan="2" class="border p-2 align-middle">Aksi</th>
                            </tr>
                            <tr>
                                <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                                    <?php foreach ($grouped_data as $periodIndex => $periodData): ?>
                                        <?php foreach ($periodData['years'] as $year): ?>
                                            <th class="border p-2 year-header" data-periode="<?= $periodIndex ?>"><?= $year ?></th>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <th class="border p-2">2025</th>
                                    <th class="border p-2">2026</th>
                                    <th class="border p-2">2027</th>
                                    <th class="border p-2">2028</th>
                                    <th class="border p-2">2029</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($renstra_data as $row): ?>

                                <?php

                                $iku = null;
                                if (!empty($iku_data)) {
                                    foreach ($iku_data as $item) {
                                        if ($item['renstra_id'] == $row['indikator_id']) {
                                            $iku = $item;
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <?php
                                // Tentukan jumlah program pendukung (minimal 1 agar tidak error)
                                $programPendukung = !empty($iku['program_pendukung']) && is_array($iku['program_pendukung'])
                                    ? $iku['program_pendukung']
                                    : ['-'];
                                $jumlahProgram = count($programPendukung);
                                ?>

                                <tr>
                                    <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>"><?= $no++ ?></td>
                                    <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>">
                                        <?= esc($row['sasaran']) ?></td>
                                    <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>">
                                        <?= esc($row['indikator_sasaran']) ?></td>
                                    <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>">
                                        <?= esc($row['satuan']) ?></td>

                                    <?php foreach ($grouped_data as $periodData): ?>
                                        <?php foreach ($periodData['years'] as $tahun): ?>
                                            <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>">
                                                <?= $row['targets'][$tahun] ?? '-' ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>

                                    <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>">
                                        <?= esc($iku['definisi'] ?? '-') ?>
                                    </td>

                                    <!-- Cetak program pendukung pertama -->
                                    <td class="border p-2 align-middle"><?= esc($programPendukung[0]) ?></td>

                                    <td class="border p-2 align-middle" rowspan="<?= $jumlahProgram ?>">
                                        <?php if (isset($row['indikator_id'])): ?>
                                            <?php if (empty($iku['definisi'])): ?>
                                                <a href="<?= base_url('adminopd/iku/tambah/' . $row['indikator_id']) ?>"
                                                    class="btn btn-sm btn-success">Tambah</a>
                                            <?php else: ?>
                                                <a href="<?= base_url('adminopd/iku/edit/' . $row['indikator_id']) ?>"
                                                    class="btn btn-sm btn-warning">Edit</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Tambahan baris untuk program pendukung berikutnya -->
                                <?php if ($jumlahProgram > 1): ?>
                                    <?php for ($i = 1; $i < $jumlahProgram; $i++): ?>
                                        <tr>
                                            <td class="border p-2 align-middle"><?= esc($programPendukung[$i]) ?></td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
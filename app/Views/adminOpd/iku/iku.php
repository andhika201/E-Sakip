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
                            <?php
                            $no = 1;

                            // Tentukan sumber data berdasarkan role
                            $dataSource = ($role === 'admin_kab') ? $rpjmd_data : $renstra_data;

                            foreach ($dataSource as $row):
                                // Tentukan field sasaran sesuai role
                                $sasaranText = ($role === 'admin_kab') ? $row['sasaran_rpjmd'] : $row['sasaran'];
                                ?>

                                <?php foreach ($row['indikator_sasaran'] as $indikator): ?>

                                <?php
                                // Cari data IKU yang cocok
                                $iku = null;
                                if (isset($iku_data) && is_array($iku_data)) {
                                    foreach ($iku_data as $item) {
                                        $match = false;

                                        if ($role === 'admin_kab' && isset($item['rpjmd_id'], $indikator['id'])) {
                                            $match = ($item['rpjmd_id'] == $indikator['id']);
                                        }
                                        if ($role === 'admin_opd' && isset($item['renstra_id'], $row['indikator_id'])) {
                                            $match = ($item['renstra_id'] == $row['indikator_id']);
                                        }

                                        if ($match) {
                                            $iku = $item;
                                            break;
                                        }
                                    }
                                }
                                ?>

                                    <tr>
                                        <td class="border p-2 align-middle"><?= $no++ ?></td>
                                        <td class="border p-2 align-middle"><?= esc($sasaranText) ?></td>
                                        <td class="border p-2 align-middle"><?= esc($indikator['indikator_sasaran']) ?></td>
                                        <td class="border p-2 align-middle"><?= esc($indikator['satuan']) ?></td>

                                        <?php foreach ($grouped_data as $periodData): ?>
                                            <?php foreach ($indikator['target_tahunan'] as $target): ?>
                                                <td class="border p-2 align-middle">
                                                    <?= esc($target['target_tahunan'] ?? '-') ?>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>

                                        <td class="border p-2 align-middle"><?= esc($iku['definisi'] ?? '-') ?></td>
                                        <td class="border p-2 align-middle"><?= esc($iku['daftar_program_pendukung'] ?? '-') ?>
                                        </td>

                                        <?php if ($role == 'admin_kab'): ?>
                                            <td class="border p-2 align-middle">
                                                <?php if (isset($indikator['id'])): ?>
                                                    <?php if (empty($iku['definisi'])): ?>
                                                        <a href="<?= base_url('adminopd/iku/tambah/' . $indikator['id']) ?>"
                                                            class="btn btn-sm btn-success">Tambah</a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminopd/iku/edit/' . $iku['id']) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php else: ?>
                                            <td class="border p-2 align-middle">
                                                <?php if (isset($row['indikator_id'])): ?>
                                                    <?php if (empty($iku['definisi'])): ?>
                                                        <a href="<?= base_url('adminopd/iku/tambah/' . $row['indikator_id']) ?>"
                                                            class="btn btn-sm btn-success">Tambah</a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminopd/iku/edit/' . $iku['id']) ?>"
                                                            class="btn btn-sm btn-warning">Edit</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RPJMD e-SAKIP</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <?php
    // Hitung jumlah tahun untuk keperluan colspan "Belum ada data"
    $yearsCount = 0;
    if (!empty($rpjmd_grouped) && is_array($rpjmd_grouped)) {
        $firstGroup = reset($rpjmd_grouped);
        if (!empty($firstGroup['years']) && is_array($firstGroup['years'])) {
            $yearsCount = count($firstGroup['years']);
        }
    }
    $emptyColspan = 9 + 2 * $yearsCount; // 4 kolom tetap + 4 kolom tetap lagi + action + 2*years
    ?>
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">
                    RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH
                </h2>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terdapat kesalahan:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center flex-fill me-3 gap-2">
                        <select id="periodFilter" class="form-select" onchange="filterByPeriode()" style="flex: 2;">
                            <?php if (!empty($rpjmd_grouped)): ?>
                                <?php $periodKeys = array_keys($rpjmd_grouped);
                                $latestPeriod = end($periodKeys); ?>
                                <?php foreach ($rpjmd_grouped as $periodKey => $periodData): ?>
                                    <option value="<?= $periodKey ?>" <?= $periodKey === $latestPeriod ? 'selected' : '' ?>>
                                        Periode <?= esc($periodData['period'] ?? $periodKey) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <select id="statusFilter" class="form-select" onchange="filterByStatus()" style="flex: 1;">
                            <option value="all">Semua Status</option>
                            <option value="draft">Draft</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    <a href="<?= base_url('adminkab/rpjmd/tambah') ?>"
                        class="btn btn-success d-flex align-items-center">
                        <i class="fas fa-plus me-1"></i> TAMBAH
                    </a>
                </div>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center small">
                        <thead class="table-success">
                            <tr>
                                <th rowspan="2" class="border p-2 align-middle">STATUS</th>
                                <th rowspan="2" class="border p-2 align-middle">MISI</th>
                                <th rowspan="2" class="border p-2 align-middle">TUJUAN</th>
                                <th rowspan="2" class="border p-2 align-middle">INDIKATOR</th>

                                <th colspan="5" class="border p-2" id="year-header-span-tujuan">TARGET TUJUAN PER TAHUN
                                </th>

                                <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                                <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                                <th rowspan="2" class="border p-2 align-middle">Definisi Operasional</th>
                                <th rowspan="2" class="border p-2 align-middle">SATUAN</th>

                                <th colspan="5" class="border p-2" id="year-header-span-sasaran">TARGET CAPAIAN PER
                                    TAHUN</th>

                                <th rowspan="2" class="border p-2 align-middle">ACTION</th>
                            </tr>
                            <!-- Baris ke-2: tahun tujuan + tahun capaian -->
                            <tr id="year-header-row-tujuan" class="border p-2" style="border-top:2px solid;"></tr>
                            <!-- Baris ke-3: dibiarkan kosong (tetap ada sesuai struktur awal) -->
                            <tr id="year-header-row-sasaran" class="border p-2" style="border-top:2px solid;"></tr>
                        </thead>

                        <tbody id="rpjmd-table-body">
                            <?php if (!empty($rpjmd_grouped)): ?>
                                <?php foreach ($rpjmd_grouped as $periodIndex => $periodData): ?>
                                    <?php foreach (($periodData['misi_data'] ?? []) as $misi): ?>
                                        <?php if (!empty($misi['tujuan'])): ?>
                                            <?php
                                            // Hitung rowspan misi
                                            $misiRowspan = 0;
                                            foreach ($misi['tujuan'] as $tj) {
                                                if (!empty($tj['sasaran'])) {
                                                    foreach ($tj['sasaran'] as $ss) {
                                                        $misiRowspan += !empty($ss['indikator_sasaran']) ? count($ss['indikator_sasaran']) : 1;
                                                    }
                                                } else {
                                                    $misiRowspan += 1;
                                                }
                                            }
                                            $misiCellsPrinted = false;
                                            ?>
                                            <?php foreach ($misi['tujuan'] as $tujuan): ?>
                                                <?php
                                                // Hitung rowspan tujuan
                                                $tujuanRowspan = 0;
                                                if (!empty($tujuan['sasaran'])) {
                                                    foreach ($tujuan['sasaran'] as $ss2) {
                                                        $tujuanRowspan += !empty($ss2['indikator_sasaran']) ? count($ss2['indikator_sasaran']) : 1;
                                                    }
                                                } else {
                                                    $tujuanRowspan = 1;
                                                }
                                                $tujuanCellsPrinted = false;
                                                ?>

                                                <?php if (!empty($tujuan['sasaran'])): ?>
                                                    <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                                                        <?php if (!empty($sasaran['indikator_sasaran'])): ?>
                                                            <?php $firstSasaranRow = true; ?>
                                                            <?php foreach ($sasaran['indikator_sasaran'] as $indikator): ?>
                                                                <tr class="periode-row" data-periode="<?= esc($periodIndex) ?>"
                                                                    data-status="<?= esc($misi['status'] ?? 'draft') ?>">

                                                                    <?php if (!$misiCellsPrinted): ?>
                                                                        <td class="border p-2 align-top text-center" rowspan="<?= $misiRowspan ?>">
                                                                            <?php
                                                                            $status = $misi['status'] ?? 'draft';
                                                                            $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                                            $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                                            ?>
                                                                            <button class="badge <?= $badgeClass ?> border-0"
                                                                                onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)" style="cursor:pointer"
                                                                                title="Klik untuk mengubah status">
                                                                                <?= $statusText ?>
                                                                            </button>
                                                                        </td>
                                                                        <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>">
                                                                            <?= esc($misi['misi'] ?? '-') ?>
                                                                        </td>
                                                                        <?php $misiCellsPrinted = true; ?>
                                                                    <?php endif; ?>

                                                                    <?php if (!$tujuanCellsPrinted): ?>
                                                                        <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                                                            <?= esc($tujuan['tujuan_rpjmd'] ?? '-') ?>
                                                                        </td>
                                                                        <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                                                            <?php if (!empty($tujuan['indikator_tujuan'])): ?>
                                                                                <?php foreach ($tujuan['indikator_tujuan'] as $i => $indTj): ?>
                                                                                    <?= esc($indTj['indikator_tujuan'] ?? '-') ?>                                                <?= $i < count($tujuan['indikator_tujuan']) - 1 ? '<br>' : '' ?>
                                                                                <?php endforeach; ?>
                                                                            <?php else: ?>-<?php endif; ?>
                                                                        </td>
                                                                        <?php $tujuanCellsPrinted = true; ?>
                                                                    <?php endif; ?>

                                                                    <!-- Target Tujuan per tahun -->
                                                                    <span class="year-cells-tujuan" data-periode="<?= esc($periodIndex) ?>">
                                                                        <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                                            <td class="border p-2 align-top text-start">
                                                                                <?php
                                                                                $val = '-';
                                                                                if (!empty($indikator['target_tahunan_tujuan'])) {
                                                                                    foreach ($indikator['target_tahunan_tujuan'] as $t) {
                                                                                        if ((string) $t['tahun'] === (string) $year) {
                                                                                            $val = esc($t['target_tahunan_tujuan']);
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                echo $val;
                                                                                ?>
                                                                            </td>
                                                                        <?php endforeach; ?>
                                                                    </span>

                                                                    <?php if ($firstSasaranRow): ?>
                                                                        <td class="border p-2 align-top text-start"
                                                                            rowspan="<?= max(1, count($sasaran['indikator_sasaran'])) ?>">
                                                                            <?= esc($sasaran['sasaran_rpjmd'] ?? '-') ?>
                                                                        </td>
                                                                        <?php $firstSasaranRow = false; ?>
                                                                    <?php endif; ?>

                                                                    <td class="border p-2 align-top text-start">
                                                                        <?= esc($indikator['indikator_sasaran'] ?? '-') ?>
                                                                    </td>
                                                                    <td class="border p-2 align-top text-start">
                                                                        <?= esc($indikator['definisi_op'] ?? '-') ?>
                                                                    </td>
                                                                    <td class="border p-2 align-top text-start">
                                                                        <?= esc($indikator['satuan'] ?? '-') ?>
                                                                    </td>

                                                                    <!-- Target Capaian per tahun -->
                                                                    <span class="year-cells-sasaran" data-periode="<?= esc($periodIndex) ?>">
                                                                        <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                                            <td class="border p-2 align-top text-start">
                                                                                <?php
                                                                                $val2 = '-';
                                                                                if (!empty($indikator['target_tahunan'])) {
                                                                                    foreach ($indikator['target_tahunan'] as $t2) {
                                                                                        if ((string) $t2['tahun'] === (string) $year) {
                                                                                            $val2 = esc($t2['target_tahunan']);
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                echo $val2;
                                                                                ?>
                                                                            </td>
                                                                        <?php endforeach; ?>
                                                                    </span>

                                                                    <!-- ACTION hanya sekali per misi -->
                                                                    <?php if (!isset($misi['_action_printed'])): ?>
                                                                        <td class="border p-2 align-middle text-center" rowspan="<?= $misiRowspan ?>">
                                                                            <div class="d-flex flex-column align-items-center gap-2">
                                                                                <a href="<?= base_url('adminkab/rpjmd/edit/' . (int) ($misi['id'] ?? 0)) ?>"
                                                                                    class="btn btn-success btn-sm">
                                                                                    <i class="fas fa-edit me-1"></i>Edit
                                                                                </a>
                                                                                <?php
                                                                                $currentStatus = $misi['status'] ?? 'draft';
                                                                                $toggleClass = $currentStatus === 'selesai' ? 'btn-warning' : 'btn-info';
                                                                                $toggleText = $currentStatus === 'selesai' ? 'Set Draft' : 'Set Selesai';
                                                                                $toggleIcon = $currentStatus === 'selesai' ? 'fas fa-undo' : 'fas fa-check';
                                                                                ?>
                                                                                <button class="btn <?= $toggleClass ?> btn-sm"
                                                                                    onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                                                    <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                                                                </button>
                                                                                <button class="btn btn-danger btn-sm"
                                                                                    onclick="confirmDelete(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                                                    <i class="fas fa-trash me-1"></i>Hapus
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <?php $misi['_action_printed'] = true; ?>
                                                                    <?php endif; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Sasaran tanpa indikator_sasaran -->
                                                            <tr class="periode-row" data-periode="<?= esc($periodIndex) ?>"
                                                                data-status="<?= esc($misi['status'] ?? 'draft') ?>">

                                                                <?php if (!$misiCellsPrinted): ?>
                                                                    <td class="border p-2 align-top text-center" rowspan="<?= $misiRowspan ?>">
                                                                        <?php
                                                                        $status = $misi['status'] ?? 'draft';
                                                                        $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                                        $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                                        ?>
                                                                        <button class="badge <?= $badgeClass ?> border-0"
                                                                            onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)"
                                                                            style="cursor:pointer"><?= $statusText ?></button>
                                                                    </td>
                                                                    <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>">
                                                                        <?= esc($misi['misi'] ?? '-') ?>
                                                                    </td>
                                                                    <?php $misiCellsPrinted = true; ?>
                                                                <?php endif; ?>

                                                                <?php if (!$tujuanCellsPrinted): ?>
                                                                    <td class="border p-2 align-top text-start" rowspan="1">
                                                                        <?= esc($tujuan['tujuan_rpjmd'] ?? '-') ?>
                                                                    </td>
                                                                    <td class="border p-2 align-top text-start" rowspan="1">
                                                                        <?php if (!empty($tujuan['indikator_tujuan'])): ?>
                                                                            <?php foreach ($tujuan['indikator_tujuan'] as $i => $indTj): ?>
                                                                                <?= esc($indTj['indikator_tujuan'] ?? '-') ?>                                            <?= $i < count($tujuan['indikator_tujuan']) - 1 ? '<br>' : '' ?>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>-<?php endif; ?>
                                                                    </td>
                                                                    <?php $tujuanCellsPrinted = true; ?>
                                                                <?php endif; ?>

                                                                <span class="year-cells-tujuan" data-periode="<?= esc($periodIndex) ?>">
                                                                    <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                                        <td class="border p-2 align-top text-start">-</td>
                                                                    <?php endforeach; ?>
                                                                </span>

                                                                <td class="border p-2 align-top text-start"><?= esc($sasaran['sasaran_rpjmd'] ?? '-') ?>
                                                                </td>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                                <td class="border p-2 align-top text-start">-</td>

                                                                <span class="year-cells-sasaran" data-periode="<?= esc($periodIndex) ?>">
                                                                    <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                                        <td class="border p-2 align-top text-start">-</td>
                                                                    <?php endforeach; ?>
                                                                </span>

                                                                <?php if (!isset($misi['_action_printed'])): ?>
                                                                    <td class="border p-2 align-middle text-center" rowspan="<?= $misiRowspan ?>">
                                                                        <div class="d-flex flex-column align-items-center gap-2">
                                                                            <a href="<?= base_url('adminkab/rpjmd/edit/' . (int) ($misi['id'] ?? 0)) ?>"
                                                                                class="btn btn-success btn-sm">
                                                                                <i class="fas fa-edit me-1"></i>Edit
                                                                            </a>
                                                                            <?php
                                                                            $currentStatus = $misi['status'] ?? 'draft';
                                                                            $toggleClass = $currentStatus === 'selesai' ? 'btn-warning' : 'btn-info';
                                                                            $toggleText = $currentStatus === 'selesai' ? 'Set Draft' : 'Set Selesai';
                                                                            $toggleIcon = $currentStatus === 'selesai' ? 'fas fa-undo' : 'fas fa-check';
                                                                            ?>
                                                                            <button class="btn <?= $toggleClass ?> btn-sm"
                                                                                onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                                                <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm"
                                                                                onclick="confirmDelete(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                                                <i class="fas fa-trash me-1"></i>Hapus
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                    <?php $misi['_action_printed'] = true; ?>
                                                                <?php endif; ?>
                                                            </tr>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <!-- Tujuan tanpa sasaran -->
                                                    <tr class="periode-row" data-periode="<?= esc($periodIndex) ?>"
                                                        data-status="<?= esc($misi['status'] ?? 'draft') ?>">

                                                        <?php if (!$misiCellsPrinted): ?>
                                                            <td class="border p-2 align-top text-center" rowspan="<?= $misiRowspan ?>">
                                                                <?php
                                                                $status = $misi['status'] ?? 'draft';
                                                                $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                                $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                                ?>
                                                                <button class="badge <?= $badgeClass ?> border-0"
                                                                    onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)"
                                                                    style="cursor:pointer"><?= $statusText ?></button>
                                                            </td>
                                                            <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>">
                                                                <?= esc($misi['misi'] ?? '-') ?>
                                                            </td>
                                                            <?php $misiCellsPrinted = true; ?>
                                                        <?php endif; ?>

                                                        <td class="border p-2 align-top text-start"><?= esc($tujuan['tujuan_rpjmd'] ?? '-') ?>
                                                        </td>
                                                        <td class="border p-2 align-top text-start">
                                                            <?php if (!empty($tujuan['indikator_tujuan'])): ?>
                                                                <?php foreach ($tujuan['indikator_tujuan'] as $i => $indTj): ?>
                                                                    <?= esc($indTj['indikator_tujuan'] ?? '-') ?>                                <?= $i < count($tujuan['indikator_tujuan']) - 1 ? '<br>' : '' ?>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>-<?php endif; ?>
                                                        </td>

                                                        <span class="year-cells-tujuan" data-periode="<?= esc($periodIndex) ?>">
                                                            <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                            <?php endforeach; ?>
                                                        </span>

                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <td class="border p-2 align-top text-start">-</td>

                                                        <span class="year-cells-sasaran" data-periode="<?= esc($periodIndex) ?>">
                                                            <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                            <?php endforeach; ?>
                                                        </span>

                                                        <?php if (!isset($misi['_action_printed'])): ?>
                                                            <td class="border p-2 align-middle text-center" rowspan="<?= $misiRowspan ?>">
                                                                <div class="d-flex flex-column align-items-center gap-2">
                                                                    <a href="<?= base_url('adminkab/rpjmd/edit/' . (int) ($misi['id'] ?? 0)) ?>"
                                                                        class="btn btn-success btn-sm">
                                                                        <i class="fas fa-edit me-1"></i>Edit
                                                                    </a>
                                                                    <?php
                                                                    $currentStatus = $misi['status'] ?? 'draft';
                                                                    $toggleClass = $currentStatus === 'selesai' ? 'btn-warning' : 'btn-info';
                                                                    $toggleText = $currentStatus === 'selesai' ? 'Set Draft' : 'Set Selesai';
                                                                    $toggleIcon = $currentStatus === 'selesai' ? 'fas fa-undo' : 'fas fa-check';
                                                                    ?>
                                                                    <button class="btn <?= $toggleClass ?> btn-sm"
                                                                        onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                                        <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                                                    </button>
                                                                    <button class="btn btn-danger btn-sm"
                                                                        onclick="confirmDelete(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                                        <i class="fas fa-trash me-1"></i>Hapus
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <?php $misi['_action_printed'] = true; ?>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- Misi tanpa tujuan -->
                                            <tr class="periode-row" data-periode="<?= esc($periodIndex) ?>"
                                                data-status="<?= esc($misi['status'] ?? 'draft') ?>">
                                                <td class="border p-2 align-top text-center">
                                                    <?php
                                                    $status = $misi['status'] ?? 'draft';
                                                    $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                    $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                    ?>
                                                    <button class="badge <?= $badgeClass ?> border-0"
                                                        onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)"
                                                        style="cursor:pointer"><?= $statusText ?></button>
                                                </td>
                                                <td class="border p-2 align-top text-start"><?= esc($misi['misi'] ?? '-') ?></td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <td class="border p-2 align-top text-start">-</td>

                                                <span class="year-cells-tujuan-sasaran" data-periode="<?= esc($periodIndex) ?>">
                                                    <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                    <?php endforeach; ?>
                                                </span>
                                                <span class="year-cells" data-periode="<?= esc($periodIndex) ?>">
                                                    <?php foreach (($periodData['years'] ?? []) as $year): ?>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                    <?php endforeach; ?>
                                                </span>

                                                <td class="border p-2 align-middle text-center">
                                                    <div class="d-flex flex-column align-items-center gap-2">
                                                        <a href="<?= base_url('adminkab/rpjmd/edit/' . (int) ($misi['id'] ?? 0)) ?>"
                                                            class="btn btn-success btn-sm">
                                                            <i class="fas fa-edit me-1"></i>Edit
                                                        </a>
                                                        <?php
                                                        $currentStatus = $misi['status'] ?? 'draft';
                                                        $toggleClass = $currentStatus === 'selesai' ? 'btn-warning' : 'btn-info';
                                                        $toggleText = $currentStatus === 'selesai' ? 'Set Draft' : 'Set Selesai';
                                                        $toggleIcon = $currentStatus === 'selesai' ? 'fas fa-undo' : 'fas fa-check';
                                                        ?>
                                                        <button class="btn <?= $toggleClass ?> btn-sm"
                                                            onclick="toggleStatus(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                            <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm"
                                                            onclick="confirmDelete(<?= (int) ($misi['id'] ?? 0) ?>)">
                                                            <i class="fas fa-trash me-1"></i>Hapus
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= (int) max(12, $emptyColspan) ?>"
                                        class="border p-4 text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Belum ada data RPJMD.
                                        <a href="<?= base_url('adminkab/rpjmd/tambah') ?>" class="text-success">
                                            Tambah data pertama
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 text-muted small">
                    <div><span id="visible-data-count">Memuat data...</span></div>
                    <div><i class="fas fa-filter me-1"></i> Filter aktif: <span id="active-filters">Periode
                            terbaru</span></div>
                </div>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>

    <script>
        const periodData = <?= json_encode($rpjmd_grouped ?? []) ?>;

        function confirmDelete(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= base_url('adminkab/rpjmd/delete') ?>/' + id;

            <?php if (csrf_token()): ?>
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '<?= csrf_token() ?>';
                csrfInput.value = '<?= csrf_hash() ?>';
                form.appendChild(csrfInput);
            <?php endif; ?>

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
        }

        // Bangun header tahun di baris kedua (tujuan + capaian)
        function updateTableHeaders(periodKey) {
            const rowTujuan = document.getElementById('year-header-row-tujuan');
            const spanTujuan = document.getElementById('year-header-span-tujuan');
            const rowSasaran = document.getElementById('year-header-row-sasaran');
            const spanSasaran = document.getElementById('year-header-span-sasaran');

            if (!periodData[periodKey] || !periodData[periodKey].years) return;
            const years = periodData[periodKey].years;

            // Sesuaikan colspan pada grup header
            spanTujuan.setAttribute('colspan', years.length);
            spanSasaran.setAttribute('colspan', years.length);

            // Baris kedua: daftar tahun tujuan lalu capaian
            rowTujuan.innerHTML = '';
            years.forEach(y => {
                const th = document.createElement('th');
                th.className = 'border p-2';
                th.textContent = y;
                rowTujuan.appendChild(th);
            });
            years.forEach(y => {
                const th = document.createElement('th');
                th.className = 'border p-2';
                th.textContent = y;
                rowTujuan.appendChild(th);
            });

            // Baris ketiga dikosongkan (tetap ada sesuai struktur)
            rowSasaran.innerHTML = '';
        }

        function filterByPeriode() {
            const filterValue = document.getElementById('periodFilter').value;
            const statusFilterValue = document.getElementById('statusFilter').value;

            const rows = document.querySelectorAll('.periode-row');
            const yearCellsTujuan = document.querySelectorAll('.year-cells-tujuan');
            const yearCellsSasaran = document.querySelectorAll('.year-cells-sasaran');

            rows.forEach(row => row.style.display = 'none');
            yearCellsTujuan.forEach(c => c.style.display = 'none');
            yearCellsSasaran.forEach(c => c.style.display = 'none');

            rows.forEach(row => {
                const rowPeriod = row.getAttribute('data-periode');
                const rowStatus = row.getAttribute('data-status') || 'draft';
                const periodMatch = rowPeriod === filterValue;
                const statusMatch = statusFilterValue === 'all' || rowStatus === statusFilterValue;
                if (periodMatch && statusMatch) row.style.display = '';
            });

            yearCellsTujuan.forEach(c => { if (c.getAttribute('data-periode') === filterValue) c.style.display = ''; });
            yearCellsSasaran.forEach(c => { if (c.getAttribute('data-periode') === filterValue) c.style.display = ''; });

            updateTableHeaders(filterValue);
            updateDataSummary(filterValue, statusFilterValue);
        }

        function filterByStatus() { filterByPeriode(); }

        function updateDataSummary(periodKey, statusFilter) {
            const visibleRows = document.querySelectorAll('.periode-row:not([style*="display: none"])');
            const totalRows = document.querySelectorAll('.periode-row').length;

            const countElement = document.getElementById('visible-data-count');
            if (countElement) countElement.textContent = `Menampilkan ${visibleRows.length} dari ${totalRows} data`;

            const filtersElement = document.getElementById('active-filters');
            if (filtersElement) {
                let filterText = periodKey && periodKey !== 'all'
                    ? (periodData[periodKey] ? `Periode ${periodData[periodKey].period}` : `Periode ${periodKey}`)
                    : 'Semua Periode';
                if (statusFilter && statusFilter !== 'all') {
                    filterText += `, Status: ${statusFilter.charAt(0).toUpperCase() + statusFilter.slice(1)}`;
                }
                filtersElement.textContent = filterText;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const totalRows = document.querySelectorAll('.periode-row').length;
            const countElement = document.getElementById('visible-data-count');
            if (countElement) countElement.textContent = `Menampilkan ${totalRows} dari ${totalRows} data`;

            const filtersElement = document.getElementById('active-filters');
            if (filtersElement) filtersElement.textContent = 'Semua Data';

            filterByPeriode();
        });

        function toggleStatus(misiId) {
            if (!confirm('Apakah Anda yakin ingin mengubah status RPJMD ini?')) return;

            fetch('<?= base_url('adminkab/rpjmd/update-status') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                },
                body: JSON.stringify({ id: misiId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) window.location.reload();
                    else alert('Gagal mengubah status: ' + (data.message || 'Terjadi kesalahan'));
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan saat mengubah status');
                });
        }
    </script>
</body>

</html>
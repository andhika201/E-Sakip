<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'IKU - e-SAKIP') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h4 fw-bold text-success text-center mb-4">
                Indikator Kinerja Utama (IKU) - Admin Kabupaten
            </h2>

            <!-- Flash -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ================= FILTER ================= -->
            <form method="get" class="row g-2 mb-4 align-items-center">

                <!-- Periode -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-secondary mb-1">Periode</label>
                    <select name="periode" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Pilih Periode --</option>
                        <?php if (!empty($grouped_data)): ?>
                            <?php foreach ($grouped_data as $key => $periode): ?>
                                <option value="<?= esc($key) ?>"
                                    <?= ($selected_periode === $key) ? 'selected' : '' ?>>
                                    <?= esc($periode['period']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Mode tampilan -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-secondary mb-1">Mode Tampilan</label>
                    <select name="mode" class="form-select" onchange="this.form.submit()">
                        <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>
                            Tampilan per OPD (RENSTRA)
                        </option>
                        <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>
                            Tampilan Kabupaten (RPJMD)
                        </option>
                    </select>
                </div>

                <!-- Filter OPD (mode OPD saja) -->
                <?php if ($mode === 'opd'): ?>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary mb-1">Filter OPD</label>
                        <select name="opd_id" class="form-select" onchange="this.form.submit()">
                            <option value="" <?= ($opdFilter === null) ? 'selected' : '' ?>>Semua OPD</option>
                            <?php if (!empty($opdList)): ?>
                                <?php foreach ($opdList as $opd): ?>
                                    <option value="<?= (int)$opd['id'] ?>"
                                        <?= ($opdFilter !== null && (int)$opdFilter === (int)$opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>

            <?php if (empty($selected_periode)): ?>

                <div class="text-center py-5 my-4">
                    <i class="bi bi-calendar2-week text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-secondary">
                        Silakan pilih periode Renstra/RPJMD terlebih dahulu.
                    </h5>
                </div>

            <?php else: ?>

                <?php
                // pilih sumber data
                $dataSource = ($mode === 'opd') ? ($renstra_data ?? []) : ($rpjmd_data ?? []);
                $showOpdCol = ($mode === 'opd');

                // helper cari IKU untuk 1 indikator
                $findIku = function (int $indikatorId) use ($iku_data, $mode) {
                    if (empty($iku_data)) {
                        return null;
                    }
                    foreach ($iku_data as $iku) {
                        if ($mode === 'opd' && (int)($iku['renstra_id'] ?? 0) === $indikatorId) {
                            return $iku;
                        }
                        if ($mode === 'kabupaten' && (int)($iku['rpjmd_id'] ?? 0) === $indikatorId) {
                            return $iku;
                        }
                    }
                    return null;
                };

                /* ===================================================
                 * PRE-CALC ROWSPAN OPD & SASARAN
                 * ==================================================*/
                $opdRowspan      = [];
                $sasaranRowspan  = [];

                if (!empty($dataSource)) {
                    foreach ($dataSource as $row) {
                        $sasaranText = ($mode === 'kabupaten')
                            ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
                            : ($row['sasaran'] ?? '-');

                        $opdName   = $row['nama_opd'] ?? '-';
                        $indikators = $row['indikator_sasaran'] ?? [];

                        $rowsForSasaran = 0;

                        foreach ($indikators as $indikator) {
                            $indikatorId = (int)($indikator['id'] ?? 0);
                            $iku         = $findIku($indikatorId);

                            // program pendukung
                            $programList = [];
                            if (!empty($iku['program_pendukung']) && is_array($iku['program_pendukung'])) {
                                $programList = $iku['program_pendukung'];
                            }
                            if (empty($programList)) {
                                $programList = [null];
                            }

                            $rowsForSasaran += count($programList);
                        }

                        $sasKey = $opdName . '|' . $sasaranText;
                        $sasaranRowspan[$sasKey] = ($sasaranRowspan[$sasKey] ?? 0) + $rowsForSasaran;

                        if (!isset($opdRowspan[$opdName])) {
                            $opdRowspan[$opdName] = 0;
                        }
                        $opdRowspan[$opdName] += $rowsForSasaran;
                    }
                }

                // penanda sudah cetak OPD & Sasaran
                $printedOpd     = [];
                $printedSasaran = [];
                ?>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped align-middle small text-center">
                        <thead class="table-success text-dark">
                        <tr>
                            <th rowspan="2" class="align-middle">No</th>
                            <?php if ($showOpdCol): ?>
                                <th rowspan="2" class="align-middle">OPD</th>
                            <?php endif; ?>
                            <th rowspan="2" class="align-middle">Status</th>
                            <th rowspan="2" class="align-middle">Sasaran</th>
                            <th rowspan="2" class="align-middle">Indikator Sasaran</th>
                            <th rowspan="2" class="align-middle">Definisi Operasional</th>
                            <th rowspan="2" class="align-middle">Satuan</th>

                            <?php if (!empty($grouped_data) && isset($grouped_data[$selected_periode])): ?>
                                <?php $years = $grouped_data[$selected_periode]['years'] ?? []; ?>
                                <th colspan="<?= count($years) ?>" class="text-center align-middle">
                                    Target Capaian per Tahun
                                </th>
                            <?php else: ?>
                                <th colspan="5" class="align-middle">Target Capaian per Tahun</th>
                            <?php endif; ?>     
                                                <th rowspan="2" class="align-middle">Program Pendukung</th>
                            <th rowspan="2" class="align-middle">Aksi</th>
                        </tr>
                        <tr>
                            <?php if (!empty($grouped_data) && isset($grouped_data[$selected_periode])): ?>
                                <?php foreach ($grouped_data[$selected_periode]['years'] as $year): ?>
                                    <th class="align-middle"><?= esc($year) ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($dataSource)): ?>
                            <tr>
                                <td colspan="<?= $showOpdCol ? 11 : 10 ?>" class="text-muted">
                                    Data IKU tidak ditemukan untuk filter yang dipilih.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php $no = 1; ?>

                            <?php foreach ($dataSource as $row): ?>
                                <?php
                                $sasaranText = ($mode === 'kabupaten')
                                    ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
                                    : ($row['sasaran'] ?? '-');

                                $opdName    = $row['nama_opd'] ?? '-';
                                $indikators = $row['indikator_sasaran'] ?? [];
                                $sasKey     = $opdName . '|' . $sasaranText;
                                ?>

                                <?php foreach ($indikators as $indikator): ?>
                                    <?php
                                    $indikatorId = (int)($indikator['id'] ?? 0);
                                    $iku         = $findIku($indikatorId);

                                    // target tahunan
                                    $targetMap = [];
                                    if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                        foreach ($indikator['target_tahunan'] as $tYear => $tVal) {
                                            $targetMap[(int)$tYear] = $tVal;
                                        }
                                    }

                                    // program pendukung: array of string
                                    $programList = [];
                                    if (!empty($iku['program_pendukung']) && is_array($iku['program_pendukung'])) {
                                        $programList = $iku['program_pendukung'];
                                    }
                                    if (empty($programList)) {
                                        $programList = [null];
                                    }
                                    $programCount = count($programList);
                                    ?>

                                    <?php foreach ($programList as $pIndex => $programName): ?>
                                        <tr>
                                            <?php if ($pIndex === 0): ?>
                                                <!-- No (per indikator, rowspan = jumlah program) -->
                                                <td rowspan="<?= $programCount ?>" class="align-middle">
                                                    <?= $no++ ?>
                                                </td>
                                            <?php endif; ?>

                                            <!-- OPD (rowspan untuk seluruh baris di OPD tsb) -->
                                            <?php if ($showOpdCol): ?>
                                                <?php if (!isset($printedOpd[$opdName])): ?>
                                                    <td rowspan="<?= $opdRowspan[$opdName] ?? 1 ?>" class="align-middle text-start">
                                                        <?= esc($opdName) ?>
                                                    </td>
                                                    <?php $printedOpd[$opdName] = true; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Status -->
                                                <td rowspan="<?= $programCount ?>" class="align-middle">
                                                    <?php
                                                    $rawStatus   = $iku['status'] ?? null;
                                                    $statusLower = $rawStatus ? strtolower(trim($rawStatus)) : '';

                                                    if ($statusLower === 'tercapai') {
                                                        $badgeClass  = 'bg-success';
                                                        $statusLabel = 'Tercapai';
                                                    } else {
                                                        $badgeClass  = 'bg-secondary';
                                                        $statusLabel = 'Belum';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= esc($statusLabel) ?>
                                                    </span>
                                                </td>
                                            <!-- Sasaran (rowspan untuk semua indikator + program di sasaran tsb) -->
                                            <?php if (!isset($printedSasaran[$sasKey])): ?>
                                                <td rowspan="<?= $sasaranRowspan[$sasKey] ?? 1 ?>" class="align-middle text-start">
                                                    <?= esc($sasaranText) ?>
                                                </td>
                                                <?php $printedSasaran[$sasKey] = true; ?>
                                            <?php endif; ?>

                                            <?php if ($pIndex === 0): ?>
                                                <!-- Indikator -->
                                                <td rowspan="<?= $programCount ?>" class="text-start">
                                                    <?= esc($indikator['indikator_sasaran'] ?? '-') ?>
                                                </td>
 <!-- Definisi -->
                                                <td rowspan="<?= $programCount ?>" class="text-start">
                                                    <?= esc($iku['definisi'] ?? '-') ?>
                                                </td>
                                                <!-- Satuan -->
                                                <td rowspan="<?= $programCount ?>">
                                                    <?= esc($indikator['satuan'] ?? '-') ?>
                                                </td>

                                                <!-- Target per tahun -->
                                                <?php if (!empty($grouped_data) && isset($grouped_data[$selected_periode])): ?>
                                                    <?php foreach ($grouped_data[$selected_periode]['years'] as $year): ?>
                                                        <?php
                                                        $y = (int)$year;
                                                        $value = '-';
                                                        if (isset($targetMap[$y]) && $targetMap[$y] !== '' && $targetMap[$y] !== null) {
                                                            $value = $targetMap[$y];
                                                        }
                                                        ?>
                                                        <td rowspan="<?= $programCount ?>"><?= esc($value) ?></td>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                                                                                                           <?php endif; ?>

                                            <!-- Program pendukung -->
                                            <td class="text-start">
                                                <?php if (!empty($programName)): ?>
                                                    <?= esc($programName) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <?php if ($pIndex === 0): ?>
                                                <!-- Aksi -->
                                                <td rowspan="<?= $programCount ?>">
                                                    <?php if (!empty($indikatorId)): ?>
                                                        <?php if (empty($iku['definisi'])): ?>
                                                            <!-- Belum ada IKU -> tambah -->
                                                            <a href="<?= base_url('adminkab/iku/tambah/' . $indikator['id'] . '?mode=' . $mode) ?>"
                                                               class="btn btn-primary btn-sm" title="Tambah IKU">
                                                                <i class="fas fa-plus"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <!-- Sudah ada IKU -> edit & ubah status -->
                                                            <a href="<?= base_url('adminkab/iku/edit/' . $indikator['id'] . '?mode=' . $mode) ?>"
                                                               class="btn btn-warning btn-sm" title="Edit IKU">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="<?= base_url('adminkab/iku/change_status/' . $indikator['id'] . '?mode=' . $mode) ?>"
                                                               class="btn btn-info btn-sm change-status-btn"
                                                               title="Ubah Status IKU">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php endforeach; ?>
                            <?php endforeach; ?>

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

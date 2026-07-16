<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'IKU - e-SAKIP') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        /* Rapikan tabel IKU */
        .iku-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            white-space: nowrap;
        }
        .iku-table th,
        .iku-table td {
            vertical-align: middle;
        }
        .iku-table td.text-start {
            min-width: 160px;
        }
        .iku-table tbody tr:hover {
            background-color: #f3faf5;
        }
        .table-wrap {
            max-height: 70vh;
            overflow: auto;
        }
    </style>
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

            <?php if (!empty($selected_periode)): ?>
                <div class="mb-3 text-end">
                    <a href="<?= base_url('adminkab/iku/cetak?' . http_build_query(array_filter([
                        'mode' => $mode ?? 'opd',
                        'opd_id' => $opdFilter ?? '',
                        'periode' => $selected_periode ?? '',
                    ], static fn($v) => $v !== '' && $v !== null))) ?>"
                        target="_blank" class="btn btn-outline-danger">
                        <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                    </a>
                </div>
            <?php endif; ?>

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

                // years utk header target
                $years = [];
                if (!empty($grouped_data) && isset($grouped_data[$selected_periode])) {
                    $years = $grouped_data[$selected_periode]['years'] ?? [];
                    if (!is_array($years)) $years = [];
                }
                if (count($years) === 0) {
                    // fallback biar colspan tidak 0
                    $years = [2025, 2026, 2027, 2028, 2029];
                }

                // total kolom untuk colspan "data kosong"
                // No + (OPD?) + Status + Sasaran + Indikator + Definisi + Formula + Satuan + Tahun + Sumber Data + Penanggung Jawab + Aksi
                $totalCols = 10 + ($showOpdCol ? 1 : 0) + count($years);

                // helper cari IKU untuk 1 indikator
                $findIku = function (int $indikatorId) use ($iku_data, $mode) {
                    if (empty($iku_data)) return null;
                    foreach ($iku_data as $iku) {
                        if ($mode === 'opd' && (int)($iku['renstra_id'] ?? 0) === $indikatorId) return $iku;
                        if ($mode === 'kabupaten' && (int)($iku['rpjmd_id'] ?? 0) === $indikatorId) return $iku;
                    }
                    return null;
                };

                /* ===================================================
                 * PRE-CALC ROWSPAN OPD & SASARAN
                 * (Program Pendukung dinonaktifkan -> 1 baris per indikator)
                 * ==================================================*/
                $opdRowspan      = [];
                $sasaranRowspan  = [];

                if (!empty($dataSource)) {
                    foreach ($dataSource as $row) {
                        $sasaranText = ($mode === 'kabupaten')
                            ? ($row['sasaran_rpjmd'] ?? $row['sasaran'] ?? '-')
                            : ($row['sasaran'] ?? '-');

                        $opdName    = $row['nama_opd'] ?? '-';
                        $indikators = $row['indikator_sasaran'] ?? [];

                        $rowsForSasaran = count($indikators);

                        $sasKey = $opdName . '|' . $sasaranText;
                        $sasaranRowspan[$sasKey] = ($sasaranRowspan[$sasKey] ?? 0) + $rowsForSasaran;

                        $opdRowspan[$opdName] = ($opdRowspan[$opdName] ?? 0) + $rowsForSasaran;
                    }
                }

                $printedOpd     = [];
                $printedSasaran = [];
                ?>

                <div class="table-responsive table-wrap mt-3">
                    <table class="table table-bordered table-striped align-middle small iku-table">
                        <thead class="table-success text-dark">
                        <tr class="text-center">
                            <th rowspan="2" class="align-middle">No</th>
                            <?php if ($showOpdCol): ?>
                                <th rowspan="2" class="align-middle">OPD</th>
                            <?php endif; ?>
                            <th rowspan="2" class="align-middle">Status</th>
                            <th rowspan="2" class="align-middle">Sasaran</th>
                            <th rowspan="2" class="align-middle">Indikator Sasaran</th>
                            <th rowspan="2" class="align-middle">Definisi Operasional</th>
                            <th rowspan="2" class="align-middle">Formula / Rumusan Perhitungan</th>
                            <th rowspan="2" class="align-middle">Satuan</th>

                            <th colspan="<?= count($years) ?>" class="align-middle">
                                Target Capaian per Tahun
                            </th>

                            <th rowspan="2" class="align-middle">Sumber Data</th>
                            <th rowspan="2" class="align-middle">Penanggung Jawab</th>
                            <!-- Program Pendukung dinonaktifkan sementara:
                            <th rowspan="2" class="align-middle">Program Pendukung</th>
                            -->
                            <th rowspan="2" class="align-middle">Aksi</th>
                        </tr>

                        <tr class="text-center">
                            <?php foreach ($years as $year): ?>
                                <th class="align-middle"><?= esc($year) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($dataSource)): ?>
                            <tr>
                                <td colspan="<?= $totalCols ?>" class="text-center text-muted py-3">
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

                                    // target tahunan map
                                    $targetMap = [];
                                    if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                        foreach ($indikator['target_tahunan'] as $tYear => $tVal) {
                                            $targetMap[(int)$tYear] = $tVal;
                                        }
                                    }

                                    // Definisi (fallback ke definisi_op RPJMD/RENSTRA bila IKU belum diisi)
                                    $definisiText = trim((string)($iku['definisi'] ?? ''));
                                    if ($definisiText === '') {
                                        $definisiText = trim((string)($indikator['definisi_op'] ?? ''));
                                    }
                                    ?>
                                    <tr>
                                        <!-- No: rowspan mengikuti kelompok terluar (mode OPD -> per OPD; mode kabupaten -> per Sasaran)
                                             agar pagination footer (rowspan-aware, patokan sel pertama) tidak memecah baris gabungan. -->
                                        <?php if ($showOpdCol): ?>
                                            <?php if (!isset($printedOpd[$opdName])): ?>
                                                <td rowspan="<?= $opdRowspan[$opdName] ?? 1 ?>" class="align-middle text-center"><?= $no++ ?></td>
                                            <?php endif; ?>
                                        <?php elseif (!isset($printedSasaran[$sasKey])): ?>
                                            <td rowspan="<?= $sasaranRowspan[$sasKey] ?? 1 ?>" class="align-middle text-center"><?= $no++ ?></td>
                                        <?php endif; ?>

                                        <!-- OPD (rowspan seluruh OPD) -->
                                        <?php if ($showOpdCol): ?>
                                            <?php if (!isset($printedOpd[$opdName])): ?>
                                                <td rowspan="<?= $opdRowspan[$opdName] ?? 1 ?>" class="align-middle text-start">
                                                    <?= esc($opdName) ?>
                                                </td>
                                                <?php $printedOpd[$opdName] = true; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Status -->
                                        <td class="align-middle text-center">
                                            <?php
                                            $rawStatus   = $iku['status'] ?? null;
                                            $statusLower = $rawStatus ? strtolower(trim($rawStatus)) : '';
                                            if ($statusLower === 'selesai') {
                                                $badgeClass  = 'bg-success';
                                                $statusLabel = 'Selesai';
                                            } else {
                                                $badgeClass  = 'bg-warning text-dark';
                                                $statusLabel = 'Draft';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= esc($statusLabel) ?></span>
                                        </td>

                                        <!-- Sasaran (rowspan per sasaran) -->
                                        <?php if (!isset($printedSasaran[$sasKey])): ?>
                                            <td rowspan="<?= $sasaranRowspan[$sasKey] ?? 1 ?>" class="align-middle text-start">
                                                <?= esc($sasaranText) ?>
                                            </td>
                                            <?php $printedSasaran[$sasKey] = true; ?>
                                        <?php endif; ?>

                                        <!-- Indikator -->
                                        <td class="align-middle text-start">
                                            <?= esc($indikator['indikator_sasaran'] ?? '-') ?>
                                        </td>

                                        <!-- Definisi Operasional -->
                                        <td class="align-middle text-start">
                                            <?= esc($definisiText !== '' ? $definisiText : '-') ?>
                                        </td>

                                        <!-- Formula / Rumusan Perhitungan -->
                                        <td class="align-middle text-start">
                                            <?= esc(trim((string)($iku['rumusan_perhitungan'] ?? '')) !== '' ? $iku['rumusan_perhitungan'] : '-') ?>
                                        </td>

                                        <!-- Satuan -->
                                        <td class="align-middle text-center">
                                            <?= esc($indikator['satuan'] ?? '-') ?>
                                        </td>

                                        <!-- Target per tahun -->
                                        <?php foreach ($years as $year): ?>
                                            <?php
                                            $y = (int)$year;
                                            $value = '-';
                                            if (isset($targetMap[$y]) && $targetMap[$y] !== '' && $targetMap[$y] !== null) {
                                                $value = $targetMap[$y];
                                            }
                                            ?>
                                            <td class="align-middle text-center"><?= esc($value) ?></td>
                                        <?php endforeach; ?>

                                        <!-- Sumber Data -->
                                        <td class="align-middle text-start">
                                            <?= esc(trim((string)($iku['sumber_data'] ?? '')) !== '' ? $iku['sumber_data'] : '-') ?>
                                        </td>

                                        <!-- Penanggung Jawab -->
                                        <td class="align-middle text-start">
                                            <?= esc(trim((string)($iku['penanggung_jawab'] ?? '')) !== '' ? $iku['penanggung_jawab'] : '-') ?>
                                        </td>

                                        <!-- Program Pendukung dinonaktifkan sementara -->

                                        <!-- Aksi -->
                                        <td class="align-middle text-center">
                                            <?php if (!empty($indikatorId)): ?>
                                                <?php if (empty($iku['definisi'])): ?>
                                                    <?php if (user_can('iku_kab.create')): ?>
                                                    <a href="<?= base_url('adminkab/iku/tambah/' . $indikator['id'] . '?mode=' . $mode) ?>"
                                                       class="btn btn-primary btn-sm" title="Tambah IKU">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (user_can('iku_kab.update')): ?>
                                                    <a href="<?= base_url('adminkab/iku/edit/' . $indikator['id'] . '?mode=' . $mode) ?>"
                                                       class="btn btn-warning btn-sm" title="Edit IKU">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" action="<?= base_url('adminkab/iku/change_status/' . $indikator['id'] . '?mode=' . $mode) ?>" class="d-inline">
                                                        <button type="submit" class="btn btn-info btn-sm change-status-btn" title="Ubah Status IKU">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </form>
                                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                    </tr>
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

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'RENJA (RKT)') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        /* ===== RKT TABLE STYLES ===== */
        .tbl-rkt-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .tbl-rkt {
            min-width: 1600px;
            border-collapse: collapse;
            font-size: 0.825rem;
            width: 100%;
        }
        .tbl-rkt thead th {
            background-color: #1a7a4a;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            padding: 10px 8px;
            font-weight: 600;
            letter-spacing: 0.3px;
            border: 1px solid #155d38;
            position: sticky;
            top: 0;
            z-index: 2;
            white-space: nowrap;
        }
        .tbl-rkt tbody td {
            vertical-align: middle;
            padding: 8px 10px;
            border: 1px solid #dee2e6;
            line-height: 1.45;
        }
        /* Kolom teks panjang bisa wrap */
        .tbl-rkt td.col-wrap {
            white-space: normal;
            word-break: break-word;
            text-align: left;
        }
        /* Kolom pendek tetap nowrap */
        .tbl-rkt td.col-nowrap {
            white-space: nowrap;
        }
        /* Stripe rows */
        .tbl-rkt tbody tr:nth-child(even) { background-color: #f9fafb; }
        .tbl-rkt tbody tr:hover { background-color: #e8f5ee; }
        /* Kolom SATUAN KERJA – beri warna beda agar mudah dibaca */
        .tbl-rkt td.col-opd {
            background-color: #d4edda;
            font-weight: 600;
            text-align: left;
            white-space: normal;
            word-break: break-word;
        }
        .tbl-rkt td.col-no  { text-align: center; white-space: nowrap; width: 40px; }
        .tbl-rkt td.col-thn { text-align: center; white-space: nowrap; width: 60px; }
        .tbl-rkt td.col-ang { text-align: right;  white-space: nowrap; }
        /* Badge status */
        .badge-selesai { background-color:#198754; color:#fff; padding:4px 8px; border-radius:4px; font-size:.75rem; }
        .badge-draft   { background-color:#ffc107; color:#212529; padding:4px 8px; border-radius:4px; font-size:.75rem; }
        /* Info bar */
        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0fdf5;
            border: 1px solid #b7e4c7;
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 12px;
            font-size: 0.85rem;
            color: #155d38;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h3 fw-bold text-success text-center mb-4">
                RENCANA KERJA TAHUNAN (RKT)
            </h2>

            <!-- Flash message -->
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

            <!-- FILTER -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div class="d-flex gap-2 flex-fill">

                    <!-- Filter Indikator Sasaran Renstra -->
                    <select id="indikatorFilter" class="form-select w-50" onchange="applyFilter()">
                        <option value="all">SEMUA INDIKATOR SASARAN RENSTRA</option>
                        <?php if (!empty($sasaranList ?? [])): ?>
                            <?php foreach ($sasaranList as $s): ?>
                                <option value="<?= esc($s['id']) ?>"
                                    <?= (isset($filter_sasaran) && (string)$filter_sasaran === (string)$s['id']) ? 'selected' : '' ?>>
                                    <?= esc($s['indikator_sasaran']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <!-- Filter Tahun -->
                    <?php $selectedYear = $filter_tahun ?? 'all'; ?>
                    <select id="yearFilter" class="form-select w-25" onchange="applyFilter()">
                        <option value="all" <?= $selectedYear === 'all' ? 'selected' : '' ?>>
                            SEMUA TAHUN
                        </option>
                        <?php if (!empty($available_years ?? [])): ?>
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?= esc($y) ?>" <?= (string)$selectedYear === (string)$y ? 'selected' : '' ?>>
                                    <?= esc($y) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <!-- Filter Status -->
                    <select id="statusFilter" class="form-select w-25" onchange="applyFilter()">
                        <option value="all" <?= ($filter_status ?? 'all') === 'all' ? 'selected' : '' ?>>
                            SEMUA STATUS
                        </option>
                        <option value="draft" <?= ($filter_status ?? '') === 'draft' ? 'selected' : '' ?>>
                            Draft
                        </option>
                        <option value="selesai" <?= ($filter_status ?? '') === 'selesai' ? 'selected' : '' ?>>
                            Selesai
                        </option>
                    </select>
                </div>
            </div>

            <!-- Info ringkas -->
            <div class="info-bar">
                <span>📋 Total indikator: <strong><?= isset($rktdata) ? count($rktdata) : 0 ?></strong></span>
                <span>🏢 OPD: <strong><?= esc($currentOpd['nama_opd'] ?? '-') ?></strong></span>
            </div>

            <!-- TABEL -->
            <div class="tbl-rkt-wrapper">
                <table class="tbl-rkt">
                    <thead>
                    <tr>
                        <th style="min-width:160px;">SATUAN KERJA</th>
                        <th style="width:44px;">NO</th>
                        <th style="width:68px;">TAHUN</th>
                        <th style="min-width:220px;">SASARAN</th>
                        <th style="min-width:220px;">INDIKATOR SASARAN</th>
                        <th style="min-width:200px;">PROGRAM</th>
                        <th style="min-width:200px;">KEGIATAN</th>
                        <th style="min-width:200px;">SUB KEGIATAN</th>
                        <th style="min-width:220px;">INDIKATOR SASARAN SUB KEGIATAN</th>
                        <th style="min-width:90px;">TARGET</th>
                        <th style="min-width:140px;">TARGET ANGGARAN</th>
                        <th style="min-width:100px;">STATUS RKT</th>
                        <th style="min-width:110px;">AKSI</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    helper('format_helper');
                    $no = 1;

                    $groupedBySasaran = [];

                    foreach ($rktdata as $ind) {
                        $sasaran = $ind['sasaran'] ?? 'Tanpa Sasaran';
                        $groupedBySasaran[$sasaran][] = $ind;
                    }
                    // Hitung total rows untuk rowspan SATUAN KERJA
                    $totalRowsAll = 0;
                    foreach ($rktdata as $indTmp) {
                        $rowCount = 0;
                        if (!empty($indTmp['rkts'])) {
                            foreach ($indTmp['rkts'] as $rktTmp) {
                                if (!empty($rktTmp['kegiatan'])) {
                                    foreach ($rktTmp['kegiatan'] as $kTmp) {
                                        $subCount = count($kTmp['subkegiatan'] ?? []);
                                        $rowCount += ($subCount > 0 ? $subCount : 1);
                                    }
                                } else {
                                    $rowCount++;
                                }
                            }
                        } else {
                            $rowCount = 1;
                        }
                        $totalRowsAll += $rowCount;
                    }

                    $firstOpdRow = true;

                    foreach ($groupedBySasaran as $sasaranNama => $indikators):
                        
                        $sasaranRowspan = 0;

                        foreach ($indikators as $indTmp) {

                            if (!empty($indTmp['rkts'])) {

                                foreach ($indTmp['rkts'] as $rktTmp) {

                                    if (!empty($rktTmp['kegiatan'])) {

                                        foreach ($rktTmp['kegiatan'] as $kTmp) {

                                            $subCount = count($kTmp['subkegiatan'] ?? []);
                                            $sasaranRowspan += ($subCount > 0 ? $subCount : 1);

                                        }

                                    } else {

                                        $sasaranRowspan++;

                                    }

                                }

                            } else {

                                $sasaranRowspan++;

                            }

                        }

                        $firstSasaranRow = true;

                        foreach ($indikators as $ind):

                        // ======= HITUNG TAHUN YANG DITAMPILKAN UNTUK INDIKATOR INI =======
                        $displayYear = '-';
                        if ($selectedYear !== 'all') {
                            // kalau user pilih tahun tertentu di filter
                            $displayYear = $selectedYear;
                        } else {
                            // ambil dari target_years renstra
                            $targetYears = $ind['target_years'] ?? [];
                            $targetYears = array_values(array_unique(array_filter($targetYears)));
                            sort($targetYears);
                            if (!empty($targetYears)) {
                                if (count($targetYears) === 1) {
                                    $displayYear = $targetYears[0];
                                } else {
                                    $displayYear = reset($targetYears) . ' - ' . end($targetYears);
                                }
                            }
                        }

                        // Hitung rowspan per indikator
                        $totalSubRows = 0;
                        if (!empty($ind['rkts'])) {
                            foreach ($ind['rkts'] as $rktTmp) {
                                if (!empty($rktTmp['kegiatan'])) {
                                    foreach ($rktTmp['kegiatan'] as $kTmp) {
                                        $subCount = count($kTmp['subkegiatan'] ?? []);
                                        $totalSubRows += ($subCount > 0 ? $subCount : 1);
                                    }
                                } else {
                                    $totalSubRows++;
                                }
                            }
                        } else {
                            $totalSubRows = 1;
                        }

                        // Gabungkan status semua RKT indikator ini
                        $statusList = [];
                        if (!empty($ind['rkts'])) {
                            foreach ($ind['rkts'] as $rktTmp) {
                                if (!empty($rktTmp['status'])) {
                                    $statusList[] = $rktTmp['status'];
                                }
                            }
                        }
                        $statusList        = array_values(array_unique($statusList));
                        $firstIndicatorRow = true;
                        $statusRendered    = false;
                        $actionRendered    = false;

                        // === BELUM ADA RKT UNTUK INDIKATOR INI ===
                        if (empty($ind['rkts'])): ?>
                            <tr>
                                <?php if ($firstOpdRow): ?>
                                    <td rowspan="<?= $totalRowsAll ?>" class="col-opd">
                                        <?= esc($currentOpd['nama_opd'] ?? '-') ?>
                                    </td>
                                    <?php $firstOpdRow = false; ?>
                                <?php endif; ?>

                                <td rowspan="<?= $totalSubRows ?>" class="col-no"><?= $no++ ?></td>
                                <td rowspan="<?= $totalSubRows ?>" class="col-thn">
                                    <?= esc($displayYear) ?>
                                </td>
                                <?php if ($firstSasaranRow): ?>
                                    <td rowspan="<?= $sasaranRowspan ?>" class="col-wrap">
                                        <?= esc($sasaranNama) ?>
                                    </td>
                                    <?php $firstSasaranRow = false; ?>
                                <?php endif; ?>
                                <td rowspan="<?= $totalSubRows ?>" class="col-wrap">
                                    <?= esc($ind['indikator_sasaran']) ?>
                                </td>

                                <td class="col-wrap">-</td>
                                <td class="col-wrap">-</td>
                                <td class="col-wrap">-</td>
                                <td class="col-wrap">-</td>
                                <td class="col-wrap">-</td>
                                <td class="col-ang">-</td>

                                <td rowspan="<?= $totalSubRows ?>" class="text-center align-middle">
                                    <span class="badge bg-secondary">Belum ada RKT</span>
                                </td>

                                <td rowspan="<?= $totalSubRows ?>" class="text-center align-middle">
                                    <a href="<?= base_url('adminopd/rkt/tambah/' . $ind['id']) ?>"
                                       class="btn btn-primary btn-sm" title="Tambah RKT">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                        // === SUDAH ADA RKT ===
                        else:
                            foreach ($ind['rkts'] as $rkt):

                                // Hitung rowspan per program
                                $programRows = 0;
                                if (!empty($rkt['kegiatan'])) {
                                    foreach ($rkt['kegiatan'] as $kTmp) {
                                        $subCount   = count($kTmp['subkegiatan'] ?? []);
                                        $programRows += ($subCount > 0 ? $subCount : 1);
                                    }
                                } else {
                                    $programRows = 1;
                                }

                                $firstProgramRow = true;

                                // --- Ada kegiatan ---
                                if (!empty($rkt['kegiatan'])):
                                    foreach ($rkt['kegiatan'] as $keg):
                                        $subCount    = count($keg['subkegiatan'] ?? []);
                                        $kegRows     = ($subCount > 0 ? $subCount : 1);
                                        $firstKegRow = true;

                                        // --- Ada subkegiatan ---
                                        if (!empty($keg['subkegiatan'])):
                                            foreach ($keg['subkegiatan'] as $sub): ?>
                                                <tr>
                                                    <?php if ($firstOpdRow): ?>
                                                        <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                                            <?= esc($currentOpd['nama_opd'] ?? '-') ?>
                                                        </td>
                                                        <?php $firstOpdRow = false; ?>
                                                    <?php endif; ?>

                                                    <?php if ($firstIndicatorRow): ?>
                                                        <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                            <?= $no++ ?>
                                                        </td>
                                                        <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                            <?= esc($displayYear) ?>
                                                        </td>
                                                        <?php if ($firstSasaranRow): ?>
                                                            <td rowspan="<?= $sasaranRowspan ?>" class="align-middle text-start">
                                                                <?= esc($sasaranNama) ?>
                                                            </td>
                                                            <?php $firstSasaranRow = false; ?>
                                                        <?php endif; ?>
                                                        <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                                            <?= esc($ind['indikator_sasaran']) ?>
                                                        </td>
                                                        <?php $firstIndicatorRow = false; ?>
                                                    <?php endif; ?>

                                                    <?php if ($firstProgramRow): ?>
                                                        <td rowspan="<?= $programRows ?>" class="align-middle text-start">
                                                            <?= esc($rkt['program_nama'] ?? '-') ?>
                                                        </td>
                                                        <?php $firstProgramRow = false; ?>
                                                    <?php endif; ?>

                                                    <?php if ($firstKegRow): ?>
                                                        <td rowspan="<?= $kegRows ?>" class="align-middle text-start">
                                                            <?= esc($keg['kegiatan'] ?? '-') ?>
                                                        </td>
                                                        <?php $firstKegRow = false; ?>
                                                    <?php endif; ?>

                                                    <td class="text-start">
                                                        <?= esc($sub['sub_kegiatan'] ?? '-') ?>
                                                    </td>
                                                    
                                                    <td class="text-start">
                                                        <?= esc($sub['indikator_sasaran_sub_kegiatan'] ?? '-') ?>
                                                    </td>

                                                    <td class="text-start">
                                                        <?= esc($sub['target'] ?? '-') ?>
                                                    </td>

                                                    <td class="text-end">
                                                        <?= formatRupiah($sub['anggaran'] ?? 0) ?>
                                                    </td>

                                                    <?php if (!$statusRendered): ?>
                                                        <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                            <?php if (empty($statusList)): ?>
                                                                <span class="badge bg-secondary">-</span>
                                                            <?php else: ?>
                                                                <?php foreach ($statusList as $st): ?>
                                                                    <?php if ($st === 'selesai'): ?>
                                                                        <span class="badge bg-success">Selesai</span>
                                                                    <?php elseif ($st === 'draft'): ?>
                                                                        <span class="badge bg-warning text-dark">Draft</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary"><?= esc($st) ?></span>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php $statusRendered = true; ?>
                                                    <?php endif; ?>

                                                    <?php if (!$actionRendered): ?>
                                                        <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                            <div class="d-flex justify-content-center gap-2">
                                                                <!-- Edit -->
                                                                <a href="<?= base_url('adminopd/rkt/edit/' . $ind['id']) ?>"
                                                                   class="btn btn-warning btn-sm" title="Edit RKT">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>

                                                                <!-- Hapus -->
                                                                <form action="<?= base_url('adminopd/rkt/delete-indikator') ?>"
                                                                    method="post"
                                                                    onsubmit="return confirm('Yakin ingin menghapus seluruh RKT indikator ini?')">

                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="indikator_id" value="<?= esc($ind['id']) ?>">

                                                                    <button type="submit"
                                                                            class="btn btn-danger btn-sm"
                                                                            title="Hapus seluruh RKT indikator">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>

                                                                </form>

                                                                <!-- Ubah Status -->
                                                                <form action="<?= base_url('adminopd/rkt/update-status') ?>"
                                                                      method="post" class="d-inline">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="indikator_id"
                                                                           value="<?= esc($ind['id']) ?>">
                                                                    <input type="hidden" name="tahun"
                                                                           value="<?= esc($selectedYear === 'all' ? '' : $selectedYear) ?>">
                                                                    <button type="submit"
                                                                            class="btn btn-info btn-sm"
                                                                            title="Ubah Status Draft/Selesai">
                                                                        <i class="fas fa-sync-alt"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                        <?php $actionRendered = true; ?>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; // foreach sub ?>
                                        <?php
                                        // --- Kegiatan tanpa subkegiatan ---
                                        else: ?>
                                            <tr>
                                                <?php if ($firstOpdRow): ?>
                                                    <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                                        <?= esc($currentOpd['nama_opd'] ?? '-') ?>
                                                    </td>
                                                    <?php $firstOpdRow = false; ?>
                                                <?php endif; ?>

                                                <?php if ($firstIndicatorRow): ?>
                                                    <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                        <?= $no++ ?>
                                                    </td>
                                                    <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                        <?= esc($displayYear) ?>
                                                    </td>
                                                    <?php if ($firstSasaranRow): ?>
                                                        <td rowspan="<?= $sasaranRowspan ?>" class="align-middle text-start">
                                                            <?= esc($sasaranNama) ?>
                                                        </td>
                                                        <?php $firstSasaranRow = false; ?>
                                                    <?php endif; ?>
                                                    <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                                        <?= esc($ind['indikator_sasaran']) ?>
                                                    </td>
                                                    <?php $firstIndicatorRow = false; ?>
                                                <?php endif; ?>

                                                <?php if ($firstProgramRow): ?>
                                                    <td rowspan="<?= $programRows ?>" class="align-middle text-start">
                                                        <?= esc($rkt['program_nama'] ?? '-') ?>
                                                    </td>
                                                    <?php $firstProgramRow = false; ?>
                                                <?php endif; ?>

                                                <td rowspan="<?= $kegRows ?>" class="align-middle text-start">
                                                    <?= esc($keg['kegiatan'] ?? '-') ?>
                                                </td>

                                                <td class="text-start">-</td>
                                                <td class="text-start">-</td>
                                                <td class="text-start">-</td>
                                                <td class="text-end">-</td>

                                                <?php if (!$statusRendered): ?>
                                                    <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                        <?php if (empty($statusList)): ?>
                                                            <span class="badge bg-secondary">-</span>
                                                        <?php else: ?>
                                                            <?php foreach ($statusList as $st): ?>
                                                                <?php if ($st === 'selesai'): ?>
                                                                    <span class="badge bg-success">Selesai</span>
                                                                <?php elseif ($st === 'draft'): ?>
                                                                    <span class="badge bg-warning text-dark">Draft</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary"><?= esc($st) ?></span>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php $statusRendered = true; ?>
                                                <?php endif; ?>

                                                <?php if (!$actionRendered): ?>
                                                    <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <a href="<?= base_url('adminopd/rkt/edit/' . $ind['id']) ?>"
                                                               class="btn btn-warning btn-sm" title="Edit RKT">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="<?= base_url('adminopd/rkt/update-status') ?>"
                                                                  method="post" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="indikator_id"
                                                                       value="<?= esc($ind['id']) ?>">
                                                                <input type="hidden" name="tahun"
                                                                       value="<?= esc($selectedYear === 'all' ? '' : $selectedYear) ?>">
                                                                <button type="submit"
                                                                        class="btn btn-info btn-sm"
                                                                        title="Ubah Status Draft/Selesai">
                                                                    <i class="fas fa-sync-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                    <?php $actionRendered = true; ?>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endif; // end if subkegiatan ?>
                                    <?php endforeach; // foreach kegiatan ?>
                                <?php
                                // --- Program tanpa kegiatan ---
                                else: ?>
                                    <tr>
                                        <?php if ($firstOpdRow): ?>
                                            <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                                <?= esc($currentOpd['nama_opd'] ?? '-') ?>
                                            </td>
                                            <?php $firstOpdRow = false; ?>
                                        <?php endif; ?>

                                        <?php if ($firstIndicatorRow): ?>
                                            <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                <?= $no++ ?>
                                            </td>
                                            <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                <?= esc($displayYear) ?>
                                            </td>
                                           <?php if ($firstSasaranRow): ?>
                                                <td rowspan="<?= $sasaranRowspan ?>" class="align-middle text-start">
                                                    <?= esc($sasaranNama) ?>
                                                </td>
                                                <?php $firstSasaranRow = false; ?>
                                            <?php endif; ?>
                                            <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                                <?= esc($ind['indikator_sasaran']) ?>
                                            </td>
                                            <?php $firstIndicatorRow = false; ?>
                                        <?php endif; ?>

                                        <td class="align-middle text-start">
                                            <?= esc($rkt['program_nama'] ?? '-') ?>
                                        </td>
                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="text-end">-</td>

                                        <?php if (!$statusRendered): ?>
                                            <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                <?php if (empty($statusList)): ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php else: ?>
                                                    <?php foreach ($statusList as $st): ?>
                                                        <?php if ($st === 'selesai'): ?>
                                                            <span class="badge bg-success">Selesai</span>
                                                        <?php elseif ($st === 'draft'): ?>
                                                            <span class="badge bg-warning text-dark">Draft</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><?= esc($st) ?></span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php $statusRendered = true; ?>
                                        <?php endif; ?>

                                        <?php if (!$actionRendered): ?>
                                            <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="<?= base_url('adminopd/rkt/edit/' . $ind['id']) ?>"
                                                       class="btn btn-warning btn-sm" title="Edit RKT">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="<?= base_url('adminopd/rkt/update-status') ?>"
                                                          method="post" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="indikator_id"
                                                               value="<?= esc($ind['id']) ?>">
                                                        <input type="hidden" name="tahun"
                                                               value="<?= esc($selectedYear === 'all' ? '' : $selectedYear) ?>">
                                                        <button type="submit"
                                                                class="btn btn-info btn-sm"
                                                                title="Ubah Status Draft/Selesai">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <?php $actionRendered = true; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endif; // end if kegiatan ?>
                            <?php endforeach; // foreach rkts ?>
                        <?php endif; // end if empty rkts
                    endforeach; // foreach indikator dalam sasaran
                    endforeach; // foreach sasaran                    
?>
                    </tbody>
                </table>
            </div><!-- /.tbl-rkt-wrapper -->
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
</div>

<script>
    function applyFilter() {
        const indikator = document.getElementById('indikatorFilter')?.value || 'all';
        const tahun     = document.getElementById('yearFilter')?.value || 'all';
        const status    = document.getElementById('statusFilter')?.value || 'all';

        const params = new URLSearchParams();

        if (indikator !== 'all') {
            params.set('sasaran', indikator);
        }
        if (tahun !== 'all') {
            params.set('tahun', tahun);
        }
        if (status !== 'all') {
            params.set('status', status);
        }

        const qs = params.toString();
        window.location.search = qs.length ? '?' + qs : '';
    }
</script>

</body>
</html>

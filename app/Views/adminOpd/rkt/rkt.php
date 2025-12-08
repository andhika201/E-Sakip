<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'RENJA (RKT)') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
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
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Total indikator: <?= isset($rktdata) ? count($rktdata) : 0 ?>
                    </small>
                    <small class="text-muted">
                        OPD: <strong><?= esc($currentOpd['nama_opd'] ?? '-') ?></strong>
                    </small>
                </div>
            </div>

            <!-- TABEL -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center small align-middle">
                    <thead class="table-success">
                    <tr>
                        <th class="border p-2">SATUAN KERJA</th>
                        <th class="border p-2">NO</th>
                        <th class="border p-2">TAHUN</th>
                        <th class="border p-2">SASARAN</th>
                        <th class="border p-2">INDIKATOR SASARAN</th>
                        <th class="border p-2">PROGRAM</th>
                        <th class="border p-2">KEGIATAN</th>
                        <th class="border p-2">SUB KEGIATAN</th>
                        <th class="border p-2">TARGET ANGGARAN</th>
                        <th class="border p-2">STATUS RKT</th>
                        <th class="border p-2">AKSI</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    helper('format_helper');
                    $no = 1;

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

                    foreach ($rktdata as $ind):

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
                                    <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                        <?= esc($currentOpd['nama_opd'] ?? '-') ?>
                                    </td>
                                    <?php $firstOpdRow = false; ?>
                                <?php endif; ?>

                                <td rowspan="<?= $totalSubRows ?>" class="align-middle"><?= $no++ ?></td>
                                <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                    <?= esc($displayYear) ?>
                                </td>
                                <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                    <?= esc($ind['sasaran']) ?>
                                </td>
                                <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                    <?= esc($ind['indikator_sasaran']) ?>
                                </td>

                                <td class="text-start">-</td>
                                <td class="text-start">-</td>
                                <td class="text-start">-</td>
                                <td class="text-end">-</td>

                                <td rowspan="<?= $totalSubRows ?>" class="align-middle">
                                    <span class="badge bg-secondary">Belum ada RKT</span>
                                </td>

                                <td rowspan="<?= $totalSubRows ?>" class="align-middle">
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
                                                        <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                                            <?= esc($ind['sasaran']) ?>
                                                        </td>
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
                                                    <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                                        <?= esc($ind['sasaran']) ?>
                                                    </td>
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
                                            <td rowspan="<?= $totalSubRows ?>" class="align-middle text-start">
                                                <?= esc($ind['sasaran']) ?>
                                            </td>
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
                    endforeach; // foreach indikator
                    ?>
                    </tbody>
                </table>
            </div>
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

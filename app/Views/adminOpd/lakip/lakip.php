<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LAKIP - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        /* Tombol icon kotak seperti contoh */
        .icon-btn {
            width: 40px;
            height: 40px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            font-size: 20px;
            border: none;
            text-decoration: none;
            padding: 0;
        }

        .icon-add {
            background-color: #0d6efd;
            color: #fff;
        }

        .icon-edit {
            background-color: #ffc107;
            color: #000;
        }

        .icon-status {
            background-color: #0dcaf0;
            color: #000;
        }

        .icon-btn i {
            pointer-events: none;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Content Wrapper -->
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
        <!-- Sidebar -->
        <?= $this->include(($role === 'admin_kab' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">
                    LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH
                </h2>

                <!-- FILTER -->
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <?php if ($role === 'admin_kab'): ?>
                        <!-- ADMIN KABUPATEN -->
                        <div class="d-flex flex-column flex-md-row gap-3 flex-fill">
                            <!-- Mode: Kabupaten / OPD -->
                            <select id="mode_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>
                                    Mode Kabupaten (RPJMD)
                                </option>
                                <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>
                                    Mode OPD (RENSTRA)
                                </option>
                            </select>

                            <!-- Filter OPD (hanya saat mode = opd) -->
                            <select id="opd_filter" class="form-select border-secondary" onchange="filterData()"
                                <?= ($mode === 'opd') ? '' : 'style="display:none;"' ?>>
                                <option value="">Pilih OPD</option>
                                <?php foreach ($opdList as $opd): ?>
                                    <option value="<?= $opd['id'] ?>" <?= (!empty($selectedOpdId) && $selectedOpdId == $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Tahun -->
                            <select id="tahun_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="">Semua Tahun</option>
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?= $year ?>" <?= (($filters['tahun'] ?? '') == $year) ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <!-- ADMIN OPD -->
                        <div class="d-flex flex-column flex-md-row gap-3 flex-fill">
                            <!-- Tahun -->
                            <select id="tahun_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="">Semua Tahun</option>
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?= $year ?>" <?= (($filters['tahun'] ?? '') == $year) ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Status -->
                            <select id="status_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="">Semua Status</option>
                                <option value="proses" <?= (($filters['status'] ?? '') === 'proses') ? 'selected' : '' ?>>
                                    Proses
                                </option>
                                <option value="siap" <?= (($filters['status'] ?? '') === 'siap') ? 'selected' : '' ?>>
                                    Siap</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TABEL LAKIP -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center small align-middle">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">NO</th>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">SATUAN</th>
                                <th class="border p-2">TARGET TAHUN SEBELUMNYA</th>
                                <th class="border p-2">CAPAIAN TAHUN SEBELUMNYA</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">CAPAIAN TAHUN INI</th>
                                <th class="border p-2">STATUS</th>
                                <th class="border p-2">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;

                            if ($role === 'admin_kab') {
                                if ($mode === 'kabupaten') {
                                    $dataSource = $rpjmdData;
                                    $isRpjmdMode = true;
                                } else {
                                    $dataSource = $renstraData;
                                    $isRpjmdMode = false;
                                }
                            } else {
                                $dataSource = $renstraData;
                                $isRpjmdMode = false;
                            }
                            ?>

                            <?php foreach ($dataSource as $row): ?>
                                <?php
                                if ($role === 'admin_kab' && $mode === 'kabupaten') {
                                    $sasaranText = $row['sasaran_rpjmd'] ?? '';
                                } else {
                                    $sasaranText = $row['sasaran'] ?? '';
                                }

                                $indikatorList = $row['indikator_sasaran'] ?? [];
                                $indikatorCount = count($indikatorList);
                                $firstRow = true;
                                ?>

                                <?php foreach ($indikatorList as $indikator): ?>
                                    <?php
                                    // cari data LAKIP untuk indikator ini
                                    $lakipItem = null;
                                    foreach ($lakip ?? [] as $item) {
                                        if ($isRpjmdMode) {
                                            if (($item['rpjmd_indikator_id'] ?? null) == $indikator['id']) {
                                                $lakipItem = $item;
                                                break;
                                            }
                                        } else {
                                            if (($item['renstra_indikator_id'] ?? null) == $indikator['id']) {
                                                $lakipItem = $item;
                                                break;
                                            }
                                        }
                                    }

                                    // target tahun berjalan
                                    $keyTarget = $isRpjmdMode ? 'target_tahunan' : 'target';
                                    $targetTahun = $indikator['target_tahunan'][0][$keyTarget] ?? null;

                                    // Status & badge
                                    $statusText = $lakipItem['status'] ?? null;
                                    $badgeClass = '';
                                    $statusLabel = '';

                                    if ($statusText === 'siap') {
                                        $badgeClass = 'badge bg-success';
                                        $statusLabel = 'Siap';
                                    } elseif ($statusText === 'proses') {
                                        $badgeClass = 'badge bg-warning text-dark';
                                        $statusLabel = 'Proses';
                                    } elseif (!empty($statusText)) {
                                        $badgeClass = 'badge bg-secondary';
                                        $statusLabel = $statusText;
                                    }

                                    // URL ganti status (controller: status($id, $to))
                                    $changeStatusUrl = '';
                                    $nextStatus = '';
                                    if (!empty($lakipItem['id'])) {
                                        if ($statusText === 'siap') {
                                            // dari siap balik ke proses
                                            $nextStatus = 'proses';
                                            $changeStatusUrl = base_url('adminopd/lakip/status/' . $lakipItem['id'] . '/proses');
                                        } else {
                                            // default: dari kosong / proses jadi siap
                                            $nextStatus = 'siap';
                                            $changeStatusUrl = base_url('adminopd/lakip/status/' . $lakipItem['id'] . '/siap');
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <?php if ($firstRow): ?>
                                            <td rowspan="<?= $indikatorCount ?>" class="align-middle">
                                                <?= $no++ ?>
                                            </td>
                                            <td rowspan="<?= $indikatorCount ?>" class="align-middle text-start">
                                                <?= esc($sasaranText) ?>
                                            </td>
                                            <?php $firstRow = false; ?>
                                        <?php endif; ?>

                                        <!-- indikator & satuan -->
                                        <td><?= esc($indikator['indikator_sasaran'] ?? '-') ?></td>
                                        <td><?= esc($indikator['satuan'] ?? '-') ?></td>

                                        <!-- target & capaian tahun lalu -->
                                        <td class="text-center"><?= esc($lakipItem['target_lalu'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($lakipItem['capaian_lalu'] ?? '-') ?></td>

                                        <!-- target tahun ini -->
                                        <td class="text-center">
                                            <?= $targetTahun ? esc($targetTahun) : '-' ?>
                                        </td>

                                        <!-- capaian tahun ini -->
                                        <td class="text-center"><?= esc($lakipItem['capaian_tahun_ini'] ?? '-') ?></td>

                                        <!-- status badge -->
                                        <td class="text-center">
                                            <?php if (!empty($statusLabel)): ?>
                                                <span class="<?= $badgeClass ?>"><?= esc($statusLabel) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- aksi icon-only -->
                                        <td class="text-center">
                                            <?php if (!empty($indikator['id'])): ?>
                                                <?php if (empty($lakipItem['id'])): ?>
                                                    <!-- belum ada LAKIP -> Tambah -->
                                                    <a href="<?= base_url('adminopd/lakip/tambah/' . $indikator['id']) ?>"
                                                        class="btn btn-primary btn-sm" title="Tambah Lakip">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <!-- sudah ada LAKIP -> Edit + Change Status -->
                                                    <a href="<?= base_url('adminopd/lakip/edit/' . $indikator['id']) ?>"
                                                        class="btn btn-warning btn-sm" title="Edit Lakip">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <?php if ($changeStatusUrl && $nextStatus): ?>
                                                        <a href="<?= $changeStatusUrl ?>" class="btn btn-info btn-sm"
                                                            title="Ubah status ke <?= esc($nextStatus) ?>">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <?php if (empty($dataSource)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        Belum ada data sasaran / indikator pada tahun ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div> <!-- End of Content Wrapper -->

    <!-- Script Filter -->
    <script>
        function filterData() {
            const params = new URLSearchParams();

            const tahunEl = document.getElementById('tahun_filter');
            const statusEl = document.getElementById('status_filter');
            const modeEl = document.getElementById('mode_filter');
            const opdEl = document.getElementById('opd_filter');

            if (modeEl) {
                const mode = modeEl.value;
                if (mode) params.append('mode', mode);

                if (mode === 'opd' && opdEl && opdEl.value) {
                    params.append('opd_id', opdEl.value);
                }
            }

            if (tahunEl && tahunEl.value) {
                params.append('tahun', tahunEl.value);
            }

            if (statusEl && statusEl.value) {
                params.append('status', statusEl.value);
            }

            const query = params.toString();
            window.location.href = query ? ('?' + query) : window.location.pathname;
        }

        // Tampilkan / sembunyikan filter OPD saat mode diganti (admin_kab)
        document.addEventListener('DOMContentLoaded', function () {
            const modeEl = document.getElementById('mode_filter');
            const opdEl = document.getElementById('opd_filter');
            if (modeEl && opdEl) {
                function toggleOpd() {
                    if (modeEl.value === 'opd') {
                        opdEl.style.display = '';
                    } else {
                        opdEl.style.display = 'none';
                    }
                }
                modeEl.addEventListener('change', toggleOpd);
                toggleOpd();
            }
        });
    </script>

</body>

</html>
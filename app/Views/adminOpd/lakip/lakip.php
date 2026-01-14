<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LAKIP - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        /* Tombol icon kotak (kalau nanti dipakai lagi) */
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

        /* AREA TOMBOL AKSI */
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            border-radius: 0.55rem;
        }

        .action-buttons .btn i {
            font-size: 0.9rem;
        }

        @media (max-width: 576px) {
            .action-buttons {
                gap: 0.25rem !important;
            }

            .action-buttons .btn {
                padding: 0.15rem 0.35rem;
            }

            .action-buttons .btn i {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Header + Sidebar -->
        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">
                    LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH
                </h2>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <?php if ($role === 'admin_kab'): ?>
                        <div class="d-flex flex-column flex-md-row gap-3 flex-fill">
                            <select id="mode_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>
                                    Mode Kabupaten (RPJMD)
                                </option>
                                <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>
                                    Mode OPD (RENSTRA)
                                </option>
                            </select>

                            <select id="opd_filter" class="form-select border-secondary" onchange="filterData()"
                                <?= ($mode === 'opd') ? '' : 'style="display:none;"' ?>>
                                <option value="">Pilih OPD</option>
                                <?php foreach (($opdList ?? []) as $opd): ?>
                                    <option value="<?= $opd['id'] ?>" <?= (!empty($selectedOpdId) && $selectedOpdId == $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="tahun_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="">Semua Tahun</option>
                                <?php foreach (($availableYears ?? []) as $year): ?>
                                    <option value="<?= $year ?>" <?= (($filters['tahun'] ?? '') == $year) ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column flex-md-row gap-3 flex-fill">

                            <select id="tahun_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="">Semua Tahun</option>
                                <?php foreach (($availableYears ?? []) as $year): ?>
                                    <option value="<?= $year ?>" <?= (($filters['tahun'] ?? '') == $year) ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="status_filter" class="form-select border-secondary" onchange="filterData()">
                                <option value="">Semua Status</option>
                                <option value="proses" <?= (($filters['status'] ?? '') === 'proses') ? 'selected' : '' ?>>
                                    Proses
                                </option>
                                <option value="siap" <?= (($filters['status'] ?? '') === 'siap') ? 'selected' : '' ?>>
                                    Siap
                                </option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                // =========================
                // FUNGSI HITUNG CAPAIAN
                // =========================
                if (!function_exists('hitungCapaianLakip')) {
                    /**
                     * Hitung capaian LAKIP (%) 
                     * Mendukung indikator positif & negatif
                     * Mendukung input koma (2,2)
                     */
                    function hitungCapaianLakip($target, $realisasi, $jenisIndikator)
                    {
                        $target = toFloatComma($target);
                        $realisasi = toFloatComma($realisasi);

                        // validasi dasar
                        if ($target === null || $target == 0 || $realisasi === null) {
                            return null;
                        }

                        $jenis = strtolower(trim((string) $jenisIndikator));

                        // indikator positif (naik = baik)
                        if ($jenis === 'indikator positif' || $jenis === 'positif') {
                            $hasil = ($realisasi / $target) * 100;
                        }

                        // indikator negatif (turun = baik)
                        elseif ($jenis === 'indikator negatif' || $jenis === 'negatif') {
                            $hasil = (($target - ($realisasi - $target)) / $target) * 100;
                        } else {
                            return null;
                        }

                        // pengaman hasil
                        if (!is_numeric($hasil)) {
                            return null;
                        }

                        // batasi nilai ekstrem (opsional tapi disarankan)
                        if ($hasil < 0)
                            $hasil = 0;
                        if ($hasil > 200)
                            $hasil = 200;

                        return $hasil;
                    }
                }

                // Tahun aktif dari filter
                $tahunAktif = (string) ($filters['tahun'] ?? '');

                // ========= PATCH: dataSource & qsBase & lakipMap =========
                // dataSource fallback
                $dataSource = $dataSource ?? null;
                if ($dataSource === null) {
                    if (($role ?? '') === 'admin_kab' && ($mode ?? '') === 'kabupaten') {
                        $dataSource = $rpjmdData ?? [];
                    } else {
                        $dataSource = $renstraData ?? [];
                    }
                }

                // qsBase untuk link tombol
                $qsBase = '';
                if (!empty($filters['tahun'])) {
                    $qsBase .= (strpos($qsBase, '?') === false ? '?' : '&') . 'tahun=' . urlencode((string) $filters['tahun']);
                }
                if (!empty($filters['status'])) {
                    $qsBase .= (strpos($qsBase, '?') === false ? '?' : '&') . 'status=' . urlencode((string) $filters['status']);
                }
                if (!empty($mode)) {
                    $qsBase .= (strpos($qsBase, '?') === false ? '?' : '&') . 'mode=' . urlencode((string) $mode);
                }
                if (!empty($selectedOpdId)) {
                    $qsBase .= (strpos($qsBase, '?') === false ? '?' : '&') . 'opd_id=' . urlencode((string) $selectedOpdId);
                }

                // lakipMap fallback dari lakip list
                $lakipMap = $lakipMap ?? [];
                if (empty($lakipMap) && !empty($lakip) && is_array($lakip)) {
                    foreach ($lakip as $l) {
                        $k = (int) ($l['renstra_indikator_id'] ?? $l['indikator_id'] ?? $l['indikator_sasaran_id'] ?? 0);
                        if ($k)
                            $lakipMap[$k] = $l;
                    }
                }
                ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center small align-middle">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">NO</th>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">SATUAN</th>
                                <th class="border p-2">JENIS INDIKATOR</th>
                                <th class="border p-2">TAHUN</th>
                                <th class="border p-2">TARGET TAHUN SEBELUMNYA</th>
                                <th class="border p-2">CAPAIAN TAHUN SEBELUMNYA</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">CAPAIAN TAHUN INI</th>
                                <th class="border p-2">CAPAIAN (%)</th>
                                <th class="border p-2">STATUS</th>
                                <th class="border p-2">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>

                            <?php foreach (($dataSource ?? []) as $row): ?>
                                <?php
                                $sasaranText = $row['sasaran'] ?? ($row['sasaran_rpjmd'] ?? '');
                                $indikatorList = $row['indikator_sasaran'] ?? [];
                                $indikatorCount = count($indikatorList);
                                $firstRow = true;
                                ?>

                                <?php foreach ($indikatorList as $indikator): ?>
                                    <?php
                                    // indikator id fallback
                                    $indikatorId = (int) ($indikator['id'] ?? $indikator['indikator_id'] ?? $indikator['renstra_indikator_id'] ?? 0);

                                    // ambil lakip dari map
                                    $lakipItem = $indikatorId ? ($lakipMap[$indikatorId] ?? null) : null;

                                    // tahun yang benar-benar dipakai untuk tampil & cari target
                                    $tahunRow = (string) (
                                        $tahunAktif !== '' ? $tahunAktif :
                                        ($indikator['tahun'] ?? $row['tahun'] ?? date('Y'))
                                    );

                                    // target tahun aktif: fleksibel
                                    $targetTahun = null;
                                    $targets = $indikator['target_tahunan'] ?? null;

                                    if (is_array($targets)) {
                                        $isAssoc = array_keys($targets) !== range(0, count($targets) - 1);

                                        if ($isAssoc) {
                                            $targetTahun = $targets[$tahunRow] ?? $targets[(int) $tahunRow] ?? null;
                                        } else {
                                            foreach ($targets as $t) {
                                                $th = (string) ($t['tahun'] ?? $t['indikator_tahun'] ?? '');
                                                if ($th === $tahunRow) {
                                                    $targetTahun =
                                                        $t['target']
                                                        ?? $t['target_tahunan']
                                                        ?? $t['target_tahun_ini']
                                                        ?? $t['nilai_target']
                                                        ?? null;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    // fallback kalau target_tahunan tidak ada
                                    if ($targetTahun === null) {
                                        $targetTahun =
                                            $indikator['target_tahun_ini']
                                            ?? $indikator['target']
                                            ?? $row['target_tahun_ini']
                                            ?? $row['target']
                                            ?? null;
                                    }

                                    $jenisIndikator = $indikator['jenis_indikator'] ?? ($row['jenis_indikator'] ?? 'indikator positif');

                                    $realisasiNow = $lakipItem['capaian_tahun_ini'] ?? null;

                                    $capaianPersen = null;
                                    if (is_numeric($realisasiNow) && is_numeric($targetTahun)) {
                                        $capaianPersen = hitungCapaianLakip($targetTahun, $realisasiNow, $jenisIndikator);
                                    }

                                    $statusText = $lakipItem['status'] ?? null;
                                    if ($statusText === 'siap') {
                                        $badgeClass = 'badge bg-success';
                                        $statusLabel = 'Siap';
                                    } elseif ($statusText === 'proses') {
                                        $badgeClass = 'badge bg-warning text-dark';
                                        $statusLabel = 'Proses';
                                    } elseif (!empty($statusText)) {
                                        $badgeClass = 'badge bg-secondary';
                                        $statusLabel = $statusText;
                                    } else {
                                        $badgeClass = '';
                                        $statusLabel = '';
                                    }

                                    $changeStatusUrl = '';
                                    $nextStatus = '';
                                    if (!empty($lakipItem['id'])) {
                                        if ($statusText === 'siap') {
                                            $nextStatus = 'proses';
                                            $changeStatusUrl = base_url('adminopd/lakip/status/' . $lakipItem['id'] . '/proses');
                                        } else {
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

                                        <td><?= esc($indikator['indikator_sasaran'] ?? '-') ?></td>
                                        <td><?= esc($indikator['satuan'] ?? '-') ?></td>

                                        <td><?= esc(ucwords((string) $jenisIndikator)) ?></td>

                                        <td><?= esc($tahunRow) ?></td>

                                        <td class="text-center"><?= esc($lakipItem['target_lalu'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($lakipItem['capaian_lalu'] ?? '-') ?></td>

                                        <td class="text-center">
                                            <?= (is_numeric($targetTahun) || $targetTahun !== null) ? esc((string) $targetTahun) : '-' ?>
                                        </td>

                                        <td class="text-center">
                                            <?= $realisasiNow !== null ? esc((string) $realisasiNow) : '-' ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($capaianPersen === null): ?>
                                                -
                                            <?php else: ?>
                                                <?= round($capaianPersen, 2) ?>%
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if (!empty($statusLabel)): ?>
                                                <span class="<?= $badgeClass ?>"><?= esc($statusLabel) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="d-flex flex-wrap justify-content-center gap-1 action-buttons">
                                                <?php if (!empty($indikatorId)): ?>
                                                    <?php if (empty($lakipItem['id'])): ?>
                                                        <a href="<?= base_url('adminopd/lakip/tambah/' . $indikatorId) . $qsBase ?>"
                                                            class="btn btn-sm btn-primary" title="Tambah LAKIP">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('adminopd/lakip/edit/' . $indikatorId) . $qsBase ?>"
                                                            class="btn btn-sm btn-warning" title="Edit LAKIP">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <?php if ($changeStatusUrl && $nextStatus): ?>
                                                            <a href="<?= $changeStatusUrl . $qsBase ?>" class="btn btn-sm btn-info"
                                                                title="Ubah status ke <?= esc(ucfirst($nextStatus)) ?>">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <?php if (empty($dataSource)): ?>
                                <tr>
                                    <td colspan="13" class="text-center text-muted">
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
    </div>

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
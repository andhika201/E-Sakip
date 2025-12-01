<!-- app/Views/adminkabupaten/rkpd/rkpd.php -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'RKPD') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <?php
    helper('format_helper');

    // ==================================================================
    // GROUPING: dari rows_grouped (per opd_id) -> per nama_opd + indikator
    // ==================================================================
    $grouped = [];

    if (!empty($rows_grouped)) {
        foreach ($rows_grouped as $opdId => $rows) {
            foreach ($rows as $row) {
                $opdName = $row['nama_opd'] ?? '-';

                // key indikator (tahun + sasaran + indikator)
                $metaKey = implode('|', [
                    $row['tahun'] ?? '',
                    $row['sasaran'] ?? '',
                    $row['indikator_sasaran'] ?? '',
                ]);

                if (!isset($grouped[$opdName][$metaKey])) {
                    $grouped[$opdName][$metaKey] = [
                        'meta' => [
                            'tahun' => $row['tahun'] ?? '',
                            'sasaran' => $row['sasaran'] ?? '',
                            'indikator_sasaran' => $row['indikator_sasaran'] ?? '',
                        ],
                        'rows' => [],
                    ];
                }

                $grouped[$opdName][$metaKey]['rows'][] = $row;
            }
        }
    }

    $selectedOpd   = $filter_opd   ?? 'all';
    $selectedYear  = $filter_tahun ?? date('Y');
    $totalIndikator = (int)($total_indikator ?? 0);
    ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">

            <h2 class="h3 fw-bold text-success text-center mb-4">
                RENCANA KERJA PEMERINTAH DAERAH (RKPD)
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

            <!-- FILTER (adaptasi style dari RKT) -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div class="d-flex gap-2 flex-fill">

                    <!-- Filter OPD -->
                    <select id="opdFilter" class="form-select w-50" onchange="applyFilter()">
                        <option value="all" <?= ($selectedOpd === 'all') ? 'selected' : '' ?>>
                            SEMUA OPD
                        </option>
                        <?php if (!empty($allOpd)): ?>
                            <?php foreach ($allOpd as $opd): ?>
                                <option value="<?= esc($opd['id']) ?>"
                                    <?= ((string)$selectedOpd === (string)$opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <!-- Filter Tahun -->
                    <select id="yearFilter" class="form-select w-25" onchange="applyFilter()">
                        <option value="all" <?= ($selectedYear === 'all') ? 'selected' : '' ?>>
                            SEMUA TAHUN
                        </option>
                        <?php if (!empty($available_years)): ?>
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?= esc($y) ?>"
                                    <?= ((string)$selectedYear === (string)$y) ? 'selected' : '' ?>>
                                    <?= esc($y) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <!-- Tombol reset -->
                    <button type="button"
                            onclick="window.location.href='<?= base_url('adminkab/rkpd') ?>'"
                            class="btn btn-outline-secondary">
                        Reset
                    </button>
                </div>
            </div>

            <!-- Info ringkas -->
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Total indikator: <?= $totalIndikator ?>
                    </small>
                    <small class="text-muted">
                        Filter:
                        OPD:
                        <strong>
                            <?php if ($selectedOpd === 'all'): ?>
                                SEMUA
                            <?php else: ?>
                                <?= esc($currentOpdName ?? '-') ?>
                            <?php endif; ?>
                        </strong>
                        , Tahun:
                        <strong><?= $selectedYear === 'all' ? 'SEMUA' : esc($selectedYear) ?></strong>
                    </small>
                </div>
            </div>

            <!-- TABEL RKPD -->
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
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($grouped)): ?>
                        <tr>
                            <td colspan="10" class="text-center p-4 text-muted">
                                Tidak ada data RKPD.
                                <br>
                                (Hanya RKT dengan status <strong>selesai</strong> yang ditampilkan di RKPD.)
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php
                        $no = 1;

                        foreach ($grouped as $opdName => $indikatorList):

                            // Hitung total baris untuk rowspan SATUAN KERJA
                            $opdRowspan = 0;
                            foreach ($indikatorList as $indVal) {
                                $opdRowspan += count($indVal['rows']);
                            }
                            $firstOpdRow = true;

                            foreach ($indikatorList as $indVal):

                                $meta  = $indVal['meta'];
                                $rows  = $indVal['rows'];
                                $rowspanIndikator = count($rows);
                                $firstIndRow = true;

                                // Kumpulkan status RKT untuk indikator ini
                                $statusSet = [];
                                foreach ($rows as $r) {
                                    if (!empty($r['status'])) {
                                        $statusSet[] = $r['status'];
                                    }
                                }
                                $statusSet = array_values(array_unique($statusSet));
                                $statusRendered = false;

                                // Hitung rowspan untuk program & kegiatan
                                $programSpan  = [];
                                $kegiatanSpan = [];

                                foreach ($rows as $r) {
                                    $p = $r['program_kegiatan'] ?? $r['program_nama'] ?? '-';
                                    $k = $r['nama_kegiatan'] ?? $r['kegiatan'] ?? '-';

                                    $programSpan[$p] = ($programSpan[$p] ?? 0) + 1;

                                    $keyKeg = $p . '|' . $k;
                                    $kegiatanSpan[$keyKeg] = ($kegiatanSpan[$keyKeg] ?? 0) + 1;
                                }

                                $printedProgram  = [];
                                $printedKegiatan = [];

                                foreach ($rows as $row):
                                    $program = $row['program_kegiatan'] ?? $row['program_nama'] ?? '-';
                                    $kegKey  = $program . '|' . ($row['nama_kegiatan'] ?? $row['kegiatan'] ?? '-');
                                    $kegName = $row['nama_kegiatan'] ?? $row['kegiatan'] ?? '-';
                                    $sub     = $row['nama_subkegiatan'] ?? $row['sub_kegiatan'] ?? '-';
                                    $anggar  = $row['target_anggaran'] ?? 0;
                                    ?>
                                    <tr>
                                        <?php if ($firstOpdRow): ?>
                                            <td rowspan="<?= $opdRowspan ?>" class="align-middle text-start">
                                                <?= esc($opdName) ?>
                                            </td>
                                            <?php $firstOpdRow = false; ?>
                                        <?php endif; ?>

                                        <?php if ($firstIndRow): ?>
                                            <td rowspan="<?= $rowspanIndikator ?>" class="align-middle">
                                                <?= $no++ ?>
                                            </td>
                                            <td rowspan="<?= $rowspanIndikator ?>" class="align-middle">
                                                <?= esc($meta['tahun']) ?>
                                            </td>
                                            <td rowspan="<?= $rowspanIndikator ?>" class="align-middle text-start">
                                                <?= esc($meta['sasaran']) ?>
                                            </td>
                                            <td rowspan="<?= $rowspanIndikator ?>" class="align-middle text-start">
                                                <?= esc($meta['indikator_sasaran']) ?>
                                            </td>
                                            <?php $firstIndRow = false; ?>
                                        <?php endif; ?>

                                        <!-- PROGRAM (rowspan jika sama) -->
                                        <?php if (empty($printedProgram[$program])): ?>
                                            <td rowspan="<?= $programSpan[$program] ?? 1 ?>" class="align-middle text-start">
                                                <?= esc($program) ?>
                                            </td>
                                            <?php $printedProgram[$program] = true; ?>
                                        <?php endif; ?>

                                        <!-- KEGIATAN (rowspan jika sama dalam program) -->
                                        <?php if (empty($printedKegiatan[$kegKey])): ?>
                                            <td rowspan="<?= $kegiatanSpan[$kegKey] ?? 1 ?>" class="align-middle text-start">
                                                <?= esc($kegName) ?>
                                            </td>
                                            <?php $printedKegiatan[$kegKey] = true; ?>
                                        <?php endif; ?>

                                        <!-- SUB KEGIATAN -->
                                        <td class="align-middle text-start">
                                            <?= esc($sub) ?>
                                        </td>

                                        <!-- TARGET ANGGARAN -->
                                        <td class="align-middle text-end">
                                            <?php
                                            if (function_exists('formatRupiah')) {
                                                echo formatRupiah($anggar);
                                            } else {
                                                echo 'Rp ' . number_format((float)$anggar, 0, ',', '.');
                                            }
                                            ?>
                                        </td>

                                        <!-- STATUS (rowspan per indikator) -->
                                        <?php if (!$statusRendered): ?>
                                            <td rowspan="<?= $rowspanIndikator ?>" class="align-middle">
                                                <?php if (empty($statusSet)): ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php else: ?>
                                                    <?php foreach ($statusSet as $st): ?>
                                                        <?php if ($st === 'selesai'): ?>
                                                            <span class="badge bg-success">Selesai</span>
                                                        <?php elseif ($st === 'draft'): ?>
                                                            <span class="badge bg-warning text-dark">Draft</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <?= esc(ucfirst($st)) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php $statusRendered = true; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; // foreach rows ?>
                            <?php endforeach; // foreach indikator ?>
                        <?php endforeach; // foreach OPD ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</div>

<script>
    function applyFilter() {
        const opd   = document.getElementById('opdFilter')?.value || 'all';
        const tahun = document.getElementById('yearFilter')?.value || 'all';

        const params = new URLSearchParams();

        if (opd !== 'all') {
            params.set('opd_id', opd);
        }
        if (tahun !== 'all') {
            params.set('tahun', tahun);
        }

        const qs = params.toString();
        window.location.search = qs.length ? '?' + qs : '';
    }
</script>

</body>
</html>

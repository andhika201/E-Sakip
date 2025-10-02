<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RPJMD e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Content Wrapper -->
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include('adminKabupaten/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH</h2>

                <!-- Flash Messages -->
                <!-- Error Messages -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Validation Errors -->
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

                <!-- Filter and Action Controls -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <!-- Period and Status Filter -->
                    <div class="d-flex align-items-center flex-fill me-3 gap-2">
                        <select id="periodFilter" class="form-select" onchange="filterByPeriode()" style="flex: 2;">
                            <?php if (isset($rpjmd_grouped) && !empty($rpjmd_grouped)): ?>
                                <?php
                                // Get the latest period (last key in the sorted array)
                                $periodKeys = array_keys($rpjmd_grouped);
                                $latestPeriod = end($periodKeys);
                                ?>
                                <?php foreach ($rpjmd_grouped as $periodKey => $periodData): ?>
                                    <option value="<?= $periodKey ?>" <?= $periodKey === $latestPeriod ? 'selected' : '' ?>>
                                        Periode <?= $periodData['period'] ?>
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

                    <!-- Action Button -->
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
                                <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                                <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                                <th rowspan="2" class="border p-2 align-middle">Definisi Operasional</th>
                                <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                                <th colspan="5" class="border p-2" id="year-header-span">TARGET CAPAIAN PER TAHUN</th>
                                <th rowspan="2" class="border p-2 align-middle">ACTION</th>
                            </tr>
                            <tr class="border p-2" style="border-top: 2px solid;" id="year-header-row">
                                <!-- Year headers will be populated by JavaScript -->
                            </tr>
                        </thead>
                        <tbody id="rpjmd-table-body">
                            <?php if (isset($rpjmd_grouped) && !empty($rpjmd_grouped)): ?>
                                <?php foreach ($rpjmd_grouped as $periodIndex => $periodData): ?>
                                    <!-- Data untuk periode ini -->
                                    <?php foreach ($periodData['misi_data'] as $misi): ?>
                                        <?php if (isset($misi['tujuan']) && !empty($misi['tujuan'])): ?>
                                            <?php
                                            $misiRowspan = 0;
                                            foreach ($misi['tujuan'] as $tujuan) {
                                                if (isset($tujuan['sasaran']) && !empty($tujuan['sasaran'])) {
                                                    foreach ($tujuan['sasaran'] as $sasaran) {
                                                        if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])) {
                                                            $misiRowspan += count($sasaran['indikator_sasaran']);
                                                        } else {
                                                            $misiRowspan += 1;
                                                        }
                                                    }
                                                } else {
                                                    $misiRowspan += 1;
                                                }
                                            }
                                            ?>

                                            <?php $firstMisiRow = true; ?>
                                            <?php foreach ($misi['tujuan'] as $tujuan): ?>
                                                <?php if (isset($tujuan['sasaran']) && !empty($tujuan['sasaran'])): ?>
                                                    <?php
                                                    $tujuanRowspan = 0;
                                                    foreach ($tujuan['sasaran'] as $sasaran) {
                                                        if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])) {
                                                            $tujuanRowspan += count($sasaran['indikator_sasaran']);
                                                        } else {
                                                            $tujuanRowspan += 1;
                                                        }
                                                    }
                                                    ?>

                                                    <?php $firstTujuanRow = true; ?>
                                                    <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                                                        <?php if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])): ?>

                                                            <?php $firstSasaranRow = true; ?>
                                                            <?php foreach ($sasaran['indikator_sasaran'] as $indikator): ?>
                                                                <tr class="periode-row" data-periode="<?= $periodIndex ?>"
                                                                    data-status="<?= $misi['status'] ?? 'draft' ?>">
                                                                    <?php if ($firstMisiRow): ?>
                                                                        <td class="border p-2 align-top text-center" rowspan="<?= $misiRowspan ?>">
                                                                            <?php
                                                                            $status = $misi['status'] ?? 'draft';
                                                                            $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                                            $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                                            ?>
                                                                            <button class="badge <?= $badgeClass ?> border-0"
                                                                                onclick="toggleStatus(<?= $misi['id'] ?>)" style="cursor: pointer;"
                                                                                title="Klik untuk mengubah status">
                                                                                <?= $statusText ?>
                                                                            </button>
                                                                        </td>
                                                                        <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>">
                                                                            <?= esc($misi['misi']) ?></td>
                                                                        <?php $firstMisiRow = false; ?>
                                                                    <?php endif; ?>

                                                                    <?php if ($firstTujuanRow): ?>
                                                                        <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                                                            <?= esc($tujuan['tujuan_rpjmd']) ?></td>
                                                                        <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                                                            <?php if (isset($tujuan['indikator_tujuan']) && !empty($tujuan['indikator_tujuan'])): ?>
                                                                                <?php foreach ($tujuan['indikator_tujuan'] as $idx => $indikatorTujuan): ?>
                                                                                    <?= esc($indikatorTujuan['indikator_tujuan']) ?>
                                                                                    <?php if ($idx < count($tujuan['indikator_tujuan']) - 1): ?><br><?php endif; ?>
                                                                                <?php endforeach; ?>
                                                                            <?php else: ?>
                                                                                -
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <?php $firstTujuanRow = false; ?>
                                                                    <?php endif; ?>

                                                                    <?php if ($firstSasaranRow): ?>
                                                                        <td class="border p-2 align-top text-start"
                                                                            rowspan="<?= count($sasaran['indikator_sasaran']) ?>">
                                                                            <?= esc($sasaran['sasaran_rpjmd']) ?></td>
                                                                        <?php $firstSasaranRow = false; ?>
                                                                    <?php endif; ?>

                                                                    <td class="border p-2 align-top text-start"><?= esc($indikator['indikator_sasaran']) ?>
                                                                    </td>
                                                                    <td class="border p-2 align-top text-start"><?= esc($indikator['definisi_op']) ?></td>
                                                                    <td class="border p-2 align-top text-start"><?= esc($indikator['satuan']) ?></td>

                                                                    <!-- Target per tahun (hanya untuk periode yang dipilih) -->
                                                                    <span class="year-cells" data-periode="<?= $periodIndex ?>">
                                                                        <?php foreach ($periodData['years'] as $year): ?>
                                                                            <td class="border p-2 align-top text-start">
                                                                                <?php if (isset($indikator['target_tahunan']) && !empty($indikator['target_tahunan'])): ?>
                                                                                    <?php foreach ($indikator['target_tahunan'] as $target): ?>
                                                                                        <?php if ($target['tahun'] == $year): ?>
                                                                                            <?= esc($target['target_tahunan']) ?>
                                                                                            <?php break; ?>
                                                                                        <?php endif; ?>
                                                                                    <?php endforeach; ?>
                                                                                <?php else: ?>
                                                                                    -
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        <?php endforeach; ?>
                                                                    </span>

                                                                    <!-- Action button hanya pada baris pertama misi -->
                                                                    <?php if ($misiRowspan > 0): ?>
                                                                        <td class="border p-2 align-middle text-center" rowspan="<?= $misiRowspan ?>">
                                                                            <div class="d-flex flex-column align-items-center gap-2">
                                                                                <a href="<?= base_url('adminkab/rpjmd/edit/' . $misi['id']) ?>"
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
                                                                                    onclick="toggleStatus(<?= $misi['id'] ?>)">
                                                                                    <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                                                                </button>
                                                                                <button class="btn btn-danger btn-sm"
                                                                                    onclick="confirmDelete(<?= $misi['id'] ?>)">
                                                                                    <i class="fas fa-trash me-1"></i>Hapus
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <?php $misiRowspan = 0; // Reset untuk mencegah duplikasi ?>
                                                                    <?php endif; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr class="periode-row" data-periode="<?= $periodIndex ?>"
                                                                data-status="<?= $misi['status'] ?? 'draft' ?>">
                                                                <?php if ($firstMisiRow): ?>
                                                                    <td class="border p-2 align-top text-center" rowspan="<?= $misiRowspan ?>">
                                                                        <?php
                                                                        $status = $misi['status'] ?? 'draft';
                                                                        $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                                        $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                                        ?>
                                                                        <button class="badge <?= $badgeClass ?> border-0"
                                                                            onclick="toggleStatus(<?= $misi['id'] ?>)" style="cursor: pointer;"
                                                                            title="Klik untuk mengubah status">
                                                                            <?= $statusText ?>
                                                                        </button>
                                                                    </td>
                                                                    <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>">
                                                                        <?= esc($misi['misi']) ?></td>
                                                                    <?php $firstMisiRow = false; ?>
                                                                <?php endif; ?>

                                                                <?php if ($firstTujuanRow): ?>
                                                                    <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                                                        <?= esc($tujuan['tujuan_rpjmd']) ?></td>
                                                                    <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                                                        <?php if (isset($tujuan['indikator_tujuan']) && !empty($tujuan['indikator_tujuan'])): ?>
                                                                            <?php foreach ($tujuan['indikator_tujuan'] as $idx => $indikatorTujuan): ?>
                                                                                <?= esc($indikatorTujuan['indikator_tujuan']) ?>
                                                                                <?php if ($idx < count($tujuan['indikator_tujuan']) - 1): ?><br><?php endif; ?>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            -
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <?php $firstTujuanRow = false; ?>
                                                                <?php endif; ?>

                                                                <td class="border p-2 align-top text-start"><?= esc($sasaran['sasaran_rpjmd']) ?></td>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                                <span class="year-cells" data-periode="<?= $periodIndex ?>">
                                                                    <?php foreach ($periodData['years'] as $year): ?>
                                                                        <td class="border p-2 align-top text-start">-</td>
                                                                    <?php endforeach; ?>
                                                                </span>
                                                            </tr>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr class="periode-row" data-periode="<?= $periodIndex ?>"
                                                        data-status="<?= $misi['status'] ?? 'draft' ?>">
                                                        <?php if ($firstMisiRow): ?>
                                                            <td class="border p-2 align-top text-center" rowspan="<?= $misiRowspan ?>">
                                                                <?php
                                                                $status = $misi['status'] ?? 'draft';
                                                                $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                                $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                                ?>
                                                                <button class="badge <?= $badgeClass ?> border-0"
                                                                    onclick="toggleStatus(<?= $misi['id'] ?>)" style="cursor: pointer;"
                                                                    title="Klik untuk mengubah status">
                                                                    <?= $statusText ?>
                                                                </button>
                                                            </td>
                                                            <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>">
                                                                <?= esc($misi['misi']) ?></td>
                                                            <?php $firstMisiRow = false; ?>
                                                        <?php endif; ?>

                                                        <td class="border p-2 align-top text-start"><?= esc($tujuan['tujuan_rpjmd']) ?></td>
                                                        <td class="border p-2 align-top text-start">
                                                            <?php if (isset($tujuan['indikator_tujuan']) && !empty($tujuan['indikator_tujuan'])): ?>
                                                                <?php foreach ($tujuan['indikator_tujuan'] as $idx => $indikatorTujuan): ?>
                                                                    <?= esc($indikatorTujuan['indikator_tujuan']) ?>
                                                                    <?php if ($idx < count($tujuan['indikator_tujuan']) - 1): ?><br><?php endif; ?>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                        <span class="year-cells" data-periode="<?= $periodIndex ?>">
                                                            <?php foreach ($periodData['years'] as $year): ?>
                                                                <td class="border p-2 align-top text-start">-</td>
                                                            <?php endforeach; ?>
                                                        </span>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="periode-row" data-periode="<?= $periodIndex ?>"
                                                data-status="<?= $misi['status'] ?? 'draft' ?>">
                                                <td class="border p-2 align-top text-center">
                                                    <?php
                                                    $status = $misi['status'] ?? 'draft';
                                                    $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                    $statusText = $status === 'selesai' ? 'Selesai' : 'Draft';
                                                    ?>
                                                    <button class="badge <?= $badgeClass ?> border-0"
                                                        onclick="toggleStatus(<?= $misi['id'] ?>)" style="cursor: pointer;"
                                                        title="Klik untuk mengubah status">
                                                        <?= $statusText ?>
                                                    </button>
                                                </td>
                                                <td class="border p-2 align-top text-start"><?= esc($misi['misi']) ?></td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <td class="border p-2 align-top text-start">-</td>
                                                <span class="year-cells" data-periode="<?= $periodIndex ?>">
                                                    <?php foreach ($periodData['years'] as $year): ?>
                                                        <td class="border p-2 align-top text-start">-</td>
                                                    <?php endforeach; ?>
                                                </span>
                                                <td class="border p-2 align-middle text-center">
                                                    <div class="d-flex flex-column align-items-center gap-2">
                                                        <a href="<?= base_url('adminkab/rpjmd/edit/' . $misi['id']) ?>"
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
                                                            onclick="toggleStatus(<?= $misi['id'] ?>)">
                                                            <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm"
                                                            onclick="confirmDelete(<?= $misi['id'] ?>)">
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
                                    <td colspan="<?= 8 + count($available_years) ?>"
                                        class="border p-4 text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Belum ada data RPJMD. <a href="<?= base_url('adminkab/rpjmd/tambah') ?>"
                                            class="text-success">Tambah data pertama</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Data Summary -->
                <div class="d-flex justify-content-between align-items-center mt-3 text-muted small">
                    <div>
                        <span id="visible-data-count">Memuat data...</span>
                    </div>
                    <div>
                        <i class="fas fa-filter me-1"></i>
                        Filter aktif: <span id="active-filters">Periode terbaru</span>
                    </div>
                </div>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div> <!-- End of Content Wrapper -->

    <!-- JavaScript for Delete Confirmation and Period Filter -->
    <script>
        // Store period data for JavaScript
        const periodData = <?= json_encode($rpjmd_grouped ?? []) ?>;

        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= base_url('adminkab/rpjmd/delete') ?>/' + id;

                // Add CSRF token if available
                <?php if (csrf_token()): ?>
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '<?= csrf_token() ?>';
                    csrfInput.value = '<?= csrf_hash() ?>';
                    form.appendChild(csrfInput);
                <?php endif; ?>

                // Add method override for DELETE
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function updateTableHeaders(periodKey) {
            const yearHeaderRow = document.getElementById('year-header-row');
            const yearHeaderSpan = document.getElementById('year-header-span');

            if (periodData[periodKey] && periodData[periodKey].years) {
                const years = periodData[periodKey].years;

                // Update colspan
                yearHeaderSpan.setAttribute('colspan', years.length);

                // Clear and rebuild year headers
                yearHeaderRow.innerHTML = '';
                years.forEach(function (year) {
                    const th = document.createElement('th');
                    th.className = 'border p-2';
                    th.style.border = '2px';
                    th.textContent = year;
                    yearHeaderRow.appendChild(th);
                });
            }
        }

        function filterByPeriode() {
            const filterValue = document.getElementById('periodFilter').value;
            const statusFilterValue = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.periode-row');
            const yearCells = document.querySelectorAll('.year-cells');

            // Hide all rows first
            rows.forEach(function (row) {
                row.style.display = 'none';
            });

            // Hide all year cells first
            yearCells.forEach(function (cells) {
                cells.style.display = 'none';
            });

            // Show only rows and year cells for selected period and status
            rows.forEach(function (row) {
                const rowPeriod = row.getAttribute('data-periode');
                const rowStatus = row.getAttribute('data-status') || 'draft';

                const periodMatch = rowPeriod === filterValue;
                const statusMatch = statusFilterValue === 'all' || !statusFilterValue || rowStatus === statusFilterValue;

                if (periodMatch && statusMatch) {
                    row.style.display = '';
                }
            });

            yearCells.forEach(function (cells) {
                if (cells.getAttribute('data-periode') === filterValue) {
                    cells.style.display = '';
                }
            });

            // Update table headers
            updateTableHeaders(filterValue);

            // Update data summary
            updateDataSummary(filterValue, statusFilterValue);
        }

        function updateDataSummary(periodKey, statusFilter) {
            const visibleRows = document.querySelectorAll('.periode-row:not([style*="display: none"])');
            const totalRows = document.querySelectorAll('.periode-row').length;

            // Update visible data count
            const countElement = document.getElementById('visible-data-count');
            if (countElement) {
                countElement.textContent = `Menampilkan ${visibleRows.length} dari ${totalRows} data`;
            }

            // Update active filters
            const filtersElement = document.getElementById('active-filters');
            if (filtersElement) {
                let filterText = '';

                if (periodKey && periodKey !== 'all') {
                    // Try to get period from periodData or use periodKey directly
                    if (typeof periodData !== 'undefined' && periodData[periodKey]) {
                        filterText = `Periode ${periodData[periodKey].period}`;
                    } else {
                        filterText = `Periode ${periodKey}`;
                    }
                } else {
                    filterText = 'Semua Periode';
                }

                if (statusFilter && statusFilter !== 'all') {
                    filterText += `, Status: ${statusFilter.charAt(0).toUpperCase() + statusFilter.slice(1)}`;
                }

                filtersElement.textContent = filterText;
            }
        }

        function filterByStatus() {
            const statusFilter = document.getElementById('statusFilter').value;
            const periodFilter = document.getElementById('periodFilter').value;

            // Get all data rows
            const rows = document.querySelectorAll('.periode-row');
            const yearCells = document.querySelectorAll('.year-cells');

            // Hide all rows first
            rows.forEach(function (row) {
                row.style.display = 'none';
            });

            // Hide all year cells first
            yearCells.forEach(function (cells) {
                cells.style.display = 'none';
            });

            // Show/hide rows based on filters
            rows.forEach(function (row) {
                const rowPeriod = row.getAttribute('data-periode');
                const rowStatus = row.getAttribute('data-status') || 'draft';

                const periodMatch = rowPeriod === periodFilter;
                const statusMatch = statusFilter === 'all' || rowStatus === statusFilter;

                if (periodMatch && statusMatch) {
                    row.style.display = '';
                }
            });

            // Show year cells for visible periods
            yearCells.forEach(function (cells) {
                if (cells.getAttribute('data-periode') === periodFilter) {
                    cells.style.display = '';
                }
            });

            // Update table headers
            updateTableHeaders(periodFilter);

            // Update data summary
            updateDataSummary(periodFilter, statusFilter);
        }

        // Initialize the filter on page load to show only the latest period
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize data summary
            const totalRows = document.querySelectorAll('.periode-row').length;
            const countElement = document.getElementById('visible-data-count');
            if (countElement) {
                countElement.textContent = `Menampilkan ${totalRows} dari ${totalRows} data`;
            }

            const filtersElement = document.getElementById('active-filters');
            if (filtersElement) {
                filtersElement.textContent = 'Semua Data';
            }

            // Apply initial filter
            filterByPeriode();
        });

        // Function to toggle status via AJAX
        function toggleStatus(misiId) {
            if (confirm('Apakah Anda yakin ingin mengubah status RPJMD ini?')) {
                fetch('<?= base_url('adminkab/rpjmd/update-status') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                    },
                    body: JSON.stringify({
                        id: misiId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload page to show updated status
                            window.location.reload();
                        } else {
                            alert('Gagal mengubah status: ' + (data.message || 'Terjadi kesalahan'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengubah status');
                    });
            }
        }
    </script>
</body>

</html>
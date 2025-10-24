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
    // fallback colspan saat kosong
    $yearsCount = 0;
    if (!empty($rpjmd_grouped) && is_array($rpjmd_grouped)) {
        $firstGroup = reset($rpjmd_grouped);
        if (!empty($firstGroup['years']) && is_array($firstGroup['years'])) {
            $yearsCount = count($firstGroup['years']);
        }
    }
    $emptyColspan = 9 + 2 * $yearsCount;
    ?>
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH</h2>

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
                            <tr id="year-header-row-tujuan" class="border p-2" style="border-top:2px solid;"></tr>
                            <tr id="year-header-row-sasaran" class="border p-2" style="border-top:2px solid;"></tr>
                        </thead>

                        <tbody id="rpjmd-table-body">
                            <?php if (!empty($rpjmd_grouped)): ?>
                                <?php foreach ($rpjmd_grouped as $periodIndex => $periodData): ?>
                                    <?php
                                    $years = $periodData['years'] ?? [];
                                    foreach (($periodData['misi_data'] ?? []) as $misi):
                                        // --- Siapkan struktur tujuan: flatten indikator tujuan & indikator sasaran jadi baris sejajar
                                        $preparedTujuan = [];
                                        $misiRowspan = 0;

                                        foreach ($misi['tujuan'] ?? [] as $tujuan) {
                                            // Left rows: indikator tujuan
                                            $leftRows = [];
                                            if (!empty($tujuan['indikator_tujuan'])) {
                                                foreach ($tujuan['indikator_tujuan'] as $it) {
                                                    $targets = [];
                                                    foreach ($it['target_tahunan_tujuan'] ?? [] as $t) {
                                                        // Tabel rpjmd_target_tujuan: tahun + target_tahunan
                                                        $targets[(string) $t['tahun']] = $t['target_tahunan'] ?? '-';
                                                    }
                                                    $leftRows[] = [
                                                        'indikator' => $it['indikator_tujuan'] ?? '-',
                                                        'targets' => $targets
                                                    ];
                                                }
                                            } else {
                                                // placeholder 1 baris jika tidak ada indikator tujuan
                                                $leftRows[] = ['indikator' => '-', 'targets' => []];
                                            }

                                            // Right rows: sasaran -> indikator_sasaran (dengan rowspan pada kolom sasaran)
                                            $rightRows = [];
                                            if (!empty($tujuan['sasaran'])) {
                                                foreach ($tujuan['sasaran'] as $sas) {
                                                    if (!empty($sas['indikator_sasaran'])) {
                                                        $countIs = count($sas['indikator_sasaran']);
                                                        foreach ($sas['indikator_sasaran'] as $idx => $is) {
                                                            $targets2 = [];
                                                            foreach ($is['target_tahunan'] ?? [] as $t2) {
                                                                $targets2[(string) $t2['tahun']] = $t2['target_tahunan'] ?? '-';
                                                            }
                                                            $rightRows[] = [
                                                                'sasaran' => ($idx === 0) ? ['text' => ($sas['sasaran_rpjmd'] ?? '-'), 'rowspan' => $countIs] : null,
                                                                'indikator' => $is['indikator_sasaran'] ?? '-',
                                                                'definisi' => $is['definisi_op'] ?? '-',
                                                                'satuan' => $is['satuan'] ?? '-',
                                                                'targets' => $targets2
                                                            ];
                                                        }
                                                    } else {
                                                        // sasaran tanpa indikator
                                                        $rightRows[] = [
                                                            'sasaran' => ['text' => ($sas['sasaran_rpjmd'] ?? '-'), 'rowspan' => 1],
                                                            'indikator' => '-',
                                                            'definisi' => '-',
                                                            'satuan' => '-',
                                                            'targets' => []
                                                        ];
                                                    }
                                                }
                                            } else {
                                                // tujuan tanpa sasaran
                                                $rightRows[] = [
                                                    'sasaran' => ['text' => '-', 'rowspan' => 1],
                                                    'indikator' => '-',
                                                    'definisi' => '-',
                                                    'satuan' => '-',
                                                    'targets' => []
                                                ];
                                            }

                                            $rowCount = max(count($leftRows), count($rightRows));
                                            $preparedTujuan[] = [
                                                'tujuan' => $tujuan,
                                                'leftRows' => $leftRows,
                                                'rightRows' => $rightRows,
                                                'rowCount' => $rowCount
                                            ];
                                            $misiRowspan += $rowCount;
                                        }

                                        $misiCellsPrinted = false;

                                        // --- Render
                                        foreach ($preparedTujuan as $block):
                                            $tujuanText = $block['tujuan']['tujuan_rpjmd'] ?? '-';
                                            $rowCount = $block['rowCount'];
                                            $leftRows = $block['leftRows'];
                                            $rightRows = $block['rightRows'];
                                            $tujuanCellsPrinted = false;

                                            for ($r = 0; $r < $rowCount; $r++):
                                                $left = $leftRows[$r] ?? ['indikator' => '-', 'targets' => []];
                                                $right = $rightRows[$r] ?? ['sasaran' => null, 'indikator' => '-', 'definisi' => '-', 'satuan' => '-', 'targets' => []];
                                                ?>
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
                                                        <td class="border p-2 align-top text-start" rowspan="<?= $rowCount ?>">
                                                            <?= esc($tujuanText) ?>
                                                        </td>
                                                        <?php $tujuanCellsPrinted = true; ?>
                                                    <?php endif; ?>

                                                    <!-- INDIKATOR TUJUAN (1 baris per indikator) -->
                                                    <td class="border p-2 align-top text-start">
                                                        <?= esc($left['indikator']) ?>
                                                    </td>

                                                    <!-- TARGET TUJUAN PER TAHUN -->
                                                    <span class="year-cells-tujuan" data-periode="<?= esc($periodIndex) ?>">
                                                        <?php foreach ($years as $y): ?>
                                                            <?php $v = $left['targets'][(string) $y] ?? '-'; ?>
                                                            <td class="border p-2 align-top text-start"><?= esc($v) ?></td>
                                                        <?php endforeach; ?>
                                                    </span>

                                                    <!-- SASARAN (rowspan di baris pertama indikator tiap sasaran) -->
                                                    <?php if (!empty($right['sasaran'])): ?>
                                                        <td class="border p-2 align-top text-start"
                                                            rowspan="<?= (int) $right['sasaran']['rowspan'] ?>">
                                                            <?= esc($right['sasaran']['text']) ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <!-- INDIKATOR SASARAN + DEF OP + SATUAN -->
                                                    <td class="border p-2 align-top text-start"><?= esc($right['indikator']) ?></td>
                                                    <td class="border p-2 align-top text-start"><?= esc($right['definisi']) ?></td>
                                                    <td class="border p-2 align-top text-start"><?= esc($right['satuan']) ?></td>

                                                    <!-- TARGET CAPAIAN PER TAHUN -->
                                                    <span class="year-cells-sasaran" data-periode="<?= esc($periodIndex) ?>">
                                                        <?php foreach ($years as $y): ?>
                                                            <?php $v2 = $right['targets'][(string) $y] ?? '-'; ?>
                                                            <td class="border p-2 align-top text-start"><?= esc($v2) ?></td>
                                                        <?php endforeach; ?>
                                                    </span>

                                                    <!-- ACTION (sekali per misi) -->
                                                    <?php if (!isset($misi['_action_printed'])): ?>
                                                        <td class="border p-2 align-middle text-center" rowspan="<?= $misiRowspan ?>">
                                                            <div class="d-flex flex-column align-items-center gap-2">
                                                                <a href="<?= base_url('adminkab/rpjmd/edit/' . (int) ($misi['id'] ?? 0)) ?>"
                                                                    class="btn btn-success btn-sm">
                                                                    <i class="fas fa-edit me-1"></i>Edit
                                                                </a>
                                                                <?php
                                                                $curStatus = $misi['status'] ?? 'draft';
                                                                $toggleClass = $curStatus === 'selesai' ? 'btn-warning' : 'btn-info';
                                                                $toggleText = $curStatus === 'selesai' ? 'Set Draft' : 'Set Selesai';
                                                                $toggleIcon = $curStatus === 'selesai' ? 'fas fa-undo' : 'fas fa-check';
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
                                                <?php
                                            endfor; // rows per tujuan
                                        endforeach; // prepared tujuan
                                    endforeach; // misi
                                    ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= (int) max(12, $emptyColspan) ?>"
                                        class="border p-4 text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Belum ada data RPJMD.
                                        <a href="<?= base_url('adminkab/rpjmd/tambah') ?>" class="text-success">Tambah data
                                            pertama</a>
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

        function updateTableHeaders(periodKey) {
            const rowTujuan = document.getElementById('year-header-row-tujuan');
            const spanTujuan = document.getElementById('year-header-span-tujuan');
            const rowSasaran = document.getElementById('year-header-row-sasaran');
            const spanSasaran = document.getElementById('year-header-span-sasaran');

            if (!periodData[periodKey] || !periodData[periodKey].years) return;
            const years = periodData[periodKey].years;

            spanTujuan.setAttribute('colspan', years.length);
            spanSasaran.setAttribute('colspan', years.length);

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

            rowSasaran.innerHTML = '';
        }

        function filterByPeriode() {
            const filterValue = document.getElementById('periodFilter').value;
            const statusFilterValue = document.getElementById('statusFilter').value;

            const rows = document.querySelectorAll('.periode-row');
            const yearCellsTujuan = document.querySelectorAll('.year-cells-tujuan');
            const yearCellsSasaran = document.querySelectorAll('.year-cells-sasaran');

            rows.forEach(r => r.style.display = 'none');
            yearCellsTujuan.forEach(c => c.style.display = 'none');
            yearCellsSasaran.forEach(c => c.style.display = 'none');

            rows.forEach(row => {
                const p = row.getAttribute('data-periode');
                const s = row.getAttribute('data-status') || 'draft';
                if (p === filterValue && (statusFilterValue === 'all' || s === statusFilterValue)) {
                    row.style.display = '';
                }
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

            const countEl = document.getElementById('visible-data-count');
            if (countEl) countEl.textContent = `Menampilkan ${visibleRows.length} dari ${totalRows} data`;

            const filtersEl = document.getElementById('active-filters');
            if (filtersEl) {
                let text = periodKey && periodKey !== 'all'
                    ? (periodData[periodKey] ? `Periode ${periodData[periodKey].period}` : `Periode ${periodKey}`)
                    : 'Semua Periode';
                if (statusFilter && statusFilter !== 'all') {
                    text += `, Status: ${statusFilter.charAt(0).toUpperCase() + statusFilter.slice(1)}`;
                }
                filtersEl.textContent = text;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const totalRows = document.querySelectorAll('.periode-row').length;
            const countEl = document.getElementById('visible-data-count');
            if (countEl) countEl.textContent = `Menampilkan ${totalRows} dari ${totalRows} data`;
            const filtersEl = document.getElementById('active-filters');
            if (filtersEl) filtersEl.textContent = 'Semua Data';
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
                .then(r => r.json())
                .then(d => { if (d.success) location.reload(); else alert('Gagal: ' + (d.message || 'Terjadi kesalahan')); })
                .catch(() => alert('Terjadi kesalahan saat mengubah status'));
        }
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RENSTRA - e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

<!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">Rencana Strategis</h2>

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

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex-fill">
                <!-- Period Filter -->
                <div class="d-flex align-items-center flex-fill me-3 gap-2">

                    <select id="rpjmd-filter" class="form-select" onchange="filterByRpjmd()" style="flex: 2;">
                        <option value="">Semua Sasaran RPJMD</option>
                        <?php if (isset($renstra_data) && !empty($renstra_data)): ?>
                            <?php 
                            $rpjmdSasaran = [];
                            foreach ($renstra_data as $data) {
                                $rpjmdSasaran[$data['rpjmd_sasaran']] = $data['rpjmd_sasaran'];
                            }
                            asort($rpjmdSasaran);
                            ?>
                            <?php foreach ($rpjmdSasaran as $sasaran): ?>
                                <option value="<?= esc($sasaran) ?>"><?= esc($sasaran) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <select id="periode-filter" class="form-select" onchange="filterByPeriode()" style="flex: 1;">
                        <option value="" selected>Semua Periode</option>
                        <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                            <?php foreach ($grouped_data as $periodKey => $periodData): ?>
                                <option value="<?= $periodKey ?>">
                                    Periode <?= $periodData['period'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <select id="status-filter" class="form-select" onchange="filterByStatus()" style="flex: 1;">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>
            <div>
                <a href="<?= base_url('adminopd/renstra/tambah') ?>" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-1"></i> TAMBAH
                </a>
            </div>
        </div>

        <!-- Tabel -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center small" style="border-collapse: collapse;">
                <thead class="table-success">
                    <tr>
                        <th rowspan="2" class="border p-2 align-middle">RPJMD Sasaran</th>
                        <th rowspan="2" class="border p-2 align-middle">Tujuan Renstra</th>
                        <th rowspan="2" class="border p-2 align-middle">Sasaran Renstra</th>
                        <th rowspan="2" class="border p-2 align-middle">Indikator Sasaran</th>
                        <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                        <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                            <?php 
                            $totalYears = 0;
                            foreach ($grouped_data as $periodData) {
                                $totalYears += count($periodData['years']);
                            }
                            ?>
                            <th colspan="<?= $totalYears ?>" class="border p-2 text-center">TARGET CAPAIAN PER TAHUN</th>
                        <?php else: ?>
                            <th colspan="5" class="border p-2">TARGET CAPAIAN PER TAHUN</th>
                        <?php endif; ?>
                        <th rowspan="2" class="border p-2 align-middle">Status</th>
                        <th rowspan="2" class="border p-2 align-middle">ACTION</th>
                    </tr>
                    <tr class="border p-2">
                        <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                            <?php foreach ($grouped_data as $periodIndex => $periodData): ?>
                                <?php foreach ($periodData['years'] as $year): ?>
                                    <th class="border p-2 year-header" data-periode="<?= $periodIndex ?>"><?= $year ?></th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th class="border p-2">2025</th>
                            <th class="border p-2">2026</th>
                            <th class="border p-2">2027</th>
                            <th class="border p-2">2028</th>
                            <th class="border p-2">2029</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="renstra-table-body">
                    <!-- Table content will be built by JavaScript -->
                </tbody>
                <script>
                    // Data dari PHP
                    const originalData = <?= json_encode($renstra_data ?? []) ?>;
                    const groupedData = <?= json_encode($grouped_data ?? []) ?>;

                    // Fungsi untuk render tabel sesuai filter
                    function renderTable(data) {
                        const tbody = document.getElementById('renstra-table-body');
                        tbody.innerHTML = '';
                        if (!data.length) {
                            tbody.innerHTML = `<tr>
                                <td colspan="12" class="border p-3 text-center text-muted">Data RENSTRA belum tersedia.</td>
                            </tr>`;
                            return;
                        }
                        data.forEach(row => {
                            let tr = `<tr>
                                <td class="border p-2">${row.rpjmd_sasaran || '-'}</td>
                                <td class="border p-2">${row.renstra_tujuan || '-'}</td>
                                <td class="border p-2">${row.sasaran}</td>
                                <td class="border p-2">${row.indikator_sasaran}</td>
                                <td class="border p-2">${row.satuan}</td>`;
                            // Target per tahun
                            if (groupedData && Object.keys(groupedData).length) {
                                Object.values(groupedData).forEach(period => {
                                    period.years.forEach(year => {
                                        tr += `<td class="border p-2">${(row.targets && row.targets[year]) ? row.targets[year] : '-'}</td>`;
                                    });
                                });
                            } else {
                                for (let y = 2025; y <= 2029; y++) {
                                    tr += `<td class="border p-2">${(row.targets && row.targets[y]) ? row.targets[y] : '-'}</td>`;
                                }
                            }
                            tr += `<td class="border p-2">${row.status === 'selesai' ? '<span class="badge bg-success">Selesai</span>' : '<span class="badge bg-secondary">Draft</span>'}</td>
                                <td class="border p-2">
                                    <a href="<?= base_url('adminopd/renstra/edit/') ?>${row.sasaran_id}" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i></a>
                                    <button onclick="confirmDelete('${row.sasaran_id}')" class="btn btn-sm btn-danger mb-1"><i class="fas fa-trash"></i></button>
                                    <button onclick="toggleStatus('${row.sasaran_id}')" class="btn btn-sm btn-info mb-1"><i class="fas fa-sync"></i></button>
                                </td>
                            </tr>`;
                            tbody.innerHTML += tr;
                        });
                    }

                    // Filter functions
                    function filterByRpjmd() {
                        applyFilters();
                    }
                    function filterByPeriode() {
                        applyFilters();
                    }
                    function filterByStatus() {
                        applyFilters();
                    }
                    function applyFilters() {
                        const rpjmd = document.getElementById('rpjmd-filter').value;
                        const periode = document.getElementById('periode-filter').value;
                        const status = document.getElementById('status-filter').value;
                        let filtered = originalData;
                        if (rpjmd) {
                            filtered = filtered.filter(row => row.rpjmd_sasaran === rpjmd);
                        }
                        if (periode) {
                            const [mulai, akhir] = periode.split('-');
                            filtered = filtered.filter(row => row.tahun_mulai == mulai && row.tahun_akhir == akhir);
                        }
                        if (status) {
                            filtered = filtered.filter(row => row.status === status);
                        }
                        renderTable(filtered);
                    }

                    // Render awal
                    document.addEventListener('DOMContentLoaded', function() {
                        renderTable(originalData);
                        document.getElementById('rpjmd-filter').addEventListener('change', filterByRpjmd);
                        document.getElementById('periode-filter').addEventListener('change', filterByPeriode);
                        document.getElementById('status-filter').addEventListener('change', filterByStatus);
                    });
                </script>
            </table>
        </div>
    </div>
  </main>

  <!-- JavaScript for Renstra functionality -->
  <script>
    // Set global variables for JavaScript
    window.base_url = '<?= base_url() ?>';
    window.csrf_header = '<?= csrf_header() ?>';
    window.csrf_hash = '<?= csrf_hash() ?>';
    
    // Debug: Log the base_url to see what it contains
    console.log('Base URL:', window.base_url);
    
    // Function to toggle status via AJAX (same as RENJA)
    function toggleStatus(sasaranId) {
        if (confirm('Apakah Anda yakin ingin mengubah status RENSTRA ini?')) {
            fetch('<?= base_url('adminopd/renstra/update-status') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                },
                body: JSON.stringify({
                    id: sasaranId
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
    
    // Function to confirm delete (same as RENJA)
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data RENSTRA ini?')) {
            window.location.href = '<?= base_url('adminopd/renstra/delete/') ?>' + id;
        }
    }
  </script>
  <script src="<?= base_url('assets/js/adminOpd/renstra/renstra.js') ?>"></script>
  <script>
    // Set period data for JavaScript
    setPeriodData(<?= json_encode($grouped_data ?? []) ?>);
    
    // Set original renstra data for filtering
    setOriginalData(<?= json_encode($renstra_data ?? []) ?>);
  </script>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div> <!-- End of main-content -->
</body>
</html>
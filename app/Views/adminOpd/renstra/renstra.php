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

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">Rencana Strategis</h2>

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex-fill">
                <!-- Period Filter -->
                <div class="d-flex align-items-center flex-fill me-3 gap-2">
                    <select id="periode-filter" class="form-select" onchange="filterByPeriode()" style="flex: 2;">
                        <option value="">Semua Periode</option>
                        <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                            <?php 
                            // Get the latest period (last key in the sorted array)
                            $periodKeys = array_keys($grouped_data);
                            $latestPeriod = end($periodKeys);
                            ?>
                            <?php foreach ($grouped_data as $periodKey => $periodData): ?>
                                <option value="<?= $periodKey ?>" <?= $periodKey === $latestPeriod ? 'selected' : '' ?>>
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
            <table class="table table-bordered text-center small" style="border-collapse: collapse;">
                <thead class="table-success">
                    <tr>
                        <th rowspan="2" class="border p-2 align-middle">Status</th>
                        <th rowspan="2" class="border p-2 align-middle">OPD</th>
                        <th rowspan="2" class="border p-2 align-middle">RPJMD Sasaran</th>
                        <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                        <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                        <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                        
                        <!-- Target columns header -->
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
                        
                        <th rowspan="2" class="border p-2 align-middle">ACTION</th>
                    </tr>
                    <tr class="border p-2">
                        <!-- Dynamic year headers for each period -->
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
                    <?php if (isset($renstra_data) && !empty($renstra_data)): ?>
                        <?php 
                        $no = 1; 
                        $currentSasaran = '';
                        $sasaranRowspan = [];
                        
                        // Calculate rowspan for each sasaran
                        foreach ($renstra_data as $data) {
                            $sasaranKey = $data['sasaran_id'];
                            if (!isset($sasaranRowspan[$sasaranKey])) {
                                $sasaranRowspan[$sasaranKey] = 0;
                            }
                            $sasaranRowspan[$sasaranKey]++;
                        }
                        
                        $sasaranCounter = [];
                        ?>
                        
                        <?php foreach ($renstra_data as $data): ?>
                            <?php 
                            $sasaranKey = $data['sasaran_id'];
                            $isFirstRowOfSasaran = !isset($sasaranCounter[$sasaranKey]);
                            if ($isFirstRowOfSasaran) {
                                $sasaranCounter[$sasaranKey] = true;
                            }
                            
                            // Get period for this data
                            $periodKey = $data['tahun_mulai'] . '-' . $data['tahun_akhir'];
                            $years = range($data['tahun_mulai'], $data['tahun_akhir']);
                            ?>
                            <tr data-periode="<?= $periodKey ?>" data-status="<?= $data['status'] ?? 'draft' ?>">
                                
                                <?php if ($isFirstRowOfSasaran): ?>
                                    <td class="border p-2" rowspan="<?= $sasaranRowspan[$sasaranKey] ?>">
                                        <?php 
                                        $status = $data['status'] ?? 'draft';
                                        $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning';
                                        ?>
                                        <button class="badge <?= $badgeClass ?> border-0" onclick="toggleStatus(<?= $data['sasaran_id'] ?>, '<?= base_url() ?>', '<?= csrf_header() ?>', '<?= csrf_hash() ?>')" style="cursor: pointer;" title="Klik untuk mengubah status">
                                            <?= ucfirst($status) ?>
                                        </button>
                                    </td>
                                    <td class="border p-2" rowspan="<?= $sasaranRowspan[$sasaranKey] ?>">
                                        <?= esc($data['nama_opd'] ?? 'N/A') ?>
                                    </td>
                                    <td class="border p-2" rowspan="<?= $sasaranRowspan[$sasaranKey] ?>">
                                        <?= esc($data['rpjmd_sasaran'] ?? 'N/A') ?>
                                    </td>
                                    <td class="border p-2" rowspan="<?= $sasaranRowspan[$sasaranKey] ?>">
                                        <?= esc($data['sasaran'] ?? 'N/A') ?>
                                    </td>
                                <?php endif; ?>
                                
                                <td class="border p-2"><?= esc($data['indikator_sasaran'] ?? 'N/A') ?></td>
                                <td class="border p-2"><?= esc($data['satuan'] ?? 'N/A') ?></td>
                                
                                <!-- Target per tahun untuk setiap periode -->
                                <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                                    <?php foreach ($grouped_data as $periodIndex => $periodData): ?>
                                        <span class="year-cells" data-periode="<?= $periodIndex ?>">
                                            <?php foreach ($periodData['years'] as $year): ?>
                                                <td class="border p-2 align-top text-start">
                                                    <?php 
                                                    // Check if this data belongs to this period
                                                    $dataPeriodKey = $data['tahun_mulai'] . '-' . $data['tahun_akhir'];
                                                    if ($dataPeriodKey === $periodIndex && isset($data['targets'][$year])) {
                                                        echo esc($data['targets'][$year]);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Fallback untuk default years jika tidak ada grouped_data -->
                                    <?php foreach (range(2025, 2029) as $year): ?>
                                        <td class="border p-2">
                                            <?= isset($data['targets'][$year]) ? esc($data['targets'][$year]) : '-' ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if ($isFirstRowOfSasaran): ?>
                                    <td class="border p-2 align-middle text-center" rowspan="<?= $sasaranRowspan[$sasaranKey] ?>">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <a href="<?= base_url('adminopd/renstra/edit/' . $data['sasaran_id']) ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <?php 
                                            $currentStatus = $data['status'] ?? 'draft';
                                            $toggleClass = $currentStatus === 'selesai' ? 'btn-warning' : 'btn-info';
                                            $toggleText = $currentStatus === 'selesai' ? 'Set Draft' : 'Set Selesai';
                                            $toggleIcon = $currentStatus === 'selesai' ? 'fas fa-undo' : 'fas fa-check';
                                            ?>
                                            <button class="btn <?= $toggleClass ?> btn-sm" onclick="toggleStatus(<?= $data['sasaran_id'] ?>, '<?= base_url() ?>', '<?= csrf_header() ?>', '<?= csrf_hash() ?>')">
                                                <i class="<?= $toggleIcon ?> me-1"></i><?= $toggleText ?>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteRenstra(<?= $data['sasaran_id'] ?>, '<?= base_url() ?>')">
                                                <i class="fas fa-trash me-1"></i>Hapus
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <?php 
                            $totalColumns = 7; // Status, OPD, RPJMD Sasaran, Sasaran, Indikator, Satuan, Action
                            if (isset($grouped_data) && !empty($grouped_data)) {
                                foreach ($grouped_data as $periodData) {
                                    $totalColumns += count($periodData['years']);
                                }
                            } else {
                                $totalColumns += 5; // Default 5 years
                            }
                            ?>
                            <td colspan="<?= $totalColumns ?>" class="border p-3 text-center text-muted">
                                Belum ada data Renstra. <a href="<?= base_url('adminopd/renstra/tambah') ?>" class="text-success">Tambah data pertama</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </main>

  <!-- JavaScript for Renstra functionality -->
  <script src="<?= base_url('assets/js/adminOpd/renstra/renstra.js') ?>"></script>
  <script>
    // Set period data for JavaScript
    setPeriodData(<?= json_encode($grouped_data ?? []) ?>);
  </script>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>
</html>

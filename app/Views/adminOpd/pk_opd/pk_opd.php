<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PK OPD</title>
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
        <h2 class="h3 fw-bold text-success text-center mb-4">PK OPD</h2>

        <!-- Filter -->
        <?php 
        // Extract unique years from PK data
        $availableYears = [];
        foreach ($pk_data as $pk) {
            $year = date('Y', strtotime($pk['tanggal']));
            $availableYears[$year] = $year; // Use key to avoid duplicates
        }
        krsort($availableYears); // Sort descending (newest first)
        
        // Get current selected year from GET parameter
        $selectedYear = $_GET['tahun'] ?? '';
        
        // Filter data based on selected year
        $filteredPkData = $pk_data;
        if (!empty($selectedYear)) {
            $filteredPkData = array_filter($pk_data, function($pk) use ($selectedYear) {
                return date('Y', strtotime($pk['tanggal'])) == $selectedYear;
            });
        }
        ?>
        
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex-fill">
                <form method="GET" action="<?= current_url() ?>" class="d-flex gap-2">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= $year ?>" <?= ($selectedYear == $year) ? 'selected' : '' ?>>
                                <?= $year ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                
                <?php if (!empty($selectedYear)): ?>
                    <a href="<?= current_url() ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>
            <a href="<?= base_url('adminopd/pk_opd/tambah') ?>" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Tambah PK OPD
            </a>
        </div>
        <!-- End Filter -->

        <!-- Info Data -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <small class="text-muted">
                Menampilkan <?= count($filteredPkData) ?> data
            </small>
            <small class="text-muted">
                Tahun: <?php if (!empty($selectedYear)): ?><?= $selectedYear ?><?php else: ?>Semua<?php endif; ?>
            </small>
        </div>
        
    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-success">
                <tr>
                    <th class="text-center">NO</th>
                    <th class="text-center">Jenis PK</th>
                    <th class="text-center">SASARAN</th>
                    <th class="text-center">INDIKATOR</th>
                    <th class="text-center">TARGET</th>
                    <th class="text-center">PROGRAM</th>
                    <th class="text-center">ANGGARAN</th>
                    <th class="text-center">FILE</th>
                    <th class="text-center">ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($filteredPkData)): ?>
                <?php $pkNumber = 1; ?>
                <?php foreach ($filteredPkData as $pk): ?>
                    <?php 
                    // Buat flat list indikator
                    $allIndikator = [];
                    if (!empty($pk['sasaran'])) {
                        foreach ($pk['sasaran'] as $sasaran) {
                            if (!empty($sasaran['indikator'])) {
                                foreach ($sasaran['indikator'] as $indikator) {
                                    $allIndikator[] = [
                                        'sasaran_id' => $sasaran['sasaran_id'],
                                        'sasaran'    => $sasaran['sasaran'],
                                        'indikator'  => $indikator['indikator'],
                                        'target'     => $indikator['target']
                                    ];
                                }
                            } else {
                                $allIndikator[] = [
                                    'sasaran_id' => $sasaran['sasaran_id'],
                                    'sasaran'    => $sasaran['sasaran'],
                                    'indikator'  => 'Tidak ada indikator',
                                    'target'     => '-'
                                ];
                            }
                        }
                    } else {
                        $allIndikator[] = [
                            'sasaran_id' => null,
                            'sasaran'    => 'Tidak ada sasaran',
                            'indikator'  => 'Tidak ada indikator',
                            'target'     => '-'
                        ];
                    }

                    // Ambil list program
                    $allProgram = !empty($pk['program']) ? $pk['program'] : [['program_kegiatan'=>'Tidak ada program','anggaran'=>0]];

                    $totalIndikatorRows = count($allIndikator);
                    $totalProgramRows   = count($allProgram);
                    $totalRows          = max($totalIndikatorRows, $totalProgramRows);
                    ?>
                    
                    <?php for ($rowIndex = 0; $rowIndex < $totalRows; $rowIndex++): ?>
                        <tr>
                            <!-- Nomor & Jenis PK -->
                            <?php if ($rowIndex == 0): ?>
                                <td class="align-middle text-center fw-bold" rowspan="<?= $totalRows ?>">
                                    <?= $pkNumber ?>
                                </td>
                                <td class="align-middle text-center" rowspan="<?= $totalRows ?>">
                                    <span class="badge text-dark"><?= esc(ucwords($pk['jenis'])) ?></span>
                                </td>
                            <?php endif; ?>

                            <!-- Sasaran, Indikator, Target -->
                            <?php if ($rowIndex < $totalIndikatorRows): ?>
                                <?php 
                                $currentIndikator = $allIndikator[$rowIndex];
                                $isFirstRowOfSasaran = ($rowIndex == 0) || 
                                    ($allIndikator[$rowIndex-1]['sasaran_id'] != $currentIndikator['sasaran_id']);

                                // Hitung rowspan normal untuk sasaran ini
                                $sasaranRowspan = 1;
                                if ($isFirstRowOfSasaran) {
                                    for ($i = $rowIndex + 1; $i < $totalIndikatorRows; $i++) {
                                        if ($allIndikator[$i]['sasaran_id'] == $currentIndikator['sasaran_id']) {
                                            $sasaranRowspan++;
                                        } else break;
                                    }
                                }

                                // Apakah indikator terakhir perlu digabung karena program lebih banyak?
                                $isLastIndikator = ($rowIndex == $totalIndikatorRows - 1 && $totalIndikatorRows < $totalProgramRows);
                                $rowspanIndikator = $isLastIndikator ? ($totalProgramRows - $totalIndikatorRows + 1) : 1;
                                ?>
                                <!-- Sasaran (pakai rowspan per sasaran) -->
                                <?php if ($isFirstRowOfSasaran): ?>
                                    <td class="align-middle" rowspan="<?= $sasaranRowspan ?>">
                                        <?php if (strpos($currentIndikator['sasaran'], 'Tidak ada') === 0): ?>
                                            <?= esc($currentIndikator['sasaran']) ?>
                                        <?php else: ?>
                                            <?= esc(ucwords(strtolower($currentIndikator['sasaran']))) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <!-- Indikator -->
                                <td rowspan="<?= $rowspanIndikator ?>">
                                    <?php if ($currentIndikator['indikator'] === '-' || strpos($currentIndikator['indikator'], 'Tidak ada') === 0): ?>
                                        <?= esc($currentIndikator['indikator']) ?>
                                    <?php else: ?>
                                        <?= esc(ucwords(strtolower($currentIndikator['indikator']))) ?>
                                    <?php endif; ?>
                                </td>
                                <!-- Target -->
                                <td class="text-center" rowspan="<?= $rowspanIndikator ?>">
                                    <?php if ($currentIndikator['target'] === '-' || is_numeric($currentIndikator['target'])): ?>
                                        <?= esc($currentIndikator['target']) ?>
                                    <?php else: ?>
                                        <?= esc(ucwords(strtolower($currentIndikator['target']))) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <!-- Program + Anggaran -->
                            <?php if ($rowIndex < $totalProgramRows): ?>
                                <?php 
                                $program = $allProgram[$rowIndex];
                                $isLastProgram = ($rowIndex == $totalProgramRows - 1 && $totalProgramRows < $totalIndikatorRows);
                                $rowspanProgram = $isLastProgram ? ($totalIndikatorRows - $totalProgramRows + 1) : 1;
                                ?>
                                <td class="align-middle" rowspan="<?= $rowspanProgram ?>">
                                    <?php if (strpos($program['program_kegiatan'], 'Tidak ada') === 0): ?>
                                        <?= esc($program['program_kegiatan']) ?>
                                    <?php else: ?>
                                        <?= esc(ucwords(strtolower($program['program_kegiatan']))) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-end" rowspan="<?= $rowspanProgram ?>">
                                    Rp <?= number_format((float)$program['anggaran'], 0, ',', '.') ?>
                                </td>
                            <?php endif; ?>

                            <!-- File & Action -->
                            <?php if ($rowIndex == 0): ?>
                                <td class="align-middle text-center" rowspan="<?= $totalRows ?>">
                                    <a href="<?= base_url('adminopd/pk_opd/cetak/' . $pk['pk_id']) ?>" 
                                    class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="fas fa-download me-1"></i>Unduh
                                    </a>
                                </td>
                                <td class="align-middle text-center" rowspan="<?= $totalRows ?>">
                                    <div class="d-grid gap-1">
                                        <a href="<?= base_url('adminopd/pk_opd/edit/' . $pk['pk_id']) ?>" 
                                        class="btn btn-success btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn btn-danger btn-sm" onclick="deletePk(<?= $pk['pk_id'] ?>)">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>

                    <?php $pkNumber++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center p-4">Tidak ada data</td></tr>
            <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>
</main>

<!-- Load PK JavaScript -->
<script>
  // Define base_url globally for pk.js
  const base_url = '<?= base_url() ?>';
</script>
<script src="<?= base_url('assets/js/adminOpd/pk/pk.js') ?>"></script>

<?= $this->include('adminOpd/templates/footer.php'); ?>
</body>
</html>
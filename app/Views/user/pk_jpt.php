<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PK JPT</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>

  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        PERJANJIAN KINERJA PIMPINAN
      </h4>
      
      <!-- Filter -->
      <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
          <div class="d-flex gap-2 flex-fill">
            <select id="opdFilter" class="form-select w-75" onchange="filterData()">
                <option value="all">Pilih Unit Kerja</option>
                <?php if (isset($opd_data)): ?>
                    <?php foreach ($opd_data as $opd): ?>
                        <option value="<?= $opd['id'] ?>">
                            <?= $opd['nama_opd'] ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <select id="yearFilter" class="form-select w-25" onchange="filterData()">
                <option value="all">SEMUA TAHUN</option>
                <?php if (isset($available_years)): ?>
                    <?php foreach ($available_years as $year): ?>
                        <option value="<?= $year ?>">
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
          </div>
      </div>
      
      <!-- Tabel PK JPT -->
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-success">
            <tr>
              <th class="text-center" style="width: 5%;">NO</th>
              <th class="text-center" style="width: 25%;">SASARAN</th>
              <th class="text-center" style="width: 20%;">INDIKATOR</th>
              <th class="text-center" style="width: 10%;">TARGET</th>
              <th class="text-center" style="width: 10%;">PROGRAM</th>
            </tr>
          </thead>
          <tbody id="pkJptTableBody">
            <!-- Default message -->
            <tr id="defaultMessage" style="display: block;">
              <td colspan="5" class="text-center text-muted py-4">
                <i class="fas fa-info-circle mb-2"></i><br>
                Silakan pilih Unit Kerja untuk menampilkan data PK JPT
              </td>
            </tr>
            
            <!-- Data will be populated here by JavaScript -->
            <?php if (!empty($pkJptData) && is_array($pkJptData)): ?>
              <?php $globalNo = 1; ?>
              <?php foreach ($pkJptData as $pk): ?>
                <?php $year = date('Y', strtotime($pk['tanggal'])); ?>
                
                <?php if (!empty($pk['sasaran_pk'])): ?>
                  <?php 
                  // Hitung total indikator dan program
                  $totalIndikator = 0;
                  foreach ($pk['sasaran_pk'] as $sasaran) {
                      $totalIndikator += count($sasaran['indikator'] ?? [1]);
                  }
                  $totalProgram = count($pk['program'] ?? []);
                  
                  // Buat array untuk indikator
                  $indikatorList = [];
                  foreach ($pk['sasaran_pk'] as $sasaran) {
                      if (!empty($sasaran['indikator'])) {
                          foreach ($sasaran['indikator'] as $indikator) {
                              $indikatorList[] = [
                                  'sasaran' => $sasaran,
                                  'indikator' => $indikator
                              ];
                          }
                      } else {
                          // Sasaran tanpa indikator
                          $indikatorList[] = [
                              'sasaran' => $sasaran,
                              'indikator' => null
                          ];
                      }
                  }
                  
                  $maxRows = max($totalIndikator, 1);
                  ?>
                  
                  <!-- Render rows untuk indikator -->
                  <?php for ($i = 0; $i < count($indikatorList); $i++): ?>
                      <tr class="pk-row" 
                          data-opd="<?= $pk['opd_id'] ?>" 
                          data-tahun="<?= $year ?>"
                          style="display: none;">
                        
                        <?php if ($i == 0): ?>
                            <!-- NO dengan rowspan untuk semua indikator -->
                            <td class="text-center" rowspan="<?= count($indikatorList) ?>"><?= $globalNo ?></td>
                        <?php endif; ?>
                        
                        <?php 
                        $currentItem = $indikatorList[$i];
                        $currentSasaran = $currentItem['sasaran'];
                        $currentIndikator = $currentItem['indikator'];
                        
                        // Hitung apakah ini indikator pertama dari sasaran ini
                        $isFirstOfSasaran = true;
                        $sasaranRowspan = 0;
                        
                        // Hitung berapa indikator untuk sasaran ini
                        foreach ($indikatorList as $j => $item) {
                            if ($item['sasaran']['sasaran'] === $currentSasaran['sasaran']) {
                                $sasaranRowspan++;
                                if ($j < $i) {
                                    $isFirstOfSasaran = false;
                                }
                            }
                        }
                        ?>
                        
                        <!-- SASARAN -->
                        <?php if ($isFirstOfSasaran): ?>
                            <td class="text-left" rowspan="<?= $sasaranRowspan ?>"><?= esc($currentSasaran['sasaran']) ?></td>
                        <?php endif; ?>
                        
                        <!-- INDIKATOR dan TARGET -->
                        <?php if ($currentIndikator): ?>
                            <td class="text-left"><?= esc($currentIndikator['indikator']) ?></td>
                            <td class="text-center"><?= esc($currentIndikator['target']) ?></td>
                        <?php else: ?>
                            <td class="text-muted">Tidak ada indikator</td>
                            <td class="text-center">-</td>
                        <?php endif; ?>
                        
                        <!-- PROGRAM dengan rowspan proporsional -->
                        <?php if ($totalProgram > 0): ?>
                            <?php
                            // Hitung rowspan untuk setiap program
                            $programRowspan = count($indikatorList) > 0 ? max(1, floor(count($indikatorList) / $totalProgram)) : 1;
                            $extraRows = $totalProgram > 0 ? count($indikatorList) % $totalProgram : 0;
                            
                            // Tentukan program mana yang ditampilkan pada baris ini
                            $currentProgramIndex = $programRowspan > 0 ? floor($i / $programRowspan) : 0;
                            
                            // Jika ada sisa baris, program terakhir mendapat tambahan
                            if ($extraRows > 0 && $currentProgramIndex >= $totalProgram - $extraRows) {
                                $adjustedRowspan = $programRowspan + 1;
                                $currentProgramIndex = $totalProgram - 1 - ($adjustedRowspan > 0 ? floor((count($indikatorList) - 1 - $i) / $adjustedRowspan) : 0);
                            }
                            
                            // Pastikan currentProgramIndex tidak melebihi batas array
                            $currentProgramIndex = min($currentProgramIndex, $totalProgram - 1);
                            
                            // Cek apakah ini baris pertama untuk program ini
                            $isFirstRowOfProgram = false;
                            if ($currentProgramIndex < $totalProgram && $currentProgramIndex >= 0) {
                                if ($extraRows > 0 && $currentProgramIndex >= $totalProgram - $extraRows) {
                                    // Program yang mendapat tambahan baris
                                    $startRow = ($totalProgram - $extraRows) * $programRowspan + ($currentProgramIndex - ($totalProgram - $extraRows)) * ($programRowspan + 1);
                                    $isFirstRowOfProgram = ($i == $startRow);
                                    $actualRowspan = $programRowspan + 1;
                                } else {
                                    // Program normal
                                    $startRow = $currentProgramIndex * $programRowspan;
                                    $isFirstRowOfProgram = ($i == $startRow);
                                    $actualRowspan = $programRowspan;
                                }
                            }
                            ?>
                            
                            <?php if ($isFirstRowOfProgram && $currentProgramIndex < $totalProgram): ?>
                                <td class="text-left" rowspan="<?= $actualRowspan ?>"><?= esc($pk['program'][$currentProgramIndex]['program_kegiatan']) ?></td>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Jika tidak ada program -->
                            <?php if ($i == 0): ?>
                                <td class="text-center" rowspan="<?= count($indikatorList) ?>">-</td>
                            <?php endif; ?>
                        <?php endif; ?>
                      </tr>
                  <?php endfor; ?>
                  <?php $globalNo++; ?>
                  
                <?php else: ?>
                  <!-- PK tanpa sasaran -->
                  <tr class="pk-row" 
                      data-opd="<?= $pk['opd_id'] ?>" 
                      data-tahun="<?= $year ?>"
                      style="display: none;">
                    <td class="text-center"><?= $globalNo++ ?></td>
                    <td class="text-muted">Tidak ada sasaran</td>
                    <td class="text-muted">-</td>
                    <td class="text-center">-</td>
                    <td class="text-left">
                      <?php if (!empty($pk['program'])): ?>
                        <?php foreach ($pk['program'] as $program): ?>
                          <div class="mb-1"><?= esc($program['program_kegiatan']) ?></div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?= $this->include('user/templates/footer'); ?>

<script>
function clearTableData() {
    // Hide all data rows
    const pkRows = document.querySelectorAll('.pk-row');
    pkRows.forEach(row => row.style.display = 'none');
    
    // Hide any existing "no data found" messages
    const existingNoDataMessages = document.querySelectorAll('.no-data-message');
    existingNoDataMessages.forEach(msg => msg.remove());
}

function showDefaultMessage() {
    const defaultMsg = document.getElementById('defaultMessage');
    if (defaultMsg) {
        defaultMsg.style.display = 'table-row';
    }
}

function hideDefaultMessage() {
    const defaultMsg = document.getElementById('defaultMessage');
    if (defaultMsg) {
        defaultMsg.style.display = 'none';
    }
}

function showNoDataMessage() {
    const tbody = document.getElementById('pkJptTableBody');
    const noDataRow = document.createElement('tr');
    noDataRow.className = 'no-data-message';
    noDataRow.innerHTML = `
        <td colspan="5" class="text-center text-muted py-4">
            <i class="fas fa-search mb-2"></i><br>
            Tidak ada data PK JPT untuk filter yang dipilih
        </td>
    `;
    tbody.appendChild(noDataRow);
}

function filterData() {
    const opdFilter = document.getElementById('opdFilter').value;
    const yearFilter = document.getElementById('yearFilter').value;

    // Clear existing data
    clearTableData();

    // If no OPD is selected, show default message
    if (opdFilter === 'all') {
        showDefaultMessage();
        return;
    }

    // Hide default message when filtering
    hideDefaultMessage();

    // Get all rows that match the OPD filter
    let matchingRows = document.querySelectorAll(`.pk-row[data-opd="${opdFilter}"]`);
    
    // Apply year filter if specified
    if (yearFilter !== 'all') {
        matchingRows = Array.from(matchingRows).filter(row => 
            row.getAttribute('data-tahun') === yearFilter
        );
    }

    // Show matching rows
    if (matchingRows.length > 0) {
        matchingRows.forEach(row => {
            row.style.display = 'table-row';
        });
        
        // Renumber the visible rows
        renumberVisibleRows();
    } else {
        showNoDataMessage();
    }
}

function renumberVisibleRows() {
    const visibleRows = Array.from(document.querySelectorAll('.pk-row')).filter(row => 
        row.style.display === 'table-row' || row.style.display === ''
    );
    
    let pkNumber = 1;
    let currentPkId = null;
    
    visibleRows.forEach((row) => {
        const opdId = row.getAttribute('data-opd');
        const tahun = row.getAttribute('data-tahun');
        const pkIdentifier = `${opdId}-${tahun}`;
        
        // Check if this is a new PK (different OPD+Year combination)
        if (currentPkId !== pkIdentifier) {
            currentPkId = pkIdentifier;
            
            // Find the first cell (number cell) in the current row
            const cells = row.getElementsByTagName('td');
            if (cells.length > 0 && cells[0].hasAttribute('rowspan')) {
                cells[0].textContent = pkNumber++;
            }
        }
    });
}

function resetFilters() {
    document.getElementById('opdFilter').value = 'all';
    document.getElementById('yearFilter').value = 'all';
    clearTableData();
    showDefaultMessage();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Show default message initially
    showDefaultMessage();
});
</script>
</body>
</html>
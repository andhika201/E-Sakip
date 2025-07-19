<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RPJMD</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Header -->
  <?= $this->include('user/templates/header'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH
      </h4>

      <!-- Filter Periode -->
      <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-4">
        <div class="d-flex gap-2 flex-fill" style="max-width: 500px;">
          <select id="periode-filter" class="form-select">
            <option value="">Semua Periode</option>
            <?php if (!empty($rpjmdGrouped)): ?>
              <?php foreach ($rpjmdGrouped as $periodKey => $periodData): ?>
                <option value="<?= $periodKey ?>">
                  Periode <?= esc($periodData['period']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <!-- Tabel RPJMD -->
      <?php if (isset($message)): ?>
        <div class="alert alert-info text-center">
          <i class="fas fa-info-circle me-2"></i>
          <?= esc($message) ?>
        </div>
      <?php elseif (empty($rpjmdGrouped)): ?>
        <div class="alert alert-warning text-center">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Belum ada data RPJMD yang telah selesai.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
              <tr>
                <th rowspan="2" class="border p-2 align-middle">MISI</th>
                <th rowspan="2" class="border p-2 align-middle">TUJUAN</th>
                <th rowspan="2" class="border p-2 align-middle">INDIKATOR TUJUAN</th>
                <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                <th rowspan="2" class="border p-2 align-middle">DEFINISI OPERASIONAL</th>
                <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                <th colspan="5" class="border p-2" id="year-header-span">TARGET CAPAIAN PER TAHUN</th>
              </tr>
              <tr class="border p-2" style="border-top: 2px solid;" id="year-header-row">
                <!-- Akan diisi oleh JavaScript -->
              </tr>
            </thead>
            <tbody id="rpjmd-table-body">
              <?php foreach ($rpjmdGrouped as $periodIndex => $periodData): ?>
                <?= renderPeriodData($periodIndex, $periodData) ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // Data periode dari controller
    const periodData = <?= json_encode($rpjmdGrouped ?? []) ?>;
    let currentPeriod = '';

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', initRpjmdTable);

    function initRpjmdTable() {
      const periodeFilter = document.getElementById('periode-filter');
      periodeFilter.addEventListener('change', filterByPeriode);
      
      // Set initial period
      const initialPeriod = Object.keys(periodData)[0] || '';
      if (initialPeriod) {
        periodeFilter.value = initialPeriod;
        filterByPeriode();
      }
    }

    function filterByPeriode() {
      const selectedPeriode = document.getElementById('periode-filter').value;
      if (selectedPeriode === currentPeriod) return;
      
      currentPeriod = selectedPeriode;
      const rows = document.querySelectorAll('#rpjmd-table-body tr');
      
      if (selectedPeriode === '') {
        // Show all periods
        rows.forEach(row => row.classList.remove('d-none'));
        updateTableHeaders(Object.keys(periodData)[0]);
      } else {
        // Show selected period
        rows.forEach(row => {
          row.classList.toggle('d-none', row.dataset.periode !== selectedPeriode);
        });
        updateTableHeaders(selectedPeriode);
      }
    }

    function updateTableHeaders(periodKey) {
      const yearHeaderRow = document.getElementById('year-header-row');
      const yearHeaderSpan = document.getElementById('year-header-span');
      
      if (!periodData[periodKey] || !periodData[periodKey].years) return;
      
      const years = periodData[periodKey].years;
      yearHeaderSpan.colSpan = years.length;
      yearHeaderRow.innerHTML = '';
      
      years.forEach(year => {
        const th = document.createElement('th');
        th.className = 'border p-2';
        th.textContent = year;
        yearHeaderRow.appendChild(th);
      });
    }
  </script>

  <?= $this->include('user/templates/footer'); ?>
</body>
</html>

<?php
// Helper function to render period data (could be in separate file)
function renderPeriodData($periodIndex, $periodData) {
  ob_start();
  foreach ($periodData['misi_data'] as $misi) {
    if (empty($misi['tujuan'])) continue;
    
    $misiRowspan = calculateMisiRowspan($misi);
    $firstMisiRow = true;
    
    foreach ($misi['tujuan'] as $tujuan) {
      if (empty($tujuan['sasaran'])) continue;
      
      $tujuanRowspan = calculateTujuanRowspan($tujuan);
      $firstTujuanRow = true;
      
      foreach ($tujuan['sasaran'] as $sasaran) {
        if (empty($sasaran['indikator_sasaran'])) continue;
        
        $sasaranRowspan = count($sasaran['indikator_sasaran']);
        $firstSasaranRow = true;
        
        foreach ($sasaran['indikator_sasaran'] as $indikator) {
          echo '<tr class="periode-row" data-periode="' . $periodIndex . '">';
          
          // MISI cell
          if ($firstMisiRow) {
            echo '<td class="border p-2 align-top text-start" rowspan="' . $misiRowspan . '">' 
                 . esc($misi['misi']) . '</td>';
            $firstMisiRow = false;
          }
          
          // TUJUAN cell
          if ($firstTujuanRow) {
            echo '<td class="border p-2 align-top text-start" rowspan="' . $tujuanRowspan . '">' 
                 . esc($tujuan['tujuan_rpjmd']) . '</td>';
            
            // INDIKATOR TUJUAN cell
            echo '<td class="border p-2 align-top text-start" rowspan="' . $tujuanRowspan . '">';
            if (!empty($tujuan['indikator_tujuan'])) {
              echo implode('<br>', array_map(
                fn($it) => esc($it['indikator_tujuan']), 
                $tujuan['indikator_tujuan']
              ));
            } else {
              echo '-';
            }
            echo '</td>';
            
            $firstTujuanRow = false;
          }
          
          // SASARAN cell
          if ($firstSasaranRow) {
            echo '<td class="border p-2 align-top text-start" rowspan="' . $sasaranRowspan . '">' 
                 . esc($sasaran['sasaran_rpjmd']) . '</td>';
            $firstSasaranRow = false;
          }
          
          // Indikator details
          echo '<td class="border p-2 align-top text-start">' . esc($indikator['indikator_sasaran']) . '</td>';
          echo '<td class="border p-2 align-top text-start">' . esc($indikator['definisi_op'] ?? '-') . '</td>';
          echo '<td class="border p-2 align-top text-start">' . esc($indikator['satuan'] ?? '-') . '</td>';
          
          // Year targets
          foreach ($periodData['years'] as $year) {
            $target = findYearTarget($indikator['target_tahunan'] ?? [], $year);
            echo '<td class="border p-2 align-top text-middle">' . esc($target) . '</td>';
          }
          
          echo '</tr>';
        }
      }
    }
  }
  return ob_get_clean();
}

// Helper functions
function calculateMisiRowspan($misi) {
  $count = 0;
  foreach ($misi['tujuan'] as $tujuan) {
    if (!empty($tujuan['sasaran'])) {
      foreach ($tujuan['sasaran'] as $sasaran) {
        $count += !empty($sasaran['indikator_sasaran']) 
          ? count($sasaran['indikator_sasaran']) 
          : 1;
      }
    } else {
      $count++;
    }
  }
  return $count;
}

function calculateTujuanRowspan($tujuan) {
  $count = 0;
  foreach ($tujuan['sasaran'] as $sasaran) {
    $count += !empty($sasaran['indikator_sasaran']) 
      ? count($sasaran['indikator_sasaran']) 
      : 1;
  }
  return $count;
}

function findYearTarget($targets, $year) {
  foreach ($targets as $target) {
    if ($target['tahun'] == $year) {
      return $target['target_tahunan'];
    }
  }
  return '-';
}
?>
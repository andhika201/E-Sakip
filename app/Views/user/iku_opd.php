<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IKU OPD</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center mb-4" style="color: #00743e;">
        INDIKATOR KINERJA UTAMA (IKU) OPD
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
            <select id="periodeFilter" class="form-select" onchange="filterData()" style="flex: 1;">
                <option value="all">Semua Periode</option>
                <?php if (isset($available_periods)): ?>
                    <?php foreach ($available_periods as $period): ?>
                        <option value="<?= $period ?>">
                            Periode <?= $period ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
          </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-success">
            <tr>
                <th rowspan="2" class="border p-2 align-middle">Sasaran IKU</th>
                <th rowspan="2" class="border p-2 align-middle">Indikator Kinerja</th>
                <th rowspan="2" class="border p-2 align-middle">Definisi Formulasi</th>
                <th rowspan="2" class="border p-2 align-middle">Satuan</th>

                <!-- Target columns header -->
                <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                    <?php 
                    $totalYears = 0;
                    foreach ($grouped_data as $periodData) {
                        $totalYears += count($periodData['years']);
                    }
                    ?>
                    <th colspan="<?= $totalYears ?>" class="border p-2 text-center" id="year-header-span">Target Capaian Pertahun</th>
                <?php else: ?>
                    <th colspan="5" class="border p-2 text-center" id="year-header-span">Target Capaian Pertahun</th>
                <?php endif; ?>
                <th rowspan="2" class="border p-2 align-middle">Program Pendukung</th>
            </tr>
            <tr class="border p-2" id="year-header-row">
                <!-- Dynamic year headers for each period -->
                <?php if (isset($grouped_data) && !empty($grouped_data)): ?>
                    <?php foreach ($grouped_data as $periodIndex => $periodData): ?>
                        <?php foreach ($periodData['years'] as $year): ?>
                            <th class="border p-2 year-header" data-periode="<?= $periodIndex ?>"><?= $year ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th class="border p-2 year-header">2025</th>
                    <th class="border p-2 year-header">2026</th>
                    <th class="border p-2 year-header">2027</th>
                    <th class="border p-2 year-header">2028</th>
                    <th class="border p-2 year-header">2029</th>
                <?php endif; ?>
            </tr>
            </thead>
          <tbody id="iku-table-body">
              <!-- Default message when no OPD selected -->
              <tr id="select-opd-message">
                  <td colspan="10" class="border p-4 text-center text-muted">
                      <i class="fas fa-building me-2" style="font-size: 2rem;"></i>
                      <div class="mt-2">
                          <strong>Silakan pilih Unit Kerja terlebih dahulu</strong><br>
                          <small>Pilih Unit Kerja dari dropdown di atas untuk melihat data IKU OPD</small>
                      </div>
                  </td>
              </tr>
              
              <!-- No data message (hidden by default, shown by JavaScript when no filtered results) -->
              <tr id="no-data-message" style="display: none;">
                  <td colspan="10" class="border p-4 text-center text-muted">
                      <i class="fas fa-search mb-2 d-block" style="font-size: 2rem;"></i>
                      <span id="no-data-text">Tidak ada data yang sesuai dengan filter</span>
                  </td>
              </tr>
          </tbody>
        </table>
      </div>
      
    </div>
  </div>
</main>

<script>
  // Store original data for filtering
  const originalData = <?= json_encode($iku_data ?? []) ?>;
  
  // Initially hide all rows and show "select OPD" message
  document.addEventListener('DOMContentLoaded', function() {
      showSelectOpdMessage();
  });

  // Function to filter data
  function filterData() {
      const opdFilter = document.getElementById('opdFilter').value;
      const periodeFilter = document.getElementById('periodeFilter').value;
      
      // Debug: check if data exists
      if (originalData.length === 0) {
          console.log('No original data available');
          clearTableData();
          showSelectOpdMessage();
          return;
      }
      
      // User must select OPD first
      if (opdFilter === 'all') {
          clearTableData();
          showSelectOpdMessage();
          return;
      }
      
      // Hide select OPD message
      hideSelectOpdMessage();
      
      // Filter original data by OPD ID
      let filteredData = originalData.filter(function(iku) {
          return iku.opd_id == opdFilter;
      });
      
      // Filter by periode if selected
      if (periodeFilter !== 'all') {
          filteredData = filteredData.filter(function(iku) {
              const periodKey = iku.tahun_mulai + '-' + iku.tahun_akhir;
              return periodKey === periodeFilter;
          });
      }
      
      // Update table headers based on filtered data
      updateTableHeaders(filteredData);
      
      // Rebuild table
      rebuildTable(filteredData);
  }
  
  // Function to update table headers with years from data
  function updateTableHeaders(data) {
      const yearHeaderRow = document.getElementById('year-header-row');
      const yearHeaderSpan = document.getElementById('year-header-span');
      
      if (data.length === 0) {
          return;
      }
      
      // Get unique years from filtered data
      const years = new Set();
      data.forEach(function(iku) {
          if (iku.targets) {
              Object.keys(iku.targets).forEach(year => years.add(parseInt(year)));
          } else {
              // Default range if no targets
              for (let year = parseInt(iku.tahun_mulai); year <= parseInt(iku.tahun_akhir); year++) {
                  years.add(year);
              }
          }
      });
      
      const sortedYears = Array.from(years).sort();
      
      // Update colspan
      yearHeaderSpan.setAttribute('colspan', sortedYears.length);
      
      // Clear and rebuild year headers
      yearHeaderRow.innerHTML = '';
      sortedYears.forEach(function(year) {
          const th = document.createElement('th');
          th.className = 'border p-2 year-header';
          th.textContent = year;
          yearHeaderRow.appendChild(th);
      });
  }
  
  // Function to rebuild table with filtered data
  function rebuildTable(data) {
      const tbody = document.querySelector('#iku-table-body');
      const noDataMessage = document.getElementById('no-data-message');
      
      // Clear existing rows except messages
      const existingRows = tbody.querySelectorAll('tr.iku-row');
      existingRows.forEach(row => row.remove());
      
      if (data.length === 0) {
          noDataMessage.style.display = '';
          document.getElementById('no-data-text').textContent = 'Tidak ada data IKU OPD untuk filter yang dipilih';
          return;
      }
      
      noDataMessage.style.display = 'none';
      
      // Group by Sasaran IKU
      const groupedData = {};
      data.forEach(function(iku) {
          const sasaranKey = iku.sasaran || 'Tidak Ada Sasaran IKU';
          
          if (!groupedData[sasaranKey]) {
              groupedData[sasaranKey] = [];
          }
          
          groupedData[sasaranKey].push(iku);
      });
      
      // Build table rows
      Object.keys(groupedData).forEach(function(sasaranIku) {
          const indikators = groupedData[sasaranIku];
          let firstSasaranRow = true;
          
          indikators.forEach(function(indikator, index) {
              const row = document.createElement('tr');
              row.className = 'iku-row';
              
              let html = '';
              
              // Sasaran IKU - show only once per Sasaran group
              if (firstSasaranRow) {
                  html += `<td class="border p-2 text-start" rowspan="${indikators.length}">${escapeHtml(sasaranIku)}</td>`;
                  firstSasaranRow = false;
              }
              
              // Indikator Kinerja
              html += `<td class="border p-2 text-start">${escapeHtml(indikator.indikator_kinerja || '-')}</td>`;
              
              // Definisi Formulasi
              html += `<td class="border p-2 text-start">${escapeHtml(indikator.definisi_formulasi || '-')}</td>`;
              
              // Satuan
              html += `<td class="border p-2">${escapeHtml(indikator.satuan || '-')}</td>`;
              
              // Target per tahun
              const yearHeaders = document.querySelectorAll('#year-header-row th');
              yearHeaders.forEach(function(yearHeader) {
                  const year = yearHeader.textContent;
                  let target = '-';
                  
                  if (indikator.targets && indikator.targets[year]) {
                      target = indikator.targets[year];
                  }
                  
                  html += `<td class="border p-2">${escapeHtml(target)}</td>`;
              });
              
              // Program Pendukung
              html += `<td class="border p-2 text-start">${escapeHtml(indikator.program_pendukung || '-')}</td>`;
              
              row.innerHTML = html;
              tbody.appendChild(row);
          });
      });
  }
  
  // Helper functions
  function clearTableData() {
      const tbody = document.querySelector('#iku-table-body');
      const noDataMessage = document.getElementById('no-data-message');
      
      // Clear all existing data rows
      const existingRows = tbody.querySelectorAll('tr.iku-row');
      existingRows.forEach(row => row.remove());
      
      // Hide no data message
      if (noDataMessage) {
          noDataMessage.style.display = 'none';
      }
  }
  
  function showSelectOpdMessage() {
      const message = document.getElementById('select-opd-message');
      if (message) {
          message.style.display = '';
      }
  }
  
  function hideSelectOpdMessage() {
      const message = document.getElementById('select-opd-message');
      if (message) {
          message.style.display = 'none';
      }
  }
  
  // Helper function to escape HTML
  function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
  }
  
  // Functions called by dropdowns
  function filterOpd() {
      filterData();
  }
  
  function filterByPeriode() {
      filterData();
  }
  
  // Function to reset all filters
  function resetFilters() {
      document.getElementById('opdFilter').value = 'all';
      document.getElementById('periodeFilter').value = 'all';
      clearTableData();
      showSelectOpdMessage();
  }
</script>

<?= $this->include('user/templates/footer'); ?>
  </body>
</html>
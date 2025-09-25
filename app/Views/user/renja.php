<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RENJA</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  
  <?= $this->include('user/templates/header'); ?>

  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA KINERJA
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

      <div class="table-responsive">
        <table class="table table-bordered align-middle tect-center">
          <thead class="table-success">
            <tr>
                <th class="border p-2">NO</th>
                <th class="border p-2">SASARAN RENSTRA</th>
                <th class="border p-2">SASARAN RENJA</th>
                <th class="border p-2">INDIKATOR SASARAN</th>
                <th class="border p-2">SATUAN</th>
                <th class="border p-2">TAHUN</th>
                <th class="border p-2">TARGET</th>
            </tr>
          </thead>
            <tbody>
              <?php if (empty($renja_data)): ?>
                  <tr id="no-data-message">
                      <td colspan="9" class="border p-4 text-center text-muted">
                          <i class="fas fa-info-circle me-2"></i>
                              Tidak ada data RENJA.
                      </td>
                  </tr>
              <?php else: ?>
              <?php 
                  // Group data by renstra_sasaran first
                  $renstraGroups = [];
                  foreach ($renja_data as $renja) {
                      $renstraSasaran = $renja['renstra_sasaran'];
                      if (!isset($renstraGroups[$renstraSasaran])) {
                          $renstraGroups[$renstraSasaran] = [
                              'renstra_sasaran' => $renstraSasaran,
                              'renja_list' => []
                          ];
                      }
                      $renstraGroups[$renstraSasaran]['renja_list'][] = $renja;
                  }
                  
                  $globalNo = 1;
              ?>
              <?php foreach ($renstraGroups as $renstraGroup): ?>
                  <?php 
                      // Calculate total rows for this RENSTRA group
                      $totalRowsForRenstra = 0;
                      foreach ($renstraGroup['renja_list'] as $renja) {
                          $totalRowsForRenstra += count($renja['indikator']);
                      }
                      $isFirstRowOfRenstra = true;
                  ?>
                  <?php foreach ($renstraGroup['renja_list'] as $renja): ?>
                      <?php 
                          $indikatorList = $renja['indikator']; 
                          $rowspanRenja = count($indikatorList);
                          // Skip if no indicators
                          if ($rowspanRenja == 0) continue;
                      ?>
                      <?php foreach ($indikatorList as $i => $indikator): ?>
                          <tr class="renja-row" data-opd="<?= $renja['opd_id'] ?? '' ?>" data-year="<?= $indikator['tahun'] ?>" data-renja-group="<?= $renja['id'] ?>">
                              <!-- Nomor - show only once per RENSTRA group -->
                              <?php if ($isFirstRowOfRenstra): ?>
                                  <td class="border p-2" rowspan="<?= $totalRowsForRenstra ?>"><?= $globalNo++ ?></td>
                              <?php endif; ?>

                              <!-- Sasaran RENSTRA - show only once per RENSTRA group -->
                              <?php if ($isFirstRowOfRenstra): ?>
                                  <td class="border p-2 text-start" rowspan="<?= $totalRowsForRenstra ?>">
                                      <?= esc($renstraGroup['renstra_sasaran']) ?>
                                  </td>
                                  <?php $isFirstRowOfRenstra = false; ?>
                              <?php endif; ?>

                              <!-- Sasaran RENJA - show only once per RENJA -->
                              <?php if ($i === 0): ?>
                                  <td class="border p-2 text-start" rowspan="<?= $rowspanRenja ?>">
                                      <?= esc($renja['sasaran_renja']) ?>
                                  </td>
                              <?php endif; ?>

                              <!-- Indikator -->
                              <td class="border p-2 text-start"><?= esc($indikator['indikator_sasaran']) ?></td>
                              <td class="border p-2"><?= esc($indikator['satuan']) ?></td>
                              <td class="border p-2"><?= esc($indikator['tahun']) ?></td>
                              <td class="border p-2"><?= esc($indikator['target']) ?></td>
                          </tr>
                      <?php endforeach; ?>
                  <?php endforeach; ?>
              <?php endforeach; ?>
              <?php endif; ?>
              
              <!-- No data message (hidden by default, shown by JavaScript when no filtered results) -->
              <tr id="no-data-message" style="display: none;">
                  <td colspan="9" class="border p-4 text-center text-muted">
                      <i class="fas fa-search mb-2 d-block" style="font-size: 2rem;"></i>
                      <span id="no-data-text">Tidak ada data yang sesuai dengan filter</span>
                  </td>
              </tr>
            </tbody>
        </table>
      </div>
    </main>

<?= $this->include('user/templates/footer'); ?>
<script>
  // Store original data for filtering
  const originalData = <?= json_encode($renja_data ?? []) ?>;
  
  // Initially hide all rows and show "select OPD" message
  document.addEventListener('DOMContentLoaded', function() {
      hideAllRows();
      showSelectOpdMessage();
  });

  // Function to filter data
  function filterData() {
      const opdFilter = document.getElementById('opdFilter').value;
      const yearFilter = document.getElementById('yearFilter').value;
      
      // User must select OPD first
      if (opdFilter === 'all') {
          hideAllRows();
          showSelectOpdMessage();
          return;
      }
      
      // Hide select OPD message
      hideSelectOpdMessage();
      
      // Filter original data
      const filteredData = originalData.filter(function(renja) {
          const opdMatch = renja.opd_id == opdFilter;
          
          // Check if any indicator matches the year filter
          const yearMatch = yearFilter === 'all' || 
              (renja.indikator && renja.indikator.some(function(indikator) {
                  return indikator.tahun === yearFilter;
              }));
          
          return opdMatch && yearMatch;
      });
      
      // If year filter is applied, filter indicators within each RENJA
      const processedData = filteredData.map(function(renja) {
          if (yearFilter === 'all') {
              return renja;
          } else {
              return {
                  ...renja,
                  indikator: renja.indikator.filter(function(indikator) {
                      return indikator.tahun === yearFilter;
                  })
              };
          }
      });
      
      // Rebuild table
      rebuildTable(processedData);
  }
  
  // Helper functions
  function hideAllRows() {
      const rows = document.querySelectorAll('.renja-row');
      rows.forEach(row => row.style.display = 'none');
  }
  
  function showSelectOpdMessage() {
      let message = document.getElementById('select-opd-message');
      if (!message) {
          // Create the message if it doesn't exist
          const tbody = document.querySelector('tbody');
          message = document.createElement('tr');
          message.id = 'select-opd-message';
          message.innerHTML = `
              <td colspan="7" class="border p-4 text-center text-muted">
                  <i class="fas fa-building me-2" style="font-size: 2rem;"></i>
                  <div class="mt-2">
                      <strong>Silakan pilih Unit Kerja terlebih dahulu</strong><br>
                      <small>Pilih Unit Kerja dari dropdown di atas untuk melihat data RENJA</small>
                  </div>
              </td>
          `;
          tbody.appendChild(message);
      }
      message.style.display = '';
  }
  
  function hideSelectOpdMessage() {
      const message = document.getElementById('select-opd-message');
      if (message) {
          message.style.display = 'none';
      }
  }
  
  // Function to rebuild table with filtered data
  function rebuildTable(data) {
      const tbody = document.querySelector('tbody');
      const noDataMessage = document.getElementById('no-data-message');
      
      // Clear existing rows except messages
      const existingRows = tbody.querySelectorAll('tr.renja-row');
      existingRows.forEach(row => row.remove());
      
      if (data.length === 0) {
          noDataMessage.style.display = '';
          document.getElementById('no-data-text').textContent = 'Tidak ada data RENJA untuk filter yang dipilih';
          return;
      }
      
      noDataMessage.style.display = 'none';
      
      // Group data by renstra_sasaran
      const renstraGroups = {};
      data.forEach(function(renja) {
          const renstraSasaran = renja.renstra_sasaran;
          if (!renstraGroups[renstraSasaran]) {
              renstraGroups[renstraSasaran] = {
                  renstra_sasaran: renstraSasaran,
                  renja_list: []
              };
          }
          renstraGroups[renstraSasaran].renja_list.push(renja);
      });
      
      let globalNo = 1;
      
      Object.values(renstraGroups).forEach(function(renstraGroup) {
          // Calculate total rows for this RENSTRA group
          let totalRowsForRenstra = 0;
          renstraGroup.renja_list.forEach(function(renja) {
              totalRowsForRenstra += renja.indikator.length;
          });
          
          let isFirstRowOfRenstra = true;
          
          renstraGroup.renja_list.forEach(function(renja) {
              const indikatorList = renja.indikator;
              const rowspanRenja = indikatorList.length;
              
              if (rowspanRenja === 0) return;
              
              indikatorList.forEach(function(indikator, i) {
                  const row = document.createElement('tr');
                  row.className = 'renja-row';
                  row.setAttribute('data-year', indikator.tahun);
                  row.setAttribute('data-renja-group', renja.id);
                  
                  let html = '';
                  
                  // Nomor - show only once per RENSTRA group
                  if (isFirstRowOfRenstra) {
                      html += `<td class="border p-2" rowspan="${totalRowsForRenstra}">${globalNo++}</td>`;
                  }
                  
                  // Sasaran RENSTRA - show only once per RENSTRA group
                  if (isFirstRowOfRenstra) {
                      html += `<td class="border p-2 text-start" rowspan="${totalRowsForRenstra}">${escapeHtml(renstraGroup.renstra_sasaran)}</td>`;
                      isFirstRowOfRenstra = false;
                  }
                  
                  // Sasaran RENJA - show only once per RENJA
                  if (i === 0) {
                      html += `<td class="border p-2 text-start" rowspan="${rowspanRenja}">${escapeHtml(renja.sasaran_renja)}</td>`;
                  }
                  
                  // Indikator data
                  html += `
                      <td class="border p-2 text-start">${escapeHtml(indikator.indikator_sasaran)}</td>
                      <td class="border p-2">${escapeHtml(indikator.satuan)}</td>
                      <td class="border p-2">${escapeHtml(indikator.tahun)}</td>
                      <td class="border p-2">${escapeHtml(indikator.target)}</td>`;
     
                  row.innerHTML = html;
                  tbody.appendChild(row);
              });
          });
      });
  }
  
  // Helper function to escape HTML
  function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
  }
  </script>
</body>
</html>
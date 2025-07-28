<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RENJA - e-SAKIP</title>
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
        <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA KERJA TAHUNAN</h2>

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
                <select id="renstraSasaranFilter" class="form-select w-50" onchange="filterData()">
                    <option value="all">SEMUA SASARAN RENSTRA</option>
                    <!-- Options will be populated by JavaScript -->
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
                <select id="statusFilter" class="form-select w-25" onchange="filterData()">
                    <option value="all">SEMUA STATUS</option>
                    <option value="draft">Draft</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            <div>
                <a href="<?= base_url('adminopd/renja/tambah') ?>" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-1"></i> TAMBAH
                </a>
            </div>
        </div>

    <!-- Data Summary -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <span id="visible-data-count">Memuat data...</span>
                </small>
                <small class="text-muted">
                    Filter aktif: <span id="active-filters">Semua Sasaran, Semua Tahun, Semua Status</span>
                </small>
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
            <tr>
                <th class="border p-2">NO</th>
                <th class="border p-2">SASARAN RENSTRA</th>
                <th class="border p-2">SASARAN RENJA</th>
                <th class="border p-2">INDIKATOR SASARAN</th>
                <th class="border p-2">SATUAN</th>
                <th class="border p-2">TAHUN</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">STATUS</th>
                <th class="border p-2">ACTION</th>
            </tr>   
            </thead>
            <tbody>
            <?php if (empty($renja_data)): ?>
                <tr id="no-data-message">
                    <td colspan="9" class="border p-4 text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                             Tidak ada data RENJA. <a href="<?= base_url('adminopd/renja/tambah') ?>" class="text-success">Tambah data pertama</a>
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
                        <tr class="renja-row" data-status="<?= $renja['status'] ?? 'draft' ?>" data-year="<?= $indikator['tahun'] ?>" data-renja-group="<?= $renja['id'] ?>">
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

                            <!-- Status - show only once per RENJA -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2" rowspan="<?= $rowspanRenja ?>">
                                    <?php 
                                        $status = $renja['status'] ?? 'draft';
                                        $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning';
                                    ?>
                                    <button 
                                        class="badge <?= $badgeClass ?> border-0" 
                                        onclick="toggleStatus(<?= $renja['id'] ?>, '<?= base_url() ?>', '<?= csrf_header() ?>', '<?= csrf_hash() ?>')" 
                                        style="cursor: pointer;" 
                                        title="Klik untuk mengubah status">
                                        <?= ucfirst($status) ?>
                                    </button>
                                </td>
                            <?php endif; ?>

                            <!-- Action - show only once per RENJA -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 align-middle text-center" rowspan="<?= $rowspanRenja ?>">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <a href="<?= base_url('adminopd/renja/edit/' . $renja['id']) ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $renja['id'] ?>)">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
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
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>

  <script>
  // Store original data for filtering
  const originalData = <?= json_encode($renja_data ?? []) ?>;
  
  // Function to toggle status via AJAX
  function toggleStatus(renjaId) {
      if (confirm('Apakah Anda yakin ingin mengubah status RENJA ini?')) {
          fetch('<?= base_url('adminopd/renja/update-status') ?>', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                  '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
              },
              body: JSON.stringify({
                  id: renjaId
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

  // Function to filter and rebuild table
  function filterData() {
      const renstraSasaranFilter = document.getElementById('renstraSasaranFilter').value;
      const yearFilter = document.getElementById('yearFilter').value;
      const statusFilter = document.getElementById('statusFilter').value;
      
      // Filter original data
      const filteredData = originalData.filter(function(renja) {
          const statusMatch = statusFilter === 'all' || renja.status === statusFilter;
          
          const renstraSasaranMatch = renstraSasaranFilter === 'all' || 
              renja.renstra_sasaran === renstraSasaranFilter;
          
          // Check if any indicator matches the year filter
          const yearMatch = yearFilter === 'all' || 
              renja.indikator.some(function(indikator) {
                  return indikator.tahun === yearFilter;
              });
          
          return statusMatch && renstraSasaranMatch && yearMatch;
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
      
      // Update summary
      const totalIndicators = processedData.reduce((sum, renja) => sum + renja.indikator.length, 0);
      const originalTotal = originalData.reduce((sum, renja) => sum + renja.indikator.length, 0);
      updateDataSummary(totalIndicators, originalTotal, renstraSasaranFilter, yearFilter, statusFilter);
  }
  
  // Function to rebuild table with filtered data
  function rebuildTable(data) {
      const tbody = document.querySelector('tbody');
      const noDataMessage = document.getElementById('no-data-message');
      
      // Clear existing rows except no-data message
      const existingRows = tbody.querySelectorAll('tr:not(#no-data-message)');
      existingRows.forEach(row => row.remove());
      
      if (data.length === 0) {
          noDataMessage.style.display = '';
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
                  row.setAttribute('data-status', renja.status || 'draft');
                  row.setAttribute('data-year', indikator.tahun);
                  row.setAttribute('data-renja-group', renja.id);
                  
                  let html = '';
                  
                  // Nomor - show only once per RENSTRA group
                  if (isFirstRowOfRenstra) {
                      html += `<td class="border p-2" rowspan="${totalRowsForRenstra}">${globalNo++}</td>`;
                  }
                  
                  // Status - show only once per RENJA
                  if (i === 0) {
                      const status = renja.status || 'draft';
                      const badgeClass = status === 'selesai' ? 'bg-success' : 'bg-warning';
                      html += `
                          <td class="border p-2" rowspan="${rowspanRenja}">
                              <button class="badge ${badgeClass} border-0" 
                                      onclick="toggleStatus(${renja.id})" 
                                      style="cursor: pointer;" 
                                      title="Klik untuk mengubah status">
                                  ${status.charAt(0).toUpperCase() + status.slice(1)}
                              </button>
                          </td>`;
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
                  
                  // Action - show only once per RENJA
                  if (i === 0) {
                      html += `
                          <td class="border p-2 align-middle text-center" rowspan="${rowspanRenja}">
                              <div class="d-flex flex-column align-items-center gap-2">
                                  <a href="<?= base_url('adminopd/renja/edit/') ?>${renja.id}" class="btn btn-success btn-sm">
                                      <i class="fas fa-edit me-1"></i>Edit
                                  </a>
                                  <button class="btn btn-danger btn-sm" onclick="confirmDelete(${renja.id})">
                                      <i class="fas fa-trash me-1"></i>Hapus
                                  </button>
                              </div>
                          </td>`;
                  }
                  
                  row.innerHTML = html;
                  tbody.insertBefore(row, noDataMessage);
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

  // Function to populate renstra sasaran filter options
  function populateRenstraSasaranFilter() {
      const renstraSasaranFilter = document.getElementById('renstraSasaranFilter');
      const uniqueSasaran = [...new Set(originalData.map(renja => renja.renstra_sasaran))];
      
      // Clear existing options except the first one (All)
      while (renstraSasaranFilter.children.length > 1) {
          renstraSasaranFilter.removeChild(renstraSasaranFilter.lastChild);
      }
      
      // Add unique sasaran renstra options
      uniqueSasaran.forEach(function(sasaran) {
          const option = document.createElement('option');
          option.value = sasaran;
          option.textContent = sasaran.length > 80 ? sasaran.substring(0, 80) + '...' : sasaran;
          option.title = sasaran; // Full text in tooltip
          renstraSasaranFilter.appendChild(option);
      });
  }

  // Function to update data summary
  function updateDataSummary(visibleCount, totalCount, renstraSasaranFilter, yearFilter, statusFilter) {
      const countElement = document.getElementById('visible-data-count');
      if (countElement) {
          countElement.textContent = `Menampilkan ${visibleCount} dari ${totalCount} data`;
      }
      
      const filtersElement = document.getElementById('active-filters');
      if (filtersElement) {
          let filterText = '';
          
          if (renstraSasaranFilter !== 'all') {
              filterText += `Sasaran: ${renstraSasaranFilter.length > 50 ? renstraSasaranFilter.substring(0, 50) + '...' : renstraSasaranFilter}`;
          } else {
              filterText += 'Semua Sasaran';
          }
          
          if (yearFilter !== 'all') {
              filterText += `, Tahun ${yearFilter}`;
          } else {
              filterText += ', Semua Tahun';
          }
          
          if (statusFilter !== 'all') {
              filterText += `, Status ${statusFilter.charAt(0).toUpperCase() + statusFilter.slice(1)}`;
          } else {
              filterText += ', Semua Status';
          }
          
          filtersElement.textContent = filterText;
      }
  }

  // Function to confirm delete
  function confirmDelete(id) {
      if (confirm('Apakah Anda yakin ingin menghapus data RENJA ini?')) {
          window.location.href = '<?= base_url('adminopd/renja/delete/') ?>' + id;
      }
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
      // Populate renstra sasaran filter options
      populateRenstraSasaranFilter();
      
      // Initialize data summary
      const totalRows = originalData.reduce((sum, renja) => sum + renja.indikator.length, 0);
      const countElement = document.getElementById('visible-data-count');
      if (countElement) {
          countElement.textContent = `Menampilkan ${totalRows} dari ${totalRows} data`;
      }
      
      const filtersElement = document.getElementById('active-filters');
      if (filtersElement) {
          filtersElement.textContent = 'Semua Sasaran, Semua Tahun, Semua Status';
      }
  });
  </script>
</body>
</html>

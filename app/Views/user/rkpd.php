<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RKPD - e-SAKIP</title>
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
        <h2 class="h3 fw-bold text-success text-center mb-4">Rencana Kerja Pemerintah Daerah</h2>

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
                <select id="rpjmdSasaranFilter" class="form-select w-50" onchange="filterData()">
                    <option value="all">SEMUA SASARAN RPJMD</option>
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
                <a href="<?= base_url('adminkab/rkpd/tambah') ?>" class="btn btn-success d-flex align-items-center">
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
                <th class="border p-2">SASARAN RPJMD</th>
                <th class="border p-2">SASARAN RKPD</th>
                <th class="border p-2">INDIKATOR SASARAN</th>
                <th class="border p-2">SATUAN</th>
                <th class="border p-2">TAHUN</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">STATUS</th>
                <th class="border p-2">ACTION</th>
            </tr>   
            </thead>
            <tbody>
            <?php if (empty($rkpd_data)): ?>
                <tr id="no-data-message">
                    <td colspan="9" class="border p-4 text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                             Tidak ada data RKPD. <a href="<?= base_url('adminkab/rkpd/tambah') ?>" class="text-success">Tambah data pertama</a>
                    </td>
                </tr>
            <?php else: ?>
            <?php 
                // Group data by rpjmd_sasaran first
                $rpjmdGroups = [];
                foreach ($rkpd_data as $rkpd) {
                    $rpjmdSasaranId = $rkpd['rpjmd_sasaran_id'];
                    if (!isset($rpjmdGroups[$rpjmdSasaranId])) {
                        $rpjmdGroups[$rpjmdSasaranId] = [
                            'rpjmd_sasaran' => $rkpd['rpjmd_sasaran'],
                            'rkpd_list' => []
                        ];
                    }
                    $rpjmdGroups[$rpjmdSasaranId]['rkpd_list'][] = $rkpd;
                }
                
                $globalNo = 1;
            ?>
            <?php foreach ($rpjmdGroups as $rpjmdGroup): ?>
                <?php 
                    // Calculate total rows for this RPJMD group
                    $totalRowsForRpjmd = 0;
                    foreach ($rpjmdGroup['rkpd_list'] as $rkpd) {
                        $totalRowsForRpjmd += count($rkpd['indikator']);
                    }
                    $isFirstRowOfRpjmd = true;
                ?>
                <?php foreach ($rpjmdGroup['rkpd_list'] as $rkpd): ?>
                    <?php 
                        $indikatorList = $rkpd['indikator']; 
                        $rowspanRkpd = count($indikatorList);
                        // Skip if no indicators
                        if ($rowspanRkpd == 0) continue;
                    ?>
                    <?php foreach ($indikatorList as $i => $indikator): ?>
                        <tr class="rkpd-row" data-status="<?= $rkpd['status'] ?? 'draft' ?>" data-year="<?= $indikator['tahun'] ?>" data-rkpd-group="<?= $rkpd['id'] ?>">
                            <!-- Nomor - show only once per RPJMD group -->
                            <?php if ($isFirstRowOfRpjmd): ?>
                                <td class="border p-2" rowspan="<?= $totalRowsForRpjmd ?>"><?= $globalNo++ ?></td>
                            <?php endif; ?>
                            
                            <!-- Sasaran RPJMD - show only once per RPJMD group -->
                            <?php if ($isFirstRowOfRpjmd): ?>
                                <td class="border p-2 text-start" rowspan="<?= $totalRowsForRpjmd ?>">
                                    <?= esc($rpjmdGroup['rpjmd_sasaran']) ?>
                                </td>
                                <?php $isFirstRowOfRpjmd = false; ?>
                            <?php endif; ?>

                            <!-- Sasaran RKPD - show only once per RKPD -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 text-start" rowspan="<?= $rowspanRkpd ?>">
                                    <?= esc($rkpd['sasaran']) ?>
                                </td>
                            <?php endif; ?>

                            <!-- Indikator -->
                            <td class="border p-2 text-start"><?= esc($indikator['indikator_sasaran']) ?></td>
                            <td class="border p-2"><?= esc($indikator['satuan']) ?></td>
                            <td class="border p-2"><?= esc($indikator['tahun']) ?></td>
                            <td class="border p-2"><?= esc($indikator['target']) ?></td>

                            <!-- Status - show only once per RKPD -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2" rowspan="<?= $rowspanRkpd ?>">
                                    <?php 
                                        $status = $rkpd['status'] ?? 'draft';
                                        $badgeClass = $status === 'selesai' ? 'bg-success' : 'bg-warning';
                                    ?>
                                    <button 
                                        class="badge <?= $badgeClass ?> border-0" 
                                        onclick="toggleStatus(<?= $rkpd['id'] ?>, '<?= base_url() ?>', '<?= csrf_header() ?>', '<?= csrf_hash() ?>')" 
                                        style="cursor: pointer;" 
                                        title="Klik untuk mengubah status">
                                        <?= ucfirst($status) ?>
                                    </button>
                                </td>
                            <?php endif; ?>

                            <!-- Action - show only once per RKPD -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 align-middle text-center" rowspan="<?= $rowspanRkpd ?>">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <a href="<?= base_url('adminkab/rkpd/edit/' . $rkpd['id']) ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $rkpd['id'] ?>)">
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
        
        <!-- Data Summary -->
        <div class="d-flex justify-content-between align-items-center mt-3 text-muted small">
            <div>
                <span id="visible-data-count">Memuat data...</span>
            </div>
            <div>
                <span id="active-filters">Semua Data</span>
            </div>
        </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div> <!-- End of Content Wrapper -->
    
  <script>
  // Store original data for filtering
  const originalData = <?= json_encode($rkpd_data ?? []) ?>;
  
  // Function to toggle status via AJAX
  function toggleStatus(rkpdId) {
      if (confirm('Apakah Anda yakin ingin mengubah status RKPD ini?')) {
          fetch('<?= base_url('adminkab/rkpd/update-status') ?>', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                  '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
              },
              body: JSON.stringify({
                  id: rkpdId
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
      const rpjmdSasaranFilter = document.getElementById('rpjmdSasaranFilter').value;
      const yearFilter = document.getElementById('yearFilter').value;
      const statusFilter = document.getElementById('statusFilter').value;
      
      // Filter original data
      const filteredData = originalData.filter(function(rkpd) {
          const statusMatch = statusFilter === 'all' || rkpd.status === statusFilter;
          
          const rpjmdSasaranMatch = rpjmdSasaranFilter === 'all' || 
              rkpd.rpjmd_sasaran === rpjmdSasaranFilter;
          
          // Check if any indicator matches the year filter
          const yearMatch = yearFilter === 'all' || 
              rkpd.indikator.some(function(indikator) {
                  return indikator.tahun === yearFilter;
              });
          
          return statusMatch && rpjmdSasaranMatch && yearMatch;
      });
      
      // If year filter is applied, filter indicators within each RKPD
      const processedData = filteredData.map(function(rkpd) {
          if (yearFilter === 'all') {
              return rkpd;
          } else {
              return {
                  ...rkpd,
                  indikator: rkpd.indikator.filter(function(indikator) {
                      return indikator.tahun === yearFilter;
                  })
              };
          }
      });
      
      // Rebuild table
      rebuildTable(processedData);
      
      // Update summary
      const totalIndicators = processedData.reduce((sum, rkpd) => sum + rkpd.indikator.length, 0);
      const originalTotal = originalData.reduce((sum, rkpd) => sum + rkpd.indikator.length, 0);
      updateDataSummary(totalIndicators, originalTotal, rpjmdSasaranFilter, yearFilter, statusFilter);
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
      
      // Group data by rpjmd_sasaran_id
      const rpjmdGroups = {};
      data.forEach(function(rkpd) {
          const rpjmdSasaranId = rkpd.rpjmd_sasaran_id;
          if (!rpjmdGroups[rpjmdSasaranId]) {
              rpjmdGroups[rpjmdSasaranId] = {
                  rpjmd_sasaran: rkpd.rpjmd_sasaran,
                  rkpd_list: []
              };
          }
          rpjmdGroups[rpjmdSasaranId].rkpd_list.push(rkpd);
      });
      
      let globalNo = 1;
      
      Object.values(rpjmdGroups).forEach(function(rpjmdGroup) {
          // Calculate total rows for this RPJMD group
          let totalRowsForRpjmd = 0;
          rpjmdGroup.rkpd_list.forEach(function(rkpd) {
              totalRowsForRpjmd += rkpd.indikator.length;
          });
          
          let isFirstRowOfRpjmd = true;
          
          rpjmdGroup.rkpd_list.forEach(function(rkpd) {
              const indikatorList = rkpd.indikator;
              const rowspanRkpd = indikatorList.length;
              
              if (rowspanRkpd === 0) return;
              
              indikatorList.forEach(function(indikator, i) {
                  const row = document.createElement('tr');
                  row.className = 'rkpd-row';
                  row.setAttribute('data-status', rkpd.status || 'draft');
                  row.setAttribute('data-year', indikator.tahun);
                  row.setAttribute('data-rkpd-group', rkpd.id);
                  
                  let html = '';
                  
                  // Nomor - show only once per RPJMD group
                  if (isFirstRowOfRpjmd) {
                      html += `<td class="border p-2" rowspan="${totalRowsForRpjmd}">${globalNo++}</td>`;
                  }
                  
                  // Sasaran RPJMD - show only once per RPJMD group
                  if (isFirstRowOfRpjmd) {
                      html += `<td class="border p-2 text-start" rowspan="${totalRowsForRpjmd}">${escapeHtml(rpjmdGroup.rpjmd_sasaran)}</td>`;
                      isFirstRowOfRpjmd = false;
                  }
                  
                  // Status - show only once per RKPD
                  if (i === 0) {
                      const status = rkpd.status || 'draft';
                      const badgeClass = status === 'selesai' ? 'bg-success' : 'bg-warning';
                      html += `
                          <td class="border p-2" rowspan="${rowspanRkpd}">
                              <button class="badge ${badgeClass} border-0" 
                                      onclick="toggleStatus(${rkpd.id})" 
                                      style="cursor: pointer;" 
                                      title="Klik untuk mengubah status">
                                  ${status.charAt(0).toUpperCase() + status.slice(1)}
                              </button>
                          </td>`;
                  }
                  
                  // Sasaran RKPD - show only once per RKPD
                  if (i === 0) {
                      html += `<td class="border p-2 text-start" rowspan="${rowspanRkpd}">${escapeHtml(rkpd.sasaran)}</td>`;
                  }
                  
                  // Indikator data
                  html += `
                      <td class="border p-2 text-start">${escapeHtml(indikator.indikator_sasaran)}</td>
                      <td class="border p-2">${escapeHtml(indikator.satuan)}</td>
                      <td class="border p-2">${escapeHtml(indikator.tahun)}</td>
                      <td class="border p-2">${escapeHtml(indikator.target)}</td>`;
                  
                  // Action - show only once per RKPD
                  if (i === 0) {
                      html += `
                          <td class="border p-2 align-middle text-center" rowspan="${rowspanRkpd}">
                              <div class="d-flex flex-column align-items-center gap-2">
                                  <a href="<?= base_url('adminkab/rkpd/edit/') ?>${rkpd.id}" class="btn btn-success btn-sm">
                                      <i class="fas fa-edit me-1"></i>Edit
                                  </a>
                                  <button class="btn btn-danger btn-sm" onclick="confirmDelete(${rkpd.id})">
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

  // Function to populate rpjmd sasaran filter options
  function populateRpjmdSasaranFilter() {
      const rpjmdSasaranFilter = document.getElementById('rpjmdSasaranFilter');
      const uniqueSasaran = [...new Set(originalData.map(rkpd => rkpd.rpjmd_sasaran))];
      
      // Clear existing options except the first one (All)
      while (rpjmdSasaranFilter.children.length > 1) {
          rpjmdSasaranFilter.removeChild(rpjmdSasaranFilter.lastChild);
      }
      
      // Add unique sasaran rpjmd options
      uniqueSasaran.forEach(function(sasaran) {
          const option = document.createElement('option');
          option.value = sasaran;
          option.textContent = sasaran.length > 80 ? sasaran.substring(0, 80) + '...' : sasaran;
          option.title = sasaran; // Full text in tooltip
          rpjmdSasaranFilter.appendChild(option);
      });
  }

  // Function to update data summary
  function updateDataSummary(visibleCount, totalCount, rpjmdSasaranFilter, yearFilter, statusFilter) {
      const countElement = document.getElementById('visible-data-count');
      if (countElement) {
          countElement.textContent = `Menampilkan ${visibleCount} dari ${totalCount} data`;
      }
      
      const filtersElement = document.getElementById('active-filters');
      if (filtersElement) {
          let filterText = '';
          
          if (rpjmdSasaranFilter !== 'all') {
              filterText += `Sasaran: ${rpjmdSasaranFilter.length > 50 ? rpjmdSasaranFilter.substring(0, 50) + '...' : rpjmdSasaranFilter}`;
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
      if (confirm('Apakah Anda yakin ingin menghapus data RKPD ini?')) {
          window.location.href = '<?= base_url('adminkab/rkpd/delete/') ?>' + id;
      }
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
      // Populate rpjmd sasaran filter options
      populateRpjmdSasaranFilter();
      
      // Initialize data summary
      const totalRows = originalData.reduce((sum, rkpd) => sum + rkpd.indikator.length, 0);
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

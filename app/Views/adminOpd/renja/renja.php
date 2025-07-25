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
                <select id="yearFilter" class="form-select" onchange="filterData()" style="flex: 1;">
                    <option value="all">SEMUA TAHUN</option>
                    <?php if (isset($available_years)): ?>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= $year ?>">
                                <?= $year ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select id="statusFilter" class="form-select" onchange="filterData()" style="flex: 1;">
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

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
            <tr>
                <th class="border p-2">NO</th>
                <th class="border p-2">STATUS</th>
                <th class="border p-2">SASARAN RENSTRA</th>
                <th class="border p-2">SASARAN RENJA</th>
                <th class="border p-2">INDIKATOR SASARAN</th>
                <th class="border p-2">SATUAN</th>
                <th class="border p-2">TAHUN</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">ACTION</th>
            </tr>   
            </thead>
            <tbody>
            <?php if (empty($renja_data)): ?>
                <tr>
                    <td colspan="9" class="border p-4 text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                             Tidak ada data RENJA. <a href="<?= base_url('adminopd/renja/tambah') ?>" class="text-success">Tambah data pertama</a>
                    </td>
                </tr>
            <?php else: ?>
            <?php $no = 1; ?>
            <?php foreach ($renja_data as $renja): ?>
                <?php 
                    $indikatorList = $renja['indikator']; 
                    $rowspan = count($indikatorList);
                    // Skip if no indicators (shouldn't happen after filter, but safety check)
                    if ($rowspan == 0) continue;
                ?>
                <?php foreach ($indikatorList as $i => $indikator): ?>
                    <tr class="renja-row" data-status="<?= $renja['status'] ?? 'draft' ?>" data-year="<?= $indikator['tahun'] ?>" data-renja-group="<?= $renja['id'] ?>">
                        <?php if ($i === 0): ?>
                            <td class="border p-2" rowspan="<?= $rowspan ?>"><?= $no++ ?></td>

                            <!-- Status -->
                            <td class="border p-2" rowspan="<?= $rowspan ?>">
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

                            <!-- Sasaran RENSTRA -->
                            <td class="border p-2 text-start" rowspan="<?= $rowspan ?>">
                                <?= esc($renja['renstra_sasaran']) ?>
                            </td>

                            <!-- Sasaran RENJA -->
                            <td class="border p-2 text-start" rowspan="<?= $rowspan ?>">
                                <?= esc($renja['sasaran_renja']) ?>
                            </td>
                        <?php endif; ?>

                        <!-- Indikator -->
                        <td class="border p-2 text-start"><?= esc($indikator['indikator_sasaran']) ?></td>
                        <td class="border p-2"><?= esc($indikator['satuan']) ?></td>
                        <td class="border p-2"><?= esc($indikator['tahun']) ?></td>
                        <td class="border p-2"><?= esc($indikator['target']) ?></td>

                        <?php if ($i === 0): ?>
                            <td class="border p-2 align-middle text-center" rowspan="<?= $rowspan ?>">
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

  <?= $this->include('adminOpd/templates/footer.php'); ?>

  <script>
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

  // Function to filter data using JavaScript (client-side) - RENSTRA approach with groups
  function filterData() {
      const yearFilter = document.getElementById('yearFilter').value;
      const statusFilter = document.getElementById('statusFilter').value;
      
      const rows = document.querySelectorAll('.renja-row');
      const noDataMessage = document.getElementById('no-data-message');
      let visibleCount = 0;
      
      // Hide all rows first
      rows.forEach(function(row) {
          row.style.display = 'none';
      });
      
      // Group rows by RENJA group and check filters
      const renjaGroups = {};
      rows.forEach(function(row) {
          const renjaGroupId = row.getAttribute('data-renja-group');
          if (!renjaGroups[renjaGroupId]) {
              renjaGroups[renjaGroupId] = [];
          }
          renjaGroups[renjaGroupId].push(row);
      });
      
      // Process each RENJA group
      Object.keys(renjaGroups).forEach(function(renjaGroupId) {
          const groupRows = renjaGroups[renjaGroupId];
          const firstRow = groupRows[0];
          const renjaStatus = firstRow.getAttribute('data-status');
          
          // Check if status matches
          const statusMatch = statusFilter === 'all' || renjaStatus === statusFilter;
          
          // Check if any row in this group has matching year
          let yearMatch = yearFilter === 'all';
          if (!yearMatch) {
              yearMatch = groupRows.some(function(row) {
                  return row.getAttribute('data-year') === yearFilter;
              });
          }
          
          // Show entire group if criteria match
          if (statusMatch && yearMatch) {
              groupRows.forEach(function(row) {
                  row.style.display = '';
                  visibleCount++;
              });
          }
      });
      
      // Show/hide no data message
      if (visibleCount === 0 && rows.length > 0) {
          noDataMessage.style.display = '';
      } else {
          noDataMessage.style.display = 'none';
      }
      
      // Update data summary
      updateDataSummary(visibleCount, rows.length, yearFilter, statusFilter);
  }

  // Function to update data summary
  function updateDataSummary(visibleCount, totalCount, yearFilter, statusFilter) {
      const countElement = document.getElementById('visible-data-count');
      if (countElement) {
          countElement.textContent = `Menampilkan ${visibleCount} dari ${totalCount} data`;
      }
      
      const filtersElement = document.getElementById('active-filters');
      if (filtersElement) {
          let filterText = '';
          
          if (yearFilter !== 'all') {
              filterText += `Tahun ${yearFilter}`;
          } else {
              filterText += 'Semua Tahun';
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
      // Initialize data summary
      const totalRows = document.querySelectorAll('.renja-row').length;
      const countElement = document.getElementById('visible-data-count');
      if (countElement) {
          countElement.textContent = `Menampilkan ${totalRows} dari ${totalRows} data`;
      }
      
      const filtersElement = document.getElementById('active-filters');
      if (filtersElement) {
          filtersElement.textContent = 'Semua Tahun, Semua Status';
      }
  });
  </script>
</body>
</html>

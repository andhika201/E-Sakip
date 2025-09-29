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

<!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

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
                <!-- Tahun -->
                <select id="yearFilter" class="form-select w-50" onchange="filterData()">
                    <option value="">Tahun</option>
                    <?php if (isset($available_years)): ?>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?= $year ?>">
                                <?= $year ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <!-- Satuan Kerja OPD -->
                <select id="opdFilter" class="form-select w-50" onchange="filterData()">
                    <option value="">OPD</option>
                    <option value="Biro Pemerintahan dan Otonomi Daerah">Biro Pemerintahan dan Otonomi Daerah</option>
                    <option value="Biro Hukum">Biro Hukum</option>
                    <option value="Biro Kesejahteraan Rakyat">Biro Kesejahteraan Rakyat</option>
                    <option value="Biro Perekonomian">Biro Perekonomian</option>
                </select>
            </div>
        </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
            <tr>
                <th class="border p-2">NO</th>
                <th class="border p-2">SATUAN KERJA</th>
                <th class="border p-2">SASARAN</th>
                <th class="border p-2">INDIKATOR</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">PROGRAM</th>
                <th class="border p-2">KEGIATAN</th>
                <th class="border p-2">SUB KEGIATAN</th>
                <th class="border p-2">TARGET ANGGARAN</th>
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
                // Group data by renstra_sasaran
                $renstraGroups = [];
                foreach ($renja_data as $renja) {
                    $sasaran = $renja['renstra_sasaran'];
                    if (!isset($renstraGroups[$sasaran])) {
                        $renstraGroups[$sasaran] = [
                            'sasaran' => $sasaran,
                            'renja_list' => []
                        ];
                    }
                    $renstraGroups[$sasaran]['renja_list'][] = $renja;
                }

                $globalNo = 1;
            ?>

            <?php foreach ($renstraGroups as $group): ?>
                <?php 
                    // Hitung total baris untuk 1 sasaran
                    $totalRowsForSasaran = 0;
                    foreach ($group['renja_list'] as $renja) {
                        $totalRowsForSasaran += count($renja['indikator']);
                    }
                    $isFirstRowOfSasaran = true;
                ?>

                <?php foreach ($group['renja_list'] as $renja): ?>
                    <?php 
                        $indikatorList = $renja['indikator']; 
                        $rowspanRenja = count($indikatorList);
                        if ($rowspanRenja == 0) continue;
                    ?>

                    <?php foreach ($indikatorList as $i => $indikator): ?>
                        <tr>
                            <!-- NO (sekali untuk 1 sasaran) -->
                            <?php if ($isFirstRowOfSasaran): ?>
                                <td class="border p-2" rowspan="<?= $totalRowsForSasaran ?>">
                                    <?= $globalNo++ ?>
                                </td>
                            <?php endif; ?>

                            <!-- SATUAN KERJA (sekali untuk setiap renja) -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 text-start" rowspan="<?= $rowspanRenja ?>">
                                    <?= esc($renja['satuan_kerja']) ?>
                                </td>
                            <?php endif; ?>

                            <!-- SASARAN (sekali per group) -->
                            <?php if ($isFirstRowOfSasaran): ?>
                                <td class="border p-2 text-start" rowspan="<?= $totalRowsForSasaran ?>">
                                    <?= esc($group['sasaran']) ?>
                                </td>
                                <?php $isFirstRowOfSasaran = false; ?>
                            <?php endif; ?>

                            <!-- INDIKATOR -->
                            <td class="border p-2 text-start"><?= esc($indikator['indikator_sasaran']) ?></td>

                            <!-- TARGET -->
                            <td class="border p-2"><?= esc($indikator['target']) ?> <?= esc($indikator['satuan']) ?></td>

                            <!-- PROGRAM (sekali per renja) -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 text-start" rowspan="<?= $rowspanRenja ?>">
                                    <?= esc($renja['program']) ?>
                                </td>
                            <?php endif; ?>

                            <!-- KEGIATAN (sekali per renja) -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 text-start" rowspan="<?= $rowspanRenja ?>">
                                    <?= esc($renja['kegiatan']) ?>
                                </td>
                            <?php endif; ?>

                            <!-- SUB KEGIATAN (sekali per renja) -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 text-start" rowspan="<?= $rowspanRenja ?>">
                                    <?= esc($renja['sub_kegiatan']) ?>
                                </td>
                            <?php endif; ?>

                            <!-- TARGET ANGGARAN (sekali per renja) -->
                            <?php if ($i === 0): ?>
                                <td class="border p-2 text-end" rowspan="<?= $rowspanRenja ?>">
                                    Rp <?= number_format($renja['target_anggaran'], 0, ',', '.') ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
                    
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
  </div>  <!-- End of main-content -->
  
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
    const yearFilter = document.getElementById('yearFilter').value;
    const opdFilter = document.getElementById('opdFilter').value;

    // Kalau salah satu belum dipilih, kosongkan tabel
    if (!yearFilter || !opdFilter) {
        rebuildTable([]); 
        updateDataSummary(0, originalData.length, yearFilter, opdFilter);
        return;
    }

    // Filter original data
    const filteredData = originalData.filter(function(renja) {
        const yearMatch = renja.indikator.some(function(indikator) {
            return indikator.tahun === yearFilter;
        });
        const opdMatch = renja.opd === opdFilter; // pastikan field 'opd' ada di data
        return yearMatch && opdMatch;
    });

    // Ambil indikator sesuai tahun
    const processedData = filteredData.map(function(renja) {
        return {
            ...renja,
            indikator: renja.indikator.filter(function(indikator) {
                return indikator.tahun === yearFilter;
            })
        };
    });

    // Rebuild table
    rebuildTable(processedData);

    // Update summary
    const totalIndicators = processedData.reduce((sum, renja) => sum + renja.indikator.length, 0);
    updateDataSummary(totalIndicators, originalData.length, yearFilter, opdFilter);
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
function updateDataSummary(visibleCount, totalCount, yearFilter, opdFilter) {
    const countElement = document.getElementById('visible-data-count');
    if (countElement) {
        countElement.textContent = visibleCount > 0
            ? `Menampilkan ${visibleCount} dari ${totalCount} data`
            : `Belum ada data ditampilkan`;
    }

    const filtersElement = document.getElementById('active-filters');
    if (filtersElement) {
        if (!yearFilter || !opdFilter) {
            filtersElement.textContent = 'Harap pilih Tahun dan Satuan Kerja OPD';
        } else {
            filtersElement.textContent = `Tahun: ${yearFilter}, OPD: ${opdFilter}`;
        }
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
          filtersElement.textContent = 'Tahun, OPD';
      }
  });
  </script>
</body>
</html>

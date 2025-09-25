<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RPJMD</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
      <div class="bg-white p-4 rounded shadow-sm">
        <h4 class="fw-bold text-center text-success mb-4">RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH</h4>
        
        <!-- Filter Periode -->
        <div class="d-flex justify-content-center mb-4">
          <div class="col-md-4">
            <select id="periode-filter" class="form-select" onchange="filterByPeriode()">
              <option value="">Semua Periode</option>
              <?php if (isset($rpjmdGrouped) && !empty($rpjmdGrouped)): ?>
                <?php foreach ($rpjmdGrouped as $periodKey => $periodData): ?>
                  <option value="<?= $periodKey ?>">
                    Periode <?= $periodData['period'] ?>
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
            <?= $message ?>
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
                  <th rowspan="2" class="border p-2 align-middle">INDIKATOR</th>
                  <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                  <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                  <th rowspan="2" class="border p-2 align-middle">DEFINISI OPERASIONAL</th>
                  <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                  <th colspan="5" class="border p-2" id="year-header-span">TARGET CAPAIAN PER TAHUN</th>
                </tr>
                <tr class="border p-2" style="border-top: 2px solid;" id="year-header-row">
                  <!-- Year headers will be populated by JavaScript -->
                </tr>
              </thead>
              <tbody id="rpjmd-table-body">
                <?php foreach ($rpjmdGrouped as $periodIndex => $periodData): ?>
                  <!-- Data untuk periode ini -->
                  <?php foreach ($periodData['misi_data'] as $misi): ?>
                    <?php if (isset($misi['tujuan']) && !empty($misi['tujuan'])): ?>
                      <?php 
                      $misiRowspan = 0;
                      foreach ($misi['tujuan'] as $tujuan) {
                          if (isset($tujuan['sasaran']) && !empty($tujuan['sasaran'])) {
                              foreach ($tujuan['sasaran'] as $sasaran) {
                                  if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])) {
                                      $misiRowspan += count($sasaran['indikator_sasaran']);
                                  } else {
                                      $misiRowspan += 1;
                                  }
                              }
                          } else {
                              $misiRowspan += 1;
                          }
                      }
                      ?>
                      
                      <?php $firstMisiRow = true; ?>
                      <?php foreach ($misi['tujuan'] as $tujuan): ?>
                        <?php if (isset($tujuan['sasaran']) && !empty($tujuan['sasaran'])): ?>
                          <?php 
                          $tujuanRowspan = 0;
                          foreach ($tujuan['sasaran'] as $sasaran) {
                              if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])) {
                                  $tujuanRowspan += count($sasaran['indikator_sasaran']);
                              } else {
                                  $tujuanRowspan += 1;
                              }
                          }
                          ?>
                          
                          <?php $firstTujuanRow = true; ?>
                          <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                            <?php if (isset($sasaran['indikator_sasaran']) && !empty($sasaran['indikator_sasaran'])): ?>
                              
                              <?php $firstSasaranRow = true; ?>
                              <?php foreach ($sasaran['indikator_sasaran'] as $indikator): ?>
                                <tr class="periode-row" data-periode="<?= $periodIndex ?>">
                                  <?php if ($firstMisiRow): ?>
                                    <td class="border p-2 align-top text-start" rowspan="<?= $misiRowspan ?>"><?= esc($misi['misi']) ?></td>
                                    <?php $firstMisiRow = false; ?>
                                  <?php endif; ?>
                                  
                                  <?php if ($firstTujuanRow): ?>
                                    <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>"><?= esc($tujuan['tujuan_rpjmd']) ?></td>
                                    <td class="border p-2 align-top text-start" rowspan="<?= $tujuanRowspan ?>">
                                      <?php if (isset($tujuan['indikator_tujuan']) && !empty($tujuan['indikator_tujuan'])): ?>
                                        <?php foreach ($tujuan['indikator_tujuan'] as $idx => $indikatorTujuan): ?>
                                          <?= esc($indikatorTujuan['indikator_tujuan']) ?>
                                          <?php if ($idx < count($tujuan['indikator_tujuan']) - 1): ?><br><?php endif; ?>
                                        <?php endforeach; ?>
                                      <?php else: ?>
                                        -
                                      <?php endif; ?>
                                    </td>
                                    <?php $firstTujuanRow = false; ?>
                                  <?php endif; ?>
                                  
                                  <?php if ($firstSasaranRow): ?>
                                    <td class="border p-2 align-top text-start" rowspan="<?= count($sasaran['indikator_sasaran']) ?>"><?= esc($sasaran['sasaran_rpjmd']) ?></td>
                                    <?php $firstSasaranRow = false; ?>
                                  <?php endif; ?>
                                  
                                  <td class="border p-2 align-top text-start"><?= esc($indikator['indikator_sasaran']) ?></td>
                                  <td class="border p-2 align-top text-start"><?= esc($indikator['definisi_op'] ?? '-') ?></td>
                                  <td class="border p-2 align-top text-start"><?= esc($indikator['satuan'] ?? '-') ?></td>
                                  
                                  <!-- Target per tahun -->
                                  <span class="year-cells" data-periode="<?= $periodIndex ?>">
                                    <?php foreach ($periodData['years'] as $year): ?>
                                      <td class="border p-2 align-top text-middle">
                                        <?php if (isset($indikator['target_tahunan']) && !empty($indikator['target_tahunan'])): ?>
                                          <?php foreach ($indikator['target_tahunan'] as $target): ?>
                                            <?php if ($target['tahun'] == $year): ?>
                                              <?= esc($target['target_tahunan']) ?>
                                              <?php break; ?>
                                            <?php endif; ?>
                                          <?php endforeach; ?>
                                        <?php else: ?>
                                          -
                                        <?php endif; ?>
                                      </td>
                                    <?php endforeach; ?>
                                  </span>
                                </tr>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
  </main>

  <script>
    // Data periode dari controller
    const periodData = <?= json_encode($rpjmdGrouped ?? []) ?>;
    
    // Fungsi untuk update header tahun
    function updateTableHeaders(periodKey) {
        const yearHeaderRow = document.getElementById('year-header-row');
        const yearHeaderSpan = document.getElementById('year-header-span');
        
        if (periodData[periodKey] && periodData[periodKey].years) {
            const years = periodData[periodKey].years;
            
            // Update colspan
            yearHeaderSpan.setAttribute('colspan', years.length);
            
            // Clear and rebuild year headers
            yearHeaderRow.innerHTML = '';
            years.forEach(function(year) {
                const th = document.createElement('th');
                th.className = 'border p-2';
                th.textContent = year;
                yearHeaderRow.appendChild(th);
            });
        }
    }
    
    // Fungsi untuk filter berdasarkan periode
    function filterByPeriode() {
      const selectedPeriode = document.getElementById('periode-filter').value;
      const rows = document.querySelectorAll('.periode-row');
      const yearCells = document.querySelectorAll('.year-cells');
      
      // Hide semua baris terlebih dahulu
      rows.forEach(row => {
        row.style.display = 'none';
      });
      
      // Hide semua year cells
      yearCells.forEach(cell => {
        cell.style.display = 'none';
      });
      
      if (selectedPeriode === '') {
        // Tampilkan semua jika tidak ada filter
        rows.forEach(row => {
          row.style.display = '';
        });
        
        // Untuk "Semua Periode", ambil periode pertama untuk header
        const firstPeriodeKey = Object.keys(periodData)[0];
        if (firstPeriodeKey) {
          updateTableHeaders(firstPeriodeKey);
          
          // Show all year cells
          yearCells.forEach(cell => {
            cell.style.display = '';
          });
        }
      } else {
        // Tampilkan hanya baris yang sesuai periode
        rows.forEach(row => {
          if (row.getAttribute('data-periode') === selectedPeriode) {
            row.style.display = '';
          }
        });
        
        // Tampilkan year cells untuk periode yang dipilih
        yearCells.forEach(cell => {
          if (cell.getAttribute('data-periode') === selectedPeriode) {
            cell.style.display = '';
          }
        });
        
        updateTableHeaders(selectedPeriode);
      }
    }
    
    // Inisialisasi tampilan saat page load
    document.addEventListener('DOMContentLoaded', function() {
      // Set filter ke periode pertama jika ada data
      const periodeFilter = document.getElementById('periode-filter');
      if (periodeFilter.children.length > 1) {
        periodeFilter.selectedIndex = 1; // Pilih periode pertama (bukan "Semua Periode")
        filterByPeriode();
      }
    });
  </script>

  <?= $this->include('user/templates/footer'); ?>

</body>
</html>
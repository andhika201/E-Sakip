<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RKPD</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
      <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA KINERJA TAHUNAN
      </h4>

      <!-- Tabel RKPD -->
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-success">
            <tr>
              <th style="width: 5%;">No</th>
              <th>Sasaran RPJMD</th>
              <th>Sasaran RKPD</th>
              <th>Indikator Sasaran</th>
              <th>Satuan</th>
              <th>Tahun</th>
              <th>Target</th>
            </tr>
          </thead>
            <tbody>
              <?php if (empty($rkpd_data)): ?>
                  <tr id="no-data-message">
                      <td colspan="9" class="border p-4 text-center text-muted">
                          <i class="fas fa-info-circle me-2"></i>
                              Tidak ada data RKPD.
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
  </div>
</main>

<?= $this->include('user/templates/footer'); ?>

</body>
</html>
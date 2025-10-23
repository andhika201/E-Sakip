<!-- app/Views/adminkabupaten/rkpd/rkpd.php -->
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>RKPD - e-SAKIP</title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    /* minor styles for clean table */
    .opd-header {
      text-transform: uppercase;
      font-weight: 700;
      text-align: center;
      vertical-align: middle;
    }

    .nowrap {
      white-space: nowrap;
    }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/header.php'); ?>
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">

      <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA KERJA PERANGKAT DAERAH</h2>

      <!-- Alerts -->
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
      <?php endif; ?>

      <!-- FILTER -->
      <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div class="d-flex gap-2 flex-fill">
          <select id="opdFilter" class="form-select w-25">
            <option value="all" <?= ($opdId === 'all') ? 'selected' : '' ?>>SEMUA OPD</option>
            <?php if (!empty($allOpd)):
              foreach ($allOpd as $opd): ?>
                <option value="<?= esc($opd['id']) ?>" <?= ((string) $opdId === (string) $opd['id']) ? 'selected' : '' ?>>
                  <?= esc($opd['nama_opd']) ?>
                </option>
              <?php endforeach; endif; ?>
          </select>

          <select id="yearFilter" class="form-select w-25">
            <?php
            $curYear = date('Y');
            $yearSelected = isset($tahun) ? $tahun : $curYear;
            ?>
            <option value="all" <?= ($yearSelected === 'all') ? 'selected' : '' ?>>SEMUA TAHUN</option>
            <?php if (!empty($available_years)):
              foreach ($available_years as $y): ?>
                <option value="<?= esc($y) ?>" <?= ((string) $yearSelected === (string) $y) ? 'selected' : '' ?>>
                  <?= esc($y) ?>
                </option>
              <?php endforeach; endif; ?>
          </select>
        </div>
      </div>

      <!-- SUMMARY -->
      <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between">
          <small class="text-muted"><span id="visible-data-count">Memuat data...</span></small>
          <small class="text-muted">Filter aktif: <span id="active-filters">
              OPD: <?= ($opdId === 'all' ? 'SEMUA' : esc($currentOpd['nama_opd'] ?? '')) ?>,
              Tahun: <?= esc($tahun) ?>
            </span></small>
        </div>
      </div>


      <!-- TABLE -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
          <thead class="table-success">
            <tr>
              <th class="border p-2">SATUAN KERJA</th>
              <th class="border p-2">NO</th>
              <th class="border p-2">SASARAN</th>
              <th class="border p-2">INDIKATOR SASARAN</th>
              <th class="border p-2">SATUAN</th>
              <th class="border p-2">TARGET</th>
              <th class="border p-2">PROGRAM</th>
              <th class="border p-2">KEGIATAN</th>
              <th class="border p-2">SUB KEGIATAN</th>
              <th class="border p-2">TARGET ANGGARAN</th>
            </tr>
          </thead>

          <tbody>
            <?php helper('format_helper'); ?>

            <?php if ($opdId === 'all'): ?>
              <tr>
                <td colspan="10">Tolong pilih filter.</td>
              </tr>

            <?php else: ?>
              <!-- MODE SINGLE OPD (fallback to indicator-based structure) -->
              <?php
              // Here $rktdata expected as indicators array (as previous getIndicatorsWithRkt returns)
              if (empty($rktdata)):
                ?>
                <tr>
                  <td colspan="10" class="p-4 text-center text-muted">Tidak ada data RENJA untuk OPD ini.</td>
                </tr>
              <?php else: ?>
                <?php
                helper('format_helper');
                $no = 1;

                // Hitung total rowspan untuk kolom OPD
                $totalRowsAll = 0;
                foreach ($rktdata as $ind) {
                  $rowCount = 0;
                  if (!empty($ind['rkts'])) {
                    foreach ($ind['rkts'] as $rkt) {
                      if (!empty($rkt['kegiatan'])) {
                        foreach ($rkt['kegiatan'] as $keg) {
                          $subCount = count($keg['subkegiatan'] ?? []);
                          $rowCount += ($subCount > 0 ? $subCount : 1);
                        }
                      } else {
                        $rowCount += 1;
                      }
                    }
                  } else {
                    $rowCount = 1;
                  }
                  $totalRowsAll += $rowCount;
                }

                $firstOpdRow = true;

                foreach ($rktdata as $ind):
                  // hitung rowspan indikator
                  $totalSub = 0;
                  if (!empty($ind['rkts'])) {
                    foreach ($ind['rkts'] as $rkt) {
                      if (!empty($rkt['kegiatan'])) {
                        foreach ($rkt['kegiatan'] as $keg) {
                          $subCount = count($keg['subkegiatan'] ?? []);
                          $totalSub += ($subCount > 0 ? $subCount : 1);
                        }
                      } else {
                        $totalSub += 1;
                      }
                    }
                  } else {
                    $totalSub = 1;
                  }

                  $firstIndicatorRow = true;
                  $actionRendered = false;

                  // Jika belum ada RKT sama sekali
                  if (empty($ind['rkts'])):
                    ?>
                    <tr>
                      <?php if ($firstOpdRow): ?>
                        <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                          <?= esc($currentOpd['nama_opd']) ?>
                        </td>
                        <?php $firstOpdRow = false; ?>
                      <?php endif; ?>

                      <td rowspan="<?= $totalSub ?>" class="align-middle"><?= $no++ ?></td>
                      <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                        <?= esc($ind['sasaran']) ?>
                      </td>
                      <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                        <?= esc($ind['indikator_sasaran']) ?>
                      </td>
                      <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                        <?= esc($ind['satuan']) ?>
                      </td>
                      <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                        <?= esc($ind['target']) ?>
                      </td>

                      <td class="text-start">-</td>
                      <td class="text-start">-</td>
                      <td class="text-start">-</td>
                      <td class="text-end">-</td>

                      
                    </tr>

                    <?php
                    // --- RKT ADA ---
                  else:
                    foreach ($ind['rkts'] as $rkt):
                      $firstProgramRow = true;
                      foreach ($rkt['kegiatan'] as $keg):
                        $subCount = count($keg['subkegiatan'] ?? []);
                        $rowspanKeg = ($subCount > 0 ? $subCount : 1);
                        $firstKegRow = true;

                        if (!empty($keg['subkegiatan'])):
                          foreach ($keg['subkegiatan'] as $sub):
                            ?>
                            <tr>
                              <?php if ($firstOpdRow): ?>
                                <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                  <?= esc($currentOpd['nama_opd']) ?>
                                </td>
                                <?php $firstOpdRow = false; ?>
                              <?php endif; ?>

                              <?php if ($firstIndicatorRow): ?>
                                <td rowspan="<?= $totalSub ?>" class="align-middle"><?= $no++ ?></td>
                                <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                                  <?= esc($ind['sasaran']) ?>
                                </td>
                                <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                                  <?= esc($ind['indikator_sasaran']) ?>
                                </td>
                                <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                  <?= esc($ind['satuan']) ?>
                                </td>
                                <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                  <?= esc($ind['target']) ?>
                                </td>
                                <?php $firstIndicatorRow = false; ?>
                              <?php endif; ?>

                              <?php if ($firstProgramRow): ?>
                                <td rowspan="<?= $rowspanKeg ?>" class="align-middle text-start">
                                  <?= esc($rkt['program_nama']) ?>
                                </td>
                                <?php $firstProgramRow = false; ?>
                              <?php endif; ?>

                              <?php if ($firstKegRow): ?>
                                <td rowspan="<?= $rowspanKeg ?>" class="align-middle text-start">
                                  <?= esc($keg['nama_kegiatan']) ?>
                                </td>
                                <?php $firstKegRow = false; ?>
                              <?php endif; ?>

                              <td class="text-start"><?= esc($sub['nama_subkegiatan']) ?></td>
                              <td class="text-end"><?= formatRupiah($sub['target_anggaran']) ?></td>

                              
                            </tr>
                            <?php
                          endforeach;
                        endif;
                      endforeach;
                    endforeach;
                  endif;
                endforeach;
                ?>
              <?php endif; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <script>
    // filter auto-apply
    function applyFilter() {
      const opd = document.getElementById('opdFilter').value;
      const tahun = document.getElementById('yearFilter').value;
      const q = new URLSearchParams();
      if (opd) q.set('opd_id', opd);
      if (tahun) q.set('tahun', tahun);
      const url = '?' + q.toString();
      window.location.href = url;
    }

    document.getElementById('opdFilter').addEventListener('change', applyFilter);
    document.getElementById('yearFilter').addEventListener('change', applyFilter);

    document.getElementById('resetFilter').addEventListener('click', function (e) {
      e.preventDefault();
      window.location.href = '<?= base_url('adminkabupaten/rkpd') ?>';
    });

    // small helper to update visible count
    (function updateSummary() {
      const rows = document.querySelectorAll('tbody tr').length;
      document.getElementById('visible-data-count').textContent = rows + ' baris ditampilkan';
    })();
  </script>
</body>

</html>
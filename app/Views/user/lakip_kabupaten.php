<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lakip</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
      <div class="bg-white p-4 rounded shadow-sm">
        <h4 class="fw-bold text-center text-success mb-4">
          LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAHAN KABUPATEN
        </h4>
        
        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex">
                <select class="form-select" onchange="filterByYear(this.value)">
                    <option value="">Semua Tahun</option>
                    <?php if (!empty($availableYears)): ?>
                        <?php foreach ($availableYears as $year): ?>
                        <option value="<?= $year ?>" <?= (isset($selected_year) && $selected_year == $year) ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php if (isset($selected_year) && !empty($selected_year)): ?>
                    <a href="<?= base_url('/lakip_kabupaten') ?>" class="btn btn-outline-secondary d-flex align-items-center">
                        <i class="fas fa-times me-2"></i> RESET
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responive">
          <table class="table table=bordered align-middle text-center">
            <thead class="table-success">
              <tr>
              <th>No</th>
              <th>Tahun Laporan</th>
              <th>Judul Laporan</th>
              <th>File</th>
            </tr>
          </thead>
            <tbody>
                <?php if (!empty($lakips) && is_array($lakips)): ?>
                    <?php 
                    $no = 1; 
                    foreach ($lakips as $lakip): 
                    ?>
                    <tr>
                        <td class="border p-2"><?= $no++ ?></td>
                        <td class="border p-2">
                            <?= !empty($lakip['tanggal_laporan']) ? date('Y', strtotime($lakip['tanggal_laporan'])) : '-' ?>
                        </td>
                        <td class="border p-2 text-start"><?= esc($lakip['judul']) ?></td>
                        <td class="border p-2">
                            <?php if (!empty($lakip['file'])): ?>
                                <a href="<?= base_url('/lakip_kabupaten/download/' . $lakip['id']) ?>" class="text-primary" title="<?= esc($lakip['judul']) ?>">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php else: ?>
                      <tr>
                          <td colspan="7" class="border p-4 text-center text-muted">
                              <i class="fas fa-folder-open me-2"></i>
                              Belum ada data LAKIP Kabupaten
                          </td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>
    </main>
    
    <?= $this->include('user/templates/footer'); ?>

      <!-- JavaScript untuk filter -->
  <script>

    function filterByYear(tahun) {
        if (tahun) {
            window.location.href = '<?= base_url("/lakip_kabupaten") ?>?tahun=' + tahun;
        } else {
            window.location.href = '<?= base_url("/lakip_kabupaten") ?>';
        }
    }
  </script>
</body>
</html>
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

        <?php if (empty($renjaData)): ?>
          <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Belum ada data RENJA yang tersedia.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
              <thead class="table-success">
                <tr>
                  <th>No</th>
                  <th>Sasaran</th>
                  <th>Indikator Sasaran</th>
                  <th>Target Capaian Per Tahun</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($renjaData as $item): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= esc($item['sasaran']) ?></td>
                    <td><?= esc($item['indikator_sasaran']) ?></td>
                    <td><?= esc($item['target_capaian_per_tahun']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?= $this->include('user/templates/footer'); ?>

</body>
</html>

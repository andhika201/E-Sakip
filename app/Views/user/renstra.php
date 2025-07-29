<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RENSTRA</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>

  <?= $this->include('user/templates/header'); ?>

  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
      <div class="bg-white p-4 rounded shadow-sm">
        <h4 class="fw-bold text-center text-success mb-4">
          RENCANA STRATEGIS
        </h4>

        <!-- Filter OPD -->
        <div class="row justify-content-center mb-4">
          <div class="col-md-4">
            <select id="filterOpd" class="form-control">
              <option value="">Semua OPD</option>
              <?php foreach ($opdList as $opd): ?>
                <option value="<?= $opd['id']; ?>"><?= $opd['nama_opd']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-success w-100" onclick="filterRenstraOpd()">
              <i class="fas fa-filter"></i> Filter
            </button>
          </div>
        </div>

        <!-- Cek apakah ada data -->
        <?php if (empty($renstraData)): ?>
          <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Belum ada data RENSTRA yang tersedia.
          </div>
        <?php else: ?>
          <!-- Tabel RENSTRA -->
          <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
              <thead class="table-success">
                <tr>
                  <th style="width: 5%;">No</th>
                  <th>Sasaran</th>
                  <th>Indikator Sasaran</th>
                  <th>Satuan</th>
                  <th>Target Tahun <?= esc($tahun_mulai ?? 'Awal') ?> - <?= esc($tahun_akhir ?? 'Akhir') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($renstraData as $item): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= esc($item['sasaran']) ?></td>
                    <td><?= esc($item['indikator']) ?></td>
                    <td><?= esc($item['satuan']) ?></td>
                    <td>
                      <?php foreach ($item['target'] as $tahun => $target): ?>
                        <div><strong><?= $tahun ?>:</strong> <?= esc($target) ?></div>
                      <?php endforeach; ?>
                    </td>
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

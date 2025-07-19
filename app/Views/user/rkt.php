<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RKT</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body>

  <?= $this->include('user/templates/header'); ?>

  <main class="flex-grow-1">
    <div class="container-fluid my-4 px-4">
      <div class="bg-white p-4 rounded shadow-sm">
        <h4 class="fw-bold text-success mb-4 text-center">
          RENCANA KINERJA TAHUNAN
        </h4>

        <!-- Tabel RKT -->
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center">
            <thead class="table-success">
              <tr>
                <th style="width: 5%;">No</th>
                <th>Sasaran</th>
                <th>Indikator Sasaran</th>
                <th>Target Capaian</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $grouped = [];
              foreach ($rktData as $item) {
                  $grouped[$item['sasaran']][] = $item;
              }
              foreach ($grouped as $sasaran => $indikatorList):
                  $rowspan = count($indikatorList);
              ?>
                <?php foreach ($indikatorList as $i => $item): ?>
                  <tr>
                    <?php if ($i === 0): ?>
                      <td rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                      <td rowspan="<?= $rowspan ?>"><?= esc($sasaran) ?></td>
                    <?php endif; ?>
                    <td><?= esc($item['indikator']) ?></td>
                    <td><?= esc($item['target']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>


<?= $this->include('user/templates/footer'); ?>

</body>
</html>
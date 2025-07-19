<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IKU OPD</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1">
  <div class="container-fluid my-4 px-4" >
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center mb-4" style="color: #00743e;">
        INDIKATOR KINERJA UTAMA (IKU) OPD
      </h4>
      
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-success">
            <tr>
              <th rowspan="2" style="width: 5%;">No</th>
              <th rowspan="2">Sasaran Strategis</th>
              <th rowspan="2">Indikator Sasaran (IKU)</th>
              <th rowspan="2">Definisi Operasional / Formulasi</th>
              <th rowspan="2">Satuan</th>
              <th colspan="<?= count($tahunList) ?>">Target Capaian Per Tahun</th>
            </tr>
            <tr>
              <?php foreach ($tahunList as $tahun): ?>
                <th><?= $tahun ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
          <tbody>
            <?php $no = 1; foreach ($ikuOpdData as $item): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= esc($item['sasaran']) ?></td>
                <td><?= esc($item['indikator']) ?></td>
                <td><?= esc($item['definisi']) ?></td>
                <td><?= esc($item['satuan']) ?></td>
                <?php foreach ($tahunList as $tahun): ?>
                  <td><?= isset($item['target_capaian'][$tahun]) ? esc($item['target_capaian'][$tahun]) : '-' ?></td>
                <?php endforeach; ?>
              </tr>
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
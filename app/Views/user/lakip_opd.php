<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LAKIP</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAHAN OPD
      </h4>
      
      <div class="table-responive">
        <table class="table table=bordered align-middle text-center">
          <thead class="table-success">
            <tr>
              <th>No</th>
              <th>Sasaran</th>
              <th>Indikator</th>
              <th>Capaian Tahun Sebelumnya</th>
              <th>Target Tahun Ini</th>
              <th>Capaian Tahun Ini</th>
            </tr>
          </thead>
        <tbody>
          <?php $no = 1; foreach ($lakipOpdData as $item): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= esc($item['sasaran']) ?></td>
                <td><?= esc($item['indikator']) ?></td>
                <td><?= esc($item['capaian_sebelumnya']) ?></td>
                <td><?= esc($item['target_tahun_ini']) ?></td>
                <td><?= esc($item['capaian_tahun_ini']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>

<?= $this->include('user/templates/footer'); ?>

</body>
</html>
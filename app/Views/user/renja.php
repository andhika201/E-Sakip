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

      <!-- Filter OPD -->
      <div class="row mb-3">
        <div class="col-md-4 d-flex align-items-center">
          <label for="filterOpd" class="me-2 fw-bold">OPD:</label>
          <select id="filterOpd" class="form-select">
            <?php foreach ($opdList as $opd): ?>
              <option value="<?= esc($opd) ?>"><?= esc($opd) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" onclick="filterOpd()">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle tect-center">
          <thead class="table-success">
            <tr>
              <th>No</th>~
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
    </main>

<?= $this->include('user/templates/footer'); ?>

</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PK JPT - OPD</title>
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>
<body>

  <?= $this->include('adminOpd/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        PERJANJIAN KINERJA PIMPINAN
      </h4>

      <!-- Info Misi dan Sasaran -->
      <div class="mb-4">
        <div class="mb-3">
          <label for="misi" class="form-label fw-bold">Misi ke
          <input type="text" id="misi" name="misi" 
                class="form-control" 
                value="<?= esc($pkJptData[0]['misi'] ?? '-') ?>" 
                readonly>
          </label>
        </div>
        <div>
          <label for="sasaran" class="form-label fw-bold">Sasaran
          <input type="text" id="sasaran" name="sasaran" 
                class="form-control" 
                value="<?= esc($pkJptData[0]['sasaran'] ?? '-') ?>" 
                readonly>
          </label>
        </div>
      </div>
    
      <!-- Tabel PK Opd -->
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-success">
            <tr>
              <th style="width: 5%;">No</th>
              <th>Indikator</th>
              <th>Satuan</th>
              <th>Target</th>
              <th>Capaian Tahun Sebelumnya</th>
              <th>Target Tahun Ini</th>
              <th>Capaian Tahun Ini</th>
            </tr>
          </thead>
          <tbody id="pkJptTableBody">
            <?php $no = 1; foreach ($pkJptData as $item): ?>
              <tr data-tahun="<?= esc($item['tahun']) ?>">
                <td><?= $no++ ?></td>
                <td><?= esc($item['indikator']) ?></td>
                <td><?= esc($item['satuan']) ?></td>
                <td><?= esc($item['target']) ?></td>
                <td><?= esc($item['capaian_sebelumnya']) ?></td>
                <td><?= esc($item['target_tahun_ini']) ?></td>
                <td><?= esc($item['capaian_tahun_ini']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?= $this->include('adminOpd/templates/footer'); ?>
</body>
</html>
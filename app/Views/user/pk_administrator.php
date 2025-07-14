<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PK Asministrator</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
      <div class="bg-white p-4 rounded shadow-sm">
        <h4 class="fw-bold text-center text-success mb-4">
        PERJANJIAN KINERJA ADMINISTRATOR
      </h4>
      
      <!-- Filter Tahun -->
      <div class="row justify-content-center mb-4">
        <div class="col-md-4">
          <input type="number" id="filterTahun" class="form-control" placeholder="Filter Tahun">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" onclick="filterPkAdministrator()">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </div>
      
      <!-- Tabel PK OPD -->
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-success">
            <tr>
              <th style="width: 5%;">No</th>
              <th>Sasaran</th>
              <th>Indikator</th>
              <th>Target</th>
            </tr>
          </thead>
          <tbody id="pkAdministratorTableBody">
            <?php $no = 1; foreach ($pkAdministratorData as $item): ?>
              <tr data-tahun="<?= esc($item['tahun']) ?>">
                <td><?= $no++ ?></td>
                <td><?= esc($item['sasaran']) ?></td>
                <td><?= esc($item['indikator']) ?></td>
                <td><?= esc($item['target']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
      </div>
    </div>
  </div>
</main>

<script>
  function filterPkAdministrator() {
    const tahun = document.getElementById('filterTahun').value.trim();
    
    if (!tahun) {
      alert('Masukkan tahun untuk memfilter!');
      return;
    }
    
    const rows = document.querySelectorAll('#pkAdministratorTableBody tr');
    rows.forEach(row => {
      const rowTahun = row.getAttribute('data-tahun');
      row.style.display = (rowTahun === tahun) ? '' : 'none';
    });
  }
</script>

<?= $this->include('user/templates/footer'); ?>
</body>
</html>
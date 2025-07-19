<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PK Bupati</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('user/templates/header'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
      <h4 class="fw-bold text-center text-success mb-4">
        PERJANJIAN KINERJA KABUPATEN
      </h4>

      <!-- Filter Tahun -->
      <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-4">
        <div class="d-flex gap-2 flex-fill" style="max-width: 500px;">
          <input type="number" id="filterTahun" class="form-control" placeholder="Filter Tahun">
          <button class="btn btn-success d-flex align-items-center" onclick="filterPkBupati()">
            <i class="fas fa-filter me-2"></i> Filter
          </button>
        </div>
      </div>

      <!-- Tabel PK Bupati -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
          <thead class="table-success align-middle">
            <tr>
              <th style="width: 5%;">No</th>
              <th>Sasaran</th>
              <th>Indikator</th>
              <th>Target</th>
            </tr>
          </thead>
          <tbody id="pkBupatiTableBody">
            <?php $no = 1; foreach ($pkBupatiData as $item): ?>
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
  </main>



<script>
  function filterPkBupati() {
    const tahun = document.getElementById('filterTahun').value.trim();

    if (!tahun) {
      alert('Masukkan tahun untuk memfilter!');
      return;
    }
    
    const rows = document.querySelectorAll('#pkBupatiTableBody tr');
    rows.forEach(row => {
      const rowTahun = row.getAttribute('data-tahun');
      row.style.display = (rowTahun === tahun) ? '' : 'none';
    });
  }
</script>

<?= $this->include('user/templates/footer'); ?>
</body>
</html>
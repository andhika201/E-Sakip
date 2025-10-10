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
      
      <!-- Filter Tahun -->
      <div class="row justify-content-center mb-4">
        <div class="col-md-4">
          <input type="number" id="filterTahun" class="form-control" placeholder="Filter Tahun">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" onclick="filterPkPimpinan()">
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
              <th>Misi</th>
              <th>Sasaran</th>
              <th>Indikator</th>
              <th>Target</th>
            </tr>
          </thead>
          <tbody id="pkPimpinanTableBody">
            <?php $no = 1; foreach ($pkPimpinanData as $item): ?>
              <tr data-tahun="<?= esc($item['tahun']) ?>">
                <td><?= $no++ ?></td>
                <td><?= esc($item['misi']) ?></td>
                <td><?= esc($item['sasaran']) ?></td>
                <td><?= esc($item['indikator']) ?></td>
                <td><?= esc($item['target']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
      </div>
      <!-- Tabel Program & Anggaran -->
      <div class="table-responsive mt-5">
        <h5 class="fw-bold text-center text-success mb-3">
          DAFTAR PROGRAM DAN ANGGARAN
        </h5>
        <table class="table table-bordered table-striped text-center small">
          <thead class="table-success">
            <tr>
              <th class="border p-2 align-middle" style="width: 50px;">No</th>
              <th class="border p-2 align-middle">Program</th>
              <th class="border p-2 align-middle">Anggaran</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="border p-2">1</td>
              <td class="border p-2 text-start">Peningkatan Infrastruktur Jalan</td>
              <td class="border p-2">Rp 5.000.000.000</td>
            </tr>
            <tr>
              <td class="border p-2">2</td>
              <td class="border p-2 text-start">Pengembangan SDM Aparatur</td>
              <td class="border p-2">Rp 1.200.000.000</td>
            </tr>
            <tr>
              <td class="border p-2">3</td>
              <td class="border p-2 text-start">Peningkatan Kesehatan Masyarakat</td>
              <td class="border p-2">Rp 3.500.000.000</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script>
  function filterPkPimpinan() {
    const tahun = document.getElementById('filterTahun').value.trim();
    
    if (!tahun) {
      alert('Masukkan tahun untuk memfilter!');
      return;
    }
    
    const rows = document.querySelectorAll('#pkPimpinanTableBody tr');
    rows.forEach(row => {
      const rowTahun = row.getAttribute('data-tahun');
      row.style.display = (rowTahun === tahun) ? '' : 'none';
    });
  }
</script>

<?= $this->include('adminOpd/templates/footer'); ?>
</body>
</html>
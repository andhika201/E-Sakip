<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RPJMD</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH
      </h4>
      
      <!-- Filter Tahun -->
      <div class="row justify-content-center mb-4">
        <div class="col-md-3">
          <input type="number" id="tahunMulai" class="form-control" placeholder="Tahun Mulai">
        </div>
        <div class="col-md-3">
          <input type="number" id="tahunSelesai" class="form-control" placeholder="Tahun Selesai">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" onclick="filterTahun()">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </div>
      
      <!-- Tabel RPJMD -->
      <?php if (isset($message)): ?>
        <div class="alert alert-info text-center">
          <i class="fas fa-info-circle me-2"></i>
          <?= $message ?>
        </div>
      <?php elseif (empty($rpjmdData)): ?>
        <div class="alert alert-warning text-center">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Belum ada data RPJMD yang tersedia.
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-success align-middle">
            <tr>
              <th rowspan="2">Misi</th>
              <th rowspan="2">Tujuan</th>
              <th rowspan="2">Indikator</th>
              <th rowspan="2">Target</th>
              <th rowspan="2">Sasaran</th>
              <th rowspan="2">Strategi</th>
              <?php if (!empty($tahunList)): ?>
              <th colspan="<?= count($tahunList) ?>">Target Capaian Per Tahun</th>
              <?php endif; ?>
            </tr>
            <tr>
              <?php foreach ($tahunList as $tahun): ?>
                <th class="tahun-col tahun-<?= $tahun ?>"><?= $tahun ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rpjmdData as $data): ?>
              <tr>
                <td><?= esc($data['misi']) ?></td>
                <td><?= esc($data['tujuan']) ?></td>
                <td><?= esc($data['indikator']) ?></td>
                <td><?= esc($data['target']) ?></td>
                <td><?= esc($data['sasaran']) ?></td>
                <td><?= esc($data['strategi']) ?></td>
                <?php foreach ($tahunList as $tahun): ?>
                  <td class="tahun-col tahun-<?= $tahun ?>">
                    <?= isset($data['target_capaian'][$tahun]) ? esc($data['target_capaian'][$tahun]) : '-' ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
  function filterTahun() {
    const mulai = parseInt(document.getElementById('tahunMulai').value);
    const selesai = parseInt(document.getElementById('tahunSelesai').value);
    
    if (isNaN(mulai) || isNaN(selesai) || mulai > selesai) {
      alert('Rentang tahun tidak valid!');
      return;
    }
    
    document.querySelectorAll('.tahun-col').forEach(el => {
      el.style.display = 'none';
    });
    
    for (let tahun = mulai; tahun <= selesai; tahun++) {
      document.querySelectorAll('.tahun-' + tahun).forEach(el => {
        el.style.display = '';
      });
    }
  }
</script>

<?= $this->include('user/templates/footer'); ?>

</body>
</html>
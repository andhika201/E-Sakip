<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PK Pengawas</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  
  <?= $this->include('user/templates/header'); ?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        PERJANJIAN KINERJA PENGAWAS
      </h4>
      
      <!-- Filter -->
      <div class="row justify-content-center mb-4">
        <div class="col-12 col-xl-10">
          <form method="GET" action="<?= base_url('pk_pengawas') ?>" class="row g-2 justify-content-center align-items-center">
            <div class="col-12 col-md-5">
              <select name="opd_id" class="form-select w-100" onchange="this.form.submit()">
                <option value="all">Semua Perangkat Daerah</option>
                <?php foreach ($opdList as $opd): ?>
                  <option value="<?= $opd['id'] ?>" <?= ($selected_opd == $opd['id']) ? 'selected' : '' ?>>
                    <?= esc($opd['nama_opd']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="col-12 col-md-3">
              <select name="tahun" class="form-select w-100" onchange="this.form.submit()">
                <?php if(empty($available_years)): ?>
                  <option value="">Belum ada data tahun</option>
                <?php else: ?>
                  <?php foreach ($available_years as $year): ?>
                    <option value="<?= $year ?>" <?= ($selected_tahun == $year) ? 'selected' : '' ?>><?= $year ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            
            <div class="col-12 col-md-auto">
              <noscript><button type="submit" class="btn btn-success w-100"><i class="fas fa-filter me-1"></i> Filter</button></noscript>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Tabel PK OPD -->
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle table-hover">
          <thead class="table-success">
            <tr>
              <th style="width: 5%;">No</th>
              <th>Perangkat Daerah</th>
              <th>Sasaran</th>
              <th>Indikator Sasaran</th>
              <th>Target</th>
              <th>Satuan</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($pkData)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted p-4">Tidak ada data untuk filter yang dipilih.</td>
              </tr>
            <?php else: ?>
              <?php $no = 1; foreach ($pkData as $item): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td class="text-start"><?= esc($item['opd']) ?></td>
                  <td class="text-start"><?= esc($item['sasaran']) ?></td>
                  <td class="text-start"><?= esc($item['indikator']) ?></td>
                  <td><?= esc($item['target']) ?></td>
                  <td><?= esc($item['satuan']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?= $this->include('user/templates/footer'); ?>
</body>
</html>
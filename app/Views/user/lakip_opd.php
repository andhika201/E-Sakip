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
      <h4 class="fw-bold text-center text-success mb-4 text-uppercase">
        LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAHAN OPD
      </h4>
      
      <!-- Filter -->
      <div class="row justify-content-center mb-4">
          <div class="col-12 col-xl-10">
              <form method="GET" action="<?= base_url('lakip_opd') ?>" class="row g-2 justify-content-center align-items-center">
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
                          <option value="">Pilih Tahun</option>
                          <?php foreach ($available_years as $year): ?>
                              <option value="<?= $year ?>" <?= ($selected_tahun == $year) ? 'selected' : '' ?>><?= $year ?></option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  
                  <div class="col-12 col-md-auto">
                      <button type="submit" class="btn btn-success w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                  </div>
              </form>
          </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center table-hover">
          <thead class="table-success">
            <tr>
              <th>No</th>
              <th>Perangkat Daerah</th>
              <th>Sasaran</th>
              <th>Indikator</th>
              <th>Capaian Tahun Sebelumnya</th>
              <th>Target Tahun Ini</th>
              <th>Capaian Tahun Ini</th>
            </tr>
          </thead>
        <tbody>
          <?php if (empty($lakipOpdData)): ?>
              <tr>
                  <td colspan="7" class="text-center text-muted p-4">Tidak ada data LAKIP untuk filter yang dipilih.</td>
              </tr>
          <?php else: ?>
              <?php $no = 1; foreach ($lakipOpdData as $item): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-start"><?= esc($item['opd'] ?? '-') ?></td>
                    <td class="text-start"><?= esc($item['sasaran']) ?></td>
                    <td class="text-start"><?= esc($item['indikator']) ?></td>
                    <td><?= esc($item['capaian_sebelumnya']) ?></td>
                    <td><?= esc($item['target_tahun_ini']) ?></td>
                    <td><?= esc($item['capaian_tahun_ini']) ?></td>
                  </tr>
                <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>

<?= $this->include('user/templates/footer'); ?>

</body>
</html>
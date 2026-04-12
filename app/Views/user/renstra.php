<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RENSTRA</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  
  <?= $this->include('user/templates/header'); ?>
  
<main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA STRATEGIS
      </h4>
  
      <!-- Filter OPD -->
      <div class="row justify-content-center mb-4">
        <div class="col-12 col-md-6 col-lg-5">
            <form method="GET" action="<?= base_url('renstra') ?>" class="d-flex w-100">
                <select name="opd_id" class="form-select" onchange="this.form.submit()">
                    <option value="all">Semua Perangkat Daerah</option>
                    <?php foreach ($opdList as $opd): ?>
                        <option value="<?= $opd['id'] ?>" <?= ($selected_opd == $opd['id']) ? 'selected' : '' ?>>
                            <?= esc($opd['nama_opd']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Tombol Submit Disembunyikan karena sudah ada onchange -->
                <noscript><button type="submit" class="btn btn-success ms-2">Filter</button></noscript>
            </form>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center" id="renstraTable">
          <thead class="table-success">
            <tr>
              <th rowspan="2" class="align-middle">No</th>
              <th rowspan="2" class="align-middle">Perangkat Daerah</th>
              <th rowspan="2" class="align-middle">Sasaran</th>
              <th rowspan="2" class="align-middle">Indikator Sasaran</th>
              <th colspan="<?= count($tahunList) ?>">Target Capaian Per Tahun</th>
            </tr>
            <tr>
              <?php foreach ($tahunList as $tahun): ?>
                <th class="tahun-col tahun-<?= $tahun ?>"><?= $tahun ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($renstraData)): ?>
              <tr>
                <td colspan="<?= 4 + count($tahunList) ?>" class="text-center text-muted p-4">Tidak ada data Renstra.</td>
              </tr>
            <?php else: ?>
              <?php $no = 1; foreach ($renstraData as $row): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td class="text-start"><?= esc($row['opd']) ?></td>
                  <td class="text-start"><?= esc($row['sasaran']) ?></td>
                  <td class="text-start">
                    <?= esc($row['indikator']) ?>
                    <?php if(!empty($row['satuan'])): ?>
                        <br><small class="text-muted">(<?= esc($row['satuan']) ?>)</small>
                    <?php endif; ?>
                  </td>
                  <?php foreach ($tahunList as $tahun): ?>
                    <td><?= esc($row['target_capaian'][$tahun] ?? '-') ?></td>
                  <?php endforeach; ?>
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
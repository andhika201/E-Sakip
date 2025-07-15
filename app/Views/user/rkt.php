<?= $this->include('user/templates/header'); ?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA KINERJA TAHUNAN
      </h4>

      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-success">
            <tr>
              <th style="width: 5%;">No</th>
              <th>Sasaran</th>
              <th>Indikator Sasaran</th>
              <th>Tahun</th>
              <th>Target</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; ?>
            <?php foreach ($grouped_rkt as $sasaran): ?>
              <?php foreach ($sasaran['indikator'] as $indikator): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= esc($sasaran['sasaran']) ?></td>
                  <td><?= esc($indikator['indikator_sasaran']) ?></td>
                  <td><?= esc($indikator['tahun']) ?></td>
                  <td><?= esc($indikator['target']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?= $this->include('user/templates/footer'); ?>

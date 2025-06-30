<?= $this->include('user/templates/header'); ?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA KINERJA
      </h4>

      <div class="table-responsive">
        <table class="table table-bordered align-middle tect-center">
          <thead class="table-success">
            <tr>
              <th>No</th>
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

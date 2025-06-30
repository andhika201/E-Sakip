<?= $this->include('user/templates/header'); ?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        INDIKATOR KINERJA UTAMA (IKU) OPD
      </h4>

      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-success">
            <tr>
              <th>No</th>
              <th>Urusan</th>
              <th>Program</th>
              <th>Indikator</th>
              <th>Satuan</th>
              <th>Target</th>
              <th>Capaian</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($ikuOpdData)): ?>
              <?php $no = 1; foreach ($ikuOpdData as $data): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= esc($data['urusan']) ?></td>
                  <td><?= esc($data['program']) ?></td>
                  <td><?= esc($data['indikator']) ?></td>
                  <td><?= esc($data['satuan']) ?></td>
                  <td><?= esc($data['target']) ?></td>
                  <td><?= esc($data['capaian']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-muted">Belum ada data IKU yang tersedia.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?= $this->include('user/templates/footer'); ?>
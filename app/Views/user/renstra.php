<?= $this->include('user/templates/header'); ?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center">
  <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        RENCANA STRATEGIS
      </h4>
  
      <!-- Filter OPD -->
      <div class="row mb-3">
        <div class="col-md-4 d-flex align-items-center">
          <label for="filterOpd" class="me-2 fw-bold">OPD:</label>
          <select id="filterOpd" class="form-select">
            <?php foreach ($opdList as $opd): ?>
              <option value="<?= esc($opd) ?>"><?= esc($opd) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" onclick="filterOpd()">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center" id="renstraTable">
          <thead class="table-success">
            <tr>
              <th>No</th>
              <th>Sasaran</th>
              <th>Indikator Sasaran</th>
              <th colspan="<?= count($tahunList) ?>">Target Capaian Per Tahun</th>
            </tr>
            <tr>
              <th colspan="3"></th>
              <?php foreach ($tahunList as $tahun): ?>
                <th class="tahun-col tahun-<?= $tahun ?>"><?= $tahun ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($renstraData as $row): ?>
              <tr data-opd="<?= esc($row['opd']) ?>">
                <td><?= $no++ ?></td>
                <td><?= esc($row['sasaran']) ?></td>
                <td><?= esc($row['indikator']) ?></td>
                <?php foreach ($tahunList as $tahun): ?>
                  <td><?= esc($row['target_capaian'][$tahun] ?? '-') ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</main>

<script>
  function filterOpd() {
    const selectedOpd = document.getElementById('filterOpd').value;
    const rows = document.querySelectorAll('#renstraTable tbody tr');

    rows.forEach(row => {
      if (row.getAttribute('data-opd') === selectedOpd) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  // Jalankan saat halaman pertama dibuka agar default filter aktif
  document.addEventListener('DOMContentLoaded', filterOpd);
</script>

<?= $this->include('user/templates/footer'); ?>
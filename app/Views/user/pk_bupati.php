<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PK Bupati</title>
  <?= $this->include('user/templates/style.php'); ?>
</head>
<body>
  
  <?= $this->include('user/templates/header'); ?>
  
  <main class="flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="container my-5" style="max-width: 1700px;">
    <div class="bg-white p-4 rounded shadow-sm">
      <h4 class="fw-bold text-center text-success mb-4">
        PERJANJIAN KINERJA KABUPATEN
      </h4>
      
      <!-- Filter -->
      <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
          <div class="d-flex gap-2 flex">
              <select class="form-select" onchange="filterByYear(this.value)">
                  <option value="">Semua Tahun</option>
                  <?php if (!empty($available_years)): ?>
                      <?php foreach ($available_years as $year): ?>
                      <option value="<?= $year ?>" <?= (isset($selected_year) && $selected_year == $year) ? 'selected' : '' ?>><?= $year ?></option>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </select>
              <?php if (isset($selected_year) && !empty($selected_year)): ?>
                  <a href="<?= base_url('/pk_bupati') ?>" class="btn btn-outline-secondary d-flex align-items-center">
                      <i class="fas fa-times me-2"></i> RESET
                  </a>
              <?php endif; ?>
          </div>
      </div>
      
      <!-- Tabel PK Bupati -->
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
          <thead class="table-success">
            <tr>
                <th class="border p-2" style="width: 50px;">NO</th>
                <th class="border p-2" style="width: 250px;">MISI RPJMD</th>
                <th class="border p-2" style="width: 250px;">SASARAN PK</th>
                <th class="border p-2" style="width: 250px;">INDIKATOR</th>
                <th class="border p-2" style="width: 120px;">TARGET</th>
                <th class="border p-2" style="width: 200px;">PROGRAM</th>
                <th class="border p-2" style="width: 100px;">TANGGAL</th>
            </tr>
          </thead>
            <tbody>
              <?php $no = 1; ?>
              <?php if (!empty($pk_data)): ?>
                  <?php foreach ($pk_data as $pk): ?>
                      <?php 
                      // Hitung total indikator dan program
                      $totalIndikator = 0;
                      foreach ($pk['sasaran'] as $sasaran) {
                          $totalIndikator += count($sasaran['indikator']);
                      }
                      $totalProgram = count($pk['program']);
                      
                      // Buat array untuk indikator
                      $indikatorList = [];
                      foreach ($pk['sasaran'] as $sasaran) {
                          foreach ($sasaran['indikator'] as $indikator) {
                              $indikatorList[] = [
                                  'sasaran' => $sasaran,
                                  'indikator' => $indikator
                              ];
                          }
                      }
                      
                      // Total rows sesuai dengan data yang ada
                      $maxRows = max($totalIndikator, $totalProgram, 1);
                      ?>
                      
                      <!-- Render rows untuk indikator -->
                      <?php for ($i = 0; $i < $totalIndikator; $i++): ?>
                          <tr>
                              <?php if ($i == 0): ?>
                                  <!-- NO dengan rowspan untuk indikator -->
                                  <td class="border p-2" style="width: 50px;" rowspan="<?= $totalIndikator ?>"><?= $no ?></td>
                                  
                                  <!-- MISI RPJMD dengan rowspan untuk indikator -->
                                  <td class="border p-2 text-start" style="width: 250px;" rowspan="<?= $totalIndikator ?>"><?= esc($pk['misi_rpjmd'] ?? '-') ?></td>
                              <?php endif; ?>
                              
                              <?php 
                              $currentItem = $indikatorList[$i];
                              $currentSasaran = $currentItem['sasaran'];
                              $currentIndikator = $currentItem['indikator'];
                              
                              // Hitung apakah ini indikator pertama dari sasaran ini
                              $isFirstOfSasaran = true;
                              $sasaranRowspan = 0;
                              
                              // Hitung berapa indikator untuk sasaran ini
                              foreach ($indikatorList as $j => $item) {
                                  if ($item['sasaran']['sasaran_id'] === $currentSasaran['sasaran_id']) {
                                      $sasaranRowspan++;
                                      if ($j < $i) {
                                          $isFirstOfSasaran = false;
                                      }
                                  }
                              }
                              ?>
                              
                              <!-- SASARAN PK -->
                              <?php if ($isFirstOfSasaran): ?>
                                  <td class="border p-2 text-start" style="width: 250px;" rowspan="<?= $sasaranRowspan ?>"><?= esc($currentSasaran['sasaran']) ?></td>
                              <?php endif; ?>
                              
                              <!-- INDIKATOR dan TARGET -->
                              <td class="border p-2 text-start" style="width: 250px;"><?= esc($currentIndikator['indikator']) ?></td>
                              <td class="border p-2" style="width: 120px;"><?= esc($currentIndikator['target']) ?></td>
                              
                              <!-- PROGRAM dan ANGGARAN dengan rowspan proporsional -->
                              <?php if ($totalProgram > 0): ?>
                                  <?php
                                  // Hitung rowspan untuk setiap program
                                  $programRowspan = $totalIndikator > 0 ? max(1, floor($totalIndikator / $totalProgram)) : 1;
                                  $extraRows = $totalProgram > 0 ? $totalIndikator % $totalProgram : 0;
                                  
                                  // Tentukan program mana yang ditampilkan pada baris ini
                                  $currentProgramIndex = $programRowspan > 0 ? floor($i / $programRowspan) : 0;
                                  
                                  // Jika ada sisa baris, program terakhir mendapat tambahan
                                  if ($extraRows > 0 && $currentProgramIndex >= $totalProgram - $extraRows) {
                                      $adjustedRowspan = $programRowspan + 1;
                                      $currentProgramIndex = $totalProgram - 1 - ($adjustedRowspan > 0 ? floor(($totalIndikator - 1 - $i) / $adjustedRowspan) : 0);
                                  }
                                  
                                  // Pastikan currentProgramIndex tidak melebihi batas array
                                  $currentProgramIndex = min($currentProgramIndex, $totalProgram - 1);
                                  
                                  // Cek apakah ini baris pertama untuk program ini
                                  $isFirstRowOfProgram = false;
                                  if ($currentProgramIndex < $totalProgram && $currentProgramIndex >= 0) {
                                      if ($extraRows > 0 && $currentProgramIndex >= $totalProgram - $extraRows) {
                                          // Program yang mendapat tambahan baris
                                          $startRow = ($totalProgram - $extraRows) * $programRowspan + ($currentProgramIndex - ($totalProgram - $extraRows)) * ($programRowspan + 1);
                                          $isFirstRowOfProgram = ($i == $startRow);
                                          $actualRowspan = $programRowspan + 1;
                                      } else {
                                          // Program normal
                                          $startRow = $currentProgramIndex * $programRowspan;
                                          $isFirstRowOfProgram = ($i == $startRow);
                                          $actualRowspan = $programRowspan;
                                      }
                                  }
                                  ?>
                                  
                                  <?php if ($isFirstRowOfProgram && $currentProgramIndex < $totalProgram): ?>
                                      <td class="border p-2 text-start" style="width: 200px;" rowspan="<?= $actualRowspan ?>"><?= esc($pk['program'][$currentProgramIndex]['program_kegiatan']) ?></td>
                                  <?php endif; ?>
                              <?php else: ?>
                                  <!-- Jika tidak ada program -->
                                  <?php if ($i == 0): ?>
                                      <td class="border p-2" style="width: 200px;" rowspan="<?= $totalIndikator ?>">-</td>
                                      <td class="border p-2" style="width: 180px;" rowspan="<?= $totalIndikator ?>">-</td>
                                  <?php endif; ?>
                              <?php endif; ?>
                              
                              <?php if ($i == 0): ?>
                                  <!-- TANGGAL dengan rowspan untuk indikator -->
                                  <td class="border p-2" style="width: 100px;" rowspan="<?= $totalIndikator ?>"><?= date('d/m/Y', strtotime($pk['tanggal'])) ?></td>
                                
                              <?php endif; ?>
                          </tr>
                      <?php endfor; ?>
                      
                      <?php $no++; ?>
                  <?php endforeach; ?>
              <?php else: ?>
                  <tr>
                      <td colspan="10" class="border p-4 text-center text-muted">
                          <i class="fas fa-info-circle me-2"></i>
                          Belum ada data PK Bupati. <a href="<?= base_url('adminkab/pk_bupati/tambah') ?>" class="text-success">Tambah data pertama</a>
                      </td>
                  </tr>
              <?php endif; ?>
            </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?= $this->include('user/templates/footer'); ?>
  <!-- JavaScript untuk  filter -->
  <script>
    function filterByYear(tahun) {
        if (tahun) {
            window.location.href = '<?= base_url("/pk_bupati") ?>?tahun=' + tahun;
        } else {
            window.location.href = '<?= base_url("/pk_bupati") ?>';
        }
    }
  </script>
</body>
</html>
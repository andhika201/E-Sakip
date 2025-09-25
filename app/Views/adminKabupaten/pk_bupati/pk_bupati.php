<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PK Bupati</title>
  <!-- Style -->
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">PERJANJIAN KINERJA BUPATI</h2>

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex-fill">
                <select class="form-select" onchange="filterByYear(this.value)">
                    <option value="">Semua Tahun</option>
                    <?php if (!empty($available_years)): ?>
                        <?php foreach ($available_years as $year): ?>
                        <option value="<?= $year ?>" <?= (isset($selected_year) && $selected_year == $year) ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php if (isset($selected_year) && !empty($selected_year)): ?>
                    <a href="<?= base_url('adminkab/pk_bupati') ?>" class="btn btn-outline-secondary d-flex align-items-center">
                        <i class="fas fa-times me-2"></i> RESET
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= base_url('adminkab/pk_bupati/tambah') ?>" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-1"></i> TAMBAH
                </a>
            </div>
        </div>

    <!-- Info Filter Aktif -->
    <?php if (isset($selected_year) && !empty($selected_year)): ?>
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        Menampilkan data PK Bupati untuk tahun  <strong> <?= $selected_year ?></strong>
        <?php if (empty($pk_data)): ?>
        - Tidak ada data yang ditemukan untuk tahun ini.
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Info jika tidak ada data tahun -->
    <?php if (empty($available_years)): ?>
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Belum ada data PK Bupati dengan tanggal yang valid. Silakan <a href="<?= base_url('adminkab/pk_bupati/tambah') ?>" class="alert-link">tambah data pertama</a>.
    </div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
            <tr>
                <th class="border p-2" style="width: 50px;">NO</th>
                <th class="border p-2" style="width: 250px;">MISI RPJMD</th>
                <th class="border p-2" style="width: 250px;">SASARAN PK</th>
                <th class="border p-2" style="width: 250px;">INDIKATOR</th>
                <th class="border p-2" style="width: 120px;">TARGET</th>
                <th class="border p-2" style="width: 200px;">PROGRAM</th>
                <th class="border p-2" style="width: 180px;">ANGGARAN</th>
                <th class="border p-2" style="width: 100px;">TANGGAL</th>
                <th class="border p-2" style="width: 80px;">FILE</th>
                <th class="border p-2" style="width: 120px;">ACTION</th>
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
                                    <td class="border p-2" style="width: 180px;" rowspan="<?= $actualRowspan ?>">Rp <?= number_format($pk['program'][$currentProgramIndex]['anggaran'], 0, ',', '.') ?></td>
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
                                
                                <!-- FILE dengan rowspan untuk indikator -->
                                <td class="border p-2" style="width: 80px;" rowspan="<?= $totalIndikator ?>">
                                    <a href="<?= base_url('adminkab/pk_bupati/cetak/' . $pk['pk_id']) ?>" class="btn btn-sm btn-outline-primary w-100 d-flex align-items-center justify-content-center" target="_blank" style="padding: 6px 4px; font-size: 15px;">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>    
                                </td>

                                <td class="border p-2" style="width: 120px;" rowspan="<?= $totalIndikator ?>">
                                    <div class="d-flex flex-column gap-1 w-100">
                                        <a href="<?= base_url('adminkab/pk_bupati/edit/' . $pk['pk_id']) ?>" class="btn btn-success btn-sm w-100 d-flex align-items-center justify-content-center" style="padding: 6px 8px; font-size: 15px;">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a>
                                        <button class="btn btn-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="padding: 6px 8px; font-size: 15px;" onclick="confirmDelete(<?= $pk['pk_id'] ?>, '<?= esc($pk['nama']) ?>')">
                                            <i class="fas fa-trash me-2"></i>Hapus
                                        </button>
                                    </div>
                                </td>
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
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- JavaScript untuk konfirmasi delete dan filter -->
  <script>
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus PK Bupati ini?\n\nData yang dihapus tidak dapat dikembalikan.')) {
            window.location.href = '<?= base_url("adminkab/pk_bupati/delete/") ?>' + id;
        }
    }

    function filterByYear(tahun) {
        if (tahun) {
            window.location.href = '<?= base_url("adminkab/pk_bupati") ?>?tahun=' + tahun;
        } else {
            window.location.href = '<?= base_url("adminkab/pk_bupati") ?>';
        }
    }
  </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PK Bupati - e-SAKIP</title>
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
            <select class="form-select">
            <option value="">TAHUN</option>
            <option>2019</option>
            <option>2020</option>
            <option>2021</option>
            <option>2022</option>
            <option>2023</option>
            <option>2024</option>
            </select>
            <a href="" class="btn btn-success d-flex align-items-center">
            <i class="fas fa-filter me-2"></i> FILTER
            </a>
                </div>
                <div>
                <a href="<?= base_url('adminkab/pk_bupati/tambah') ?>" class="btn btn-success d-flex align-items-center">
            <i class="fas fa-plus me-1"></i> TAMBAH
            </a>
        </div>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
            <tr>
                <th class="border p-2">NO</th>
                <th class="border p-2">NAMA BUPATI</th>
                <th class="border p-2">MISI RPJMD</th>
                <th class="border p-2">SASARAN PK</th>
                <th class="border p-2">INDIKATOR</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">PROGRAM</th>
                <th class="border p-2">ANGGARAN</th>
                <th class="border p-2">TANGGAL</th>
                <th class="border p-2">FILE</th>
                <th class="border p-2">ACTION</th>
            </tr>
            </thead>
            <tbody>
            <?php $no = 1; ?>
            <?php if (!empty($pk_data)): ?>
                <?php foreach ($pk_data as $pk): ?>
                    <?php 
                    // Hitung total rows untuk PK ini
                    $totalSasaran = count($pk['sasaran']);
                    $totalProgram = count($pk['program']);
                    $totalIndikator = 0;
                    
                    foreach ($pk['sasaran'] as $sasaran) {
                        $totalIndikator += count($sasaran['indikator']);
                    }
                    
                    // Total rows adalah max antara total indikator dan total program
                    $totalRows = max($totalIndikator, $totalProgram, 1);
                    
                    $currentIndikatorIndex = 0;
                    $currentProgramIndex = 0;
                    ?>
                    
                    <?php for ($row = 0; $row < $totalRows; $row++): ?>
                        <tr>
                            <?php if ($row == 0): ?>
                                <!-- NO dengan rowspan -->
                                <td class="border p-2" rowspan="<?= $totalRows ?>"><?= $no ?></td>
                                
                                <!-- NAMA BUPATI dengan rowspan -->
                                <td class="border p-2" rowspan="<?= $totalRows ?>"><?= esc($pk['nama']) ?></td>
                                
                                <!-- MISI RPJMD dengan rowspan -->
                                <td class="border p-2" rowspan="<?= $totalRows ?>"><?= esc($pk['misi_rpjmd'] ?? '-') ?></td>
                            <?php endif; ?>
                            
                            <?php 
                            // Cari sasaran dan indikator untuk row ini
                            $currentSasaran = null;
                            $currentIndikator = null;
                            $sasaranRowspan = 1;
                            $showSasaran = false;
                            
                            $tempIndikatorIndex = 0;
                            foreach ($pk['sasaran'] as $sasaranIndex => $sasaran) {
                                foreach ($sasaran['indikator'] as $indikatorIndex => $indikator) {
                                    if ($tempIndikatorIndex == $currentIndikatorIndex) {
                                        $currentSasaran = $sasaran;
                                        $currentIndikator = $indikator;
                                        
                                        // Check if this is the first indikator of this sasaran
                                        if ($indikatorIndex == 0) {
                                            $showSasaran = true;
                                            $sasaranRowspan = count($sasaran['indikator']);
                                        }
                                        break 2;
                                    }
                                    $tempIndikatorIndex++;
                                }
                            }
                            ?>
                            
                            <!-- SASARAN PK -->
                            <?php if ($showSasaran && $currentSasaran): ?>
                                <td class="border p-2" rowspan="<?= $sasaranRowspan ?>"><?= esc($currentSasaran['sasaran']) ?></td>
                            <?php endif; ?>
                            
                            <!-- INDIKATOR -->
                            <td class="border p-2"><?= $currentIndikator ? esc($currentIndikator['indikator']) : '-' ?></td>
                            
                            <!-- TARGET -->
                            <td class="border p-2"><?= $currentIndikator ? esc($currentIndikator['target']) : '-' ?></td>
                            
                            <!-- PROGRAM -->
                            <?php if (isset($pk['program'][$currentProgramIndex])): ?>
                                <td class="border p-2"><?= esc($pk['program'][$currentProgramIndex]['program_kegiatan']) ?></td>
                                <td class="border p-2">Rp <?= number_format($pk['program'][$currentProgramIndex]['anggaran'], 0, ',', '.') ?></td>
                            <?php else: ?>
                                <td class="border p-2">-</td>
                                <td class="border p-2">-</td>
                            <?php endif; ?>
                            
                            <?php if ($row == 0): ?>
                                <!-- TANGGAL dengan rowspan -->
                                <td class="border p-2" rowspan="<?= $totalRows ?>"><?= date('d/m/Y', strtotime($pk['tanggal'])) ?></td>
                                
                                <!-- FILE dengan rowspan -->
                                <td class="border p-2" rowspan="<?= $totalRows ?>">
                                    <a href="<?= base_url('adminkab/pk_bupati/cetak/' . $pk['pk_id']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                </td>
                                
                                <!-- ACTION dengan rowspan -->
                                <td class="border p-2" rowspan="<?= $totalRows ?>">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= base_url('adminkab/pk_bupati/edit/' . $pk['pk_id']) ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?= $pk['pk_id'] ?>, '<?= esc($pk['nama']) ?>')" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                        
                        <?php 
                        // Increment indikator index jika ada indikator
                        if ($currentIndikator) {
                            $currentIndikatorIndex++;
                        }
                        
                        // Increment program index jika ada program
                        if (isset($pk['program'][$currentProgramIndex])) {
                            $currentProgramIndex++;
                        }
                        ?>
                    <?php endfor; ?>
                    
                    <?php $no++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center p-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Belum ada data PK Bupati</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <!-- JavaScript untuk konfirmasi delete -->
  <script>
    function confirmDelete(id, nama) {
        if (confirm('Apakah Anda yakin ingin menghapus PK Bupati "' + nama + '"?\n\nData yang dihapus tidak dapat dikembalikan.')) {
            window.location.href = '<?= base_url("adminkab/pk_bupati/delete/") ?>' + id;
        }
    }

    // Filter berdasarkan tahun
    document.addEventListener('DOMContentLoaded', function() {
        const yearSelect = document.querySelector('select.form-select');
        const filterBtn = document.querySelector('.btn-success[href=""]');
        
        if (filterBtn) {
            filterBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedYear = yearSelect.value;
                
                if (selectedYear) {
                    // Implementasi filter tahun jika diperlukan
                    console.log('Filter tahun:', selectedYear);
                    // window.location.href = '<?= base_url("adminkab/pk_bupati?tahun=") ?>' + selectedYear;
                } else {
                    alert('Silakan pilih tahun terlebih dahulu');
                }
            });
        }
    });
  </script>
</body>
</html>

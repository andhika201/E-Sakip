<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kegiatan PK - e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Content Wrapper -->
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
    
  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">Daftar Kegiatan PK</h2>

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Cari Kegiatan OPD..." id="searchInput">
                </div>
            </div>
            <div>
                <a href="<?= base_url('adminopd/kegiatan_opd/tambah') ?>" class="btn btn-success d-flex align-items-center">
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
                    <th class="border p-2">KEGIATAN</th>
                    <th class="border p-2">ANGGARAN</th>
                    <th class="border p-2">ACTION</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($kegiatans)) : ?>
                    <?php $no = 1; ?>
                    <?php foreach ($kegiatans as $kegiatan) : ?>
                    <tr>
                        <td class="border p-2"><?= $no++ ?></td>
                        <td class="border p-2 text-start"><?= esc($kegiatan['kegiatan']) ?></td>
                        <td class="border p-2">Rp <?= number_format($kegiatan['anggaran'], 0, ',', '.') ?></td>
                        <td class="border p-2">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <a href="<?= base_url('adminopd/kegiatan_opd/edit/' . $kegiatan['id']) ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $kegiatan['id'] ?>)">
                                    <i class="fas fa-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="border p-3 text-muted">
                            <i class="fas fa-info-circle me-2"></i>Belum ada data kegiatan PK. <a href="<?= base_url('adminopd/kegiatan_opd/tambah') ?>" class="text-success">Tambah data pertama</a>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </main>

  <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div> <!-- End of Content Wrapper -->

  <script>
    // Function to confirm delete
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus kegiatan ini?')) {
            window.location.href = '<?= base_url('adminopd/kegiatan_opd/delete/') ?>' + id;
        }
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const kegiatanText = row.cells[1]?.textContent.toLowerCase() || '';
            const anggaranText = row.cells[2]?.textContent.toLowerCase() || '';
            
            if (kegiatanText.includes(searchValue) || anggaranText.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Auto dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 3500);
  </script>
</body>
</html>

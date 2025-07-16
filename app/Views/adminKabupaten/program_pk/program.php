<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Program PK - e-SAKIP</title>
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
        <h2 class="h3 fw-bold text-success text-center mb-4">Daftar Program PK</h2>

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
            <div class="d-flex align-items-center gap-2 col-md-4">
                <input type="text" class="form-control" placeholder="Cari Program PK..." id="searchInput">
                <button class="btn btn-success" id="searchButton"><i class="fas fa-search"></i></button>
            </div>
            <div>
                <a href="<?= base_url('adminkab/program_pk/tambah') ?>" class="btn btn-success d-flex align-items-center">
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
                    <th class="border p-2">PROGRAM</th>
                    <th class="border p-2">ANGGARAN</th>
                    <th class="border p-2 " style="max-width: 200px; width:1%; white-space:nowrap;" >ACTION</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($programs)) : ?>
                    <?php $no = 1; ?>
                    <?php foreach ($programs as $program) : ?>
                    <tr>
                        <td class="border p-2"><?= $no++ ?></td>
                        <td class="border p-2 text-start"><?= esc($program['program_kegiatan']) ?></td>
                        <td class="border p-2">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                        <td class="border p-2" style="max-width: 200px; width:1%; white-space:nowrap;">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <a href="<?= base_url('adminkab/program_pk/edit/' . $program['id']) ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <a href="<?= base_url('adminkab/program_pk/delete/' . $program['id']) ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus program ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="border p-3 text-muted">
                            <i class="fas fa-info-circle me-2"></i>Belum ada data program PK. <a href="<?= base_url('adminkab/program_pk/tambah') ?>" class="text-success">Tambah data pertama</a>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  <script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const programText = row.cells[1]?.textContent.toLowerCase() || '';
            const anggaranText = row.cells[2]?.textContent.toLowerCase() || '';
            
            if (programText.includes(searchValue) || anggaranText.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Search button
    document.getElementById('searchButton').addEventListener('click', function() {
        const searchInput = document.getElementById('searchInput');
        searchInput.focus();
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

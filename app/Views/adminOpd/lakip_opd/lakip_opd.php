<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LAKIP OPD - e-SAKIP</title>
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
        <h2 class="h3 fw-bold text-success text-center mb-4">LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH</h2>

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                  <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                    <li><?= $error ?></li>
                  <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('validation')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                  <?php foreach (session()->getFlashdata('validation') as $error) : ?>
                    <li><?= $error ?></li>
                  <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex flex-column flex-md-row gap-3 flex-fill">
                <select id="tahun_filter" class="form-select border-secondary w-50" onchange="filterData()">
                    <option value="">Semua Tahun</option>
                    <?php if (!empty($availableYears)): ?>
                        <?php foreach ($availableYears as $year): ?>
                            <option value="<?= $year ?>" <?= (isset($filters['tahun']) && $filters['tahun'] == $year) ? 'selected' : '' ?>>
                                <?= $year ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select id="status_filter" class="form-select border-secondary w-50" onchange="filterData()">
                    <option value="">Semua Status</option>
                    <option value="selesai" <?= (isset($filters['status']) && $filters['status'] == 'selesai') ? 'selected' : '' ?>>Selesai</option>
                    <option value="draft" <?= (isset($filters['status']) && $filters['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2">
                <a href="<?= base_url('adminopd/lakip_opd/tambah') ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Tambah
                </a>
            </div>
        </div>

        <!-- Tabel -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center small">
                <thead class="table-success">
                <tr>
                    <th class="border p-2">NO</th>
                    <th class="border p-2">TAHUN LAPORAN</th>
                    <th class="border p-2">JUDUL LAPORAN</th>
                    <th class="border p-2">TANGGAL UPLOAD</th>
                    <th class="border p-2">FILE</th>
                    <th class="border p-2">STATUS</th>
                    <th class="border p-2">ACTION</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($lakips) && is_array($lakips)): ?>
                    <?php 
                    $no = 1; 
                    foreach ($lakips as $lakip): 
                    ?>
                    <tr>
                        <td class="border p-2"><?= $no++ ?></td>
                        <td class="border p-2">
                            <?= !empty($lakip['tanggal_laporan']) ? date('Y', strtotime($lakip['tanggal_laporan'])) : '-' ?>
                        </td>
                        <td class="border p-2 text-start"><?= esc($lakip['judul']) ?></td>
                        <td class="border p-2">
                            <?= !empty($lakip['created_at']) ? date('d/m/Y', strtotime($lakip['created_at'])) : '-' ?>
                        </td>
                        <td class="border p-2">
                            <?php if (!empty($lakip['file'])): ?>
                                <a href="<?= base_url('adminopd/lakip_opd/download/' . $lakip['id']) ?>" class="text-primary" title="<?= esc($lakip['judul']) ?>">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="border p-2">
                            <button class="badge border-0 <?= $lakip['status'] == 'selesai' ? 'bg-success' : 'bg-warning text-light' ?>" 
                                    onclick="toggleStatus(<?= $lakip['id'] ?>)" 
                                    style="cursor: pointer;" 
                                    title="Klik untuk mengubah status">
                                <?= ucfirst($lakip['status']) ?>
                            </button>
                        </td>
                        <td class="border p-2 align-middle text-center">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <a href="<?= base_url('adminopd/lakip_opd/edit/' . $lakip['id']) ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $lakip['id'] ?>)">
                                    <i class="fas fa-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="border p-4 text-center text-muted">
                            <i class="fas fa-folder-open me-2"></i>
                            Belum ada data LAKIP OPD
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
function filterData() {
    const tahun = document.getElementById('tahun_filter').value;
    const status = document.getElementById('status_filter').value;

    let url = '<?= base_url('adminopd/lakip_opd') ?>';
    const params = new URLSearchParams();
    
    if (tahun) params.append('tahun', tahun);
    if (status) params.append('status', status);
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    window.location.href = url;
}

function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data LAKIP ini?')) {
        // Create form for delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('adminopd/lakip_opd/delete/') ?>' + id;

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '<?= csrf_token() ?>';
        csrfInput.value = '<?= csrf_hash() ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Function to toggle status via AJAX
function toggleStatus(lakipId) {
    if (confirm('Apakah Anda yakin ingin mengubah status LAKIP ini?')) {
        fetch('<?= base_url('adminopd/lakip_opd/update-status') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({
                id: lakipId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated status
                window.location.reload();
            } else {
                alert('Gagal mengubah status: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengubah status');
        });
    }
}
</script>
</body>
</html>

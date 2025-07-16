<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RKT - e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

<!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA KERJA TAHUNAN</h2>

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div class="d-flex gap-2 flex-fill">
                <select class="form-select">
                <option value="">TAHUN</option>
                <option>2020</option>
                <option>2021</option>
                <option>2022</option>
                <option>2023</option>
                <option>2024</option>
                <option>2025</option>
                </select>
                <select class="form-select">
                <option value="">OPD</option>
                <option>Dinas Pendidikan</option>
                <option>Dinas Kesehatan</option>
                <option>Dinas Sosial</option>
                </select>
                <a href="" class="btn btn-success d-flex align-items-center">
                <i class="fas fa-filter me-2"></i> FILTER
                </a>
            </div>
        <div>
            <a href="<?= base_url('adminkab/rkt/tambah') ?>" class="btn btn-success d-flex align-items-center">
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
                <th class="border p-2">SASARAN</th>
                <th class="border p-2">INDIKATOR SASARAN</th>
                <th class="border p-2">SATUAN</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">ACTION</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="border p-2">1</td>
                <td class="border p-2">Pembangunan Sekolah Dasar</td>
                <td class="border p-2">Jumlah SD yang dibangun</td>
                <td class="border p-2">unit</td>
                <td class="border p-2">5 unit</td>
                <td class="border p-2">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <a href="<?= base_url('adminkab/rkt/edit') ?>" class="text-success">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-link text-danger p-0"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <!-- Tambah baris lainnya jika diperlukan -->
            </tbody>
        </table>
        </div>
    </div>
  </main>

  <?= $this->include('adminKabupaten/templates/footer.php'); ?>
   </div> <!-- End Content Wrapper -->

</body>
</html>

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
 <!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

  <!-- Navbar/Header -->
  <?= $this->include('adminKabupaten/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">PERJANJIAN KINERJA BUPATI</h2>

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
                <a href="#" class="btn btn-success d-flex align-items-center">
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
                <th class="border p-2">SASARAN STRATEGIS</th>
                <th class="border p-2">INDIKATOR KINERJA</th>
                <th class="border p-2">TARGET</th>
                <th class="border p-2">ANGGARAN (Rp)</th>
                <th class="border p-2">PENANGGUNG JAWAB</th>
                <th class="border p-2">ACTION</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="border p-2">1</td>
                <td class="border p-2">Meningkatkan Kesejahteraan Masyarakat</td>
                <td class="border p-2">Indeks Pembangunan Manusia</td>
                <td class="border p-2">75.5</td>
                <td class="border p-2">2,500,000,000</td>
                <td class="border p-2">Dinas Sosial</td>
                <td class="border p-2">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <a href="#" class="text-success">
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

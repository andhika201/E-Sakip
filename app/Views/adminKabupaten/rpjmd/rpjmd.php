<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RPJMD e-SAKIP</title>
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
        <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH</h2>

        <!-- Filter -->
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div class="d-flex gap-2 flex-fill">
            <select class="form-select">
            <option value="">TAHUN MULAI</option>
            <option>2019</option>
            <option>2020</option>
            <option>2021</option>
            </select>
            <select class="form-select">
            <option value="">TAHUN SELESAI</option>
            <option>2022</option>
            <option>2023</option>
            <option>2024</option>
            </select>
            <a href="" class="btn btn-success d-flex align-items-center">
                <i class="fas fa-filter me-2"></i> FILTER
            </a>
        </div>
        <div>
            <a href="<?= base_url('adminkab/rpjmd/tambah') ?>" class="btn btn-success d-flex align-items-center">
                <i class="fas fa-plus me-1"></i> TAMBAH
            </a>
        </div>  
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small">
            <thead class="table-success">
            <tr>
                <th rowspan="2" class="border p-2 align-middle">MISI</th>
                <th rowspan="2" class="border p-2 align-middle">TUJUAN</th>
                <th rowspan="2" class="border p-2 align-middle">INDIKATOR</th>
                <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                <th rowspan="2" class="border p-2 align-middle">STRATEGI</th>
                <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                <th colspan="5" class="border p-2">TARGET CAPAIAN PER TAHUN</th>
                <th rowspan="2" class="border p-2 align-middle">ACTION</th>
            </tr>
            <tr class="border p-2" style="border-top: 2px solid;">
                <th class="border p-2" style="border: 2px;">2025</th>
                <th class="border p-2" style="border: 2px;">2026</th>
                <th class="border p-2" style="border: 2px;">2027</th>
                <th class="border p-2" style="border: 2px;">2028</th>
                <th class="border p-2" style="border: 2px;">2029</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="border p-2">MISI</td>
                <td class="border p-2">TUJUAN</td>
                <td class="border p-2">INDIKATOR</td>
                <td class="border p-2">SASARAN</td>
                <td class="border p-2">INDIKATOR SASARAN</td>
                <td class="border p-2">STRATEGI</td>
                <td class="border p-2">PERSEN</td>
                <td class="border p-2">✓</td>
                <td class="border p-2">✓</td>
                <td class="border p-2">✓</td>
                <td class="border p-2">✓</td>
                <td class="border p-2">✓</td>
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
</body>
</html>

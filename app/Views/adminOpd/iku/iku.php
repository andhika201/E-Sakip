<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>IKU - e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Navbar/Header -->
  <?= $this->include('adminOpd/templates/header.php'); ?>

  <!-- Sidebar -->
  <?= $this->include('adminOpd/templates/sidebar.php'); ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">INDIKATOR KINERJA UTAMA</h2>

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
                <a href="<?= base_url('adminopd/iku/tambah') ?>" class="btn btn-success d-flex align-items-center">
            <i class="fas fa-plus me-1"></i> TAMBAH
            </a>
        </div>
    </div>

    <!-- Tabel -->
    <div class="table-responsive">
        <table class="table table-bordered text-center small" style="border-collapse: collapse;">
            <thead class="table-success">
            <tr>
                <th rowspan="2" class="border p-2 align-middle">NO</th>
                <th rowspan="2" class="border p-2 align-middle">SASARAN STRATEGIS</th>
                <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN<br>(INDIKATOR KINERJA UTAMA)</th>
                <th rowspan="2" class="border p-2 align-middle">DEFINISI OPERASIONAL/<br>FORMULASI</th>
                <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                <th colspan="5" class="border p-2">TARGET CAPAIAN PER TAHUN</th>
                <th rowspan="2" class="border p-2 align-middle">PROGRAM PENDUKUNG SASARAN</th>
                <th rowspan="2" class="border p-2 align-middle">ACTION</th>
            </tr>
            <tr class="border p-2" style="border-top: 2px solid;">
                <th class="border p-2">2023</th>
                <th class="border p-2">2024</th>
                <th class="border p-2">2025</th>
                <th class="border p-2">2026</th>
                <th class="border p-2">2027</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="border p-2" style="border: 1px solid;">1.</td>
                <td class="border p-2">Meningkatkan Pertumbuhan Ekonomi Daerah</td>
                <td class="border p-2">Pertumbuhan Ekonomi</td>
                <td class="border p-2">Persentase pertumbuhan PDRB Kabupaten Pringsewu tahun berjalan dibandingkan tahun sebelumnya</td>
                <td class="border p-2">Persen</td>
                <td class="border p-2">5.0%</td>
                <td class="border p-2">5.2%</td>
                <td class="border p-2">5.5%</td>
                <td class="border p-2">5.8%</td>
                <td class="border p-2">5.8%</td>
                <td class="border p-2">Program Pembangunan Ekonomi Daerah</td>
                <td class="border p-2">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <a href="<?= base_url('adminopd/iku/edit') ?>" class="text-success">
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

  <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>
</html>

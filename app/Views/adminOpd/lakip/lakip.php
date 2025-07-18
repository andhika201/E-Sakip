<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LAKIP - e-SAKIP</title>
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
            <h2 class="h3 fw-bold text-success text-center mb-4">Laporan Akuntabilitas Kinerja Instansi Pemerintah</h2>
            <div class="d-flex justify-content-end mb-4">
                <a href="<?= base_url('adminopd/iku/tambah') ?>" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-1"></i> TAMBAH
                </a>
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-bordered text-center small" style="border-collapse: collapse;">
                    <thead class="table-success">
                        <tr>
                            <th rowspan="2" class="border p-2 align-middle">NO</th>
                            <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                            <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                            <th colspan="3" class="border p-2 text-center">TAHUN 2023</th>
                            <th colspan="3" class="border p-2 text-center">TAHUN 2024</th>
                            <th rowspan="2" class="border p-2 align-middle">ACTION</th>
                        </tr>
                        <tr>
                            <th class="border p-2">TARGET</th>
                            <th class="border p-2">REALISASI</th>
                            <th class="border p-2">CAPAIAN</th>
                            <th class="border p-2">TARGET</th>
                            <th class="border p-2">REALISASI</th>
                            <th class="border p-2">CAPAIAN</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Contoh Sasaran</td>
                            <td>Contoh Indikator</td>
                            <td>80%</td>
                            <td>75%</td>
                            <td>93%</td>
                            <td>85%</td>
                            <td>82%</td>
                            <td>96%</td>
                            <td>
                                <button class="btn btn-sm btn-primary">Edit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>

</html>
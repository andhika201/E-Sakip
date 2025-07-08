<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RENSTRA - e-SAKIP</title>
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
            <h2 class="h3 fw-bold text-success text-center mb-4">Rencana Strategis</h2>

            <!-- Filter -->
            <div class="d-flex justify-content-end mb-4">
                <a href="<?= base_url('adminopd/renstra/tambah') ?>" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-1"></i> TAMBAH
                </a>
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-bordered text-center small" style="border-collapse: collapse;">
                    <thead class="table-success">
                        <tr>
                            <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                            <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                            <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                            <th colspan="5" class="border p-2">TARGET CAPAIAN PER TAHUN</th>
                            <th rowspan="2" class="border p-2 align-middle">ACTION</th>
                        </tr>
                        <tr class="border p-2" style="border-top: 2px solid;">
                            <th class="border p-2" style="border: 2px;">2023</th>
                            <th class="border p-2" style="border: 2px;">2024</th>
                            <th class="border p-2" style="border: 2px;">2025</th>
                            <th class="border p-2" style="border: 2px;">2026</th>
                            <th class="border p-2" style="border: 2px;">2027</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border p-2" style="border: 2px solid;">Meningkatkan Pertumbuhan Ekonomi Daerah
                            </td>
                            <td class="border p-2" style="border: 2px solid;">Pertumbuhan Ekonomi</td>
                            <td class="border p-2" style="border: 2px solid;">Persen</td>
                            <td class="border p-2" style="border: 2px solid;">5.0%</td>
                            <td class="border p-2" style="border: 2px solid;">5.2%</td>
                            <td class="border p-2" style="border: 2px solid;">5.5%</td>
                            <td class="border p-2" style="border: 2px solid;">5.8%</td>
                            <td class="border p-2" style="border: 2px solid;">5.8%</td>
                            <td class="border p-2" style="border: 2px solid;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <a href="<?= base_url('adminopd/renstra/edit') ?>" class="text-success">
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
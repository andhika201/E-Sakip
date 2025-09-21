<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monev - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include('adminOpd/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Monev</h2>

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
                        <a href="<?= base_url('adminopd/iku/tambah') ?>"
                            class="btn btn-success d-flex align-items-center">
                            <i class="fas fa-plus me-1"></i> TAMBAH
                        </a>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-success fw-bold text-dark">
                            <tr>
                                <th rowspan="2" class="border p-2 align-middle">No</th>
                                <th rowspan="2" class="border p-2 align-middle">Tujuan</th>
                                <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                                <th rowspan="2" class="border p-2 align-middle">Indikator</th>
                                <th rowspan="2" class="border p-2 align-middle">Rencana Aksi</th>
                                <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                                <th rowspan="2" class="border p-2 align-middle">Baseline (Capaian)</th>
                                <th rowspan="2" class="border p-2 align-middle">Target 2025</th>
                                <th colspan="4" class="border p-2 align-middle">Target Triwulan</th>
                                <th colspan="4" class="border p-2 align-middle">Capaian 2025</th>
                                <th rowspan="2" class="border p-2 align-middle">Capaian Total</th>
                                <th rowspan="2" class="border p-2 align-middle">Penanggung Jawab</th>
                            </tr>
                            <tr>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Contoh satu indikator punya 2 rencana aksi -->
                            <tr>
                                <td rowspan="2">1</td>
                                <td rowspan="2">Meningkatkan tata kelola pemerintahan yang baik</td>
                                <td rowspan="2">Meningkatnya kualitas penyelenggaraan pemerintahan</td>
                                <td rowspan="2">Indeks Reformasi Birokrasi</td>
                                <td>Penyusunan Rencana Aksi RB general dan Tematik</td>
                                <td>Indeks</td>
                                <td>63.33</td>
                                <td>57.01</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td rowspan="2">Inspektorat <br> Bagian Organisasi</td>
                            </tr>
                            <tr>
                                <td>Penyusunan Dokumen Laporan Akuntabilitas Instansi Pemerintah</td>
                                <td>Indeks</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>
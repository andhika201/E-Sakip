<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LAKIP Kabupaten - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('user/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Navbar/Header -->
    <?= $this->include('user/templates/header.php'); ?>

    <!-- Sidebar -->

    <!-- Konten Utama -->
    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h3 fw-bold text-success text-center mb-4">LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH
                KABUPATEN</h2>

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
                
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center small">
                    <thead class="table-success align-middle text-center">
                        <tr>
                            <th class="border p-2 align-middle" rowspan="2">NO</th>
                            <th class="border p-2 align-middle" rowspan="2">TAHUN LAPORAN</th>
                            <th class="border p-2" colspan="3">2023</th>
                            <th class="border p-2" colspan="3">2024</th>
                            <th class="border p-2 align-middle" rowspan="2">FILE</th>
                        </tr>
                        <tr>
                            <th class="border p-2">Target</th>
                            <th class="border p-2">Capaian</th>
                            <th class="border p-2">Realisasi</th>
                            <th class="border p-2">Target</th>
                            <th class="border p-2">Capaian</th>
                            <th class="border p-2">Realisasi</th>
                        </tr>
                    </thead>


                    <tbody>
                        <tr>
                            <td class="border p-2">1</td>
                            <td class="border p-2">2023</td>

                            <!-- Kolom 2023 -->
                            <td class="border p-2">100%</td> <!-- Target -->
                            <td class="border p-2">95%</td> <!-- Capaian -->
                            <td class="border p-2">Rp 950jt</td> <!-- Realisasi -->

                            <!-- Kolom 2024 -->
                            <td class="border p-2">100%</td>
                            <td class="border p-2">80%</td>
                            <td class="border p-2">Rp 800jt</td>

                       
                            <td class="border p-2">
                                <a href="#" class="text-primary">
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>
    </main>

    <?= $this->include('user/templates/footer.php'); ?>
</body>

</html>
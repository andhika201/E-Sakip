<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tentang Kami - e-SAKIP</title>
  <!-- Style -->
  <?= $this->include('user/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <!-- Content Wrapper -->
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
    
    <!-- Navbar/Header -->
    <?= $this->include('user/templates/header.php'); ?>

    <!-- Konten Utama -->
    <main class="flex-fill p-4 mt-2">
    <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">TENTANG KAMI</h2>

        <!-- Konten Tentang Kami -->
        <div class="row g-4">
            <!-- Sejarah -->
            <div class="col-12">
                <div class="bg-light p-4 rounded">
                    <h3 class="h4 fw-semibold text-success mb-3">Sejarah e-SAKIP</h3>
                    <p class="text-dark lh-lg">
                        Sistem Akuntabilitas Kinerja Instansi Pemerintah (SAKIP) merupakan rangkaian sistematik dari berbagai aktivitas, alat, dan prosedur yang dirancang untuk tujuan penetapan dan pengukuran, pengumpulan data, pengklasifikasian, pengikhtisaran dan pelaporan kinerja pada instansi pemerintah, dalam rangka pertanggungjawaban dan peningkatan kinerja instansi pemerintah.
                    </p>
                </div>
            </div>

            <!-- Visi Misi -->
            <div class="col-12 col-md-6">
                <div class="bg-primary bg-opacity-10 p-4 rounded h-100">
                    <h3 class="h4 fw-semibold text-success mb-3">Visi</h3>
                    <p class="text-dark lh-lg">
                        Mewujudkan tata kelola pemerintahan yang akuntabel dan transparan melalui sistem pelaporan kinerja yang terintegrasi dan berkualitas.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="bg-primary bg-opacity-10 p-4 rounded h-100">
                    <h3 class="h4 fw-semibold text-success mb-3">Misi</h3>
                    <ul class="text-dark list-unstyled">
                        <li class="mb-2">• Meningkatkan kualitas pelaporan kinerja instansi pemerintah</li>
                        <li class="mb-2">• Mempermudah proses monitoring dan evaluasi kinerja</li>
                        <li class="mb-2">• Meningkatkan transparansi dan akuntabilitas publik</li>
                        <li class="mb-2">• Mendukung pengambilan keputusan berbasis data</li>
                    </ul>
                </div>
            </div>

            <!-- Tim Pengembang -->
            <div class="col-12">
                <div class="bg-light p-4 rounded">
                    <h3 class="h4 fw-semibold text-success mb-3">Tim Pengembang</h3>
                    <div class="row g-3">
                        <div class="col-12 col-md-4 text-center">
                            <div class="bg-success bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user text-success fs-2"></i>
                            </div>
                            <h4 class="h6 fw-semibold">Tim IT Kabupaten</h4>
                            <p class="small text-muted">Developer</p>
                        </div>
                        <div class="col-12 col-md-4 text-center">
                            <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-users text-primary fs-2"></i>
                            </div>
                            <h4 class="h6 fw-semibold">Bagian Organisasi</h4>
                            <p class="small text-muted">Koordinator</p>
                        </div>
                        <div class="col-12 col-md-4 text-center">
                            <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-chart-bar text-warning fs-2"></i>
                            </div>
                            <h4 class="h6 fw-semibold">Tim Monitoring</h4>
                            <p class="small text-muted">Evaluator</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kontak -->
            <div class="col-12">
                <div class="bg-gradient text-white p-4 rounded" style="background: linear-gradient(90deg, #198754, #0d6efd);">
                    <h3 class="h4 fw-semibold mb-3">Kontak Kami</h3>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <p class="d-flex align-items-center mb-2">
                                <i class="fas fa-map-marker-alt me-3"></i>
                                Jl. Contoh No. 123, Kabupaten ABC
                            </p>
                            <p class="d-flex align-items-center mb-2">
                                <i class="fas fa-phone me-3"></i>
                                (021) 1234-5678
                            </p>
                        </div>
                        <div class="col-12 col-md-6">
                            <p class="d-flex align-items-center mb-2">
                                <i class="fas fa-envelope me-3"></i>
                                sakip@kabupaten.go.id
                            </p>
                            <p class="d-flex align-items-center">
                                <i class="fas fa-globe me-3"></i>
                                www.kabupaten.go.id
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </main>

  <?= $this->include('user/templates/footer.php'); ?>
  
  </div> <!-- End Content Wrapper -->

</body>
</html>

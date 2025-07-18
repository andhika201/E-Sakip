<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPJMD - Rencana Pembangunan Jangka Menengah Daerah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-max {
            max-width: 1700px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .table thead th {
            background-color: #e9f5ef;
            font-weight: 600;
            vertical-align: top !important;
            text-align: left !important;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
            vertical-align: top !important;
            text-align: left !important;
        }

        .align-top {
            vertical-align: top !important;
        }

        .filter-container {
            max-width: 400px;
            margin: 0 auto 2rem;
        }

        .alert {
            border-radius: 8px;
        }

        .bg-success-light {
            background-color: #d1e7dd;
        }

        .text-success-dark {
            color: #0d6e3f;
        }

        .year-cell {
            min-width: 80px;
            font-weight: 500;
            text-align: left !important;
            vertical-align: top !important;
        }

        /* Key change: All table rows now have white background */
        #rpjmd-table-body tr {
            background-color: white;
        }

        /* Remove striped effect */
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: white !important;
        }

        /* Hover effect */
        #rpjmd-table-body tr:hover {
            background-color: #f1f9f5 !important;
        }

        .fixed-column {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 10;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .target-cell {
            text-align: left !important;
            vertical-align: top !important;
            font-weight: 500;
        }

        .header-title {
            color: #198754;
            border-bottom: 2px solid #198754;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
    </style>
</head>

<body>
    <header class="bg-success text-white py-3 shadow">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-0"><i class="fas fa-building me-2"></i>PEMERINTAH DAERAH</h1>
                </div>
                <div class="text-end">
                    <p class="mb-0">RPJMD</p>
                    <p class="mb-0 small">Rencana Pembangunan Jangka Menengah Daerah</p>
                </div>
            </div>
        </div>
    </header>

    <main class="py-4">
        <div class="container container-max">
            <div class="table-container p-4">
                <div class="text-center mb-4">
                    <h3 class="header-title">
                        <i class="fas fa-chart-line me-2"></i>
                        RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH
                    </h3>
                    <p class="text-muted">Strategi Pembangunan Daerah</p>
                </div>

                <!-- Filter Section -->
                <div class="filter-container mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-success text-white"><i class="fas fa-filter"></i></span>
                        <select id="periode-filter" class="form-select form-select-lg">
                            <option value="">Semua Periode</option>
                            <option value="2019-2024" selected>Periode 2019-2024</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-success">
                            <tr>
                                <th rowspan="2" class="align-middle fixed-column">MISI</th>
                                <th rowspan="2" class="align-middle">TUJUAN</th>
                                <th rowspan="2" class="align-middle">INDIKATOR</th>
                                <th rowspan="2" class="align-middle">SASARAN</th>
                                <th rowspan="2" class="align-middle">INDIKATOR SASARAN</th>
                                <th rowspan="2" class="align-middle">DEFINISI OPERASIONAL</th>
                                <th rowspan="2" class="align-middle">SATUAN</th>
                                <th colspan="5" class="text-center">TARGET CAPAIAN PER TAHUN</th>
                            </tr>
                            <tr>
                                <th class="year-cell">2019</th>
                                <th class="year-cell">2020</th>
                                <th class="year-cell">2021</th>
                                <th class="year-cell">2022</th>
                                <th class="year-cell">2023</th>
                            </tr>
                        </thead>
                        <tbody id="rpjmd-table-body">
                            <!-- MISI: Mewujudkan masyarakat yang religius dan berbudaya -->
                            <tr>
                                <td rowspan="4" class="fixed-column">Mewujudkan masyarakat yang religius dan berbudaya
                                </td>
                                <td rowspan="4">Terwujudnya kehidupan masyarakat yang agamis, berbudaya, aman dan damai
                                </td>
                                <td>Indeks Demokrasi Indonesia (IDI)</td>
                                <td>Meningkatnya kualitas demokrasi di daerah</td>
                                <td>Indeks Demokrasi Indonesia (IDI)</td>
                                <td>-</td>
                                <td>Indeks</td>
                                <td class="target-cell">0.00</td>
                                <td class="target-cell">72.00</td>
                                <td class="target-cell">73.00</td>
                                <td class="target-cell">74.00</td>
                                <td class="target-cell">75.00</td>
                            </tr>
                            <tr>
                                <td rowspan="2">Indeks Kerukunan Umat Beragama</td>
                                <td rowspan="2">Meningkatnya kerukunan antar umat beragama</td>
                                <td>Indeks Kerukunan Umat Beragama</td>
                                <td>Meningkatkan dukungan dalam upaya mempertahankan, menyebarkan, dan melaksanakan
                                    nilai-nilai agama dalam masyarakat</td>
                                <td>Indeks</td>
                                <td class="target-cell">68.50</td>
                                <td class="target-cell">69.00</td>
                                <td class="target-cell">73.30</td>
                                <td class="target-cell">73.50</td>
                                <td class="target-cell">73.70</td>
                            </tr>
                            <tr>
                                <td>Indeks Kerukunan Umat Beragama</td>
                                <td>Meningkatkan peran lembaga adat dan agama dalam mencegah konflik</td>
                                <td>Indeks</td>
                                <td class="target-cell">68.50</td>
                                <td class="target-cell">69.00</td>
                                <td class="target-cell">73.30</td>
                                <td class="target-cell">73.50</td>
                                <td class="target-cell">73.70</td>
                            </tr>
                            <tr>
                                <td>Indeks Pembangunan Kebudayaan (IPK)</td>
                                <td>Meningkatnya pelestarian dan pemanfaatan budaya</td>
                                <td>Indeks Pembangunan Kebudayaan (IPK)</td>
                                <td>-</td>
                                <td>Indeks</td>
                                <td class="target-cell">0.00</td>
                                <td class="target-cell">0.00</td>
                                <td class="target-cell">54.80</td>
                                <td class="target-cell">55.30</td>
                                <td class="target-cell">55.80</td>
                            </tr>

                            <!-- MISI: Memperkuat ekonomi daerah -->
                            <tr>
                                <td rowspan="3" class="fixed-column">Memperkuat ekonomi daerah</td>
                                <td rowspan="3">Meningkatkan pertumbuhan ekonomi yang inklusif dan berkelanjutan</td>
                                <td>Laju Pertumbuhan Ekonomi</td>
                                <td>Meningkatnya pertumbuhan ekonomi daerah</td>
                                <td>Pertumbuhan PDRB</td>
                                <td>Pertumbuhan Produk Domestik Regional Bruto</td>
                                <td>%</td>
                                <td class="target-cell">5.20</td>
                                <td class="target-cell">5.50</td>
                                <td class="target-cell">5.80</td>
                                <td class="target-cell">6.00</td>
                                <td class="target-cell">6.20</td>
                            </tr>
                            <tr>
                                <td>Indeks Pembangunan Manusia (IPM)</td>
                                <td>Meningkatnya kualitas sumber daya manusia</td>
                                <td>Indeks Pembangunan Manusia</td>
                                <td>Pengukuran capaian pembangunan manusia</td>
                                <td>Indeks</td>
                                <td class="target-cell">70.10</td>
                                <td class="target-cell">70.80</td>
                                <td class="target-cell">71.50</td>
                                <td class="target-cell">72.20</td>
                                <td class="target-cell">72.90</td>
                            </tr>
                            <tr>
                                <td>Tingkat Pengangguran Terbuka</td>
                                <td>Menurunnya tingkat pengangguran</td>
                                <td>Persentase Pengangguran</td>
                                <td>Pengukuran tingkat pengangguran terbuka</td>
                                <td>%</td>
                                <td class="target-cell">6.80</td>
                                <td class="target-cell">6.50</td>
                                <td class="target-cell">6.20</td>
                                <td class="target-cell">5.90</td>
                                <td class="target-cell">5.60</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Info Panel -->
                <div class="alert bg-success-light text-success-dark mt-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-2">Informasi RPJMD</h5>
                            <p class="mb-0">RPJMD adalah dokumen perencanaan pembangunan daerah untuk periode 5 tahun
                                yang memuat visi, misi, tujuan, strategi, kebijakan, dan program pembangunan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-success text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-university me-2"></i>PEMERINTAH DAERAH</h5>
                    <p class="mb-0">Jl. Pemda No. 123, Kota Administratif</p>
                    <p class="mb-0">Telp: (021) 12345678 | Email: info@pemda.go.id</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h5>RENCANA PEMBANGUNAN JANGKA MENENGAH DAERAH</h5>
                    <p class="mb-0">© 2023 - Sistem Informasi Pembangunan Daerah</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Filter functionality
        document.getElementById('periode-filter').addEventListener('change', function () {
            const selectedPeriod = this.value;
            console.log(`Filtering by period: ${selectedPeriod}`);
            // In a real implementation, this would filter the table data
        });

        // Highlight row on hover
        document.querySelectorAll('#rpjmd-table-body tr').forEach(row => {
            row.addEventListener('mouseenter', function () {
                this.style.backgroundColor = '#f1f9f5';
            });

            row.addEventListener('mouseleave', function () {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>

</html>
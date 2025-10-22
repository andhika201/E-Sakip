<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RENJA - e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Content Wrapper -->
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include('adminOpd/templates/header.php'); ?>

        <!-- Sidebar -->
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <!-- Konten Utama -->
        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">RENCANA KERJA TAHUNAN</h2>

                <!-- Error Messages -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Validation Errors -->
                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terdapat kesalahan:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div class="d-flex gap-2 flex-fill">
                        <select id="renstraSasaranFilter" class="form-select w-50" onchange="filterData()">
                            <option value="all">SEMUA SASARAN RENSTRA</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                        <select id="yearFilter" class="form-select w-25" onchange="filterData()">
                            <option value="all">SEMUA TAHUN</option>
                            <?php if (isset($available_years)): ?>
                                <?php foreach ($available_years as $year): ?>
                                    <option value="<?= $year ?>">
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <select id="statusFilter" class="form-select w-25" onchange="filterData()">
                            <option value="all">SEMUA STATUS</option>
                            <option value="draft">Draft</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>

                <!-- Data Summary -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <span id="visible-data-count">Memuat data...</span>
                            </small>
                            <small class="text-muted">
                                Filter aktif: <span id="active-filters">Semua Sasaran, Semua Tahun, Semua Status</span>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center small">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">SATUAN KERJA</th>
                                <th class="border p-2">NO</th>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR SASARAN</th>
                                <th class="border p-2">SATUAN</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">PROGRAM</th>
                                <th class="border p-2">KEGIATAN</th>
                                <th class="border p-2">SUB KEGIATAN</th>
                                <th class="border p-2">TARGET ANGGARAN</th>
                                <th class="border p-2">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            helper('format_helper');
                            $no = 1;

                            // Hitung total rowspan untuk kolom OPD
                            $totalRowsAll = 0;
                            foreach ($rktdata as $ind) {
                                $rowCount = 0;
                                if (!empty($ind['rkts'])) {
                                    foreach ($ind['rkts'] as $rkt) {
                                        if (!empty($rkt['kegiatan'])) {
                                            foreach ($rkt['kegiatan'] as $keg) {
                                                $subCount = count($keg['subkegiatan'] ?? []);
                                                $rowCount += ($subCount > 0 ? $subCount : 1);
                                            }
                                        } else {
                                            $rowCount += 1;
                                        }
                                    }
                                } else {
                                    $rowCount = 1;
                                }
                                $totalRowsAll += $rowCount;
                            }

                            $firstOpdRow = true;

                            foreach ($rktdata as $ind):
                                // hitung rowspan indikator
                                $totalSub = 0;
                                if (!empty($ind['rkts'])) {
                                    foreach ($ind['rkts'] as $rkt) {
                                        if (!empty($rkt['kegiatan'])) {
                                            foreach ($rkt['kegiatan'] as $keg) {
                                                $subCount = count($keg['subkegiatan'] ?? []);
                                                $totalSub += ($subCount > 0 ? $subCount : 1);
                                            }
                                        } else {
                                            $totalSub += 1;
                                        }
                                    }
                                } else {
                                    $totalSub = 1;
                                }

                                $firstIndicatorRow = true;
                                $actionRendered = false;

                                // Jika belum ada RKT sama sekali
                                if (empty($ind['rkts'])):
                                    ?>
                                    <tr>
                                        <?php if ($firstOpdRow): ?>
                                            <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                                <?= esc($currentOpd['nama_opd']) ?></td>
                                            <?php $firstOpdRow = false; ?>
                                        <?php endif; ?>

                                        <td rowspan="<?= $totalSub ?>" class="align-middle"><?= $no++ ?></td>
                                        <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                                            <?= esc($ind['sasaran']) ?></td>
                                        <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                                            <?= esc($ind['indikator_sasaran']) ?></td>
                                        <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                            <?= esc($ind['satuan']) ?></td>
                                        <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                            <?= esc($ind['target']) ?></td>

                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="text-start">-</td>
                                        <td class="text-end">-</td>

                                        <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                            <a href="<?= base_url('adminopd/rkt/tambah/' . $ind['id']) ?>"
                                                class="btn btn-sm btn-primary">Tambah</a>
                                        </td>
                                    </tr>

                                    <?php
                                    // --- RKT ADA ---
                                else:
                                    foreach ($ind['rkts'] as $rkt):
                                        $firstProgramRow = true;
                                        foreach ($rkt['kegiatan'] as $keg):
                                            $subCount = count($keg['subkegiatan'] ?? []);
                                            $rowspanKeg = ($subCount > 0 ? $subCount : 1);
                                            $firstKegRow = true;

                                            if (!empty($keg['subkegiatan'])):
                                                foreach ($keg['subkegiatan'] as $sub):
                                                    ?>
                                                    <tr>
                                                        <?php if ($firstOpdRow): ?>
                                                            <td rowspan="<?= $totalRowsAll ?>" class="align-middle">
                                                                <?= esc($currentOpd['nama_opd']) ?></td>
                                                            <?php $firstOpdRow = false; ?>
                                                        <?php endif; ?>

                                                        <?php if ($firstIndicatorRow): ?>
                                                            <td rowspan="<?= $totalSub ?>" class="align-middle"><?= $no++ ?></td>
                                                            <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                                                                <?= esc($ind['sasaran']) ?></td>
                                                            <td rowspan="<?= $totalSub ?>" class="align-middle text-start">
                                                                <?= esc($ind['indikator_sasaran']) ?></td>
                                                            <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                                                <?= esc($ind['satuan']) ?></td>
                                                            <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                                                <?= esc($ind['target']) ?></td>
                                                            <?php $firstIndicatorRow = false; ?>
                                                        <?php endif; ?>

                                                        <?php if ($firstProgramRow): ?>
                                                            <td rowspan="<?= $rowspanKeg ?>" class="align-middle text-start">
                                                                <?= esc($rkt['program_nama']) ?></td>
                                                            <?php $firstProgramRow = false; ?>
                                                        <?php endif; ?>

                                                        <?php if ($firstKegRow): ?>
                                                            <td rowspan="<?= $rowspanKeg ?>" class="align-middle text-start">
                                                                <?= esc($keg['nama_kegiatan']) ?></td>
                                                            <?php $firstKegRow = false; ?>
                                                        <?php endif; ?>

                                                        <td class="text-start"><?= esc($sub['nama_subkegiatan']) ?></td>
                                                        <td class="text-end"><?= formatRupiah($sub['target_anggaran']) ?></td>

                                                        <?php if (!$actionRendered): ?>
                                                            <td rowspan="<?= $totalSub ?>" class="align-middle text-center">
                                                                <a href="<?= base_url('adminopd/rkt/edit/' . $ind['id']) ?>"
                                                                    class="btn btn-sm btn-warning">Edit</a>
                                                            </td>
                                                            <?php $actionRendered = true; ?>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <?php
                                                endforeach;
                                            endif;
                                        endforeach;
                                    endforeach;
                                endif;
                            endforeach;
                            ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div> <!-- End of main-content -->

</body>
<script>
function filterData() {
    let opd    = document.getElementById('opdFilter').value;
    let sasaran = document.getElementById('renstraSasaranFilter').value;
    let tahun   = document.getElementById('yearFilter').value;
    let status  = document.getElementById('statusFilter').value;

    let url = `?opd=${opd}&sasaran=${sasaran}&tahun=${tahun}&status=${status}`;
    window.location.href = url;
}
</script>

</html>
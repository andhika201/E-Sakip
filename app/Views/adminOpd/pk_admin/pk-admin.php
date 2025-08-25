<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PK Administrator - e-SAKIP</title>
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
            <h2 class="h3 fw-bold text-success text-center mb-4">PK ADMINISTRATOR</h2>

            <!-- Filter -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div class="d-flex gap-2 flex-fill">
                    <select class="form-select">
                        <option value="">TAHUN</option>
                        <option>2020</option>
                        <option>2021</option>
                        <option>2022</option>
                        <option>2023</option>
                        <option>2024</option>
                        <option>2025</option>
                    </select>
                </div>
                <div>
                    <a href="<?= base_url('adminopd/pk_admin/tambah') ?>"
                        class="btn btn-success d-flex align-items-center">
                        <i class="fas fa-plus me-1"></i> TAMBAH
                    </a>
                </div>
            </div>

            <!-- Tabel -->
            <div class="table-responsive">
            <?php if (isset($pk_data['jenis']) && $pk_data['jenis'] === 'administrator'): ?>
                <!-- TABEL 1: Sasaran + Indikator -->
                <table class="table table-bordered table-striped text-center small mb-5">
                    <thead class="table-success">
                        <tr>
                            <th class="border p-2">NO</th>
                            <th class="border p-2">SASARAN</th>
                            <th class="border p-2">INDIKATOR</th>
                            <th class="border p-2">TARGET</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($pk_data['sasaran'] as $sasaran): ?>
                            <?php $rowspan = count($sasaran['indikator']); ?>
                            <?php foreach ($sasaran['indikator'] as $index => $indikator): ?>
                                <tr>
                                    <?php if ($index === 0): ?>
                                        <td class="border p-2" rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                                        <td class="border p-2" rowspan="<?= $rowspan ?>"><?= esc($sasaran['sasaran']) ?></td>
                                    <?php endif; ?>
                                    <td class="border p-2"><?= esc($indikator['indikator']) ?></td>
                                    <td class="border p-2"><?= esc($indikator['target']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h4 class="h3 fw-bold text-success text-left mb-4">PROGRAM DAN ANGGARAN</h4>
                <!-- TABEL 2: Program + Anggaran -->
                <table class="table table-bordered table-striped text-center small">
                    <thead class="table-info">
                        <tr>
                            <th class="border p-2">NO</th>
                            <th class="border p-2">PROGRAM</th>
                            <th class="border p-2">ANGGARAN</th>
                            <th class="border p-2">Tingkat PK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no_program = 1; ?>
                        <?php foreach ($pk_data['program'] as $program): ?>
                            <tr>
                                <td class="border p-2"><?= $no_program++ ?></td>
                                <td class="border p-2"><?= esc($program['program_kegiatan']) ?></td>
                                <td class="border p-2">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                <td class="border p-2"><?= esc(ucwords($pk_data['jenis'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Tombol aksi di kanan -->
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="<?= base_url('adminopd/pk_admin/cetak/' . $pk_data['pk_id']) ?>"
                        class="btn btn-primary btn-sm text-white" target="_blank">
                        <i class="fas fa-download me-1"></i> Download
                    </a>
                    <a href="<?= base_url('adminopd/pk_admin/edit/' . $pk_data['pk_id']) ?>"
                        class="btn btn-success btn-sm">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <button class="btn btn-danger btn-sm"
                        onclick="deleteRenstra(<?= $pk_data['pk_id'] ?>, '<?= base_url() ?>')">
                        <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">Belum ada data PK</div>
            <?php endif; ?>
            </div>
        </div>
    </main>
    <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>
</html>
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
            <?php
            // Hitung jumlah baris per sasaran
            $rowspans = [];
            foreach ($pk_data as $item) {
                $sasaran = $item['sasaran'];
                if (!isset($rowspans[$sasaran])) {
                    $rowspans[$sasaran] = 0;
                }
                $rowspans[$sasaran]++;
            }

            // Untuk tracking apakah sudah ditampilkan
            $shown_sasaran = [];
            $no = 1;
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center small">
                    <thead class="table-success">
                        <tr>
                            <th class="border p-2">NO</th>
                            <th class="border p-2">SASARAN</th>
                            <th class="border p-2">INDIKATOR</th>
                            <th class="border p-2">TARGET</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pk_data as $data): ?>
                            <tr>
                                <td class="border p-2"><?= $no++ ?></td>

                                <?php if (!in_array($data['sasaran'], $shown_sasaran)): ?>
                                    <td class="border p-2 text-start align-top" rowspan="<?= $rowspans[$data['sasaran']] ?>">
                                        <?= esc($data['sasaran']) ?>
                                    </td>
                                    <?php $shown_sasaran[] = $data['sasaran']; ?>
                                <?php endif; ?>

                                <td class="border p-2 text-start"><?= esc($data['indikator']) ?></td>
                                <td class="border p-2"><?= esc($data['target']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php $pk_id = $pk_data[0]['pk_id'] ?? null; ?>
            <div class="mt-4">
                <h5 class="fw-bold mb-3">Daftar Program & Anggaran</h5>

                <table class="table table-bordered table-sm">
                    <thead class="table-secondary">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th>Program</th>
                            <th>Anggaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($program_data[$pk_id])): ?>
                            <?php $no = 1;
                            foreach ($program_data[$pk_id] as $prog): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <input type="text" class="form-control" name="program_kegiatan[]"
                                            value="<?= esc($prog['program_kegiatan']) ?>" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="anggaran[]"
                                            value="<?= 'Rp ' . number_format($prog['anggaran'], 0, ',', '.') ?>" readonly>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center fst-italic">Tidak ada data program</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="<?= base_url('adminopd/pk_admin/cetak/' . $pk_id) ?>"
                    class="text-white btn btn-primary btn-sm me-2" target="_blank">
                    <i class="fas fa-download me-1"></i>Cetak
                </a>
                <?php if (!empty($pk_id)): ?>
                    <a href="<?= base_url('adminopd/pk_admin/edit/' . $pk_id) ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>

</html>
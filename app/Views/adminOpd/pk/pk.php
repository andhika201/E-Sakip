<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PK <?= ucfirst($jenis) ?> - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
    <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>
    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h3 fw-bold text-success text-center mb-4">PK <?= strtoupper($jenis) ?></h2>

            <!-- Filter Tahun & Tombol Tambah -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div class="d-flex gap-2 flex-fill">
                    <form method="get" action="">
                        <select class="form-select" name="tahun" onchange="this.form.submit()">
                            <option value="">TAHUN</option>
                            <?php for ($t = 2024; $t <= 2030; $t++): ?>
                                <option value="<?= $t ?>" <?= ($tahun == $t ? 'selected' : '') ?>><?= $t ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
                <div>
                    <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis . '/tambah') ?>"
                        class="btn btn-success d-flex align-items-center">
                        <i class="fas fa-plus me-1"></i> TAMBAH
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <?php if (!empty($pk_data)): ?>
                    <h4 class="h3 fw-bold text-success text-left mb-4">SASARAN DAN INDIKATOR</h4>
                    <table class="table table-bordered table-striped text-center small mb-5">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">NO</th>
                                <?php if (strtolower($jenis) === 'jpt'): ?>
                                    <th class="border p-2">MISI BUPATI</th>
                                <?php endif; ?>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">SATUAN</th>
                                <th class="border p-2">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pk_data)): ?>
                                <?php $no = 1; ?>
                                <?php $printedMisi = []; ?>
                                <?php $renderedMisi = []; ?>

                                <?php foreach ($pk_data as $pk): ?>
                                    <?php
                                    // Hitung total indikator per PK (berdasarkan misi di PK)
                                    $misiId = $pk['rpjmd_misi_id'];
                                    $misiCounts = 0;
                                    foreach ($pk['sasaran'] as $sasaran) {
                                        $misiCounts += count($sasaran['indikator']);
                                    }
                                    ?>

                                    <?php foreach ($pk['sasaran'] as $sasaranIndex => $sasaran): ?>
                                        <?php $indikatorList = $sasaran['indikator']; ?>
                                        <?php $sasaranRowspan = count($indikatorList); ?>

                                        <?php foreach ($indikatorList as $index => $indikator): ?>
                                            <tr>
                                                <?php if ($index === 0): ?>
                                                    <!-- Kolom nomor -->
                                                    <td class="border p-2" rowspan="<?= $sasaranRowspan ?>"><?= $no++ ?></td>

                                                    <!-- Kolom Misi (hanya sekali per PK) -->
                                                    <?php if (!isset($printedMisi[$pk['rpjmd_misi_id']])): ?>
                                                        <td class="border p-2" rowspan="<?= $misiCounts ?>">
                                                            <?= esc($misiListById[$pk['rpjmd_misi_id']] ?? '-') ?>
                                                        </td>
                                                        <?php $printedMisi[$pk['rpjmd_misi_id']] = true; ?>
                                                    <?php endif; ?>

                                                    <!-- Kolom Sasaran -->
                                                    <td class="border p-2" rowspan="<?= $sasaranRowspan ?>">
                                                        <?= esc($sasaran['sasaran']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- Kolom Indikator -->
                                                <td class="border p-2"><?= esc($indikator['indikator']) ?></td>
                                                <td class="border p-2"><?= esc($indikator['target']) ?></td>
                                                <td class="border p-2"><?= esc($indikator['satuan'] ?? '-') ?></td>

                                                <!-- Kolom Aksi (Edit/Hapus) hanya sekali per PK -->
                                                <?php if (!isset($renderedMisi[$pk['rpjmd_misi_id']])): ?>
                                                    <td class="border p-2" rowspan="<?= $misiCounts ?>">
                                                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $pk['jenis'] . '/edit/' . $pk['id']) ?>"
                                                            class="btn btn-success btn-sm mb-1">
                                                            <i class="fas fa-edit me-1"></i> Edit
                                                        </a>
                                                        <button class="btn btn-danger btn-sm"
                                                            onclick="deletePk(<?= $pk['id'] ?>, '<?= base_url() ?>')">
                                                            <i class="fas fa-trash me-1"></i> Hapus
                                                        </button>
                                                    </td>
                                                    <?php $renderedMisi[$pk['rpjmd_misi_id']] = true; ?>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Data PK tidak tersedia</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <h4 class="h3 fw-bold text-success text-left mb-4">PROGRAM DAN ANGGARAN</h4>
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
                            <?php if (strtolower($jenis) === 'bupati'): ?>
                                <?php $allPrograms = model('App\\Models\\PkModel')->getAllPrograms(); ?>
                                <?php foreach ($allPrograms as $program): ?>
                                    <tr>
                                        <td class="border p-2"><?= $no_program++ ?></td>
                                        <td class="border p-2"><?= esc($program['program_kegiatan']) ?></td>
                                        <td class="border p-2">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                        <td class="border p-2">Bupati</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($pk_data as $pk): ?>
                                    <?php foreach ($pk['program'] as $program): ?>
                                        <tr>
                                            <td class="border p-2"><?= $no_program++ ?></td>
                                            <td class="border p-2"><?= esc($program['program_kegiatan']) ?></td>
                                            <td class="border p-2">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                            <td class="border p-2"><?= esc(ucwords($pk['jenis'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis . '/cetak/' . $pk['id']) ?>"
                            class="btn btn-primary btn-sm text-white" target="_blank">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Belum ada data PK</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?= $this->include('adminOpd/templates/footer.php'); ?>
    <!-- Global JS variables for PK page -->
    <script>
        window.base_url = '<?= base_url() ?>';
        window.jenis = '<?= $jenis ?>';
    </script>
    <!-- PK page logic -->
    <script src="<?= base_url('assets/js/adminOpd/pk/pk.js') ?>"></script>
</body>

</html>
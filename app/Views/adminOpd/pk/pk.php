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
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div class="d-flex gap-2 flex-fill">
                    <form method="get" action="">
                        <select class="form-select" name="tahun" onchange="this.form.submit()">
                            <option value="">TAHUN</option>
                            <?php for ($t = 2020; $t <= 2025; $t++): ?>
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
                <?php if (isset($pk_data['jenis']) && $pk_data['jenis'] === $jenis): ?>
                    <!-- Tabel Indikator Acuan (Referensi) -->
                    <?php if (!empty($pk_data['id']) && strtolower($jenis) !== 'jpt'): ?>
                        <?php $indikatorAcuan = model('App\\Models\\PkModel')->getIndikatorAcuanByPkId($pk_data['id']); ?>
                        <?php if (!empty($indikatorAcuan)): ?>
                            <h4 class="h5 fw-bold text-primary text-left mb-2">Indikator Acuan (Referensi)</h4>
                            <table class="table table-bordered table-striped text-center small mb-4"
                                style="max-width:400px; margin-left:0;">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="border p-2" style="width:50px;">NO</th>
                                        <th class="border p-2" style="width:300px;">Indikator Acuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no_acuan = 1; ?>
                                    <?php foreach ($indikatorAcuan as $acuan): ?>
                                        <tr>
                                            <td class="border p-2" style="width:50px;"><?= $no_acuan++ ?></td>
                                            <td class="border p-2" style="width:300px;"><?= esc($acuan['nama_indikator']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
                    <h4 class="h3 fw-bold text-success text-left mb-4">SASARAN DAN INDIKATOR</h4>

                    <table class="table table-bordered table-striped text-center small mb-5">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">NO</th>
                                <?php if (!empty($pk_data['id']) && strtolower($jenis) === 'jpt'): ?>
                                    <th class="border p-2">MISI BUPATI</th>
                                <?php endif; ?>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">SATUAN</th>
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
                                            <?php $misi = model('App\\Models\\RpjmdModel')->getMisiById($sasaran['rpjmd_misi_id']); ?>
                                            <td class="border p-2" rowspan="<?= $rowspan ?>"><?= esc($misi ? $misi['misi'] : '-') ?>
                                            </td>
                                            <td class="border p-2" rowspan="<?= $rowspan ?>"><?= esc($sasaran['sasaran']) ?></td>
                                        <?php endif; ?>
                                        <td class="border p-2"><?= esc($indikator['indikator']) ?></td>
                                        <td class="border p-2"><?= esc($indikator['target']) ?></td>
                                        <td class="border p-2"><?= esc($indikator['satuan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
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
                                <?php foreach ($pk_data['program'] as $program): ?>
                                    <tr>
                                        <td class="border p-2"><?= $no_program++ ?></td>
                                        <td class="border p-2"><?= esc($program['program_kegiatan']) ?></td>
                                        <td class="border p-2">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                        <td class="border p-2"><?= esc(ucwords($pk_data['jenis'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis . '/cetak/' . $pk_data['id']) ?>"
                            class="btn btn-primary btn-sm text-white" target="_blank">
                            <i class="fas fa-download me-1"></i> Download
                        </a>

                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $pk_data['jenis'] . '/edit/' . $pk_data['id']) ?>"
                            class="btn btn-success btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <button class="btn btn-danger btn-sm"
                            onclick="deletePk(<?= $pk_data['id'] ?>, '<?= base_url() ?>')">
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
    <!-- Global JS variables for PK page -->
    <script>
        window.base_url = '<?= base_url() ?>';
        window.jenis = '<?= $jenis ?>';
    </script>
    <!-- PK page logic -->
    <script src="<?= base_url('assets/js/adminOpd/pk/pk.js') ?>"></script>
</body>

</html>
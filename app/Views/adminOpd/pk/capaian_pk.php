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
            <h2 class="h3 fw-bold text-success text-center mb-4">CAPAIAN PK <?= strtoupper($jenis) ?></h2>
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
            </div>
            <div class="table-responsive">
                <?php if (isset($pk_data['jenis']) && $pk_data['jenis'] === $jenis): ?>
                    <!-- Tabel Misi Bupati untuk jenis JPT -->
                    <?php if (!empty($pk_data['id']) && strtolower($jenis) === 'jpt'): ?>
                        <?php $misiBupati = model('App\\Models\\RpjmdModel')->getAllMisi(); ?>
                        <?php $pkMisiRows = model('App\\Models\\PkModel')->db->table('pk_misi')->where('pk_id', $pk_data['id'])->get()->getResultArray(); ?>
                        <?php if (!empty($pkMisiRows)): ?>
                            <h4 class="h5 fw-bold text-primary text-left mb-2">Misi Bupati</h4>
                            <table class="table table-bordered table-striped text-center small mb-4"
                                style="max-width:600px; margin-left:0;">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="border p-2" style="width:50px;">NO</th>
                                        <th class="border p-2" style="width:500px;">Misi Bupati</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no_misi = 1; ?>
                                    <?php foreach ($pkMisiRows as $row): ?>
                                        <?php $misi = model('App\\Models\\RpjmdModel')->getMisiById($row['rpjmd_misi_id']); ?>
                                        <?php if ($misi): ?>
                                            <tr>
                                                <td class="border p-2" style="width:50px;"><?= $no_misi++ ?></td>
                                                <td class="border p-2" style="width:500px;"><?= esc($misi['misi']) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
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
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">SATUAN</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">CAPAIAN</th>
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
                                        <td class="border p-2"><?= esc($indikator['satuan']) ?></td>
                                        <td class="border p-2"><?= esc($indikator['target']) ?></td>
                                        <td class="border p-2"><?= esc(data: $indikator['capaian']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= base_url('adminopd/pk/' . $jenis . '/cetak/' . $pk_data['id']) ?>"
                            class="btn btn-primary btn-sm text-white" target="_blank">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                        <a href="<?= base_url(($jenis==='bupati' ? 'adminkab/capaian_pk/' : 'adminopd/capaian_pk/') . $pk_data['jenis'] . '/' . $pk_data['id']) ?>"
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
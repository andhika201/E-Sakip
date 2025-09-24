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
            <h2 class="h3 fw-bold text-success text-center mb-4">SET CAPAIAN PK <?= strtoupper($jenis) ?></h2>
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
                    <form method="POST" action="<?= base_url(($jenis==='bupati'? 'adminkab/':'adminopd/').'capaian_pk/'.$jenis.'/setcapaian/'. $pk_data['id']) ?>">
                        <h4 class="h3 fw-bold text-success text-left mb-4">SASARAN DAN INDIKATOR</h4>
                        <table class="table table-bordered table-striped text-center small mb-5">
                            <thead class="table-success">
                                <tr>
                                    <th class="border p-2">NO</th>
                                    <th class="border p-2">SASARAN</th>
                                    <th class="border p-2">INDIKATOR</th>
                                    <th class="border p-2">SATUAN</th>
                                    <th class="border p-2">TARGET</th>
                                    <th class="border p-2">Triwulan 1</th>
                                    <th class="border p-2">Triwulan 2</th>
                                    <th class="border p-2">Triwulan 3</th>
                                    <th class="border p-2">Triwulan 4</th>
                                    <th class="border p-2">%</th>
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
                                            <td class="border p-2">
                                                <input type="text" name="triwulan1[<?= $indikator['id'] ?>]" value="<?= esc($indikator['triwulan1'] ?? '') ?>" class="form-control" />
                                            </td>
                                            <td class="border p-2">
                                                <input type="text" name="triwulan2[<?= $indikator['id'] ?>]" value="<?= esc($indikator['triwulan2'] ?? '') ?>" class="form-control" />
                                            </td>
                                            <td class="border p-2">
                                                <input type="text" name="triwulan3[<?= $indikator['id'] ?>]" value="<?= esc($indikator['triwulan3'] ?? '') ?>" class="form-control" />
                                            </td>
                                            <td class="border p-2">
                                                <input type="text" name="triwulan4[<?= $indikator['id'] ?>]" value="<?= esc($indikator['triwulan4'] ?? '') ?>" class="form-control" />
                                            </td>
                                            <td class="border p-2">
                                                <input type="text" name="persen[<?= $indikator['id'] ?>]" value="<?= esc($indikator['persen'] ?? '') ?>" class="form-control" readonly style="background:#f8f9fa;" />
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/capaian_pk/' : 'adminopd/capaian_pk/') . $jenis) ?>"
                                class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Simpan
                            </button>
                        </div>
                    </form>
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
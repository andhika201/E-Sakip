<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LAKIP OPD - e-SAKIP</title>
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
                <h2 class="h3 fw-bold text-success text-center mb-4">LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH
                    DAERAH</h2>

                <!-- Filter -->
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div class="d-flex flex-column flex-md-row gap-3 flex-fill">
                        <select id="tahun_filter" class="form-select border-secondary w-50" onchange="filterData()">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($availableYears as $year): ?>
                                <option value="<?= $year ?>" <?= ($filters['tahun'] ?? '') == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="status_filter" class="form-select border-secondary w-50" onchange="filterData()">
                            <option value="">Semua Status</option>
                            <option value="selesai" <?= (isset($filters['status']) && $filters['status'] == 'selesai') ? 'selected' : '' ?>>Selesai</option>
                            <option value="draft" <?= (isset($filters['status']) && $filters['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                </div>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center small">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">NO</th>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">SATUAN</th>
                                <th class="border p-2">TARGET TAHUN SEBELUMNYA</th>
                                <th class="border p-2">CAPAIAN TAHUN SEBELUMNYA</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">CAPAIAN TAHUN INI</th>
                                <th class="border p-2">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $dataSource = ($role === 'admin_kab') ? $rpjmdData : $renstraData;
                            ?>
                            <?php foreach ($dataSource as $row): ?>
                                <?php
                                $sasaranText = ($role === 'admin_kab') ? $row['sasaran_rpjmd'] : $row['sasaran'];
                                $indikatorCount = count($row['indikator_sasaran'] ?? []);
                                $firstRow = true;
                                ?>

                                <?php foreach ($row['indikator_sasaran'] ?? [] as $indikator): ?>
                                    <?php
                                    $iku = null;
                                    foreach ($lakip ?? [] as $item) {
                                        if (
                                            ($role === 'admin_kab' && ($item['rpjmd_id'] ?? null) == $indikator['id']) ||
                                            ($role === 'admin_opd' && ($item['renstra_id'] ?? null) == $indikator['id'])
                                        ) {
                                            $iku = $item;
                                            break;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <?php if ($firstRow): ?>
                                            <td rowspan="<?= $indikatorCount ?>" class="align-middle"><?= $no++ ?></td>
                                            <td rowspan="<?= $indikatorCount ?>" class="align-middle text-start">
                                                <?= esc($sasaranText) ?>
                                            </td>
                                            <?php $firstRow = false; ?>
                                        <?php endif; ?>

                                        <td><?= esc($indikator['indikator_sasaran']) ?></td>
                                        <td><?= esc($indikator['satuan']) ?></td>



                                        <td class="text-center"><?= esc($iku['target_lalu'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($iku['capaian_lalu'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php
                                            $targetTahun = $indikator['target_tahunan'][0]['target'] ?? null;
                                            echo $targetTahun ? esc($targetTahun) : '-';
                                            ?>
                                        </td>
                                        <td class="text-center"><?= esc($iku['capaian_tahun_ini'] ?? '-') ?></td>


                                        <td>
                                            <?php if (!empty($indikator['id'])): ?>
                                                <?php if (empty($iku['definisi'])): ?>
                                                    <a href="<?= base_url('adminopd/iku/tambah/' . $indikator['id']) ?>"
                                                        class="btn btn-sm btn-success" title="Tambah IKU">
                                                        <i class="bi bi-plus-circle"></i> Tambah
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= base_url('adminopd/iku/edit/' . $indikator['id']) ?>"
                                                        class="btn btn-sm btn-warning text-dark" title="Edit IKU">
                                                        <i class="bi bi-pencil-square"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div> <!-- End of Content Wrapper -->
</body>
<script>
function filterData() {
    const tahun = document.getElementById('tahun_filter').value;
    const status = document.getElementById('status_filter') ? document.getElementById('status_filter').value : '';
    const params = new URLSearchParams();

    if (tahun) params.append('tahun', tahun);
    if (status) params.append('status', status);

    window.location.href = "?" + params.toString();
}
</script>


</html>
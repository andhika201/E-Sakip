<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Target Rencana</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit Target Rencana</h2>

            <?php
// <<<<<<< monev
                    $db = \Config\Database::connect();
                    // Ambil data indikator berdasarkan renja_indikator_sasaran_id dari target
                    $indikator = $db->table('renja_indikator_sasaran')
                        ->where('id', $target['renja_indikator_sasaran_id'])
                        ->get()->getRowArray();
                    $selectedSatuan = $indikator ? $indikator['satuan'] : '';
                    $selectedTarget = $indikator ? $indikator['target'] : '';
                    $selectedTahun = $indikator ? $indikator['tahun'] : '';
                    // Ambil nama sasaran renja
                    $sasaranRenja = '';
                    $renjaSasaranId = $indikator ? $indikator['renja_sasaran_id'] : null;
                    if ($renjaSasaranId) {
                        $sasaran = $db->table('renja_sasaran')->select('sasaran_renja')->where('id', $renjaSasaranId)->get()->getRowArray();
                        if ($sasaran) $sasaranRenja = $sasaran['sasaran_renja'];
                    }
// =======
//                 $db = \Config\Database::connect();
//                 $indikator = $db->table('renja_indikator_sasaran')
//                     ->select('satuan, target, tahun')
//                     ->where('renja_sasaran_id', $target['renja_sasaran_id'])
//                     ->get()->getRowArray();
//                 $selectedSatuan = $indikator ? $indikator['satuan'] : '';
//                 $selectedTarget = $indikator ? $indikator['target'] : '';
//                 $selectedTahun = $indikator ? $indikator['tahun'] : '';
//                 // Ambil nama sasaran renja
//                 $sasaranRenja = '';
//                 $sasaran = $db->table('renja_sasaran')->select('sasaran_renja')->where('id', $target['renja_sasaran_id'])->get()->getRowArray();
//                 if ($sasaran) $sasaranRenja = $sasaran['sasaran_renja'];
// >>>>>>> main
            ?>
            <form action="<?= base_url('adminopd/target/update/' . $target['id']) ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="renja_indikator_sasaran_id" value="<?= esc($indikator['id']) ?>">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Indikator</label>
                        <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="rencana_aksi" class="form-label">Rencana Aksi</label>
                        <input type="text" name="rencana_aksi" id="rencana_aksi" class="form-control" value="<?= esc($target['rencana_aksi']) ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" value="<?= esc($indikator['satuan']) ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Target</label>
                        <input type="text" class="form-control" value="<?= esc($indikator['target']) ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Tahun</label>
                        <input type="text" class="form-control" value="<?= esc($indikator['tahun']) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="capaian" class="form-label">Baseline (Capaian)</label>
                        <input type="text" name="capaian" id="capaian" class="form-control" value="<?= esc($target['capaian']) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Target Triwulan</label>
                    <div class="row">
                        <div class="col"><input type="text" name="target_triwulan_1" class="form-control" value="<?= esc($target['target_triwulan_1']) ?>" placeholder="Triwulan I"></div>
                        <div class="col"><input type="text" name="target_triwulan_2" class="form-control" value="<?= esc($target['target_triwulan_2']) ?>" placeholder="Triwulan II"></div>
                        <div class="col"><input type="text" name="target_triwulan_3" class="form-control" value="<?= esc($target['target_triwulan_3']) ?>" placeholder="Triwulan III"></div>
                        <div class="col"><input type="text" name="target_triwulan_4" class="form-control" value="<?= esc($target['target_triwulan_4']) ?>" placeholder="Triwulan IV"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                    <input type="text" name="penanggung_jawab" id="penanggung_jawab" class="form-control" value="<?= esc($target['penanggung_jawab']) ?>">
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= base_url('adminopd/target') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>
</html>
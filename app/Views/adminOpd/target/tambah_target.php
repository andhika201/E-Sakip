<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Target Rencana</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        .alert { transition: all 0.3s ease; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    </style>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Navbar/Header -->
    <?= $this->include('adminOpd/templates/header.php'); ?>

    <!-- Sidebar -->
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <!-- Konten Utama -->
    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah Target Rencana</h2>

            <!-- Alert Container -->
            <div id="alert-container">
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
            </div>

            <?php
                $selectedRenjaSasaran = isset($_GET['r']) ? $_GET['r'] : '';
                $selectedRenjaSasaranNama = '';
                $selectedSatuan = '';
                $selectedTarget = '';
                $selectedTahun = '';
                $db = \Config\Database::connect();
                $indikator = $db->table('renja_indikator_sasaran')
                    ->select('satuan, target, tahun')
                    ->where('renja_sasaran_id', $selectedRenjaSasaran)
                    ->get()->getRowArray();
                if ($indikator) {
                    $selectedSatuan = $indikator['satuan'];
                    $selectedTarget = $indikator['target'];
                    $selectedTahun = $indikator['tahun'];
                }
                foreach ($renjaSasaran as $s) {
                    if ($selectedRenjaSasaran == $s['id']) {
                        $selectedRenjaSasaranNama = $s['sasaran_renja'];
                        break;
                    }
                }
            ?>
            <form action="<?= base_url('adminopd/target/save') ?>" method="post">
                <?= csrf_field() ?>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Sasaran Renja</label>
                        <input type="hidden" name="renja_sasaran_id" value="<?= esc($selectedRenjaSasaran) ?>">
                        <input type="text" class="form-control" value="<?= esc($selectedRenjaSasaranNama) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="rencana_aksi" class="form-label">Rencana Aksi</label>
                        <input type="text" name="rencana_aksi" id="rencana_aksi" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="satuan" class="form-label">Satuan</label>
                        <input type="text" name="satuan" id="satuan" class="form-control" value="<?= esc($selectedSatuan) ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="capaian" class="form-label">Baseline (Capaian)</label>
                        <input type="text" name="capaian" id="capaian" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label for="target" class="form-label">Target</label>
                        <input type="text" name="target" id="target" class="form-control" value="<?= esc($selectedTarget) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="number" name="tahun" id="tahun" class="form-control" value="<?= esc($selectedTahun) ?>" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Target Triwulan</label>
                    <div class="row">
                        <div class="col"><input type="text" name="target_triwulan_1" class="form-control" placeholder="Triwulan I"></div>
                        <div class="col"><input type="text" name="target_triwulan_2" class="form-control" placeholder="Triwulan II"></div>
                        <div class="col"><input type="text" name="target_triwulan_3" class="form-control" placeholder="Triwulan III"></div>
                        <div class="col"><input type="text" name="target_triwulan_4" class="form-control" placeholder="Triwulan IV"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                    <input type="text" name="penanggung_jawab" id="penanggung_jawab" class="form-control">
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= base_url('adminopd/target') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>
</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Target Rencana</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        .alert {
            transition: all 0.3s ease;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
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
            $db = \Config\Database::connect();
            $indikatorId = isset($_GET['indikator']) ? $_GET['indikator'] : null;
            $indikator = null;
            if ($indikatorId) {
                $indikator = $db->table('renja_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()->getRowArray();
            }
            if (!$indikator) {
                echo '<div class="alert alert-danger">Indikator tidak ditemukan. Silakan pilih indikator dari tabel.</div>';
                return;
            }
            ?>
            <form action="<?= base_url('adminopd/target/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="renja_indikator_sasaran_id" value="<?= esc($indikator['id']) ?>">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Indikator</label>
                        <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>"
                            readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="rencana_aksi" class="form-label">Rencana Aksi</label>
                        <input type="text" name="rencana_aksi" id="rencana_aksi" class="form-control" required>
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
                        <input type="text" name="capaian" id="capaian" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Target Triwulan</label>
                    <div class="row">
                        <div class="col"><input type="text" name="target_triwulan_1" class="form-control"
                                placeholder="Triwulan I"></div>
                        <div class="col"><input type="text" name="target_triwulan_2" class="form-control"
                                placeholder="Triwulan II"></div>
                        <div class="col"><input type="text" name="target_triwulan_3" class="form-control"
                                placeholder="Triwulan III"></div>
                        <div class="col"><input type="text" name="target_triwulan_4" class="form-control"
                                placeholder="Triwulan IV"></div>
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
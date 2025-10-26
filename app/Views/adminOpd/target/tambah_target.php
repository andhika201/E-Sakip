<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Target Rencana</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        .alert {
            transition: all .3s ease;
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Tambah Target Rencana</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?php if (empty($rt)): ?>
                <div class="alert alert-danger">Data tidak ditemukan. Silakan kembali ke daftar.</div>
                <div class="text-end">
                    <a href="<?= base_url('adminopd/target') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            <?php else: ?>
                <form action="<?= base_url('adminopd/target/save') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <!-- Hidden essentials -->
                    <input type="hidden" name="renstra_target_id" value="<?= (int) $rt['renstra_target_id'] ?>">
                    <?php if (($role ?? '') === 'admin_kab' && !empty($opdIdToUse)): ?>
                        <input type="hidden" name="opd_id" value="<?= (int) $opdIdToUse ?>">
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <label class="form-label">Indikator (RENSTRA)</label>
                            <input type="text" class="form-control" value="<?= esc($rt['indikator_sasaran']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" value="<?= esc($rt['satuan'] ?? '-') ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Tahun (Renstra)</label>
                            <input type="text" class="form-control" value="<?= esc($rt['tahun']) ?>" readonly>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Target (Renstra)</label>
                            <input type="text" class="form-control" value="<?= esc($rt['target']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="capaian">Baseline (Capaian)</label>
                            <input type="text" class="form-control" id="capaian" name="capaian"
                                value="<?= old('capaian') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="rencana_aksi">Rencana Aksi</label>
                        <input type="text" class="form-control" id="rencana_aksi" name="rencana_aksi"
                            value="<?= old('rencana_aksi') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Triwulan</label>
                        <div class="row g-2">
                            <div class="col"><input type="text" name="target_triwulan_1" class="form-control"
                                    placeholder="Triwulan I" value="<?= old('target_triwulan_1') ?>"></div>
                            <div class="col"><input type="text" name="target_triwulan_2" class="form-control"
                                    placeholder="Triwulan II" value="<?= old('target_triwulan_2') ?>"></div>
                            <div class="col"><input type="text" name="target_triwulan_3" class="form-control"
                                    placeholder="Triwulan III" value="<?= old('target_triwulan_3') ?>"></div>
                            <div class="col"><input type="text" name="target_triwulan_4" class="form-control"
                                    placeholder="Triwulan IV" value="<?= old('target_triwulan_4') ?>"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="penanggung_jawab">Penanggung Jawab</label>
                        <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab"
                            value="<?= old('penanggung_jawab') ?>">
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
            <?php endif; ?>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
</body>

</html>
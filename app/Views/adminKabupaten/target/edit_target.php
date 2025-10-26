<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Target Rencana</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">

            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Edit Target Rencana</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <?php if (empty($detail)): ?>
                <div class="alert alert-danger">Data tidak ditemukan. Silakan kembali ke daftar.</div>
                <div class="text-end">
                    <a href="<?= base_url('adminKabupaten/target') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            <?php else: ?>
                <form action="<?= base_url('adminKabupaten/target/update/' . (int) $detail['id']) ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <!-- Info kontekstual (read-only) -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label">Indikator</label>
                            <input type="text" class="form-control" value="<?= esc($detail['indikator_sasaran'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sasaran Renstra</label>
                            <input type="text" class="form-control" value="<?= esc($detail['sasaran_renstra'] ?? '-') ?>"
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" value="<?= esc($detail['satuan'] ?? '-') ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">Target (Renstra)</label>
                            <input type="text" class="form-control" value="<?= esc($detail['indikator_target'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">Tahun (Renstra)</label>
                            <input type="text" class="form-control" value="<?= esc($detail['tahun'] ?? '-') ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="capaian">Baseline (Capaian)</label>
                            <input type="text" class="form-control" id="capaian" name="capaian"
                                value="<?= esc($detail['capaian'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Field yang bisa diubah -->
                    <div class="mb-3">
                        <label for="rencana_aksi" class="form-label">Rencana Aksi</label>
                        <input type="text" name="rencana_aksi" id="rencana_aksi" class="form-control"
                            value="<?= esc($detail['rencana_aksi'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Triwulan</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="text" name="target_triwulan_1" class="form-control" placeholder="Triwulan I"
                                    value="<?= esc($detail['target_triwulan_1'] ?? '') ?>">
                            </div>
                            <div class="col">
                                <input type="text" name="target_triwulan_2" class="form-control" placeholder="Triwulan II"
                                    value="<?= esc($detail['target_triwulan_2'] ?? '') ?>">
                            </div>
                            <div class="col">
                                <input type="text" name="target_triwulan_3" class="form-control" placeholder="Triwulan III"
                                    value="<?= esc($detail['target_triwulan_3'] ?? '') ?>">
                            </div>
                            <div class="col">
                                <input type="text" name="target_triwulan_4" class="form-control" placeholder="Triwulan IV"
                                    value="<?= esc($detail['target_triwulan_4'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                        <input type="text" name="penanggung_jawab" id="penanggung_jawab" class="form-control"
                            value="<?= esc($detail['penanggung_jawab'] ?? '') ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= base_url('adminKabupaten/target') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</body>

</html>
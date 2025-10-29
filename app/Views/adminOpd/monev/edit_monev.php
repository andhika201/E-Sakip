<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Capaian Monev</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        .btn:disabled {
            opacity: .6;
            cursor: not-allowed
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Edit Capaian Monev</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('adminopd/monev/update/' . (int) ($monev['id'] ?? 0)) ?>" method="post"
                id="formMonevEdit" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="target_rencana_id" value="<?= (int) ($monev['target_rencana_id'] ?? 0) ?>">

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Sasaran RENSTRA</label>
                        <input type="text" class="form-control"
                            value="<?= esc($monev['sasaran_renstra'] ?? $monev['sasaran_renja'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Indikator</label>
                        <input type="text" class="form-control" value="<?= esc($monev['indikator_sasaran'] ?? '-') ?>"
                            readonly>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" value="<?= esc($monev['satuan'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Target (RENSTRA)</label>
                        <input type="text" class="form-control" value="<?= esc($monev['indikator_target'] ?? '-') ?>"
                            readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Tahun</label>
                        <input type="text" class="form-control" value="<?= esc($monev['indikator_tahun'] ?? '-') ?>"
                            readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Penanggung Jawab</label>
                        <input type="text" class="form-control" value="<?= esc($monev['penanggung_jawab'] ?? '-') ?>"
                            readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Aksi</label>
                    <input type="text" class="form-control" value="<?= esc($monev['rencana_aksi'] ?? '-') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Capaian Triwulan</label>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_1" class="form-control"
                                value="<?= esc($monev['capaian_triwulan_1'] ?? '') ?>" placeholder="Triwulan I">
                        </div>
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_2" class="form-control"
                                value="<?= esc($monev['capaian_triwulan_2'] ?? '') ?>" placeholder="Triwulan II">
                        </div>
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_3" class="form-control"
                                value="<?= esc($monev['capaian_triwulan_3'] ?? '') ?>" placeholder="Triwulan III">
                        </div>
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_4" class="form-control"
                                value="<?= esc($monev['capaian_triwulan_4'] ?? '') ?>" placeholder="Triwulan IV">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Total Capaian <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="total" id="totalCapaianEdit" class="form-control"
                                value="<?= esc($monev['total'] ?? '') ?>" required>
                            <small class="text-muted">Isi manual (tidak dihitung otomatis).</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= base_url('adminopd/monev') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success" id="btnSubmit">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>

    <script>
        (function () { // cegah double submit
            const f = document.getElementById('formMonevEdit'), b = document.getElementById('btnSubmit');
            if (f && b) { f.addEventListener('submit', () => { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...'; }); }
        })();
    </script>
</body>

</html>
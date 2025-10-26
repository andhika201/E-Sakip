<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Capaian Monev</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .btn:disabled {
            opacity: .6;
            cursor: not-allowed
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Edit Capaian Monev</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?php
            // susun URL kembali dengan filter
            $backUrl = base_url('adminkab/monev');
            $req = service('request');
            $opdIdQS = isset($opdId) ? (int) $opdId : (int) ($req->getGet('opd_id') ?? 0);
            $tahunQS = isset($tahunQS) ? (string) $tahunQS : (string) ($req->getGet('tahun') ?? 'all');
            $qs = [];
            if ($opdIdQS > 0)
                $qs['opd_id'] = $opdIdQS;
            if ($tahunQS !== '')
                $qs['tahun'] = $tahunQS;
            if (!empty($qs))
                $backUrl .= '?' . http_build_query($qs);
            ?>

            <form action="<?= base_url('adminkab/monev/update/' . (int) $monev['id']) ?>" method="post"
                id="formMonevEdit" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="target_rencana_id" value="<?= (int) ($monev['target_rencana_id'] ?? 0) ?>">

                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Sasaran RENSTRA</label>
                        <input class="form-control" value="<?= esc($monev['sasaran_renstra'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Indikator</label>
                        <input class="form-control" value="<?= esc($monev['indikator_sasaran'] ?? '-') ?>" readonly>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Satuan</label>
                        <input class="form-control" value="<?= esc($monev['satuan'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Target (Renstra)</label>
                        <input class="form-control" value="<?= esc($monev['indikator_target'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">Tahun</label>
                        <input class="form-control" value="<?= esc($monev['indikator_tahun'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Penanggung Jawab</label>
                        <input class="form-control" value="<?= esc($monev['penanggung_jawab'] ?? '-') ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Aksi</label>
                    <input class="form-control" value="<?= esc($monev['rencana_aksi'] ?? '-') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Capaian Triwulan</label>
                    <div class="row g-2 mb-2">
                        <div class="col"><input type="number" step="any" min="0" name="capaian_triwulan_1"
                                class="form-control" value="<?= esc($monev['capaian_triwulan_1'] ?? '') ?>"
                                placeholder="Triwulan I"></div>
                        <div class="col"><input type="number" step="any" min="0" name="capaian_triwulan_2"
                                class="form-control" value="<?= esc($monev['capaian_triwulan_2'] ?? '') ?>"
                                placeholder="Triwulan II"></div>
                        <div class="col"><input type="number" step="any" min="0" name="capaian_triwulan_3"
                                class="form-control" value="<?= esc($monev['capaian_triwulan_3'] ?? '') ?>"
                                placeholder="Triwulan III"></div>
                        <div class="col"><input type="number" step="any" min="0" name="capaian_triwulan_4"
                                class="form-control" value="<?= esc($monev['capaian_triwulan_4'] ?? '') ?>"
                                placeholder="Triwulan IV"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Total Capaian <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="total" id="totalCapaianEdit" class="form-control"
                                value="<?= esc($monev['total'] ?? '') ?>" required>
                            <small class="text-muted">Diisi manual (tanpa hitung otomatis).</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $backUrl ?>" class="btn btn-secondary"><i
                            class="fas fa-arrow-left me-1"></i>Kembali</a>
                    <button type="submit" class="btn btn-success" id="btnSubmit"><i class="fas fa-save me-1"></i>Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

    <script>
        (function () {
            const f = document.getElementById('formMonevEdit'), b = document.getElementById('btnSubmit');
            if (f && b) { f.addEventListener('submit', () => { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...'; }); }
        })();
    </script>
</body>

</html>
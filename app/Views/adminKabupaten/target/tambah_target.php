<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Target Rencana</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .alert {
            transition: all .3s ease;
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .form-text {
            font-size: .85rem;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Tambah Target Rencana</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?php if (empty($rt)): ?>
                <div class="alert alert-danger">Data tidak ditemukan. Silakan kembali ke daftar.</div>
                <div class="text-end">
                    <a href="<?= base_url('adminkab/target') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            <?php else: ?>
                <form action="<?= base_url('adminkab/target/save') ?>" method="post" novalidate id="formTambah">
                    <?= csrf_field() ?>

                    <!-- Hidden essentials -->
                    <input type="hidden" name="renstra_target_id" value="<?= (int) ($rt['renstra_target_id'] ?? 0) ?>">
                    <input type="hidden" name="tahun_qs" value="<?= esc($tahunQS ?? '') ?>">

                    <?php if (($role ?? '') === 'admin_kab'): ?>
                        <div class="mb-3">
                            <label class="form-label">Pilih OPD <span class="text-danger">*</span></label>
                            <select name="opd_id" class="form-select" required>
                                <option value="">-- Pilih OPD --</option>
                                <?php foreach (($opdList ?? []) as $opd): ?>
                                    <option value="<?= (int) $opd['id'] ?>" <?= ((string) ($opdIdToUse ?? '') === (string) $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Indikator ini dimiliki oleh OPD ID <b><?= (int) ($rt['opd_id'] ?? 0) ?></b>.
                                Jika memilih OPD lain, penyimpanan akan ditolak.
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- admin_opd: kunci ke OPD login -->
                        <input type="hidden" name="opd_id" value="<?= (int) ($opdIdToUse ?? 0) ?>">
                    <?php endif; ?>

                    <!-- Info indikator (read-only) -->
                    <div class="row mb-3">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <label class="form-label">Indikator (RENSTRA)</label>
                            <input type="text" class="form-control" value="<?= esc($rt['indikator_sasaran'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" value="<?= esc($rt['satuan'] ?? '-') ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Tahun (RENSTRA)</label>
                            <input type="text" class="form-control" value="<?= esc($rt['tahun'] ?? '-') ?>" readonly>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Target (RENSTRA)</label>
                            <input type="text" class="form-control" value="<?= esc($rt['target'] ?? '-') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="capaian">Baseline (Capaian)</label>
                            <input type="text" class="form-control" id="capaian" name="capaian"
                                value="<?= esc(old('capaian')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="rencana_aksi">Rencana Aksi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rencana_aksi" name="rencana_aksi"
                            value="<?= esc(old('rencana_aksi')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Triwulan</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="text" name="target_triwulan_1" class="form-control" placeholder="Triwulan I"
                                    value="<?= esc(old('target_triwulan_1')) ?>">
                            </div>
                            <div class="col">
                                <input type="text" name="target_triwulan_2" class="form-control" placeholder="Triwulan II"
                                    value="<?= esc(old('target_triwulan_2')) ?>">
                            </div>
                            <div class="col">
                                <input type="text" name="target_triwulan_3" class="form-control" placeholder="Triwulan III"
                                    value="<?= esc(old('target_triwulan_3')) ?>">
                            </div>
                            <div class="col">
                                <input type="text" name="target_triwulan_4" class="form-control" placeholder="Triwulan IV"
                                    value="<?= esc(old('target_triwulan_4')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="penanggung_jawab">Penanggung Jawab</label>
                        <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab"
                            value="<?= esc(old('penanggung_jawab')) ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <?php
                        // Kembali tetap membawa filter opd & tahun bila ada
                        $backParams = [];
                        if (!empty($opdIdToUse))
                            $backParams['opd_id'] = (int) $opdIdToUse;
                        if (!empty($tahunQS))
                            $backParams['tahun'] = $tahunQS;
                        $backUrl = base_url('adminkab/target') . (empty($backParams) ? '' : '?' . http_build_query($backParams));
                        ?>
                        <a href="<?= $backUrl ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-success" id="btnSubmit">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

    <script>
        // Cegah double submit
        (function () {
            const form = document.getElementById('formTambah');
            const btn = document.getElementById('btnSubmit');
            if (form && btn) {
                form.addEventListener('submit', function () {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                });
            }
        })();
    </script>
</body>

</html>
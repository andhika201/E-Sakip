<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Capaian Monev</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Tambah Capaian Monev</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?php if (empty($target)): ?>
                <div class="alert alert-danger">Data target tidak ditemukan. Silakan kembali ke daftar.</div>
                <div class="text-end">
                    <a href="<?= base_url('adminkab/monev') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            <?php else: ?>

                <?php
                $mode = $mode ?? 'opd';
                $labelSas = ($mode === 'kab' ? 'Sasaran RPJMD' : 'Sasaran RENSTRA');
                $labelTgt = ($mode === 'kab' ? 'Target (RPJMD)' : 'Target (RENSTRA)');
                ?>

                <form action="<?= base_url('adminkab/monev/save') ?>" method="post" id="formMonev" novalidate>
                    <?= csrf_field() ?>

                    <!-- Hidden context -->
                    <input type="hidden" name="target_rencana_id"
                        value="<?= (int) ($target['target_id'] ?? $target['id'] ?? 0) ?>">
                    <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                    <input type="hidden" name="tahun" value="<?= esc($tahun ?? 'all') ?>">
                    <input type="hidden" name="opd_filter" value="<?= esc($opdFilter ?? 'all') ?>">

                    <!-- Info target (read-only) -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label"><?= esc($labelSas) ?></label>
                            <input type="text" class="form-control" value="<?= esc($target['sasaran_renstra'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rencana Aksi</label>
                            <input type="text" class="form-control" value="<?= esc($target['rencana_aksi'] ?? '-') ?>"
                                readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" value="<?= esc($target['satuan'] ?? '-') ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label"><?= esc($labelTgt) ?></label>
                            <input type="text" class="form-control" value="<?= esc($target['indikator_target'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="form-label">Tahun</label>
                            <input type="text" class="form-control" value="<?= esc($target['indikator_tahun'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" class="form-control" value="<?= esc($target['penanggung_jawab'] ?? '-') ?>"
                                readonly>
                        </div>
                    </div>

                    <!-- Input capaian -->
                    <div class="mb-3">
                        <label class="form-label">Capaian Triwulan</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_1" class="form-control"
                                    placeholder="Triwulan I" value="<?= esc(old('capaian_triwulan_1')) ?>"
                                    oninput="hitungTotal()">
                            </div>
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_2" class="form-control"
                                    placeholder="Triwulan II" value="<?= esc(old('capaian_triwulan_2')) ?>"
                                    oninput="hitungTotal()">
                            </div>
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_3" class="form-control"
                                    placeholder="Triwulan III" value="<?= esc(old('capaian_triwulan_3')) ?>"
                                    oninput="hitungTotal()">
                            </div>
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_4" class="form-control"
                                    placeholder="Triwulan IV" value="<?= esc(old('capaian_triwulan_4')) ?>"
                                    oninput="hitungTotal()">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Total Capaian (otomatis)</label>
                                <input type="number" step="any" name="total" id="totalCapaian" class="form-control"
                                    value="<?= esc(old('total')) ?>">
                                <small class="text-muted d-block mt-1">
                                    Nilai total akan terisi otomatis sebagai rata-rata dari capaian triwulan yang diisi,
                                    namun tetap bisa Anda ubah manual jika diperlukan.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= base_url('adminkab/monev?' . http_build_query([
                            'mode' => $mode ?? 'opd',
                            'tahun' => $tahun ?? 'all',
                            'opd_id' => $opdFilter ?? 'all',
                        ])) ?>" class="btn btn-secondary">
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
        function hitungTotal() {
            const get = n => {
                const el = document.getElementsByName(n)[0];
                if (!el) return null;
                const v = el.value ?? '';
                return v === '' ? null : parseFloat(v);
            };
            const vals = [
                get('capaian_triwulan_1'),
                get('capaian_triwulan_2'),
                get('capaian_triwulan_3'),
                get('capaian_triwulan_4')
            ].filter(v => v !== null && !isNaN(v));

            const total = (vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : '');
            const el = document.getElementById('totalCapaian');
            if (el) el.value = (total === '' ? '' : Math.round(total));
        }

        window.addEventListener('DOMContentLoaded', function () {
            hitungTotal();
        });

        (function () {
            const form = document.getElementById('formMonev');
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
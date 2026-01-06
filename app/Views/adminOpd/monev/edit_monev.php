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
            cursor: not-allowed;
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

                <!-- Hidden -->
                <input type="hidden" name="target_rencana_id" value="<?= (int) ($monev['target_rencana_id'] ?? 0) ?>">

                <!-- Info sasaran & indikator -->
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

                <!-- Info indikator & PJ -->
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

                <!-- Rencana Aksi -->
                <div class="mb-3">
                    <label class="form-label">Rencana Aksi</label>
                    <input type="text" class="form-control" value="<?= esc($monev['rencana_aksi'] ?? '-') ?>" readonly>
                </div>

                <!-- Input capaian -->
                <div class="mb-3">
                    <label class="form-label">Capaian Triwulan</label>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_1" class="form-control"
                                value="<?= esc(old('capaian_triwulan_1', $monev['capaian_triwulan_1'] ?? '')) ?>"
                                placeholder="Triwulan I" oninput="hitungTotalEdit()">
                        </div>
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_2" class="form-control"
                                value="<?= esc(old('capaian_triwulan_2', $monev['capaian_triwulan_2'] ?? '')) ?>"
                                placeholder="Triwulan II" oninput="hitungTotalEdit()">
                        </div>
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_3" class="form-control"
                                value="<?= esc(old('capaian_triwulan_3', $monev['capaian_triwulan_3'] ?? '')) ?>"
                                placeholder="Triwulan III" oninput="hitungTotalEdit()">
                        </div>
                        <div class="col">
                            <input type="number" step="any" min="0" name="capaian_triwulan_4" class="form-control"
                                value="<?= esc(old('capaian_triwulan_4', $monev['capaian_triwulan_4'] ?? '')) ?>"
                                placeholder="Triwulan IV" oninput="hitungTotalEdit()">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Total Capaian</label>
                            <input type="number" step="any" name="total" id="totalCapaianEdit" class="form-control"
                                value="<?= esc(old('total', $monev['total'] ?? '')) ?>">
                            <small class="text-muted d-block mt-1">
                                Nilai total dapat dihitung otomatis sebagai rata-rata dari capaian triwulan yang diisi,
                                namun tetap bisa Anda ubah manual jika diperlukan.
                            </small>
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
        // Hitung total sebagai rata-rata nilai triwulan yang terisi (edit)
        function hitungTotalEdit() {
            const get = (n) => {
                const el = document.getElementsByName(n)[0];
                if (!el) return null;
                const v = el.value ?? '';
                return v === '' ? null : parseFloat(v);
            };

            const vals = [
                get('capaian_triwulan_1'),
                get('capaian_triwulan_2'),
                get('capaian_triwulan_3'),
                get('capaian_triwulan_4'),
            ].filter(v => v !== null && !isNaN(v));

            const total = (vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : '');
            const elTotal = document.getElementById('totalCapaianEdit');
            if (elTotal) {
                elTotal.value = (total === '' ? '' : Math.round(total));
            }
        }

        // Panggil sekali saat halaman load (untuk data awal / old input)
        window.addEventListener('DOMContentLoaded', function () {
            hitungTotalEdit();
        });

        // Cegah double submit
        (function () {
            const f = document.getElementById('formMonevEdit');
            const b = document.getElementById('btnSubmit');
            if (f && b) {
                f.addEventListener('submit', function () {
                    b.disabled = true;
                    b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                });
            }
        })();
    </script>
</body>

</html>
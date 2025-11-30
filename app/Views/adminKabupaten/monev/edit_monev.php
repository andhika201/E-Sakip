<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Capaian Monev</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;">Edit Capaian Monev</h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?php if (empty($monev)): ?>
                <div class="alert alert-danger">Data Monev tidak ditemukan. Silakan kembali ke daftar.</div>
                <div class="text-end">
                    <a href="<?= base_url('adminkab/monev') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            <?php else: ?>
                <form action="<?= base_url('adminkab/monev/update/' . (int) $monev['id']) ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <input type="hidden" name="mode" value="<?= esc($mode ?? 'opd') ?>">
                    <input type="hidden" name="tahun" value="<?= esc($tahun ?? 'all') ?>">
                    <input type="hidden" name="opd_filter" value="<?= esc($opdFilter ?? 'all') ?>">

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label">Sasaran RENSTRA</label>
                            <input type="text" class="form-control" value="<?= esc($monev['sasaran_renstra'] ?? '-') ?>"
                                readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rencana Aksi</label>
                            <input type="text" class="form-control" value="<?= esc($monev['rencana_aksi'] ?? '-') ?>"
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
                        <label class="form-label">Capaian Triwulan</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_1" class="form-control"
                                    placeholder="Triwulan I"
                                    value="<?= esc(old('capaian_triwulan_1', $monev['capaian_triwulan_1'] ?? '')) ?>">
                            </div>
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_2" class="form-control"
                                    placeholder="Triwulan II"
                                    value="<?= esc(old('capaian_triwulan_2', $monev['capaian_triwulan_2'] ?? '')) ?>">
                            </div>
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_3" class="form-control"
                                    placeholder="Triwulan III"
                                    value="<?= esc(old('capaian_triwulan_3', $monev['capaian_triwulan_3'] ?? '')) ?>">
                            </div>
                            <div class="col">
                                <input type="number" step="any" min="0" name="capaian_triwulan_4" class="form-control"
                                    placeholder="Triwulan IV"
                                    value="<?= esc(old('capaian_triwulan_4', $monev['capaian_triwulan_4'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Total Capaian</label>
                                <input type="number" step="any" name="total" class="form-control"
                                    value="<?= esc(old('total', $monev['total'] ?? '')) ?>">
                                <small class="text-muted d-block mt-1">
                                    Bisa diisi manual atau hasil perhitungan sendiri.
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
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Update
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</body>

</html>
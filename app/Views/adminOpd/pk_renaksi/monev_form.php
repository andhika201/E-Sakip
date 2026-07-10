<?php
$isBupati = ($jenis === 'bupati');
$judul    = 'Input Capaian';
$monevPath = ($jenis === 'bupati') ? 'adminkab/monev'
           : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl  = base_url($monevPath);
$mv = function (string $k) use ($monev) {
    return old($k, $monev[$k] ?? '');
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($judul) ?> - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
    <?= $this->include($isBupati ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php'); ?>
    <?= $this->include($isBupati ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;"><?= esc($judul) ?></h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Indikator PK</label>
                    <input type="text" class="form-control" value="<?= esc($detail['indikator_sasaran'] ?? '-') ?>" readonly>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Rencana Aksi</label>
                    <textarea class="form-control" rows="2" readonly><?= esc($detail['rencana_aksi'] ?? '-') ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Satuan</label>
                    <input type="text" class="form-control" value="<?= esc($detail['satuan'] ?? '-') ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Target</label>
                    <input type="text" class="form-control" value="<?= esc($detail['indikator_target'] ?? '-') ?>" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted">Target Triwulan (referensi)</label>
                <div class="row g-2">
                    <div class="col"><input type="text" class="form-control bg-light" value="<?= esc($detail['target_triwulan_1'] ?? '-') ?>" readonly></div>
                    <div class="col"><input type="text" class="form-control bg-light" value="<?= esc($detail['target_triwulan_2'] ?? '-') ?>" readonly></div>
                    <div class="col"><input type="text" class="form-control bg-light" value="<?= esc($detail['target_triwulan_3'] ?? '-') ?>" readonly></div>
                    <div class="col"><input type="text" class="form-control bg-light" value="<?= esc($detail['target_triwulan_4'] ?? '-') ?>" readonly></div>
                </div>
            </div>

            <form action="<?= $baseUrl . '/save' ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="target_rencana_id" value="<?= (int) ($detail['id'] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Realisasi / Capaian Triwulan</label>
                    <div class="row g-2">
                        <div class="col"><input type="text" name="capaian_triwulan_1" class="form-control" placeholder="Capaian I" value="<?= esc($mv('capaian_triwulan_1')) ?>"></div>
                        <div class="col"><input type="text" name="capaian_triwulan_2" class="form-control" placeholder="Capaian II" value="<?= esc($mv('capaian_triwulan_2')) ?>"></div>
                        <div class="col"><input type="text" name="capaian_triwulan_3" class="form-control" placeholder="Capaian III" value="<?= esc($mv('capaian_triwulan_3')) ?>"></div>
                        <div class="col"><input type="text" name="capaian_triwulan_4" class="form-control" placeholder="Capaian IV" value="<?= esc($mv('capaian_triwulan_4')) ?>"></div>
                    </div>
                    <small class="text-muted">Bisa diisi angka atau teks sesuai kebutuhan.</small>
                </div>

                <div class="mb-3" style="max-width:240px;">
                    <label class="form-label" for="total">Total Capaian</label>
                    <input type="text" name="total" id="total" class="form-control" value="<?= esc($mv('total')) ?>">
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan Capaian</button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>

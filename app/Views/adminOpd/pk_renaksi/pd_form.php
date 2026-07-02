<?php
// Form kelola Perangkat Daerah pendukung PK Bupati untuk 1 Sasaran PK.
$selected = array_flip(array_map('intval', $selectedIds ?? []));
$baseUrl  = base_url('adminkab/target_renaksi');
$judul    = ($isManual ?? false) ? 'Edit Perangkat Daerah Pendukung' : 'Tambah Perangkat Daerah Pendukung';
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
        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
                <h2 class="h3 fw-bold text-center mb-1" style="color:#00743e;"><?= esc($judul) ?></h2>
                <p class="text-center text-muted small mb-4">Pilih Perangkat Daerah yang mendukung pencapaian sasaran PK Bupati ini.</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-9 mb-3 mb-md-0">
                        <label class="form-label">Sasaran PK Bupati</label>
                        <input type="text" class="form-control" value="<?= esc($sasaran['sasaran'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun</label>
                        <input type="text" class="form-control" value="<?= esc($sasaran['tahun'] ?? '-') ?>" readonly>
                    </div>
                </div>

                <?php if (!($isManual ?? false)): ?>
                    <div class="alert alert-info small py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Daftar di bawah sudah dicentang otomatis dari hasil pencocokan Cascading. Sesuaikan bila perlu, lalu simpan untuk mengunci pilihan manual.
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('adminkab/target_renaksi/pd/save') ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="pk_sasaran_id" value="<?= (int) ($sasaran['id'] ?? 0) ?>">

                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <div class="input-group input-group-sm" style="max-width:320px;">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="pd-search" class="form-control" placeholder="Cari Perangkat Daerah...">
                        </div>
                        <button type="button" id="pd-all" class="btn btn-outline-success btn-sm">Pilih semua</button>
                        <button type="button" id="pd-none" class="btn btn-outline-secondary btn-sm">Kosongkan</button>
                        <span class="ms-auto small text-muted"><span id="pd-count"><?= count($selected) ?></span> dipilih</span>
                    </div>

                    <div class="border rounded p-2" style="max-height:420px; overflow:auto;">
                        <div class="row g-1" id="pd-list">
                            <?php foreach (($opdList ?? []) as $opd): ?>
                                <?php $oid = (int) $opd['id']; ?>
                                <div class="col-md-6 pd-item">
                                    <label class="d-flex align-items-start gap-2 p-2 rounded border-0 w-100" style="cursor:pointer;">
                                        <input class="form-check-input mt-1 pd-check" type="checkbox" name="opd_ids[]"
                                               value="<?= $oid ?>" <?= isset($selected[$oid]) ? 'checked' : '' ?>>
                                        <span class="pd-name small"><?= esc($opd['nama_opd']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">Jika semua dikosongkan lalu disimpan, tampilan kembali memakai daftar otomatis (Cascading).</small>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        (function () {
            var search = document.getElementById('pd-search');
            var list   = document.getElementById('pd-list');
            var count  = document.getElementById('pd-count');
            if (!list) return;

            function refreshCount() {
                count.textContent = list.querySelectorAll('.pd-check:checked').length;
            }
            list.addEventListener('change', function (e) {
                if (e.target.classList.contains('pd-check')) refreshCount();
            });
            document.getElementById('pd-all').addEventListener('click', function () {
                list.querySelectorAll('.pd-item:not([hidden]) .pd-check').forEach(function (c) { c.checked = true; });
                refreshCount();
            });
            document.getElementById('pd-none').addEventListener('click', function () {
                list.querySelectorAll('.pd-check').forEach(function (c) { c.checked = false; });
                refreshCount();
            });
            if (search) {
                search.addEventListener('input', function () {
                    var q = this.value.toLowerCase().trim();
                    list.querySelectorAll('.pd-item').forEach(function (it) {
                        var name = (it.querySelector('.pd-name').textContent || '').toLowerCase();
                        it.hidden = q !== '' && name.indexOf(q) === -1;
                    });
                });
            }
        })();
    </script>
</body>

</html>

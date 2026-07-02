<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Edit Pegawai') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:800px;">
                <h2 class="h4 fw-bold text-success text-center mb-4">Edit Jabatan &amp; OPD Pegawai</h2>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= base_url('adminkab/pegawai/update/' . (int) $pegawai['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Pegawai</label>
                            <input type="text" class="form-control" value="<?= esc($pegawai['nama_pegawai']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIP</label>
                            <input type="text" class="form-control" value="<?= esc($pegawai['nip_pegawai']) ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">OPD <span class="text-danger">*</span></label>
                        <select name="opd_id" class="form-select" required>
                            <option value="">Pilih OPD</option>
                            <?php foreach ($opdList as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>"
                                    <?= ((int) $pegawai['opd_id'] === (int) $opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                        <select name="jabatan_id" class="form-select" required>
                            <option value="">Pilih Jabatan</option>
                            <?php foreach ($jabatanList as $j): ?>
                                <option value="<?= (int) $j['id'] ?>"
                                    <?= ((int) $pegawai['jabatan_id'] === (int) $j['id']) ? 'selected' : '' ?>>
                                    <?= esc($j['nama_jabatan']) ?><?= !empty($j['nama_opd']) ? ' — ' . esc($j['nama_opd']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            Untuk mengubah <em>nama</em> jabatan, gunakan menu
                            <a href="<?= base_url('adminkab/pegawai/jabatan') ?>">Kelola Jabatan</a>.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pangkat / Golongan</label>
                        <select name="pangkat_id" class="form-select">
                            <option value="">- Tidak diubah / kosong -</option>
                            <?php foreach ($pangkatOptions as $pk): ?>
                                <option value="<?= (int) $pk['id'] ?>"
                                    <?= ((int) ($pegawai['pangkat_id'] ?? 0) === (int) $pk['id']) ? 'selected' : '' ?>>
                                    <?= esc($pk['nama_pangkat']) ?><?= !empty($pk['golongan']) ? ' (' . esc($pk['golongan']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= base_url('adminkab/pegawai') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

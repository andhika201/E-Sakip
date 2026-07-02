<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Sinkron Pegawai dari SIMPEG') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .sync-table thead th { position: sticky; top: 0; z-index: 2; white-space: nowrap; }
        .table-wrap { max-height: 55vh; overflow: auto; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <h2 class="h4 fw-bold text-success mb-0">🔄 Sinkron Pegawai dari SIMPEG</h2>
                    <a href="<?= base_url('adminkab/master') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Master Data
                    </a>
                </div>
                <p class="text-muted small">Menarik master <strong>OPD, Pangkat, Jabatan</strong> dan data <strong>Pegawai</strong> dari SIMPEG (Presensi Sync API).</p>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($configured)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        Integrasi SIMPEG belum dikonfigurasi. Isi di file <code>.env</code>:
                        <pre class="mb-0 mt-2 bg-light p-2 rounded small">simpeg.enabled = true
simpeg.baseUrl = https://&lt;host&gt;/api/presensi
simpeg.token   = &lt;api-token minimal 32 karakter&gt;</pre>
                        Detail: <code>SIMPEG_SIKASN_INTEGRATION.md</code>.
                    </div>
                <?php else: ?>

                    <div class="d-flex gap-2 mb-3">
                        <span class="badge bg-success align-self-center">SIMPEG Terkonfigurasi</span>
                        <a href="<?= base_url('adminkab/pegawai/sync?preview=1') ?>" class="btn btn-outline-success">
                            <i class="fas fa-eye me-1"></i> Pratinjau Data
                        </a>
                    </div>

                    <?php if (!empty($syncError)): ?>
                        <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-1"></i> <?= esc($syncError) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($preview)): ?>
                        <?php $s = $preview['summary']; ?>
                        <div class="row g-3 mb-3">
                            <?php
                            $cards = [
                                ['OPD', 'opd', $s['opd_remote'], $s['opd_baru'], 'fa-building'],
                                ['Pangkat', 'pangkat', $s['pangkat_remote'], $s['pangkat_baru'], 'fa-medal'],
                                ['Jabatan', 'jabatan', $s['jabatan_remote'], $s['jabatan_baru'], 'fa-id-badge'],
                            ];
                            foreach ($cards as [$lbl, $ent, $tot, $baru, $ic]): ?>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-3 h-100 d-flex flex-column">
                                        <div class="text-muted small"><i class="fas <?= $ic ?> me-1"></i><?= $lbl ?></div>
                                        <div class="fs-4 fw-bold"><?= (int) $tot ?></div>
                                        <div class="small mb-2"><span class="badge bg-success"><?= (int) $baru ?> baru</span></div>
                                        <form action="<?= base_url('adminkab/pegawai/sync/run') ?>" method="post" class="mt-auto"
                                            onsubmit="return confirm('Sinkron <?= $lbl ?> dari SIMPEG?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="entity" value="<?= $ent ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm w-100">
                                                <i class="fas fa-sync-alt me-1"></i> Sync <?= $lbl ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 h-100 d-flex flex-column">
                                    <div class="text-muted small"><i class="fas fa-users me-1"></i>Pegawai</div>
                                    <div class="fs-4 fw-bold"><?= (int) $s['pegawai_remote'] ?></div>
                                    <div class="small text-muted mb-2">lokal: <?= (int) $s['pegawai_lokal'] ?></div>
                                    <form action="<?= base_url('adminkab/pegawai/sync/run') ?>" method="post" class="mt-auto"
                                        onsubmit="return confirm('Sinkron <?= (int) $s['pegawai_remote'] ?> pegawai dari SIMPEG? Pastikan OPD/Pangkat/Jabatan sudah disync lebih dulu.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="entity" value="pegawai">
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-sync-alt me-1"></i> Sync Pegawai
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border small mb-3 d-flex flex-wrap align-items-center gap-2">
                            <span>
                                <i class="fas fa-info-circle me-1"></i>
                                Sync per data, disarankan urut: <strong>OPD → Pangkat → Jabatan → Pegawai</strong>
                                (pegawai menautkan ke OPD/Pangkat/Jabatan yang sudah ada). Aman diulang (idempoten).
                            </span>
                            <form action="<?= base_url('adminkab/pegawai/sync/run') ?>" method="post" class="ms-auto"
                                onsubmit="return confirm('Jalankan sinkron SEMUA (OPD, Pangkat, Jabatan, Pegawai) sekaligus?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="entity" value="all">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-bolt me-1"></i> Sync Semua Sekaligus
                                </button>
                            </form>
                        </div>

                        <h6 class="fw-bold text-success">Contoh data pegawai (25 baris pertama)</h6>
                        <div class="table-responsive table-wrap">
                            <table class="table table-bordered table-striped align-middle small sync-table">
                                <thead class="table-success text-dark text-center">
                                    <tr><th>NIP</th><th>Nama</th><th>OPD</th><th>Pangkat</th><th>Jabatan</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview['rows'] as $r): ?>
                                        <tr>
                                            <td><?= esc($r['nip']) ?></td>
                                            <td class="text-start"><?= esc($r['nama']) ?></td>
                                            <td class="text-start"><?= esc($r['opd'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($r['pangkat'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($r['jabatan'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($preview['rows'])): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data pegawai.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border">
                            Klik <strong>Pratinjau Data</strong> untuk melihat ringkasan dari SIMPEG sebelum menerapkan sinkron.
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>
</body>

</html>

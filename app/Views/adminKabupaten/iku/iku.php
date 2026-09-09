<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'IKU - e-SAKIP') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        /* Kepala tabel ikut menggantung saat digulir; lebar kolom & perataan
           sel diatur di templates/iku/_tabel.php agar satu sumber, tidak
           terpecah antara halaman OPD dan halaman Kabupaten.
           `min-width` per sel yang dulu ada di sini sengaja DIBUANG: ia
           menyamaratakan semua kolom teks, sehingga Definisi Operasional yang
           berisi paragraf mendapat ruang sama sempit dengan Penanggung Jawab. */
        .iku-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
        }
        .iku-table tbody tr:hover > * { background-color: #f3faf5; }
        .table-wrap { max-height: 70vh; overflow: auto; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h4 fw-bold text-success text-center mb-4">
                Indikator Kinerja Utama (IKU) - Admin Kabupaten
            </h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ================= FILTER ================= -->
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold text-secondary mb-1">Mode Tampilan</label>
                    <select name="mode" class="form-select" onchange="this.form.submit()">
                        <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>
                            IKU Pemerintah Kabupaten
                        </option>
                        <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>
                            Rekap IKU Perangkat Daerah
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold text-secondary mb-1">Periode</label>
                    <select name="periode" class="form-select" onchange="this.form.submit()">
                        <?php if (empty($grouped_data)): ?>
                            <option value="">-- Belum ada data --</option>
                        <?php else: ?>
                            <?php /* Periode RPJMD tetap ditawarkan walau IKU-nya
                                     masih kosong — sama seperti layar IKU OPD
                                     terhadap Renstra — dan diberi keterangan
                                     supaya kosongnya terbaca wajar. */ ?>
                            <?php foreach ($grouped_data as $key => $periode): ?>
                                <option value="<?= esc($key) ?>" <?= ($selected_periode === $key) ? 'selected' : '' ?>>
                                    <?= esc($periode['period']) ?><?= isset($periode['punya_iku']) && ! $periode['punya_iku']
                                        ? '  —  belum ada IKU' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if ($mode === 'opd'): ?>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary mb-1">Filter OPD</label>
                        <select name="opd_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua OPD</option>
                            <?php foreach ($opdList ?? [] as $opd): ?>
                                <option value="<?= (int) $opd['id'] ?>"
                                    <?= ($opdFilter !== null && (int) $opdFilter === (int) $opd['id']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>

            <?php
            /* Keadaan pengesahan IKU Kabupaten — panel yang sama dengan layar
               IKU OPD supaya konsepnya tidak bercabang. Bedanya satu dan
               disengaja: dokumen kabupaten disahkan penyusunnya sendiri, jadi
               pengganti "menunggu Admin Kabupaten" adalah tombol Sahkan. */
            $menunggu = $revisiMenunggu ?? null;
            $berlaku  = $revisiBerlaku ?? null;
            $adaIsi   = ! empty($iku_data);
            [$tmIku, $taIku] = ! empty($selected_periode) && strpos((string) $selected_periode, '-') !== false
                ? array_map('intval', explode('-', (string) $selected_periode))
                : [0, 0];
            ?>

            <?php if ($mode === 'kabupaten' && $tmIku > 0): ?>
                <div class="alert <?= $menunggu !== null ? 'alert-primary' : ($berlaku !== null ? 'alert-success' : 'alert-warning') ?> d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <?php if ($menunggu !== null): ?>
                            <i class="fas fa-hourglass-half me-1"></i>
                            <strong>Dibekukan, menunggu disahkan</strong>
                            <div class="small mt-1">
                                Dibekukan
                                <?= ! empty($menunggu['submitted_at'])
                                    ? esc(date('d M Y H:i', strtotime($menunggu['submitted_at']))) : '' ?>.
                                Perubahan yang Anda lakukan sesudah itu <strong>tidak ikut terbawa</strong> —
                                tarik dulu bila isinya masih perlu diperbaiki.
                            </div>
                        <?php elseif ($berlaku !== null): ?>
                            <i class="fas fa-circle-check me-1"></i>
                            <strong>Sudah disahkan</strong> &mdash;
                            <?= esc($berlaku['nomor'] ?? '') ?><?= ! empty($berlaku['nama']) ? ' ' . esc($berlaku['nama']) : '' ?>,
                            berlaku mulai <?= (int) $berlaku['berlaku_mulai_tahun'] ?>.
                            <div class="small mt-1">
                                Perubahan berikutnya dibuat lewat <strong>Versi IKU</strong>:
                                buat revisi, sunting yang berubah, lalu sahkan. Menu ini sendiri
                                tidak lagi mengubah dokumen yang berlaku.
                            </div>
                        <?php else: ?>
                            <i class="fas fa-pen-ruler me-1"></i>
                            <strong>Belum pernah disahkan</strong>
                            <div class="small mt-1">
                                IKU Kabupaten periode ini masih bebas disusun. Bila sudah sesuai,
                                bekukan lalu sahkan supaya LAKIP punya versi resmi untuk dirujuk.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($menunggu !== null): ?>
                            <?php if (user_can('iku_kab.revisi_sahkan')): ?>
                                <form method="post"
                                      action="<?= base_url('adminkab/iku/revisi/sahkan/' . (int) $menunggu['id']) ?>"
                                      onsubmit="return confirm('Sahkan IKU Kabupaten periode <?= esc($selected_periode) ?>? Versi ini menjadi rujukan resmi LAKIP.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-success btn-sm">
                                        <i class="fas fa-stamp me-1"></i>Sahkan Sekarang
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (user_can('iku_kab.revisi')): ?>
                                <form method="post"
                                      action="<?= base_url('adminkab/iku/revisi/tarik/' . (int) $menunggu['id']) ?>"
                                      onsubmit="return confirm('Tarik pembekuan? Revisi kembali jadi draft dan bisa disunting.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-rotate-left me-1"></i>Tarik
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php elseif ($berlaku === null && $adaIsi && user_can('iku_kab.revisi')): ?>
                            <form method="post" action="<?= base_url('adminkab/iku/ajukan-pengesahan') ?>"
                                  onsubmit="return confirm('Bekukan IKU Kabupaten periode <?= esc($selected_periode) ?> untuk disahkan? Isinya dibekukan apa adanya saat ini.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="tahun_mulai" value="<?= $tmIku ?>">
                                <input type="hidden" name="tahun_akhir" value="<?= $taIku ?>">
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-paper-plane me-1"></i>Ajukan Pengesahan
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="<?= base_url('adminkab/iku/revisi') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-code-branch me-1"></i>Versi IKU
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'kabupaten' && $tmIku > 0 && ! $adaIsi): ?>
                <div class="alert alert-info">
                    <i class="fas fa-circle-info me-1"></i>
                    <strong>IKU Kabupaten periode <?= esc($selected_periode) ?> belum berisi apa pun.</strong>
                    <div class="small mt-1">
                        Ambil sasaran &amp; indikatornya dari RPJMD lewat
                        <strong>Sync dari RPJMD</strong>.
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="text-muted small">
                    <?php if ($mode === 'kabupaten'): ?>
                        IKU tingkat kabupaten — sasaran, indikator, satuan, dan targetnya diinput langsung di sini.
                    <?php else: ?>
                        Rekap IKU seluruh Perangkat Daerah (hanya lihat). Penyuntingan dilakukan lewat akun OPD terkait.
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($mode === 'kabupaten' && user_can('iku_kab.create')): ?>
                        <?php /* Tidak ada tombol "Tambah IKU". IKU Kabupaten bersumber
                                 dari RPJMD lewat sync; menambah sasaran manual di
                                 sebelahnya mudah melahirkan sasaran kembar — sync
                                 memakai ulang sasaran bernama sama, penambahan manual
                                 selalu membuat baris baru. */ ?>
                        <a href="<?= base_url('adminkab/iku/sync') ?>" class="btn btn-outline-success"
                           title="Ambil sasaran, indikator, dan target dari RPJMD">
                            <i class="fas fa-sync-alt me-1"></i> Sync dari RPJMD
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($selected_periode)): ?>
                        <a href="<?= base_url('adminkab/iku/cetak?' . http_build_query(array_filter([
                            'mode'    => $mode,
                            'opd_id'  => $opdFilter ?? '',
                            'periode' => $selected_periode,
                        ], static fn($v) => $v !== '' && $v !== null))) ?>"
                           target="_blank" class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $this->setData([
                'show_opd'   => ($mode === 'opd'),
                'base_url'   => 'adminkab/iku',
                'perm'       => 'iku_kab',
                'can_manage' => ($mode === 'kabupaten'),
                'query'      => '?mode=' . $mode,
            ]);
            ?>
            <?= $this->include('templates/iku/_tabel') ?>

        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</div>
</body>

</html>

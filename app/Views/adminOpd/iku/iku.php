<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'IKU - e-SAKIP') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        .iku-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            white-space: nowrap;
        }
        .iku-table th,
        .iku-table td { vertical-align: middle; }
        .iku-table td.text-start { min-width: 160px; }
        .iku-table tbody tr:hover { background-color: #f3faf5; }
        .table-wrap { max-height: 70vh; overflow: auto; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4">
                <h2 class="h4 fw-bold text-success text-center mb-4">
                    Indikator Kinerja Utama (IKU)
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

                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-secondary mb-1">Periode</label>
                        <select name="periode" class="form-select" onchange="this.form.submit()">
                            <?php if (empty($grouped_data)): ?>
                                <option value="">-- Belum ada data --</option>
                            <?php else: ?>
                                <?php foreach ($grouped_data as $key => $periode): ?>
                                    <?php /* Periode yang IKU-nya belum ada tetap ditawarkan —
                                             asalkan Renstra-nya sudah ada — dan diberi keterangan
                                             supaya jelas bahwa kosongnya memang wajar, bukan
                                             data yang hilang. */ ?>
                                    <option value="<?= esc($key) ?>" <?= ($selected_periode === $key) ? 'selected' : '' ?>>
                                        <?= esc($periode['period']) ?><?= isset($periode['punya_iku']) && ! $periode['punya_iku']
                                            ? '  —  belum ada IKU' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>

                <?php
                /* Keadaan pengesahan IKU periode ini. Panel di bawah adalah
                   satu-satunya pintu menuju verifikasi dari layar ini; menu
                   Revisi baru berisi sesudah pengajuan pertama, jadi menaruh
                   pintunya hanya di sana berarti IKU hasil sync tidak punya
                   jalan sama sekali. */
                $menunggu = $revisiMenunggu ?? null;
                $berlaku  = $revisiBerlaku ?? null;
                $adaIsi   = ! empty($iku_data);
                [$tmIku, $taIku] = ! empty($selected_periode) && strpos((string) $selected_periode, '-') !== false
                    ? array_map('intval', explode('-', (string) $selected_periode))
                    : [0, 0];
                ?>

                <?php if ($tmIku > 0): ?>
                    <div class="alert <?= $menunggu !== null ? 'alert-primary' : ($berlaku !== null ? 'alert-success' : 'alert-warning') ?> d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <?php if ($menunggu !== null): ?>
                                <i class="fas fa-hourglass-half me-1"></i>
                                <strong>Menunggu verifikasi Admin Kabupaten</strong>
                                <div class="small mt-1">
                                    Diajukan
                                    <?= ! empty($menunggu['submitted_at'])
                                        ? esc(date('d M Y H:i', strtotime($menunggu['submitted_at']))) : '' ?>.
                                    Isinya sudah dibekukan, jadi perubahan yang Anda lakukan sekarang
                                    <strong>tidak ikut terkirim</strong> — tarik pengajuan bila perlu diperbaiki.
                                </div>
                            <?php elseif ($berlaku !== null): ?>
                                <i class="fas fa-circle-check me-1"></i>
                                <strong>Sudah disahkan</strong> &mdash;
                                <?= esc($berlaku['nomor'] ?? '') ?><?= ! empty($berlaku['nama']) ? ' ' . esc($berlaku['nama']) : '' ?>,
                                berlaku mulai <?= (int) $berlaku['berlaku_mulai_tahun'] ?>.
                                <div class="small mt-1">
                                    Perubahan berikutnya dibuat lewat <strong>Versi IKU</strong>:
                                    buat revisi, sunting yang berubah, lalu ajukan. Menu ini sendiri
                                    tidak lagi mengajukan apa pun.
                                </div>
                            <?php else: ?>
                                <i class="fas fa-pen-ruler me-1"></i>
                                <strong>Belum pernah disahkan</strong>
                                <div class="small mt-1">
                                    IKU periode ini masih bebas disusun. Bila sudah sesuai, ajukan untuk
                                    disahkan Admin Kabupaten.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($menunggu !== null): ?>
                                <?php if (user_can('iku_opd.revisi')): ?>
                                    <form method="post"
                                          action="<?= base_url('adminopd/iku/revisi/tarik/' . (int) $menunggu['id']) ?>"
                                          onsubmit="return confirm('Tarik pengajuan? Revisi kembali jadi draft dan bisa disunting.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-rotate-left me-1"></i>Tarik Pengajuan
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php /* Tombol ini HANYA untuk pengesahan pertama. Sesudah ada
                                     revisi yang berlaku, menekannya lagi hanya akan melahirkan
                                     revisi yang isinya sama persis dengan yang sudah berlaku.
                                     Servernya pun menolak — lihat bekukanDanAjukan(). */ ?>
                            <?php elseif ($berlaku === null && $adaIsi && user_can('iku_opd.revisi')): ?>
                                <form method="post" action="<?= base_url('adminopd/iku/ajukan-pengesahan') ?>"
                                      onsubmit="return confirm('Ajukan IKU periode <?= esc($selected_periode) ?> untuk disahkan Admin Kabupaten? Isinya dibekukan apa adanya saat ini.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tahun_mulai" value="<?= $tmIku ?>">
                                    <input type="hidden" name="tahun_akhir" value="<?= $taIku ?>">
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane me-1"></i>Ajukan Pengesahan
                                    </button>
                                </form>
                            <?php endif; ?>

                            <a href="<?= base_url('adminopd/iku/revisi') ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-code-branch me-1"></i>Versi IKU
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($tmIku > 0 && ! $adaIsi): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-circle-info me-1"></i>
                        <strong>IKU periode <?= esc($selected_periode) ?> belum berisi apa pun.</strong>
                        <div class="small mt-1">
                            Renstra periode ini sudah ada, jadi langkah berikutnya adalah
                            <strong>Sync dari Renstra</strong> &mdash; pilih versi Renstra yang
                            dijadikan titik tolak, lalu ajukan pengesahannya.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="text-muted small">
                        Sasaran, indikator, satuan, dan target IKU diinput langsung di sini — tidak lagi mengikuti Renstra.
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (user_can('iku_opd.create')): ?>
                            <?php /* Tidak ada tombol "Tambah IKU". IKU bersumber dari
                                     Renstra lewat sync; menambah sasaran manual di
                                     sebelahnya mudah melahirkan sasaran kembar — sync
                                     memakai ulang sasaran bernama sama, penambahan manual
                                     selalu membuat baris baru. Rutenya kini ikut ditutup
                                     di controller, bukan sekadar disembunyikan. */ ?>
                            <?php if (empty($is_lintas_opd)): ?>
                                <?php /* Periode ikut dibawa: tanpa itu layar sync memilih
                                         bawaannya sendiri, dan pemakai yang baru saja memilih
                                         2030-2034 mendapati dirinya menyalin periode lain. */ ?>
                                <a href="<?= base_url('adminopd/iku/sync' . (! empty($selected_periode)
                                       ? '?periode=' . urlencode((string) $selected_periode) : '')) ?>"
                                   class="btn btn-outline-success"
                                   title="Ambil sasaran, indikator, dan target dari Renstra OPD ini">
                                    <i class="fas fa-sync-alt me-1"></i> Sync dari Renstra
                                </a>

                                <?php /* =====================================================
                                         TOMBOL "SASARAN MANDIRI" SENGAJA DICABUT DARI SINI

                                         Pintu itu menulis LANGSUNG ke tabel live, di luar alur
                                         revisi: IKU berjalan berubah tanpa revisi, tanpa
                                         pengesahan, dan sasarannya tidak pernah masuk arsip
                                         versi mana pun — sehingga ia tampil di layar ini tetapi
                                         TIDAK ADA di dokumen penilaian tahun mana pun, dan LAKIP
                                         tidak bisa menilainya.

                                         Sejak 2026-08-31 sasaran mandiri ditambahkan di dalam
                                         layar sunting draft revisi (tombol "Tambah Sasaran",
                                         bersebelahan dengan "Tambah Indikator"), sehingga ia
                                         ikut diperiksa dan baru berlaku setelah disahkan.

                                         Sync tetap di sini: ia jalan yang benar untuk sasaran
                                         yang MEMANG ada di Renstra, dan ia menyimpan silsilahnya.
                                    ===================================================== */ ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($selected_periode)): ?>
                            <a href="<?= base_url('adminopd/iku/cetak?' . http_build_query(['periode' => $selected_periode])) ?>"
                               target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $this->setData([
                    'show_opd'   => !empty($is_lintas_opd),
                    'base_url'   => 'adminopd/iku',
                    'perm'       => 'iku_opd',
                    'can_manage' => true,
                    'query'      => '',
                ]);
                ?>
                <?= $this->include('templates/iku/_tabel') ?>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>

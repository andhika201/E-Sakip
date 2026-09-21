<?php

/**
 * Landing page versi dokumen (§48).
 *
 * Satu halaman untuk RPJMD maupun Renstra — yang berbeda hanya lingkupnya,
 * dan itu sudah diselesaikan controller lewat DokumenVersiTrait.
 *
 * @var string $namaDokumen
 * @var string $baseUrl
 * @var array  $blok  [['periode','scope','daftar','sekarang','konflik'], ...]
 */
$title        = $title ?? ('Versi ' . $namaDokumen);
$judulHalaman = $judulHalaman ?? ('Versi Dokumen ' . $namaDokumen);

/** Badge dihitung dari timeline (§7), bukan dibaca dari kolom status. */
$kelasBadge = static function (string $badge): string {
    return match ($badge) {
        'CURRENT'             => 'bg-success',
        'HISTORICAL'          => 'bg-secondary',
        'UPCOMING'            => 'bg-info text-dark',
        'DRAFT'               => 'bg-warning text-dark',
        'MENUNGGU VERIFIKASI' => 'bg-primary',
        'CANCELLED'           => 'bg-dark',
        default               => 'bg-light text-dark',
    };
};
?>
<?= $this->include('templates/shell_atas') ?>

<div class="kotak-jejak mb-4">
    <div class="fw-semibold mb-1">Apa yang dilakukan halaman ini</div>
    <div class="small text-secondary">
        <?= esc($namaDokumen) ?> berlaku sekitar lima tahun, tetapi kebijakan bisa berubah di
        tengah periode. Setiap perubahan disimpan sebagai <strong>versi tersendiri yang dibekukan</strong>,
        lengkap dengan tanggal mulai berlakunya. Nomor versi <em>tidak</em> menentukan waktu berlaku —
        yang menentukan adalah <strong>tanggal berlaku</strong>, sehingga versi yang baru dibuat hari ini
        boleh saja berlaku untuk masa lalu.
    </div>
</div>

<div class="mb-3">
    <a href="<?= base_url($baseUrl) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Kembali ke <?= esc($namaDokumen) ?>
    </a>
</div>

<?php if (empty($blok)): ?>
    <div class="alert alert-light border text-center mb-0">
        Belum ada periode <?= esc($namaDokumen) ?> yang bisa diversikan.
    </div>
<?php endif; ?>

<?php foreach ($blok as $b): ?>
    <?php
    $sekarang = $b['sekarang'];
    $periode  = $b['periode'];
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-bold"><?= esc($namaDokumen) ?> <?= esc($periode) ?></span>
                <?php if ($sekarang !== null): ?>
                    <span class="text-secondary sel-kecil ms-2">
                        Berlaku sekarang: <strong>V<?= (int) $sekarang['version_no'] ?></strong>
                        &middot; <?= esc($sekarang['label']) ?>
                    </span>
                <?php elseif ($b['konflik'] === null): ?>
                    <span class="text-secondary sel-kecil ms-2">Belum ada versi yang berlaku</span>
                <?php endif; ?>
            </div>

            <?php if ($bolehBuat): ?>
                <a href="<?= base_url($baseUrl . '/versi/buat?periode=' . urlencode($periode)) ?>"
                   class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Buat Versi Baru
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?php if ($b['konflik'] !== null): ?>
                <div class="kotak-jejak awas mb-3">
                    <div class="fw-semibold text-danger mb-1">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Konflik masa berlaku
                    </div>
                    <div class="small">
                        Sistem <strong>tidak memilih salah satu secara diam-diam</strong>. Selama konflik ini ada,
                        periode tersebut tidak punya versi yang pasti — perbaiki dulu tanggal berlakunya.
                    </div>
                    <div class="small text-secondary mt-2"><?= esc($b['konflik']) ?></div>
                </div>
            <?php endif; ?>

            <?php if (empty($b['daftar'])): ?>
                <div class="alert alert-light border mb-0 small">
                    Belum ada versi untuk periode ini.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle small revisi-tabel" data-no-paginate>
                        <thead class="table-success">
                            <tr>
                                <th style="width:56px">Versi</th>
                                <th>Label</th>
                                <th style="width:190px">Berlaku</th>
                                <th style="width:150px">Status</th>
                                <th style="width:140px">Dibuat</th>
                                <th style="width:130px">Sumber</th>
                                <th style="width:250px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($b['daftar'] as $v): ?>
                                <tr>
                                    <td class="text-center fw-semibold">V<?= (int) $v['version_no'] ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= esc($v['label']) ?>
                                            <?php if ((int) ($v['tampilan_utama'] ?? 0) === 1): ?>
                                                <?php /* Badge CURRENT/HISTORICAL menjawab "berlaku menurut
                                                         tanggal". Tunjukan menjawab "yang tampil di menu".
                                                         Keduanya bisa berbeda, jadi keduanya ditampilkan. */ ?>
                                                <span class="badge bg-primary ms-1" title="Dipakai sebagai tampilan utama menu dokumen ini">
                                                    <i class="fa-solid fa-thumbtack me-1"></i>Tampilan Utama
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (! empty($v['dasar_perubahan'])): ?>
                                            <div class="text-secondary sel-kecil">
                                                Dasar: <?= esc($v['dasar_perubahan']) ?>
                                                <?= ! empty($v['nomor_dasar']) ? ' No. ' . esc($v['nomor_dasar']) : '' ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (! empty($v['alasan_perubahan'])): ?>
                                            <div class="text-secondary sel-kecil">
                                                <?= esc(mb_strimwidth($v['alasan_perubahan'], 0, 120, '...')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sel-kecil"><?= esc($v['rentang']) ?></td>
                                    <td>
                                        <span class="badge badge-lifecycle <?= $kelasBadge($v['badge']) ?>">
                                            <?= esc($v['badge']) ?>
                                        </span>
                                    </td>
                                    <td class="sel-kecil text-secondary">
                                        <?= ! empty($v['created_at']) ? esc(date('d M Y', strtotime($v['created_at']))) : '&mdash;' ?>
                                    </td>
                                    <td class="sel-kecil text-secondary">
                                        <?php if (! empty($v['copied_from_version_id'])): ?>
                                            Salinan versi lain
                                        <?php elseif ((int) $v['mulai_dari_kosong'] === 1): ?>
                                            Dari kosong
                                        <?php else: ?>
                                            Kondisi berjalan
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php /* Sunting & Hapus dihitung controller dengan aturan yang sama
                                                 dengan halaman Lihat. Draft: bisa disunting dan dihapus.
                                                 Sudah ditetapkan: hanya Lihat — perbaikannya lewat Izin
                                                 Sunting, dan versinya tidak bisa dihapus (§16). */ ?>
                                        <div class="d-flex flex-wrap gap-1 align-items-center">
                                            <a href="<?= base_url($baseUrl . '/versi/lihat/' . (int) $v['id']) ?>"
                                               class="btn btn-outline-primary btn-sm" title="Lihat isi versi">
                                                <i class="fa-solid fa-eye me-1"></i>Lihat
                                            </a>
                                            <?php if (! empty($v['boleh_sunting'])): ?>
                                                <a href="<?= base_url($baseUrl . '/versi/sunting/' . (int) $v['id']) ?>"
                                                   class="btn btn-warning btn-sm" title="Sunting isi draft">
                                                    <i class="fa-solid fa-pen me-1"></i>Sunting
                                                </a>
                                            <?php endif; ?>
                                            <?php if (! empty($v['keadaan_hapus']['boleh'])): ?>
                                                <form method="post" action="<?= base_url($baseUrl . '/versi/hapus/' . (int) $v['id']) ?>"
                                                      class="d-inline"
                                                      data-konfirmasi="Versi dokumen ini akan dihapus permanen beserta seluruh arsip isinya."
                                                      data-konfirmasi-judul="Hapus Versi Dokumen"
                                                      data-konfirmasi-nama="<?= esc(trim($namaDokumen . ' ' . $periode . ' — V' . (int) $v['version_no'] . ' ' . ($v['label'] ?? '')), 'attr') ?>"
                                                      data-konfirmasi-rincian="Seluruh tujuan, sasaran, indikator, dan target yang diarsipkan di versi ini|Jejak riwayat versi ini (dipindahkan ke log aktivitas)"
                                                      data-konfirmasi-ketik="HAPUS">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-outline-danger btn-sm" title="Hapus versi ini">
                                                        <i class="fa-solid fa-trash me-1"></i>Hapus
                                                    </button>
                                                </form>
                                            <?php elseif (! empty($v['keadaan_hapus']['alasan'])): ?>
                                                <?php /* Versi apa pun yang belum bisa dihapus menampilkan ALASANNYA,
                                                         termasuk yang HISTORICAL tetapi masih dirujuk data lain —
                                                         supaya tidak terkesan tombolnya sekadar hilang. */ ?>
                                                <span class="badge bg-light text-secondary border" role="note"
                                                      title="<?= esc($v['keadaan_hapus']['alasan'], 'attr') ?>">
                                                    <i class="fa-solid fa-lock me-1"></i>Tidak bisa dihapus
                                                </span>
                                                <div class="text-secondary sel-kecil mt-1" style="max-width:230px">
                                                    <?= esc($v['keadaan_hapus']['alasan']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?= $this->include('templates/shell_bawah') ?>

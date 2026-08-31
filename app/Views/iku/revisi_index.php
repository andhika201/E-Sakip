<?php
$title        = $title ?? 'Revisi IKU';
$judulHalaman = 'Revisi IKU';

/** @var array $daftar */
/** @var array $konflik  [tahun => pesan] */
$badge = static function (string $status): array {
    return match ($status) {
        'draft'      => ['bg-warning text-dark', 'Draft'],
        'menunggu'   => ['bg-primary',           'Menunggu Verifikasi'],
        'berlaku'    => ['bg-success',           'Berlaku'],
        'superseded' => ['bg-secondary',         'Arsip'],
        'batal'      => ['bg-dark',              'Dibatalkan'],
        default      => ['bg-light text-dark',   ucfirst($status)],
    };
};
?>
<?= $this->include('templates/shell_atas') ?>

<div class="kotak-jejak mb-4">
    <div class="fw-semibold mb-1">Apa yang dilakukan halaman ini</div>
    <div class="small text-secondary">
        IKU berlaku sekitar lima tahun, tetapi kebijakan bisa berubah di tengah periode.
        Setiap revisi disimpan sebagai <strong>versi tersendiri yang dibekukan</strong>, lengkap dengan
        tahun mulai berlakunya. LAKIP tahun mana pun membaca versi yang berlaku
        <em>pada tahun itu</em>, sehingga laporan tahun-tahun lampau tidak ikut berubah
        ketika IKU direvisi hari ini.
    </div>
</div>

<?php if (! empty($konflik)): ?>
    <div class="kotak-jejak awas mb-4">
        <div class="fw-semibold text-danger mb-2">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>Konflik masa berlaku revisi
        </div>
        <div class="small mb-2">
            Sistem <strong>tidak memilih salah satu secara diam-diam</strong>. Selama konflik ini ada,
            tahun terkait tidak punya versi IKU yang pasti — perbaiki dulu masa berlakunya.
        </div>
        <ul class="small mb-0">
            <?php foreach ($konflik as $tahun => $pesan): ?>
                <li><strong>Tahun <?= esc($tahun) ?>:</strong> <?= esc($pesan) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= base_url($baseUrl) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Kembali ke IKU
    </a>

    <?php if ($bolehRevisi): ?>
        <a href="<?= base_url($baseUrl . '/revisi/buat') ?>" class="btn btn-success btn-sm">
            <i class="fa-solid fa-plus me-1"></i>Buat Revisi
        </a>
    <?php endif; ?>
</div>

<?php if (empty($daftar)): ?>
    <div class="alert alert-light border text-center mb-0">
        Belum ada revisi. Versi pertama (<em>Kondisi Awal</em>) dibuat otomatis saat Anda
        membuat revisi pertama, berisi salinan IKU yang berlaku saat itu.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle small revisi-tabel" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th style="width:44px">No</th>
                    <th>Nama Revisi</th>
                    <th style="width:110px">Periode IKU</th>
                    <th style="width:130px">Masa Berlaku</th>
                    <th style="width:100px">Status</th>
                    <th>Dasar</th>
                    <th style="width:210px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar as $i => $r): ?>
                    <?php [$kelas, $label] = $badge($r['status']); ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= esc($r['nama']) ?></div>
                            <div class="text-secondary sel-kecil">
                                <?= ((int) $r['nomor'] === 0) ? 'Kondisi awal' : ('Revisi ke-' . (int) $r['nomor']) ?>
                                <?php if (! empty($r['catatan'])): ?>
                                    &middot; <?= esc(mb_strimwidth($r['catatan'], 0, 120, '...')) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center"><?= (int) $r['tahun_mulai'] ?>&ndash;<?= (int) $r['tahun_akhir'] ?></td>
                        <td class="text-center">
                            <?php if ($r['status'] === 'draft' || $r['status'] === 'batal'): ?>
                                <span class="text-secondary">&mdash;</span>
                                <div class="sel-kecil text-secondary">diusulkan <?= (int) $r['berlaku_mulai_tahun'] ?></div>
                            <?php else: ?>
                                <?= (int) $r['berlaku_mulai_tahun'] ?> &ndash;
<?php /* NULL pada berlaku_sampai_tahun berarti "belum ada revisi
                         berikutnya", BUKAN "berlaku selamanya" — IKU berhenti
                         di ujung periodenya. Yang ditampilkan karena itu tahun
                         akhir periode; nilainya sengaja TIDAK ditulis ke basis
                         data supaya sahkan() tetap bebas menjahit ulang
                         timeline tanpa harus membatalkan angka tampilan. */ ?>
                                <?= $r['berlaku_sampai_tahun'] !== null
                                    ? (int) $r['berlaku_sampai_tahun']
                                    : (int) $r['tahun_akhir'] ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-lifecycle <?= $kelas ?>"><?= $label ?></span>
                        </td>
                        <td class="sel-kecil">
                            <?php if (! empty($r['dasar_hukum'])): ?>
                                <?= esc($r['dasar_hukum']) ?>
                                <?php if (! empty($r['nomor_dasar'])): ?>
                                    <br><span class="text-secondary">No. <?= esc($r['nomor_dasar']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-secondary">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= base_url($baseUrl . '/revisi/lihat/' . (int) $r['id']) ?>"
                               class="btn btn-sm btn-outline-primary mb-1">
                                <i class="fa-solid fa-eye"></i> Lihat
                            </a>

                            <?php if ($r['status'] === 'draft' && $bolehRevisi): ?>
                                <a href="<?= base_url($baseUrl . '/revisi/sunting/' . (int) $r['id']) ?>"
                                   class="btn btn-sm btn-warning mb-1">
                                    <i class="fa-solid fa-pen"></i> Sunting
                                </a>

                                <?php /* Dua jalur yang berbeda tegas: lingkup Kabupaten
                                         mengesahkan sendiri, lingkup OPD mengajukan ke
                                         Admin Kabupaten. Yang menentukan bukan izin,
                                         melainkan lingkupnya. */ ?>
                                <?php if (! empty($perluVerifikasi)): ?>
                                    <form method="post" class="d-inline"
                                          action="<?= base_url($baseUrl . '/revisi/ajukan/' . (int) $r['id']) ?>"
                                          onsubmit="return confirm('Ajukan revisi ini untuk disahkan Admin Kabupaten? Selama menunggu, isinya tidak bisa disunting.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-primary mb-1">
                                            <i class="fa-solid fa-paper-plane"></i> Ajukan
                                        </button>
                                    </form>
                                <?php elseif ($bolehSahkan): ?>
                                    <form method="post" class="d-inline"
                                          action="<?= base_url($baseUrl . '/revisi/sahkan/' . (int) $r['id']) ?>"
                                          onsubmit="return confirm('Sahkan revisi ini? Isinya akan diterapkan ke IKU berjalan, dan revisi sebelumnya menjadi arsip. Data lama TIDAK dihapus.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-success mb-1">
                                            <i class="fa-solid fa-check"></i> Sahkan
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" class="d-inline"
                                      action="<?= base_url($baseUrl . '/revisi/batalkan/' . (int) $r['id']) ?>"
                                      onsubmit="return confirm('Batalkan draft ini? Jejaknya tetap tersimpan.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger mb-1">
                                        <i class="fa-solid fa-xmark"></i> Batalkan
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($r['status'] === 'menunggu' && $bolehRevisi): ?>
                                <form method="post" class="d-inline"
                                      action="<?= base_url($baseUrl . '/revisi/tarik/' . (int) $r['id']) ?>"
                                      onsubmit="return confirm('Tarik pengajuan? Revisi kembali jadi draft dan bisa disunting.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger mb-1">
                                        <i class="fa-solid fa-rotate-left"></i> Tarik Pengajuan
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php /* =====================================================
                                     REVISI YANG SUDAH BERLAKU

                                     Dulu baris ini hanya punya "Lihat", sehingga jalan
                                     untuk meminta izin sunting tidak pernah terlihat dari
                                     sini — dan tampak seolah menu ini memang tidak
                                     menyediakannya. Padahal panelnya ada, hanya
                                     tersembunyi satu klik di halaman detail.

                                     Formnya TIDAK diduplikasi ke sini: alasan wajib diisi,
                                     dan kotak teks per baris membuat tabel ini tidak
                                     terbaca. Yang ditaruh di sini adalah PENUNJUK ke
                                     tempat formnya, plus keadaan permohonan yang sedang
                                     berjalan supaya terbaca sekilas.
                                ===================================================== */ ?>
                            <?php $izin = $r['izin_keadaan'] ?? []; ?>

                            <?php if (! empty($izin['sedang_disunting'])): ?>
                                <a href="<?= base_url($baseUrl . '/revisi/sunting/' . (int) $r['id']) ?>"
                                   class="btn btn-sm btn-warning mb-1">
                                    <i class="fa-solid fa-pen"></i> Perbaiki
                                </a>
                                <span class="badge bg-success-subtle text-success-emphasis border mb-1">
                                    izin terbuka
                                </span>
                            <?php elseif (! empty($izin['izin']) && ! empty($izin['boleh_tarik'])): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border mb-1">
                                    <i class="fa-solid fa-hourglass-half"></i> izin menunggu keputusan
                                </span>
                            <?php elseif (! empty($izin['boleh_minta'])): ?>
                                <a href="<?= base_url($baseUrl . '/revisi/lihat/' . (int) $r['id']) ?>"
                                   class="btn btn-sm btn-outline-warning mb-1"
                                   title="<?= ! empty($izin['arsip'])
                                       ? 'Versi ini sudah digantikan, tetapi isinya masih dibaca LAKIP tahun-tahun yang dipayunginya. Ajukan izin untuk membetulkannya.'
                                       : 'Revisi ini sudah berlaku sehingga terkunci. Ajukan izin dulu untuk memperbaikinya.' ?>">
                                    <i class="fa-solid fa-unlock"></i>
                                    <?= ! empty($izin['arsip']) ? 'Ajukan Izin Perbaikan Arsip' : 'Ajukan Izin Sunting' ?>
                                </a>
                            <?php endif; ?>

                            <?php /* Permohonan HAPUS versi. Formnya (beserta alasan wajib)
                                     ada di halaman detail; di sini cukup penunjuknya, dan
                                     hanya bila memang sedang bisa diajukan — menawarkan
                                     tombol yang pasti ditolak hanya membuang waktu. */ ?>
                            <?php $hps = $r['hapus_keadaan'] ?? []; ?>

                            <?php if (! empty($hps['permohonan'])): ?>
                                <span class="badge bg-danger-subtle text-danger-emphasis border mb-1">
                                    <i class="fa-solid fa-hourglass-half"></i> permohonan hapus menunggu
                                </span>
                            <?php elseif (! empty($hps['boleh_minta'])): ?>
                                <a href="<?= base_url($baseUrl . '/revisi/lihat/' . (int) $r['id']) ?>"
                                   class="btn btn-sm btn-outline-danger mb-1"
                                   title="Penghapusan versi tidak bisa dibatalkan, jadi harus disetujui Admin Kabupaten.">
                                    <i class="fa-solid fa-trash"></i> Ajukan Hapus Versi
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-secondary sel-kecil mt-2">
        Revisi berstatus <strong>Arsip</strong> tetap menjadi sumber resmi LAKIP untuk tahun-tahun
        yang dulu dipayunginya. Karena itu ia tidak pernah dihapus.
    </div>
<?php endif; ?>

<?= $this->include('templates/shell_bawah') ?>

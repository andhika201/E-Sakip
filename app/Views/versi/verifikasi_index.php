<?php

/**
 * Antrean verifikasi pengajuan versi (§17, §47).
 *
 * @var array    $antrean
 * @var string[] $modulBoleh
 */
$title        = $title ?? 'Verifikasi Pengajuan Versi';
$judulHalaman = $judulHalaman ?? 'Verifikasi Pengajuan Versi Dokumen';

$labelModul = static fn (string $m): string => match ($m) {
    'rpjmd'   => 'RPJMD',
    'renstra' => 'Renstra',
    'iku'     => 'IKU',
    'lakip'   => 'LAKIP',
    default   => strtoupper($m),
};
?>
<?= $this->include('templates/shell_atas') ?>

<div class="kotak-jejak mb-4">
    <div class="fw-semibold mb-1">Apa yang diputuskan di halaman ini</div>
    <div class="small text-secondary">
        Setiap versi dokumen yang diajukan Perangkat Daerah menunggu di sini sampai
        diputuskan. Menyetujui berarti <strong>menetapkannya berlaku</strong> — isinya langsung
        diterapkan ke data berjalan. Mengembalikan berarti versi kembali menjadi draft
        yang bisa disunting penyusunnya, dan <strong>catatan pengembalian wajib diisi</strong>.
    </div>
</div>

<?php $ikuRevisi = $ikuRevisi ?? []; ?>

<?php if (! empty($ikuRevisi)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            Revisi IKU OPD <span class="badge bg-info text-dark ms-1"><?= count($ikuRevisi) ?></span>
            <div class="text-secondary sel-kecil fw-normal mt-1">
                Mengesahkan berarti isinya diterapkan ke IKU berjalan mulai tahun yang tertulis,
                dan revisi sebelumnya menjadi arsip. Indikator yang hilang
                <strong>dipensiunkan, bukan dihapus</strong> &mdash; realisasi LAKIP tahun-tahun
                lampau tetap utuh.
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle small mb-0" data-no-paginate>
                    <thead class="table-light">
                        <tr>
                            <th style="width:190px">OPD &amp; Periode</th>
                            <th>Revisi</th>
                            <th style="width:110px">Berlaku Mulai</th>
                            <th style="width:150px">Diajukan</th>
                            <th style="width:230px">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ikuRevisi as $r): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc($r['nama_opd'] ?? ('OPD #' . (int) $r['opd_key'])) ?></div>
                                    <div class="text-secondary sel-kecil">
                                        IKU <?= (int) $r['tahun_mulai'] ?>&ndash;<?= (int) $r['tahun_akhir'] ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        <?= esc($r['nomor'] ?? '') ?><?= ! empty($r['nama']) ? ' — ' . esc($r['nama']) : '' ?>
                                    </div>
                                    <?php if (! empty($r['dasar_hukum'])): ?>
                                        <div class="text-secondary sel-kecil">Dasar: <?= esc($r['dasar_hukum']) ?></div>
                                    <?php endif; ?>
                                    <?php if (! empty($r['catatan'])): ?>
                                        <div class="text-secondary sel-kecil mt-1">
                                            <?= nl2br(esc(mb_strimwidth((string) $r['catatan'], 0, 300, '...'))) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-semibold"><?= (int) $r['berlaku_mulai_tahun'] ?></td>
                                <td class="sel-kecil text-secondary">
                                    <?= ! empty($r['submitted_at'])
                                        ? esc(date('d M Y H:i', strtotime($r['submitted_at'])))
                                        : '&mdash;' ?>
                                </td>
                                <td>
                                    <?php /* Tombol utamanya PERIKSA, bukan Sahkan: menyetujui
                                             dokumen yang belum dibaca bukan verifikasi. */ ?>
                                    <a href="<?= base_url('adminkab/verifikasi/iku/lihat/' . (int) $r['id']) ?>"
                                       class="btn btn-primary btn-sm w-100 mb-2">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i>Periksa Isinya
                                    </a>

                                    <form method="post"
                                          action="<?= base_url('adminkab/verifikasi/iku/sahkan/' . (int) $r['id']) ?>"
                                          class="mb-2"
                                          onsubmit="return confirm('Sahkan revisi IKU ini? Isinya diterapkan ke IKU berjalan mulai tahun <?= (int) $r['berlaku_mulai_tahun'] ?>, dan revisi sebelumnya menjadi arsip.')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-success btn-sm w-100">
                                            <i class="fa-solid fa-check me-1"></i>Sahkan
                                        </button>
                                    </form>

                                    <?php /* Catatan WAJIB, sama seperti pengembalian versi
                                             Renstra: tanpa itu penyusun hanya bisa menebak
                                             apa yang harus diperbaiki. */ ?>
                                    <form method="post"
                                          action="<?= base_url('adminkab/verifikasi/iku/kembalikan/' . (int) $r['id']) ?>">
                                        <?= csrf_field() ?>
                                        <textarea name="catatan" rows="2" required maxlength="5000"
                                                  class="form-control form-control-sm mb-1"
                                                  placeholder="Catatan pengembalian (wajib)"></textarea>
                                        <button class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fa-solid fa-rotate-left me-1"></i>Kembalikan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $izinSunting = $izinSunting ?? []; ?>

<?php if (! empty($izinSunting)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            Permohonan Izin Sunting <span class="badge bg-warning text-dark ms-1"><?= count($izinSunting) ?></span>
            <div class="text-secondary sel-kecil fw-normal mt-1">
                OPD meminta kunci dokumennya dibuka agar bisa diperbaiki. Menyetujui
                <strong>tidak mengubah versi yang sudah ditetapkan</strong> &mdash; arsipnya tetap utuh.
                Modul dokumen tertera pada tiap baris:
                <strong>RENSTRA</strong> hasilnya kembali ke antrean ini sebagai versi berikutnya;
                <strong>IKU</strong> diterapkan OPD sendiri ke IKU berjalan setelah perbaikannya selesai.
                Laporan LAKIP yang sudah difinalkan tidak ikut berubah pada kedua kasus.
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle small mb-0" data-no-paginate>
                    <thead class="table-light">
                        <tr>
                            <th style="width:200px">OPD &amp; Periode</th>
                            <th>Alasan</th>
                            <th style="width:150px">Pemohon</th>
                            <th style="width:230px">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($izinSunting as $z): ?>
                            <?php
                            /* Permohonan HAPUS menumpang antrean yang sama.
                               Ia WAJIB terbaca berbeda: menyetujuinya berarti
                               MENGHAPUS versi, bukan membuka kunci — dan itu
                               tidak bisa dibatalkan. */
                            $iniHapus = ($z['jenis'] ?? 'sunting') === 'hapus';
                            ?>
                            <tr class="<?= $iniHapus ? 'table-danger' : '' ?>">
                                <td>
                                    <div class="fw-semibold"><?= esc($z['nama_opd'] ?? ('OPD #' . (int) $z['opd_key'])) ?></div>
                                    <div class="text-secondary sel-kecil">
                                        <?= esc(strtoupper($z['modul'])) ?>
                                        <?= (int) $z['periode_mulai'] ?>&ndash;<?= (int) $z['periode_akhir'] ?>
                                    </div>
                                    <?php if ($iniHapus): ?>
                                        <span class="badge bg-danger mt-1">
                                            <i class="fa-solid fa-trash me-1"></i>PERMOHONAN HAPUS VERSI
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($z['alasan']) ?></td>
                                <td class="sel-kecil text-secondary">
                                    <?= esc($z['diminta_nama'] ?? '&mdash;') ?>
                                    <div>
                                        <?= ! empty($z['diminta_pada'])
                                            ? esc(date('d M Y H:i', strtotime($z['diminta_pada'])))
                                            : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="post"
                                          action="<?= base_url('adminkab/verifikasi/izin/setujui/' . (int) $z['id']) ?>"
                                          class="mb-2"
                                          onsubmit="return confirm('<?= $iniHapus
                                              ? 'HAPUS versi ini sekarang? Arsip isinya ikut terhapus dan TIDAK BISA dikembalikan.'
                                              : 'Beri izin sunting ' . esc(strtoupper($z['modul']), 'js') . '? Dokumen OPD ini akan terbuka untuk diperbaiki, sementara arsip versi yang sudah ditetapkan tetap utuh.' ?>')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm w-100 <?= $iniHapus ? 'btn-danger' : 'btn-success' ?>">
                                            <i class="fa-solid <?= $iniHapus ? 'fa-trash' : 'fa-unlock' ?> me-1"></i>
                                            <?= $iniHapus ? 'Setujui &amp; Hapus' : 'Beri Izin' ?>
                                        </button>
                                    </form>

                                    <?php /* Menolak WAJIB bercatatan: tanpa itu pemohon hanya bisa
                                             menebak apa yang harus diperbaiki, dan biasanya
                                             mengajukan permohonan yang sama lagi. */ ?>
                                    <form method="post"
                                          action="<?= base_url('adminkab/verifikasi/izin/tolak/' . (int) $z['id']) ?>">
                                        <?= csrf_field() ?>
                                        <textarea name="catatan" rows="2" required maxlength="5000"
                                                  class="form-control form-control-sm mb-1"
                                                  placeholder="Alasan penolakan (wajib)"></textarea>
                                        <button class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fa-solid fa-xmark me-1"></i>Tolak
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php /* =====================================================================
         IZIN YANG SEDANG TERBUKA

         Antrean di atas hanya memuat yang berstatus `pending`. Begitu sebuah
         izin disetujui, ia LENYAP dari layar ini — padahal cabut() dan rutenya
         sudah ada sejak awal, hanya tidak pernah punya tempat untuk ditekan.

         Akibatnya nyata: satu dokumen hanya boleh punya satu permohonan
         menggantung, sehingga izin yang terlanjur disetujui lalu tidak pernah
         ditutup MEMBLOKIR seluruh lingkup itu dari permohonan baru — dan tidak
         seorang pun bisa melihat penyebabnya, apalagi mencabutnya.
     ===================================================================== */ ?>
<?php $izinBerjalan = $izinBerjalan ?? []; ?>

<?php if (! empty($izinBerjalan)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            Izin Sunting yang Sedang Terbuka
            <span class="badge bg-success ms-1"><?= count($izinBerjalan) ?></span>
            <div class="text-secondary sel-kecil fw-normal mt-1">
                Sudah disetujui dan belum ditutup. Selama masih terbuka, lingkup itu
                tidak bisa mengajukan permohonan baru — cabut bila penyuntingannya
                ternyata tidak jadi dikerjakan.
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle small mb-0" data-no-paginate>
                    <thead class="table-light">
                        <tr>
                            <th style="width:200px">OPD &amp; Periode</th>
                            <th>Alasan</th>
                            <th style="width:170px">Disetujui</th>
                            <th style="width:150px">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($izinBerjalan as $z): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <?= esc($z['nama_opd'] ?? ('OPD #' . (int) $z['opd_key'])) ?>
                                    </div>
                                    <div class="text-secondary sel-kecil">
                                        <?= esc(strtoupper($z['modul'])) ?>
                                        <?= (int) $z['periode_mulai'] ?>&ndash;<?= (int) $z['periode_akhir'] ?>
                                        <?php if (! empty($z['version_id'])): ?>
                                            &middot; versi #<?= (int) $z['version_id'] ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= esc($z['alasan']) ?></td>
                                <td class="sel-kecil text-secondary">
                                    <?= esc($z['diputus_nama'] ?? '&mdash;') ?>
                                    <div>
                                        <?= ! empty($z['diputus_pada'])
                                            ? esc(date('d M Y H:i', strtotime($z['diputus_pada'])))
                                            : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="post"
                                          action="<?= base_url('adminkab/verifikasi/izin/cabut/' . (int) $z['id']) ?>"
                                          onsubmit="return confirm('Cabut izin sunting ini? Dokumennya terkunci kembali, dan lingkup itu bisa mengajukan permohonan baru.');">
                                        <?= csrf_field() ?>
                                        <input type="text" name="catatan" maxlength="500"
                                               class="form-control form-control-sm mb-1"
                                               placeholder="Catatan (opsional)">
                                        <button class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fa-solid fa-lock me-1"></i>Cabut Izin
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (! empty($koreksi)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            Permintaan Koreksi <span class="badge bg-primary ms-1"><?= count($koreksi) ?></span>
            <div class="text-secondary sel-kecil fw-normal mt-1">
                Perbaikan salah ketik / administratif pada versi yang sudah ditetapkan.
                Perubahan substantif tidak lewat sini — jalannya versi baru.
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle small revisi-tabel" data-no-paginate>
                    <thead class="table-success">
                        <tr>
                            <th style="width:190px">Dokumen &amp; Kolom</th>
                            <th>Sebelum</th>
                            <th>Usulan</th>
                            <th style="width:200px">Alasan</th>
                            <th style="width:230px">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($koreksi as $k): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc($labelModul($k['modul'])) ?> V<?= (int) $k['version_no'] ?></div>
                                    <div class="text-secondary sel-kecil">
                                        <?= esc($k['nama_opd'] ?? 'Tingkat Kabupaten') ?>
                                    </div>
                                    <div class="sel-kecil mt-1"><code><?= esc($k['field']) ?></code></div>
                                </td>
                                <td class="sel-kecil garis-putus"><?= esc($k['old_value'] ?? '—') ?></td>
                                <td class="sel-kecil"><strong><?= esc($k['requested_value'] ?? '—') ?></strong></td>
                                <td class="sel-kecil">
                                    <?= esc($k['reason']) ?>
                                    <?php if (! empty($k['dasar'])): ?>
                                        <div class="text-secondary">Dasar: <?= esc($k['dasar']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="mb-2"
                                          action="<?= base_url('adminkab/verifikasi/koreksi/setujui/' . (int) $k['id']) ?>"
                                          onsubmit="return confirm('Setujui koreksi ini? Nilai akan langsung diperbarui pada arsip dan data berjalan.')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-success btn-sm w-100">
                                            <i class="fa-solid fa-check me-1"></i>Setujui &amp; Terapkan
                                        </button>
                                    </form>
                                    <form method="post"
                                          action="<?= base_url('adminkab/verifikasi/koreksi/kembalikan/' . (int) $k['id']) ?>">
                                        <?= csrf_field() ?>
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="catatan" class="form-control" required
                                                   placeholder="Catatan (wajib)">
                                            <button class="btn btn-outline-danger">Kembalikan</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($antrean)): ?>
    <div class="alert alert-light border text-center mb-0">
        <i class="fa-regular fa-circle-check text-success me-1"></i>
        Tidak ada pengajuan versi yang menunggu verifikasi.
    </div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-secondary">
            <strong><?= count($antrean) ?></strong> pengajuan menunggu keputusan.
        </div>
        <div class="small text-secondary">
            Wewenang Anda: <?= esc(implode(', ', array_map($labelModul, $modulBoleh))) ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle small revisi-tabel" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th style="width:90px">Dokumen</th>
                    <th style="width:200px">Perangkat Daerah</th>
                    <th>Versi &amp; Alasan</th>
                    <th style="width:120px">Mulai Berlaku</th>
                    <th style="width:130px">Diajukan</th>
                    <th style="width:110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($antrean as $v): ?>
                    <tr>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= esc($labelModul($v['modul'])) ?></span>
                            <div class="text-secondary sel-kecil mt-1">
                                <?= (int) $v['periode_mulai'] ?>&ndash;<?= (int) $v['periode_akhir'] ?>
                            </div>
                        </td>
                        <td>
                            <?= esc($v['nama_opd'] ?? 'Tingkat Kabupaten') ?>
                        </td>
                        <td>
                            <div class="fw-semibold">
                                V<?= (int) $v['version_no'] ?> — <?= esc($v['label']) ?>
                            </div>
                            <?php if (! empty($v['alasan_perubahan'])): ?>
                                <div class="text-secondary sel-kecil">
                                    <?= esc(mb_strimwidth($v['alasan_perubahan'], 0, 160, '...')) ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-secondary sel-kecil">
                                <?php if (! empty($v['dasar_perubahan'])): ?>
                                    Dasar: <?= esc($v['dasar_perubahan']) ?>
                                    <?= ! empty($v['nomor_dasar']) ? ' No. ' . esc($v['nomor_dasar']) : '' ?>
                                    &middot;
                                <?php endif; ?>
                                <?php if (! empty($v['copied_from_version_id'])): ?>
                                    Salinan versi lain
                                <?php elseif ((int) $v['mulai_dari_kosong'] === 1): ?>
                                    <span class="text-danger">Mulai dari kosong</span>
                                <?php else: ?>
                                    Salinan kondisi berjalan
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center sel-kecil"><?= esc($v['effective_from']) ?></td>
                        <td class="sel-kecil">
                            <?= ! empty($v['submitted_at'])
                                ? esc(date('d M Y H:i', strtotime($v['submitted_at'])))
                                : '&mdash;' ?>
                            <?php if ($v['lama_hari'] !== null && $v['lama_hari'] >= 3): ?>
                                <div class="text-danger">menunggu <?= (int) $v['lama_hari'] ?> hari</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= base_url('adminkab/verifikasi/lihat/' . (int) $v['id']) ?>"
                               class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-gavel me-1"></i>Periksa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->include('templates/shell_bawah') ?>

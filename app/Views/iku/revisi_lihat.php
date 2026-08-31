<?php
$title        = $title ?? 'Isi Revisi IKU';
$judulHalaman = $revisi['nama'];

/** @var array $isi */
/** @var array $years */
$tanda = static function (string $jenis): array {
    return match ($jenis) {
        'revisi'     => ['bg-info text-dark',    'Direvisi'],
        'pengganti'  => ['bg-primary',           'Pengganti'],
        'baru'       => ['bg-success',           'Baru'],
        'dihentikan' => ['bg-dark',              'Dihentikan'],
        default      => ['bg-light text-dark',   'Tetap'],
    };
};
?>
<?= $this->include('templates/shell_atas') ?>

<div class="kotak-jejak beku mb-4">
    <div class="row g-3 small">
        <div class="col-md-3">
            <div class="text-secondary">Status</div>
            <div class="fw-semibold"><?= esc(ucfirst($revisi['status'])) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-secondary">Masa berlaku</div>
            <div class="fw-semibold">
                <?php if (in_array($revisi['status'], ['draft', 'batal'], true)): ?>
                    Diusulkan mulai <?= (int) $revisi['berlaku_mulai_tahun'] ?>
                <?php else: ?>
                    <?= (int) $revisi['berlaku_mulai_tahun'] ?> &ndash;
<?php /* NULL pada berlaku_sampai_tahun berarti "belum ada revisi
                         berikutnya", BUKAN "berlaku selamanya" — IKU berhenti
                         di ujung periodenya. Yang ditampilkan karena itu tahun
                         akhir periode; nilainya sengaja TIDAK ditulis ke basis
                         data supaya sahkan() tetap bebas menjahit ulang
                         timeline tanpa harus membatalkan angka tampilan. */ ?>
                    <?= $revisi['berlaku_sampai_tahun'] !== null
                        ? (int) $revisi['berlaku_sampai_tahun']
                        : (int) $revisi['tahun_akhir'] ?>
                <?php endif; ?>
            </div>

            <?php /* Hanya muncul saat revisinya memang boleh disunting (draft,
                     atau berlaku di bawah izin). Mengubah tahun berlaku revisi
                     BERLAKU ikut menggeser batas revisi sebelumnya — modelnya
                     yang menjahit, form ini cuma menyampaikan niat. */ ?>
            <?php if (! empty($bolehUbahBerlaku)): ?>
                <form method="post"
                      action="<?= base_url($baseUrl . '/revisi/berlaku/' . (int) $revisi['id']) ?>"
                      class="d-flex align-items-center gap-1 mt-2"
                      onsubmit="return confirm('Ubah tahun mulai berlaku revisi ini? Masa berlaku revisi sebelumnya ikut disesuaikan.');">
                    <?= csrf_field() ?>
                    <?php
                    /* SELURUH tahun periode ditawarkan, tahun pertama termasuk.
                       Dulu tahun pertama dibuang diam-diam sebagai "jatah
                       Kondisi Awal", sehingga ia hilang dari daftar bahkan
                       ketika benar-benar kosong — dan pemakai tidak diberi tahu
                       alasannya.

                       Tahun yang sedang dipakai revisi lain tetap ditampilkan,
                       dimatikan, DAN disebut pemakainya. Penolakan sungguhannya
                       ada di model saat disimpan; yang di sini hanya
                       memindahkannya ke depan. */
                    $bebas    = $tahunBebas ?? [];
                    $terpakai = $tahunTerpakai ?? [];
                    $kini     = (int) $revisi['berlaku_mulai_tahun'];
                    ?>
                    <select name="berlaku_mulai_tahun" class="form-select form-select-sm" style="width:auto">
                        <?php foreach (range((int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir']) as $th): ?>
                            <?php $dipakai = $terpakai[$th] ?? null; ?>
                            <option value="<?= $th ?>"
                                <?= $th === $kini ? 'selected' : '' ?>
                                <?= $dipakai !== null ? 'disabled' : '' ?>>
                                <?= $th ?><?= $dipakai !== null
                                    ? ' — dipakai revisi ke-' . esc($dipakai['nomor']) . ' (' . esc($dipakai['status']) . ')'
                                    : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap">
                        <i class="fa-solid fa-calendar-pen me-1"></i>Ubah Tahun
                    </button>
                </form>

                <?php if ($bebas === []): ?>
                    <div class="small text-danger mt-1">
                        Seluruh tahun lain pada periode <?= (int) $revisi['tahun_mulai'] ?>&ndash;<?= (int) $revisi['tahun_akhir'] ?>
                        sudah dipakai revisi lain. Geser dulu tahun berlaku salah satunya.
                    </div>
                <?php else: ?>
                    <div class="small text-muted mt-1">
                        Tahun yang masih kosong: <strong><?= esc(implode(', ', $bebas)) ?></strong>.
                        <?php if ((int) $revisi['nomor'] === 0): ?>
                            <br>
                            <span class="text-warning-emphasis">
                                Ini <strong>Kondisi Awal</strong> — jangkar awal periode. Menggesernya
                                membuat tahun sebelum tahun barunya tidak dipayungi versi IKU mana pun,
                                sehingga LAKIP tahun itu dinilai memakai Renstra/RPJMD.
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="col-md-3">
            <div class="text-secondary">Dasar</div>
            <div class="fw-semibold">
                <?= esc($revisi['dasar_hukum'] ?: '—') ?>
                <?= ! empty($revisi['nomor_dasar']) ? ' No. ' . esc($revisi['nomor_dasar']) : '' ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-secondary">Dibekukan</div>
            <div class="fw-semibold"><?= esc(($revisi['dibekukan_pada'] ?? null) ?: '—') ?></div>
        </div>
        <?php if (! empty($revisi['catatan'])): ?>
            <div class="col-12">
                <div class="text-secondary">Catatan</div>
                <div><?= esc($revisi['catatan']) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/* Keadaan kunci revisi ini. Dikirim IkuRevisiTrait::revisiLihat(); layar lama
   yang belum mengirimnya tetap merender arsip beku seperti sebelumnya. */
$keadaanIzin = $keadaanIzin ?? ['terkunci' => false, 'izin' => null, 'boleh_minta' => false,
                               'boleh_tarik' => false, 'sedang_disunting' => false, 'alasan' => null];
$aksiIzin    = base_url($baseUrl . '/revisi/izin');
?>

<?php if (! empty($keadaanIzin['sedang_disunting'])): ?>
    <?php /* Arsip dan revisi berlaku sama-sama boleh diperbaiki di bawah izin,
             tetapi AKIBATNYA berbeda tegas — dan tombolnya harus jujur soal itu.
             Arsip tidak pernah diterapkan ke IKU berjalan; menerapkannya justru
             memundurkan dokumen yang sedang dipakai ke keadaan lama. */ ?>
    <?php $izinArsip = ! empty($keadaanIzin['arsip']); ?>
    <div class="alert alert-warning">
        <div class="fw-semibold mb-1">
            <i class="fa-solid fa-unlock me-1"></i>
            <?= $izinArsip
                ? 'Arsip versi ini sedang terbuka untuk dibetulkan'
                : 'Revisi ini sedang terbuka untuk diperbaiki' ?>
        </div>
        <div class="small mb-2"><?= esc($keadaanIzin['alasan'] ?? '') ?></div>

        <?php /* Dua langkah terpisah dengan sengaja: menyimpan boleh berkali-kali,
                 sedangkan menutup izin adalah keputusan tersendiri. */ ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary btn-sm"
               href="<?= base_url($baseUrl . '/revisi/sunting/' . (int) $revisi['id']) ?>">
                <i class="fa-solid fa-pen me-1"></i>Sunting Isi Revisi
            </a>

            <form method="post" action="<?= $aksiIzin . '/selesai/' . (int) $revisi['id'] ?>"
                  <?php /* Pesannya disusun di PHP lalu di-json_encode, bukan dirangkai
                           langsung di dalam atribut: "\n" pada string PHP berkutip ganda
                           menghasilkan baris baru sungguhan untuk confirm(), dan
                           json_encode yang mengurus kutip serta escape-nya. */ ?>
                  onsubmit="return confirm(<?= json_encode($izinArsip
                      ? "Tutup izin perbaikan arsip ini?\n\nIKU berjalan TIDAK berubah."
                        . " Yang menyesuaikan hanyalah bacaan LAKIP tahun-tahun yang dipayungi versi ini."
                      : "Terapkan perbaikan ke IKU berjalan dan tutup izin sunting?\n\nIKU berjalan"
                        . " akan mengikuti isi arsip revisi ini. Laporan LAKIP yang sudah difinalkan"
                        . " tidak ikut berubah.") ?>);">
                <?= csrf_field() ?>
                <button class="btn btn-success btn-sm">
                    <i class="fa-solid fa-check me-1"></i>
                    <?= $izinArsip ? 'Selesai &amp; Tutup Izin' : 'Selesai &amp; Terapkan ke IKU Berjalan' ?>
                </button>
            </form>
        </div>
    </div>

<?php elseif (! empty($keadaanIzin['terkunci'])): ?>
    <div class="alert alert-light border">
        <div class="fw-semibold mb-1">
            <i class="fa-solid fa-lock me-1"></i>Arsip beku
        </div>
        <div class="small mb-2">
            Isinya adalah IKU sebagaimana berlaku pada masanya &mdash; LAKIP tahun-tahun
            tersebut membacanya dari sini.
            <?= esc($keadaanIzin['alasan'] ?? '') ?>
        </div>

        <?php if (! empty($keadaanIzin['boleh_tarik']) && ! empty($keadaanIzin['izin'])): ?>
            <div class="small text-secondary mb-2">
                Diajukan <?= esc($keadaanIzin['izin']['diminta_nama'] ?? '&mdash;') ?>
                <?= ! empty($keadaanIzin['izin']['diminta_pada'])
                    ? esc(date('d M Y H:i', strtotime($keadaanIzin['izin']['diminta_pada'])))
                    : '' ?>
                &mdash; alasan: <?= esc($keadaanIzin['izin']['alasan'] ?? '') ?>
            </div>
            <form method="post" action="<?= $aksiIzin . '/tarik/' . (int) $keadaanIzin['izin']['id'] ?>"
                  onsubmit="return confirm('Tarik permohonan izin sunting?');">
                <?= csrf_field() ?>
                <button class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-rotate-left me-1"></i>Tarik Permohonan
                </button>
            </form>
        <?php endif; ?>

        <?php if (! empty($keadaanIzin['boleh_minta'])): ?>
            <form method="post" action="<?= $aksiIzin . '/ajukan/' . (int) $revisi['id'] ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-9">
                    <input type="text" name="alasan" class="form-control form-control-sm" required
                           maxlength="500"
                           placeholder="Alasan perbaikan — mis. salah ketik indikator 3, atau target 2032 keliru">
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-warning btn-sm">
                        <i class="fa-solid fa-unlock me-1"></i>Ajukan Izin Sunting
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php /* =====================================================================
         PERMOHONAN HAPUS VERSI

         Diletakkan SESUDAH blok izin sunting dan bergaya paling redup yang
         masih terbaca: menghapus versi tidak bisa dibatalkan, jadi ia bukan
         tombol yang pantas ditemukan lebih dulu daripada "perbaiki".

         Alasannya WAJIB — yang membaca permohonan ini orang di instansi lain,
         dan tanpa alasan ia hanya bisa menebak.
     ===================================================================== */ ?>
<?php $keadaanHapus = $keadaanHapus ?? ['boleh_minta' => false, 'permohonan' => null, 'alasan' => null]; ?>

<?php if (! empty($keadaanHapus['permohonan'])): ?>
    <div class="kotak-jejak awas mb-3">
        <div class="fw-semibold mb-1">
            <i class="fa-solid fa-hourglass-half me-1"></i>Permohonan penghapusan menunggu keputusan
        </div>
        <div class="small text-secondary">
            Diajukan <?= esc($keadaanHapus['permohonan']['diminta_nama'] ?? '—') ?>
            <?= ! empty($keadaanHapus['permohonan']['diminta_pada'])
                ? esc(date('d M Y H:i', strtotime($keadaanHapus['permohonan']['diminta_pada'])))
                : '' ?>
            &mdash; alasan: <?= esc($keadaanHapus['permohonan']['alasan'] ?? '') ?>
            <br>Versi ini akan dihapus begitu Admin Kabupaten menyetujuinya.
        </div>
        <?php if (! empty($keadaanIzin['boleh_tarik']) || ! empty($bolehUbahBerlaku)): ?>
            <form method="post"
                  action="<?= $aksiIzin . '/tarik/' . (int) $keadaanHapus['permohonan']['id'] ?>"
                  class="mt-2"
                  onsubmit="return confirm('Tarik permohonan penghapusan ini?');">
                <?= csrf_field() ?>
                <button class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-rotate-left me-1"></i>Tarik Permohonan
                </button>
            </form>
        <?php endif; ?>
    </div>
<?php elseif (! empty($keadaanHapus['boleh_minta'])): ?>
    <div class="kotak-jejak awas mb-3">
        <div class="fw-semibold mb-1">
            <i class="fa-solid fa-trash me-1"></i>Hapus versi ini
        </div>
        <div class="small text-secondary mb-2">
            <?= esc($keadaanHapus['alasan'] ?? '') ?>
            Arsip isinya ikut terhapus dan <strong>tidak bisa dikembalikan</strong>.
            <?php if ($revisi['status'] === 'berlaku'): ?>
                Versi ini sedang berlaku — bila dihapus, versi penerusnya akan
                diberlakukan kembali ke IKU berjalan.
            <?php endif; ?>
        </div>
        <form method="post" action="<?= base_url($baseUrl . '/revisi/hapus/ajukan/' . (int) $revisi['id']) ?>"
              class="row g-2"
              onsubmit="return confirm('Ajukan penghapusan versi ini kepada Admin Kabupaten?');">
            <?= csrf_field() ?>
            <div class="col-md-9">
                <input type="text" name="alasan" class="form-control form-control-sm" required
                       maxlength="500"
                       placeholder="Alasan penghapusan — mis. versi ini dibuat karena salah pilih periode">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-trash me-1"></i>Ajukan Penghapusan
                </button>
            </div>
        </form>
    </div>
<?php elseif (! empty($keadaanHapus['penghalang'])): ?>
    <div class="kotak-jejak mb-3">
        <div class="small text-secondary">
            <i class="fa-solid fa-lock me-1"></i><?= esc($keadaanHapus['alasan'] ?? '') ?>
        </div>
    </div>
<?php endif; ?>

<a href="<?= base_url($baseUrl . '/revisi') ?>" class="btn btn-outline-secondary btn-sm mb-3">
    <i class="fa-solid fa-arrow-left me-1"></i>Kembali ke Daftar Revisi
</a>

<?php if (empty($isi)): ?>
    <div class="alert alert-light border text-center mb-0">Revisi ini belum berisi sasaran/indikator.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle small revisi-tabel" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th rowspan="2" style="width:40px">No</th>
                    <th rowspan="2">Sasaran</th>
                    <th rowspan="2">Indikator Kinerja Utama</th>
                    <th rowspan="2" style="width:90px">Satuan</th>
                    <th colspan="<?= max(1, count($years)) ?>">Target per Tahun</th>
                    <th rowspan="2" style="width:120px">Perubahan</th>
                </tr>
                <tr>
                    <?php foreach ($years as $th): ?>
                        <th style="width:70px"><?= $th ?></th>
                    <?php endforeach; ?>
                    <?php if (empty($years)): ?><th>-</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 0; ?>
                <?php foreach ($isi as $sas): ?>
                    <?php $baris = ! empty($sas['indikator']) ? $sas['indikator'] : [null]; ?>
                    <?php foreach ($baris as $k => $ind): ?>
                        <tr class="<?= ($ind && $ind['jenis_perubahan'] === 'dihentikan') ? 'table-secondary' : '' ?>">
                            <?php if ($k === 0): ?>
                                <td class="text-center" rowspan="<?= count($baris) ?>"><?= ++$no ?></td>
                                <td rowspan="<?= count($baris) ?>"><?= esc($sas['sasaran']) ?></td>
                            <?php endif; ?>

                            <?php if (! $ind): ?>
                                <td colspan="<?= 3 + max(1, count($years)) ?>" class="text-center text-secondary">
                                    Belum ada indikator pada sasaran ini.
                                </td>
                            <?php else: ?>
                                <td class="<?= $ind['jenis_perubahan'] === 'dihentikan' ? 'garis-putus' : '' ?>">
                                    <?= esc($ind['indikator']) ?>
                                    <?php if (! empty($ind['perubahan_substansial'])): ?>
                                        <span class="badge bg-danger ms-1" title="Definisi/metodologi berubah — angka tahun ini tidak sebanding dengan tahun lalu">
                                            tren terputus
                                        </span>
                                    <?php endif; ?>
                                    <?php if (! empty($ind['catatan_perubahan'])): ?>
                                        <div class="sel-kecil text-secondary"><?= esc($ind['catatan_perubahan']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= esc($ind['satuan_nama'] ?: ($ind['satuan'] ?: '-')) ?></td>
                                <?php foreach ($years as $th): ?>
                                    <td class="text-center"><?= esc($ind['target'][$th]['target'] ?? '-') ?></td>
                                <?php endforeach; ?>
                                <?php if (empty($years)): ?><td class="text-center">-</td><?php endif; ?>
                                <td class="text-center">
                                    <?php [$kelas, $label] = $tanda((string) $ind['jenis_perubahan']); ?>
                                    <span class="badge <?= $kelas ?>"><?= $label ?></span>
                                    <?php if (! empty($ind['indikator_sebelumnya_id'])): ?>
                                        <div class="sel-kecil text-secondary mt-1">
                                            menggantikan #<?= (int) $ind['indikator_sebelumnya_id'] ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-secondary sel-kecil mt-2">
        Baris berlatar abu-abu bertanda <strong>Dihentikan</strong> adalah catatan bahwa indikator itu
        berhenti dipakai mulai revisi ini. Baris tersebut <em>tidak</em> ikut tercetak di LAKIP, tetapi
        sengaja disimpan agar terbaca apa yang berubah.
    </div>
<?php endif; ?>

<?= $this->include('templates/shell_bawah') ?>

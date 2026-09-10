<?php

/**
 * Lihat isi satu versi + aksi lifecycle-nya.
 *
 * §16: versi published hanya boleh dilihat, dibandingkan, dijadikan dasar versi
 * baru, atau diajukan koreksi — TIDAK ada tombol edit.
 *
 * @var array $versi
 * @var array $isi        pohon arsip (RPJMD: misi>tujuan>...; Renstra: tujuan>...)
 * @var array $praTinjau  dampak timeline bila versi ini ditetapkan (draft saja)
 */
$title        = $title ?? $versi['label'];
$judulHalaman = $judulHalaman ?? $versi['label'];

$kelasBadge = static function (string $b): string {
    return match ($b) {
        'CURRENT'             => 'bg-success',
        'HISTORICAL'          => 'bg-secondary',
        'UPCOMING'            => 'bg-info text-dark',
        'DRAFT'               => 'bg-warning text-dark',
        'MENUNGGU VERIFIKASI' => 'bg-primary',
        'CANCELLED'           => 'bg-dark',
        default               => 'bg-light text-dark',
    };
};

/*
 * Renstra disusun tujuan demi tujuan lewat form yang sama dengan "Tambah
 * Renstra" (lihat RenstraVersiIsiTrait). RPJMD belum punya jalur itu, jadi
 * tombolnya hanya muncul untuk Renstra yang masih draft.
 */
$bolehIsiTujuan = ! empty($bolehSunting) && ($versi['modul'] ?? '') === 'renstra';

$aksiTujuan = static function (array $t) use ($bolehIsiTujuan, $baseUrl, $versi): string {
    if (! $bolehIsiTujuan || empty($t['id'])) {
        return '';
    }

    $vid = (int) $versi['id'];
    $tid = (int) $t['id'];

    return '<div class="mt-2 d-flex gap-1">'
        . '<a href="' . base_url($baseUrl . '/versi/tujuan/sunting/' . $vid . '/' . $tid) . '"'
        . ' class="btn btn-outline-warning btn-sm py-0 px-1" title="Sunting tujuan ini">'
        . '<i class="fa-solid fa-pen"></i></a>'
        . '<form method="post" action="' . base_url($baseUrl . '/versi/tujuan/hapus/' . $vid . '/' . $tid) . '"'
        . ' data-konfirmasi="Tujuan ini akan dihapus dari draft versi."'
        . ' data-konfirmasi-judul="Hapus Tujuan dari Draft"'
        . ' data-konfirmasi-nama="' . esc((string) ($t['tujuan_rpjmd'] ?? $t['tujuan'] ?? '-'), 'attr') . '"'
        . ' data-konfirmasi-rincian="Seluruh sasaran di bawah tujuan ini|Indikator beserta target tahunannya">'
        . csrf_field()
        . '<button class="btn btn-outline-danger btn-sm py-0 px-1" title="Hapus tujuan ini">'
        . '<i class="fa-solid fa-trash"></i></button></form>'
        . '</div>';
};

/**
 * Ratakan pohon arsip menjadi daftar tujuan.
 *
 * RPJMD berpuncak di misi (anaknya array `tujuan`); Renstra langsung berpuncak
 * di tujuan. Pembedanya WAJIB `is_array`, bukan `isset`: pada baris tujuan
 * Renstra, kunci `tujuan` berisi TEKS tujuannya sendiri — bukan daftar anak.
 * Memakai isset() membuat teks itu ikut di-foreach dan halaman gagal render.
 */
$tujuanSemua = [];

foreach ($isi as $akar) {
    if (is_array($akar['tujuan'] ?? null)) {
        foreach ($akar['tujuan'] as $t) {
            $tujuanSemua[] = $t;
        }
    } else {
        $tujuanSemua[] = $akar;
    }
}
?>
<?= $this->include('templates/shell_atas') ?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <span class="badge badge-lifecycle <?= $kelasBadge($badge) ?> me-2"><?= esc($badge) ?></span>
        <span class="text-secondary sel-kecil">
            V<?= (int) $versi['version_no'] ?>
            &middot; <?= esc($namaDokumen) ?> <?= (int) $versi['periode_mulai'] ?>&ndash;<?= (int) $versi['periode_akhir'] ?>
            &middot; berlaku <?= esc($rentang) ?>
        </span>
    </div>
    <a href="<?= base_url($baseUrl . '/versi') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Daftar Versi
    </a>
</div>

<?php if (! empty($galatValidasi)): ?>
    <div class="kotak-jejak awas mb-3">
        <div class="fw-semibold text-danger mb-1">Versi ini belum bisa diajukan</div>
        <ul class="small mb-0">
            <?php foreach ($galatValidasi as $g): ?><li><?= esc($g) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($praTinjau !== null && ! empty($praTinjau['peringatan'])): ?>
    <div class="kotak-jejak <?= $praTinjau['boleh_terbit'] ? 'beku' : 'awas' ?> mb-3">
        <div class="fw-semibold mb-1">Dampak bila versi ini ditetapkan</div>
        <ul class="small mb-0">
            <?php foreach ($praTinjau['peringatan'] as $p): ?><li><?= esc($p) ?></li><?php endforeach; ?>
        </ul>
        <?php if (! empty($praTinjau['tahun_terdampak'])): ?>
            <div class="small text-secondary mt-2">
                Tahun yang akan dipayungi versi ini:
                <strong><?= esc(implode(', ', $praTinjau['tahun_terdampak'])) ?></strong>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
/* Tunjukan tampilan utama adalah jawaban KEDUA atas "versi mana yang dipakai";
   yang pertama adalah rentang tanggal. Selama keduanya sama, tidak ada yang
   perlu dikatakan. Begitu berbeda, perbedaannya harus terbaca di layar —
   sebab pengguna yang mengira sedang melihat versi yang berlaku hari ini,
   padahal bukan, tidak akan pernah menyadarinya sendiri. */
$tanggalVersi = $versiMenurutTanggal ?? null;
$selisihTunjuk = ! empty($sudahDitunjuk)
    && $tanggalVersi !== null
    && (int) $tanggalVersi['id'] !== (int) $versi['id'];
?>

<?php if (! empty($sudahDitunjuk)): ?>
    <div class="alert <?= $selisihTunjuk ? 'alert-warning' : 'alert-primary' ?> d-flex gap-2 mb-4">
        <i class="fa-solid fa-thumbtack mt-1"></i>
        <div>
            <strong>Versi ini dipakai sebagai tampilan utama <?= esc($namaDokumen) ?>.</strong>
            <?php if ($selisihTunjuk): ?>
                <div class="small mt-1">
                    Perlu diketahui: menurut tanggal berlaku, yang berlaku hari ini sebenarnya
                    <strong>V<?= (int) $tanggalVersi['version_no'] ?> — <?= esc($tanggalVersi['label']) ?></strong>
                    (mulai <?= esc($tanggalVersi['effective_from']) ?>). Menu <?= esc($namaDokumen) ?>
                    tetap menampilkan versi ini karena Anda menunjuknya, dan perbedaan itu
                    ikut ditulis di sana.
                </div>
            <?php else: ?>
                <div class="small mt-1 text-secondary">
                    Sejalan dengan tanggal berlakunya — versi ini memang yang berlaku hari ini.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <?php if (! empty($bolehTunjuk)): ?>
        <?php if (! empty($sudahDitunjuk)): ?>
            <form method="post" action="<?= base_url($baseUrl . '/versi/lepas-utama/' . (int) $versi['id']) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-thumbtack-slash me-1"></i>Lepas dari Tampilan Utama
                </button>
            </form>
        <?php else: ?>
            <form method="post" action="<?= base_url($baseUrl . '/versi/jadikan-utama/' . (int) $versi['id']) ?>"
                  onsubmit="return confirm('Jadikan versi ini tampilan utama menu <?= esc($namaDokumen) ?>? Isi dokumen tidak berubah — yang berubah hanya versi mana yang tampil lebih dulu.')">
                <?= csrf_field() ?>
                <button class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-thumbtack me-1"></i>Jadikan Tampilan Utama
                </button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($bolehIsiTujuan): ?>
        <a href="<?= base_url($baseUrl . '/versi/tujuan/tambah/' . (int) $versi['id']) ?>" class="btn btn-success btn-sm">
            <i class="fa-solid fa-plus me-1"></i>Tambah Tujuan
        </a>
    <?php endif; ?>

    <?php if ($bolehSunting): ?>
        <a href="<?= base_url($baseUrl . '/versi/sunting/' . (int) $versi['id']) ?>" class="btn btn-warning btn-sm">
            <i class="fa-solid fa-pen me-1"></i>Sunting Isi Draft
        </a>
    <?php endif; ?>

    <?php if ($bolehKeterangan): ?>
        <a href="<?= base_url($baseUrl . '/versi/keterangan/' . (int) $versi['id']) ?>" class="btn btn-outline-warning btn-sm">
            <i class="fa-solid fa-calendar-day me-1"></i>Ubah Tanggal &amp; Keterangan
        </a>
    <?php elseif ($bolehTanggalBaseline): ?>
        <a href="<?= base_url($baseUrl . '/versi/keterangan/' . (int) $versi['id']) ?>" class="btn btn-outline-danger btn-sm">
            <i class="fa-solid fa-calendar-day me-1"></i>Perbaiki Tanggal Baseline
        </a>
    <?php endif; ?>

    <?php if (! empty($bolehIsiBaseline)): ?>
        <form method="post" action="<?= base_url($baseUrl . '/versi/isi-baseline/' . (int) $versi['id']) ?>"
              onsubmit="return confirm('Isi baseline ini dengan salinan kondisi berjalan? Data berjalan tidak akan berubah.')">
            <?= csrf_field() ?>
            <button class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-download me-1"></i>Isi dari Kondisi Berjalan
            </button>
        </form>
    <?php endif; ?>

    <?php if ($bolehAjukan): ?>
        <form method="post" action="<?= base_url($baseUrl . '/versi/ajukan/' . (int) $versi['id']) ?>"
              onsubmit="return confirm('Ajukan versi ini untuk ditetapkan? Selama menunggu verifikasi, isinya tidak bisa disunting.')">
            <?= csrf_field() ?>
            <button class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane me-1"></i>Ajukan untuk Ditetapkan</button>
        </form>
    <?php endif; ?>

    <?php if ($bolehTetapkan): ?>
        <?php if (! empty($dampakKosong)): ?>
            <?php /* Versi tanpa isi: konfirmasi tersendiri yang menyebut angkanya.
                     Konfirmasi generik terbukti tidak cukup untuk akibat sebesar ini. */ ?>
            <form method="post" action="<?= base_url($baseUrl . '/versi/tetapkan/' . (int) $versi['id']) ?>"
                  class="border border-danger rounded p-2"
                  onsubmit="return confirm('SEKALI LAGI: seluruh <?= esc($dampakKosong) ?> akan dipensiunkan. Lanjutkan?')">
                <?= csrf_field() ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="konfirmasi_kosong" value="1"
                           id="konfKosong" required>
                    <label class="form-check-label small text-danger fw-semibold" for="konfKosong">
                        Saya paham versi ini kosong, dan menetapkannya akan memensiunkan
                        <?= esc($dampakKosong) ?> pada periode ini.
                    </label>
                </div>
                <button class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Tetapkan Berlaku (Versi Kosong)
                </button>
            </form>
        <?php else: ?>
            <form method="post" action="<?= base_url($baseUrl . '/versi/tetapkan/' . (int) $versi['id']) ?>"
                  onsubmit="return confirm('Tetapkan versi ini berlaku? Isinya akan diterapkan ke data berjalan dan tindakan ini tidak bisa dibatalkan.')">
                <?= csrf_field() ?>
                <button class="btn btn-success btn-sm"><i class="fa-solid fa-check me-1"></i>Tetapkan Berlaku</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php /* Tombol "Ajukan Koreksi" DIHAPUS. Perbaikan pada dokumen yang sudah
             ditetapkan kini lewat Izin Sunting di menu dokumennya: yang dibuka
             adalah kuncinya, bukan arsipnya, dan penyuntingannya memakai form
             yang sudah dikenal. Layanan koreksi beserta tabelnya sengaja
             DIPERTAHANKAN supaya permintaan lama tetap terbaca di jejak audit. */ ?>

    <?php if (! empty($daftarBanding)): ?>
        <form method="get" action="<?= base_url($baseUrl . '/versi/banding') ?>" class="d-flex gap-2">
            <input type="hidden" name="b" value="<?= (int) $versi['id'] ?>">
            <select name="a" class="form-select form-select-sm" style="width:auto">
                <?php foreach ($daftarBanding as $d): ?>
                    <?php if ((int) $d['id'] === (int) $versi['id']) { continue; } ?>
                    <option value="<?= (int) $d['id'] ?>">V<?= (int) $d['version_no'] ?> — <?= esc($d['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-code-compare me-1"></i>Bandingkan</button>
        </form>
    <?php endif; ?>

    <?php if ($bolehBatalkan): ?>
        <form method="post" action="<?= base_url($baseUrl . '/versi/batalkan/' . (int) $versi['id']) ?>"
              onsubmit="return confirm('Batalkan versi ini? Barisnya tetap tersimpan sebagai jejak.')">
            <?= csrf_field() ?>
            <input type="hidden" name="alasan" value="Dibatalkan dari halaman versi">
            <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-ban me-1"></i>Batalkan</button>
        </form>
    <?php endif; ?>

    <?php if (! empty($keadaanHapus['boleh'])): ?>
        <form method="post" action="<?= base_url($baseUrl . '/versi/hapus/' . (int) $versi['id']) ?>"
              data-konfirmasi="Versi dokumen ini akan dihapus permanen beserta seluruh arsip isinya."
              data-konfirmasi-judul="Hapus Versi Dokumen"
              data-konfirmasi-nama="<?= esc(trim(($namaDokumen ?? 'Versi') . ' — ' . ($versi['label'] ?? ('#' . (int) $versi['id']))), 'attr') ?>"
              data-konfirmasi-rincian="Seluruh tujuan, sasaran, indikator, dan target yang diarsipkan di versi ini|Jejak riwayat versi ini"
              data-konfirmasi-ketik="HAPUS">
            <?= csrf_field() ?>
            <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash me-1"></i>Hapus Versi</button>
        </form>
    <?php elseif (! empty($keadaanHapus['penghalang'])): ?>
        <?php /* Alasannya dinyatakan, bukan didiamkan: tombol yang hilang tanpa
                keterangan membuat orang mengira layarnya rusak. */ ?>
        <span class="badge bg-light text-secondary border" role="note"
              title="<?= esc($keadaanHapus['alasan']) ?>">
            <i class="fa-solid fa-lock me-1"></i>Tidak bisa dihapus
        </span>
    <?php endif; ?>
</div>

<?php if (! $bolehSunting && $versi['status'] === 'published'): ?>
    <div class="kotak-jejak kunci mb-4">
        <div class="fw-semibold mb-1">Versi ini sudah ditetapkan dan bersifat tetap</div>
        <div class="small text-secondary">
            Isinya tidak bisa diubah dari sini, dan itu yang membuat jejak audit,
            snapshot LAKIP, dan perbandingan antarversi tetap sah. Bila ada yang perlu
            diperbaiki, ajukan <strong>Izin Sunting</strong> di menu <?= esc($namaDokumen) ?>;
            setelah disetujui Admin Kabupaten, penyuntingan berjalan seperti biasa dan
            hasilnya menjadi <strong>versi berikutnya</strong>.
        </div>
    </div>
<?php endif; ?>

<?php if (! empty($ringkas)): ?>
    <div class="d-flex flex-wrap gap-3 mb-3 sel-kecil text-secondary">
        <?php foreach ($ringkas as $nama => $jml): ?>
            <span><?= esc(ucwords(str_replace('_', ' ', $nama))) ?>: <strong><?= (int) $jml ?></strong></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($tujuanSemua)): ?>
    <div class="alert alert-light border text-center">
        Versi ini belum berisi apa pun.
        <?php if ($bolehIsiTujuan): ?>
            Mulailah dengan <strong>Tambah Tujuan</strong> di atas &mdash; formnya sama
            dengan form Tambah Renstra yang sudah Anda kenal.
        <?php endif; ?>
        <?php if ($versi['status'] === 'published'): ?>
            Menetapkannya berarti seluruh isi periode ini dipensiunkan.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle small revisi-tabel" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th>Tujuan</th>
                    <th>Sasaran</th>
                    <th>Indikator</th>
                    <th style="width:90px">Satuan</th>
                    <th style="width:220px">Target per Tahun</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tujuanSemua as $t): ?>
                    <?php
                    $namaTujuan = $t['tujuan_rpjmd'] ?? $t['tujuan'] ?? '';
                    $sasaranList = $t['sasaran'] ?? [];

                    /* Tinggi rowspan tujuan = jumlah baris seluruh anaknya.
                       Sasaran tanpa indikator tetap satu baris, supaya
                       kekurangan itu terlihat, bukan raib. */
                    $tinggiTujuan = 0;

                    foreach ($sasaranList as $sHitung) {
                        $tinggiTujuan += max(1, count($sHitung['indikator'] ?? []));
                    }

                    $tinggiTujuan  = max(1, $tinggiTujuan);
                    $tujuanDicetak = false;
                    ?>
                    <?php if (empty($sasaranList)): ?>
                        <tr>
                            <td><?= esc($namaTujuan) ?><?= $aksiTujuan($t) ?></td>
                            <td colspan="4" class="text-secondary">Belum ada sasaran</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sasaranList as $s): ?>
                            <?php
                            $namaSasaran = $s['sasaran_rpjmd'] ?? $s['sasaran'] ?? '';
                            $indList     = $s['indikator'] ?? [];
                            ?>
                            <?php if (empty($indList)): ?>
                                <tr>
                                    <?php if (! $tujuanDicetak): ?>
                                        <td rowspan="<?= $tinggiTujuan ?>"><?= esc($namaTujuan) ?><?= $aksiTujuan($t) ?></td>
                                        <?php $tujuanDicetak = true; ?>
                                    <?php endif; ?>
                                    <td><?= esc($namaSasaran) ?></td>
                                    <td colspan="3" class="text-secondary">Belum ada indikator</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($indList as $k => $i): ?>
                                    <tr>
                                        <?php if (! $tujuanDicetak): ?>
                                            <td rowspan="<?= $tinggiTujuan ?>"><?= esc($namaTujuan) ?><?= $aksiTujuan($t) ?></td>
                                            <?php $tujuanDicetak = true; ?>
                                        <?php endif; ?>
                                        <?php if ($k === 0): ?>
                                            <td rowspan="<?= count($indList) ?>"><?= esc($namaSasaran) ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?= esc($i['indikator_sasaran'] ?? '') ?>
                                            <?php if (($i['jenis_perubahan'] ?? 'tetap') !== 'tetap'): ?>
                                                <span class="badge bg-light text-dark border ms-1"><?= esc($i['jenis_perubahan']) ?></span>
                                            <?php endif; ?>
                                            <?php if ((int) ($i['perubahan_substansial'] ?? 0) === 1): ?>
                                                <span class="badge bg-warning text-dark ms-1"
                                                      title="Tren antar tahun tidak boleh disambung">substansial</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= esc($i['satuan_nama'] ?? $i['satuan'] ?? '') ?></td>
                                        <td class="sel-kecil">
                                            <?php foreach ($i['target'] ?? [] as $tg): ?>
                                                <span class="d-inline-block me-2">
                                                    <?= esc($tg['tahun']) ?>:
                                                    <strong><?= esc($tg['target_tahunan'] ?? $tg['target'] ?? '') ?></strong>
                                                </span>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Jejak Audit</div>
    <div class="card-body">
        <?php if (empty($riwayat)): ?>
            <div class="text-secondary small">Belum ada jejak.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle small mb-0" data-no-paginate>
                    <thead>
                        <tr class="text-secondary">
                            <th style="width:150px">Waktu</th>
                            <th style="width:150px">Aksi</th>
                            <th style="width:160px">Oleh</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $h): ?>
                            <tr>
                                <td class="sel-kecil"><?= esc(date('d M Y H:i', strtotime($h['pada']))) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= esc($h['aksi']) ?></span></td>
                                <td class="sel-kecil">
                                    <?= esc($h['oleh_nama'] ?? '—') ?>
                                    <?php if (! empty($h['oleh_role'])): ?>
                                        <span class="text-secondary">(<?= esc($h['oleh_role']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="sel-kecil">
                                    <?= esc($h['ringkasan'] ?? '') ?>
                                    <?php if (! empty($h['catatan'])): ?>
                                        <div class="text-danger">Catatan: <?= esc($h['catatan']) ?></div>
                                    <?php endif; ?>
                                    <?php if (! empty($h['alasan'])): ?>
                                        <div class="text-secondary">Alasan: <?= esc($h['alasan']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->include('templates/shell_bawah') ?>

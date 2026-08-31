<?php

/**
 * Sunting isi draft revisi IKU.
 *
 * =====================================================================
 * TATA LETAKNYA MENGIKUTI FORM "TAMBAH RENSTRA"
 *
 * Satu kartu per indikator, medan berlabel, dan tombol "+ Tambah Indikator"
 * yang melahirkan kartu baru — bukan satu slot kosong permanen yang hanya
 * bisa menampung SATU tambahan per penyimpanan seperti sebelumnya.
 *
 * Kartu baru lahir dari <template> yang dirender PHP, lalu digandakan JS
 * dengan penanda __N__ diganti nomor urut. Dengan begitu pengetahuan tentang
 * bentuk medan hanya hidup di SATU tempat (closure PHP di bawah), bukan
 * diduplikasi ke string JS yang diam-diam bisa menyimpang.
 *
 * =====================================================================
 * HALAMAN INI SENGAJA TIDAK MEMAKAI SELECT2
 *
 * Footer admin memang meng-select2-kan `select.form-select`, TETAPI ia
 * melewati select yang berada di dalam form ber-method POST
 * (adminOpd/templates/footer.php). Form di halaman ini POST, jadi seluruh
 * dropdown-nya tetap native — dan itu memang yang dikehendaki: konvensi
 * berkas footer adalah form isian tidak di-select2-kan.
 *
 * Konsekuensi yang harus dijaga:
 *
 *   1. JANGAN meng-select2-kan kartu hasil gandaan sendirian. Kartu lama
 *      native, kartu baru ber-select2 = tampilan belang, plus event change
 *      dari select2 (event jQuery) tidak sampai ke listener native di bawah.
 *      Kalau suatu saat halaman ini memang mau ber-select2, ubah KEDUANYA.
 *   2. Karena semuanya native, seluruh handler di bawah cukup memakai
 *      addEventListener dengan delegasi di <form>, tanpa jQuery sama sekali.
 *      Skrip inline ini berjalan SEBELUM footer memuat jQuery, jadi
 *      pemeriksaan `window.jQuery` di sini akan selalu bernilai false —
 *      jangan menghidupkannya kembali.
 *   3. Dropdown "indikator yang digantikan" tetap dibungkus div `.blok-asal`
 *      dan div itulah yang disembunyikan. Membungkusnya tidak merugikan, dan
 *      pembungkus itu yang membuat halaman tetap benar seandainya kelak
 *      select2 dipasang di sini (select2 menampilkan container-nya sendiri,
 *      sehingga menyembunyikan <select>-nya saja tidak menyembunyikan apa pun).
 *
 * @var array $revisi
 * @var array $isi            pohon sasaran > indikator > target (arsip draft)
 * @var array $years
 * @var array $satuan_options
 * @var array $indikatorLive  indikator IKU berjalan, untuk memilih yang digantikan
 */
$title        = $title ?? 'Sunting Draft Revisi';
$judulHalaman = 'Sunting Draft: ' . $revisi['nama'];

// Layar lama yang belum mengirim keadaan izin tetap merender jalur draft.
$keadaanIzin = $keadaanIzin ?? [];

$jenisOpsi = [
    'tetap'      => 'Tetap (tidak berubah)',
    'revisi'     => 'Direvisi (indikator sama, redaksi/target disesuaikan)',
    'pengganti'  => 'Pengganti (indikator baru menggantikan yang lama)',
    'baru'       => 'Baru (tambahan, tidak menggantikan apa pun)',
    'dihentikan' => 'Dihentikan (tidak dipakai lagi mulai revisi ini)',
];

/**
 * Tempelkan isian yang baru saja ditolak di atas nilai yang tersimpan.
 *
 * Saat penyimpanan ditolak, controller melakukan `withInput()`. Tanpa lapisan
 * ini janji itu kosong: form dirender ulang dari isi draft, dan semua ketikan
 * yang belum tersimpan menguap — pemakai diminta mengetik ulang justru pada
 * saat ia sedang membetulkan satu kesalahan kecil.
 *
 * Bentuk targetnya berbeda antara keduanya: draft menyimpan baris
 * (`[tahun]['target']`), POST mengirim skalar (`[tahun]`). Perbedaan itu
 * dirapikan di sini — kalau tidak, `['target']` dibaca dari sebuah string dan
 * seluruh kolom target tampil kosong tanpa galat apa pun.
 */
$tempelIsianDitolak = static function (array $ind, $lama): array {
    if (! is_array($lama)) {
        return $ind;
    }

    $target = $ind['target'] ?? [];

    if (isset($lama['target']) && is_array($lama['target'])) {
        foreach ($lama['target'] as $th => $nilai) {
            $target[$th] = ['target' => is_array($nilai) ? ($nilai['target'] ?? '') : $nilai];
        }
    }

    unset($lama['target']);

    return array_merge($ind, $lama, ['target' => $target]);
};

/**
 * Satu blok medan indikator. Dipakai kartu yang sudah ada DAN template kartu
 * baru — dipakai ulang, bukan disalin, supaya keduanya tidak pernah menyimpang.
 *
 * @param bool $adaBaris true = baris arsip yang sudah ada (awalan baris[ID])
 */
$medanIndikator = static function (
    string $awalan,
    array $ind,
    array $satuanOptions,
    array $years,
    array $jenisOpsi,
    array $indikatorLive,
    bool $adaBaris
) {
    $jenisSekarang = $ind['jenis_perubahan'] ?? ($adaBaris ? 'tetap' : 'baru');
    ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Indikator <span class="text-danger">*</span></label>
        <?php /* `required` bukan sekadar hiasan: tanpa itu, kartu yang lupa
                 diisi namanya ditolak baru di sisi model — setelah pemakai
                 kehilangan konteks halaman. Sisi model tetap menjaga, karena
                 atribut ini bisa dilewati. */ ?>
        <textarea name="<?= $awalan ?>[indikator]" class="form-control isian-indikator" rows="2" required
                  placeholder="Tuliskan indikator kinerjanya"><?= esc($ind['indikator'] ?? '') ?></textarea>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Definisi Operasional</label>
            <textarea name="<?= $awalan ?>[definisi]" class="form-control" rows="2"
                      placeholder="Penjelasan makna indikator"><?= esc($ind['definisi'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Formula / Rumusan Perhitungan</label>
            <textarea name="<?= $awalan ?>[rumusan_perhitungan]" class="form-control" rows="2"
                      placeholder="Cara menghitung capaian indikator"><?= esc($ind['rumusan_perhitungan'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Satuan</label>
            <select name="<?= $awalan ?>[satuan]" class="form-select">
                <option value="">&mdash; pilih &mdash;</option>
                <?php foreach ($satuanOptions as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"
                        <?= ((string) ($ind['satuan'] ?? '') === (string) $s['id']) ? 'selected' : '' ?>>
                        <?= esc($s['satuan']) ?>
                    </option>
                <?php endforeach; ?>
                <?php
                /* Satuan berupa teks bebas (bukan id) tetap dipertahankan
                   sebagai pilihan, supaya menyimpan ulang tidak menghapusnya. */
                $bebas = trim((string) ($ind['satuan'] ?? ''));
                if ($bebas !== '' && ! preg_match('/^[0-9]+$/', $bebas)): ?>
                    <option value="<?= esc($bebas) ?>" selected><?= esc($bebas) ?></option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Jenis Indikator</label>
            <select name="<?= $awalan ?>[jenis_indikator]" class="form-select">
                <option value="">Pilih Jenis Indikator</option>
                <option value="positif" <?= ($ind['jenis_indikator'] ?? '') === 'positif' ? 'selected' : '' ?>>
                    Indikator Positif (naik = baik)
                </option>
                <option value="negatif" <?= ($ind['jenis_indikator'] ?? '') === 'negatif' ? 'selected' : '' ?>>
                    Indikator Negatif (turun = baik)
                </option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kondisi Awal (Baseline)</label>
            <input type="text" name="<?= $awalan ?>[baseline]" class="form-control" maxlength="50"
                   value="<?= esc($ind['baseline'] ?? '') ?>" placeholder="Opsional">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Sumber Data</label>
            <textarea name="<?= $awalan ?>[sumber_data]" class="form-control" rows="2"
                      placeholder="Contoh: Laporan rutin bidang"><?= esc($ind['sumber_data'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Penanggung Jawab</label>
            <input type="text" name="<?= $awalan ?>[penanggung_jawab]" class="form-control" maxlength="255"
                   value="<?= esc($ind['penanggung_jawab'] ?? '') ?>" placeholder="Contoh: Bidang Kesmas">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Target Capaian per Tahun</label>
        <div class="row g-2">
            <?php foreach ($years as $th): ?>
                <div class="col">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><?= (int) $th ?></span>
                        <input type="text" class="form-control text-center"
                               name="<?= $awalan ?>[target][<?= (int) $th ?>]"
                               value="<?= esc($adaBaris ? ($ind['target'][$th]['target'] ?? '') : '') ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="border-top pt-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Jenis Perubahan</label>
                <select name="<?= $awalan ?>[jenis_perubahan]" class="form-select pilih-jenis">
                    <?php foreach ($jenisOpsi as $nilai => $label): ?>
                        <?php if (! $adaBaris && ! in_array($nilai, ['baru', 'pengganti'], true)) {
                            continue;
                        } ?>
                        <option value="<?= $nilai ?>" <?= $jenisSekarang === $nilai ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <?php /* Dibungkus div karena select2 menampilkan container-nya
                         sendiri — menyembunyikan <select>-nya saja tidak
                         menyembunyikan apa pun. */ ?>
                <div class="blok-asal" <?= $jenisSekarang === 'pengganti' ? '' : 'style="display:none"' ?>>
                    <label class="form-label">Indikator yang digantikan <span class="text-danger">*</span></label>
                    <select name="<?= $awalan ?>[indikator_sebelumnya_id]" class="form-select">
                        <option value="">&mdash; pilih indikator yang digantikan &mdash;</option>
                        <?php foreach ($indikatorLive as $live): ?>
                            <option value="<?= (int) $live['id'] ?>"
                                <?= ((int) ($ind['indikator_sebelumnya_id'] ?? 0) === (int) $live['id']) ? 'selected' : '' ?>>
                                <?= esc(mb_strimwidth($live['indikator'], 0, 70, '...')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check mb-2">
                    <?php /* Underscore SENGAJA dipertahankan pada id: awalan kartu
                             template memuat placeholder __N__, dan membuang
                             underscore-nya berarti setiap kartu gandaan lahir
                             dengan id kembar — klik label "Tren terputus" di
                             kartu kedua mencentang kartu pertama. */ ?>
                    <input class="form-check-input" type="checkbox" value="1"
                           id="subs<?= esc(preg_replace('/[^A-Za-z0-9_]/', '', $awalan)) ?>"
                           name="<?= $awalan ?>[perubahan_substansial]"
                        <?= ! empty($ind['perubahan_substansial']) ? 'checked' : '' ?>>
                    <label class="form-check-label small"
                           for="subs<?= esc(preg_replace('/[^A-Za-z0-9_]/', '', $awalan)) ?>">
                        Tren terputus
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <input type="text" name="<?= $awalan ?>[catatan_perubahan]" class="form-control form-control-sm"
                   value="<?= esc($ind['catatan_perubahan'] ?? '') ?>"
                   placeholder="Catatan perubahan (opsional) — mengapa indikator ini berubah">
        </div>
    </div>
    <?php
};
?>
<?= $this->include('templates/shell_atas') ?>

<style>
    /* =====================================================================
       TATA LETAK MENGIKUTI LAYAR "TAMBAH SASARAN MANDIRI"

       Kartu shell bawaannya selebar layar. Di sini ia dipersempit dan
       dipusatkan persis seperti adminOpd/iku/tambah_iku.php (max-width
       1200px), supaya dua layar yang sama-sama mengisi sasaran & indikator
       tidak terasa seperti dua aplikasi berbeda.
       ===================================================================== */
    main > .bg-white {
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        width: 100%;
    }
    main { display: flex; justify-content: center; }

    /* Judul bawaan shell diganti judul+subjudul di badan halaman. */
    main > .bg-white > h2.h4 { display: none; }

    /* Kartu yang ditandai Dihentikan diredupkan supaya nasibnya terbaca
       sekilas, tanpa menyembunyikan isinya — pengguna masih bisa berubah
       pikiran dan memilih jenis lain. */
    .indikator-kartu.kartu-dihentikan {
        opacity: .55;
        border-left: 4px solid #dc3545 !important;
    }

    /* Bilah simpan menempel di bawah: pada draft berisi banyak indikator,
       tombol simpan yang hanya ada di ujung halaman mudah dikira tidak ada. */
    .bilah-simpan {
        position: sticky;
        bottom: 0;
        z-index: 100;
        background: rgba(255, 255, 255, .97);
        border-top: 1px solid #dee2e6;
        /* shell_atas membungkus isi dalam kartu ber-`p-4` = 1.5rem; margin
           negatifnya harus sebesar itu agar bilah benar-benar menempel ke
           tepi kartu, bukan berhenti 0.5rem sebelumnya. */
        margin: 0 -1.5rem -1.5rem;
        padding: .75rem 1.5rem;
    }
</style>

<?php
/* Halaman ini melayani DUA keadaan yang sangat berbeda, dan menyamakan
   kalimatnya akan menyesatkan: menyunting draft (belum pernah berlaku) versus
   memperbaiki arsip yang SUDAH berlaku di bawah izin. Yang kedua menyentuh
   dokumen resmi, jadi ia harus terbaca seperti itu. */
$bawahIzin = ! empty($keadaanIzin['sedang_disunting']);
?>

<h2 class="h3 fw-bold text-center mb-2" style="color: #00743e;">
    <?= $bawahIzin ? 'Perbaiki Revisi IKU' : 'Sunting Draft Revisi IKU' ?>
</h2>
<p class="text-center text-muted small mb-4">
    <?= esc($revisi['nama']) ?> &mdash; periode
    <?= (int) $revisi['tahun_mulai'] ?>&ndash;<?= (int) $revisi['tahun_akhir'] ?>,
    berlaku mulai <strong><?= (int) $revisi['berlaku_mulai_tahun'] ?></strong>.
</p>

<?php if ($bawahIzin): ?>
    <div class="kotak-jejak mb-4 border-warning">
        <div class="fw-semibold mb-1">
            <i class="fa-solid fa-unlock me-1"></i>Memperbaiki arsip revisi yang SUDAH BERLAKU
        </div>
        <div class="small text-secondary">
            Perbaikan tersimpan ke arsip revisi ini. IKU berjalan, LAKIP, dashboard, dan API
            publik <strong>belum berubah</strong> sampai Anda menekan
            <em>Selesai &amp; Terapkan</em> di halaman revisi. Laporan LAKIP yang sudah
            difinalkan tidak akan ikut berubah — angkanya sudah dibekukan ke snapshot.
        </div>
    </div>
<?php else: ?>
    <div class="kotak-jejak mb-4">
        <div class="fw-semibold mb-1">Anda sedang menyunting DRAFT</div>
        <div class="small text-secondary">
            Semua perubahan di halaman ini tersimpan di dalam draft saja. IKU berjalan, LAKIP,
            dashboard, dan API publik <strong>belum berubah</strong> sampai draft ini disahkan.
            Draft berlaku mulai tahun <strong><?= (int) $revisi['berlaku_mulai_tahun'] ?></strong>.
        </div>
    </div>
<?php endif; ?>

<div class="alert alert-light border small">
    <strong>Membedakan jenis perubahan itu penting.</strong>
    <em>Direvisi</em> berarti indikatornya tetap sama — trennya boleh disambung antar tahun.
    <em>Pengganti</em> berarti indikator lama diganti indikator lain; asal-usulnya wajib dipilih supaya
    riwayatnya bisa ditelusuri. Bila rumusan/metodologinya berubah sehingga angka tahun ini
    <u>tidak sebanding</u> dengan tahun lalu, centang <strong>Tren terputus</strong> agar grafik tidak
    menyambungkan dua hal yang berbeda.
</div>

<div class="d-flex gap-2 mb-3">
    <a href="<?= base_url($baseUrl . '/revisi') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Daftar Revisi
    </a>
    <a href="<?= base_url($baseUrl . '/revisi/lihat/' . (int) $revisi['id']) ?>" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-eye me-1"></i>Pratinjau
    </a>
</div>

<form method="post" action="<?= base_url($baseUrl . '/revisi/sunting/' . (int) $revisi['id']) ?>" id="form-draft">
    <?= csrf_field() ?>

    <?php if (empty($isi)): ?>
        <div class="alert alert-warning">
            Draft ini kosong — tidak ada sasaran IKU pada periode
            <?= (int) $revisi['tahun_mulai'] ?>&ndash;<?= (int) $revisi['tahun_akhir'] ?> saat draft dibuat.
        </div>
    <?php else: ?>
        <?php /* Satu <section> per sasaran, bergaya sama dengan
                 templates/iku/_form.php: judul h5 lalu daftar kartu
                 `.indikator-item`. Kelas fungsional (.indikator-kartu,
                 .wadah-baru, .pola-indikator, .tanda-jenis) DIPERTAHANKAN —
                 seluruh JS di bawah bergantung padanya. */ ?>
        <?php foreach ($isi as $nSas => $sas): ?>
            <?php $sasId = (int) $sas['id']; ?>
            <section class="mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 fw-semibold mb-0">
                        <span class="text-muted">Sasaran <?= $nSas + 1 ?>.</span>
                        <?= esc($sas['sasaran']) ?>
                    </h2>
                    <span class="badge bg-light text-dark border">
                        <?= count($sas['indikator']) ?> indikator
                    </span>
                </div>

                <?php foreach ($sas['indikator'] as $n => $ind): ?>
                    <?php $id = (int) $ind['id']; ?>
                    <div class="indikator-item border rounded p-3 bg-light mb-3 indikator-kartu
                                <?= ($ind['jenis_perubahan'] ?? '') === 'dihentikan' ? 'kartu-dihentikan' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium indikator-title mb-0">Indikator <?= $n + 1 ?></label>
                            <span class="badge bg-light text-dark border tanda-jenis">
                                <?= esc($jenisOpsi[$ind['jenis_perubahan'] ?? 'tetap'] ?? 'Tetap') ?>
                            </span>
                        </div>
                        <?php $medanIndikator(
                            'baris[' . $id . ']',
                            $tempelIsianDitolak($ind, old('baris.' . $id)),
                            $satuan_options, $years, $jenisOpsi, $indikatorLive, true
                        ); ?>
                    </div>
                <?php endforeach; ?>

                <div class="wadah-baru" data-sasaran="<?= $sasId ?>"></div>

                <button type="button" class="btn btn-outline-success btn-sm tambah-indikator"
                        data-sasaran="<?= $sasId ?>">
                    <i class="fa-solid fa-plus me-1"></i>Tambah Indikator
                </button>

                <?php /* Template kartu baru. Isi <template> tidak pernah ikut
                         terkirim saat submit, jadi medan ber-placeholder __N__
                         di dalamnya aman. */ ?>
                <template class="pola-indikator" data-sasaran="<?= $sasId ?>">
                    <div class="indikator-item border border-success rounded p-3 bg-white mb-3 indikator-kartu">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-medium indikator-title text-success mb-0">
                                <i class="fa-solid fa-plus me-1"></i>Indikator baru
                            </label>
                            <button type="button" class="btn btn-outline-danger btn-sm hapus-kartu-baru"
                                    title="Batalkan penambahan ini">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <input type="hidden" name="baru[s<?= $sasId ?>___N__][revisi_sasaran_id]"
                               value="<?= $sasId ?>">
                        <?php $medanIndikator('baru[s' . $sasId . '___N__]', [], $satuan_options, $years, $jenisOpsi, $indikatorLive, false); ?>
                    </div>
                </template>
            </section>
            <hr class="my-4">
        <?php endforeach; ?>
    <?php endif; ?>

    <?php /* =====================================================================
             SASARAN BARU (SASARAN MANDIRI)

             Ditempatkan di EKOR daftar, sesudah seluruh kartu sasaran — sejajar
             dengan "Tambah Indikator" yang juga duduk di ekor daftar indikator
             dalam kartunya. Pemakai membaca dokumen dari atas ke bawah, lalu
             menemukan tombol tambah tepat di tempat isian barunya akan muncul.

             Hanya ditawarkan bila ada Tujuan Renstra yang bisa dipilih. Lingkup
             KABUPATEN tidak punya Renstra, jadi daftarnya kosong dan blok ini
             tidak dirender sama sekali — lebih jujur daripada menampilkan tombol
             yang pasti berujung galat.
        ===================================================================== */ ?>
    <?php if (! empty($tujuanOptions)): ?>
        <div class="wadah-sasaran-baru"></div>

        <div class="d-grid mb-4">
            <button type="button" class="btn btn-outline-primary tambah-sasaran">
                <i class="fa-solid fa-plus me-1"></i>Tambah Sasaran
            </button>
            <div class="form-text text-center mt-1">
                Sasaran yang lahir di IKU, bukan salinan Renstra. Ia ikut diperiksa
                dan baru berlaku setelah revisi ini disahkan.
            </div>
        </div>

        <template class="pola-sasaran">
            <section class="mb-4 border border-primary rounded p-3 kartu-sasaran-baru">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 fw-semibold mb-0 text-primary">
                        <i class="fa-solid fa-plus me-1"></i>Sasaran baru
                    </h2>
                    <button type="button" class="btn btn-outline-danger btn-sm hapus-sasaran-baru"
                            title="Batalkan penambahan sasaran ini">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">
                                Sasaran <span class="text-danger">*</span>
                            </label>
                            <textarea name="sasaran_baru[s__S__][sasaran]" rows="2"
                                      class="form-control isian-sasaran" required
                                      placeholder="Rumusan sasaran yang hendak ditambahkan"></textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">
                                Tujuan Renstra <span class="text-danger">*</span>
                            </label>
                            <select name="sasaran_baru[s__S__][renstra_tujuan_id]" class="form-select" required>
                                <option value="">— pilih tujuan —</option>
                                <?php foreach ($tujuanOptions as $t): ?>
                                    <option value="<?= (int) $t['id'] ?>"><?= esc($t['tujuan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Menentukan letaknya di Cascading. Tanpa ini barisnya
                                muncul di luar blok Tujuan mana pun.
                            </div>
                        </div>
                    </div>

                    <div class="wadah-indikator-sasaran-baru"></div>

                    <button type="button" class="btn btn-outline-success btn-sm tambah-indikator-sasaran-baru">
                        <i class="fa-solid fa-plus me-1"></i>Tambah Indikator
                    </button>
                    <div class="form-text">Sasaran baru wajib punya minimal satu indikator.</div>

                    <?php /* Template bersarang. Isi <template> tidak pernah ikut
                             terkirim, jadi placeholder __S__/__N__ di dalamnya aman. */ ?>
                    <template class="pola-indikator-sasaran-baru">
                        <div class="indikator-item border border-success rounded p-3 mb-3 bg-white indikator-kartu">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-medium indikator-title text-success mb-0">
                                    <i class="fa-solid fa-plus me-1"></i>Indikator baru
                                </label>
                                <button type="button" class="btn btn-outline-danger btn-sm hapus-kartu-baru"
                                        title="Batalkan penambahan ini">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                            <?php $medanIndikator(
                                'sasaran_baru[s__S__][indikator][i__N__]',
                                [], $satuan_options, $years, $jenisOpsi, $indikatorLive, false
                            ); ?>
                        </div>
                    </template>
                </div>
            </section>
        </template>
    <?php endif; ?>

    <div class="bilah-simpan d-flex gap-2 align-items-center">
        <button class="btn btn-success">
            <i class="fa-solid fa-floppy-disk me-1"></i>
            <?= $bawahIzin ? 'Simpan Perbaikan' : 'Simpan Draft' ?>
        </button>
        <a href="<?= $bawahIzin
                    ? base_url($baseUrl . '/revisi/lihat/' . (int) $revisi['id'])
                    : base_url($baseUrl . '/revisi') ?>"
           class="btn btn-outline-secondary">Batal</a>
        <span class="small text-muted ms-auto">
            <?= $bawahIzin
                ? 'Tersimpan ke arsip revisi — IKU berjalan belum berubah sampai diterapkan.'
                : 'Tersimpan ke draft — IKU berjalan belum berubah sampai disahkan.' ?>
        </span>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('form-draft');

        if (!form) return;

        /* Delegasi di <form>, bukan bind per-elemen: kartu hasil gandaan
           ikut tertangani tanpa perlu bind ulang. Native saja sudah cukup —
           lihat catatan select2 di kepala berkas. */
        function saatBerubah(pemilih, tangani) {
            form.addEventListener('change', function (e) {
                if (e.target.matches(pemilih)) tangani(e.target);
            });
        }

        saatBerubah('.pilih-jenis', function (sel) {
            var kartu = sel.closest('.indikator-kartu');

            if (!kartu) return;

            var asal = kartu.querySelector('.blok-asal');

            if (asal) asal.style.display = (sel.value === 'pengganti') ? '' : 'none';

            kartu.classList.toggle('kartu-dihentikan', sel.value === 'dihentikan');

            var tanda = kartu.querySelector('.tanda-jenis');

            if (tanda) tanda.textContent = sel.options[sel.selectedIndex].text;
        });

        // ============ TAMBAH / HAPUS KARTU BARU ============
        var urut = 0;

        document.querySelectorAll('.tambah-indikator').forEach(function (tombol) {
            tombol.addEventListener('click', function () {
                var sasaran = tombol.getAttribute('data-sasaran');
                var pola = document.querySelector('.pola-indikator[data-sasaran="' + sasaran + '"]');
                var wadah = document.querySelector('.wadah-baru[data-sasaran="' + sasaran + '"]');

                if (!pola || !wadah) return;

                urut++;

                /* Placeholder __N__ diganti lewat HTML string, bukan per-atribut:
                   ia muncul di name DAN di id/for pasangan checkbox. */
                var bungkus = document.createElement('div');
                bungkus.innerHTML = pola.innerHTML.split('__N__').join('_' + urut);

                var kartu = bungkus.firstElementChild;
                wadah.appendChild(kartu);

                /* Sengaja TIDAK di-select2-kan: kartu lama pun native.
                   Lihat catatan di kepala berkas sebelum mengubah ini. */
                kartu.querySelector('.isian-indikator').focus();
            });
        });

        /* Kartu baru boleh dibatalkan sebelum disimpan. Kartu yang SUDAH ada
           tidak punya tombol hapus — nasibnya diputuskan lewat jenis perubahan
           "Dihentikan", supaya jejaknya tercatat, bukan lenyap diam-diam. */
        form.addEventListener('click', function (e) {
            var tombol = e.target.closest('.hapus-kartu-baru');

            if (!tombol) return;

            var kartu = tombol.closest('.indikator-kartu');

            if (kartu) kartu.remove();
        });

        // ============ TAMBAH / HAPUS SASARAN BARU ============
        //
        // Sengaja TIDAK menumpang handler .tambah-indikator di atas: handler itu
        // mencari pola & wadahnya lewat document.querySelector('[data-sasaran=..]'),
        // penanda milik sasaran yang SUDAH ADA di arsip. Sasaran baru belum punya
        // id, jadi pencarian di sini dilingkupkan ke kartunya sendiri.
        var urutSasaran = 0;

        var tombolSasaran = document.querySelector('.tambah-sasaran');
        var polaSasaran   = document.querySelector('.pola-sasaran');
        var wadahSasaran  = document.querySelector('.wadah-sasaran-baru');

        if (tombolSasaran && polaSasaran && wadahSasaran) {
            tombolSasaran.addEventListener('click', function () {
                urutSasaran++;

                var bungkus = document.createElement('div');
                bungkus.innerHTML = polaSasaran.innerHTML.split('__S__').join('_' + urutSasaran);

                var kartu = bungkus.firstElementChild;
                wadahSasaran.appendChild(kartu);

                // Satu kartu indikator langsung disodorkan: sasaran tanpa
                // indikator pasti ditolak server, jadi menyodorkannya lebih
                // awal menghemat satu klik sekaligus memberitahu bentuk isiannya.
                var tombolInd = kartu.querySelector('.tambah-indikator-sasaran-baru');
                if (tombolInd) tombolInd.click();

                kartu.querySelector('.isian-sasaran').focus();
            });
        }

        form.addEventListener('click', function (e) {
            var tombol = e.target.closest('.tambah-indikator-sasaran-baru');

            if (!tombol) return;

            var kartuSas = tombol.closest('.kartu-sasaran-baru');

            if (!kartuSas) return;

            var pola  = kartuSas.querySelector('.pola-indikator-sasaran-baru');
            var wadah = kartuSas.querySelector('.wadah-indikator-sasaran-baru');

            if (!pola || !wadah) return;

            urut++;

            var bungkus = document.createElement('div');
            bungkus.innerHTML = pola.innerHTML.split('__N__').join('_' + urut);

            var kartu = bungkus.firstElementChild;
            wadah.appendChild(kartu);

            /* Sengaja TIDAK di-select2-kan; lihat catatan di kepala berkas. */
            kartu.querySelector('.isian-indikator').focus();
        });

        form.addEventListener('click', function (e) {
            var tombol = e.target.closest('.hapus-sasaran-baru');

            if (!tombol) return;

            var kartu = tombol.closest('.kartu-sasaran-baru');

            if (kartu) kartu.remove();
        });
    })();
</script>

<?= $this->include('templates/shell_bawah') ?>

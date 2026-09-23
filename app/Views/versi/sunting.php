<?php

/**
 * Sunting isi draft versi (§9, §11).
 *
 * Struktur nama input sengaja seragam untuk RPJMD dan Renstra, sehingga satu
 * berkas melayani keduanya:
 *
 *   <tingkat>[<idArsip>][teks|csf|satuan|...]
 *   indikator[<id>][target][<tahun>]
 *   baru[<tingkat>][<idInduk>][<n>][...]
 *   hapus[] = "<tingkat>:<idArsip>"
 *
 * @var array  $isi       pohon arsip
 * @var array  $tahun     daftar tahun periode
 * @var string $modul     rpjmd | renstra
 */
$title        = $title ?? ('Sunting ' . $versi['label']);
$judulHalaman = $judulHalaman ?? ((empty($sudahTerbit) ? 'Sunting Draft: ' : 'Sunting Versi: ') . $versi['label']);

$jenisOpsi = [
    'tetap'      => 'Tetap (tidak berubah)',
    'revisi'     => 'Direvisi (indikator sama, redaksi/target disesuaikan)',
    'pengganti'  => 'Pengganti (menggantikan indikator lama)',
    'baru'       => 'Baru (tambahan, tidak menggantikan apa pun)',
    'dihentikan' => 'Dihentikan (tidak dipakai lagi mulai versi ini)',
];

$adaMisi = $modul === 'rpjmd';

/** Ratakan: RPJMD berpuncak di misi, Renstra langsung di tujuan. */
$akar = [];

if ($adaMisi) {
    $akar = $isi;
} else {
    $akar = [['id' => 0, 'misi' => null, 'tujuan' => $isi]];
}

$nilaiSatuan = static function ($ind) {
    // `satuan` bisa berisi id master (numerik) atau teks bebas — keduanya sah,
    // dan yang tersimpan apa adanya supaya tidak mengubah data lama.
    return (string) ($ind['satuan'] ?? '');
};
?>
<?= $this->include('templates/shell_atas', ['shellCss' => '
    .baris-hapus { background: #fdf3f4 !important; }
    .baris-hapus input, .baris-hapus select, .baris-hapus textarea { opacity: .55; }
    .kotak-tambah { border: 1px dashed #adb5bd; border-radius: .375rem; padding: .75rem; background: #fcfcfd; }
    .tabel-sunting td { vertical-align: top; }
']) ?>

<?php if (! empty($sedangBerlaku)): ?>
    <div class="alert alert-warning border mb-3">
        <div class="fw-semibold mb-1">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>Versi ini SEDANG BERLAKU
        </div>
        <div class="small">
            Yang Anda sunting bukan draft, melainkan dokumen <?= esc($namaDokumen) ?> yang dipakai
            hari ini. Begitu disimpan, perubahannya <strong>langsung diterapkan</strong> ke
            <?= esc($namaDokumen) ?> berjalan, dan ikut terbaca oleh Cascading, RKT, Renaksi,
            MONEV, LAKIP, dashboard, serta API publik. Seluruh suntingan tercatat di Jejak Audit.
        </div>
    </div>
<?php elseif (! empty($sudahTerbit)): ?>
    <div class="kotak-jejak beku mb-3">
        <div class="fw-semibold mb-1">Versi sudah ditetapkan, masa berlakunya sudah lewat</div>
        <div class="small text-secondary">
            Suntingan di sini memperbaiki <strong>arsipnya saja</strong>.
            <?= esc($namaDokumen) ?> berjalan sengaja tidak disentuh, karena yang berlaku
            hari ini adalah versi lain.
        </div>
    </div>
<?php else: ?>
    <div class="kotak-jejak beku mb-3">
        <div class="fw-semibold mb-1">Anda sedang menyunting DRAFT</div>
        <div class="small text-secondary">
            Semua perubahan di halaman ini tersimpan di dalam draft saja.
            <?= esc($namaDokumen) ?> berjalan, Cascading, RKT, Renaksi, MONEV, LAKIP, dashboard,
            dan API publik <strong>belum berubah</strong> sampai versi ini ditetapkan berlaku
            (rencananya mulai <strong><?= esc($versi['effective_from']) ?></strong>).
        </div>
    </div>
<?php endif; ?>

<div class="alert alert-light border small">
    <strong>Membedakan jenis perubahan itu penting.</strong>
    <em>Direvisi</em> berarti indikatornya tetap sama — trennya boleh disambung antar tahun.
    <em>Pengganti</em> berarti indikator lama diganti indikator lain; asal-usulnya wajib dipilih
    supaya riwayatnya bisa ditelusuri. Bila rumusan atau metodologinya berubah sehingga angka
    tahun ini <u>tidak sebanding</u> dengan tahun lalu, centang <strong>Tren terputus</strong>
    agar grafik tidak menyambungkan dua hal yang berbeda.
</div>

<div class="d-flex gap-2 mb-3">
    <a href="<?= base_url($baseUrl . '/versi') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Daftar Versi
    </a>
    <a href="<?= base_url($baseUrl . '/versi/lihat/' . (int) $versi['id']) ?>" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-eye me-1"></i>Pratinjau &amp; Ajukan
    </a>
</div>

<form method="post" action="<?= base_url($baseUrl . '/versi/sunting/' . (int) $versi['id']) ?>" id="formSunting">
    <?= csrf_field() ?>

    <?php if (empty($isi)): ?>
        <div class="alert alert-warning small">
            Draft ini masih kosong.
            <?= $adaMisi
                ? 'Tambahkan misi lebih dulu, lalu tujuan, sasaran, dan indikator di bawahnya.'
                : 'Tambahkan tujuan lebih dulu, lalu sasaran dan indikator di bawahnya.' ?>
        </div>
    <?php endif; ?>

    <?php foreach ($akar as $iMisi => $m): ?>
        <?php $misiId = (int) $m['id']; ?>
        <div class="card mb-4">
            <?php if ($adaMisi): ?>
                <div class="card-header bg-success-subtle">
                    <div class="row g-2 align-items-start">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold mb-1">Misi</label>
                            <textarea name="misi[<?= $misiId ?>][teks]" rows="2"
                                      class="form-control form-control-sm"><?= esc($m['misi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">Visi</label>
                            <textarea name="misi[<?= $misiId ?>][visi]" rows="2"
                                      class="form-control form-control-sm"><?= esc($m['visi'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="form-check form-check-inline mt-2">
                        <input class="form-check-input tandai-hapus" type="checkbox"
                               name="hapus[]" value="misi:<?= $misiId ?>" id="hapusMisi<?= $misiId ?>">
                        <label class="form-check-label small text-danger" for="hapusMisi<?= $misiId ?>">
                            Keluarkan misi ini beserta seluruh isinya dari versi
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card-body">
                <?php foreach ($m['tujuan'] ?? [] as $t): ?>
                    <?php $tujuanId = (int) $t['id']; ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-9">
                                <label class="form-label small fw-semibold mb-1">Tujuan</label>
                                <textarea name="tujuan[<?= $tujuanId ?>][teks]" rows="2"
                                          class="form-control form-control-sm"><?= esc($t['tujuan_rpjmd'] ?? $t['tujuan'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input tandai-hapus" type="checkbox"
                                           name="hapus[]" value="tujuan:<?= $tujuanId ?>" id="hapusTujuan<?= $tujuanId ?>">
                                    <label class="form-check-label small text-danger" for="hapusTujuan<?= $tujuanId ?>">
                                        Keluarkan tujuan ini
                                    </label>
                                </div>
                            </div>
                        </div>

                        <?php /* =====================================================
                                 INDIKATOR TUJUAN — khusus RPJMD.
                                 Renstra tidak punya indikator di tingkat tujuan, jadi
                                 seksi ini hanya muncul untuk modul rpjmd. Backend
                                 (RpjmdVersiModel::petaKolom) sudah mengenal tingkat
                                 'indikator_tujuan' + target_tujuan-nya.
                              ===================================================== */ ?>
                        <?php if (($modul ?? '') === 'rpjmd'): ?>
                            <div class="border-start border-3 border-primary ps-3 mb-3">
                                <div class="small fw-semibold text-primary mb-1">Indikator Tujuan</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle small tabel-sunting mb-2">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:40%">Indikator Tujuan</th>
                                                <th>Target per Tahun</th>
                                                <th style="width:76px">Keluarkan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($t['indikator_tujuan'] ?? [] as $it): ?>
                                                <?php
                                                $itId    = (int) $it['id'];
                                                $itTarget = [];
                                                foreach ($it['target'] ?? [] as $tg) {
                                                    $itTarget[(int) $tg['tahun']] = $tg['target_tahunan'] ?? $tg['target'] ?? '';
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <textarea name="indikator_tujuan[<?= $itId ?>][teks]" rows="2"
                                                                  class="form-control form-control-sm"><?= esc($it['indikator_tujuan'] ?? '') ?></textarea>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <?php foreach ($tahun as $th): ?>
                                                                <div style="width:80px">
                                                                    <label class="sel-kecil text-secondary"><?= $th ?></label>
                                                                    <input type="text" class="form-control form-control-sm"
                                                                           name="indikator_tujuan[<?= $itId ?>][target][<?= $th ?>]"
                                                                           value="<?= esc($itTarget[$th] ?? '') ?>">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="form-text sel-kecil">
                                                            Kosongkan untuk menghapus target tahun itu dari versi ini.
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <input class="form-check-input tandai-hapus" type="checkbox"
                                                               name="hapus[]" value="indikator_tujuan:<?= $itId ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="kotak-tambah" data-tambah="indikator_tujuan" data-induk="<?= $tujuanId ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-semibold text-secondary">Tambah indikator tujuan</span>
                                        <button type="button" class="btn btn-outline-success btn-sm tombol-tambah">
                                            <i class="fa-solid fa-plus me-1"></i>Tambah
                                        </button>
                                    </div>
                                    <div class="wadah-baru mt-2"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($t['sasaran'] ?? [] as $s): ?>
                            <?php $sasaranId = (int) $s['id']; ?>
                            <div class="border-start border-3 border-success ps-3 mb-3">
                                <div class="row g-2 align-items-end mb-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Sasaran</label>
                                        <textarea name="sasaran[<?= $sasaranId ?>][teks]" rows="2"
                                                  class="form-control form-control-sm"><?= esc($s['sasaran_rpjmd'] ?? $s['sasaran'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">CSF</label>
                                        <textarea name="sasaran[<?= $sasaranId ?>][csf]" rows="2"
                                                  class="form-control form-control-sm"><?= esc($s['csf'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input tandai-hapus" type="checkbox"
                                                   name="hapus[]" value="sasaran:<?= $sasaranId ?>"
                                                   id="hapusSasaran<?= $sasaranId ?>">
                                            <label class="form-check-label small text-danger" for="hapusSasaran<?= $sasaranId ?>">
                                                Keluarkan
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle small tabel-sunting mb-2">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:24%">Indikator</th>
                                                <th style="width:12%">Satuan</th>
                                                <th style="width:9%">Baseline</th>
                                                <?php /* Jenis indikator menentukan ARAH penilaian (naik = baik, atau
                                                         turun = baik). Kolom ini dulu tidak ada di sini — satu-satunya
                                                         halaman sunting versi yang tidak punya, padahal RPJMD dan IKU
                                                         punya. Akibatnya indikator Renstra yang jenisnya salah atau
                                                         kosong tidak bisa dibetulkan dari layar mana pun, dan
                                                         capaian LAKIP-nya ikut terbalik atau tidak terhitung. */ ?>
                                                <th style="width:12%">Jenis Indikator</th>
                                                <th style="width:20%">Jenis Perubahan</th>
                                                <th>Target per Tahun</th>
                                                <th style="width:76px">Keluarkan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($s['indikator'] ?? [] as $ind): ?>
                                                <?php
                                                $indId  = (int) $ind['id'];
                                                $target = [];

                                                foreach ($ind['target'] ?? [] as $tg) {
                                                    $target[(int) $tg['tahun']] = $tg['target_tahunan'] ?? $tg['target'] ?? '';
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <textarea name="indikator[<?= $indId ?>][teks]" rows="2"
                                                                  class="form-control form-control-sm"><?= esc($ind['indikator_sasaran'] ?? '') ?></textarea>
                                                    </td>
                                                    <td>
                                                        <input list="daftarSatuan" name="indikator[<?= $indId ?>][satuan]"
                                                               class="form-control form-control-sm"
                                                               value="<?= esc($nilaiSatuan($ind)) ?>">
                                                        <?php if (! empty($ind['satuan_nama'])): ?>
                                                            <div class="text-secondary sel-kecil">= <?= esc($ind['satuan_nama']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="indikator[<?= $indId ?>][baseline]"
                                                               class="form-control form-control-sm"
                                                               value="<?= esc($ind['baseline'] ?? '') ?>">
                                                    </td>
                                                    <td>
                                                        <?php $jenisIndNilai = strtolower(trim((string) ($ind['jenis_indikator'] ?? ''))); ?>
                                                        <select name="indikator[<?= $indId ?>][jenis_indikator]"
                                                                class="form-select form-select-sm">
                                                            <option value="">— pilih —</option>
                                                            <option value="positif" <?= in_array($jenisIndNilai, ['positif', 'indikator positif'], true) ? 'selected' : '' ?>>
                                                                Positif (naik = baik)
                                                            </option>
                                                            <option value="negatif" <?= in_array($jenisIndNilai, ['negatif', 'indikator negatif'], true) ? 'selected' : '' ?>>
                                                                Negatif (turun = baik)
                                                            </option>
                                                        </select>
                                                        <?php if ($jenisIndNilai === ''): ?>
                                                            <div class="text-warning sel-kecil mt-1"
                                                                 title="Capaian LAKIP indikator ini tidak dapat dihitung selama jenisnya kosong">
                                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>belum ditentukan
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <select name="indikator[<?= $indId ?>][jenis_perubahan]"
                                                                class="form-select form-select-sm mb-1 pilih-jenis">
                                                            <?php foreach ($jenisOpsi as $k => $lbl): ?>
                                                                <option value="<?= $k ?>"
                                                                    <?= ($ind['jenis_perubahan'] ?? 'tetap') === $k ? 'selected' : '' ?>>
                                                                    <?= esc($lbl) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <select name="indikator[<?= $indId ?>][indikator_sebelumnya_id]"
                                                                class="form-select form-select-sm mb-1 pilih-asal">
                                                            <option value="">— indikator yang digantikan —</option>
                                                            <?php foreach ($indikatorAsal as $ia): ?>
                                                                <option value="<?= (int) $ia['id'] ?>"
                                                                    <?= (int) ($ind['indikator_sebelumnya_id'] ?? 0) === (int) $ia['id'] ? 'selected' : '' ?>>
                                                                    <?= esc(mb_strimwidth($ia['indikator'], 0, 70, '...')) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="1"
                                                                   name="indikator[<?= $indId ?>][perubahan_substansial]"
                                                                   id="subst<?= $indId ?>"
                                                                   <?= (int) ($ind['perubahan_substansial'] ?? 0) === 1 ? 'checked' : '' ?>>
                                                            <label class="form-check-label sel-kecil" for="subst<?= $indId ?>">
                                                                Tren terputus
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <?php foreach ($tahun as $th): ?>
                                                                <div style="width:80px">
                                                                    <label class="sel-kecil text-secondary"><?= $th ?></label>
                                                                    <input type="text" class="form-control form-control-sm"
                                                                           name="indikator[<?= $indId ?>][target][<?= $th ?>]"
                                                                           value="<?= esc($target[$th] ?? '') ?>">
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="form-text sel-kecil">
                                                            Kosongkan untuk menghapus target tahun itu dari versi ini.
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <input class="form-check-input tandai-hapus" type="checkbox"
                                                               name="hapus[]" value="indikator:<?= $indId ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="kotak-tambah" data-tambah="indikator" data-induk="<?= $sasaranId ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-semibold text-secondary">Tambah indikator pada sasaran ini</span>
                                        <button type="button" class="btn btn-outline-success btn-sm tombol-tambah">
                                            <i class="fa-solid fa-plus me-1"></i>Tambah
                                        </button>
                                    </div>
                                    <div class="wadah-baru mt-2"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="kotak-tambah" data-tambah="sasaran" data-induk="<?= $tujuanId ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold text-secondary">Tambah sasaran pada tujuan ini</span>
                                <button type="button" class="btn btn-outline-success btn-sm tombol-tambah">
                                    <i class="fa-solid fa-plus me-1"></i>Tambah
                                </button>
                            </div>
                            <div class="wadah-baru mt-2"></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="kotak-tambah" data-tambah="tujuan" data-induk="<?= $misiId ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-secondary">Tambah tujuan</span>
                        <button type="button" class="btn btn-outline-success btn-sm tombol-tambah">
                            <i class="fa-solid fa-plus me-1"></i>Tambah
                        </button>
                    </div>
                    <div class="wadah-baru mt-2"></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($adaMisi): ?>
        <div class="kotak-tambah mb-4" data-tambah="misi" data-induk="0">
            <div class="d-flex justify-content-between align-items-center">
                <span class="small fw-semibold text-secondary">Tambah misi baru</span>
                <button type="button" class="btn btn-outline-success btn-sm tombol-tambah">
                    <i class="fa-solid fa-plus me-1"></i>Tambah
                </button>
            </div>
            <div class="wadah-baru mt-2"></div>
        </div>
    <?php endif; ?>

    <datalist id="daftarSatuan">
        <?php foreach ($satuanOpsi as $so): ?>
            <option value="<?= (int) $so['id'] ?>"><?= esc($so['satuan']) ?></option>
        <?php endforeach; ?>
    </datalist>

    <div class="d-flex justify-content-between align-items-center">
        <div class="small text-secondary">
            Baris yang dicentang <span class="text-danger">Keluarkan</span> dibuang dari draft.
            Pada data berjalan, baris itu nanti <strong>dipensiunkan</strong> — bukan dihapus.
        </div>
        <button type="submit" class="btn btn-success">
            <i class="fa-solid fa-floppy-disk me-1"></i><?= empty($sudahTerbit) ? 'Simpan Draft' : 'Simpan Versi' ?>
        </button>
    </div>
</form>

<script>
(function () {
    var tahun = <?= json_encode(array_values($tahun)) ?>;
    var opsiSatuan = 'daftarSatuan';
    var hitung = {};

    function inputTarget(prefix) {
        var h = '<div class="d-flex flex-wrap gap-1">';
        tahun.forEach(function (th) {
            h += '<div style="width:80px">'
               + '<label class="sel-kecil text-secondary">' + th + '</label>'
               + '<input type="text" class="form-control form-control-sm" name="' + prefix + '[target][' + th + ']">'
               + '</div>';
        });
        return h + '</div>';
    }

    function barisBaru(tingkat, induk, n) {
        var prefix = 'baru[' + tingkat + '][' + induk + '][' + n + ']';
        var h = '<div class="border rounded p-2 mb-2 bg-white">';

        if (tingkat === 'indikator') {
            h += '<div class="row g-2">'
               + '<div class="col-md-4"><label class="sel-kecil text-secondary">Indikator</label>'
               + '<textarea name="' + prefix + '[teks]" rows="2" class="form-control form-control-sm"></textarea></div>'
               + '<div class="col-md-2"><label class="sel-kecil text-secondary">Satuan</label>'
               + '<input list="' + opsiSatuan + '" name="' + prefix + '[satuan]" class="form-control form-control-sm"></div>'
               + '<div class="col-md-2"><label class="sel-kecil text-secondary">Baseline</label>'
               + '<input type="text" name="' + prefix + '[baseline]" class="form-control form-control-sm"></div>'
               // Indikator BARU pun wajib punya arah penilaian. Tanpa baris ini
               // penjaganya bocor lewat jalur "tambah indikator": barisnya lahir
               // tanpa jenis, dan capaian LAKIP-nya tidak terhitung.
               + '<div class="col-md-2"><label class="sel-kecil text-secondary">Jenis indikator</label>'
               + '<select name="' + prefix + '[jenis_indikator]" class="form-select form-select-sm" required>'
               + '<option value="">— pilih —</option>'
               + '<option value="positif">Positif (naik = baik)</option>'
               + '<option value="negatif">Negatif (turun = baik)</option>'
               + '</select></div>'
               + '<div class="col-md-2"><label class="sel-kecil text-secondary">Jenis perubahan</label>'
               + '<select name="' + prefix + '[jenis_perubahan]" class="form-select form-select-sm">'
               + '<option value="baru">Baru (tambahan)</option>'
               + '<option value="pengganti">Pengganti</option>'
               + '</select></div>'
               + '<div class="col-12">' + inputTarget(prefix) + '</div>'
               + '</div>';
        } else if (tingkat === 'indikator_tujuan') {
            h += '<label class="sel-kecil text-secondary">Indikator Tujuan</label>'
               + '<textarea name="' + prefix + '[teks]" rows="2" class="form-control form-control-sm"></textarea>'
               + inputTarget(prefix);
        } else if (tingkat === 'sasaran') {
            h += '<div class="row g-2">'
               + '<div class="col-md-7"><label class="sel-kecil text-secondary">Sasaran</label>'
               + '<textarea name="' + prefix + '[teks]" rows="2" class="form-control form-control-sm"></textarea></div>'
               + '<div class="col-md-5"><label class="sel-kecil text-secondary">CSF</label>'
               + '<textarea name="' + prefix + '[csf]" rows="2" class="form-control form-control-sm"></textarea></div>'
               + '</div>'
               + '<div class="form-text sel-kecil">Simpan dulu, lalu indikatornya bisa ditambahkan.</div>';
        } else if (tingkat === 'misi') {
            h += '<div class="row g-2">'
               + '<div class="col-md-7"><label class="sel-kecil text-secondary">Misi</label>'
               + '<textarea name="' + prefix + '[teks]" rows="2" class="form-control form-control-sm"></textarea></div>'
               + '<div class="col-md-5"><label class="sel-kecil text-secondary">Visi</label>'
               + '<textarea name="' + prefix + '[visi]" rows="2" class="form-control form-control-sm"></textarea></div>'
               + '</div>';
        } else {
            h += '<label class="sel-kecil text-secondary">Tujuan</label>'
               + '<textarea name="' + prefix + '[teks]" rows="2" class="form-control form-control-sm"></textarea>'
               + '<div class="form-text sel-kecil">Simpan dulu, lalu sasarannya bisa ditambahkan.</div>';
        }

        return h + '</div>';
    }

    document.querySelectorAll('.tombol-tambah').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var kotak   = btn.closest('.kotak-tambah');
            var tingkat = kotak.dataset.tambah;
            var induk   = kotak.dataset.induk;
            var kunci   = tingkat + ':' + induk;

            hitung[kunci] = (hitung[kunci] || 0);
            kotak.querySelector('.wadah-baru').insertAdjacentHTML(
                'beforeend', barisBaru(tingkat, induk, hitung[kunci]++)
            );
        });
    });

    // Baris yang ditandai keluar diredupkan supaya tidak tersimpan tanpa sadar.
    document.querySelectorAll('.tandai-hapus').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var wadah = cb.closest('tr') || cb.closest('.border');
            if (wadah) { wadah.classList.toggle('baris-hapus', cb.checked); }
        });
    });

    // "Pengganti" tanpa asal-usul ditolak server; diingatkan lebih awal di sini
    // supaya operator tidak kehilangan isian formnya.
    document.getElementById('formSunting').addEventListener('submit', function (e) {
        var gagal = null;
        document.querySelectorAll('.pilih-jenis').forEach(function (sel) {
            if (sel.value !== 'pengganti') { return; }
            var asal = sel.closest('td').querySelector('.pilih-asal');
            if (asal && !asal.value) { gagal = asal; }
        });
        if (gagal) {
            e.preventDefault();
            gagal.focus();
            alert('Indikator bertanda "Pengganti" wajib menyebutkan indikator mana yang digantikan.');
        }
    });
})();
</script>

<?= $this->include('templates/shell_bawah') ?>

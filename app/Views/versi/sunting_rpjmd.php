<?php

/**
 * Sunting isi DRAFT versi RPJMD — tampilan menyerupai halaman "Tambah RPJMD"
 * (adminkab/rpjmd/tambah), tetapi memakai KONTRAK FIELD sunting:
 *
 *   <tingkat>[<idArsip>][...]                    -> perbarui baris yang ada
 *   indikator[<id>][target][<tahun>]             -> target per tahun
 *   indikator_tujuan[<id>][target][<tahun>]
 *   hapus[] = "<tingkat>:<idArsip>"               -> keluarkan baris yang ada
 *   baru[<tingkat>][<idInduk>][<n>][...]          -> baris baru
 *
 * Hanya dipakai RpjmdController (versiSuntingView). Renstra tetap memakai
 * versi/sunting.php — struktur dokumennya berbeda.
 *
 * @var array  $isi    pohon arsip (misi -> tujuan -> {indikator_tujuan, sasaran -> indikator})
 * @var array  $tahun  daftar tahun periode
 * @var array  $satuanOpsi
 * @var array  $indikatorAsal
 * @var array  $versi
 * @var string $baseUrl
 * @var string $namaDokumen
 */
$jenisOpsi = [
    'tetap'      => 'Tetap (tidak berubah)',
    'revisi'     => 'Direvisi (redaksi/target disesuaikan)',
    'pengganti'  => 'Pengganti (menggantikan indikator lama)',
    'baru'       => 'Baru (tambahan)',
    'dihentikan' => 'Dihentikan (tidak dipakai lagi mulai versi ini)',
];

$nilaiSatuan = static fn ($ind) => (string) ($ind['satuan'] ?? '');

/* Opsi satuan dibangun sekali, dipakai ulang di PHP (baris lama) & JS (baris baru). */
$opsiSatuanHtml = '<option value="">— pilih / ketik satuan —</option>';
foreach (($satuanOpsi ?? []) as $so) {
    $opsiSatuanHtml .= '<option value="' . esc($so['id'], 'attr') . '">' . esc($so['satuan']) . '</option>';
}

/** <select satuan> untuk sebuah baris; nilai lama dipertahankan walau berupa teks bebas. */
$satuanSelect = static function (string $name, string $nilai) use ($opsiSatuanHtml, $satuanOpsi): string {
    $ada = false;
    foreach (($satuanOpsi ?? []) as $so) {
        if ((string) $so['id'] === $nilai) { $ada = true; break; }
    }
    $opsi = $opsiSatuanHtml;
    // Nilai lama yang bukan id master (teks bebas / id terhapus) tetap terpilih.
    if ($nilai !== '' && ! $ada) {
        $opsi .= '<option value="' . esc($nilai, 'attr') . '" selected>' . esc($nilai) . ' (nilai lama)</option>';
    }
    $opsi = $ada
        ? str_replace('value="' . esc($nilai, 'attr') . '"', 'value="' . esc($nilai, 'attr') . '" selected', $opsi)
        : $opsi;

    return '<select name="' . $name . '" class="form-select select2 satuan-select">' . $opsi . '</select>';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sunting Versi RPJMD e-SAKIP</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single {
            height: 38px; padding: 6px 12px; border: 1px solid #ced4da;
            border-radius: .375rem; display: flex; align-items: center; background: #fff;
        }
        .select2-selection__rendered { padding-left: 0 !important; color: #495057; }
        .select2-selection__arrow { height: 100% !important; }
        .select2-results__option--highlighted { background: #00743e !important; color: #fff; }
        .baris-hapus { background: #fdf3f4 !important; }
        .baris-hapus input, .baris-hapus select, .baris-hapus textarea { opacity: .5; }
        .tahun-target { width: 84px; }
    </style>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition:margin-left .3s ease;">
    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%;max-width:1200px;">
            <h2 class="h3 fw-bold text-center mb-2" style="color:#00743e;">Sunting Versi RPJMD</h2>
            <p class="text-center text-secondary mb-4"><?= esc($versi['label'] ?? '') ?></p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="alert alert-light border small mb-3">
                <strong>Anda sedang menyunting DRAFT.</strong>
                Semua perubahan tersimpan di draft saja — RPJMD berjalan, Cascading, LAKIP, dan dashboard
                <strong>belum berubah</strong> sampai versi ini ditetapkan berlaku
                (rencana mulai <strong><?= esc($versi['effective_from'] ?? '-') ?></strong>).
                Baris lama yang dicentang <span class="text-danger">Keluarkan</span> akan dipensiunkan (bukan dihapus)
                saat versi ditetapkan.
            </div>

            <div class="d-flex gap-2 mb-4">
                <a href="<?= base_url($baseUrl . '/versi') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i>Daftar Versi
                </a>
                <a href="<?= base_url($baseUrl . '/versi/lihat/' . (int) $versi['id']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-eye me-1"></i>Pratinjau &amp; Ajukan
                </a>
            </div>

            <form method="post" action="<?= base_url($baseUrl . '/versi/sunting/' . (int) $versi['id']) ?>" id="rpjmd-form">
                <?= csrf_field() ?>

                <?php if (empty($isi)): ?>
                    <div class="alert alert-warning small">Draft ini masih kosong. Tambahkan misi lebih dulu.</div>
                <?php endif; ?>

                <?php foreach ($isi as $m): ?>
                    <?php $misiId = (int) $m['id']; ?>
                    <section class="border rounded p-3 mb-4">
                        <h2 class="h5 fw-semibold mb-3">Informasi Misi</h2>
                        <div class="row g-3 mb-2">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Misi RPJMD</label>
                                <textarea name="misi[<?= $misiId ?>][teks]" class="form-control" rows="2"><?= esc($m['misi'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Visi Daerah</label>
                                <textarea name="misi[<?= $misiId ?>][visi]" class="form-control" rows="2"><?= esc($m['visi'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input tandai-hapus" type="checkbox" name="hapus[]"
                                   value="misi:<?= $misiId ?>" id="hapusMisi<?= $misiId ?>">
                            <label class="form-check-label small text-danger" for="hapusMisi<?= $misiId ?>">
                                Keluarkan misi ini beserta seluruh isinya
                            </label>
                        </div>

                        <hr class="my-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="h6 fw-semibold mb-0">Daftar Tujuan</h3>
                        </div>

                        <?php foreach ($m['tujuan'] ?? [] as $t): ?>
                            <?php $tujuanId = (int) $t['id']; ?>
                            <div class="bg-light border rounded p-3 mb-3">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <label class="form-label fw-semibold">Tujuan RPJMD</label>
                                        <div class="form-check">
                                            <input class="form-check-input tandai-hapus" type="checkbox" name="hapus[]"
                                                   value="tujuan:<?= $tujuanId ?>" id="hapusTujuan<?= $tujuanId ?>">
                                            <label class="form-check-label small text-danger" for="hapusTujuan<?= $tujuanId ?>">Keluarkan tujuan</label>
                                        </div>
                                    </div>
                                    <textarea name="tujuan[<?= $tujuanId ?>][teks]" class="form-control" rows="2"><?= esc($t['tujuan_rpjmd'] ?? '') ?></textarea>
                                </div>

                                <!-- Indikator Tujuan -->
                                <div class="mb-4">
                                    <h4 class="fw-medium h6">Indikator Tujuan</h4>
                                    <?php foreach ($t['indikator_tujuan'] ?? [] as $it): ?>
                                        <?php
                                        $itId = (int) $it['id'];
                                        $itTarget = [];
                                        foreach ($it['target'] ?? [] as $tg) { $itTarget[(int) $tg['tahun']] = $tg['target_tahunan'] ?? ''; }
                                        ?>
                                        <div class="border rounded p-3 bg-white mb-3">
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <label class="form-label">Indikator</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input tandai-hapus" type="checkbox" name="hapus[]"
                                                               value="indikator_tujuan:<?= $itId ?>" id="hapusIT<?= $itId ?>">
                                                        <label class="form-check-label small text-danger" for="hapusIT<?= $itId ?>">Keluarkan</label>
                                                    </div>
                                                </div>
                                                <input type="text" name="indikator_tujuan[<?= $itId ?>][teks]"
                                                       class="form-control" value="<?= esc($it['indikator_tujuan'] ?? '') ?>">
                                            </div>
                                            <label class="form-label mb-1">Target per Tahun</label>
                                            <div class="row g-2">
                                                <?php foreach ($tahun as $th): ?>
                                                    <div class="col-auto">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text"><?= $th ?></span>
                                                            <input type="text" style="width:90px"
                                                                   name="indikator_tujuan[<?= $itId ?>][target][<?= $th ?>]"
                                                                   class="form-control" value="<?= esc($itTarget[$th] ?? '') ?>">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-primary btn-sm tombol-tambah"
                                                data-tambah="indikator_tujuan" data-induk="<?= $tujuanId ?>">
                                            <i class="fas fa-plus me-1"></i>Tambah Indikator Tujuan
                                        </button>
                                    </div>
                                    <div class="wadah-baru" data-untuk="indikator_tujuan:<?= $tujuanId ?>"></div>
                                </div>

                                <!-- Sasaran -->
                                <div>
                                    <h4 class="fw-medium h6">Sasaran Terkait Tujuan Ini</h4>
                                    <?php foreach ($t['sasaran'] ?? [] as $s): ?>
                                        <?php $sasaranId = (int) $s['id']; ?>
                                        <div class="border rounded p-3 bg-white mb-3">
                                            <div class="row g-2 mb-2">
                                                <div class="col-md-7">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <label class="form-label">Sasaran RPJMD</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input tandai-hapus" type="checkbox" name="hapus[]"
                                                                   value="sasaran:<?= $sasaranId ?>" id="hapusSas<?= $sasaranId ?>">
                                                            <label class="form-check-label small text-danger" for="hapusSas<?= $sasaranId ?>">Keluarkan</label>
                                                        </div>
                                                    </div>
                                                    <textarea name="sasaran[<?= $sasaranId ?>][teks]" class="form-control" rows="2"><?= esc($s['sasaran_rpjmd'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label">CSF (opsional)</label>
                                                    <textarea name="sasaran[<?= $sasaranId ?>][csf]" class="form-control" rows="2"><?= esc($s['csf'] ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="ms-2 ps-2 border-start border-3 border-success-subtle">
                                                <h5 class="fw-medium h6">Indikator Sasaran</h5>
                                                <?php foreach ($s['indikator'] ?? [] as $ind): ?>
                                                    <?php
                                                    $indId = (int) $ind['id'];
                                                    $target = [];
                                                    foreach ($ind['target'] ?? [] as $tg) { $target[(int) $tg['tahun']] = $tg['target_tahunan'] ?? ''; }
                                                    $jenisIndikator = strtolower(trim($ind['jenis_indikator'] ?? ''));
                                                    ?>
                                                    <div class="border rounded p-3 bg-light mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <label class="fw-medium mb-0">Indikator Sasaran</label>
                                                            <div class="form-check">
                                                                <input class="form-check-input tandai-hapus" type="checkbox" name="hapus[]"
                                                                       value="indikator:<?= $indId ?>" id="hapusInd<?= $indId ?>">
                                                                <label class="form-check-label small text-danger" for="hapusInd<?= $indId ?>">Keluarkan</label>
                                                            </div>
                                                        </div>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Indikator</label>
                                                                <input type="text" name="indikator[<?= $indId ?>][teks]"
                                                                       class="form-control" value="<?= esc($ind['indikator_sasaran'] ?? '') ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Satuan</label>
                                                                <?= $satuanSelect('indikator[' . $indId . '][satuan]', $nilaiSatuan($ind)) ?>
                                                                <?php if (! empty($ind['satuan_nama'])): ?>
                                                                    <div class="text-secondary sel-kecil">= <?= esc($ind['satuan_nama']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Jenis Indikator</label>
                                                                <select name="indikator[<?= $indId ?>][jenis_indikator]" class="form-select select2">
                                                                    <option value="indikator positif" <?= $jenisIndikator === 'indikator positif' ? 'selected' : '' ?>>Indikator Positif</option>
                                                                    <option value="indikator negatif" <?= $jenisIndikator === 'indikator negatif' ? 'selected' : '' ?>>Indikator Negatif</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-md-4">
                                                                <label class="form-label">Baseline (Kondisi Awal)</label>
                                                                <input type="text" name="indikator[<?= $indId ?>][baseline]"
                                                                       class="form-control" value="<?= esc($ind['baseline'] ?? '') ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Jenis Perubahan</label>
                                                                <select name="indikator[<?= $indId ?>][jenis_perubahan]" class="form-select pilih-jenis">
                                                                    <?php foreach ($jenisOpsi as $k => $lbl): ?>
                                                                        <option value="<?= $k ?>" <?= ($ind['jenis_perubahan'] ?? 'tetap') === $k ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Menggantikan</label>
                                                                <select name="indikator[<?= $indId ?>][indikator_sebelumnya_id]" class="form-select pilih-asal">
                                                                    <option value="">— indikator yang digantikan —</option>
                                                                    <?php foreach ($indikatorAsal as $ia): ?>
                                                                        <option value="<?= (int) $ia['id'] ?>" <?= (int) ($ind['indikator_sebelumnya_id'] ?? 0) === (int) $ia['id'] ? 'selected' : '' ?>>
                                                                            <?= esc(mb_strimwidth($ia['indikator'], 0, 60, '...')) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="form-check mt-1">
                                                                    <input class="form-check-input" type="checkbox" value="1"
                                                                           name="indikator[<?= $indId ?>][perubahan_substansial]"
                                                                           id="subst<?= $indId ?>" <?= (int) ($ind['perubahan_substansial'] ?? 0) === 1 ? 'checked' : '' ?>>
                                                                    <label class="form-check-label sel-kecil" for="subst<?= $indId ?>">Tren terputus</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <label class="form-label mb-1">Target per Tahun</label>
                                                        <div class="row g-2">
                                                            <?php foreach ($tahun as $th): ?>
                                                                <div class="col-auto">
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text"><?= $th ?></span>
                                                                        <input type="text" style="width:90px"
                                                                               name="indikator[<?= $indId ?>][target][<?= $th ?>]"
                                                                               class="form-control" value="<?= esc($target[$th] ?? '') ?>">
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-info btn-sm tombol-tambah"
                                                            data-tambah="indikator" data-induk="<?= $sasaranId ?>">
                                                        <i class="fas fa-plus me-1"></i>Tambah Indikator Sasaran
                                                    </button>
                                                </div>
                                                <div class="wadah-baru" data-untuk="indikator:<?= $sasaranId ?>"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-success btn-sm tombol-tambah"
                                                data-tambah="sasaran" data-induk="<?= $tujuanId ?>">
                                            <i class="fas fa-plus me-1"></i>Tambah Sasaran
                                        </button>
                                    </div>
                                    <div class="wadah-baru" data-untuk="sasaran:<?= $tujuanId ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-success btn-sm tombol-tambah"
                                    data-tambah="tujuan" data-induk="<?= $misiId ?>">
                                <i class="fas fa-plus me-1"></i>Tambah Tujuan
                            </button>
                        </div>
                        <div class="wadah-baru" data-untuk="tujuan:<?= $misiId ?>"></div>
                    </section>
                <?php endforeach; ?>

                <?php if (! empty($isi)): ?>
                    <div class="d-flex justify-content-end mb-4">
                        <button type="button" class="btn btn-outline-success btn-sm tombol-tambah"
                                data-tambah="misi" data-induk="0">
                            <i class="fas fa-plus me-1"></i>Tambah Misi
                        </button>
                    </div>
                    <div class="wadah-baru mb-4" data-untuk="misi:0"></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= base_url($baseUrl . '/versi/lihat/' . (int) $versi['id']) ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Simpan Draft
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    var tahun = <?= json_encode(array_values($tahun)) ?>;
    var opsiSatuan = <?= json_encode($opsiSatuanHtml) ?>;
    var jenisPerubahan = <?= json_encode($jenisOpsi) ?>;
    var indikatorAsal = <?= json_encode(array_map(static fn ($ia) => ['id' => (int) $ia['id'], 'teks' => mb_strimwidth((string) $ia['indikator'], 0, 60, '...')], $indikatorAsal ?? [])) ?>;
    var hitung = {};

    function initSelect2(ctx) {
        if (!window.jQuery) { return; }
        jQuery(ctx || document).find('.select2').each(function () {
            if (jQuery(this).hasClass('select2-hidden-accessible')) { jQuery(this).select2('destroy'); }
            jQuery(this).select2({ width: '100%', dropdownParent: jQuery('body') });
        });
    }

    function targetRows(prefix) {
        var h = '<label class="form-label mb-1">Target per Tahun</label><div class="row g-2">';
        tahun.forEach(function (th) {
            h += '<div class="col-auto"><div class="input-group input-group-sm">'
               + '<span class="input-group-text">' + th + '</span>'
               + '<input type="text" style="width:90px" class="form-control" name="' + prefix + '[target][' + th + ']"></div></div>';
        });
        return h + '</div>';
    }

    function opsiJenis() {
        var h = '';
        Object.keys(jenisPerubahan).forEach(function (k) {
            if (k === 'tetap' || k === 'dihentikan') { return; } // baris baru: baru/pengganti/revisi
            h += '<option value="' + k + '"' + (k === 'baru' ? ' selected' : '') + '>' + jenisPerubahan[k] + '</option>';
        });
        return h;
    }

    function opsiAsal() {
        var h = '<option value="">— indikator yang digantikan —</option>';
        indikatorAsal.forEach(function (a) { h += '<option value="' + a.id + '">' + a.teks + '</option>'; });
        return h;
    }

    // Markup baris BARU per tingkat (nama field: baru[tingkat][induk][n][...])
    function barisBaru(tingkat, induk, n) {
        var pre = 'baru[' + tingkat + '][' + induk + '][' + n + ']';
        var tutup = '<div class="text-end mt-1"><button type="button" class="btn btn-outline-danger btn-sm buang-baru">'
                  + '<i class="fas fa-times me-1"></i>Batalkan</button></div>';
        var h = '<div class="border rounded p-3 mb-2 bg-white baris-baru">';

        if (tingkat === 'misi') {
            h += '<div class="row g-2">'
               + '<div class="col-md-7"><label class="form-label">Misi RPJMD</label><textarea name="' + pre + '[teks]" rows="2" class="form-control"></textarea></div>'
               + '<div class="col-md-5"><label class="form-label">Visi Daerah</label><textarea name="' + pre + '[visi]" rows="2" class="form-control"></textarea></div>'
               + '</div><div class="form-text">Simpan dulu, lalu tujuan & sasarannya bisa ditambahkan.</div>';
        } else if (tingkat === 'tujuan') {
            h += '<label class="form-label">Tujuan RPJMD</label><textarea name="' + pre + '[teks]" rows="2" class="form-control"></textarea>'
               + '<div class="form-text">Simpan dulu, lalu indikator tujuan & sasarannya bisa ditambahkan.</div>';
        } else if (tingkat === 'indikator_tujuan') {
            h += '<label class="form-label">Indikator Tujuan</label>'
               + '<input type="text" name="' + pre + '[teks]" class="form-control mb-2">'
               + targetRows(pre);
        } else if (tingkat === 'sasaran') {
            h += '<div class="row g-2">'
               + '<div class="col-md-7"><label class="form-label">Sasaran RPJMD</label><textarea name="' + pre + '[teks]" rows="2" class="form-control"></textarea></div>'
               + '<div class="col-md-5"><label class="form-label">CSF (opsional)</label><textarea name="' + pre + '[csf]" rows="2" class="form-control"></textarea></div>'
               + '</div><div class="form-text">Simpan dulu, lalu indikatornya bisa ditambahkan.</div>';
        } else if (tingkat === 'indikator') {
            h += '<div class="row g-2 mb-2">'
               + '<div class="col-md-6"><label class="form-label">Indikator</label><input type="text" name="' + pre + '[teks]" class="form-control"></div>'
               + '<div class="col-md-3"><label class="form-label">Satuan</label><select name="' + pre + '[satuan]" class="form-select select2 satuan-select">' + opsiSatuan + '</select></div>'
               + '<div class="col-md-3"><label class="form-label">Jenis Indikator</label><select name="' + pre + '[jenis_indikator]" class="form-select select2"><option value="indikator positif">Indikator Positif</option><option value="indikator negatif">Indikator Negatif</option></select></div>'
               + '</div>'
               + '<div class="row g-2 mb-2">'
               + '<div class="col-md-4"><label class="form-label">Baseline</label><input type="text" name="' + pre + '[baseline]" class="form-control"></div>'
               + '<div class="col-md-4"><label class="form-label">Jenis Perubahan</label><select name="' + pre + '[jenis_perubahan]" class="form-select pilih-jenis">' + opsiJenis() + '</select></div>'
               + '<div class="col-md-4"><label class="form-label">Menggantikan</label><select name="' + pre + '[indikator_sebelumnya_id]" class="form-select pilih-asal">' + opsiAsal() + '</select></div>'
               + '</div>'
               + targetRows(pre);
        }

        return h + tutup + '</div>';
    }

    document.addEventListener('click', function (ev) {
        var tombol = ev.target.closest('.tombol-tambah');
        if (tombol) {
            var tingkat = tombol.dataset.tambah, induk = tombol.dataset.induk;
            var kunci = tingkat + ':' + induk;
            hitung[kunci] = (hitung[kunci] || 0);
            var wadah = document.querySelector('.wadah-baru[data-untuk="' + kunci + '"]');
            if (wadah) {
                wadah.insertAdjacentHTML('beforeend', barisBaru(tingkat, induk, hitung[kunci]++));
                initSelect2(wadah.lastElementChild);
            }
            return;
        }
        var buang = ev.target.closest('.buang-baru');
        if (buang) { var b = buang.closest('.baris-baru'); if (b) { b.remove(); } return; }
    });

    document.addEventListener('change', function (ev) {
        var cb = ev.target.closest('.tandai-hapus');
        if (cb) {
            var wadah = cb.closest('.border') || cb.closest('section');
            if (wadah) { wadah.classList.toggle('baris-hapus', cb.checked); }
        }
    });

    document.getElementById('rpjmd-form').addEventListener('submit', function (e) {
        var gagal = null;
        document.querySelectorAll('.pilih-jenis').forEach(function (sel) {
            if (sel.value !== 'pengganti') { return; }
            var asal = sel.closest('.row, .baris-baru').querySelector('.pilih-asal');
            if (asal && !asal.value) { gagal = asal; }
        });
        if (gagal) {
            e.preventDefault();
            gagal.focus();
            alert('Indikator bertanda "Pengganti" wajib menyebutkan indikator yang digantikan.');
        }
    });

    if (window.jQuery) { jQuery(document).ready(function () { initSelect2(); }); }
    else { window.addEventListener('load', function () { initSelect2(); }); }
})();
</script>
</body>
</html>

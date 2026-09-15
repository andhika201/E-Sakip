<?php
helper('capaian');

$isBupati = ($jenis === 'bupati');
$isEdit   = ($mode === 'edit');

// Satuan bertipe predikat (mis. Opini BPK) -> Target Triwulan dipilih dari
// skala, bukan diketik bebas. Lihat app/Models/SatuanModel.php.
$skala      = $skala ?? [];
$isPredikat = $skala !== [];
$eselonLabel = function ($pkJenis, $jabatanEselon = null, $jabatanNama = null) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Eselon III', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    $pkJenis = strtolower(trim((string) $pkJenis));
    if ($pkJenis !== '' && isset($map[$pkJenis])) {
        return $map[$pkJenis];
    }

    $formatNamaEselon = static function ($value) {
        $value = trim((string) $value);
        if ($value === '' || ctype_digit($value)) {
            return null;
        }
        if (preg_match('/^eselon\s+/i', $value)) {
            return $value;
        }
        return 'Eselon ' . $value;
    };

    $label = $formatNamaEselon($jabatanEselon);
    if ($label !== null) {
        return $label;
    }

    $jabatanText = strtolower(trim(preg_replace('/\s+/', ' ', (string) $jabatanNama)));
    if ($jabatanText !== '') {
        if (strpos($jabatanText, 'kepala sub') === 0) {
            return 'Eselon IV';
        }
        if (strpos($jabatanText, 'kepala bidang') === 0) {
            return 'Eselon III';
        }
        if ($jabatanText === 'sekretaris' || strpos($jabatanText, 'sekretaris dinas') === 0 || strpos($jabatanText, 'sekretaris badan') === 0) {
            return 'Eselon III';
        }
        if (in_array($jabatanText, ['inspektur', 'inspektur kabupaten', 'inspektur daerah', 'inspektur kabupaten pringsewu'], true) || strpos($jabatanText, 'kepala dinas') === 0 || strpos($jabatanText, 'kepala bagian') === 0) {
            return 'Eselon II';
        }
    }

    return '-';
};
$ctxEselon = $eselonLabel($ctx['pk_jenis'] ?? '', $ctx['pejabat_eselon'] ?? null, $ctx['pejabat_jabatan'] ?? '');
$judul    = ($isEdit ? 'Edit' : 'Tambah') . ' Rencana Aksi';
$renaksiPath = ($jenis === 'bupati') ? 'adminkab/target_renaksi'
             : (($base === 'adminopd') ? 'adminopd/target_renaksi' : ($base . '/renaksi_pk/' . $jenis));
$baseUrl  = base_url($renaksiPath);
$action   = $isEdit
    ? $baseUrl . '/update/' . (int) ($detail['id'] ?? 0)
    : $baseUrl . '/save';

// Nilai prefill (edit pakai $detail, tambah pakai old())
// old() meng-esc() nilainya secara bawaan. Di sini SEMUA pemakainya sudah
// meng-esc() sendiri (value="…"), membandingkan mentah ($pjVal === nama
// OPD), atau memasukkannya ke JSON untuk skrip — jadi escape bawaan itu
// justru merusak: "&" jadi "&amp;amp;" di kotak isian, dan nama OPD ber-"&"
// tidak pernah terpilih lagi setelah galat validasi. Nilai mentah dikembalikan.
$val = function (string $k) use ($isEdit, $detail, $ctx) {
    if ($isEdit) {
        $default = $detail[$k] ?? '';
        if ($k === 'penanggung_jawab' && $default === '') {
            $default = $ctx['pejabat_jabatan'] ?? '';
        }
        return old($k, $default, false);
    }

    $default = ($k === 'penanggung_jawab') ? ($ctx['pejabat_jabatan'] ?? '') : '';
    return old($k, $default, false);
};
$tahun  = $ctx['tahun'] ?? ($ctx['indikator_tahun'] ?? '-');

// format_helper tidak ikut autoload (lihat Config\Autoload::$helpers), jadi
// formatRupiah() dipanggil lewat pembungkus yang punya cadangan sendiri.
$rupiah = function ($nilai) {
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};

// Label unit anggaran (Program / Kegiatan / Sub Kegiatan) mengikuti jenis PK
// MENTAH, bukan label eselon. Controller sudah mengirim $labelUnitHeader;
// sisanya sekadar cadangan supaya view tidak pecah kalau belum dikirim.
$labelUnit = $labelUnitHeader
    ?? (function_exists('pk_unit_label')
        ? pk_unit_label($detail['pk_jenis'] ?? ($ctx['pk_jenis'] ?? null))
        : 'Program');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($judul) ?> - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <style>
        /* Kotak sub rencana aksi tumbuh KE BAWAH mengikuti isinya.
           `resize:none` karena tingginya sudah diatur tumbuhkan(); membiarkan
           pegangan resize hanya membuat tinggi manual bertabrakan dengan tinggi
           otomatis. `overflow-y:hidden` menyembunyikan bilah gulung yang tidak
           akan pernah terpakai. */
        .sub-item .sub-input {
            resize: none;
            overflow-y: hidden;
            line-height: 1.5;
        }

        /* Nomor, satuan, dan tombol hapus dibiarkan MEREGANG mengikuti tinggi
           kotak — itu perilaku bawaan input-group Bootstrap. Sempat saya coba
           menahannya di baris pertama, tetapi hasilnya justru rusak: sudut
           kanan bawah grup menganga dan sisanya tampak menggantung. Meregang
           membuat satu baris sub tetap terbaca sebagai satu kesatuan kotak. */

        /* Sub yang DIKEMBALIKAN ke form setelah penghapusannya ditolak
           (capaian MONEV-nya sudah tersimpan). Ditandai jelas: inilah baris
           yang tadi "hilang" dari layar padahal masih ada di basis data. */
        .sub-item.sub-ditolak .input-group > .form-control,
        .sub-item.sub-ditolak .input-group > .form-select,
        .sub-item.sub-ditolak .input-group > .input-group-text {
            border-color: #dc3545;
        }

        .sub-item.sub-ditolak .sub-ditolak-ket {
            color: #b02a37;
            font-size: .8125rem;
            margin: .125rem 0 .25rem 2.25rem;
        }

        /* Sub yang capaian MONEV-nya tersimpan: tombol hapusnya tetap ada
           (menekan memunculkan penjelasan), tetapi tampil lebih redup supaya
           terbaca "tidak semudah itu". */
        .sub-item.sub-bermonev .remove-sub {
            opacity: .55;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
    <?= $this->include($isBupati ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php'); ?>
    <?= $this->include($isBupati ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;"><?= esc($judul) ?></h2>

            <?php if (session()->getFlashdata('error')): ?>
                <?php // Pesannya teks polos dan ikut membawa nama sub ketikan pemakai — di-esc(). ?>
                <div class="alert alert-danger mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <?php if (!empty($subDitolak)): ?>
                <?php /* Sub yang penghapusannya DITOLAK server. Baris-barisnya
                         dipasang kembali ke form oleh skrip di bawah (ditandai
                         merah); panel ini menjelaskan mengapa dan membuka
                         jalan pintas ke layar MONEV tempat capaiannya bisa
                         dikosongkan. Semua teks di sini diketik pemakai, jadi
                         di-esc(). */ ?>
                <div class="alert alert-warning mb-3" id="panel-sub-ditolak">
                    <div class="fw-semibold mb-1">
                        <i class="fas fa-rotate-left me-1"></i>
                        <?= count($subDitolak) ?> sub rencana aksi dikembalikan ke form
                    </div>
                    <div class="small mb-2">
                        Sub berikut tidak bisa dibuang karena capaian MONEV-nya sudah tersimpan.
                        Kalau memang hendak dihapus, kosongkan dulu capaiannya lewat tautan
                        <em>Buka MONEV</em>, lalu simpan ulang form ini.
                    </div>
                    <ul class="small mb-0 ps-3">
                        <?php foreach ($subDitolak as $d): ?>
                            <li class="mb-1">
                                &ldquo;<?= esc($d['teks']) ?>&rdquo;
                                <span class="text-muted">(<?= esc($d['ringkas']) ?>)</span>
                                &mdash;
                                <a href="<?= esc($d['monev_url']) ?>" target="_blank" rel="noopener">
                                    Buka MONEV <i class="fas fa-arrow-up-right-from-square fa-xs"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= $action ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <?php if (!$isEdit): ?>
                    <input type="hidden" name="pk_indikator_id" value="<?= (int) ($ctx['pk_indikator_id'] ?? 0) ?>">
                <?php endif; ?>

                <?php if (!$isBupati): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Eselon</label>
                            <input type="text" class="form-control" value="<?= esc($ctxEselon) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Pejabat (PK)</label>
                            <?php
                            $pejabatPk = trim((string) ($ctx['pejabat_nama'] ?? ''));
                            if (!empty($ctx['pejabat_jabatan'])) {
                                $pejabatPk .= ' (' . $ctx['pejabat_jabatan'] . ')';
                            }
                            ?>
                            <input type="text" class="form-control" value="<?= esc($pejabatPk !== '' ? $pejabatPk : '-') ?>" readonly>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Sasaran</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['sasaran_renstra'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label class="form-label">Indikator PK</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['indikator_sasaran'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['satuan'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Tahun PK</label>
                        <input type="text" class="form-control" value="<?= esc($tahun) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Target</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['indikator_target'] ?? '-') ?>" readonly>
                    </div>
                </div>

                <?php // Unit anggaran ikut Perjanjian Kinerja indikator ini — hanya ditampilkan. ?>
                <div class="mb-3">
                    <label class="form-label"><?= esc($labelUnit) ?> &amp; Anggaran <span class="text-muted fw-normal">(dari Perjanjian Kinerja)</span></label>
                    <?php if (empty($programPk ?? [])): ?>
                        <div class="alert alert-light border mb-0 py-2 px-3 text-muted small">
                            Indikator PK ini belum ditautkan ke <?= esc(strtolower($labelUnit)) ?> mana pun. Atur lewat menu Program Perjanjian Kinerja.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-1">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:90px;">Kode</th>
                                        <th><?= esc($labelUnit) ?></th>
                                        <th class="text-end" style="width:170px;">Anggaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $totalAnggaran = 0; ?>
                                    <?php foreach ($programPk as $prog): ?>
                                        <?php $totalAnggaran += (float) $prog['anggaran']; ?>
                                        <tr>
                                            <td><?= esc($prog['kode'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($prog['program']) ?></td>
                                            <td class="text-end text-nowrap"><?= esc($rupiah($prog['anggaran'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if (count($programPk) > 1): ?>
                                    <tfoot>
                                        <tr class="fw-semibold">
                                            <td colspan="2" class="text-end">Total</td>
                                            <td class="text-end text-nowrap"><?= esc($rupiah($totalAnggaran)) ?></td>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <small class="text-muted">Diambil otomatis dari PK — ubah datanya di menu Program Perjanjian Kinerja.</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Aksi &amp; Sub Rencana Aksi</label>
                    <?php if ($isPredikat): ?>
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="fas fa-list-ol me-1"></i>
                            Satuan <strong><?= esc($ctx['satuan'] ?? '-') ?></strong> memakai <strong>skala predikat</strong>,
                            jadi Target Triwulan dipilih dari daftar:
                            <?php foreach ($skala as $s): ?>
                                <span class="badge bg-white text-dark border ms-1"><?= esc($s['kode']) ?> = <?= esc(rtrim(rtrim(number_format((float) $s['nilai'], 2, '.', ''), '0'), '.')) ?></span>
                            <?php endforeach; ?>
                            <div class="text-muted mt-1">Ubah skalanya di <em>Master Data &rarr; Satuan</em>.</div>
                        </div>
                    <?php endif; ?>
                    <div id="renaksi-list"></div>
                    <button type="button" id="add-renaksi" class="btn btn-outline-success btn-sm mt-1">
                        <i class="fas fa-plus me-1"></i> Tambah Rencana Aksi
                    </button>
                    <small class="text-muted d-block mt-1">
                        Tiap rencana aksi bisa dirinci lagi jadi beberapa sub rencana aksi; di tabel akan tampil
                        sebagai daftar 1, 2, 3 … dengan sub-nya masing-masing.
                    </small>
                    <textarea name="rencana_aksi" id="rencana_aksi_joined" class="d-none" required></textarea>
                    <input type="hidden" name="sub_rencana_json" id="sub_rencana_json" value="">
                </div>

                <?php // Target triwulan tingkat indikator DIHAPUS dari form: targetnya kini
                      // diisi per Sub Rencana Aksi di atas, dan MONEV membacanya dari sana. ?>

                <div class="mb-3">
                    <label class="form-label" for="penanggung_jawab">
                        Penanggung Jawab <?= $isBupati ? '(Perangkat Daerah)' : '' ?>
                    </label>
                    <?php if ($isBupati): ?>
                        <?php $pjVal = (string) $val('penanggung_jawab'); ?>
                        <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" required>
                            <option value="">&mdash; Pilih Perangkat Daerah &mdash;</option>
                            <?php foreach (($opdList ?? []) as $opd): ?>
                                <option value="<?= esc($opd['nama_opd']) ?>" <?= ($pjVal === $opd['nama_opd']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php // fallback: data lama (teks jabatan) yang belum cocok OPD, tetap ditampilkan agar tak hilang ?>
                            <?php if ($pjVal !== '' && !in_array($pjVal, array_column($opdList ?? [], 'nama_opd'), true)): ?>
                                <option value="<?= esc($pjVal) ?>" selected><?= esc($pjVal) ?> (data lama)</option>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Pilih <strong>Perangkat Daerah</strong> penanggung jawab rencana aksi ini.</small>
                    <?php else: ?>
                        <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab"
                            value="<?= esc($val('penanggung_jawab')) ?>" placeholder="Isi nama jabatan (mis. Kepala Bidang ...)">
                        <small class="text-muted">Diisi dengan <strong>nama jabatan</strong> penanggung jawab.</small>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        (function () {
            var initial = <?= json_encode((string) $val('rencana_aksi')) ?>;
            // Skala predikat satuan indikator ini; kosong = target diketik bebas.
            var SKALA = <?= json_encode(array_map(static fn ($s) => [
                'kode'  => $s['kode'],
                'label' => $s['label'] ?? '',
            ], $skala), JSON_UNESCAPED_UNICODE) ?>;
            // Sub rencana aksi tersimpan: { "<indeks butir>": [ {id, teks}, ... ] }
            <?php /* Nama satuan saja: yang disimpan pada sub rencana aksi adalah
                     TEKS-nya, bukan id — supaya rencana lama tidak ikut berubah
                     bunyinya bila master satuan kelak diubah namanya. */ ?>
            var SATUAN = <?= json_encode(
                array_values(array_filter(array_map(
                    static fn ($s) => (string) ($s['satuan'] ?? ''),
                    $satuanOptions ?? []
                ), static fn ($s) => $s !== '')),
                JSON_UNESCAPED_UNICODE
            ) ?>;
            var initialSub = <?= json_encode($subRencana ?? [], JSON_UNESCAPED_UNICODE) ?>;
            // old() TANPA escape: nilainya JSON yang akan di-JSON.parse. Dengan
            // escape bawaan, tanda kutipnya jadi &quot; literal di dalam <script>
            // (entitas HTML tidak didekode di sana), parse selalu gagal, dan form
            // diam-diam kembali ke data tersimpan — suntingan pemakai hilang
            // setiap kali ada galat. json_encode() sudah mengamankannya untuk
            // skrip ("</" menjadi "<\/").
            var oldSub = <?= json_encode((string) (old('sub_rencana_json', '', false) ?? '')) ?>;
            if (oldSub) {
                try { initialSub = JSON.parse(oldSub); } catch (err) { /* pakai data tersimpan */ }
            }

            // Capaian MONEV yang sudah tersimpan, per id sub: { "191": {"1":"0","2":"25"} }.
            // Hanya triwulan terisi yang ada. Definisinya SAMA dengan penjaga
            // server (MonevModel::capaianTerisiPerSub) — form tidak pernah
            // membolehkan apa yang server akan tolak.
            var MONEV = <?= json_encode((object) ($subMonev ?? []), JSON_UNESCAPED_UNICODE) ?>;
            var MONEV_URL = <?= json_encode((string) ($monevInputUrl ?? '')) ?>;

            // Sub yang baru saja ditolak penghapusannya oleh server. Dipasang
            // kembali ke barisnya setelah form dibangun dari old().
            var SUB_DITOLAK = <?= json_encode(array_values($subDitolak ?? []), JSON_UNESCAPED_UNICODE) ?>;

            var list = document.getElementById('renaksi-list');
            var joined = document.getElementById('rencana_aksi_joined');
            var subJson = document.getElementById('sub_rencana_json');
            if (!list || !joined || !subJson) return;

            function esc(s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

            /**
             * Samakan tinggi sebuah textarea sub dengan tinggi isinya.
             *
             * Tingginya dinolkan dulu; tanpa itu `scrollHeight` hanya pernah
             * bertambah dan kotak tidak pernah menyusut kembali saat teksnya
             * dihapus.
             */
            function tumbuhkan(el) {
                if (!el) return;
                el.style.height = 'auto';
                el.style.height = el.scrollHeight + 'px';
            }

            /** Semua textarea sub di dalam sebuah wadah (atau seluruh daftar). */
            function tumbuhkanSemua(wadah) {
                Array.prototype.forEach.call(
                    (wadah || list).querySelectorAll('.sub-input'),
                    tumbuhkan
                );
            }

            /** Satu baris sub: teksnya + target triwulan I-IV milik sub itu sendiri. */
            function subRowHtml(sub) {
                sub = sub || {};
                var teks = sub.teks || '';
                var tw = sub.tw || [];
                var id = sub.id ? String(sub.id) : '';
                var twHtml = [0, 1, 2, 3].map(function (i) {
                    var label = ['I', 'II', 'III', 'IV'][i];
                    var nilai = tw[i] || '';

                    // Satuan berpredikat -> dropdown skala. Nilai lama yang tidak
                    // ada di skala tetap ditawarkan agar tidak terhapus diam-diam.
                    if (SKALA.length) {
                        var dikenal = SKALA.some(function (s) {
                            return String(s.kode).toLowerCase() === String(nilai).trim().toLowerCase();
                        });
                        var opts = '<option value="">TW ' + label + '</option>'
                            + SKALA.map(function (s) {
                                var pilih = String(s.kode).toLowerCase() === String(nilai).trim().toLowerCase() ? ' selected' : '';
                                var teks = s.label ? (s.kode + ' — ' + s.label) : s.kode;
                                return '<option value="' + esc(s.kode) + '"' + pilih + '>' + esc(teks) + '</option>';
                            }).join('')
                            + (nilai !== '' && !dikenal
                                ? '<option value="' + esc(nilai) + '" selected>' + esc(nilai) + ' (di luar skala)</option>'
                                : '');
                        return '<div class="col">'
                            + '<select class="form-select form-select-sm sub-tw" data-q="' + i + '"'
                            + ' title="Target Triwulan ' + label + '">' + opts + '</select>'
                            + '</div>';
                    }

                    return '<div class="col">'
                        + '<input type="text" class="form-control form-control-sm sub-tw" data-q="' + i + '"'
                        + ' placeholder="TW ' + label + '" title="Target Triwulan ' + label + '"'
                        + ' value="' + esc(nilai) + '">'
                        + '</div>';
                }).join('');

                // Satuan target triwulan sub ini. Diletakkan TEPAT SESUDAH
                // kolom sub rencana aksi, sama seperti urutannya di tabel.
                //
                // Nilai lama yang tidak ada di master tetap ditawarkan sebagai
                // pilihan terpilih — master satuan bisa berubah, dan rencana
                // yang sudah tersimpan tidak boleh kehilangan satuannya
                // diam-diam hanya karena dibuka kembali.
                var satuan = sub.satuan || '';
                var dikenalSat = SATUAN.some(function (s) {
                    return String(s).toLowerCase() === String(satuan).trim().toLowerCase();
                });
                var satOpts = '<option value="">— satuan —</option>'
                    + SATUAN.map(function (s) {
                        var pilih = String(s).toLowerCase() === String(satuan).trim().toLowerCase() ? ' selected' : '';
                        return '<option value="' + esc(s) + '"' + pilih + '>' + esc(s) + '</option>';
                    }).join('')
                    + (satuan !== '' && !dikenalSat
                        ? '<option value="' + esc(satuan) + '" selected>' + esc(satuan) + ' (di luar master)</option>'
                        : '');

                var kelasTambahan = (id && MONEV[id]) ? ' sub-bermonev' : '';

                return '<div class="sub-item mb-2' + kelasTambahan + '" data-id="' + esc(id) + '">'
                    + '<div class="input-group input-group-sm mb-1">'
                    + '<span class="input-group-text sub-no bg-white text-muted"></span>'
                    // Textarea, bukan input satu baris. Sub rencana aksi sering
                    // berupa kalimat penuh; pada <input> teksnya menggulung ke
                    // samping sehingga yang terlihat hanya potongan terakhir.
                    // Tingginya diatur tumbuhkan() mengikuti isi, jadi seluruh
                    // kalimat terbaca tanpa perlu menggulung.
                    //
                    // rows="1" supaya baris pendek tetap setinggi kotak biasa.
                    + '<textarea class="form-control sub-input" rows="1"'
                    + ' placeholder="Tulis sub rencana aksi">' + esc(teks) + '</textarea>'
                    // Lebarnya proporsional, bukan dipatok sempit: nama satuan bisa
                    // panjang ("Dokumen", "Orang/Kegiatan", "Persentase"), dan kotak
                    // 150px memotongnya jadi tidak terbaca. flex-basis memberi porsi
                    // tetap sambil tetap boleh menyusut di layar sempit.
                    + '<select class="form-select sub-satuan flex-grow-0 flex-shrink-1"'
                    + ' style="flex-basis:220px;min-width:130px"'
                    + ' title="Satuan target triwulan sub ini">' + satOpts + '</select>'
                    + '<button type="button" class="btn btn-outline-danger remove-sub" title="'
                    + (kelasTambahan ? 'Capaian MONEV sudah tersimpan &mdash; lihat keterangan' : 'Hapus sub')
                    + '"><i class="fas fa-times"></i></button>'
                    + '</div>'
                    + '<div class="row g-1 ps-4">' + twHtml + '</div>'
                    + '</div>';
            }

            /** "TW I: 0, TW II: 25" dari peta capaian satu sub. */
            function ringkasCapaian(peta) {
                var rom = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV' };
                var bagian = [];
                [1, 2, 3, 4].forEach(function (q) {
                    if (peta && peta[q] !== undefined && peta[q] !== null && String(peta[q]) !== '') {
                        bagian.push('TW ' + rom[q] + ': ' + peta[q]);
                    }
                });
                return bagian.join(', ');
            }

            /** Rincian untuk dialog: satu baris per triwulan terisi. */
            function rincianCapaian(peta) {
                var rom = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV' };
                var baris = [];
                [1, 2, 3, 4].forEach(function (q) {
                    if (peta && peta[q] !== undefined && peta[q] !== null && String(peta[q]) !== '') {
                        baris.push('Triwulan ' + rom[q] + ': ' + peta[q]);
                    }
                });
                return baris;
            }

            /**
             * Dialog konfirmasi milik aplikasi (templates/konfirmasi.php).
             * Kalau karena suatu hal belum termuat, jatuh ke confirm() bawaan
             * — tetap bertanya, hanya tanpa rinciannya.
             */
            function tanya(o) {
                if (window.Konfirmasi && typeof window.Konfirmasi.tanya === 'function') {
                    return window.Konfirmasi.tanya(o);
                }
                var teks = (o.judul ? o.judul + '\n\n' : '') + (o.pesan || '')
                    + (o.nama ? '\n\n' + o.nama : '')
                    + (o.rincian && o.rincian.length ? '\n- ' + o.rincian.join('\n- ') : '');
                return Promise.resolve(window.confirm(teks));
            }

            /**
             * Sub yang capaian MONEV-nya tersimpan TIDAK dihapus dari form —
             * server toh akan menolaknya, dan menghilangkannya dari layar hanya
             * menjebak pemakai (lihat SUB_DITOLAK). Yang ditawarkan: buka MONEV.
             */
            function jelaskanBermonev(teks, id) {
                var url = MONEV_URL ? MONEV_URL + '?sub=' + encodeURIComponent(id) : '';
                return tanya({
                    jenis: 'peringatan',
                    judul: 'Capaian MONEV sudah tersimpan',
                    pesan: 'Sub rencana aksi ini tidak bisa dihapus selama capaian MONEV-nya masih terisi. '
                        + 'Kosongkan dulu capaiannya di menu MONEV, lalu simpan ulang form ini.',
                    nama: teks,
                    rincian: rincianCapaian(MONEV[id]),
                    rincianJudul: 'Capaian yang tersimpan',
                    ya: url ? 'Buka MONEV' : 'Mengerti',
                    tidak: 'Tutup',
                    permanen: false
                }).then(function (ya) {
                    if (ya && url) { window.open(url, '_blank', 'noopener'); }
                    return false;
                });
            }

            function rowHtml(val, subs) {
                var subHtml = (subs && subs.length ? subs : []).map(subRowHtml).join('');
                return '<div class="renaksi-item border rounded p-2 mb-2 bg-light">'
                    + '<div class="input-group mb-2">'
                    + '<span class="input-group-text renaksi-no bg-white fw-semibold"></span>'
                    + '<input type="text" class="form-control renaksi-input" placeholder="Tulis rencana aksi" value="' + esc(val) + '">'
                    + '<button type="button" class="btn btn-outline-danger remove-renaksi" title="Hapus rencana aksi"><i class="fas fa-trash"></i></button>'
                    + '</div>'
                    + '<div class="ps-3 border-start">'
                    + '<div class="small text-muted mb-1">Sub Rencana Aksi</div>'
                    + '<div class="sub-list">' + subHtml + '</div>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm add-sub"><i class="fas fa-plus me-1"></i>Tambah Sub</button>'
                    + '</div>'
                    + '</div>';
            }

            /** Nomori ulang label 1,2,3 supaya cocok dengan tampilan tabel. */
            function renumber() {
                var n = 0;
                Array.prototype.forEach.call(list.querySelectorAll('.renaksi-item'), function (item) {
                    var label = item.querySelector('.renaksi-no');
                    if (label) label.textContent = (++n);
                    var s = 0;
                    Array.prototype.forEach.call(item.querySelectorAll('.sub-no'), function (el) {
                        el.textContent = (++s);
                    });
                });
            }

            /**
             * Butir kosong tidak ikut disimpan, jadi indeks sub HARUS dihitung dari
             * urutan butir yang TIDAK kosong — sama dengan cara tabel memecah baris.
             */
            function sync() {
                var vals = [];
                var map = {};
                var idx = 0;

                Array.prototype.forEach.call(list.querySelectorAll('.renaksi-item'), function (item) {
                    var inp = item.querySelector('.renaksi-input');
                    var v = inp ? inp.value.trim() : '';
                    if (v === '') return;

                    vals.push(v);

                    var subs = [];
                    Array.prototype.forEach.call(item.querySelectorAll('.sub-item'), function (si) {
                        var teksEl = si.querySelector('.sub-input');
                        var teks = teksEl ? teksEl.value.trim() : '';
                        if (teks === '') return;

                        var tw = ['', '', '', ''];
                        Array.prototype.forEach.call(si.querySelectorAll('.sub-tw'), function (t) {
                            var q = parseInt(t.getAttribute('data-q'), 10);
                            if (!isNaN(q) && q >= 0 && q < 4) tw[q] = t.value.trim();
                        });

                        // id ikut dikirim supaya sub yang sudah ada di DB diperbarui
                        // di tempat — capaian MONEV menempel ke id ini.
                        var id = parseInt(si.getAttribute('data-id'), 10);

                        var satEl = si.querySelector('.sub-satuan');
                        var sat = satEl ? satEl.value.trim() : '';

                        subs.push({ id: isNaN(id) ? 0 : id, teks: teks, satuan: sat, tw: tw });
                    });

                    if (subs.length) map[idx] = subs;
                    idx++;
                });

                joined.value = vals.join('\n');
                subJson.value = JSON.stringify(map);
            }

            function addRow(val, subs) {
                list.insertAdjacentHTML('beforeend', rowHtml(val, subs));
                // Hanya butir yang baru saja disisipkan yang perlu diukur.
                tumbuhkanSemua(list.lastElementChild);
                renumber();
                sync();
            }

            // Inisialisasi baris dari nilai tersimpan (edit) atau 1 baris kosong (tambah)
            var lines = String(initial || '').split(/\r\n|\r|\n/).map(function (s) { return s.trim(); }).filter(function (s) { return s !== ''; });
            if (lines.length === 0) lines = [''];

            lines.forEach(function (line, i) {
                var raw = initialSub ? initialSub[i] : null;
                var subs = [];
                if (Array.isArray(raw)) {
                    subs = raw.map(function (s) {
                        if (s && typeof s === 'object') {
                            // Dari DB tw berindeks 1..4 (jadi objek saat di-JSON-kan);
                            // dari old() tw berupa array berindeks 0..3.
                            var t = s.tw || {};
                            var tw = Array.isArray(t)
                                ? [t[0] || '', t[1] || '', t[2] || '', t[3] || '']
                                : [t[1] || '', t[2] || '', t[3] || '', t[4] || ''];
                            // `satuan` WAJIB ikut dibawa. Tanpa baris ini,
                            // dropdown satuan terbuka kosong saat menyunting,
                            // dan satuan yang sudah tersimpan terhapus begitu
                            // form disimpan ulang — hilang tanpa gejala.
                            return { id: s.id || 0, teks: s.teks || '', satuan: s.satuan || '', tw: tw };
                        }
                        return { id: 0, teks: String(s || ''), satuan: '', tw: ['', '', '', ''] };
                    }).filter(function (s) { return s.teks !== ''; });
                }
                addRow(line, subs);
            });

            /**
             * PASANG KEMBALI SUB YANG DITOLAK SERVER.
             *
             * Form di atas dibangun dari old() — kiriman yang ditolak — dan di
             * kiriman itu sub-nya memang sudah tidak ada. Tanpa langkah ini
             * pemakai melihat form "sudah bersih", menekan Simpan, dan ditolak
             * lagi dengan pesan yang sama.
             *
             * Untuk tiap sub: cari butir induknya (indeks baris_rencana, dicek
             * teksnya; kalau tidak cocok, cari butir bertekst sama di mana pun;
             * kalau butirnya ikut dihapus, butirnya dibuat lagi). Lalu, kalau di
             * butir itu ada sub BARU (id 0) berteks sama — pemakai menghapus lalu
             * mengetik ulang — sub baru itu yang "diadopsi" memakai id lama,
             * supaya tidak jadi dua. Selain itu, barisnya disisipkan kembali.
             */
            function pulihkanSubDitolak() {
                if (!SUB_DITOLAK.length) return;

                SUB_DITOLAK.forEach(function (d) {
                    var id = String(d.id);
                    if (d.capaian) MONEV[id] = d.capaian;

                    var items = list.querySelectorAll('.renaksi-item');
                    var butirTeks = String(d.butir || '').trim();
                    var item = null;

                    var kandidat = items[d.baris_rencana];
                    if (kandidat) {
                        var inp = kandidat.querySelector('.renaksi-input');
                        if (inp && (butirTeks === '' || inp.value.trim() === butirTeks)) item = kandidat;
                    }
                    if (!item && butirTeks !== '') {
                        Array.prototype.some.call(items, function (it) {
                            var i2 = it.querySelector('.renaksi-input');
                            if (i2 && i2.value.trim() === butirTeks) { item = it; return true; }
                            return false;
                        });
                    }
                    if (!item) {
                        addRow(butirTeks, []);
                        item = list.lastElementChild;
                    }

                    var wrap = item.querySelector('.sub-list');
                    if (!wrap) return;

                    // Sudah ada dengan id yang sama DI MANA PUN di form (mis. tombol
                    // Kembali peramban, atau form terbangun dari data tersimpan)?
                    // Cukup ditandai di tempatnya — jangan sampai jadi dua.
                    var sudah = list.querySelector('.sub-item[data-id="' + id + '"]');

                    if (!sudah) {
                        var teksSub = String(d.teks || '').trim();
                        Array.prototype.some.call(wrap.querySelectorAll('.sub-item'), function (si) {
                            var sid = si.getAttribute('data-id');
                            var ta  = si.querySelector('.sub-input');
                            if ((!sid || sid === '0') && ta && ta.value.trim() === teksSub) {
                                si.setAttribute('data-id', id);
                                sudah = si;
                                return true;
                            }
                            return false;
                        });
                    }

                    if (!sudah) {
                        var t = d.tw || {};
                        wrap.insertAdjacentHTML('beforeend', subRowHtml({
                            id: d.id,
                            teks: d.teks || '',
                            satuan: d.satuan || '',
                            tw: [t[1] || '', t[2] || '', t[3] || '', t[4] || '']
                        }));
                        sudah = wrap.lastElementChild;
                        tumbuhkanSemua(sudah);
                    }

                    sudah.classList.add('sub-ditolak', 'sub-bermonev');

                    if (!sudah.querySelector('.sub-ditolak-ket')) {
                        var ket = document.createElement('div');
                        ket.className = 'sub-ditolak-ket';
                        ket.textContent = 'Dikembalikan: capaian MONEV sudah tersimpan (' + ringkasCapaian(d.capaian) + '). ';
                        if (d.monev_url) {
                            var a = document.createElement('a');
                            a.href = d.monev_url;
                            a.target = '_blank';
                            a.rel = 'noopener';
                            a.textContent = 'Buka MONEV';
                            ket.appendChild(a);
                        }
                        var grup = sudah.querySelector('.input-group');
                        if (grup && grup.nextSibling) sudah.insertBefore(ket, grup.nextSibling);
                        else sudah.appendChild(ket);
                    }
                });

                renumber();
                sync();
            }

            pulihkanSubDitolak();

            document.getElementById('add-renaksi').addEventListener('click', function () { addRow('', []); });

            list.addEventListener('click', function (e) {
                if (e.target.closest('.add-sub')) {
                    var wrap = e.target.closest('.renaksi-item').querySelector('.sub-list');
                    if (wrap) {
                        wrap.insertAdjacentHTML('beforeend', subRowHtml({ id: 0, teks: '', satuan: '', tw: ['', '', '', ''] }));
                        tumbuhkanSemua(wrap.lastElementChild);
                    }
                    renumber();
                    sync();
                    return;
                }

                if (e.target.closest('.remove-sub')) {
                    var subEl  = e.target.closest('.sub-item');
                    var subId  = subEl.getAttribute('data-id') || '';
                    var subTa  = subEl.querySelector('.sub-input');
                    var subTxt = subTa ? subTa.value.trim() : '';

                    // Capaian MONEV-nya tersimpan: jelaskan, jangan dihapus.
                    if (subId && MONEV[subId]) {
                        jelaskanBermonev(subTxt, subId);
                        return;
                    }

                    // Sub yang masih kosong tidak perlu ditanya — tidak ada yang hilang.
                    if (subTxt === '') {
                        subEl.remove();
                        renumber();
                        sync();
                        return;
                    }

                    tanya({
                        jenis: 'hapus',
                        judul: 'Hapus Sub Rencana Aksi',
                        pesan: 'Sub ini dibuang dari daftar dan ikut terhapus saat form disimpan.',
                        nama: subTxt,
                        permanen: false
                    }).then(function (ya) {
                        if (!ya) return;
                        subEl.remove();
                        renumber();
                        sync();
                    });
                    return;
                }

                if (e.target.closest('.remove-renaksi')) {
                    var items  = list.querySelectorAll('.renaksi-item');
                    var item   = e.target.closest('.renaksi-item');
                    var inp    = item.querySelector('.renaksi-input');
                    var butir  = inp ? inp.value.trim() : '';
                    var subEls = item.querySelectorAll('.sub-item');

                    // Ada sub di butir ini yang capaian MONEV-nya tersimpan?
                    // Butirnya tidak bisa dibuang utuh — sub itu akan ditolak server.
                    var bermonev = [];
                    Array.prototype.forEach.call(subEls, function (si) {
                        var sid = si.getAttribute('data-id') || '';
                        var ta  = si.querySelector('.sub-input');
                        if (sid && MONEV[sid]) {
                            bermonev.push((ta ? ta.value.trim() : '') + ' (' + ringkasCapaian(MONEV[sid]) + ')');
                        }
                    });

                    if (bermonev.length) {
                        tanya({
                            jenis: 'peringatan',
                            judul: 'Butir ini punya capaian MONEV',
                            pesan: 'Rencana aksi ini tidak bisa dihapus utuh: ' + bermonev.length
                                + ' sub di dalamnya sudah punya capaian MONEV tersimpan. '
                                + 'Kosongkan dulu capaiannya di menu MONEV, atau hapus sub lainnya saja.',
                            nama: butir,
                            rincian: bermonev,
                            rincianJudul: 'Sub yang menahan',
                            ya: 'Mengerti',
                            tidak: 'Tutup',
                            permanen: false
                        });
                        return;
                    }

                    function buangButir() {
                        if (items.length > 1) {
                            item.remove();
                        } else {
                            // sisa satu butir: kosongkan saja, jangan sampai form tanpa baris
                            if (inp) inp.value = '';
                            var sl = item.querySelector('.sub-list');
                            if (sl) sl.innerHTML = '';
                        }
                        renumber();
                        sync();
                    }

                    // Butir yang masih kosong tanpa sub berisi: langsung saja.
                    var subBerisi = 0;
                    Array.prototype.forEach.call(subEls, function (si) {
                        var ta = si.querySelector('.sub-input');
                        if (ta && ta.value.trim() !== '') subBerisi++;
                    });
                    if (butir === '' && subBerisi === 0) {
                        buangButir();
                        return;
                    }

                    tanya({
                        jenis: 'hapus',
                        judul: 'Hapus Rencana Aksi',
                        pesan: 'Butir ini beserta seluruh sub-nya dibuang dari daftar dan ikut terhapus saat form disimpan.',
                        nama: butir,
                        rincian: subBerisi ? [subBerisi + ' sub rencana aksi'] : [],
                        permanen: false
                    }).then(function (ya) {
                        if (ya) buangButir();
                    });
                }
            });

            list.addEventListener('input', function (e) {
                var c = e.target.classList;
                if (c.contains('sub-input')) tumbuhkan(e.target);
                if (c.contains('renaksi-input') || c.contains('sub-input') || c.contains('sub-tw')) sync();
            });

            // Enter TIDAK menyisipkan baris baru. Sub rencana aksi disimpan
            // sebagai satu nilai tunggal, dan `joined` memakai baris baru
            // sebagai pemisah antar rencana aksi — membiarkan Enter berarti
            // memasukkan pemisah ke dalam isi. Textarea di sini dipakai murni
            // supaya kalimat panjang terlihat utuh, bukan untuk teks berbaris.
            list.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && e.target.classList.contains('sub-input')) {
                    e.preventDefault();
                }
            });

            // Lebar kotak berubah saat jendela diubah ukurannya atau sidebar
            // dibuka/ditutup; pembungkusan barisnya ikut berubah, jadi tingginya
            // harus diukur ulang.
            window.addEventListener('resize', function () { tumbuhkanSemua(); });

            // <select> memicu 'change', bukan 'input'. Peramban modern memang
            // ikut memicu 'input', tetapi menyandarkan penyimpanan pada
            // perilaku yang tidak dijamin itu berarti satuan (dan target
            // triwulan pada satuan berpredikat, yang juga <select>) bisa tidak
            // ikut terkirim tanpa gejala apa pun.
            list.addEventListener('change', function (e) {
                var c = e.target.classList;
                if (c.contains('sub-satuan') || c.contains('sub-tw')) sync();
            });

            // Jaring terakhir: apa pun yang terlewat di atas tetap tersinkron
            // tepat sebelum form dikirim.
            var form = list.closest('form');
            if (form) form.addEventListener('submit', sync);
        })();
    </script>
</body>

</html>

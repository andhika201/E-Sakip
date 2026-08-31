<?php
helper('pk_unit');  // pk_unit_header() & pk_bagi_baris() untuk kolom unit anggaran

$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;                                   // modul PK OPD/Kecamatan
$isKab    = (($role ?? '') === 'admin_kab');              // pakai chrome adminKabupaten
$judul    = ($isBupati || !$isKab) ? 'Target dan Rencana Aksi' : 'Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');    // kolom & filter OPD untuk admin_kab
$showPejabat = $isOpd;                                    // kolom Pejabat/Eselon
// Path URL bersih. Prefix area SELALU dari $base (controller) supaya role
// read-only bupati tetap di /bupati dan tidak pernah dilempar ke /adminkab:
//   jenis bupati    -> <base>/target_renaksi & <base>/monev
//   es3 admin_opd   -> adminopd/target_renaksi & adminopd/monev
//   es3 lintas OPD  -> <base>/renaksi_pk/es3 & <base>/monev_pk/es3
$renaksiPath = ($jenis === 'bupati') ? ($base . '/target_renaksi')
    : (($base === 'adminopd') ? 'adminopd/target_renaksi' : ($base . '/renaksi_pk/' . $jenis));
$monevPath   = ($jenis === 'bupati') ? ($base . '/monev')
    : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl  = base_url($renaksiPath);

$pkFilterOptions = [];
if ($isOpd) {
    $roleName = (string) ($role ?? '');
    if ($roleName !== 'admin_kecamatan') {
        $pkFilterOptions['jpt'] = 'Eselon II';
    }
    $pkFilterOptions['administrator'] = 'Eselon III';
    $pkFilterOptions['pengawas'] = 'Eselon IV';
}

// Label eselon dari pk.jenis
$eselonLabel = function ($pkJenis, $jabatanEselon = null, $jabatanNama = null) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Eselon III', 'kecamatan' => 'Eselon III', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
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

// ---------------------------------------------------------------------------
// PETA KOLOM TABEL
// Lebar ditetapkan eksplisit (px) lalu dipasang lewat <colgroup> +
// table-layout:fixed. Tanpa ini browser membagi sendiri lebar 16-18 kolom
// sehingga kolom teks panjang (nama program) tergencet jadi satu kata per
// baris. Peta ini juga jadi sumber hitungan:
//   - min-width tabel        (jumlah seluruh lebar)
//   - offset kolom beku kiri (posisi sticky)
//   - colspan baris kosong   (count)
// non-bupati: No, [OPD], [Pejabat], Sasaran, Indikator, Tahun, Satuan, Target,
//             Unit (Program/Kegiatan/Sub Kegiatan), Anggaran, Rencana Aksi,
//             Sub Rencana Aksi, Satuan (milik SUB), 4x Triwulan,
//             Penanggung Jawab, Aksi
//
// Kolom 'satuan' dan 'subsat' sengaja dua-duanya ada dan TIDAK boleh
// disatukan: yang pertama satuan INDIKATOR, yang kedua satuan target
// triwulan tiap SUB rencana aksi. Keduanya kerap berbeda.
// ---------------------------------------------------------------------------
if ($isBupati) {
    // Tabel PK Bupati hanya 8 kolom -> lebarnya sengaja dijaga ~1.180px
    // supaya MUAT di layar 1366 tanpa geser mendatar sama sekali (kalau
    // tabel cuma sedikit lebih lebar dari layar, kolom Aksi yang membeku
    // justru menutupi ujung kolom Perangkat Daerah).
    $kolom = [
        ['key' => 'no',        'w' => 50],
        ['key' => 'sasaran',   'w' => 235],
        ['key' => 'indikator', 'w' => 235],
        ['key' => 'tahun',     'w' => 65],
        ['key' => 'satuan',    'w' => 82],
        ['key' => 'target',    'w' => 78],
        ['key' => 'pd',        'w' => 355],
        ['key' => 'aksi',      'w' => 80],
    ];
} else {
    $kolom = array_merge(
        [['key' => 'no', 'w' => 52]],
        $showOpd     ? [['key' => 'opd', 'w' => 175]] : [],
        $showPejabat ? [['key' => 'pejabat', 'w' => 195]] : [],
        [
            ['key' => 'sasaran',   'w' => 220],
            ['key' => 'indikator', 'w' => 220],
            ['key' => 'tahun',     'w' => 68],
            ['key' => 'satuan',    'w' => 92],
            ['key' => 'target',    'w' => 80],
            ['key' => 'unit',      'w' => 250],
            ['key' => 'anggaran',  'w' => 140],
            ['key' => 'renaksi',   'w' => 240],
            ['key' => 'sub',       'w' => 230],
            ['key' => 'subsat',    'w' => 90],
            ['key' => 'tw1',       'w' => 60],
            ['key' => 'tw2',       'w' => 60],
            ['key' => 'tw3',       'w' => 60],
            ['key' => 'tw4',       'w' => 60],
            ['key' => 'pj',        'w' => 170],
            ['key' => 'aksi',      'w' => 78],
        ]
    );
}
$kolomKeys     = array_column($kolom, 'key');
$tabelMinWidth = array_sum(array_column($kolom, 'w'));
$cols          = count($kolom); // colspan untuk baris "belum ada data"

// Kolom beku (sticky) kiri: No + SATU kolom identitas berikutnya, supaya baris
// tetap bisa dilacak saat tabel digeser jauh ke kanan. Offsetnya kumulatif.
$bekuKiri = ['no'];
foreach (['opd', 'pejabat', 'sasaran'] as $kandidat) {
    if (in_array($kandidat, $kolomKeys, true)) {
        $bekuKiri[] = $kandidat;
        break;
    }
}
$offsetKiri = [];
$lebarBeku  = 0;
foreach ($kolom as $k) {
    if (!in_array($k['key'], $bekuKiri, true)) {
        break; // hanya kolom beruntun dari tepi kiri yang bisa dibekukan
    }
    $offsetKiri[$k['key']] = $lebarBeku;
    $lebarBeku += $k['w'];
}

// Kolom sekunder yang disembunyikan pada "Mode ringkas" (layar sempit).
$kolomRingkas = array_values(array_intersect(['tahun', 'satuan', 'anggaran', 'pj'], $kolomKeys));

// Query string filter aktif (untuk tautan MONEV)
$filterQs = http_build_query(array_filter([
    'tahun'      => ($tahun !== 'all') ? $tahun : null,
    'eselon'     => $eselon ?? null,
    'pejabat_id' => $pejabatId ?? null,
    'opd_id'     => $opdFilter ?: null,
]));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($judul) ?> - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <?php
    // Kolom sticky = kolom beku kiri + kolom Aksi (kanan).
    $kolomSticky = array_merge(array_keys($offsetKiri), ['aksi']);
    $bekuAkhir   = array_key_last($offsetKiri);
    $ringkasMin  = $tabelMinWidth;
    foreach ($kolom as $k) {
        if (in_array($k['key'], $kolomRingkas, true)) {
            $ringkasMin -= $k['w'];
        }
    }
    $selSticky = implode(",\n        ", array_map(
        fn($k) => "main .renaksi-table tbody tr > td.c-{$k}",
        $kolomSticky
    ));
    ?>
    <style>
        /* ==================================================================
           TABEL TARGET & RENCANA AKSI
           Tabelnya memang lebar (16-18 kolom) — memaksanya muat justru
           membuat teks pecah satu kata per baris. Jadi tabel diberi:
             1. lebar kolom PASTI  (colgroup + table-layout:fixed)
             2. area gulir sendiri (kepala tabel & kolom identitas membeku)
             3. penanda tepi       (bayangan: masih ada kolom di kanan/kiri)
             4. mode ringkas       (sembunyikan kolom sekunder di layar sempit)
           ================================================================== */

        /* Ringkasan angka ikut lebar layar supaya tidak jebol di HP. */
        .rk-stat-num {
            font-size: clamp(1.15rem, 4.5vw, 1.5rem);
            line-height: 1.2;
            margin-bottom: 0;
        }

        .rk-box {
            position: relative;
            background: #fff;
            border: 1px solid #e3e8e4;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(16, 40, 24, .05);
        }

        .rk-scroll {
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            /* Tabel punya jendela gulirnya sendiri supaya kepala tabel bisa
               membeku dan halaman tidak ikut memanjang ratusan baris. */
            max-height: clamp(360px, calc(100vh - 250px), 900px);
            border-radius: 14px;
        }

        .renaksi-table {
            min-width: <?= (int) $tabelMinWidth ?>px;
            table-layout: fixed;
            /* WAJIB separate: dengan border-collapse bawaan, garis sel yang
               sticky ikut hilang saat digulir. */
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }

        /* Lebar tiap kolom (dipasang ke <colgroup>) */
<?php foreach ($kolom as $k): ?>
        .renaksi-table col.c-<?= $k['key'] ?> { width: <?= (int) $k['w'] ?>px; }
<?php endforeach; ?>

        /* Garis sel dibuat sendiri (bukan .table-bordered) supaya tidak dobel
           saat border-spacing 0, dan supaya sel gabungan hanya bergaris di
           ujung rentangnya — itu yang memberi kesan berkelompok. */
        .renaksi-table > :not(caption) > * > * {
            border: 0;
            border-right: 1px solid #e6ece8;
            border-bottom: 1px solid #e6ece8;
            box-shadow: none;
            background-color: #fff;
            overflow-wrap: break-word;
        }

        /* ---- Kepala tabel membeku (2 baris) ---- */
        .renaksi-table thead th {
            position: sticky;
            top: 0;
            z-index: 4;
        }

        .renaksi-table thead tr:first-child th {
            height: 46px;
        }

        /* Baris ke-2 (I-IV) menempel tepat di bawah baris ke-1.
           --rk-h1 diukur ulang oleh JS agar tidak ada celah/tumpang tindih. */
        .renaksi-table thead tr + tr th {
            top: var(--rk-h1, 46px);
            z-index: 3;
        }

        .renaksi-table thead tr:last-child th {
            box-shadow: 0 6px 10px -8px rgba(16, 40, 24, .55);
        }

        /* ---- Kolom identitas membeku di kiri, kolom Aksi di kanan ---- */
<?php foreach ($offsetKiri as $key => $off): ?>
        .renaksi-table th.c-<?= $key ?>,
        .renaksi-table td.c-<?= $key ?> { position: sticky; left: <?= (int) $off ?>px; z-index: 2; }
<?php endforeach; ?>
        .renaksi-table th.c-aksi,
        .renaksi-table td.c-aksi { position: sticky; right: 0; z-index: 2; }

<?php foreach ($kolomSticky as $key): ?>
        .renaksi-table thead th.c-<?= $key ?> { z-index: 6; }
<?php endforeach; ?>

        /* Sel beku HARUS buram — kalau tembus, teks kolom lain lewat di
           belakangnya. !important untuk menimpa aturan "baris seragam" global. */
        <?= $selSticky ?> {
            background-color: #fff !important;
        }

        /* Bayangan pemisah: muncul hanya saat memang ada kolom tersembunyi. */
<?php if ($bekuAkhir !== null): ?>
        .rk-box.is-geser-kiri .renaksi-table th.c-<?= $bekuAkhir ?>,
        .rk-box.is-geser-kiri .renaksi-table td.c-<?= $bekuAkhir ?> {
            box-shadow: 10px 0 12px -9px rgba(16, 40, 24, .45);
        }
<?php endif; ?>

        .rk-box.is-ada-kanan .renaksi-table th.c-aksi,
        .rk-box.is-ada-kanan .renaksi-table td.c-aksi {
            box-shadow: -10px 0 12px -9px rgba(16, 40, 24, .45);
        }

        /* ---- Watak isi sel ---- */
        /* Sel gabungan dibaca dari ATAS — jelas isi mana milik kelompok mana. */
        .renaksi-table td.va-top,
        .renaksi-table td.prog-cell,
        .renaksi-table td.prog-cell-money {
            vertical-align: top;
        }

        .renaksi-table td.prog-cell-money,
        .renaksi-table td.tw-cell {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .renaksi-table td.prog-cell-money {
            text-align: right;
        }

        /* Sub rencana aksi tampil menjorok, menandakan miliknya rencana aksi di kirinya. */
        .renaksi-table td.sub-cell {
            border-left: 2px solid #cfe0d5;
        }

        /* Batas antar kelompok: tebal per OPD, sedang per Sasaran. */
        .renaksi-table tbody tr.rk-grp > td { border-top: 2px solid #c3dccd; }
        .renaksi-table tbody tr.rk-grp-opd > td { border-top: 2px solid #7fae95; }

        /* ---- Mode ringkas: kolom sekunder dilipat ---- */
<?php foreach ($kolomRingkas as $rk): ?>
        .rk-box.ringkas .renaksi-table col.c-<?= $rk ?> { width: 0; }
        .rk-box.ringkas .renaksi-table th.c-<?= $rk ?>,
        .rk-box.ringkas .renaksi-table td.c-<?= $rk ?> { display: none; }
<?php endforeach; ?>
        .rk-box.ringkas .renaksi-table { min-width: <?= (int) $ringkasMin ?>px; }

        /* ---- Tablet ke bawah ---- */
        @media (max-width: 991.98px) {
            .rk-scroll {
                max-height: clamp(320px, calc(100vh - 190px), 900px);
            }
        }

        /* ---- Ponsel ----
           Kolom beku dimatikan: di layar sesempit ini ia memakan lebih dari
           separuh ruang baca. Kepala tabel TETAP membeku ke atas, hanya
           kuncian mendatarnya yang dilepas (left/right: auto) — kalau
           dibiarkan, kepala kolom Aksi akan melayang di atas kolom lain. */
        @media (max-width: 767.98px) {
            .renaksi-table th[class*="c-"],
            .renaksi-table td[class*="c-"] {
                left: auto;
                right: auto;
            }

            .renaksi-table tbody td[class*="c-"] {
                position: static;
                box-shadow: none;
            }
        }

        @media (max-width: 575.98px) {
            .renaksi-table { font-size: .75rem; }
            main .renaksi-table > :not(caption) > * > * { padding: .45rem .5rem; }
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
        <?= $this->include($isKab ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php'); ?>
        <?= $this->include($isKab ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-1"><?= esc($judul) ?></h2>
                <p class="text-center text-muted small mb-4">Turunkan indikator PK menjadi rencana aksi &amp; target triwulan, lalu pantau realisasinya di menu MONEV.</p>

                <?php if (!empty($summary)): ?>
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="border rounded p-2 p-sm-3 text-center h-100">
                                <div class="rk-stat-num fw-bold text-dark"><?= (int) $summary['indikator'] ?></div>
                                <small class="text-muted">Indikator PK</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 p-sm-3 text-center h-100">
                                <div class="rk-stat-num fw-bold text-success"><?= (int) $summary['with_renaksi'] ?></div>
                                <small class="text-muted">Sudah ada Rencana Aksi</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 p-sm-3 text-center h-100">
                                <div class="rk-stat-num fw-bold text-warning"><?= (int) $summary['belum'] ?></div>
                                <small class="text-muted">Belum ada Rencana Aksi</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <form method="get" class="row g-2 mb-3 align-items-center">
                    <?php if ($isKab): ?>
                        <div class="col-12 col-md-6 col-lg-3">
                            <select class="form-select fw-semibold" aria-label="Mode tampilan" onchange="if(this.value){window.location.href=this.value;}">
                                <option value="<?= base_url($base . '/target_renaksi') ?>" <?= $isBupati ? 'selected' : '' ?>>Mode: PK Bupati (Kabupaten)</option>
                                <option value="<?= base_url($base . '/renaksi_pk/es3') ?>" <?= $isOpd ? 'selected' : '' ?>>Mode: PK OPD/Kecamatan</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="tahun" class="form-select" aria-label="Filter tahun" onchange="this.form.submit()">
                            <option value="all">Semua Tahun</option>
                            <?php foreach ($tahunList as $t): ?>
                                <option value="<?= esc($t['tahun']) ?>" <?= ((string) $tahun === (string) $t['tahun']) ? 'selected' : '' ?>>
                                    <?= esc($t['tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($showOpd): ?>
                        <div class="col-12 col-md-6 col-lg-3">
                            <select name="opd_id" class="form-select select2-opd" aria-label="Filter perangkat daerah" onchange="this.form.submit()">
                                <option value="">Semua OPD</option>
                                <?php foreach (($opdList ?? []) as $opd): ?>
                                    <option value="<?= (int) $opd['id'] ?>" <?= ((int) ($opdFilter ?? 0) === (int) $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ($isOpd): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <select name="eselon" class="form-select" aria-label="Filter eselon" onchange="this.form.submit()">
                                <option value="">Semua Eselon</option>
                                <?php foreach ($pkFilterOptions as $pkKey => $pkLabel): ?>
                                    <option value="<?= esc($pkKey) ?>" <?= (($eselon ?? '') === $pkKey) ? 'selected' : '' ?>>
                                        <?= esc($pkLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($pejabatList)): ?>
                            <div class="col-12 col-md-6 col-lg-3">
                                <select name="pejabat_id" class="form-select select2-pejabat" aria-label="Filter pejabat" onchange="this.form.submit()">
                                    <option value="">Semua Pejabat</option>
                                    <?php foreach ($pejabatList as $pj): ?>
                                        <option value="<?= (int) $pj['id'] ?>" <?= ((int) ($pejabatId ?? 0) === (int) $pj['id']) ? 'selected' : '' ?>>
                                            <?= esc(!empty($pj['jabatan']) ? $pj['jabatan'] : $pj['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="col-12 col-lg d-flex flex-wrap gap-1 justify-content-start justify-content-lg-end">
                        <a href="<?= base_url($renaksiPath . '/cetak') . ($filterQs ? '?' . $filterQs : '') ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                        <a href="<?= base_url($monevPath) . ($filterQs ? '?' . $filterQs : '') ?>" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-chart-line me-1"></i> Lihat MONEV (Realisasi)
                        </a>
                    </div>
                </form>

                <!-- Palang alat tabel: petunjuk gulir + saklar mode ringkas -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="text-muted small rk-hint d-none">
                        <i class="fas fa-arrows-left-right me-1"></i>
                        Geser tabel ke samping untuk kolom lainnya<span class="d-none d-md-inline">
                            &mdash; kolom <?= $isBupati ? 'Sasaran' : 'identitas' ?> dan Aksi tetap terkunci</span>.
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <?php if (!$isBupati && !empty($kolomRingkas)): ?>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="rkRingkas">
                                <label class="form-check-label small text-muted" for="rkRingkas"
                                    title="Sembunyikan kolom Tahun, Satuan, Anggaran &amp; Penanggung Jawab">Mode ringkas</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rk-box">
                    <div class="rk-scroll" tabindex="0" role="region" aria-label="Tabel <?= esc($judul) ?>">
                        <table class="table text-center align-middle small renaksi-table">
                            <colgroup>
                                <?php foreach ($kolom as $k): ?><col class="c-<?= $k['key'] ?>"><?php endforeach; ?>
                            </colgroup>
                        <thead class="table-success fw-bold text-dark">
                            <?php if ($isBupati): ?>
                                <tr>
                                    <th class="c-no">No</th>
                                    <th class="c-sasaran">Sasaran</th>
                                    <th class="c-indikator">Indikator</th>
                                    <th class="c-tahun">Tahun</th>
                                    <th class="c-satuan">Satuan</th>
                                    <th class="c-target">Target</th>
                                    <th class="c-pd">Perangkat Daerah Pendukung PK BUPATI</th>
                                    <th class="c-aksi">Aksi</th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th rowspan="2" class="c-no">No</th>
                                    <?php if ($showOpd): ?><th rowspan="2" class="c-opd">OPD</th><?php endif; ?>
                                    <?php if ($showPejabat): ?><th rowspan="2" class="c-pejabat">Pejabat (Eselon)</th><?php endif; ?>
                                    <th rowspan="2" class="c-sasaran">Sasaran</th>
                                    <th rowspan="2" class="c-indikator">Indikator</th>
                                    <th rowspan="2" class="c-tahun">Tahun</th>
                                    <th rowspan="2" class="c-satuan">Satuan</th>
                                    <th rowspan="2" class="c-target">Target</th>
                                    <th rowspan="2" class="c-unit"><?= esc($labelUnitHeader ?? 'Program') ?></th>
                                    <th rowspan="2" class="c-anggaran">Anggaran</th>
                                    <th rowspan="2" class="c-renaksi">Rencana Aksi</th>
                                    <th rowspan="2" class="c-sub">Sub Rencana Aksi</th>
                                    <?php /* Satuan target triwulan tiap SUB — berbeda dari kolom
                                             "Satuan" di kiri yang milik INDIKATOR. Satu indikator
                                             bersatuan Persen lazim dirinci jadi sub-sub yang
                                             dihitung dalam Dokumen atau Kegiatan. */ ?>
                                    <th rowspan="2" class="c-subsat">Satuan</th>
                                    <th colspan="4">Target Triwulan</th>
                                    <th rowspan="2" class="c-pj">Penanggung Jawab</th>
                                    <th rowspan="2" class="c-aksi">Aksi</th>
                                </tr>
                                <tr>
                                    <th class="c-tw1">I</th>
                                    <th class="c-tw2">II</th>
                                    <th class="c-tw3">III</th>
                                    <th class="c-tw4">IV</th>
                                </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php if (!empty($grouped)): ?>
                                <?php
                                $no = 1;
                                // Pisah teks rencana aksi menjadi daftar item (1 baris = 1 item).
                                $splitAksi = function ($text) {
                                    $text = trim((string) $text);
                                    if ($text === '') return [];
                                    $lines = preg_split('/\r\n|\r|\n/', $text);
                                    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
                                };
                                // Tinggi 1 indikator = yang TERTINGGI antara dua sisi yang tidak
                                // saling berhubungan:
                                //   * sisi rencana aksi -> tiap butir setinggi jumlah sub-nya (min 1);
                                //     inilah yang membuat 1 Rencana Aksi membentang atas Sub 1, 2, 3;
                                //   * sisi unit         -> jumlah Program/Kegiatan/Sub Kegiatan.
                                // Yang lebih pendek DIREGANGKAN mengisi tinggi itu lewat rowspan
                                // (pk_bagi_baris untuk unit, pk_bagi_renaksi untuk rencana aksi),
                                // sehingga tidak ada sisi yang menyisakan sel menganga.
                                $subMap     = $subMap ?? [];
                                $programMap = $programMap ?? [];
                                $barisFor = function ($row) use ($splitAksi, $subMap, $programMap) {
                                    $items = $splitAksi($row['rencana_aksi'] ?? '');
                                    $subs  = $subMap[(int) ($row['target_id'] ?? 0)] ?? [];
                                    $nUnit = count($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);

                                    if (empty($items)) {
                                        return [[], [1], max(1, $nUnit), 1]; // belum ada renaksi -> 1 baris kosong
                                    }

                                    $perButir = [];
                                    foreach ($items as $k => $_) {
                                        $perButir[$k] = max(1, count($subs[$k] ?? []));
                                    }
                                    $barisRenaksi = array_sum($perButir);

                                    return [$items, $perButir, max($barisRenaksi, $nUnit), $barisRenaksi];
                                };
                                // Format anggaran: helper format_helper (autoload) menyediakan formatRupiah().
                                $rupiah = function ($nilai) {
                                    if (function_exists('formatRupiah')) {
                                        return formatRupiah($nilai);
                                    }
                                    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
                                };
                                // Total baris per OPD -> nomor & kolom OPD digabung (rowspan) per OPD (mode admin_kab).
                                $opdTotals = [];
                                if ($showOpd) {
                                    foreach ($grouped as $gr) {
                                        $ok = $gr[0]['opd_id'] ?? ($gr[0]['nama_opd'] ?? '-');
                                        $t  = 0;
                                        foreach ($gr as $grRow) {
                                            [,, $nBaris] = $barisFor($grRow);
                                            $t += $nBaris;
                                        }
                                        $opdTotals[$ok] = ($opdTotals[$ok] ?? 0) + $t;
                                    }
                                }
                                $curOpdKey = null;
                                ?>
                                <?php if ($isBupati): ?>
                                    <?php
                                    // PK Bupati: Penanggung Jawab Perangkat Daerah OTOMATIS dari mapping Cascading
                                    // (sasaran PK Bupati dicocokkan ke sasaran RPJMD -> OPD via rantai renstra).
                                    // Eselon dipilih lewat tautan per baris -> menuju PK OPD/Kecamatan tsb. Read-only.
                                    $normSas = static fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
                                    $es3Base = base_url($base . '/renaksi_pk/es3');
                                    ?>
                                    <?php foreach ($grouped as $rows): ?>
                                        <?php
                                        $sasaran    = $rows[0]['sasaran_renstra'] ?? '-';
                                        $sasTotal   = count($rows);
                                        $autoOpds   = ($autoPd ?? [])[$normSas($sasaran)] ?? [];
                                        if (empty($autoOpds)) { // fallback: cocokkan lewat teks INDIKATOR (atasi typo/beda teks sasaran)
                                            foreach ($rows as $rr) {
                                                $ik = $normSas($rr['indikator_sasaran'] ?? '');
                                                if ($ik !== '' && !empty(($autoPd ?? [])[$ik])) {
                                                    $autoOpds = $autoPd[$ik];
                                                    break;
                                                }
                                            }
                                        }
                                        // Override MANUAL (kolom Aksi): bila ada, gantikan hasil otomatis.
                                        $pkSasaranId = (int) ($rows[0]['pk_sasaran_id'] ?? 0);
                                        $manualOpds  = ($manualPd ?? [])[$pkSasaranId] ?? [];
                                        $isManual    = !empty($manualOpds);
                                        $displayOpds = $isManual ? $manualOpds : $autoOpds;
                                        $sasPrinted = false;
                                        $pdPrinted  = false;
                                        $noPrinted  = false;
                                        ?>
                                        <?php foreach ($rows as $row): ?>
                                            <tr<?= $noPrinted ? '' : ' class="rk-grp"' ?>>
                                                <?php if (!$noPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="c-no"><?= $no ?></td>
                                                    <?php $noPrinted = true; ?>
                                                <?php endif; ?>
                                                <?php if (!$sasPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start va-top c-sasaran"><?= esc($sasaran) ?></td>
                                                    <?php $sasPrinted = true; ?>
                                                <?php endif; ?>
                                                <td class="text-start c-indikator"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                <td class="c-tahun"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                <td class="c-satuan"><?= esc($row['satuan'] ?? '-') ?></td>
                                                <td class="c-target"><?= esc($row['indikator_target'] ?? '-') ?></td>
                                                <?php if (!$pdPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start va-top c-pd">
                                                        <?php if ($isManual): ?>
                                                            <div class="mb-2"><span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fas fa-hand-pointer me-1"></i>Diatur manual</span></div>
                                                        <?php endif; ?>
                                                        <?php if (empty($displayOpds)): ?>
                                                            <span class="text-muted">Belum ditetapkan</span>
                                                        <?php else: ?>
                                                            <?php foreach ($displayOpds as $o): ?>
                                                                <?php
                                                                $isKecamatanPd = stripos((string) ($o['nama'] ?? ''), 'kecamatan') !== false;
                                                                $eselonLinks = $isKecamatanPd
                                                                    ? ['administrator' => 'Eselon III', 'pengawas' => 'Eselon IV']
                                                                    : ['jpt' => 'Eselon II', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
                                                                ?>
                                                                <div class="mb-2">
                                                                    <span class="fw-semibold text-success align-middle">
                                                                        <i class="fas fa-building me-1"></i><?= esc($o['nama']) ?>
                                                                    </span>
                                                                    <span class="ms-1">
                                                                        <?php foreach ($eselonLinks as $ek => $elabel): ?>
                                                                            <a href="<?= esc($es3Base . '?opd_id=' . (int) $o['id'] . '&eselon=' . $ek) ?>"
                                                                                class="badge rounded-pill bg-success-subtle text-success border border-success-subtle text-decoration-none fw-normal"
                                                                                title="Buka PK <?= esc($elabel) ?> &mdash; <?= esc($o['nama']) ?>"><?= esc($elabel) ?></a>
                                                                        <?php endforeach; ?>
                                                                    </span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-center va-top c-aksi">
                                                        <?php if ($canWrite ?? false): ?>
                                                            <?php $hasPd = !empty($displayOpds); // ada PD (manual ATAU otomatis) -> Edit; kosong -> Tambah 
                                                            ?>
                                                            <a href="<?= $baseUrl . '/pd/' . $pkSasaranId ?>"
                                                                class="btn btn-<?= $hasPd ? 'warning' : 'primary' ?> btn-sm"
                                                                title="<?= $hasPd ? 'Edit' : 'Tambah' ?> Perangkat Daerah Pendukung">
                                                                <i class="fas fa-<?= $hasPd ? 'edit' : 'plus' ?>"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php $pdPrinted = true; ?>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php $no++; // nomor per Sasaran 
                                        ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($grouped as $rows): ?>
                                        <?php
                                        $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                                        $opdKey  = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
                                        // jumlah baris per indikator = jumlah item renaksi (min 1); total utk rowspan sasaran/pejabat
                                        $indCounts = [];
                                        $sasTotal  = 0;
                                        foreach ($rows as $ri => $r) {
                                            [,, $c] = $barisFor($r);
                                            $indCounts[$ri] = $c;
                                            $sasTotal += $c;
                                        }
                                        $newOpd = ($showOpd && $opdKey !== $curOpdKey); // awal blok OPD baru
                                        $sasPrinted = false;
                                        $noPrinted  = false;
                                        ?>
                                        <?php foreach ($rows as $ri => $row): ?>
                                            <?php
                                            [$items, $barisButir, $n] = $barisFor($row);
                                            $subsRow  = ($subMap[(int) ($row['target_id'] ?? 0)] ?? []);
                                            // "Unit" = Program / Kegiatan / Sub Kegiatan, tergantung pk.jenis baris ini.
                                            $units    = array_values($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);
                                            // Datarkan jadi daftar baris: tiap elemen = [indeks butir, indeks sub]
                                            $barisRender = [];
                                            foreach ($barisButir as $bk => $bJumlah) {
                                                for ($bj = 0; $bj < $bJumlah; $bj++) {
                                                    $barisRender[] = [$bk, $bj];
                                                }
                                            }

                                            // Tinggi indikator DIBAGI RATA ke jumlah unit lewat rowspan,
                                            // supaya tidak ada sel Unit/Anggaran yang menganga kosong.
                                            // Contoh: 4 baris & 2 unit -> rowspan 2 dan 2.
                                            [$spanUnit, $mulaiUnit] = pk_bagi_baris($units, $n);

                                            // Baris rencana aksi dibagi rata dengan cara yang sama, supaya
                                            // sisa tinggi ketika unit lebih banyak TIDAK jadi blok kosong.
                                            [$spanBaris, $mulaiBaris, $spanButir] = pk_bagi_renaksi($barisRender, $n);
                                            ?>
                                            <?php for ($k = 0; $k < $n; $k++): ?>
                                                <?php
                                                $riBaris             = $mulaiBaris[$k] ?? null;
                                                [$butirIdx, $subIdx] = $riBaris !== null ? $barisRender[$riBaris] : [null, null];
                                                $spanRow             = $riBaris !== null ? ($spanBaris[$riBaris] ?? 1) : 1;
                                                // Penanda awal kelompok -> garis pemisah lebih tegas.
                                                $awalOpd  = ($showOpd && $newOpd);
                                                $trClass  = $awalOpd ? 'rk-grp rk-grp-opd' : (!$noPrinted ? 'rk-grp' : '');
                                                ?>
                                                <tr<?= $trClass !== '' ? ' class="' . $trClass . '"' : '' ?>>
                                                    <?php if (!$noPrinted): ?>
                                                        <td rowspan="<?= $sasTotal ?>" class="va-top c-no"><?= $no ?></td>
                                                        <?php $noPrinted = true; ?>
                                                    <?php endif; ?>
                                                    <?php if ($awalOpd): ?>
                                                        <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="text-start va-top c-opd"><?= esc($row['nama_opd'] ?? '-') ?></td>
                                                        <?php $curOpdKey = $opdKey;
                                                        $newOpd = false; ?>
                                                    <?php endif; ?>
                                                    <?php if (!$sasPrinted): ?>
                                                        <?php if ($showPejabat): ?>
                                                            <td rowspan="<?= $sasTotal ?>" class="text-start va-top c-pejabat">
                                                                <div class="fw-semibold"><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?></div>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle mt-1"><?= esc($eselonLabel(!empty($eselon ?? null) ? $eselon : ($rows[0]['pk_jenis'] ?? ''), $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? '')) ?></span>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td rowspan="<?= $sasTotal ?>" class="text-start va-top c-sasaran"><?= esc($sasaran) ?></td>
                                                        <?php $sasPrinted = true; ?>
                                                    <?php endif; ?>
                                                    <?php if ($k === 0): ?>
                                                        <td rowspan="<?= $n ?>" class="text-start va-top c-indikator"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top c-tahun"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top c-satuan"><?= esc($row['satuan'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top c-target"><?= esc($row['indikator_target'] ?? '-') ?></td>

                                                    <?php endif; ?>

                                                    <?php // Unit & anggaran memakai rowspan: tinggi indikator dibagi
                                                    // rata ke jumlah unit, jadi sejajar dan tanpa sel kosong.
                                                    ?>
                                                    <?php if (empty($units)): ?>
                                                        <?php if ($k === 0): ?>
                                                            <td rowspan="<?= $n ?>" class="text-muted va-top c-unit">-</td>
                                                            <td rowspan="<?= $n ?>" class="text-muted va-top c-anggaran">-</td>
                                                        <?php endif; ?>
                                                    <?php elseif (isset($mulaiUnit[$k])): ?>
                                                        <?php
                                                        $ui   = $mulaiUnit[$k];
                                                        $unit = $units[$ui];
                                                        $span = $spanUnit[$ui] ?? 1;
                                                        // Badge tingkat ditampilkan bila tabel memuat campuran eselon
                                                        // (judul kolom generik) ATAU unit ini turun tingkat (fallback),
                                                        // supaya pembaca tahu isinya Program / Kegiatan / Sub Kegiatan.
                                                        $unitFallback = !empty($unit['fallback']);
                                                        $tampilBadge  = ($unitHeaderGenerik ?? false) || $unitFallback;
                                                        ?>
                                                        <td rowspan="<?= $span ?>" class="text-start prog-cell c-unit">
                                                            <?= esc($unit['nama'] ?? ($unit['program'] ?? '-')) ?>
                                                            <?php if ($tampilBadge && !empty($unit['level_label'])): ?>
                                                                <span class="badge <?= $unitFallback ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle' ?> fw-normal ms-1"
                                                                    <?= $unitFallback ? 'title="Tingkat aslinya kosong, ditampilkan dari tingkat di atasnya"' : '' ?>><?= esc($unit['level_label']) ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td rowspan="<?= $span ?>" class="prog-cell-money c-anggaran">
                                                            <?= esc($rupiah($unit['anggaran'] ?? 0)) ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php if ($butirIdx !== null): ?>
                                                        <?php // Rencana Aksi membentang setinggi seluruh sub rencana aksinya
                                                        ?>
                                                        <?php if ($subIdx === 0): ?>
                                                            <td rowspan="<?= $spanButir[$butirIdx] ?? 1 ?>" class="text-start va-top c-renaksi">
                                                                <?php
                                                                $txt = $items[$butirIdx] ?? '';
                                                                echo ($txt !== '') ? esc(($butirIdx + 1) . '. ' . $txt) : '<span class="text-muted">-</span>';
                                                                ?>
                                                            </td>
                                                        <?php endif; ?>

                                                        <?php $sub = $subsRow[$butirIdx][$subIdx] ?? null; ?>
                                                        <td rowspan="<?= $spanRow ?>" class="text-start sub-cell va-top c-sub">
                                                            <?= $sub !== null ? esc(($subIdx + 1) . '. ' . $sub['teks']) : '<span class="text-muted">-</span>' ?>
                                                        </td>

                                                        <td rowspan="<?= $spanRow ?>" class="va-top c-subsat">
                                                            <?= ($sub !== null && ($sub['satuan'] ?? '') !== '')
                                                                ? esc($sub['satuan'])
                                                                : '<span class="text-muted">-</span>' ?>
                                                        </td>

                                                        <?php // Target Triwulan mengikuti SUB rencana aksi pada baris ini
                                                        ?>
                                                        <?php foreach ([1, 2, 3, 4] as $q): ?>
                                                            <?php $nilaiTw = $sub['tw'][$q] ?? null; ?>
                                                            <td rowspan="<?= $spanRow ?>" class="tw-cell va-top c-tw<?= $q ?>"><?= ($nilaiTw !== null && $nilaiTw !== '') ? esc($nilaiTw) : '<span class="text-muted">-</span>' ?></td>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>

                                                    <?php if ($k === 0): ?>
                                                        <td rowspan="<?= $n ?>" class="text-start va-top c-pj"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top c-aksi">
                                                            <?php if ($canWrite ?? true): ?>
                                                                <?php if (empty($row['target_id'])): ?>
                                                                    <a href="<?= $baseUrl . '/tambah?pi=' . (int) $row['pk_indikator_id'] ?>"
                                                                        class="btn btn-primary btn-sm" title="Tambah Rencana Aksi">
                                                                        <i class="fas fa-plus"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="<?= $baseUrl . '/edit/' . (int) $row['target_id'] ?>"
                                                                        class="btn btn-warning btn-sm" title="Edit Rencana Aksi">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <?php if (empty($row['target_id'])): ?>
                                                                    <span class="badge bg-light text-muted border">Belum</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Ada</span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endfor; ?>
                                        <?php endforeach; ?>
                                        <?php $no++; // nomor per Sasaran (semua role) 
                                        ?>
                                    <?php endforeach; ?>
                                <?php endif; // PK Bupati vs Eselon 
                                ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $cols ?>" class="text-muted py-5">
                                        <i class="fas fa-table-list fa-2x mb-2 d-block opacity-50"></i>
                                        Belum ada indikator PK <?= $isBupati ? 'Bupati' : 'OPD/Kecamatan' ?> untuk filter ini.<br>
                                        Pastikan dokumen PK sudah dibuat di menu Perjanjian Kinerja.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        // ---------------------------------------------------------------
        // Kerangka baca tabel lebar:
        //   1. --rk-h1 : tinggi baris kepala ke-1, dipakai baris ke-2 agar
        //                menempel persis (tanpa celah / tumpang tindih).
        //   2. bayangan tepi: penanda bahwa masih ada kolom di kiri/kanan.
        //   3. mode ringkas: pilihan pengguna, diingat per peramban.
        // ---------------------------------------------------------------
        (function() {
            var box = document.querySelector('.rk-box');
            if (!box) return;
            var scroll  = box.querySelector('.rk-scroll');
            var table   = box.querySelector('.renaksi-table');
            var hint    = document.querySelector('.rk-hint');
            var ringkas = document.getElementById('rkRingkas');
            var KUNCI   = 'rk-ringkas';

            function ukurKepala() {
                var baris = table && table.querySelector('thead tr');
                if (baris) {
                    table.style.setProperty('--rk-h1', Math.round(baris.getBoundingClientRect().height) + 'px');
                }
            }

            function tandaiTepi() {
                var sisaKanan = scroll.scrollWidth - scroll.clientWidth - scroll.scrollLeft;
                box.classList.toggle('is-geser-kiri', scroll.scrollLeft > 2);
                box.classList.toggle('is-ada-kanan', sisaKanan > 2);
                if (hint) {
                    hint.classList.toggle('d-none', scroll.scrollWidth <= scroll.clientWidth + 2);
                }
            }

            function segarkan() {
                ukurKepala();
                tandaiTepi();
            }

            scroll.addEventListener('scroll', tandaiTepi, { passive: true });
            window.addEventListener('resize', segarkan);
            // Pagination otomatis (templates/footer.php) menyembunyikan baris
            // SETELAH skrip ini jalan -> ukur ulang saat DOM & aset siap.
            document.addEventListener('DOMContentLoaded', segarkan);
            window.addEventListener('load', segarkan);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(segarkan).catch(function() {});
            }

            if (ringkas) {
                var colgroup = table.querySelector('colgroup');
                var semuaCol = colgroup ? Array.prototype.slice.call(colgroup.children) : [];
                var kelasRingkas = <?= json_encode(array_map(fn($k) => 'c-' . $k, $kolomRingkas)) ?>;

                // Menyembunyikan SEL saja tidak cukup: sel sesudahnya akan
                // bergeser ke slot kolom sebelumnya sehingga tidak lagi cocok
                // dengan lebar di <colgroup>. Jadi <col>-nya ikut dilepas.
                function terapkanRingkas(aktif) {
                    box.classList.toggle('ringkas', aktif);
                    if (!colgroup) return;
                    var dipakai = aktif ? semuaCol.filter(function(c) {
                        return kelasRingkas.indexOf(c.className) === -1;
                    }) : semuaCol;
                    while (colgroup.firstChild) { colgroup.removeChild(colgroup.firstChild); }
                    dipakai.forEach(function(c) { colgroup.appendChild(c); });
                }

                try { ringkas.checked = localStorage.getItem(KUNCI) === '1'; } catch (e) {}
                terapkanRingkas(ringkas.checked);
                ringkas.addEventListener('change', function() {
                    terapkanRingkas(ringkas.checked);
                    try { localStorage.setItem(KUNCI, ringkas.checked ? '1' : '0'); } catch (e) {}
                    segarkan();
                });
            }

            segarkan();
        })();

        // Filter OPD & Pejabat pakai Select2 (dropdown pencarian)
        $(function() {
            if (!$.fn.select2) return;
            var base = {
                width: '100%',
                theme: 'bootstrap-5',
                dropdownParent: $('body')
            };
            $('.select2-opd').select2(Object.assign({}, base, {
                placeholder: 'Semua OPD'
            }));
            $('.select2-pejabat').select2(Object.assign({}, base, {
                placeholder: 'Semua Pejabat'
            }));
        });

        // Drilldown PK Bupati: klik Perangkat Daerah -> tampil/sembunyikan daftar rencana aksi.
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.drill-toggle');
            if (!btn) return;
            var body = document.getElementById(btn.getAttribute('data-target'));
            if (!body) return;
            body.classList.toggle('d-none');
            var caret = btn.querySelector('.drill-caret');
            if (caret) {
                caret.classList.toggle('fa-caret-right');
                caret.classList.toggle('fa-caret-down');
            }
        });

        // PK Bupati: pilih jenis PK -> buka PK OPD/Kecamatan Perangkat Daerah tsb.
        document.addEventListener('change', function(e) {
            var sel = e.target.closest('.pk-eselon-jump');
            if (!sel || !sel.value) return;
            var url = sel.getAttribute('data-url');
            if (url) window.location.href = url + '&eselon=' + encodeURIComponent(sel.value);
        });
    </script>
</body>

</html>
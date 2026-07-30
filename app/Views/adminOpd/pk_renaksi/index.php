<?php
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

// Jumlah kolom (untuk baris kosong)
// non-bupati: No, Sasaran, Indikator, Tahun, Satuan, Target, Program, Anggaran,
//             Rencana Aksi, Sub Rencana Aksi, 4x Triwulan, Penanggung Jawab, Aksi
$cols = $isBupati ? 8 : (16 + ($showPejabat ? 1 : 0) + ($showOpd ? 1 : 0));

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
    <style>
        /* Lebar minimum supaya kolom tidak saling gencet; sisanya digeser
           horizontal oleh .table-responsive. */
        .renaksi-table {
            min-width: 1500px;
        }

        /* Sel gabungan (indikator, sasaran, program, rencana aksi) dibaca dari
           ATAS — supaya jelas isi mana milik kelompok mana. */
        .renaksi-table td.va-top {
            vertical-align: top;
        }

        /* Program & anggaran: 1 program = 1 BARIS tabel sungguhan, jadi
           sejajarnya dijamin struktur tabel dan tingginya ikut isi (tidak ada
           teks yang tumpang tindih walau nama programnya panjang). */
        .renaksi-table td.prog-cell,
        .renaksi-table td.prog-cell-money {
            vertical-align: top;
        }

        .renaksi-table td.prog-cell-money {
            white-space: nowrap;
            text-align: right;
        }

        .renaksi-table col.col-program {
            width: 18%;
        }

        /* Sub rencana aksi tampil menjorok, menandakan miliknya rencana aksi di kirinya. */
        .renaksi-table td.sub-cell {
            border-left: 2px solid #cfe0d5;
        }

        .renaksi-table td.tw-cell {
            white-space: nowrap;
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
                            <div class="border rounded p-3 text-center h-100">
                                <div class="h4 mb-0 fw-bold text-dark"><?= (int) $summary['indikator'] ?></div>
                                <small class="text-muted">Indikator PK</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="h4 mb-0 fw-bold text-success"><?= (int) $summary['with_renaksi'] ?></div>
                                <small class="text-muted">Sudah ada Rencana Aksi</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="h4 mb-0 fw-bold text-warning"><?= (int) $summary['belum'] ?></div>
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

                <form method="get" class="row g-2 mb-4 align-items-center">
                    <?php if ($isKab): ?>
                        <div class="col-md-3">
                            <select class="form-select fw-semibold" onchange="if(this.value){window.location.href=this.value;}">
                                <option value="<?= base_url($base . '/target_renaksi') ?>" <?= $isBupati ? 'selected' : '' ?>>Mode: PK Bupati (Kabupaten)</option>
                                <option value="<?= base_url($base . '/renaksi_pk/es3') ?>" <?= $isOpd ? 'selected' : '' ?>>Mode: PK OPD/Kecamatan</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                            <option value="all">Semua Tahun</option>
                            <?php foreach ($tahunList as $t): ?>
                                <option value="<?= esc($t['tahun']) ?>" <?= ((string) $tahun === (string) $t['tahun']) ? 'selected' : '' ?>>
                                    <?= esc($t['tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($showOpd): ?>
                        <div class="col-md-3">
                            <select name="opd_id" class="form-select select2-opd" onchange="this.form.submit()">
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
                        <div class="col-md-2">
                            <select name="eselon" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Eselon</option>
                                <?php foreach ($pkFilterOptions as $pkKey => $pkLabel): ?>
                                    <option value="<?= esc($pkKey) ?>" <?= (($eselon ?? '') === $pkKey) ? 'selected' : '' ?>>
                                        <?= esc($pkLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($pejabatList)): ?>
                            <div class="col-md-3">
                                <select name="pejabat_id" class="form-select select2-pejabat" onchange="this.form.submit()">
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
                    <div class="col text-end">
                        <a href="<?= base_url($renaksiPath . '/cetak') . ($filterQs ? '?' . $filterQs : '') ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                        <a href="<?= base_url($monevPath) . ($filterQs ? '?' . $filterQs : '') ?>" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-chart-line me-1"></i> Lihat MONEV (Realisasi)
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small renaksi-table">
                        <thead class="table-success fw-bold text-dark">
                            <?php if ($isBupati): ?>
                                <tr>
                                    <th>No</th>
                                    <th>Sasaran</th>
                                    <th>Indikator</th>
                                    <th>Tahun</th>
                                    <th>Satuan</th>
                                    <th>Target</th>
                                    <th>Perangkat Daerah Pendukung PK BUPATI</th>
                                    <th>Aksi</th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th rowspan="2">No</th>
                                    <?php if ($showOpd): ?><th rowspan="2">OPD</th><?php endif; ?>
                                    <?php if ($showPejabat): ?><th rowspan="2">Pejabat (Eselon)</th><?php endif; ?>
                                    <th rowspan="2">Sasaran</th>
                                    <th rowspan="2">Indikator</th>
                                    <th rowspan="2">Tahun</th>
                                    <th rowspan="2">Satuan</th>
                                    <th rowspan="2">Target</th>
                                    <th rowspan="2">Program</th>
                                    <th rowspan="2">Anggaran</th>
                                    <th rowspan="2">Rencana Aksi</th>
                                    <th rowspan="2">Sub Rencana Aksi</th>
                                    <th colspan="4">Target Triwulan</th>
                                    <th rowspan="2">Penanggung Jawab</th>
                                    <th rowspan="2">Aksi</th>
                                </tr>
                                <tr>
                                    <th>I</th>
                                    <th>II</th>
                                    <th>III</th>
                                    <th>IV</th>
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
                                // Tinggi baris 1 indikator = jumlah butir rencana aksi, di mana tiap butir
                                // sendiri setinggi jumlah sub rencana aksinya (min 1). Ini yang membuat
                                // 1 Rencana Aksi bisa membentang atas Sub 1, 2, 3.
                                // Tinggi 1 indikator ditentukan HANYA oleh rencana aksi:
                                // tiap butir setinggi jumlah sub-nya (min 1). Program & anggaran
                                // menempel ke indikator (digabung setinggi $n), bukan menambah baris.
                                $subMap     = $subMap ?? [];
                                $programMap = $programMap ?? [];
                                $barisFor = function ($row) use ($splitAksi, $subMap, $programMap) {
                                    $items = $splitAksi($row['rencana_aksi'] ?? '');
                                    $subs  = $subMap[(int) ($row['target_id'] ?? 0)] ?? [];
                                    $nProg = count($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);

                                    if (empty($items)) {
                                        return [[], [1], max(1, $nProg), 1]; // belum ada renaksi -> 1 baris kosong
                                    }

                                    $perButir = [];
                                    foreach ($items as $k => $_) {
                                        $perButir[$k] = max(1, count($subs[$k] ?? []));
                                    }
                                    $barisRenaksi = array_sum($perButir);

                                    return [$items, $perButir, max($barisRenaksi, $nProg), $barisRenaksi];
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
                                            <tr>
                                                <?php if (!$noPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>"><?= $no ?></td>
                                                    <?php $noPrinted = true; ?>
                                                <?php endif; ?>
                                                <?php if (!$sasPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                                                    <?php $sasPrinted = true; ?>
                                                <?php endif; ?>
                                                <td class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                <td><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                <td><?= esc($row['satuan'] ?? '-') ?></td>
                                                <td><?= esc($row['indikator_target'] ?? '-') ?></td>
                                                <?php if (!$pdPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start">
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
                                                    <td rowspan="<?= $sasTotal ?>" class="text-center">
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
                                            [$items, $barisButir, $n, $barisRenaksi] = $barisFor($row);
                                            $subsRow  = ($subMap[(int) ($row['target_id'] ?? 0)] ?? []);
                                            $programs = array_values($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);
                                            // Datarkan jadi daftar baris: tiap elemen = [indeks butir, indeks sub]
                                            $barisRender = [];
                                            foreach ($barisButir as $bk => $bJumlah) {
                                                for ($bj = 0; $bj < $bJumlah; $bj++) {
                                                    $barisRender[] = [$bk, $bj];
                                                }
                                            }

                                            // Tinggi indikator DIBAGI RATA ke jumlah program lewat rowspan,
                                            // supaya tidak ada sel Program/Anggaran yang menganga kosong.
                                            // Contoh: 4 baris & 2 program -> rowspan 2 dan 2.
                                            $spanProgram = [];
                                            $mulaiProgram = [];
                                            if (!empty($programs)) {
                                                $sisaBaris  = $n;
                                                $sisaProgram = count($programs);
                                                $awal = 0;
                                                foreach ($programs as $pi => $_) {
                                                    $span = (int) ceil($sisaBaris / max(1, $sisaProgram));
                                                    $span = max(1, $span);
                                                    $spanProgram[$pi]  = $span;
                                                    $mulaiProgram[$awal] = $pi;
                                                    $awal        += $span;
                                                    $sisaBaris   -= $span;
                                                    $sisaProgram--;
                                                }
                                            }
                                            ?>
                                            <?php for ($k = 0; $k < $n; $k++): ?>
                                                <?php [$butirIdx, $subIdx] = $barisRender[$k] ?? [null, null]; ?>
                                                <tr>
                                                    <?php if (!$noPrinted): ?>
                                                        <td rowspan="<?= $sasTotal ?>"><?= $no ?></td>
                                                        <?php $noPrinted = true; ?>
                                                    <?php endif; ?>
                                                    <?php if ($showOpd && $newOpd): ?>
                                                        <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="text-start"><?= esc($row['nama_opd'] ?? '-') ?></td>
                                                        <?php $curOpdKey = $opdKey;
                                                        $newOpd = false; ?>
                                                    <?php endif; ?>
                                                    <?php if (!$sasPrinted): ?>
                                                        <?php if ($showPejabat): ?>
                                                            <td rowspan="<?= $sasTotal ?>" class="text-start">
                                                                <div class="fw-semibold"><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?></div>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= esc($eselonLabel(!empty($eselon ?? null) ? $eselon : ($rows[0]['pk_jenis'] ?? ''), $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? '')) ?></span>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                                                        <?php $sasPrinted = true; ?>
                                                    <?php endif; ?>
                                                    <?php if ($k === 0): ?>
                                                        <td rowspan="<?= $n ?>" class="text-start va-top"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top"><?= esc($row['indikator_tahun'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top"><?= esc($row['satuan'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>" class="va-top"><?= esc($row['indikator_target'] ?? '-') ?></td>

                                                    <?php endif; ?>

                                                    <?php // Program & anggaran memakai rowspan: tinggi indikator dibagi
                                                    // rata ke jumlah program, jadi sejajar dan tanpa sel kosong. 
                                                    ?>
                                                    <?php if (empty($programs)): ?>
                                                        <?php if ($k === 0): ?>
                                                            <td rowspan="<?= $n ?>" class="text-muted va-top">-</td>
                                                            <td rowspan="<?= $n ?>" class="text-muted va-top">-</td>
                                                        <?php endif; ?>
                                                    <?php elseif (isset($mulaiProgram[$k])): ?>
                                                        <?php
                                                        $pi   = $mulaiProgram[$k];
                                                        $prog = $programs[$pi];
                                                        $span = $spanProgram[$pi] ?? 1;
                                                        ?>
                                                        <td rowspan="<?= $span ?>" class="text-start prog-cell">
                                                            <?= esc($prog['program']) ?>
                                                        </td>
                                                        <td rowspan="<?= $span ?>" class="prog-cell-money">
                                                            <?= esc($rupiah($prog['anggaran'])) ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php if ($butirIdx === null): ?>
                                                        <?php // Sisa baris ketika program lebih banyak dari baris rencana aksi 
                                                        ?>
                                                        <?php if ($k === $barisRenaksi): ?>
                                                            <td colspan="6" rowspan="<?= $n - $barisRenaksi ?>"></td>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php // Rencana Aksi membentang setinggi sub rencana aksinya 
                                                        ?>
                                                        <?php if ($subIdx === 0): ?>
                                                            <td rowspan="<?= $barisButir[$butirIdx] ?? 1 ?>" class="text-start va-top">
                                                                <?php
                                                                $txt = $items[$butirIdx] ?? '';
                                                                echo ($txt !== '') ? esc(($butirIdx + 1) . '. ' . $txt) : '<span class="text-muted">-</span>';
                                                                ?>
                                                            </td>
                                                        <?php endif; ?>

                                                        <?php $sub = $subsRow[$butirIdx][$subIdx] ?? null; ?>
                                                        <td class="text-start sub-cell va-top">
                                                            <?= $sub !== null ? esc(($subIdx + 1) . '. ' . $sub['teks']) : '<span class="text-muted">-</span>' ?>
                                                        </td>

                                                        <?php // Target Triwulan mengikuti SUB rencana aksi pada baris ini 
                                                        ?>
                                                        <?php foreach ([1, 2, 3, 4] as $q): ?>
                                                            <?php $nilaiTw = $sub['tw'][$q] ?? null; ?>
                                                            <td class="tw-cell"><?= ($nilaiTw !== null && $nilaiTw !== '') ? esc($nilaiTw) : '<span class="text-muted">-</span>' ?></td>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>

                                                    <?php if ($k === 0): ?>
                                                        <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                                                        <td rowspan="<?= $n ?>">
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
                                    <td colspan="<?= $cols ?>" class="text-muted">
                                        Belum ada indikator PK <?= $isBupati ? 'Bupati' : 'OPD/Kecamatan' ?> untuk filter ini.
                                        Pastikan dokumen PK sudah dibuat di menu Perjanjian Kinerja.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
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
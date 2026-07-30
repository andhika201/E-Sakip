<?php
helper('capaian'); // capaianFormatPersen() & capaianMetodeNama() untuk kolom Capaian Total

$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$isKab    = (($role ?? '') === 'admin_kab');
$judul    = ($isBupati || !$isKab) ? 'MONEV' : 'Monitoring Capaian Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');
$showPejabat = $isOpd;
// Path URL bersih. Prefix area SELALU dari $base (controller) supaya role
// read-only bupati tetap di /bupati dan tidak pernah dilempar ke /adminkab.
$renaksiPath = ($jenis === 'bupati') ? ($base . '/target_renaksi')
    : (($base === 'adminopd') ? 'adminopd/target_renaksi' : ($base . '/renaksi_pk/' . $jenis));
$monevPath   = ($jenis === 'bupati') ? ($base . '/monev')
    : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl  = base_url($monevPath);

$pkFilterOptions = [];
if ($isOpd) {
    $roleName = (string) ($role ?? '');
    if ($roleName !== 'admin_kecamatan') {
        $pkFilterOptions['jpt'] = 'Eselon II';
    }
    $pkFilterOptions['administrator'] = 'Eselon III';
    $pkFilterOptions['pengawas'] = 'Eselon IV';
}

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

// helper angka ID -> float (null bila kosong/non-numerik)
$toNum = function ($v) {
    if ($v === null || $v === '') return null;
    $v = str_replace(',', '.', (string) $v);
    return is_numeric($v) ? (float) $v : null;
};

// PK Bupati: Penanggung Jawab Perangkat Daerah OTOMATIS (cocokkan sasaran -> OPD) + hyperlink Eselon (MONEV).
$normSas = static fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
$es3Base = base_url($base . '/monev_pk/es3');

// No, Sasaran, Indikator, Satuan, Program, Anggaran, Realisasi Anggaran (I-IV + Aksi),
// Rencana Aksi, Sub Rencana Aksi, Target TW (4), Capaian TW (4), Total, Penanggung Jawab, Aksi
$cols = 24 + ($showPejabat ? 1 : 0) + ($showOpd ? 1 : 0);

// format_helper tidak ikut autoload — pakai pembungkus bercadangan.
$rupiah = function ($nilai) {
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};

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
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
        <?= $this->include($isKab ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php'); ?>
        <?= $this->include($isKab ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-1"><?= esc($judul) ?></h2>
                <p class="text-center text-muted small mb-4">Realisasi capaian triwulanan terhadap target Rencana Aksi PK.</p>

                <?php if (!empty($summary)): ?>
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="h4 mb-0 fw-bold text-dark"><?= (int) $summary['renaksi'] ?></div>
                                <small class="text-muted">Rencana Aksi PK</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="h4 mb-0 fw-bold text-primary"><?= (int) $summary['with_capaian'] ?></div>
                                <small class="text-muted">Sudah diisi Capaian</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="h4 mb-0 fw-bold text-success">
                                    <?= $summary['avg_pct'] !== null ? esc($summary['avg_pct']) . '%' : '-' ?>
                                </div>
                                <small class="text-muted">Rata-rata Realisasi</small>
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
                                <?php // Mode PK Bupati disembunyikan (sementara) di MONEV admin_kab — tidak dikunci, hanya tidak ditampilkan. 
                                ?>
                                <option value="<?= base_url($base . '/monev_pk/es3') ?>" <?= $isOpd ? 'selected' : '' ?>>Mode: PK OPD/Kecamatan</option>
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
                        <a href="<?= base_url($monevPath . '/cetak') . ($filterQs ? '?' . $filterQs : '') ?>"
                            target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                        </a>
                        <a href="<?= base_url($renaksiPath) . ($filterQs ? '?' . $filterQs : '') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list-check me-1"></i> Kelola Rencana Aksi
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle small">
                        <thead class="table-primary fw-bold text-dark">
                            <tr>
                                <th rowspan="2">No</th>
                                <?php if ($showOpd): ?><th rowspan="2">OPD</th><?php endif; ?>
                                <?php if ($showPejabat): ?><th rowspan="2">Pejabat (Eselon)</th><?php endif; ?>
                                <th rowspan="2">Sasaran</th>
                                <th rowspan="2">Indikator</th>
                                <th rowspan="2">Satuan</th>
                                <th rowspan="2">Program</th>
                                <th rowspan="2">Anggaran</th>
                                <th colspan="5">Realisasi Anggaran Per Triwulan (Rp)</th>
                                <th rowspan="2">Rencana Aksi</th>
                                <th rowspan="2">Sub Rencana Aksi</th>
                                <th colspan="4">Target Triwulan</th>
                                <th colspan="4">Capaian Triwulan</th>
                                <th rowspan="2">Capaian Total</th>
                                <th rowspan="2"><?= $isBupati ? 'Penanggung Jawab Perangkat Daerah' : 'Penanggung Jawab' ?></th>
                                <th rowspan="2">Aksi</th>
                            </tr>
                            <tr>
                                <?php // Realisasi Anggaran punya tombol aksinya sendiri 
                                ?>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                                <th>Aksi</th>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                                <th>I</th>
                                <th>II</th>
                                <th>III</th>
                                <th>IV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($grouped)): ?>
                                <?php
                                $no = 1;
                                // Pisah teks rencana aksi menjadi daftar item (1 baris = 1 item) — samakan dgn Target Rencana Aksi.
                                $splitAksi = function ($text) {
                                    $text = trim((string) $text);
                                    if ($text === '') return [];
                                    $lines = preg_split('/\r\n|\r|\n/', $text);
                                    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
                                };
                                // Baris MONEV mengikuti SUB rencana aksi: tiap butir renaksi setinggi
                                // jumlah sub-nya (min 1). Target triwulan diambil dari sub, capaian
                                // triwulan pun disimpan per sub.
                                $subMap     = $subMap ?? [];
                                $monevSub   = $monevSub ?? [];
                                $programMap = $programMap ?? [];
                                $barisFor = function ($row) use ($splitAksi, $subMap, $programMap) {
                                    $items = $splitAksi($row['rencana_aksi'] ?? '');
                                    $subs  = $subMap[(int) ($row['target_id'] ?? 0)] ?? [];
                                    $nProg = count($programMap[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);

                                    if (empty($items)) {
                                        return [[], [1], max(1, $nProg), 1];
                                    }

                                    $perButir = [];
                                    foreach ($items as $k => $_) {
                                        $perButir[$k] = max(1, count($subs[$k] ?? []));
                                    }
                                    $barisRenaksi = array_sum($perButir);

                                    return [$items, $perButir, max($barisRenaksi, $nProg), $barisRenaksi];
                                };
                                // Total baris per OPD -> nomor & kolom OPD digabung (rowspan) per OPD (mode admin_kab).
                                $opdTotals = [];
                                if ($showOpd) {
                                    foreach ($grouped as $gr) {
                                        $ok = $gr[0]['opd_id'] ?? ($gr[0]['nama_opd'] ?? '-');
                                        $t  = 0;
                                        foreach ($gr as $grRow) {
                                            [,, $nb] = $barisFor($grRow);
                                            $t += $nb;
                                        }
                                        $opdTotals[$ok] = ($opdTotals[$ok] ?? 0) + $t;
                                    }
                                }
                                $curOpdKey = null;
                                ?>
                                <?php foreach ($grouped as $rows): ?>
                                    <?php
                                    // jumlah baris per indikator = jumlah item renaksi (min 1); total utk rowspan sasaran/pejabat/PD
                                    $indCounts = [];
                                    $sasTotal  = 0;
                                    foreach ($rows as $ri => $r) {
                                        [,, $c] = $barisFor($r);
                                        $indCounts[$ri] = $c;
                                        $sasTotal += $c;
                                    }
                                    unset($c);
                                    $printed = false;
                                    $pdPrinted = false;
                                    $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                                    $autoOpds = $isBupati ? (($autoPd ?? [])[$normSas($sasaran)] ?? []) : [];
                                    if ($isBupati && empty($autoOpds)) { // fallback: cocokkan lewat teks INDIKATOR
                                        foreach ($rows as $rr) {
                                            $ik = $normSas($rr['indikator_sasaran'] ?? '');
                                            if ($ik !== '' && !empty(($autoPd ?? [])[$ik])) {
                                                $autoOpds = $autoPd[$ik];
                                                break;
                                            }
                                        }
                                    }
                                    $opdKey  = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
                                    $newOpd  = ($showOpd && $opdKey !== $curOpdKey);
                                    ?>
                                    <?php foreach ($rows as $ri => $row): ?>
                                        <?php
                                        [$items, $barisButir, $n, $barisRenaksi] = $barisFor($row);
                                        $targetId  = (int) ($row['target_id'] ?? 0);
                                        $subsRow   = $subMap[$targetId] ?? [];
                                        $capaian   = $monevSub[$targetId] ?? [];
                                        $programs  = array_values(($programMap ?? [])[(int) ($row['pk_indikator_id'] ?? 0)] ?? []);
                                        $realisasi = ($anggaranMap ?? [])[$targetId] ?? null;
                                        // Datarkan jadi daftar baris: tiap elemen = [indeks butir, indeks sub]
                                        $barisRender = [];
                                        foreach ($barisButir as $bk => $bJumlah) {
                                            for ($bj = 0; $bj < $bJumlah; $bj++) {
                                                $barisRender[] = [$bk, $bj];
                                            }
                                        }

                                        // Program & anggaran dibagi rata lewat rowspan (sama seperti
                                        // Target & Rencana Aksi) supaya tidak ada sel kosong.
                                        $spanProgram = [];
                                        $mulaiProgram = [];
                                        if (!empty($programs)) {
                                            $sisaBaris   = $n;
                                            $sisaProgram = count($programs);
                                            $awal = 0;
                                            foreach ($programs as $pi => $_) {
                                                $span = max(1, (int) ceil($sisaBaris / max(1, $sisaProgram)));
                                                $spanProgram[$pi]   = $span;
                                                $mulaiProgram[$awal] = $pi;
                                                $awal      += $span;
                                                $sisaBaris -= $span;
                                                $sisaProgram--;
                                            }
                                        }
                                        ?>
                                        <?php for ($k = 0; $k < $n; $k++): ?>
                                            <?php [$butirIdx, $subIdx] = $barisRender[$k] ?? [null, null]; ?>
                                            <tr>
                                                <?php if ($showOpd): ?>
                                                    <?php if ($newOpd): ?>
                                                        <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>"><?= $no ?></td>
                                                        <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="text-start"><?= esc($row['nama_opd'] ?? '-') ?></td>
                                                        <?php $curOpdKey = $opdKey;
                                                        $no++;
                                                        $newOpd = false; ?>
                                                    <?php endif; ?>
                                                <?php elseif ($k === 0): ?>
                                                    <td rowspan="<?= $n ?>"><?= $no++ ?></td>
                                                <?php endif; ?>
                                                <?php if (!$printed): ?>
                                                    <?php if ($showPejabat): ?>
                                                        <td rowspan="<?= $sasTotal ?>" class="text-start">
                                                            <div class="fw-semibold"><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?></div>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= esc($eselonLabel(!empty($eselon ?? null) ? $eselon : ($rows[0]['pk_jenis'] ?? ''), $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? '')) ?></span>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                                                    <?php $printed = true; ?>
                                                <?php endif; ?>
                                                <?php if ($k === 0): ?>
                                                    <td rowspan="<?= $n ?>" class="text-start align-top"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                    <td rowspan="<?= $n ?>" class="align-top"><?= esc($row['satuan'] ?? '-') ?></td>
                                                <?php endif; ?>

                                                <?php // Program & pagu anggaran ikut PK, dibagi lewat rowspan 
                                                ?>
                                                <?php if (empty($programs)): ?>
                                                    <?php if ($k === 0): ?>
                                                        <td rowspan="<?= $n ?>" class="text-muted align-top">-</td>
                                                        <td rowspan="<?= $n ?>" class="text-muted align-top">-</td>
                                                    <?php endif; ?>
                                                <?php elseif (isset($mulaiProgram[$k])): ?>
                                                    <?php
                                                    $pi   = $mulaiProgram[$k];
                                                    $prog = $programs[$pi];
                                                    $span = $spanProgram[$pi] ?? 1;
                                                    ?>
                                                    <td rowspan="<?= $span ?>" class="text-start align-top">
                                                        <?= esc($prog['program']) ?>
                                                    </td>
                                                    <td rowspan="<?= $span ?>" class="text-end text-nowrap align-top"><?= esc($rupiah($prog['anggaran'])) ?></td>
                                                <?php endif; ?>

                                                <?php // Realisasi anggaran: PER INDIKATOR, dengan tombol aksinya sendiri 
                                                ?>
                                                <?php if ($k === 0): ?>
                                                    <?php foreach ([1, 2, 3, 4] as $q): ?>
                                                        <?php $rv = $realisasi['realisasi_triwulan_' . $q] ?? null; ?>
                                                        <td rowspan="<?= $n ?>" class="text-end text-nowrap align-top">
                                                            <?= ($rv !== null && $rv !== '') ? esc($rupiah($rv)) : '<span class="text-muted">-</span>' ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td rowspan="<?= $n ?>" class="align-top">
                                                        <?php if (empty($row['target_id'])): ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php elseif ($canWrite ?? true): ?>
                                                            <a href="<?= $baseUrl . '/anggaran/' . (int) $row['target_id'] ?>"
                                                                class="btn btn-<?= empty($realisasi) ? 'primary' : 'warning' ?> btn-sm"
                                                                title="<?= empty($realisasi) ? 'Input' : 'Edit' ?> Realisasi Anggaran">
                                                                <i class="fas fa-<?= empty($realisasi) ? 'plus' : 'edit' ?>"></i>
                                                            </a>
                                                        <?php elseif (empty($realisasi)): ?>
                                                            <span class="badge bg-light text-muted border">Belum</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Terisi</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($butirIdx === null): ?>
                                                    <?php // Sisa baris ketika program lebih banyak dari baris rencana aksi.
                                                    // Renaksi + Sub + 4 target + 4 capaian + total + Aksi = 12 kolom.
                                                    // (kolom Penanggung Jawab tidak ikut: hanya dicetak di baris k=0) 
                                                    ?>
                                                    <?php if ($k === $barisRenaksi): ?>
                                                        <td colspan="12" rowspan="<?= $n - $barisRenaksi ?>"></td>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php // Rencana Aksi membentang setinggi sub rencana aksinya 
                                                    ?>
                                                    <?php if ($subIdx === 0): ?>
                                                        <td rowspan="<?= $barisButir[$butirIdx] ?? 1 ?>" class="text-start align-top">
                                                            <?php $txt = $items[$butirIdx] ?? ''; ?>
                                                            <?= ($txt !== '') ? esc(($butirIdx + 1) . '. ' . $txt) : '<span class="text-muted">-</span>' ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php
                                                    $sub    = $subsRow[$butirIdx][$subIdx] ?? null;
                                                    $subId  = (int) ($sub['id'] ?? 0);
                                                    // Capaian menempel ke id sub; tanpa sub dipakai capaian tingkat renaksi (id 0).
                                                    $cap    = $capaian[$subId] ?? null;
                                                    ?>
                                                    <td class="text-start">
                                                        <?= $sub !== null ? esc(($subIdx + 1) . '. ' . $sub['teks']) : '<span class="text-muted">-</span>' ?>
                                                    </td>

                                                    <?php // Target Triwulan diambil dari SUB rencana aksi 
                                                    ?>
                                                    <?php foreach ([1, 2, 3, 4] as $q): ?>
                                                        <?php $tw = $sub['tw'][$q] ?? null; ?>
                                                        <td><?= ($tw !== null && $tw !== '') ? esc($tw) : '<span class="text-muted">-</span>' ?></td>
                                                    <?php endforeach; ?>

                                                    <?php // Capaian Triwulan juga per sub 
                                                    ?>
                                                    <?php foreach ([1, 2, 3, 4] as $q): ?>
                                                        <?php $cv = $cap['capaian_triwulan_' . $q] ?? null; ?>
                                                        <td><?= ($cv !== null && $cv !== '') ? esc($cv) : '<span class="text-muted">-</span>' ?></td>
                                                    <?php endforeach; ?>

                                                    <?php // Capaian Total = persentase hasil hitungan server (monev.total) ?>
                                                    <td class="text-nowrap">
                                                        <span class="fw-semibold"><?= capaianFormatPersen($cap['total'] ?? null, '<span class="text-muted fw-normal">-</span>') ?></span>
                                                        <?php if (!empty($cap['metode_perhitungan'])): ?>
                                                            <div class="text-muted" style="font-size:.7rem;"><?= esc(capaianMetodeNama($cap['metode_perhitungan'])) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <?php if ($isBupati): ?>
                                                    <?php if (!$pdPrinted): ?>
                                                        <td rowspan="<?= $sasTotal ?>" class="text-start">
                                                            <?php if (empty($autoOpds)): ?>
                                                                <span class="text-muted">Belum ditetapkan</span>
                                                            <?php else: ?>
                                                                <?php foreach ($autoOpds as $o): ?>
                                                                    <?php
                                                                    $isKecamatanPd = stripos((string) ($o['nama'] ?? ''), 'kecamatan') !== false;
                                                                    $eselonLinks = $isKecamatanPd
                                                                        ? ['administrator' => 'Eselon III', 'pengawas' => 'Eselon IV']
                                                                        : ['jpt' => 'Eselon II', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
                                                                    ?>
                                                                    <div class="mb-2">
                                                                        <span class="fw-semibold text-success align-middle"><i class="fas fa-building me-1"></i><?= esc($o['nama']) ?></span>
                                                                        <span class="ms-1">
                                                                            <?php foreach ($eselonLinks as $ek => $elabel): ?>
                                                                                <a href="<?= esc($es3Base . '?opd_id=' . (int) $o['id'] . '&eselon=' . $ek) ?>"
                                                                                    class="badge rounded-pill bg-success-subtle text-success border border-success-subtle text-decoration-none fw-normal"
                                                                                    title="Buka MONEV PK <?= esc($elabel) ?> &mdash; <?= esc($o['nama']) ?>"><?= esc($elabel) ?></a>
                                                                            <?php endforeach; ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php $pdPrinted = true; ?>
                                                    <?php endif; ?>
                                                <?php elseif ($k === 0): ?>
                                                    <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['penanggung_jawab'] ?? '-') ?></td>
                                                <?php endif; ?>
                                                <?php // Capaian diisi PER SUB, jadi tombolnya juga per baris sub.
                                                // Baris sisa (tanpa sub) sudah tertutup colspan di atas. 
                                                ?>
                                                <?php if ($butirIdx !== null): ?>
                                                    <td>
                                                        <?php if (empty($row['target_id'])): ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php elseif ($canWrite ?? true): ?>
                                                            <a href="<?= $baseUrl . '/input/' . (int) $row['target_id'] . ($subId > 0 ? '?sub=' . $subId : '') ?>"
                                                                class="btn btn-<?= empty($cap) ? 'primary' : 'warning' ?> btn-sm"
                                                                title="<?= empty($cap) ? 'Input Capaian' : 'Edit Capaian' ?><?= $sub !== null ? ' — ' . esc($sub['teks'], 'attr') : '' ?>">
                                                                <i class="fas fa-<?= empty($cap) ? 'plus' : 'edit' ?>"></i>
                                                            </a>
                                                        <?php elseif (empty($cap)): ?>
                                                            <span class="badge bg-light text-muted border">Belum</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Terisi</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endfor; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $cols ?>" class="text-muted">
                                        Belum ada Rencana Aksi PK <?= $isBupati ? 'Bupati' : 'OPD/Kecamatan' ?>.
                                        Buat dulu di menu <em>Rencana Aksi</em> sebelum mengisi capaian.
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
    </script>
</body>

</html>
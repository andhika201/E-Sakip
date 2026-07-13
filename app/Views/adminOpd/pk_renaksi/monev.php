<?php
$isBupati = ($jenis === 'bupati');
$isOpd    = !$isBupati;
$isKab    = (($role ?? '') === 'admin_kab');
$judul    = ($isBupati || !$isKab) ? 'MONEV' : 'Monitoring Capaian Rencana Aksi';
$showOpd  = ($isOpd && ($role ?? '') === 'admin_kab');
$showPejabat = $isOpd;
// Path URL bersih: bupati -> adminkab/monev & adminkab/target_renaksi;
// es3 admin_opd -> adminopd/monev & adminopd/target_renaksi; es3 admin_kab -> monev_pk/es3
$renaksiPath = ($jenis === 'bupati') ? 'adminkab/target_renaksi'
             : (($base === 'adminopd') ? 'adminopd/target_renaksi' : ($base . '/renaksi_pk/' . $jenis));
$monevPath   = ($jenis === 'bupati') ? 'adminkab/monev'
             : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl  = base_url($monevPath);

$eselonLabel = function ($pkJenis, $jabatanEselon = null, $jabatanNama = null) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Kecamatan (Eselon III)', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
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

$cols = 17 + ($showPejabat ? 1 : 0) + ($showOpd ? 1 : 0);

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
                            <?php // Mode PK Bupati disembunyikan (sementara) di MONEV admin_kab — tidak dikunci, hanya tidak ditampilkan. ?>
                            <option value="<?= base_url($base . '/monev_pk/es3') ?>" <?= $isOpd ? 'selected' : '' ?>>Mode: PK OPD (Eselon II/III/IV)</option>
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
                            <option value="jpt" <?= (($eselon ?? '') === 'jpt') ? 'selected' : '' ?>>Eselon II</option>
                            <option value="camat" <?= (($eselon ?? '') === 'camat') ? 'selected' : '' ?>>Kecamatan (Eselon III)</option>
                            <option value="administrator" <?= (($eselon ?? '') === 'administrator') ? 'selected' : '' ?>>Eselon III</option>
                            <option value="pengawas" <?= (($eselon ?? '') === 'pengawas') ? 'selected' : '' ?>>Eselon IV</option>
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
                            <th rowspan="2">Rencana Aksi</th>
                            <th rowspan="2">Baseline</th>
                            <th colspan="4">Target Triwulan</th>
                            <th colspan="4">Capaian Triwulan</th>
                            <th rowspan="2">Capaian Total</th>
                            <th rowspan="2"><?= $isBupati ? 'Penanggung Jawab Perangkat Daerah' : 'Penanggung Jawab' ?></th>
                            <th rowspan="2">Aksi</th>
                        </tr>
                        <tr>
                            <th>I</th><th>II</th><th>III</th><th>IV</th>
                            <th>I</th><th>II</th><th>III</th><th>IV</th>
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
                            // Total baris (item renaksi) per OPD -> nomor & kolom OPD digabung (rowspan) per OPD (mode admin_kab).
                            $opdTotals = [];
                            if ($showOpd) {
                                foreach ($grouped as $gr) {
                                    $ok = $gr[0]['opd_id'] ?? ($gr[0]['nama_opd'] ?? '-');
                                    $t  = 0;
                                    foreach ($gr as $grRow) { $t += max(1, count($splitAksi($grRow['rencana_aksi'] ?? ''))); }
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
                                    $c = max(1, count($splitAksi($r['rencana_aksi'] ?? '')));
                                    $indCounts[$ri] = $c;
                                    $sasTotal += $c;
                                }
                                $printed = false;
                                $pdPrinted = false;
                                $sasaran = $rows[0]['sasaran_renstra'] ?? '-';
                                $autoOpds = $isBupati ? (($autoPd ?? [])[$normSas($sasaran)] ?? []) : [];
                                if ($isBupati && empty($autoOpds)) { // fallback: cocokkan lewat teks INDIKATOR
                                    foreach ($rows as $rr) {
                                        $ik = $normSas($rr['indikator_sasaran'] ?? '');
                                        if ($ik !== '' && !empty(($autoPd ?? [])[$ik])) { $autoOpds = $autoPd[$ik]; break; }
                                    }
                                }
                                $opdKey  = $rows[0]['opd_id'] ?? ($rows[0]['nama_opd'] ?? '-');
                                $newOpd  = ($showOpd && $opdKey !== $curOpdKey);
                                ?>
                                <?php foreach ($rows as $ri => $row): ?>
                                    <?php
                                    $items  = $splitAksi($row['rencana_aksi'] ?? '');
                                    $n      = $indCounts[$ri];
                                    ?>
                                    <?php for ($k = 0; $k < $n; $k++): ?>
                                        <tr>
                                            <?php if ($showOpd): ?>
                                                <?php if ($newOpd): ?>
                                                    <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>"><?= $no ?></td>
                                                    <td rowspan="<?= $opdTotals[$opdKey] ?? $sasTotal ?>" class="text-start"><?= esc($row['nama_opd'] ?? '-') ?></td>
                                                    <?php $curOpdKey = $opdKey; $no++; $newOpd = false; ?>
                                                <?php endif; ?>
                                            <?php elseif ($k === 0): ?>
                                                <td rowspan="<?= $n ?>"><?= $no++ ?></td>
                                            <?php endif; ?>
                                            <?php if (!$printed): ?>
                                                <?php if ($showPejabat): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start">
                                                        <div class="fw-semibold"><?= esc(!empty($rows[0]['pejabat_jabatan']) ? $rows[0]['pejabat_jabatan'] : ($rows[0]['pejabat_nama'] ?? '-')) ?></div>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= esc($eselonLabel($rows[0]['pk_jenis'] ?? '', $rows[0]['pejabat_eselon'] ?? null, $rows[0]['pejabat_jabatan'] ?? '')) ?></span>
                                                    </td>
                                                <?php endif; ?>
                                                <td rowspan="<?= $sasTotal ?>" class="text-start"><?= esc($sasaran) ?></td>
                                                <?php $printed = true; ?>
                                            <?php endif; ?>
                                            <?php if ($k === 0): ?>
                                                <td rowspan="<?= $n ?>" class="text-start"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['satuan'] ?? '-') ?></td>
                                            <?php endif; ?>
                                            <td class="text-start">
                                                <?php $txt = $items[$k] ?? ''; echo ($txt !== '') ? esc($txt) : ($n === 1 ? '-' : ''); ?>
                                            </td>
                                            <?php if ($k === 0): ?>
                                                <td rowspan="<?= $n ?>"><?= esc($row['target_capaian'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['target_triwulan_1'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['target_triwulan_2'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['target_triwulan_3'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['target_triwulan_4'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['capaian_triwulan_1'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['capaian_triwulan_2'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['capaian_triwulan_3'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['capaian_triwulan_4'] ?? '-') ?></td>
                                                <td rowspan="<?= $n ?>"><?= esc($row['monev_total'] ?? '-') ?></td>
                                            <?php endif; ?>
                                            <?php if ($isBupati): ?>
                                                <?php if (!$pdPrinted): ?>
                                                    <td rowspan="<?= $sasTotal ?>" class="text-start">
                                                        <?php if (empty($autoOpds)): ?>
                                                            <span class="text-muted">Belum ditetapkan</span>
                                                        <?php else: ?>
                                                            <?php $eselonLinks = ['jpt' => 'Eselon II', 'camat' => 'Kecamatan (Eselon III)', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV']; ?>
                                                            <?php foreach ($autoOpds as $o): ?>
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
                                            <?php if ($k === 0): ?>
                                                <td rowspan="<?= $n ?>">
                                                    <?php if ($canWrite ?? true): ?>
                                                        <a href="<?= $baseUrl . '/input/' . (int) $row['target_id'] ?>"
                                                           class="btn btn-<?= empty($row['monev_id']) ? 'primary' : 'warning' ?> btn-sm"
                                                           title="<?= empty($row['monev_id']) ? 'Input Capaian' : 'Edit Capaian' ?>">
                                                            <i class="fas fa-<?= empty($row['monev_id']) ? 'plus' : 'edit' ?>"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <?php if (empty($row['monev_id'])): ?>
                                                            <span class="badge bg-light text-muted border">Belum</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Terisi</span>
                                                        <?php endif; ?>
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
                                    Belum ada Rencana Aksi PK <?= $isBupati ? 'Bupati' : 'Eselon II/III/IV' ?>.
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
        $(function () {
            if (!$.fn.select2) return;
            var base = { width: '100%', theme: 'bootstrap-5', dropdownParent: $('body') };
            $('.select2-opd').select2(Object.assign({}, base, { placeholder: 'Semua OPD' }));
            $('.select2-pejabat').select2(Object.assign({}, base, { placeholder: 'Semua Pejabat' }));
        });
    </script>
</body>

</html>

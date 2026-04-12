<!-- app/Views/adminkabupaten/rkpd/rkpd.php -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'RKPD') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .tbl-rkt-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .tbl-rkt {
            min-width: 1700px;
            border-collapse: collapse;
            font-size: 0.825rem;
            width: 100%;
        }
        .tbl-rkt thead th {
            background-color: #1a7a4a;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            padding: 10px 8px;
            font-weight: 600;
            border: 1px solid #155d38;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .tbl-rkt tbody td {
            vertical-align: middle;
            padding: 7px 10px;
            border: 1px solid #dee2e6;
            line-height: 1.4;
        }
        .tbl-rkt tbody tr:hover td { background-color: #e8f5ee; }
        .col-opd {
            background-color: #d4edda !important;
            font-weight: 600;
            text-align: left;
            white-space: normal;
            word-break: break-word;
        }
        .col-no    { text-align: center; }
        .col-thn   { text-align: center; }
        .col-wrap  { white-space: normal; word-break: break-word; text-align: left; }
        .col-ang   { text-align: right; white-space: nowrap; }
        .col-status{ text-align: center; }
        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0fdf5;
            border: 1px solid #b7e4c7;
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 12px;
            font-size: 0.85rem;
            color: #155d38;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
<div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <?php
    helper('format_helper');

    /* ---------------------------------------------------------------
     * 1. Flatten rows_grouped → satu flat array
     *    Query sudah ORDER BY nama_opd, s.id, i.id, p.id, k.id, sk.id
     *    jadi rows per-OPD, per-indikator, dst sudah KONSEKUTIF.
     * --------------------------------------------------------------- */
    $allRows = [];
    if (!empty($rows_grouped)) {
        foreach ($rows_grouped as $opdId => $rows) {
            foreach ($rows as $row) {
                $allRows[] = $row;
            }
        }
    }

    $selectedOpd    = $filter_opd   ?? 'all';
    $selectedYear   = $filter_tahun ?? date('Y');
    $totalIndikator = (int)($total_indikator ?? 0);

    /* ---------------------------------------------------------------
     * 2. Pre-kalkulasi rowspan — SATU PASS
     *    Key menggunakan indeks integer (posisi dalam array) untuk
     *    menghindari masalah encoding karakter apapun.
     *    Strategi: scan dari belakang, hitung berapa baris berturut
     *    yang punya nilai sama dengan baris saat ini.
     * --------------------------------------------------------------- */
    $n = count($allRows);

    // Array rowspan per baris (index = posisi baris)
    $rsOpd  = array_fill(0, $n, 0); // 0 = jangan print (sudah di-cover rowspan)
    $rsInd  = array_fill(0, $n, 0);
    $rsProg = array_fill(0, $n, 0);
    $rsKeg  = array_fill(0, $n, 0);
    $rsSt   = array_fill(0, $n, 0);

    // Scan dari DEPAN ke BELAKANG
    for ($i = 0; $i < $n; $i++) {
        $r = $allRows[$i];

        $opdCur  = $r['opd_id']          ?? 0;
        $indCur  = $r['indikator_id']     ?? 0;
        $progCur = $r['program_kegiatan'] ?? '-';
        $kegCur  = $r['nama_kegiatan']    ?? '-';

        $prev = $allRows[$i - 1] ?? null;

        // OPD
        if ($prev && ($prev['opd_id'] ?? 0) == $opdCur) {
            $rsOpd[$i] = 0; // di-cover row atas
        } else {
            $count = 1;
            for ($j = $i + 1; $j < $n && ($allRows[$j]['opd_id'] ?? 0) == $opdCur; $j++) {
                $count++;
            }
            $rsOpd[$i] = $count;
        }

        // Indikator (dalam OPD yang sama)
        if ($prev
            && ($prev['opd_id']      ?? 0) == $opdCur
            && ($prev['indikator_id']?? 0) == $indCur) {
            $rsInd[$i] = 0;
            $rsSt[$i] = 0;
        } else {
            $count = 1;
            for ($j = $i + 1; $j < $n
                    && ($allRows[$j]['opd_id']      ?? 0) == $opdCur
                    && ($allRows[$j]['indikator_id'] ?? 0) == $indCur; $j++) {
                $count++;
            }
            $rsInd[$i] = $count;
            $rsSt[$i]  = $count; // STATUS sama dengan indikator
        }

        // Program (dalam indikator + OPD yang sama)
        if ($prev
            && ($prev['opd_id']          ?? 0)   == $opdCur
            && ($prev['indikator_id']    ?? 0)    == $indCur
            && ($prev['program_kegiatan']?? '-')  == $progCur) {
            $rsProg[$i] = 0;
        } else {
            $count = 1;
            for ($j = $i + 1; $j < $n
                    && ($allRows[$j]['opd_id']          ?? 0)   == $opdCur
                    && ($allRows[$j]['indikator_id']    ?? 0)    == $indCur
                    && ($allRows[$j]['program_kegiatan']?? '-')  == $progCur; $j++) {
                $count++;
            }
            $rsProg[$i] = $count;
        }

        // Kegiatan (dalam program + indikator + OPD yang sama)
        if ($prev
            && ($prev['opd_id']          ?? 0)  == $opdCur
            && ($prev['indikator_id']    ?? 0)   == $indCur
            && ($prev['program_kegiatan']?? '-') == $progCur
            && ($prev['nama_kegiatan']   ?? '-') == $kegCur) {
            $rsKeg[$i] = 0;
        } else {
            $count = 1;
            for ($j = $i + 1; $j < $n
                    && ($allRows[$j]['opd_id']          ?? 0)  == $opdCur
                    && ($allRows[$j]['indikator_id']    ?? 0)   == $indCur
                    && ($allRows[$j]['program_kegiatan']?? '-') == $progCur
                    && ($allRows[$j]['nama_kegiatan']   ?? '-') == $kegCur; $j++) {
                $count++;
            }
            $rsKeg[$i] = $count;
        }
    }

    /* ---------------------------------------------------------------
     * 3. Nomor urut per indikator
     * --------------------------------------------------------------- */
    $indNoMap = [];
    $no = 1;
    foreach ($allRows as $r) {
        $ik = ($r['opd_id'] ?? 0) . '_' . ($r['indikator_id'] ?? 0);
        if (!isset($indNoMap[$ik])) $indNoMap[$ik] = $no++;
    }
    ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">

            <h2 class="h3 fw-bold text-success text-center mb-4">
                RENCANA KERJA PEMERINTAH DAERAH (RKPD)
            </h2>

            <!-- Flash -->
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- FILTER -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div class="d-flex gap-2 flex-fill">
                    <select id="opdFilter" class="form-select w-50" onchange="applyFilter()">
                        <option value="all" <?= ($selectedOpd === 'all') ? 'selected' : '' ?>>SEMUA OPD</option>
                        <?php foreach ($allOpd ?? [] as $opd): ?>
                            <option value="<?= esc($opd['id']) ?>"
                                <?= ((string)$selectedOpd === (string)$opd['id']) ? 'selected' : '' ?>>
                                <?= esc($opd['nama_opd']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="yearFilter" class="form-select w-25" onchange="applyFilter()">
                        <option value="all" <?= ($selectedYear === 'all') ? 'selected' : '' ?>>SEMUA TAHUN</option>
                        <?php foreach ($available_years ?? [] as $y): ?>
                            <option value="<?= esc($y) ?>"
                                <?= ((string)$selectedYear === (string)$y) ? 'selected' : '' ?>>
                                <?= esc($y) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button"
                            onclick="window.location.href='<?= base_url('adminkab/rkpd') ?>'"
                            class="btn btn-outline-secondary">Reset</button>
                </div>
            </div>

            <!-- Info -->
            <div class="info-bar">
                <span>📋 Total indikator: <strong><?= $totalIndikator ?></strong></span>
                <span>🔍 OPD: <strong><?= $selectedOpd === 'all' ? 'SEMUA' : esc($currentOpdName ?? '-') ?></strong>
                    &nbsp;|&nbsp;
                    Tahun: <strong><?= $selectedYear === 'all' ? 'SEMUA' : esc($selectedYear) ?></strong>
                </span>
            </div>

            <!-- TABEL -->
            <div class="tbl-rkt-wrapper">
                <table class="tbl-rkt">
                    <thead>
                        <tr>
                            <th style="min-width:160px;">SATUAN KERJA</th>
                            <th style="width:44px;">NO</th>
                            <th style="width:68px;">TAHUN</th>
                            <th style="min-width:220px;">SASARAN</th>
                            <th style="min-width:220px;">INDIKATOR SASARAN</th>
                            <th style="min-width:200px;">PROGRAM</th>
                            <th style="min-width:200px;">KEGIATAN</th>
                            <th style="min-width:200px;">SUB KEGIATAN</th>
                            <th style="min-width:220px;">INDIKATOR SASARAN SUB KEGIATAN</th>
                            <th style="min-width:90px;">TARGET</th>
                            <th style="min-width:140px;">TARGET ANGGARAN</th>
                            <th style="min-width:100px;">STATUS RKT</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($allRows)): ?>
                        <tr>
                            <td colspan="12" class="text-center p-4 text-muted">
                                Tidak ada data RKPD.<br>
                                (Hanya RKT dengan status <strong>selesai</strong> yang ditampilkan di RKPD.)
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allRows as $i => $row):
                            $ik     = ($row['opd_id'] ?? 0) . '_' . ($row['indikator_id'] ?? 0);
                            $anggar = $row['target_anggaran'] ?? 0;
                            $st     = $row['status'] ?? '-';
                        ?>
                        <tr>
                            <?php if ($rsOpd[$i] > 0): ?>
                            <td rowspan="<?= $rsOpd[$i] ?>" class="col-opd">
                                <?= esc($row['nama_opd'] ?? '-') ?>
                            </td>
                            <?php endif; ?>

                            <?php if ($rsInd[$i] > 0): ?>
                            <td rowspan="<?= $rsInd[$i] ?>" class="col-no"><?= $indNoMap[$ik] ?></td>
                            <td rowspan="<?= $rsInd[$i] ?>" class="col-thn"><?= esc($row['tahun'] ?? '-') ?></td>
                            <td rowspan="<?= $rsInd[$i] ?>" class="col-wrap"><?= esc($row['sasaran'] ?? '-') ?></td>
                            <td rowspan="<?= $rsInd[$i] ?>" class="col-wrap"><?= esc($row['indikator_sasaran'] ?? '-') ?></td>
                            <?php endif; ?>

                            <?php if ($rsProg[$i] > 0): ?>
                            <td rowspan="<?= $rsProg[$i] ?>" class="col-wrap"><?= esc($row['program_kegiatan'] ?? '-') ?></td>
                            <?php endif; ?>

                            <?php if ($rsKeg[$i] > 0): ?>
                            <td rowspan="<?= $rsKeg[$i] ?>" class="col-wrap"><?= esc($row['nama_kegiatan'] ?? '-') ?></td>
                            <?php endif; ?>

                            <td class="col-wrap"><?= esc($row['nama_subkegiatan'] ?? '-') ?></td>
                            <td class="col-wrap"><?= esc($row['indikator_sasaran_sub_kegiatan'] ?? '-') ?></td>
                            <td class="col-wrap"><?= esc($row['target'] ?? '-') ?></td>
                            <td class="col-ang">
                                <?= function_exists('formatRupiah') ? formatRupiah($anggar) : 'Rp ' . number_format((float)$anggar, 0, ',', '.') ?>
                            </td>

                            <?php if ($rsSt[$i] > 0): ?>
                            <td rowspan="<?= $rsSt[$i] ?>" class="col-status align-middle">
                                <?php if ($st === 'selesai'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                <?php elseif ($st === 'draft'): ?>
                                    <span class="badge bg-warning text-dark">Draft</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= esc(ucfirst($st)) ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
</div>

<script>
    function applyFilter() {
        const opd   = document.getElementById('opdFilter')?.value || 'all';
        const tahun = document.getElementById('yearFilter')?.value || 'all';
        const params = new URLSearchParams();
        if (opd   !== 'all') params.set('opd_id', opd);
        if (tahun !== 'all') params.set('tahun', tahun);
        const qs = params.toString();
        window.location.search = qs.length ? '?' + qs : '';
    }
</script>

</body>
</html>

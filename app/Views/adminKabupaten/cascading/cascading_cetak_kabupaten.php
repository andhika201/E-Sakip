<?php
$periodeTxt = !empty($years) ? (min($years) . ' – ' . max($years)) : '-';
$tahunAkhir = $tahun_akhir ?? (!empty($years) ? max($years) : null);
$rows       = $rows ?? [];

// =====================================================================
// Susun baris flat (getMatrix) -> pohon:
//   Misi -> Tujuan -> Sasaran -> Indikator -> Perangkat Daerah -> Program
// =====================================================================
$tree = [];
foreach ($rows as $r) {
    $mi = $r['misi_id']      ?? 0;
    $ti = $r['tujuan_id']    ?? ('t' . $mi);
    $si = $r['sasaran_id']   ?? ('s' . $ti);
    $ii = $r['indikator_id'] ?? ('i' . $si);

    if (!isset($tree[$mi])) $tree[$mi] = ['misi' => $r['misi'] ?? '-', 'tujuan' => []];
    if (!isset($tree[$mi]['tujuan'][$ti])) $tree[$mi]['tujuan'][$ti] = ['nama' => $r['tujuan_rpjmd'] ?? '-', 'sasaran' => []];
    $tj = &$tree[$mi]['tujuan'][$ti];
    if (!isset($tj['sasaran'][$si])) $tj['sasaran'][$si] = ['nama' => $r['sasaran_rpjmd'] ?? '-', 'indikator' => []];
    $ss = &$tj['sasaran'][$si];
    if (!isset($ss['indikator'][$ii])) $ss['indikator'][$ii] = [
        'nama'     => $r['indikator_sasaran'] ?? null,
        'satuan'   => $r['satuan'] ?? null,
        'baseline' => $r['baseline'] ?? null,
        'targets'  => $r['targets'] ?? [],
        'opd'      => [],
    ];
    $ind = &$ss['indikator'][$ii];
    $opdName = trim((string) ($r['nama_opd'] ?? ''));
    $opdKey  = $opdName !== '' ? $opdName : '__none__';
    if (!isset($ind['opd'][$opdKey])) $ind['opd'][$opdKey] = ['nama' => $opdName, 'programs' => []];
    $prog = trim((string) ($r['program_kegiatan'] ?? ''));
    if ($prog !== '') $ind['opd'][$opdKey]['programs'][] = $prog;
    unset($tj, $ss, $ind);
}

// Rowspan = jumlah baris leaf (1 baris = 1 program; OPD tanpa program tetap 1 baris)
$opdRows  = fn($opd) => max(1, count($opd['programs']));
$indRows  = function ($ind) use ($opdRows) { $c = 0; foreach ($ind['opd'] as $o) $c += $opdRows($o); return max(1, $c); };
$sasRows  = function ($sas) use ($indRows) { $c = 0; foreach ($sas['indikator'] as $i) $c += $indRows($i); return max(1, $c); };
$tujRows  = function ($tuj) use ($sasRows) { $c = 0; foreach ($tuj['sasaran'] as $s) $c += $sasRows($s); return max(1, $c); };
$misiRows = function ($m)   use ($tujRows) { $c = 0; foreach ($m['tujuan'] as $t) $c += $tujRows($t); return max(1, $c); };

$colCount = 6 + 2 + count($years) + 1; // Misi..PD + Satuan,KondisiAwal + Target(tahun) + KondisiAkhir
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
    <style>
        td.center, th.center, td.c, th.c { text-align: center; }
        .nowrap { white-space: nowrap; }
        .pdf-visi { font-weight: bold; font-size: 14px; margin: 4px 0 10px; color: #15311f; }
        /* Ukuran dinaikkan agar terbaca saat dicetak fisik (kertas A3 landscape) */
        table.pdf-table td, table.pdf-table th { font-size: 13px; padding: 6px 7px; }
        table.pdf-table thead th { font-size: 12px; }
        table.pdf-table td.c { vertical-align: middle; } /* angka (rowspan) di-tengah vertikal biar rapi */
        /* Tampilan bersih & profesional: nonaktifkan zebra */
        table.pdf-table tbody tr:nth-child(even) td { background: #fff; }
        /* Kolom induk (Misi/Tujuan/Sasaran): garis dalam grup dibuat SAMAR (tipis)
           & pemisah antar-grup tegas -> terlihat "menyatu" TANPA rowspan besar
           (rowspan besar memicu halaman kosong di mpdf). Garis dibiarkan ADA
           (bukan none) supaya tidak hilang/terpotong saat pindah halaman. */
        table.pdf-table td.casc-parent { border-top-color: #cfd8d1; border-bottom-color: #cfd8d1; }
        table.pdf-table td.casc-parent.grp-start { border-top-color: #6b7a70; }
    </style>
</head>

<body>
    <?php $this->setData([ // param kop via data-share (include options tak diteruskan CI4)
        'judul'      => 'Cascading Kinerja Kabupaten',
        'subjudul'   => 'Visi · Misi · Tujuan · Sasaran · Indikator · Program · Perangkat Daerah · Periode ' . $periodeTxt,
        'logoOnly'   => false,   // tampilkan teks instansi (kop profesional)
        'hideAksara' => true,    // hanya lambang Kabupaten; logo AKSARA dipakai sbg watermark
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php if (!empty($visi)): ?>
        <div class="pdf-visi">VISI : <?= esc($visi) ?></div>
    <?php endif; ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th rowspan="2" style="width:135px;">Misi</th>
                <th rowspan="2" style="width:135px;">Tujuan</th>
                <th rowspan="2" style="width:135px;">Sasaran</th>
                <th rowspan="2" style="width:125px;">Indikator</th>
                <th rowspan="2" style="width:210px;">Program</th>
                <th rowspan="2" style="width:175px;">Perangkat Daerah Penanggung Jawab</th>
                <th rowspan="2" style="width:48px;">Satuan</th>
                <th rowspan="2" style="width:66px;">Kondisi Awal</th>
                <th colspan="<?= count($years) ?>">Target</th>
                <th rowspan="2" style="width:66px;">Kondisi Akhir</th>
            </tr>
            <tr>
                <?php foreach ($years as $y): ?>
                    <th class="c nowrap" style="width:66px;"><?= esc($y) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tree)): ?>
                <tr><td colspan="<?= $colCount ?>" class="c pdf-muted">Tidak ada data cascading.</td></tr>
            <?php else: ?>
                <?php $miNo = 0; foreach ($tree as $m): $miNo++; $mSpan = $misiRows($m); $mPr = false; ?>
                    <?php $tuNo = 0; foreach ($m['tujuan'] as $t): $tuNo++; $tSpan = $tujRows($t); $tPr = false; ?>
                        <?php $saNo = 0; foreach ($t['sasaran'] as $s): $saNo++; $sSpan = $sasRows($s); $sPr = false; ?>
                            <?php
                            $inNo = 0;
                            foreach ($s['indikator'] as $ind):
                                $inNo++; $iSpan = $indRows($ind); $iPr = false;
                                $targets      = $ind['targets'];
                                $kondisiAkhir = ($tahunAkhir !== null) ? ($targets[$tahunAkhir] ?? null) : null;
                                ?>
                                <?php foreach ($ind['opd'] as $opd): $oSpan = $opdRows($opd); $oPr = false;
                                    $progs = !empty($opd['programs']) ? $opd['programs'] : [null]; ?>
                                    <?php $pNo = 0; foreach ($progs as $prog): $pNo++; ?>
                                        <tr>
                                            <?php // Misi/Tujuan/Sasaran: tulis SEKALI di baris pertama grup (tanpa rowspan) agar mpdf tak menghasilkan halaman kosong pada data besar. Indikator & Perangkat Daerah tetap di-merge (rowspan kecil, aman). ?>
                                            <td class="text-start casc-parent<?= $mPr ? '' : ' grp-start' ?>"><?php if (!$mPr): ?><?= $miNo ?>. <?= nl2br(esc($m['misi'])) ?><?php $mPr = true; endif; ?></td>
                                            <td class="text-start casc-parent<?= $tPr ? '' : ' grp-start' ?>"><?php if (!$tPr): ?><?= $tuNo ?>. <?= nl2br(esc($t['nama'])) ?><?php $tPr = true; endif; ?></td>
                                            <td class="text-start casc-parent<?= $sPr ? '' : ' grp-start' ?>"><?php if (!$sPr): ?><?= $saNo ?>. <?= nl2br(esc($s['nama'])) ?><?php $sPr = true; endif; ?></td>
                                            <?php if (!$iPr): ?><td rowspan="<?= $iSpan ?>" class="text-start"><?= $inNo ?>. <?= $ind['nama'] !== null ? nl2br(esc($ind['nama'])) : '-' ?></td><?php endif; ?>
                                            <td class="text-start"><?= $prog !== null ? $pNo . '. ' . nl2br(esc($prog)) : '-' ?></td>
                                            <?php if (!$oPr): ?><td rowspan="<?= $oSpan ?>" class="text-start"><?= $opd['nama'] !== '' ? nl2br(esc($opd['nama'])) : '-' ?></td><?php $oPr = true; endif; ?>
                                            <?php if (!$iPr): ?>
                                                <td class="c nowrap" rowspan="<?= $iSpan ?>"><?= esc($ind['satuan'] ?? '-') ?></td>
                                                <td class="c nowrap" rowspan="<?= $iSpan ?>"><?= esc($ind['baseline'] ?? '-') ?></td>
                                                <?php foreach ($years as $y): ?>
                                                    <td class="c nowrap" rowspan="<?= $iSpan ?>"><?= esc($targets[$y] ?? '-') ?></td>
                                                <?php endforeach; ?>
                                                <td class="c nowrap" rowspan="<?= $iSpan ?>"><?= esc($kondisiAkhir ?? '-') ?></td>
                                                <?php $iPr = true; ?>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>

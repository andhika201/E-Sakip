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
        .pdf-visi { font-weight: bold; font-size: 15px; margin: 4px 0 8px; color: #15311f; }
        /* Kertas A3 landscape supaya 14 kolom terbaca.
           table-layout: fixed -> lebar kolom ikut <colgroup> (persen) & tabel MENGISI PENUH lebar halaman */
        table.pdf-table { table-layout: fixed; width: 100%; }
        table.pdf-table td, table.pdf-table th { font-size: 14.5px; padding: 7px 7px; overflow-wrap: break-word; word-wrap: break-word; }
        table.pdf-table thead th { font-size: 13px; }
        /* Sel gabungan (angka/indikator/perangkat daerah/kolom induk) di-tengah vertikal */
        table.pdf-table td.c, table.pdf-table td.vmid, table.pdf-table td.casc-parent { vertical-align: middle; }
        table.pdf-table td .no { color: #d35400; font-weight: bold; } /* penomoran oranye spt contoh */
        /* Tampilan bersih & profesional: nonaktifkan zebra */
        table.pdf-table tbody tr:nth-child(even) td { background: #fff; }
        /* Kolom induk (Misi/Tujuan/Sasaran): garis dalam grup dibuat SAMAR (tipis)
           & pemisah antar-grup tegas -> terlihat "menyatu" TANPA rowspan besar
           (rowspan besar memicu halaman kosong di mpdf). Garis dibiarkan ADA
           (bukan none) supaya tidak hilang/terpotong saat pindah halaman. */
        /* Kolom induk tampil MENYATU: hilangkan garis dalam grup (tak ada kotak kosong),
           garis pemisah hanya di awal tiap grup (Misi/Tujuan/Sasaran baru). */
        table.pdf-table td.casc-parent { border-top: none; border-bottom: none; }
        table.pdf-table td.casc-parent.grp-start { border-top: 0.5px solid #6b7a70; }
    </style>
</head>

<body>
    <?php $this->setData([ // param kop via data-share (include options tak diteruskan CI4)
        'judul'      => 'Cascading Kabupaten Pringsewu ' . (!empty($years) ? (min($years) . '-' . max($years)) : ''),
        'subjudul'   => '',      // judul saja (samakan dgn contoh)
        'logoOnly'   => false,   // tampilkan teks instansi (kop profesional)
        'hideAksara' => true,    // hanya lambang Kabupaten; logo AKSARA dipakai sbg watermark
    ]); ?>
    <?= $this->include('templates/pdf_kop') ?>

    <?php if (!empty($visi)): ?>
        <div class="pdf-visi">VISI : <?= esc($visi) ?></div>
    <?php endif; ?>

    <table class="pdf-table">
        <colgroup>
            <col style="width:7.5%">   <!-- Misi -->
            <col style="width:7.5%">   <!-- Tujuan -->
            <col style="width:7.5%">   <!-- Sasaran -->
            <col style="width:8%">     <!-- Indikator -->
            <col style="width:21%">    <!-- Program (konten padat -> paling lebar) -->
            <col style="width:14%">    <!-- Perangkat Daerah -->
            <col style="width:3.5%">   <!-- Satuan -->
            <col style="width:4.5%">   <!-- Kondisi Awal -->
            <?php $yw = 21 / max(1, count($years)); foreach ($years as $y): ?><col style="width:<?= round($yw, 2) ?>%"><?php endforeach; ?>
            <col style="width:5%">     <!-- Kondisi Akhir -->
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">Misi</th>
                <th rowspan="2">Tujuan</th>
                <th rowspan="2">Sasaran</th>
                <th rowspan="2">Indikator</th>
                <th rowspan="2">Program</th>
                <th rowspan="2">Perangkat Daerah Penanggung Jawab</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Kondisi Awal</th>
                <th colspan="<?= count($years) ?>">Target</th>
                <th rowspan="2">Kondisi Akhir</th>
            </tr>
            <tr>
                <?php foreach ($years as $y): ?>
                    <th class="c"><?= esc($y) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tree)): ?>
                <tr><td colspan="<?= $colCount ?>" class="c pdf-muted">Tidak ada data cascading.</td></tr>
            <?php else: ?>
                <?php $miNo = 0; foreach ($tree as $m): $miNo++; $mSpan = $misiRows($m); $mMid = intdiv($mSpan - 1, 2); $mRow = 0; ?>
                    <?php $tuNo = 0; foreach ($m['tujuan'] as $t): $tuNo++; $tSpan = $tujRows($t); $tMid = intdiv($tSpan - 1, 2); $tRow = 0; ?>
                        <?php $saNo = 0; foreach ($t['sasaran'] as $s): $saNo++; $sSpan = $sasRows($s); $sMid = intdiv($sSpan - 1, 2); $sRow = 0; ?>
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
                                            <td class="text-start casc-parent<?= $mRow === 0 ? ' grp-start' : '' ?>"><?php if ($mRow === $mMid): ?><span class="no"><?= $miNo ?>.</span> <?= nl2br(esc($m['misi'])) ?><?php endif; $mRow++; ?></td>
                                            <td class="text-start casc-parent<?= $tRow === 0 ? ' grp-start' : '' ?>"><?php if ($tRow === $tMid): ?><span class="no"><?= $tuNo ?>.</span> <?= nl2br(esc($t['nama'])) ?><?php endif; $tRow++; ?></td>
                                            <td class="text-start casc-parent<?= $sRow === 0 ? ' grp-start' : '' ?>"><?php if ($sRow === $sMid): ?><span class="no"><?= $saNo ?>.</span> <?= nl2br(esc($s['nama'])) ?><?php endif; $sRow++; ?></td>
                                            <?php if (!$iPr): ?><td rowspan="<?= $iSpan ?>" class="text-start vmid"><span class="no"><?= $inNo ?>.</span> <?= $ind['nama'] !== null ? nl2br(esc($ind['nama'])) : '-' ?></td><?php endif; ?>
                                            <td class="text-start"><?php if ($prog !== null): ?><span class="no"><?= $pNo ?>.</span> <?= nl2br(esc($prog)) ?><?php else: ?>-<?php endif; ?></td>
                                            <?php if (!$oPr): ?><td rowspan="<?= $oSpan ?>" class="text-start vmid"><?= $opd['nama'] !== '' ? nl2br(esc($opd['nama'])) : '-' ?></td><?php $oPr = true; endif; ?>
                                            <?php if (!$iPr): ?>
                                                <td class="c" rowspan="<?= $iSpan ?>"><?= esc($ind['satuan'] ?? '-') ?></td>
                                                <td class="c" rowspan="<?= $iSpan ?>"><?= esc($ind['baseline'] ?? '-') ?></td>
                                                <?php foreach ($years as $y): ?>
                                                    <td class="c" rowspan="<?= $iSpan ?>"><?= esc($targets[$y] ?? '-') ?></td>
                                                <?php endforeach; ?>
                                                <td class="c" rowspan="<?= $iSpan ?>"><?= esc($kondisiAkhir ?? '-') ?></td>
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

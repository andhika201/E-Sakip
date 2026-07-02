<?php
/**
 * Tabel Cascading — MODE KABUPATEN (matriks RPJMD penuh, selaras Cetak Cascading):
 * Visi + Misi -> Tujuan -> Sasaran -> Indikator -> Program -> Perangkat Daerah
 * + Satuan, Kondisi Awal, Target per tahun, Kondisi Akhir, Aksi (kelola mapping).
 * Butuh: $rows (getMatrix), $years, $visi, $tahun_akhir, $filters['periode'].
 * Di layar (browser) rowspan aman dipakai penuh untuk tampilan merge yang rapi.
 */
$periode    = $filters['periode'] ?? '';
$tahunAkhir = $tahun_akhir ?? (!empty($years) ? max($years) : null);
$rows       = $rows ?? [];

// Susun flat rows -> pohon: Misi -> Tujuan -> Sasaran -> Indikator -> Perangkat Daerah -> Program
$tree = [];
foreach ($rows as $r) {
    $mi = $r['misi_id'] ?? 0;
    $ti = $r['tujuan_id'] ?? ('t' . $mi);
    $si = $r['sasaran_id'] ?? ('s' . $ti);
    $ii = $r['indikator_id'] ?? ('i' . $si);
    if (!isset($tree[$mi])) $tree[$mi] = ['misi' => $r['misi'] ?? '-', 'tujuan' => []];
    if (!isset($tree[$mi]['tujuan'][$ti])) $tree[$mi]['tujuan'][$ti] = ['nama' => $r['tujuan_rpjmd'] ?? '-', 'sasaran' => []];
    $tj = &$tree[$mi]['tujuan'][$ti];
    if (!isset($tj['sasaran'][$si])) $tj['sasaran'][$si] = ['nama' => $r['sasaran_rpjmd'] ?? '-', 'indikator' => []];
    $ss = &$tj['sasaran'][$si];
    if (!isset($ss['indikator'][$ii])) $ss['indikator'][$ii] = [
        'id'       => $r['indikator_id'] ?? null,
        'nama'     => $r['indikator_sasaran'] ?? null,
        'satuan'   => $r['satuan'] ?? null,
        'baseline' => $r['baseline'] ?? null,
        'targets'  => $r['targets'] ?? [],
        'is_mapped'=> $r['is_mapped'] ?? 0,
        'opd'      => [],
    ];
    $ind = &$ss['indikator'][$ii];
    if (!empty($r['is_mapped'])) $ind['is_mapped'] = 1;
    $opdName = trim((string) ($r['nama_opd'] ?? ''));
    $opdKey  = $opdName !== '' ? $opdName : '__none__';
    if (!isset($ind['opd'][$opdKey])) $ind['opd'][$opdKey] = ['nama' => $opdName, 'programs' => []];
    $prog = trim((string) ($r['program_kegiatan'] ?? ''));
    if ($prog !== '') $ind['opd'][$opdKey]['programs'][] = $prog;
    unset($tj, $ss, $ind);
}
$opdRows  = fn($o) => max(1, count($o['programs']));
$indRows  = function ($ind) use ($opdRows) { $c = 0; foreach ($ind['opd'] as $o) $c += $opdRows($o); return max(1, $c); };
$sasRows  = function ($s) use ($indRows) { $c = 0; foreach ($s['indikator'] as $i) $c += $indRows($i); return max(1, $c); };
$tujRows  = function ($t) use ($sasRows) { $c = 0; foreach ($t['sasaran'] as $s) $c += $sasRows($s); return max(1, $c); };
$misiRows = function ($m) use ($tujRows) { $c = 0; foreach ($m['tujuan'] as $t) $c += $tujRows($t); return max(1, $c); };

$colCount = 6 + 2 + count($years) + 2; // Misi..PD + Satuan,KondisiAwal + Target(tahun) + KondisiAkhir + Aksi
?>
<?php if (!empty($visi)): ?>
    <div class="alert alert-success py-2 px-3 mb-3 fw-bold" style="font-size:.9rem;">
        <i class="fas fa-flag-checkered me-1"></i> VISI : <?= esc($visi) ?>
    </div>
<?php endif; ?>
<div class="casc-table-wrap">
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle casc-table mb-0">
            <thead class="text-center">
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
                    <th rowspan="2">Aksi</th>
                </tr>
                <tr>
                    <?php foreach ($years as $y): ?>
                        <th><?= esc($y) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tree)): ?>
                    <tr><td colspan="<?= $colCount ?>" class="text-muted">Data tidak ditemukan.</td></tr>
                <?php else: ?>
                    <?php $miNo = 0; foreach ($tree as $m): $miNo++; $mSpan = $misiRows($m); $mPr = false; ?>
                        <?php $tuNo = 0; foreach ($m['tujuan'] as $t): $tuNo++; $tSpan = $tujRows($t); $tPr = false; ?>
                            <?php $saNo = 0; foreach ($t['sasaran'] as $s): $saNo++; $sSpan = $sasRows($s); $sPr = false; ?>
                                <?php $inNo = 0; foreach ($s['indikator'] as $ind): $inNo++; $iSpan = $indRows($ind); $iPr = false;
                                    $targets = $ind['targets']; $kondisiAkhir = ($tahunAkhir !== null) ? ($targets[$tahunAkhir] ?? null) : null; ?>
                                    <?php foreach ($ind['opd'] as $opd): $oSpan = $opdRows($opd); $oPr = false;
                                        $progs = !empty($opd['programs']) ? $opd['programs'] : [null]; ?>
                                        <?php $pNo = 0; foreach ($progs as $prog): $pNo++; ?>
                                            <tr>
                                                <?php if (!$mPr): ?><td rowspan="<?= $mSpan ?>" class="text-start"><?= $miNo ?>. <?= esc($m['misi']) ?></td><?php $mPr = true; endif; ?>
                                                <?php if (!$tPr): ?><td rowspan="<?= $tSpan ?>" class="text-start"><?= $tuNo ?>. <?= esc($t['nama']) ?></td><?php $tPr = true; endif; ?>
                                                <?php if (!$sPr): ?><td rowspan="<?= $sSpan ?>" class="text-start"><?= $saNo ?>. <?= esc($s['nama']) ?></td><?php $sPr = true; endif; ?>
                                                <?php if (!$iPr): ?><td rowspan="<?= $iSpan ?>" class="text-start"><?php if ($ind['nama'] !== null): ?><span class="ind-kode">IK</span><?= esc($ind['nama']) ?><?php else: ?><span class="text-muted">-</span><?php endif; ?></td><?php endif; ?>
                                                <td class="text-start"><?= $prog !== null ? $pNo . '. ' . esc($prog) : '<span class="text-muted">-</span>' ?></td>
                                                <?php if (!$oPr): ?><td rowspan="<?= $oSpan ?>" class="text-start"><?= $opd['nama'] !== '' ? esc($opd['nama']) : '<span class="text-muted">-</span>' ?></td><?php $oPr = true; endif; ?>
                                                <?php if (!$iPr): ?>
                                                    <td rowspan="<?= $iSpan ?>"><?= esc($ind['satuan'] ?? '-') ?></td>
                                                    <td rowspan="<?= $iSpan ?>"><?= esc($ind['baseline'] ?? '-') ?></td>
                                                    <?php foreach ($years as $y): ?>
                                                        <td rowspan="<?= $iSpan ?>"><?= esc($targets[$y] ?? '-') ?></td>
                                                    <?php endforeach; ?>
                                                    <td rowspan="<?= $iSpan ?>"><?= esc($kondisiAkhir ?? '-') ?></td>
                                                    <td rowspan="<?= $iSpan ?>">
                                                        <?php if (!empty($ind['id'])): ?>
                                                            <?php $cls = ($ind['is_mapped'] == 1) ? 'btn-warning' : 'btn-success'; ?>
                                                            <?php $ico = ($ind['is_mapped'] == 1) ? 'fa-edit' : 'fa-plus'; ?>
                                                            <a href="<?= base_url('adminkab/cascading/tambah/' . $ind['id'] . '?periode=' . esc($periode, 'url')) ?>"
                                                               class="btn <?= $cls ?> btn-sm casc-act" title="Kelola Cascading (mapping Program &amp; Perangkat Daerah)">
                                                                <i class="fas <?= $ico ?>"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
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
    </div>
</div>

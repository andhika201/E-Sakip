<?php
/**
 * Tabel Cascading — MODE KESELURUHAN (RPJMD → Renstra tiap OPD).
 * Butuh: $rows, $rowspan, $firstShow (kunci komposit sama dgn controller).
 */
$opdKey = fn($r) => ($r['sasaran_id'] ?? 'x') . '|' . ($r['opd_id'] ?? 'x');
$rtKey  = fn($r) => $opdKey($r) . '|' . ($r['renstra_tujuan_id'] ?? 'x');
$rsKey  = fn($r) => $rtKey($r) . '|' . ($r['renstra_sasaran_id'] ?? 'x');
?>
<div class="casc-table-wrap">
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle casc-table mb-0">
            <thead class="text-center">
                <tr>
                    <th>Tujuan RPJMD</th>
                    <th>Sasaran RPJMD</th>
                    <th>Perangkat Daerah</th>
                    <th>Tujuan Renstra</th>
                    <th>Sasaran Renstra</th>
                    <th>Indikator Renstra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $r): ?>
                    <tr>
                        <!-- TUJUAN RPJMD -->
                        <?php if (($firstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>" class="text-start">
                                <?= esc($r['tujuan_rpjmd'] ?? '-') ?>
                            </td>
                        <?php endif; ?>

                        <!-- SASARAN RPJMD -->
                        <?php if (($firstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="text-start">
                                <?= esc($r['sasaran_rpjmd'] ?? '-') ?>
                            </td>
                        <?php endif; ?>

                        <!-- OPD -->
                        <?php if (($firstShow['opd'][$opdKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['opd'][$opdKey($r)] ?? 1 ?>" class="text-start">
                                <?= !empty($r['nama_opd']) ? esc($r['nama_opd']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- TUJUAN RENSTRA -->
                        <?php if (($firstShow['renstra_tujuan'][$rtKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['renstra_tujuan'][$rtKey($r)] ?? 1 ?>" class="text-start">
                                <?= !empty($r['renstra_tujuan']) ? esc($r['renstra_tujuan']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- SASARAN RENSTRA -->
                        <?php if (($firstShow['renstra_sasaran'][$rsKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['renstra_sasaran'][$rsKey($r)] ?? 1 ?>" class="text-start">
                                <?= !empty($r['renstra_sasaran']) ? esc($r['renstra_sasaran']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- INDIKATOR RENSTRA -->
                        <td class="text-start">
                            <?php if (!empty($r['renstra_indikator'])): ?>
                                <span class="ind-kode">IK</span><?= esc($r['renstra_indikator']) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

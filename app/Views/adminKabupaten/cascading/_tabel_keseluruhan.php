<?php
/**
 * Tabel Cascading — MODE KESELURUHAN.
 *
 * Rantai penuh sesuai KemenPAN-RB:
 *   Tujuan RPJMD → Sasaran RPJMD → Perangkat Daerah → Tujuan Renstra
 *   → Sasaran Renstra (Eselon II) → Indikator Renstra
 *   → Eselon III → Eselon IV/JF → PELAKSANA
 *
 * Butuh: $rows, $rowspan, $firstShow (kunci komposit sama dgn controller
 * AdminKab\CascadingController::ks*Key()).
 *
 * Read-only: mode ini memantau SELURUH Perangkat Daerah, pengelolaan datanya
 * tetap di area masing-masing OPD.
 */
$K = \App\Controllers\AdminKab\CascadingController::class;

$opdKey = static fn($r) => $K::ksOpdKey($r);
$rtKey  = static fn($r) => $K::ksRtKey($r);
$rsKey  = static fn($r) => $K::ksRsKey($r);
$risKey = static fn($r) => $K::ksRisKey($r);
$e3Key  = static fn($r) => $K::ksEs3Key($r);
$i3Key  = static fn($r) => $K::ksI3Key($r);
$e4Key  = static fn($r) => $K::ksEs4Key($r);
$i4Key  = static fn($r) => $K::ksI4Key($r);
$pKey   = static fn($r) => $K::ksPelKey($r);

$kosong = '<span class="text-muted">-</span>';
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
                    <th>Sasaran ESS II</th>
                    <th>Indikator ESS II</th>
                    <th>Sasaran ESS III</th>
                    <th>Indikator ESS III</th>
                    <th>Sasaran ESS IV / JF</th>
                    <th>Indikator ESS IV</th>
                    <th><?= esc(casc_pelaksana_label('Sasaran ')) ?></th>
                    <th><?= esc(casc_pelaksana_label('Indikator ')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $r): ?>
                    <tr>
                        <!-- TUJUAN RPJMD -->
                        <?php if (($firstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['tujuan_rpjmd']) ? esc($r['tujuan_rpjmd']) : $kosong ?>
                            </td>
                        <?php endif; ?>

                        <!-- SASARAN RPJMD -->
                        <?php if (($firstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['sasaran_rpjmd']) ? esc($r['sasaran_rpjmd']) : $kosong ?>
                            </td>
                        <?php endif; ?>

                        <!-- PERANGKAT DAERAH -->
                        <?php if (($firstShow['opd'][$opdKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['opd'][$opdKey($r)] ?? 1 ?>" class="text-start">
                                <?= !empty($r['nama_opd']) ? esc($r['nama_opd']) : $kosong ?>
                            </td>
                        <?php endif; ?>

                        <!-- TUJUAN RENSTRA -->
                        <?php if (($firstShow['renstra_tujuan'][$rtKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['renstra_tujuan'][$rtKey($r)] ?? 1 ?>" class="text-start">
                                <?= !empty($r['renstra_tujuan']) ? esc($r['renstra_tujuan']) : $kosong ?>
                            </td>
                        <?php endif; ?>

                        <!-- SASARAN RENSTRA (ESS II) -->
                        <?php if (($firstShow['renstra_sasaran'][$rsKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['renstra_sasaran'][$rsKey($r)] ?? 1 ?>" class="text-start">
                                <?= !empty($r['renstra_sasaran']) ? esc($r['renstra_sasaran']) : $kosong ?>
                            </td>
                        <?php endif; ?>

                        <!-- INDIKATOR RENSTRA (ESS II) -->
                        <?php if (($firstShow['renstra_indikator'][$risKey($r)] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['renstra_indikator'][$risKey($r)] ?? 1 ?>" class="text-start">
                                <?php if (!empty($r['renstra_indikator'])): ?>
                                    <span class="ind-kode">IK</span><?= esc($r['renstra_indikator']) ?>
                                <?php else: ?>
                                    <?= $kosong ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                        <?php // ================= ESELON III ================= ?>
                        <?php if (empty($r['es3_id'])): ?>
                            <?php // colspan 6 = ESS III (2) + ESS IV (2) + Pelaksana (2) ?>
                            <?php if (($firstShow['renstra_indikator'][$risKey($r)] ?? -1) == $index): ?>
                                <td colspan="6" class="text-muted">
                                    <?= is_numeric($r['renstra_indikator_id'] ?? null) ? 'Belum ada cascade Eselon III' : '-' ?>
                                </td>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (($firstShow['es3'][$e3Key($r)] ?? -1) == $index): ?>
                                <td rowspan="<?= $rowspan['es3'][$e3Key($r)] ?? 1 ?>" class="text-start">
                                    <?= !empty($r['es3_sasaran']) ? esc($r['es3_sasaran']) : $kosong ?>
                                </td>
                            <?php endif; ?>
                            <?php if (($firstShow['es3_indikator'][$i3Key($r)] ?? -1) == $index): ?>
                                <td rowspan="<?= $rowspan['es3_indikator'][$i3Key($r)] ?? 1 ?>" class="text-start">
                                    <?php if (!empty($r['es3_indikator'])): ?>
                                        <span class="ind-kode">IK</span><?= esc($r['es3_indikator']) ?>
                                    <?php else: ?>
                                        <?= $kosong ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <?php // ================= ESELON IV / JF ================= ?>
                            <?php if (empty($r['es4_id'])): ?>
                                <?php // colspan 4 = ESS IV (2) + Pelaksana (2) ?>
                                <?php if (($firstShow['es3_indikator'][$i3Key($r)] ?? -1) == $index): ?>
                                    <td colspan="4" class="text-muted">Belum ada cascade Eselon IV</td>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (($firstShow['es4'][$e4Key($r)] ?? -1) == $index): ?>
                                    <td rowspan="<?= $rowspan['es4'][$e4Key($r)] ?? 1 ?>" class="text-start">
                                        <?= !empty($r['es4_sasaran']) ? esc($r['es4_sasaran']) : $kosong ?>
                                    </td>
                                <?php endif; ?>
                                <?php if (($firstShow['es4_indikator'][$i4Key($r)] ?? -1) == $index): ?>
                                    <td rowspan="<?= $rowspan['es4_indikator'][$i4Key($r)] ?? 1 ?>" class="text-start">
                                        <?php if (!empty($r['es4_indikator'])): ?>
                                            <span class="ind-kode">IK</span><?= esc($r['es4_indikator']) ?>
                                        <?php else: ?>
                                            <?= $kosong ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <?php // ================= PELAKSANA ================= ?>
                                <?php if (empty($r['pelaksana_id'])): ?>
                                    <?php if (($firstShow['es4_indikator'][$i4Key($r)] ?? -1) == $index): ?>
                                        <td colspan="2" class="text-muted">
                                            Belum ada cascade <?= esc(casc_pelaksana_label()) ?>
                                        </td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (($firstShow['pelaksana'][$pKey($r)] ?? -1) == $index): ?>
                                        <td rowspan="<?= $rowspan['pelaksana'][$pKey($r)] ?? 1 ?>" class="text-start">
                                            <?= !empty($r['pelaksana_sasaran']) ? esc($r['pelaksana_sasaran']) : $kosong ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="text-start">
                                        <?php if (!empty($r['pelaksana_indikator'])): ?>
                                            <span class="ind-kode">IK</span><?= esc($r['pelaksana_indikator']) ?>
                                        <?php else: ?>
                                            <?= $kosong ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

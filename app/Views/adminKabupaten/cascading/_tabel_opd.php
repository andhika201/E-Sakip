<?php
/**
 * Tabel Cascading — MODE OPD (renstra lengkap, READ-ONLY untuk admin_kab).
 * Butuh: $rows, $rowspan, $firstShow (bentuk getCascadingMatrixByOpd / opd*Meta).
 * Tidak ada aksi edit/hapus; CSF ditampilkan sebagai teks.
 */
?>
<div class="casc-table-wrap">
    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle casc-table mb-0">
            <thead class="text-center">
                <tr>
                    <th>Tujuan RPJMD</th>
                    <th>Sasaran RPJMD</th>
                    <th>Tujuan RENSTRA</th>
                    <th>Indikator Tujuan</th>
                    <th>Sasaran ESS II</th>
                    <th>Indikator ESS II</th>
                    <th>Sasaran ESS III</th>
                    <th>Indikator ESS III</th>
                    <th>Sasaran ESS IV / JF</th>
                    <th>Indikator ESS IV</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $r): ?>
                    <?php $hasIndikatorEss2 = is_numeric($r['indikator_id'] ?? null); ?>
                    <tr>
                        <!-- TUJUAN RPJMD -->
                        <?php if (($firstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['tujuan_rpjmd']) ? esc($r['tujuan_rpjmd']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- SASARAN RPJMD -->
                        <?php if (($firstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['sasaran_rpjmd']) ? esc($r['sasaran_rpjmd']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- TUJUAN RENSTRA -->
                        <?php if (($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['renstra_tujuan']) ? esc($r['renstra_tujuan']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- INDIKATOR TUJUAN -->
                        <?php if (($firstShow['indikator_tujuan'][$r['indikator_tujuan_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['indikator_tujuan'][$r['indikator_tujuan_id']] ?? 1 ?>" class="text-start">
                                <?php if (!empty($r['indikator_tujuan'])): ?>
                                    <span class="ind-kode">IK</span><?= esc($r['indikator_tujuan']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>



                        <!-- SASARAN ESS II -->
                        <?php if (($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] ?? -1) == $index): ?>
                            <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['renstra_sasaran']) ? esc($r['renstra_sasaran']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <!-- INDIKATOR ESS II -->
                        <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                                <?php if (!empty($r['indikator_sasaran'])): ?>
                                    <span class="ind-kode">IK</span><?= esc($r['indikator_sasaran']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                        <!-- ESS III -->
                        <?php if (empty($r['es3_id'])): ?>
                            <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                <td colspan="4" class="text-muted"><?= $hasIndikatorEss2 ? 'Belum ada cascade Eselon III' : '-' ?></td>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                                <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-start">
                                    <?= !empty($r['es3_sasaran']) ? esc($r['es3_sasaran']) : '<span class="text-muted">-</span>' ?>
                                </td>
                            <?php endif; ?>
                            <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                            <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>" class="text-start">
                                    <?php if (!empty($r['es3_indikator'])): ?>
                                        <span class="ind-kode">IK</span><?= esc($r['es3_indikator']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- ESS IV -->
                        <?php if (!empty($r['es3_id']) && empty($r['es4_id'])): ?>
                            <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                <td colspan="2" class="text-muted">Belum ada cascade Eselon IV</td>
                            <?php endif; ?>
                        <?php elseif (!empty($r['es4_id'])): ?>
                            <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                                <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                    <?= !empty($r['es4_sasaran']) ? esc($r['es4_sasaran']) : '<span class="text-muted">-</span>' ?>
                                </td>
                            <?php endif; ?>
                            <td class="text-start">
                                <?php if (!empty($r['es4_indikator'])): ?>
                                    <span class="ind-kode">IK</span><?= esc($r['es4_indikator']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

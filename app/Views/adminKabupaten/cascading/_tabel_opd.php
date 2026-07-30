<?php
/**
 * Tabel Cascading — MODE OPD (renstra lengkap, monitoring lintas OPD di admin_kab).
 * Butuh: $rows, $rowspan, $firstShow (bentuk getCascadingMatrixByOpd + CascadingOpdMetaTrait).
 *
 * Struktur kolom:
 *   Tujuan/Sasaran RPJMD -> Tujuan Renstra (+Indikator) -> ESS II -> ESS III
 *   -> ESS IV/JF -> PELAKSANA (Sasaran, Indikator, Aksi)
 *
 * PENGELOLAAN DATA: cascading Perangkat Daerah tetap milik OPD masing-masing.
 * Kolom "Aksi" hanya memunculkan tombol bila peran yang login memang berhak
 * mengubah cascading OPD (user_can('cascading_opd.update')) DAN tidak terikat
 * satu OPD tertentu. Untuk admin_kab / admin_inspektorat kolom itu tampil
 * sebagai penanda read-only — akses TIDAK diperlebar dari pola existing.
 */
helper('rbac');

$cascWrite = user_can('cascading_opd.update') && empty(session()->get('opd_id'));
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
                    <?php // Jenjang PELAKSANA — di bawah Eselon IV / JF ?>
                    <th><?= esc(casc_pelaksana_label('Sasaran ')) ?></th>
                    <th><?= esc(casc_pelaksana_label('Indikator ')) ?></th>
                    <th width="96"><?= esc(casc_pelaksana_label('Aksi ')) ?></th>
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
                            <?php // colspan 7 = ESS III (2) + ESS IV (2) + Pelaksana (3) ?>
                            <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                <td colspan="7" class="text-muted"><?= $hasIndikatorEss2 ? 'Belum ada cascade Eselon III' : '-' ?></td>
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

                            <!-- ESS IV + PELAKSANA -->
                            <?php if (empty($r['es4_id'])): ?>
                                <?php // colspan 5 = ESS IV (2) + Pelaksana (3) ?>
                                <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                    <td colspan="5" class="text-muted">Belum ada cascade Eselon IV</td>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                                    <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                        <?= !empty($r['es4_sasaran']) ? esc($r['es4_sasaran']) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                <?php endif; ?>

                                <?php // Indikator ESS IV butuh rowspan: satu indikator bisa
                                      // menaungi beberapa baris Pelaksana. ?>
                                <?php $keyI4 = $r['es4_id'] . '_' . ($r['es4_indikator_id'] ?? null); ?>
                                <?php if (($firstShow['es4_indikator'][$keyI4] ?? null) == $index): ?>
                                    <td rowspan="<?= $rowspan['es4_indikator'][$keyI4] ?? 1 ?>" class="text-start">
                                        <?php if (!empty($r['es4_indikator'])): ?>
                                            <span class="ind-kode">IK</span><?= esc($r['es4_indikator']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <?php // ================= PELAKSANA ================= ?>
                                <?php if (empty($r['pelaksana_id'])): ?>
                                    <?php if (($firstShow['es4_indikator'][$keyI4] ?? null) == $index): ?>
                                        <td colspan="3" class="text-center">
                                            <?php if ($cascWrite && !empty($r['es4_indikator_id'])): ?>
                                                <a href="<?= base_url('adminopd/cascading/tambah-pelaksana/' . $r['es4_indikator_id']) ?>"
                                                    class="btn btn-success btn-sm"
                                                    title="<?= esc(casc_pelaksana_label('Tambah Sasaran '), 'attr') ?>">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Belum ada cascade <?= esc(casc_pelaksana_label()) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (($firstShow['pelaksana'][$r['pelaksana_id']] ?? null) == $index): ?>
                                        <td rowspan="<?= $rowspan['pelaksana'][$r['pelaksana_id']] ?? 1 ?>" class="text-start">
                                            <?= !empty($r['pelaksana_sasaran']) ? esc($r['pelaksana_sasaran']) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                    <?php endif; ?>

                                    <td class="text-start">
                                        <?php if (!empty($r['pelaksana_indikator'])): ?>
                                            <span class="ind-kode">IK</span><?= esc($r['pelaksana_indikator']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <?php if (($firstShow['pelaksana'][$r['pelaksana_id']] ?? null) == $index): ?>
                                        <td rowspan="<?= $rowspan['pelaksana'][$r['pelaksana_id']] ?? 1 ?>" class="text-nowrap text-center">
                                            <?php if ($cascWrite): ?>
                                                <?php // Hanya Edit + Hapus. Menambah Sasaran Pelaksana LAIN pada
                                                      // indikator ES IV yang sama dilakukan dari DALAM form Edit. ?>
                                                <a href="<?= base_url('adminopd/cascading/edit-pelaksana/' . $r['pelaksana_id']) ?>"
                                                    class="btn btn-warning btn-sm casc-act"
                                                    title="<?= esc(casc_pelaksana_label('Edit Sasaran '), 'attr') ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php // Pelaksana = jenjang terakhir, tidak punya anak -> hapus selalu boleh. ?>
                                                <a href="#"
                                                    class="btn btn-danger btn-sm casc-act casc-del"
                                                    data-url="<?= base_url('adminopd/cascading/delete-pelaksana/' . $r['pelaksana_id']) ?>"
                                                    data-confirm="<?= esc('Hapus ' . casc_pelaksana_label('Sasaran ') . ' ini beserta seluruh indikatornya?', 'attr') ?>"
                                                    title="<?= esc(casc_pelaksana_label('Hapus Sasaran '), 'attr') ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <?php // Monitoring lintas OPD: pengelolaan tetap di Perangkat Daerah pemilik data. ?>
                                                <span class="badge bg-light text-secondary border" title="Pemantauan saja — cascading dikelola Perangkat Daerah">
                                                    <i class="fas fa-eye me-1"></i>Monitoring
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

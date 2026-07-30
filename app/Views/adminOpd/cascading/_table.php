<?php
/**
 * Partial tabel Cascading (dipakai halaman index & endpoint refresh AJAX).
 * Butuh: $rows, $rowspan, $firstShow.
 */
?>
<table class="table table-bordered text-center align-middle casc-table mb-0">
    <thead class="text-center">
        <tr>
            <th>Tujuan RPJMD</th>
            <th>Sasaran RPJMD</th>
            <th>Tujuan RENSTRA</th>
            <th>Indikator Tujuan</th>

            <th><?= casc_relabel('Sasaran ESS II') ?></th>
            <th><?= casc_relabel('Indikator ESS II') ?></th>

            <th><?= casc_relabel('Sasaran ESS III') ?></th>
            <th><?= casc_relabel('Indikator ESS III') ?></th>
            <th width="90"><?= casc_relabel('Aksi ESS III') ?></th>

            <th><?= casc_relabel('Sasaran ESS IV / JF') ?></th>
            <th><?= casc_relabel('Indikator ESS IV') ?></th>
            <th width="90"><?= casc_relabel('Aksi ESS IV') ?></th>

            <?php // Jenjang PELAKSANA — di bawah Eselon IV / JF ?>
            <th><?= casc_pelaksana_label('Sasaran ') ?></th>
            <th><?= casc_pelaksana_label('Indikator ') ?></th>
            <th width="90"><?= casc_pelaksana_label('Aksi ') ?></th>
        </tr>
    </thead>

    <tbody>
        <?php
        // Es3 yang MASIH punya Es4 -> tombol Hapus Es3 disembunyikan
        // (user harus menghapus Es4 di bawahnya lebih dulu).
        $es3WithEs4 = [];
        // Es4 yang MASIH punya Pelaksana -> tombol Hapus Es4 disembunyikan,
        // mengikuti aturan yang sama seperti Es3 di atasnya.
        $es4WithPel = [];
        foreach ($rows as $__r) {
            if (!empty($__r['es3_id']) && !empty($__r['es4_id'])) {
                $es3WithEs4[$__r['es3_id']] = true;
            }
            if (!empty($__r['es4_id']) && !empty($__r['pelaksana_id'])) {
                $es4WithPel[$__r['es4_id']] = true;
            }
        }
        ?>
        <?php foreach ($rows as $index => $r): ?>
            <?php $hasIndikatorEss2 = is_numeric($r['indikator_id'] ?? null); ?>
            <tr>
                <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>" class="text-start">
                        <?= !empty($r['tujuan_rpjmd']) ? esc($r['tujuan_rpjmd']) : '<span class="text-muted">-</span>' ?>
                    </td>
                <?php endif; ?>

                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>" class="text-start">
                        <?= !empty($r['sasaran_rpjmd']) ? esc($r['sasaran_rpjmd']) : '<span class="text-muted">-</span>' ?>
                    </td>
                <?php endif; ?>

                <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>" class="text-start">
                        <?= !empty($r['renstra_tujuan']) ? esc($r['renstra_tujuan']) : '<span class="text-muted">-</span>' ?>
                    </td>
                <?php endif; ?>

                <?php if (($firstShow['indikator_tujuan'][$r['indikator_tujuan_id']] ?? null) == $index): ?>
                    <td rowspan="<?= $rowspan['indikator_tujuan'][$r['indikator_tujuan_id']] ?? 1 ?>" class="text-start">
                        <?php if (!empty($r['indikator_tujuan'])): ?>
                            <span class="ind-kode">IK</span><?= esc($r['indikator_tujuan']) ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>


                <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="text-start">
                        <?= !empty($r['renstra_sasaran']) ? esc($r['renstra_sasaran']) : '<span class="text-muted">-</span>' ?>
                    </td>
                <?php endif; ?>

                <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                    <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                        <?php if (!empty($r['indikator_sasaran'])): ?>
                            <span class="ind-kode">IK</span><?= esc($r['indikator_sasaran']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <?php if (empty($r['es3_id'])): ?>
                    <?php // colspan 9 = Sasaran/Indikator/Aksi untuk ES III + ES IV + Pelaksana ?>
                    <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                        <td colspan="9" class="text-center">
                            <?php if ($hasIndikatorEss2): ?>
                                <a href="<?= base_url('adminopd/cascading/tambah-es3/' . $r['indikator_id']) ?>"
                                    class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> <?= casc_relabel('Tambah ESS III') ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
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
                                -
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <?php // AKSI ESS III: satu sel per Es3 (rowspan penuh), muncul di baris pertama Es3. ?>
                    <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                        <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-nowrap text-center">
                            <a href="<?= base_url('adminopd/cascading/edit-es3/' . $r['es3_id']) ?>"
                                class="btn btn-warning btn-sm casc-act"
                                title="<?= casc_relabel('Edit ESS III') ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php // Hapus Es3 hanya bila TIDAK ada Es4 di bawahnya (hapus Es4 dulu). ?>
                            <?php if (empty($es3WithEs4[$r['es3_id']])): ?>
                                <a href="#"
                                    class="btn btn-danger btn-sm casc-act casc-del"
                                    data-url="<?= base_url('adminopd/cascading/delete-es3/' . $r['es3_id']) ?>"
                                    data-confirm="<?= casc_relabel('Hapus Sasaran Eselon III ini beserta seluruh indikatornya?') ?>"
                                    title="<?= casc_relabel('Hapus ESS III') ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                <?php endif; ?>

                <?php // Blok Es4 + Pelaksana HANYA utk baris yang sudah punya Es3.
                      // Saat Es3 kosong, sel "Tambah ESS III" (colspan=9) sudah menutup seluruh blok kanan. ?>
                <?php if (!empty($r['es3_id'])): ?>
                    <?php if (empty($r['es4_id'])): ?>
                        <?php // colspan 6 = ES IV (3 kolom) + Pelaksana (3 kolom) ?>
                        <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                            <td colspan="6" class="text-center">
                                <?php if (!empty($r['es3_indikator_id'])): ?>
                                    <a href="<?= base_url('adminopd/cascading/tambah-es4/' . $r['es3_indikator_id']) ?>"
                                        class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                            <!-- Sasaran ES IV -->
                            <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                <?= !empty($r['es4_sasaran']) ? esc($r['es4_sasaran']) : '<span class="text-muted">-</span>' ?>
                            </td>
                        <?php endif; ?>

                        <?php // Indikator ES IV kini butuh rowspan: satu indikator bisa
                              // punya beberapa baris Pelaksana di bawahnya. ?>
                        <?php $keyI4 = $r['es4_id'] . '_' . ($r['es4_indikator_id'] ?? null); ?>
                        <?php if (($firstShow['es4_indikator'][$keyI4] ?? null) == $index): ?>
                            <td rowspan="<?= $rowspan['es4_indikator'][$keyI4] ?? 1 ?>" class="text-start">
                                <?php if (!empty($r['es4_indikator'])): ?>
                                    <span class="ind-kode">IK</span><?= esc($r['es4_indikator']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                        <?php // AKSI ESS IV: satu sel per Es4 (rowspan penuh), sejalan dgn Aksi ESS III. ?>
                        <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                            <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-nowrap text-center">
                                <a href="<?= base_url('adminopd/cascading/edit-es4/' . $r['es4_id']) ?>"
                                    class="btn btn-warning btn-sm casc-act"
                                    title="<?= casc_relabel('Edit ESS IV') ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php // Hapus Es4 hanya bila TIDAK ada Pelaksana di bawahnya. ?>
                                <?php if (empty($es4WithPel[$r['es4_id']])): ?>
                                    <a href="#"
                                        class="btn btn-danger btn-sm casc-act casc-del"
                                        data-url="<?= base_url('adminopd/cascading/delete-es4/' . $r['es4_id']) ?>"
                                        data-confirm="<?= casc_relabel('Hapus Sasaran Eselon IV ini beserta seluruh indikatornya?') ?>"
                                        title="<?= casc_relabel('Hapus ESS IV') ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                        <?php // ================= PELAKSANA ================= ?>
                        <?php if (empty($r['pelaksana_id'])): ?>
                            <?php if (($firstShow['es4_indikator'][$keyI4] ?? null) == $index): ?>
                                <td colspan="3" class="text-center">
                                    <?php if (!empty($r['es4_indikator_id'])): ?>
                                        <a href="<?= base_url('adminopd/cascading/tambah-pelaksana/' . $r['es4_indikator_id']) ?>"
                                            class="btn btn-success btn-sm"
                                            title="<?= esc(casc_pelaksana_label('Tambah Sasaran '), 'attr') ?>">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
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
                                    -
                                <?php endif; ?>
                            </td>

                            <?php if (($firstShow['pelaksana'][$r['pelaksana_id']] ?? null) == $index): ?>
                                <td rowspan="<?= $rowspan['pelaksana'][$r['pelaksana_id']] ?? 1 ?>" class="text-nowrap text-center">
                                    <?php // Hanya Edit + Hapus — sama seperti Aksi ESS III & Aksi ESS IV.
                                          // Menambah Sasaran Pelaksana LAIN pada indikator ES IV yang sama
                                          // dilakukan dari DALAM form Edit ("+ Tambah Sasaran Pelaksana lain"),
                                          // bukan lewat tombol "+" di sel ini: tombol itu dulu tetap tampil
                                          // walau data sudah ada dan berulang di tiap Sasaran Pelaksana. ?>
                                    <a href="<?= base_url('adminopd/cascading/edit-pelaksana/' . $r['pelaksana_id']) ?>"
                                        class="btn btn-warning btn-sm casc-act"
                                        title="<?= esc(casc_pelaksana_label('Edit Sasaran '), 'attr') ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#"
                                        class="btn btn-danger btn-sm casc-act casc-del"
                                        data-url="<?= base_url('adminopd/cascading/delete-pelaksana/' . $r['pelaksana_id']) ?>"
                                        data-confirm="<?= esc('Hapus ' . casc_pelaksana_label('Sasaran ') . ' ini beserta seluruh indikatornya?', 'attr') ?>"
                                        title="<?= esc(casc_pelaksana_label('Hapus Sasaran '), 'attr') ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

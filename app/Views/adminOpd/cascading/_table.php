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

            <th>Sasaran ESS II</th>
            <th>Indikator ESS II</th>

            <th>Sasaran ESS III</th>
            <th>Indikator ESS III</th>
            <th width="90">Aksi ESS III</th>

            <th>Sasaran ESS IV / JF</th>
            <th>Indikator ESS IV</th>

            <th width="90">Aksi ESS IV</th>
        </tr>
    </thead>

    <tbody>
        <?php
        // Es3 yang MASIH punya Es4 -> tombol Hapus Es3 disembunyikan
        // (user harus menghapus Es4 di bawahnya lebih dulu).
        $es3WithEs4 = [];
        foreach ($rows as $__r) {
            if (!empty($__r['es3_id']) && !empty($__r['es4_id'])) {
                $es3WithEs4[$__r['es3_id']] = true;
            }
        }
        ?>
        <?php foreach ($rows as $index => $r): ?>
            <tr>
                <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>" class="text-start">
                        <?= esc($r['tujuan_rpjmd']) ?>
                    </td>
                <?php endif; ?>

                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>" class="text-start">
                        <?= esc($r['sasaran_rpjmd']) ?>
                    </td>
                <?php endif; ?>

                <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>" class="text-start">
                        <?= esc($r['renstra_tujuan']) ?>
                    </td>
                <?php endif; ?>

                <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                    <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="text-start">
                        <?= esc($r['renstra_sasaran']) ?>
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
                    <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                        <td colspan="6" class="text-center">
                            <a href="<?= base_url('adminopd/cascading/tambah-es3/' . $r['indikator_id']) ?>"
                                class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Tambah ESS III
                            </a>
                        </td>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                        <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-start">
                            <?= esc($r['es3_sasaran']) ?>
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
                                class="btn btn-warning btn-sm casc-act casc-edit"
                                data-url="<?= base_url('adminopd/cascading/edit-es3/' . $r['es3_id']) ?>"
                                data-title="Edit Cascading Eselon III"
                                title="Edit ESS III">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php // Hapus Es3 hanya bila TIDAK ada Es4 di bawahnya (hapus Es4 dulu). ?>
                            <?php if (empty($es3WithEs4[$r['es3_id']])): ?>
                                <a href="<?= base_url('adminopd/cascading/delete-es3/' . $r['es3_id']) ?>"
                                    class="btn btn-danger btn-sm casc-act casc-del"
                                    data-url="<?= base_url('adminopd/cascading/delete-es3/' . $r['es3_id']) ?>"
                                    data-confirm="Hapus Sasaran Eselon III ini beserta seluruh indikatornya?"
                                    title="Hapus ESS III">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                <?php endif; ?>

                <?php // Blok Es4 (Sasaran/Indikator/Aksi ES IV) HANYA utk baris yang sudah punya Es3.
                      // Saat Es3 kosong, sel "Tambah ESS III" (colspan=6) sudah menutup seluruh blok kanan. ?>
                <?php if (!empty($r['es3_id'])): ?>
                    <?php if (empty($r['es4_id'])): ?>
                        <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                            <td colspan="2" class="text-center">
                                <a href="<?= base_url('adminopd/cascading/tambah-es4/' . $r['es3_indikator_id']) ?>"
                                    class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </td>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                            <!-- Sasaran ES IV -->
                            <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                <?= esc($r['es4_sasaran']) ?>
                            </td>
                        <?php endif; ?>
                        <td class="text-start">
                            <?php if (!empty($r['es4_indikator'])): ?>
                                <span class="ind-kode">IK</span><?= esc($r['es4_indikator']) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <td class="text-nowrap">
                        <?php if (!empty($r['es4_id'])): ?>
                            <a href="<?= base_url('adminopd/cascading/edit-es4/' . $r['es4_id']) ?>"
                                class="btn btn-warning btn-sm casc-act casc-edit"
                                data-url="<?= base_url('adminopd/cascading/edit-es4/' . $r['es4_id']) ?>"
                                data-title="Edit Cascading Eselon IV"
                                title="Edit ESS IV">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= base_url('adminopd/cascading/delete-es4/' . $r['es4_id']) ?>"
                                class="btn btn-danger btn-sm casc-act casc-del"
                                data-url="<?= base_url('adminopd/cascading/delete-es4/' . $r['es4_id']) ?>"
                                data-confirm="Hapus Sasaran Eselon IV ini beserta seluruh indikatornya?"
                                title="Hapus ESS IV">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

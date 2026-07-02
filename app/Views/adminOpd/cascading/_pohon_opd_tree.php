<?php
/**
 * Partial Pohon Kinerja OPD (legenda + pohon).
 * Variabel dibutuhkan dari parent view:
 *   $tree  array  hasil buildOpdTree()
 */
$tree = $tree ?? [];
// CSF disembunyikan di semua tampilan pohon OPD (admin_kab & adminOpd). Bisa di-override true.
$showCsf = $showCsf ?? false;
// Indikator diberi kode "IK" secara default (admin_kab & adminOpd).
$showKode = $showKode ?? true;
?>

<!-- LEGENDA WARNA -->
<div class="pohon-legend">
    <span class="lg-title">Keterangan:</span>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#15803d,#166534)"></span> Tujuan RPJMD</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#0f766e,#115e59)"></span> Sasaran RPJMD</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#2563eb,#1e40af)"></span> Tujuan Renstra</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#c2410c,#9a3412)"></span> Sasaran Eselon II</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#9333ea,#7e22ce)"></span> Sasaran Eselon III</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#e11d48,#be123c)"></span> Sasaran Eselon IV</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#eef2f5;border:1px solid #dbe4de"></span> Indikator Kinerja</div>
    <?php if ($showCsf): ?>
        <div class="lg-item"><span class="lg-swatch" style="background:#faf3e6;border:1px solid #ecdcb8"></span> CSF</div>
    <?php endif; ?>
</div>

<div class="tree-container text-center">
    <div class="tree" id="tree-container">
        <ul>
            <?php foreach ($tree as $tujuanRpjmd): ?>
                <li>
                    <!-- L1: Tujuan RPJMD -->
                    <div class="tree-node">
                        <div class="box-l1">
                            <div class="node-label">Tujuan RPJMD</div>
                            <?= nl2br(esc($tujuanRpjmd['nama'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($tujuanRpjmd['sasarans'])): ?>
                        <ul>
                            <?php foreach ($tujuanRpjmd['sasarans'] as $sasaranRpjmd): ?>
                                <li>
                                    <!-- L2: Sasaran RPJMD -->
                                    <div class="tree-node">
                                        <div class="box-l2">
                                            <div class="node-label">Sasaran RPJMD</div>
                                            <?= nl2br(esc($sasaranRpjmd['nama'])) ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($sasaranRpjmd['tujuan_renstras'])): ?>
                                        <ul>
                                            <?php foreach ($sasaranRpjmd['tujuan_renstras'] as $tujuanRenstra): ?>
                                                <li>
                                                    <!-- L3: Tujuan Renstra -->
                                                    <div class="tree-node">
                                                        <div class="box-l3">
                                                            <div class="node-label">Tujuan Renstra</div>
                                                            <?= nl2br(esc($tujuanRenstra['nama'])) ?>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($tujuanRenstra['es2s'])): ?>
                                                        <ul>
                                                            <?php foreach ($tujuanRenstra['es2s'] as $es2): ?>
                                                                <li>
                                                                    <!-- L4: Sasaran ESS II -->
                                                                    <div class="tree-node">
                                                                        <?php if ($showCsf && !empty($es2['csf'])): ?>
                                                                            <div class="box-csf">
                                                                                <div class="node-label" style="opacity:.8">CSF</div>
                                                                                <?= nl2br(esc($es2['csf'])) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div class="box-es2">
                                                                            <div class="node-label">Sasaran ESS II</div>
                                                                            <?= nl2br(esc($es2['nama'])) ?>
                                                                        </div>
                                                                        <?php foreach ($es2['indikators'] as $indikatorEs2): ?>
                                                                            <div class="box-iks"><?php if ($showKode): ?><span class="ind-kode">IK</span><?php endif; ?><?= nl2br(esc($indikatorEs2)) ?></div>
                                                                        <?php endforeach; ?>
                                                                    </div>

                                                                    <?php if (!empty($es2['es3s'])): ?>
                                                                        <ul>
                                                                            <?php foreach ($es2['es3s'] as $es3): ?>
                                                                                <li>
                                                                                    <!-- L5: Sasaran ESS III -->
                                                                                    <div class="tree-node">
                                                                                        <?php if ($showCsf && !empty($es3['csf'])): ?>
                                                                                            <div class="box-csf">
                                                                                                <div class="node-label" style="opacity:.8">CSF</div>
                                                                                                <?= nl2br(esc($es3['csf'])) ?>
                                                                                            </div>
                                                                                        <?php endif; ?>
                                                                                        <div class="box-es3">
                                                                                            <div class="node-label">Sasaran ESS III</div>
                                                                                            <?= nl2br(esc($es3['nama'])) ?>
                                                                                        </div>
                                                                                        <?php foreach ($es3['indikators'] as $indikatorEs3): ?>
                                                                                            <div class="box-iks"><?php if ($showKode): ?><span class="ind-kode">IK</span><?php endif; ?><?= nl2br(esc($indikatorEs3)) ?></div>
                                                                                        <?php endforeach; ?>
                                                                                    </div>

                                                                                    <?php if (!empty($es3['es4s'])): ?>
                                                                                        <ul>
                                                                                            <?php foreach ($es3['es4s'] as $es4): ?>
                                                                                                <li>
                                                                                                    <!-- L6: Sasaran ESS IV -->
                                                                                                    <div class="tree-node">
                                                                                                        <?php if ($showCsf && !empty($es4['csf'])): ?>
                                                                                                            <div class="box-csf">
                                                                                                                <div class="node-label" style="opacity:.8">CSF</div>
                                                                                                                <?= nl2br(esc($es4['csf'])) ?>
                                                                                                            </div>
                                                                                                        <?php endif; ?>
                                                                                                        <div class="box-es4">
                                                                                                            <div class="node-label">Sasaran ESS IV</div>
                                                                                                            <?= nl2br(esc($es4['nama'])) ?>
                                                                                                        </div>
                                                                                                        <?php foreach ($es4['indikators'] as $indikatorEs4): ?>
                                                                                                            <div class="box-iks"><?php if ($showKode): ?><span class="ind-kode">IK</span><?php endif; ?><?= nl2br(esc($indikatorEs4)) ?></div>
                                                                                                        <?php endforeach; ?>
                                                                                                    </div>
                                                                                                </li>
                                                                                            <?php endforeach; ?>
                                                                                        </ul>
                                                                                    <?php endif; ?>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

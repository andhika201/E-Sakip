<?php
/**
 * Partial Pohon Kinerja (legenda + pohon).
 * Variabel yang dibutuhkan dari parent view:
 *   $tree  array  hasil CascadingModel::getPohonKinerja()
 *   $visi  string visi RPJMD
 */
$tree = $tree ?? [];
$visi = $visi ?? '';
?>

<!-- LEGENDA WARNA -->
<div class="pohon-legend">
    <span class="lg-title">Keterangan:</span>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#34487d,#28365f)"></span> Visi</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#2f8579,#246b61)"></span> Misi</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#43885d,#356f4a)"></span> Tujuan RPJMD</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#e9f3ed;border:1px solid #cce3d5"></span> Indikator Tujuan</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#9a6a44,#7e5334)"></span> Sasaran RPJMD</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#fbeede;border:1px solid #f0d6b4"></span> Indikator Sasaran</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#fff7e9;border:1px solid #f0dcaf"></span> CSF</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#6f5f8a,#574a6e)"></span> Perangkat Daerah</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#e9eef7;border:1px solid #cfd9ec"></span> Program</div>
</div>

<div class="tree-container text-center">
    <div class="tree" id="tree-container">
        <ul>
            <!-- VISI (root tunggal) -->
            <li>
                <div class="tree-node" style="width:280px;">
                    <div class="box-visi">
                        <div class="node-label">Visi</div>
                        <?= !empty($visi) ? esc($visi) : '<em style="opacity:.6">Visi belum diisi</em>' ?>
                    </div>
                </div>

                <?php if (!empty($tree)): ?>
                    <ul>
                        <?php $misiNo = 0; foreach ($tree as $misi): $misiNo++; ?>
                            <li>
                                <!-- MISI -->
                                <div class="tree-node">
                                    <div class="box-misi">
                                        <div class="node-label">Misi <?= $misiNo ?></div>
                                        <?= esc($misi['misi']) ?>
                                    </div>
                                </div>

                                <?php if (!empty($misi['tujuan'])): ?>
                                    <ul>
                                        <?php foreach ($misi['tujuan'] as $tujuan): ?>
                                            <li>
                                                <!-- TUJUAN -->
                                                <div class="tree-node">
                                                    <div class="box-tujuan">
                                                        <div class="node-label">Tujuan RPJMD</div>
                                                        <?= esc($tujuan['tujuan_rpjmd']) ?>
                                                    </div>
                                                    <?php foreach ($tujuan['indikator_tujuan'] as $ikt): ?>
                                                        <div class="box-ikt"><?= esc($ikt['indikator_tujuan']) ?></div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <?php if (!empty($tujuan['sasaran'])): ?>
                                                    <ul>
                                                        <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                                                            <li>
                                                                <!-- SASARAN -->
                                                                <div class="tree-node">
                                                                    <?php if (!empty($sasaran['csf'])): ?>
                                                                        <div class="box-csf">
                                                                            <div class="node-label" style="opacity:.8">Critical Success Factor</div>
                                                                            <?= nl2br(esc($sasaran['csf'])) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="box-sasaran">
                                                                        <div class="node-label">Sasaran RPJMD</div>
                                                                        <?= nl2br(esc($sasaran['sasaran_rpjmd'])) ?>
                                                                    </div>
                                                                    <?php foreach ($sasaran['indikator_sasaran'] as $iks): ?>
                                                                        <div class="box-iks"><?= esc($iks['indikator_sasaran']) ?></div>
                                                                    <?php endforeach; ?>
                                                                </div>

                                                                <!-- CABANG OPD + PROGRAM -->
                                                                <?php if (!empty($sasaran['opd'])): ?>
                                                                    <ul>
                                                                        <?php foreach ($sasaran['opd'] as $opd): ?>
                                                                            <li>
                                                                                <div class="tree-node">
                                                                                    <div class="box-opd">
                                                                                        <div class="node-label"><i class="fas fa-building me-1"></i>Perangkat Daerah</div>
                                                                                        <?= esc($opd['nama_opd']) ?>
                                                                                    </div>
                                                                                    <?php foreach ($opd['programs'] as $prog): ?>
                                                                                        <div class="box-program"><?= esc($prog) ?></div>
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
        </ul>
    </div>
</div>

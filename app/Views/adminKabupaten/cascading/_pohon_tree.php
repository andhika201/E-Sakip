<?php
/**
 * Partial Pohon Kinerja (legenda + pohon).
 * Variabel yang dibutuhkan dari parent view:
 *   $tree  array  hasil CascadingModel::getPohonKinerja()
 *   $visi  string visi RPJMD
 */
$tree = $tree ?? [];
$visi = $visi ?? '';
// Mode Kabupaten memangkas cabang OPD/Program (hanya sampai Indikator Sasaran).
$showOpd = $showOpd ?? true;
?>

<!-- LEGENDA WARNA -->
<div class="pohon-legend">
    <span class="lg-title">Keterangan:</span>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#2f3e63,#212c46)"></span> Visi</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#1f6f68,#14524d)"></span> Misi</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#2f7d4f,#21603a)"></span> Tujuan RPJMD</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#eef2f5;border:1px solid #dbe4de"></span> Indikator Tujuan</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#8a6a3c,#654b27)"></span> Sasaran RPJMD</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#eef2f5;border:1px solid #dbe4de"></span> Indikator Sasaran</div>
    <?php if ($showOpd): ?>
        <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#565f70,#3d4553)"></span> Perangkat Daerah</div>
        <div class="lg-item"><span class="lg-swatch" style="background:#eef1f6;border:1px solid #dbe1ee"></span> Program</div>
    <?php endif; ?>
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
                                                        <div class="box-ikt"><span class="ind-kode">IK</span><?= esc($ikt['indikator_tujuan']) ?></div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <?php if (!empty($tujuan['sasaran'])): ?>
                                                    <ul>
                                                        <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                                                            <li>
                                                                <!-- SASARAN -->
                                                                <div class="tree-node">
                                                                    <div class="box-sasaran">
                                                                        <div class="node-label">Sasaran RPJMD</div>
                                                                        <?= nl2br(esc($sasaran['sasaran_rpjmd'])) ?>
                                                                    </div>
                                                                    <?php foreach ($sasaran['indikator_sasaran'] as $iks): ?>
                                                                        <div class="box-iks"><span class="ind-kode">IK</span><?= esc($iks['indikator_sasaran']) ?></div>
                                                                    <?php endforeach; ?>
                                                                </div>

                                                                <!-- CABANG OPD + PROGRAM -->
                                                                <?php if ($showOpd && !empty($sasaran['opd'])): ?>
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

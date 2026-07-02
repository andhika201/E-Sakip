<?php
/**
 * Partial Pohon Kinerja KESELURUHAN (versi Perangkat Daerah).
 * Perangkat Daerah → Tujuan Renstra → Sasaran Renstra → Indikator.
 * Visi/Misi/Tujuan RPJMD/Sasaran RPJMD TIDAK ditampilkan (permintaan user).
 * Butuh: $tree (CascadingModel::getKeseluruhanByOpd) = list [{nama_opd, tujuan:[{nama, sasaran:[{nama, indikators:[]}]}]}].
 * Wajib include _pohon_styles & _pohon_opd_styles di parent (memakai box-* keduanya).
 */
$tree = $tree ?? [];
$rootLabel = function_exists('setting') ? setting('instansi', 'Pemerintah Kabupaten Pringsewu') : 'Pemerintah Kabupaten Pringsewu';
?>

<!-- LEGENDA WARNA -->
<div class="pohon-legend">
    <span class="lg-title">Keterangan:</span>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#565f70,#3d4553)"></span> Perangkat Daerah</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#345a86,#244463)"></span> Tujuan Renstra</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#2c7c92,#1f5c6b)"></span> Sasaran Renstra</div>
    <div class="lg-item"><span class="lg-swatch" style="background:#eef2f5;border:1px solid #dbe4de"></span> Indikator</div>
</div>

<div class="tree-container text-center">
    <div class="tree" id="tree-container">
        <ul>
            <!-- ROOT: Pemerintah Daerah (bukan Visi) -->
            <li>
                <div class="tree-node" style="width:280px;">
                    <div class="box-visi">
                        <div class="node-label"><i class="fas fa-landmark me-1"></i>Pemerintah Daerah</div>
                        <?= esc($rootLabel) ?>
                    </div>
                </div>

                <?php if (!empty($tree)): ?>
                    <ul>
                        <?php foreach ($tree as $opd): ?>
                            <li>
                                <!-- PERANGKAT DAERAH -->
                                <div class="tree-node">
                                    <div class="box-opd">
                                        <div class="node-label"><i class="fas fa-building me-1"></i>Perangkat Daerah</div>
                                        <?= esc($opd['nama_opd']) ?>
                                    </div>
                                </div>

                                <?php if (!empty($opd['tujuan'])): ?>
                                    <ul>
                                        <?php foreach ($opd['tujuan'] as $tr): ?>
                                            <li>
                                                <!-- TUJUAN RENSTRA -->
                                                <div class="tree-node">
                                                    <div class="box-l3">
                                                        <div class="node-label">Tujuan Renstra</div>
                                                        <?= nl2br(esc($tr['nama'])) ?>
                                                    </div>
                                                </div>

                                                <?php if (!empty($tr['sasaran'])): ?>
                                                    <ul>
                                                        <?php foreach ($tr['sasaran'] as $sr): ?>
                                                            <li>
                                                                <!-- SASARAN RENSTRA -->
                                                                <div class="tree-node">
                                                                    <div class="box-sasaran-renstra">
                                                                        <div class="node-label">Sasaran Renstra</div>
                                                                        <?= nl2br(esc($sr['nama'])) ?>
                                                                    </div>
                                                                    <?php foreach ($sr['indikators'] as $ind): ?>
                                                                        <div class="box-iks"><span class="ind-kode">IK</span><?= nl2br(esc($ind)) ?></div>
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
        </ul>
    </div>
</div>

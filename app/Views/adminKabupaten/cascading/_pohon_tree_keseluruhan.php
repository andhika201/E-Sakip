<?php
/**
 * Partial Pohon Kinerja KESELURUHAN (versi Perangkat Daerah).
 * Perangkat Daerah → Tujuan Renstra → Sasaran Renstra (ESS II) → Indikator
 *   → Eselon III → Eselon IV/JF → PELAKSANA.
 * Visi/Misi/Tujuan RPJMD/Sasaran RPJMD TIDAK ditampilkan (permintaan user).
 * Butuh: $tree (CascadingModel::getKeseluruhanByOpd).
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
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#2c7c92,#1f5c6b)"></span> Sasaran ESS II (Renstra)</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#9333ea,#7e22ce)"></span> Sasaran ESS III</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#e11d48,#be123c)"></span> Sasaran ESS IV / JF</div>
    <div class="lg-item"><span class="lg-swatch" style="background:linear-gradient(135deg,#b45309,#92400e)"></span> <?= esc(casc_pelaksana_label('Sasaran ')) ?></div>
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
                                                                <!-- SASARAN RENSTRA (ESELON II) -->
                                                                <div class="tree-node">
                                                                    <div class="box-sasaran-renstra">
                                                                        <div class="node-label">Sasaran ESS II</div>
                                                                        <?= nl2br(esc($sr['nama'])) ?>
                                                                    </div>
                                                                    <?php foreach ($sr['indikators'] as $ind): ?>
                                                                        <div class="box-iks"><span class="ind-kode">IK</span><?= nl2br(esc($ind)) ?></div>
                                                                    <?php endforeach; ?>
                                                                </div>

                                                                <?php if (!empty($sr['es3s'])): ?>
                                                                    <ul>
                                                                        <?php foreach ($sr['es3s'] as $es3): ?>
                                                                            <li>
                                                                                <!-- ESELON III -->
                                                                                <div class="tree-node">
                                                                                    <div class="box-es3">
                                                                                        <div class="node-label">Sasaran ESS III</div>
                                                                                        <?= nl2br(esc($es3['nama'])) ?>
                                                                                    </div>
                                                                                    <?php foreach ($es3['indikators'] as $ind): ?>
                                                                                        <div class="box-iks"><span class="ind-kode">IK</span><?= nl2br(esc($ind)) ?></div>
                                                                                    <?php endforeach; ?>
                                                                                </div>

                                                                                <?php if (!empty($es3['es4s'])): ?>
                                                                                    <ul>
                                                                                        <?php foreach ($es3['es4s'] as $es4): ?>
                                                                                            <li>
                                                                                                <!-- ESELON IV / JF -->
                                                                                                <div class="tree-node">
                                                                                                    <div class="box-es4">
                                                                                                        <div class="node-label">Sasaran ESS IV / JF</div>
                                                                                                        <?= nl2br(esc($es4['nama'])) ?>
                                                                                                    </div>
                                                                                                    <?php foreach ($es4['indikators'] as $ind): ?>
                                                                                                        <div class="box-iks"><span class="ind-kode">IK</span><?= nl2br(esc($ind)) ?></div>
                                                                                                    <?php endforeach; ?>
                                                                                                </div>

                                                                                                <?php if (!empty($es4['pelaksanas'])): ?>
                                                                                                    <ul>
                                                                                                        <?php foreach ($es4['pelaksanas'] as $pel): ?>
                                                                                                            <li>
                                                                                                                <!-- PELAKSANA -->
                                                                                                                <div class="tree-node">
                                                                                                                    <div class="box-pelaksana">
                                                                                                                        <div class="node-label"><?= esc(casc_pelaksana_label('Sasaran ')) ?></div>
                                                                                                                        <?= nl2br(esc($pel['nama'])) ?>
                                                                                                                    </div>
                                                                                                                    <?php foreach ($pel['indikators'] as $ind): ?>
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

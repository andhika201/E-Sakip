<?php
/**
 * Grid breakdown target massal — AdminOpd\IkpController::breakdown.
 * Tab "tahunan": semua IKP × tahun periode; tab "bulanan": semua IKP × 12 bulan
 * tahun terpilih + TW otomatis. Simpan per baris (Enter / tombol) ke
 * POST ikp/breakdown/save (JSON).
 *
 * @var string $tab      tahunan|bulanan
 * @var array  $rekap    IkpRekapService::rekapOpd()
 * @var array  $tahunan  [ikp_id => [tahun => [target, target_teks]]]
 * @var string $kategori saringan kategori ('' = semua)
 */
$metodeSingkat = ['sum' => 'Akumulasi', 'trend_naik' => 'Posisi ↑', 'trend_turun' => 'Posisi ↓', 'trend_flat' => 'Tetap'];
$fmt4 = static fn ($v) => $v === null ? '' : ikp_fmt((float) $v, 4);
$urlTab = static fn (string $t) => $u('adminopd/ikp/breakdown', ['tab' => $t, 'tahun' => $tahun, 'kategori' => $kategori]);
?>
<?= $this->include('ikp/_kepala') ?>

<div class="ikp-info">
    <i class="fas fa-table-cells"></i>
    <div>
        <p>Isi target semua IKP sekaligus. Tekan <kbd>Enter</kbd> atau tombol <i class="fas fa-floppy-disk"></i> untuk menyimpan satu baris.
            Kolom <strong>Cek</strong> memeriksa kesesuaian menurut metode masing-masing IKP (akumulasi: dijumlah; posisi: nilai akhir).</p>
        <p class="small text-secondary mb-0">Titik = pemisah ribuan (1.250), koma = desimal (12,5). Kosong atau 0 berarti belum ada target.</p>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item"><a class="nav-link <?= $tab === 'tahunan' ? 'active bg-success' : 'text-success' ?>" href="<?= esc($urlTab('tahunan'), 'attr') ?>"><i class="fas fa-calendar me-1"></i>Tahunan <?= (int) $tahunList[0] ?>–<?= (int) end($tahunList) ?></a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'bulanan' ? 'active bg-success' : 'text-success' ?>" href="<?= esc($urlTab('bulanan'), 'attr') ?>"><i class="fas fa-calendar-days me-1"></i>Bulanan <?= (int) $tahun ?></a></li>
    </ul>
    <form method="get" action="<?= base_url('adminopd/ikp/breakdown') ?>" class="d-flex gap-2 align-items-center">
        <?php if (! empty($scope['can_pick'])): ?><input type="hidden" name="opd_id" value="<?= (int) $scope['opd_id'] ?>"><?php endif; ?>
        <input type="hidden" name="tab" value="<?= esc($tab, 'attr') ?>">
        <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
        <select name="kategori" class="form-select form-select-sm" data-no-select2 onchange="this.form.submit()" aria-label="Saring kategori">
            <option value="">Semua kategori</option>
            <?php foreach ($kategoriList as $k => $label): ?>
                <option value="<?= $k ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($rekap === []): ?>
    <div class="ikp-kosong">
        <div class="ic"><i class="fas fa-table-cells"></i></div>
        <div class="fw-bold mb-1">Belum ada IKP untuk dipecah targetnya.</div>
        <a href="<?= esc($u('adminopd/ikp'), 'attr') ?>" class="btn btn-outline-secondary btn-sm mt-2">Ke daftar IKP</a>
    </div>
<?php else: ?>
    <div class="ikp-grid-wrap">
        <table class="ikp-grid" data-no-paginate id="grid-breakdown"
               data-jenis="<?= esc($tab, 'attr') ?>" data-tahun="<?= (int) $tahun ?>"
               data-url-simpan="<?= esc($u('adminopd/ikp/breakdown/save'), 'attr') ?>">
            <thead>
                <tr>
                    <th class="lekat text-start">Indikator Kinerja Prioritas</th>
                    <?php if ($tab === 'tahunan'): ?>
                        <th>Target 5 th</th>
                        <?php foreach ($tahunList as $th): ?><th><?= (int) $th ?></th><?php endforeach; ?>
                    <?php else: ?>
                        <th>Target <?= (int) $tahun ?></th>
                        <?php for ($m = 1; $m <= 12; $m++): ?><th><?= esc(ikp_nama_bulan($m, true)) ?></th><?php endfor; ?>
                        <?php for ($q = 1; $q <= 4; $q++): ?><th class="tw">TW <?= capaianRomawi($q) ?></th><?php endfor; ?>
                    <?php endif; ?>
                    <th>Cek</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rekap as $r): ?>
                    <?php
                    $ikp    = $r['ikp'];
                    $id     = (int) $ikp['id'];
                    $metode = (string) ($ikp['metode'] ?? '');
                    $t5     = $ikp['target_5_tahun'] === null ? null : (float) $ikp['target_5_tahun'];
                    $thn    = $tahunan[$id] ?? [];
                    $awal   = $thn[$tahun - 1]['target'] ?? $ikp['baseline'];
                    $bulat  = ikp_satuan_bulat($ikp['satuan_label'] ?? '');
                    $bisa   = $bolehUbah;
                    ?>
                    <tr data-ikp="<?= $id ?>" data-metode="<?= esc($metode, 'attr') ?>"
                        data-t5="<?= $t5 === null ? '' : $t5 ?>"
                        data-baseline="<?= $ikp['baseline'] === null ? '' : (float) $ikp['baseline'] ?>"
                        data-induk="<?= $r['target_tahunan'] === null ? '' : (float) $r['target_tahunan'] ?>"
                        data-awal="<?= $awal === null ? '' : (float) $awal ?>"
                        data-bulat="<?= $bulat ? '1' : '0' ?>">
                        <td class="lekat">
                            <div class="nama"><?= esc(mb_strimwidth((string) $ikp['output_prioritas'], 0, 120, '…')) ?></div>
                            <div class="sub"><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '-') ?> ·
                                <?= $metode !== '' ? esc($metodeSingkat[$metode] ?? $metode) : '<span class="text-danger">metode belum dipilih</span>' ?></div>
                        </td>
                        <?php if ($tab === 'tahunan'): ?>
                            <td class="angka fw-bold">
                                <?= $t5 !== null ? esc(ikp_fmt($t5, 4)) : '<span class="small text-secondary fw-normal" title="' . esc((string) $ikp['target_5_tahun_teks'], 'attr') . '">uraian</span>' ?>
                            </td>
                            <?php foreach ($tahunList as $th): ?>
                                <?php $teksTh = ($thn[$th]['target'] ?? null) === null ? trim((string) ($thn[$th]['target_teks'] ?? '')) : ''; ?>
                                <td><input type="text" class="isian" data-kunci="<?= (int) $th ?>" aria-label="Target <?= (int) $th ?>"
                                           value="<?= esc($fmt4($thn[$th]['target'] ?? null), 'attr') ?>"
                                           placeholder="<?= $teksTh !== '' ? esc(mb_strimwidth($teksTh, 0, 12, '…'), 'attr') : '–' ?>"
                                           <?= $teksTh !== '' ? 'title="Uraian target: ' . esc($teksTh, 'attr') . '"' : '' ?> <?= $bisa ? '' : 'disabled' ?>></td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <td class="angka fw-bold"><?= esc(ikp_fmt($r['target_tahunan'], 4)) ?></td>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td><input type="text" class="isian" data-kunci="<?= $m ?>" aria-label="Target <?= esc(ikp_nama_bulan($m), 'attr') ?>"
                                           value="<?= esc($fmt4($r['bulan'][$m]['target']), 'attr') ?>" placeholder="–" <?= $bisa ? '' : 'disabled' ?>></td>
                            <?php endfor; ?>
                            <?php for ($q = 1; $q <= 4; $q++): ?>
                                <td class="tw" data-tw="<?= $q ?>"><?= esc(ikp_fmt($r['triwulan'][$q]['target'], 4)) ?></td>
                            <?php endfor; ?>
                        <?php endif; ?>
                        <td class="text-center sel-cek"></td>
                        <td>
                            <div class="d-flex gap-1 justify-content-center">
                                <?php if ($bisa): ?>
                                    <button type="button" class="btn btn-outline-success btn-sm tombol-bagi" title="Bagi rata" <?= $metode === '' ? 'disabled' : '' ?>><i class="fas fa-wand-magic-sparkles"></i></button>
                                    <button type="button" class="btn btn-success btn-sm tombol-simpan" title="Simpan baris (Enter)"><i class="fas fa-floppy-disk"></i></button>
                                <?php endif; ?>
                                <a href="<?= esc($u('adminopd/ikp/target/' . $id, ['tahun' => $tahun]), 'attr') ?>" class="btn btn-outline-secondary btn-sm" title="Buka target IKP ini"><i class="fas fa-up-right-from-square"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="small text-secondary mt-2">
        <?php if ($tab === 'tahunan'): ?>
            <i class="fas fa-circle-info me-1"></i>Bagi rata tahunan: akumulasi dibagi rata (sisa ke tahun awal); posisi naik/turun bertahap dari baseline ke target 5 tahun; tetap = sama tiap tahun.
        <?php else: ?>
            <i class="fas fa-circle-info me-1"></i>Bagi rata bulanan: akumulasi dibagi 12 (bilangan bulat untuk satuan orang/unit/dokumen); posisi bertahap dari target <?= (int) $tahun - 1 ?> (atau baseline) ke target <?= (int) $tahun ?>; tetap = sama tiap bulan.
        <?php endif; ?>
    </div>
<?php endif; ?>

<script defer src="<?= base_url('assets/js/adminopd/ikp/ikp-angka.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/adminopd/ikp/ikp-angka.js') ?>"></script>
<script defer src="<?= base_url('assets/js/adminopd/ikp/ikp-breakdown.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/adminopd/ikp/ikp-breakdown.js') ?>"></script>

<?= $this->include('templates/shell_bawah') ?>

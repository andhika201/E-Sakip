<?php
/**
 * Target satu IKP: langkah 1 target tahunan (periode RPJMD) terhadap target
 * 5 tahun, langkah 2 target 12 bulan tahun terpilih terhadap target tahunan,
 * dengan rekap TW otomatis. Simpan: POST ikp/target/{id}/save (JSON).
 *
 * @var array $ikp      baris IkpRekapService::satu()
 * @var array $rekap    IkpRekapService::rekapSatu() untuk $tahun
 * @var array $tahunan  [tahun => [target, target_teks]]
 * @var bool  $bulat    satuan tak terbagi -> Bagi Rata bilangan bulat
 */
$js      = static fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$id      = (int) $ikp['id'];
$metode  = (string) ($ikp['metode'] ?? '');
$kat     = (string) $ikp['kategori'];
$meta    = $kategoriMeta[$kat] ?? ['ikon' => 'fa-tag', 'singkat' => $kat];
$jelas   = $metodeJelas[$metode] ?? null;
$t5      = $ikp['target_5_tahun'] === null ? null : (float) $ikp['target_5_tahun'];
$baseline = $ikp['baseline'] === null ? null : (float) $ikp['baseline'];
$fmt4    = static fn ($v) => $v === null ? '' : ikp_fmt((float) $v, 4);
$aturanTahunan = [
    'sum'         => 'Jumlah target 5 tahun harus sama dengan target 5 tahun.',
    'trend_naik'  => 'Target tahun terakhir harus sama dengan target 5 tahun, dan tidak boleh turun dari tahun ke tahun.',
    'trend_turun' => 'Target tahun terakhir harus sama dengan target 5 tahun, dan tidak boleh naik dari tahun ke tahun.',
    'trend_flat'  => 'Setiap tahun bernilai sama dengan target 5 tahun.',
][$metode] ?? 'Pilih metode perhitungan dulu agar target bisa diperiksa.';
$aturanBulanan = [
    'sum'         => 'Jumlah 12 bulan harus sama dengan target tahunan. Triwulan = jumlah 3 bulannya.',
    'trend_naik'  => 'Target Desember harus sama dengan target tahunan dan tidak boleh turun. Triwulan = posisi bulan terakhirnya.',
    'trend_turun' => 'Target Desember harus sama dengan target tahunan dan tidak boleh naik. Triwulan = posisi bulan terakhirnya.',
    'trend_flat'  => 'Setiap bulan bernilai sama dengan target tahunan.',
][$metode] ?? '';
$awalBulanan = $tahunan[$tahun - 1]['target'] ?? $baseline;
$konfig = [
    'urlSimpan' => $u('adminopd/ikp/target/' . $id . '/save'),
    'metode'    => $metode,
    't5'        => $t5,
    'baseline'  => $baseline,
    'bulat'     => $bulat,
    'tahun'     => $tahun,
    'tahunList' => $tahunList,
];
?>
<?= $this->include('ikp/_kepala') ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <a href="<?= esc($u('adminopd/ikp', ['tahun' => $tahun]), 'attr') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Daftar IKP</a>
    <div class="d-flex gap-2">
        <?php if ($bolehUbah): ?>
            <a href="<?= esc($u('adminopd/ikp/edit/' . $id), 'attr') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-pen me-1"></i>Ubah IKP</a>
        <?php endif; ?>
        <a href="<?= esc($u('adminopd/ikp/realisasi', ['tahun' => $tahun]), 'attr') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-pen-to-square me-1"></i>Isi realisasi</a>
    </div>
</div>

<!-- Ringkasan IKP -->
<div class="ikp-langkah">
    <div class="hd">
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap gap-1 mb-1">
                <span class="ikp-kat <?= esc($kat, 'attr') ?>"><i class="fas <?= $meta['ikon'] ?>"></i><?= esc($meta['singkat']) ?></span>
                <?php if (! empty($ikp['pu_nama'])): ?>
                    <span class="ikp-pu" style="--pu: <?= esc($ikp['pu_warna'] ?: '#00743e', 'attr') ?>"><i class="fas <?= esc($ikp['pu_ikon'] ?: 'fa-star', 'attr') ?>"></i><?= esc($ikp['pu_nama']) ?></span>
                <?php endif; ?>
            </div>
            <h3><?= esc($ikp['output_prioritas']) ?></h3>
            <?php if (! empty($ikp['program_opd'])): ?><p><i class="fas fa-folder-open me-1"></i><?= esc($ikp['program_opd']) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="bd">
        <div class="ikp-ringkas-ikp">
            <div class="it"><div class="l">Satuan</div><div class="v"><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '-') ?></div></div>
            <div class="it"><div class="l">Metode</div><div class="v"><?= esc($jelas['judul'] ?? 'Belum dipilih') ?></div></div>
            <div class="it"><div class="l">Baseline</div><div class="v"><?= esc(ikp_fmt($baseline, 4)) ?></div></div>
            <div class="it"><div class="l">Target 5 tahun</div><div class="v"><?= $t5 !== null ? esc(ikp_fmt($t5, 4)) : esc($ikp['target_5_tahun_teks'] ?: '-') ?></div></div>
            <div class="it"><div class="l">Penanggung jawab</div><div class="v"><?= esc($ikp['pj_nama'] ?? '-') ?></div></div>
        </div>
    </div>
</div>

<?php if ($jelas): ?>
    <div class="ikp-info">
        <i class="fas fa-calculator"></i>
        <div>
            <p><strong>Metode <?= esc($jelas['judul']) ?>.</strong> <?= esc($jelas['isi']) ?></p>
            <p class="small text-secondary mb-0">Contoh: <?= esc($jelas['contoh']) ?>. Pemeriksaan di bawah hanya peringatan — target tetap tersimpan walau belum sesuai.</p>
        </div>
    </div>
<?php else: ?>
    <div class="ikp-info kuning">
        <i class="fas fa-triangle-exclamation"></i>
        <div><p><strong>Metode perhitungan belum dipilih.</strong> Tanpa metode, sistem tidak tahu apakah angka bulanan dijumlah atau diambil posisinya, sehingga Bagi Rata, pemeriksaan, dan rekap triwulan tidak dapat dihitung.
            <?php if ($bolehUbah): ?><a href="<?= esc($u('adminopd/ikp/edit/' . $id), 'attr') ?>">Pilih metode sekarang</a>.<?php endif; ?></p></div>
    </div>
<?php endif; ?>

<div id="ikp-target" data-konfig="<?= esc($js($konfig), 'attr') ?>">

    <!-- Langkah 1: tahunan -->
    <section class="ikp-langkah" id="langkah-tahunan">
        <div class="hd">
            <span class="no">1</span>
            <div class="flex-grow-1">
                <h3>Target tahunan <?= (int) $tahunList[0] ?>–<?= (int) end($tahunList) ?></h3>
                <p><?= esc($aturanTahunan) ?></p>
            </div>
            <div class="small text-secondary">Target 5 tahun: <strong class="text-dark"><?= $t5 !== null ? esc(ikp_fmt($t5, 4)) : 'bukan angka' ?></strong></div>
        </div>
        <div class="bd">
            <div class="ikp-sel-grid thn">
                <?php foreach ($tahunList as $th): $isi = $tahunan[$th] ?? []; ?>
                    <div class="ikp-sel<?= (int) $th === (int) $tahun ? ' pilih' : '' ?>">
                        <label for="thn-<?= (int) $th ?>"><?= (int) $th ?><?= (int) $th === (int) $tahun ? ' · dipilih' : '' ?></label>
                        <input type="text" class="isian" id="thn-<?= (int) $th ?>" data-tahun="<?= (int) $th ?>"
                               value="<?= esc($fmt4($isi['target'] ?? null), 'attr') ?>"
                               placeholder="<?= ! empty($isi['target_teks']) && ($isi['target'] ?? null) === null ? esc(mb_strimwidth((string) $isi['target_teks'], 0, 14, '…'), 'attr') : '–' ?>" <?= $bolehUbah ? '' : 'disabled' ?>
                               <?= ! empty($isi['target_teks']) && ($isi['target'] ?? null) === null ? 'title="Teks asal: ' . esc($isi['target_teks'], 'attr') . '"' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="ikp-hasil-cek">
                <span id="cek-tahunan"></span>
                <span class="small text-secondary" id="cek-tahunan-pesan"></span>
                <?php if ($bolehUbah): ?>
                    <span class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" id="bagi-tahunan" <?= ($t5 === null || $metode === '') ? 'disabled title="Butuh target 5 tahun berupa angka dan metode"' : '' ?>>
                            <i class="fas fa-wand-magic-sparkles me-1"></i>Bagi rata
                        </button>
                        <button type="button" class="btn btn-success btn-sm" id="simpan-tahunan"><i class="fas fa-floppy-disk me-1"></i>Simpan target tahunan</button>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Langkah 2: bulanan -->
    <section class="ikp-langkah" id="langkah-bulanan">
        <div class="hd">
            <span class="no">2</span>
            <div class="flex-grow-1">
                <h3>Target bulanan <?= (int) $tahun ?></h3>
                <p><?= esc($aturanBulanan) ?></p>
            </div>
            <nav class="ikp-tahun" aria-label="Tahun target bulanan">
                <?php foreach ($tahunList as $th): ?>
                    <a href="<?= esc($u('adminopd/ikp/target/' . $id, ['tahun' => $th]), 'attr') ?>" class="<?= (int) $th === (int) $tahun ? 'aktif' : '' ?> pindah-tahun"><?= (int) $th ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="bd">
            <div class="small text-secondary mb-2">
                Target tahunan <?= (int) $tahun ?>: <strong class="text-dark" id="induk-bulanan"><?= esc(ikp_fmt($tahunan[$tahun]['target'] ?? null, 4)) ?></strong>
                <?php if (in_array($metode, ['trend_naik', 'trend_turun'], true)): ?>
                    · Bagi rata dimulai dari <?= isset($tahunan[$tahun - 1]['target']) ? 'target ' . ($tahun - 1) : 'baseline' ?>:
                    <strong class="text-dark"><?= esc(ikp_fmt($awalBulanan, 4)) ?></strong>
                <?php endif; ?>
            </div>
            <div class="ikp-sel-grid bln">
                <?php for ($m = 1; $m <= 12; $m++): $b = $rekap['bulan'][$m] ?? []; ?>
                    <div class="ikp-sel">
                        <label for="bln-<?= $m ?>"><?= esc(ikp_nama_bulan($m)) ?></label>
                        <input type="text" class="isian" id="bln-<?= $m ?>" data-bulan="<?= $m ?>"
                               value="<?= esc($fmt4($b['target'] ?? null), 'attr') ?>" placeholder="–" <?= $bolehUbah ? '' : 'disabled' ?>>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="small fw-bold text-secondary mt-3 mb-1"><i class="fas fa-calculator me-1"></i>Rekap triwulan (dihitung otomatis dari bulan)</div>
            <div class="ikp-sel-grid tw4">
                <?php for ($q = 1; $q <= 4; $q++): ?>
                    <div class="ikp-sel tw">
                        <label>TW <?= capaianRomawi($q) ?></label>
                        <div class="nilai" id="tw-<?= $q ?>"><?= esc(ikp_fmt($rekap['triwulan'][$q]['target'] ?? null, 4)) ?></div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="ikp-hasil-cek">
                <span id="cek-bulanan"></span>
                <span class="small text-secondary" id="cek-bulanan-pesan"></span>
                <?php if ($bolehUbah): ?>
                    <span class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" id="bagi-bulanan" <?= $metode === '' ? 'disabled' : '' ?>>
                            <i class="fas fa-wand-magic-sparkles me-1"></i>Bagi rata
                        </button>
                        <button type="button" class="btn btn-success btn-sm" id="simpan-bulanan"><i class="fas fa-floppy-disk me-1"></i>Simpan target bulanan</button>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($bulat): ?>
                <div class="small text-secondary mt-2"><i class="fas fa-circle-info me-1"></i>Satuan <?= esc($ikp['satuan_label']) ?> tidak terbagi: Bagi Rata menghasilkan bilangan bulat dan sisa pembagian ditaruh di bulan/tahun awal.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script defer src="<?= base_url('assets/js/adminopd/ikp/ikp-angka.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/adminopd/ikp/ikp-angka.js') ?>"></script>
<script defer src="<?= base_url('assets/js/adminopd/ikp/ikp-target.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/adminopd/ikp/ikp-target.js') ?>"></script>

<?= $this->include('templates/shell_bawah') ?>

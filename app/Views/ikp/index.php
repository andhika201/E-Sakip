<?php
/**
 * Daftar IKP (Indikator Kinerja Prioritas) satu OPD — AdminOpd\IkpController::index.
 *
 * @var array    $baris    elemen IkpRekapService::rekapOpd() (sudah tersaring)
 * @var array    $ringkas  IkpRekapService::ringkasOpd() (seluruh IKP, tanpa saringan)
 * @var array    $filter   kategori, pu, q
 * @var bool     $adaFilter
 * @var array    $daftarPu
 * @var array    $kategoriList, $kategoriMeta, $metodeList, $metodeJelas
 * @var int      $tahun
 * @var callable $u        pembentuk URL (membawa opd_id untuk super admin)
 */
$bulanTerakhir = $ringkas['bulan_terakhir'] ? ikp_nama_bulan((int) $ringkas['bulan_terakhir']) : null;
$perhatian     = (int) $ringkas['kuning'] + (int) $ringkas['merah'];
$katAktif      = $filter['kategori'];
$metodeSingkat = ['sum' => 'Akumulasi', 'trend_naik' => 'Posisi ↑', 'trend_turun' => 'Posisi ↓', 'trend_flat' => 'Tetap'];
?>
<?= $this->include('ikp/_kepala') ?>

<div class="ikp-info">
    <i class="fas fa-circle-info"></i>
    <div>
        <p><strong>IKP</strong> adalah ukuran kinerja yang terukur setiap bulan dari Program Unggulan Bupati,
            program prioritas, penugasan khusus, dan penugasan tambahan.</p>
        <p class="small text-secondary">Alur kerja: <strong>1.</strong> daftarkan indikator
            → <strong>2.</strong> pecah target 5 tahun menjadi tahunan dan bulanan
            → <strong>3.</strong> isi realisasi setiap bulan → <strong>4.</strong> rekap triwulan dihitung otomatis.</p>
    </div>
</div>

<!-- ===== Kartu ringkasan ===== -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:#00743e"><i class="fas fa-bullseye"></i></span><span class="kt">Jumlah IKP</span></div>
            <div class="kn"><?= (int) $ringkas['jumlah_ikp'] ?></div>
            <div class="ikp-kat-list">
                <?php foreach ($kategoriList as $k => $label): ?>
                    <a class="ikp-kat <?= $k ?>" href="<?= esc($u('adminopd/ikp', ['tahun' => $tahun, 'kategori' => $k]), 'attr') ?>" title="Tampilkan <?= esc($label, 'attr') ?>">
                        <i class="fas <?= $kategoriMeta[$k]['ikon'] ?>"></i><?= esc(['program_unggulan' => 'Unggulan', 'program_prioritas' => 'Prioritas', 'penugasan_khusus' => 'Khusus', 'penugasan_tambahan' => 'Tambahan'][$k] ?? $label) ?>
                        <span class="n"><?= (int) ($ringkas['per_kategori'][$k] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:#0a8f50"><i class="fas fa-gauge-high"></i></span><span class="kt">Capaian rata-rata <?= (int) $tahun ?></span></div>
            <div class="kn"><?= $ringkas['rata_capaian'] === null ? '–' : esc(capaianFormatPersen($ringkas['rata_capaian'])) ?></div>
            <div class="ks">
                <?php if ($ringkas['rata_capaian'] === null): ?>
                    Belum ada realisasi yang bisa dinilai pada tahun ini.
                <?php else: ?>
                    Rata-rata <?= (int) $ringkas['terisi_realisasi'] ?> IKP yang sudah berealisasi<?= $bulanTerakhir ? ', data s.d. ' . esc($bulanTerakhir) : '' ?>.
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:<?= $perhatian > 0 ? '#e07b39' : '#6eab11' ?>"><i class="fas fa-triangle-exclamation"></i></span><span class="kt">Perlu perhatian</span></div>
            <div class="kn"><?= $perhatian ?> <small>IKP</small></div>
            <div class="ks">
                <span class="me-2"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:<?= dash_color('hijau')['hex'] ?>"></span><?= (int) $ringkas['hijau'] ?> tercapai</span>
                <span class="me-2"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:<?= dash_color('kuning')['hex'] ?>"></span><?= (int) $ringkas['kuning'] ?> mendekati</span>
                <span class="me-2"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:<?= dash_color('merah')['hex'] ?>"></span><?= (int) $ringkas['merah'] ?> kritis</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:<?= dash_color('abu')['hex'] ?>"></span><?= (int) $ringkas['abu'] ?> belum dinilai</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:#3f6296"><i class="fas fa-table-cells"></i></span><span class="kt">Breakdown lengkap</span></div>
            <div class="kn"><?= (int) $ringkas['lengkap_breakdown'] ?> <small>/ <?= (int) $ringkas['jumlah_ikp'] ?></small></div>
            <div class="ks">IKP yang target tahunan 5 tahun &amp; 12 target bulanan <?= (int) $tahun ?>-nya sudah terisi.
                <a href="<?= esc($u('adminopd/ikp/breakdown', ['tahun' => $tahun]), 'attr') ?>">Lengkapi&nbsp;›</a></div>
        </div>
    </div>
</div>

<!-- ===== Saringan & tombol ===== -->
<div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
    <form method="get" action="<?= base_url('adminopd/ikp') ?>" class="row g-2 align-items-end flex-grow-1" style="max-width: 900px;">
        <?php if (! empty($scope['can_pick'])): ?><input type="hidden" name="opd_id" value="<?= (int) $scope['opd_id'] ?>"><?php endif; ?>
        <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="f-kategori">Kategori</label>
            <select name="kategori" id="f-kategori" class="form-select form-select-sm">
                <option value="">Semua kategori</option>
                <?php foreach ($kategoriList as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $katAktif === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1" for="f-pu">Program Unggulan</label>
            <select name="pu" id="f-pu" class="form-select form-select-sm">
                <option value="">Semua</option>
                <?php foreach ($daftarPu as $pu): ?>
                    <option value="<?= (int) $pu['id'] ?>" <?= $filter['pu'] === (string) $pu['id'] ? 'selected' : '' ?>><?= esc($pu['nama']) ?></option>
                <?php endforeach; ?>
                <option value="tanpa" <?= $filter['pu'] === 'tanpa' ? 'selected' : '' ?>>(tanpa Program Unggulan)</option>
            </select>
        </div>
        <div class="col-8 col-md-4">
            <label class="form-label small mb-1" for="f-q">Cari indikator / program</label>
            <input type="search" name="q" id="f-q" class="form-control form-control-sm" value="<?= esc($filter['q']) ?>" placeholder="mis. bank sampah" maxlength="100">
        </div>
        <div class="col-4 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-success btn-sm flex-grow-1"><i class="fas fa-filter"></i><span class="d-none d-lg-inline ms-1">Saring</span></button>
            <?php if ($adaFilter): ?>
                <a href="<?= esc($u('adminopd/ikp', ['tahun' => $tahun]), 'attr') ?>" class="btn btn-outline-secondary btn-sm" title="Hapus saringan"><i class="fas fa-xmark"></i></a>
            <?php endif; ?>
        </div>
    </form>
    <?php if ($bolehTambah): ?>
        <a href="<?= esc($u('adminopd/ikp/tambah', ['kategori' => $katAktif]), 'attr') ?>" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Tambah IKP
        </a>
    <?php endif; ?>
</div>

<?php if ($baris === []): ?>
    <div class="ikp-kosong">
        <div class="ic"><i class="fas fa-bullseye"></i></div>
        <?php if ($adaFilter): ?>
            <div class="fw-bold mb-1">Tidak ada IKP yang cocok dengan saringan.</div>
            <div class="small mb-3">Ubah kata kunci atau hapus saringan untuk melihat semua IKP.</div>
            <a href="<?= esc($u('adminopd/ikp', ['tahun' => $tahun]), 'attr') ?>" class="btn btn-outline-secondary btn-sm">Tampilkan semua</a>
        <?php else: ?>
            <div class="fw-bold mb-1">Belum ada IKP untuk perangkat daerah ini.</div>
            <div class="small mb-3">Mulailah dengan mendaftarkan indikator dari Program Unggulan Bupati (bisa dicari dari Buku Saku), program prioritas, atau penugasan.</div>
            <?php if ($bolehTambah): ?>
                <a href="<?= esc($u('adminopd/ikp/tambah'), 'attr') ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Tambah IKP pertama</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle ikp-tabel mb-0">
            <thead class="table-success">
                <tr>
                    <th style="width:40px" class="text-center d-none d-md-table-cell">No</th>
                    <th style="min-width:280px; width:38%">Indikator Kinerja Prioritas</th>
                    <th class="d-none d-md-table-cell sempit">Satuan</th>
                    <th class="d-none d-md-table-cell sempit">Metode</th>
                    <th class="text-end d-none d-lg-table-cell sempit">Target 5 Th</th>
                    <th class="text-end sempit d-none d-md-table-cell">Target <?= (int) $tahun ?></th>
                    <th class="d-none d-md-table-cell sempit">Kelengkapan</th>
                    <th class="sempit d-none d-md-table-cell">Capaian <?= (int) $tahun ?></th>
                    <th class="text-end sempit d-none d-md-table-cell">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($baris as $i => $r): ?>
                    <?php
                    $ikp  = $r['ikp'];
                    $id   = (int) $ikp['id'];
                    $kat  = (string) $ikp['kategori'];
                    $meta = $kategoriMeta[$kat] ?? ['ikon' => 'fa-tag', 'singkat' => $kat];
                    $kel  = $r['kelengkapan'];
                    $tb   = $r['tahun_berjalan'];
                    $t5   = $ikp['target_5_tahun'] !== null ? ikp_fmt((float) $ikp['target_5_tahun'], 4) : (string) ($ikp['target_5_tahun_teks'] ?? '');
                    ?>
                    <tr>
                        <td class="text-center text-secondary d-none d-md-table-cell"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <span class="ikp-kat <?= esc($kat, 'attr') ?>"><i class="fas <?= $meta['ikon'] ?>"></i><?= esc($meta['singkat']) ?></span>
                                <?php if (! empty($ikp['pu_nama'])): ?>
                                    <span class="ikp-pu" style="--pu: <?= esc($ikp['pu_warna'] ?: '#00743e', 'attr') ?>"><i class="fas <?= esc($ikp['pu_ikon'] ?: 'fa-star', 'attr') ?>"></i><?= esc($ikp['pu_nama']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="nama"><?= esc($ikp['output_prioritas']) ?></div>
                            <div class="sub">
                                <?php if (! empty($ikp['program_opd'])): ?><i class="fas fa-folder-open me-1"></i><?= esc(mb_strimwidth((string) $ikp['program_opd'], 0, 110, '…')) ?><?php endif; ?>
                                <?php if (! empty($ikp['pj_nama'])): ?><span class="ms-2"><i class="fas fa-user-check me-1"></i>PJ: <?= esc($ikp['pj_nama']) ?></span><?php endif; ?>
                                <span class="d-md-none ms-1">· <?= esc($ikp['satuan_label']) ?> · <?= esc($metodeSingkat[$ikp['metode'] ?? ''] ?? 'metode belum dipilih') ?></span>
                            </div>
                            <?php /* Ponsel: kolom angka & aksi dilipat ke dalam sel ini agar tidak perlu menggulir mendatar. */ ?>
                            <div class="d-md-none ikp-hp-ringkas">
                                <span>Target <?= (int) $tahun ?>: <strong><?= esc(ikp_fmt($r['target_tahunan'], 4)) ?></strong></span>
                                <span><?= $tb['persen'] !== null ? '<strong>' . esc(capaianFormatPersen($tb['persen'])) . '</strong> ' : '' ?><?= ikp_lencana_status($tb['warna'], $tb['status_label'], $tb['keterangan']) ?></span>
                                <span class="w-100 d-flex gap-1">
                                    <a href="<?= esc($u('adminopd/ikp/target/' . $id, ['tahun' => $tahun]), 'attr') ?>" class="btn btn-success btn-sm"><i class="fas fa-sliders me-1"></i>Target</a>
                                    <?php if ($bolehUbah): ?>
                                        <a href="<?= esc($u('adminopd/ikp/edit/' . $id), 'attr') ?>" class="btn btn-outline-secondary btn-sm" title="Ubah IKP"><i class="fas fa-pen"></i></a>
                                    <?php endif; ?>
                                    <?php if ($bolehHapus): ?>
                                        <?= view('templates/tombol_hapus', [
                                        'url'     => $u('adminopd/ikp/delete/' . $id),
                                        'judul'   => 'Hapus IKP',
                                        'nama'    => (string) $ikp['output_prioritas'],
                                        'pesan'   => 'IKP ini akan dihapus dari daftar, rekap, dan Lampiran PK.',
                                        // MENGAPA jenis "peringatan": hapus IKP = soft delete (data tetap ada
                                        // untuk rujukan SKP eKin), jadi kalimat "tidak dapat dibatalkan" dan
                                        // "ikut terhapus" dari jenis "hapus" akan menyesatkan.
                                        'jenis'   => 'peringatan',
                                        'rincian' => [
                                            'Tidak tampil lagi di daftar, rekap triwulan, dan Lampiran PK',
                                            (int) $kel['tahunan'] . ' target tahunan, ' . (int) $kel['bulanan'] . ' target bulanan & ' . (int) $kel['realisasi'] . ' realisasi ' . (int) $tahun . ' tetap tersimpan sebagai arsip',
                                            'SKP pegawai di eKin yang merujuk IKP ini tetap bisa membaca sumbernya',
                                        ],
                                        'ya'      => 'Ya, hapus IKP',
                                    ]) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell"><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '-') ?></td>
                        <td class="d-none d-md-table-cell">
                            <?php if (! empty($ikp['metode'])): ?>
                                <span class="small" title="<?= esc($metodeJelas[$ikp['metode']]['isi'] ?? '', 'attr') ?>"><?= esc($metodeSingkat[$ikp['metode']] ?? $ikp['metode']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark" title="Tanpa metode, rekap triwulan tidak dapat dihitung">Belum dipilih</span>
                            <?php endif; ?>
                        </td>
                        <td class="angka d-none d-lg-table-cell">
                            <?php if ($ikp['target_5_tahun'] !== null): ?>
                                <?= esc($t5) ?>
                            <?php else: ?>
                                <span class="small text-secondary d-inline-block text-wrap text-start" style="max-width:150px" title="<?= esc($t5, 'attr') ?>"><?= esc(mb_strimwidth($t5, 0, 60, '…')) ?: '-' ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="angka fw-bold d-none d-md-table-cell"><?= esc(ikp_fmt($r['target_tahunan'], 4)) ?></td>
                        <td class="d-none d-md-table-cell">
                            <div class="ikp-meter" title="Target tahunan terisi <?= (int) $kel['tahunan'] ?> dari <?= (int) $kel['tahunan_dari'] ?> tahun">
                                <span class="bar"><span style="width: <?= (int) round(100 * $kel['tahunan'] / max(1, $kel['tahunan_dari'])) ?>%"></span></span>
                                <?= (int) $kel['tahunan'] ?>/<?= (int) $kel['tahunan_dari'] ?> th
                            </div>
                            <div class="ikp-meter mt-1" title="Target bulanan <?= (int) $tahun ?> terisi <?= (int) $kel['bulanan'] ?> dari 12 bulan">
                                <span class="bar"><span style="width: <?= (int) round(100 * $kel['bulanan'] / 12) ?>%"></span></span>
                                <?= (int) $kel['bulanan'] ?>/12 bln
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <?php if ($tb['persen'] !== null): ?>
                                <div class="ikp-persen"><?= esc(capaianFormatPersen($tb['persen'])) ?></div>
                            <?php endif; ?>
                            <?= ikp_lencana_status($tb['warna'], $tb['status_label'], $tb['keterangan']) ?>
                            <?php if ($tb['sampai_bulan']): ?>
                                <div class="sub">s.d. <?= esc(ikp_nama_bulan((int) $tb['sampai_bulan'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="ikp-aksi">
                                <a href="<?= esc($u('adminopd/ikp/target/' . $id, ['tahun' => $tahun]), 'attr') ?>" class="btn btn-success btn-sm" title="Target & breakdown IKP ini">
                                    <i class="fas fa-sliders"></i>
                                </a>
                                <?php if ($bolehUbah): ?>
                                    <a href="<?= esc($u('adminopd/ikp/edit/' . $id), 'attr') ?>" class="btn btn-outline-secondary btn-sm" title="Ubah IKP"><i class="fas fa-pen"></i></a>
                                <?php endif; ?>
                                <?php if ($bolehHapus): ?>
                                    <?= view('templates/tombol_hapus', [
                                        'url'     => $u('adminopd/ikp/delete/' . $id),
                                        'judul'   => 'Hapus IKP',
                                        'nama'    => (string) $ikp['output_prioritas'],
                                        'pesan'   => 'IKP ini akan dihapus dari daftar, rekap, dan Lampiran PK.',
                                        // MENGAPA jenis "peringatan": hapus IKP = soft delete (data tetap ada
                                        // untuk rujukan SKP eKin), jadi kalimat "tidak dapat dibatalkan" dan
                                        // "ikut terhapus" dari jenis "hapus" akan menyesatkan.
                                        'jenis'   => 'peringatan',
                                        'rincian' => [
                                            'Tidak tampil lagi di daftar, rekap triwulan, dan Lampiran PK',
                                            (int) $kel['tahunan'] . ' target tahunan, ' . (int) $kel['bulanan'] . ' target bulanan & ' . (int) $kel['realisasi'] . ' realisasi ' . (int) $tahun . ' tetap tersimpan sebagai arsip',
                                            'SKP pegawai di eKin yang merujuk IKP ini tetap bisa membaca sumbernya',
                                        ],
                                        'ya'      => 'Ya, hapus IKP',
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="small text-secondary mt-2 mb-0">
        <i class="fas fa-circle-info me-1"></i>Warna status mengikuti ambang capaian yang ditetapkan Super Admin
        (Pengaturan Dashboard). Capaian dihitung dari bulan yang realisasinya sudah diisi.
    </p>
<?php endif; ?>

<?= $this->include('templates/shell_bawah') ?>

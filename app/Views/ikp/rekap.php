<?php
/**
 * Rekap triwulan IKP — AdminOpd\IkpController::rekap.
 * Per IKP: target & realisasi per triwulan, capaian (%) berwarna, dan
 * capaian tahun berjalan. Semua angka dari IkpRekapService (sama dengan PDF,
 * lampiran PK, Kabupaten, Bupati, dan API eKin).
 *
 * @var array  $baris    IkpRekapService::rekapOpd() (tersaring kategori)
 * @var array  $ringkas  IkpRekapService::ringkasOpd()
 * @var string $kategori
 */
$metodeSingkat = ['sum' => 'Akumulasi', 'trend_naik' => 'Posisi ↑', 'trend_turun' => 'Posisi ↓', 'trend_flat' => 'Tetap'];
$sel = static function (array $t) {
    if ($t['realisasi'] === null && $t['target'] === null) {
        return '<span class="text-secondary">–</span>';
    }
    $h = '<div class="ikp-tw"><span class="r">' . esc(ikp_fmt($t['realisasi'], 4)) . '</span><span class="t"> / ' . esc(ikp_fmt($t['target'], 4)) . '</span></div>';
    if ($t['capaian'] !== null || $t['status'] !== 'belum_ada_data') {
        $h .= ikp_lencana_status($t['warna'], $t['capaian'] !== null ? capaianFormatPersen($t['capaian']) : $t['status_label'], $t['keterangan']);
    }
    if (! empty($t['berjalan'])) {
        $h .= '<div style="font-size:.66rem;color:#7b8a80">berjalan'
            . (! empty($t['sampai_bulan']) ? ' · capaian s.d. ' . esc(ikp_nama_bulan((int) $t['sampai_bulan'], true)) : '') . '</div>';
    }

    return $h;
};
$grup = [];
foreach ($baris as $r) {
    $grup[$r['ikp']['kategori']][] = $r;
}
$bulanTerakhir = $ringkas['bulan_terakhir'] ? ikp_nama_bulan((int) $ringkas['bulan_terakhir']) : null;
$no = 0;
?>
<?= $this->include('ikp/_kepala') ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:#00743e"><i class="fas fa-bullseye"></i></span><span class="kt">IKP dipantau</span></div>
            <div class="kn"><?= (int) $ringkas['jumlah_ikp'] ?></div>
            <div class="ks"><?= (int) $ringkas['terisi_realisasi'] ?> sudah berealisasi di <?= (int) $tahun ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:#0a8f50"><i class="fas fa-gauge-high"></i></span><span class="kt">Capaian rata-rata</span></div>
            <div class="kn"><?= $ringkas['rata_capaian'] === null ? '–' : esc(capaianFormatPersen($ringkas['rata_capaian'])) ?></div>
            <div class="ks"><?= $bulanTerakhir ? 'Data s.d. ' . esc($bulanTerakhir) . ' ' . (int) $tahun : 'Belum ada realisasi' ?></div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="ikp-kartu">
            <div class="kh"><span class="ki" style="background:#3f6296"><i class="fas fa-traffic-light"></i></span><span class="kt">Sebaran status (tahun berjalan)</span></div>
            <div class="d-flex flex-wrap gap-2">
                <?= ikp_lencana_status('hijau', (int) $ringkas['hijau'] . ' tercapai / melampaui') ?>
                <?= ikp_lencana_status('kuning', (int) $ringkas['kuning'] . ' mendekati / perlu perhatian') ?>
                <?= ikp_lencana_status('merah', (int) $ringkas['merah'] . ' kritis') ?>
                <?= ikp_lencana_status('abu', (int) $ringkas['abu'] . ' belum dapat dinilai') ?>
            </div>
            <div class="ks">Status per IKP memakai ambang capaian Pengaturan Dashboard; hanya bulan yang realisasinya terisi yang dihitung.</div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="get" action="<?= base_url('adminopd/ikp/rekap') ?>" class="d-flex gap-2 align-items-center">
        <?php if (! empty($scope['can_pick'])): ?><input type="hidden" name="opd_id" value="<?= (int) $scope['opd_id'] ?>"><?php endif; ?>
        <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
        <select name="kategori" class="form-select form-select-sm" data-no-select2 onchange="this.form.submit()" aria-label="Saring kategori">
            <option value="">Semua kategori</option>
            <?php foreach ($kategoriList as $k => $label): ?>
                <option value="<?= $k ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="d-flex gap-2">
        <a href="<?= esc($u('adminopd/ikp/realisasi', ['tahun' => $tahun]), 'attr') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-pen-to-square me-1"></i>Isi realisasi</a>
        <a href="<?= esc($u('adminopd/ikp/cetak', ['tahun' => $tahun, 'kategori' => $kategori]), 'attr') ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener"><i class="fas fa-file-pdf me-1"></i>Cetak PDF</a>
    </div>
</div>

<?php if ($baris === []): ?>
    <div class="ikp-kosong">
        <div class="ic"><i class="fas fa-chart-column"></i></div>
        <div class="fw-bold mb-1">Belum ada IKP untuk direkap.</div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle ikp-tabel mb-0" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th class="text-center" style="width:40px">No</th>
                    <th style="min-width:260px">Indikator Kinerja Prioritas</th>
                    <th class="text-end sempit">Target <?= (int) $tahun ?></th>
                    <?php for ($q = 1; $q <= 4; $q++): ?><th class="text-center sempit">TW <?= capaianRomawi($q) ?></th><?php endfor; ?>
                    <th class="text-center sempit">Tahun berjalan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategoriList as $k => $label): if (empty($grup[$k])) { continue; } $m = $kategoriMeta[$k]; ?>
                    <tr>
                        <td colspan="8" style="background:#f3f8f5">
                            <span class="ikp-kat <?= $k ?>"><i class="fas <?= $m['ikon'] ?>"></i><?= esc($label) ?></span>
                            <span class="small text-secondary ms-1"><?= count($grup[$k]) ?> IKP</span>
                        </td>
                    </tr>
                    <?php foreach ($grup[$k] as $r): $ikp = $r['ikp']; $tb = $r['tahun_berjalan']; $no++; ?>
                        <tr>
                            <td class="text-center text-secondary"><?= $no ?></td>
                            <td>
                                <?php if (! empty($ikp['pu_nama'])): ?>
                                    <span class="ikp-pu mb-1" style="--pu: <?= esc($ikp['pu_warna'] ?: '#00743e', 'attr') ?>"><i class="fas <?= esc($ikp['pu_ikon'] ?: 'fa-star', 'attr') ?>"></i><?= esc($ikp['pu_nama']) ?></span>
                                <?php endif; ?>
                                <div class="nama"><a class="text-reset text-decoration-none" href="<?= esc($u('adminopd/ikp/target/' . (int) $ikp['id'], ['tahun' => $tahun]), 'attr') ?>"><?= esc($ikp['output_prioritas']) ?></a></div>
                                <div class="sub"><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '-') ?> · <?= esc($metodeSingkat[$ikp['metode'] ?? ''] ?? 'metode belum dipilih') ?></div>
                            </td>
                            <td class="angka fw-bold"><?= esc(ikp_fmt($r['target_tahunan'], 4)) ?></td>
                            <?php for ($q = 1; $q <= 4; $q++): ?>
                                <td class="text-center"><?= $sel($r['triwulan'][$q]) ?></td>
                            <?php endfor; ?>
                            <td class="text-center">
                                <?php if ($tb['persen'] !== null): ?><div class="ikp-persen"><?= esc(capaianFormatPersen($tb['persen'])) ?></div><?php endif; ?>
                                <?= ikp_lencana_status($tb['warna'], $tb['status_label'], $tb['keterangan']) ?>
                                <?php if ($tb['sampai_bulan']): ?><div class="sub">s.d. <?= esc(ikp_nama_bulan((int) $tb['sampai_bulan'])) ?></div><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="small text-secondary mt-2 mb-0">
        <i class="fas fa-circle-info me-1"></i>Setiap sel triwulan: <strong>realisasi</strong> / target, lalu capaian.
        Akumulasi: dijumlah per bulan; posisi: nilai bulan terakhir yang terisi. Triwulan berjalan dihitung dari bulan yang sudah terisi saja.
    </p>
<?php endif; ?>

<?= $this->include('templates/shell_bawah') ?>

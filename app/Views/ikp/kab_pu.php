<?php
/**
 * IKP per Program Unggulan Bupati — 9 ubin berwarna (+ "belum terpetakan")
 * dan rincian lintas OPD untuk satu Program Unggulan (?pu=slug). Hanya baca.
 *
 * @var int        $tahun
 * @var int        $bulan
 * @var int[]      $tahunList
 * @var array      $tile     [pu, jumlah, opd, rata, status, merah]
 * @var string     $puMinta
 * @var array|null $puAktif
 * @var array      $rinci    IKP (opd + rekap + sd + bln + per_bulan + lapor)
 */
$namaBulan  = ikp_nama_bulan($bulan);
$qs         = static fn (array $x = []) => '?' . http_build_query(array_merge(['tahun' => $tahun, 'bulan' => $bulan], $x));
$persenTeks = static fn (?float $v) => $v === null ? '–' : ikp_fmt($v, 2) . '%';
$metodeNm   = ['sum' => 'Akumulasi', 'trend_naik' => 'Posisi (naik)', 'trend_turun' => 'Posisi (turun)', 'trend_flat' => 'Dipertahankan'];
$this->setVar('title', $title ?? 'IKP per Program Unggulan');
$this->setVar('shellCss', <<<'CSS'
.pu-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(215px, 1fr)); gap:12px; }
.pu-tile { position:relative; display:flex; flex-direction:column; gap:8px; padding:14px 16px; border-radius:16px; border:1px solid #e6ece8;
  background:#fff; text-decoration:none; color:#16321f; box-shadow:0 6px 16px rgba(16,40,24,.05); transition:transform .15s, box-shadow .15s; overflow:hidden; }
.pu-tile:hover { transform:translateY(-3px); box-shadow:0 12px 26px rgba(16,40,24,.1); color:#16321f; }
.pu-tile::before { content:''; position:absolute; left:0; top:0; bottom:0; width:5px; background:var(--pu); }
.pu-tile.aktif { outline:2px solid var(--pu); outline-offset:1px; }
.pu-tile .ic { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; color:#fff; background:var(--pu); font-size:17px; }
.pu-tile .nm { font-weight:800; font-size:.92rem; line-height:1.25; }
.pu-tile .angka { display:flex; gap:14px; font-size:.78rem; color:#5d6b62; }
.pu-tile .angka b { display:block; font-size:1.2rem; color:#15311f; }
.pu-tile .cap { font-size:.78rem; font-weight:700; border-radius:8px; padding:.3em .55em; align-self:flex-start; }
.pu-tabel { font-size:.85rem; }
.pu-tabel thead th { background:#00713c; color:#fff; font-size:.7rem; text-transform:uppercase; letter-spacing:.35px; vertical-align:middle; }
.pu-tabel td { vertical-align:middle; }
.pct-badge { font-weight:800; font-size:.8rem; padding:.3em .6em; border-radius:8px; display:inline-block; min-width:64px; text-align:center; }
.catatan-kecil { font-size:.78rem; color:#6b7a70; }
@media (max-width: 767.98px) {
  table.tabel-kartu-hp thead { display:none; }
  table.tabel-kartu-hp, table.tabel-kartu-hp tbody, table.tabel-kartu-hp tr, table.tabel-kartu-hp td { display:block; width:100%; }
  table.tabel-kartu-hp { min-width:0 !important; } /* style.php memberi semua tabel min-width 500px di ponsel */
  table.tabel-kartu-hp tr { border:1px solid #e6ece8; border-radius:12px; margin-bottom:10px; padding:8px 12px; background:#fff; }
  table.tabel-kartu-hp td { border:0 !important; padding:3px 0 !important; text-align:left !important; }
  table.tabel-kartu-hp td[data-label] { display:flex; justify-content:space-between; align-items:center; gap:12px; }
  table.tabel-kartu-hp td[data-label]::before { content:attr(data-label); font-size:.7rem; color:#6b7a70; font-weight:700; text-transform:uppercase; letter-spacing:.3px; flex:0 0 auto; }
  table.tabel-kartu-hp td[data-label] > div { flex:1 1 auto; max-width:58%; min-width:0; }
  table.tabel-kartu-hp .bar-mini { min-width:36px; }
  table.tabel-kartu-hp td.sembunyi-hp { display:none; }
}
@media (max-width: 575.98px) { .panel { padding:14px 12px; } .dash-hero { padding:18px 16px; }
  .pu-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:8px; } .pu-tile { padding:12px 12px 12px 14px; } .pu-tile .nm { font-size:.8rem; } .pu-tile .ic { width:32px; height:32px; font-size:14px; } }
CSS);
?>
<?= $this->include('templates/shell_atas') ?>
<?= $this->include('templates/dashboard_kit') ?>

<div class="mb-3">
  <a href="<?= base_url('adminkab/ikp') . $qs() ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-arrow-left me-1"></i> Rekap per Perangkat Daerah</a>
</div>

<div class="dash-hero mb-3">
  <div class="dh-ic"><i class="fas fa-shapes"></i></div>
  <div class="flex-fill">
    <h2>IKP per Program Unggulan Bupati</h2>
    <p>Tahun <?= (int) $tahun ?> &middot; capaian s.d. <strong><?= esc($namaBulan) ?></strong>
      &middot; <span class="dash-mode"><i class="fas fa-eye"></i> Hanya baca</span></p>
  </div>
</div>

<form method="get" class="dash-filter mb-3">
  <?php if ($puMinta !== ''): ?><input type="hidden" name="pu" value="<?= esc($puMinta, 'attr') ?>"><?php endif; ?>
  <div class="row g-3 align-items-end">
    <div class="col-6 col-md-2">
      <label for="f-tahun" class="form-label mb-1">Tahun</label>
      <select name="tahun" id="f-tahun" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php foreach ($tahunList as $t): ?>
          <option value="<?= (int) $t ?>" <?= (int) $t === $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label for="f-bulan" class="form-label mb-1">Capaian s.d. bulan</label>
      <select name="bulan" id="f-bulan" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc(ikp_nama_bulan($m)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>
</form>

<div class="pu-grid mb-4">
  <?php foreach ($tile as $t): ?>
    <?php $st = $t['status']; $warnaPu = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $t['pu']['warna']) ? $t['pu']['warna'] : '#00743e'; ?>
    <a class="pu-tile<?= $puMinta === $t['pu']['slug'] ? ' aktif' : '' ?>" style="--pu: <?= esc($warnaPu, 'attr') ?>;"
       href="<?= base_url('adminkab/ikp/program-unggulan') . $qs(['pu' => $t['pu']['slug']]) ?>">
      <div class="d-flex align-items-center gap-2">
        <div class="ic"><i class="fas <?= esc($t['pu']['ikon'] ?: 'fa-star', 'attr') ?>"></i></div>
        <div class="nm"><?= esc($t['pu']['nama']) ?></div>
      </div>
      <div class="angka">
        <div><b><?= (int) $t['jumlah'] ?></b>IKP</div>
        <div><b><?= (int) $t['opd'] ?></b>OPD</div>
        <?php if ($t['merah'] > 0): ?><div><b style="color:#d64545;"><?= (int) $t['merah'] ?></b>kritis</div><?php endif; ?>
      </div>
      <span class="cap" style="background:<?= esc($st['color_soft'], 'attr') ?>; color:<?= esc($st['color_hex'], 'attr') ?>;">
        <?= $t['rata'] === null ? esc($st['name']) : esc($persenTeks($t['rata'])) . ' · ' . esc($st['name']) ?>
      </span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($puMinta === ''): ?>
  <div class="panel"><div class="empty"><div class="ic"><i class="fas fa-hand-pointer"></i></div>
    <p class="mb-0">Pilih salah satu Program Unggulan untuk melihat IKP pendukungnya di seluruh perangkat daerah.</p></div></div>
<?php else: ?>
  <div class="panel">
    <div class="panel-head">
      <div>
        <h3><?= esc($puAktif['pu']['nama'] ?? '') ?></h3>
        <p><?= count($rinci) ?> IKP dari <?= (int) ($puAktif['opd'] ?? 0) ?> perangkat daerah &middot; urut menurut perangkat daerah</p>
      </div>
      <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('adminkab/ikp/program-unggulan') . $qs() ?>"><i class="fas fa-xmark me-1"></i> Tutup rincian</a>
    </div>
    <?php if ($rinci === []): ?>
      <div class="empty"><div class="ic"><i class="fas fa-inbox"></i></div><p class="mb-0">Belum ada IKP pada Program Unggulan ini.</p></div>
    <?php else: ?>
      <table class="table table-hover pu-tabel tabel-kartu-hp mb-2" data-no-paginate>
        <thead>
          <tr>
            <th style="width:44px;">No</th>
            <th>Perangkat Daerah</th>
            <th>Indikator IKP (output prioritas)</th>
            <th class="text-center">Satuan</th>
            <th class="text-center">Metode</th>
            <th class="text-center">Target 5 th</th>
            <th class="text-center">Target <?= (int) $tahun ?></th>
            <th class="text-center">Realisasi terakhir</th>
            <th class="text-center">Capaian s.d. <?= esc(ikp_nama_bulan($bulan, true)) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rinci as $n => $x): ?>
            <?php
            $ikp = $x['rekap']['ikp'];
            $st  = $x['sd']['status'];
            $bt  = $x['sd']['bulan_terakhir'];
            $t5  = trim((string) ($ikp['target_5_tahun_teks'] ?? '')) !== '' ? $ikp['target_5_tahun_teks'] : ($ikp['target_5_tahun'] !== null ? ikp_fmt($ikp['target_5_tahun']) : '–');
            ?>
            <tr>
              <td class="text-muted sembunyi-hp"><?= $n + 1 ?></td>
              <td><a class="text-decoration-none fw-semibold" href="<?= base_url('adminkab/ikp/opd/' . (int) $x['opd']['id']) . $qs() ?>"><?= esc($x['opd']['nama_opd']) ?></a></td>
              <td><?= esc(ikp_rapikan_teks($ikp['output_prioritas'])) ?></td>
              <td class="text-center" data-label="Satuan"><?= esc($ikp['satuan_label'] !== '' ? $ikp['satuan_label'] : '–') ?></td>
              <td class="text-center small" data-label="Metode"><?= esc($metodeNm[$ikp['metode'] ?? ''] ?? 'Belum dipilih') ?></td>
              <td class="text-center" data-label="Target 5 th"><?= esc(mb_strimwidth((string) $t5, 0, 40, '…')) ?></td>
              <td class="text-center" data-label="Target <?= (int) $tahun ?>"><?= $x['rekap']['target_tahunan'] !== null ? esc(ikp_fmt($x['rekap']['target_tahunan'])) : '–' ?></td>
              <td class="text-center" data-label="Realisasi terakhir">
                <?php if ($bt !== null): ?>
                  <?= esc(ikp_fmt($x['rekap']['bulan'][$bt]['realisasi'])) ?> <span class="catatan-kecil">(<?= esc(ikp_nama_bulan($bt, true)) ?>)</span>
                <?php else: ?>–<?php endif; ?>
              </td>
              <td class="text-center" data-label="Capaian s.d. <?= esc(ikp_nama_bulan($bulan, true), 'attr') ?>">
                <span class="pct-badge" style="background:<?= esc($st['color_soft'], 'attr') ?>; color:<?= esc($st['color_hex'], 'attr') ?>;" title="<?= esc((string) $x['sd']['keterangan'], 'attr') ?>">
                  <?= esc($persenTeks($x['sd']['persen'])) ?>
                </span>
                <div class="catatan-kecil d-none d-md-block"><?= esc($st['name']) ?></div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?= $this->include('templates/shell_bawah') ?>

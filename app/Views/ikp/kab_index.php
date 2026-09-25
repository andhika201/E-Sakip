<?php
/**
 * Rekap Kinerja Prioritas (IKP) lintas Perangkat Daerah — area Kabupaten (baca).
 * Data: AdminKab\IkpController::index (rekapLintas + totalKabupaten).
 *
 * @var int   $tahun
 * @var int   $bulan
 * @var string $jenis  ''|opd|kecamatan
 * @var int[] $tahunList
 * @var array $baris
 * @var array $total
 */
$namaBulan = ikp_nama_bulan($bulan);
$qs        = static fn (array $x = []) => '?' . http_build_query(array_merge(['tahun' => $tahun, 'bulan' => $bulan], $x));
$persenTeks = static fn (?float $v) => $v === null ? '–' : ikp_fmt($v, 2) . '%';
$this->setVar('title', $title ?? 'Rekap Kinerja Prioritas (IKP)');
$this->setVar('shellCss', <<<'CSS'
.ikp-tabel { font-size:.86rem; }
.ikp-tabel thead th { background:#00713c; color:#fff; font-size:.7rem; text-transform:uppercase; letter-spacing:.35px; vertical-align:middle; white-space:nowrap; }
.ikp-tabel thead th.urut { cursor:pointer; user-select:none; }
.ikp-tabel thead th.urut:hover { background:#005d31; }
.ikp-tabel thead th.urut .ar { opacity:.45; margin-left:4px; font-size:.65rem; }
.ikp-tabel thead th.urut.aktif .ar { opacity:1; }
.ikp-tabel td { vertical-align:middle; }
.ikp-tabel td.num { text-align:center; white-space:nowrap; }
.ikp-opd a { color:#16321f; font-weight:600; text-decoration:none; }
.ikp-opd a:hover { color:#00743e; text-decoration:underline; }
.ikp-opd .jenis { font-size:.7rem; color:#6b7a70; }
.bar-mini { height:6px; border-radius:6px; background:#eef2ef; overflow:hidden; min-width:70px; }
.bar-mini > span { display:block; height:100%; background:#0a8f50; }
.pct-badge { font-weight:800; font-size:.8rem; padding:.3em .6em; border-radius:8px; display:inline-block; min-width:64px; text-align:center; }
.sebaran { display:inline-flex; gap:3px; }
.sebaran span { font-size:.68rem; font-weight:700; border-radius:6px; padding:.18em .45em; color:#fff; }
.catatan-kecil { font-size:.78rem; color:#6b7a70; }
.baris-kosong td { color:#8a968f; }
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
@media (max-width: 575.98px) { .panel { padding:14px 12px; } .dash-hero { padding:18px 16px; } }
CSS);
?>
<?= $this->include('templates/shell_atas') ?>
<?= $this->include('templates/dashboard_kit') ?>

<div class="dash-hero mb-3">
  <div class="dh-ic"><i class="fas fa-bullseye"></i></div>
  <div class="flex-fill">
    <h2>Kinerja Prioritas (IKP) Lintas Perangkat Daerah</h2>
    <p>
      Tahun <?= (int) $tahun ?> &middot; capaian s.d. <strong><?= esc($namaBulan) ?></strong>
      &middot; <span class="dash-mode"><i class="fas fa-eye"></i> Hanya baca</span>
    </p>
  </div>
</div>

<form method="get" class="dash-filter mb-3">
  <div class="row g-3 align-items-end">
    <div class="col-6 col-md-2">
      <label for="f-tahun" class="form-label mb-1">Tahun</label>
      <select name="tahun" id="f-tahun" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php foreach ($tahunList as $t): ?>
          <option value="<?= (int) $t ?>" <?= (int) $t === $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <label for="f-bulan" class="form-label mb-1">Capaian s.d. bulan</label>
      <select name="bulan" id="f-bulan" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>><?= esc(ikp_nama_bulan($m)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-12 col-md-3 col-lg-3">
      <label for="f-jenis" class="form-label mb-1">Jenis</label>
      <select name="jenis" id="f-jenis" class="form-select" data-no-select2 onchange="this.form.submit()">
        <option value="" <?= $jenis === '' ? 'selected' : '' ?>>Perangkat Daerah &amp; Kecamatan</option>
        <option value="opd" <?= $jenis === 'opd' ? 'selected' : '' ?>>Perangkat Daerah saja</option>
        <option value="kecamatan" <?= $jenis === 'kecamatan' ? 'selected' : '' ?>>Kecamatan saja</option>
      </select>
    </div>
    <div class="col-12 col-md-4 col-lg-5 d-flex flex-wrap gap-2 justify-content-md-end">
      <a href="<?= base_url('adminkab/ikp/program-unggulan') . $qs() ?>" class="btn btn-outline-success">
        <i class="fas fa-shapes me-1"></i> Per Program Unggulan
      </a>
      <a href="<?= base_url('adminkab/ikp/cetak') . $qs(['jenis' => $jenis]) ?>" class="btn btn-success" target="_blank" rel="noopener">
        <i class="fas fa-file-pdf me-1"></i> Cetak PDF
      </a>
    </div>
  </div>
</form>

<!-- ======================= KARTU RINGKAS ======================= -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#0a8f50,#00743e);"><i class="fas fa-list-check"></i></div>
        <div class="kpi-title">IKP Terdaftar</div>
      </div>
      <div class="kpi-num"><?= (int) $total['ikp'] ?></div>
      <div class="kpi-sub">
        di <?= (int) $total['opd_ber_ikp'] ?> dari <?= (int) $total['opd'] ?> perangkat daerah<br>
        <?= (int) $total['lengkap'] ?> IKP dengan target tahunan &amp; bulanan lengkap
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#3f6296,#2f4d7a);"><i class="fas fa-gauge-high"></i></div>
        <div class="kpi-title">Rata-rata Capaian</div>
      </div>
      <div class="kpi-num" style="color:<?= esc($total['status_sd']['color_hex'], 'attr') ?>"><?= esc($persenTeks($total['rata_sd'])) ?></div>
      <div class="kpi-sub">
        <span class="dot" style="background:<?= esc($total['status_sd']['color_hex'], 'attr') ?>"></span><?= esc($total['status_sd']['name']) ?>
        &middot; s.d. <?= esc($namaBulan) ?>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#c98a3c,#a86a26);"><i class="fas fa-calendar-check"></i></div>
        <div class="kpi-title">Melapor <?= esc($namaBulan) ?></div>
      </div>
      <div class="kpi-num"><?= (int) $total['lapor_opd'] ?><span style="font-size:1rem;color:#6b7a70;"> / <?= (int) $total['opd_ber_ikp'] ?> OPD</span></div>
      <div class="kpi-sub"><?= (int) $total['lapor_ikp'] ?> dari <?= (int) $total['ikp'] ?> IKP sudah berisi realisasi bulan ini</div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="kpi">
      <div class="kpi-head">
        <div class="kpi-ic" style="background:linear-gradient(135deg,#d64545,#b13333);"><i class="fas fa-traffic-light"></i></div>
        <div class="kpi-title">Sebaran Status IKP</div>
      </div>
      <div class="kpi-sub">
        <div><span class="dot" style="background:#0a8f50"></span><?= (int) $total['hijau'] ?> tercapai / melampaui</div>
        <div><span class="dot" style="background:#d9a520"></span><?= (int) $total['kuning'] ?> mendekati / perlu perhatian</div>
        <div><span class="dot" style="background:#d64545"></span><?= (int) $total['merah'] ?> kritis</div>
        <div><span class="dot" style="background:#8a968f"></span><?= (int) $total['abu'] ?> belum dapat dinilai</div>
      </div>
      <?php if ($total['tanpa_metode'] > 0): ?>
        <div class="kpi-foot" style="color:#a86a26;"><i class="fas fa-triangle-exclamation me-1"></i><?= (int) $total['tanpa_metode'] ?> IKP belum memilih metode</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ======================= TABEL PER OPD ======================= -->
<div class="panel">
  <div class="panel-head">
    <div>
      <h3>Rekap per Perangkat Daerah</h3>
      <p>Klik judul kolom untuk mengurutkan; klik nama perangkat daerah untuk rincian bulanan.</p>
    </div>
    <select id="urut-hp" class="form-select form-select-sm d-md-none" data-no-select2 aria-label="Urutkan" style="max-width: 230px;">
      <option value="nama|asc">Urut: nama A–Z</option>
      <option value="capaian|asc">Urut: capaian terendah</option>
      <option value="capaian|desc">Urut: capaian tertinggi</option>
      <option value="lapor|asc">Urut: pelaporan terendah</option>
      <option value="jumlah|desc">Urut: IKP terbanyak</option>
    </select>
  </div>

  <?php if ($baris === []): ?>
    <div class="empty"><div class="ic"><i class="fas fa-building"></i></div><p class="mb-0">Tidak ada perangkat daerah untuk saringan ini.</p></div>
  <?php else: ?>
    <table class="table table-hover ikp-tabel tabel-kartu-hp mb-2" id="tabel-rekap" data-no-paginate>
      <thead>
        <tr>
          <th class="urut" data-kunci="no" style="width:48px;">No<i class="fas fa-sort ar"></i></th>
          <th class="urut" data-kunci="nama" data-teks="1">Perangkat Daerah<i class="fas fa-sort ar"></i></th>
          <th class="urut text-center" data-kunci="jumlah">IKP<i class="fas fa-sort ar"></i></th>
          <th class="urut" data-kunci="lengkap">Breakdown lengkap<i class="fas fa-sort ar"></i></th>
          <th class="urut text-center" data-kunci="lapor">Realisasi <?= esc(ikp_nama_bulan($bulan, true)) ?><i class="fas fa-sort ar"></i></th>
          <th class="urut" data-kunci="isi">Terisi s.d. <?= esc(ikp_nama_bulan($bulan, true)) ?><i class="fas fa-sort ar"></i></th>
          <th class="urut text-center" data-kunci="capaian">Rata-rata capaian<i class="fas fa-sort ar"></i></th>
          <th class="text-center">Sebaran status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($baris as $n => $b): ?>
          <?php
          $a        = $b['agregat'];
          $pLengkap = $a['jumlah'] > 0 ? round($a['lengkap'] / $a['jumlah'] * 100) : 0;
          $pIsi     = $a['sel_wajib'] > 0 ? round($a['sel_terisi'] / $a['sel_wajib'] * 100) : 0;
          $st       = $a['status_sd'];
          ?>
          <tr class="<?= $a['jumlah'] === 0 ? 'baris-kosong' : '' ?>"
              data-no="<?= $n + 1 ?>" data-nama="<?= esc(mb_strtolower($b['opd']['nama_opd']), 'attr') ?>"
              data-jumlah="<?= (int) $a['jumlah'] ?>" data-lengkap="<?= $a['jumlah'] > 0 ? $pLengkap : -1 ?>"
              data-lapor="<?= $a['jumlah'] > 0 ? round($a['lapor_bulan'] / $a['jumlah'] * 100, 2) : -1 ?>"
              data-isi="<?= $a['jumlah'] > 0 ? $pIsi : -1 ?>"
              data-capaian="<?= $a['rata_sd'] === null ? -1 : $a['rata_sd'] ?>">
            <td class="num text-muted sembunyi-hp"><?= $n + 1 ?></td>
            <td class="ikp-opd">
              <a href="<?= base_url('adminkab/ikp/opd/' . (int) $b['opd']['id']) . $qs() ?>"><?= esc($b['opd']['nama_opd']) ?></a>
              <div class="jenis">
                <?= $b['opd']['jenis'] === 'kecamatan' ? 'Kecamatan' : 'Perangkat Daerah' ?>
                <?php if ($a['tanpa_metode'] > 0): ?>
                  &middot; <span style="color:#a86a26;"><i class="fas fa-triangle-exclamation"></i> <?= (int) $a['tanpa_metode'] ?> tanpa metode</span>
                <?php endif; ?>
              </div>
            </td>
            <td class="num fw-bold" data-label="Jumlah IKP"><?= $a['jumlah'] > 0 ? (int) $a['jumlah'] : '<span class="text-muted fw-normal">belum ada</span>' ?></td>
            <td data-label="Breakdown lengkap">
              <?php if ($a['jumlah'] > 0): ?>
                <div class="d-flex align-items-center gap-2">
                  <div class="bar-mini flex-fill"><span style="width:<?= $pLengkap ?>%"></span></div>
                  <span class="small text-nowrap"><?= (int) $a['lengkap'] ?>/<?= (int) $a['jumlah'] ?></span>
                </div>
              <?php else: ?>–<?php endif; ?>
            </td>
            <td class="num" data-label="Realisasi <?= esc(ikp_nama_bulan($bulan, true), 'attr') ?>"><?= $a['jumlah'] > 0 ? (int) $a['lapor_bulan'] . '/' . (int) $a['jumlah'] : '–' ?></td>
            <td data-label="Terisi s.d. <?= esc(ikp_nama_bulan($bulan, true), 'attr') ?>">
              <?php if ($a['jumlah'] > 0): ?>
                <div class="d-flex align-items-center gap-2">
                  <div class="bar-mini flex-fill"><span style="width:<?= $pIsi ?>%; background:#3f6296;"></span></div>
                  <span class="small text-nowrap"><?= $pIsi ?>%</span>
                </div>
              <?php else: ?>–<?php endif; ?>
            </td>
            <td class="num" data-label="Rata-rata capaian">
              <span class="pct-badge" style="background:<?= esc($st['color_soft'], 'attr') ?>; color:<?= esc($st['color_hex'], 'attr') ?>;"
                    title="<?= esc($st['name'], 'attr') ?>">
                <?= esc($persenTeks($a['rata_sd'])) ?>
              </span>
              <div class="catatan-kecil d-none d-md-block"><?= esc($st['name']) ?></div>
            </td>
            <td class="num" data-label="Sebaran status">
              <?php if ($a['jumlah'] > 0): ?>
                <span class="sebaran" title="hijau / kuning / merah / belum dinilai">
                  <span style="background:#0a8f50"><?= (int) $a['warna']['hijau'] ?></span>
                  <span style="background:#d9a520"><?= (int) $a['warna']['kuning'] ?></span>
                  <span style="background:#d64545"><?= (int) $a['warna']['merah'] ?></span>
                  <span style="background:#8a968f"><?= (int) $a['warna']['abu'] ?></span>
                </span>
              <?php else: ?>–<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="catatan-kecil">
      <i class="fas fa-circle-info me-1"></i>
      Capaian dihitung dengan rumus yang sama dengan halaman OPD: hanya bulan yang realisasinya sudah diisi yang ikut dihitung
      (Akumulasi: jumlah realisasi ÷ jumlah target; Posisi: bulan terisi terakhir). Warna mengikuti ambang status dasbor.
      IKP tanpa metode perhitungan belum dapat dinilai.
    </div>
  <?php endif; ?>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var tabel = document.getElementById('tabel-rekap');
    if (!tabel) return;
    var arah = {};
    function urutkan(k, a) {
      var th = tabel.querySelector('th.urut[data-kunci="' + k + '"]');
      var teks = th && th.dataset.teks === '1';
      arah[k] = a;
      var tb = tabel.tBodies[0];
      var rows = Array.prototype.slice.call(tb.rows);
      rows.sort(function (x, y) {
        var p = x.dataset[k], q = y.dataset[k];
        var c = teks ? p.localeCompare(q, 'id') : (parseFloat(p) - parseFloat(q));
        return a === 'asc' ? c : -c;
      });
      rows.forEach(function (r) { tb.appendChild(r); });
      tabel.querySelectorAll('th.urut').forEach(function (h) {
        h.classList.toggle('aktif', h === th);
        var i = h.querySelector('.ar');
        if (i) i.className = 'fas ar ' + (h === th ? (a === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort');
      });
    }
    tabel.querySelectorAll('th.urut').forEach(function (th) {
      th.addEventListener('click', function () {
        var k = th.dataset.kunci, teks = th.dataset.teks === '1';
        urutkan(k, arah[k] === 'asc' ? 'desc' : (arah[k] === 'desc' ? 'asc' : (teks || k === 'no' ? 'asc' : 'desc')));
      });
    });
    var hp = document.getElementById('urut-hp');
    if (hp) hp.addEventListener('change', function () { var v = hp.value.split('|'); urutkan(v[0], v[1]); });
  });
</script>

<?= $this->include('templates/shell_bawah') ?>

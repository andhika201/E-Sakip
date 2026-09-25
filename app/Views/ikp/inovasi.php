<?php
/**
 * Rencana Inovasi Perangkat Daerah — Lampiran III Perjanjian Kinerja Eselon II.
 *
 * Data dari AdminOpd\IkpInovasiController::index. Semua mutasi lewat POST +
 * csrf_field(); hapus lewat templates/tombol_hapus. Tombol tulis hanya tampil
 * bila perannya berizin — server tetap memeriksa ulang di _remap().
 *
 * @var array      $lingkup   opd_id, opd, boleh_pilih, opd_list
 * @var int|null   $opdId
 * @var int        $tahun
 * @var int[]      $tahunList
 * @var array      $daftar    baris ikp_inovasi (+ ikp_nama, ikp_dihapus)
 * @var array      $ikpList   IkpRekapService::daftar()
 * @var array      $pkList    PK jpt/camat tahun ini
 * @var array|null $pkPilihan PK bawaan untuk lampiran
 */
$title  = $title ?? 'Rencana Inovasi Perangkat Daerah';
$opd    = $lingkup['opd'] ?? null;
$js     = static fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$kategoriLabel = \App\Models\Ikp\IkpModel::KATEGORI;
$lampiranUrl   = base_url('adminopd/ikp/lampiran-pk') . '?tahun=' . (int) $tahun . $qsOpd;

$shellCss = <<<'CSS'
.ino-head { display:flex; align-items:center; gap:16px; padding-bottom:18px; margin-bottom:20px; border-bottom:1px solid #e8ece9; flex-wrap:wrap; }
.ino-head .ic { flex:0 0 auto; width:52px; height:52px; display:grid; place-items:center; border-radius:15px;
  background:linear-gradient(135deg,#0a8f50 0%,#00743e 100%); color:#fff; font-size:21px; }
.ino-head h2 { margin:0; font-weight:800; font-size:1.3rem; color:#16321f; }
.ino-head p { margin:3px 0 0; color:#6b7a70; font-size:.86rem; }
.ino-toolbar { background:#f6f9f7; border:1px solid #e6ece8; border-radius:14px; padding:14px 16px; margin-bottom:18px; }
.ino-toolbar label { font-size:.74rem; font-weight:700; color:#6b7a70; text-transform:uppercase; letter-spacing:.3px; }
.ino-card { border:1px solid #e6ece8; border-radius:14px; padding:16px 18px; background:#fff; }
.ino-card h3 { font-size:.98rem; font-weight:700; color:#16321f; margin:0 0 12px; }
.ino-tabel { font-size:.88rem; }
.ino-tabel thead th { background:#00713c; color:#fff; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; vertical-align:middle; }
.ino-tabel td { vertical-align:top; }
.ino-nama { font-weight:700; color:#1d2b23; }
.ino-desk { color:#4d5b52; font-size:.84rem; white-space:pre-line; margin-top:4px; }
.chip-ikp { display:inline-flex; gap:6px; align-items:flex-start; background:#eef6f1; color:#0b5a33; border:1px solid #d3e8db;
  border-radius:9px; padding:4px 8px; font-size:.78rem; line-height:1.35; }
.chip-ikp.mati { background:#fbf1f1; color:#8a2d2d; border-color:#f0d5d5; }
.ino-empty { text-align:center; padding:40px 20px; border-radius:16px; border:1px dashed #cfd8d2; background:#f8faf9; color:#5d6b62; }
.ino-empty .ic { font-size:38px; margin-bottom:10px; color:#00743e; opacity:.35; }
.lamp-box { border:1px solid #d9e8df; background:linear-gradient(180deg,#f7fbf8,#fff); border-radius:14px; padding:14px 16px; }
.lamp-box .ttl { font-weight:700; color:#16321f; }
.lamp-box ol { margin:6px 0 0; padding-left:1.1rem; font-size:.82rem; color:#4d5b52; }
.nomor { width:34px; height:34px; border-radius:10px; background:#e8f2ec; color:#00743e; font-weight:800; display:grid; place-items:center; font-size:.85rem; }
@media (max-width: 767.98px) {
  .ino-head h2 { font-size:1.1rem; }
  .ino-tabel .kol-ikp { display:none !important; }
  .ino-tabel .ikp-hp { display:block !important; }
  .chip-ikp { white-space:normal; }
}
@media (max-width: 767.98px) {
  table.tabel-kartu-hp thead { display:none; }
  table.tabel-kartu-hp, table.tabel-kartu-hp tbody, table.tabel-kartu-hp tr, table.tabel-kartu-hp td { display:block; width:100%; }
  table.tabel-kartu-hp { min-width:0 !important; } /* style.php memberi semua tabel min-width 500px di ponsel */
  table.tabel-kartu-hp tr { border:1px solid #e6ece8; border-radius:12px; margin-bottom:10px; padding:8px 12px; background:#fff; }
  table.tabel-kartu-hp td { border:0 !important; padding:3px 0 !important; text-align:left !important; }
  table.tabel-kartu-hp td[data-label] { display:flex; justify-content:space-between; align-items:center; gap:12px; }
  table.tabel-kartu-hp td[data-label]::before { content:attr(data-label); font-size:.7rem; color:#6b7a70; font-weight:700; text-transform:uppercase; letter-spacing:.3px; flex:0 0 auto; }
  table.tabel-kartu-hp td[data-label] > div { flex:0 0 auto; }
  table.tabel-kartu-hp .bar-mini { min-width:36px; }
  table.tabel-kartu-hp td.sembunyi-hp { display:none; }
}

CSS;
// MENGAPA setVar: $this->include() hanya melihat DATA view, bukan variabel
// lokal berkas ini (dan argumen keduanya adalah $options, bukan data).
$this->setVar('title', $title);
$this->setVar('shellCss', $shellCss);
?>
<?= $this->include('templates/shell_atas') ?>

<div class="ino-head">
  <div class="ic"><i class="fas fa-lightbulb"></i></div>
  <div class="flex-fill">
    <h2>Rencana Inovasi Perangkat Daerah</h2>
    <p>
      Lampiran III Perjanjian Kinerja Eselon II
      <?php if ($opd): ?> &middot; <strong><?= esc($opd['nama_opd']) ?></strong><?php endif; ?>
      &middot; Tahun <?= (int) $tahun ?>
    </p>
  </div>
</div>

<!-- ======================= FILTER ======================= -->
<form method="get" class="ino-toolbar">
  <div class="row g-3 align-items-end">
    <?php if ($lingkup['boleh_pilih']): ?>
      <div class="col-12 col-md-6 col-lg-5">
        <label for="f-opd" class="form-label mb-1">Perangkat Daerah</label>
        <select name="opd_id" id="f-opd" class="form-select" onchange="this.form.submit()">
          <option value="">— Pilih perangkat daerah —</option>
          <?php foreach ($lingkup['opd_list'] as $o): ?>
            <option value="<?= (int) $o['id'] ?>" <?= (int) $o['id'] === (int) $opdId ? 'selected' : '' ?>><?= esc($o['nama_opd']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
    <div class="col-6 col-md-3 col-lg-2">
      <label for="f-tahun" class="form-label mb-1">Tahun</label>
      <select name="tahun" id="f-tahun" class="form-select" data-no-select2 onchange="this.form.submit()">
        <?php foreach ($tahunList as $t): ?>
          <option value="<?= (int) $t ?>" <?= (int) $t === (int) $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3 col-lg-auto">
      <noscript><button type="submit" class="btn btn-success">Tampilkan</button></noscript>
    </div>
  </div>
</form>

<?php if ($opdId === null): ?>
  <div class="ino-empty">
    <div class="ic"><i class="fas fa-building"></i></div>
    <div class="fw-semibold mb-1">Pilih perangkat daerah terlebih dahulu</div>
    <div class="small">Rencana inovasi selalu dicatat per perangkat daerah dan per tahun.</div>
  </div>
<?php else: ?>

<div class="row g-3 mb-3">
  <!-- ======================= FORM TAMBAH ======================= -->
  <div class="col-12 col-xl-7">
    <?php if ($bolehTambah): ?>
      <div class="ino-card h-100">
        <h3><i class="fas fa-plus-circle text-success me-1"></i> Tambah Rencana Inovasi <?= (int) $tahun ?></h3>
        <form method="post" action="<?= base_url('adminopd/ikp/inovasi/save') ?>" id="form-tambah" autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
          <?php if ($lingkup['boleh_pilih']): ?>
            <input type="hidden" name="opd_id" value="<?= (int) $opdId ?>">
          <?php endif; ?>
          <div class="mb-2">
            <label for="t-nama" class="form-label small fw-semibold mb-1">Nama inovasi <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="t-nama" name="nama" maxlength="255" required
                   value="<?= esc(old('nama') ?? '') ?>" placeholder="mis. Bank Sampah Digital Pekon">
          </div>
          <div class="mb-2">
            <label for="t-deskripsi" class="form-label small fw-semibold mb-1">Deskripsi singkat</label>
            <textarea class="form-control" id="t-deskripsi" name="deskripsi" rows="3" maxlength="5000"
                      placeholder="Masalah yang dijawab, cara kerja, dan manfaat yang diharapkan."><?= esc(old('deskripsi') ?? '') ?></textarea>
          </div>
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-9">
              <label for="t-ikp" class="form-label small fw-semibold mb-1">Mendukung IKP <span class="text-muted fw-normal">(opsional)</span></label>
              <select class="form-select pilih-ikp" id="t-ikp" name="ikp_id">
                <option value="">— Tidak dikaitkan ke IKP tertentu —</option>
                <?php foreach ($ikpList as $i): ?>
                  <option value="<?= (int) $i['id'] ?>" <?= (string) old('ikp_id') === (string) $i['id'] ? 'selected' : '' ?>>
                    <?= esc(mb_strimwidth(ikp_rapikan_teks($i['output_prioritas']), 0, 140, '…')) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label for="t-urutan" class="form-label small fw-semibold mb-1">No. urut</label>
              <input type="number" class="form-control" id="t-urutan" name="urutan" min="1" max="999" placeholder="otomatis">
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="alert alert-light border mb-0 h-100">
        <i class="fas fa-eye me-1"></i> Anda memiliki akses <strong>lihat saja</strong> untuk Rencana Inovasi.
      </div>
    <?php endif; ?>
  </div>

  <!-- ======================= LAMPIRAN PK ======================= -->
  <div class="col-12 col-xl-5">
    <div class="lamp-box h-100">
      <div class="d-flex align-items-start gap-2 mb-2">
        <i class="fas fa-file-pdf text-danger mt-1"></i>
        <div>
          <div class="ttl">Cetak Lampiran PK Eselon II <?= (int) $tahun ?></div>
          <div class="small text-muted">Satu berkas PDF (kertas Folio) berisi:</div>
        </div>
      </div>
      <ol>
        <li>Halaman perjanjian &amp; <strong>Lampiran I</strong> — sasaran, indikator, target (IKU) serta program &amp; anggaran <em>APBD Murni</em></li>
        <li><strong>Lampiran II</strong> — Indikator Kinerja Prioritas (IKP)</li>
        <li><strong>Lampiran III</strong> — Rencana Inovasi (daftar di halaman ini)</li>
        <li><strong>Lampiran IV</strong> — target tahunan IKP selama periode RPJMD</li>
        <li><strong>Lampiran V</strong> — target bulanan &amp; triwulan IKP tahun <?= (int) $tahun ?></li>
      </ol>

      <?php if ($pkList === []): ?>
        <div class="alert alert-warning small py-2 px-3 mt-3 mb-2">
          <i class="fas fa-triangle-exclamation me-1"></i>
          Perjanjian Kinerja Eselon II tahun <?= (int) $tahun ?> belum ada di AKSARA. Lampiran II–V tetap dapat
          dicetak sebagai <strong>draf tanpa tanda tangan</strong>.
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= $lampiranUrl ?>" target="_blank" rel="noopener">
          <i class="fas fa-print me-1"></i> Cetak Draf Lampiran II–V
        </a>
      <?php elseif (count($pkList) === 1): ?>
        <div class="small text-muted mt-3 mb-2">
          PK: <strong><?= esc($pkList[0]['nama_pihak_1'] ?? '—') ?></strong>
          <?php if (! empty($pkList[0]['tanggal'])): ?> &middot; <?= esc(formatTanggal($pkList[0]['tanggal'])) ?><?php endif; ?>
        </div>
        <a class="btn btn-success btn-sm" href="<?= $lampiranUrl ?>" target="_blank" rel="noopener">
          <i class="fas fa-print me-1"></i> Cetak Lampiran PK (PDF)
        </a>
      <?php else: ?>
        <form method="get" action="<?= base_url('adminopd/ikp/lampiran-pk') ?>" target="_blank" class="mt-3">
          <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
          <?php if ($lingkup['boleh_pilih']): ?><input type="hidden" name="opd_id" value="<?= (int) $opdId ?>"><?php endif; ?>
          <label for="pk-pilih" class="form-label small fw-semibold mb-1">Ada <?= count($pkList) ?> PK Eselon II tahun ini — pilih penanda tangan:</label>
          <div class="d-flex gap-2 flex-wrap">
            <select name="pk_id" id="pk-pilih" class="form-select form-select-sm" data-no-select2 style="max-width: 320px;">
              <?php foreach ($pkList as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) ($pkPilihan['id'] ?? 0) ? 'selected' : '' ?>>
                  <?= esc($p['nama_pihak_1'] ?? ('PK #' . $p['id'])) ?><?= ! empty($p['tanggal']) ? ' — ' . esc(formatTanggal($p['tanggal'])) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-print me-1"></i> Cetak</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ======================= DAFTAR ======================= -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
  <h3 class="h6 fw-bold mb-0 text-success">
    <i class="fas fa-list-ol me-1"></i> Daftar Rencana Inovasi <?= (int) $tahun ?>
    <span class="badge bg-light text-secondary border ms-1"><?= count($daftar) ?></span>
  </h3>
  <?php if ($ikpList === []): ?>
    <span class="small text-muted"><i class="fas fa-circle-info me-1"></i>Belum ada IKP aktif untuk dikaitkan.</span>
  <?php endif; ?>
</div>

<?php if ($daftar === []): ?>
  <div class="ino-empty">
    <div class="ic"><i class="fas fa-lightbulb"></i></div>
    <div class="fw-semibold mb-1">Belum ada rencana inovasi untuk tahun <?= (int) $tahun ?></div>
    <div class="small">
      <?= $bolehTambah ? 'Tambahkan lewat formulir di atas. Inovasi yang dicatat di sini otomatis tercetak sebagai Lampiran III.' : 'Perangkat daerah belum mencatat rencana inovasi.' ?>
    </div>
  </div>
<?php else: ?>
  <table class="table table-hover align-middle ino-tabel tabel-kartu-hp" data-no-paginate>
    <thead>
      <tr>
        <th style="width:56px;" class="text-center">No</th>
        <th>Rencana Inovasi</th>
        <th class="kol-ikp" style="width:32%;">Mendukung IKP</th>
        <?php if ($bolehUbah || $bolehHapus): ?><th style="width:110px;" class="text-center">Aksi</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($daftar as $n => $r): ?>
        <?php
        $ikpMati = ! empty($r['ikp_id']) && ($r['ikp_nama'] === null || $r['ikp_dihapus'] !== null);
        $chip = '';
        if (! empty($r['ikp_id'])) {
            $chip = '<span class="chip-ikp' . ($ikpMati ? ' mati' : '') . '"><i class="fas ' . ($ikpMati ? 'fa-link-slash' : 'fa-bullseye') . ' mt-1"></i><span>'
                . ($ikpMati ? 'IKP yang ditautkan sudah dihapus' : esc(ikp_rapikan_teks($r['ikp_nama']))) . '</span></span>';
        }
        ?>
        <tr>
          <td class="text-center sembunyi-hp"><div class="nomor mx-auto"><?= $n + 1 ?></div></td>
          <td>
            <div class="ino-nama"><span class="d-md-none text-success"><?= $n + 1 ?>.</span> <?= esc($r['nama']) ?></div>
            <?php if (trim((string) $r['deskripsi']) !== ''): ?>
              <div class="ino-desk"><?= esc($r['deskripsi']) ?></div>
            <?php endif; ?>
            <?php if ($chip !== ''): ?><div class="ikp-hp mt-2" style="display:none;"><?= $chip ?></div><?php endif; ?>
            <?php if (! empty($r['legacy_prioritas_id'])): ?>
              <div class="small text-muted mt-1"><i class="fas fa-file-import me-1"></i>Diimpor dari aplikasi Prioritas</div>
            <?php endif; ?>
          </td>
          <td class="kol-ikp"><?= $chip !== '' ? $chip : '<span class="text-muted small">—</span>' ?></td>
          <?php if ($bolehUbah || $bolehHapus): ?>
            <td class="text-center text-nowrap" data-label="Aksi">
              <div class="d-inline-flex gap-1 justify-content-end">
              <?php if ($bolehUbah): ?>
                <button type="button" class="btn btn-outline-success btn-sm btn-ubah" title="Ubah"
                        data-id="<?= (int) $r['id'] ?>"
                        data-nama="<?= esc($r['nama'], 'attr') ?>"
                        data-deskripsi="<?= esc((string) $r['deskripsi'], 'attr') ?>"
                        data-ikp="<?= (int) ($r['ikp_id'] ?? 0) ?>"
                        data-urutan="<?= (int) $r['urutan'] ?>">
                  <i class="fas fa-pen"></i>
                </button>
              <?php endif; ?>
              <?php if ($bolehHapus): ?>
                <?= view('templates/tombol_hapus', [
                    'url'   => base_url('adminopd/ikp/inovasi/delete/' . (int) $r['id']),
                    'judul' => 'Hapus Rencana Inovasi',
                    'nama'  => $r['nama'],
                    'pesan' => 'Rencana inovasi ini akan dihapus dan tidak lagi tercetak di Lampiran III PK.',
                ]) ?>
              <?php endif; ?>
              </div>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php if ($bolehUbah): ?>
  <!-- ======================= MODAL UBAH ======================= -->
  <div class="modal fade" id="modal-ubah" tabindex="-1" aria-labelledby="modal-ubah-judul" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form method="post" class="modal-content" id="form-ubah" autocomplete="off">
        <?= csrf_field() ?>
        <?php if ($lingkup['boleh_pilih']): ?>
          <input type="hidden" name="opd_id" value="<?= (int) $opdId ?>">
        <?php endif; ?>
        <div class="modal-header">
          <h5 class="modal-title" id="modal-ubah-judul"><i class="fas fa-pen me-2 text-success"></i>Ubah Rencana Inovasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label for="u-nama" class="form-label small fw-semibold mb-1">Nama inovasi <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="u-nama" name="nama" maxlength="255" required>
          </div>
          <div class="mb-2">
            <label for="u-deskripsi" class="form-label small fw-semibold mb-1">Deskripsi singkat</label>
            <textarea class="form-control" id="u-deskripsi" name="deskripsi" rows="5" maxlength="5000"></textarea>
          </div>
          <div class="row g-2">
            <div class="col-12 col-md-9">
              <label for="u-ikp" class="form-label small fw-semibold mb-1">Mendukung IKP</label>
              <select class="form-select pilih-ikp" id="u-ikp" name="ikp_id">
                <option value="">— Tidak dikaitkan ke IKP tertentu —</option>
                <?php foreach ($ikpList as $i): ?>
                  <option value="<?= (int) $i['id'] ?>"><?= esc(mb_strimwidth(ikp_rapikan_teks($i['output_prioritas']), 0, 140, '…')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <label for="u-urutan" class="form-label small fw-semibold mb-1">No. urut</label>
              <input type="number" class="form-control" id="u-urutan" name="urutan" min="1" max="999">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var urlUbah = <?= $js(base_url('adminopd/ikp/inovasi/update/')) ?>;

    // Select2 TIDAK dipasang otomatis pada form POST (footer), jadi dipasang di sini.
    if ($ && $.fn && $.fn.select2) {
      $('#t-ikp').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '— Tidak dikaitkan ke IKP tertentu —', allowClear: true });
      $('#u-ikp').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modal-ubah'), placeholder: '— Tidak dikaitkan ke IKP tertentu —', allowClear: true });
    }

    document.querySelectorAll('.btn-ubah').forEach(function (b) {
      b.addEventListener('click', function () {
        var f = document.getElementById('form-ubah');
        f.action = urlUbah + b.dataset.id;
        f.querySelector('#u-nama').value = b.dataset.nama || '';
        f.querySelector('#u-deskripsi').value = b.dataset.deskripsi || '';
        f.querySelector('#u-urutan').value = b.dataset.urutan || '';
        var ikp = b.dataset.ikp && b.dataset.ikp !== '0' ? b.dataset.ikp : '';
        if ($ && $.fn && $.fn.select2) { $('#u-ikp').val(ikp).trigger('change'); }
        else { f.querySelector('#u-ikp').value = ikp; }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-ubah')).show();
      });
    });
  });
</script>

<?= $this->include('templates/shell_bawah') ?>

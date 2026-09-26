<?php
/**
 * AKSARA+ — "Masuk sebagai": cari akun lalu masuk sebagai pemiliknya (App\Services\MasukSebagaiService).
 *
 * @var array{q:string, peran:string, jenis:string} $saring
 * @var list<array<string,mixed>>                   $akun
 * @var list<array<string,mixed>>                   $terakhir
 * @var array<string,mixed>|null                    $asli
 * @var list<string>                                $peranSaring
 * @var array<string,string>                        $jenisSaring
 */
$labelPeran = ['admin_opd' => 'Admin OPD', 'admin_kecamatan' => 'Admin Kecamatan', 'bupati' => 'Bupati', 'admin_inspektorat' => 'Admin Inspektorat'];
$adaSaring  = $saring['q'] !== '' || $saring['peran'] !== '' || $saring['jenis'] !== '';
$pintas     = [
    'Bupati'          => ['peran' => 'bupati'],
    'Dinas & Badan'   => ['jenis' => 'opd'],
    'Kecamatan'       => ['peran' => 'admin_kecamatan'],
    'Diskominfo'      => ['q' => 'komunikasi'],
    'Dinas Kesehatan' => ['q' => 'kesehatan'],
    'Inspektorat'     => ['peran' => 'admin_inspektorat'],
];
$rapikan = static fn (?string $s): string => ucwords(strtolower((string) $s));
$tombol  = static function (array $a, string $kelas = 'btn-success'): string {
    return '<form method="post" action="' . base_url('masuk-sebagai/' . (int) $a['user_id']) . '" class="d-inline">'
        . csrf_field()
        . '<button type="submit" class="btn btn-sm ' . $kelas . '"><i class="fas fa-right-to-bracket me-1" aria-hidden="true"></i>Masuk</button></form>';
};
?>
<?= $this->include('templates/shell_atas') ?>

<style>
  .ms-saring { background: #f7faf8; border: 1px solid #e3ebe6; border-radius: 12px; padding: 1rem; }
  .ms-saring .form-label { font-size: .78rem; font-weight: 600; color: #5d6b63; margin-bottom: .2rem; }
  .ms-pintas { display: inline-block; font-size: .8rem; padding: .15rem .65rem; border-radius: 99px; background: #eef2ef; color: #3f4d45; text-decoration: none; }
  .ms-pintas:hover { background: #dfe9e3; color: #1d2b23; }
  .ms-chip { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid #e3ebe6; border-radius: 99px; background: #fff; padding: .2rem .35rem .2rem .7rem; font-size: .84rem; }
  .ms-chip .btn { font-size: .74rem; padding: .08rem .55rem; border-radius: 99px; }
  .ms-tabel td { vertical-align: middle; }
  .ms-kecil { font-size: .78rem; color: #64736b; }
  .ms-peran { display: inline-block; font-size: .74rem; padding: .05rem .55rem; border-radius: 99px; background: #eef2ff; color: #3730a3; white-space: nowrap; }
  @media (max-width: 767.98px) {
    .ms-tabel thead { display: none; }
    .ms-tabel tr { display: grid; grid-template-columns: 1fr auto; gap: .15rem .6rem; padding: .6rem 0; border-bottom: 1px solid #e3ebe6; }
    .ms-tabel td { border: 0; padding: 0 .25rem; }
    .ms-tabel td.ms-aksi { grid-row: 1 / span 4; grid-column: 2; align-self: center; }
  }
</style>

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
  <div>
    <h2 class="h4 fw-bold text-success mb-1"><i class="fas fa-user-secret me-2" aria-hidden="true"></i>Masuk sebagai</h2>
    <p class="text-muted mb-0">Pakai akun Admin OPD, Admin Kecamatan, Bupati, atau Inspektorat untuk melihat dan mencoba AKSARA+ persis seperti pemiliknya, lalu kembali ke <strong><?= esc($asli['username'] ?? '') ?></strong> kapan saja.</p>
  </div>
</div>

<?php if ($terakhir !== []): ?>
  <div class="mb-3">
    <div class="ms-kecil mb-1">Baru saja dimasuki</div>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($terakhir as $t): ?>
        <span class="ms-chip"><span><?= esc($t['username']) ?><?php if (! empty($t['nama_opd'])): ?> <span class="text-muted">&middot; <?= esc($t['singkatan'] ?: $rapikan($t['nama_opd'])) ?></span><?php endif; ?></span><?= $tombol($t, 'btn-outline-success') ?></span>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<form method="get" action="<?= base_url('masuk-sebagai') ?>" class="ms-saring mb-3" role="search">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-lg-6">
      <label class="form-label" for="ms-q">Cari nama pengguna, perangkat daerah, atau nama/jabatan kepalanya</label>
      <input type="search" class="form-control" id="ms-q" name="q" value="<?= esc($saring['q'], 'attr') ?>" placeholder="mis. kesehatan, camat, admin_dlh…" autofocus>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="ms-peran">Peran akun</label>
      <select class="form-select" id="ms-peran" name="peran">
        <option value="">Semua</option>
        <?php foreach ($peranSaring as $p): ?>
          <option value="<?= esc($p, 'attr') ?>" <?= $p === $saring['peran'] ? 'selected' : '' ?>><?= esc($labelPeran[$p] ?? $p) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label class="form-label" for="ms-jenis">Jenis perangkat daerah</label>
      <select class="form-select" id="ms-jenis" name="jenis">
        <option value="">Semua</option>
        <?php foreach ($jenisSaring as $k => $label): ?>
          <option value="<?= esc($k, 'attr') ?>" <?= $k === $saring['jenis'] ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-lg-2 d-grid">
      <button type="submit" class="btn btn-success"><i class="fas fa-magnifying-glass me-1" aria-hidden="true"></i>Cari</button>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
    <span class="ms-kecil">Pintas:</span>
    <?php foreach ($pintas as $label => $isi): ?>
      <a class="ms-pintas" href="<?= base_url('masuk-sebagai') . '?' . http_build_query($isi) ?>"><?= esc($label) ?></a>
    <?php endforeach; ?>
    <?php if ($adaSaring): ?>
      <a class="ms-kecil ms-auto" href="<?= base_url('masuk-sebagai') ?>"><i class="fas fa-xmark me-1" aria-hidden="true"></i>Hapus saringan</a>
    <?php endif; ?>
  </div>
</form>

<p class="ms-kecil mb-2"><?= count($akun) ?> akun cocok. AKSARA memakai satu akun per perangkat daerah; pegawai perorangan (sampai pelaksana) ada di eKin. Akun Super Admin dan Admin Kabupaten tidak bisa dimasuki.</p>

<?php if ($akun === []): ?>
  <div class="text-center text-muted py-5 border rounded"><i class="fas fa-user-slash fa-2x mb-2 d-block" aria-hidden="true"></i>Tidak ada akun yang cocok. Longgarkan saringan atau hapus kata pencarian.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table ms-tabel mb-0">
      <thead class="table-light">
        <tr><th>Akun</th><th>Perangkat daerah</th><th>Kepala perangkat daerah</th><th class="text-end"><span class="visually-hidden">Aksi</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($akun as $a): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= esc($a['username']) ?></div>
              <span class="ms-peran"><?= esc($a['peran_label'] ?? $a['role']) ?></span>
            </td>
            <td>
              <div><?= $a['nama_opd'] !== null ? esc($rapikan($a['nama_opd'])) : ($a['role'] === 'bupati' ? 'Kabupaten Pringsewu' : 'Lintas perangkat daerah') ?></div>
              <?php if (! empty($a['jenis_opd'])): ?><div class="ms-kecil"><?= esc($jenisSaring[$a['jenis_opd']] ?? $a['jenis_opd']) ?></div><?php endif; ?>
            </td>
            <td>
              <?php if (! empty($a['kepala_nama'])): ?>
                <div><?= esc($a['kepala_nama']) ?></div>
                <div class="ms-kecil"><?= esc($a['kepala_jabatan'] ?? '') ?></div>
              <?php else: ?>
                <span class="ms-kecil">–</span>
              <?php endif; ?>
            </td>
            <td class="text-end ms-aksi"><?= $tombol($a) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="alert alert-light border mt-3 mb-0 small">
  <strong>Cara kerja.</strong> Tekan <strong>Masuk</strong> pada akun mana pun: Anda melihat dan bisa melakukan semua yang bisa dilakukan pemilik akun itu.
  Pita jingga di atas halaman menandai bahwa Anda sedang memakai akun lain — tekan <strong>Ganti akun</strong> untuk pindah, atau
  <strong>Kembali ke <?= esc($asli['username'] ?? '') ?></strong> bila sudah selesai (tombol Logout juga membawa Anda kembali).
  Setiap perpindahan akun tercatat di log aktivitas, dan setiap perubahan data ditandai "lewat Masuk sebagai oleh <?= esc($asli['username'] ?? '') ?>".
  Kata sandi dan 2FA akun yang dimasuki tidak bisa diubah.
</div>

<?= $this->include('templates/shell_bawah') ?>

<?php
/**
 * AKSARA+ — pita "Anda sedang masuk sebagai …" (App\Services\MasukSebagaiService), di atas setiap halaman admin & Bupati
 * selama Admin Kabupaten / Super Admin memakai akun lain. Tombol "Ganti akun" dan "Kembali ke <akun asli>".
 * Di luar mode itu berkas ini tidak mengeluarkan apa pun.
 */
if (! \App\Services\MasukSebagaiService::sedangMeniru()) {
    return;
}

$pitaDb   = \Config\Database::connect();
$pitaPeran = $pitaDb->table('roles')->select('label')->where('name', session()->get('role'))->get()->getRowArray()['label'] ?? session()->get('role');
$pitaOpd  = session()->get('opd_id')
    ? ($pitaDb->table('opd')->select('nama_opd')->where('id', (int) session()->get('opd_id'))->get()->getRowArray()['nama_opd'] ?? '')
    : '';
?>
<div class="pita-tiru" role="status" aria-live="polite">
  <span>
    <i class="fas fa-user-secret" aria-hidden="true"></i>
    Anda sedang masuk sebagai <strong><?= esc(session()->get('username')) ?></strong><span class="pita-tiru-ket"> &middot; <?= esc($pitaPeran) ?><?= $pitaOpd !== '' ? ' &middot; ' . esc(ucwords(strtolower($pitaOpd))) : '' ?></span>
  </span>
  <span class="pita-tiru-aksi">
    <a class="btn btn-sm btn-light" href="<?= base_url('masuk-sebagai') ?>"><i class="fas fa-shuffle me-1" aria-hidden="true"></i>Ganti akun</a>
    <form method="post" action="<?= base_url('masuk-sebagai/kembali') ?>" class="d-inline">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-sm btn-dark"><i class="fas fa-rotate-left me-1" aria-hidden="true"></i>Kembali ke <?= esc(session()->get('masuk_sebagai_asli_username')) ?></button>
    </form>
  </span>
</div>
<style>
  .pita-tiru {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .4rem .9rem;
    background: #c2410c; color: #fff; font-size: .86rem; line-height: 1.35; padding: .45rem .9rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .15);
  }
  .pita-tiru .fa-user-secret { margin-right: .35rem; }
  .pita-tiru-ket { opacity: .9; }
  .pita-tiru-aksi { display: inline-flex; gap: .4rem; flex-wrap: wrap; }
  .pita-tiru .btn { font-size: .78rem; padding: .18rem .6rem; }
  @media (max-width: 576px) { .pita-tiru-ket { display: none; } }
  @media print { .pita-tiru { display: none; } }
</style>

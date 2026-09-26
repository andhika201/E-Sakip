<?php
/**
 * AKSARA+ — pita tipis "DATA SIMULASI" di atas halaman admin & Bupati.
 *
 * Hanya tampil bila `.env` berisi `demo.simulasi = true` (lingkungan prototipe/demo). Tanpa kunci itu — seperti di
 * produksi — berkas ini tidak mengeluarkan apa pun, jadi aman ikut ter-deploy.
 *
 * MENGAPA env, bukan Config/Pengaturan: penanda ini milik LINGKUNGAN (DB berisi data simulasi), bukan milik
 * aplikasi; tidak boleh bisa dimatikan dari UI sementara datanya masih data simulasi.
 */
$pitaSimulasi = filter_var(env('demo.simulasi', false), FILTER_VALIDATE_BOOLEAN);
?>
<?php if ($pitaSimulasi): ?>
<div class="pita-simulasi" role="note" aria-label="Data simulasi">
  <i class="fas fa-flask" aria-hidden="true"></i>
  <strong>DATA SIMULASI</strong>
  <span class="pita-simulasi-ket">&middot; prototipe AKSARA+ &mdash; angka, realisasi, dan penilaian 2026 di sini bukan data resmi</span>
</div>
<style>
  .pita-simulasi {
    background: #fff3bf;
    color: #5c4400;
    border-bottom: 1px solid #f0d264;
    font-size: .76rem;
    line-height: 1.25;
    padding: .28rem .9rem;
    text-align: center;
  }
  .pita-simulasi strong { letter-spacing: .08em; }
  .pita-simulasi .fa-flask { margin-right: .3rem; }
  @media (max-width: 576px) {
    .pita-simulasi-ket { display: none; }
  }
</style>
<?php endif; ?>

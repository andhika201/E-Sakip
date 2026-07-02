<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - e-SAKIP Kabupaten Pringsewu</title>
  <?= $this->include('user/templates/style.php'); ?>

  <style>
    /* ===================== Landing / Hero modern ===================== */
    html, body { height: auto; min-height: 100%; }
    body { min-height: 100vh; }

    .hero {
      position: relative;
      flex: 1 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      background: linear-gradient(180deg, rgba(3, 38, 22, .38) 0%, rgba(3, 38, 22, .74) 100%);
    }

    .hero-inner {
      width: 100%;
      max-width: 1080px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 32px;
    }

    /* ---- Kartu sambutan (glass) ---- */
    .hero-card {
      width: 100%;
      max-width: 780px;
      text-align: center;
      color: #fff;
      background: rgba(255, 255, 255, .12);
      backdrop-filter: blur(14px) saturate(140%);
      -webkit-backdrop-filter: blur(14px) saturate(140%);
      border: 1px solid rgba(255, 255, 255, .26);
      border-radius: 24px;
      padding: 42px 40px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, .28);
      animation: heroUp .6s ease both;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, .18);
      border: 1px solid rgba(255, 255, 255, .34);
      color: #fff;
      font-weight: 600;
      font-size: .8rem;
      letter-spacing: .3px;
      padding: 6px 15px;
      border-radius: 999px;
      margin-bottom: 18px;
    }
    .hero-card h1 {
      font-weight: 800;
      font-size: clamp(1.7rem, 3.4vw, 2.6rem);
      line-height: 1.15;
      margin: 0 0 14px;
      text-shadow: 0 2px 18px rgba(0, 0, 0, .28);
    }
    .hero-card h1 .accent { color: #c6e89a; }
    .hero-card p {
      margin: 0 auto;
      max-width: 620px;
      font-size: clamp(.95rem, 1.4vw, 1.08rem);
      color: rgba(255, 255, 255, .92);
      line-height: 1.65;
    }

    /* ---- Akses cepat ---- */
    .quick-wrap { width: 100%; animation: heroUp .6s ease .12s both; }
    .quick-label {
      text-align: center;
      color: #fff;
      font-weight: 700;
      letter-spacing: 2px;
      font-size: .75rem;
      text-transform: uppercase;
      opacity: .92;
      margin-bottom: 14px;
      text-shadow: 0 2px 10px rgba(0, 0, 0, .35);
    }
    .quick-grid {
      width: 100%;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(168px, 1fr));
      gap: 16px;
    }
    .quick-card {
      display: flex;
      flex-direction: column;
      gap: 10px;
      text-decoration: none;
      background: rgba(255, 255, 255, .97);
      border: 1px solid rgba(255, 255, 255, .6);
      border-radius: 18px;
      padding: 18px 18px 16px;
      min-height: 138px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .quick-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 18px 42px rgba(0, 0, 0, .26);
    }
    .quick-card .qc-icon {
      width: 46px;
      height: 46px;
      border-radius: 13px;
      display: grid;
      place-items: center;
      color: #fff;
      font-size: 20px;
    }
    .quick-card .qc-title {
      font-weight: 700;
      color: #16321f;
      font-size: .98rem;
      line-height: 1.2;
    }
    .quick-card .qc-sub {
      font-size: .78rem;
      color: #6b7a70;
      margin: 2px 0 0;
      line-height: 1.35;
    }
    .quick-card .qc-arrow {
      margin-top: auto;
      color: #00743e;
      font-weight: 600;
      font-size: .8rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: gap .18s ease;
    }
    .quick-card:hover .qc-arrow { gap: 10px; }

    .qc-green  { background: linear-gradient(135deg, #0a8f50, #00743e); }
    .qc-lime   { background: linear-gradient(135deg, #84c225, #6eab11); }
    .qc-teal   { background: linear-gradient(135deg, #2f8579, #246b61); }
    .qc-blue   { background: linear-gradient(135deg, #3f6296, #2f4d7a); }
    .qc-amber  { background: linear-gradient(135deg, #c98a3c, #a86a26); }
    .qc-purple { background: linear-gradient(135deg, #6f5f8a, #574a6e); }

    @keyframes heroUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 575px) {
      .hero { padding: 40px 16px; }
      .hero-card { padding: 28px 22px; }
      .quick-card { min-height: 124px; }
    }
  </style>
</head>

<body>

  <?= $this->include('user/templates/header'); ?>

  <main class="hero">
    <div class="hero-inner">

      <!-- Kartu sambutan -->
      <div class="hero-card">
        <span class="hero-badge"><i class="fas fa-shield-halved"></i> Pemerintah Kabupaten Pringsewu</span>
        <h1>Selamat Datang di <span class="accent">Dashboard e-SAKIP</span></h1>
        <p>
          Sistem Informasi Akuntabilitas Kinerja Instansi Pemerintah Kabupaten Pringsewu &mdash;
          akses data perencanaan, kinerja, dan akuntabilitas secara transparan dan terintegrasi.
        </p>
      </div>

      <!-- Akses cepat -->
      <div class="quick-wrap">
        <div class="quick-label">Akses Cepat</div>
        <div class="quick-grid">

          <a class="quick-card" href="<?= base_url('rpjmd') ?>">
            <div class="qc-icon qc-green"><i class="fas fa-landmark"></i></div>
            <div>
              <div class="qc-title">RPJMD</div>
              <p class="qc-sub">Rencana Pembangunan Jangka Menengah Daerah</p>
            </div>
            <span class="qc-arrow">Buka <i class="fas fa-arrow-right"></i></span>
          </a>

          <a class="quick-card" href="<?= base_url('rkpd') ?>">
            <div class="qc-icon qc-lime"><i class="fas fa-calendar-check"></i></div>
            <div>
              <div class="qc-title">RKPD</div>
              <p class="qc-sub">Rencana Kerja Pemerintah Daerah</p>
            </div>
            <span class="qc-arrow">Buka <i class="fas fa-arrow-right"></i></span>
          </a>

          <a class="quick-card" href="<?= base_url('cascading_kabupaten') ?>">
            <div class="qc-icon qc-teal"><i class="fas fa-sitemap"></i></div>
            <div>
              <div class="qc-title">Pohon Kinerja &amp; Cascading</div>
              <p class="qc-sub">Cascading kinerja pemerintah kabupaten</p>
            </div>
            <span class="qc-arrow">Buka <i class="fas fa-arrow-right"></i></span>
          </a>

          <a class="quick-card" href="<?= base_url('lakip_kabupaten') ?>">
            <div class="qc-icon qc-blue"><i class="fas fa-file-contract"></i></div>
            <div>
              <div class="qc-title">LAKIP</div>
              <p class="qc-sub">Laporan Akuntabilitas Kinerja Instansi</p>
            </div>
            <span class="qc-arrow">Buka <i class="fas fa-arrow-right"></i></span>
          </a>

          <a class="quick-card" href="<?= base_url('cascading_opd') ?>">
            <div class="qc-icon qc-purple"><i class="fas fa-building"></i></div>
            <div>
              <div class="qc-title">Kinerja Perangkat Daerah</div>
              <p class="qc-sub">Renstra &amp; cascading perangkat daerah</p>
            </div>
            <span class="qc-arrow">Buka <i class="fas fa-arrow-right"></i></span>
          </a>

          <a class="quick-card" href="<?= base_url('tentang_kami') ?>">
            <div class="qc-icon qc-amber"><i class="fas fa-circle-info"></i></div>
            <div>
              <div class="qc-title">Tentang Kami</div>
              <p class="qc-sub">Profil &amp; informasi sistem e-SAKIP</p>
            </div>
            <span class="qc-arrow">Buka <i class="fas fa-arrow-right"></i></span>
          </a>

        </div>
      </div>

    </div>

  <?= $this->include('user/templates/footer'); ?>

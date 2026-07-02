<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tentang Kami - e-SAKIP</title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .tk-hero { text-align: center; padding-bottom: 6px; margin-bottom: 26px; }
    .tk-hero .tk-logo-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 16px 26px;
      background: #fff;
      border: 1px solid #eef1ee;
      border-radius: 20px;
      box-shadow: 0 10px 28px rgba(16, 40, 24, .08);
    }
    .tk-hero img { max-width: 200px; height: auto; display: block; }
    .tk-hero h2 { font-weight: 800; color: #15311f; margin: 18px 0 5px; letter-spacing: .3px; }
    .tk-hero p { color: #6b7a70; margin: 0; font-size: .92rem; }

    .tk-card { border-radius: 14px; padding: 22px 24px; border: 1px solid #e6ece8; background: #f7faf8; }
    .tk-card.tk-accent { background: linear-gradient(135deg, #eef6f0 0%, #e9f3ed 100%); border-color: #cfe6d8; }
    .tk-card h3 { font-weight: 700; color: #00743e; margin-bottom: 12px; font-size: 1.15rem; }
    .tk-card p, .tk-card li { color: #3a4a40; line-height: 1.75; }
    .tk-card ul { margin-bottom: .5rem; }

    .tk-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
    .tk-item {
      background: #fff;
      border: 1px solid #e9efea;
      border-radius: 12px;
      padding: 18px 18px 16px;
      box-shadow: 0 4px 14px rgba(16, 40, 24, .05);
    }
    .tk-item .tk-ic {
      width: 44px; height: 44px;
      border-radius: 12px;
      display: grid; place-items: center;
      color: #fff; font-size: 18px;
      background: linear-gradient(135deg, #0a8f50, #00743e);
      box-shadow: 0 6px 14px rgba(0, 116, 62, .25);
      margin-bottom: 12px;
    }
    .tk-item h4 { font-weight: 700; color: #16321f; font-size: 1rem; margin-bottom: 8px; }
    .tk-item p, .tk-item li { color: #3a4a40; line-height: 1.65; font-size: .9rem; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>

    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4">

        <!-- HERO -->
        <div class="tk-hero">
          <span class="tk-logo-wrap">
            <img src="<?= base_url('assets/images/LogoTentang.png') ?>" alt="Logo AKSARA Kabupaten Pringsewu">
          </span>
          <h2 class="h3 fw-bold">Tentang AKSARA</h2>
          <p>Akuntabilitas Sistem Kinerja Aparatur &mdash; Kabupaten Pringsewu</p>
        </div>

        <div class="row g-4">
          <!-- Intro -->
          <div class="col-12">
            <div class="tk-card">
              <h3>Akuntabilitas Sistem Kinerja Aparatur Kabupaten Pringsewu</h3>
              <p class="mb-0">
                Logo <strong>AKSARA (Akuntabilitas Sistem Kinerja Aparatur)</strong> Kabupaten Pringsewu merupakan
                representasi visual dari komitmen pemerintah daerah dalam mewujudkan tata kelola pemerintahan yang
                akuntabel, transparan, dan berbasis kinerja, dengan tetap berlandaskan nilai-nilai kearifan lokal
                masyarakat Lampung.
              </p>
            </div>
          </div>

          <!-- Filosofis -->
          <div class="col-12">
            <div class="tk-card tk-accent">
              <h3>Makna Filosofis Utama</h3>
              <p class="mb-0">
                AKSARA dimaknai sebagai simbol pencatatan, pertanggungjawaban, dan keberlanjutan kinerja aparatur.
                Hal ini mencerminkan bahwa setiap proses pemerintahan tidak hanya dilaksanakan, tetapi juga harus
                terdokumentasi, terukur, dan dapat dipertanggungjawabkan secara sistematis.
              </p>
            </div>
          </div>

          <!-- Unsur Visual -->
          <div class="col-12">
            <div class="tk-card">
              <h3>Unsur Visual dan Makna Simbolik</h3>
              <div class="tk-grid">

                <div class="tk-item">
                  <div class="tk-ic"><i class="fas fa-crown"></i></div>
                  <h4>Siger (Mahkota Lampung)</h4>
                  <p>Melambangkan kehormatan &amp; martabat, kepemimpinan yang bijaksana, dan nilai luhur budaya Lampung. Sistem akuntabilitas dibangun di atas teknologi sekaligus berpijak pada nilai budaya lokal sebagai fondasi moral.</p>
                </div>

                <div class="tk-item">
                  <div class="tk-ic"><i class="fas fa-language"></i></div>
                  <h4>Aksara Lampung</h4>
                  <p>Mencerminkan identitas daerah, warisan budaya, dan kearifan lokal. Transformasi digital melalui e-SAKIP tetap menjaga dan mengintegrasikan nilai-nilai lokal dalam tata kelola modern.</p>
                </div>

                <div class="tk-item">
                  <div class="tk-ic"><i class="fas fa-shield-halved"></i></div>
                  <h4>Lambang Kabupaten Pringsewu</h4>
                  <p>Melambangkan otoritas pemerintahan daerah, keselarasan sistem kinerja dengan visi pembangunan, serta integrasi kebijakan strategis dengan implementasi kinerja.</p>
                </div>

                <div class="tk-item">
                  <div class="tk-ic"><i class="fas fa-book-open"></i></div>
                  <h4>Buku Terbuka</h4>
                  <p>Melambangkan transparansi, akuntabilitas, dan dokumentasi kinerja — merepresentasikan siklus perencanaan, pengukuran, pelaporan, dan evaluasi yang berkelanjutan.</p>
                </div>

                <div class="tk-item">
                  <div class="tk-ic"><i class="fas fa-border-all"></i></div>
                  <h4>Ornamen Tapis Lampung</h4>
                  <p>Mencerminkan ketelitian &amp; ketekunan, keteraturan sistem, dan nilai kerja keras — keberhasilan akuntabilitas kinerja menuntut proses yang konsisten, terstruktur, dan berkelanjutan.</p>
                </div>

                <div class="tk-item">
                  <div class="tk-ic"><i class="fas fa-palette"></i></div>
                  <h4>Warna Emas dan Merah</h4>
                  <p><strong>Emas</strong> melambangkan kejayaan, kemakmuran, dan kualitas tinggi; <strong>Merah</strong> melambangkan semangat, keberanian, dan komitmen mencapai target kinerja yang unggul dan berdaya saing.</p>
                </div>

              </div>
            </div>
          </div>

          <!-- Makna Strategis -->
          <div class="col-12">
            <div class="tk-card tk-accent">
              <h3>Makna Strategis</h3>
              <p class="mb-2">Logo AKSARA mencerminkan integrasi antara:</p>
              <ul>
                <li><strong>Budaya lokal</strong> sebagai landasan nilai</li>
                <li><strong>Sistem digital (e-SAKIP)</strong> sebagai alat</li>
                <li><strong>Kinerja berbasis outcome</strong> sebagai tujuan</li>
              </ul>
              <p class="mb-2">Melalui AKSARA, setiap kinerja aparatur harus:</p>
              <ul>
                <li>Direncanakan secara sistematis</li>
                <li>Diukur secara objektif</li>
                <li>Dilaporkan secara transparan</li>
                <li>Dievaluasi secara berkelanjutan</li>
              </ul>
              <p class="mb-0">
                Sehingga mampu memberikan dampak nyata bagi peningkatan kualitas pelayanan publik dan kesejahteraan
                masyarakat Kabupaten Pringsewu.
              </p>
            </div>
          </div>
        </div>

      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  </div>

</body>

</html>

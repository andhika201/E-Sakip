<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pengaturan Aplikasi - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .set-card { border: 1px solid #e6ece8; border-radius: 14px; padding: 20px 22px; background: #fff; height: 100%; }
    .set-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .set-card-head .ic {
      width: 42px; height: 42px; border-radius: 11px; flex: 0 0 auto;
      display: grid; place-items: center; color: #fff; font-size: 18px;
      background: linear-gradient(135deg, #0a8f50, #00743e);
    }
    .set-card-head h3 { margin: 0; font-weight: 700; font-size: 1.05rem; color: #16321f; }
    .set-card-head p { margin: 0; font-size: .8rem; color: #6b7a70; }
    .set-preview {
      width: 96px; height: 96px; border-radius: 12px; border: 1px solid #e3e8e4;
      background: #f6f9f7; display: grid; place-items: center; overflow: hidden; flex: 0 0 auto;
    }
    .set-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .form-label { font-weight: 600; font-size: .85rem; color: #3a4a40; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>

    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">Pengaturan Aplikasi</h2>

        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-triangle-exclamation me-1"></i> <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('adminkab/pengaturan/save') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="row g-4">

            <!-- Identitas Web -->
            <div class="col-12 col-lg-6">
              <div class="set-card">
                <div class="set-card-head">
                  <div class="ic"><i class="fas fa-globe"></i></div>
                  <div><h3>Identitas Web</h3><p>Nama aplikasi yang tampil di judul & header.</p></div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Nama Aplikasi (singkat)</label>
                  <input type="text" name="app_name" class="form-control" value="<?= esc($settings['app_name'] ?? '') ?>" placeholder="mis. e-SAKIP">
                </div>
                <div class="mb-0">
                  <label class="form-label">Nama Panjang / Tagline</label>
                  <input type="text" name="app_long_name" class="form-control" value="<?= esc($settings['app_long_name'] ?? '') ?>" placeholder="Sistem Akuntabilitas Kinerja Instansi Pemerintah">
                </div>
              </div>
            </div>

            <!-- Instansi & Footer -->
            <div class="col-12 col-lg-6">
              <div class="set-card">
                <div class="set-card-head">
                  <div class="ic"><i class="fas fa-building-columns"></i></div>
                  <div><h3>Instansi &amp; Footer</h3><p>Tampil di footer & kontak.</p></div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Nama Instansi</label>
                  <input type="text" name="instansi" class="form-control" value="<?= esc($settings['instansi'] ?? '') ?>" placeholder="Pemerintah Kabupaten Pringsewu">
                </div>
                <div class="mb-3">
                  <label class="form-label">Alamat</label>
                  <input type="text" name="instansi_address" class="form-control" value="<?= esc($settings['instansi_address'] ?? '') ?>">
                </div>
                <div class="row g-2 mb-0">
                  <div class="col-12 col-md-6">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="instansi_phone" class="form-control" value="<?= esc($settings['instansi_phone'] ?? '') ?>">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Email</label>
                    <input type="text" name="instansi_email" class="form-control" value="<?= esc($settings['instansi_email'] ?? '') ?>">
                  </div>
                </div>
              </div>
            </div>

            <!-- Logo & Favicon -->
            <div class="col-12 col-lg-6">
              <div class="set-card">
                <div class="set-card-head">
                  <div class="ic"><i class="fas fa-image"></i></div>
                  <div><h3>Logo &amp; Favicon</h3><p>Logo utama & ikon tab browser. PNG/SVG, maks 3 MB.</p></div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="set-preview">
                    <img src="<?= base_url($settings['app_logo'] ?? 'assets/images/LogoTentang.png') ?>" alt="Logo">
                  </div>
                  <div class="flex-fill">
                    <label class="form-label">Logo Aplikasi (AKSARA)</label>
                    <input type="file" name="app_logo" class="form-control" accept="image/*">
                    <small class="text-muted">Branding aplikasi (dashboard/login). Kosongkan jika tidak ingin mengganti.</small>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="set-preview">
                    <img src="<?= base_url($settings['kab_logo'] ?? 'assets/images/logo.png') ?>" alt="Logo Kabupaten">
                  </div>
                  <div class="flex-fill">
                    <label class="form-label">Logo Kabupaten (Kop Dokumen)</label>
                    <input type="file" name="kab_logo" class="form-control" accept="image/*">
                    <small class="text-muted">Lambang daerah untuk kop <strong>semua cetak PDF</strong>. Kosongkan untuk memakai lambang bawaan.</small>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-0">
                  <div class="set-preview" style="width:64px;height:64px;">
                    <img src="<?= base_url($settings['favicon'] ?? 'assets/images/sakipLogo.png') ?>" alt="Favicon">
                  </div>
                  <div class="flex-fill">
                    <label class="form-label">Favicon</label>
                    <input type="file" name="favicon" class="form-control" accept="image/*,.ico">
                    <small class="text-muted">Disarankan gambar persegi (mis. 512×512).</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pengembang -->
            <div class="col-12 col-lg-6">
              <div class="set-card">
                <div class="set-card-head">
                  <div class="ic"><i class="fas fa-code"></i></div>
                  <div><h3>Pengembang</h3><p>Logo pengembang & serial number di footer.</p></div>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="set-preview" style="background:#1b5e20;">
                    <img src="<?= base_url($settings['dev_logo'] ?? 'assets/images/devtech.png') ?>" alt="Logo Pengembang" style="filter: brightness(0) invert(1);">
                  </div>
                  <div class="flex-fill">
                    <label class="form-label">Logo Pengembang</label>
                    <input type="file" name="dev_logo" class="form-control" accept="image/*">
                    <small class="text-muted">Tampil putih di footer.</small>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Nama Pengembang</label>
                  <input type="text" name="dev_name" class="form-control" value="<?= esc($settings['dev_name'] ?? '') ?>" placeholder="DevTech">
                </div>
                <div class="mb-0">
                  <label class="form-label">Serial Number</label>
                  <input type="text" name="serial_number" class="form-control" value="<?= esc($settings['serial_number'] ?? '') ?>" placeholder="ESAKIP-2025-001">
                </div>
              </div>
            </div>

            <!-- SEO -->
            <div class="col-12">
              <div class="set-card">
                <div class="set-card-head">
                  <div class="ic"><i class="fas fa-magnifying-glass-chart"></i></div>
                  <div><h3>SEO</h3><p>Meta untuk mesin pencari & media sosial.</p></div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Meta Description</label>
                  <textarea name="seo_description" class="form-control" rows="2"><?= esc($settings['seo_description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Meta Keywords</label>
                  <textarea name="seo_keywords" class="form-control" rows="2"><?= esc($settings['seo_keywords'] ?? '') ?></textarea>
                  <small class="text-muted">Pisahkan dengan koma.</small>
                </div>
                <div class="mb-0">
                  <label class="form-label">Author</label>
                  <input type="text" name="seo_author" class="form-control" value="<?= esc($settings['seo_author'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Integrasi AI -->
            <div class="col-12">
              <div class="set-card">
                <div class="set-card-head">
                  <div class="ic"><i class="fas fa-robot"></i></div>
                  <div><h3>Integrasi AI (Gemini)</h3><p>Untuk fitur <strong>Analisis AI</strong>. Dapatkan API key gratis di Google AI Studio.</p></div>
                </div>

                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" role="switch" id="aiDashToggle"
                    name="ai_dashboard_enabled" value="1"
                    <?= (($settings['ai_dashboard_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="aiDashToggle">
                    Tampilkan widget <strong>Analisis AI</strong> di Dashboard
                  </label>
                  <div><small class="text-muted">Jika dimatikan, widget Analisis AI tidak muncul di dashboard (halaman menu Analisis AI tetap bisa diakses).</small></div>
                </div>

                <div class="row g-2">
                  <div class="col-12 col-md-8">
                    <label class="form-label">API Key Gemini</label>
                    <input type="password" name="gemini_api_key" class="form-control" autocomplete="off"
                      value="<?= esc($settings['gemini_api_key'] ?? '') ?>" placeholder="AIza...">
                    <small class="text-muted">Disimpan di server. Kosongkan untuk menonaktifkan Analisis AI.</small>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Model</label>
                    <?php
                      $curModel  = $settings['gemini_model'] ?? 'gemini-2.0-flash';
                      $modelOpts = !empty($availableModels) ? $availableModels
                          : ['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.0-flash', 'gemini-2.0-flash-lite'];
                      if ($curModel && !in_array($curModel, $modelOpts, true)) {
                          array_unshift($modelOpts, $curModel);
                      }
                    ?>
                    <select name="gemini_model" class="form-select">
                      <?php foreach ($modelOpts as $m): ?>
                        <option value="<?= esc($m) ?>" <?= $curModel === $m ? 'selected' : '' ?>><?= esc($m) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php if (!empty($availableModels)): ?>
                      <small class="text-success"><i class="fas fa-circle-check me-1"></i><?= count($availableModels) ?> model tersedia untuk API key ini.</small>
                    <?php else: ?>
                      <small class="text-muted">Setelah API key disimpan, daftar model diisi otomatis dari key Anda.</small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-success px-4">
              <i class="fas fa-floppy-disk me-1"></i> Simpan Pengaturan
            </button>
          </div>
        </form>
      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  </div>

</body>

</html>

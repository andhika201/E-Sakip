<?php
/**
 * Super Admin — Pengaturan Dashboard > Ambang Status Capaian.
 * Satu-satunya tempat mengubah rentang & warna status capaian dashboard.
 */
helper('dashboard_status');
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ambang Status Capaian - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .thr-card { border: 1px solid #e6ece8; border-radius: 14px; padding: 18px 20px; background: #fff; }
    .thr-card + .thr-card { margin-top: 14px; }
    .thr-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .thr-dot { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: 17px; flex: 0 0 auto; }
    .thr-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .72rem; color: #6b7a70; background: #f2f5f3; padding: 2px 8px; border-radius: 6px; }
    .form-label { font-weight: 600; font-size: .82rem; color: #3a4a40; }
    .thr-preview { display: flex; flex-wrap: wrap; gap: 8px; }
    .thr-chip { font-size: .76rem; font-weight: 700; padding: .35em .7em; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; }
    .thr-note { font-size: .78rem; color: #6b7a70; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-1">Ambang Status Capaian</h2>
        <p class="text-center text-muted small mb-4">
          Rentang persentase yang menentukan status &amp; warna capaian pada Dashboard Perangkat Daerah.
        </p>

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

        <div class="alert alert-light border">
          <div class="fw-semibold mb-1"><i class="fas fa-circle-info me-1 text-success"></i> Aturan pengisian</div>
          <ul class="mb-0 thr-note">
            <li>Nilai minimum harus lebih kecil atau sama dengan maksimum.</li>
            <li>Rentang antar status aktif tidak boleh tumpang tindih dan tidak boleh ada celah (selisihnya tepat 0,01).</li>
            <li>Hanya satu status yang boleh dibiarkan tanpa batas maksimum, dan status itu harus yang tertinggi.</li>
            <li>Kode status tidak dapat diubah karena dirujuk langsung oleh program; nama, warna, dan ikonnya bebas disesuaikan.</li>
            <li>Warna hanya dapat dipilih dari palet yang tersedia — bukan CSS bebas.</li>
          </ul>
        </div>

        <!-- Pratinjau susunan yang sedang berlaku -->
        <div class="thr-preview mb-4">
          <?php foreach ($rows as $r): ?>
            <?php $w = dash_color($r['color'] ?? null); ?>
            <span class="thr-chip" style="background: <?= esc($w['soft']) ?>; color: <?= esc($w['hex']) ?>; <?= (int) ($r['is_active'] ?? 1) === 1 ? '' : 'opacity:.45;' ?>">
              <?php if (!empty($r['icon'])): ?><i class="fas <?= esc($r['icon']) ?>"></i><?php endif; ?>
              <?= esc($r['name']) ?>
              <span style="font-weight:600;opacity:.8;">
                <?= $r['min_value'] === null ? '&minus;∞' : esc(rtrim(rtrim(number_format((float) $r['min_value'], 2, ',', '.'), '0'), ',')) ?>
                &ndash;
                <?= $r['max_value'] === null ? '∞' : esc(rtrim(rtrim(number_format((float) $r['max_value'], 2, ',', '.'), '0'), ',')) ?>
              </span>
            </span>
          <?php endforeach; ?>
        </div>

        <form method="post" action="<?= base_url('adminkab/dashboard-thresholds/save') ?>">
          <?= csrf_field() ?>

          <?php foreach ($rows as $r): ?>
            <?php $w = dash_color($r['color'] ?? null); $code = esc($r['code']); ?>
            <div class="thr-card">
              <div class="thr-head">
                <div class="thr-dot" style="background: <?= esc($w['hex']) ?>">
                  <i class="fas <?= esc($r['icon'] ?: 'fa-circle') ?>"></i>
                </div>
                <div class="flex-fill">
                  <div class="fw-bold" style="font-size:.98rem;color:#16321f;"><?= esc($r['name']) ?></div>
                  <span class="thr-code">code: <?= $code ?></span>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch"
                         id="aktif-<?= $code ?>" name="rows[<?= $code ?>][is_active]" value="1"
                         <?= (int) ($r['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="aktif-<?= $code ?>">Aktif</label>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-12 col-lg-4">
                  <label class="form-label" for="nama-<?= $code ?>">Nama Status</label>
                  <input type="text" class="form-control" id="nama-<?= $code ?>"
                         name="rows[<?= $code ?>][name]" value="<?= esc($r['name']) ?>" maxlength="100" required>
                </div>
                <div class="col-6 col-lg-2">
                  <label class="form-label" for="min-<?= $code ?>">Minimum (%)</label>
                  <input type="text" inputmode="decimal" class="form-control" id="min-<?= $code ?>"
                         name="rows[<?= $code ?>][min_value]"
                         value="<?= $r['min_value'] === null ? '' : esc((string) (float) $r['min_value']) ?>"
                         placeholder="kosong = tanpa batas">
                </div>
                <div class="col-6 col-lg-2">
                  <label class="form-label" for="max-<?= $code ?>">Maksimum (%)</label>
                  <input type="text" inputmode="decimal" class="form-control" id="max-<?= $code ?>"
                         name="rows[<?= $code ?>][max_value]"
                         value="<?= $r['max_value'] === null ? '' : esc((string) (float) $r['max_value']) ?>"
                         placeholder="kosong = tanpa batas">
                </div>
                <div class="col-6 col-lg-2">
                  <label class="form-label" for="warna-<?= $code ?>">Warna</label>
                  <select class="form-select" id="warna-<?= $code ?>" name="rows[<?= $code ?>][color]" data-no-select2>
                    <?php foreach ($colors as $slug => $c): ?>
                      <option value="<?= esc($slug) ?>" <?= $slug === ($r['color'] ?? '') ? 'selected' : '' ?>><?= esc($c['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6 col-lg-2">
                  <label class="form-label" for="ikon-<?= $code ?>">Ikon</label>
                  <select class="form-select" id="ikon-<?= $code ?>" name="rows[<?= $code ?>][icon]" data-no-select2>
                    <option value="">— tanpa ikon —</option>
                    <?php foreach ($icons as $ikon => $label): ?>
                      <option value="<?= esc($ikon) ?>" <?= $ikon === ($r['icon'] ?? '') ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <input type="hidden" name="rows[<?= $code ?>][sort_order]" value="<?= (int) ($r['sort_order'] ?? 0) ?>">
            </div>
          <?php endforeach; ?>

          <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
            <button type="submit" class="btn btn-success"><i class="fas fa-floppy-disk me-1"></i> Simpan Perubahan</button>
          </div>
        </form>

        <form method="post" action="<?= base_url('adminkab/dashboard-thresholds/reset') ?>" class="mt-2 text-end"
              onsubmit="return confirm('Kembalikan seluruh ambang status ke konfigurasi bawaan?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-rotate-left me-1"></i> Reset ke Default
          </button>
        </form>
      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
  </div>
</body>

</html>

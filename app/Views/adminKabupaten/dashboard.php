<?php
/**
 * Dashboard Pengendalian Kinerja Kabupaten.
 *
 * Dua mode dalam satu halaman:
 *   - MODE KABUPATEN (filter OPD = semua)
 *   - MODE FOKUS OPD (satu OPD dipilih; kartu & grafik berganti konteks,
 *     tetap di area /adminkab, tanpa berpindah halaman)
 *
 * Halaman ini RINGKASAN + NAVIGASI: tidak ada form input PK / Rencana Aksi /
 * MONEV / LAKIP, dan tidak ada tabel besar. Rincian dibuka lewat drawer.
 *
 * Data disiapkan App\Services\KabupatenDashboardService (lihat AdminKabupatenController).
 */
helper(['capaian', 'dashboard_status', 'format']);

$fokusMode = ($scope['mode'] ?? 'kabupaten') === 'fokus_opd';
$canWrite  = (bool) ($scope['can_write'] ?? false);
$romawi    = ['', 'I', 'II', 'III', 'IV'];
$ctx       = $fokusMode ? ($fokus['context'] ?? []) : ($dash['context'] ?? []);

$filterQs = http_build_query(array_filter([
    'opd_id'   => $scope['opd_id'] ?? null,
    'tahun'    => $tahun,
    'triwulan' => $triwulan,
    'misi_id'  => $misiId ?: null,
]));

/** Bungkus JSON aman untuk ditanam di <script>. */
$js = static fn ($v) => json_encode(
    $v,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Dashboard Kinerja Kabupaten') ?></title>

  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <?= $this->include('templates/dashboard_kit') ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">

      <!-- ======================= HEADER ======================= -->
      <div class="dash-hero mb-3">
        <div class="dh-ic"><i class="fas fa-gauge-high"></i></div>
        <div class="flex-fill">
          <h2>Dashboard Pengendalian Kinerja</h2>
          <p>
            <span class="dash-mode">
              <i class="fas <?= $fokusMode ? 'fa-crosshairs' : 'fa-city' ?>"></i>
              <?= $fokusMode ? 'Mode Fokus OPD' : 'Mode Kabupaten' ?>
            </span>
            &nbsp;<?= $fokusMode ? esc($scope['opd_nama']) : 'Seluruh Perangkat Daerah' ?>
            &middot; Tahun <?= (int) $tahun ?> s.d. Triwulan <?= esc($romawi[$triwulan] ?? '') ?>
            <?php if (!$canWrite): ?>
              &middot; <span class="dash-mode"><i class="fas fa-eye"></i> Hanya baca</span>
            <?php endif; ?>
          </p>
        </div>
        <?php if (!empty($ctx['last_update'])): ?>
          <div class="text-white-50 small text-end">
            <div>Pembaruan data terakhir</div>
            <div class="fw-semibold text-white"><?= esc(formatTanggal($ctx['last_update'])) ?></div>
          </div>
        <?php endif; ?>
      </div>

      <!-- ======================= FILTER GLOBAL ======================= -->
      <form method="get" class="dash-filter mb-4">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-4 col-lg-4">
            <label for="f-opd" class="form-label mb-1">Perangkat Daerah</label>
            <select name="opd_id" id="f-opd" class="form-select">
              <option value="">Semua OPD — Mode Kabupaten</option>
              <?php foreach ($scope['opd_list'] as $o): ?>
                <option value="<?= (int) $o['id'] ?>" <?= (int) $o['id'] === (int) ($scope['opd_id'] ?? 0) ? 'selected' : '' ?>>
                  <?= esc($o['nama_opd']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-md-3 col-lg-2">
            <label for="f-tahun" class="form-label mb-1">Tahun</label>
            <select name="tahun" id="f-tahun" class="form-select">
              <?php foreach ($tahunList as $t): ?>
                <option value="<?= (int) $t ?>" <?= (int) $t === (int) $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-md-3 col-lg-2">
            <label for="f-tw" class="form-label mb-1">Triwulan</label>
            <select name="triwulan" id="f-tw" class="form-select">
              <?php foreach ([1, 2, 3, 4] as $q): ?>
                <option value="<?= $q ?>" <?= $q === (int) $triwulan ? 'selected' : '' ?>>s.d. Triwulan <?= $romawi[$q] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-6 col-lg-2">
            <label for="f-misi" class="form-label mb-1">Misi Bupati</label>
            <select name="misi_id" id="f-misi" class="form-select" <?= $fokusMode ? 'disabled' : '' ?>>
              <option value="">Semua misi</option>
              <?php foreach ($misiList as $i => $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= (int) $m['id'] === (int) $misiId ? 'selected' : '' ?>>
                  Misi <?= $i + 1 ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-2 d-grid">
            <button type="submit" class="btn btn-success"><i class="fas fa-filter me-1"></i> Terapkan</button>
          </div>
        </div>
      </form>

      <?php if ($fokusMode): ?>
        <?php
          $fPk       = $fokus['pk'];
          $fCapaian  = $fokus['capaian'];
          $fAnggaran = $fokus['anggaran'];
          $fWasp     = $fokus['perhatian'];
          $fMisi     = $fokus['misi'];
          $adaDataFokus = ($fPk['indikator'] ?? 0) > 0;
        ?>

        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
          <a href="<?= base_url('adminkab/dashboard?tahun=' . (int) $tahun . '&triwulan=' . (int) $triwulan) ?>"
             class="btn btn-sm btn-outline-success">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Mode Kabupaten
          </a>
          <span class="text-muted small">Menampilkan konteks <strong><?= esc($scope['opd_nama']) ?></strong>.</span>
        </div>

        <?php if (!$adaDataFokus): ?>
          <div class="panel">
            <div class="empty">
              <div class="ic"><i class="fas fa-file-signature"></i></div>
              <p class="mb-1 fw-semibold">Belum ada Perjanjian Kinerja pimpinan untuk tahun <?= (int) $tahun ?>.</p>
              <p class="mb-0 small"><?= esc($scope['opd_nama']) ?> belum memiliki indikator PK yang dapat dipantau pada tahun ini.</p>
            </div>
          </div>
        <?php else: ?>

          <!-- ============ EMPAT KARTU (FOKUS OPD) ============ -->
          <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
              <button type="button" class="kpi" data-drawer="f_pk">
                <div class="kpi-head">
                  <div class="kpi-ic" style="background: linear-gradient(135deg,#0a8f50,#00743e);"><i class="fas fa-file-signature"></i></div>
                  <div class="kpi-title">PK &amp; Kontribusi Misi</div>
                </div>
                <div>
                  <div class="kpi-num"><?= (int) $fPk['indikator'] ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Indikator PK</span></div>
                  <div class="kpi-sub mt-2">
                    <div><span class="dot" style="background:#0a8f50"></span><?= (int) $fPk['mendukung_misi'] ?> mendukung Misi Bupati</div>
                    <div><span class="dot" style="background:#e07b39"></span><?= (int) $fPk['tanpa_renaksi'] ?> belum memiliki Rencana Aksi</div>
                  </div>
                </div>
                <div class="kpi-foot">Lihat indikator <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
              </button>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <button type="button" class="kpi" data-drawer="f_capaian">
                <div class="kpi-head">
                  <div class="kpi-ic" style="background: linear-gradient(135deg,#3f6296,#2f4d7a);"><i class="fas fa-bullseye"></i></div>
                  <div class="kpi-title">Capaian OPD</div>
                </div>
                <div>
                  <?php if ($fCapaian['can_compute']): ?>
                    <div class="kpi-num" style="color:<?= esc($fCapaian['status']['color_hex']) ?>"><?= esc(capaianFormatPersen($fCapaian['total'])) ?></div>
                    <div class="kpi-sub mt-2">
                      <div><?= (int) $fCapaian['valid'] ?> dari <?= (int) $fCapaian['wajib'] ?> indikator valid</div>
                      <div class="fw-semibold" style="color:<?= esc($fCapaian['status']['color_hex']) ?>">
                        <?= esc($fCapaian['label']) ?><?= $fCapaian['belum_verifikasi'] > 0 ? ' — ' . (int) $fCapaian['belum_verifikasi'] . ' belum diverifikasi' : '' ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="kpi-num sm text-muted">Belum dapat dihitung</div>
                    <div class="kpi-sub mt-2">
                      <div><?= (int) $fCapaian['valid'] ?> dari <?= (int) $fCapaian['wajib'] ?> indikator valid</div>
                      <div class="fw-semibold text-warning-emphasis"><?= (int) $fCapaian['belum_valid'] ?> indikator perlu dilengkapi</div>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="kpi-foot">Rincian indikator <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
              </button>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <button type="button" class="kpi" data-drawer="f_anggaran">
                <div class="kpi-head">
                  <div class="kpi-ic" style="background: linear-gradient(135deg,#c98a3c,#a86a26);"><i class="fas fa-sack-dollar"></i></div>
                  <div class="kpi-title">Penyerapan Anggaran</div>
                </div>
                <div>
                  <div class="kpi-num<?= $fAnggaran['persen'] === null ? ' sm text-muted' : '' ?>">
                    <?= $fAnggaran['persen'] !== null ? esc(capaianFormatPersen($fAnggaran['persen'])) : 'Belum ada realisasi' ?>
                  </div>
                  <div class="kpi-sub mt-2">
                    <div><?= esc(formatRupiah($fAnggaran['realisasi'] ?? 0)) ?> dari <?= esc(formatRupiah($fAnggaran['anggaran'])) ?></div>
                    <div><?= (int) $fAnggaran['program_count'] ?> program pendukung PK</div>
                  </div>
                </div>
                <div class="kpi-foot">Rincian program <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
              </button>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <button type="button" class="kpi" data-drawer="f_perhatian">
                <div class="kpi-head">
                  <div class="kpi-ic" style="background: linear-gradient(135deg,#d64545,#b13333);"><i class="fas fa-triangle-exclamation"></i></div>
                  <div class="kpi-title">Perlu Perhatian</div>
                </div>
                <div>
                  <div class="kpi-num"><?= (int) $fWasp['total'] ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Tindak Lanjut</span></div>
                  <div class="kpi-sub mt-2">
                    <?php if ($fWasp['kritis'] > 0): ?><div><span class="dot" style="background:#d64545"></span><?= (int) $fWasp['kritis'] ?> indikator kritis</div><?php endif; ?>
                    <?php if ($fWasp['monev_belum'] > 0): ?><div><span class="dot" style="background:#e07b39"></span><?= (int) $fWasp['monev_belum'] ?> MONEV belum lengkap</div><?php endif; ?>
                    <?php if ($fWasp['renaksi_belum'] > 0): ?><div><span class="dot" style="background:#d9a520"></span><?= (int) $fWasp['renaksi_belum'] ?> Rencana Aksi belum ada</div><?php endif; ?>
                    <?php if ($fWasp['total'] === 0): ?><div class="text-success fw-semibold">Tidak ada kondisi yang perlu ditindaklanjuti.</div><?php endif; ?>
                  </div>
                </div>
                <div class="kpi-foot">Buka daftar prioritas <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
              </button>
            </div>
          </div>

          <!-- ============ DUA GRAFIK (FOKUS OPD) ============ -->
          <div class="row g-3 mb-4">
            <div class="col-12 col-lg-5">
              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h3>Distribusi Status Indikator</h3>
                    <p><?= esc($fokus['status_distribution']['caption']) ?></p>
                  </div>
                </div>
                <div class="chart-box"><canvas id="chartStatus"></canvas></div>
                <div id="legendStatus" class="d-flex flex-wrap gap-2 justify-content-center mt-3"></div>
              </div>
            </div>
            <div class="col-12 col-lg-7">
              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h3>Target vs Capaian Triwulanan</h3>
                    <p>Satu indikator pada satu waktu — satuan &amp; metode perhitungannya berbeda.</p>
                  </div>
                  <div style="min-width: 240px; flex: 1 1 240px;">
                    <select id="serialPicker" class="form-select form-select-sm" data-no-select2 aria-label="Pilih indikator"></select>
                  </div>
                </div>
                <div class="chart-box"><canvas id="chartTriwulan"></canvas></div>
                <p id="serialInfo" class="text-muted mt-3 mb-0" style="font-size:.76rem;"></p>
              </div>
            </div>
          </div>

          <!-- ============ PANEL (FOKUS OPD) ============ -->
          <div class="row g-3">
            <div class="col-12 col-xl-6">
              <div class="panel">
                <div class="panel-head">
                  <div><h3>Prioritas Tindak Lanjut OPD</h3><p>Disusun otomatis dari aturan kelengkapan &amp; capaian.</p></div>
                  <?php if (count($fokus['insights']) > 5): ?>
                    <button type="button" class="btn btn-sm btn-outline-success" data-drawer="f_perhatian">Lihat semua (<?= count($fokus['insights']) ?>)</button>
                  <?php endif; ?>
                </div>
                <?php if ($fokus['insights'] === []): ?>
                  <div class="empty"><div class="ic"><i class="fas fa-circle-check"></i></div>
                    <p class="mb-0 small">Tidak ada kondisi yang perlu ditindaklanjuti.</p></div>
                <?php else: ?>
                  <?php foreach (array_slice($fokus['insights'], 0, 5) as $ins): ?>
                    <div class="ins">
                      <div class="ins-bar" style="background: <?= esc($ins['color']['hex']) ?>"></div>
                      <div class="ins-body">
                        <div class="ins-title"><?= esc($ins['judul']) ?></div>
                        <div class="ins-why"><?= esc($ins['alasan']) ?></div>
                        <span class="badge-soft mt-2" style="background: <?= esc($ins['color']['soft']) ?>; color: <?= esc($ins['color']['hex']) ?>;"><?= esc($ins['status']) ?></span>
                      </div>
                      <div class="ins-act">
                        <a href="<?= esc($ins['url']) ?>" class="btn btn-sm btn-outline-success"><?= $canWrite ? esc($ins['tombol']) : 'Lihat' ?></a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-12 col-xl-6">
              <div class="panel">
                <div class="panel-head"><div><h3>Kontribusi terhadap Misi Bupati</h3><p>Dari keterkaitan PK &amp; Misi RPJMD.</p></div></div>
                <?php if ($fMisi['items'] === [] && $fMisi['tanpa_misi'] === []): ?>
                  <div class="empty"><div class="ic"><i class="fas fa-diagram-project"></i></div>
                    <p class="mb-0 small">Belum ada indikator yang dapat dipetakan ke Misi Bupati.</p></div>
                <?php else: ?>
                  <?php foreach ($fMisi['items'] as $m): ?>
                    <div class="misi-item">
                      <button type="button" class="misi-btn" data-drawer="f_misi" data-misi="<?= (int) $m['misi_id'] ?>">
                        <span class="misi-no"><?= (int) $m['nomor'] ?></span>
                        <span class="flex-fill">
                          <span class="d-block fw-bold" style="font-size:.86rem;color:#1d2b23;">Misi <?= (int) $m['nomor'] ?></span>
                          <span class="d-block text-muted" style="font-size:.79rem;line-height:1.4;"><?= esc($m['misi']) ?></span>
                          <span class="d-block mt-2" style="font-size:.77rem;color:#5d6b62;">
                            <strong><?= (int) $m['indikator'] ?></strong> indikator &middot; <?= (int) $m['valid'] ?> valid &middot; <?= (int) $m['belum'] ?> belum lengkap
                            <?php if ($m['sumber'] === 'renstra'): ?>
                              <span class="badge-soft ms-1" style="background:#f1f3f2;color:#6b7a70;">via Renstra</span>
                            <?php endif; ?>
                          </span>
                        </span>
                        <i class="fas fa-chevron-right text-muted mt-1" style="font-size:.7rem"></i>
                      </button>
                    </div>
                  <?php endforeach; ?>
                  <?php if ($fMisi['tanpa_misi'] !== []): ?>
                    <div class="misi-item">
                      <button type="button" class="misi-btn" data-drawer="f_misi" data-misi="0">
                        <span class="misi-no" style="background:#f1f3f2;color:#8a968f;"><i class="fas fa-question"></i></span>
                        <span class="flex-fill">
                          <span class="d-block fw-bold" style="font-size:.86rem;color:#1d2b23;">Belum memiliki keterkaitan Misi Bupati</span>
                          <span class="d-block mt-1" style="font-size:.77rem;color:#5d6b62;"><strong><?= count($fMisi['tanpa_misi']) ?></strong> indikator belum terpetakan.</span>
                        </span>
                        <i class="fas fa-chevron-right text-muted mt-1" style="font-size:.7rem"></i>
                      </button>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <?php
          $pkB   = $dash['pk_bupati'];
          $opdR  = $dash['opd'];
          $telat = $dash['belum_update'];
          $prio  = $dash['prioritas'];
          $misiK = $dash['misi'];
        ?>

        <!-- ============ EMPAT KARTU (MODE KABUPATEN) ============ -->
        <div class="row g-3 mb-4">

          <!-- 1. Capaian PK Bupati -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="pk_bupati">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#0a8f50,#00743e);"><i class="fas fa-award"></i></div>
                <div class="kpi-title">Capaian PK Bupati</div>
              </div>
              <div>
                <?php if (!$pkB['ada']): ?>
                  <div class="kpi-num sm text-muted">Belum ada PK Bupati</div>
                  <div class="kpi-sub mt-2"><div>Tahun <?= (int) $tahun ?> belum memiliki dokumen PK Bupati.</div></div>
                <?php elseif ($pkB['can_compute']): ?>
                  <div class="kpi-num" style="color:<?= esc($pkB['status']['color_hex']) ?>"><?= esc(capaianFormatPersen($pkB['total'])) ?></div>
                  <div class="kpi-sub mt-2">
                    <div><?= (int) $pkB['valid'] ?> dari <?= (int) $pkB['wajib'] ?> indikator valid</div>
                    <div class="fw-semibold" style="color:<?= esc($pkB['status']['color_hex']) ?>">
                      <?= esc($pkB['label']) ?><?= $pkB['belum_verifikasi'] > 0 ? ' — ' . (int) $pkB['belum_verifikasi'] . ' belum diverifikasi' : '' ?>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="kpi-num sm text-muted">Belum dapat dihitung</div>
                  <div class="kpi-sub mt-2">
                    <div><?= (int) $pkB['valid'] ?> dari <?= (int) $pkB['wajib'] ?> indikator valid</div>
                    <div class="fw-semibold text-warning-emphasis"><?= (int) $pkB['belum_valid'] ?> indikator perlu dilengkapi</div>
                    <?php if ($pkB['formula_gap'] > 0): ?>
                      <div><span class="dot" style="background:#8a968f"></span><?= (int) $pkB['formula_gap'] ?> formula belum tersedia</div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="kpi-foot">Lihat indikator PK Bupati <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>

          <!-- 2. Status Perangkat Daerah -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="opd">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#3f6296,#2f4d7a);"><i class="fas fa-building-columns"></i></div>
                <div class="kpi-title">Status Perangkat Daerah</div>
              </div>
              <div>
                <div class="kpi-num"><?= (int) $opdR['total'] ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Perangkat Daerah</span></div>
                <div class="kpi-sub mt-2">
                  <div><?= (int) $opdR['dapat_dinilai'] ?> dapat dinilai &middot; <?= (int) $opdR['belum_lengkap'] ?> belum lengkap</div>
                  <div>
                    <span class="dot" style="background:#d64545"></span><?= (int) $opdR['kritis'] ?> kritis
                    &nbsp;<span class="dot" style="background:#e07b39"></span><?= (int) $opdR['perhatian'] ?> perlu perhatian
                    &nbsp;<span class="dot" style="background:#0a8f50"></span><?= (int) $opdR['terkendali'] ?> terkendali
                  </div>
                </div>
              </div>
              <div class="kpi-foot">Daftar Perangkat Daerah <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>

          <!-- 3. OPD Belum Update -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="belum_update">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#c98a3c,#a86a26);"><i class="fas fa-clock-rotate-left"></i></div>
                <div class="kpi-title">OPD Belum Update</div>
              </div>
              <div>
                <div class="kpi-num"><?= (int) $telat['total'] ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Perangkat Daerah</span></div>
                <div class="kpi-sub mt-2">
                  <?php if ($telat['belum_pernah'] > 0): ?><div><span class="dot" style="background:#d64545"></span><?= (int) $telat['belum_pernah'] ?> belum pernah input MONEV</div><?php endif; ?>
                  <?php if ($telat['belum_periode'] > 0): ?><div><span class="dot" style="background:#e07b39"></span><?= (int) $telat['belum_periode'] ?> belum lengkap Triwulan <?= esc($romawi[$triwulan]) ?></div><?php endif; ?>
                  <?php if ($telat['terlambat'] > 0): ?><div><span class="dot" style="background:#d9a520"></span><?= (int) $telat['terlambat'] ?> lebih dari <?= (int) $telat['batas_hari'] ?> hari tidak diperbarui</div><?php endif; ?>
                  <div><?= (int) $telat['indikator_belum'] ?> indikator belum diinput pada periode ini</div>
                </div>
              </div>
              <div class="kpi-foot">Lihat daftar <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>

          <!-- 4. Prioritas Pimpinan -->
          <div class="col-12 col-sm-6 col-xl-3">
            <button type="button" class="kpi" data-drawer="prioritas">
              <div class="kpi-head">
                <div class="kpi-ic" style="background: linear-gradient(135deg,#d64545,#b13333);"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="kpi-title">Prioritas Pimpinan</div>
              </div>
              <div>
                <div class="kpi-num"><?= count($prio) ?> <span style="font-size:.9rem;font-weight:700;color:#6b7a70;">Prioritas</span></div>
                <div class="kpi-sub mt-2">
                  <?php
                    $hitung = static fn (array $p, string $c) => count(array_filter($p, static fn ($x) => $x['code'] === $c));
                    $baris = array_values(array_filter([
                        $hitung($prio, 'pk_bupati_kritis') > 0 ? ['#d64545', $hitung($prio, 'pk_bupati_kritis') . ' indikator PK Bupati kritis'] : null,
                        $hitung($prio, 'opd_kritis') > 0 ? ['#d64545', $hitung($prio, 'opd_kritis') . ' OPD kritis'] : null,
                        $hitung($prio, 'opd_belum_update') > 0 ? ['#e07b39', $hitung($prio, 'opd_belum_update') . ' OPD belum update'] : null,
                        $hitung($prio, 'serap_tinggi_capaian_rendah') > 0 ? ['#3f6296', $hitung($prio, 'serap_tinggi_capaian_rendah') . ' OPD penyerapan tinggi, capaian rendah'] : null,
                        $hitung($prio, 'pk_bupati_formula') > 0 ? ['#8a968f', $hitung($prio, 'pk_bupati_formula') . ' formula belum tersedia'] : null,
                    ]));
                  ?>
                  <?php foreach (array_slice($baris, 0, 4) as $b): ?>
                    <div><span class="dot" style="background:<?= esc($b[0]) ?>"></span><?= esc($b[1]) ?></div>
                  <?php endforeach; ?>
                  <?php if ($baris === []): ?><div class="text-success fw-semibold">Tidak ada isu prioritas.</div><?php endif; ?>
                </div>
              </div>
              <div class="kpi-foot">Buka daftar prioritas <i class="fas fa-chevron-right ms-1" style="font-size:.65rem"></i></div>
            </button>
          </div>
        </div>

        <!-- ============ DUA GRAFIK (MODE KABUPATEN) ============ -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-5">
            <div class="panel">
              <div class="panel-head">
                <div><h3>Distribusi Status Perangkat Daerah</h3><p><?= esc($dash['distribusi']['caption']) ?></p></div>
              </div>
              <div class="chart-box"><canvas id="chartStatusOpd"></canvas></div>
              <div id="legendStatusOpd" class="d-flex flex-wrap gap-2 justify-content-center mt-3"></div>
              <div class="d-grid mt-3">
                <button type="button" class="btn btn-sm btn-outline-success" data-drawer="anggaran_kinerja">
                  <i class="fas fa-chart-simple me-1"></i> Lihat Analisis Anggaran dan Kinerja
                </button>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <div class="panel">
              <div class="panel-head">
                <div><h3>Tren Indikator PK Bupati</h3><p>Target dan realisasi PK per triwulan, satu indikator pada satu waktu.</p></div>
                <div style="min-width: 240px; flex: 1 1 240px;">
                  <select id="trenPicker" class="form-select form-select-sm" data-no-select2 aria-label="Pilih indikator PK Bupati"></select>
                </div>
              </div>
              <div class="chart-box"><canvas id="chartTren"></canvas></div>
              <p id="trenInfo" class="text-muted mt-3 mb-0" style="font-size:.76rem;"></p>
            </div>
          </div>
        </div>

        <!-- ============ PANEL (MODE KABUPATEN) ============ -->
        <div class="row g-3">
          <div class="col-12 col-xl-6">
            <div class="panel">
              <div class="panel-head">
                <div><h3>Prioritas Pimpinan</h3><p>Berbasis aturan, diurutkan dari risiko tertinggi.</p></div>
                <?php if (count($prio) > 5): ?>
                  <button type="button" class="btn btn-sm btn-outline-success" data-drawer="prioritas">Lihat semua (<?= count($prio) ?>)</button>
                <?php endif; ?>
              </div>
              <?php if ($prio === []): ?>
                <div class="empty"><div class="ic"><i class="fas fa-circle-check"></i></div>
                  <p class="mb-0 small">Tidak ada isu yang perlu ditindaklanjuti pada periode ini.</p></div>
              <?php else: ?>
                <?php foreach (array_slice($prio, 0, 5) as $ins): ?>
                  <div class="ins">
                    <div class="ins-bar" style="background: <?= esc($ins['color']['hex']) ?>"></div>
                    <div class="ins-body">
                      <div class="ins-title"><?= esc($ins['judul']) ?></div>
                      <div class="ins-why"><?= esc($ins['alasan']) ?></div>
                      <div class="ins-why"><?= esc($ins['objek']) ?></div>
                      <span class="badge-soft mt-2" style="background: <?= esc($ins['color']['soft']) ?>; color: <?= esc($ins['color']['hex']) ?>;"><?= esc($ins['status']) ?></span>
                    </div>
                    <div class="ins-act">
                      <a href="<?= esc($ins['url']) ?>" class="btn btn-sm btn-outline-success"><?= esc($ins['tombol']) ?></a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-12 col-xl-6">
            <div class="panel">
              <div class="panel-head">
                <div><h3>Kontribusi terhadap Misi Bupati</h3><p>Dari relasi PK &amp; Misi RPJMD — bukan pencocokan teks.</p></div>
              </div>

              <?php if ($misiK['gap_pk_bupati']): ?>
                <div class="alert alert-light border py-2 px-3 mb-3" style="font-size:.77rem;">
                  <i class="fas fa-circle-info text-success me-1"></i>
                  Dokumen PK Bupati belum dipetakan ke Misi RPJMD (tabel <code>pk_misi</code>), sehingga jumlah indikator PK Bupati per misi masih 0.
                  Kontribusi di bawah dihitung dari PK pimpinan Perangkat Daerah.
                </div>
              <?php endif; ?>

              <?php if ($misiK['items'] === []): ?>
                <div class="empty"><div class="ic"><i class="fas fa-diagram-project"></i></div>
                  <p class="mb-0 small">Belum ada Misi RPJMD yang berlaku pada tahun <?= (int) $tahun ?>.</p></div>
              <?php else: ?>
                <?php foreach ($misiK['items'] as $m): ?>
                  <div class="misi-item">
                    <button type="button" class="misi-btn" data-drawer="misi" data-misi="<?= (int) $m['misi_id'] ?>">
                      <span class="misi-no"><?= (int) $m['nomor'] ?></span>
                      <span class="flex-fill">
                        <span class="d-block fw-bold" style="font-size:.86rem;color:#1d2b23;">Misi <?= (int) $m['nomor'] ?></span>
                        <span class="d-block text-muted" style="font-size:.79rem;line-height:1.4;"><?= esc($m['misi']) ?></span>
                        <span class="d-block mt-2" style="font-size:.77rem;color:#5d6b62;">
                          <strong><?= (int) $m['indikator_bupati'] ?></strong> indikator PK Bupati &middot;
                          <strong><?= (int) $m['opd_count'] ?></strong> OPD pengampu &middot;
                          <?= (int) $m['kritis'] ?> indikator kritis &middot;
                          <?= (int) $m['belum_update'] ?> OPD belum update
                          <?php if (in_array('renstra', (array) $m['sumber'], true)): ?>
                            <span class="badge-soft ms-1" style="background:#f1f3f2;color:#6b7a70;">sebagian via Renstra</span>
                          <?php endif; ?>
                        </span>
                      </span>
                      <i class="fas fa-chevron-right text-muted mt-1" style="font-size:.7rem"></i>
                    </button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
  </div>

  <!-- ======================= DRAWER ======================= -->
  <div class="offcanvas offcanvas-end dash-drawer" tabindex="-1" id="dashDrawer" aria-labelledby="dashDrawerTitle">
    <div class="offcanvas-header">
      <div>
        <h5 class="offcanvas-title mb-0" id="dashDrawerTitle">Rincian</h5>
        <small id="dashDrawerSub" class="text-white-50"></small>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body" id="dashDrawerBody"></div>
  </div>

  <script>
    window.DASHKAB = <?= $js([
      'mode'      => $fokusMode ? 'fokus_opd' : 'kabupaten',
      'can_write' => $canWrite,
      'tahun'     => (int) $tahun,
      'triwulan'  => (int) $triwulan,
      'filterQs'  => $filterQs,
      'urls'      => [
        'pkBupati'  => base_url('adminkab/dashboard/pk-bupati'),
        'opd'       => base_url('adminkab/dashboard/opd'),
        'statusOpd' => base_url('adminkab/dashboard/status-opd'),
        'misi'      => base_url('adminkab/dashboard/misi'),
        'anggaran'  => base_url('adminkab/dashboard/anggaran-kinerja'),
        'dashboard' => base_url('adminkab/dashboard'),
      ],
      'kab'   => $fokusMode ? null : [
        'pk_bupati'    => $dash['pk_bupati'],
        'opd'          => $dash['opd'],
        'opd_list'     => $dash['opd_list'],
        'belum_update' => $dash['belum_update'],
        'prioritas'    => $dash['prioritas'],
        'distribusi'   => $dash['distribusi'],
        'tren'         => $dash['tren'],
        'misi'         => $dash['misi'],
      ],
      'fokus' => $fokusMode ? [
        'pk'         => $fokus['pk'],
        'capaian'    => $fokus['capaian'],
        'anggaran'   => $fokus['anggaran'],
        'perhatian'  => $fokus['perhatian'],
        'insights'   => $fokus['insights'],
        'indicators' => $fokus['indicators'],
        'series'     => $fokus['chart_series'],
        'distribusi' => $fokus['status_distribution'],
        'misi'       => $fokus['misi'],
        'links'      => $fokus['links'],
        'opd_nama'   => $scope['opd_nama'],
      ] : null,
    ]) ?>;
  </script>
  <script>
    (function () {
      'use strict';
      var D = window.DASHKAB;
      var romawi = ['', 'I', 'II', 'III', 'IV'];

      var rp = function (n) {
        if (n === null || n === undefined) return '-';
        return 'Rp ' + Number(n).toLocaleString('id-ID', { maximumFractionDigits: 0 });
      };
      var pct = function (n) {
        if (n === null || n === undefined || isNaN(n)) return '-';
        return Number(n).toFixed(2).replace('.', ',').replace(/,00$/, '') + '%';
      };
      var esc = function (s) {
        return String(s === null || s === undefined ? '' : s)
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
      };
      var badge = function (st) {
        return '<span class="badge-soft" style="background:' + esc(st.color_soft) + ';color:' + esc(st.color_hex) + ';">' +
          (st.icon ? '<i class="fas ' + esc(st.icon) + '"></i>' : '') + esc(st.name) + '</span>';
      };

      /* ---------------- Drawer ---------------- */
      var drawer = new bootstrap.Offcanvas(document.getElementById('dashDrawer'));
      var body   = document.getElementById('dashDrawerBody');
      var judul  = document.getElementById('dashDrawerTitle');
      var sub    = document.getElementById('dashDrawerSub');

      function buka(t, s, html) { judul.textContent = t; sub.textContent = s || ''; body.innerHTML = html; drawer.show(); }
      function memuat(t, s) {
        buka(t, s, '<div class="drawer-section"><div class="skel" style="width:70%"></div><div class="skel"></div><div class="skel" style="width:85%"></div></div>');
      }
      function gagal(e) {
        body.innerHTML = '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas fa-triangle-exclamation"></i></div>' +
          '<p class="mb-0 small">' + esc(e.message || e) + '</p></div></div>';
      }
      function ambil(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) {
            return r.json().then(function (j) {
              if (r.ok && j.status === 'success') return j.data;
              throw new Error(j.message || 'Gagal memuat data.');
            });
          });
      }
      function kosong(pesan, ikon) {
        return '<div class="drawer-section"><div class="empty"><div class="ic"><i class="fas ' + (ikon || 'fa-inbox') + '"></i></div>' +
          '<p class="mb-0 small">' + esc(pesan) + '</p></div></div>';
      }

      /* ---------------- Kartu ---------------- */
      function kartuOpd(o) {
        var url = o.url_fokus || (D.urls.dashboard + '?opd_id=' + o.opd_id + '&tahun=' + D.tahun + '&triwulan=' + D.triwulan);
        return '<div class="ind-card">' +
          '<div class="d-flex justify-content-between gap-2 align-items-start">' +
            '<div class="fw-bold" style="font-size:.87rem;">' + esc(o.nama_opd) + '</div>' +
            '<div class="ind-pct">' + (o.can_compute ? pct(o.percentage) : '—') + '</div>' +
          '</div>' +
          '<div class="ind-meta">' +
            '<span>' + o.valid + '/' + o.indikator + ' indikator valid</span>' +
            '<span>' + o.kritis + ' kritis</span>' +
            '<span>Update: ' + esc(o.last_update || 'belum ada') + '</span>' +
          '</div>' +
          '<div class="d-flex flex-wrap gap-2 mt-2 align-items-center">' + badge(o.status) +
            '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + esc(o.update.label) + '</span>' +
            '<a class="btn btn-sm btn-outline-success ms-auto" href="' + esc(url) + '">Fokus OPD</a>' +
          '</div>' +
          (o.status.reason ? '<div class="ins-why mt-2"><i class="fas fa-circle-info me-1"></i>' + esc(o.status.reason) + '</div>' : '') +
        '</div>';
      }

      function kartuIndikatorBupati(i) {
        return '<div class="ind-card" data-pkbupati="' + i.indikator_id + '" role="button">' +
          '<div class="d-flex justify-content-between gap-2 align-items-start">' +
            '<div class="fw-bold" style="font-size:.87rem;line-height:1.35;">' + esc(i.indikator) + '</div>' +
            '<div class="ind-pct">' + (i.percentage_teks || '—') + '</div>' +
          '</div>' +
          '<div class="ind-meta">' +
            '<span>Target: <strong>' + esc(i.target_tahunan || '-') + '</strong> ' + esc(i.satuan || '') + '</span>' +
            '<span>Metode: ' + esc(i.metode_nama) + '</span>' +
            '<span>' + (i.pengampu && i.pengampu.length ? i.pengampu.length + ' OPD pengampu' : 'OPD pengampu belum ditetapkan') + '</span>' +
          '</div>' +
          '<div class="d-flex flex-wrap gap-2 mt-2">' + badge(i.status) +
            '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + (i.is_valid ? 'Valid' : 'Belum valid') + '</span>' +
            '<span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">' + esc(i.verification.label) + '</span>' +
          '</div>' +
          (i.reason ? '<div class="ins-why mt-2"><i class="fas fa-circle-info me-1"></i>' + esc(i.reason) + '</div>' : '') +
        '</div>';
      }

      function daftarInsight(list) {
        if (!list.length) return kosong('Tidak ada kondisi yang perlu ditindaklanjuti.', 'fa-circle-check');
        return '<div class="drawer-section">' + list.map(function (i) {
          return '<div class="ins" style="background:#fff;">' +
            '<div class="ins-bar" style="background:' + esc(i.color.hex) + '"></div>' +
            '<div class="ins-body"><div class="ins-title">' + esc(i.judul) + '</div>' +
            '<div class="ins-why">' + esc(i.alasan) + '</div>' +
            (i.objek ? '<div class="ins-why">' + esc(i.objek) + '</div>' : '') +
            '<span class="badge-soft mt-2" style="background:' + esc(i.color.soft) + ';color:' + esc(i.color.hex) + ';">' + esc(i.status) + '</span></div>' +
            '<div class="ins-act"><a class="btn btn-sm btn-outline-success" href="' + esc(i.url) + '">' + esc(D.can_write ? i.tombol : 'Lihat') + '</a></div></div>';
        }).join('') + '</div>';
      }

      /* ---------------- Drawer: indikator PK Bupati (drill-down) ---------------- */
      function bukaPkBupati(id) {
        memuat('Indikator PK Bupati', 'Memuat…');
        ambil(D.urls.pkBupati + '/' + encodeURIComponent(id) + '?' + D.filterQs).then(function (d) {
          var tw = d.rows.map(function (b) {
            var isi = ['I', 'II', 'III', 'IV'].map(function (r, k) {
              var t = b.targets[k + 1], c = b.capaian[k + 1];
              return '<div class="d-flex justify-content-between" style="font-size:.79rem;padding:3px 0;border-bottom:1px dashed #eef2ef;">' +
                '<span class="text-muted">TW ' + r + '</span><span>Target <strong>' + esc(t === null || t === '' ? '-' : t) +
                '</strong> &middot; Realisasi <strong>' + esc(c === null || c === '' ? '-' : c) + '</strong></span></div>';
            }).join('');
            return '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">' + esc(b.label || 'Rencana Aksi PK Bupati') + '</div>' + isi +
              (b.validity.reason ? '<div class="ins-why mt-2"><i class="fas fa-circle-info me-1"></i>' + esc(b.validity.reason) + '</div>' : '') + '</div>';
          }).join('');

          var rantai = d.gap_relasi
            ? '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">Jalur ke Perangkat Daerah</div>' +
              '<div class="alert alert-warning py-2 px-3 mb-2" style="font-size:.78rem;"><i class="fas fa-link-slash me-1"></i>' + esc(d.gap_pesan) + '</div>' +
              (D.can_write ? '<a class="btn btn-sm btn-outline-success" href="' + esc(d.links.pd) + '">Kelola Perangkat Daerah pengampu</a>' : '') + '</div>'
            : d.pengampu.map(function (p) {
                var isi = p.indikator.length
                  ? p.indikator.map(function (x) {
                      return '<div class="d-flex justify-content-between gap-2" style="font-size:.79rem;padding:4px 0;border-bottom:1px dashed #eef2ef;">' +
                        '<span>' + esc(x.indikator) + (x.turunan ? ' <span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + x.turunan + ' turunan Eselon III/IV</span>' : '') + '</span>' +
                        '<span class="text-nowrap">' + (x.percentage_teks || '—') + '</span></div>';
                    }).join('')
                  : '<div class="text-muted" style="font-size:.79rem;">PK pimpinan OPD ini belum tersedia pada tahun terpilih.</div>';
                return '<div class="drawer-section"><div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
                  '<div class="fw-bold" style="font-size:.83rem;">' + esc(p.nama_opd) + '</div>' +
                  '<a class="btn btn-sm btn-outline-success" href="' + esc(p.url_fokus) + '">Fokus OPD</a></div>' + isi + '</div>';
              }).join('');

          var misi = d.misi.length
            ? d.misi.map(function (m) { return '<div style="font-size:.8rem;">&bull; ' + esc(m.misi) + '</div>'; }).join('')
            : '<div class="text-muted" style="font-size:.8rem;">Dokumen PK Bupati belum dipetakan ke Misi RPJMD.</div>';

          judul.textContent = 'Indikator PK Bupati';
          sub.textContent = d.sasaran;
          body.innerHTML =
            '<div class="drawer-section">' +
              '<div class="fw-bold mb-2" style="font-size:.92rem;line-height:1.4;">' + esc(d.indikator) + '</div>' +
              '<div class="d-flex flex-wrap gap-2 mb-3">' + badge(d.status) +
                '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + (d.validity.is_valid ? 'Valid' : 'Belum valid') + '</span>' +
                '<span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">' + esc(d.verification.label) + '</span></div>' +
              '<dl class="drawer-dl mb-0">' +
                '<dt>Sasaran PK Bupati</dt><dd>' + esc(d.sasaran) + '</dd>' +
                '<dt>Satuan</dt><dd>' + esc(d.satuan || '-') + '</dd>' +
                '<dt>Target tahunan</dt><dd>' + esc(d.target_tahunan || '-') + '</dd>' +
                '<dt>Formula / metode</dt><dd>' + esc(d.metode_nama) + '</dd>' +
                '<dt>Realisasi PK</dt><dd>' + (d.percentage_teks || 'belum dapat dihitung') + '</dd>' +
                '<dt>Anggaran</dt><dd>' + esc(d.anggaran_teks) + '</dd>' +
                '<dt>Realisasi anggaran</dt><dd>' + (d.realisasi_teks || 'belum dilaporkan') + '</dd>' +
                '<dt>Pembaruan terakhir</dt><dd>' + esc(d.updated_at || '-') + '</dd>' +
              '</dl>' +
              (d.validity.reason ? '<div class="ins-why mt-3"><i class="fas fa-circle-info me-1"></i>' + esc(d.validity.reason) + '</div>' : '') +
            '</div>' +
            '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">Misi Bupati yang didukung</div>' + misi + '</div>' +
            tw + rantai +
            '<div class="drawer-section d-flex flex-wrap gap-2">' +
              '<a class="btn btn-sm btn-success" href="' + esc(d.links.monev) + '">MONEV PK Bupati</a>' +
              '<a class="btn btn-sm btn-outline-success" href="' + esc(d.links.renaksi) + '">Rencana Aksi</a>' +
              '<a class="btn btn-sm btn-outline-success" href="' + esc(d.links.lakip) + '">LAKIP</a>' +
            '</div>';
        }).catch(gagal);
      }

      /* ---------------- Drawer: OPD per status ---------------- */
      function bukaStatusOpd(code, nama) {
        memuat('Perangkat Daerah: ' + nama, 'Tahun ' + D.tahun + ' s.d. Triwulan ' + romawi[D.triwulan]);
        ambil(D.urls.statusOpd + '/' + encodeURIComponent(code) + '?' + D.filterQs).then(function (d) {
          body.innerHTML = d.opd.length ? d.opd.map(kartuOpd).join('') : kosong('Tidak ada Perangkat Daerah pada status ini.');
        }).catch(gagal);
      }

      /* ---------------- Drawer: kontribusi misi ---------------- */
      function bukaMisi(id) {
        memuat('Kontribusi Misi', 'Memuat…');
        ambil(D.urls.misi + '/' + encodeURIComponent(id) + '?' + D.filterQs).then(function (m) {
          var kepala = '<div class="drawer-section">' +
            '<div class="fw-bold mb-1" style="font-size:.88rem;line-height:1.4;">' + esc(m.misi) + '</div>' +
            '<div class="ind-meta"><span>' + m.indikator_bupati + ' indikator PK Bupati</span><span>' + m.opd_count + ' OPD pengampu</span>' +
            '<span>' + m.kritis + ' indikator kritis</span><span>' + m.belum_update + ' OPD belum update</span></div></div>';

          var indB = (m.indikator_bupati_daftar && m.indikator_bupati_daftar.length)
            ? '<div class="drawer-section"><div class="fw-bold mb-0" style="font-size:.83rem;">Indikator PK Bupati</div></div>' +
              m.indikator_bupati_daftar.map(kartuIndikatorBupati).join('')
            : '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">Indikator PK Bupati</div>' +
              '<div class="text-muted" style="font-size:.79rem;">Belum ada indikator PK Bupati yang dipetakan ke misi ini (relasi <code>pk_misi</code> pada dokumen PK Bupati belum diisi).</div></div>';

          var opd = m.opd.length
            ? m.opd.map(function (o) {
                return '<div class="ind-card"><div class="d-flex justify-content-between gap-2 align-items-start">' +
                  '<div class="fw-bold" style="font-size:.85rem;">' + esc(o.nama_opd) + '</div>' + badge(o.status) + '</div>' +
                  '<div class="ind-meta"><span>' + o.indikator + ' indikator mendukung misi</span><span>' + o.valid + ' valid</span><span>' + o.kritis + ' kritis</span></div>' +
                  '<div class="mt-2 d-flex gap-2 align-items-center"><span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + esc(o.update.label) + '</span>' +
                  '<a class="btn btn-sm btn-outline-success ms-auto" href="' + esc(o.url_fokus) + '">Fokus OPD</a></div></div>';
              }).join('')
            : kosong('Belum ada Perangkat Daerah yang PK-nya dipetakan ke misi ini.');

          buka('Misi ' + m.nomor, m.opd_count + ' OPD pengampu', kepala + indB +
            '<div class="drawer-section"><div class="fw-bold mb-0" style="font-size:.83rem;">Perangkat Daerah pengampu</div></div>' + opd);
        }).catch(gagal);
      }

      /* ---------------- Drawer: analisis anggaran vs kinerja ---------------- */
      var scatter = null;
      function bukaAnggaranKinerja() {
        memuat('Analisis Anggaran dan Kinerja', 'Memuat…');
        ambil(D.urls.anggaran + '?' + D.filterQs).then(function (d) {
          var kepala = '<div class="drawer-section">' +
            '<div class="chart-box" style="height:300px;"><canvas id="scatterAK"></canvas></div>' +
            '<p class="text-muted mt-2 mb-0" style="font-size:.75rem;">' + esc(d.catatan) + '</p></div>';

          var lain = d.excluded.length
            ? '<div class="drawer-section"><div class="fw-bold mb-2" style="font-size:.83rem;">Tidak dapat dibandingkan (' + d.excluded.length + ')</div>' +
              d.excluded.map(function (x) {
                return '<div style="font-size:.79rem;padding:4px 0;border-bottom:1px dashed #eef2ef;"><strong>' + esc(x.nama_opd) + '</strong><br>' +
                  '<span class="text-muted">' + esc(x.alasan) + '</span></div>';
              }).join('') + '</div>'
            : '';

          buka('Analisis Anggaran dan Kinerja', d.points.length + ' Perangkat Daerah dibandingkan', kepala + lain);

          if (!d.points.length) {
            document.getElementById('scatterAK').parentNode.innerHTML =
              '<div class="empty"><div class="ic"><i class="fas fa-chart-simple"></i></div>' +
              '<p class="mb-0 small">Belum ada Perangkat Daerah yang capaian dan realisasi anggarannya lengkap.</p></div>';
            return;
          }

          if (scatter) scatter.destroy();
          scatter = new Chart(document.getElementById('scatterAK'), {
            type: 'scatter',
            data: { datasets: [{
              label: 'Perangkat Daerah',
              data: d.points.map(function (p) { return { x: p.x, y: p.y, nama: p.nama_opd, st: p.status, ang: p.anggaran, real: p.realisasi }; }),
              backgroundColor: d.points.map(function (p) { return p.color; }),
              pointRadius: 6, pointHoverRadius: 8
            }] },
            options: {
              responsive: true, maintainAspectRatio: false,
              scales: {
                x: { title: { display: true, text: 'Penyerapan anggaran (%)' }, grid: { color: '#eef2ef' } },
                y: { title: { display: true, text: 'Capaian kinerja (%)' }, grid: { color: '#eef2ef' } }
              },
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: '#15311f', padding: 10, cornerRadius: 8, displayColors: false,
                  callbacks: {
                    label: function (c) {
                      var r = c.raw;
                      return [r.nama, 'Capaian ' + pct(r.y) + ' · Penyerapan ' + pct(r.x), r.st, r.real + ' dari ' + r.ang];
                    }
                  }
                }
              }
            }
          });
        }).catch(gagal);
      }

      /* ---------------- Grafik Mode Kabupaten ---------------- */
      if (D.mode === 'kabupaten') {
        var segs = D.kab.distribusi.segments || [];
        var el = document.getElementById('chartStatusOpd');
        if (el) {
          if (!segs.length) {
            el.parentNode.innerHTML = '<div class="empty"><div class="ic"><i class="fas fa-chart-pie"></i></div>' +
              '<p class="mb-0 small">Belum ada Perangkat Daerah yang dapat dikelompokkan statusnya.</p></div>';
          } else {
            new Chart(el, {
              type: 'doughnut',
              data: {
                labels: segs.map(function (s) { return s.name; }),
                datasets: [{ data: segs.map(function (s) { return s.count; }), backgroundColor: segs.map(function (s) { return s.color; }), borderWidth: 0 }]
              },
              options: {
                responsive: true, maintainAspectRatio: false, cutout: '64%',
                plugins: {
                  legend: { display: false },
                  tooltip: { backgroundColor: '#15311f', padding: 10, cornerRadius: 8, displayColors: false,
                    callbacks: { label: function (c) { return c.label + ': ' + c.parsed + ' OPD'; } } }
                },
                onClick: function (evt, items) { if (items.length) bukaStatusOpd(segs[items[0].index].code, segs[items[0].index].name); }
              }
            });
            document.getElementById('legendStatusOpd').innerHTML = segs.map(function (s) {
              return '<button type="button" class="btn btn-sm btn-light border" data-status="' + esc(s.code) + '" style="font-size:.74rem;font-weight:600;">' +
                '<span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:' + esc(s.color) + ';margin-right:6px;"></span>' +
                esc(s.name) + ' (' + s.count + ')</button>';
            }).join('');
            document.querySelectorAll('#legendStatusOpd [data-status]').forEach(function (b) {
              b.addEventListener('click', function () { bukaStatusOpd(b.getAttribute('data-status'), b.textContent.trim()); });
            });
          }
        }

        var tren = D.kab.tren || [];
        var picker = document.getElementById('trenPicker');
        var elTren = document.getElementById('chartTren');
        var chartTren = null;

        var gambarTren = function (idx) {
          var s = tren[idx];
          if (!s) return;
          var d = s.series;
          var info = document.getElementById('trenInfo');
          if (chartTren) chartTren.destroy();
          chartTren = new Chart(elTren, {
            type: 'bar',
            data: {
              labels: ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'],
              datasets: [
                { label: 'Target PK', data: d.target, backgroundColor: 'rgba(63,98,150,.75)', borderRadius: 6, maxBarThickness: 34 },
                { label: 'Realisasi PK', data: d.capaian, backgroundColor: 'rgba(10,143,80,.85)', borderRadius: 6, maxBarThickness: 34 }
              ]
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              scales: { y: { beginAtZero: true, grid: { color: '#eef2ef' } }, x: { grid: { display: false } } },
              plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: '#15311f', padding: 10, cornerRadius: 8,
                  callbacks: { label: function (c) {
                    var arr = c.datasetIndex === 0 ? d.label_target : d.label_capaian;
                    var teks = arr[c.dataIndex];
                    if (teks === null || teks === undefined) return c.dataset.label + ': belum tersedia';
                    return c.dataset.label + ': ' + teks + (s.satuan ? ' ' + s.satuan : '');
                  } } }
              }
            }
          });
          if (info) {
            info.innerHTML = s.tersedia
              ? 'Metode: <strong>' + esc(d.metode_nama) + '</strong>' + (s.satuan ? ' &middot; Satuan: <strong>' + esc(s.satuan) + '</strong>' : '') +
                (d.predikat ? ' &middot; nilai predikat dipetakan ke skala satuan untuk posisi grafik' : '')
              : '<span class="text-warning-emphasis"><i class="fas fa-circle-info me-1"></i>Realisasi PK belum tersedia untuk indikator ini' +
                (s.alasan ? ' — ' + esc(s.alasan) : '') + '. Monitoring Rencana Aksi pendukung dapat dibuka lewat drawer indikator.</span>';
          }
        };

        if (picker && elTren) {
          if (!tren.length) {
            elTren.parentNode.innerHTML = '<div class="empty"><div class="ic"><i class="fas fa-chart-column"></i></div>' +
              '<p class="mb-0 small">Belum ada indikator PK Bupati untuk tahun ' + D.tahun + '.</p></div>';
            picker.style.display = 'none';
          } else {
            var grup = {};
            tren.forEach(function (s, i) { (grup[s.indikator] = grup[s.indikator] || []).push(i); });
            picker.innerHTML = Object.keys(grup).map(function (nama) {
              return '<optgroup label="' + esc(nama) + '">' + grup[nama].map(function (i) {
                return '<option value="' + i + '">' + esc(tren[i].label || nama) + (tren[i].tersedia ? '' : ' — belum tersedia') + '</option>';
              }).join('') + '</optgroup>';
            }).join('');
            var awal = tren.findIndex(function (s) { return s.status === 'critical'; });
            if (awal < 0) awal = tren.findIndex(function (s) { return s.misi; });
            if (awal < 0) awal = tren.findIndex(function (s) { return s.is_valid; });
            if (awal < 0) awal = 0;
            picker.value = String(awal);
            gambarTren(awal);
            picker.addEventListener('change', function () { gambarTren(parseInt(picker.value, 10)); });
          }
        }
      }

      /* ---------------- Grafik Mode Fokus OPD ---------------- */
      function kartuIndikatorOpd(i) {
        var L = D.fokus.links;
        var tombol = (L.renaksi ? '<a class="btn btn-sm btn-outline-success" href="' + esc(L.renaksi) + '">Rencana Aksi</a> ' : '') +
                     (L.monev ? '<a class="btn btn-sm btn-outline-success" href="' + esc(L.monev) + '">MONEV</a>' : '');
        return '<div class="ind-card">' +
          '<div class="d-flex justify-content-between gap-2 align-items-start">' +
            '<div class="fw-bold" style="font-size:.87rem;line-height:1.35;">' + esc(i.indikator) + '</div>' +
            '<div class="ind-pct">' + (i.percentage_teks || '—') + '</div></div>' +
          '<div class="ind-meta">' +
            '<span>Target: <strong>' + esc(i.target_tahunan || '-') + '</strong> ' + esc(i.satuan || '') + '</span>' +
            '<span>Metode: ' + esc(i.metode_nama) + '</span>' +
            (i.capaian_terakhir ? '<span>Capaian TW ' + romawi[i.capaian_terakhir.triwulan] + ': <strong>' + esc(i.capaian_terakhir.nilai) + '</strong></span>' : '<span>Capaian: belum ada</span>') +
          '</div>' +
          '<div class="d-flex flex-wrap gap-2 mt-2">' + badge(i.status) +
            '<span class="badge-soft" style="background:#f1f3f2;color:#6b7a70;">' + (i.is_valid ? 'Valid' : 'Belum valid') + '</span>' +
            '<span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">' + esc(i.verification.label) + '</span></div>' +
          (i.reason ? '<div class="ins-why mt-2"><i class="fas fa-circle-info me-1"></i>' + esc(i.reason) + '</div>' : '') +
          '<div class="mt-2 d-flex flex-wrap gap-1">' + tombol + '</div></div>';
      }

      if (D.mode === 'fokus_opd' && D.fokus) {
        var fsegs = (D.fokus.distribusi && D.fokus.distribusi.segments) || [];
        var elF = document.getElementById('chartStatus');
        if (elF) {
          if (!fsegs.length) {
            elF.parentNode.innerHTML = '<div class="empty"><div class="ic"><i class="fas fa-chart-pie"></i></div>' +
              '<p class="mb-0 small">Belum ada indikator yang dapat dikelompokkan statusnya.</p></div>';
          } else {
            new Chart(elF, {
              type: 'doughnut',
              data: {
                labels: fsegs.map(function (s) { return s.name; }),
                datasets: [{ data: fsegs.map(function (s) { return s.count; }), backgroundColor: fsegs.map(function (s) { return s.color; }), borderWidth: 0 }]
              },
              options: {
                responsive: true, maintainAspectRatio: false, cutout: '64%',
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#15311f', padding: 10, cornerRadius: 8, displayColors: false,
                  callbacks: { label: function (c) { return c.label + ': ' + c.parsed + ' indikator'; } } } }
              }
            });
            document.getElementById('legendStatus').innerHTML = fsegs.map(function (s) {
              return '<span class="badge-soft" style="background:#f7faf8;color:#5d6b62;">' +
                '<span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:' + esc(s.color) + ';"></span>' +
                esc(s.name) + ' (' + s.count + ')</span>';
            }).join('');
          }
        }

        var fser = D.fokus.series || [];
        var fpick = document.getElementById('serialPicker');
        var elFT = document.getElementById('chartTriwulan');
        var chartFT = null;

        var gambarSeri = function (idx) {
          var s = fser[idx];
          if (!s) return;
          var d = s.series;
          if (chartFT) chartFT.destroy();
          chartFT = new Chart(elFT, {
            type: 'bar',
            data: {
              labels: ['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV'],
              datasets: [
                { label: 'Target', data: d.target, backgroundColor: 'rgba(63,98,150,.75)', borderRadius: 6, maxBarThickness: 34 },
                { label: 'Capaian', data: d.capaian, backgroundColor: 'rgba(10,143,80,.85)', borderRadius: 6, maxBarThickness: 34 }
              ]
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              scales: { y: { beginAtZero: true, grid: { color: '#eef2ef' } }, x: { grid: { display: false } } },
              plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: '#15311f', padding: 10, cornerRadius: 8,
                  callbacks: { label: function (c) {
                    var arr = c.datasetIndex === 0 ? d.label_target : d.label_capaian;
                    var teks = arr[c.dataIndex];
                    if (teks === null || teks === undefined) return c.dataset.label + ': belum diisi';
                    return c.dataset.label + ': ' + teks + (s.satuan ? ' ' + s.satuan : '');
                  } } } }
            }
          });
          var info = document.getElementById('serialInfo');
          if (info) {
            info.innerHTML = 'Metode: <strong>' + esc(d.metode_nama) + '</strong>' +
              (s.satuan ? ' &middot; Satuan: <strong>' + esc(s.satuan) + '</strong>' : '') +
              (s.is_valid ? '' : ' &middot; <span class="text-warning-emphasis">indikator ini belum dapat dihitung</span>');
          }
        };

        if (fpick && elFT) {
          if (!fser.length) {
            elFT.parentNode.innerHTML = '<div class="empty"><div class="ic"><i class="fas fa-chart-column"></i></div>' +
              '<p class="mb-0 small">Belum ada Rencana Aksi bertarget triwulanan untuk ditampilkan.</p></div>';
            fpick.style.display = 'none';
          } else {
            var g2 = {};
            fser.forEach(function (s, i) { (g2[s.indikator] = g2[s.indikator] || []).push(i); });
            fpick.innerHTML = Object.keys(g2).map(function (nama) {
              return '<optgroup label="' + esc(nama) + '">' + g2[nama].map(function (i) {
                return '<option value="' + i + '">' + esc(fser[i].label || nama) + '</option>';
              }).join('') + '</optgroup>';
            }).join('');
            var i0 = fser.findIndex(function (s) { return s.status === 'critical'; });
            if (i0 < 0) i0 = fser.findIndex(function (s) { return s.misi; });
            if (i0 < 0) i0 = fser.findIndex(function (s) { return s.is_valid; });
            if (i0 < 0) i0 = 0;
            fpick.value = String(i0);
            gambarSeri(i0);
            fpick.addEventListener('change', function () { gambarSeri(parseInt(fpick.value, 10)); });
          }
        }
      }

      /* ---------------- Isi drawer dari data tertanam ---------------- */
      var builders = {
        pk_bupati: function () {
          var b = D.kab.pk_bupati;
          if (!b.ada) {
            return { t: 'PK Bupati', s: '', html: kosong('Belum ada Perjanjian Kinerja Bupati untuk tahun ' + D.tahun + '.', 'fa-file-signature') };
          }
          var kepala = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
            '<dt>Capaian PK Bupati</dt><dd>' + (b.can_compute ? pct(b.total) : 'belum dapat dihitung') + '</dd>' +
            '<dt>Indikator valid</dt><dd>' + b.valid + ' dari ' + b.wajib + '</dd>' +
            '<dt>Status nilai</dt><dd>' + esc(b.label) + '</dd>' +
            (b.formula_gap ? '<dt>Formula belum tersedia</dt><dd>' + b.formula_gap + ' indikator</dd>' : '') +
            '</dl><p class="text-muted mt-3 mb-0" style="font-size:.75rem;">' + esc(b.verifikasi.note) + '</p></div>';
          return { t: 'Indikator PK Bupati', s: b.valid + ' dari ' + b.wajib + ' indikator valid', html: kepala + b.indikator.map(kartuIndikatorBupati).join('') };
        },
        opd: function () {
          var o = D.kab.opd;
          var kepala = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
            '<dt>Perangkat Daerah</dt><dd>' + o.total + '</dd>' +
            '<dt>Dapat dinilai</dt><dd>' + o.dapat_dinilai + '</dd>' +
            '<dt>Belum lengkap</dt><dd>' + o.belum_lengkap + '</dd>' +
            '<dt>Kritis / Perlu perhatian / Terkendali</dt><dd>' + o.kritis + ' / ' + o.perhatian + ' / ' + o.terkendali + '</dd>' +
            '</dl></div>';
          return { t: 'Perangkat Daerah', s: o.total + ' Perangkat Daerah', html: kepala + D.kab.opd_list.map(kartuOpd).join('') };
        },
        belum_update: function () {
          var t = D.kab.belum_update;
          var kepala = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
            '<dt>Belum pernah input</dt><dd>' + t.belum_pernah + ' OPD</dd>' +
            '<dt>Belum lengkap periode ini</dt><dd>' + t.belum_periode + ' OPD</dd>' +
            '<dt>Lebih dari ' + t.batas_hari + ' hari</dt><dd>' + t.terlambat + ' OPD</dd>' +
            '<dt>Indikator belum diinput</dt><dd>' + t.indikator_belum + '</dd>' +
            '</dl></div>';
          return {
            t: 'OPD Belum Update', s: t.total + ' Perangkat Daerah',
            html: kepala + (t.daftar.length ? t.daftar.map(kartuOpd).join('') : kosong('Seluruh Perangkat Daerah sudah memperbarui data.', 'fa-circle-check'))
          };
        },
        prioritas: function () {
          return { t: 'Prioritas Pimpinan', s: D.kab.prioritas.length + ' isu', html: daftarInsight(D.kab.prioritas) };
        },
        f_pk: function () {
          var p = D.fokus.pk;
          var kepala = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
            '<dt>Dokumen PK</dt><dd>' + p.pk_count + ' &middot; ' + esc(p.jenis_label) + '</dd>' +
            '<dt>Indikator PK</dt><dd>' + p.indikator + '</dd>' +
            '<dt>Mendukung Misi Bupati</dt><dd>' + p.mendukung_misi + '</dd>' +
            '<dt>Belum ada Rencana Aksi</dt><dd>' + p.tanpa_renaksi + '</dd>' +
            '<dt>Indikator pendukung</dt><dd>' + p.pendukung.indikator + ' (Administrator &amp; Pengawas)</dd>' +
            '</dl></div>';
          return { t: 'Perjanjian Kinerja', s: D.fokus.opd_nama, html: kepala + D.fokus.indicators.map(kartuIndikatorOpd).join('') };
        },
        f_capaian: function () {
          var c = D.fokus.capaian;
          var kepala = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
            '<dt>Capaian OPD</dt><dd>' + (c.can_compute ? pct(c.total) : 'belum dapat dihitung') + '</dd>' +
            '<dt>Indikator valid</dt><dd>' + c.valid + ' dari ' + c.wajib + '</dd>' +
            '<dt>Status nilai</dt><dd>' + esc(c.label) + '</dd>' +
            '</dl><p class="text-muted mt-3 mb-0" style="font-size:.75rem;">' + esc(c.verifikasi.note) + '</p></div>';
          return { t: 'Rincian Indikator', s: D.fokus.opd_nama, html: kepala + D.fokus.indicators.map(kartuIndikatorOpd).join('') };
        },
        f_anggaran: function () {
          var a = D.fokus.anggaran;
          var kepala = '<div class="drawer-section"><dl class="drawer-dl mb-0">' +
            '<dt>Pagu anggaran</dt><dd>' + rp(a.anggaran) + '</dd>' +
            '<dt>Realisasi s.d. TW ' + romawi[a.triwulan] + '</dt><dd>' + (a.realisasi === null ? 'belum dilaporkan' : rp(a.realisasi)) + '</dd>' +
            '<dt>Penyerapan</dt><dd>' + (a.persen === null ? '-' : pct(a.persen)) + '</dd>' +
            '</dl><p class="text-muted mt-3 mb-0" style="font-size:.75rem;">Realisasi kosong berarti <strong>belum dilaporkan</strong> — tidak dianggap 0. ' +
            'Efisiensi anggaran tetap diinput manual pada modul LAKIP.</p>' +
            (a.program_lain_count ? '<div class="alert alert-warning mt-3 mb-0 py-2 px-3" style="font-size:.76rem;">' +
              '<i class="fas fa-triangle-exclamation me-1"></i>' + a.program_lain_count + ' program tertaut pada PK tercatat milik Perangkat Daerah lain (pagu ' +
              rp(a.anggaran_lain) + ') dan <strong>tidak dijumlahkan</strong>.</div>' : '') + '</div>';

          var semua = a.programs.concat(a.program_lain || []);
          var daftar = semua.length ? semua.map(function (p) {
            var serap = (p.anggaran > 0 && p.realisasi !== null) ? (p.realisasi / p.anggaran * 100) : null;
            return '<div class="ind-card"' + (p.milik_opd_lain ? ' style="border-color:#f0d9a8;background:#fffdf6;"' : '') + '>' +
              '<div class="fw-bold" style="font-size:.85rem;">' + esc(p.kode ? p.kode + ' — ' : '') + esc(p.nama) +
              (p.milik_opd_lain ? ' <span class="badge-soft" style="background:#fdf0e6;color:#e07b39;">milik PD lain — tidak dihitung</span>' : '') + '</div>' +
              '<div class="ind-meta"><span>Anggaran: <strong>' + rp(p.anggaran) + '</strong></span>' +
              '<span>Realisasi: <strong>' + (p.realisasi === null ? 'belum dilaporkan' : rp(p.realisasi)) + '</strong></span>' +
              '<span>Penyerapan: <strong>' + (serap === null ? '-' : pct(serap)) + '</strong></span></div>' +
              '<div class="ind-meta"><span>Mendukung: ' + p.indikator.map(function (x) { return esc(x.nama); }).join('; ') + '</span></div></div>';
          }).join('') : kosong('Belum ada program pendukung pada Perjanjian Kinerja.');

          return { t: 'Penyerapan Anggaran', s: D.fokus.opd_nama, html: kepala + daftar };
        },
        f_perhatian: function () {
          return { t: 'Prioritas Tindak Lanjut', s: D.fokus.opd_nama, html: daftarInsight(D.fokus.insights) };
        }
      };

      function bukaMisiFokus(id) {
        if (id === 0) {
          buka('Belum memiliki keterkaitan Misi Bupati', D.fokus.misi.tanpa_misi.length + ' indikator',
            D.fokus.misi.tanpa_misi.length ? D.fokus.misi.tanpa_misi.map(kartuIndikatorOpd).join('') : kosong('Tidak ada indikator.'));
          return;
        }
        var m = null;
        D.fokus.misi.items.forEach(function (x) { if (x.misi_id === id) m = x; });
        if (!m) return;
        var kepala = '<div class="drawer-section"><div class="fw-bold mb-1" style="font-size:.88rem;line-height:1.4;">' + esc(m.misi) + '</div>' +
          '<div class="ind-meta"><span>' + m.indikator + ' indikator</span><span>' + m.valid + ' valid</span><span>' + m.belum + ' belum lengkap</span></div>' +
          (m.sumber === 'renstra'
            ? '<p class="text-muted mt-2 mb-0" style="font-size:.75rem;">Keterkaitan ditelusuri lewat rantai Renstra OPD → sasaran RPJMD → misi.</p>'
            : '<p class="text-muted mt-2 mb-0" style="font-size:.75rem;">Keterkaitan diambil dari pemetaan Misi pada dokumen Perjanjian Kinerja.</p>') + '</div>';
        buka('Misi ' + m.nomor, m.indikator + ' indikator mendukung misi ini', kepala + m.daftar.map(kartuIndikatorOpd).join(''));
      }

      /* ---------------- Perutean klik ---------------- */
      document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-drawer]');
        if (t) {
          var jenis = t.getAttribute('data-drawer');
          if (jenis === 'misi') { bukaMisi(parseInt(t.getAttribute('data-misi'), 10)); return; }
          if (jenis === 'f_misi') { bukaMisiFokus(parseInt(t.getAttribute('data-misi'), 10)); return; }
          if (jenis === 'anggaran_kinerja') { bukaAnggaranKinerja(); return; }
          var b = builders[jenis];
          if (b) { var r = b(); buka(r.t, r.s, r.html); }
          return;
        }

        var kartu = e.target.closest('.ind-card[data-pkbupati]');
        if (kartu && !e.target.closest('a')) { bukaPkBupati(kartu.getAttribute('data-pkbupati')); }
      });
    })();
  </script>
</body>

</html>

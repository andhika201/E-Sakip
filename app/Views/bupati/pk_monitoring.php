<?php
/**
 * Monitoring Perjanjian Kinerja — READ-ONLY (role Bupati).
 *
 * Dua bentuk tampilan:
 *   - jenis 'bupati' : Sasaran PK Bupati -> daftar indikator & targetnya
 *   - jenis lain      : daftar indikator PK per Perangkat Daerah
 *
 * Tidak ada tombol tambah / ubah / hapus / cetak-ubah — halaman ini murni
 * pembacaan. Filter tahun & Perangkat Daerah divalidasi di controller.
 */
helper('format');

$rows          = $rows ?? [];
$sasaranBupati = $sasaranBupati ?? [];
$isBupati      = ($jenis ?? '') === 'bupati';
$tahunAktif    = $tahun ?? null;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($title ?? 'Monitoring Perjanjian Kinerja') ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .pk-head { display: flex; align-items: center; gap: 16px; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid #e8ece9; }
    .pk-head .ic { flex: 0 0 auto; width: 52px; height: 52px; display: grid; place-items: center; border-radius: 15px;
      background: linear-gradient(135deg, #0a8f50 0%, #00743e 100%); color: #fff; font-size: 21px; }
    .pk-head h2 { margin: 0; font-weight: 800; font-size: 1.3rem; color: #16321f; }
    .pk-head p { margin: 3px 0 0; color: #6b7a70; font-size: .84rem; }
    .pk-toolbar { background: #f6f9f7; border: 1px solid #e6ece8; border-radius: 14px; padding: 14px 16px; margin-bottom: 20px; }
    .pk-tabs { display: inline-flex; flex-wrap: wrap; gap: 4px; background: #eef2ef; border: 1px solid #e0e7e2; border-radius: 12px; padding: 4px; margin-bottom: 14px; }
    .pk-tabs a { border: 0; background: transparent; color: #5d6b62; font-weight: 600; font-size: .82rem; padding: 7px 14px; border-radius: 9px; text-decoration: none; }
    .pk-tabs a:hover { color: #00743e; }
    .pk-tabs a.active { background: #fff; color: #00743e; box-shadow: 0 2px 6px rgba(0, 0, 0, .08); }
    .pk-table { font-size: .84rem; }
    .pk-table thead th { background: #00713c; color: #fff; font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; vertical-align: middle; }
    .pk-table tbody td { vertical-align: middle; }
    .pk-empty { text-align: center; padding: 46px 24px; border-radius: 16px; border: 1px dashed #cfd8d2; background: #f8faf9; color: #5d6b62; }
    .pk-empty .ic { font-size: 40px; margin-bottom: 12px; color: #00743e; opacity: .35; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4">

        <div class="pk-head">
          <div class="ic"><i class="fas fa-file-signature"></i></div>
          <div>
            <h2><?= esc($judul) ?></h2>
            <p>
              Pemantauan dokumen Perjanjian Kinerja
              &middot; <span class="badge bg-light text-secondary border"><i class="fas fa-eye me-1"></i>Hanya baca</span>
            </p>
          </div>
        </div>

        <!-- Pilihan jenis PK -->
        <div class="pk-tabs">
          <?php
          $tabs = [
              'bupati'        => 'PK Bupati',
              'es3'           => 'PK Pimpinan PD (JPT)',
              'kecamatan'     => 'PK Camat',
              'administrator' => 'PK Administrator',
              'pengawas'      => 'PK Pengawas',
          ];
          foreach ($tabs as $seg => $label):
              $qs = $tahunAktif ? ('?tahun=' . (int) $tahunAktif) : '';
          ?>
            <a href="<?= base_url('bupati/pk/' . $seg) . $qs ?>" class="<?= ($segmen === $seg || ($seg === 'es3' && $segmen === 'jpt')) ? 'active' : '' ?>">
              <?= esc($label) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Filter -->
        <div class="pk-toolbar">
          <form method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label mb-1" style="font-size:.74rem;font-weight:700;color:#6b7a70;text-transform:uppercase;">Tahun</label>
              <select name="tahun" class="form-select" onchange="this.form.submit()">
                <?php if ($tahunList === []): ?>
                  <option value="">Belum ada data</option>
                <?php else: ?>
                  <?php foreach ($tahunList as $t): ?>
                    <option value="<?= (int) $t ?>" <?= (int) $t === (int) $tahunAktif ? 'selected' : '' ?>><?= (int) $t ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <?php if (!$isBupati): ?>
              <div class="col-12 col-md-6">
                <label class="form-label mb-1" style="font-size:.74rem;font-weight:700;color:#6b7a70;text-transform:uppercase;">Perangkat Daerah</label>
                <select name="opd_id" class="form-select" onchange="this.form.submit()">
                  <option value="all">Semua Perangkat Daerah</option>
                  <?php foreach ($opdList as $o): ?>
                    <option value="<?= (int) $o['id'] ?>" <?= (string) $opdFilter === (string) $o['id'] ? 'selected' : '' ?>>
                      <?= esc($o['nama_opd']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="col-12 col-md-3">
              <noscript><button type="submit" class="btn btn-success w-100"><i class="fas fa-filter me-1"></i> Terapkan</button></noscript>
              <a href="<?= base_url('bupati/dashboard?tahun=' . (int) $tahunAktif) ?>" class="btn btn-outline-secondary w-100">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
              </a>
            </div>
          </form>
        </div>

        <?php if ($tahunAktif === null): ?>
          <div class="pk-empty">
            <div class="ic"><i class="fas fa-calendar-days"></i></div>
            <h5 class="fw-bold">Belum Ada Dokumen</h5>
            <p class="mb-0">Belum ada dokumen <?= esc($judul) ?> yang tersimpan.</p>
          </div>

        <?php elseif ($isBupati): ?>
          <?php if ($sasaranBupati === []): ?>
            <div class="pk-empty">
              <div class="ic"><i class="fas fa-folder-open"></i></div>
              <h5 class="fw-bold">Belum Ada Data</h5>
              <p class="mb-0">Dokumen PK Bupati tahun <?= (int) $tahunAktif ?> belum tersedia.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered align-middle pk-table mb-0">
                <thead class="text-center">
                  <tr>
                    <th style="width:4%;">No</th>
                    <th>Sasaran Strategis</th>
                    <th>Indikator Kinerja</th>
                    <th style="width:12%;">Target</th>
                    <th style="width:12%;">Satuan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1; ?>
                  <?php foreach ($sasaranBupati as $s): ?>
                    <?php $jml = max(1, count($s['indikator'])); $first = true; ?>
                    <?php if ($s['indikator'] === []): ?>
                      <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= esc($s['sasaran']) ?></td>
                        <td colspan="3" class="text-center text-muted">Belum ada indikator.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($s['indikator'] as $i): ?>
                        <tr>
                          <?php if ($first): ?>
                            <td class="text-center" rowspan="<?= $jml ?>"><?= $no++ ?></td>
                            <td rowspan="<?= $jml ?>"><?= esc($s['sasaran']) ?></td>
                            <?php $first = false; ?>
                          <?php endif; ?>
                          <td><?= esc($i['indikator']) ?></td>
                          <td class="text-center"><?= esc($i['target'] ?? '-') ?></td>
                          <td class="text-center"><?= esc($i['satuan'] ?? '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <?php if ($rows === []): ?>
            <div class="pk-empty">
              <div class="ic"><i class="fas fa-folder-open"></i></div>
              <h5 class="fw-bold">Belum Ada Data</h5>
              <p class="mb-0">Tidak ditemukan dokumen <?= esc($judul) ?> untuk filter yang dipilih.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered align-middle pk-table mb-0">
                <thead class="text-center">
                  <tr>
                    <th style="width:4%;">No</th>
                    <th style="width:20%;">Perangkat Daerah</th>
                    <th>Sasaran</th>
                    <th>Indikator Kinerja</th>
                    <th style="width:10%;">Target</th>
                    <th style="width:10%;">Satuan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1; ?>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= esc($r['opd'] ?? '-') ?></td>
                      <td><?= esc($r['sasaran'] ?? '-') ?></td>
                      <td><?= esc($r['indikator'] ?? '-') ?></td>
                      <td class="text-center"><?= esc($r['target'] ?? '-') ?></td>
                      <td class="text-center"><?= esc($r['satuan'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php endif; ?>

      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
  </div>
</body>

</html>

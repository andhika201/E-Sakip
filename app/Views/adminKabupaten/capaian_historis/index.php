<?php
/**
 * Dashboard > Capaian PK Bupati tahun sebelumnya (input manual).
 * Titik grafik "Tren Capaian PK Bupati" untuk tahun sebelum $mulaiEngine.
 */
$ro = !$bolehUbah;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Capaian PK Bupati Tahun Sebelumnya - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .hist-note { font-size: .8rem; color: #6b7a70; }
    .hist-table td, .hist-table th { vertical-align: middle; }
    .hist-table .th-tahun { width: 110px; font-weight: 700; color: #16321f; }
    .hist-table .td-capaian { width: 200px; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4" style="max-width: 900px; margin: 0 auto; width: 100%;">
        <h2 class="h3 fw-bold text-success text-center mb-1">Capaian PK Bupati Tahun Sebelumnya</h2>
        <p class="text-center text-muted small mb-4">
          Persentase capaian PK Bupati per tahun untuk grafik <strong>Tren Capaian PK Bupati</strong> di dashboard.
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
        <?php if (!$tabelAda): ?>
          <div class="alert alert-warning">
            <i class="fas fa-database me-1"></i> Tabel <code>capaian_pk_bupati_historis</code> belum dibuat.
            Jalankan <code>db/update_2026-09-26_capaian_pk_bupati_historis.sql</code> lebih dulu.
          </div>
        <?php endif; ?>

        <div class="alert alert-light border">
          <div class="fw-semibold mb-1"><i class="fas fa-circle-info me-1 text-success"></i> Aturan pengisian</div>
          <ul class="mb-0 hist-note">
            <li>Isi hanya untuk tahun sebelum <?= (int) $mulaiEngine ?>. Mulai <?= (int) $mulaiEngine ?>, capaian dihitung otomatis dari LAKIP Kabupaten.</li>
            <li>Angka dalam persen, boleh desimal dengan koma (mis. <code>87,5</code>). Kosongkan untuk menghapus.</li>
            <li>Keterangan opsional, mis. sumber angkanya (LKjIP tahun tersebut).</li>
          </ul>
        </div>

        <form method="post" action="<?= base_url('adminkab/dashboard/capaian-historis/save') ?>">
          <?= csrf_field() ?>
          <div class="table-responsive">
            <table class="table table-bordered hist-table mb-0">
              <thead class="table-light">
                <tr>
                  <th class="text-center">Tahun</th>
                  <th class="text-center">Capaian (%)</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tahunList as $th): ?>
                  <?php
                  $r   = $peta[$th] ?? null;
                  $old = old('rows.' . $th);
                  $nilai = is_array($old)
                      ? (string) ($old['capaian'] ?? '')
                      : ($r !== null ? str_replace('.', ',', rtrim(rtrim((string) $r['capaian'], '0'), '.')) : '');
                  $ket = is_array($old) ? (string) ($old['keterangan'] ?? '') : (string) ($r['keterangan'] ?? '');
                  ?>
                  <tr>
                    <td class="th-tahun text-center"><?= (int) $th ?></td>
                    <td class="td-capaian">
                      <div class="input-group input-group-sm">
                        <input type="text" inputmode="decimal" class="form-control text-end"
                               name="rows[<?= (int) $th ?>][capaian]" value="<?= esc($nilai) ?>"
                               aria-label="Capaian <?= (int) $th ?>" <?= $ro ? 'readonly' : '' ?>>
                        <span class="input-group-text">%</span>
                      </div>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm" maxlength="255"
                             name="rows[<?= (int) $th ?>][keterangan]" value="<?= esc($ket) ?>"
                             aria-label="Keterangan <?= (int) $th ?>" <?= $ro ? 'readonly' : '' ?>>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex flex-wrap gap-2 justify-content-between mt-4">
            <a href="<?= base_url('adminkab/dashboard') ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <?php if (!$ro && $tabelAda): ?>
              <button type="submit" class="btn btn-success"><i class="fas fa-floppy-disk me-1"></i> Simpan</button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>
  </div>
</body>

</html>

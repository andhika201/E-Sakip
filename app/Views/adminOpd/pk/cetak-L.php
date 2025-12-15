<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Perjanjian Kinerja <?= esc(date('Y', strtotime($tanggal))) ?></title>
  <style>
    body {
      font-family: 'Times New Roman', Times, serif;
      margin: 40px;
      color: #000;
      font-size: 12pt;
    }

    .signature-line {
      display: inline-block;
      border-bottom: 1px solid #000;
      padding-bottom: 5px;
      margin-bottom: 10px;
    }

    .name-line {
      font-weight: bold;
      text-transform: uppercase;
    }

    p {
      text-align: justify;
      margin-bottom: 15px;
    }

    /* Table dengan border */
    .table-bordered-custom,
    .table-bordered-custom th,
    .table-bordered-custom td {
      border: 1px solid #000;
      border-collapse: collapse;
    }

    /* Table tanpa border */
    .table-no-border,
    .table-no-border th,
    .table-no-border td {
      border: none !important;
      border-collapse: collapse;
    }

    .table-no-row-border td {
      border-top: none !important;
      border-bottom: none !important;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
    }

    .table-header th {
      border: 1px solid #000;
    }

    .no-border td {
      border: none;
      padding: 4px 0;
    }

    .center {
      text-align: center;
    }

    .text-uppercase {
      text-transform: uppercase;
    }

    .fw-bold {
      font-weight: bold;
    }

    .signature {
      margin-top: 60px;
      margin-bottom: 60px;
      text-align: center;
    }

    .section {
      margin-top: 50px;
    }

    table {
      page-break-inside: auto;
    }

    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    td,
    th {
      padding: 6px;
      vertical-align: top;
    }

    .table-bordered-custom td,
    .table-bordered-custom th {
      font-size: 11pt;
    }

    .signature-block {
      margin-top: 80px;
    }

    .signature-right {
      text-align: right;
    }

    .signature-center {
      text-align: center;
    }

    .signature-name {
      font-weight: bold;
      text-transform: uppercase;
      margin: 0;
    }

    .signature-meta {
      margin: 0;
      font-size: 11pt;
    }
  </style>
</head>

<body>
  <!-- Halaman 2 -->
  <page orientation="P" size="FOLIO">
    <h4 style="text-align: center;">
      PERJANJIAN KINERJA TAHUN <?= esc(date('Y', strtotime($tanggal))) ?>
      <br>
      <?php if (strtolower($jenis) === 'bupati'): ?>
        KABUPATEN PRINGSEWU
      <?php else: ?>
        <?= esc($nama_opd) ?>
      <?php endif; ?>
    </h4>

    <table class="table-bordered-custom table-header" style="width: 100%; margin-top: 10px;">
      <thead>
        <tr>
          <th style="width: 5%; text-align: center;">No</th>
          <th style="width: 35%; text-align: center;">SASARAN STRATEGIS</th>
          <th style="width: 35%; text-align: center;">INDIKATOR SASARAN</th>
          <th style="width: 15%; text-align: center;">TARGET</th>
          <th style="width: 10%; text-align: center;">SATUAN</th>

        </tr>
      </thead>
      <tbody style="border: none; width: 100%; cellpadding: 0; cellspacing: 0;">
        <tr>
          <td style="text-align: center; font-style: italic;">1</td>
          <td style="text-align: center; font-style: italic;">2</td>
          <td style="text-align: center; font-style: italic;">3</td>
          <td style="text-align: center; font-style: italic;">4</td>
          <td style="text-align: center; font-style: italic;">5</td>
        </tr>
        <?php $no = 1; ?>
        <?php foreach ($sasaran_pk as $sasaran): ?>
          <?php foreach ($sasaran['indikator'] as $i => $indikator): ?>
            <tr class="table-no-row-border">
              <?php if ($i === 0): ?>
                <td rowspan="<?= count($sasaran['indikator']) ?>" style="text-align: center;"><?= $no++ ?>.</td>
                <td rowspan="<?= count($sasaran['indikator']) ?>" style="text-align: left;">
                  <?= esc(ucwords($sasaran['sasaran'])) ?>
                </td>
              <?php endif; ?>
              <td style="text-align: left;"><?= esc($indikator['indikator']) ?></td>
              <td style="text-align: center;"><?= esc($indikator['target']) ?></td>
              <td style="text-align: center;"><?= esc($indikator['satuan']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <tr class="table-no-row-border">
          <td style="height: 20px;"></td>
          <td style="height: 20px;"></td>
          <td style="height: 20px;"></td>
          <td style="height: 20px;"></td>
        </tr>
      </tbody>
    </table>

    <table class="table-bordered-custom" style="width:100%; margin-top:30px;">
      <thead>
        <tr class="center fw-bold">
          <th style="width:5%;">NO</th>
          <th style="width:65%;">PROGRAM</th>
          <th style="width:30%;">ANGGARAN (Rp)</th>
        </tr>
      </thead>
      <tbody>
        <?php $no_pa = 1;
        $totalAnggaran = 0; ?>
        <?php foreach ($program_pk as $prog): ?>
          <?php $totalAnggaran += (float) $prog['anggaran']; ?>
          <tr>
            <td class="center"><?= $no_pa++ ?></td>
            <td><?= esc($prog['program_kegiatan']) ?></td>
            <td style="text-align:right;">
              <?= number_format($prog['anggaran'], 0, ',', '.') ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <!-- TOTAL -->
        <tr class="fw-bold">
          <td colspan="2" style="text-align:right;">TOTAL</td>
          <td style="text-align:right;">
            <?= number_format($totalAnggaran, 0, ',', '.') ?>
          </td>
        </tr>
      </tbody>
    </table>


    <table style="width: 100%; margin-top: 70px;" class="table-no-border">
      <tr>
        <?php if (strtolower($jenis) !== 'bupati'): ?>
          <!-- PIHAK KEDUA -->
          <td style="text-align: center; width: 50%; vertical-align: top;">
            <p class="text-uppercase">PIHAK KEDUA, <br><?= esc($jabatan_pihak_2) ?></p>
            <br><br><br><br><br>
            <p class="text-uppercase" style="font-weight: bold; margin: 0;"><?= esc($nama_pihak_2) ?></p>
            <p style="margin: 0;"><?= esc($pangkat_pihak_2) ?></p>
            <p style="margin: 0;"><?= esc($nip_pihak_2) ?></p>
          </td>

          <!-- PIHAK KESATU -->
          <td style="text-align: center; width: 50%; vertical-align: top;">
            <p class="text-uppercase">PIHAK KESATU,<br><?= esc($jabatan_pihak_1) ?></p>
            <br><br><br><br><br>
            <p class="text-uppercase" style="font-weight: bold; margin: 0;"><?= esc($nama_pihak_1) ?></p>
            <p style="margin: 0;"><?= esc($pangkat_pihak_1) ?></p>
            <p style="margin: 0;"><?= esc($nip_pihak_1) ?></p>
          </td>

        <?php else: ?>

          <td style="text-align: right; width: 50%; vertical-align: top;">
            <p class="text-uppercase">BUPATI PRINGSEWU</p>
            <br><br><br><br><br>
            <p class="text-uppercase" style="font-weight: bold; margin: 0;"><?= esc($nama_pihak_1) ?></p>
          </td>
        <?php endif; ?>
      </tr>
    </table>
    <page />
</body>

</html>
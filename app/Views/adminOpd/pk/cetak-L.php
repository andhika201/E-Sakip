<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Perjanjian Kinerja <?= esc(date('Y', strtotime($tanggal))) ?></title>
  <style>
    /* =========================
     GLOBAL
  ========================== */
    body {
      font-family: "Times New Roman", Times, serif;
      margin: 40px;
      font-size: 12pt;
      color: #000;
      line-height: 1.5;
    }

    h4 {
      text-align: center;
      margin-bottom: 20px;
    }

    p {
      text-align: justify;
      margin: 0 0 14px 0;
    }

    /* =========================
     TEXT UTILITIES
  ========================== */
    .center {
      text-align: center;
    }

    .fw-bold {
      font-weight: bold;
    }

    .text-uppercase {
      text-transform: uppercase;
    }

    /* =========================
     TABLE GENERAL
  ========================== */
    table {
      width: 100%;
      border-collapse: collapse;
      page-break-inside: auto;
    }

    th,
    td {
      padding: 6px 8px;
      vertical-align: top;
      font-size: 11pt;
    }

    tr {
      page-break-inside: avoid;
    }

    /* =========================
     TABLE BORDERED
  ========================== */
    .table-bordered-custom th,
    .table-bordered-custom td {
      border: 1px solid #000;
    }

    .table-header th {
      text-align: center;
      font-weight: bold;
    }

    /* Baris indikator tanpa border horizontal */
    .table-no-row-border td {
      border-top: none;
      border-bottom: none;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
    }


    /* =========================
     SIGNATURE
  ========================== */
    .signature-table {
      width: 100%;
      border-collapse: collapse;
      border: none;
    }

    .signature-bottom {
      width: 100%;
      border-collapse: collapse;
      border: none;
    }

    .signature-title {
      text-align: center;
      vertical-align: top;
      height: 160px;
      padding: 6px 4px;
    }

    .signature-title p {
      margin: 0;
      line-height: 1.3;
    }

    .signature-title .label {
      font-weight: bold;
      margin-bottom: 4px;
    }

    .signature-name {
      margin: 0;
      text-align: center;
      font-weight: bold;
      text-transform: uppercase;
    }

    .signature-meta {
      text-align: center;
      font-size: 11pt;
      margin: 2px 0;
    }

    .signature-meta p {
      margin: 0;
      line-height: 1.3;
    }

    .signature-meta .label {
      font-weight: bold;
      margin-bottom: 4px;
    }

    /* ========================= SECTION SPACING ========================== */
    .section {
      margin-top: 40px;
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

          <?php
          $label = strtoupper(trim($sasaran['sasaran']));

          // 👉 JIKA SASARAN "-" atau "N/A" → SKIP TOTAL (TIDAK TERCETAK)
          if (in_array($label, ['-', 'N/A'])) {
            continue;
          }

          if (empty($sasaran['indikator'])) {
            continue;
          }

          $rowspan = count($sasaran['indikator']);
          ?>

          <?php foreach ($sasaran['indikator'] as $i => $indikator): ?>
            <tr class="table-no-row-border">
              <?php if ($i === 0): ?>
                <td rowspan="<?= $rowspan ?>" style="text-align:center;">
                  <?= $no++ ?>.
                </td>
                <td rowspan="<?= $rowspan ?>" style="text-align:left;">
                  <?= esc(ucwords($sasaran['sasaran'])) ?>
                </td>
              <?php endif; ?>

              <td style="text-align:left;">
                <?= esc($indikator['indikator']) ?>
              </td>
              <td style="text-align:center;">
                <?= esc($indikator['target']) ?>
              </td>
              <td style="text-align:center;">
                <?= esc($indikator['satuan']) ?>
              </td>
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

    <?php if ($tampilkanProgram && !empty($program_pk)): ?>
      <table class="table-bordered-custom" style="width:100%; margin-top:30px;">
        <thead>
          <tr class="center fw-bold">
            <th style="width:7%;">No</th>
            <th style="width:65%;"><?= strtoupper(esc($program)) ?></th>
            <th style="width:28%;">ANGGARAN (Rp)</th>
          </tr>
        </thead>
        <tbody>
          <?php $no_pa = 1;
          $totalAnggaran = 0; ?>
          <?php foreach ($program_pk as $prog): ?>
            <?php $totalAnggaran += (float) $prog['anggaran']; ?>
            <tr>
              <td class="center"><?= $no_pa++ ?></td>
              <td><?= esc($prog[$program]) ?></td>
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
    <?php endif; ?>



    <table style="width:100%; margin-top:30px;">
      <tr>
        <?php if (strtolower($jenis) !== 'bupati'): ?>
          <!-- PIHAK KEDUA -->
          <td style="width:50%;">
            <table class="signature-table">
              <tr>
                <td class="signature-title">
                  <p class="label">PIHAK KEDUA,</p>
                  <p><?= esc($jabatan_pihak_2) ?></p>
                </td>
              </tr>
            </table>
            <table class="signature-bottom">
              <tr>
                <td class="signature-meta">
                  <p class="label"><strong><?= esc(strtoupper($nama_pihak_2)) ?></strong></p>
                  <?php if (strtolower($jenis) !== 'jpt'): ?>
                    <p>NIP. <?= esc($nip_pihak_2) ?></p>
                  <?php endif; ?>
                </td>
              </tr>
            </table>
          </td>

          <!-- PIHAK KESATU -->
          <td style="width:50%;">
            <table class="signature-table">
              <tr>
                <td class="signature-title">
                  <p class="label">PIHAK KESATU,</p>
                  <p><?= esc($jabatan_pihak_1) ?></p>
                </td>
              </tr>
            </table>
            <table class="signature-bottom">
              <tr>
                <td class="signature-meta">
                  <p class="label"><strong><?= esc(strtoupper($nama_pihak_1)) ?></strong></p>
                  <p>NIP. <?= esc($nip_pihak_1) ?></p>
                </td>
              </tr>
            </table>
          </td>
        <?php else: ?>

          <td style="width:50%;"></td>
          <td style="width:50%;">
            <table class="signature-table">
              <tr>
                <td class="signature-title">
                  <strong>BUPATI PRINGSEWU</strong>
                </td>
              </tr>
            </table>
            <table class="signature-table">
              <tr>
                <td class="signature-name"><?= esc(strtoupper($nama_pihak_1)) ?></td>
              </tr>
            </table>
          </td>

        <?php endif; ?>

      </tr>
    </table>


    <page />
</body>

</html>
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



    td,
    th {
      padding: 6px;
      vertical-align: top;
    }

    .table-bordered-custom td,
    .table-bordered-custom th {
      font-size: 11pt;
    }

    .signature-name {
      font-weight: bold;
      text-transform: uppercase;
      margin: 0;
      text-align: center;
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
      /* jarak = ±1 baris */
    }

    .footer {
      bottom: 15px;
      left: 0;
      right: 0;
      font-size: 9pt;
      color: #444;
    }

    .footer-left {
      position: absolute;
      left: 40px;
    }

    .footer-right {
      position: absolute;
      right: 40px;
    }
  </style>
</head>

<body>
  <!-- Halaman 1 -->
  <table width="100%" style="border: none; text-align: center;">
    <tr>
      <td style="border: none;">
        <img src="<?= $logo_url ?>" alt="Logo">
        <?php if (strtolower($jenis) === 'bupati'): ?>
          <h3 style="font-weight: bold; margin: 0;">BUPATI PRINGSEWU</h3>
          <h3 style="font-weight: bold; margin: 0;">PROVINSI LAMPUNG</h3>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
    </tr>
    <tr>
      <!-- Halaman 1 -->
      <td style="border: none;">
        <h4 style="font-weight: bold; margin: 0;">PERJANJIAN KINERJA TAHUN <?= esc(date('Y', strtotime($tanggal))) ?>
        </h4>
        <?php if (strtolower($jenis) === 'bupati'): ?>
          <h4 style="font-weight: bold; margin: 0;">PEMERINTAH KABUPATEN PRINGSEWU</h4>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <p>Dalam rangka mewujudkan manajemen pemerintahan yang efektif, transparan dan akuntabel serta berorientasi pada
    hasil, kami yang bertanda tangan di bawah ini :</p>

  <table class="table-no-border" style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="width: 80px;">Nama</td>
      <td style="width: 10px;">:</td>
      <td class="fw-bold text-uppercase"><?= esc($nama_pihak_1) ?></td>
    </tr>
    <tr>
      <td>Jabatan</td>
      <td>:</td>
      <td class="fw-bold text-uppercase"><?= esc($jabatan_pihak_1) ?></td>
    </tr>
  </table>

  <?php if (strtolower($jenis) !== 'bupati'): ?>
    <p>Selanjutnya disebut PIHAK KESATU</p>

    <table class="table-no-border" syle="width: 100%; border-collapse: collapse; margin-top: 15px;">
      <tr>
        <td style="width: 80px;">Nama</td>
        <td style="width: 10px;">:</td>
        <td class="fw-bold text-uppercase"><?= esc($nama_pihak_2) ?></td>
      </tr>
      <tr>
        <td>Jabatan</td>
        <td>:</td>
        <td class="fw-bold text-uppercase"><?= esc($jabatan_pihak_2) ?></td>
      </tr>
    </table>

    <p>Selaku atasan PIHAK KESATU, selanjutnya disebut PIHAK KEDUA.</p>

    <p>PIHAK KESATU pada tahun <?= esc(date('Y', strtotime($tanggal))) ?> berjanji akan mewujudkan target kinerja
      tahunan sesuai lampiran perjanjian ini dalam rangka mencapai target kinerja jangka menengah seperti yang
      ditetapkan dalam dokumen perencanaan. Keberhasilan pencapaian target kinerja tersebut menjadi tanggung jawab PIHAK
      KESATU.</p>

    <p>PIHAK KEDUA akan memberikan supervisi yang diperlukan serta akan melakukan evaluasi akuntabilitas kinerja
      terhadap capaian kinerja dari perjanjian ini dan mengambil tindakan yang diperlukan dalam rangka pemberian
      penghargaan dan sanksi.</p>
  <?php endif; ?>

  <?php if (strtolower($jenis) === 'bupati'): ?>
    <p>Berjanji akan mewujudkan target kinerja tahunan sesuai lampiran perjanjian ini dalam rangka mencapai target
      kinerja
      jangka menengah seperti yang ditetapkan dalam dokumen perencanaan. Keberhasilan dan kegagalan pencapaian target
      kinerja
      tersebut menjadi tanggungjawab kami.</p>

  <?php endif; ?>

  <table width="100%" cellspacing="0" cellpadding="4" style="margin-top:30px;">
    <tr>
      <?php if (strtolower($jenis) !== 'bupati'): ?>

        <!-- PIHAK KEDUA -->
        <td width="50%" align="center" valign="top">
          <strong>PIHAK KEDUA,</strong><br>
          <?= esc($jabatan_pihak_2) ?><br><br><br><br>

          <strong><?= esc(strtoupper($nama_pihak_2)) ?></strong><br>
          <?php if (strtolower($jenis) !== 'jpt'): ?>
            NIP. <?= esc($nip_pihak_2) ?>
          <?php endif; ?>
        </td>

        <!-- PIHAK KESATU -->
        <td width="50%" align="center" valign="top">
          <strong>PIHAK KESATU,</strong><br>
          <?= esc($jabatan_pihak_1) ?><br><br><br><br>

          <strong><?= esc(strtoupper($nama_pihak_1)) ?></strong><br>
          NIP. <?= esc($nip_pihak_1) ?>
        </td>

      <?php else: ?>

        <td width="50%"></td>
        <td width="50%" align="center" valign="top">
          <strong>BUPATI PRINGSEWU</strong><br><br><br><br>
          <strong><?= esc(strtoupper($nama_pihak_1)) ?></strong>
        </td>

      <?php endif; ?>
    </tr>
  </table>


</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Perjanjian Kinerja 2025</title>
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

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    table td,
    table th {
      border: 1px solid #000;
      padding: 6px 10px;
      vertical-align: top;
    }

    table th {
      background-color: #f2f2f2;
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

    .table-no-border td,
    .table-no-border {
      border: none !important;
    }
  </style>
</head>

<body>
  <!-- Halaman 1 -->
  <page orientation="P" size="FOLIO">
    <table width="100%" style="border: none; text-align: center;">
      <tr>
        <td style="border: none;">
          <img src="<?= $logo_url ?>" alt="Logo">
        </td>
      </tr>
      <tr>
        <!-- Halaman 1 -->
        <td style="border: none;">
          <h4 style="font-weight: bold; margin: 0;">PERJANJIAN KINERJA TAHUN <?= esc(date('Y', strtotime($tanggal))) ?>
          </h4>
        </td>
      </tr>
    </table>

    <p>Dalam rangka mewujudkan manajemen pemerintahan yang efektif, transparan dan akuntabel serta berorientasi pada
      hasil, kami yang bertanda tangan di bawah ini :</p>

    <table class="table-no-border">
      <tr>
        <td style="width: 80px;"><strong>Nama</strong></td>
        <td style="width: 10px;">:</td>
        <td class="fw-bold text-uppercase"><?= esc($nama_pihak_1) ?></td>
      </tr>
      <tr>
        <td><strong>Jabatan</strong></td>
        <td>:</td>
        <td class="fw-bold"><?= esc($jabatan_pihak_1) ?></td>
      </tr>
    </table>

    <p>Selanjutnya disebut <strong>PIHAK KESATU</strong></p>

    <table class="table-no-border">
      <tr>
        <td style="width: 80px;"><strong>Nama</strong></td>
        <td style="width: 10px;">:</td>
        <td class="fw-bold text-uppercase"><?= esc($nama_pihak_2) ?></td>
      </tr>
      <tr>
        <td><strong>Jabatan</strong></td>
        <td>:</td>
        <td class="fw-bold"><?= esc($jabatan_pihak_2) ?></td>
      </tr>
    </table>

    <p>Selaku atasan<strong>PIHAK KESATU</strong>, selanjutnya disebut <strong>PIHAK KEDUA</strong>.</p>

    <p>PIHAK KESATU pada tahun <?= esc(date('Y', strtotime($tanggal))) ?> berjanji akan mewujudkan target kinerja
      tahunan sesuai lampiran perjanjian ini dalam rangka mencapai target kinerja jangka menengah seperti yang
      ditetapkan dalam dokumen perencanaan. Keberhasilan pencapaian target kinerja tersebut menjadi tanggung jawab PIHAK
      KESATU.</p>

    <p>PIHAK KEDUA akan memberikan supervisi yang diperlukan serta akan melakukan evaluasi akuntabilitas kinerja
      terhadap capaian kinerja dari perjanjian ini dan mengambil tindakan yang diperlukan dalam rangka pemberian
      penghargaan dan sanksi.</p>

    <table style="width: 100%; margin-top: 80px;" class="table-no-border">
      <tr>
        <!-- PIHAK KEDUA -->
        <td style="text-align: center; width: 50%; vertical-align: top;">
          <p><strong>PIHAK KEDUA</strong><br><?= esc($nama_pihak_2) ?></p>
          <br><br><br><br><br>
          <p class="text-uppercase" style="font-weight: bold; margin: 0;"><?= esc($jabatan_pihak_2) ?></p>
          <p style="margin: 0;"><?= esc($pangkat_pihak_2) ?></p>
          <p style="margin: 0;"><?= esc($nip_pihak_2) ?></p>
        </td>

        <!-- PIHAK KESATU -->
        <td style="text-align: center; width: 50%; vertical-align: top;">
          <p><strong>PIHAK KESATU</strong><br><?= esc($nama_pihak_1) ?></p>
          <br><br><br><br><br>
          <p class="text-uppercase" style="font-weight: bold; margin: 0;"><?= esc($jabatan_pihak_1) ?></p>
          <p style="margin: 0;"><?= esc($pangkat_pihak_1) ?></p>
          <p style="margin: 0;"><?= esc($nip_pihak_1) ?></p>
        </td>
      </tr>
    </table>
  </page>
</body>

</html>
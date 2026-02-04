<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Perjanjian Kinerja <?= esc($tahun) ?></title>
  <style>
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

    .center {
      text-align: center;
    }

    .fw-bold {
      font-weight: bold;
    }

    .text-uppercase {
      text-transform: uppercase;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 6px 8px;
      vertical-align: top;
      font-size: 11pt;
    }

    .table-bordered-custom th,
    .table-bordered-custom td {
      border: 1px solid #000;
    }

    .table-header th {
      text-align: center;
      font-weight: bold;
    }

    .table-no-row-border td {
      border-top: none;
      border-bottom: none;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
    }

    .signature-title {
      text-align: center;
      height: 160px;
    }

    .signature-meta {
      text-align: center;
      font-size: 11pt;
    }

    .signature-name {
      text-align: center;
      font-weight: bold;
      text-transform: uppercase;
    }
  </style>
</head>

<body>

  <h4>
    PERJANJIAN KINERJA TAHUN <?= esc($tahun) ?><br>
    <?= strtolower($jenis) === 'bupati' ? 'KABUPATEN PRINGSEWU' : esc($nama_opd) ?>
  </h4>

  <?php
  $head_table = ($jenis === 'pengawas') ? 'KEGIATAN' : 'PROGRAM';
  $head_table_sasaran = ($jenis === 'pengawas')
    ? 'KEGIATAN'
    : (in_array($jenis, ['jpt', 'bupati']) ? 'STRATEGIS' : 'PROGRAM');
  ?>

  <!-- TABEL SASARAN -->
  <table class="table-bordered-custom table-header">
    <thead>
      <tr>
        <th width="5%">No</th>
        <th width="35%">SASARAN <?= strtoupper($head_table_sasaran) ?></th>
        <th width="30%">INDIKATOR SASARAN</th>
        <th width="15%">TARGET</th>
        <th width="15%">SATUAN</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td width="5%" class="center"><i>1</i></td>
        <td width="35%" class="center"><i>2</i></td>
        <td width="30%" class="center"><i>3</i></td>
        <td width="15%" class="center"><i>4</i></td>
        <td width="15%" class="center"><i>5</i></td>
      </tr>

      <?php $no = 1; ?>
      <?php foreach ($sasaran_pk as $sasaran): ?>
        <?php
        if (in_array(strtoupper(trim($sasaran['sasaran'])), ['-', 'N/A']))
          continue;
        if (empty($sasaran['indikator']))
          continue;
        $rowspan = count($sasaran['indikator']);
        ?>

        <?php foreach ($sasaran['indikator'] as $i => $indikator): ?>
          <tr class="table-no-row-border">
            <?php if ($i === 0): ?>
              <td width="5%" rowspan="<?= $rowspan ?>" class="center">
                <?= $no++ ?>
              </td>
              <td width="35%" rowspan="<?= $rowspan ?>">
                <?= esc($sasaran['sasaran']) ?>
              </td>
            <?php endif; ?>

            <td width="30%">
              <?= esc($indikator['indikator']) ?>
            </td>
            <td width="15%" class="center">
              <?= esc($indikator['target']) ?>
            </td>
            <td width="15%" class="center">
              <?= esc($indikator['satuan']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table width="100%">
    <tr>
      <td style="height:25px;"></td>
    </tr>
  </table>


  <!-- TABEL PROGRAM -->
  <?php if (!empty($mainRows)): ?>
<table class="table-bordered-custom" style="margin-top:30px;">
  <thead>
    <tr class="fw-bold center">
      <th width="5%">No</th>
      <th width="65%">PROGRAM</th>
      <th width="30%">ANGGARAN (Rp)</th>
    </tr>
  </thead>
  <tbody>
    <?php $noProg = 1; ?>
    <?php foreach ($mainRows as $item): ?>
      <tr>
        <td class="center"><?= $noProg++ ?></td>
        <td><?= esc($item['nama']) ?></td>
        <td align="right"><?= number_format($item['anggaran'],0,',','.') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php if (!empty($tailRows)): ?>
<table class="table-bordered-custom" style="margin-top:20px;">
  <thead>
    <tr class="fw-bold center">
      <th width="5%">No</th>
      <th width="65%">PROGRAM</th>
      <th width="30%">ANGGARAN (Rp)</th>
    </tr>
  </thead>
  <tbody>

    <?php foreach ($tailRows as $item): ?>
      <tr>
        <td class="center"><?= $noProg++ ?></td>
        <td><?= esc($item['nama']) ?></td>
        <td align="right"><?= number_format($item['anggaran'],0,',','.') ?></td>
      </tr>
    <?php endforeach; ?>

    <?php
        $grandTotal = 0;
        foreach ($mainRows as $r)
          $grandTotal += (float) $r['anggaran'];
        foreach ($tailRows as $r)
          $grandTotal += (float) $r['anggaran'];
        ?>

    <tr class="fw-bold">
      <td colspan="2">TOTAL</td>
      <td align="right"><?= number_format($grandTotal,0,',','.') ?></td>
    </tr>

    <!-- SPASI -->
    <tr><td colspan="3" style="height:25px;"></td></tr>

    <!-- TTD -->
    <tr>
      <td colspan="3">
        <table width="100%">
          <tr>
            <td width="50%" align="center">
              <b>PIHAK KEDUA</b><br>
              BUPATI PRINGSEWU<br><br><br>
              <b>RIYANTO PAMUNGKAS</b>
            </td>
            <td width="50%" align="center">
              <b>PIHAK KESATU</b><br>
              Camat Pringsewu<br><br><br>
              <b>CHRISTIANTO H. SANI</b><br>
              NIP. 198710202010011001
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </tbody>
</table>
<?php endif; ?>





</body>

</html>
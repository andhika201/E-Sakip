text/x-generic cetak-L.php ( HTML document, ASCII text, with CRLF line terminators )
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

    .program-table tbody tr:nth-last-child(-n+2),
    .signature-block {
      page-break-inside: avoid;
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

    <?php $head_table = ($jenis === 'pengawas') ? 'KEGIATAN' : 'PROGRAM';
    ?>

    <?php
    $head_table_sasaran = ($jenis === 'pengawas')
      ? 'KEGIATAN'
      : (in_array($jenis, ['jpt', 'bupati']) ? 'STRATEGIS' : 'PROGRAM');
    ?>

    <table class="table-bordered-custom table-header" style="width: 100%; margin-top: 10px;">
      <thead>
        <tr>
          <th style="width: 10%; text-align: center;">No</th>
          <th style="width: 30%; text-align: center;">SASARAN <?= strtoupper($head_table_sasaran) ?></th>
          <th style="width: 30%; text-align: center;">INDIKATOR SASARAN</th>
          <th style="width: 15%; text-align: center;">TARGET</th>
          <th style="width: 15%; text-align: center;">SATUAN</th>
        </tr>
      </thead>
      <tbody style="border: none; width: 100%;">
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
      <table class="table-bordered-custom program-table" style="width:100%; margin-top:30px;">
        <thead>
          <tr class="center fw-bold">
            <th style="width:7%;">No</th>
            <th style="width:65%;"><?= strtoupper(esc($head_table)) ?></th>
            <th style="width:28%;">ANGGARAN (Rp)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          /**
           * ==============================
           * SETTING STRUKTUR BERDASARKAN JENIS PK
           * ==============================
           */
          if ($jenis === 'bupati' || $jenis === 'jpt') {
            $groupField = null;                 // tidak ada header
            $itemField = 'program_kegiatan';   // item = program
          } elseif ($jenis === 'administrator') {
            $groupField = 'program_kegiatan';   // header = program
            $itemField = 'kegiatan';            // item = kegiatan
          } elseif ($jenis === 'pengawas') {
            $groupField = 'kegiatan';            // header = kegiatan
            $itemField = 'sub_kegiatan';        // item = sub kegiatan
          }

          /**
           * ==============================
           * GROUPING DATA (UNIVERSAL)
           * ==============================
           */
          $groupedData = [];

          foreach ($program_pk as $row) {

            /**
             * ==============================
             * KHUSUS PK BUPATI (OPSI B)
             * TAMPILKAN SEMUA BARIS (TANPA DEDUPLIKASI)
             * ==============================
             */
            if ($jenis === 'bupati') {

              if (!isset($groupedData['_flat'])) {
                $groupedData['_flat'] = [
                  'nama' => null,
                  'items' => []
                ];
              }

              $groupedData['_flat']['items'][] = [
                'nama' => $row['program_kegiatan'],
                'anggaran' => $row['anggaran']
              ];

              continue;
            }

            /**
             * ==============================
             * JENIS LAIN (LOGIKA LAMA)
             * ==============================
             */
            $groupName = $groupField ? ($row[$groupField] ?? '-') : null;

            $itemKey = md5(
              ($row[$itemField] ?? '') . '|' . ($row['anggaran'] ?? 0)
            );

            if ($groupField) {
              if (!isset($groupedData[$groupName])) {
                $groupedData[$groupName] = [
                  'nama' => $groupName,
                  'items' => []
                ];
              }

              if (!isset($groupedData[$groupName]['items'][$itemKey])) {
                $groupedData[$groupName]['items'][$itemKey] = [
                  'nama' => $row[$itemField],
                  'anggaran' => $row['anggaran']
                ];
              }
            } else {
              if (!isset($groupedData['_flat'])) {
                $groupedData['_flat'] = [
                  'nama' => null,
                  'items' => []
                ];
              }

              if (!isset($groupedData['_flat']['items'][$itemKey])) {
                $groupedData['_flat']['items'][$itemKey] = [
                  'nama' => $row[$itemField],
                  'anggaran' => $row['anggaran']
                ];
              }
            }
          }
          ?>

          <?php $grandTotal = 0; ?>

          <?php foreach ($groupedData as $group): ?>

            <?php if ($group['nama']): ?>
              <tr class="fw-bold">
                <td colspan="3">
                  <?= esc($group['nama']) ?>
                </td>
              </tr>
            <?php endif; ?>

            <?php $no = 1; ?>
            <?php foreach ($group['items'] as $item): ?>
              <?php $grandTotal += (float) $item['anggaran']; ?>
              <tr>
                <td class="center"><?= $no++ ?></td>
                <td><?= esc($item['nama']) ?></td>
                <td align="right"><?= number_format($item['anggaran'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>

          <?php endforeach; ?>

          <tr class="fw-bold">
            <td colspan="2" class="right">TOTAL</td>
            <td align="right"><?= number_format($grandTotal, 0, ',', '.') ?></td>
          </tr>
        </tbody>
      </table>
    <?php endif; ?>


    <div class="signature-block">

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
    </div>


    <page />
</body>

</html>
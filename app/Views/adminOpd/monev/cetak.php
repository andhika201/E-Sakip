<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: "Times New Roman";
            font-size: 11pt;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
        }

        th {
            background: #eee;
            text-align: center;
        }

        .logo {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 70px;
        }
    </style>

</head>

<body>

    <table style="width:100%; border:none; margin-bottom:20px;">
        <tr>
            <td style="width:120px; border:none;">
                <img src="<?= $logo_url ?>" style="width:80px;">
            </td>

            <td style="border:none; text-align:center;">
                <div style="font-weight:bold; font-size:16px;">
                    MONITORING DAN EVALUASI KINERJA
                </div>
                <div style="font-weight:bold; font-size:14px;">
                    <?= esc(strtoupper($opd['nama_opd'] ?? '-')) ?>
                </div>
                <div style="font-size:13px;">
                    Tahun <?= esc($tahun ?? '-') ?>
                </div>
            </td>

            <td style="width:120px; border:none;"></td>
        </tr>
    </table>

    <table>

        <thead>

            <tr>
                <th>No</th>
                <th>Sasaran</th>
                <th>Indikator</th>
                <th>Tahun</th>
                <th>Satuan</th>
                <th>Rencana Aksi</th>
                <th>Baseline</th>

                <th>TW1</th>
                <th>TW2</th>
                <th>TW3</th>
                <th>TW4</th>

                <th>C1</th>
                <th>C2</th>
                <th>C3</th>
                <th>C4</th>

                <th>Total</th>
                <th>PJ</th>

            </tr>

        </thead>

        <tbody>

            <?php $no = 1;
            foreach ($monevList as $row): ?>

                <tr>

                    <td><?= $no++ ?></td>
                    <td><?= esc($row['sasaran_renstra']) ?></td>
                    <td><?= esc($row['indikator_sasaran']) ?></td>
                    <td><?= esc($row['indikator_tahun']) ?></td>
                    <td><?= esc($row['satuan']) ?></td>

                    <td><?= esc($row['rencana_aksi']) ?></td>

                    <td><?= esc($row['target_capaian']) ?></td>

                    <td><?= esc($row['target_triwulan_1']) ?></td>
                    <td><?= esc($row['target_triwulan_2']) ?></td>
                    <td><?= esc($row['target_triwulan_3']) ?></td>
                    <td><?= esc($row['target_triwulan_4']) ?></td>

                    <td><?= esc($row['capaian_triwulan_1']) ?></td>
                    <td><?= esc($row['capaian_triwulan_2']) ?></td>
                    <td><?= esc($row['capaian_triwulan_3']) ?></td>
                    <td><?= esc($row['capaian_triwulan_4']) ?></td>

                    <td><?= esc($row['monev_total']) ?></td>

                    <td><?= esc($row['penanggung_jawab']) ?></td>

                </tr>

            <?php endforeach ?>

        </tbody>

    </table>

</body>

</html>
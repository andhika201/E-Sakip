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
            page-break-inside: auto;

        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
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

        .signature-space {
            height: 120px;
        }

        .signature-name {
            text-align: center;
            margin-top: 10px;
        }

        .keep-with-signature {
            page-break-inside: avoid;
        }

        .signature-block {
            page-break-before: avoid;
            margin-top: 40px;
        }

        tr {
            page-break-inside: avoid;
        }

        .no-border-table td {
            border: none !important;
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
            <?php
            $total = count($monevList);
            $breakPoint = $total - 2; // Kita sisakan 2 baris terakhir untuk menemani ttd
            $no = 1;

            foreach ($monevList as $i => $row):
                // Jika sudah mencapai 2 baris terakhir, tutup tbody lama dan buka yang baru
                if ($i == $breakPoint && $i > 0):
                    ?>
                </tbody>
                <tbody style="page-break-inside: avoid;">
                <?php endif; ?>

                <tr>
                    <td style="text-align:center;"><?= $no++ ?></td>
                    <td><?= esc($row['sasaran_renstra']) ?></td>
                    <td><?= esc($row['indikator_sasaran']) ?></td>
                    <td><?= esc($row['indikator_tahun']) ?></td>
                    <td><?= esc($row['satuan']) ?></td>

                    <td class="text-start">
                        <?= nl2br(esc($row['rencana_aksi'] ?? '-')) ?>
                    </td>
                    <td style="text-align:center;"><?= esc($row['target_capaian']) ?></td>

                    <td style="text-align:center;"><?= esc($row['target_triwulan_1']) ?></td>
                    <td style="text-align:center;"><?= esc($row['target_triwulan_2']) ?></td>
                    <td style="text-align:center;"><?= esc($row['target_triwulan_3']) ?></td>
                    <td style="text-align:center;"><?= esc($row['target_triwulan_4']) ?></td>

                    <td style="text-align:center;"><?= esc($row['capaian_triwulan_1']) ?></td>
                    <td style="text-align:center;"><?= esc($row['capaian_triwulan_2']) ?></td>
                    <td style="text-align:center;"><?= esc($row['capaian_triwulan_3']) ?></td>
                    <td style="text-align:center;"><?= esc($row['capaian_triwulan_4']) ?></td>

                    <td style="text-align:center;"><?= esc($row['monev_total']) ?></td>

                    <td><?= esc($row['penanggung_jawab']) ?></td>
                </tr>

            <?php endforeach ?>

            <!-- Baris Kosong Penengah -->
            <tr>
                <td colspan="17" style="border:none; height:30px;"></td>
            </tr>

            <!-- Area Tanda Tangan (masih di dalam tbody yang sama agar tidak terpisah) -->
            <tr>
                <td colspan="11" style="border:none;"></td>
                <td colspan="6" style="border:none; text-align:center;">
                    Pringsewu, <?= formatTanggal(date('Y-m-d')) ?>
                    <br>
                    <?= esc($pejabat['nama_jabatan'] ?? ('Kepala ' . ($opd['nama_opd'] ?? '-'))) ?>
                    <br><br><br><br><br>
                    <strong>( <?= esc($pejabat['nama_pegawai'] ?? '........................................') ?> )</strong>
                    <br>
                    NIP. <?= esc($pejabat['nip_pegawai'] ?? '....................................') ?>
                </td>
            </tr>
        </tbody>

    </table>



</body>

</html>
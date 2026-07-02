<?php
$namaUnit = strtoupper($opd['nama_opd'] ?? 'Kabupaten Pringsewu');
$pejabat  = $pejabat ?? null;
$tglCetak = function_exists('formatTanggal') ? formatTanggal(date('Y-m-d')) : date('d-m-Y');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <?= $this->include('templates/pdf_style') ?>
</head>

<body>
    <?= $this->include('templates/pdf_kop', [
        'judul'    => 'Monitoring dan Evaluasi Kinerja',
        'subjudul' => 'Tahun ' . esc($tahun ?? '-'),
        'namaUnit' => $namaUnit,
    ]) ?>

    <table class="pdf-table">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Sasaran</th>
                <th rowspan="2">Indikator</th>
                <th rowspan="2">Thn</th>
                <th rowspan="2">Satuan</th>
                <th rowspan="2">Rencana Aksi</th>
                <th rowspan="2">Baseline</th>
                <th colspan="4">Target Triwulan</th>
                <th colspan="4">Realisasi Triwulan</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">Penanggung Jawab</th>
            </tr>
            <tr>
                <th class="c">I</th><th class="c">II</th><th class="c">III</th><th class="c">IV</th>
                <th class="c">I</th><th class="c">II</th><th class="c">III</th><th class="c">IV</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($monevList as $row): ?>
                <tr>
                    <td class="c"><?= $no++ ?></td>
                    <td><?= esc($row['sasaran_renstra']) ?></td>
                    <td><?= esc($row['indikator_sasaran']) ?></td>
                    <td class="c"><?= esc($row['indikator_tahun']) ?></td>
                    <td class="c"><?= esc($row['satuan']) ?></td>
                    <td><?= nl2br(esc($row['rencana_aksi'] ?? '-')) ?></td>
                    <td class="c"><?= esc($row['target_capaian']) ?></td>
                    <td class="c"><?= esc($row['target_triwulan_1']) ?></td>
                    <td class="c"><?= esc($row['target_triwulan_2']) ?></td>
                    <td class="c"><?= esc($row['target_triwulan_3']) ?></td>
                    <td class="c"><?= esc($row['target_triwulan_4']) ?></td>
                    <td class="c"><?= esc($row['capaian_triwulan_1']) ?></td>
                    <td class="c"><?= esc($row['capaian_triwulan_2']) ?></td>
                    <td class="c"><?= esc($row['capaian_triwulan_3']) ?></td>
                    <td class="c"><?= esc($row['capaian_triwulan_4']) ?></td>
                    <td class="c"><?= esc($row['monev_total']) ?></td>
                    <td><?= esc($row['penanggung_jawab']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($monevList)): ?>
                <tr><td colspan="17" class="c pdf-muted">Tidak ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="pdf-ttd">
        <tr>
            <td style="width:58%;">&nbsp;</td>
            <td>
                Pringsewu, <?= esc($tglCetak) ?><br>
                <?= esc($pejabat['nama_jabatan'] ?? ('Kepala ' . ($opd['nama_opd'] ?? '-'))) ?>
                <div class="sp">&nbsp;</div>
                <span class="nm"><?= esc($pejabat['nama_pegawai'] ?? '(........................................)') ?></span><br>
                <span class="nip">NIP. <?= esc($pejabat['nip_pegawai'] ?? '....................................') ?></span>
            </td>
        </tr>
    </table>
</body>

</html>

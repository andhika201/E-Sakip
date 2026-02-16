<style>

table{
    border-collapse:collapse;
    width:100%;
    font-size:10pt;
}

th,td{
    border:1px solid #000;
    padding:4px;
}

thead{
    text-align:center;
    font-weight:bold;
}

tr{
    page-break-inside:avoid;
}

</style>

<h4 align="center">
CASCADING RPJMD
</h4>

<table>

<thead>
<tr>
<th rowspan="2">Tujuan</th>
<th rowspan="2">Sasaran</th>
<th rowspan="2">Indikator</th>
<th rowspan="2">Satuan</th>
<th rowspan="2">Baseline</th>
<th colspan="<?=count($years)?>">Target</th>
<th rowspan="2">Program</th>
<th rowspan="2">OPD</th>
</tr>

<tr>
<?php foreach($years as $y):?>
<th><?=$y?></th>
<?php endforeach;?>
</tr>
</thead>

<tbody>

<?php foreach($rows as $index=>$r):?>

<tr>

<?php if(($firstShow['tujuan'][$r['tujuan_id']]??-1)==$index):?>
<td rowspan="<?=$rowspan['tujuan'][$r['tujuan_id']]??1?>">
<?=$r['tujuan_rpjmd']?>
</td>
<?php endif;?>

<?php if(($firstShow['sasaran'][$r['sasaran_id']]??-1)==$index):?>
<td rowspan="<?=$rowspan['sasaran'][$r['sasaran_id']]??1?>">
<?=$r['sasaran_rpjmd']?>
</td>
<?php endif;?>

<?php if(($firstShow['indikator'][$r['indikator_id']]??-1)==$index):?>

<td rowspan="<?=$rowspan['indikator'][$r['indikator_id']]??1?>">
<?=$r['indikator_sasaran']?>
</td>

<td rowspan="<?=$rowspan['indikator'][$r['indikator_id']]??1?>">
<?=$r['satuan']?>
</td>

<td rowspan="<?=$rowspan['indikator'][$r['indikator_id']]??1?>">
<?=$r['baseline']?>
</td>

<?php foreach($years as $y):?>
<td rowspan="<?=$rowspan['indikator'][$r['indikator_id']]??1?>">
<?=$r['targets'][$y]??'-'?>
</td>
<?php endforeach;?>

<?php endif;?>

<td><?=$r['program_kegiatan']?></td>
<td><?=$r['nama_opd']?></td>

</tr>

<?php endforeach;?>

</tbody>
</table>

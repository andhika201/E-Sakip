<?php
/**
 * Blok tanda tangan Lampiran PK (diulang di tiap lampiran, seperti draf
 * Bapperida). Susunan & aturan NIP mengikuti adminOpd/pk/cetak-L.php:
 * PIHAK KEDUA (Bupati) kiri tanpa NIP untuk PK jpt/camat, PIHAK KESATU kanan.
 * Tanpa PK: tidak ada tanda tangan (lampiran berstatus draf).
 *
 * @var array|null $lampPk
 * @var bool       $lampAdaTtd
 */
if (empty($lampAdaTtd) || empty($lampPk)) {
    return;
}
$jenisTtd = strtolower((string) ($lampPk['jenis'] ?? ''));
?>
<table class="lp-ttd">
  <tr>
    <td style="width: 50%;">PIHAK KEDUA,</td>
    <td style="width: 50%;">PIHAK KESATU,</td>
  </tr>
  <tr>
    <td><?= esc((string) ($lampPk['jabatan_pihak_2'] ?? '')) ?></td>
    <td><?= esc((string) ($lampPk['jabatan_pihak_1'] ?? '')) ?></td>
  </tr>
  <tr>
    <td class="lp-ttd-ruang">&nbsp;</td>
    <td class="lp-ttd-ruang">&nbsp;</td>
  </tr>
  <tr>
    <td class="lp-ttd-nama"><?= esc((string) ($lampPk['nama_pihak_2'] ?? '')) ?></td>
    <td class="lp-ttd-nama"><?= esc((string) ($lampPk['nama_pihak_1'] ?? '')) ?></td>
  </tr>
  <tr>
    <td><?= ! in_array($jenisTtd, ['jpt', 'camat'], true) && ! empty($lampPk['nip_pihak_2']) ? 'NIP. ' . esc($lampPk['nip_pihak_2']) : '' ?></td>
    <td><?= ! empty($lampPk['nip_pihak_1']) ? 'NIP. ' . esc($lampPk['nip_pihak_1']) : '' ?></td>
  </tr>
</table>

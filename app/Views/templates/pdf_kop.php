<?php
/**
 * Kop surat (letterhead) standar untuk dokumen cetak PDF.
 * Pakai branding dari Pengaturan Aplikasi (app_settings) via helper setting().
 *
 * Parameter (kirim lewat data array saat include):
 *   $judul     (string)  judul dokumen (wajib)
 *   $subjudul  (string)  baris kecil di bawah judul (opsional)
 *   $namaUnit  (string)  nama OPD/unit di kop (opsional; mis. "Dinas Kominfo")
 *
 * Contoh: <?= $this->include('templates/pdf_kop', ['judul' => 'MONEV ...', 'namaUnit' => $opd['nama_opd'] ?? '']) ?>
 */
helper('setting');

$judul    = $judul    ?? '';
$subjudul = $subjudul ?? '';
$namaUnit = $namaUnit ?? '';
// Kop "logo saja": sembunyikan teks instansi/unit/alamat, sisakan 2 logo (dipakai cascading cetak).
$logoOnly = $logoOnly ?? false;
// Sembunyikan logo AKSARA (kanan) -> kop resmi hanya lambang Kabupaten (mis. cascading cetak; AKSARA dipakai sbg watermark).
$hideAksara = $hideAksara ?? false;

// Kop dokumen resmi memakai LAMBANG KABUPATEN (bukan logo aplikasi/AKSARA).
// Prioritas: 'kab_logo' yang di-upload super admin di Pengaturan
//   -> lambang bawaan assets/images/logo.png -> app_logo (fallback terakhir).
$kabLogo = trim(setting('kab_logo', ''));
$logoAbs = $kabLogo !== '' ? FCPATH . ltrim($kabLogo, '/') : '';
if ($logoAbs === '' || !is_file($logoAbs)) {
    $logoAbs = FCPATH . 'assets/images/logo.png';
}
if (!is_file($logoAbs)) {
    $logoAbs = FCPATH . ltrim(setting('app_logo', 'assets/images/LogoTentang.png'), '/');
}

// Logo AKSARA TIDAK lagi ditaruh di kop -> dipakai sebagai watermark halaman
// (lihat helper pdf_watermark_aksara()). Kop resmi cukup lambang Kabupaten + teks instansi.

$instansi = setting('instansi', 'Pemerintah Kabupaten Pringsewu');
$alamat   = trim(setting('instansi_address', ''));
$telp     = trim(setting('instansi_phone', ''));
$email    = trim(setting('instansi_email', ''));

$kontak = [];
if ($alamat !== '') { $kontak[] = $alamat; }
$te = trim($telp . (($telp && $email) ? ' · ' : '') . $email);
if ($te !== '') { $kontak[] = $te; }
?>
<table class="pdf-kop">
    <tr>
        <td style="width: 86px; text-align: center; vertical-align: middle;">
            <?php if (is_file($logoAbs)): ?>
                <img src="<?= $logoAbs ?>" alt="Lambang Kabupaten" height="72" style="height:72px; width:auto;">
            <?php endif; ?>
        </td>
        <td style="text-align: center; vertical-align: middle;">
            <?php if (!$logoOnly): ?>
                <div class="inst-name"><?= esc($instansi) ?></div>
                <?php if ($namaUnit !== ''): ?>
                    <div class="inst-unit"><?= esc($namaUnit) ?></div>
                <?php endif; ?>
                <?php if (!empty($kontak)): ?>
                    <div class="inst-addr"><?= esc(implode(' · ', $kontak)) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td style="width: 86px;"></td><!-- penyeimbang: teks instansi benar-benar di tengah -->
    </tr>
</table>
<hr class="pdf-kop-rule">
<hr class="pdf-kop-rule thin">

<?php if ($judul !== ''): ?>
    <div class="pdf-title"><?= esc($judul) ?></div>
<?php endif; ?>
<?php if ($subjudul !== ''): ?>
    <div class="pdf-subtitle"><?= esc($subjudul) ?></div>
<?php endif; ?>

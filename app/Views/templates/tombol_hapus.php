<?php

/**
 * Tombol HAPUS berbentuk form POST — bukan tautan.
 *
 * =====================================================================
 * MENGAPA BUKAN <a href>
 *
 * Menghapus lewat GET berarti sekadar MEMBUKA sebuah alamat sudah cukup
 * untuk memusnahkan data. Akibatnya:
 *
 *   * CSRF tidak menjaganya. CodeIgniter hanya memeriksa token pada
 *     POST/PUT/PATCH/DELETE, jadi tautan hapus tetap bisa dipicu dari situs
 *     lain — cukup <img src="https://esakip.../master/user/delete/7"> di
 *     halaman mana pun yang kebetulan dibuka admin yang sedang login.
 *   * Perambah, pemindai tautan, dan prefetch boleh membukanya sendiri tanpa
 *     seorang pun menekan apa pun.
 *
 * `onclick="return confirm(...)"` tidak menolong: ia hanya berjalan bila
 * yang menekan memang manusia di halaman itu.
 *
 * =====================================================================
 * KONFIRMASI
 *
 * Pertanyaannya dilempar ke dialog bersama (templates/konfirmasi.php),
 * bukan `confirm()` bawaan peramban: dialog itu bisa menyebut nama data
 * yang dihapus, merinci apa saja yang ikut terbawa, dan tidak bisa
 * dibungkam pengguna lewat "jangan tampilkan lagi".
 *
 * =====================================================================
 * PEMAKAIAN
 *
 *   <?= view('templates/tombol_hapus', [
 *          'url'     => base_url('adminkab/master/user/delete/' . (int) $u['id']),
 *          'pesan'   => 'Pengguna ini akan dihapus permanen.',
 *          'judul'   => 'Hapus Pengguna',
 *          'nama'    => $u['nama'],
 *          'rincian' => ['3 Perjanjian Kinerja', '12 indikator'],
 *       ]) ?>
 *
 * Parameter:
 *   url      wajib — alamat tujuan (rute harus menerima POST)
 *   pesan    kalimat konfirmasi; kosongkan dengan '' untuk tanpa konfirmasi
 *   judul    judul dialog SEKALIGUS atribut title tombol
 *   nama     nama data yang dihapus, ditonjolkan di dalam dialog
 *   rincian  array dampak ikutan yang dirinci sebagai daftar
 *   jenis    hapus | peringatan | tanya (bawaan: hapus)
 *   ketik    kata yang harus diketik ulang sebelum tombol setuju hidup
 *   ya       label tombol setuju di dialog
 *   kelas    kelas tombol (bawaan: btn btn-danger btn-sm)
 *   ikon     kelas ikon (bawaan: fas fa-trash)
 *   label    teks di samping ikon (bawaan: kosong)
 *
 * @var string            $url
 * @var string|null       $pesan
 * @var string|null       $judul
 * @var string|null       $nama
 * @var array<string>|null $rincian
 * @var string|null       $jenis
 * @var string|null       $ketik
 * @var string|null       $ya
 * @var string|null       $kelas
 * @var string|null       $ikon
 * @var string|null       $label
 */

$pesan   = $pesan   ?? 'Data ini akan dihapus permanen dari sistem.';
$judul   = $judul   ?? 'Hapus';
$nama    = $nama    ?? '';
$rincian = $rincian ?? [];
$jenis   = $jenis   ?? 'hapus';
$ketik   = $ketik   ?? '';
$ya      = $ya      ?? '';
$kelas   = $kelas   ?? 'btn btn-danger btn-sm';
$ikon    = $ikon    ?? 'fas fa-trash';
$label   = $label   ?? '';

$atribut = '';
if ($pesan !== '') {
    $atribut = ' data-konfirmasi="' . esc($pesan, 'attr') . '"'
        . ' data-konfirmasi-judul="' . esc($judul, 'attr') . '"'
        . ' data-konfirmasi-jenis="' . esc($jenis, 'attr') . '"';

    if ($nama !== '') {
        $atribut .= ' data-konfirmasi-nama="' . esc($nama, 'attr') . '"';
    }
    if ($rincian !== []) {
        $atribut .= ' data-konfirmasi-rincian="' . esc(implode('|', $rincian), 'attr') . '"';
    }
    if ($ketik !== '') {
        $atribut .= ' data-konfirmasi-ketik="' . esc($ketik, 'attr') . '"';
    }
    if ($ya !== '') {
        $atribut .= ' data-konfirmasi-ya="' . esc($ya, 'attr') . '"';
    }
} else {
    // Tanpa konfirmasi memang disengaja di sini — beri tahu jaring pengaman
    // di templates/konfirmasi.php agar tidak ikut menyisipkan pertanyaan.
    $atribut = ' data-konfirmasi-lewati';
}

?>
<form action="<?= $url ?>" method="post" class="d-inline"<?= $atribut ?>>
    <?= csrf_field() ?>
    <button type="submit" class="<?= esc($kelas) ?>" title="<?= esc($judul) ?>">
        <i class="<?= esc($ikon) ?>"></i><?= $label !== '' ? ' ' . esc($label) : '' ?>
    </button>
</form>

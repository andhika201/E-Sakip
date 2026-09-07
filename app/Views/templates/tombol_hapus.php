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
 * PEMAKAIAN
 *
 *   <?= view('templates/tombol_hapus', [
 *          'url'   => base_url('adminkab/master/user/delete/' . (int) $u['id']),
 *          'pesan' => 'Hapus pengguna ini?',
 *          'judul' => 'Hapus Pengguna',
 *       ]) ?>
 *
 * Parameter:
 *   url    wajib — alamat tujuan (rute harus menerima POST)
 *   pesan  kalimat konfirmasi; kosongkan dengan '' untuk tanpa konfirmasi
 *   judul  atribut title tombol
 *   kelas  kelas tombol (bawaan: btn btn-danger btn-sm)
 *   ikon   kelas ikon (bawaan: fas fa-trash)
 *   label  teks di samping ikon (bawaan: kosong)
 *
 * @var string      $url
 * @var string|null $pesan
 * @var string|null $judul
 * @var string|null $kelas
 * @var string|null $ikon
 * @var string|null $label
 */

$pesan = $pesan ?? 'Yakin ingin menghapus data ini?';
$judul = $judul ?? 'Hapus';
$kelas = $kelas ?? 'btn btn-danger btn-sm';
$ikon  = $ikon  ?? 'fas fa-trash';
$label = $label ?? '';

?>
<form action="<?= $url ?>" method="post" class="d-inline"
    <?= $pesan === '' ? '' : 'onsubmit="return confirm(\'' . esc($pesan, 'js') . '\');"' ?>>
    <?= csrf_field() ?>
    <button type="submit" class="<?= esc($kelas) ?>" title="<?= esc($judul) ?>">
        <i class="<?= esc($ikon) ?>"></i><?= $label !== '' ? ' ' . esc($label) : '' ?>
    </button>
</form>

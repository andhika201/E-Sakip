<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran ATURAN BISNIS — pesannya memang untuk dibaca pengguna.
 *
 * =====================================================================
 * MENGAPA PERLU PENANDA SENDIRI
 *
 * Sebagian besar `throw` di proyek ini membawa kalimat Indonesia yang memang
 * ditulis untuk layar: "Tahun 2028 sudah dipakai revisi lain...", "Hanya draft
 * yang bisa ditambahi hasil sync." Kalimat seperti itu justru penolong, dan
 * menggantinya dengan pesan generik akan membuat aplikasi lebih sulit dipakai,
 * bukan lebih aman.
 *
 * Yang TIDAK boleh sampai ke layar adalah galat teknis: nama tabel, nama
 * indeks, potongan SQL, jalur berkas. Masalahnya, keduanya bisa tampak sama
 * dari luar — lihat catatan di `galat_helper.php` tentang DatabaseException
 * yang ternyata turunan RuntimeException.
 *
 * Kelas ini memberi penanda yang tidak bisa keliru: apa pun yang dilempar
 * sebagai AturanBisnis dinyatakan aman ditampilkan oleh penulisnya sendiri.
 *
 * Kode lama yang melempar RuntimeException polos tetap diperlakukan aman
 * (lihat daftar di helper) supaya tidak perlu diubah serentak — kelas ini
 * untuk kode baru, dan untuk saat maksudnya perlu dinyatakan tegas.
 */
class AturanBisnis extends RuntimeException
{
}

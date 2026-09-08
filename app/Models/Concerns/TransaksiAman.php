<?php

namespace App\Models\Concerns;

use RuntimeException;
use Throwable;

/**
 * Pembungkus transaksi yang benar-benar bisa di-rollback.
 *
 * Dipakai modul revisi IKU, snapshot LAKIP, dan penyesuaian kebijakan —
 * ketiganya menulis ke banyak tabel sekaligus dan invariant 7 mensyaratkan
 * "gagal satu langkah = rollback seluruh operasi, tidak boleh ada revisi atau
 * snapshot setengah jadi".
 *
 * ---------------------------------------------------------------------
 * DUA JEBAKAN CODEIGNITER 4 YANG DITANGANI DI SINI
 *
 * 1. TRANSAKSI BERSARANG ITU PALSU.
 *    BaseConnection::transBegin() baris 833-837: bila transDepth sudah > 0 ia
 *    HANYA menaikkan penghitung dan langsung return true — tidak ada SAVEPOINT.
 *    Pasangannya, transRollback() pada kedalaman > 1, juga hanya menurunkan
 *    penghitung tanpa ROLLBACK sungguhan.
 *
 *    Akibatnya: memanggil operasi bertransaksi dari DALAM transaksi lain
 *    menghasilkan data separuh tertulis TANPA exception apa pun. Karena itu
 *    method ini MENOLAK dijalankan bila sudah ada transaksi berjalan, alih-alih
 *    berpura-pura aman.
 *
 * 2. $transStatus ITU LENGKET.
 *    transBegin() hanya me-reset $transFailure, bukan $transStatus (lihat baris
 *    846 vs 322). Satu query gagal di awal request — misalnya pemeriksaan tabel
 *    opsional yang wajar gagal — membuat SEMUA transaksi berikutnya dalam
 *    request yang sama dilaporkan gagal, padahal query-nya sendiri sukses.
 *
 *    Karena itu status di-reset tepat setelah transaksi terluar dibuka.
 * ---------------------------------------------------------------------
 */
trait TransaksiAman
{
    /**
     * Jalankan $kerja di dalam satu transaksi sungguhan.
     *
     * @param callable():mixed $kerja
     * @param string           $namaOperasi dipakai pada pesan galat
     *
     * @return mixed nilai kembalian $kerja
     *
     * @throws RuntimeException bila sudah ada transaksi berjalan atau transaksi gagal
     * @throws Throwable        apa pun yang dilempar $kerja, setelah rollback
     */
    protected function dalamTransaksi(callable $kerja, string $namaOperasi = 'operasi')
    {
        $db = $this->db;

        if ($db->transDepth > 0) {
            // Sengaja menolak, bukan ikut bersarang. Lihat jebakan 1 di atas:
            // bersarang di CI4 tidak memberi jaminan rollback apa pun, dan
            // diam-diam menulis separuh data jauh lebih berbahaya daripada
            // menolak terang-terangan.
            throw new RuntimeException(
                'Tidak bisa menjalankan ' . $namaOperasi . ' di dalam transaksi lain: '
                . 'CodeIgniter tidak mendukung transaksi bersarang yang benar-benar bisa dibatalkan.'
            );
        }

        $db->transBegin();
        $db->resetTransStatus(); // lihat jebakan 2

        try {
            $hasil = $kerja();

            if ($db->transStatus() === false) {
                // =====================================================
                // GALAT ASLINYA DICATAT, BUKAN DIBUANG
                //
                // Di dalam transaksi, query yang gagal hanya menurunkan
                // transStatus — pesan aslinya tidak ikut naik ke sini. Sebelum
                // ini pesan itu HILANG SAMA SEKALI: pengguna menerima kalimat
                // generik, dan yang memperbaiki tidak punya satu pun petunjuk
                // tentang query mana yang gagal atau mengapa.
                //
                // `$db->error()` masih menyimpannya pada titik ini. Dicatat
                // bersama kode rujukan yang sama dengan yang dilihat pengguna,
                // sehingga keluhan "muncul ERR-A1B2C3" bisa langsung
                // ditemukan di log tanpa menebak.
                // =====================================================
                $kode = $this->catatGagalTransaksi($db, $namaOperasi);

                throw new RuntimeException(
                    'Transaksi ' . $namaOperasi . ' gagal pada salah satu query.'
                    . ($kode === '' ? '' : ' Kode referensi: ' . $kode)
                );
            }

            $db->transCommit();

            return $hasil;
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }
    }

    /**
     * Catat galat basis data yang menggagalkan transaksi, kembalikan kodenya.
     *
     * Memakai helper galat terpusat bila tersedia supaya bentuk barisnya sama
     * dengan galat lain — dan tetap bekerja tanpa helper itu, karena model
     * dipakai juga dari perintah CLI yang tidak memuat helper controller.
     */
    private function catatGagalTransaksi($db, string $namaOperasi): string
    {
        $galat = $db->error();
        $pesan = trim((string) ($galat['message'] ?? ''));
        $nomor = (string) ($galat['code'] ?? '');

        // =============================================================
        // QUERY TERAKHIR IKUT DICATAT — NILAINYA DISAMARKAN
        //
        // `$db->error()` hanya menyimpan galat query TERAKHIR. Bila sesudah
        // query yang gagal masih ada query lain yang berhasil, isinya sudah
        // tergantikan dan yang tersisa hanya transStatus=false tanpa satu pun
        // keterangan. Query terakhir tetap menunjukkan tabel dan pernyataan
        // yang terlibat, dan itu jauh lebih berguna daripada tidak ada apa-apa.
        //
        // Seluruh literal string diganti '?' lebih dulu. Bentuk query — tabel
        // dan kolomnya — itulah yang menolong menemukan sebab; NILAInya tidak,
        // dan justru nilai itulah yang bisa membawa data pribadi atau hash
        // kata sandi ke dalam berkas log yang dibaca banyak orang (§36).
        // =============================================================
        $query = (string) $db->getLastQuery();

        if ($query !== '') {
            // Pola sengaja SEDERHANA: setiap petik tunggal berpasangan
            // dianggap satu literal. Kutip yang di-escape di dalam nilai akan
            // membuatnya memotong lebih awal — dan itu justru menyamarkan
            // LEBIH banyak, bukan lebih sedikit. Untuk penyamaran, arah gagal
            // seperti itulah yang benar.
            $query = (string) preg_replace("/'[^']*'/", "'?'", $query);
            $query = mb_substr((string) preg_replace('/\s+/', ' ', $query), 0, 400);
        }

        if ($pesan === '' && $nomor === '' && $query === '') {
            return '';
        }

        $kode = function_exists('kodeGalat')
            ? kodeGalat()
            : 'ERR-' . strtoupper(bin2hex(random_bytes(3)));

        log_message('critical', sprintf(
            '[GALAT] kode=%s | operasi=transaksi:%s | basis=%s | kode_db=%s | pesan=%s | query=%s',
            $kode,
            $namaOperasi,
            $db->getDatabase(),
            $nomor === '' ? '-' : $nomor,
            $pesan === '' ? '-' : $pesan,
            $query === '' ? '-' : $query
        ));

        return $kode;
    }
}

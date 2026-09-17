<?php

namespace App\Libraries;

/**
 * mPDF untuk seluruh cetak PDF e-SAKIP.
 *
 * Satu-satunya perbedaan dari \Mpdf\Mpdf: perbaikan paginasi tabel di bawah.
 * Semua controller cetak WAJIB memakai kelas ini (bukan \Mpdf\Mpdf langsung)
 * supaya perbaikannya berlaku ke semua dokumen.
 */
class PdfMpdf extends \Mpdf\Mpdf
{
    /**
     * @param array $config konfigurasi mPDF biasa
     */
    public function __construct(array $config = [], $container = null)
    {
        // Dokumen besar (MONEV/Rencana Aksi seluruh OPD: >50 ribu sel, puncak
        // ~640 MB) kehabisan memori 512M. Batas dinaikkan sebagai jaring
        // pengaman; view-nya sendiri memecah tabel per OPD supaya mPDF melepas
        // memori tiap tabel. JANGAN memakai packTableData sebagai gantinya:
        // di mPDF 8.2.7 opsi itu memicu "array offset on int" pada tabel dengan
        // rowspan/colspan (thead) dan CI mengubah warning-nya menjadi 500.
        @set_time_limit(300);
        $batas = self::iniKeBytes((string) ini_get('memory_limit'));
        if ($batas >= 0 && $batas < 1024 * 1024 * 1024) {
            @ini_set('memory_limit', '1024M');
        }

        parent::__construct($config, $container);
    }

    /** Ubah nilai singkat php.ini ("512M", "1G", "-1") menjadi byte; -1 = tanpa batas. */
    private static function iniKeBytes(string $nilai): int
    {
        $nilai = trim($nilai);
        if ($nilai === '' || $nilai === '-1') {
            return -1;
        }
        $angka  = (int) $nilai;
        $satuan = strtoupper(substr($nilai, -1));

        return match ($satuan) {
            'G'     => $angka * 1024 * 1024 * 1024,
            'M'     => $angka * 1024 * 1024,
            'K'     => $angka * 1024,
            default => $angka,
        };
    }

    /**
     * Tinggi blok baris yang harus muat di sisa halaman.
     *
     * mPDF memutuskan pindah halaman HANYA pada baris yang punya sel nyata di
     * kolom pertama. Baris-baris yang kolom pertamanya masih dilintasi rowspan
     * tidak pernah diperiksa, sehingga fungsi ini harus menjumlahkan tinggi
     * seluruh baris yang dilintasi rowspan tersebut (satu "blok").
     *
     * mPDF 8.2.7 punya salah ketik: baris ke-(jumlah KOLOM - 1) dianggap baris
     * terakhir dan dikembalikan tingginya sendiri saja. Bila sebuah blok rowspan
     * kebetulan dimulai di indeks itu, mPDF mengira bloknya pendek, mencetaknya
     * di sisa halaman, dan seluruh blok meluber keluar halaman — terpotong.
     * Terjadi mis. pada cetak Target & Rencana Aksi 16 kolom yang blok
     * keduanya mulai di baris ke-15.
     *
     * Di sini pembandingnya diganti jumlah BARIS (nr), sesuai maksud aslinya.
     *
     * @param array $table
     * @param int   $row
     *
     * @return float
     */
    function _tableGetMaxRowHeight($table, $row)
    {
        if ($row == $table['nr'] - 1) {
            return $table['hr'][$row];
        }

        $maxrowheight = $table['hr'][$row];
        for ($i = $row + 1; $i < $table['nr']; $i++) {
            $cellsset = 0;
            for ($j = 0; $j < $table['nc']; $j++) {
                if (!empty($table['cells'][$i][$j])) {
                    $cellsset += $table['cells'][$i][$j]['colspan'] ?? 1;
                }
            }
            if ($cellsset == $table['nc']) {
                return $maxrowheight;
            }
            $maxrowheight += $table['hr'][$i];
        }

        return $maxrowheight;
    }
}

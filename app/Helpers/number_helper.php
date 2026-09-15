<?php

if (!function_exists('toFloatComma')) {
    /**
     * Angka bertulis gaya Indonesia/campuran -> float.
     *
     * =====================================================================
     * MENGAPA TIDAK LAGI "TITIK SELALU RIBUAN"
     *
     * Data target & realisasi LAKIP tidak seragam: target IKU/Renstra sering
     * tersimpan dengan titik sebagai DESIMAL ("0.648", "7.78", "67.01"),
     * sementara realisasi diketik operator dengan koma ("0,650", "7,60").
     * Aturan lama membuang setiap titik sebagai pemisah ribuan, sehingga
     * "0.648" menjadi 648 dan capaian Indeks Pendidikan tampil 0,10% —
     * di layar LAKIP, cetakannya, Excel-nya, dan dashboard.
     *
     * Aturan sekarang:
     *   - titik & koma keduanya ada -> pemisah TERAKHIR adalah desimal
     *   - hanya titik, satu buah    -> desimal, KECUALI tepat 3 digit di
     *                                  belakangnya dan bagian depan bukan "0"
     *                                  ("5.066" ton = 5066, "0.648" = 0,648)
     *   - hanya titik, >1 buah      -> ribuan ("1.234.567")
     *   - hanya koma                -> desimal (perilaku lama)
     *
     * Setiap nilai yang dulu terbaca benar tetap terbaca sama; yang berubah
     * hanya nilai yang dulu memang salah terbaca.
     * =====================================================================
     */
    function toFloatComma($val): ?float
    {
        if ($val === null || $val === '') return null;

        $val = trim((string) $val);

        $adaTitik = strpos($val, '.') !== false;
        $adaKoma  = strpos($val, ',') !== false;

        if ($adaTitik && $adaKoma) {
            // Pemisah terakhir = desimal; yang lain = ribuan.
            if (strrpos($val, ',') > strrpos($val, '.')) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else {
                $val = str_replace(',', '', $val);
            }
        } elseif ($adaTitik) {
            if (substr_count($val, '.') > 1) {
                $val = str_replace('.', '', $val);
            } else {
                [$depan, $belakang] = explode('.', $val, 2);
                $depanBersih = ltrim($depan, '+-');
                if (strlen($belakang) === 3 && ctype_digit($belakang) && $depanBersih !== '' && $depanBersih !== '0') {
                    $val = $depan . $belakang; // ribuan
                }
            }
        } elseif ($adaKoma) {
            $val = str_replace(',', '.', $val);
        }

        return is_numeric($val) ? (float) $val : null;
    }
}

if (!function_exists('formatAngkaID')) {
    function formatAngkaID($val, int $digit = 2): string
    {
        if ($val === null || !is_numeric($val)) return '-';
        return number_format((float) $val, $digit, ',', '.');
    }
}

if (!function_exists('formatAtauRaw')) {
    function formatAtauRaw($val, int $digit = 2): string
    {
        if ($val === null || (is_string($val) && trim($val) === '')) return '-';
        $num = toFloatComma($val);
        if ($num !== null) {
            return number_format($num, $digit, ',', '.');
        }
        return (string) $val;
    }
}

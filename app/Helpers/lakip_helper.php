<?php

if (!function_exists('toFloatComma')) {
    function toFloatComma($value)
    {
        if ($value === null || $value === '')
            return null;

        $value = trim((string) $value);
        $value = str_replace('.', '', $value);   // hapus ribuan
        $value = str_replace(',', '.', $value);  // koma → titik

        return is_numeric($value) ? (float) $value : null;
    }
}

if (!function_exists('fmtComma')) {
    /**
     * Format angka ke tampilan koma Indonesia
     * Contoh: 2.2 -> 2,2
     */
    function fmtComma($value, $dec = 1)
    {
        $num = toFloatComma($value);
        if ($num === null)
            return '-';

        return number_format($num, $dec, ',', '.');
    }
}

if (!function_exists('hitungCapaianLakip')) {
    function hitungCapaianLakip($target, $realisasi, $jenisIndikator)
    {
        $target = toFloatComma($target);
        $realisasi = toFloatComma($realisasi);

        if ($target === null || $target == 0 || $realisasi === null) {
            return null;
        }

        $jenis = strtolower(trim((string) $jenisIndikator));

        if ($jenis === 'indikator positif' || $jenis === 'positif') {
            return ($realisasi / $target) * 100;
        }

        if ($jenis === 'indikator negatif' || $jenis === 'negatif') {
            return (($target - ($realisasi - $target)) / $target) * 100;
        }

        return null;
    }
}

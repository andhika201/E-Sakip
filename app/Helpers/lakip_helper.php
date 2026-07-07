<?php

// Catatan: toFloatComma() disediakan oleh number_helper (di-autoload di Config\Autoload),
// jadi fungsi tersebut selalu tersedia saat helper ini dipakai.

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

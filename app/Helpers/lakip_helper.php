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

        // Indikator negatif (semakin rendah semakin baik): target / realisasi.
        // Rumus yang SAMA dengan metode "Trend Turun" pada MONEV
        // (calculateCapaianTotalPercentage) — sebelumnya LAKIP memakai
        // (2×target − realisasi)/target, sehingga Indeks Risiko Bencana target
        // 88,82 realisasi 135,15 tampil 47,84% di LAKIP tetapi 65,72% di MONEV.
        // Realisasi 0 = target tercapai sempurna -> 100%, seperti MONEV.
        if ($jenis === 'indikator negatif' || $jenis === 'negatif') {
            return $realisasi <= 0 ? 100.0 : ($target / $realisasi) * 100;
        }

        return null;
    }
}

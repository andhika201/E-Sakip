<?php

if (!function_exists('toFloatComma')) {
    function toFloatComma($val): ?float
    {
        if ($val === null || $val === '') return null;

        // hapus spasi
        $val = trim((string) $val);

        // hapus pemisah ribuan (.)
        $val = str_replace('.', '', $val);

        // koma → titik
        $val = str_replace(',', '.', $val);

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

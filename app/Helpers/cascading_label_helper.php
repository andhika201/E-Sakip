<?php

/**
 * Label eselon Cascading — penyesuaian untuk role admin_kecamatan.
 *
 * Di Kecamatan tidak ada Eselon II: Camat = Eselon III, Kepala Seksi = Eselon IV,
 * lalu Pelaksana / JF. Maka SELURUH label eselon "digeser" turun satu tingkat,
 * hanya untuk role admin_kecamatan. Data (es2/es3/es4) TIDAK berubah — kosmetik saja.
 *
 *   ESS II       -> ESS III
 *   ESS III      -> ESS IV
 *   ESS IV / JF  -> Pelaksana / JF
 */

if (!function_exists('is_kecamatan_role')) {
    function is_kecamatan_role(): bool
    {
        return session()->get('role') === 'admin_kecamatan';
    }
}

if (!function_exists('casc_relabel')) {
    /**
     * Geser istilah eselon dalam sebuah string bila user = admin_kecamatan.
     * Untuk role lain, string dikembalikan apa adanya.
     *
     * Contoh: "Sasaran ESS II" -> "Sasaran ESS III",
     *         "Sasaran ESS IV/JF" -> "Sasaran Pelaksana / JF".
     */
    function casc_relabel(string $s): string
    {
        if (!is_kecamatan_role()) {
            return $s;
        }
        // Urutan WAJIB dari tingkat terbawah ke atas agar tidak tergeser ganda.
        // Backreference $1 mempertahankan kata aslinya ("ESS" atau "Eselon").
        $s = preg_replace('/(ESS|Eselon)\s*IV\s*\/?\s*JF/iu', 'Pelaksana / JF', $s); // "ESS IV/JF"
        $s = preg_replace('/(ESS|Eselon)\s*IV/iu', 'Pelaksana / JF', $s);
        $s = preg_replace('/(ESS|Eselon)\s*III/iu', '$1 IV', $s);
        $s = preg_replace('/(ESS|Eselon)\s*II/iu', '$1 III', $s);
        return $s;
    }
}

<?php

function formatTanggal($tanggal)
{
    if (empty($tanggal) || !strtotime($tanggal)) {
        return 'Tanggal tidak valid';
    }

    $bulanIndo = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $day = date('d', $timestamp);
    $month = $bulanIndo[(int)date('m', $timestamp) - 1];
    $year = date('Y', $timestamp);

    return "$day $month $year";

}


if (! function_exists('formatRupiah')) {
    function formatRupiah($nilai)
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }
        return 'Rp ' . number_format((float)$nilai, 0, ',', '.');
    }
}

if (! function_exists('parseRupiah')) {
    /**
     * Kebalikan formatRupiah(): teks rupiah -> angka murni.
     *
     * Menerima "Rp 150.000.000", "150000000", maupun "150.000.000,50".
     * Kosong -> null (belum diisi, dibedakan dari 0).
     * Bukan angka atau negatif -> false.
     *
     * Dipakai saat menyimpan nominal supaya yang masuk database selalu angka
     * murni — tanpa "Rp" dan tanpa titik pemisah ribuan.
     *
     * @return float|null|false
     */
    function parseRupiah($nilai)
    {
        if ($nilai === null) {
            return null;
        }

        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }

        // Buang simbol & pemisah ribuan; koma dijadikan titik desimal.
        $teks = str_ireplace(['rp', ' ', "\u{00A0}", '.'], '', $teks);
        $teks = str_replace(',', '.', $teks);

        if (!is_numeric($teks)) {
            return false;
        }

        $angka = (float) $teks;

        return $angka < 0 ? false : $angka;
    }
}
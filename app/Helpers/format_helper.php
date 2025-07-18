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
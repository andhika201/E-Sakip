<?php

/**
 * Cangkang halaman untuk view yang DIPAKAI BERSAMA beberapa role.
 *
 * Modul lama menaruh satu salinan view per role (adminKabupaten/... dan
 * adminOpd/...) yang isinya hampir identik, hanya berbeda direktori template.
 * Untuk halaman baru yang perilakunya sama persis di kedua area — Revisi IKU
 * dan perbandingan Snapshot LAKIP — satu berkas sudah cukup: yang benar-benar
 * berbeda cuma direktori template, dan itu bisa dipilih dari role.
 *
 * Pasangannya: templates/shell_bawah.php
 *
 * Variabel opsional:
 *   $title      judul halaman
 *   $judulHalam judul besar di dalam kartu
 *   $shellCss   CSS tambahan (string, sudah berupa isi <style>)
 */
$peranSekarang = session()->get('role');

$tpl = in_array($peranSekarang, ['admin_opd', 'admin_kecamatan'], true)
    ? 'adminOpd'
    : 'adminKabupaten';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'e-SAKIP') ?></title>
    <?= $this->include($tpl . '/templates/style.php'); ?>
    <style>
        .revisi-tabel th,
        .revisi-tabel td { vertical-align: middle; }
        .revisi-tabel thead th { white-space: nowrap; }
        .badge-lifecycle { font-weight: 600; letter-spacing: .2px; }
        .kotak-jejak { background: #f8f9fa; border-left: 3px solid #adb5bd; padding: .5rem .75rem; }
        .kotak-jejak.beku { border-left-color: #0d6efd; background: #f1f6ff; }
        .kotak-jejak.kunci { border-left-color: #198754; background: #f2f9f5; }
        .kotak-jejak.awas { border-left-color: #dc3545; background: #fdf3f4; }
        .sel-kecil { font-size: .82rem; }
        .garis-putus { text-decoration: line-through; opacity: .6; }
        <?= $shellCss ?? '' ?>
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <?php
    /*
     * `id="main-content"` + `.content-wrapper` WAJIB ada.
     *
     * Sidebar menggesernya lewat margin-left saat dibuka. Tanpa pembungkus ini
     * sidebar menimpa isi halaman — termasuk tombol burger di pojok kiri atas,
     * sehingga menu tidak bisa ditutup lagi. Seluruh halaman lama memakai pola
     * yang sama; cangkang ini sempat tertinggal.
     */
    ?>
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
        <?= $this->include($tpl . '/templates/header.php'); ?>
        <?= $this->include($tpl . '/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow-sm p-4">
                <?php if (! empty($judulHalaman)): ?>
                    <h2 class="h4 fw-bold text-success text-center mb-4"><?= esc($judulHalaman) ?></h2>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php /* Berhasil TAPI ada akibat yang perlu diketahui — mis.
                         menggeser Kondisi Awal membuat awal periode tidak
                         berpayung. Tanpa laras ini, pesan seperti itu terpaksa
                         menumpang 'error' (padahal tidak gagal) atau hilang. */ ?>
                <?php if (session()->getFlashdata('warning')): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <?= esc(session()->getFlashdata('warning')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

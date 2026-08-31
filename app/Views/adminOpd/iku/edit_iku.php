<?php

/**
 * Sunting KETERANGAN indikator IKU.
 *
 * =====================================================================
 * MENGAPA HANYA EMPAT MEDAN
 *
 * Sasaran, indikator, satuan, dan target datang dari Renstra lewat sync, dan
 * perubahannya berjalan lewat REVISI supaya tercatat dan disahkan. Membiarkan
 * keempatnya bisa disunting di sini berarti menyediakan jalan pintas yang
 * melewati seluruh alur itu — tanpa jejak, tanpa persetujuan.
 *
 * Yang tersisa di halaman ini adalah keterangan yang memang harus diisi
 * sendiri OPD dan tidak pernah ada di Renstra: definisi operasional, formula,
 * sumber data, dan penanggung jawab.
 *
 * Pembatasannya BUKAN hanya di layar. `IkuController::update()` memanggil
 * `perbaruiKeterangan()` yang menyentuh empat kolom itu saja, sehingga POST
 * yang dikarang pun tidak punya jalan mengubah target atau satuan.
 *
 * Isi layarnya ada di templates/iku/_edit_keterangan — SATU berkas dengan
 * layar IKU Kabupaten, supaya aturannya tidak bercabang jadi dua salinan.
 */
$title = $title ?? 'Sunting Keterangan IKU';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Keterangan IKU') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <?php $this->setData([
                'action_url'  => base_url('adminopd/iku/update'),
                'back_url'    => base_url('adminopd/iku'),
                'labelSumber'   => 'Renstra',
                'sasaranSumber' => $sasaranRenstra ?? null,
            ]); ?>
            <?= $this->include('templates/iku/_edit_keterangan') ?>
        </main>

        <?php /* =====================================================================
                 FOOTER WAJIB IKUT — DAN PERNAH TIDAK.

                 Berkas ini sempat berakhir tepat di baris include di atas: tanpa
                 </main>, tanpa footer, tanpa penutup <body>/<html>. Akibatnya
                 bukan sekadar HTML cacat — `adminOpd/templates/footer.php` yang
                 memuat jQuery, select2, dan DataTables tidak pernah dijalankan,
                 sehingga seluruh skrip yang diandalkan halaman ini diam.

                 Kembarannya di adminKabupaten/iku/edit_iku.php selalu lengkap;
                 yang ini tertinggal. Terjaring `php spark versi:render`, yang
                 memang menolak render tanpa </html>.
             ===================================================================== */ ?>
        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>
</body>

</html>
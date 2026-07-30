<?php
/**
 * Partial FORM edit Pelaksana (dipakai edit_pelaksana.php halaman penuh &
 * modal AJAX). Butuh: $sasaran (pelaksana), $es4, $indikator_es4,
 * $indikator (indikator pelaksana).
 */
?>
<form action="<?= base_url('adminopd/cascading/update-pelaksana/' . $sasaran['id']) ?>" method="post" class="casc-form">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label><?= casc_relabel('Sasaran ESS IV / JF') ?></label>
        <input type="text" class="form-control bg-light" value="<?= esc($es4['nama_sasaran'] ?? '') ?>" readonly>
    </div>

    <div class="mb-3">
        <label><?= casc_relabel('Indikator ESS IV') ?></label>
        <input type="text" class="form-control bg-light" value="<?= esc($indikator_es4['indikator'] ?? '') ?>" readonly>
    </div>

    <hr>

    <label><?= esc(casc_pelaksana_label('Sasaran ')) ?></label>
    <input type="text" name="nama" class="form-control mb-3"
        value="<?= esc($sasaran['nama_sasaran']) ?>" required>

    <div class="indikator-container" id="indikator-container">
        <?php foreach ($indikator as $idx => $i): ?>
            <div class="indikator-pel">
                <?php // id lama dipertahankan agar konsisten dengan jenjang di atasnya ?>
                <input type="hidden" name="indikator[<?= $idx ?>][id]" value="<?= esc($i['id']) ?>">
                <input type="text" name="indikator[<?= $idx ?>][nama]" class="form-control"
                    value="<?= esc($i['indikator']) ?>"
                    placeholder="<?= esc(casc_pelaksana_label('Masukkan indikator '), 'attr') ?>">
                <button type="button" class="btn btn-delete btn-delete-indikator"
                    onclick="hapusIndikatorPelaksana(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addIndikatorEditPelaksana()">
            + <?= esc(casc_pelaksana_label('Tambah Indikator ')) ?>
        </button>
    </div>

    <hr class="my-4">

    <?php
    /**
     * Tambah SASARAN PELAKSANA LAIN di bawah indikator Eselon IV yang sama —
     * pola kembar dengan _form_es4.php. Ini pengganti tombol "+" yang dulu ada
     * di kolom Aksi Pelaksana pada tabel Cascading (tombol itu tetap tampil
     * walau data sudah ada dan berulang di tiap Sasaran Pelaksana, sehingga
     * tidak konsisten dengan Aksi ESS III/ESS IV yang hanya edit + hapus).
     *
     * data-label dipakai JS karena istilahnya berbeda untuk role
     * admin_kecamatan ("Staf Pelaksana", lihat casc_pelaksana_label()).
     */
    ?>
    <div id="sasaran-baru-container-pel" data-label="<?= esc(casc_pelaksana_label(), 'attr') ?>"></div>

    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addSasaranBaruPelaksana()">
        + <?= esc(casc_pelaksana_label('Tambah Sasaran ')) ?> lain
    </button>
    <div class="form-text">
        Ditambahkan di bawah <?= esc(casc_relabel('Indikator ESS IV')) ?> yang sama seperti sasaran di atas.
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="<?= base_url('adminopd/cascading') ?>" class="btn btn-secondary casc-cancel">Batal</a>
    </div>
</form>

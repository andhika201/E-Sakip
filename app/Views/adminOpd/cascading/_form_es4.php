<?php
/**
 * Partial FORM edit Es4 (dipakai edit_es4.php halaman penuh & modal AJAX).
 * Butuh: $sasaran (es4), $es3, $indikator_es3, $indikator (indikator es4).
 */
?>
<form action="<?= base_url('adminopd/cascading/update-es4/' . $sasaran['id']) ?>" method="post" class="casc-form">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label>Sasaran ESS III</label>
        <input type="text" class="form-control" value="<?= esc($es3['nama_sasaran'] ?? '') ?>" readonly>
    </div>

    <div class="mb-3">
        <label>Indikator ESS III</label>
        <input type="text" class="form-control" value="<?= esc($indikator_es3['indikator'] ?? '') ?>" readonly>
    </div>

    <hr>

    <label>Sasaran ESS IV</label>
    <input type="text" name="nama" class="form-control mb-3" value="<?= esc($sasaran['nama_sasaran']) ?>" required>

    <div class="indikator-container" id="indikator-container">
        <?php foreach ($indikator as $i): ?>
            <div class="indikator-es4">
                <input type="text" name="indikator[][nama]" class="form-control"
                    value="<?= esc($i['indikator']) ?>">
                <button type="button" class="btn btn-delete btn-delete-indikator"
                    onclick="this.parentElement.remove()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addIndikatorEditEs4()">
            + Tambah Indikator ESS IV
        </button>
    </div>

    <hr class="my-4">

    <div id="sasaran-baru-container"></div>
    
    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addSasaranBaruEs4()">
        + Tambah Sasaran ESS IV
    </button>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="<?= base_url('adminopd/cascading') ?>" class="btn btn-secondary casc-cancel">Batal</a>
    </div>
</form>

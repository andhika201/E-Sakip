<?php
/**
 * Partial FORM edit Es3 (dipakai edit_es3.php halaman penuh & modal AJAX).
 * Butuh: $sasaran, $indikator (tiap item boleh punya 'es4_count').
 */
?>
<form action="<?= base_url('adminopd/cascading/update-es3/' . $sasaran['id']) ?>" method="post" class="casc-form">
    <?= csrf_field() ?>

    <label>Sasaran ESS III</label>
    <input type="text" name="nama" class="form-control mb-3" value="<?= esc($sasaran['nama_sasaran']) ?>" required>

    <div class="indikator-container" id="indikator-container">
        <?php foreach ($indikator as $idx => $i): ?>
            <div class="indikator-es3">
                <!-- id lama dipertahankan agar Es4 anak tetap tertaut -->
                <input type="hidden" name="indikator[<?= $idx ?>][id]" value="<?= esc($i['id']) ?>">
                <input type="text" name="indikator[<?= $idx ?>][nama]" class="form-control"
                    value="<?= esc($i['indikator']) ?>" placeholder="Masukkan indikator ESS III">
                <button type="button" class="btn btn-delete btn-delete-indikator"
                    data-es4-count="<?= (int) ($i['es4_count'] ?? 0) ?>"
                    onclick="hapusIndikatorEs3(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addIndikatorEs3Edit()">
            + Tambah Indikator ESS III
        </button>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="<?= base_url('adminopd/cascading') ?>" class="btn btn-secondary casc-cancel">Batal</a>
    </div>
</form>

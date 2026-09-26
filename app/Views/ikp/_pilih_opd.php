<?php
/**
 * Super admin membuka area OPD tanpa OPD di sesi: pilih OPD dulu.
 * Daftar OPD berasal dari controller (sudah tanpa OpdModel::EXCLUDED_OPD_IDS);
 * pilihan tetap divalidasi ulang di server.
 *
 * @var array  $scope
 * @var string $tujuan path tujuan (mis. adminopd/ikp/rekap)
 * @var int    $tahun
 */
$this->setVar('shellCss', (string) @file_get_contents(FCPATH . 'assets/css/ikp.css'));
?>
<?= $this->include('templates/shell_atas') ?>

<div class="ikp-kepala">
    <div class="ic"><i class="fas fa-bullseye"></i></div>
    <div class="isi">
        <h2>Kinerja Prioritas (IKP)</h2>
        <p>Anda masuk sebagai Super Admin. Pilih perangkat daerah yang akan dikelola IKP-nya.</p>
    </div>
</div>

<form method="get" action="<?= base_url($tujuan) ?>" class="row g-2 align-items-end" style="max-width: 720px;">
    <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    <div class="col-md-9">
        <label class="form-label" for="pilih-opd">Perangkat daerah</label>
        <select name="opd_id" id="pilih-opd" class="form-select" required>
            <option value="">— pilih perangkat daerah —</option>
            <?php foreach ($scope['opd_list'] as $o): ?>
                <option value="<?= (int) $o['id'] ?>"><?= esc($o['nama_opd']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-success w-100"><i class="fas fa-arrow-right me-1"></i>Buka</button>
    </div>
</form>

<?= $this->include('templates/shell_bawah') ?>

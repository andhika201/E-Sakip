<?php
$isBupati = ($jenis === 'bupati');
$isEdit   = ($mode === 'edit');
$eselonLabel = function ($pkJenis) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    return $map[$pkJenis] ?? '-';
};
$ctxEselon = $eselonLabel($ctx['pk_jenis'] ?? '');
$judul    = ($isEdit ? 'Edit' : 'Tambah') . ' Rencana Aksi';
$renaksiPath = ($jenis === 'bupati') ? 'adminkab/target_renaksi'
             : (($base === 'adminopd') ? 'adminopd/target_renaksi' : ($base . '/renaksi_pk/' . $jenis));
$baseUrl  = base_url($renaksiPath);
$action   = $isEdit
    ? $baseUrl . '/update/' . (int) ($detail['id'] ?? 0)
    : $baseUrl . '/save';

// Nilai prefill (edit pakai $detail, tambah pakai old())
$val = function (string $k) use ($isEdit, $detail) {
    if ($isEdit) {
        return old($k, $detail[$k] ?? '');
    }
    return old($k);
};
$tahun  = $ctx['tahun'] ?? ($ctx['indikator_tahun'] ?? '-');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($judul) ?> - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
    <?= $this->include($isBupati ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php'); ?>
    <?= $this->include($isBupati ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;"><?= esc($judul) ?></h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= $action ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <?php if (!$isEdit): ?>
                    <input type="hidden" name="pk_indikator_id" value="<?= (int) ($ctx['pk_indikator_id'] ?? 0) ?>">
                <?php endif; ?>

                <?php if (!$isBupati): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Eselon</label>
                            <input type="text" class="form-control" value="<?= esc($ctxEselon) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Pejabat (PK)</label>
                            <input type="text" class="form-control" value="<?= esc($ctx['pejabat_nama'] ?? '-') ?>" readonly>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Sasaran PK</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['sasaran_renstra'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label class="form-label">Indikator PK</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['indikator_sasaran'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['satuan'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="form-label">Tahun PK</label>
                        <input type="text" class="form-control" value="<?= esc($tahun) ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="form-label">Target</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['indikator_target'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="capaian">Baseline (Capaian Awal)</label>
                        <input type="text" class="form-control" id="capaian" name="capaian" value="<?= esc($val('capaian')) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Aksi</label>
                    <div id="renaksi-list"></div>
                    <button type="button" id="add-renaksi" class="btn btn-outline-success btn-sm mt-1">
                        <i class="fas fa-plus me-1"></i> Tambah Rencana Aksi
                    </button>
                    <small class="text-muted d-block mt-1">Tambahkan satu atau beberapa rencana aksi; akan tampil sebagai daftar 1, 2, 3 … di tabel.</small>
                    <textarea name="rencana_aksi" id="rencana_aksi_joined" class="d-none" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Triwulan</label>
                    <div class="row g-2">
                        <div class="col"><input type="text" name="target_triwulan_1" class="form-control" placeholder="Triwulan I" value="<?= esc($val('target_triwulan_1')) ?>"></div>
                        <div class="col"><input type="text" name="target_triwulan_2" class="form-control" placeholder="Triwulan II" value="<?= esc($val('target_triwulan_2')) ?>"></div>
                        <div class="col"><input type="text" name="target_triwulan_3" class="form-control" placeholder="Triwulan III" value="<?= esc($val('target_triwulan_3')) ?>"></div>
                        <div class="col"><input type="text" name="target_triwulan_4" class="form-control" placeholder="Triwulan IV" value="<?= esc($val('target_triwulan_4')) ?>"></div>
                    </div>
                    <small class="text-muted">Gunakan koma untuk desimal (mis. 1,5).</small>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="penanggung_jawab">
                        Penanggung Jawab <?= $isBupati ? '(Perangkat Daerah)' : '' ?>
                    </label>
                    <?php if ($isBupati): ?>
                        <?php $pjVal = (string) $val('penanggung_jawab'); ?>
                        <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" required>
                            <option value="">&mdash; Pilih Perangkat Daerah &mdash;</option>
                            <?php foreach (($opdList ?? []) as $opd): ?>
                                <option value="<?= esc($opd['nama_opd']) ?>" <?= ($pjVal === $opd['nama_opd']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php // fallback: data lama (teks jabatan) yang belum cocok OPD, tetap ditampilkan agar tak hilang ?>
                            <?php if ($pjVal !== '' && !in_array($pjVal, array_column($opdList ?? [], 'nama_opd'), true)): ?>
                                <option value="<?= esc($pjVal) ?>" selected><?= esc($pjVal) ?> (data lama)</option>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Pilih <strong>Perangkat Daerah</strong> penanggung jawab rencana aksi ini.</small>
                    <?php else: ?>
                        <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab"
                            value="<?= esc($val('penanggung_jawab')) ?>" placeholder="Isi nama jabatan (mis. Kepala Bidang ...)">
                        <small class="text-muted">Diisi dengan <strong>nama jabatan</strong> penanggung jawab.</small>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        (function () {
            var initial = <?= json_encode((string) $val('rencana_aksi')) ?>;
            var list = document.getElementById('renaksi-list');
            var joined = document.getElementById('rencana_aksi_joined');
            if (!list || !joined) return;

            function esc(s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

            function rowHtml(val) {
                return '<div class="input-group mb-2 renaksi-item">'
                    + '<input type="text" class="form-control renaksi-input" placeholder="Tulis rencana aksi" value="' + esc(val) + '">'
                    + '<button type="button" class="btn btn-outline-danger remove-renaksi" title="Hapus"><i class="fas fa-trash"></i></button>'
                    + '</div>';
            }

            function sync() {
                var vals = Array.prototype.slice.call(list.querySelectorAll('.renaksi-input'))
                    .map(function (i) { return i.value.trim(); })
                    .filter(function (v) { return v !== ''; });
                joined.value = vals.join('\n');
            }

            function addRow(val) { list.insertAdjacentHTML('beforeend', rowHtml(val)); sync(); }

            // Inisialisasi baris dari nilai tersimpan (edit) atau 1 baris kosong (tambah)
            var lines = String(initial || '').split(/\r\n|\r|\n/).map(function (s) { return s.trim(); }).filter(function (s) { return s !== ''; });
            if (lines.length === 0) lines = [''];
            lines.forEach(addRow);

            document.getElementById('add-renaksi').addEventListener('click', function () { addRow(''); });

            list.addEventListener('click', function (e) {
                if (e.target.closest('.remove-renaksi')) {
                    var items = list.querySelectorAll('.renaksi-item');
                    if (items.length > 1) {
                        e.target.closest('.renaksi-item').remove();
                    } else {
                        var inp = e.target.closest('.renaksi-item').querySelector('.renaksi-input');
                        if (inp) inp.value = '';
                    }
                    sync();
                }
            });
            list.addEventListener('input', function (e) {
                if (e.target.classList.contains('renaksi-input')) sync();
            });
            var form = list.closest('form');
            if (form) form.addEventListener('submit', sync);
        })();
    </script>
</body>

</html>

<?php

/**
 * Input REALISASI ANGGARAN per triwulan (MONEV).
 *
 * Pagu anggarannya read-only karena ikut Perjanjian Kinerja
 * (pk_program -> program_pk); yang diinput di sini hanya realisasinya.
 *
 * @var array      $detail    baris target_rencana + konteks PK
 * @var array|null $anggaran  baris monev_anggaran yang sudah ada
 * @var array      $programPk daftar program + pagu dari PK
 */
$isBupati  = ($jenis === 'bupati');
$judul     = 'Input Realisasi Anggaran';
$monevPath = ($jenis === 'bupati') ? 'adminkab/monev'
           : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl   = base_url($monevPath);

$programPk = $programPk ?? [];
$anggaran  = $anggaran ?? null;

// format_helper tidak ikut autoload — pakai pembungkus bercadangan.
$rupiah = function ($nilai) {
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};

$totalPagu = 0.0;
foreach ($programPk as $p) {
    $totalPagu += (float) $p['anggaran'];
}

/** Nilai prefill: old() dulu (kalau validasi gagal), lalu data tersimpan. */
$val = function (int $q) use ($anggaran) {
    $simpan = $anggaran['realisasi_triwulan_' . $q] ?? '';
    // DECIMAL dari DB keluar sebagai "1500000" — tampilkan apa adanya agar mudah disunting.
    return old('realisasi_triwulan_' . $q, $simpan === null ? '' : $simpan);
};
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

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Indikator PK</label>
                        <input type="text" class="form-control bg-light" value="<?= esc($detail['indikator_sasaran'] ?? '-') ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Program &amp; Pagu Anggaran <span class="text-muted fw-normal">(dari Perjanjian Kinerja)</span></label>
                    <?php if (empty($programPk)): ?>
                        <div class="alert alert-light border mb-0 py-2 px-3 text-muted small">
                            Indikator PK ini belum ditautkan ke program mana pun.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-1">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:90px;">Kode</th>
                                        <th>Program</th>
                                        <th class="text-end" style="width:180px;">Pagu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($programPk as $prog): ?>
                                        <tr>
                                            <td><?= esc($prog['kode'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($prog['program']) ?></td>
                                            <td class="text-end text-nowrap"><?= esc($rupiah($prog['anggaran'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-semibold">
                                        <td colspan="2" class="text-end">Total Pagu</td>
                                        <td class="text-end text-nowrap"><?= esc($rupiah($totalPagu)) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <form action="<?= $baseUrl . '/anggaran/save' ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_rencana_id" value="<?= (int) ($detail['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Realisasi Anggaran per Triwulan (Rp)</label>
                        <div class="row g-2">
                            <?php foreach ([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $q => $label): ?>
                                <div class="col">
                                    <label class="form-label small text-muted mb-1">Triwulan <?= $label ?></label>
                                    <input type="text" name="realisasi_triwulan_<?= $q ?>" class="form-control realisasi-input"
                                           inputmode="numeric" placeholder="0" value="<?= esc($val($q)) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">
                            Boleh diketik dengan pemisah ribuan (mis. <code>1.500.000</code>) — akan dinormalkan otomatis.
                            Dikosongkan berarti belum diisi.
                        </small>
                    </div>

                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Total Realisasi</span>
                            <span class="fw-bold" id="total-realisasi">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mt-1">
                            <span>Sisa terhadap pagu</span>
                            <span id="sisa-pagu">&mdash;</span>
                        </div>
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
            var pagu = <?= json_encode((float) $totalPagu) ?>;
            var inputs = document.querySelectorAll('.realisasi-input');
            var totalEl = document.getElementById('total-realisasi');
            var sisaEl = document.getElementById('sisa-pagu');

            function keAngka(teks) {
                teks = String(teks || '').replace(/[Rr]p|\s|\./g, '').replace(',', '.');
                var n = parseFloat(teks);
                return isNaN(n) ? 0 : n;
            }

            function format(n) {
                return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function hitung() {
                var total = 0;
                inputs.forEach(function (i) { total += keAngka(i.value); });
                totalEl.textContent = format(total);

                if (pagu > 0) {
                    var sisa = pagu - total;
                    sisaEl.textContent = format(sisa) + ' (' + (total / pagu * 100).toFixed(1) + '% terserap)';
                    sisaEl.className = sisa < 0 ? 'text-danger fw-semibold' : '';
                } else {
                    sisaEl.textContent = '—';
                }
            }

            inputs.forEach(function (i) { i.addEventListener('input', hitung); });
            hitung();
        })();
    </script>
</body>

</html>

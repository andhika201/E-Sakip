<?php
helper('capaian');

$isBupati = ($jenis === 'bupati');
$judul    = 'Input Capaian';
$monevPath = ($jenis === 'bupati') ? 'adminkab/monev'
           : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl  = base_url($monevPath);
$mv = function (string $k) use ($monev) {
    return old($k, $monev[$k] ?? '');
};

// Capaian diinput per SUB rencana aksi. Kalau ?sub= diberikan, target
// triwulan acuannya diambil dari sub itu — bukan dari tingkat indikator.
// $targets sudah disiapkan controller dari sumber yang benar (DB, bukan form).
$sub     = $sub ?? null;
$targets = $targets ?? [1 => null, 2 => null, 3 => null, 4 => null];
$preview = $preview ?? ['percentage' => null, 'calculation_description' => '', 'error' => null];

// Satuan bertipe predikat (mis. Opini BPK) -> capaian dipilih dari skala,
// bukan diketik. Skornya yang dipakai menghitung Capaian Total.
$skala      = $skala ?? [];
$isPredikat = $skala !== [];
$petaSkala  = capaianSkalaMap($skala);
$labelSkala = static function ($nilai) use ($skala) {
    foreach ($skala as $s) {
        if (strcasecmp(trim((string) $s['kode']), trim((string) $nilai)) === 0) {
            return $s['kode'] . (($s['label'] ?? '') !== '' ? ' — ' . $s['label'] : '');
        }
    }
    return null;
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
                    <input type="text" class="form-control" value="<?= esc($detail['indikator_sasaran'] ?? '-') ?>" readonly>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Rencana Aksi</label>
                    <textarea class="form-control" rows="2" readonly><?= esc($detail['rencana_aksi'] ?? '-') ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Satuan</label>
                    <input type="text" class="form-control" value="<?= esc($detail['satuan'] ?? '-') ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Target</label>
                    <input type="text" class="form-control" value="<?= esc($detail['indikator_target'] ?? '-') ?>" readonly>
                </div>
            </div>

            <?php if ($sub !== null): ?>
                <div class="mb-3">
                    <label class="form-label">Sub Rencana Aksi</label>
                    <input type="text" class="form-control bg-light" value="<?= esc($sub['sub_rencana_aksi']) ?>" readonly>
                    <small class="text-muted">Capaian di bawah ini khusus untuk sub rencana aksi tersebut.</small>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label text-muted">
                    Target Triwulan (referensi)
                    <?= $sub !== null ? '&mdash; dari sub rencana aksi' : '&mdash; tingkat rencana aksi' ?>
                </label>
                <div class="row g-2">
                    <?php foreach ([1, 2, 3, 4] as $q): ?>
                        <?php
                        $tw   = $targets[$q] ?? null;
                        $twLb = $isPredikat && capaianTerisi($tw) ? $labelSkala($tw) : null;
                        ?>
                        <div class="col">
                            <input type="text" class="form-control bg-light"
                                value="<?= esc(capaianTerisi($tw) ? ($twLb ?? $tw) : '-') ?>"
                                title="<?= esc(capaianTerisi($tw) ? ($twLb ?? $tw) : '-', 'attr') ?>" readonly>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small class="text-muted">Target ini yang jadi pembagi saat menghitung Capaian Total.</small>
            </div>

            <?php if ($isPredikat): ?>
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="fas fa-list-ol me-1"></i>
                    Satuan <strong><?= esc($detail['satuan'] ?? '-') ?></strong> memakai <strong>skala predikat</strong>.
                    Capaian dipilih dari daftar, lalu skornya dipakai menghitung persentase:
                    <?php foreach ($skala as $s): ?>
                        <span class="badge bg-white text-dark border ms-1"><?= esc($s['kode']) ?> = <?= esc(rtrim(rtrim(number_format((float) $s['nilai'], 2, '.', ''), '0'), '.')) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?= $baseUrl . '/save' ?>" method="post" novalidate id="form-capaian">
                <?= csrf_field() ?>
                <input type="hidden" name="target_rencana_id" value="<?= (int) ($detail['id'] ?? 0) ?>">
                <input type="hidden" name="target_sub_rencana_id" value="<?= (int) ($sub['id'] ?? 0) ?>">

                <div class="mb-3" style="max-width:420px;">
                    <label class="form-label fw-bold" for="metode_perhitungan">Metode Perhitungan</label>
                    <select name="metode_perhitungan" id="metode_perhitungan" class="form-select" required>
                        <option value="">Pilih Metode Perhitungan</option>
                        <?php foreach ($metodeList as $kode => $label): ?>
                            <option value="<?= esc($kode) ?>" <?= ((string) $mv('metode_perhitungan') === (string) $kode) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Menentukan cara Capaian Total dihitung. Wajib dipilih sebelum menyimpan.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Realisasi / Capaian Triwulan</label>
                    <div class="row g-2">
                        <?php foreach ([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $q => $rom): ?>
                            <?php $nilaiQ = (string) $mv('capaian_triwulan_' . $q); ?>
                            <div class="col">
                                <?php if ($isPredikat): ?>
                                    <select name="capaian_triwulan_<?= $q ?>" id="capaian_triwulan_<?= $q ?>"
                                        class="form-select js-capaian" title="Capaian Triwulan <?= $rom ?>">
                                        <option value="">Capaian <?= $rom ?></option>
                                        <?php foreach ($skala as $s): ?>
                                            <option value="<?= esc($s['kode']) ?>" <?= (strcasecmp($nilaiQ, (string) $s['kode']) === 0) ? 'selected' : '' ?>>
                                                <?= esc($s['kode']) ?><?= ($s['label'] ?? '') !== '' ? ' — ' . esc($s['label']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php // Nilai lama di luar skala tetap ditawarkan supaya tidak terhapus diam-diam. ?>
                                        <?php if ($nilaiQ !== '' && !isset($petaSkala[strtolower(trim($nilaiQ))])): ?>
                                            <option value="<?= esc($nilaiQ) ?>" selected><?= esc($nilaiQ) ?> (di luar skala)</option>
                                        <?php endif; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="number" step="any" inputmode="decimal"
                                        name="capaian_triwulan_<?= $q ?>" id="capaian_triwulan_<?= $q ?>"
                                        class="form-control js-capaian" placeholder="Capaian <?= $rom ?>"
                                        value="<?= esc($nilaiQ) ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">
                        <?= $isPredikat
                            ? 'Pilih predikat sesuai skala satuan. Kosongkan bila triwulannya belum berjalan.'
                            : 'Isi angka saja (boleh desimal). Nilai 0 dianggap sudah diisi; kosongkan bila triwulannya belum berjalan.' ?>
                    </small>
                </div>

                <div class="mb-3" style="max-width:280px;">
                    <label class="form-label fw-bold" for="capaian_total_tampil">Capaian Total</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-calculator"></i></span>
                        <input type="text" id="capaian_total_tampil"
                            class="form-control bg-light fw-bold text-success"
                            value="<?= esc(capaianFormatPersen($preview['percentage'] ?? null)) ?>"
                            readonly tabindex="-1"
                            aria-describedby="capaian_total_info">
                    </div>
                    <small id="capaian_total_info" class="d-block mt-1 <?= !empty($preview['error']) ? 'text-danger' : 'text-muted' ?>">
                        <?= esc($preview['calculation_description'] ?? '') ?>
                    </small>
                    <small class="text-muted d-block">Capaian Total dihitung otomatis dalam bentuk persentase.</small>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan Capaian</button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        /**
         * Pratinjau realtime Capaian Total.
         *
         * CERMINAN dari calculateCapaianTotalPercentage() di
         * app/Helpers/capaian_helper.php — kalau rumus di sana berubah, ubah di
         * sini juga. Nilai yang benar-benar disimpan tetap hasil hitungan server;
         * blok ini murni untuk pratinjau, jadi tidak ada input tersembunyi yang
         * ikut terkirim.
         */
        (function () {
            // Target triwulan acuan, langsung dari DB (bukan dari input yang bisa diubah).
            var TARGETS = <?= json_encode([
                1 => $targets[1] ?? null,
                2 => $targets[2] ?? null,
                3 => $targets[3] ?? null,
                4 => $targets[4] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            // Skala predikat: { "wtp": 4, "wdp": 3, ... }. Kosong = satuan angka biasa.
            var SKALA = <?= json_encode($petaSkala, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>;
            var PREDIKAT = Object.keys(SKALA).length > 0;

            var ROMAWI = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV' };
            var NAMA_METODE = {
                sum: 'Akumulasi / Jumlah',
                trend_naik: 'Trend Naik',
                trend_turun: 'Trend Turun',
                trend_flat: 'Trend Flat'
            };

            var selMetode = document.getElementById('metode_perhitungan');
            var elTotal = document.getElementById('capaian_total_tampil');
            var elInfo = document.getElementById('capaian_total_info');
            if (!selMetode || !elTotal || !elInfo) return;

            /** 0 dan "0" dianggap SUDAH diisi; hanya null/undefined/'' yang kosong. */
            function terisi(nilai) {
                return nilai !== null && nilai !== undefined && String(nilai).trim() !== '';
            }

            /**
             * Cerminan capaianNilaiSkala() di PHP: angka apa adanya, atau skor
             * predikatnya. null bila bukan angka DAN kodenya di luar skala.
             */
            function keAngka(nilai) {
                if (!terisi(nilai)) return null;
                var teks = String(nilai).trim().replace(/[%\s ]/g, '');
                if (teks.indexOf(',') !== -1 && teks.indexOf('.') !== -1) {
                    teks = teks.replace(/\./g, ''); // titik = pemisah ribuan
                }
                teks = teks.replace(',', '.');
                if (/^-?\d+(\.\d+)?$/.test(teks)) {
                    var angka = parseFloat(teks);
                    if (isFinite(angka)) return angka;
                }
                if (PREDIKAT) {
                    var skor = SKALA[String(nilai).trim().toLowerCase()];
                    if (skor !== undefined) return Number(skor);
                }
                return null;
            }

            /** 93.75 -> "93,75%", 100 -> "100%". */
            function formatPersen(nilai) {
                var teks = nilai.toFixed(2).replace('.', ',');
                return teks.replace(/,00$/, '') + '%';
            }

            function calculateAchievementPercentage() {
                var metode = selMetode.value;
                var terisiList = [];
                var pesan = null;

                for (var q = 1; q <= 4; q++) {
                    var input = document.getElementById('capaian_triwulan_' + q);
                    var nilai = input ? input.value : null;
                    if (!terisi(nilai)) continue;

                    var angka = keAngka(nilai);
                    if (angka === null) {
                        return tampilkan(null, 'Capaian Triwulan ' + ROMAWI[q] + (PREDIKAT
                            ? ' tidak ada pada skala predikat satuan ini.'
                            : ' harus berupa angka.'), true);
                    }
                    terisiList.push({ quarter: q, target: TARGETS[q], achievement: angka });
                }

                if (terisiList.length === 0) {
                    return tampilkan(null, 'Isi minimal satu capaian triwulan untuk menghitung capaian total.', false);
                }
                if (!NAMA_METODE[metode]) {
                    return tampilkan(null, 'Metode perhitungan belum dipilih.', true);
                }

                var akhir = terisiList[terisiList.length - 1];
                var dipakai = (metode === 'sum') ? terisiList : [akhir];
                for (var i = 0; i < dipakai.length; i++) {
                    if (keAngka(dipakai[i].target) === null) {
                        return tampilkan(null, 'Target Triwulan ' + ROMAWI[dipakai[i].quarter] + (PREDIKAT
                            ? ' tidak ada pada skala predikat satuan ini, Capaian Total tidak dapat dihitung.'
                            : ' harus berupa angka agar Capaian Total dapat dihitung.'), true);
                    }
                }

                if (metode === 'sum') {
                    var totalCapaian = 0, totalTarget = 0;
                    terisiList.forEach(function (b) {
                        totalCapaian += b.achievement;
                        totalTarget += keAngka(b.target);
                    });
                    if (Math.abs(totalTarget) < 1e-9) {
                        return tampilkan(null, 'Total target triwulan bernilai 0, Capaian Total tidak dapat dihitung.', true);
                    }
                    return tampilkan(totalCapaian / totalTarget * 100,
                        'Dihitung dari akumulasi ' + terisiList.length + ' triwulan yang telah diisi.', false);
                }

                var target = keAngka(akhir.target);
                var capaian = akhir.achievement;
                pesan = 'Dihitung dari Capaian Triwulan ' + ROMAWI[akhir.quarter] +
                    ' menggunakan metode ' + NAMA_METODE[metode] + '.';

                if (metode === 'trend_turun') {
                    // Capaian 0 pada indikator "semakin rendah semakin baik" =
                    // tercapai sempurna. Dipatok 100% agar bukan Infinity.
                    // Samakan dengan capaian_helper.php bila kebijakannya berubah.
                    if (Math.abs(capaian) < 1e-9) return tampilkan(100, pesan, false);
                    return tampilkan(target / capaian * 100, pesan, false);
                }

                // trend_naik & trend_flat
                if (Math.abs(target) < 1e-9) {
                    return tampilkan(null, 'Target Triwulan ' + ROMAWI[akhir.quarter] +
                        ' bernilai 0, Capaian Total tidak dapat dihitung.', true);
                }
                return tampilkan(capaian / target * 100, pesan, false);
            }

            function tampilkan(persen, keterangan, bermasalah) {
                elTotal.value = (persen === null || !isFinite(persen)) ? '-' : formatPersen(persen);
                elInfo.textContent = keterangan;
                elInfo.classList.toggle('text-danger', !!bermasalah);
                elInfo.classList.toggle('text-muted', !bermasalah);
            }

            ['input', 'change'].forEach(function (ev) {
                selMetode.addEventListener(ev, calculateAchievementPercentage);
                document.querySelectorAll('.js-capaian').forEach(function (el) {
                    el.addEventListener(ev, calculateAchievementPercentage);
                });
            });

            // Form edit: tampilkan hasil begitu halaman dibuka.
            calculateAchievementPercentage();
        })();
    </script>
</body>

</html>

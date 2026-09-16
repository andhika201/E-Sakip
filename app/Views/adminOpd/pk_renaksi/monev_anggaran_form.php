<?php

/**
 * Input REALISASI ANGGARAN per triwulan (MONEV), SATU BARIS PER UNIT ANGGARAN.
 *
 * "Unit" = satuan anggaran yang tingkatnya mengikuti jenis PK
 * (Program / Kegiatan / Sub Kegiatan — lihat app/Helpers/pk_unit_helper.php).
 * Pagunya read-only karena ikut Perjanjian Kinerja; yang diinput hanya realisasinya.
 *
 * Bentuk POST yang dibaca controller (monevAnggaranSave):
 *   realisasi[<target_rencana_id>][<ref_key>][1..4]
 *   unit[<ref_key>][level] & unit[<ref_key>][ref_id]  (hanya dicocokkan)
 *
 * Satu unit bisa memuat beberapa baris indikator (dipakai bersama). Baris
 * saudara hanya diberi input bila ia memang mencatat realisasi pada unit ini
 * ($b['boleh_sunting']); yang mencatat di tingkat lain ($b['tingkat_lain'])
 * tampil read-only — controller menolak ref_key yang bukan unit indikator itu.
 *
 * @var array      $detail          baris target_rencana + konteks PK (memuat pk_jenis)
 * @var array      $units           daftar unit anggaran indikator ini
 * @var array      $anggaranUnit    [ref_key => baris monev_anggaran]
 * @var array|null $anggaranWarisan baris lama (ref_key ':0') yang belum dirinci per unit
 * @var string     $labelUnitHeader label kolom unit dari controller
 */
$isBupati  = ($jenis === 'bupati');
$judul     = 'Input Realisasi Anggaran';
$monevPath = ($jenis === 'bupati') ? 'adminkab/monev'
           : (($base === 'adminopd') ? 'adminopd/monev' : ($base . '/monev_pk/' . $jenis));
$baseUrl   = base_url($monevPath);

// Kunci baru; kunci lama ($programPk/$anggaran) tetap dipakai sebagai cadangan
// supaya view ini tidak pecah kalau dipanggil dari jalur yang belum diperbarui.
$units           = $units ?? ($programPk ?? []);
$anggaranUnit    = $anggaranUnit ?? [];
helper('serapan'); // penanda serapan anggaran terhadap pagu

$anggaranWarisan = $anggaranWarisan ?? ($anggaran ?? null);

// Rincian unit + indikator pemakainya, disiapkan controller.
$unitDetail  = $unitDetail ?? [];
$targetIdIni = $targetIdIni ?? 0;

// Eselon di halaman ini sudah pasti satu, jadi labelnya tunggal (tanpa badge).
$labelUnit = $labelUnitHeader
    ?? (function_exists('pk_unit_label') ? pk_unit_label($detail['pk_jenis'] ?? null) : 'Program');

// format_helper tidak ikut autoload — pakai pembungkus bercadangan.
$rupiah = function ($nilai) {
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};

$totalPagu = 0.0;
foreach ($units as $u) {
    $totalPagu += (float) ($u['anggaran'] ?? 0);
}

// old() dikembalikan mentah; peng-escape-an dilakukan sendiri saat dicetak.
$oldRealisasi = old('realisasi', [], false);
$oldRealisasi = is_array($oldRealisasi) ? $oldRealisasi : [];

/** Nilai prefill satu sel: old() dulu (kalau validasi gagal), lalu data tersimpan. */
$val = function (string $refKey, int $q) use ($anggaranUnit, $oldRealisasi) {
    if (isset($oldRealisasi[$refKey][$q])) {
        return (string) $oldRealisasi[$refKey][$q];
    }
    // DECIMAL dari DB keluar sebagai "1500000" — tampilkan apa adanya agar mudah disunting.
    $simpan = $anggaranUnit[$refKey]['realisasi_triwulan_' . $q] ?? '';
    return $simpan === null ? '' : (string) $simpan;
};

// Total realisasi warisan hanya untuk ditampilkan (tidak ikut ter-submit).
$totalWarisan = 0.0;
if ($anggaranWarisan) {
    foreach ([1, 2, 3, 4] as $q) {
        $totalWarisan += (float) ($anggaranWarisan['realisasi_triwulan_' . $q] ?? 0);
    }
}

$triwulan = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
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
            <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:1100px;">
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

                <form action="<?= $baseUrl . '/anggaran/save' ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_rencana_id" value="<?= (int) ($detail['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Realisasi Anggaran per <?= esc($labelUnit) ?> dan Triwulan (Rp)
                            <span class="text-muted fw-normal">— pagu dari Perjanjian Kinerja</span>
                        </label>

                        <?php if (empty($units)): ?>
                            <div class="alert alert-light border mb-0 py-2 px-3 text-muted small">
                                Indikator PK ini belum ditautkan ke <?= esc(strtolower($labelUnit)) ?> mana pun,
                                jadi realisasi anggaran belum bisa diinput.
                                Atur dulu lewat menu Program Perjanjian Kinerja.
                            </div>
                        <?php else: ?>

<?php /* =====================================================================
         LEBAR KOLOM DIPATOK DI SATU TEMPAT

         Sebelumnya hanya Pagu (180px) dan TW (4 x 140px) yang dipatok, sehingga
         kolom nama unit menerima sisa ruang apa adanya. Pada layar biasa sisanya
         tinggal sempit, dan blok "dipakai bersama" di dalamnya patah satu-dua
         kata per baris — barisnya jadi setinggi layar dan kolom input yang
         justru jadi tujuan utama terdorong keluar pandangan.

         Sekarang tabelnya diberi lebar minimum dan kolom namanya diberi jatah
         nyata; bila layar tak cukup, pembungkus .table-responsive yang menggeser
         mendatar — bukan kolomnya yang dihimpit.
   ===================================================================== */ ?>
<?= serapan_gaya() ?>
<style>
    #tabel-anggaran { min-width: 940px; }
    #tabel-anggaran .kolom-kode { width: 72px; }
    #tabel-anggaran .kolom-pagu { width: 190px; }
    #tabel-anggaran .kolom-tw   { width: 132px; }
    #tabel-anggaran .kolom-unit { min-width: 300px; }

    /* Nama unit boleh memenuhi lebarnya; hanya kata yang benar-benar panjang
       yang dipenggal. */
    #tabel-anggaran .nama-unit { overflow-wrap: anywhere; }

    /* Kepala unit dibedakan tipis dari baris indikator di bawahnya —
       cukup untuk terbaca sebagai kelompok, tanpa menambah kebisingan. */
    #tabel-anggaran .baris-unit > td { background: #f8f9fa; }
    #tabel-anggaran .baris-indikator > td { border-top-style: dotted; }
    #tabel-anggaran .baris-ini > td { background: rgba(13, 110, 253, .04); }

    /* Sisa pagu — satu angka yang menentukan berapa boleh diketik di sebelah
       kanan, jadi ia diberi bobot, bukan diselipkan sebagai teks kecil. */
    #tabel-anggaran .baris-sisa { font-size: .875rem; font-weight: 600; white-space: nowrap; }
    #tabel-anggaran .sisa-aman  { color: var(--bs-success, #198754); }
    #tabel-anggaran .sisa-lebih { color: var(--bs-danger, #dc3545); }

    #tabel-anggaran td { vertical-align: top; }
    #tabel-anggaran .realisasi-input { min-width: 110px; }
</style>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-1" id="tabel-anggaran">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="kolom-kode">Kode</th>
                                            <th><?= esc($labelUnit) ?> / Indikator</th>
                                            <?php foreach ($triwulan as $q => $label): ?>
                                                <th class="text-end kolom-tw">TW <?= $label ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($anggaranWarisan): ?>
                                            <?php // Baris WARISAN: realisasi lama yang belum dirinci per unit.
                                                  // Terkunci & tidak ikut ter-submit. ?>
                                            <tr class="table-light text-muted">
                                                <td colspan="2">
                                                    <em>Realisasi lama, belum dirinci per <?= esc(strtolower($labelUnit)) ?></em>
                                                    <i class="fas fa-lock ms-1" title="Angka historis tidak diubah dari sini"></i>
                                                </td>
                                                <?php foreach ($triwulan as $q => $label): ?>
                                                    <td class="text-end">
                                                        <input type="text" class="form-control form-control-sm text-end bg-light"
                                                               value="<?= esc((string) ($anggaranWarisan['realisasi_triwulan_' . $q] ?? '')) ?>"
                                                               disabled readonly>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endif; ?>

                                        <?php foreach ($unitDetail as $refKey => $u): ?>
                                            <?php $unit = $u['unit']; $pagu = (float) $u['pagu']; ?>

                                            <?php /* =========================================
                                                     KEPALA UNIT — identitas & pagu, tanpa input.
                                                     Barisnya sendiri agar nama unit yang panjang
                                                     punya lebar penuh, bukan terhimpit di samping
                                                     lima kolom angka.
                                                  ========================================= */ ?>
                                            <tr class="baris-unit">
                                                <td class="text-nowrap align-middle">
                                                    <?= esc($unit['kode'] ?? '-') ?>
                                                    <input type="hidden" name="unit[<?= esc($refKey) ?>][level]"
                                                           value="<?= esc((string) ($unit['level'] ?? '')) ?>">
                                                    <input type="hidden" name="unit[<?= esc($refKey) ?>][ref_id]"
                                                           value="<?= (int) ($unit['ref_id'] ?? 0) ?>">
                                                </td>
                                                <td class="align-middle">
                                                    <span class="fw-semibold"><?= esc($unit['nama'] ?? '-') ?></span>
                                                    <?php if (!empty($unit['fallback'])): ?>
                                                        <span class="badge bg-light text-muted border fw-normal ms-1"
                                                              title="Tingkat asli PK ini tidak punya data">
                                                            tingkat <?= esc(strtolower((string) ($unit['level_label'] ?? ''))) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td colspan="4" class="align-middle">
                                                    <div class="d-flex align-items-center justify-content-end gap-3">
                                                        <span class="text-nowrap">Pagu <span class="fw-semibold"><?= esc($rupiah($pagu)) ?></span></span>
                                                        <div class="serapan-unit" style="width:180px"
                                                             data-unit="<?= esc($refKey) ?>"
                                                             data-pagu="<?= esc((string) $pagu) ?>"></div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <?php /* Satu baris per indikator pemakai unit ini (§20, §22).
                                                     Inilah pengganti kotak "dipakai bersama" yang dulu
                                                     menjejalkan daftar indikator ke kolom sempit. */ ?>
                                            <?php foreach ($u['baris'] as $b): ?>
                                                <tr class="baris-indikator<?= $b['ini'] ? ' baris-ini' : '' ?>"
                                                    data-unit="<?= esc($refKey) ?>">
                                                    <td></td>
                                                    <td class="small">
                                                        <?= esc($b['nama']) ?>
                                                        <?php if ($b['ini']): ?>
                                                            <span class="badge bg-primary ms-1">Indikator saat ini</span>
                                                        <?php elseif ($b['target_rencana_id'] === null): ?>
                                                            <span class="text-muted ms-1">&mdash; Rencana Aksi belum ada</span>
                                                        <?php elseif (!empty($b['tingkat_lain'])): ?>
                                                            <?php /* Saudara yang mencatat realisasi di tingkat lain
                                                                     (mis. pengawas -> Sub Kegiatan). Tidak diberi input
                                                                     karena controller menolak ref_key yang bukan unitnya. */ ?>
                                                            <span class="badge bg-light text-muted border fw-normal ms-1"
                                                                  title="Realisasi indikator ini diisi dari MONEV indikator tersebut, pada unit tingkat <?= esc($b['tingkat_lain'], 'attr') ?>">
                                                                dicatat pada tingkat <?= esc($b['tingkat_lain']) ?>
                                                            </span>
                                                        <?php elseif (!$b['boleh_sunting']): ?>
                                                            <span class="badge bg-light text-muted border fw-normal ms-1">
                                                                <?= esc(strtoupper((string) $b['pk_jenis'])) ?> &mdash; hanya lihat
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php foreach ($triwulan as $q => $label): ?>
                                                        <td class="text-end">
                                                            <?php if ($b['boleh_sunting']): ?>
                                                                <input type="text"
                                                                       name="realisasi[<?= (int) $b['target_rencana_id'] ?>][<?= esc($refKey) ?>][<?= $q ?>]"
                                                                       class="form-control form-control-sm text-end realisasi-input"
                                                                       data-q="<?= $q ?>" data-unit="<?= esc($refKey) ?>"
                                                                       inputmode="numeric" placeholder="0"
                                                                       title="TW <?= $label ?> &mdash; <?= esc($b['nama'], 'attr') ?>"
                                                                       value="<?= esc((string) ($b['realisasi'][$q] ?? '')) ?>">
                                                            <?php elseif ($b['target_rencana_id'] === null): ?>
                                                                <?php /* belum ada renaksi: sel dibiarkan kosong */ ?>
                                                            <?php else: ?>
                                                                <span class="small text-muted nilai-kunci"
                                                                      data-unit="<?= esc($refKey) ?>"
                                                                      data-nilai="<?= esc((string) ($b['realisasi'][$q] ?? 0)) ?>">
                                                                    <?= ($b['realisasi'][$q] ?? null) !== null
                                                                        ? esc($rupiah($b['realisasi'][$q])) : '' ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-semibold">
                                            <td colspan="2" class="text-end">Total seluruh indikator</td>
                                            <?php foreach ($triwulan as $q => $label): ?>
                                                <td class="text-end text-nowrap" id="total-tw-<?= $q ?>">Rp 0</td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <small class="text-muted">
                                Boleh diketik dengan pemisah ribuan (mis. <code>1.500.000</code>) — akan dinormalkan otomatis.
                                Dikosongkan berarti belum diisi.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-light border">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">Total Realisasi</span>
                            <span class="fw-bold" id="total-realisasi">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mt-1">
                            <span>Sisa terhadap total pagu</span>
                            <span id="sisa-pagu">&mdash;</span>
                        </div>
                        <?php if ($anggaranWarisan): ?>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>Realisasi lama (belum dirinci, terkunci)</span>
                                <span><?= esc($rupiah($totalWarisan)) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        <?php if (!empty($units)): ?>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        (function () {
            var totalPagu = <?= json_encode((float) $totalPagu) ?>;
            var totalEl   = document.getElementById('total-realisasi');
            var sisaEl    = document.getElementById('sisa-pagu');
            var tabel     = document.getElementById('tabel-anggaran');

            if (!tabel) return;

            // =========================================================
            // ANGKA SAJA — DISARING SAAT DIKETIK
            //
            // Kolom `monev_anggaran.realisasi_triwulan_*` bertipe
            // decimal(15,0): tidak ada pecahan sen sama sekali. Jadi yang
            // diterima hanya DIGIT; huruf, koma, minus, dan tanda apa pun
            // dibuang saat itu juga, bukan ditolak belakangan setelah
            // pemakai menekan Simpan.
            //
            // Titik yang muncul adalah PEMISAH RIBUAN yang kita pasang
            // sendiri, bukan yang diketik pemakai — dan server membuangnya
            // lagi (rupiahKeAngka) sebelum menyimpan. Nilainya tidak pernah
            // berubah karena diformat.
            // =========================================================
            function hanyaDigit(teks) {
                return String(teks || '').replace(/\D+/g, '');
            }

            function keAngka(teks) {
                var d = hanyaDigit(teks);
                return d === '' ? 0 : parseInt(d, 10);
            }

            function ribuan(digit) {
                return digit.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function format(n) {
                var negatif = n < 0;
                return (negatif ? '-Rp ' : 'Rp ') + ribuan(Math.round(Math.abs(n)).toString());
            }

            /**
             * Rapikan isi sebuah input sambil MENJAGA POSISI KURSOR.
             *
             * Menulis ulang value memindahkan kursor ke ujung. Kalau itu
             * dibiarkan, menyisipkan satu digit di tengah angka akan
             * melemparkan kursor ke belakang setiap ketukan — praktis
             * membuat penyuntingan angka panjang mustahil.
             *
             * Yang dihitung adalah jumlah DIGIT sebelum kursor, bukan jumlah
             * karakter: titik ribuan bertambah/berkurang sendiri saat
             * diformat, sehingga posisi karakter tidak bisa dipakai.
             */
            function rapikan(inp) {
                var awal    = inp.selectionStart;
                var sebelum = inp.value;
                var digitKiri = hanyaDigit(sebelum.slice(0, awal === null ? sebelum.length : awal)).length;

                var digit = hanyaDigit(sebelum);

                // Nol di depan dibuang, tetapi "0" tunggal dipertahankan:
                // 0 berarti "sudah diisi dengan nol", berbeda dari kosong.
                digit = digit.replace(/^0+(?=\d)/, '');

                var baru = digit === '' ? '' : ribuan(digit);

                if (baru === sebelum) {
                    return;
                }

                inp.value = baru;

                if (awal === null || inp.selectionStart === null) {
                    return;
                }

                var lewat = 0;
                var pos   = 0;

                while (pos < baru.length && lewat < digitKiri) {
                    if (/\d/.test(baru[pos])) lewat++;
                    pos++;
                }

                try { inp.setSelectionRange(pos, pos); } catch (e) { /* input tak mendukung */ }
            }

            // Cermin serapan_bar() di PHP — barnya ikut berubah saat diketik.
            // Lihat app/Helpers/serapan_helper.php.
            function barSerapan(pagu, realisasi) {
                if (!(pagu > 0)) {
                    return realisasi > 0
                        ? '<div class="small text-danger fw-semibold">'
                            + '<i class="fas fa-triangle-exclamation me-1"></i>pagu belum diisi, realisasi '
                            + format(realisasi) + '</div>'
                        : '<div class="small text-muted">pagu belum diisi</div>';
                }

                var persen = realisasi / pagu * 100;
                var lebih  = realisasi > pagu;
                var dalam  = lebih ? (pagu / realisasi * 100) : Math.max(0, persen);
                var luar   = lebih ? (100 - dalam) : 0;

                var html = '<div class="serapan-jalur' + (lebih ? ' serapan-lebih' : '') + '">'
                    + '<div class="serapan-isi" style="width:' + dalam.toFixed(2) + '%"></div>';

                if (luar > 0) {
                    html += '<div class="serapan-luber" style="width:' + luar.toFixed(2) + '%"></div>';
                }

                html += '</div><div class="serapan-ket small '
                    + (lebih ? 'text-danger fw-semibold' : 'text-muted') + '">';
                html += lebih
                    ? '<i class="fas fa-triangle-exclamation me-1"></i>'
                        + '<span class="serapan-nilai">' + persen.toFixed(1).replace('.', ',') + '%</span>'
                        + ' &mdash; lebih <span class="serapan-nilai">' + format(realisasi - pagu) + '</span>'
                    : persen.toFixed(1).replace('.', ',') + '% terserap';

                return html + '</div>';
            }

            function hitung() {
                var total   = 0;
                var totalTw = { 1: 0, 2: 0, 3: 0, 4: 0 };
                var perUnit = {};

                // Dijumlah PER UNIT lintas indikator: plafon pagu berlaku pada
                // jumlah seluruh bagian, bukan pada satu baris.
                Array.prototype.forEach.call(
                    tabel.querySelectorAll('.realisasi-input'),
                    function (inp) {
                        var unit  = inp.getAttribute('data-unit') || '';
                        var nilai = keAngka(inp.value);
                        var q     = inp.getAttribute('data-q');

                        perUnit[unit] = (perUnit[unit] || 0) + nilai;
                        total += nilai;
                        if (totalTw[q] !== undefined) totalTw[q] += nilai;
                    }
                );

                // Baris yang hanya bisa dilihat tetap menekan pagu unitnya.
                Array.prototype.forEach.call(
                    tabel.querySelectorAll('.nilai-kunci'),
                    function (el) {
                        var unit  = el.getAttribute('data-unit') || '';
                        var nilai = parseFloat(el.getAttribute('data-nilai')) || 0;
                        perUnit[unit] = (perUnit[unit] || 0) + nilai;
                    }
                );

                Array.prototype.forEach.call(
                    tabel.querySelectorAll('.serapan-unit'),
                    function (info) {
                        var kunci = info.getAttribute('data-unit') || '';
                        var pagu  = parseFloat(info.getAttribute('data-pagu')) || 0;
                        info.innerHTML = barSerapan(pagu, perUnit[kunci] || 0);
                    }
                );

                [1, 2, 3, 4].forEach(function (q) {
                    var el = document.getElementById('total-tw-' + q);
                    if (el) el.textContent = format(totalTw[q]);
                });

                if (totalEl) totalEl.textContent = format(total);

                if (sisaEl) {
                    if (totalPagu > 0) {
                        var sisa = totalPagu - total;
                        sisaEl.textContent = format(sisa) + ' (' + (total / totalPagu * 100).toFixed(1) + '% terserap)';
                        sisaEl.className = sisa < 0 ? 'text-danger fw-semibold' : '';
                    } else {
                        sisaEl.textContent = '—';
                    }
                }
            }

            Array.prototype.forEach.call(tabel.querySelectorAll('.realisasi-input'), function (i) {
                // Nilai dari server dirapikan sekali di awal supaya tampilannya
                // seragam dengan yang baru diketik.
                i.value = i.value === '' ? '' : ribuan(hanyaDigit(i.value));

                i.addEventListener('input', function () {
                    rapikan(i);
                    hitung();
                });

                // Tempel (paste) tidak memicu 'input' di sebagian peramban lama.
                i.addEventListener('paste', function () {
                    setTimeout(function () { rapikan(i); hitung(); }, 0);
                });
            });

            hitung();
        })();
    </script>
</body>

</html>

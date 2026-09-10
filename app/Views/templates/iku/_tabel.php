<?php

/**
 * Tabel IKU standalone — dipakai bersama halaman IKU admin kabupaten & admin OPD.
 *
 * Variabel yang diharapkan:
 * @var array  $iku_data   daftar sasaran, tiap item punya key `indikator`
 * @var array  $years      tahun-tahun periode terpilih (header kolom target)
 * @var bool   $show_opd   tampilkan kolom OPD (rekap lintas OPD)
 * @var string $base_url   'adminkab/iku' | 'adminopd/iku'
 * @var string $perm       prefix permission: 'iku_kab' | 'iku_opd'
 * @var bool   $can_manage baris di tabel ini boleh disunting dari halaman ini
 * @var string $query      query string tambahan untuk link aksi (mis. '?mode=kabupaten')
 */
$iku_data   = $iku_data ?? [];
$years      = !empty($years) ? $years : [];
$show_opd   = $show_opd ?? false;
$base_url   = $base_url ?? 'adminopd/iku';
$perm       = $perm ?? 'iku_opd';
$can_manage = $can_manage ?? true;
$query      = $query ?? '';

// Nomor + kolom OPD digabung per blok OPD supaya pagination di footer (yang
// mengelompokkan baris lewat rowspan sel pertama) tidak memotong blok gabungan.
$barisPerOpd = [];
if ($show_opd) {
    foreach ($iku_data as $sasaran) {
        $namaOpd = $sasaran['nama_opd'] ?? '-';
        $barisPerOpd[$namaOpd] = ($barisPerOpd[$namaOpd] ?? 0) + max(1, count($sasaran['indikator'] ?? []));
    }
}

$opdTercetak = [];
$no          = 1;

// No + (OPD) + Sasaran + Indikator + Definisi + Formula + Satuan + tahun + Sumber + PJ + Aksi
//
// Kolom "Status" (draft/selesai per indikator) DIHAPUS. Ia tidak pernah dibaca
// modul mana pun — LAKIP, RKT, dan MONEV tidak menyaringnya, dan getMatrix()
// tidak pernah dipanggil dengan saringan status dari menu IKU. Yang tersisa
// hanyalah lencana yang bisa diklik tanpa akibat, berdiri di sebelah status
// revisi yang justru punya arti.
$totalKolom = 9 + ($show_opd ? 1 : 0) + max(1, count($years));

// ---------------------------------------------------------------------
// LEBAR KOLOM DIPATOK, bukan diserahkan ke penyesuaian otomatis browser.
//
// Tabel ini 13-15 kolom dan tiga di antaranya berisi paragraf (Definisi,
// Formula, Sumber Data). Tanpa patokan, browser memberi ruang menurut panjang
// isi TERPANJANG per kolom: Definisi Operasional yang isinya satu paragraf
// justru terjepit jadi pita sempit setinggi belasan baris, sementara Satuan
// dan tahun kebagian ruang berlebih. Lebarnya dalam piksel (bukan persen)
// karena tabel memang lebih lebar dari layar dan digulir mendatar — persen
// akan memerasnya kembali ke lebar layar.
// Dipasangkan dengan `table-layout: fixed` di CSS bawah; tanpa itu patokan
// ini hanya jadi usulan yang boleh diabaikan browser.
// ---------------------------------------------------------------------
$lebarKolom = [48];                                  // No
if ($show_opd) {
    $lebarKolom[] = 170;                             // OPD
}
$lebarKolom[] = 230;                                 // Sasaran
$lebarKolom[] = 230;                                 // Indikator Kinerja Utama
$lebarKolom[] = 300;                                 // Definisi Operasional
$lebarKolom[] = 260;                                 // Formula / Rumusan
$lebarKolom[] = 90;                                  // Satuan
foreach (range(1, max(1, count($years))) as $ignored) {
    $lebarKolom[] = 78;                              // satu kolom per tahun
}
$lebarKolom[] = 200;                                 // Sumber Data
$lebarKolom[] = 180;                                 // Penanggung Jawab
$lebarKolom[] = 96;                                  // Aksi

$lebarTabel = array_sum($lebarKolom);

/**
 * Penyaji isi sel: kosong -> tanda pisah yang diredupkan, terisi -> teks aman.
 *
 * `nl2br` dipakai karena Definisi dan terutama Formula sering ditulis
 * berbaris-baris (mis. keterangan tiap variabel di bawah rumusnya). Tanpa itu
 * seluruhnya menyatu jadi satu paragraf dan rumusnya jadi sulit dibaca.
 * esc() dijalankan LEBIH DULU, jadi nl2br hanya menambah <br> pada teks yang
 * sudah aman — bukan sebaliknya.
 */
$sel = static function ($nilai): string {
    $teks = trim((string) ($nilai ?? ''));

    return $teks === ''
        ? '<span class="kosong">&mdash;</span>'
        : nl2br(esc($teks));
};
?>

<style>
    /* Lebar kolom baru dipatuhi kalau tata letaknya `fixed`; `max-content`
       membuat tabel selebar jumlah kolom lalu digulir di dalam pembungkusnya,
       bukan diperas mengikuti lebar layar. */
    .iku-table {
        table-layout: fixed;
        width: <?= $lebarTabel ?>px;
        min-width: 100%;
    }

    /* Sel berisi paragraf dibaca dari atas, bukan dari tengah — rata tengah
       membuat teks pendek (Indikator) mengambang di tengah baris yang
       tingginya ditentukan kolom lain (Definisi), sehingga antar kolom tidak
       sejajar. Kelas `align-middle` Bootstrap ber-!important, jadi kelas itu
       DIBUANG dari <table> dan hanya dipasang pada sel yang memang perlu
       (No, Sasaran ber-rowspan, Satuan, tahun, Aksi). */
    /* Spesifisitas sengaja dinaikkan (`main .table.iku-table`): design kit di
       templates/style.php memasang `main .table tbody td { vertical-align:
       middle }` untuk SEMUA tabel, dan itu menang atas `.iku-table tbody td`.
       Ditimpa di sini saja, bukan diubah di design kit — tabel lain memang
       cocok rata tengah karena selnya pendek. Sel ber-kelas `align-middle`
       (No, Sasaran, Satuan, tahun, Aksi) tetap di tengah karena utilitas
       Bootstrap itu ber-!important. */
    main .table.iku-table tbody td { vertical-align: top; }

    /* Judul kolom boleh turun baris: dengan lebar yang sudah dipatok, `nowrap`
       hanya akan membuat judul panjang menembus kolomnya. */
    .iku-table thead th {
        white-space: normal;
        line-height: 1.25;
        font-size: .78rem;
        letter-spacing: .01em;
    }

    /* Rumus & sumber data kerap memuat kata sangat panjang (URL, formula tanpa
       spasi) yang bisa mendorong lebar kolom. Dipaksa patah di dalam sel. */
    .iku-table tbody td {
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.4;
    }

    .iku-table td.kolom-angka { white-space: nowrap; font-variant-numeric: tabular-nums; }

    /* Sasaran & indikator adalah tulang punggung pembacaan tabel ini. */
    .iku-table td.kolom-utama { font-weight: 600; }

    /* Nilai '-' (data kosong) diredupkan supaya mata langsung menangkap
       kolom mana yang benar-benar terisi. */
    .iku-table .kosong { color: #9aa0a6; }
</style>

<div class="d-flex justify-content-end">
    <span class="text-muted small fst-italic petunjuk-gulir">
        <i class="fas fa-arrows-left-right me-1"></i>Tabel dapat digeser ke samping untuk melihat kolom Sumber Data, Penanggung Jawab, dan Aksi.
    </span>
</div>

<div class="table-responsive table-wrap mt-2">
    <table class="table table-bordered table-striped small iku-table">
        <colgroup>
            <?php foreach ($lebarKolom as $lebar): ?>
                <col style="width: <?= (int) $lebar ?>px;">
            <?php endforeach; ?>
        </colgroup>
        <thead class="table-success text-dark">
            <tr class="text-center">
                <th rowspan="2" class="align-middle">No</th>
                <?php if ($show_opd): ?>
                    <th rowspan="2" class="align-middle">OPD</th>
                <?php endif; ?>
                <th rowspan="2" class="align-middle">Sasaran</th>
                <th rowspan="2" class="align-middle">Indikator Kinerja Utama</th>
                <th rowspan="2" class="align-middle">Definisi Operasional</th>
                <th rowspan="2" class="align-middle">Formula / Rumusan Perhitungan</th>
                <th rowspan="2" class="align-middle">Satuan</th>
                <th colspan="<?= max(1, count($years)) ?>" class="align-middle">Target Capaian per Tahun</th>
                <th rowspan="2" class="align-middle">Sumber Data</th>
                <th rowspan="2" class="align-middle">Penanggung Jawab</th>
                <th rowspan="2" class="align-middle">Aksi</th>
            </tr>
            <tr class="text-center">
                <?php if (empty($years)): ?>
                    <th class="align-middle">-</th>
                <?php else: ?>
                    <?php foreach ($years as $tahun): ?>
                        <th class="align-middle"><?= esc($tahun) ?></th>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($iku_data)): ?>
                <tr>
                    <td colspan="<?= $totalKolom ?>" class="text-center text-muted py-4">
                        Belum ada data IKU untuk filter yang dipilih.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($iku_data as $sasaran): ?>
                    <?php
                    $indikators   = $sasaran['indikator'] ?? [];
                    $barisSasaran = max(1, count($indikators));
                    $namaOpd      = $sasaran['nama_opd'] ?? '-';
                    $sasaranId    = (int) ($sasaran['id'] ?? 0);

                    // sasaran tanpa indikator tetap ditampilkan satu baris kosong
                    $daftarBaris = !empty($indikators) ? $indikators : [null];
                    $barisPertamaSasaran = true;
                    ?>

                    <?php foreach ($daftarBaris as $indikator): ?>
                        <tr>
                            <?php if ($show_opd): ?>
                                <?php if (!isset($opdTercetak[$namaOpd])): ?>
                                    <td rowspan="<?= $barisPerOpd[$namaOpd] ?? $barisSasaran ?>" class="text-center align-middle">
                                        <?= $no++ ?>
                                    </td>
                                    <td rowspan="<?= $barisPerOpd[$namaOpd] ?? $barisSasaran ?>" class="text-start align-middle">
                                        <?= esc($namaOpd) ?>
                                    </td>
                                    <?php $opdTercetak[$namaOpd] = true; ?>
                                <?php endif; ?>
                            <?php elseif ($barisPertamaSasaran): ?>
                                <td rowspan="<?= $barisSasaran ?>" class="text-center align-middle"><?= $no++ ?></td>
                            <?php endif; ?>

                            <?php if ($barisPertamaSasaran): ?>
                                <td rowspan="<?= $barisSasaran ?>" class="text-start align-middle kolom-utama">
                                    <?= $sel($sasaran['sasaran'] ?? null) ?>
                                </td>
                            <?php endif; ?>

                            <?php if ($indikator === null): ?>
                                <td colspan="<?= 7 + max(1, count($years)) ?>" class="text-center text-muted">
                                    Belum ada indikator pada sasaran ini.
                                </td>
                            <?php else: ?>
                                <td class="text-start kolom-utama"><?= $sel($indikator['indikator'] ?? null) ?></td>
                                <td class="text-start"><?= $sel($indikator['definisi'] ?? null) ?></td>
                                <td class="text-start"><?= $sel($indikator['rumusan_perhitungan'] ?? null) ?></td>
                                <td class="text-center align-middle"><?= $sel($indikator['satuan_nama'] ?? null) ?></td>

                                <?php if (empty($years)): ?>
                                    <td class="text-center align-middle kolom-angka"><span class="kosong">&mdash;</span></td>
                                <?php else: ?>
                                    <?php foreach ($years as $tahun): ?>
                                        <td class="text-center align-middle kolom-angka">
                                            <?= $sel($indikator['target'][(int) $tahun] ?? null) ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <td class="text-start"><?= $sel($indikator['sumber_data'] ?? null) ?></td>
                                <td class="text-start"><?= $sel($indikator['penanggung_jawab'] ?? null) ?></td>

                            <?php endif; ?>

                            <?php if ($barisPertamaSasaran): ?>
                                <td rowspan="<?= $barisSasaran ?>" class="text-center align-middle text-nowrap">
                                    <?php if ($can_manage && (user_can($perm . '.update') || user_can($perm . '.delete'))): ?>
                                        <?php if (user_can($perm . '.update')): ?>
                                            <a href="<?= base_url($base_url . '/edit/' . $sasaranId . $query) ?>"
                                               class="btn btn-warning btn-sm" title="Edit IKU">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (user_can($perm . '.delete')): ?>
                                            <form method="post" action="<?= base_url($base_url . '/delete/' . $sasaranId) ?>"
                                                  class="d-inline"
                                                  data-konfirmasi="Sasaran IKU ini akan dihapus permanen."
                                                  data-konfirmasi-judul="Hapus Sasaran IKU"
                                                  data-konfirmasi-nama="<?= esc($sasaran['sasaran'] ?? '-', 'attr') ?>"
                                                  data-konfirmasi-rincian="<?= count($indikators) ?> indikator di bawah sasaran ini|Target tahunan tiap indikator|Program pendukung yang tertaut">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus IKU">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php $barisPertamaSasaran = false; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php

/**
 * Isi satu versi dokumen, dengan KOLOM YANG SAMA dengan menu dokumennya.
 *
 * =====================================================================
 * MENGAPA ADA
 *
 * Halaman Lihat Versi dahulu menampilkan isi arsip lewat tabel generik
 * berkolom sedikit (Tujuan | Sasaran | Indikator | Satuan | Target
 * digabung satu sel). Akibatnya menyandingkan sebuah versi dengan menu
 * dokumennya harus dikira-kira sendiri: visi, misi, baseline, jenis
 * indikator, dan kondisi akhir tidak pernah muncul, padahal seluruhnya
 * SUDAH dibekukan ke dalam arsip.
 *
 * Berkas ini menggantikannya dengan tabel yang meniru menu masing-masing:
 *
 *   RPJMD   -> adminKabupaten/rpjmd/rpjmd.php
 *   Renstra -> adminOpd/renstra/renstra.php
 *
 * IKU tidak lewat sini: arsipnya bukan ArsipVersiModel melainkan
 * `iku_revisi_*`, dan halaman lihatnya sendiri (iku/revisi_lihat.php).
 *
 * =====================================================================
 * YANG SENGAJA TIDAK DITIRU DARI MENU
 *
 *   STATUS  -> keadaan pengerjaan baris berjalan, bukan sifat arsip.
 *              Pada menu RPJMD pun kolom ini disembunyikan.
 *   AKSI    -> tombol sunting/hapus baris hidup. Id di sini milik arsip,
 *              jadi tombolnya akan menunjuk baris yang salah.
 *   DEFINISI OPERASIONAL (RPJMD) -> dinonaktifkan juga di menunya.
 *
 * Sebaliknya, penanda `jenis_perubahan` dan `substansial` DITAMBAHKAN
 * walau menu tidak punya: justru itu yang membedakan arsip versi dari
 * dokumen berjalan.
 *
 * @var array $versi  baris dokumen_versi
 * @var array $isi    pohon arsip (RPJMD: misi>tujuan>…; Renstra: tujuan>…)
 */
$modulIni = strtolower((string) ($versi['modul'] ?? ''));
$adaMisi  = $modulIni === 'rpjmd';

/* ---------------------------------------------------------------------
   TAHUN

   Kerangkanya periode versi. Tahun di luar periode tetap ditampilkan bila
   arsipnya memuatnya — menyembunyikannya membuat target yang ada terasa
   raib padahal ia tersimpan.
   ------------------------------------------------------------------- */
$tahunVersi = [];

for ($thn = (int) $versi['periode_mulai']; $thn <= (int) $versi['periode_akhir']; $thn++) {
    $tahunVersi[] = (string) $thn;
}

$tahunArsip = [];

$pungutTahun = static function (array $daftarInd) use (&$tahunArsip): void {
    foreach ($daftarInd as $ind) {
        foreach ($ind['target'] ?? [] as $tg) {
            $tahunArsip[(string) $tg['tahun']] = true;
        }
    }
};

$tujuanMentah = [];

foreach ($isi as $akar) {
    // RPJMD berpuncak di misi (anaknya array `tujuan`); Renstra langsung di
    // tujuan. Pembedanya WAJIB is_array, bukan isset: pada baris tujuan
    // Renstra kunci `tujuan` berisi TEKS tujuannya sendiri, bukan daftar anak.
    if (is_array($akar['tujuan'] ?? null)) {
        foreach ($akar['tujuan'] as $tRow) {
            $tujuanMentah[] = [$akar, $tRow];
        }
    } else {
        $tujuanMentah[] = [null, $akar];
    }
}

foreach ($tujuanMentah as [, $tRow]) {
    $pungutTahun($tRow['indikator_tujuan'] ?? []);

    foreach ($tRow['sasaran'] ?? [] as $sRow) {
        $pungutTahun($sRow['indikator'] ?? []);
    }
}

$tahunVersi = array_values(array_unique(array_merge($tahunVersi, array_keys($tahunArsip))));
sort($tahunVersi, SORT_STRING);

$tahunTerakhir = $tahunVersi === [] ? null : $tahunVersi[array_key_last($tahunVersi)];
$jumlahTahun   = max(1, count($tahunVersi));

/** Target satu indikator arsip -> peta tahun => nilai.
 *  RPJMD menamai kolomnya `target_tahunan`, Renstra `target` — keduanya diterima. */
$petaTarget = static function (array $indikator): array {
    $out = [];

    foreach ($indikator['target'] ?? [] as $tg) {
        $out[(string) $tg['tahun']] = $tg['target_tahunan'] ?? $tg['target'] ?? '-';
    }

    return $out;
};

/** Label jenis indikator, mengikuti penulisan di menu masing-masing. */
$labelJenis = static function ($jenis) use ($adaMisi): string {
    $j = strtolower(trim((string) $jenis));

    if ($j === 'indikator positif' || $j === 'positif') {
        return $adaMisi ? 'Indikator Positif' : 'Positif';
    }

    if ($j === 'indikator negatif' || $j === 'negatif') {
        return $adaMisi ? 'Indikator Negatif' : 'Negatif';
    }

    return $j === '' ? '-' : (string) $jenis;
};

/* ---------------------------------------------------------------------
   SATU BLOK PER TUJUAN

   Indikator tujuan (kiri) dan sasaran > indikator sasaran (kanan) disusun
   BERDAMPINGAN lalu diratakan ke jumlah baris terbanyak — pola yang sama
   dengan kedua menu. Sasaran tanpa indikator tetap satu baris supaya
   kekurangannya terlihat, bukan raib.
   ------------------------------------------------------------------- */
$bangunBlok = static function (array $tRow) use ($petaTarget): array {
    $kiri = [];

    foreach ($tRow['indikator_tujuan'] ?? [] as $itRow) {
        $kiri[] = [
            'indikator' => $itRow['indikator_tujuan'] ?? '-',
            'baseline'  => $itRow['baseline'] ?? '-',
            'target'    => $petaTarget($itRow),
        ];
    }

    $kanan  = [];
    $kosong = [
        'sasaran'   => null,
        'indikator' => '-',
        'baseline'  => '-',
        'satuan'    => '-',
        'jenis'     => '',
        'target'    => [],
        'penanda'   => null,
    ];

    foreach ($tRow['sasaran'] ?? [] as $sRow) {
        $daftarInd   = $sRow['indikator'] ?? [];
        $teksSasaran = $sRow['sasaran_rpjmd'] ?? $sRow['sasaran'] ?? '-';

        if ($daftarInd === []) {
            $kanan[] = ['sasaran' => ['teks' => $teksSasaran, 'rowspan' => 1]] + $kosong;

            continue;
        }

        foreach ($daftarInd as $ke => $iRow) {
            $kanan[] = [
                'sasaran'   => $ke === 0
                    ? ['teks' => $teksSasaran, 'rowspan' => count($daftarInd)]
                    : null,
                'indikator' => $iRow['indikator_sasaran'] ?? '-',
                'baseline'  => $iRow['baseline'] ?? '-',
                'satuan'    => $iRow['satuan_nama'] ?? $iRow['satuan'] ?? '-',
                'jenis'     => $iRow['jenis_indikator'] ?? '',
                'target'    => $petaTarget($iRow),
                'penanda'   => [
                    'jenis_perubahan'       => $iRow['jenis_perubahan'] ?? 'tetap',
                    'perubahan_substansial' => (int) ($iRow['perubahan_substansial'] ?? 0),
                ],
            ];
        }
    }

    if ($kiri === []) {
        $kiri[] = ['indikator' => '-', 'baseline' => '-', 'target' => []];
    }

    if ($kanan === []) {
        $kanan[] = ['sasaran' => ['teks' => '-', 'rowspan' => 1]] + $kosong;
    }

    return [
        'teks'  => $tRow['tujuan_rpjmd'] ?? $tRow['tujuan'] ?? '-',
        'kiri'  => $kiri,
        'kanan' => $kanan,
        'baris' => max(count($kiri), count($kanan)),
    ];
};

/* Susun: RPJMD dikelompokkan per misi (dan visi di atasnya); Renstra rata. */
$blokMisi     = [];
$blokTujuan   = [];
$totalPerVisi = [];

if ($adaMisi) {
    foreach ($isi as $mRow) {
        $anak   = [];
        $tinggi = 0;

        foreach ($mRow['tujuan'] ?? [] as $tRow) {
            $b = $bangunBlok($tRow);
            $anak[] = $b;
            $tinggi += $b['baris'];
        }

        if ($anak === []) {
            continue;
        }

        $kunciVisi = (string) ($mRow['source_visi_id'] ?? ('t:' . ($mRow['visi'] ?? '-')));
        $totalPerVisi[$kunciVisi] = ($totalPerVisi[$kunciVisi] ?? 0) + $tinggi;

        $blokMisi[] = [
            'visi'       => $mRow['visi'] ?? '-',
            'kunci_visi' => $kunciVisi,
            'misi'       => $mRow['misi'] ?? '-',
            'tinggi'     => $tinggi,
            'tujuan'     => $anak,
        ];
    }
} else {
    foreach ($tujuanMentah as [, $tRow]) {
        $blokTujuan[] = $bangunBlok($tRow);
    }
}

$visiTercetak = [];
$nomorTujuan  = 1;
?>

<?php if ($adaMisi): ?>
    <?php // ===================== RPJMD ===================== ?>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped text-center small align-middle revisi-tabel" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th rowspan="2" class="border p-2 align-middle">VISI</th>
                    <th rowspan="2" class="border p-2 align-middle">MISI</th>
                    <th rowspan="2" class="border p-2 align-middle">TUJUAN</th>
                    <th rowspan="2" class="border p-2 align-middle">INDIKATOR</th>
                    <th rowspan="2" class="border p-2 align-middle">BASELINE</th>
                    <th colspan="<?= $jumlahTahun ?>" class="border p-2">TARGET TUJUAN PER TAHUN</th>

                    <th rowspan="2" class="border p-2 align-middle">SASARAN</th>
                    <th rowspan="2" class="border p-2 align-middle">INDIKATOR SASARAN</th>
                    <th rowspan="2" class="border p-2 align-middle">BASELINE SASARAN</th>
                    <th rowspan="2" class="border p-2 align-middle">SATUAN</th>
                    <th rowspan="2" class="border p-2 align-middle">JENIS INDIKATOR</th>
                    <th colspan="<?= $jumlahTahun ?>" class="border p-2">TARGET CAPAIAN PER TAHUN</th>
                    <th rowspan="2" class="border p-2 align-middle">KONDISI AKHIR</th>
                </tr>
                <tr>
                    <?php foreach ($tahunVersi as $thn): ?>
                        <th class="border p-2"><?= esc($thn) ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($tahunVersi as $thn): ?>
                        <th class="border p-2"><?= esc($thn) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blokMisi as $mb): ?>
                    <?php $misiTercetak = false; ?>
                    <?php foreach ($mb['tujuan'] as $tb): ?>
                        <?php $tujuanTercetak = false; ?>
                        <?php for ($brs = 0; $brs < $tb['baris']; $brs++): ?>
                            <?php
                            $kiri  = $tb['kiri'][$brs] ?? ['indikator' => '-', 'baseline' => '-', 'target' => []];
                            $kanan = $tb['kanan'][$brs] ?? [
                                'sasaran'   => null,
                                'indikator' => '-',
                                'baseline'  => '-',
                                'satuan'    => '-',
                                'jenis'     => '',
                                'target'    => [],
                                'penanda'   => null,
                            ];
                            ?>
                            <tr>
                                <?php if (! $misiTercetak && ! isset($visiTercetak[$mb['kunci_visi']])): ?>
                                    <td class="border p-2 align-top text-start"
                                        rowspan="<?= (int) ($totalPerVisi[$mb['kunci_visi']] ?? $mb['tinggi']) ?>">
                                        <?= esc($mb['visi']) ?>
                                    </td>
                                    <?php $visiTercetak[$mb['kunci_visi']] = true; ?>
                                <?php endif; ?>

                                <?php if (! $misiTercetak): ?>
                                    <td class="border p-2 align-top text-start" rowspan="<?= (int) $mb['tinggi'] ?>">
                                        <?= esc($mb['misi']) ?>
                                    </td>
                                    <?php $misiTercetak = true; ?>
                                <?php endif; ?>

                                <?php if (! $tujuanTercetak): ?>
                                    <td class="border p-2 align-top text-start" rowspan="<?= (int) $tb['baris'] ?>">
                                        <?= esc($tb['teks']) ?>
                                    </td>
                                    <?php $tujuanTercetak = true; ?>
                                <?php endif; ?>

                                <td class="border p-2 align-top text-start"><?= esc($kiri['indikator']) ?></td>
                                <td class="border p-2 align-top text-start"><?= esc($kiri['baseline'] ?? '-') ?></td>
                                <?php foreach ($tahunVersi as $thn): ?>
                                    <td class="border p-2 align-top text-start"><?= esc($kiri['target'][$thn] ?? '-') ?></td>
                                <?php endforeach; ?>

                                <?php if ($kanan['sasaran'] !== null): ?>
                                    <td class="border p-2 align-top text-start"
                                        rowspan="<?= (int) $kanan['sasaran']['rowspan'] ?>">
                                        <?= esc($kanan['sasaran']['teks']) ?>
                                    </td>
                                <?php endif; ?>

                                <td class="border p-2 align-top text-start">
                                    <?= esc($kanan['indikator']) ?>
                                    <?php if (($kanan['penanda']['jenis_perubahan'] ?? 'tetap') !== 'tetap'): ?>
                                        <span class="badge bg-light text-dark border ms-1"><?= esc($kanan['penanda']['jenis_perubahan']) ?></span>
                                    <?php endif; ?>
                                    <?php if ((int) ($kanan['penanda']['perubahan_substansial'] ?? 0) === 1): ?>
                                        <span class="badge bg-warning text-dark ms-1"
                                              title="Tren antar tahun tidak boleh disambung">substansial</span>
                                    <?php endif; ?>
                                </td>
                                <td class="border p-2 align-top text-start"><?= esc($kanan['baseline'] ?? '-') ?></td>
                                <td class="border p-2 align-top text-start"><?= esc($kanan['satuan'] ?? '-') ?></td>
                                <td class="border p-2 align-top text-start"><?= esc($labelJenis($kanan['jenis'] ?? '')) ?></td>
                                <?php foreach ($tahunVersi as $thn): ?>
                                    <td class="border p-2 align-top text-start"><?= esc($kanan['target'][$thn] ?? '-') ?></td>
                                <?php endforeach; ?>

                                <?php // KONDISI AKHIR = target tahun terakhir periode, sama dengan menu. ?>
                                <td class="border p-2 align-top text-start">
                                    <?= esc($tahunTerakhir !== null ? ($kanan['target'][$tahunTerakhir] ?? '-') : '-') ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <?php // ===================== RENSTRA ===================== ?>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped text-center small align-middle revisi-tabel" data-no-paginate>
            <thead class="table-success">
                <tr>
                    <th rowspan="2" class="border p-2 align-middle" style="width:40px">No</th>
                    <th rowspan="2" class="border p-2 align-middle">Tujuan</th>
                    <th rowspan="2" class="border p-2 align-middle">Indikator Tujuan</th>
                    <th colspan="<?= $jumlahTahun ?>" class="border p-2">TARGET TUJUAN PER TAHUN</th>

                    <th rowspan="2" class="border p-2 align-middle">Sasaran</th>
                    <th rowspan="2" class="border p-2 align-middle">Indikator Sasaran</th>
                    <th rowspan="2" class="border p-2 align-middle">Satuan</th>
                    <th rowspan="2" class="border p-2 align-middle">Kondisi Awal</th>
                    <th colspan="<?= $jumlahTahun ?>" class="border p-2">TARGET SASARAN PER TAHUN</th>
                    <th rowspan="2" class="border p-2 align-middle">Kondisi Akhir</th>
                    <th rowspan="2" class="border p-2 align-middle">Jenis Indikator</th>
                </tr>
                <tr>
                    <?php foreach ($tahunVersi as $thn): ?>
                        <th class="border p-2"><?= esc($thn) ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($tahunVersi as $thn): ?>
                        <th class="border p-2"><?= esc($thn) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blokTujuan as $tb): ?>
                    <?php $tujuanTercetak = false; ?>
                    <?php for ($brs = 0; $brs < $tb['baris']; $brs++): ?>
                        <?php
                        $kiri  = $tb['kiri'][$brs] ?? ['indikator' => '-', 'baseline' => '-', 'target' => []];
                        $kanan = $tb['kanan'][$brs] ?? [
                            'sasaran'   => null,
                            'indikator' => '-',
                            'baseline'  => '-',
                            'satuan'    => '-',
                            'jenis'     => '',
                            'target'    => [],
                            'penanda'   => null,
                        ];
                        ?>
                        <tr>
                            <?php if (! $tujuanTercetak): ?>
                                <td class="border p-2 align-middle text-center" rowspan="<?= (int) $tb['baris'] ?>">
                                    <?= $nomorTujuan++ ?>
                                </td>
                                <td class="border p-2 align-top text-start" rowspan="<?= (int) $tb['baris'] ?>">
                                    <?= esc($tb['teks']) ?>
                                </td>
                                <?php $tujuanTercetak = true; ?>
                            <?php endif; ?>

                            <td class="border p-2 align-top text-start"><?= esc($kiri['indikator']) ?></td>
                            <?php foreach ($tahunVersi as $thn): ?>
                                <td class="border p-2 align-top text-start"><?= esc($kiri['target'][$thn] ?? '-') ?></td>
                            <?php endforeach; ?>

                            <?php if ($kanan['sasaran'] !== null): ?>
                                <td class="border p-2 align-top text-start"
                                    rowspan="<?= (int) $kanan['sasaran']['rowspan'] ?>">
                                    <?= esc($kanan['sasaran']['teks']) ?>
                                </td>
                            <?php endif; ?>

                            <td class="border p-2 align-top text-start">
                                <?= esc($kanan['indikator']) ?>
                                <?php if (($kanan['penanda']['jenis_perubahan'] ?? 'tetap') !== 'tetap'): ?>
                                    <span class="badge bg-light text-dark border ms-1"><?= esc($kanan['penanda']['jenis_perubahan']) ?></span>
                                <?php endif; ?>
                                <?php if ((int) ($kanan['penanda']['perubahan_substansial'] ?? 0) === 1): ?>
                                    <span class="badge bg-warning text-dark ms-1"
                                          title="Tren antar tahun tidak boleh disambung">substansial</span>
                                <?php endif; ?>
                            </td>
                            <td class="border p-2 align-top text-start"><?= esc($kanan['satuan'] ?? '-') ?></td>
                            <td class="border p-2 align-top text-start"><?= esc($kanan['baseline'] ?? '-') ?></td>
                            <?php foreach ($tahunVersi as $thn): ?>
                                <td class="border p-2 align-top text-start"><?= esc($kanan['target'][$thn] ?? '-') ?></td>
                            <?php endforeach; ?>

                            <?php // Kondisi Akhir = target tahun terakhir periode, sama dengan menu. ?>
                            <td class="border p-2 align-top text-start">
                                <?= esc($tahunTerakhir !== null ? ($kanan['target'][$tahunTerakhir] ?? '-') : '-') ?>
                            </td>
                            <td class="border p-2 align-top text-start"><?= esc($labelJenis($kanan['jenis'] ?? '')) ?></td>
                        </tr>
                    <?php endfor; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

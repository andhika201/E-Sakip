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

// No + (OPD) + Sasaran + Indikator + Definisi + Formula + Satuan + tahun + Sumber + PJ + Status + Aksi
$totalKolom = 10 + ($show_opd ? 1 : 0) + max(1, count($years));
?>

<div class="table-responsive table-wrap mt-3">
    <table class="table table-bordered table-striped align-middle small iku-table">
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
                <th rowspan="2" class="align-middle">Status</th>
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
                                <td rowspan="<?= $barisSasaran ?>" class="text-start align-middle">
                                    <?= esc($sasaran['sasaran'] ?? '-') ?>
                                </td>
                            <?php endif; ?>

                            <?php if ($indikator === null): ?>
                                <td colspan="<?= 7 + max(1, count($years)) ?>" class="text-center text-muted">
                                    Belum ada indikator pada sasaran ini.
                                </td>
                            <?php else: ?>
                                <td class="text-start"><?= esc($indikator['indikator'] ?? '-') ?></td>
                                <td class="text-start"><?= esc(($indikator['definisi'] ?? '') !== '' ? $indikator['definisi'] : '-') ?></td>
                                <td class="text-start"><?= esc(($indikator['rumusan_perhitungan'] ?? '') !== '' ? $indikator['rumusan_perhitungan'] : '-') ?></td>
                                <td class="text-center"><?= esc(($indikator['satuan_nama'] ?? '') !== '' ? $indikator['satuan_nama'] : '-') ?></td>

                                <?php if (empty($years)): ?>
                                    <td class="text-center">-</td>
                                <?php else: ?>
                                    <?php foreach ($years as $tahun): ?>
                                        <?php $nilai = $indikator['target'][(int) $tahun] ?? null; ?>
                                        <td class="text-center"><?= esc(($nilai === null || $nilai === '') ? '-' : $nilai) ?></td>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <td class="text-start"><?= esc(($indikator['sumber_data'] ?? '') !== '' ? $indikator['sumber_data'] : '-') ?></td>
                                <td class="text-start"><?= esc(($indikator['penanggung_jawab'] ?? '') !== '' ? $indikator['penanggung_jawab'] : '-') ?></td>

                                <td class="text-center">
                                    <?php $selesai = strtolower(trim((string) ($indikator['status'] ?? 'draft'))) === 'selesai'; ?>
                                    <span class="badge <?= $selesai ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= $selesai ? 'Selesai' : 'Draft' ?>
                                    </span>
                                    <?php if ($can_manage && user_can($perm . '.update')): ?>
                                        <form method="post"
                                              action="<?= base_url($base_url . '/change_status/' . (int) $indikator['id']) ?>"
                                              class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none"
                                                    title="Ubah status indikator ini">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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
                                                  onsubmit="return confirm('Hapus sasaran IKU ini beserta seluruh indikator dan targetnya?');">
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

<?php

/**
 * Halaman keputusan Admin Kabupaten atas satu revisi IKU OPD.
 *
 * =====================================================================
 * YANG DITARUH PALING ATAS, DAN MENGAPA
 *
 * Urutannya sengaja: DAMPAK dulu, isi lengkap belakangan.
 *
 * Isi dokumen bisa dibaca siapa saja kapan saja. Yang hanya bisa dilihat di
 * sini — dan hanya sebelum tombol ditekan — adalah akibatnya: indikator mana
 * yang akan DIPENSIUNKAN, revisi mana yang akan menjadi arsip, dan tahun
 * berapa saja yang akan dipayungi. Menaruhnya di bawah tabel panjang sama
 * saja dengan menyembunyikannya.
 *
 * @var array $revisi
 * @var array $isi        pohon sasaran > indikator > target
 * @var array $praTinjau  hasil IkuRevisiModel::praTinjauPengesahan()
 * @var bool  $bolehPutus revisi ini memang sedang menunggu keputusan
 */
$title        = $title ?? 'Verifikasi Revisi IKU';
$judulHalaman = $judulHalaman ?? $title;

$baru       = $praTinjau['baru'] ?? [];
$berubah    = $praTinjau['berubah'] ?? [];
$dihentikan = $praTinjau['dihentikan'] ?? [];
$pembanding = $praTinjau['pembanding'] ?? null;
$digeser    = $praTinjau['digeser'] ?? [];
$tahun      = $praTinjau['tahun'] ?? ['mulai' => 0, 'sampai' => null];

$rincianSelisih = static function (array $selisih): string {
    $baris = [];

    foreach ($selisih as $medan => $d) {
        $baris[] = '<div>' . esc($medan) . ': '
            . esc(($d['lama'] ?? '') === '' ? '-' : $d['lama'])
            . ' &rarr; <strong>' . esc(($d['baru'] ?? '') === '' ? '-' : $d['baru']) . '</strong></div>';
    }

    return implode('', $baris);
};
?>
<?= $this->include('templates/shell_atas') ?>

<div class="kotak-jejak beku mb-4">
    <div class="row g-3 small">
        <div class="col-md-3">
            <div class="text-secondary">OPD</div>
            <div class="fw-semibold"><?= esc($namaOpd) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-secondary">Revisi</div>
            <div class="fw-semibold">
                <?= esc($revisi['nomor'] ?? '') ?><?= ! empty($revisi['nama']) ? ' — ' . esc($revisi['nama']) : '' ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-secondary">Periode IKU</div>
            <div class="fw-semibold">
                <?= (int) $revisi['tahun_mulai'] ?>&ndash;<?= (int) $revisi['tahun_akhir'] ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-secondary">Diajukan</div>
            <div class="fw-semibold">
                <?= ! empty($revisi['submitted_at'])
                    ? esc(date('d M Y H:i', strtotime($revisi['submitted_at'])))
                    : '&mdash;' ?>
            </div>
        </div>
        <?php if (! empty($revisi['dasar_hukum'])): ?>
            <div class="col-md-6">
                <div class="text-secondary">Dasar</div>
                <div class="fw-semibold">
                    <?= esc($revisi['dasar_hukum']) ?><?= ! empty($revisi['nomor_dasar'])
                        ? ' No. ' . esc($revisi['nomor_dasar']) : '' ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (! empty($revisi['catatan'])): ?>
            <div class="col-md-6">
                <div class="text-secondary">Catatan</div>
                <div><?= nl2br(esc($revisi['catatan'])) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============ DAMPAK BILA DISAHKAN ============ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Bila revisi ini disahkan</div>
    <div class="card-body">
        <div class="small mb-3">
            Berlaku mulai <strong><?= (int) $tahun['mulai'] ?></strong>
            <?= $tahun['sampai'] !== null
                ? 'sampai sebelum <strong>' . (int) $tahun['sampai'] . '</strong>'
                : 'dan seterusnya' ?>.
            <?php if ($pembanding !== null): ?>
                Dibandingkan dengan revisi yang berlaku sekarang
                (<strong><?= esc($pembanding['nomor'] ?? '') ?>
                <?= ! empty($pembanding['nama']) ? '— ' . esc($pembanding['nama']) : '' ?></strong>).
            <?php else: ?>
                Belum ada revisi lain yang berlaku, jadi seluruh isinya terhitung baru.
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-success"><?= count($baru) ?> indikator baru</span>
            <span class="badge bg-warning text-dark"><?= count($berubah) ?> berubah</span>
            <span class="badge <?= $dihentikan === [] ? 'bg-light text-dark border' : 'bg-danger' ?>">
                <?= count($dihentikan) ?> dipensiunkan
            </span>
            <?php if ($digeser !== []): ?>
                <span class="badge bg-secondary"><?= count($digeser) ?> revisi jadi arsip</span>
            <?php endif; ?>
        </div>

        <?php if ($dihentikan !== []): ?>
            <?php /* Ditaruh paling menonjol: inilah satu-satunya akibat yang
                     TIDAK terbaca dari isi dokumen, karena yang hilang memang
                     tidak tertulis di sana. */ ?>
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    <?= count($dihentikan) ?> indikator tidak lagi tercantum dan akan dipensiunkan
                </div>
                <div class="small mb-2">
                    Dipensiunkan, <strong>bukan dihapus</strong> — realisasi LAKIP tahun-tahun
                    sebelumnya tetap utuh dan tetap menunjuk indikator ini.
                </div>
                <ul class="small mb-0">
                    <?php foreach ($dihentikan as $d): ?>
                        <li>
                            <?= esc($d['indikator']) ?>
                            <span class="text-muted">(<?= esc($d['sasaran'] ?? '-') ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($berubah !== []): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle small" data-no-paginate>
                    <thead class="table-light">
                        <tr>
                            <th style="width:32%">Indikator</th>
                            <th>Yang berubah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($berubah as $b): ?>
                            <tr>
                                <td>
                                    <?= esc($b['indikator']) ?>
                                    <div class="text-muted sel-kecil"><?= esc($b['sasaran'] ?? '-') ?></div>
                                </td>
                                <td class="sel-kecil"><?= $rincianSelisih($b['selisih'] ?? []) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($baru !== []): ?>
            <div class="small">
                <div class="fw-semibold mb-1">Indikator baru</div>
                <ul class="mb-0">
                    <?php foreach ($baru as $b): ?>
                        <li>
                            <?= esc($b['indikator']) ?>
                            <span class="text-muted">(<?= esc($b['sasaran'] ?? '-') ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============ KEPUTUSAN ============ -->
<?php if ($bolehPutus): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <form method="post"
                          action="<?= base_url('adminkab/verifikasi/iku/sahkan/' . (int) $revisi['id']) ?>"
                          onsubmit="return confirm('Sahkan revisi IKU ini? Isinya diterapkan ke IKU berjalan mulai tahun <?= (int) $tahun['mulai'] ?><?= $dihentikan !== [] ? ', dan ' . count($dihentikan) . ' indikator dipensiunkan' : '' ?>.')">
                        <?= csrf_field() ?>
                        <button class="btn btn-success w-100">
                            <i class="fa-solid fa-check me-1"></i>Sahkan Revisi
                        </button>
                    </form>
                </div>
                <div class="col-md-7">
                    <form method="post"
                          action="<?= base_url('adminkab/verifikasi/iku/kembalikan/' . (int) $revisi['id']) ?>">
                        <?= csrf_field() ?>
                        <textarea name="catatan" rows="2" required maxlength="5000"
                                  class="form-control form-control-sm mb-2"
                                  placeholder="Catatan pengembalian (wajib)"></textarea>
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="fa-solid fa-rotate-left me-1"></i>Kembalikan ke Penyusun
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-light border">
        Revisi ini tidak sedang menunggu keputusan
        (status: <strong><?= esc($revisi['status']) ?></strong>), jadi halaman ini hanya bisa dibaca.
    </div>
<?php endif; ?>

<!-- ============ ISI LENGKAP ============ -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Isi Lengkap Revisi</div>
    <div class="card-body">
        <?php if (empty($isi)): ?>
            <div class="alert alert-warning border mb-0 small">
                Revisi ini <strong>tidak berisi apa pun</strong>. Mengesahkannya berarti seluruh
                IKU periode ini dipensiunkan.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle small" data-no-paginate>
                    <thead class="table-light">
                        <tr>
                            <th>Sasaran</th>
                            <th>Indikator</th>
                            <th style="width:80px">Satuan</th>
                            <?php foreach ($years as $th): ?>
                                <th style="width:70px" class="text-center"><?= (int) $th ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($isi as $sas): ?>
                            <?php
                            $daftarInd = $sas['indikator'] ?? [];
                            $tinggi    = max(1, count($daftarInd));
                            $dicetak   = false;
                            ?>
                            <?php if ($daftarInd === []): ?>
                                <tr>
                                    <td><?= esc($sas['sasaran']) ?></td>
                                    <td colspan="<?= 2 + count($years) ?>" class="text-secondary fst-italic">
                                        Belum ada indikator
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftarInd as $ind): ?>
                                    <tr class="<?= ($ind['jenis_perubahan'] ?? '') === 'dihentikan' ? 'table-secondary' : '' ?>">
                                        <?php if (! $dicetak): ?>
                                            <td rowspan="<?= $tinggi ?>"><?= esc($sas['sasaran']) ?></td>
                                            <?php $dicetak = true; ?>
                                        <?php endif; ?>
                                        <td>
                                            <?= esc($ind['indikator']) ?>
                                            <?php if (($ind['jenis_perubahan'] ?? 'tetap') !== 'tetap'): ?>
                                                <span class="badge bg-light text-dark border ms-1">
                                                    <?= esc($ind['jenis_perubahan']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= esc($ind['satuan_nama'] ?? $ind['satuan'] ?? '') ?>
                                        </td>
                                        <?php foreach ($years as $th): ?>
                                            <td class="text-center">
                                                <?= esc($ind['target'][(int) $th]['target'] ?? '-') ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <a href="<?= base_url('adminkab/verifikasi') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Kembali ke Antrean
    </a>
</div>

<?= $this->include('templates/shell_bawah') ?>

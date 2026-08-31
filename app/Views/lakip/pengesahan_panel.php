<?php

/**
 * Panel Pengesahan LAKIP — kunci tahun + permintaan perbaikan.
 *
 * Menyembunyikan dirinya sendiri bila tabelnya belum ada, sama seperti
 * snapshot_panel: basis data yang belum dimigrasi tidak boleh membuat
 * halaman ini pecah.
 *
 * Butuh: $tahun, $pengesahan (array|null), $permintaanMenunggu (array|null),
 *        $riwayatPermintaan (array), $bolehSahkan (bool)
 */
$pengesahan        = $pengesahan        ?? null;
$permintaanMenunggu = $permintaanMenunggu ?? null;
$riwayatPermintaan = $riwayatPermintaan ?? [];
$bolehSahkan       = $bolehSahkan       ?? false;
$tahun             = $tahun             ?? date('Y');

// Panel yang sama dipakai layar OPD dan layar Kabupaten; hanya alamat
// tujuannya yang berbeda. Bawaannya OPD supaya layar lama tidak berubah.
$pengesahanUrl     = rtrim($pengesahanUrl ?? base_url('adminopd/lakip/pengesahan'), '/');

// Panel tidak dirender sama sekali bila fitur belum terpasang.
if (! ($pengesahanSiap ?? false)) {
    return;
}

$terkunci = $pengesahan !== null && $pengesahan['status'] === 'disahkan';
$dibuka   = $pengesahan !== null && $pengesahan['status'] === 'dibuka';
?>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h5 class="mb-0">
                <i class="fa-solid fa-stamp me-1"></i> Pengesahan LAKIP <?= esc($tahun) ?>
            </h5>

            <?php if ($terkunci): ?>
                <span class="badge bg-success"><i class="fa-solid fa-lock me-1"></i>Sudah disahkan &mdash; terkunci</span>
            <?php elseif ($dibuka): ?>
                <span class="badge bg-warning text-dark"><i class="fa-solid fa-lock-open me-1"></i>Dibuka untuk perbaikan</span>
            <?php else: ?>
                <span class="badge bg-secondary">Belum disahkan</span>
            <?php endif; ?>
        </div>

        <?php /* ---------- KEADAAN & PENJELASAN ---------- */ ?>
        <?php if ($terkunci): ?>
            <div class="alert alert-success border-0 small mb-3">
                Angka LAKIP <?= esc($tahun) ?> <strong>terkunci</strong> dan tidak bisa disunting,
                ditambah, maupun dihapus.
                <?php if (! empty($pengesahan['nomor'])): ?>
                    <br>Nomor: <strong><?= esc($pengesahan['nomor']) ?></strong>
                <?php endif; ?>
                <?php if (! empty($pengesahan['disahkan_pada'])): ?>
                    <br>Disahkan pada <?= esc(date('d/m/Y H:i', strtotime($pengesahan['disahkan_pada']))) ?>
                <?php endif; ?>
                <br>Bila ada <em>typo</em> atau salah ketik, ajukan permintaan perbaikan di bawah &mdash;
                Admin Kabupaten yang memutuskan.
            </div>
        <?php elseif ($dibuka): ?>
            <div class="alert alert-warning border-0 small mb-3">
                Permintaan perbaikan Anda <strong>disetujui</strong>. Silakan perbaiki angkanya sekarang,
                lalu tekan <strong>Sahkan Ulang</strong> supaya tahun ini terkunci kembali.
            </div>
        <?php else: ?>
            <div class="alert alert-light border small mb-3">
                Setelah seluruh realisasi <?= esc($tahun) ?> terisi dan benar, tekan <strong>Sahkan</strong>.
                Tahun ini akan terkunci sehingga angkanya tidak berubah lagi tanpa sepengetahuan
                Admin Kabupaten.
            </div>
        <?php endif; ?>

        <?php if ($bolehSahkan): ?>
            <?php /* ---------- SAHKAN / SAHKAN ULANG ---------- */ ?>
            <?php if (! $terkunci): ?>
                <form method="post" action="<?= esc($pengesahanUrl . '/sahkan') ?>"
                      class="row g-2 align-items-end mb-3"
                      onsubmit="return confirm('Sahkan LAKIP <?= esc($tahun) ?>?\n\nSetelah disahkan, angka tahun ini terkunci. Perbaikan berikutnya harus lewat persetujuan Admin Kabupaten.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tahun" value="<?= esc($tahun) ?>">

                    <div class="col-md-4">
                        <label class="form-label small mb-1">Nomor surat / berita acara <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="nomor" class="form-control form-control-sm"
                               value="<?= esc($pengesahan['nomor'] ?? '') ?>" placeholder="mis. 800/123/LAKIP/2026">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Catatan <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="catatan" class="form-control form-control-sm"
                               placeholder="mis. disahkan setelah verifikasi internal">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success btn-sm w-100">
                            <i class="fa-solid fa-stamp me-1"></i>
                            <?= $dibuka ? 'Sahkan Ulang' : 'Sahkan LAKIP ' . esc($tahun) ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php /* ---------- AJUKAN PERBAIKAN ---------- */ ?>
            <?php if ($terkunci && $permintaanMenunggu === null): ?>
                <form method="post" action="<?= esc($pengesahanUrl . '/ajukan') ?>"
                      class="border rounded p-3 bg-light">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tahun" value="<?= esc($tahun) ?>">

                    <label class="form-label small fw-semibold mb-1">
                        Ajukan Permintaan Perbaikan <span class="text-danger">*</span>
                    </label>
                    <div class="form-text mb-2">
                        Sebutkan <strong>apa yang keliru</strong> dan letaknya di indikator mana.
                        Alasan ini yang dibaca Admin Kabupaten untuk memutuskan.
                    </div>
                    <div class="row g-2">
                        <div class="col-md-9">
                            <input type="text" name="alasan" class="form-control form-control-sm" required
                                   placeholder="mis. Capaian Angka Kematian Ibu tertulis 62, seharusnya 6,2 (salah ketik koma)">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary btn-sm w-100">
                                <i class="fa-solid fa-paper-plane me-1"></i>Kirim ke Admin Kab
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <?php /* ---------- PERMINTAAN YANG SEDANG MENUNGGU ---------- */ ?>
            <?php if ($permintaanMenunggu !== null): ?>
                <div class="alert alert-info border-0 small d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong><i class="fa-solid fa-hourglass-half me-1"></i>Menunggu keputusan Admin Kabupaten</strong>
                        <br>Alasan: <em><?= esc($permintaanMenunggu['alasan']) ?></em>
                        <br><span class="text-muted">Diajukan <?= esc(date('d/m/Y H:i', strtotime($permintaanMenunggu['diminta_pada']))) ?></span>
                    </div>
                    <form method="post"
                          action="<?= esc($pengesahanUrl . '/tarik/' . (int) $permintaanMenunggu['id']) ?>"
                          onsubmit="return confirm('Tarik kembali permintaan perbaikan ini?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tahun" value="<?= esc($tahun) ?>">
                        <button class="btn btn-outline-secondary btn-sm">Tarik Permintaan</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php /* ---------- RIWAYAT ----------
                 Ditampilkan seluruhnya, termasuk yang ditolak. Justru riwayat
                 inilah yang membuat pengesahan bisa dipertanggungjawabkan. */ ?>
        <?php if ($riwayatPermintaan !== []): ?>
            <div class="mt-3">
                <div class="small fw-semibold text-secondary mb-2">Riwayat permintaan perbaikan</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:130px">Diajukan</th>
                                <th>Alasan</th>
                                <th style="width:110px">Keputusan</th>
                                <th>Tanggapan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayatPermintaan as $r): ?>
                                <tr>
                                    <td class="small text-muted"><?= esc(date('d/m/Y H:i', strtotime($r['diminta_pada']))) ?></td>
                                    <td class="small"><?= esc($r['alasan']) ?></td>
                                    <td>
                                        <?php
                                        $warna = [
                                            'menunggu'  => 'bg-warning text-dark',
                                            'disetujui' => 'bg-success',
                                            'ditolak'   => 'bg-danger',
                                            'ditarik'   => 'bg-secondary',
                                        ][$r['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $warna ?>"><?= esc(ucfirst($r['status'])) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= esc($r['tanggapan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

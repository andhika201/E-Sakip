<?php
/**
 * Dua tabel tambahan di bawah tabel utama LAKIP (tampilan layar).
 *
 * Dipakai bersama oleh:
 *   - adminKabupaten/lakip/lakip.php  (AdminKab\LakipController)
 *   - adminOpd/lakip/lakip.php        (AdminOpd\LakipOpdController)
 *
 * Variabel yang diharapkan (semua sudah disiapkan controller lewat
 * LakipAddendumTrait::lakipAddendumData()):
 *   $indikatorRows  daftar target/indikator tahun aktif (flat, punya target_id)
 *   $analisisMap    [target_id => daftar baris analisis]
 *   $efisiensiRows  baris efisiensi program tahun aktif
 *   $programOptions daftar program tahun aktif yang boleh dipakai unit ini
 *   $addendumScope  ['mode','tahun','opdScope','canWrite','role','alasan']
 *   $addendumBase   prefix url ('adminkab/lakip' | 'adminopd/lakip')
 */

helper('number');

$indikatorRows  = $indikatorRows ?? [];
$analisisMap    = $analisisMap ?? [];
$efisiensiRows  = $efisiensiRows ?? [];
$programOptions = $programOptions ?? [];
$scope          = $addendumScope ?? ['mode' => 'opd', 'tahun' => '', 'opdScope' => null, 'canWrite' => false, 'role' => '', 'alasan' => null];
$addendumBase   = $addendumBase ?? 'adminopd/lakip';

$canWrite   = (bool) ($scope['canWrite'] ?? false);
$tahunAddon = (string) ($scope['tahun'] ?? '');
$modeAddon  = (string) ($scope['mode'] ?? 'opd');
$opdAddon   = (int) ($scope['opdScope'] ?? 0);

// format_helper tidak ikut autoload — pakai pembungkus bercadangan
// (pola yang sama dipakai adminOpd/pk_renaksi/monev.php).
$rupiahAddon = static function ($nilai) {
    if ($nilai === null || $nilai === '') {
        return '-';
    }
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }

    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};

// Indikator unik per target — tabel analisis mengikuti indikator tahun aktif.
$daftarIndikator = [];
foreach ($indikatorRows as $r) {
    $tid = (int) ($r['target_id'] ?? 0);
    if ($tid <= 0 || isset($daftarIndikator[$tid])) {
        continue;
    }
    $daftarIndikator[$tid] = [
        'target_id' => $tid,
        'indikator' => (string) ($r['indikator_sasaran'] ?? '-'),
        'sasaran'   => (string) ($r['sasaran'] ?? ($r['sasaran_rpjmd'] ?? '')),
        'nama_opd'  => (string) ($r['nama_opd'] ?? ''),
    ];
}
?>

<?php // Halaman LAKIP sebelumnya belum menampilkan flashdata sama sekali,
      // padahal simpan/hapus di sini mengandalkannya. ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mt-4 mb-0">
        <i class="fas fa-circle-check me-1"></i><?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-4 mb-0">
        <i class="fas fa-circle-exclamation me-1"></i><?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show mt-4 mb-0">
        <i class="fas fa-circle-info me-1"></i><?= esc(session()->getFlashdata('info')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ============================================================
     CARD 2 — ANALISIS FAKTOR PENCAPAIAN KINERJA
     ============================================================ -->
<div class="bg-white rounded shadow p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="h5 fw-bold text-success mb-1">ANALISIS FAKTOR PENCAPAIAN KINERJA</h3>
            <p class="text-muted small mb-0">
                Tahun <?= esc($tahunAddon !== '' ? $tahunAddon : '-') ?>.
                Satu indikator boleh punya lebih dari satu baris analisis.
            </p>
        </div>
    </div>

    <?php if (!$canWrite && !empty($scope['alasan'])): ?>
        <div class="alert alert-info py-2 small mb-3"><i class="fas fa-info-circle me-1"></i><?= esc($scope['alasan']) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small align-middle">
            <thead class="table-success">
                <tr>
                    <th class="border p-2" style="width:4%;">NO</th>
                    <th class="border p-2" style="width:22%;">INDIKATOR</th>
                    <th class="border p-2" style="width:23%;">FAKTOR PENDUKUNG KEBERHASILAN/KEGAGALAN,<br>PENURUNAN/PENINGKATAN KINERJA</th>
                    <th class="border p-2" style="width:23%;">FAKTOR PENGHAMBAT</th>
                    <th class="border p-2" style="width:23%;">UPAYA UNTUK MENINGKATKAN PENCAPAIAN KINERJA</th>
                    <th class="border p-2" style="width:5%;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php $noA = 1; ?>
                <?php foreach ($daftarIndikator as $ind): ?>
                    <?php
                    $daftar = $analisisMap[$ind['target_id']] ?? [];
                    $jumlah = max(1, count($daftar));
                    $indJson = htmlspecialchars(json_encode([
                        'target_id' => $ind['target_id'],
                        'indikator' => $ind['indikator'],
                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>

                    <?php if (empty($daftar)): ?>
                        <tr>
                            <td><?= $noA++ ?></td>
                            <td class="text-start"><?= esc($ind['indikator']) ?></td>
                            <td colspan="3" class="text-muted fst-italic">Belum ada analisis faktor untuk indikator ini.</td>
                            <td>
                                <?php if ($canWrite): ?>
                                    <button type="button" class="btn btn-sm btn-primary js-analisis-add"
                                        data-json='<?= $indJson ?>' title="Tambah analisis">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar as $i => $a): ?>
                            <?php
                            $aId   = (int) ($a['id'] ?? 0);
                            $aJson = htmlspecialchars(json_encode([
                                'id'                => $aId,
                                'target_id'         => $ind['target_id'],
                                'indikator'         => $ind['indikator'],
                                'faktor_pendukung'  => (string) ($a['faktor_pendukung'] ?? ''),
                                'faktor_penghambat' => (string) ($a['faktor_penghambat'] ?? ''),
                                'upaya_peningkatan' => (string) ($a['upaya_peningkatan'] ?? ''),
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <?php if ($i === 0): ?>
                                    <td rowspan="<?= $jumlah ?>" class="align-middle"><?= $noA++ ?></td>
                                    <td rowspan="<?= $jumlah ?>" class="align-middle text-start"><?= esc($ind['indikator']) ?></td>
                                <?php endif; ?>
                                <td class="text-start"><?= nl2br(esc($a['faktor_pendukung'] ?? '')) ?: '<span class="text-muted">-</span>' ?></td>
                                <td class="text-start"><?= nl2br(esc($a['faktor_penghambat'] ?? '')) ?: '<span class="text-muted">-</span>' ?></td>
                                <td class="text-start"><?= nl2br(esc($a['upaya_peningkatan'] ?? '')) ?: '<span class="text-muted">-</span>' ?></td>
                                <td>
                                    <?php if ($canWrite): ?>
                                        <div class="d-flex flex-wrap justify-content-center gap-1 action-buttons">
                                            <?php if ($i === 0): ?>
                                                <button type="button" class="btn btn-sm btn-primary js-analisis-add"
                                                    data-json='<?= $indJson ?>' title="Tambah analisis">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($aId > 0): ?>
                                                <button type="button" class="btn btn-sm btn-warning js-analisis-edit"
                                                    data-json='<?= $aJson ?>' title="Edit analisis">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="submit" form="form-analisis-hapus-<?= $aId ?>"
                                                    class="btn btn-sm btn-danger" title="Hapus analisis"
                                                    onclick="return confirm('Hapus baris analisis ini?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php // Hapus lewat POST (bukan tautan) supaya tidak bisa dipicu dari luar. ?>
                                        <?php if ($aId > 0): ?>
                                        <form id="form-analisis-hapus-<?= $aId ?>" class="d-none" method="post"
                                            action="<?= base_url($addendumBase . '/analisis/delete/' . $aId) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tahun" value="<?= esc($tahunAddon, 'attr') ?>">
                                            <input type="hidden" name="mode" value="<?= esc($modeAddon, 'attr') ?>">
                                            <input type="hidden" name="opd_id" value="<?= $opdAddon ?>">
                                        </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (empty($daftarIndikator)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Belum ada indikator pada tahun ini, jadi belum ada yang bisa dianalisis.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     CARD 3 — EFISIENSI PROGRAM DAN ANGGARAN
     ============================================================ -->
<div class="bg-white rounded shadow p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="h5 fw-bold text-success mb-1">EFISIENSI PROGRAM DAN ANGGARAN</h3>
            <p class="text-muted small mb-0">
                Tahun <?= esc($tahunAddon !== '' ? $tahunAddon : '-') ?>.
                Pagu anggaran mengikuti Perjanjian Kinerja; realisasi &amp; efisiensi diisi manual.
            </p>
        </div>
        <?php if ($canWrite): ?>
            <div>
                <button type="button" class="btn btn-success btn-sm js-efisiensi-add"
                    <?= empty($programOptions) ? 'disabled' : '' ?>>
                    <i class="fas fa-plus me-1"></i> Tambah Program
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canWrite && empty($programOptions)): ?>
        <div class="alert alert-warning py-2 small mb-3">
            <i class="fas fa-triangle-exclamation me-1"></i>
            Belum ada program Perjanjian Kinerja tahun <?= esc($tahunAddon !== '' ? $tahunAddon : '-') ?> untuk unit ini,
            jadi belum ada yang bisa dipilih. Lengkapi dulu Program pada menu Perjanjian Kinerja.
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center small align-middle">
            <thead class="table-success">
                <tr>
                    <th class="border p-2" style="width:5%;">NO</th>
                    <th class="border p-2" style="width:43%;">NAMA PROGRAM</th>
                    <th class="border p-2" style="width:16%;">ANGGARAN</th>
                    <th class="border p-2" style="width:16%;">REALISASI</th>
                    <th class="border p-2" style="width:14%;">EFISIENSI</th>
                    <th class="border p-2" style="width:6%;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php $noE = 1; ?>
                <?php foreach ($efisiensiRows as $e): ?>
                    <?php
                    $eJson = htmlspecialchars(json_encode([
                        'id'         => (int) $e['id'],
                        'program_id' => (int) $e['program_id'],
                        'anggaran'   => (string) $e['anggaran'],
                        'realisasi'  => (string) $e['realisasi'],
                        'efisiensi'  => (string) $e['efisiensi'],
                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td><?= $noE++ ?></td>
                        <td class="text-start">
                            <?= esc($e['program_kegiatan'] ?? '-') ?>
                            <?php if (!empty($e['kode_program'])): ?>
                                <div class="text-muted" style="font-size:.72rem;">Kode: <?= esc($e['kode_program']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap"><?= esc($rupiahAddon($e['anggaran'])) ?></td>
                        <td class="text-end text-nowrap"><?= esc($rupiahAddon($e['realisasi'])) ?></td>
                        <td class="text-end text-nowrap"><?= esc($rupiahAddon($e['efisiensi'])) ?></td>
                        <td>
                            <?php if ($canWrite): ?>
                                <div class="d-flex flex-wrap justify-content-center gap-1 action-buttons">
                                    <button type="button" class="btn btn-sm btn-warning js-efisiensi-edit"
                                        data-json='<?= $eJson ?>' title="Edit efisiensi program">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="submit" form="form-efisiensi-hapus-<?= (int) $e['id'] ?>"
                                        class="btn btn-sm btn-danger" title="Hapus"
                                        onclick="return confirm('Hapus data efisiensi program ini?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <form id="form-efisiensi-hapus-<?= (int) $e['id'] ?>" class="d-none" method="post"
                                    action="<?= base_url($addendumBase . '/efisiensi/delete/' . (int) $e['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tahun" value="<?= esc($tahunAddon, 'attr') ?>">
                                    <input type="hidden" name="mode" value="<?= esc($modeAddon, 'attr') ?>">
                                    <input type="hidden" name="opd_id" value="<?= $opdAddon ?>">
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($efisiensiRows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Belum ada data efisiensi program pada tahun ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canWrite): ?>
    <!-- ============ MODAL: ANALISIS FAKTOR ============ -->
    <div class="modal fade" id="modal-analisis" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="post" action="<?= base_url($addendumBase . '/analisis/save') ?>" id="form-analisis">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="analisis-id" value="">
                    <input type="hidden" name="target_id" id="analisis-target-id" value="">
                    <input type="hidden" name="tahun" value="<?= esc($tahunAddon, 'attr') ?>">
                    <input type="hidden" name="mode" value="<?= esc($modeAddon, 'attr') ?>">
                    <input type="hidden" name="opd_id" value="<?= $opdAddon ?>">

                    <div class="modal-header">
                        <h5 class="modal-title" id="analisis-judul">Tambah Analisis Faktor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Indikator</label>
                            <input type="text" class="form-control bg-light" id="analisis-indikator" readonly>
                            <small class="text-muted">Indikator terpilih otomatis dan tidak dapat diubah dari sini.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="faktor_pendukung">
                                Faktor Pendukung Keberhasilan/Kegagalan, Penurunan/Peningkatan Kinerja
                            </label>
                            <textarea class="form-control" name="faktor_pendukung" id="faktor_pendukung" rows="3" maxlength="5000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="faktor_penghambat">Faktor Penghambat</label>
                            <textarea class="form-control" name="faktor_penghambat" id="faktor_penghambat" rows="3" maxlength="5000"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="upaya_peningkatan">Upaya untuk Meningkatkan Pencapaian Kinerja</label>
                            <textarea class="form-control" name="upaya_peningkatan" id="upaya_peningkatan" rows="3" maxlength="5000"></textarea>
                        </div>
                        <div class="text-muted small" id="analisis-pesan">
                            Minimal salah satu dari ketiga isian di atas harus diisi.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ MODAL: EFISIENSI PROGRAM ============ -->
    <div class="modal fade" id="modal-efisiensi" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="<?= base_url($addendumBase . '/efisiensi/save') ?>" id="form-efisiensi">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="efisiensi-id" value="">
                    <input type="hidden" name="mode" value="<?= esc($modeAddon, 'attr') ?>">
                    <input type="hidden" name="opd_id" value="<?= $opdAddon ?>">

                    <div class="modal-header">
                        <h5 class="modal-title" id="efisiensi-judul">Tambah Efisiensi Program</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tahun</label>
                            <?php // Tahun ikut filter halaman; dikirim readonly & tetap diverifikasi server. ?>
                            <input type="text" class="form-control bg-light" value="<?= esc($tahunAddon) ?>" readonly>
                            <input type="hidden" name="tahun" value="<?= esc($tahunAddon, 'attr') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="efisiensi-program">Nama Program</label>
                            <select class="form-select" name="program_id" id="efisiensi-program" required>
                                <option value="">&mdash; Pilih Program &mdash;</option>
                                <?php foreach ($programOptions as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" data-anggaran="<?= esc((string) $p['anggaran'], 'attr') ?>">
                                        <?= esc(($p['kode_program'] !== '' ? '[' . $p['kode_program'] . '] ' : '') . $p['program_kegiatan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hanya program Perjanjian Kinerja tahun <?= esc($tahunAddon) ?> milik unit ini.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="efisiensi-anggaran">Anggaran</label>
                            <input type="text" class="form-control bg-light fw-semibold" id="efisiensi-anggaran"
                                readonly tabindex="-1" value="-">
                            <?php // Sengaja TIDAK ada input name="anggaran": pagunya diambil ulang server dari DB. ?>
                            <small class="text-muted">Terisi otomatis dari program yang dipilih.</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="efisiensi-realisasi">Realisasi</label>
                                <input type="text" class="form-control js-rupiah" name="realisasi"
                                    id="efisiensi-realisasi" inputmode="numeric" placeholder="Rp 0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="efisiensi-efisiensi">Efisiensi</label>
                                <input type="text" class="form-control js-rupiah" name="efisiensi"
                                    id="efisiensi-efisiensi" inputmode="numeric" placeholder="Rp 0">
                            </div>
                        </div>
                        <div class="text-muted small mt-2" id="efisiensi-sisa"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        /**
         * Modal & format Rupiah untuk dua tabel tambahan LAKIP.
         *
         * Daftar program beserta pagunya sudah ikut ter-render di <option
         * data-anggaran>, jadi tidak perlu AJAX terpisah. Nilai anggaran di
         * sini murni tampilan — saat disimpan, server mengambil ulang pagunya
         * dari database (lihat LakipAddendumTrait::efisiensiSave()).
         */
        (function () {
            function rupiah(angka) {
                var n = Number(angka);
                if (!isFinite(n)) return '-';
                return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
            }

            /** "Rp 150.000.000" -> "150000000" (hanya digit). */
            function keAngka(teks) {
                var digit = String(teks == null ? '' : teks).replace(/[^\d]/g, '');
                return digit === '' ? '' : digit;
            }

            function pasangFormatRupiah(el) {
                if (!el) return;
                function terapkan() {
                    var digit = keAngka(el.value);
                    el.value = digit === '' ? '' : rupiah(digit);
                    hitungSisa();
                }
                el.addEventListener('input', terapkan);
                el.addEventListener('blur', terapkan);
            }

            var selProgram = document.getElementById('efisiensi-program');
            var elAnggaran = document.getElementById('efisiensi-anggaran');
            var elRealisasi = document.getElementById('efisiensi-realisasi');
            var elEfisiensi = document.getElementById('efisiensi-efisiensi');
            var elSisa = document.getElementById('efisiensi-sisa');

            /** Info tambahan saja — TIDAK mengubah nilai efisiensi yang diisi manual. */
            function hitungSisa() {
                if (!elSisa || !selProgram) return;
                var opt = selProgram.options[selProgram.selectedIndex];
                var anggaran = opt ? Number(opt.getAttribute('data-anggaran') || 0) : 0;
                var realisasi = Number(keAngka(elRealisasi ? elRealisasi.value : '') || 0);
                if (!anggaran) { elSisa.textContent = ''; return; }
                elSisa.textContent = 'Sisa anggaran berdasarkan perhitungan sistem: '
                    + rupiah(anggaran - realisasi) + ' (informasi saja, nilai Efisiensi tetap diisi manual).';
            }

            function tampilkanAnggaran() {
                if (!selProgram || !elAnggaran) return;
                var opt = selProgram.options[selProgram.selectedIndex];
                var nilai = opt ? opt.getAttribute('data-anggaran') : null;
                elAnggaran.value = (nilai === null || nilai === '' || Number(nilai) === 0) ? '-' : rupiah(nilai);
                hitungSisa();
            }

            if (selProgram) selProgram.addEventListener('change', tampilkanAnggaran);
            pasangFormatRupiah(elRealisasi);
            pasangFormatRupiah(elEfisiensi);

            function bukaModal(id) {
                var el = document.getElementById(id);
                if (el && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                }
            }

            function bacaJson(btn) {
                try { return JSON.parse(btn.getAttribute('data-json') || '{}'); } catch (e) { return {}; }
            }

            document.addEventListener('click', function (ev) {
                var tambahA = ev.target.closest('.js-analisis-add');
                if (tambahA) {
                    var d = bacaJson(tambahA);
                    document.getElementById('analisis-judul').textContent = 'Tambah Analisis Faktor';
                    document.getElementById('analisis-id').value = '';
                    document.getElementById('analisis-target-id').value = d.target_id || '';
                    document.getElementById('analisis-indikator').value = d.indikator || '';
                    document.getElementById('faktor_pendukung').value = '';
                    document.getElementById('faktor_penghambat').value = '';
                    document.getElementById('upaya_peningkatan').value = '';
                    bukaModal('modal-analisis');
                    return;
                }

                var editA = ev.target.closest('.js-analisis-edit');
                if (editA) {
                    var e = bacaJson(editA);
                    document.getElementById('analisis-judul').textContent = 'Edit Analisis Faktor';
                    document.getElementById('analisis-id').value = e.id || '';
                    document.getElementById('analisis-target-id').value = e.target_id || '';
                    document.getElementById('analisis-indikator').value = e.indikator || '';
                    document.getElementById('faktor_pendukung').value = e.faktor_pendukung || '';
                    document.getElementById('faktor_penghambat').value = e.faktor_penghambat || '';
                    document.getElementById('upaya_peningkatan').value = e.upaya_peningkatan || '';
                    bukaModal('modal-analisis');
                    return;
                }

                var tambahE = ev.target.closest('.js-efisiensi-add');
                if (tambahE) {
                    document.getElementById('efisiensi-judul').textContent = 'Tambah Efisiensi Program';
                    document.getElementById('efisiensi-id').value = '';
                    if (selProgram) selProgram.value = '';
                    if (elRealisasi) elRealisasi.value = '';
                    if (elEfisiensi) elEfisiensi.value = '';
                    tampilkanAnggaran();
                    bukaModal('modal-efisiensi');
                    return;
                }

                var editE = ev.target.closest('.js-efisiensi-edit');
                if (editE) {
                    var f = bacaJson(editE);
                    document.getElementById('efisiensi-judul').textContent = 'Edit Efisiensi Program';
                    document.getElementById('efisiensi-id').value = f.id || '';
                    if (selProgram) selProgram.value = f.program_id || '';
                    if (elRealisasi) elRealisasi.value = f.realisasi ? rupiah(Math.round(Number(f.realisasi))) : '';
                    if (elEfisiensi) elEfisiensi.value = f.efisiensi ? rupiah(Math.round(Number(f.efisiensi))) : '';
                    tampilkanAnggaran();
                    bukaModal('modal-efisiensi');
                }
            });

            // Validasi sisi depan: minimal satu isian analisis terisi.
            var formAnalisis = document.getElementById('form-analisis');
            if (formAnalisis) {
                formAnalisis.addEventListener('submit', function (ev) {
                    var isi = ['faktor_pendukung', 'faktor_penghambat', 'upaya_peningkatan']
                        .map(function (id) { return (document.getElementById(id).value || '').trim(); })
                        .filter(function (v) { return v !== ''; });
                    if (isi.length === 0) {
                        ev.preventDefault();
                        var pesan = document.getElementById('analisis-pesan');
                        pesan.textContent = 'Isi minimal salah satu: Faktor Pendukung, Faktor Penghambat, atau Upaya Peningkatan.';
                        pesan.classList.remove('text-muted');
                        pesan.classList.add('text-danger');
                    }
                });
            }

            // Angka dikirim tanpa "Rp" & titik supaya server menerima angka murni.
            var formEfisiensi = document.getElementById('form-efisiensi');
            if (formEfisiensi) {
                formEfisiensi.addEventListener('submit', function () {
                    if (elRealisasi) elRealisasi.value = keAngka(elRealisasi.value) || '0';
                    if (elEfisiensi) elEfisiensi.value = keAngka(elEfisiensi.value) || '0';
                });
            }
        })();
    </script>
<?php endif; ?>

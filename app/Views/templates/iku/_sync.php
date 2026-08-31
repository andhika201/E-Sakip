<?php

/**
 * Pratinjau sync IKU dari RPJMD / Renstra — dipakai bersama admin kabupaten & OPD.
 *
 * Yang dikirim ke server hanya ID sumber lewat `pilih[sasaran_id][indikator_id]`;
 * isi sasaran/indikator dibaca ulang dari DB oleh model.
 *
 * @var array  $kandidat       sasaran sumber + indikator + target
 * @var array  $daftar_periode opsi periode sumber
 * @var array  $periode        periode terpilih (key, period, years, tahun_mulai, tahun_akhir)
 * @var array  $years          tahun periode terpilih
 * @var string $sumber_label   'RPJMD' | 'Renstra'
 * @var string $action_url     URL submit
 * @var string $back_url       URL kembali
 * @var string $filter_url     URL untuk ganti periode
 */
$kandidat       = $kandidat ?? [];
$daftar_periode = $daftar_periode ?? [];
$periode        = $periode ?? [];
$years          = !empty($years) ? $years : [];
$sumber_label   = $sumber_label ?? 'RPJMD';
$action_url     = $action_url ?? '';
$back_url       = $back_url ?? '';
$filter_url     = $filter_url ?? '';

$totalIndikator = 0;
$totalBaru      = 0;
$totalBerubah   = 0;
foreach ($kandidat as $s) {
    $totalIndikator += count($s['indikator'] ?? []);
    $totalBaru      += (int) ($s['jumlah_baru'] ?? 0);
    $totalBerubah   += (int) ($s['jumlah_berubah'] ?? 0);
}
?>

<?php
/* Versi sumber hanya ditawarkan bila memang ada versi yang sudah ditetapkan
   DAN berisi. Untuk RPJMD (yang belum punya pilihan versi) daftarnya kosong
   dan seluruh blok ini tidak muncul sama sekali. */
$versiTersedia = $versi_tersedia ?? [];
$versiDipilih  = $versi_dipilih ?? null;

/* Ke mana hasil sync bermuara. Ini bukan detail teknis: selisihnya adalah
   antara "IKU langsung berubah" dan "IKU baru berubah setelah disahkan",
   dan pengisi form berhak tahu yang mana sebelum menekan tombol. */
$keRevisi      = ! empty($ke_revisi);
$revisiBerlaku = $revisi_berlaku ?? null;
$draftTersedia = $draft_tersedia ?? [];
?>

<form method="get" action="<?= esc($filter_url, 'attr') ?>" class="row g-2 mb-3 align-items-end">
    <div class="col-md-5">
        <label class="form-label fw-semibold text-secondary mb-1">Periode <?= esc($sumber_label) ?></label>
        <select name="periode" class="form-select" onchange="this.form.submit()">
            <?php foreach ($daftar_periode as $key => $p): ?>
                <option value="<?= esc($key) ?>" <?= (($periode['key'] ?? '') === $key) ? 'selected' : '' ?>>
                    <?= esc($p['period']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (! empty($versiTersedia)): ?>
        <div class="col-md-7">
            <label class="form-label fw-semibold text-secondary mb-1">
                Versi <?= esc($sumber_label) ?> yang disalin
            </label>
            <select name="renstra_versi" class="form-select" onchange="this.form.submit()">
                <option value="">Kondisi berjalan (terkini)</option>
                <?php foreach ($versiTersedia as $v): ?>
                    <option value="<?= (int) $v['id'] ?>"
                        <?= $versiDipilih !== null && (int) $versiDipilih['id'] === (int) $v['id'] ? 'selected' : '' ?>>
                        <?php /* `label_tampil` sudah memuat "V{n} — " bila memang perlu.
                                 Menambahkannya lagi di sini yang dulu mencetak
                                 "V2 — V2 — RENSTRA ...". */ ?>
                        <?= esc($v['label_tampil'] ?? $v['label']) ?>
                        (<?= (int) $v['jumlah_sasaran'] ?> sasaran, berlaku <?= esc($v['effective_from']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">
                IKU disalin <strong>sekali</strong> dari sumber ini lalu hidup sendiri —
                Renstra yang berubah kemudian tidak ikut mengubah IKU.
            </div>
        </div>
    <?php endif; ?>
</form>

<div class="alert alert-info d-flex flex-wrap gap-3 align-items-center">
    <div>
        <i class="fas fa-info-circle me-1"></i>
        Data di bawah diambil dari <strong><?= esc($sumber_label) ?></strong> periode
        <strong><?= esc($periode['period'] ?? '-') ?></strong>,
        <?php if ($versiDipilih !== null): ?>
            versi <strong><?= esc($versiDipilih['label_tampil'] ?? $versiDipilih['label']) ?></strong>.
            Isinya tetap sebagaimana saat versi itu ditetapkan.
        <?php else: ?>
            <strong>kondisi berjalan</strong>. Baris yang sudah dipensiunkan tidak ikut ditawarkan.
        <?php endif; ?>
    </div>
    <div class="ms-auto small">
        <span class="badge bg-secondary"><?= $totalIndikator ?> indikator tersedia</span>
        <span class="badge bg-success"><?= $totalBaru ?> belum ada di IKU</span>
        <?php if ($totalBerubah > 0): ?>
            <span class="badge bg-warning text-dark"><?= $totalBerubah ?> berbeda dari IKU</span>
        <?php endif; ?>
    </div>
</div>

<?php
$tanpaPadanan = $tanpa_padanan ?? [];

/* MODE GANTI — pratinjau.
   `dipertahankan` adalah inti peringatannya: indikator yang tidak ada di
   sumber TETAPI sudah dipakai di tempat lain. Membuangnya bukan menghapus
   satu baris, melainkan memutus pekerjaan orang lain — sasaran Eselon III/IV
   di bawahnya, capaian LAKIP yang sudah diinput, atau dokumen revisi yang
   sudah disahkan. Karena itu baris seperti ini TIDAK PERNAH dibuang, dan
   alasannya disebut satu per satu. */
$gantiPratinjau = $ganti_pratinjau ?? ['dibuang_indikator' => 0, 'dipertahankan' => []];
$akanDibuang    = (int) ($gantiPratinjau['dibuang_indikator'] ?? 0);
$dipertahankan  = $gantiPratinjau['dipertahankan'] ?? [];

// Satu gerbang untuk panel Ganti DAN untuk kalimat yang menyebutnya. Sebelum
// ini kalimatnya berkata "kecuali Anda mencentang Ganti di bawah" walau
// panelnya tidak dirender — pengguna mencari centang yang tidak ada.
$gantiTampil = ! ($keRevisi ?? false) && ($akanDibuang > 0 || $dipertahankan !== []);
?>

<?php if ($tanpaPadanan !== []): ?>
    <div class="alert alert-light border">
        <i class="fas fa-circle-info me-1"></i>
        <strong><?= count($tanpaPadanan) ?> indikator IKU tidak ada pada sumber ini.</strong>
        <div class="small text-muted mt-1">
            Ini <strong>bukan kesalahan</strong>. IKU memang boleh memuat indikator yang tidak
            ada di <?= esc($sumber_label) ?> &mdash; itulah gunanya ia dokumen tersendiri.
            Daftar ini hanya supaya Anda tahu mana yang berdiri sendiri, terutama setelah
            berganti versi sumber.
            <?php if ($gantiTampil): ?>
                Tidak ada yang dihapus, <em>kecuali</em> Anda mencentang
                <strong>Ganti</strong> di bawah.
            <?php else: ?>
                <strong>Tidak ada yang dihapus.</strong>
            <?php endif; ?>
        </div>
        <ul class="small mt-2 mb-0">
            <?php foreach ($tanpaPadanan as $t): ?>
                <li>
                    <?= esc($t['indikator']) ?>
                    <span class="text-muted">(<?= esc($t['sasaran']) ?>)</span>
                    <?php if (! empty($t['dari_sumber'])): ?>
                        <span class="badge bg-light text-dark border ms-1"
                              title="Dulu disalin dari dokumen perencanaan, tapi tidak ada pada sumber yang dipilih sekarang">
                            pernah dari <?= esc($sumber_label) ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php /* PERINGATAN TURUNAN — hanya muncul bila memang ada yang sudah dipakai,
         dan hanya di jalur yang benar-benar bisa membuang (bukan draft revisi):
         menakut-nakuti tentang penghapusan yang tidak akan terjadi sama saja
         dengan berbohong ke arah sebaliknya. */ ?>
<?php if (! $keRevisi && $dipertahankan !== []): ?>
    <div class="alert alert-warning">
        <i class="fas fa-triangle-exclamation me-1"></i>
        <strong><?= count($dipertahankan) ?> indikator di antaranya sudah dipakai di tempat lain.</strong>
        <div class="small mt-1">
            Kalau Anda memilih <strong>Ganti</strong>, indikator berikut <strong>tetap tidak akan
            dibuang</strong> &mdash; yang ikut hilang bukan barisnya saja, melainkan pekerjaan
            yang sudah menempel padanya.
        </div>
        <ul class="small mt-2 mb-0">
            <?php foreach ($dipertahankan as $d): ?>
                <li>
                    <?= esc($d['indikator']) ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1">
                        <?= esc($d['alasan']) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="small text-muted mt-2">
            Untuk benar-benar membuangnya, lepaskan dulu kaitannya &mdash; hapus baris
            cascading di bawahnya, atau kosongkan capaian LAKIP-nya.
        </div>
    </div>
<?php endif; ?>

<?php if ($keRevisi): ?>
    <div class="alert alert-warning">
        <i class="fas fa-shield-halved me-1"></i>
        <strong>IKU periode ini sudah punya revisi yang berlaku<?= ! empty($revisiBerlaku['nama'])
            ? ' (' . esc($revisiBerlaku['nama']) . ')' : '' ?>.</strong>
        <div class="small mt-1">
            Karena itu hasil sync <strong>tidak langsung masuk ke IKU berjalan</strong> —
            ia masuk ke sebuah <strong>draft revisi</strong>, lalu ikut antre disahkan
            Admin Kabupaten seperti perubahan lainnya. Menambah indikator langsung ke
            dokumen yang sudah resmi berarti mengubahnya tanpa sepengetahuan siapa pun.
        </div>

        <?php if (empty($draftTersedia)): ?>
            <div class="mt-2">
                Belum ada draft revisi yang bisa menampungnya.
                <a href="<?= esc(rtrim($back_url, '/') . '/revisi', 'attr') ?>">Buat revisi lebih dulu</a>.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (empty($kandidat)): ?>

    <div class="text-center py-5 my-4">
        <i class="bi bi-inbox text-secondary" style="font-size: 3rem;"></i>
        <h5 class="mt-3 text-secondary">
            Tidak ada sasaran <?= esc($sumber_label) ?> pada periode ini.
        </h5>
        <a href="<?= esc($back_url, 'attr') ?>" class="btn btn-secondary mt-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

<?php else: ?>

    <form method="post" action="<?= esc($action_url, 'attr') ?>" id="sync-form">
        <?= csrf_field() ?>
        <input type="hidden" name="periode" value="<?= esc($periode['key'] ?? '', 'attr') ?>">
        <?php /* Versi ikut terkirim supaya yang tersimpan benar-benar berasal
                 dari sumber yang tampil di layar, bukan dari sumber lain yang
                 kebetulan menjadi bawaan saat POST diproses. */ ?>
        <input type="hidden" name="renstra_versi"
               value="<?= $versiDipilih !== null ? (int) $versiDipilih['id'] : '' ?>">

        <?php if ($keRevisi && ! empty($draftTersedia)): ?>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary mb-1">
                    Masukkan ke draft revisi <span class="text-danger">*</span>
                </label>
                <select name="revisi_tujuan" class="form-select" required>
                    <option value="">-- Pilih draft --</option>
                    <?php foreach ($draftTersedia as $d): ?>
                        <option value="<?= (int) $d['id'] ?>">
                            <?= esc($d['nomor'] ?? '') ?><?= ! empty($d['nama']) ? ' — ' . esc($d['nama']) : '' ?>
                            (berlaku mulai <?= (int) $d['berlaku_mulai_tahun'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php /* Tidak ada pemilihan per indikator sama sekali. Yang dipilih
                 pemakai adalah SUMBER-nya (periode + versi) di atas; tabel ini
                 pratinjau atas apa yang akan terjadi bila tombol ditekan. */ ?>
        <div class="alert alert-success py-2 px-3 small">
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <i class="fas fa-check-double mt-1"></i>
                <div>
                    <strong>Seluruh isi sumber ini akan disalin ke IKU.</strong>
                    <div class="mt-1">
                        <?= $totalBaru ?> indikator <strong>ditambahkan</strong><?php if ($totalBerubah > 0): ?>,
                        <?= $totalBerubah ?> yang <strong>berbeda</strong> diambil ulang nilainya dari sumber
                        (teks, satuan, baseline, dan target <strong>tertimpa</strong>)<?php endif; ?>.
                        Yang sudah sama dilewati.
                    </div>
                    <?php if ($totalBerubah > 0): ?>
                        <div class="mt-1 text-muted">
                            Definisi operasional, rumusan perhitungan, sumber data, dan penanggung jawab
                            yang Anda ketik di IKU <strong>tidak ikut tertimpa</strong> &mdash; keempatnya
                            tidak punya padanan di <?= esc($sumber_label) ?>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="table-responsive table-wrap">
            <table class="table table-bordered table-striped align-middle small iku-table" data-no-paginate>
                <thead class="table-success text-dark">
                    <tr class="text-center">
                        <th>Sasaran <?= esc($sumber_label) ?></th>
                        <th>Indikator</th>
                        <th>Satuan</th>
                        <th colspan="<?= max(1, count($years)) ?>">Target per Tahun</th>
                        <th>Status di IKU</th>
                    </tr>
                    <tr class="text-center">
                        <th colspan="3"></th>
                        <?php if (empty($years)): ?>
                            <th>-</th>
                        <?php else: ?>
                            <?php foreach ($years as $tahun): ?>
                                <th><?= esc($tahun) ?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($kandidat as $sasaran): ?>
                        <?php
                        $indikators   = $sasaran['indikator'] ?? [];
                        $barisSasaran = max(1, count($indikators));
                        $daftarBaris  = !empty($indikators) ? $indikators : [null];
                        $barisPertama = true;
                        ?>

                        <?php foreach ($daftarBaris as $ind): ?>
                            <?php /* Dipakai kolom "Status di IKU" di ujung baris.
                                     Baris sasaran tanpa indikator ($ind === null)
                                     tidak punya status apa pun. */ ?>
                            <?php $bandingInd = $ind === null
                                ? null
                                : ($ind['banding'] ?? (! empty($ind['sudah_ada']) ? 'sama' : 'baru')); ?>
                            <tr>
                                <?php if ($barisPertama): ?>
                                    <td rowspan="<?= $barisSasaran ?>" class="text-start align-middle">
                                        <?= esc($sasaran['sasaran'] ?? '-') ?>
                                        <?php if (!empty($sasaran['induk'])): ?>
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-level-up-alt fa-rotate-90 me-1"></i>
                                                <?= esc($sasaran['induk']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php $barisPertama = false; ?>
                                <?php endif; ?>

                                <?php if ($ind === null): ?>
                                    <td colspan="<?= 3 + max(1, count($years)) ?>" class="text-center text-muted">
                                        Sasaran ini belum punya indikator di <?= esc($sumber_label) ?>.
                                    </td>
                                <?php else: ?>
                                    <td class="text-start"><?= esc($ind['indikator'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc(($ind['satuan_nama'] ?? '') !== '' ? $ind['satuan_nama'] : '-') ?></td>

                                    <?php if (empty($years)): ?>
                                        <td class="text-center">-</td>
                                    <?php else: ?>
                                        <?php foreach ($years as $tahun): ?>
                                            <?php
                                            $nilai = $ind['target'][(int) $tahun] ?? null;
                                            $geser = $ind['selisih']['target ' . (int) $tahun] ?? null;
                                            ?>
                                            <td class="text-center <?= $geser !== null ? 'table-warning' : '' ?>">
                                                <?= esc(($nilai === null || $nilai === '') ? '-' : $nilai) ?>
                                                <?php if ($geser !== null): ?>
                                                    <div class="text-muted" style="font-size:.75em">
                                                        IKU: <?= esc($geser['iku'] === '' ? '-' : $geser['iku']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <td class="text-center">
                                        <?php if ($bandingInd === 'baru'): ?>
                                            <span class="badge bg-success">Baru</span>
                                        <?php elseif ($bandingInd === 'berubah'): ?>
                                            <span class="badge bg-warning text-dark">Berubah</span>
                                            <?php
                                            $bukanTarget = [];

                                            foreach ($ind['selisih'] ?? [] as $medan => $d) {
                                                if (strpos($medan, 'target ') !== 0) {
                                                    $bukanTarget[$medan] = $d;
                                                }
                                            }
                                            ?>
                                            <?php if ($bukanTarget !== []): ?>
                                                <div class="text-muted mt-1" style="font-size:.75em">
                                                    <?php foreach ($bukanTarget as $medan => $d): ?>
                                                        <div>
                                                            <?= esc($medan) ?>:
                                                            <?= esc($d['iku'] === '' ? '-' : $d['iku']) ?>
                                                            &rarr; <strong><?= esc($d['sumber'] === '' ? '-' : $d['sumber']) ?></strong>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">Sama</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php /* MODE GANTI.
                 Hanya ditawarkan bila memang ADA yang bisa dibuang — tanpa itu
                 centang ini cuma pilihan yang tidak melakukan apa-apa, dan
                 pilihan semacam itu justru mengundang klik sembarangan.

                 TIDAK ditawarkan bila hasil sync bermuara ke DRAFT REVISI.
                 Di jalur itu IKU berjalan tidak disentuh sama sekali — yang
                 diisi adalah arsip revisi — sehingga "membuang isi IKU" tidak
                 punya arti di sana. Menampilkannya berarti menawarkan tombol
                 yang diam-diam tidak mengerjakan apa pun. */ ?>
        <?php if ($gantiTampil): ?>
            <div class="border rounded p-3 mt-4 bg-light">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ganti" value="1" id="sync-ganti">
                    <label class="form-check-label" for="sync-ganti">
                        <strong>Ganti</strong> &mdash; samakan IKU dengan <?= esc($sumber_label) ?>,
                        buang yang tidak ada di sana.
                    </label>
                </div>
                <div class="small text-muted mt-2">
                    Tanpa centang ini, sync hanya <strong>menambah dan memperbarui</strong>;
                    tidak ada yang dihapus.
                    <?php if ($akanDibuang > 0): ?>
                        Dengan centang ini, <strong><?= $akanDibuang ?> indikator</strong> yang tidak
                        ada di <?= esc($sumber_label) ?> akan dibuang beserta targetnya.
                    <?php endif; ?>
                    <?php if ($dipertahankan !== []): ?>
                        <strong><?= count($dipertahankan) ?></strong> lainnya tetap dipertahankan
                        karena sudah dipakai &mdash; lihat peringatan di atas.
                    <?php endif; ?>
                    Definisi, rumusan, sumber data, dan penanggung jawab pada indikator yang
                    <em>tetap ada</em> tidak ikut tertimpa.
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mt-4">
            <a href="<?= esc($back_url, 'attr') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-download me-1"></i> Salin Seluruhnya ke IKU
            </button>
        </div>
    </form>

    <?php if (! $keRevisi && ($akanDibuang > 0 || $dipertahankan !== [])): ?>
        <script>
            // Menghapus tidak boleh terjadi karena salah klik.
            document.getElementById('sync-form')?.addEventListener('submit', function (e) {
                var ganti = document.getElementById('sync-ganti');
                if (!ganti || !ganti.checked) return;

                var jml = <?= (int) $akanDibuang ?>;
                if (!confirm('Mode Ganti aktif.\n\n' + jml + ' indikator IKU yang tidak ada di '
                    + <?= json_encode((string) $sumber_label) ?>
                    + ' akan DIBUANG beserta targetnya.\n'
                    + 'Indikator yang sudah dipakai (cascading / LAKIP / arsip revisi) tetap dipertahankan.\n\n'
                    + 'Lanjutkan?')) {
                    e.preventDefault();
                }
            });
        </script>
    <?php endif; ?>


<?php endif; ?>

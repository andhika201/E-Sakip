<?php
/**
 * Pemilik Kinerja (sampai Pelaksana) — lihat AdminOpd\PemilikKinerjaController.
 *
 * Kerangka pohon dirender di sini (server); chip pemilik, nilai indikator,
 * status simpul dan angka cakupan diisi/diperbarui oleh
 * public/assets/js/adminopd/pemilik_kinerja.js dari data JSON #pmk-data,
 * supaya satu sumber templat dipakai baik saat memuat maupun sesudah simpan.
 */
$lingkup   = $lingkup ?? ['opdId' => null, 'bolehPilih' => false, 'daftarOpd' => [], 'alasan' => null];
$bolehUbah = (bool) ($bolehUbah ?? false);
$urlDasar  = $urlDasar ?? base_url('adminopd/pemilik-kinerja');
$areaOpd   = ($area ?? 'adminopd') === 'adminopd';   // tautan ke menu Cascading OPD hanya di area OPD
?>
<?= $this->include('templates/shell_atas') ?>

<div class="pmk" id="pmk-akar">
    <div class="pmk-head">
        <div class="pmk-ikon"><i class="fas fa-sitemap"></i></div>
        <div>
            <h2>Pemilik Kinerja (sampai Pelaksana)</h2>
            <p>Setiap simpul pohon kinerja punya pemilik, indikator bersatuan, dan target tahunan &mdash; dasar SKP pegawai di eKin.
                <?php if (! $bolehUbah): ?><span class="badge rounded-pill text-bg-light border ms-1"><i class="fas fa-eye me-1"></i>Hanya baca</span><?php endif; ?></p>
        </div>
    </div>

<?php if (! ($siap ?? false)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-triangle-exclamation me-1"></i>
        Tabel pemilik kinerja belum tersedia di basis data ini. Minta administrator menjalankan pembaruan
        <code>db/update_2026-09-26_ikp_kinerja.sql</code> (atau <code>php spark migrate</code>).
    </div>

<?php elseif ($lingkup['opdId'] === null): ?>
    <?php if ($lingkup['bolehPilih']): ?>
        <div class="pmk-filter">
            <div class="tb-label"><i class="fas fa-building me-1"></i>Pilih Perangkat Daerah</div>
            <form method="get" action="<?= $urlDasar ?>" class="pmk-filter-baris">
                <div class="pmk-filter-opd">
                    <select name="opd_id" class="form-select" onchange="this.form.submit()" aria-label="Perangkat daerah">
                        <option value="">&mdash; Pilih perangkat daerah &mdash;</option>
                        <?php foreach ($lingkup['daftarOpd'] as $o): ?>
                            <option value="<?= (int) $o['id'] ?>"><?= esc($o['nama_opd']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-success"><i class="fas fa-arrow-right"></i> Tampilkan</button>
            </form>
        </div>
        <div class="pmk-kosong">
            <div class="ikon"><i class="fas fa-building-columns"></i></div>
            <h5>Pilih perangkat daerah</h5>
            <p class="mb-0">Pilih perangkat daerah yang pohon kinerjanya ingin dilihat.</p>
        </div>
    <?php else: ?>
        <div class="alert alert-danger"><?= esc($lingkup['alasan'] ?? 'Perangkat daerah tidak diketahui.') ?></div>
    <?php endif; ?>

<?php else: ?>
<?php
    $tahun      = (int) $tahun;
    $pohon      = $pohon ?? ['es2' => [], 'simpul' => [], 'indikator' => []];
    $simpulAll  = $pohon['simpul'];
    $indAll     = $pohon['indikator'];
    $jumlahSimpul = count($simpulAll);
    $st         = $statistik;
    $namaKat    = ['struktural' => 'Struktural', 'fungsional' => 'Fungsional', 'pelaksana' => 'Pelaksana', 'lainnya' => 'Tanpa kode jabatan'];
    $katJumlah  = ['struktural' => 0, 'fungsional' => 0, 'pelaksana' => 0, 'lainnya' => 0];
    $katTanpa   = $katJumlah;
    foreach ($roster['pegawai'] as $p) {
        $k = isset($katJumlah[$p['kategori']]) ? $p['kategori'] : 'lainnya';
        $katJumlah[$k]++;
        if ($p['peran'] === 0) {
            $katTanpa[$k]++;
        }
    }
    $persen = static fn (int $a, int $b): int => $b > 0 ? (int) round($a * 100 / $b) : 0;
    $tot = ['simpul' => 0, 'berpemilik' => 0, 'indikator' => 0, 'indikatorLengkap' => 0];
    foreach ($st['level'] as $lv) {
        foreach ($tot as $k => $v) {
            $tot[$k] += $lv[$k];
        }
    }

    // Jumlah simpul turunan (untuk label "n simpul di bawah").
    $turunan = [];
    $hitungTurunan = function (int $id) use (&$hitungTurunan, &$turunan, $simpulAll): int {
        if (isset($turunan[$id])) {
            return $turunan[$id];
        }
        $n = 0;
        foreach ($simpulAll[$id]['anak'] as $ids) {
            foreach ($ids as $a) {
                $n += 1 + $hitungTurunan($a);
            }
        }

        return $turunan[$id] = $n;
    };

    // Satu simpul (Eselon III / IV / Pelaksana) beserta turunannya.
    $renderSimpul = function (int $id) use (&$renderSimpul, $hitungTurunan, $simpulAll, $indAll, $label, $bolehUbah): string {
        $s      = $simpulAll[$id];
        $nTurun = $hitungTurunan($id);
        $banyakInd = count($s['indikator']) > 1;
        ob_start(); ?>
        <div class="pmk-simpul" data-simpul-id="<?= $id ?>" data-level="<?= esc($s['level'], 'attr') ?>">
            <div class="pmk-kartu">
                <div class="pmk-kepala">
                    <?php if ($nTurun > 0): ?>
                        <button type="button" class="pmk-lipat" data-aksi="lipat" aria-expanded="true" title="Lipat/buka turunan">
                            <i class="fas fa-chevron-down"></i><span class="visually-hidden">Lipat/buka</span>
                        </button>
                    <?php endif; ?>
                    <span class="pmk-level"><?= esc($label[$s['level']]) ?></span>
                    <span class="pmk-status" data-status-simpul="<?= $id ?>"></span>
                    <?php if ($nTurun > 0): ?>
                        <span class="pmk-turunan"><?= $nTurun ?> simpul di bawahnya</span>
                    <?php endif; ?>
                </div>
                <div class="pmk-sasaran"><?= esc($s['sasaran']) ?></div>
                <div class="pmk-pemilik-wadah">
                    <span class="pmk-label">Pemilik</span>
                    <span class="pmk-pemilik" data-pemilik-simpul="<?= $id ?>"></span>
                    <?php if ($bolehUbah): ?>
                        <button type="button" class="pmk-tambah" data-aksi="tambah-pemilik"><i class="fas fa-plus"></i> Tambah pemilik</button>
                    <?php endif; ?>
                </div>
                <?php if ($s['indikator'] === []): ?>
                    <div class="pmk-kosong-kecil"><i class="fas fa-circle-info me-1"></i>Simpul ini belum punya indikator. Tambahkan lewat menu Cascading.</div>
                <?php else: ?>
                    <ul class="pmk-ind-daftar">
                        <?php foreach ($s['indikator'] as $iid): ?>
                            <li class="pmk-ind" data-indikator-id="<?= (int) $iid ?>" data-lengkap="0">
                                <div class="pmk-ind-nama"><i class="fas fa-circle"></i><?= esc($indAll[$iid]['nama']) ?></div>
                                <div class="pmk-ind-nilai"></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php if ($nTurun > 0): ?>
                <div class="pmk-anak">
                    <?php foreach ($s['indikator'] as $iid): ?>
                        <?php if (empty($s['anak'][$iid])) { continue; } ?>
                        <div class="pmk-grup">
                            <?php if ($banyakInd): ?>
                                <div class="pmk-grup-kepala"><i class="fas fa-turn-up fa-rotate-90 ikon"></i><span>Mendukung indikator:</span><span class="nm"><?= esc($indAll[$iid]['nama']) ?></span></div>
                            <?php endif; ?>
                            <?php foreach ($s['anak'][$iid] as $anak): ?>
                                <?= $renderSimpul((int) $anak) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php return (string) ob_get_clean();
    };

    $dataJs = [
        'meta' => [
            'tahun'       => $tahun,
            'opdId'       => (int) $opd['id'],
            'bolehUbah'   => $bolehUbah,
            'bolehHapus'  => (bool) ($bolehHapus ?? false),
            'jumlahSimpul' => $jumlahSimpul,
            'label'       => $label,
            'metode'      => $metodeOpsi,
            'metodeSingkat' => $metodeSingkat,
            'kategoriIkp' => $kategoriIkp,
            'url'         => [
                'pegawai'   => $urlDasar . '/pegawai',
                'simpan'    => $urlDasar . '/save',
                'hapus'     => $urlDasar . '/delete/',
                'indikator' => $urlDasar . '/indikator',
            ],
        ],
        'simpul'      => array_map(static fn (array $s): array => [
            'id'        => $s['id'],
            'level'     => $s['level'],
            'sasaran'   => $s['sasaran'],
            'indikator' => $s['indikator'],
            'pemilik'   => $s['pemilik'],
        ], $simpulAll),
        'indikator'   => $indAll,
        'usulan'      => $usulan,
        'ikp'         => $ikpOpsi,
        'jumlahPeran' => (object) $roster['jumlahPeran'],
    ];
    $jsonAman = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>

    <!-- ===== Filter: OPD (super admin) & tahun ===== -->
    <div class="pmk-filter">
        <div class="tb-label"><i class="fas fa-filter me-1"></i>Perangkat Daerah &amp; Tahun</div>
        <form method="get" action="<?= $urlDasar ?>" class="pmk-filter-baris">
            <?php if ($lingkup['bolehPilih']): ?>
                <div class="pmk-filter-opd">
                    <select name="opd_id" class="form-select" onchange="this.form.submit()" aria-label="Perangkat daerah">
                        <?php foreach ($lingkup['daftarOpd'] as $o): ?>
                            <option value="<?= (int) $o['id'] ?>" <?= (int) $o['id'] === (int) $opd['id'] ? 'selected' : '' ?>><?= esc($o['nama_opd']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="pmk-opd-nama"><?= esc($opd['nama_opd']) ?>
                    <small>Periode IKU <?= (int) $periode['awal'] ?>&ndash;<?= (int) $periode['akhir'] ?></small></div>
            <?php endif; ?>
            <div class="pmk-filter-tahun">
                <select name="tahun" class="form-select" onchange="this.form.submit()" aria-label="Tahun">
                    <?php foreach ($daftarTahun as $t): ?>
                        <option value="<?= (int) $t ?>" <?= (int) $t === $tahun ? 'selected' : '' ?>>Tahun <?= (int) $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <noscript><button class="btn btn-success">Tampilkan</button></noscript>
        </form>
    </div>

    <ul class="pmk-cara">
        <li><span class="no">1</span><span>Tetapkan <b>pemilik</b> tiap simpul: <b>penanggung jawab</b> (simpul menjadi RHK-nya) dan bila perlu <b>anggota</b> tim.</span></li>
        <li><span class="no">2</span><span>Isi <b>satuan</b> dan <b>target tahun <?= $tahun ?></b> tiap indikator; tautkan ke IKP bila indikator itu turunan langsung IKP.</span></li>
        <li><span class="no">3</span><span>Pegawai menarik simpul miliknya sebagai <b>RHK</b> di eKin &mdash; target bulanan mengikuti IKP atau Rencana Aksi.</span></li>
    </ul>

    <?php if (! $akarIku): ?>
        <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-1"></i>
            Pohon kinerja di basis data ini belum berjangkar IKU, sehingga simpul belum bisa ditampilkan di halaman ini.</div>
    <?php endif; ?>

    <!-- ===== Cakupan ===== -->
    <div class="pmk-cakupan" aria-live="polite">
        <div class="pmk-tile">
            <div class="judul">Simpul berpemilik</div>
            <div class="besar"><span data-stat="pemilik-total"><?= $tot['berpemilik'] ?>/<?= $tot['simpul'] ?></span>
                <small data-stat="pemilik-persen"><?= $persen($tot['berpemilik'], $tot['simpul']) ?>%</small></div>
            <?php foreach (['es3', 'es4', 'pelaksana'] as $lv): $x = $st['level'][$lv]; ?>
                <div class="pmk-baris-lv">
                    <span><?= esc($label[$lv]) ?></span>
                    <span class="pmk-progres"><span data-stat="pemilik-bar-<?= $lv ?>" style="width: <?= $persen($x['berpemilik'], $x['simpul']) ?>%"></span></span>
                    <span class="angka" data-stat="pemilik-<?= $lv ?>"><?= $x['berpemilik'] ?>/<?= $x['simpul'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="pmk-tile">
            <div class="judul">Indikator bersatuan &amp; bertarget <?= $tahun ?></div>
            <div class="besar"><span data-stat="ind-total"><?= $tot['indikatorLengkap'] ?>/<?= $tot['indikator'] ?></span>
                <small data-stat="ind-persen"><?= $persen($tot['indikatorLengkap'], $tot['indikator']) ?>%</small></div>
            <?php foreach (['es3', 'es4', 'pelaksana'] as $lv): $x = $st['level'][$lv]; ?>
                <div class="pmk-baris-lv">
                    <span><?= esc($label[$lv]) ?></span>
                    <span class="pmk-progres"><span data-stat="ind-bar-<?= $lv ?>" style="width: <?= $persen($x['indikatorLengkap'], $x['indikator']) ?>%"></span></span>
                    <span class="angka" data-stat="ind-<?= $lv ?>"><?= $x['indikatorLengkap'] ?>/<?= $x['indikator'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="pmk-tile">
            <div class="judul">Pemilik <?= esc($label['es2']) ?></div>
            <?php if ($es2Pemilik === []): ?>
                <div class="pmk-es2-orang text-danger">Belum ada <?= esc($label['pk_es2']) ?> <?= $tahun ?>
                    <small>Pemilik <?= esc($label['es2']) ?> diambil dari pihak pertama <?= esc($label['pk_es2']) ?>. Buat PK-nya di menu Perjanjian Kinerja.</small></div>
            <?php else: ?>
                <?php foreach ($es2Pemilik as $e): ?>
                    <div class="pmk-es2-orang"><?= esc(trim($e['plt'] . ' ' . $e['nama'])) ?>
                        <small><?= esc($e['jabatan']) ?> &middot; <?= esc($label['pk_es2']) ?> <?= $tahun ?></small></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="pmk-tile-catatan"><i class="fas fa-lock me-1"></i>Mengikuti <?= esc($label['pk_es2']) ?>, bukan diisi di sini.</div>
        </div>
        <div class="pmk-tile">
            <div class="judul">Pegawai tanpa peran di pohon</div>
            <div class="besar"><span data-stat="tanpa-peran"><?= $st['tanpaPeran'] ?></span>
                <small>dari <?= $st['pegawai'] ?> pegawai</small></div>
            <div class="pmk-kat-mini">
                <?php foreach ($namaKat as $k => $nm): if ($katJumlah[$k] === 0) { continue; } ?>
                    <span><?= esc($nm) ?>: <b data-stat="tanpa-<?= $k ?>"><?= $katTanpa[$k] ?></b>/<?= $katJumlah[$k] ?></span>
                <?php endforeach; ?>
            </div>
            <a href="#pmk-tanpa-peran" data-aksi="buka-roster" class="d-inline-block mt-2">Lihat daftarnya <i class="fas fa-arrow-down"></i></a>
        </div>
    </div>

    <!-- ===== "Matriks 0": pegawai yang belum punya satu pun simpul ===== -->
    <details class="pmk-tanpa-peran" id="pmk-tanpa-peran">
        <summary><i class="fas fa-chevron-right"></i>
            <span>Pegawai belum punya peran di pohon kinerja <?= $tahun ?></span>
            <span class="badge bg-warning text-dark" data-stat="tanpa-peran-lencana"><?= $st['tanpaPeran'] ?></span></summary>
        <div class="isi">
            <p class="small text-secondary mb-2">Pegawai perangkat daerah ini (<?= $st['pegawai'] ?> orang, termasuk yang tercatat di kode OPD lama)
                yang belum menjadi pemilik simpul mana pun tahun <?= $tahun ?> &mdash; setara metrik &ldquo;matriks 0&rdquo; di e-Kinerja.
                Tetapkan mereka lewat tombol <b>Tambah pemilik</b> pada simpul yang sesuai.</p>
            <div class="pmk-roster-alat">
                <input type="search" class="form-control form-control-sm" placeholder="Cari nama, jabatan, atau NIP…" data-aksi="cari-roster" aria-label="Cari pegawai tanpa peran">
                <button type="button" class="pmk-kat-btn aktif" data-kategori="">Semua</button>
                <?php foreach ($namaKat as $k => $nm): if ($katJumlah[$k] === 0) { continue; } ?>
                    <button type="button" class="pmk-kat-btn" data-kategori="<?= $k ?>"><?= esc($nm) ?></button>
                <?php endforeach; ?>
            </div>
            <ul class="pmk-roster">
                <?php foreach ($roster['pegawai'] as $p): $k = isset($namaKat[$p['kategori']]) ? $p['kategori'] : 'lainnya'; ?>
                    <li data-roster-id="<?= $p['id'] ?>" data-kategori="<?= $k ?>"
                        data-cari="<?= esc(mb_strtolower($p['nama'] . ' ' . $p['jabatan'] . ' ' . $p['nip']), 'attr') ?>"
                        class="<?= $p['peran'] > 0 ? 'pmk-punya-peran' : '' ?>">
                        <div class="n" title="<?= esc($p['nama'], 'attr') ?>"><?= esc($p['nama']) ?></div>
                        <div class="j" title="<?= esc($p['jabatan'], 'attr') ?>"><?= esc($p['jabatan'] !== '' ? $p['jabatan'] : 'Jabatan belum tercatat') ?> &middot; <?= esc($namaKat[$k]) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="small text-success mt-2 mb-0 d-none" data-roster-kosong><i class="fas fa-circle-check me-1"></i>Semua pegawai sudah punya peran.</p>
        </div>
    </details>

    <?php if ($pohon['es2'] === []): ?>
        <div class="pmk-kosong">
            <div class="ikon"><i class="fas fa-diagram-project"></i></div>
            <h5>IKU periode <?= (int) $periode['awal'] ?>&ndash;<?= (int) $periode['akhir'] ?> belum ada</h5>
            <p class="mb-2">Pohon kinerja berakar pada IKU perangkat daerah. Isi IKU terlebih dahulu, lalu turunkan ke Eselon III, IV, dan Pelaksana lewat menu Cascading.</p>
            <?php if ($areaOpd): ?>
                <a class="btn btn-outline-success btn-sm" href="<?= base_url('adminopd/cascading?periode=' . (int) $periode['awal'] . '-' . (int) $periode['akhir']) ?>"><i class="fas fa-sitemap"></i> Buka Cascading</a>
            <?php endif; ?>
        </div>
    <?php else: ?>

    <!-- ===== Bilah lengket ===== -->
    <div class="pmk-bar" id="pmk-bar">
        <div class="pmk-cari"><i class="fas fa-magnifying-glass"></i>
            <input type="search" class="form-control" placeholder="Cari simpul, indikator, atau pemilik…" data-aksi="cari" aria-label="Cari di pohon kinerja"></div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="pmkBelum" data-aksi="belum-lengkap">
            <label class="form-check-label" for="pmkBelum"><span class="pmk-teks-lebar">Hanya yang belum lengkap</span><span class="pmk-teks-sempit">Belum lengkap</span></label>
        </div>
        <select class="form-select" data-aksi="kedalaman" data-no-select2 aria-label="Buka pohon hingga jenjang">
            <option value="">Buka hingga…</option>
            <option value="es2"><?= esc($label['es2']) ?></option>
            <option value="es3"><?= esc($label['es3']) ?></option>
            <option value="es4"><?= esc($label['es4']) ?></option>
            <option value="pelaksana">Semua jenjang</option>
        </select>
        <?php if ($bolehUbah): ?>
            <button type="button" class="btn btn-outline-warning text-dark" data-aksi="usulan" aria-pressed="false" <?= $usulan === [] ? 'disabled' : '' ?>
                title="<?= $usulan === [] ? esc('Tidak ada usulan: tidak ada teks simpul yang sama dengan PK tahun ' . $tahun . ', atau usulannya sudah diterapkan.', 'attr') : esc('Tampilkan usulan pemilik dari Perjanjian Kinerja ' . $tahun, 'attr') ?>">
                <i class="fas fa-wand-magic-sparkles"></i><span class="pmk-teks-lebar">Usulkan pemilik dari PK</span>
                <span class="badge bg-warning text-dark" data-stat="usulan"><?= count($usulan) ?></span>
            </button>
        <?php endif; ?>
        <div class="pmk-mini">
            <span>Simpul berpemilik <b data-stat="mini-pemilik"><?= $tot['berpemilik'] ?>/<?= $tot['simpul'] ?></b></span>
            <span>Indikator lengkap <b data-stat="mini-ind"><?= $tot['indikatorLengkap'] ?>/<?= $tot['indikator'] ?></b></span>
            <span>Pegawai tanpa peran <b data-stat="mini-tanpa"><?= $st['tanpaPeran'] ?></b></span>
            <span class="ms-auto">Tahun <?= $tahun ?></span>
        </div>
    </div>

    <div class="pmk-usulan-info" role="status">
        <span><i class="fas fa-wand-magic-sparkles me-1"></i>
            <b data-stat="usulan-info">0</b> usulan dari PK <?= $tahun ?> (<?= esc($label['es3']) ?> &harr; PK <?= $kecamatan ? 'Pengawas' : 'Administrator' ?><?= $kecamatan ? '' : ', ' . esc($label['es4']) . ' &harr; PK Pengawas' ?>;
            dicocokkan dari teks indikator/sasaran yang sama). Periksa, lalu klik <i class="fas fa-check"></i> untuk menerima; simpul dengan beberapa calon dipilih satu per satu.</span>
        <span class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-success" data-aksi="terima-semua" title="Terima semua usulan yang hanya punya satu calon per simpul"><i class="fas fa-check-double"></i> Terima yang jelas</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-aksi="usulan"><i class="fas fa-eye-slash"></i> Sembunyikan</button>
        </span>
    </div>

    <!-- ===== Pohon ===== -->
    <div class="pmk-pohon" id="pmk-pohon">
        <?php foreach ($pohon['es2'] as $g): ?>
            <?php $nEs3 = 0; foreach ($g['indikator'] as $ii) { $nEs3 += count($ii['anak']); } ?>
            <div class="pmk-simpul" data-level="es2" data-es2-id="<?= (int) $g['id'] ?>">
                <div class="pmk-kartu">
                    <div class="pmk-kepala">
                        <button type="button" class="pmk-lipat" data-aksi="lipat" aria-expanded="true" title="Lipat/buka turunan">
                            <i class="fas fa-chevron-down"></i><span class="visually-hidden">Lipat/buka</span>
                        </button>
                        <span class="pmk-level"><?= esc($label['es2']) ?></span>
                        <span class="small text-secondary">Sasaran IKU perangkat daerah</span>
                        <span class="pmk-turunan"><?= $nEs3 ?> simpul <?= esc($label['es3']) ?></span>
                    </div>
                    <div class="pmk-sasaran"><?= esc($g['sasaran']) ?></div>
                    <div class="pmk-pemilik-wadah">
                        <span class="pmk-label">Pemilik</span>
                        <?php if ($es2Pemilik === []): ?>
                            <span class="pmk-belum">Belum ada <?= esc($label['pk_es2']) ?> <?= $tahun ?></span>
                        <?php else: foreach ($es2Pemilik as $e): ?>
                            <span class="pmk-chip pmk-chip-pj pmk-chip-kunci" title="<?= esc($label['pk_es2'] . ' ' . $tahun . ' (pihak pertama)', 'attr') ?>">
                                <span class="pmk-chip-teks"><span class="pmk-chip-nama"><?= esc(trim($e['plt'] . ' ' . $e['nama'])) ?></span>
                                    <span class="pmk-chip-jab"><?= esc($e['jabatan']) ?></span></span>
                                <span class="pmk-chip-peran" title="Penanggung jawab"><i class="fas fa-lock"></i> PJ</span>
                            </span>
                        <?php endforeach; endif; ?>
                        <span class="pmk-catatan"><i class="fas fa-circle-info me-1"></i>Pemilik <?= esc($label['es2']) ?> mengikuti <?= esc($label['pk_es2']) ?> (pihak pertama) tahun <?= $tahun ?>; diubah lewat menu Perjanjian Kinerja.</span>
                    </div>
                </div>
                <div class="pmk-anak">
                    <?php foreach ($g['indikator'] as $ii): ?>
                        <div class="pmk-grup" data-grup-iku="<?= (int) $ii['id'] ?>" data-kosong="<?= $ii['anak'] === [] ? '1' : '0' ?>">
                            <div class="pmk-grup-kepala">
                                <i class="fas fa-bullseye ikon"></i><span>Indikator IKU:</span><span class="nm"><?= esc($ii['nama']) ?></span>
                                <span class="pmk-tag"><?= $ii['satuan'] !== '' ? 'Satuan: <b>' . esc($ii['satuan']) . '</b>' : 'Satuan belum diisi' ?></span>
                                <span class="pmk-tag">Target <?= $tahun ?>: <b><?= esc($ii['target'] !== '' ? $ii['target'] : '—') ?></b></span>
                            </div>
                            <?php if ($ii['anak'] === []): ?>
                                <div class="pmk-kosong-kecil"><i class="fas fa-circle-info me-1"></i>Indikator ini belum diturunkan ke <?= esc($label['es3']) ?>.
                                    Tambahkan lewat menu <?php if ($areaOpd): ?><a href="<?= base_url('adminopd/cascading?periode=' . (int) $periode['awal'] . '-' . (int) $periode['akhir']) ?>">Cascading</a><?php else: ?>Cascading perangkat daerah<?php endif; ?>.</div>
                            <?php else: foreach ($ii['anak'] as $a): ?>
                                <?= $renderSimpul((int) $a) ?>
                            <?php endforeach; endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="pmk-kosong pmk-hasil-nihil mt-3" data-hasil-nihil>
            <div class="ikon"><i class="fas fa-magnifying-glass"></i></div>
            <h5 data-hasil-nihil-judul>Tidak ada simpul yang cocok</h5>
            <p class="mb-0" data-hasil-nihil-teks>Ubah kata pencarian atau matikan saringan.</p>
        </div>
    </div>
    <?php endif; ?>

    <datalist id="pmkDaftarSatuan">
        <?php foreach ($satuanOpsi as $s): ?><option value="<?= esc($s, 'attr') ?>"></option><?php endforeach; ?>
    </datalist>

    <?php if ($bolehUbah): ?>
    <!-- ===== Modal: tambah pemilik ===== -->
    <div class="modal fade" id="pmkModalPemilik" tabindex="-1" aria-labelledby="pmkModalPemilikJudul" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pmkModalPemilikJudul"><i class="fas fa-user-plus text-success me-2"></i>Tambah Pemilik Simpul</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="pmk-modal-simpul" data-isi="kotak"><span class="pmk-level" data-isi="level"></span><div class="isi" data-isi="sasaran"></div></div>
                    <div class="alert alert-danger py-2 small d-none" data-isi="galat"></div>
                    <label class="form-label" for="pmkPilihPegawai">Pegawai</label>
                    <select id="pmkPilihPegawai" class="form-select" data-no-select2></select>
                    <div class="form-text">Daftar awal berisi pegawai perangkat daerah ini. Ketik minimal 3 huruf nama atau NIP untuk mencari juga di perangkat daerah lain.</div>
                    <div class="small text-secondary mt-1" data-isi="pratinjau"></div>
                    <div class="mt-3">
                        <div class="form-label mb-2">Peran</div>
                        <div class="pmk-opsi-peran">
                            <label class="pmk-opsi"><input type="radio" name="pmkPeran" value="penanggung_jawab">
                                <span><b>Penanggung jawab</b><small>Simpul ini menjadi RHK pegawai tersebut di SKP.</small></span></label>
                            <label class="pmk-opsi"><input type="radio" name="pmkPeran" value="anggota">
                                <span><b>Anggota</b><small>Ikut mengerjakan simpul ini bersama penanggung jawab.</small></span></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" data-aksi="simpan-pemilik"><i class="fas fa-check"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Modal: satuan & target indikator ===== -->
    <div class="modal fade" id="pmkModalIndikator" tabindex="-1" aria-labelledby="pmkModalIndikatorJudul" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pmkModalIndikatorJudul"><i class="fas fa-bullseye text-success me-2"></i>Satuan &amp; Target Indikator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="pmk-modal-simpul" data-isi="kotak"><span class="pmk-level" data-isi="level"></span><div class="isi" data-isi="indikator"></div>
                        <div class="small text-secondary mt-1" data-isi="sasaran"></div></div>
                    <div class="alert alert-danger py-2 small d-none" data-isi="galat"></div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="pmkSatuan">Satuan</label>
                            <input type="text" class="form-control" id="pmkSatuan" list="pmkDaftarSatuan" maxlength="50" autocomplete="off" placeholder="mis. Persen, Dokumen, Orang">
                            <div class="form-text">Pilih dari daftar atau ketik sendiri.</div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="pmkTarget">Target tahun <?= $tahun ?></label>
                            <input type="text" class="form-control" id="pmkTarget" maxlength="100" inputmode="decimal" autocomplete="off" placeholder="mis. 40 · 1.250 · 12,5">
                            <div class="form-text">Titik = pemisah ribuan, koma = desimal. Target berupa predikat (mis. &ldquo;Baik&rdquo;) boleh, tetapi capaiannya tidak bisa dihitung otomatis.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="pmkMetode">Cara menghitung capaian</label>
                            <select class="form-select" id="pmkMetode" data-no-select2>
                                <?php foreach ($metodeOpsi as $k => $v): ?>
                                    <option value="<?= esc($k, 'attr') ?>"><?= esc($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" data-isi="metode-bantu"></div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="pmkIkp">Turunan langsung IKP? <span class="text-secondary fw-normal">(opsional)</span></label>
                            <select class="form-select" id="pmkIkp" data-no-select2>
                                <option value="">&mdash; Tidak ditautkan ke IKP &mdash;</option>
                                <?php foreach ($ikpOpsi as $i): ?>
                                    <option value="<?= (int) $i['id'] ?>">[<?= esc($kategoriIkp[$i['kategori']] ?? $i['kategori']) ?>] <?= esc($i['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($ikpOpsi === []): ?>
                                <div class="form-text">Perangkat daerah ini belum punya IKP aktif untuk tahun <?= $tahun ?>.</div>
                            <?php endif; ?>
                            <div class="pmk-info-ikp" data-isi="info-ikp"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-outline-success" data-aksi="simpan-indikator" data-lanjut="1" title="Simpan lalu buka indikator berikutnya yang belum lengkap">
                        <i class="fas fa-forward"></i> Simpan &amp; <span class="d-none d-sm-inline">berikutnya</span><span class="d-sm-none">lanjut</span></button>
                    <button type="button" class="btn btn-success" data-aksi="simpan-indikator"><i class="fas fa-check"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="pmk-toast-wadah" aria-live="polite"></div>
    <noscript><div class="alert alert-warning mt-3">Halaman ini memerlukan JavaScript untuk menampilkan pemilik dan target.</div></noscript>

    <script type="application/json" id="pmk-data"><?= json_encode($dataJs, $jsonAman) ?></script>
    <?php $js = FCPATH . 'assets/js/adminopd/pemilik_kinerja.js'; ?>
    <script src="<?= base_url('assets/js/adminopd/pemilik_kinerja.js') ?>?v=<?= is_file($js) ? filemtime($js) : '1' ?>" defer></script>
<?php endif; ?>
</div>

<?= $this->include('templates/shell_bawah') ?>

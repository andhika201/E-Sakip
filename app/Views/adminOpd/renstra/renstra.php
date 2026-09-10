<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Rencana Strategis') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>

    <?php if (function_exists('csrf_token')): ?>
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php endif; ?>
    <style>
        /* Rapikan tabel Renstra */
        .renstra-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            white-space: nowrap;
        }
        .renstra-table th,
        .renstra-table td {
            vertical-align: middle;
        }
        .renstra-table td.text-start {
            min-width: 170px;
        }
        .renstra-table .col-tahun {
            white-space: nowrap;
            width: 60px;
        }
        .renstra-table tbody tr:hover {
            background-color: #f3faf5;
        }
        .table-wrap {
            max-height: 72vh;
            overflow: auto;
        }
        /* Kode indikator (badge "IK" di depan nama) */
        .ind-kode {
            display: inline-block;
            font-weight: 800;
            font-size: .72em;
            letter-spacing: .6px;
            padding: 1px 6px;
            margin-right: 5px;
            border-radius: 5px;
            background: #00743e;
            color: #fff;
            vertical-align: middle;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">📊 Rencana Strategis</h2>

                <!-- Flash Message -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php
                $filters = $filters ?? [
                    'misi' => '',
                    'tujuan' => '',
                    'rpjmd' => '',
                    'periode' => '',
                    'status' => '',
                ];

                /* ============ VERSI YANG SEDANG DILIHAT ============
                 * $versi_aktif null  = kondisi berjalan (perilaku lama, apa adanya)
                 * $versi_aktif terisi = membaca ARSIP sebuah versi yang sudah
                 *                       ditetapkan; barisnya milik arsip, bukan
                 *                       baris berjalan, sehingga seluruh tombol
                 *                       tulis WAJIB padam di bawah.
                 */
                $versiPilihan = $versi_pilihan ?? [];
                $versiAktif   = $versi_aktif ?? null;
                $bacaArsip    = $versiAktif !== null;

                /* Tampilan ini bisa berasal dari dua hal, dan bedanya penting:
                   dipilih sendiri sekali lihat, atau dari TUNJUKAN tampilan
                   utama yang terpasang tetap. Yang kedua perlu dikatakan, sebab
                   pemakai yang tidak memilih apa-apa akan mengira ia sedang
                   melihat kondisi berjalan. */
                $dariTunjukan = ! empty($versi_dari_tunjukan);
                $tanggalVersi = $versi_menurut_tanggal ?? null;
                $selisihTunjuk = $dariTunjukan && $tanggalVersi !== null
                    && (int) $tanggalVersi['id'] !== (int) $versiAktif['id'];
                ?>

                <!-- ===================== FORM FILTER ===================== -->
                <form id="filterForm" method="GET" action="<?= base_url('adminopd/renstra') ?>"
                    class="d-flex flex-column flex-md-row gap-2 mb-4 align-items-center">

                    <?php /* Filter "Misi RPJMD" & "Tujuan Renstra" dihapus (permintaan user) */ ?>

                    <!-- Sasaran RPJMD -->
                    <select id="rpjmdFilter" name="rpjmd" class="form-select select2-flt" style="flex:1;">
                        <option value="">Semua Sasaran RPJMD</option>
                        <?php
                        // Opsi filter diambil dari data se-periode (bukan yang sudah terfilter) agar tidak menciut.
                        $filterSrc = $filter_source ?? ($renstra_data ?? []);
                        $sList = [];
                        if (!empty($filterSrc)) {
                            foreach ($filterSrc as $d) {
                                if (!empty($d['sasaran_rpjmd'])) {
                                    $sList[$d['sasaran_rpjmd']] = $d['sasaran_rpjmd'];
                                }
                            }
                        }
                        asort($sList);
                        foreach ($sList as $s): ?>
                            <option value="<?= esc($s) ?>" <?= ($filters['rpjmd'] === $s) ? 'selected' : '' ?>>
                                <?= esc($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Periode -->
                    <select id="periodeFilter" name="periode" class="form-select select2-flt" style="flex:1;">
                        <option value="">-- Pilih Periode --</option>
                        <?php
                        $periodeList = [];
                        if (!empty($periode_master ?? [])) {
                            foreach ($periode_master as $p) {
                                $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
                                $periodeList[$key] = $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'];
                            }
                        } elseif (!empty($renstra_data)) {
                            foreach ($renstra_data as $d) {
                                if (!empty($d['tahun_mulai']) && !empty($d['tahun_akhir'])) {
                                    $key = $d['tahun_mulai'] . '-' . $d['tahun_akhir'];
                                    $periodeList[$key] = $d['tahun_mulai'] . ' - ' . $d['tahun_akhir'];
                                }
                            }
                        }
                        foreach ($periodeList as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= ($filters['periode'] === $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Versi -->
                    <?php if (! empty($filters['periode']) && ! empty($versiPilihan)): ?>
                        <select id="versiFilter" name="versi" class="form-select select2-flt" style="flex:1;">
                            <?php /* Nilainya 'berjalan', bukan kosong: memilih kondisi
                                     berjalan harus bisa dibedakan dari "tidak memilih
                                     apa-apa", supaya tunjukan tampilan utama tidak
                                     menimpanya kembali. */ ?>
                            <option value="berjalan" <?= $bacaArsip ? '' : 'selected' ?>>
                                Kondisi berjalan (terkini)
                            </option>
                            <?php foreach ($versiPilihan as $v): ?>
                                <option value="<?= (int) $v['id'] ?>"
                                    <?= $bacaArsip && (int) $versiAktif['id'] === (int) $v['id'] ? 'selected' : '' ?>>
                                    V<?= (int) $v['version_no'] ?> — <?= esc($v['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <!-- Status -->
                    <?php /* Status adalah keadaan PENGERJAAN; versi yang sudah
                             ditetapkan tidak lagi dikerjakan, jadi saringannya
                             tidak ditawarkan saat membaca arsip. */ ?>
                    <select id="statusFilter" name="status" class="form-select select2-flt" style="flex:1;"
                            <?= $bacaArsip ? 'disabled data-terkunci="1"' : '' ?>>
                        <option value="">Semua Status</option>
                        <option value="draft" <?= ($filters['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="selesai" <?= ($filters['status'] === 'selesai') ? 'selected' : '' ?>>Selesai
                        </option>
                    </select>

                    <!-- Tombol Aksi -->
                    <div class="d-flex gap-2 mt-2 mt-md-0">
                        <a href="<?= base_url('adminopd/renstra') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                        <?php if (!empty($filters['periode'])): ?>
                            <a href="<?= base_url('adminopd/renstra/cetak?' . http_build_query(array_filter([
                                'misi' => $filters['misi'] ?? '',
                                'tujuan' => $filters['tujuan'] ?? '',
                                'rpjmd' => $filters['rpjmd'] ?? '',
                                'periode' => $filters['periode'] ?? '',
                                'status' => $filters['status'] ?? '',
                                'versi' => $bacaArsip ? (string) (int) $versiAktif['id'] : 'berjalan',
                            ], static fn($v) => $v !== ''))) ?>" target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf"></i> Cetak PDF
                            </a>
                        <?php endif; ?>
                        <?php /* =====================================================
                                 HAPUS RENSTRA SATU PERIODE

                                 Hanya muncul setelah sebuah periode dipilih, dan
                                 hanya bila periode itu MEMANG belum dipakai apa pun.
                                 Yang terhalang tetap diberi keterangan — tombol yang
                                 hilang tanpa alasan membuat orang mengira layarnya
                                 rusak, lalu mencari jalan lain yang lebih berbahaya.
                              ===================================================== */ ?>
                        <?php
                            $kunciPeriode = trim((string) ($filters['periode'] ?? ''));
                            $kunciPeriode = preg_replace('/\s*-\s*/', '-', $kunciPeriode);
                            $keadaanHapus = ($hapus_periode ?? [])[$kunciPeriode] ?? null;
                        ?>
                        <?php if ($kunciPeriode !== '' && $keadaanHapus !== null && ! $bacaArsip): ?>
                            <?php if (! empty($keadaanHapus['boleh'])): ?>
                                <?php
                                    $isi = $keadaanHapus['isi'];
                                    $rinci = $isi['tujuan'] . ' tujuan, ' . $isi['sasaran'] . ' sasaran, '
                                        . $isi['indikator'] . ' indikator, ' . $isi['target'] . ' target tahunan';
                                ?>
                                <?php /* =========================================
                                         TOMBOLNYA DI SINI, FORMNYA DI LUAR

                                         Blok ini berada DI DALAM #filterForm.
                                         Menaruh <form> di sini membuat form
                                         bersarang — HTML tidak sah, dan peramban
                                         membuang tag form bagian dalam. Tombolnya
                                         lalu menjadi tombol submit form FILTER:
                                         mengirim GET, halaman dimuat ulang, dan
                                         tampak seolah tidak terjadi apa-apa.

                                         Atribut `form=` menautkannya ke form yang
                                         berdiri di luar, sesudah </form> filter.
                                       ========================================= */ ?>
                                <button type="submit" form="formHapusPeriode" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Hapus Periode
                                </button>
                            <?php elseif (! empty($keadaanHapus['penghalang'])): ?>
                                <span class="btn btn-outline-secondary disabled"
                                      title="<?= esc($keadaanHapus['alasan']) ?>">
                                    <i class="fas fa-lock"></i> Periode dipakai
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (user_can('renstra.create') && ! $bacaArsip): ?>
                            <a href="<?= base_url('adminopd/renstra/tambah') ?>" class="btn btn-success">
                                <i class="fas fa-plus"></i> Tambah RENSTRA
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php /* Form hapus periode — sengaja DI LUAR #filterForm.
                         Lihat catatan pada tombolnya di atas: form bersarang
                         dibuang peramban, dan tombolnya berubah menjadi submit
                         form filter yang tidak menghapus apa pun. */ ?>
                <?php if ($kunciPeriode !== '' && $keadaanHapus !== null && ! $bacaArsip
                    && ! empty($keadaanHapus['boleh'])): ?>
                    <form id="formHapusPeriode" method="post"
                          action="<?= base_url('adminopd/renstra/periode/hapus') ?>"
                          data-konfirmasi="Seluruh Renstra pada periode ini akan dihapus permanen, beserta versi dokumennya."
                          data-konfirmasi-judul="Hapus Seluruh Renstra Periode"
                          data-konfirmasi-nama="Periode <?= esc($kunciPeriode, 'attr') ?>"
                          data-konfirmasi-rincian="<?= esc($rinci, 'attr') ?>|Seluruh versi dokumen Renstra periode ini"
                          data-konfirmasi-ketik="HAPUS">
                        <?= csrf_field() ?>
                        <input type="hidden" name="periode" value="<?= esc($kunciPeriode) ?>">
                    </form>
                <?php endif; ?>

                <?php if ($bacaArsip): ?>
                    <?php /* Spanduk ini bukan hiasan. Tabel di bawah bentuknya sama
                             persis dengan tampilan kondisi berjalan, sehingga tanpa
                             penanda yang jelas pemakai bisa mengira sedang melihat
                             Renstra yang berlaku hari ini. */ ?>
                    <div class="alert <?= $selisihTunjuk ? 'alert-warning' : ($dariTunjukan ? 'alert-primary' : 'alert-secondary') ?> d-flex flex-wrap align-items-center gap-2 mb-4">
                        <i class="fas <?= $dariTunjukan ? 'fa-thumbtack' : 'fa-clock-rotate-left' ?>"></i>
                        <div class="flex-grow-1">
                            <strong>
                                <?= $dariTunjukan ? 'Tampilan utama yang Anda tunjuk' : 'Anda sedang membaca versi tersimpan' ?>:
                                V<?= (int) $versiAktif['version_no'] ?> — <?= esc($versiAktif['label']) ?></strong>
                            <div class="small text-muted">
                                Berlaku sejak <?= esc($versiAktif['effective_from']) ?><?= empty($versiAktif['effective_to'])
                                    ? ' sampai sekarang' : ' sampai ' . esc($versiAktif['effective_to']) ?>.
                                Isinya tetap sebagaimana saat ditetapkan dan tidak bisa disunting dari sini.
                            </div>
                            <?php if ($selisihTunjuk): ?>
                                <div class="small mt-1">
                                    <strong>Perhatikan:</strong> menurut tanggal berlaku, yang berlaku hari ini
                                    sebenarnya V<?= (int) $tanggalVersi['version_no'] ?> —
                                    <?= esc($tanggalVersi['label']) ?> (mulai <?= esc($tanggalVersi['effective_from']) ?>).
                                    Halaman ini menampilkan versi lain karena tunjukan Anda.
                                </div>
                            <?php endif; ?>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="<?= base_url('adminopd/renstra?' . http_build_query(array_filter([
                               'rpjmd' => $filters['rpjmd'] ?? '',
                               'periode' => $filters['periode'] ?? '',
                               'versi' => 'berjalan',
                           ], static fn($v) => $v !== ''))) ?>">
                            Kembali ke kondisi berjalan
                        </a>
                    </div>
                <?php endif; ?>

                <?php
                /* ============ SIKLUS HIDUP RENSTRA (Versi 1) ============
                 * Renstra yang disusun di menu ini ADALAH Versi 1. Panel di
                 * bawah menampilkan keadaannya dan menyediakan aksinya.
                 * Penguncian sesungguhnya ada di controller, bukan di sini.
                 */
                $sk = $siklus ?? null;

                /* Periode dipecah DI SINI, bukan di dalam blok panel di bawah.
                   Sebelumnya $skMulai lahir di dalam blok itu lalu dipakai juga
                   oleh modal di luarnya — sehingga begitu panelnya tidak
                   tampil (misalnya saat sedang membaca versi), modal meledak
                   dengan "Undefined variable". Nilai yang dipakai dua tempat
                   tidak boleh lahir di salah satunya. */
                $skMulai = 0;
                $skAkhir = 0;

                if (! empty($filters['periode'])
                    && preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $filters['periode'], $mSk)) {
                    $skMulai = (int) $mSk[1];
                    $skAkhir = (int) $mSk[2];
                }
                ?>
                <?php if ($skMulai > 0 && $sk !== null && ! $bacaArsip): ?>
                    <?php
                    /* Sedang disunting berdasarkan izin adalah keadaan tersendiri,
                       bukan sekadar "published" yang kebetulan terbuka. Tabel di
                       bawah menampilkan data yang SUDAH menyimpang dari versi
                       resmi, dan itu harus dikatakan — bukan disembunyikan di
                       balik lencana hijau "Sudah Ditetapkan". */
                    $skDisunting = ! empty($sk['sedang_disunting']);

                    $skKelas = match (true) {
                        $skDisunting                        => ['alert-warning', 'fa-unlock', 'Sedang Disunting (izin diberikan)'],
                        $sk['status'] === 'pending_approval' => ['alert-primary', 'fa-hourglass-half', 'Menunggu Verifikasi Admin Kabupaten'],
                        $sk['status'] === 'published'        => ['alert-success', 'fa-circle-check', 'Sudah Ditetapkan & Berlaku'],
                        default                             => ['alert-warning', 'fa-pen-ruler', 'Masih Disusun (belum divalidasi)'],
                    };
                    ?>
                    <div class="alert <?= $skKelas[0] ?> d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <i class="fas <?= $skKelas[1] ?> me-1"></i>
                            <strong><?= esc($skKelas[2]) ?></strong>
                            <div class="small mt-1">
                                <?php if ($skDisunting): ?>
                                    Admin Kabupaten memberi izin menyunting periode ini. Versi resmi
                                    <?php if (! empty($sk['versi'])): ?>
                                        <strong>V<?= (int) $sk['versi']['version_no'] ?></strong>
                                    <?php endif; ?>
                                    tetap utuh dan tidak berubah &mdash; yang Anda sunting sekarang adalah
                                    data berjalan. Setelah selesai, <strong>ajukan ulang untuk divalidasi</strong>;
                                    hasilnya menjadi versi berikutnya.
                                    <?php if (! empty($sk['izin']['alasan'])): ?>
                                        <div class="mt-1 text-muted">
                                            Alasan permohonan: <em><?= esc($sk['izin']['alasan']) ?></em>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($sk['terkunci']): ?>
                                    <?= esc($sk['alasan']) ?>
                                    <?php if (! empty($sk['izin']) && $sk['izin']['status'] === 'ditolak'
                                              && ! empty($sk['izin']['catatan_keputusan'])): ?>
                                        <div class="mt-1">
                                            Catatan Admin Kabupaten:
                                            <em><?= esc($sk['izin']['catatan_keputusan']) ?></em>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Renstra periode ini masih bebas disunting. Bila sudah sesuai,
                                    ajukan untuk divalidasi Admin Kabupaten.
                                <?php endif; ?>

                                <?php /* Draft berisi yang disusun di menu Versi menghalangi
                                         pengajuan dari sini, sebab pengajuan dari sini
                                         membekukan ulang dari kondisi berjalan dan akan
                                         menimpanya. Alasannya ditulis, bukan sekadar
                                         tombolnya hilang tanpa keterangan. */ ?>
                                <?php if (! empty($sk['draft_berisi']) && ! empty($sk['alasan']) && ! $sk['terkunci']): ?>
                                    <div class="mt-2">
                                        <i class="fas fa-triangle-exclamation me-1"></i>
                                        <?= esc($sk['alasan']) ?>
                                        <a class="ms-1" href="<?= base_url('adminopd/renstra/versi/lihat/'
                                            . (int) $sk['draft_berisi']['id']) ?>">Buka draft itu</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($sk['boleh_ajukan'] && user_can('renstra.version.submit')): ?>
                                <form method="post" action="<?= base_url('adminopd/renstra/ajukan-validasi') ?>"
                                      onsubmit="return confirm('Ajukan Renstra periode <?= esc($filters['periode']) ?> untuk divalidasi? Selama menunggu, isinya tidak bisa disunting.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tahun_mulai" value="<?= $skMulai ?>">
                                    <input type="hidden" name="tahun_akhir" value="<?= $skAkhir ?>">
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane me-1"></i>Ajukan Validasi
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($sk['boleh_tarik']): ?>
                                <form method="post" action="<?= base_url('adminopd/renstra/tarik-permohonan') ?>"
                                      onsubmit="return confirm('Tarik permohonan validasi? Renstra bisa disunting lagi setelahnya.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tahun_mulai" value="<?= $skMulai ?>">
                                    <input type="hidden" name="tahun_akhir" value="<?= $skAkhir ?>">
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-rotate-left me-1"></i>Tarik Permohonan
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php /* ===== IZIN SUNTING =====
                                     Menggantikan tombol "Ajukan Koreksi". Yang diminta
                                     bukan perubahan satu kolom, melainkan dibukanya
                                     kunci — sesudah itu penyuntingan berjalan lewat
                                     form dan tombol yang sudah dikenal. */ ?>
                            <?php if (! empty($sk['boleh_minta_izin']) && user_can('renstra.izin_sunting.request')): ?>
                                <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalIzinSunting">
                                    <i class="fas fa-unlock me-1"></i>Ajukan Izin Sunting
                                </button>
                            <?php endif; ?>

                            <?php if (! empty($sk['izin']) && $sk['izin']['status'] === 'pending'
                                      && user_can('renstra.izin_sunting.request')): ?>
                                <form method="post"
                                      action="<?= base_url('adminopd/renstra/izin-sunting/tarik/' . (int) $sk['izin']['id']) ?>"
                                      onsubmit="return confirm('Tarik permohonan izin sunting?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-rotate-left me-1"></i>Tarik Permohonan Izin
                                    </button>
                                </form>
                            <?php endif; ?>

                            <a href="<?= base_url('adminopd/renstra/versi') ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-code-branch me-1"></i>Versi Renstra
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php /* Syaratnya WAJIB sama persis dengan syarat tombol pemanggilnya.
                         Modal tanpa tombol hanya markup mati; tombol tanpa modal
                         adalah tombol yang tidak melakukan apa-apa. */ ?>
                <?php if ($skMulai > 0 && $sk !== null && ! $bacaArsip
                          && ! empty($sk['boleh_minta_izin']) && user_can('renstra.izin_sunting.request')): ?>
                    <div class="modal fade" id="modalIzinSunting" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="post" action="<?= base_url('adminopd/renstra/izin-sunting/ajukan') ?>"
                                  class="modal-content">
                                <?= csrf_field() ?>
                                <input type="hidden" name="tahun_mulai" value="<?= $skMulai ?>">
                                <input type="hidden" name="tahun_akhir" value="<?= $skAkhir ?>">

                                <div class="modal-header">
                                    <h5 class="modal-title">Ajukan Izin Sunting Renstra <?= esc($filters['periode']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <p class="small text-secondary">
                                        Setelah Admin Kabupaten menyetujui, Renstra periode ini bisa disunting
                                        seperti biasa. Versi yang sudah ditetapkan <strong>tidak berubah</strong>;
                                        hasil penyuntingan Anda menjadi <strong>versi berikutnya</strong> ketika
                                        diajukan ulang untuk divalidasi.
                                    </p>

                                    <label class="form-label small fw-semibold">
                                        Alasan <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="alasan" rows="4" class="form-control" required
                                              maxlength="5000"
                                              placeholder="Contoh: target indikator X salah ketik, seharusnya 95 bukan 9,5"></textarea>
                                    <div class="form-text">
                                        Yang membaca ini adalah orang di instansi lain. Sebutkan apa yang
                                        keliru dan apa yang akan diperbaiki.
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane me-1"></i>Kirim Permohonan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($filters['periode'])): ?>

                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu untuk menampilkan data RENSTRA.
                    </div>

                <?php elseif (empty($renstra_data)): ?>

                    <div class="alert alert-info text-center p-4">
                        📁 Tidak ada data RENSTRA untuk filter yang dipilih.
                    </div>

                <?php else: ?>

                    <?php
                    [$start, $end] = explode('-', $filters['periode']);
                    $start = (int) trim($start);
                    $end = (int) trim($end);
                    $yearCount = $end - $start + 1;
                    ?>

                    <div class="table-responsive table-wrap">
                        <table class="table table-bordered text-center align-middle small renstra-table">
                            <thead class="table-success fw-bold text-dark text-center">
                                <tr>
                                    <th rowspan="2">No</th>
                                    <?php /* Kolom "Sasaran RPJMD" di-hide (permintaan user) */ ?>
                                    <th rowspan="2">Tujuan</th>
                                    <th rowspan="2">Indikator Tujuan</th>
                                    <th colspan="<?= $yearCount ?>">TARGET TUJUAN PER TAHUN</th>

                                    <th rowspan="2">Sasaran</th>
                                    <th rowspan="2">Indikator Sasaran</th>
                                    <th rowspan="2">Satuan</th>
                                    <th rowspan="2">Kondisi Awal</th>
                                    <th colspan="<?= $yearCount ?>">TARGET SASARAN PER TAHUN</th>
                                    <th rowspan="2">Kondisi Akhir</th>
                                    <th rowspan="2">Jenis Indikator</th>

                                    <?php /* Kolom Status & Aksi tidak berlaku bagi arsip:
                                             status adalah keadaan pengerjaan, dan id barisnya
                                             milik arsip sehingga tombolnya akan menunjuk baris
                                             yang salah. */ ?>
                                    <?php if (! $bacaArsip): ?>
                                        <th rowspan="2">Status</th>
                                        <th rowspan="2">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                                <tr>
                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th class="col-tahun"><?= $y ?></th>
                                    <?php endfor; ?>

                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th class="col-tahun"><?= $y ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $jenisLabel = static function ($v) {
                                    $v = strtolower(trim((string) $v));
                                    if ($v === 'positif') return 'Positif';
                                    if ($v === 'negatif') return 'Negatif';
                                    return $v !== '' ? ucfirst($v) : '';
                                };
                                $no = 1;
                                ?>
                                <?php foreach ($renstra_data as $tujuan): ?>
                                    <?php
                                    $tujuanId = $tujuan['tujuan_renstra_id'] ?? null;

                                    // Flatten sasaran x indikator
                                    $flatSas = [];
                                    foreach ($tujuan['sasaran'] as $s) {
                                        foreach ($s['indikator'] as $is) {
                                            $flatSas[] = [
                                                'sasaran_id' => $s['sasaran_id'],
                                                'sasaran'    => $s['sasaran'],
                                                'status'     => $s['status'],
                                                'indikator'  => $is['indikator'],
                                                'satuan'     => $is['satuan'],
                                                'baseline'   => $is['baseline'] ?? '',
                                                'jenis'      => $is['jenis_indikator'] ?? '',
                                                'targets'    => $is['targets'],
                                            ];
                                        }
                                    }

                                    $itCount  = count($tujuan['indikator_tujuan']);
                                    $sasCount = count($flatSas);
                                    $totalRow = max($itCount, $sasCount, 1);

                                    // Rowspan per sasaran = jumlah indikatornya
                                    $sasRowspan = [];
                                    foreach ($flatSas as $fs) {
                                        $rid = $fs['sasaran_id'];
                                        $sasRowspan[$rid] = ($sasRowspan[$rid] ?? 0) + 1;
                                    }
                                    $sasPrinted = [];
                                    $rowPrinted = false;
                                    ?>

                                    <?php for ($i = 0; $i < $totalRow; $i++): ?>
                                        <tr>
                                            <?php if (!$rowPrinted): ?>
                                                <td rowspan="<?= $totalRow ?>" class="text-center align-middle"><?= $no++ ?></td>
                                                <?php /* Kolom "Sasaran RPJMD" di-hide (permintaan user) */ ?>
                                                <td rowspan="<?= $totalRow ?>" class="text-start"><?= esc($tujuan['tujuan']) ?></td>
                                            <?php endif; ?>

                                            <!-- ================= INDIKATOR TUJUAN ================= -->
                                            <?php if ($i < $itCount): ?>
                                                <?php $it = $tujuan['indikator_tujuan'][$i]; ?>
                                                <td class="text-start"><span class="ind-kode">IK</span><?= esc($it['indikator_tujuan']) ?></td>
                                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                                    <td class="col-tahun"><?= esc($it['targets'][$y] ?? '') ?></td>
                                                <?php endfor; ?>
                                            <?php else: ?>
                                                <td></td>
                                                <?php for ($y = $start; $y <= $end; $y++): ?><td></td><?php endfor; ?>
                                            <?php endif; ?>

                                            <!-- ================= SASARAN ================= -->
                                            <?php if ($i < $sasCount): ?>
                                                <?php
                                                $ss  = $flatSas[$i];
                                                $sid = $ss['sasaran_id'];
                                                $isFirstOfSasaran = !isset($sasPrinted[$sid]);
                                                if ($isFirstOfSasaran) {
                                                    $sasPrinted[$sid] = true;
                                                }
                                                $kondisiAkhir = $ss['targets'][$end] ?? '';
                                                ?>

                                                <?php if ($isFirstOfSasaran): ?>
                                                    <td rowspan="<?= $sasRowspan[$sid] ?>" class="text-start"><?= esc($ss['sasaran']) ?></td>
                                                <?php endif; ?>

                                                <td class="text-start"><span class="ind-kode">IK</span><?= esc($ss['indikator']) ?></td>
                                                <td><?= esc($ss['satuan']) ?></td>
                                                <td><?= esc($ss['baseline']) ?></td>
                                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                                    <td class="col-tahun"><?= esc($ss['targets'][$y] ?? '') ?></td>
                                                <?php endfor; ?>
                                                <td><?= esc($kondisiAkhir) ?></td>
                                                <td><?= esc($jenisLabel($ss['jenis'])) ?></td>

                                                <?php if ($isFirstOfSasaran && ! $bacaArsip): ?>
                                                    <!-- STATUS (per sasaran) -->
                                                    <td rowspan="<?= $sasRowspan[$sid] ?>">
                                                        <?php
                                                        $sStatus = strtolower($ss['status'] ?? 'draft');
                                                        $sBadge  = $sStatus === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                        $sLabel  = $sStatus === 'selesai' ? 'Selesai' : 'Draft';
                                                        ?>
                                                        <span class="badge <?= $sBadge ?>"><?= $sLabel ?></span>
                                                    </td>
                                                    <!-- AKSI (per sasaran) -->
                                                    <td rowspan="<?= $sasRowspan[$sid] ?>">
                                                        <?php if (user_can('renstra.delete')): ?>
                                                        <?= view('templates/tombol_hapus', [
                                                            'url'   => base_url('adminopd/renstra/delete/' . esc($sid)),
                                                            'pesan' => 'Yakin ingin menghapus sasaran ini?',
                                                            'judul' => 'Hapus Sasaran',
                                                            'kelas' => 'btn btn-danger btn-sm mb-1',
                                                        ]) ?>
                                                        <?php endif; ?>
                                                        <?php if (user_can('renstra.update')): ?>
                                                        <button type="button" class="btn btn-info btn-sm change-status-btn mb-1"
                                                            data-id="<?= esc($sid) ?>" title="Ubah Status">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <?php if ($tujuanId): ?>
                                                            <a href="<?= base_url('adminopd/renstra/edit-tujuan/' . esc($tujuanId)) ?>"
                                                                class="btn btn-warning btn-sm" title="Edit Tujuan & Sasaran">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                            <?php else: ?>
                                                <!-- baris ekstra (indikator tujuan lebih banyak dari sasaran) -->
                                                <td></td><!-- Sasaran -->
                                                <td></td><!-- Indikator Sasaran -->
                                                <td></td><!-- Satuan -->
                                                <td></td><!-- Kondisi Awal -->
                                                <?php for ($y = $start; $y <= $end; $y++): ?><td></td><?php endfor; ?>
                                                <td></td><!-- Kondisi Akhir -->
                                                <td></td><!-- Jenis Indikator -->
                                                <?php if (! $bacaArsip): ?>
                                                    <td></td><!-- Status -->
                                                    <td></td><!-- Aksi -->
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </tr>
                                        <?php $rowPrinted = true; ?>
                                    <?php endfor; ?>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>


            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('filterForm');

            const periodeSelect = document.getElementById('periodeFilter');
            const rpjmdSelect = document.getElementById('rpjmdFilter');
            const statusSelect = document.getElementById('statusFilter');
            const versiSelect = document.getElementById('versiFilter');

            const otherFilters = [rpjmdSelect, statusSelect, versiSelect];

            // Semua filter dropdown jadi Select2 (searchable)
            const hasSelect2 = window.jQuery && $.fn.select2;
            if (hasSelect2) {
                $('.select2-flt').select2({ width: '100%', theme: 'bootstrap-5', dropdownParent: $('body') });
            }

            function toggleFilters() {
                const hasPeriode = (periodeSelect?.value || '').trim() !== '';
                otherFilters.forEach(el => {
                    if (!el) return;
                    // Dikunci dari server (mis. saringan status saat membaca arsip);
                    // menghidupkannya di sini akan membatalkan keputusan itu.
                    if (el.dataset.terkunci === '1') return;
                    if (hasSelect2) {
                        $(el).prop('disabled', !hasPeriode);
                    } else {
                        el.disabled = !hasPeriode;
                    }
                });
            }

            toggleFilters();

            [periodeSelect, ...otherFilters].forEach(el => {
                if (!el) return;
                const handler = function () { toggleFilters(); form.submit(); };
                // Pakai jQuery .on agar perubahan dari Select2 (dipicu lewat jQuery) tertangkap;
                // addEventListener native TIDAK menerima event yang dipicu Select2.
                if (hasSelect2) { $(el).on('change', handler); }
                else { el.addEventListener('change', handler); }
            });

            // ===================== UBAH STATUS (AJAX) =====================
            const csrfName = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');

            document.querySelectorAll('.change-status-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    if (!confirm('Apakah Anda yakin ingin mengubah status data ini?')) return;

                    const payload = { id: id };
                    if (csrfName && csrfHash) payload[csrfName] = csrfHash;

                    fetch('<?= base_url('adminopd/renstra/update-status') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert(`✅ ${data.message}\nStatus sekarang: ${String(data.newStatus || '').toUpperCase()}`);
                                location.reload();
                            } else {
                                alert(`❌ ${data.message || 'Gagal mengubah status.'}`);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('❌ Terjadi kesalahan koneksi.');
                        });
                });
            });
        });
    </script>

</body>

</html>

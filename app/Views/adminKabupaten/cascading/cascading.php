<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Pohon Kinerja & Cascading') ?> - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <?= $this->include('adminKabupaten/cascading/_pohon_styles'); ?>
    <?php if (in_array(($mode ?? 'kabupaten'), ['opd', 'keseluruhan'], true)): ?>
        <?= $this->include('adminOpd/cascading/_pohon_opd_styles'); ?>
    <?php endif; ?>

    <?php if (function_exists('csrf_token')): ?>
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php endif; ?>

    <style>
        /* ===================== Polish layar cascading ===================== */
        .casc-paper { border-radius: 16px; }

        .casc-head {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e8ece9;
        }
        .casc-head .casc-icon {
            flex: 0 0 auto;
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: linear-gradient(135deg, #0a8f50 0%, #00743e 100%);
            color: #fff;
            font-size: 23px;
            box-shadow: 0 8px 18px rgba(0, 116, 62, .28);
        }
        .casc-head h2 {
            margin: 0;
            font-weight: 800;
            font-size: 1.45rem;
            color: #16321f;
            letter-spacing: .2px;
        }
        .casc-head p {
            margin: 3px 0 0;
            color: #6b7a70;
            font-size: .85rem;
        }

        .casc-toolbar {
            background: #f6f9f7;
            border: 1px solid #e6ece8;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 22px;
        }
        .casc-toolbar .tb-label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #5d8a3f;
            margin-bottom: 8px;
        }
        .casc-toolbar .form-select { border-radius: 9px; }

        /* Pilihan Mode */
        .mode-switch {
            display: inline-flex;
            background: #eef2ef;
            border: 1px solid #e0e7e2;
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
            flex-wrap: wrap;
        }
        .mode-switch a {
            border: 0;
            background: transparent;
            color: #5d6b62;
            font-weight: 600;
            font-size: .85rem;
            padding: 8px 16px;
            border-radius: 9px;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s ease;
        }
        .mode-switch a:hover { color: #00743e; }
        .mode-switch a.active {
            background: #fff;
            color: #00743e;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
        }

        /* Toggle Tabel / Pohon */
        .casc-viewbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .casc-viewtoggle {
            display: inline-flex;
            background: #eef2ef;
            border: 1px solid #e0e7e2;
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
        }
        .casc-viewtoggle .vt-btn {
            border: 0;
            background: transparent;
            color: #5d6b62;
            font-weight: 600;
            font-size: .85rem;
            padding: 8px 16px;
            border-radius: 9px;
            cursor: pointer;
            transition: all .15s ease;
        }
        .casc-viewtoggle .vt-btn:hover { color: #00743e; }
        .casc-viewtoggle .vt-btn.active {
            background: #fff;
            color: #00743e;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
        }
        .casc-viewtools {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Tabel */
        .casc-table-wrap {
            border: 1px solid #e3e8e4;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(16, 40, 24, .06);
        }
        .casc-table-wrap .table-responsive { max-height: 74vh; }
        .casc-table { margin: 0; font-size: .82rem; border-color: #e6ebe7; }
        .casc-table > :not(caption) > * > * { padding: .68rem .7rem; }
        .casc-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #00713c;
            background-image: linear-gradient(180deg, #04864c 0%, #00713c 100%);
            color: #fff;
            font-weight: 600;
            vertical-align: middle;
            text-align: center;
            font-size: .7rem;
            letter-spacing: .5px;
            text-transform: uppercase;
            border-color: rgba(255, 255, 255, .16);
            box-shadow: inset 0 -2px 0 rgba(0, 0, 0, .12);
        }
        .casc-table tbody td {
            vertical-align: top;
            color: #344039;
            border-color: #eceeec;
            line-height: 1.5;
        }
        /* Sel hierarki (rowspan) diberi latar lembut agar mudah dibaca */
        .casc-table tbody td.text-start { text-align: left; }
        .casc-table tbody td[rowspan] {
            background: #f7faf8;
            font-weight: 500;
            border-left: 1px solid #e2ebe5;
        }
        .casc-table tbody tr:hover td { background: #eef7f1; }
        .casc-table tbody tr:hover td[rowspan] { background: #e7f3ec; }
        .casc-table .text-muted { font-style: italic; opacity: .7; }

        /* Tombol aksi */
        .casc-act {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            border-radius: 10px;
            padding: 0;
        }

        /* Empty state */
        .casc-empty {
            text-align: center;
            padding: 52px 24px;
            border-radius: 16px;
            border: 1px dashed #cfd8d2;
            background: #f8faf9;
            color: #5d6b62;
        }
        .casc-empty .ce-icon {
            font-size: 42px;
            margin-bottom: 14px;
            color: #00743e;
            opacity: .35;
        }
        .casc-empty h5 { font-weight: 700; color: #3a4a40; margin-bottom: 6px; }
        .casc-empty p { font-size: .9rem; margin: 0; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4 casc-paper">

                <!-- HEADER -->
                <div class="casc-head">
                    <div class="casc-icon"><i class="fas fa-sitemap"></i></div>
                    <div>
                        <h2>Pohon Kinerja &amp; Cascading</h2>
                        <p>Penjabaran Tujuan &amp; Sasaran RPJMD hingga Renstra Perangkat Daerah</p>
                    </div>
                </div>

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
                $filters = $filters ?? ['periode' => ''];
                $mode    = $mode ?? 'kabupaten';
                $periode = $filters['periode'] ?? '';
                // Bangun query string konsisten utk tombol cetak
                $cetakQS = 'mode=' . $mode . '&periode=' . urlencode($periode)
                    . ($mode === 'opd' && !empty($opd_id) ? '&opd_id=' . (int) $opd_id : '');
                ?>

                <!-- ===================== MODE ===================== -->
                <div class="casc-toolbar">
                    <div class="tb-label"><i class="fas fa-layer-group me-1"></i>Mode Tampilan</div>
                    <div class="mode-switch mb-3">
                        <a href="<?= base_url('adminkab/cascading?mode=kabupaten&periode=' . urlencode($periode)) ?>"
                            class="<?= $mode === 'kabupaten' ? 'active' : '' ?>">
                            <i class="fas fa-landmark me-1"></i> Kabupaten
                        </a>
                        <a href="<?= base_url('adminkab/cascading?mode=opd&periode=' . urlencode($periode) . (!empty($opd_id) ? '&opd_id=' . (int) $opd_id : '')) ?>"
                            class="<?= $mode === 'opd' ? 'active' : '' ?>">
                            <i class="fas fa-building me-1"></i> OPD (Renstra Lengkap)
                        </a>
                        <!-- <a href="<?= base_url('adminkab/cascading?mode=keseluruhan&periode=' . urlencode($periode)) ?>"
                            class="<?= $mode === 'keseluruhan' ? 'active' : '' ?>">
                            <i class="fas fa-diagram-project me-1"></i> Keseluruhan
                        </a> -->
                    </div>

                    <div class="tb-label"><i class="fas fa-filter me-1"></i>Filter</div>
                    <form id="filterForm" method="GET" action="<?= base_url('adminkab/cascading') ?>"
                        class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center">
                        <input type="hidden" name="mode" value="<?= esc($mode) ?>">

                        <!-- Periode -->
                        <select id="periodeFilter" name="periode" class="form-select" style="flex:1;"
                            onchange="this.form.submit()">
                            <option value="">-- Pilih Periode RPJMD --</option>
                            <?php
                            $periodeOpts = [];
                            foreach (($periode_master ?? []) as $p) {
                                $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
                                $periodeOpts[$key] = $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'];
                            }
                            foreach ($periodeOpts as $key => $label): ?>
                                <option value="<?= esc($key) ?>" <?= ($periode === $key) ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- OPD (hanya mode OPD) -->
                        <?php if ($mode === 'opd'): ?>
                            <select name="opd_id" class="form-select" style="flex:1.4;" onchange="this.form.submit()">
                                <option value="">-- Pilih Perangkat Daerah --</option>
                                <?php foreach (($opd_list ?? []) as $o): ?>
                                    <option value="<?= (int) $o['id'] ?>" <?= ((string) ($opd_id ?? '') === (string) $o['id']) ? 'selected' : '' ?>>
                                        <?= esc($o['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <div class="d-flex gap-2">
                            <a href="<?= base_url('adminkab/cascading?mode=' . $mode) ?>" class="btn btn-outline-secondary text-nowrap">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($periode)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-calendar-days"></i></div>
                        <h5>Pilih Periode Terlebih Dahulu</h5>
                        <p>Silakan pilih periode RPJMD pada filter di atas untuk menampilkan data.</p>
                    </div>

                <?php elseif ($mode === 'opd' && empty($opd_id)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-building"></i></div>
                        <h5>Pilih Perangkat Daerah</h5>
                        <p>Pilih salah satu Perangkat Daerah untuk menampilkan cascade Renstra lengkapnya (Eselon II / III / IV).</p>
                    </div>

                <?php elseif (empty($rows)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-folder-open"></i></div>
                        <h5>Belum Ada Data</h5>
                        <p>Tidak ditemukan data untuk pilihan saat ini.</p>
                    </div>

                <?php else: ?>

                    <!-- TOGGLE TAMPILAN -->
                    <div class="casc-viewbar">
                        <div class="casc-viewtoggle" role="tablist">
                            <button type="button" class="vt-btn active" data-view="tabel">
                                <i class="fas fa-table-cells me-1"></i> Tabel Cascading
                            </button>
                            <button type="button" class="vt-btn" data-view="pohon">
                                <i class="fas fa-sitemap me-1"></i> Pohon Kinerja
                            </button>
                        </div>
                        <!-- Tools tab Tabel Cascading -->
                        <div class="casc-viewtools" id="tabelTools">
                            <a href="<?= base_url('adminkab/cascading/cetak?' . $cetakQS) ?>"
                                target="_blank" class="btn btn-sm btn-danger text-nowrap">
                                <i class="fas fa-file-pdf me-1"></i> Cetak Cascading
                            </a>
                        </div>
                        <!-- Tools tab Pohon Kinerja -->
                        <div class="casc-viewtools" id="pohonTools" hidden>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(-1)" title="Perkecil">
                                <i class="fas fa-magnifying-glass-minus"></i>
                            </button>
                            <span id="pohonZoomLbl" class="small text-muted" style="min-width:42px;text-align:center;">60%</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(1)" title="Perbesar">
                                <i class="fas fa-magnifying-glass-plus"></i>
                            </button>
                            <a href="<?= base_url('adminkab/cascading/cetak-pohon?' . $cetakQS) ?>"
                                target="_blank" class="btn btn-sm btn-success text-nowrap">
                                <i class="fas fa-print me-1"></i> Cetak Pohon
                            </a>
                        </div>
                    </div>

                    <!-- ============== VIEW: TABEL ============== -->
                    <div id="view-tabel">
                        <?php if ($mode === 'kabupaten'): ?>
                            <?= $this->include('adminKabupaten/cascading/_tabel_kabupaten') ?>
                        <?php elseif ($mode === 'opd'): ?>
                            <?= $this->include('adminKabupaten/cascading/_tabel_opd') ?>
                        <?php else: ?>
                            <?= $this->include('adminKabupaten/cascading/_tabel_keseluruhan') ?>
                        <?php endif; ?>
                    </div>

                    <!-- ============== VIEW: POHON KINERJA ============== -->
                    <div id="view-pohon" hidden>
                        <?php if (empty($tree ?? [])): ?>
                            <div class="casc-empty">
                                <div class="ce-icon"><i class="fas fa-diagram-project"></i></div>
                                <h5>Pohon Kinerja Belum Tersedia</h5>
                                <p>Belum ada data untuk membentuk pohon kinerja pada pilihan ini.</p>
                            </div>
                        <?php elseif ($mode === 'kabupaten'): ?>
                            <?= $this->include('adminKabupaten/cascading/_pohon_tree') ?>
                        <?php elseif ($mode === 'opd'): ?>
                            <?= $this->include('adminOpd/cascading/_pohon_opd_tree') ?>
                        <?php else: ?>
                            <?= $this->include('adminKabupaten/cascading/_pohon_tree_keseluruhan') ?>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>

    <!-- Toggle Tabel / Pohon Kinerja + Zoom -->
    <script>
        (function () {
            const btns = document.querySelectorAll('.vt-btn');
            const vTabel = document.getElementById('view-tabel');
            const vPohon = document.getElementById('view-pohon');
            const toolsPohon = document.getElementById('pohonTools');
            const toolsTabel = document.getElementById('tabelTools');

            btns.forEach(b => b.addEventListener('click', () => {
                btns.forEach(x => x.classList.remove('active'));
                b.classList.add('active');
                const v = b.dataset.view;
                if (vTabel) vTabel.hidden = (v !== 'tabel');
                if (vPohon) vPohon.hidden = (v !== 'pohon');
                if (toolsTabel) toolsTabel.hidden = (v !== 'tabel');
                if (toolsPohon) toolsPohon.hidden = (v !== 'pohon');
                if (v === 'pohon') pohonZoom(0); // terapkan skala setelah pohon tampil (offset valid)
            }));
        })();

        let _pohonZoom = 0.60;
        function pohonZoom(dir) {
            _pohonZoom = Math.min(1.2, Math.max(0.3, _pohonZoom + dir * 0.1));
            const t = document.getElementById('tree-container');
            if (t) {
                // Pakai transform:scale (BUKAN zoom) agar tak muncul kotak hitam
                // (bug render Chromium: zoom + gradient + box-shadow pada banyak node).
                t.style.zoom = '';
                t.style.transformOrigin = 'top left';
                t.style.transform = 'scale(' + _pohonZoom + ')';
                // Transform tak mengubah layout box -> kompensasi agar tak ada ruang kosong.
                const natW = t.offsetWidth, natH = t.offsetHeight;
                t.style.marginRight  = (natW * (_pohonZoom - 1)) + 'px';
                t.style.marginBottom = (natH * (_pohonZoom - 1)) + 'px';
            }
            const lbl = document.getElementById('pohonZoomLbl');
            if (lbl) lbl.textContent = Math.round(_pohonZoom * 100) + '%';
        }
        document.addEventListener('DOMContentLoaded', () => pohonZoom(0));

        // Deep-link dari menu: buka tab Pohon Kinerja bila URL berakhir #pohon
        document.addEventListener('DOMContentLoaded', function () {
            if (location.hash === '#pohon') {
                var pb = document.querySelector('.vt-btn[data-view="pohon"]');
                if (pb) pb.click();
            }
        });
    </script>

</body>

</html>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'POHON KINERJA KABUPATEN') ?></title>
    <?= $this->include('user/templates/style.php'); ?>
    <?= $this->include('adminKabupaten/cascading/_pohon_styles'); ?>

    <style>
        /* ===================== Polish layar pohon kinerja (publik) ===================== */
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
            font-size: 1.4rem;
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

        .casc-viewbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .casc-viewtools {
            display: flex;
            align-items: center;
            gap: 8px;
        }
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

    <?= $this->include('user/templates/header'); ?>

    <main class="flex-grow-1 d-flex flex-column align-items-center my-5">
        <div class="container-fluid" style="max-width: 1700px;">
            <div class="bg-white p-4 rounded shadow-sm casc-paper">

                <!-- HEADER -->
                <div class="casc-head">
                    <div class="casc-icon"><i class="fas fa-sitemap"></i></div>
                    <div>
                        <h2>Pohon Kinerja Kabupaten</h2>
                        <p>Penjabaran Visi, Misi, Tujuan &amp; Sasaran RPJMD dalam bentuk Pohon Kinerja</p>
                    </div>
                </div>

                <?php
                $filters = $filters ?? [
                    'periode' => '',
                ];
                ?>

                <!-- ===================== FORM FILTER ===================== -->
                <div class="casc-toolbar">
                    <div class="tb-label"><i class="fas fa-filter me-1"></i>Filter Periode RPJMD</div>
                    <form id="filterForm" method="GET" action="<?= base_url('pohon_kinerja_kabupaten') ?>"
                        class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center w-100">
                        <!-- Periode -->
                        <select id="periodeFilter" name="periode" class="form-select flex-fill"
                            onchange="this.form.submit()">
                            <option value="">-- Pilih Periode --</option>
                            <?php
                            $periodeList = [];
                            if (!empty($periode_master ?? [])) {
                                foreach ($periode_master as $p) {
                                    $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
                                    $periodeList[$key] = $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'];
                                }
                            }
                            foreach ($periodeList as $key => $label): ?>
                                <option value="<?= esc($key) ?>" <?= ($filters['periode'] === $key) ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="d-flex gap-2">
                            <noscript><button type="submit" class="btn btn-success">Filter</button></noscript>
                            <a href="<?= base_url('pohon_kinerja_kabupaten') ?>"
                                class="btn btn-outline-secondary text-nowrap">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($filters['periode'])): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-calendar-days"></i></div>
                        <h5>Pilih Periode Terlebih Dahulu</h5>
                        <p>Silakan pilih periode RPJMD pada filter di atas untuk menampilkan Pohon Kinerja.</p>
                    </div>

                <?php elseif (empty($tree ?? [])): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-diagram-project"></i></div>
                        <h5>Pohon Kinerja Belum Tersedia</h5>
                        <p>Belum ada data Misi/Tujuan/Sasaran RPJMD untuk periode yang dipilih.</p>
                    </div>

                <?php else: ?>

                    <!-- TOOLBAR POHON: Zoom + Cetak -->
                    <div class="casc-viewbar">
                        <div class="casc-viewtools">
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(-1)" title="Perkecil">
                                <i class="fas fa-magnifying-glass-minus"></i>
                            </button>
                            <span id="pohonZoomLbl" class="small text-muted" style="min-width:42px;text-align:center;">70%</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(1)" title="Perbesar">
                                <i class="fas fa-magnifying-glass-plus"></i>
                            </button>
                            <a href="<?= base_url('pohon_kinerja_kabupaten/cetak?periode=' . $filters['periode']) ?>"
                                target="_blank" class="btn btn-sm btn-success text-nowrap">
                                <i class="fas fa-print me-1"></i> Cetak Pohon
                            </a>
                        </div>
                    </div>

                    <!-- ============== POHON KINERJA ============== -->
                    <?= $this->include('adminKabupaten/cascading/_pohon_tree') ?>

                <?php endif; ?>

            </div>
        </div>
    </main>

    <?= $this->include('user/templates/footer'); ?>

    <!-- Zoom Pohon Kinerja -->
    <script>
        let _pohonZoom = 0.70;
        function pohonZoom(dir) {
            _pohonZoom = Math.min(1.2, Math.max(0.3, _pohonZoom + dir * 0.1));
            const t = document.getElementById('tree-container');
            if (t) t.style.zoom = _pohonZoom;
            const lbl = document.getElementById('pohonZoomLbl');
            if (lbl) lbl.textContent = Math.round(_pohonZoom * 100) + '%';
        }
        document.addEventListener('DOMContentLoaded', () => pohonZoom(0));
    </script>

</body>

</html>

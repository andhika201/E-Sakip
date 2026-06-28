<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING KABUPATEN') ?></title>
    <?= $this->include('user/templates/style.php'); ?>
    <?= $this->include('adminKabupaten/cascading/_pohon_styles'); ?>

    <style>
        /* ===================== Polish layar cascading (publik) ===================== */
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
        .casc-act {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            border-radius: 10px;
            padding: 0;
        }

        /* Tabel */
        .casc-table-wrap {
            border: 1px solid #e3e8e4;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(16, 40, 24, .06);
        }
        .casc-table { margin: 0; font-size: .8rem; }
        .casc-table > :not(caption) > * > * { padding: .62rem .6rem; }
        .casc-table thead th {
            background: linear-gradient(180deg, #00824a 0%, #00743e 100%);
            color: #fff;
            font-weight: 600;
            vertical-align: middle;
            text-align: center;
            font-size: .7rem;
            letter-spacing: .4px;
            text-transform: uppercase;
            border-color: rgba(255, 255, 255, .18);
        }
        .casc-table tbody td {
            vertical-align: middle;
            color: #344039;
            border-color: #e8ede9;
            line-height: 1.4;
        }
        .casc-table tbody tr:hover td { background: #f1f8f3; }

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
                        <h2>Pohon Kinerja &amp; Cascading Kabupaten</h2>
                        <p>Penjabaran Tujuan &amp; Sasaran RPJMD ke Program Perangkat Daerah</p>
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
                    <form id="filterForm" method="GET" action="<?= base_url('cascading_kabupaten') ?>"
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
                            <a href="<?= base_url('cascading_kabupaten') ?>"
                                class="btn btn-outline-secondary text-nowrap">
                                <i class="fas fa-undo"></i> Reset
                            </a>

                            <?php if (!empty($filters['periode']) && !empty($rows)): ?>
                                <a href="<?= base_url('cascading_kabupaten/cetak?periode=' . $filters['periode']) ?>"
                                    class="btn btn-danger text-nowrap" target="_blank">
                                    <i class="fas fa-file-pdf me-1"></i> Cetak Cascading
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($filters['periode'])): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-calendar-days"></i></div>
                        <h5>Pilih Periode Terlebih Dahulu</h5>
                        <p>Silakan pilih periode RPJMD pada filter di atas untuk menampilkan data Cascading &amp; Pohon Kinerja.</p>
                    </div>

                <?php elseif (empty($rows)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-folder-open"></i></div>
                        <h5>Belum Ada Data Cascading</h5>
                        <p>Tidak ditemukan data Cascading untuk periode yang dipilih.</p>
                    </div>

                <?php else: ?>

                    <?php
                    [$start, $end] = explode('-', $filters['periode']);
                    $start = (int) trim($start);
                    $end = (int) trim($end);
                    $yearCount = $end - $start + 1;
                    ?>

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
                        <div class="casc-viewtools" id="pohonTools" hidden>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(-1)" title="Perkecil">
                                <i class="fas fa-magnifying-glass-minus"></i>
                            </button>
                            <span id="pohonZoomLbl" class="small text-muted" style="min-width:42px;text-align:center;">70%</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(1)" title="Perbesar">
                                <i class="fas fa-magnifying-glass-plus"></i>
                            </button>
                            <a href="<?= base_url('cascading_kabupaten/cetak-pohon?periode=' . $filters['periode']) ?>"
                                target="_blank" class="btn btn-sm btn-success text-nowrap">
                                <i class="fas fa-print me-1"></i> Cetak Pohon
                            </a>
                        </div>
                    </div>

                    <!-- ============== VIEW: TABEL ============== -->
                    <div id="view-tabel">
                        <div class="casc-table-wrap">
                            <div class="table-responsive">
                                <table class="table table-bordered text-center align-middle casc-table mb-0">
                                    <thead class="text-center">
                                        <tr>
                                            <th rowspan="2">Tujuan</th>
                                            <th rowspan="2">CSF</th>
                                            <th rowspan="2">Sasaran</th>
                                            <th rowspan="2">Indikator</th>
                                            <th rowspan="2">Satuan</th>
                                            <th rowspan="2">Baseline</th>

                                            <th colspan="<?= count($years) ?>">Target</th>

                                            <th rowspan="2">Program</th>
                                            <th rowspan="2">OPD</th>
                                        </tr>

                                        <tr>
                                            <?php foreach ($years as $y): ?>
                                                <th><?= $y ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($rows as $index => $r): ?>
                                            <tr>

                                                <!-- TUJUAN -->
                                                <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>" class="text-start">
                                                        <?= esc($r['tujuan_rpjmd']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- CSF -->
                                                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="p-2 text-start"
                                                        style="min-width:180px;">
                                                        <?= esc($r['csf'] ?? '-') ?>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- SASARAN -->
                                                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" class="text-start">
                                                        <?= esc($r['sasaran_rpjmd']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <!-- INDIKATOR -->
                                                <?php if ($firstShow['indikator'][$r['indikator_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                                                        <?= esc($r['indikator_sasaran']) ?>
                                                    </td>

                                                    <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                        <?= esc($r['satuan']) ?>
                                                    </td>

                                                    <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                        <?= esc($r['baseline']) ?>
                                                    </td>

                                                    <?php foreach ($years as $y): ?>
                                                        <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                            <?= esc($r['targets'][$y] ?? '-') ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>

                                                <!-- PROGRAM -->
                                                <td class="text-start">
                                                    <?= $r['program_kegiatan'] ?? '-' ?>
                                                </td>

                                                <!-- OPD -->
                                                <?php
                                                $key = $r['indikator_id'] . '-' . $r['nama_opd'];
                                                ?>

                                                <?php if ($firstShow['opd'][$key] == $index): ?>
                                                    <td rowspan="<?= $rowspan['opd'][$key] ?? 1 ?>" class="text-start">
                                                        <?= esc($r['nama_opd']) ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ============== VIEW: POHON KINERJA ============== -->
                    <div id="view-pohon" hidden>
                        <?php if (empty($tree ?? [])): ?>
                            <div class="casc-empty">
                                <div class="ce-icon"><i class="fas fa-diagram-project"></i></div>
                                <h5>Pohon Kinerja Belum Tersedia</h5>
                                <p>Belum ada data Misi/Tujuan/Sasaran RPJMD untuk periode ini.</p>
                            </div>
                        <?php else: ?>
                            <?= $this->include('adminKabupaten/cascading/_pohon_tree') ?>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </main>

    <?= $this->include('user/templates/footer'); ?>

    <!-- Toggle Tabel / Pohon Kinerja + Zoom -->
    <script>
        (function () {
            const btns = document.querySelectorAll('.vt-btn');
            const vTabel = document.getElementById('view-tabel');
            const vPohon = document.getElementById('view-pohon');
            const tools = document.getElementById('pohonTools');

            btns.forEach(b => b.addEventListener('click', () => {
                btns.forEach(x => x.classList.remove('active'));
                b.classList.add('active');
                const v = b.dataset.view;
                if (vTabel) vTabel.hidden = (v !== 'tabel');
                if (vPohon) vPohon.hidden = (v !== 'pohon');
                if (tools) tools.hidden = (v !== 'pohon');
            }));
        })();

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

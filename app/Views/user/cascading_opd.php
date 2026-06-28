<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING OPD') ?></title>
    <?= $this->include('user/templates/style.php'); ?>
    <?= $this->include('adminOpd/cascading/_pohon_opd_styles'); ?>

    <style>
        /* ===================== Polish layar cascading OPD (publik) ===================== */
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
            font-size: .68rem;
            letter-spacing: .3px;
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
                        <h2>Pohon Kinerja &amp; Cascading Perangkat Daerah</h2>
                        <p>Penjabaran Kinerja RPJMD &rarr; Renstra &rarr; Eselon II / III / IV</p>
                    </div>
                </div>

                <?php
                $filters = $filters ?? [
                    'periode' => '',
                    'opd_id' => ''
                ];
                ?>

                <!-- ====================== FILTER ====================== -->
                <div class="casc-toolbar">
                    <div class="tb-label"><i class="fas fa-filter me-1"></i>Filter Perangkat Daerah &amp; Periode</div>
                    <form method="GET" action="<?= base_url('cascading_opd') ?>"
                        class="row g-2 align-items-center">

                        <!-- Filter OPD -->
                        <div class="col-12 col-md-5">
                            <select name="opd_id" class="form-select w-100" onchange="this.form.submit()">
                                <option value="">-- Pilih Perangkat Daerah --</option>
                                <?php foreach ($opdList as $opd): ?>
                                    <option value="<?= $opd['id'] ?>" <?= ($filters['opd_id'] == $opd['id']) ? 'selected' : '' ?>>
                                        <?= esc($opd['nama_opd']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter Periode -->
                        <div class="col-12 col-md-4">
                            <select name="periode" class="form-select w-100" onchange="this.form.submit()">
                                <option value="">-- Pilih Periode --</option>
                                <?php foreach ($periode_master ?? [] as $p): ?>
                                    <?php $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir']; ?>
                                    <option value="<?= $key ?>" <?= ($filters['periode'] == $key) ? 'selected' : '' ?>>
                                        <?= $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-auto d-flex gap-2">
                            <noscript><button type="submit" class="btn btn-success"><i class="fas fa-filter me-1"></i> Filter</button></noscript>
                            <a href="<?= base_url('cascading_opd') ?>" class="btn btn-outline-secondary text-nowrap">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                            <?php if (!empty($filters['periode']) && !empty($filters['opd_id']) && !empty($rows)): ?>
                                <a href="<?= base_url('cascading_opd/cetak?periode=' . $filters['periode'] . '&opd_id=' . $filters['opd_id']) ?>"
                                    class="btn btn-danger text-nowrap" target="_blank">
                                    <i class="fas fa-file-pdf me-1"></i> Cetak Cascading
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- ====================== DATA ====================== -->
                <?php if (empty($filters['periode']) || empty($filters['opd_id'])): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-building-flag"></i></div>
                        <h5>Pilih Perangkat Daerah &amp; Periode</h5>
                        <p>Silakan pilih Perangkat Daerah dan Periode terlebih dahulu untuk menampilkan Cascading &amp; Pohon Kinerja.</p>
                    </div>

                <?php elseif (empty($rows)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-folder-open"></i></div>
                        <h5>Belum Ada Data Cascading</h5>
                        <p>Tidak ditemukan data Cascading untuk filter yang dipilih.</p>
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
                        <div class="casc-viewtools" id="pohonTools" hidden>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(-1)" title="Perkecil">
                                <i class="fas fa-magnifying-glass-minus"></i>
                            </button>
                            <span id="pohonZoomLbl" class="small text-muted" style="min-width:42px;text-align:center;">60%</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(1)" title="Perbesar">
                                <i class="fas fa-magnifying-glass-plus"></i>
                            </button>
                            <a href="<?= base_url('cascading_opd/cetak-pohon?periode=' . $filters['periode'] . '&opd_id=' . $filters['opd_id']) ?>"
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
                                            <th>Tujuan RPJMD</th>
                                            <th>Sasaran RPJMD</th>
                                            <th>Tujuan RENSTRA</th>

                                            <th>CSF ESS II</th>
                                            <th>Sasaran ESS II</th>
                                            <th>Indikator ESS II</th>

                                            <th>CSF ESS III</th>
                                            <th>Sasaran ESS III</th>
                                            <th>Indikator ESS III</th>

                                            <th>Sasaran ESS IV / JF</th>
                                            <th>Indikator ESS IV</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($rows as $index => $r): ?>
                                            <tr>
                                                <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>" class="text-start">
                                                        <?= esc($r['tujuan_rpjmd']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>" class="text-start">
                                                        <?= esc($r['sasaran_rpjmd']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>" class="text-start">
                                                        <?= esc($r['renstra_tujuan']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="p-2 text-start">
                                                        <?= esc($r['csf_es2'] ?? '-') ?>
                                                    </td>
                                                    <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="text-start">
                                                        <?= esc($r['renstra_sasaran']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                                    <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                                                        <?= esc($r['indikator_sasaran'] ?? '-') ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if (empty($r['es3_id'])): ?>
                                                    <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                                        <td colspan="3" class="text-center text-muted">-</td>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                                                        <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="p-2 text-start">
                                                            <?= esc($r['csf_es3'] ?? '-') ?>
                                                        </td>
                                                        <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-start">
                                                            <?= esc($r['es3_sasaran']) ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                                                    <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                        <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>" class="text-start">
                                                            <?= esc($r['es3_indikator']) ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (!empty($r['es3_id']) && empty($r['es4_id'])): ?>
                                                    <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                        <td colspan="2" class="text-center text-muted">-</td>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                                                        <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                                            <?= esc($r['es4_sasaran']) ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td class="text-start">
                                                        <?= $r['es4_indikator'] ?? '-' ?>
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
                                <p>Belum ada data cascading Renstra/Eselon untuk Perangkat Daerah &amp; periode ini.</p>
                            </div>
                        <?php else: ?>
                            <?= $this->include('adminOpd/cascading/_pohon_opd_tree') ?>
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

        let _pohonZoom = 0.60;
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

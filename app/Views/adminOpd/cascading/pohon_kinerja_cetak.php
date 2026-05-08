<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pohon Kinerja OPD</title>
    <!-- Include Bootstrap for basic typography and modern reset -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .print-header {
            text-align: center;
            margin-bottom: 30px;
        }

        /* Tree Styles */
        .tree-container {
            overflow-x: auto;
            padding-bottom: 30px;
        }

        .tree {
            display: inline-block;
            min-width: 100%;
        }

        .tree ul {
            padding-top: 20px;
            position: relative;
            display: flex;
            justify-content: center;
            padding-left: 0;
        }

        .tree li {
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Connecting lines */
        .tree li::before, .tree li::after {
            content: '';
            position: absolute; 
            top: 0; 
            right: 50%;
            border-top: 2px solid #b0bec5;
            width: 50%; 
            height: 20px;
        }
        
        .tree li::after {
            right: auto; 
            left: 50%;
            border-left: 2px solid #b0bec5;
        }

        /* Edge formatting */
        .tree li:only-child::after, .tree li:only-child::before {
            display: none;
        }

        .tree li:only-child {
            padding-top: 0;
        }

        .tree li:first-child::before, .tree li:last-child::after {
            border: 0 none;
        }

        .tree li:last-child::before {
            border-right: 2px solid #b0bec5;
            border-radius: 0 5px 0 0;
        }

        .tree li:first-child::after {
            border-radius: 5px 0 0 0;
        }

        /* Downward line from parents */
        .tree ul ul::before {
            content: '';
            position: absolute; 
            top: 0; 
            left: 50%;
            border-left: 2px solid #b0bec5;
            width: 0; 
            height: 20px;
            transform: translateX(-50%);
        }

        .tree-node {
            display: inline-flex;
            flex-direction: column;
            align-items: stretch;
            gap: 5px;
            width: clamp(120px, 14vw, 180px);
            transition: all 0.3s;
        }

        /* L1: Tujuan RPJMD - Teal */
        .box-l1 {
            background: linear-gradient(135deg, #00b8a9 0%, #008f83 100%);
            color: #fff;
            border-radius: 12px;
            padding: clamp(6px, 1.2vw, 12px);
            font-weight: 700;
            font-size: clamp(9px, 1.1vw, 13px);
            box-shadow: 0 4px 6px rgba(0, 184, 169, 0.3);
            border: 2px solid #fff;
        }

        /* L2: Sasaran RPJMD - Darker Teal/Green */
        .box-l2 {
            background: linear-gradient(135deg, #00897b 0%, #00695c 100%);
            color: #fff;
            border-radius: 10px;
            padding: clamp(5px, 1vw, 10px);
            font-size: clamp(9px, 1.1vw, 13px);
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(0, 137, 123, 0.3);
        }

        /* L3: Tujuan Renstra - Blue */
        .box-l3 {
            background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
            color: #fff;
            border-radius: 8px;
            padding: clamp(5px, 1vw, 10px);
            font-size: clamp(8px, 1vw, 12px);
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(30, 136, 229, 0.3);
        }

        /* L4/L5: Sasaran ESS (Brown/Orange) */
        .box-sasaran {
            background: linear-gradient(135deg, #6d4c41 0%, #4e342e 100%);
            color: #fff;
            border-radius: 8px;
            padding: clamp(5px, 1vw, 10px);
            font-size: clamp(8px, 1vw, 12px);
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(109, 76, 65, 0.3);
        }

        /* Box Indikator - Orange */
        .box-iks {
            background: #f57c00;
            color: #fff;
            border-radius: 6px;
            padding: clamp(4px, 0.8vw, 6px);
            font-size: clamp(7px, 0.9vw, 11px);
            margin-top: -3px;
            box-shadow: 0 2px 4px rgba(245, 124, 0, 0.3);
            text-align: left;
        }

        /* Box CSF - Yellow */
        .box-csf {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffb74d;
            border-radius: 6px;
            padding: clamp(4px, 0.8vw, 6px);
            font-size: clamp(7px, 0.85vw, 10px);
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(255, 183, 77, 0.2);
            margin-bottom: 2px;
            text-align: left;
        }

        /* ===== PRINT CONTROLS ===== */
        .print-controls {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            background: white;
            padding: 14px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            width: clamp(170px, 22vw, 220px);
            max-height: calc(100vh - 32px);
            overflow-y: auto;
        }
        .print-controls label {
            font-size: clamp(10px, 1vw, 11px);
            font-weight: 600;
            color: #555;
            margin-bottom: 3px;
            display: block;
        }
        .print-controls select {
            font-size: clamp(11px, 1.1vw, 12px);
            padding: 5px 8px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            width: 100%;
            margin-bottom: 8px;
        }
        .zoom-info {
            font-size: 10px;
            color: #888;
            text-align: center;
            margin-top: 4px;
        }

        /* Toggle button for mobile */
        .controls-toggle {
            display: none;
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 1100;
            background: #1e88e5;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0,0,0,0.25);
            align-items: center;
            justify-content: center;
        }

        /* ===== LEGENDA ===== */
        .legenda-wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px 12px;
            margin-bottom: 24px;
            padding: 10px 16px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .legenda-title {
            font-size: clamp(10px, 1.1vw, 11px);
            font-weight: 700;
            color: #555;
            align-self: center;
            margin-right: 4px;
        }
        .legenda-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legenda-swatch {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        .legenda-item span {
            font-size: clamp(10px, 1.1vw, 11px);
            color: #333;
        }

        /* ===== PRINT HEADER ===== */
        .print-header h2 {
            font-size: clamp(16px, 3vw, 26px);
        }
        .print-header p {
            font-size: clamp(12px, 1.8vw, 16px);
        }

        /* ===== RESPONSIVE BREAKPOINTS ===== */
        @media (max-width: 768px) {
            body { padding: 12px; }

            .controls-toggle { display: flex; }

            .print-controls {
                top: 0;
                right: 0;
                width: 100%;
                max-width: 100%;
                border-radius: 0 0 12px 12px;
                display: none;
                max-height: 70vh;
            }
            .print-controls.open { display: block; }

            .legenda-wrap { gap: 6px 10px; padding: 8px 10px; }
            .legenda-swatch { width: 14px; height: 14px; }

            .tree-node { width: clamp(90px, 28vw, 140px); }
        }

        @media (max-width: 480px) {
            .tree-node { width: clamp(80px, 32vw, 120px); }
            .box-l1, .box-l2, .box-l3, .box-sasaran { border-radius: 6px; }
        }

        /* Dynamic @page – dioverride oleh JS */
        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding-bottom: 14mm;
            }
            .print-controls, .controls-toggle {
                display: none !important;
            }
            .tree-container {
                overflow: visible !important;
                width: 100%;
            }
            .tree-node {
                page-break-inside: avoid;
            }
            .tree li {
                padding: 12px 3px 0 3px;
            }
            .box-l1, .box-l2, .box-l3, .box-sasaran { box-shadow: none; }
            .box-iks, .box-csf { box-shadow: none; }
            .wm-footer {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                padding: 5px 10mm;
                background: #fff;
                border-top: 1px solid #ccc;
                justify-content: space-between;
                align-items: center;
                font-family: Arial, sans-serif;
                font-size: 8pt;
                color: #888;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .wm-footer .wm-left  { font-style: italic; }
            .wm-footer .wm-right { font-weight: 600; color: #aaa; }
        }
    </style>
</head>
<body>

    <!-- Mobile toggle untuk print-controls -->
    <button class="controls-toggle" id="controlsToggle" onclick="toggleControls()" title="Pengaturan Cetak">
        <i class="fas fa-sliders-h"></i>
    </button>

    <div class="print-controls" id="printControls">
        <div class="fw-bold mb-2" style="font-size:13px; color:#333;">
            <i class="fas fa-print me-1 text-primary"></i> Pengaturan Cetak
        </div>

        <label for="selectKertas">Ukuran Kertas</label>
        <select id="selectKertas" onchange="updatePrintStyle()">
            <option value="A4">A4 (210 × 297 mm)</option>
            <option value="A3">A3 (297 × 420 mm)</option>
            <option value="F4">F4 / Folio (215 × 330 mm)</option>
            <option value="legal">Legal (216 × 356 mm)</option>
        </select>

        <label for="selectOrientasi">Orientasi</label>
        <select id="selectOrientasi" onchange="updatePrintStyle()">
            <option value="landscape" selected>Landscape</option>
            <option value="portrait">Portrait</option>
        </select>

        <label for="selectZoom">Zoom Pohon</label>
        <select id="selectZoom" onchange="updateZoom()">
            <option value="0.35">35% — Sangat Kecil</option>
            <option value="0.45">45% — Kecil</option>
            <option value="0.55" selected>55% — Normal</option>
            <option value="0.70">70% — Besar</option>
            <option value="0.85">85% — Sangat Besar</option>
            <option value="1.00">100% — Penuh</option>
        </select>

        <button onclick="doCetak()" class="btn btn-primary btn-sm w-100 mb-2">
            <i class="fas fa-print"></i> Cetak Sekarang
        </button>
        <button onclick="tutupHalaman()" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-times"></i> Tutup
        </button>
        <div class="zoom-info" id="zoomInfo">Zoom: 55%</div>
    </div>

    <div class="print-header">
        <h2 class="fw-bold mb-1">POHON KINERJA OPD</h2>
        <p class="text-muted mb-0" style="font-size: 16px;">Periode <?= esc($tahun_mulai) ?> - <?= esc($tahun_akhir) ?></p>
    </div>

    <!-- LEGENDA WARNA -->
    <div class="legenda-wrap">
        <span class="legenda-title">Keterangan:</span>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: linear-gradient(135deg, #00b8a9, #008f83);"></div>
            <span>Tujuan RPJMD</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: linear-gradient(135deg, #00897b, #00695c);"></div>
            <span>Sasaran RPJMD</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: linear-gradient(135deg, #1e88e5, #1565c0);"></div>
            <span>Tujuan Renstra</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: linear-gradient(135deg, #6d4c41, #4e342e);"></div>
            <span>Sasaran Eselon II</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: linear-gradient(135deg, #7b1fa2, #4a148c);"></div>
            <span>Sasaran Eselon III</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: linear-gradient(135deg, #1565c0, #0d47a1);"></div>
            <span>Sasaran Eselon IV</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: #f57c00;"></div>
            <span>Indikator Kinerja</span>
        </div>

        <div class="legenda-item">
            <div class="legenda-swatch" style="background: #fff3e0; border: 1px solid #ffb74d;"></div>
            <span>CSF (Critical Success Factor)</span>
        </div>
    </div>

    <div class="tree-container text-center">
        <div class="tree" id="tree-container">
            <ul>
                <?php foreach ($tree as $tujuanRpjmd): ?>
                    <li>
                        <!-- L1: Tujuan RPJMD -->
                        <div class="tree-node">
                            <div class="box-l1">
                                <div class="opacity-75 mb-1" style="font-size: 9px; font-weight: normal;">Tujuan RPJMD</div>
                                <?= nl2br(esc($tujuanRpjmd['nama'])) ?>
                            </div>
                        </div>

                        <?php if (!empty($tujuanRpjmd['sasarans'])): ?>
                            <ul>
                                <?php foreach ($tujuanRpjmd['sasarans'] as $sasaranRpjmd): ?>
                                    <li>
                                        <!-- L2: Sasaran RPJMD -->
                                        <div class="tree-node">
                                            <div class="box-l2">
                                                <div class="opacity-75 mb-1" style="font-size: 9px; font-weight: normal;">Sasaran RPJMD</div>
                                                <?= nl2br(esc($sasaranRpjmd['nama'])) ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($sasaranRpjmd['tujuan_renstras'])): ?>
                                            <ul>
                                                <?php foreach ($sasaranRpjmd['tujuan_renstras'] as $tujuanRenstra): ?>
                                                    <li>
                                                        <!-- L3: Tujuan Renstra -->
                                                        <div class="tree-node">
                                                            <div class="box-l3">
                                                                <div class="opacity-75 mb-1" style="font-size: 9px; font-weight: normal;">Tujuan Renstra</div>
                                                                <?= nl2br(esc($tujuanRenstra['nama'])) ?>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($tujuanRenstra['es2s'])): ?>
                                                            <ul>
                                                                <?php foreach ($tujuanRenstra['es2s'] as $es2): ?>
                                                                    <li>
                                                                        <!-- L4: Sasaran ESS II -->
                                                                        <div class="tree-node">
                                                                            <?php if (!empty($es2['csf'])): ?>
                                                                                <div class="box-csf">
                                                                                    <?= nl2br(esc($es2['csf'])) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <div class="box-sasaran">
                                                                                <?= nl2br(esc($es2['nama'])) ?>
                                                                            </div>
                                                                            <?php foreach ($es2['indikators'] as $indikatorEs2): ?>
                                                                                <div class="box-iks">
                                                                                    <?= nl2br(esc($indikatorEs2)) ?>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>

                                                                        <?php if (!empty($es2['es3s'])): ?>
                                                                            <ul>
                                                                                <?php foreach ($es2['es3s'] as $es3): ?>
                                                                                    <li>
                                                                                        <!-- L5: Sasaran ESS III -->
                                                                                        <div class="tree-node">
                                                                                            <?php if (!empty($es3['csf'])): ?>
                                                                                                <div class="box-csf">
                                                                                                    <?= nl2br(esc($es3['csf'])) ?>
                                                                                                </div>
                                                                                            <?php endif; ?>
                                                                                            <div class="box-sasaran" style="background: linear-gradient(135deg, #7b1fa2 0%, #4a148c 100%);">
                                                                                                <?= nl2br(esc($es3['nama'])) ?>
                                                                                            </div>
                                                                                            <?php foreach ($es3['indikators'] as $indikatorEs3): ?>
                                                                                                <div class="box-iks" style="background: #8e24aa;">
                                                                                                    <?= nl2br(esc($indikatorEs3)) ?>
                                                                                                </div>
                                                                                            <?php endforeach; ?>
                                                                                        </div>

                                                                                        <?php if (!empty($es3['es4s'])): ?>
                                                                                            <ul>
                                                                                                <?php foreach ($es3['es4s'] as $es4): ?>
                                                                                                    <li>
                                                                                                        <!-- L6: Sasaran ESS IV -->
                                                                                                        <div class="tree-node">
                                                                                                            <?php if (!empty($es4['csf'])): ?>
                                                                                                                <div class="box-csf">
                                                                                                                    <?= nl2br(esc($es4['csf'])) ?>
                                                                                                                </div>
                                                                                                            <?php endif; ?>
                                                                                                            <div class="box-sasaran" style="background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);">
                                                                                                                <?= nl2br(esc($es4['nama'])) ?>
                                                                                                            </div>
                                                                                                            <?php foreach ($es4['indikators'] as $indikatorEs4): ?>
                                                                                                                <div class="box-iks" style="background: #1976d2;">
                                                                                                                    <?= nl2br(esc($indikatorEs4)) ?>
                                                                                                                </div>
                                                                                                            <?php endforeach; ?>
                                                                                                        </div>
                                                                                                    </li>
                                                                                                <?php endforeach; ?>
                                                                                            </ul>
                                                                                        <?php endif; ?>
                                                                                    </li>
                                                                                <?php endforeach; ?>
                                                                            </ul>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- WATERMARK FOOTER -->
    <div class="wm-footer">
        <span class="wm-left">
            &copy; <?= esc($nama_opd ?? 'Perangkat Daerah') ?> &mdash; E-Sakip &bull; Dicetak: <?= date('d/m/Y H:i') ?>
        </span>
        <span class="wm-right">Print by Aksara</span>
    </div>

</body>

<script>
    // ============================================================
    // Inject dynamic @page style sesuai pilihan kertas & orientasi
    // ============================================================
    const pageSizes = {
        'A4':    { w: '210mm', h: '297mm' },
        'A3':    { w: '297mm', h: '420mm' },
        'F4':    { w: '215mm', h: '330mm' },
        'legal': { w: '216mm', h: '356mm' },
    };

    let dynamicStyle = document.getElementById('dynamicPrintStyle');
    if (!dynamicStyle) {
        dynamicStyle = document.createElement('style');
        dynamicStyle.id = 'dynamicPrintStyle';
        document.head.appendChild(dynamicStyle);
    }

    function updatePrintStyle() {
        const kertas     = document.getElementById('selectKertas').value;
        const orientasi  = document.getElementById('selectOrientasi').value;
        const size       = pageSizes[kertas] || pageSizes['A4'];

        const pageSize = orientasi === 'landscape'
            ? `${size.h} ${size.w}`   // swap untuk landscape
            : `${size.w} ${size.h}`;

        dynamicStyle.textContent = `
            @media print {
                @page {
                    size: ${pageSize};
                    margin: 6mm;
                }
            }
        `;

        // Hint pada zoom-info
        document.getElementById('zoomInfo').textContent =
            `${kertas} | ${orientasi === 'landscape' ? 'Landscape' : 'Portrait'} | Zoom: ${Math.round(parseFloat(document.getElementById('selectZoom').value)*100)}%`;
    }

    function updateZoom() {
        const zoom = parseFloat(document.getElementById('selectZoom').value);
        document.getElementById('tree-container').style.zoom = zoom;
        updatePrintStyle();
    }

    function toggleControls() {
        const panel = document.getElementById('printControls');
        panel.classList.toggle('open');
    }

    // Tutup panel jika klik di luar
    document.addEventListener('click', function(e) {
        const panel  = document.getElementById('printControls');
        const toggle = document.getElementById('controlsToggle');
        if (panel && toggle && !panel.contains(e.target) && !toggle.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    function tutupHalaman() {
        // Coba tutup tab; jika gagal (dibuka langsung), kembali ke halaman sebelumnya
        window.close();
        setTimeout(() => {
            if (!window.closed) {
                window.history.back();
            }
        }, 300);
    }

    function doCetak() {
        updatePrintStyle();
        setTimeout(() => window.print(), 100);
    }

    // Init saat halaman load
    document.addEventListener('DOMContentLoaded', () => {
        updatePrintStyle();
        updateZoom();
    });
</script>

</html>

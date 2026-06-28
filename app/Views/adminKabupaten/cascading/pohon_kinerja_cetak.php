<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pohon Kinerja Kabupaten</title>
    <!-- Bootstrap untuk tipografi & reset modern -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Gaya pohon bersama -->
    <?= $this->include('adminKabupaten/cascading/_pohon_styles') ?>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #eef1f4;
            color: #2c3340;
            margin: 0;
            padding: 24px 20px 40px;
        }

        /* ===== Header dokumen ===== */
        .print-header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 2px solid #d7dde4;
        }
        .print-header h2 {
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #1f2937;
            margin: 0 0 4px;
        }
        .print-header .ph-sub {
            font-size: 13px;
            color: #5b6675;
            font-weight: 500;
            margin: 0;
        }
        .print-header .ph-meta {
            display: inline-block;
            margin-top: 10px;
            padding: 3px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #356f4a;
            background: #e9f3ed;
            border: 1px solid #cce3d5;
            border-radius: 20px;
        }

        /* ===== Panel pengaturan cetak ===== */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .15);
            width: 210px;
        }
        .print-controls label {
            font-size: 11px;
            font-weight: 600;
            color: #555;
            margin-bottom: 3px;
            display: block;
        }
        .print-controls select {
            font-size: 12px;
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

        /* ===== Penyesuaian cetak ===== */
        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 0 0 14mm;
            }
            .print-controls { display: none !important; }
            .tree-container { overflow: visible !important; width: 100%; }
            .tree-node { page-break-inside: avoid; }
            .tree li { padding: 12px 3px 0 3px; }

            .box-visi   { font-size: 12px; padding: 9px 11px; box-shadow: none; }
            .box-misi   { font-size: 11.5px; padding: 8px 10px; box-shadow: none; }
            .box-tujuan,
            .box-sasaran { font-size: 10.5px; padding: 7px 9px; box-shadow: none; }
            .box-opd    { font-size: 10px; padding: 7px 9px; box-shadow: none; }
            .box-ikt,
            .box-iks,
            .box-csf,
            .box-program { font-size: 9px; padding: 4px 6px; box-shadow: none; }
            .node-label { font-size: 7.5px; margin-bottom: 1px; }
        }

        /* ===== Watermark footer ===== */
        .wm-footer { display: none; }
        @media print {
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

    <div class="print-controls">
        <div class="fw-bold mb-2" style="font-size:13px; color:#333;">
            <i class="fas fa-print me-1 text-success"></i> Pengaturan Cetak
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
            <option value="0.55">55% — Normal</option>
            <option value="0.65" selected>65% — Agak Besar</option>
            <option value="0.70">70% — Besar</option>
            <option value="0.85">85% — Sangat Besar</option>
            <option value="1.00">100% — Penuh</option>
        </select>

        <button onclick="doCetak()" class="btn btn-success btn-sm w-100 mb-2">
            <i class="fas fa-print"></i> Cetak Sekarang
        </button>
        <button onclick="tutupHalaman()" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-times"></i> Tutup
        </button>
        <div class="zoom-info" id="zoomInfo">Zoom: 65%</div>
    </div>

    <div class="print-header">
        <h2>POHON KINERJA</h2>
        <p class="ph-sub">Cascading RPJMD &mdash; Visi &middot; Misi &middot; Tujuan &middot; Sasaran &middot; Perangkat Daerah</p>
        <div class="ph-meta">Periode <?= esc($tahun_mulai) ?> &ndash; <?= esc($tahun_akhir) ?></div>
    </div>

    <?= $this->include('adminKabupaten/cascading/_pohon_tree') ?>

    <!-- WATERMARK FOOTER -->
    <div class="wm-footer">
        <span class="wm-left">
            &copy; Kabupaten Pringsewu &mdash; E-Sakip &bull; Dicetak: <?= date('d/m/Y H:i') ?>
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
        const kertas    = document.getElementById('selectKertas').value;
        const orientasi = document.getElementById('selectOrientasi').value;
        const size      = pageSizes[kertas] || pageSizes['A4'];

        const pageSize = orientasi === 'landscape'
            ? `${size.h} ${size.w}`
            : `${size.w} ${size.h}`;

        dynamicStyle.textContent = `
            @media print {
                @page {
                    size: ${pageSize};
                    margin: 6mm;
                }
            }
        `;

        document.getElementById('zoomInfo').textContent =
            `${kertas} | ${orientasi === 'landscape' ? 'Landscape' : 'Portrait'} | Zoom: ${Math.round(parseFloat(document.getElementById('selectZoom').value)*100)}%`;
    }

    function updateZoom() {
        const zoom = parseFloat(document.getElementById('selectZoom').value);
        document.getElementById('tree-container').style.zoom = zoom;
        updatePrintStyle();
    }

    function tutupHalaman() {
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

    document.addEventListener('DOMContentLoaded', () => {
        updatePrintStyle();
        updateZoom();
    });
</script>

</html>

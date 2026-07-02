<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pohon Kinerja Keseluruhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Gaya pohon (RPJMD + Renstra OPD) -->
    <?= $this->include('adminKabupaten/cascading/_pohon_styles') ?>
    <?= $this->include('adminOpd/cascading/_pohon_opd_styles') ?>

    <!-- html2canvas: ekspor pohon ke gambar (PNG) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- jsPDF: bungkus gambar pohon ke PDF (unduh langsung) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #eef1f4;
            color: #2c3340;
            margin: 0;
            padding: 24px 20px 40px;
        }
        .print-header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 3px double #14532d;
        }
        /* Kop surat (letterhead) 2 logo — selaras dengan Cascading cetak */
        .kop-surat { width: 100%; border-collapse: collapse; }
        .kop-surat td { vertical-align: middle; padding: 0; }
        .kop-logo-l { width: 96px; text-align: left; }
        .kop-logo-r { width: 120px; text-align: right; }
        .kop-logo-l img { height: 66px; width: auto; }
        .kop-logo-r img { height: 52px; width: auto; }
        .kop-teks { text-align: center; padding: 0 10px; }
        .kop-inst { font-weight: 800; font-size: 17px; letter-spacing: .5px; text-transform: uppercase; color: #15311f; line-height: 1.25; }
        .kop-addr { font-size: 11px; color: #5b6675; font-weight: 500; margin-top: 3px; line-height: 1.35; }
        @media (max-width: 768px) {
            .kop-logo-l img { height: 52px; }
            .kop-logo-r img { height: 42px; }
            .kop-inst { font-size: 14px; }
        }
        .print-header h2 { font-weight: 800; letter-spacing: 1.5px; color: #1f2937; margin: 0 0 4px; }
        .print-header .ph-sub { font-size: 13px; color: #5b6675; font-weight: 500; margin: 0; }
        .print-header .ph-meta {
            display: inline-block; margin-top: 10px; padding: 3px 16px; font-size: 12px;
            font-weight: 600; color: #356f4a; background: #e9f3ed; border: 1px solid #cce3d5; border-radius: 20px;
        }
        .print-controls {
            position: fixed; top: 20px; right: 20px; z-index: 1000; background: #fff;
            padding: 16px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0, 0, 0, .15); width: 210px;
        }
        .print-controls label { font-size: 11px; font-weight: 600; color: #555; margin-bottom: 3px; display: block; }
        .print-controls select {
            font-size: 12px; padding: 5px 8px; border-radius: 6px; border: 1px solid #ced4da; width: 100%; margin-bottom: 8px;
        }
        .zoom-info { font-size: 10px; color: #888; text-align: center; margin-top: 4px; }

        @media print {
            /* Default landscape (pohon melebar); ditimpa dinamis bila user ganti orientasi */
            @page { size: A4 landscape; margin: 6mm; }
            body {
                background: #fff;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 0 0 14mm;
            }
            .print-controls { display: none !important; }
            .tree-container { overflow: visible !important; width: 100%; text-align: center !important; }
            .tree { min-width: 0 !important; display: inline-block; }
            [class^="box-"], [class*=" box-"], .ind-kode, .pohon-legend .lg-swatch {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .tree-node { page-break-inside: avoid; }
            .tree li { padding: 12px 3px 0 3px; }
            .box-visi, .box-misi, .box-tujuan, .box-sasaran, .box-opd,
            .box-l3, .box-es2 { box-shadow: none; }
            .box-ikt, .box-iks, .box-csf { box-shadow: none; }
        }

        .wm-footer { display: none; }
        @media print {
            .wm-footer {
                display: flex; position: fixed; bottom: 0; left: 0; width: 100%;
                padding: 5px 10mm; background: #fff; border-top: 1px solid #ccc;
                justify-content: space-between; align-items: center;
                font-family: Arial, sans-serif; font-size: 8pt; color: #888;
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .wm-footer .wm-left  { font-weight: 500; color: #555; }
            .wm-footer .wm-right { color: #888; }
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
            <option value="fit" selected>Fit — Sesuaikan Halaman</option>
            <option value="0.35">35% — Sangat Kecil</option>
            <option value="0.45">45% — Kecil</option>
            <option value="0.55">55% — Normal</option>
            <option value="0.65">65% — Agak Besar</option>
            <option value="0.70">70% — Besar</option>
            <option value="1.00">100% — Penuh</option>
        </select>

        <button onclick="doCetak()" class="btn btn-success btn-sm w-100 mb-2">
            <i class="fas fa-print"></i> Cetak Sekarang
        </button>
        <button onclick="unduhPDF()" id="btnPDF" class="btn btn-danger btn-sm w-100 mb-2">
            <i class="fas fa-file-pdf"></i> Unduh PDF
        </button>
        <button onclick="unduhGambar()" id="btnGambar" class="btn btn-outline-success btn-sm w-100 mb-2">
            <i class="fas fa-image"></i> Unduh Gambar (PNG)
        </button>
        <button onclick="tutupHalaman()" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-times"></i> Tutup
        </button>
        <div class="zoom-info" id="zoomInfo">Zoom: 45%</div>
    </div>

    <?php
    helper('setting');
    $kopLogo   = setting_asset('kab_logo', 'assets/images/logo.png');
    $kopAksara = setting_asset('app_logo', 'assets/images/LogoTentang.png');
    $kopInst   = setting('instansi', 'Pemerintah Kabupaten Pringsewu');
    $kopAlamat = trim(setting('instansi_address', ''));
    $kopTelp   = trim(setting('instansi_phone', ''));
    $kopEmail  = trim(setting('instansi_email', ''));
    $kopKontak = [];
    if ($kopAlamat !== '') { $kopKontak[] = $kopAlamat; }
    $kopTE = trim($kopTelp . (($kopTelp && $kopEmail) ? ' · ' : '') . $kopEmail);
    if ($kopTE !== '') { $kopKontak[] = $kopTE; }
    ?>
    <div id="capture-area">
    <div class="print-header">
        <table class="kop-surat">
            <tr>
                <td class="kop-logo-l">
                    <?php if ($kopLogo): ?><img src="<?= esc($kopLogo) ?>" alt="Lambang Kabupaten"><?php endif; ?>
                </td>
                <td class="kop-teks"><!-- logo saja: teks instansi dihilangkan --></td>
                <td class="kop-logo-r">
                    <?php if ($kopAksara): ?><img src="<?= esc($kopAksara) ?>" alt="AKSARA"><?php endif; ?>
                </td>
            </tr>
        </table>
        <h2 style="margin-top:6px;">POHON KINERJA KESELURUHAN</h2>
        <p class="ph-sub">Renstra Perangkat Daerah &mdash; Tujuan &middot; Sasaran &middot; Indikator</p>
        <div class="ph-meta">Periode <?= esc($tahun_mulai) ?> &ndash; <?= esc($tahun_akhir) ?></div>
    </div>

    <?= $this->include('adminKabupaten/cascading/_pohon_tree_keseluruhan') ?>
    </div>

    <div class="wm-footer">
        <span class="wm-left">Print Document by AKSARA</span>
        <span class="wm-right">Dicetak <?= date('d/m/Y H:i') ?></span>
    </div>

</body>

<script>
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

    const PAGE_MARGIN_MM = 6;

    function selVal(id) { const el = document.getElementById(id); return el ? el.value : ''; }

    // Lebar area cetak (px @96dpi): kertas + orientasi - margin.
    function printableWidthPx() {
        const size = pageSizes[selVal('selectKertas')] || pageSizes['A4'];
        const wMm  = parseFloat(selVal('selectOrientasi') === 'landscape' ? size.h : size.w);
        const mm   = Math.max(20, wMm - 2 * PAGE_MARGIN_MM);
        return mm / 25.4 * 96;
    }
    // Tinggi area cetak (px @96dpi).
    function printableHeightPx() {
        const size = pageSizes[selVal('selectKertas')] || pageSizes['A4'];
        const hMm  = parseFloat(selVal('selectOrientasi') === 'landscape' ? size.w : size.h);
        const mm   = Math.max(20, hMm - 2 * PAGE_MARGIN_MM);
        return mm / 25.4 * 96;
    }

    // Ukur dimensi natural pohon (px) pada skala 1 & tanpa min-width (lebar KONTEN sebenarnya).
    function treeNaturalSize() {
        const t = document.getElementById('tree-container');
        if (!t) return { w: 0, h: 0 };
        const pz = t.style.zoom, pm = t.style.minWidth;
        t.style.zoom = '1';
        t.style.minWidth = '0';
        const w = t.scrollWidth, h = t.scrollHeight;
        t.style.zoom = pz;
        t.style.minWidth = pm;
        return { w, h };
    }

    // Skala: manual, atau "fit" = muat SATU halaman (lebar & tinggi), maks 100%, margin aman 3%.
    function effectiveZoom() {
        const sel = selVal('selectZoom');
        if (sel === 'fit') {
            const nat = treeNaturalSize();
            if (!nat.w) return 1;
            const sw = (printableWidthPx()  * 0.97) / nat.w;
            const sh = nat.h ? (printableHeightPx() * 0.97) / nat.h : sw;
            let s = Math.min(sw, sh);
            if (!isFinite(s) || s <= 0) s = 1;
            return Math.min(1, Math.max(0.10, s));
        }
        return parseFloat(sel) || 1;
    }

    // Ukuran standar -> sintaks "A4 landscape" (lebih andal memaksa orientasi di dialog cetak).
    const NAMED_SIZE = { 'A4': 'A4', 'A3': 'A3' };
    function applyPageStyle() {
        const kertas = selVal('selectKertas');
        const ori = selVal('selectOrientasi') === 'portrait' ? 'portrait' : 'landscape';
        let decl;
        if (NAMED_SIZE[kertas]) {
            decl = `${NAMED_SIZE[kertas]} ${ori}`;
        } else {
            const size = pageSizes[kertas] || pageSizes['A4'];
            decl = ori === 'landscape' ? `${size.h} ${size.w}` : `${size.w} ${size.h}`;
        }
        dynamicStyle.textContent =
            `@media print { @page { size: ${decl}; margin: ${PAGE_MARGIN_MM}mm; } }`;
    }

    function updateZoom() {
        applyPageStyle();
        const z = effectiveZoom();
        const t = document.getElementById('tree-container');
        if (t) t.style.zoom = z;
        const info = document.getElementById('zoomInfo');
        if (info) {
            const isFit = selVal('selectZoom') === 'fit';
            info.textContent = `${selVal('selectKertas')} | ${selVal('selectOrientasi') === 'landscape' ? 'Landscape' : 'Portrait'} | Zoom: ${(isFit ? 'Fit ' : '')}${Math.round(z * 100)}%`;
        }
    }

    // Alias lama (onchange kertas/orientasi memanggil ini) -> hitung ulang fit.
    function updatePrintStyle() { updateZoom(); }

    function tutupHalaman() {
        window.close();
        setTimeout(() => { if (!window.closed) { window.history.back(); } }, 300);
    }

    function doCetak() {
        updateZoom();
        setTimeout(() => window.print(), 150);
    }

    // ============================================================
    // Render pohon ke <canvas> full-size (html2canvas) — dipakai bersama
    // oleh unduh PNG & unduh PDF. Sembunyikan kontrol/footer sementara.
    // ============================================================
    async function captureTreeCanvas() {
        const area     = document.getElementById('capture-area');
        const treeEl   = document.getElementById('tree-container');
        const wrap     = document.querySelector('.tree-container');
        const controls = document.querySelector('.print-controls');
        const footer   = document.querySelector('.wm-footer');
        if (!area) return null;

        const st = {
            zoom: treeEl ? treeEl.style.zoom : '',
            ov:   wrap ? wrap.style.overflow : '',
            ctrl: controls ? controls.style.display : '',
            ft:   footer ? footer.style.display : ''
        };
        if (treeEl) treeEl.style.zoom = '1';
        if (wrap)   wrap.style.overflow = 'visible';
        if (controls) controls.style.display = 'none';
        if (footer)   footer.style.display = 'none';

        try {
            const w = area.scrollWidth, h = area.scrollHeight;
            let scale = Math.min(2, 12000 / Math.max(w, h));
            if (!isFinite(scale) || scale < 1) scale = 1;
            return await html2canvas(area, {
                backgroundColor: '#ffffff',
                scale: scale,
                useCORS: true,
                logging: false,
                width: w, height: h, windowWidth: w, windowHeight: h
            });
        } finally {
            if (treeEl) treeEl.style.zoom = st.zoom;
            if (wrap)   wrap.style.overflow = st.ov;
            if (controls) controls.style.display = st.ctrl;
            if (footer)   footer.style.display = st.ft;
            updateZoom();
        }
    }

    async function withButtonBusy(id, task) {
        const btn = document.getElementById(id);
        const orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses…'; }
        try { await task(); }
        finally { if (btn) { btn.disabled = false; btn.innerHTML = orig; } }
    }

    // Ekspor pohon ke gambar PNG.
    async function unduhGambar() {
        if (typeof html2canvas === 'undefined') { alert('Pustaka gambar belum termuat, coba lagi sebentar.'); return; }
        await withButtonBusy('btnGambar', async () => {
            try {
                const canvas = await captureTreeCanvas();
                if (!canvas) return;
                const link = document.createElement('a');
                link.download = 'pohon-kinerja-keseluruhan-<?= esc($tahun_mulai) ?>-<?= esc($tahun_akhir) ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                alert('Gagal membuat gambar: ' + (e && e.message ? e.message : e));
            }
        });
    }

    // Unduh langsung sebagai PDF (gambar pohon dibungkus jsPDF, 1 halaman se-ukuran pohon).
    async function unduhPDF() {
        if (typeof html2canvas === 'undefined') { alert('Pustaka gambar belum termuat, coba lagi sebentar.'); return; }
        const jsPDF = (window.jspdf || {}).jsPDF;
        if (!jsPDF) { alert('Pustaka PDF belum termuat, coba lagi sebentar.'); return; }
        await withButtonBusy('btnPDF', async () => {
            try {
                const canvas = await captureTreeCanvas();
                if (!canvas) return;
                const imgData = canvas.toDataURL('image/png');
                const w = canvas.width, h = canvas.height;
                const pdf = new jsPDF({
                    orientation: w >= h ? 'landscape' : 'portrait',
                    unit: 'px',
                    format: [w, h],
                    compress: true
                });
                pdf.addImage(imgData, 'PNG', 0, 0, w, h, undefined, 'FAST');
                pdf.save('pohon-kinerja-keseluruhan-<?= esc($tahun_mulai) ?>-<?= esc($tahun_akhir) ?>.pdf');
            } catch (e) {
                alert('Gagal membuat PDF: ' + (e && e.message ? e.message : e));
            }
        });
    }

    window.addEventListener('beforeprint', updateZoom);
    window.addEventListener('resize', function () { if (selVal('selectZoom') === 'fit') updateZoom(); });
    document.addEventListener('DOMContentLoaded', updateZoom);
</script>

</html>

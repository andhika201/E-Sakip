<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pohon Kinerja OPD</title>
    <!-- Bootstrap untuk tipografi & reset modern -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Gaya pohon OPD bersama -->
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

        /* ===== Header dokumen ===== */
        .print-header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 14px;
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
        .print-header h2 {
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #1f2937;
            margin: 0 0 4px;
            font-size: clamp(18px, 3vw, 26px);
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
            top: 16px;
            right: 16px;
            z-index: 1000;
            background: white;
            padding: 14px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .15);
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

        /* Toggle untuk mobile */
        .controls-toggle {
            display: none;
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 1100;
            background: #00743e;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .25);
            align-items: center;
            justify-content: center;
        }

        /* ===== Responsive ===== */
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
            .tree-node { width: clamp(120px, 28vw, 150px); }
        }
        @media (max-width: 480px) {
            .tree-node { width: clamp(100px, 32vw, 130px); }
        }

        /* ===== Penyesuaian cetak ===== */
        @media print {
            /* Default landscape (pohon melebar); ditimpa dinamis bila user ganti orientasi */
            @page { size: A4 landscape; margin: 6mm; }
            body {
                background: #fff;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 0 0 14mm;
            }
            .print-controls, .controls-toggle { display: none !important; }
            .tree-container { overflow: visible !important; width: 100%; text-align: center !important; }
            .tree { min-width: 0 !important; display: inline-block; }
            .tree-node { page-break-inside: avoid; }
            .tree li { padding: 12px 3px 0 3px; }
            .box-l1, .box-l2, .box-l3, .box-es2, .box-es3, .box-es4 { box-shadow: none; }
            .box-iks, .box-csf { box-shadow: none; }
            /* Paksa warna node tercetak walau "Background graphics" nonaktif */
            [class^="box-"], [class*=" box-"], .ind-kode, .pohon-legend .lg-swatch {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
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
            .wm-footer .wm-left  { font-weight: 500; color: #555; }
            .wm-footer .wm-right { color: #888; }
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
            <i class="fas fa-print me-1 text-success"></i> Pengaturan Cetak
        </div>

        <label for="selectKertas">Ukuran Kertas</label>
        <select id="selectKertas" onchange="updatePrintStyle()">
            <option value="A4">A4 (210 × 297 mm)</option>
            <option value="A3">A3 (297 × 420 mm)</option>
            <option value="A2">A2 (420 × 594 mm)</option>
            <option value="A1">A1 (594 × 841 mm)</option>
            <option value="A0">A0 (841 × 1189 mm) — poster</option>
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
            <option value="0.70">70% — Besar</option>
            <option value="0.85">85% — Sangat Besar</option>
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
        <div class="zoom-info" id="zoomInfo">Zoom: 55%</div>
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
        <h2 style="margin-top:6px;">POHON KINERJA</h2>
        <p class="ph-sub"><?= esc($nama_opd ?? 'Perangkat Daerah') ?></p>
        <div class="ph-meta">Periode <?= esc($tahun_mulai) ?> &ndash; <?= esc($tahun_akhir) ?></div>
    </div>

    <?= $this->include('adminOpd/cascading/_pohon_opd_tree', ['showCsf' => $showCsf ?? true]) ?>
    </div>

    <!-- WATERMARK FOOTER -->
    <div class="wm-footer">
        <span class="wm-left">Print Document by AKSARA</span>
        <span class="wm-right">Dicetak <?= date('d/m/Y H:i') ?></span>
    </div>

</body>

<script>
    // ============================================================
    // Inject dynamic @page style sesuai pilihan kertas & orientasi
    // ============================================================
    const pageSizes = {
        'A4':    { w: '210mm', h: '297mm' },
        'A3':    { w: '297mm', h: '420mm' },
        'A2':    { w: '420mm', h: '594mm' },
        'A1':    { w: '594mm', h: '841mm' },
        'A0':    { w: '841mm', h: '1189mm' },
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

    function printableWidthPx() {
        const size = pageSizes[selVal('selectKertas')] || pageSizes['A4'];
        const wMm  = parseFloat(selVal('selectOrientasi') === 'landscape' ? size.h : size.w);
        const mm   = Math.max(20, wMm - 2 * PAGE_MARGIN_MM);
        return mm / 25.4 * 96;
    }
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

    // "fit" = muat SATU halaman (lebar & tinggi), maks 100%, margin aman 3%.
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

    function toggleControls() {
        const panel = document.getElementById('printControls');
        panel.classList.toggle('open');
    }

    document.addEventListener('click', function (e) {
        const panel  = document.getElementById('printControls');
        const toggle = document.getElementById('controlsToggle');
        if (panel && toggle && !panel.contains(e.target) && !toggle.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    function tutupHalaman() {
        window.close();
        setTimeout(() => {
            if (!window.closed) {
                window.history.back();
            }
        }, 300);
    }

    function doCetak() {
        updateZoom();
        setTimeout(() => window.print(), 150);
    }

    // ============================================================
    // Render pohon ke <canvas> full-size (html2canvas) — dipakai bersama
    // oleh unduh PNG & unduh PDF. Sembunyikan kontrol/footer sementara.
    // ============================================================
    // Pangkas kanvas ke kotak-isi: buang ruang putih di tepi agar gambar/PDF padat.
    // (analisis di kanvas kecil dulu agar cepat, lalu potong pada resolusi penuh)
    function cropCanvasToContent(src, pad) {
        try {
            const MAXA = 1400;
            const ratio = Math.min(1, MAXA / Math.max(src.width, src.height));
            const aw = Math.max(1, Math.round(src.width * ratio));
            const ah = Math.max(1, Math.round(src.height * ratio));
            const a = document.createElement('canvas');
            a.width = aw; a.height = ah;
            const actx = a.getContext('2d');
            actx.fillStyle = '#ffffff'; actx.fillRect(0, 0, aw, ah);
            actx.drawImage(src, 0, 0, aw, ah);
            const d = actx.getImageData(0, 0, aw, ah).data;
            let minX = aw, minY = ah, maxX = -1, maxY = -1;
            for (let y = 0; y < ah; y++) {
                for (let x = 0; x < aw; x++) {
                    const i = (y * aw + x) * 4;
                    if (d[i] < 245 || d[i + 1] < 245 || d[i + 2] < 245) {
                        if (x < minX) minX = x;
                        if (x > maxX) maxX = x;
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                    }
                }
            }
            if (maxX < 0) return src;                       // kanvas kosong
            const p = pad || 10;
            const sx = Math.max(0, Math.floor(minX / ratio) - p);
            const sy = Math.max(0, Math.floor(minY / ratio) - p);
            const sw = Math.min(src.width  - sx, Math.ceil((maxX - minX + 1) / ratio) + 2 * p);
            const sh = Math.min(src.height - sy, Math.ceil((maxY - minY + 1) / ratio) + 2 * p);
            if (sw <= 0 || sh <= 0) return src;
            if (sx === 0 && sy === 0 && sw >= src.width - 2 && sh >= src.height - 2) return src;
            const out = document.createElement('canvas');
            out.width = Math.round(sw); out.height = Math.round(sh);
            const octx = out.getContext('2d');
            octx.fillStyle = '#ffffff'; octx.fillRect(0, 0, out.width, out.height);
            octx.drawImage(src, sx, sy, sw, sh, 0, 0, out.width, out.height);
            return out;
        } catch (e) {
            return src;   // getImageData gagal (mis. kanvas ter-taint) -> pakai asli
        }
    }

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

            // Skala ideal untuk ketajaman (perbesar pohon kecil, jangan kekecilan).
            let scale = Math.min(2, 12000 / Math.max(w, h));
            if (!isFinite(scale) || scale <= 0) scale = 1;

            // WAJIB: jaga kanvas hasil di bawah batas browser. Bila terlampaui,
            // kanvas menjadi HITAM/kosong — inilah penyebab cetak pohon besar rusak.
            // (Chrome ~268 jt px luas & 65 rb px sisi; Safari sisi 16.384 px.)
            const CANVAS_MAX_DIM  = 16384;
            const CANVAS_MAX_AREA = 200000000;
            scale = Math.min(
                scale,
                CANVAS_MAX_DIM / Math.max(w, h),
                Math.sqrt(CANVAS_MAX_AREA / (w * h))
            );
            if (!isFinite(scale) || scale <= 0) scale = 0.1;

            const canvas = await html2canvas(area, {
                backgroundColor: '#ffffff',
                scale: scale,
                useCORS: true,
                logging: false,
                width: w, height: h, windowWidth: w, windowHeight: h
            });
            return cropCanvasToContent(canvas, 12);   // buang ruang putih di tepi
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
                link.download = 'pohon-kinerja-<?= esc($tahun_mulai) ?>-<?= esc($tahun_akhir) ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (e) {
                alert('Gagal membuat gambar: ' + (e && e.message ? e.message : e));
            }
        });
    }

    // Unduh sebagai PDF: seluruh pohon dimuat rapi ke SATU halaman (dipangkas
    // dari ruang kosong, lalu diperbesar sepenuh halaman). Untuk pohon besar &
    // lebar, pilih kertas lebih besar (A3/A2/A1/A0) agar hasilnya tetap terbaca.
    async function unduhPDF() {
        if (typeof html2canvas === 'undefined') { alert('Pustaka gambar belum termuat, coba lagi sebentar.'); return; }
        const jsPDF = (window.jspdf || {}).jsPDF;
        if (!jsPDF) { alert('Pustaka PDF belum termuat, coba lagi sebentar.'); return; }
        await withButtonBusy('btnPDF', async () => {
            try {
                const canvas = await captureTreeCanvas();
                if (!canvas) return;

                const DIMS_MM = { A0:[841,1189], A1:[594,841], A2:[420,594], A3:[297,420], A4:[210,297], F4:[215,330], legal:[216,356] };
                const base = DIMS_MM[selVal('selectKertas')] || DIMS_MM.A4;
                const land = selVal('selectOrientasi') !== 'portrait';
                const pw = land ? Math.max(base[0], base[1]) : Math.min(base[0], base[1]);
                const ph = land ? Math.min(base[0], base[1]) : Math.max(base[0], base[1]);
                const margin = 6, availW = pw - 2 * margin, availH = ph - 2 * margin;

                const cw = canvas.width, ch = canvas.height;
                const s  = Math.min(availW / cw, availH / ch);   // mm per px: muat 1 halaman
                const iw = cw * s, ih = ch * s;

                const pdf = new jsPDF({ orientation: land ? 'landscape' : 'portrait', unit: 'mm', format: [pw, ph], compress: true });
                pdf.addImage(canvas.toDataURL('image/png'), 'PNG',
                    margin + (availW - iw) / 2, margin + (availH - ih) / 2, iw, ih, undefined, 'FAST');
                pdf.save('pohon-kinerja-<?= esc($tahun_mulai) ?>-<?= esc($tahun_akhir) ?>.pdf');
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

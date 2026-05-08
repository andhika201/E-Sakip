<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cascading Kinerja Kabupaten</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            background: #fff;
            padding: 16px 20px 80px 20px;
        }

        /* ===== PRINT CONTROLS ===== */
        .print-controls {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            background: #fff;
            padding: 14px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            width: 200px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .print-controls .ctrl-title {
            font-size: 12px;
            font-weight: 700;
            color: #2e7d32;
            margin-bottom: 8px;
        }
        .print-controls label {
            font-size: 10px;
            font-weight: 600;
            color: #555;
            display: block;
            margin-bottom: 2px;
        }
        .print-controls select {
            font-size: 11px;
            padding: 4px 6px;
            border-radius: 5px;
            border: 1px solid #bbb;
            width: 100%;
            margin-bottom: 7px;
        }
        .print-controls button {
            width: 100%;
            margin-bottom: 5px;
            font-size: 11px;
            padding: 6px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-cetak  { background: #2e7d32; color: #fff; }
        .btn-tutup  { background: #f5f5f5; color: #333; border: 1px solid #ccc !important; }
        .zoom-info  { font-size: 9px; color: #999; text-align: center; margin-top: 3px; }

        /* ===== HEADER ===== */
        .doc-header {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 3px solid #2e7d32;
        }
        .doc-header .kop-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1b5e20;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-header .kop-sub {
            font-size: 10pt;
            color: #2e7d32;
            margin-top: 3px;
        }
        .doc-header .kop-meta {
            font-size: 9pt;
            color: #555;
            margin-top: 4px;
        }

        /* ===== TABLE ===== */
        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #888;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 8pt;
        }
        thead tr:first-child th {
            background-color: #1b5e20;
            color: #fff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        thead tr:last-child th {
            background-color: #388e3c;
            color: #fff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) td { background-color: #f1f8e9; }
        td.center, th.center { text-align: center; vertical-align: middle; }
        .nowrap { white-space: nowrap; }
        .dash { text-align: center; color: #aaa; }

        /* ===== WATERMARK FOOTER ===== */
        .wm-footer { display: none; }

        /* ===== PRINT ===== */
        @media print {
            body { padding: 6mm 8mm 14mm 8mm; background: #fff; }
            .print-controls { display: none !important; }
            .table-wrap { overflow: visible; }
            tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            .wm-footer {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                padding: 5px 10mm;
                border-top: 1px solid #ccc;
                background: #fff;
                justify-content: space-between;
                align-items: center;
                font-size: 8pt;
                color: #888;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .wm-footer .wm-left  { font-style: italic; }
            .wm-footer .wm-right { font-weight: 600; color: #aaa; }
        }
        }
    </style>
</head>
<body>

    <!-- PRINT CONTROLS -->
    <div class="print-controls">
        <div class="ctrl-title">🖨 Pengaturan Cetak</div>

        <label>Ukuran Kertas</label>
        <select id="selKertas" onchange="applyPrint()">
            <option value="A4">A4 (210×297 mm)</option>
            <option value="A3">A3 (297×420 mm)</option>
            <option value="F4">F4 / Folio (215×330 mm)</option>
            <option value="legal">Legal (216×356 mm)</option>
        </select>

        <label>Orientasi</label>
        <select id="selOrientasi" onchange="applyPrint()">
            <option value="landscape" selected>Landscape</option>
            <option value="portrait">Portrait</option>
        </select>

        <button class="btn-cetak" onclick="doCetak()">🖨 Cetak Sekarang</button>
        <button class="btn-tutup" onclick="tutupHalaman()">✕ Tutup</button>
        <div class="zoom-info" id="zoomInfo">A4 | Landscape</div>
    </div>

    <!-- HEADER -->
    <div class="doc-header">
        <div class="kop-title">Cascading Kinerja Kabupaten</div>
        <div class="kop-sub">Matriks Cascading RPJMD &rarr; Program OPD</div>
        <?php if (!empty($years)): ?>
        <div class="kop-meta">Periode: <?= esc(min($years)) ?> &ndash; <?= esc(max($years)) ?></div>
        <?php endif; ?>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="min-width:150px;">Tujuan RPJMD</th>
                <th rowspan="2" style="min-width:150px;">Sasaran RPJMD</th>
                <th rowspan="2" style="min-width:150px;">Indikator Sasaran</th>
                <th rowspan="2" class="nowrap">Satuan</th>
                <th rowspan="2" class="nowrap">Baseline</th>
                <th colspan="<?= count($years) ?>" style="text-align:center">Target Per Tahun</th>
                <th rowspan="2" style="min-width:180px;">Program</th>
                <th rowspan="2" style="min-width:150px;">OPD</th>
            </tr>
            <tr>
                <?php foreach ($years as $y): ?>
                <th class="nowrap"><?= esc($y) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= 7 + count($years) ?>" class="center">Tidak ada data cascading.</td>
            </tr>
            <?php else: ?>

            <?php foreach ($rows as $index => $r): ?>
            <tr>
                <?php if (($firstShow['tujuan'][$r['tujuan_id']] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['tujuan_rpjmd'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <?php if (($firstShow['sasaran'][$r['sasaran_id']] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['sasaran_rpjmd'] ?? '-')) ?>
                </td>
                <?php endif; ?>

                <?php if (($firstShow['indikator'][$r['indikator_id']] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                    <?= nl2br(esc($r['indikator_sasaran'] ?? '-')) ?>
                </td>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="center nowrap">
                    <?= esc($r['satuan'] ?? '-') ?>
                </td>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="center nowrap">
                    <?= esc($r['baseline'] ?? '-') ?>
                </td>
                <?php foreach ($years as $y): ?>
                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="center nowrap">
                    <?= esc($r['targets'][$y] ?? '-') ?>
                </td>
                <?php endforeach; ?>
                <?php endif; ?>

                <td><?= nl2br(esc($r['program_kegiatan'] ?? '-')) ?></td>

                <?php $opdKey = $r['indikator_id'] . '-' . $r['nama_opd']; ?>
                <?php if (($firstShow['opd'][$opdKey] ?? -1) == $index): ?>
                <td rowspan="<?= $rowspan['opd'][$opdKey] ?? 1 ?>">
                    <?= esc($r['nama_opd'] ?? '-') ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- WATERMARK FOOTER -->
    <div class="wm-footer">
        <span class="wm-left">
            &copy; Kabupaten Pringsewu &mdash; E-Sakip &bull; Dicetak: <?= date('d/m/Y H:i') ?>
        </span>
        <span class="wm-right">Print by Aksara</span>
    </div>

</body>

<script>
    const pageSizes = {
        'A4':    { w:'210mm', h:'297mm' },
        'A3':    { w:'297mm', h:'420mm' },
        'F4':    { w:'215mm', h:'330mm' },
        'legal': { w:'216mm', h:'356mm' },
    };
    let dynStyle = document.getElementById('dynPrint');
    if (!dynStyle) {
        dynStyle = document.createElement('style');
        dynStyle.id = 'dynPrint';
        document.head.appendChild(dynStyle);
    }

    function applyPrint() {
        const k = document.getElementById('selKertas').value;
        const o = document.getElementById('selOrientasi').value;
        const s = pageSizes[k] || pageSizes['A4'];
        const size = o === 'landscape' ? `${s.h} ${s.w}` : `${s.w} ${s.h}`;
        dynStyle.textContent = `@media print { @page { size: ${size}; margin: 8mm; } }`;
        document.getElementById('zoomInfo').textContent = `${k} | ${o === 'landscape' ? 'Landscape' : 'Portrait'}`;
    }

    function doCetak() { applyPrint(); setTimeout(() => window.print(), 100); }

    function tutupHalaman() {
        window.close();
        setTimeout(() => { if (!window.closed) window.history.back(); }, 300);
    }

    document.addEventListener('DOMContentLoaded', applyPrint);
</script>

</html>

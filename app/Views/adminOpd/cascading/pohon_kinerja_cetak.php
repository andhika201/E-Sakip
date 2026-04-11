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

        /* Box Elements */
        .tree-node {
            display: inline-flex;
            flex-direction: column;
            align-items: stretch;
            gap: 5px;
            width: 180px; /* Slightly narrower for OPD to fit 5 levels */
            transition: all 0.3s;
        }

        /* L1: Tujuan RPJMD - Teal */
        .box-l1 {
            background: linear-gradient(135deg, #00b8a9 0%, #008f83 100%);
            color: #fff;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 4px 6px rgba(0, 184, 169, 0.3);
            border: 2px solid #fff;
        }

        /* L2: Sasaran RPJMD - Darker Teal/Green */
        .box-l2 {
            background: linear-gradient(135deg, #00897b 0%, #00695c 100%);
            color: #fff;
            border-radius: 10px;
            padding: 10px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(0, 137, 123, 0.3);
        }

        /* L3: Tujuan Renstra - Blue */
        .box-l3 {
            background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
            color: #fff;
            border-radius: 8px;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(30, 136, 229, 0.3);
        }

        /* L4/L5: Sasaran ESS (Brown/Orange) */
        .box-sasaran {
            background: linear-gradient(135deg, #6d4c41 0%, #4e342e 100%);
            color: #fff;
            border-radius: 8px;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(109, 76, 65, 0.3);
        }

        /* Box Indikator - Orange */
        .box-iks {
            background: #f57c00;
            color: #fff;
            border-radius: 6px;
            padding: 6px;
            font-size: 11px;
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
            padding: 6px;
            font-size: 10px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(255, 183, 77, 0.2);
            margin-bottom: 2px;
            text-align: left;
        }

        /* Print Options */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
            body {
                background: #fff;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                padding: 0;
            }
            .print-controls {
                display: none !important;
            }
            .tree-container {
                overflow: visible !important;
                width: 100%;
            }
            .tree {
                zoom: 0.55; /* Sangat kecil agar 5 level termuat di A4 Landscape */
            }
            .tree-node {
                width: 140px; 
                page-break-inside: avoid;
            }
            .tree li {
                padding: 15px 3px 0 3px;
            }
            .box-l1, .box-l2, .box-l3, .box-sasaran { box-shadow: none; }
            .box-iks, .box-csf { box-shadow: none;}
        }
    </style>
</head>
<body>

    <div class="print-controls d-flex flex-column gap-2">
        <button onclick="window.print()" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
            <i class="fas fa-print"></i> Cetak Sekarang
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2" id="btnClose">
            <i class="fas fa-times"></i> Tutup
        </button>
        <small class="text-muted mt-2 text-center" style="max-width: 150px; font-size: 11px;">
            Pastikan pengaturan cetak diset ke <b>Landscape</b> dan <b>Background graphics</b> diaktifkan.
        </small>
    </div>

    <div class="print-header">
        <h2 class="fw-bold mb-1">POHON KINERJA OPD</h2>
        <p class="text-muted mb-0" style="font-size: 16px;">Periode <?= esc($tahun_mulai) ?> - <?= esc($tahun_akhir) ?></p>
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
                                                                            <!-- CSF ES2 -->
                                                                            <?php if (!empty($es2['csf'])): ?>
                                                                                <div class="box-csf">
                                                                                    <div class="opacity-75 mb-1" style="font-size:9px;">Critical Success Factor ES.II</div>
                                                                                    <?= nl2br(esc($es2['csf'])) ?>
                                                                                </div>
                                                                            <?php endif; ?>

                                                                            <div class="box-sasaran">
                                                                                <div class="opacity-75 mb-1" style="font-size: 9px; font-weight: normal;">Sasaran ESS II</div>
                                                                                <?= nl2br(esc($es2['nama'])) ?>
                                                                            </div>

                                                                            <!-- Indikator ES2 -->
                                                                            <?php foreach ($es2['indikators'] as $indikatorEs2): ?>
                                                                                <div class="box-iks">
                                                                                    <div class="opacity-75" style="font-size:8px;">Indikator ESS II</div>
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
                                                                                            <!-- CSF ES3 -->
                                                                                            <?php if (!empty($es3['csf'])): ?>
                                                                                                <div class="box-csf">
                                                                                                    <div class="opacity-75 mb-1" style="font-size:9px;">Critical Success Factor ES.III</div>
                                                                                                    <?= nl2br(esc($es3['csf'])) ?>
                                                                                                </div>
                                                                                            <?php endif; ?>

                                                                                            <div class="box-sasaran" style="background: linear-gradient(135deg, #7b1fa2 0%, #4a148c 100%);">
                                                                                                <div class="opacity-75 mb-1" style="font-size: 9px; font-weight: normal;">Sasaran ESS III</div>
                                                                                                <?= nl2br(esc($es3['nama'])) ?>
                                                                                            </div>

                                                                                            <!-- Indikator ES3 -->
                                                                                            <?php foreach ($es3['indikators'] as $indikatorEs3): ?>
                                                                                                <div class="box-iks" style="background: #8e24aa;">
                                                                                                    <div class="opacity-75" style="font-size:8px;">Indikator ESS III</div>
                                                                                                    <?= nl2br(esc($indikatorEs3)) ?>
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
        </div>
    </div>

</body>
</html>

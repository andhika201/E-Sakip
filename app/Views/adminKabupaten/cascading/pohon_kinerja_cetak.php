<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pohon Kinerja Kabupaten</title>
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
            width: 200px;
            transition: all 0.3s;
        }

        /* Box Visi - Deep Blue/Indigo */
        .box-visi {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: #fff;
            border-radius: 14px;
            padding: 14px 18px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 6px 12px rgba(26, 35, 126, 0.4);
            border: 2px solid rgba(255,255,255,0.3);
            letter-spacing: 0.3px;
        }

        /* Box Misi - Teal */
        .box-misi {
            background: linear-gradient(135deg, #00b8a9 0%, #008f83 100%);
            color: #fff;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0, 184, 169, 0.3);
            border: 2px solid #fff;
        }

        /* Box Tujuan - Dark Green */
        .box-tujuan {
            background: linear-gradient(135deg, #00897b 0%, #00695c 100%);
            color: #fff;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(0, 137, 123, 0.3);
        }

        /* Box Indikator Tujuan - Light Green */
        .box-ikt {
            background: #43a047;
            color: #fff;
            border-radius: 6px;
            padding: 6px;
            font-size: 11px;
            margin-top: -3px; 
            box-shadow: 0 2px 4px rgba(67, 160, 71, 0.3);
        }

        /* Box Sasaran - Brown */
        .box-sasaran {
            background: linear-gradient(135deg, #6d4c41 0%, #4e342e 100%);
            color: #fff;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            
            font-weight: 600;
            box-shadow: 0 3px 5px rgba(109, 76, 65, 0.3);
        }

        /* Box Indikator Sasaran - Orange */
        .box-iks {
            background: #f57c00;
            color: #fff;
            border-radius: 6px;
            padding: 6px;
            font-size: 11px;
            margin-top: -3px;
            box-shadow: 0 2px 4px rgba(245, 124, 0, 0.3);
        }

        /* Box CSF - Yellow */
        .box-csf {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffb74d;
            border-radius: 6px;
            padding: 6px;
            font-size: 11px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(255, 183, 77, 0.2);
            margin-bottom: 2px;
            text-align: left; /* Biar CSF lebih mudah dibaca jika panjang / banyak enter */
        }

        /* Root style to remove top connection if multiple Misi */
        /* Baris antar Misi kita fungsikan agar terhubung, sehingga blok di atas ditiadakan */

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
                zoom: 0.65; /* Memperkecil rasio agar muat banyak cabang di satu lembar */
            }
            .tree-node {
                width: 140px; /* Compress slightly for print */
                page-break-inside: avoid;
            }
            .tree li {
                padding: 15px 3px 0 3px;
            }
            .box-visi  { font-size: 12px; padding: 10px; border-width: 1px; box-shadow: none; }
            .box-misi  { font-size: 12px; padding: 10px; border-width: 1px; box-shadow: none; }
            .box-tujuan { font-size: 11px; padding: 8px; box-shadow: none;}
            .box-sasaran { font-size: 11px; padding: 8px; box-shadow: none;}
            .box-ikt, .box-iks, .box-csf { font-size: 9px; padding: 5px; box-shadow: none;}
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
        <h2 class="fw-bold mb-1">POHON KINERJA</h2>
        <p class="text-muted mb-0" style="font-size: 16px;">Periode <?= esc($tahun_mulai) ?> - <?= esc($tahun_akhir) ?></p>
    </div>

    <div class="tree-container text-center">
        <div class="tree" id="tree-container">
            <ul>
                <!-- VISI NODE (root tunggal) -->
                <li>
                    <div class="tree-node" style="width:280px;">
                        <div class="box-visi">
                            <div class="mb-1 opacity-75 small">VISI</div>
                            <?= !empty($visi) ? esc($visi) : '<em style="opacity:.6">Visi belum diisi</em>' ?>
                        </div>
                    </div>

                    <!-- Misi sebagai anak dari Visi -->
                    <?php if (!empty($tree)): ?>
                    <ul>
                        <?php $misiNo = 0; foreach ($tree as $misi): $misiNo++; ?>
                        <li>
                            <!-- MISI NODE -->
                            <div class="tree-node">
                                <div class="box-misi">
                                    <div class="mb-1 opacity-75 small">Misi <?= $misiNo ?></div>
                                    <?= esc($misi['misi']) ?>
                                </div>
                            </div>

                            <?php if (!empty($misi['tujuan'])): ?>
                                <ul>
                                    <?php foreach ($misi['tujuan'] as $tujuan): ?>
                                        <li>
                                            <!-- TUJUAN NODE -->
                                            <div class="tree-node">
                                                <div class="box-tujuan">
                                                    <?= esc($tujuan['tujuan_rpjmd']) ?>
                                                </div>
                                                <!-- INDIKATOR TUJUAN -->
                                                <?php foreach ($tujuan['indikator_tujuan'] as $ikt): ?>
                                                    <div class="box-ikt">
                                                        <?= esc($ikt['indikator_tujuan']) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if (!empty($tujuan['sasaran'])): ?>
                                                <ul>
                                                    <?php foreach ($tujuan['sasaran'] as $sasaran): ?>
                                                        <li>
                                                            <!-- SASARAN NODE -->
                                                            <div class="tree-node">
                                                                <!-- CSF -->
                                                                <?php if (!empty($sasaran['csf'])): ?>
                                                                    <div class="box-csf">
                                                                        <div class="opacity-75 mb-1" style="font-size:9px;">Critical Success Factor</div>
                                                                        <?= nl2br(esc($sasaran['csf'])) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="box-sasaran">
                                                                    <?= nl2br(esc($sasaran['sasaran_rpjmd'])) ?>
                                                                </div>
                                                                <!-- INDIKATOR SASARAN -->
                                                                <?php foreach ($sasaran['indikator_sasaran'] as $iks): ?>
                                                                    <div class="box-iks">
                                                                        <?= esc($iks['indikator_sasaran']) ?>
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
            </ul>
        </div>
    </div>

    <!-- Script to Auto Print (dimatikan sesuai request) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Auto open print dialog when page is loaded (disabled request user)
            // setTimeout(() => {
            //     window.print();
            // }, 500);

            // Logic to go back / close window when user clicks close
            document.getElementById('btnClose').addEventListener('click', () => {
                if(window.history.length > 1) {
                    window.history.back();
                } else {
                    window.close();
                }
            });
        });
    </script>
</body>
</html>
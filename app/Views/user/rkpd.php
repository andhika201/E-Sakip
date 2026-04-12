<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKPD - e-SAKIP</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <?= $this->include('user/templates/header'); ?>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="container my-5" style="max-width: 1700px;">
            <div class="bg-white p-4 rounded shadow-sm">
                <h4 class="fw-bold text-center text-success mb-4 text-uppercase">
                    Rencana Kerja Pemerintah Daerah (RKPD)
                </h4>
                
                <!-- Filter -->
                <div class="row justify-content-center mb-4">
                    <div class="col-12 col-xl-10">
                        <form method="GET" action="<?= base_url('rkpd') ?>" class="row g-2 justify-content-center align-items-center">
                            <div class="col-12 col-md-5">
                                <select name="opd_id" class="form-select w-100" onchange="this.form.submit()">
                                    <option value="all">Semua Perangkat Daerah</option>
                                    <?php foreach ($opdList as $opd): ?>
                                        <option value="<?= $opd['id'] ?>" <?= ($selected_opd == $opd['id']) ? 'selected' : '' ?>>
                                            <?= esc($opd['nama_opd']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 col-md-3">
                                <select name="tahun" class="form-select w-100" onchange="this.form.submit()">
                                    <option value="all">Semua Tahun</option>
                                    <?php foreach ($available_years as $year): ?>
                                        <option value="<?= $year ?>" <?= ($selected_tahun == $year) ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-success w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center table-hover">
                        <thead class="table-success align-middle">
                            <tr>
                                <th class="p-2">No</th>
                                <th class="p-2">Perangkat Daerah</th>
                                <th class="p-2">Sasaran Strategis</th>
                                <th class="p-2">Indikator Kinerja</th>
                                <th class="p-2">Tahun</th>
                                <th class="p-2">Target</th>
                                <th class="p-2">Program</th>
                                <th class="p-2">Kegiatan</th>
                                <th class="p-2">Sub Kegiatan</th>
                                <th class="p-2">Anggaran (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rkpd_data)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted p-4">
                                        <i class="fas fa-info-circle me-2"></i>Tidak ada data RKPD untuk tahun/filter yang dipilih.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                    // Grouping Data
                                    $grouped = [];
                                    foreach ($rkpd_data as $row) {
                                        $opd = $row['nama_opd'] ?? '-';
                                        $sasaran = $row['sasaran'] ?? '-';
                                        $indikator = $row['indikator_sasaran'] ?? '-';
                                        
                                        $key = $opd . '||' . $sasaran . '||' . $indikator;
                                        
                                        if(!isset($grouped[$key])) {
                                            $grouped[$key] = [
                                                'opd' => $opd,
                                                'sasaran' => $sasaran,
                                                'indikator' => $indikator,
                                                'satuan' => $row['satuan'],
                                                'tahun' => $row['tahun'],
                                                'target' => $row['target_renstra'],
                                                'items' => []
                                            ];
                                        }
                                        
                                        $grouped[$key]['items'][] = [
                                            'program' => $row['program_kegiatan'] ?? '-',
                                            'kegiatan' => $row['nama_kegiatan'] ?? '-',
                                            'subkegiatan' => $row['nama_subkegiatan'] ?? '-',
                                            'anggaran' => $row['target_anggaran'] ?? '0'
                                        ];
                                    }
                                    
                                    $no = 1;
                                ?>
                                <?php foreach($grouped as $group): ?>
                                    <?php 
                                        $rowspan = count($group['items']);
                                        if($rowspan == 0) $rowspan = 1; // Safeguard
                                    ?>
                                    <tr>
                                        <!-- KOLOM INDUK -->
                                        <td class="p-2" rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                                        <td class="p-2 text-start" rowspan="<?= $rowspan ?>"><?= esc($group['opd']) ?></td>
                                        <td class="p-2 text-start" rowspan="<?= $rowspan ?>"><?= esc($group['sasaran']) ?></td>
                                        <td class="p-2 text-start" rowspan="<?= $rowspan ?>">
                                            <?= esc($group['indikator']) ?>
                                            <?php if(!empty($group['satuan'])): ?>
                                                <br><small class="text-muted">(<?= esc($group['satuan']) ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-2" rowspan="<?= $rowspan ?>"><?= esc($group['tahun']) ?></td>
                                        <td class="p-2" rowspan="<?= $rowspan ?>"><?= esc($group['target']) ?></td>
                                        
                                        <!-- KOLOM KEGIATAN PERTAMA -->
                                        <?php if(!empty($group['items'])): ?>
                                            <td class="p-2 text-start"><?= esc($group['items'][0]['program']) ?></td>
                                            <td class="p-2 text-start"><?= esc($group['items'][0]['kegiatan']) ?></td>
                                            <td class="p-2 text-start"><?= esc($group['items'][0]['subkegiatan']) ?></td>
                                            <td class="p-2 text-end">
                                                <?= is_numeric($group['items'][0]['anggaran']) ? 'Rp ' . number_format($group['items'][0]['anggaran'], 0, ',', '.') : esc($group['items'][0]['anggaran']) ?>
                                            </td>
                                        <?php else: ?>
                                            <td class="p-2 text-center text-muted">-</td>
                                            <td class="p-2 text-center text-muted">-</td>
                                            <td class="p-2 text-center text-muted">-</td>
                                            <td class="p-2 text-center text-muted">-</td>
                                        <?php endif; ?>
                                    </tr>
                                    
                                    <!-- SISA BARIS MAPPING (JIKA ADA) -->
                                    <?php for($i = 1; $i < $rowspan; $i++): ?>
                                        <tr>
                                            <td class="p-2 text-start"><?= esc($group['items'][$i]['program']) ?></td>
                                            <td class="p-2 text-start"><?= esc($group['items'][$i]['kegiatan']) ?></td>
                                            <td class="p-2 text-start"><?= esc($group['items'][$i]['subkegiatan']) ?></td>
                                            <td class="p-2 text-end">
                                                <?= is_numeric($group['items'][$i]['anggaran']) ? 'Rp ' . number_format($group['items'][$i]['anggaran'], 0, ',', '.') : esc($group['items'][$i]['anggaran']) ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?= $this->include('user/templates/footer'); ?>

</body>
</html>

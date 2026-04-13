<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PK Bupati - e-SAKIP</title>
    <?= $this->include('user/templates/style.php'); ?>
</head>

<body>
    <?= $this->include('user/templates/header'); ?>

    <main class="flex-grow-1 p-4 mt-2">
        <div class="bg-white rounded shadow p-4">

            <h2 class="h3 fw-bold text-success text-center mb-4">
                PK BUPATI
            </h2>

            <!-- Filter Tahun -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-semibold text-muted">Tahun PK</label>
                            <select name="tahun" class="form-select" onchange="this.form.submit()">
                                <option value="" disabled <?= empty($tahun) ? 'selected' : '' ?>>
                                    Pilih Tahun
                                </option>
                                <?php foreach ($availableYears as $yr): ?>
                                    <option value="<?= esc($yr) ?>" <?= ($tahun == $yr) ? 'selected' : '' ?>>
                                        <?= esc($yr) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($availableYears)): ?>
                                    <option disabled>-- Belum ada data --</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Sasaran dan Indikator -->
            <div class="table-responsive">
                <?php if (!empty($sasaranList)): ?>
                    <h4 class="h5 fw-bold text-success mb-3">SASARAN DAN INDIKATOR</h4>
                    <table class="table table-bordered table-striped text-center small">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2" style="width:5%">NO</th>
                                <th class="border p-2" style="width:30%">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2" style="width:12%">TARGET</th>
                                <th class="border p-2" style="width:10%">SATUAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($sasaranList as $sasaran): ?>
                                <?php
                                $label = strtoupper(trim($sasaran['sasaran']));
                                if (in_array($label, ['-', 'N/A'])) continue;
                                if (empty($sasaran['indikator'])) continue;
                                $rowspan = count($sasaran['indikator']);
                                ?>
                                <?php foreach ($sasaran['indikator'] as $i => $ind): ?>
                                    <tr>
                                        <?php if ($i === 0): ?>
                                            <td class="border p-2 align-middle" rowspan="<?= $rowspan ?>">
                                                <?= $no++ ?>
                                            </td>
                                            <td class="border p-2 align-middle text-start" rowspan="<?= $rowspan ?>">
                                                <?= esc($sasaran['sasaran']) ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="border p-2 text-start"><?= esc($ind['indikator']) ?></td>
                                        <td class="border p-2"><?= esc($ind['target']) ?></td>
                                        <td class="border p-2"><?= esc($ind['satuan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($tahun): ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-info-circle me-1"></i>
                        Belum ada data PK Bupati untuk tahun <?= esc($tahun) ?>.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-1"></i>
                        Silakan pilih tahun untuk menampilkan data.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?= $this->include('user/templates/footer'); ?>
</body>

</html>
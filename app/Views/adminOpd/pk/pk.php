<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PK <?= ucfirst($jenis) ?> - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative" data-jenis="<?= $jenis ?>">
    <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>
    <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>
    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <?php
            if (stripos($current_opd['nama_opd'], 'kecamatan') !== false) {
                $judulPk = 'CAMAT';
            } else {
                $judulPk = strtoupper($jenis);
            }
            ?>

            <h2 class="h3 fw-bold text-success text-center mb-4">
                PK <?= $judulPk ?>
            </h2>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">

                        <!-- Tahun -->
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label fw-semibold text-muted">
                                Tahun PK
                            </label>
                            <select name="tahun" class="form-select" onchange="this.form.submit()">
                                <option value="" disabled <?= empty($tahun) ? 'selected' : '' ?>>
                                    Pilih Tahun
                                </option>
                                <?php for ($i = 2020; $i <= 2030; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($tahun == $i) ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Relasi PK -->
                        <div class="col-lg-5 col-md-8">
                            <label class="form-label fw-semibold text-muted">
                                Pihak 1 ↔ Pihak 2
                            </label>
                            <select name="pk_id" class="form-select" <?= empty($tahun) ? 'disabled' : '' ?>>
                                <option value="">-- Pilih Relasi PK --</option>
                                <?php foreach ($pkRelasiList as $pk): ?>
                                    <option value="<?= $pk['id'] ?>" <?= ($pk_id == $pk['id']) ? 'selected' : '' ?>>
                                        <?= esc($pk['relasi']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tombol Tampilkan -->
                        <div class="col-lg-2 col-md-6">
                            <button class="btn btn-success w-100">
                                <i class="fas fa-search me-1"></i>
                                Tampilkan
                            </button>
                        </div>

                        <!-- Tombol Tambah -->
                        <div class="col-lg-2 col-md-6 text-lg-end">
                            <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis . '/tambah') ?>"
                                class="btn btn-outline-success w-100">
                                <i class="fas fa-plus me-1"></i>
                                Tambah
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <?php if (isset($pk_data['jenis']) && $pk_data['jenis'] === $jenis): ?>
                    <!-- Tabel Misi Bupati untuk jenis JPT -->
                    <?php if (!empty($pk_data['id']) && strtolower($jenis) === 'jpt'): ?>
                        <?php $misiBupati = model('App\\Models\\RpjmdModel')->getAllMisi(); ?>
                        <?php $pkMisiRows = model('App\\Models\\PkModel')->db->table('pk_misi')->where('pk_id', $pk_data['id'])->get()->getResultArray(); ?>
                        <?php if (!empty($pkMisiRows)): ?>
                            <h4 class="h5 fw-bold text-primary text-left mb-2">Misi Bupati</h4>
                            <table class="table table-bordered table-striped text-center small mb-4"
                                style="max-width:600px; margin-left:0;">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="border p-2" style="width:50px;">NO</th>
                                        <th class="border p-2" style="width:500px;">Misi Bupati</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no_misi = 1; ?>
                                    <?php foreach ($pkMisiRows as $row): ?>
                                        <?php $misi = model('App\\Models\\RpjmdModel')->getMisiById($row['rpjmd_misi_id']); ?>
                                        <?php if ($misi): ?>
                                            <tr>
                                                <td class="border p-2" style="width:50px;"><?= $no_misi++ ?></td>
                                                <td class="border p-2" style="width:500px;"><?= esc($misi['misi']) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!-- Tabel Indikator Acuan (Referensi) -->
                    <?php if (!empty($pk_data['id']) && strtolower($jenis) !== 'jpt'): ?>
                        <?php $indikatorAcuan = model('App\\Models\\PkModel')->getIndikatorAcuanByPkId($pk_data['id']); ?>
                        <?php if (!empty($indikatorAcuan)): ?>
                            <h4 class="h5 fw-bold text-primary text-left mb-2">Indikator Acuan (Referensi)</h4>
                            <table class="table table-bordered table-striped text-center small mb-4"
                                style="max-width:400px; margin-left:0;">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="border p-2" style="width:50px;">NO</th>
                                        <th class="border p-2" style="width:300px;">Indikator Acuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no_acuan = 1; ?>
                                    <?php foreach ($indikatorAcuan as $acuan): ?>
                                        <tr>
                                            <td class="border p-2" style="width:50px;"><?= $no_acuan++ ?></td>
                                            <td class="border p-2" style="width:300px;"><?= esc($acuan['nama_indikator']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
                    <h4 class="h3 fw-bold text-success text-left mb-4">SASARAN DAN INDIKATOR</h4>

                    <table class="table table-bordered table-striped text-center small mb-5">
                        <thead class="table-success">
                            <tr>
                                <th class="border p-2">NO</th>
                                <th class="border p-2">SASARAN</th>
                                <th class="border p-2">INDIKATOR</th>
                                <th class="border p-2">TARGET</th>
                                <th class="border p-2">SATUAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>

                            <?php if (!empty($pk_data['sasaran'])): ?>
                                <?php foreach ($pk_data['sasaran'] as $sasaran): ?>

                                    <?php
                                    // Skip sasaran tidak valid
                                    $label = strtoupper(trim($sasaran['sasaran']));
                                    if (in_array($label, ['-', 'N/A'])) {
                                        continue;
                                    }

                                    if (empty($sasaran['indikator'])) {
                                        continue;
                                    }

                                    $rowspan = count($sasaran['indikator']);
                                    ?>

                                    <?php foreach ($sasaran['indikator'] as $i => $indikator): ?>
                                        <tr>
                                            <?php if ($i === 0): ?>
                                                <!-- NO -->
                                                <td class="border p-2 align-middle" rowspan="<?= $rowspan ?>">
                                                    <?= $no++ ?>
                                                </td>

                                                <!-- SASARAN -->
                                                <td class="border p-2 align-middle" rowspan="<?= $rowspan ?>">
                                                    <?= esc($sasaran['sasaran']) ?>
                                                </td>
                                            <?php endif; ?>

                                            <!-- INDIKATOR -->
                                            <td class="border p-2">
                                                <?= esc($indikator['indikator']) ?>
                                            </td>

                                            <!-- TARGET -->
                                            <td class="border p-2">
                                                <?= esc($indikator['target']) ?>
                                            </td>

                                            <!-- SATUAN -->
                                            <td class="border p-2">
                                                <?= esc($indikator['satuan_nama']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if (strtolower($jenis) === 'bupati'): ?>
                        <h4 class="h3 fw-bold text-success text-left mb-4">PROGRAM DAN ANGGARAN</h4>
                        <table class="table table-bordered table-striped text-center small">
                            <thead class="table-info">
                                <tr>
                                    <th class="border p-2">NO</th>
                                    <th class="border p-2">PROGRAM</th>
                                    <th class="border p-2">ANGGARAN</th>
                                    <th class="border p-2">Tingkat PK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_program = 1; ?>
                                <?php $allPrograms = model('App\\Models\\PkModel')->getAllPrograms(); ?>
                                <?php foreach ($allPrograms as $program): ?>
                                    <tr>
                                        <td class="border p-2"><?= $no_program++ ?></td>
                                        <td class="border p-2"><?= esc($program['program_kegiatan']) ?></td>
                                        <td class="border p-2">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                        <td class="border p-2">Bupati</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($jenis === 'jpt' && $tampilkanProgram): ?>
                        <h4 class="h3 fw-bold text-success text-left mb-4">PROGRAM DAN ANGGARAN</h4>

                        <table class="table table-bordered table-striped text-center small">
                            <thead class="table-info">
                                <tr>
                                    <th class="border p-2">NO</th>
                                    <th class="border p-2">PROGRAM</th>
                                    <th class="border p-2">ANGGARAN</th>
                                    <th class="border p-2">Tingkat PK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>

                                <?php if (!empty($pk_data['program'])): ?>
                                    <?php foreach ($pk_data['program'] as $program): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= esc($program['program_kegiatan']) ?></td>
                                            <td>Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                            <td>JPT</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">Tidak ada data program</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                    <?php elseif ($jenis === 'administrator'): ?>
                        <h4 class="h3 fw-bold text-success mb-4">KEGIATAN DAN ANGGARAN</h4>

                        <table class="table table-bordered table-striped small">
                            <thead class="table-info text-center">
                                <tr>
                                    <th style="width:40px">NO</th>
                                    <th>URAIAN</th>
                                    <th style="width:180px">ANGGARAN</th>
                                    <th style="width:120px">TINGKAT PK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $programMap = [];
                                foreach ($pk_data['sasaran'] as $sasaran) {
                                    foreach ($sasaran['indikator'] as $indikator) {
                                        foreach ($indikator['program'] as $program) {

                                            $programKey = $program['program_id']; // kunci bisnis

                                            if (!isset($programMap[$programKey])) {
                                                $programMap[$programKey] = [
                                                    'program_kegiatan' => $program['program_kegiatan'],
                                                    'kegiatan' => []
                                                ];
                                            }

                                            foreach ($program['kegiatan'] as $keg) {
                                                // kunci unik kegiatan = nama + anggaran
                                                $kegKey = md5($keg['kegiatan'] . '|' . $keg['anggaran']);

                                                if (!isset($programMap[$programKey]['kegiatan'][$kegKey])) {
                                                    $programMap[$programKey]['kegiatan'][$kegKey] = $keg;
                                                }
                                            }
                                        }
                                    }
                                }
                                ?>

                                <?php $no = 1; ?>

                                <?php foreach ($programMap as $program): ?>

                                    <!-- PROGRAM -->
                                    <tr class="table-light fw-bold">
                                        <td colspan="4">
                                            PROGRAM: <?= esc($program['program_kegiatan']) ?>
                                        </td>
                                    </tr>

                                    <!-- KEGIATAN -->
                                    <?php foreach ($program['kegiatan'] as $keg): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= esc($keg['kegiatan']) ?></td>
                                            <td class="text-end">
                                                Rp <?= number_format($keg['anggaran'], 0, ',', '.') ?>
                                            </td>
                                            <td class="text-center">
                                                <?= esc(ucwords($pk_data['jenis'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php endforeach; ?>

                            </tbody>

                        </table>


                    <?php elseif ($jenis === 'pengawas'): ?>

                        <h4 class="h3 fw-bold text-success mb-4">
                            KEGIATAN DAN SUBKEGIATAN
                        </h4>

                        <?php
                        /**
                         * STEP 1: KUMPULKAN DATA
                         */
                        $grouped = [];

                        foreach ($pk_data['sasaran'] as $sasaran) {
                            foreach ($sasaran['indikator'] as $indikator) {
                                foreach ($indikator['program'] as $program) {
                                    foreach ($program['kegiatan'] as $kegiatan) {

                                        $kegId = $kegiatan['kegiatan_id'];

                                        // init kegiatan
                                        if (!isset($grouped[$kegId])) {
                                            $grouped[$kegId] = [
                                                'kegiatan' => $kegiatan['kegiatan'],
                                                'subkegiatan' => []
                                            ];
                                        }

                                        foreach ($kegiatan['subkegiatan'] as $sub) {
                                            $subKey = $sub['subkegiatan_id'];

                                            // cegah duplikat subkegiatan
                                            if (!isset($grouped[$kegId]['subkegiatan'][$subKey])) {
                                                $grouped[$kegId]['subkegiatan'][$subKey] = [
                                                    'nama' => $sub['sub_kegiatan'],
                                                    'anggaran' => $sub['anggaran']
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        ?>

                        <table class="table table-bordered table-striped small">
                            <thead class="table-info text-center">
                                <tr>
                                    <th style="width:40px">NO</th>
                                    <th>KEGIATAN / SUBKEGIATAN</th>
                                    <th style="width:180px">ANGGARAN</th>
                                    <th style="width:120px">TINGKAT PK</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php
                                $no = 1;

                                foreach ($grouped as $kegiatan):
                                ?>

                                    <!-- HEADER KEGIATAN (HANYA SEKALI) -->
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="4">
                                            KEGIATAN: <?= esc($kegiatan['kegiatan']) ?>
                                        </td>
                                    </tr>

                                    <?php foreach ($kegiatan['subkegiatan'] as $sub): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= esc($sub['nama']) ?></td>
                                            <td class="text-end">
                                                Rp <?= number_format($sub['anggaran'], 0, ',', '.') ?>
                                            </td>
                                            <td class="text-center">
                                                <?= esc(ucwords($pk_data['jenis'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                <?php endforeach; ?>

                            </tbody>
                        </table>

                    <?php endif; ?>


                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis . '/cetak/' . $pk_data['id']) ?>"
                            class="btn btn-primary btn-sm text-white" target="_blank">
                            <i class="fas fa-download me-1"></i> Download
                        </a>

                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $pk_data['jenis'] . '/edit/' . $pk_data['id']) ?>"
                            class="btn btn-success btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>

                        <button class="btn btn-danger btn-sm" onclick="deletePk(<?= $pk_data['id'] ?>)">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </button>

                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Belum ada data PK</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?= $this->include('adminOpd/templates/footer.php'); ?>
    <!-- Global JS variables for PK page -->
    <script>
        const tahunSelect = document.getElementById('tahun');
        if (tahunSelect) {
            tahunSelect.addEventListener('change', function() {
                window.location = '?tahun=' + this.value;
            });
        }
        window.base_url = '<?= base_url() ?>';
        window.jenis = '<?= $jenis ?>';
    </script>
    <!-- <script src="<?= base_url('assets/js/adminOpd/pk/pk.js') ?>"></script> -->
    <script src="<?= base_url('assets/js/adminOpd/pk/pk_detail.js') ?>"></script>
</body>

</html>
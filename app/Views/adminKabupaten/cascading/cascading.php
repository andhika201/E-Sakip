<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>

    <?php if (function_exists('csrf_token')): ?>
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php endif; ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">Cascading & Pohon Kinerja</h2>

                <!-- Flash Message -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php
                $filters = $filters ?? [
                    'misi' => '',
                    'tujuan' => '',
                    'rpjmd' => '',
                    'periode' => '',
                    'status' => '',
                ];
                ?>

                <!-- ===================== FORM FILTER ===================== -->
                <form id="filterForm" method="GET" action="<?= base_url('adminkab/cascading') ?>"
                    class="d-flex flex-column flex-md-row gap-2 mb-4 align-items-center">

                    <!-- Periode -->
                    <select id="periodeFilter" name="periode" class="form-select" style="flex:1;"
                        onchange="this.form.submit()">
                        <option value="">-- Pilih Periode --</option>
                        <?php
                        $periodeList = [];
                        if (!empty($periode_master ?? [])) {
                            foreach ($periode_master as $p) {
                                $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
                                $periodeList[$key] = $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'];
                            }
                        } elseif (!empty($renstra_data)) {
                            foreach ($renstra_data as $d) {
                                if (!empty($d['tahun_mulai']) && !empty($d['tahun_akhir'])) {
                                    $key = $d['tahun_mulai'] . '-' . $d['tahun_akhir'];
                                    $periodeList[$key] = $d['tahun_mulai'] . ' - ' . $d['tahun_akhir'];
                                }
                            }
                        }
                        foreach ($periodeList as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= ($filters['periode'] === $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Tombol Aksi -->
                    <div class="d-flex gap-2 mt-2 mt-md-0">
                        <a href="<?= base_url('adminkab/cascading') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($filters['periode'])): ?>

                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu untuk menampilkan data Cascading.
                    </div>

                <?php elseif (empty($rows)): ?>

                    <div class="alert alert-info text-center p-4">
                        📁 Tidak ada data Cascading untuk periode yang dipilih.
                    </div>

                <?php else: ?>

                    <?php
                    [$start, $end] = explode('-', $filters['periode']);
                    $start = (int) trim($start);
                    $end = (int) trim($end);
                    $yearCount = $end - $start + 1;
                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small">
                            <thead class="table-success text-center">
                                <tr>
                                    <th rowspan="2">Tujuan</th>
                                    <th rowspan="2">CSF</th>
                                    <th rowspan="2">Sasaran</th>
                                    <th rowspan="2">Indikator</th>
                                    <th rowspan="2">Satuan</th>
                                    <th rowspan="2">Baseline</th>

                                    <th colspan="<?= count($years) ?>">Target</th>

                                    <th rowspan="2">Program</th>
                                    <th rowspan="2">OPD</th>
                                    <th rowspan="2">Aksi</th>
                                </tr>

                                <tr>
                                    <?php foreach ($years as $y): ?>
                                        <th>
                                            <?= $y ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>



                            <tbody>
                                <?php foreach ($rows as $index => $r): ?>
                                    <tr>

                                        <!-- TUJUAN -->
                                        <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?? 1 ?>">
                                                <?= esc($r['tujuan_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <!-- CSF -->
                                        <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>" style="min-width:180px;">
                                                <div class="d-flex flex-column gap-1">
                                                    <textarea class="form-control form-control-sm csf-input"
                                                        data-sasaran-id="<?= $r['sasaran_id'] ?>"
                                                        rows="2"
                                                        placeholder="Isi CSF..."><?= esc($r['csf'] ?? '') ?></textarea>
                                                    <button type="button" class="btn btn-sm btn-primary btn-save-csf" data-sasaran-id="<?= $r['sasaran_id'] ?>">
                                                        <i class="fas fa-save"></i> Simpan
                                                    </button>
                                                </div>
                                            </td>
                                        <?php endif; ?>

                                        <!-- SASARAN -->
                                        <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?? 1 ?>">
                                                <?= esc($r['sasaran_rpjmd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <!-- INDIKATOR -->
                                        <?php if ($firstShow['indikator'][$r['indikator_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                <?= esc($r['indikator_sasaran']) ?>
                                            </td>

                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                <?= esc($r['satuan']) ?>
                                            </td>

                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                <?= esc($r['baseline']) ?>
                                            </td>

                                            <?php foreach ($years as $y): ?>
                                                <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">
                                                    <?= esc($r['targets'][$y] ?? '-') ?>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <!-- PROGRAM -->
                                        <td>
                                            <?= $r['program_kegiatan'] ?? '-' ?>
                                        </td>

                                        <!-- OPD -->
                                        <?php
                                        $key = $r['indikator_id'] . '-' . $r['nama_opd'];
                                        ?>

                                        <?php if ($firstShow['opd'][$key] == $index): ?>
                                            <td rowspan="<?= $rowspan['opd'][$key] ?? 1 ?>">
                                                <?= esc($r['nama_opd']) ?>
                                            </td>
                                        <?php endif; ?>

                                        <!-- ACTION -->
                                        <?php if ($firstShow['indikator'][$r['indikator_id']] == $index): ?>
                                            <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>">

                                                <?php if (($r['is_mapped'] ?? 0) == 1): ?>

                                                    <a href="<?= base_url('adminkab/cascading/tambah/' . $r['indikator_id'] . '?periode=' . ($filters['periode'] ?? '')) ?>"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                <?php else: ?>

                                                    <a href="<?= base_url('adminkab/cascading/tambah/' . $r['indikator_id'] . '?periode=' . ($filters['periode'] ?? '')) ?>"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i>
                                                    </a>

                                                <?php endif; ?>

                                            </td>
                                        <?php endif; ?>


                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>
                <?php endif; ?>

            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="<?= base_url(
                    'adminkab/cascading/cetak?periode=' . $filters['periode']
                ) ?>" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Cetak PDF
                </a>

                <a href="<?= base_url(
                    'adminkab/cascading/cetak-pohon?periode=' . $filters['periode']
                ) ?>" class="btn btn-success">
                    <i class="fas fa-sitemap"></i> Cetak Pohon Kinerja
                </a>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-save-csf').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const sasaranId = this.getAttribute('data-sasaran-id');
                const textarea = document.querySelector('.csf-input[data-sasaran-id="' + sasaranId + '"]');
                const csf = textarea.value;
                const button = this;

                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                const formData = new FormData();
                formData.append('sasaran_id', sasaranId);
                formData.append('csf', csf);

                <?php if (function_exists('csrf_token')): ?>
                formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                <?php endif; ?>

                fetch('<?= base_url('adminkab/cascading/save-csf') ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        button.innerHTML = '<i class="fas fa-check"></i> Tersimpan';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');
                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-save"></i> Simpan';
                            button.classList.remove('btn-success');
                            button.classList.add('btn-primary');
                            button.disabled = false;
                        }, 2000);
                    } else {
                        alert('Gagal menyimpan CSF: ' + (data.message || ''));
                        button.innerHTML = '<i class="fas fa-save"></i> Simpan';
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan saat menyimpan CSF');
                    button.innerHTML = '<i class="fas fa-save"></i> Simpan';
                    button.disabled = false;
                });
            });
        });
    });
    </script>

</body>

</html>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Rencana Strategis') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">📊 Rencana Strategis</h2>

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
                // jaga2 supaya $filters selalu ada
                $filters = $filters ?? [
                    'misi' => '',
                    'tujuan' => '',
                    'rpjmd' => '',
                    'periode' => '',
                    'status' => '',
                ];
                ?>

                <!-- ===================== FORM FILTER ===================== -->
                <form method="GET" action="<?= base_url('adminopd/renstra') ?>"
                    class="d-flex flex-column flex-md-row gap-2 mb-4 align-items-center">

                    <!-- Misi RPJMD -->
                    <select id="misiFilter" name="misi" class="form-select" style="flex:1;"
                        onchange="this.form.submit()">
                        <option value="">Semua Misi RPJMD</option>
                        <?php
                        $misiList = [];
                        if (!empty($renstra_data)) {
                            foreach ($renstra_data as $row) {
                                if (!empty($row['rpjmd_misi'])) {
                                    $misiList[$row['rpjmd_misi']] = $row['rpjmd_misi'];
                                }
                            }
                        }
                        ksort($misiList);
                        foreach ($misiList as $misiText): ?>
                            <option value="<?= esc($misiText) ?>" <?= $filters['misi'] === $misiText ? 'selected' : '' ?>>
                                <?= esc($misiText) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Tujuan Renstra -->
                    <select id="tujuanFilter" name="tujuan" class="form-select" style="flex:1;"
                        onchange="this.form.submit()">
                        <option value="">Semua Tujuan Renstra</option>
                        <?php
                        $tujuanList = [];
                        if (!empty($renstra_data)) {
                            foreach ($renstra_data as $d) {
                                if (!empty($d['tujuan_renstra'])) {
                                    $tujuanList[$d['tujuan_renstra']] = $d['tujuan_renstra'];
                                }
                            }
                        }
                        asort($tujuanList);
                        foreach ($tujuanList as $t): ?>
                            <option value="<?= esc($t) ?>" <?= $filters['tujuan'] === $t ? 'selected' : '' ?>>
                                <?= esc($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Sasaran RPJMD -->
                    <select id="rpjmdFilter" name="rpjmd" class="form-select" style="flex:1;"
                        onchange="this.form.submit()">
                        <option value="">Semua Sasaran RPJMD</option>
                        <?php
                        $sList = [];
                        if (!empty($renstra_data)) {
                            foreach ($renstra_data as $d) {
                                if (!empty($d['sasaran_rpjmd'])) {
                                    $sList[$d['sasaran_rpjmd']] = $d['sasaran_rpjmd'];
                                }
                            }
                        }
                        asort($sList);
                        foreach ($sList as $s): ?>
                            <option value="<?= esc($s) ?>" <?= $filters['rpjmd'] === $s ? 'selected' : '' ?>>
                                <?= esc($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Periode (WAJIB dipilih, yang lain akan disable kalau ini kosong) -->
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
                            <option value="<?= esc($key) ?>" <?= $filters['periode'] === $key ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status -->
                    <select id="statusFilter" name="status" class="form-select" style="flex:1;"
                        onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="selesai" <?= $filters['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>

                    <!-- Tombol Aksi -->
                    <div class="d-flex gap-2 mt-2 mt-md-0">
                        <a href="<?= base_url('adminopd/renstra') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                        <a href="<?= base_url('adminopd/renstra/tambah') ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Tambah RENSTRA
                        </a>
                    </div>
                </form>

                <!-- ================ LOGIKA TAMPIL DATA ================= -->
                <?php if (empty($filters['periode'])): ?>

                    <!-- WAJIB PILIH PERIODE DULU -->
                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu untuk menampilkan data RENSTRA.
                    </div>

                <?php elseif (empty($renstra_data)): ?>

                    <!-- PERIODE SUDAH DIPILIH, TAPI DATA KOSONG -->
                    <div class="alert alert-info text-center p-4">
                        📁 Tidak ada data RENSTRA untuk filter yang dipilih.
                    </div>

                <?php else: ?>

                    <?php
                    // Range tahun dari filter periode (mis: 2025-2029)
                    [$start, $end] = explode('-', $filters['periode']);
                    $start = (int) trim($start);
                    $end = (int) trim($end);
                    $yearCount = $end - $start + 1;
                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small">
                            <thead class="table-success fw-bold text-dark text-center">
                                <tr>
                                    <th rowspan="2">Sasaran RPJMD</th>
                                    <th rowspan="2">Tujuan RENSTRA</th>
                                    <th rowspan="2">Indikator Tujuan</th>
                                    <th colspan="<?= $yearCount ?>">TARGET TUJUAN PER TAHUN</th>

                                    <th rowspan="2">Sasaran RENSTRA</th>
                                    <th rowspan="2">Indikator Sasaran</th>
                                    <th rowspan="2">Satuan</th>
                                    <th colspan="<?= $yearCount ?>">TARGET SASARAN PER TAHUN</th>

                                    <th rowspan="2">Status</th>
                                    <th rowspan="2">Aksi</th>
                                </tr>
                                <tr>
                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th><?= $y ?></th>
                                    <?php endfor; ?>

                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th><?= $y ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $rowCount = count($renstra_data);
                                $currentGroupKey = null;
                                $groupRowspan = 0;

                                for ($index = 0; $index < $rowCount; $index++):
                                    $r = $renstra_data[$index];

                                    // kelompok: Sasaran RPJMD + Tujuan Renstra + Indikator Tujuan
                                    $groupKey = ($r['sasaran_rpjmd'] ?? '') . '|' .
                                        ($r['tujuan_renstra'] ?? '') . '|' .
                                        ($r['indikator_tujuan'] ?? '');

                                    if ($groupKey !== $currentGroupKey) {
                                        // hitung banyak baris satu grup
                                        $groupRowspan = 1;
                                        for ($i = $index + 1; $i < $rowCount; $i++) {
                                            $next = $renstra_data[$i];
                                            $nextKey = ($next['sasaran_rpjmd'] ?? '') . '|' .
                                                ($next['tujuan_renstra'] ?? '') . '|' .
                                                ($next['indikator_tujuan'] ?? '');
                                            if ($nextKey === $groupKey) {
                                                $groupRowspan++;
                                            } else {
                                                break;
                                            }
                                        }
                                        $currentGroupKey = $groupKey;
                                    } else {
                                        $groupRowspan = 0;
                                    }
                                    ?>
                                    <tr>
                                        <?php if ($groupRowspan > 0): ?>
                                            <!-- Sasaran RPJMD -->
                                            <td rowspan="<?= $groupRowspan ?>">
                                                <?= esc($r['sasaran_rpjmd'] ?? '-') ?>
                                            </td>

                                            <!-- Tujuan RENSTRA -->
                                            <td rowspan="<?= $groupRowspan ?>">
                                                <?= esc($r['tujuan_renstra'] ?? '-') ?>
                                            </td>

                                            <!-- Indikator Tujuan -->
                                            <td rowspan="<?= $groupRowspan ?>">
                                                <?= esc($r['indikator_tujuan'] ?? '-') ?>
                                            </td>

                                            <!-- Target Tujuan per tahun -->
                                            <?php for ($y = $start; $y <= $end; $y++): ?>
                                                <td rowspan="<?= $groupRowspan ?>">
                                                    <?= esc($r['targets_tujuan'][$y] ?? '-') ?>
                                                </td>
                                            <?php endfor; ?>
                                        <?php endif; ?>

                                        <!-- Sasaran RENSTRA -->
                                        <td><?= esc($r['sasaran'] ?? '-') ?></td>

                                        <!-- Indikator Sasaran -->
                                        <td><?= esc($r['indikator_sasaran'] ?? '-') ?></td>

                                        <!-- Satuan -->
                                        <td><?= esc($r['satuan'] ?? '-') ?></td>

                                        <!-- Target Sasaran per tahun -->
                                        <?php
                                        $targets = $r['targets'] ?? [];
                                        for ($y = $start; $y <= $end; $y++): ?>
                                            <td><?= esc($targets[$y] ?? '-') ?></td>
                                        <?php endfor; ?>

                                        <?php if ($groupRowspan > 0): ?>
                                            <!-- Status -->
                                            <td rowspan="<?= $groupRowspan ?>">
                                                <span class="badge <?= ($r['status'] ?? 'draft') === 'draft'
                                                    ? 'bg-secondary'
                                                    : 'bg-success' ?>">
                                                    <?= ucfirst($r['status'] ?? 'draft') ?>
                                                </span>
                                            </td>

                                            <!-- Aksi -->
                                            <td rowspan="<?= $groupRowspan ?>">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="<?= base_url('adminopd/renstra/edit/' . $r['sasaran_id']) ?>"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= base_url('adminopd/renstra/delete/' . $r['sasaran_id']) ?>"
                                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                                        class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-info btn-sm change-status-btn"
                                                        data-id="<?= $r['sasaran_id'] ?>">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        // 🔒 Paksa pilih Periode dulu: filter lain disable kalau periode kosong
        document.addEventListener('DOMContentLoaded', function () {
            const periodeSelect = document.getElementById('periodeFilter');
            const otherFilters = [
                document.getElementById('misiFilter'),
                document.getElementById('tujuanFilter'),
                document.getElementById('rpjmdFilter'),
                document.getElementById('statusFilter'),
            ];

            function toggleFilters() {
                const hasPeriode = periodeSelect.value.trim() !== '';
                otherFilters.forEach(el => {
                    if (!el) return;
                    el.disabled = !hasPeriode;
                });
            }

            // set awal (kalau periode belum dipilih → disable semua)
            toggleFilters();

            // tiap ganti periode → enable/disable + submit form
            periodeSelect.addEventListener('change', function () {
                toggleFilters();
                this.form.submit();
            });

            // tombol ubah status
            const buttons = document.querySelectorAll('.change-status-btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    if (!confirm('Apakah Anda yakin ingin mengubah status data ini?')) return;

                    fetch('<?= base_url('adminopd/renstra/update-status') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ id: id })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert(`✅ ${data.message}\nStatus sekarang: ${data.newStatus.toUpperCase()}`);
                                location.reload();
                            } else {
                                alert(`❌ ${data.message}`);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('❌ Terjadi kesalahan koneksi.');
                        });
                });
            });
        });
    </script>

</body>

</html>
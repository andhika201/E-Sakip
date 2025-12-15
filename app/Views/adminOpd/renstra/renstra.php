<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Rencana Strategis') ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>

    <?php if (function_exists('csrf_token')): ?>
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php endif; ?>
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
                $filters = $filters ?? [
                    'misi' => '',
                    'tujuan' => '',
                    'rpjmd' => '',
                    'periode' => '',
                    'status' => '',
                ];
                ?>

                <!-- ===================== FORM FILTER ===================== -->
                <form id="filterForm" method="GET" action="<?= base_url('adminopd/renstra') ?>"
                    class="d-flex flex-column flex-md-row gap-2 mb-4 align-items-center">

                    <!-- Misi RPJMD -->
                    <select id="misiFilter" name="misi" class="form-select" style="flex:1;">
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
                            <option value="<?= esc($misiText) ?>" <?= ($filters['misi'] === $misiText) ? 'selected' : '' ?>>
                                <?= esc($misiText) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Tujuan Renstra -->
                    <select id="tujuanFilter" name="tujuan" class="form-select" style="flex:1;">
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
                            <option value="<?= esc($t) ?>" <?= ($filters['tujuan'] === $t) ? 'selected' : '' ?>>
                                <?= esc($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Sasaran RPJMD -->
                    <select id="rpjmdFilter" name="rpjmd" class="form-select" style="flex:1;">
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
                            <option value="<?= esc($s) ?>" <?= ($filters['rpjmd'] === $s) ? 'selected' : '' ?>>
                                <?= esc($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Periode -->
                    <select id="periodeFilter" name="periode" class="form-select" style="flex:1;">
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

                    <!-- Status -->
                    <select id="statusFilter" name="status" class="form-select" style="flex:1;">
                        <option value="">Semua Status</option>
                        <option value="draft" <?= ($filters['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="selesai" <?= ($filters['status'] === 'selesai') ? 'selected' : '' ?>>Selesai
                        </option>
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

                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu untuk menampilkan data RENSTRA.
                    </div>

                <?php elseif (empty($renstra_data)): ?>

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

                                // tracker key per level
                                $curK1 = $curK2 = $curK3 = $curK4 = null;

                                // helper hitung rowspan (data harus sudah berurutan)
                                $calcRowspan = function (array $data, int $startIndex, callable $key) use ($rowCount) {
                                    $span = 1;
                                    $baseKey = $key($data[$startIndex]);
                                    for ($i = $startIndex + 1; $i < $rowCount; $i++) {
                                        if ($key($data[$i]) === $baseKey)
                                            $span++;
                                        else
                                            break;
                                    }
                                    return $span;
                                };

                                for ($index = 0; $index < $rowCount; $index++):
                                    $r = $renstra_data[$index];

                                    // ====== LEVEL 1: Sasaran RPJMD ======
                                    $k1 = ($r['sasaran_rpjmd'] ?? '');
                                    if ($k1 !== $curK1) {
                                        $rs1 = $calcRowspan($renstra_data, $index, fn($x) => ($x['sasaran_rpjmd'] ?? ''));
                                        $curK1 = $k1;
                                    } else {
                                        $rs1 = 0;
                                    }

                                    // ====== LEVEL 2: Sasaran RPJMD + Tujuan RENSTRA ======
                                    $k2 = $k1 . '|' . ($r['tujuan_renstra'] ?? '');
                                    if ($k2 !== $curK2) {
                                        $rs2 = $calcRowspan(
                                            $renstra_data,
                                            $index,
                                            fn($x) =>
                                            (($x['sasaran_rpjmd'] ?? '') . '|' . ($x['tujuan_renstra'] ?? ''))
                                        );
                                        $curK2 = $k2;
                                    } else {
                                        $rs2 = 0;
                                    }

                                    // ====== LEVEL 3: + Indikator Tujuan (target tujuan ikut rowspan ini) ======
                                    $k3 = $k2 . '|' . ($r['indikator_tujuan'] ?? '');
                                    if ($k3 !== $curK3) {
                                        $rs3 = $calcRowspan(
                                            $renstra_data,
                                            $index,
                                            fn($x) =>
                                            (($x['sasaran_rpjmd'] ?? '') . '|' . ($x['tujuan_renstra'] ?? '') . '|' . ($x['indikator_tujuan'] ?? ''))
                                        );
                                        $curK3 = $k3;
                                    } else {
                                        $rs3 = 0;
                                    }

                                    // ====== LEVEL 4: + Sasaran RENSTRA (biar sasaran renstra yg sama merge) ======
                                    $k4 = $k3 . '|' . ($r['sasaran'] ?? '');
                                    if ($k4 !== $curK4) {
                                        $rs4 = $calcRowspan(
                                            $renstra_data,
                                            $index,
                                            fn($x) =>
                                            (($x['sasaran_rpjmd'] ?? '') . '|' . ($x['tujuan_renstra'] ?? '') . '|' . ($x['indikator_tujuan'] ?? '') . '|' . ($x['sasaran'] ?? ''))
                                        );
                                        $curK4 = $k4;
                                    } else {
                                        $rs4 = 0;
                                    }
                                    ?>
                                    <tr>
                                        <?php if ($rs1 > 0): ?>
                                            <td rowspan="<?= $rs1 ?>"><?= esc($r['sasaran_rpjmd'] ?? '-') ?></td>
                                        <?php endif; ?>

                                        <?php if ($rs2 > 0): ?>
                                            <td rowspan="<?= $rs2 ?>"><?= esc($r['tujuan_renstra'] ?? '-') ?></td>
                                        <?php endif; ?>

                                        <?php if ($rs3 > 0): ?>
                                            <td rowspan="<?= $rs3 ?>"><?= esc($r['indikator_tujuan'] ?? '-') ?></td>

                                            <?php for ($y = $start; $y <= $end; $y++): ?>
                                                <td rowspan="<?= $rs3 ?>"><?= esc($r['targets_tujuan'][$y] ?? '-') ?></td>
                                            <?php endfor; ?>
                                        <?php endif; ?>

                                        <?php if ($rs4 > 0): ?>
                                            <td rowspan="<?= $rs4 ?>"><?= esc($r['sasaran'] ?? '-') ?></td>
                                        <?php endif; ?>

                                        <td><?= esc($r['indikator_sasaran'] ?? '-') ?></td>
                                        <td><?= esc($r['satuan'] ?? '-') ?></td>

                                        <?php
                                        $targets = $r['targets'] ?? [];
                                        for ($y = $start; $y <= $end; $y++): ?>
                                            <td><?= esc($targets[$y] ?? '-') ?></td>
                                        <?php endfor; ?>

                                        <?php if ($rs3 > 0): ?>
                                            <td rowspan="<?= $rs3 ?>">
                                                <span
                                                    class="badge <?= ($r['status'] ?? 'draft') === 'draft' ? 'bg-secondary' : 'bg-success' ?>">
                                                    <?= ucfirst($r['status'] ?? 'draft') ?>
                                                </span>
                                            </td>

                                            <td rowspan="<?= $rs3 ?>">
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
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('filterForm');

            const periodeSelect = document.getElementById('periodeFilter');
            const misiSelect = document.getElementById('misiFilter');
            const tujuanSelect = document.getElementById('tujuanFilter');
            const rpjmdSelect = document.getElementById('rpjmdFilter');
            const statusSelect = document.getElementById('statusFilter');

            const otherFilters = [misiSelect, tujuanSelect, rpjmdSelect, statusSelect];

            function toggleFilters() {
                const hasPeriode = (periodeSelect?.value || '').trim() !== '';
                otherFilters.forEach(el => {
                    if (!el) return;
                    el.disabled = !hasPeriode;
                });
            }

            toggleFilters();

            // Auto submit: satu pintu
            [periodeSelect, ...otherFilters].forEach(el => {
                if (!el) return;
                el.addEventListener('change', function () {
                    toggleFilters();
                    form.submit();
                });
            });

            // ===================== UBAH STATUS (AJAX) =====================
            const csrfName = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');

            document.querySelectorAll('.change-status-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    if (!confirm('Apakah Anda yakin ingin mengubah status data ini?')) return;

                    const payload = { id: id };
                    if (csrfName && csrfHash) payload[csrfName] = csrfHash;

                    fetch('<?= base_url('adminopd/renstra/update-status') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                alert(`✅ ${data.message}\nStatus sekarang: ${String(data.newStatus || '').toUpperCase()}`);
                                location.reload();
                            } else {
                                alert(`❌ ${data.message || 'Gagal mengubah status.'}`);
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
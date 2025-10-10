<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title) ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <h2 class="h3 fw-bold text-success text-center mb-4">📊 Rencana Strategis</h2>

                <!-- ✅ Flash Message -->
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

                <!-- 🔍 FORM FILTER -->
                <form method="GET" action="<?= base_url('adminopd/renstra') ?>"
                    class="d-flex flex-column flex-md-row gap-2 mb-4 align-items-center">

                    <!-- Misi -->
                    <select name="misi" class="form-select" style="flex:1;" onchange="this.form.submit()"
                        <?= empty($filters['periode']) ? 'disabled' : '' ?>>
                        <option value="">Semua Misi</option>
                        <?php
                        $misiList = [];
                        foreach ($renstra_data as $d) {
                            if ($d['rpjmd_misi'])
                                $misiList[$d['rpjmd_misi']] = $d['rpjmd_misi'];
                        }
                        asort($misiList);
                        foreach ($misiList as $m): ?>
                            <option value="<?= esc($m) ?>" <?= ($filters['misi'] ?? '') == $m ? 'selected' : '' ?>>
                                <?= esc($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Tujuan -->
                    <select name="tujuan" class="form-select" style="flex:1;" onchange="this.form.submit()"
                        <?= empty($filters['periode']) ? 'disabled' : '' ?>>
                        <option value="">Semua Tujuan</option>
                        <?php
                        $tList = [];
                        foreach ($renstra_data as $d) {
                            if ($d['rpjmd_tujuan'])
                                $tList[$d['rpjmd_tujuan']] = $d['rpjmd_tujuan'];
                        }
                        asort($tList);
                        foreach ($tList as $t): ?>
                            <option value="<?= esc($t) ?>" <?= ($filters['tujuan'] ?? '') == $t ? 'selected' : '' ?>>
                                <?= esc($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Sasaran RPJMD -->
                    <select name="rpjmd" class="form-select" style="flex:1;" onchange="this.form.submit()"
                        <?= empty($filters['periode']) ? 'disabled' : '' ?>>
                        <option value="">Semua Sasaran RPJMD</option>
                        <?php
                        $sList = [];
                        foreach ($renstra_data as $d) {
                            if ($d['rpjmd_sasaran'])
                                $sList[$d['rpjmd_sasaran']] = $d['rpjmd_sasaran'];
                        }
                        asort($sList);
                        foreach ($sList as $s): ?>
                            <option value="<?= esc($s) ?>" <?= ($filters['rpjmd'] ?? '') == $s ? 'selected' : '' ?>>
                                <?= esc($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Periode -->
                    <select name="periode" class="form-select" style="flex:1;" onchange="this.form.submit()">
                        <option value="">-- Pilih Periode --</option>
                        <?php
                        $periodeList = [];
                        foreach ($renstra_data as $d) {
                            if ($d['tahun_mulai'] && $d['tahun_akhir']) {
                                $periodeKey = $d['tahun_mulai'] . '-' . $d['tahun_akhir'];
                                $periodeList[$periodeKey] = $d['tahun_mulai'] . ' - ' . $d['tahun_akhir'];
                            }
                        }
                        foreach ($periodeList as $key => $p): ?>
                            <option value="<?= esc($key) ?>" <?= ($filters['periode'] ?? '') == $key ? 'selected' : '' ?>>
                                <?= esc($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status -->
                    <select name="status" class="form-select" style="flex:1;" onchange="this.form.submit()"
                        <?= empty($filters['periode']) ? 'disabled' : '' ?>>
                        <option value="">Semua Status</option>
                        <option value="draft" <?= ($filters['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="selesai" <?= ($filters['status'] ?? '') == 'selesai' ? 'selected' : '' ?>>Selesai
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

                <!-- 📊 TABEL HASIL -->
                <?php if (empty($filters['periode'])): ?>
                    <div class="alert alert-warning text-center p-4">
                        📅 Silakan pilih <strong>Periode</strong> terlebih dahulu untuk menampilkan data RENSTRA.
                    </div>
                <?php elseif (empty($renstra_data)): ?>
                    <div class="alert alert-info text-center p-4">
                        📁 Tidak ada data RENSTRA untuk periode yang dipilih.
                    </div>
                <?php else: ?>

                    <?php
                    $periode = $filters['periode'] ?? null;
                    if ($periode) {
                        [$start, $end] = explode('-', $periode);
                        $start = (int) trim($start);
                        $end = (int) trim($end);
                    } else {
                        $start = $renstra_data[0]['tahun_mulai'] ?? date('Y');
                        $end = $renstra_data[0]['tahun_akhir'] ?? ($start + 4);
                    }
                    ?>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle small">
                            <thead class="table-success fw-bold text-dark text-center">
                                <tr>
                                    <th rowspan="2">Misi</th>
                                    <th rowspan="2">Tujuan</th>
                                    <th rowspan="2">RPJMD Sasaran</th>
                                    <th rowspan="2">Sasaran RENSTRA</th>
                                    <th rowspan="2">Indikator Sasaran</th>
                                    <th rowspan="2">Satuan</th>
                                    <th colspan="<?= ($end - $start + 1) ?>">TARGET CAPAIAN PER TAHUN</th>
                                    <th rowspan="2">Status</th>
                                    <th rowspan="2">Aksi</th>
                                </tr>
                                <tr>
                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th><?= $y ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rowCount = count($renstra_data);
                                $currentMisi = $currentTujuan = $currentSasaran = null;
                                $misiCount = $tujuanCount = $sasaranCount = 0;

                                foreach ($renstra_data as $index => $r) {
                                    if ($r['rpjmd_misi'] !== $currentMisi) {
                                        $misiCount = 1;
                                        for ($i = $index + 1; $i < $rowCount; $i++) {
                                            if ($renstra_data[$i]['rpjmd_misi'] === $r['rpjmd_misi']) {
                                                $misiCount++;
                                            } else
                                                break;
                                        }
                                        $currentMisi = $r['rpjmd_misi'];
                                    } else {
                                        $misiCount = 0;
                                    }

                                    if ($r['rpjmd_tujuan'] !== $currentTujuan) {
                                        $tujuanCount = 1;
                                        for ($i = $index + 1; $i < $rowCount; $i++) {
                                            if ($renstra_data[$i]['rpjmd_tujuan'] === $r['rpjmd_tujuan']) {
                                                $tujuanCount++;
                                            } else
                                                break;
                                        }
                                        $currentTujuan = $r['rpjmd_tujuan'];
                                    } else {
                                        $tujuanCount = 0;
                                    }

                                    if ($r['rpjmd_sasaran'] !== $currentSasaran) {
                                        $sasaranCount = 1;
                                        for ($i = $index + 1; $i < $rowCount; $i++) {
                                            if ($renstra_data[$i]['rpjmd_sasaran'] === $r['rpjmd_sasaran']) {
                                                $sasaranCount++;
                                            } else
                                                break;
                                        }
                                        $currentSasaran = $r['rpjmd_sasaran'];
                                    } else {
                                        $sasaranCount = 0;
                                    }
                                    ?>
                                    <tr>
                                        <?php if ($misiCount > 0): ?>
                                            <td rowspan="<?= $misiCount ?>"><?= esc($r['rpjmd_misi']) ?></td>
                                        <?php endif; ?>

                                        <?php if ($tujuanCount > 0): ?>
                                            <td rowspan="<?= $tujuanCount ?>"><?= esc($r['rpjmd_tujuan']) ?></td>
                                        <?php endif; ?>

                                        <?php if ($sasaranCount > 0): ?>
                                            <td rowspan="<?= $sasaranCount ?>"><?= esc($r['rpjmd_sasaran']) ?></td>
                                        <?php endif; ?>

                                        <td><?= esc($r['sasaran']) ?></td>
                                        <td><?= esc($r['indikator_sasaran']) ?></td>
                                        <td><?= esc($r['satuan']) ?></td>

                                        <?php
                                        $targets = $r['targets'] ?? [];
                                        for ($y = $start; $y <= $end; $y++): ?>
                                            <td><?= esc($targets[$y] ?? '-') ?></td>
                                        <?php endfor; ?>

                                        <td>
                                            <span class="badge <?= $r['status'] === 'draft' ? 'bg-secondary' : 'bg-success' ?>">
                                                <?= ucfirst($r['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="<?= base_url('adminopd/renstra/edit/' . $r['sasaran_id']) ?>"
                                                    class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                                <a href="<?= base_url('adminopd/renstra/delete/' . $r['sasaran_id']) ?>"
                                                    onclick="return confirm('Yakin ingin menghapus data ini?')"
                                                    class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                                <!-- ✅ Tombol Ubah Status -->
                                                <button type="button" class="btn btn-info btn-sm change-status-btn"
                                                    data-id="<?= $r['sasaran_id'] ?>">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
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
            const buttons = document.querySelectorAll('.change-status-btn');

            buttons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');

                    if (!confirm('Apakah Anda yakin ingin mengubah status data ini?')) return;

                    fetch(`<?= base_url('adminopd/renstra/update-status') ?>`, {
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
                                location.reload(); // refresh supaya status baru muncul
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
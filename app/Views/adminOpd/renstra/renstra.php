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
    <style>
        /* Rapikan tabel Renstra */
        .renstra-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            white-space: nowrap;
        }
        .renstra-table th,
        .renstra-table td {
            vertical-align: middle;
        }
        .renstra-table td.text-start {
            min-width: 170px;
        }
        .renstra-table .col-tahun {
            white-space: nowrap;
            width: 60px;
        }
        .renstra-table tbody tr:hover {
            background-color: #f3faf5;
        }
        .table-wrap {
            max-height: 72vh;
            overflow: auto;
        }
        /* Kode indikator (badge "IK" di depan nama) */
        .ind-kode {
            display: inline-block;
            font-weight: 800;
            font-size: .72em;
            letter-spacing: .6px;
            padding: 1px 6px;
            margin-right: 5px;
            border-radius: 5px;
            background: #00743e;
            color: #fff;
            vertical-align: middle;
        }
    </style>
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

                    <?php /* Filter "Misi RPJMD" & "Tujuan Renstra" dihapus (permintaan user) */ ?>

                    <!-- Sasaran RPJMD -->
                    <select id="rpjmdFilter" name="rpjmd" class="form-select select2-flt" style="flex:1;">
                        <option value="">Semua Sasaran RPJMD</option>
                        <?php
                        // Opsi filter diambil dari data se-periode (bukan yang sudah terfilter) agar tidak menciut.
                        $filterSrc = $filter_source ?? ($renstra_data ?? []);
                        $sList = [];
                        if (!empty($filterSrc)) {
                            foreach ($filterSrc as $d) {
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
                    <select id="periodeFilter" name="periode" class="form-select select2-flt" style="flex:1;">
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
                    <select id="statusFilter" name="status" class="form-select select2-flt" style="flex:1;">
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
                        <?php if (!empty($filters['periode'])): ?>
                            <a href="<?= base_url('adminopd/renstra/cetak?' . http_build_query(array_filter([
                                'misi' => $filters['misi'] ?? '',
                                'tujuan' => $filters['tujuan'] ?? '',
                                'rpjmd' => $filters['rpjmd'] ?? '',
                                'periode' => $filters['periode'] ?? '',
                                'status' => $filters['status'] ?? '',
                            ], static fn($v) => $v !== ''))) ?>" target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf"></i> Cetak PDF
                            </a>
                        <?php endif; ?>
                        <?php if (user_can('renstra.create')): ?>
                            <a href="<?= base_url('adminopd/renstra/tambah') ?>" class="btn btn-success">
                                <i class="fas fa-plus"></i> Tambah RENSTRA
                            </a>
                        <?php endif; ?>
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
                    [$start, $end] = explode('-', $filters['periode']);
                    $start = (int) trim($start);
                    $end = (int) trim($end);
                    $yearCount = $end - $start + 1;
                    ?>

                    <div class="table-responsive table-wrap">
                        <table class="table table-bordered text-center align-middle small renstra-table">
                            <thead class="table-success fw-bold text-dark text-center">
                                <tr>
                                    <th rowspan="2">No</th>
                                    <?php /* Kolom "Sasaran RPJMD" di-hide (permintaan user) */ ?>
                                    <th rowspan="2">Tujuan</th>
                                    <th rowspan="2">Indikator Tujuan</th>
                                    <th colspan="<?= $yearCount ?>">TARGET TUJUAN PER TAHUN</th>

                                    <th rowspan="2">Sasaran</th>
                                    <th rowspan="2">Indikator Sasaran</th>
                                    <th rowspan="2">Satuan</th>
                                    <th rowspan="2">Kondisi Awal</th>
                                    <th colspan="<?= $yearCount ?>">TARGET SASARAN PER TAHUN</th>
                                    <th rowspan="2">Kondisi Akhir</th>
                                    <th rowspan="2">Jenis Indikator</th>

                                    <th rowspan="2">Status</th>
                                    <th rowspan="2">Aksi</th>
                                </tr>
                                <tr>
                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th class="col-tahun"><?= $y ?></th>
                                    <?php endfor; ?>

                                    <?php for ($y = $start; $y <= $end; $y++): ?>
                                        <th class="col-tahun"><?= $y ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $jenisLabel = static function ($v) {
                                    $v = strtolower(trim((string) $v));
                                    if ($v === 'positif') return 'Positif';
                                    if ($v === 'negatif') return 'Negatif';
                                    return $v !== '' ? ucfirst($v) : '';
                                };
                                $no = 1;
                                ?>
                                <?php foreach ($renstra_data as $tujuan): ?>
                                    <?php
                                    $tujuanId = $tujuan['tujuan_renstra_id'] ?? null;

                                    // Flatten sasaran x indikator
                                    $flatSas = [];
                                    foreach ($tujuan['sasaran'] as $s) {
                                        foreach ($s['indikator'] as $is) {
                                            $flatSas[] = [
                                                'sasaran_id' => $s['sasaran_id'],
                                                'sasaran'    => $s['sasaran'],
                                                'status'     => $s['status'],
                                                'indikator'  => $is['indikator'],
                                                'satuan'     => $is['satuan'],
                                                'baseline'   => $is['baseline'] ?? '',
                                                'jenis'      => $is['jenis_indikator'] ?? '',
                                                'targets'    => $is['targets'],
                                            ];
                                        }
                                    }

                                    $itCount  = count($tujuan['indikator_tujuan']);
                                    $sasCount = count($flatSas);
                                    $totalRow = max($itCount, $sasCount, 1);

                                    // Rowspan per sasaran = jumlah indikatornya
                                    $sasRowspan = [];
                                    foreach ($flatSas as $fs) {
                                        $rid = $fs['sasaran_id'];
                                        $sasRowspan[$rid] = ($sasRowspan[$rid] ?? 0) + 1;
                                    }
                                    $sasPrinted = [];
                                    $rowPrinted = false;
                                    ?>

                                    <?php for ($i = 0; $i < $totalRow; $i++): ?>
                                        <tr>
                                            <?php if (!$rowPrinted): ?>
                                                <td rowspan="<?= $totalRow ?>" class="text-center align-middle"><?= $no++ ?></td>
                                                <?php /* Kolom "Sasaran RPJMD" di-hide (permintaan user) */ ?>
                                                <td rowspan="<?= $totalRow ?>" class="text-start"><?= esc($tujuan['tujuan']) ?></td>
                                            <?php endif; ?>

                                            <!-- ================= INDIKATOR TUJUAN ================= -->
                                            <?php if ($i < $itCount): ?>
                                                <?php $it = $tujuan['indikator_tujuan'][$i]; ?>
                                                <td class="text-start"><span class="ind-kode">IK</span><?= esc($it['indikator_tujuan']) ?></td>
                                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                                    <td class="col-tahun"><?= esc($it['targets'][$y] ?? '') ?></td>
                                                <?php endfor; ?>
                                            <?php else: ?>
                                                <td></td>
                                                <?php for ($y = $start; $y <= $end; $y++): ?><td></td><?php endfor; ?>
                                            <?php endif; ?>

                                            <!-- ================= SASARAN ================= -->
                                            <?php if ($i < $sasCount): ?>
                                                <?php
                                                $ss  = $flatSas[$i];
                                                $sid = $ss['sasaran_id'];
                                                $isFirstOfSasaran = !isset($sasPrinted[$sid]);
                                                if ($isFirstOfSasaran) {
                                                    $sasPrinted[$sid] = true;
                                                }
                                                $kondisiAkhir = $ss['targets'][$end] ?? '';
                                                ?>

                                                <?php if ($isFirstOfSasaran): ?>
                                                    <td rowspan="<?= $sasRowspan[$sid] ?>" class="text-start"><?= esc($ss['sasaran']) ?></td>
                                                <?php endif; ?>

                                                <td class="text-start"><span class="ind-kode">IK</span><?= esc($ss['indikator']) ?></td>
                                                <td><?= esc($ss['satuan']) ?></td>
                                                <td><?= esc($ss['baseline']) ?></td>
                                                <?php for ($y = $start; $y <= $end; $y++): ?>
                                                    <td class="col-tahun"><?= esc($ss['targets'][$y] ?? '') ?></td>
                                                <?php endfor; ?>
                                                <td><?= esc($kondisiAkhir) ?></td>
                                                <td><?= esc($jenisLabel($ss['jenis'])) ?></td>

                                                <?php if ($isFirstOfSasaran): ?>
                                                    <!-- STATUS (per sasaran) -->
                                                    <td rowspan="<?= $sasRowspan[$sid] ?>">
                                                        <?php
                                                        $sStatus = strtolower($ss['status'] ?? 'draft');
                                                        $sBadge  = $sStatus === 'selesai' ? 'bg-success' : 'bg-warning text-dark';
                                                        $sLabel  = $sStatus === 'selesai' ? 'Selesai' : 'Draft';
                                                        ?>
                                                        <span class="badge <?= $sBadge ?>"><?= $sLabel ?></span>
                                                    </td>
                                                    <!-- AKSI (per sasaran) -->
                                                    <td rowspan="<?= $sasRowspan[$sid] ?>">
                                                        <?php if (user_can('renstra.delete')): ?>
                                                        <a href="<?= base_url('adminopd/renstra/delete/' . esc($sid)) ?>"
                                                            onclick="return confirm('Yakin ingin menghapus sasaran ini?')"
                                                            class="btn btn-danger btn-sm mb-1" title="Hapus Sasaran">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        <?php if (user_can('renstra.update')): ?>
                                                        <button type="button" class="btn btn-info btn-sm change-status-btn mb-1"
                                                            data-id="<?= esc($sid) ?>" title="Ubah Status">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <?php if ($tujuanId): ?>
                                                            <a href="<?= base_url('adminopd/renstra/edit-tujuan/' . esc($tujuanId)) ?>"
                                                                class="btn btn-warning btn-sm" title="Edit Tujuan & Sasaran">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                            <?php else: ?>
                                                <!-- baris ekstra (indikator tujuan lebih banyak dari sasaran) -->
                                                <td></td><!-- Sasaran -->
                                                <td></td><!-- Indikator Sasaran -->
                                                <td></td><!-- Satuan -->
                                                <td></td><!-- Kondisi Awal -->
                                                <?php for ($y = $start; $y <= $end; $y++): ?><td></td><?php endfor; ?>
                                                <td></td><!-- Kondisi Akhir -->
                                                <td></td><!-- Jenis Indikator -->
                                                <td></td><!-- Status -->
                                                <td></td><!-- Aksi -->
                                            <?php endif; ?>

                                        </tr>
                                        <?php $rowPrinted = true; ?>
                                    <?php endfor; ?>

                                <?php endforeach; ?>
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
            const rpjmdSelect = document.getElementById('rpjmdFilter');
            const statusSelect = document.getElementById('statusFilter');

            const otherFilters = [rpjmdSelect, statusSelect];

            // Semua filter dropdown jadi Select2 (searchable)
            const hasSelect2 = window.jQuery && $.fn.select2;
            if (hasSelect2) {
                $('.select2-flt').select2({ width: '100%', theme: 'bootstrap-5', dropdownParent: $('body') });
            }

            function toggleFilters() {
                const hasPeriode = (periodeSelect?.value || '').trim() !== '';
                otherFilters.forEach(el => {
                    if (!el) return;
                    if (hasSelect2) {
                        $(el).prop('disabled', !hasPeriode);
                    } else {
                        el.disabled = !hasPeriode;
                    }
                });
            }

            toggleFilters();

            [periodeSelect, ...otherFilters].forEach(el => {
                if (!el) return;
                const handler = function () { toggleFilters(); form.submit(); };
                // Pakai jQuery .on agar perubahan dari Select2 (dipicu lewat jQuery) tertangkap;
                // addEventListener native TIDAK menerima event yang dipicu Select2.
                if (hasSelect2) { $(el).on('change', handler); }
                else { el.addEventListener('change', handler); }
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

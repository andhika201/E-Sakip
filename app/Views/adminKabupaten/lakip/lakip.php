<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'LAKIP') ?></title>

    <?= $this->include('adminKabupaten/templates/style.php'); ?>

    <style>
        /* kolom aksi biar gak melebar */
        th.col-aksi,
        td.col-aksi {
            width: 92px;
            min-width: 92px;
            white-space: nowrap;
        }

        /* wrapper tombol aksi */
        .aksi-wrap {
            display: flex;
            gap: 6px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        /* tombol icon kecil & rapi */
        .aksi-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            padding: 0;
            line-height: 1;
        }

        .aksi-btn i {
            font-size: 14px;
            pointer-events: none;
        }

        /* layar kecil: stack vertikal + lebih kecil */
        @media (max-width: 576px) {

            th.col-aksi,
            td.col-aksi {
                width: 70px;
                min-width: 70px;
            }

            .aksi-wrap {
                flex-direction: column;
            }

            .aksi-btn {
                width: 30px;
                height: 30px;
                border-radius: 9px;
            }

            .aksi-btn i {
                font-size: 13px;
            }
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="flex-fill p-4 mt-2">
        <div class="bg-white rounded shadow p-4">
            <h2 class="h3 fw-bold text-success text-center mb-4">
                LAPORAN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH DAERAH
            </h2>

            <!-- FILTER -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Mode</label>
                    <select id="mode_filter" class="form-select border-secondary" onchange="filterData()">
                        <option value="kabupaten" <?= ($mode === 'kabupaten') ? 'selected' : '' ?>>Mode Kabupaten (RPJMD)
                        </option>
                        <option value="opd" <?= ($mode === 'opd') ? 'selected' : '' ?>>Mode OPD (RENSTRA)</option>
                    </select>
                </div>

                <div class="col-md-5" id="opd_filter_wrap" style="<?= ($mode === 'opd') ? '' : 'display:none;' ?>">
                    <label class="form-label fw-semibold">OPD</label>
                    <select id="opd_filter" class="form-select border-secondary" onchange="filterData()">
                        <option value="">Semua OPD</option>
                        <?php foreach (($opdList ?? []) as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= (!empty($selectedOpdId) && (string) $selectedOpdId === (string) $o['id']) ? 'selected' : '' ?>>
                                <?= esc($o['nama_opd']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tahun</label>
                    <select id="tahun_filter" class="form-select border-secondary" onchange="filterData()">
                        <?php foreach (($availableYears ?? []) as $y): ?>
                            <option value="<?= esc($y) ?>" <?= ((string) ($filters['tahun'] ?? '') === (string) $y) ? 'selected' : '' ?>>
                                <?= esc($y) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="status_filter" class="form-select border-secondary" onchange="filterData()">
                        <option value="" <?= empty($filters['status']) ? 'selected' : '' ?>>Semua Status</option>
                        <option value="proses" <?= (($filters['status'] ?? '') === 'proses') ? 'selected' : '' ?>>Proses
                        </option>
                        <option value="siap" <?= (($filters['status'] ?? '') === 'siap') ? 'selected' : '' ?>>Siap</option>
                    </select>
                </div>
            </div>

            <?php
            if (!function_exists('hitungCapaianLakip')) {
                function hitungCapaianLakip($target, $realisasi, $jenisIndikator)
                {
                    $target = toFloatComma($target);
                    $realisasi = toFloatComma($realisasi);

                    if ($target === null || $target <= 0 || $realisasi === null) {
                        return null;
                    }

                    $jenis = strtolower(trim((string) $jenisIndikator));

                    // indikator positif (semakin besar semakin baik)
                    if ($jenis === 'indikator positif' || $jenis === 'positif') {
                        $hasil = ($realisasi / $target) * 100;
                    }
                    // indikator negatif (semakin kecil semakin baik)
                    elseif ($jenis === 'indikator negatif' || $jenis === 'negatif') {
                        $hasil = (($target - ($realisasi - $target)) / $target) * 100;
                    } else {
                        return null;
                    }

                    return max(0, min($hasil, 200)); // clamp 0–200%
                }

            }

            $rows = $rows ?? [];
            $lakipMap = $lakipMap ?? [];

            // rowspan counts
            $opdCounts = [];
            $sasCounts = [];

            if ($mode === 'opd') {
                foreach ($rows as $r) {
                    $opdKey = (string) ($r['opd_id'] ?? '');
                    $sasKey = $opdKey . '|' . (string) ($r['sasaran'] ?? '');
                    $opdCounts[$opdKey] = ($opdCounts[$opdKey] ?? 0) + 1;
                    $sasCounts[$sasKey] = ($sasCounts[$sasKey] ?? 0) + 1;
                }
            } else {
                foreach ($rows as $r) {
                    $sasKey = (string) ($r['sasaran'] ?? '');
                    $sasCounts[$sasKey] = ($sasCounts[$sasKey] ?? 0) + 1;
                }
            }

            $opdSeen = [];
            $sasSeen = [];
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center small align-middle">
                    <thead class="table-success">
                        <tr>
                            <th class="border p-2">NO</th>
                            <?php if ($mode === 'opd'): ?>
                                <th class="border p-2">OPD</th>
                            <?php endif; ?>
                            <th class="border p-2">SASARAN</th>
                            <th class="border p-2">INDIKATOR</th>
                            <th class="border p-2">SATUAN</th>
                            <th class="border p-2">JENIS INDIKATOR</th>
                            <th class="border p-2">TAHUN</th>
                            <th class="border p-2">TARGET</th>
                            <th class="border p-2">TARGET TAHUN SEBELUMNYA</th>
                            <th class="border p-2">CAPAIAN TAHUN SEBELUMNYA</th>
                            <th class="border p-2">CAPAIAN TAHUN INI</th>
                            <th class="border p-2">CAPAIAN (%)</th>
                            <th class="border p-2">STATUS</th>
                            <th class="border p-2 col-aksi">AKSI</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="<?= ($mode === 'opd') ? 14 : 13 ?>" class="text-muted">
                                    Data target belum tersedia untuk filter ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($rows as $r): ?>
                                <?php
                                $targetId = (int) ($r['target_id'] ?? 0);
                                $lakipItem = $lakipMap[$targetId] ?? null;

                                $jenis = $r['jenis_indikator'] ?? 'indikator positif';
                                $targetNow = $r['target_tahun_ini'] ?? null;

                                $realisasiNow = $lakipItem['capaian_tahun_ini'] ?? null;
                                $capaianPersen = hitungCapaianLakip($targetNow, $realisasiNow, $jenis);

                                $statusText = $lakipItem['status'] ?? null;
                                $badge = 'badge bg-secondary';
                                if ($statusText === 'siap')
                                    $badge = 'badge bg-success';
                                if ($statusText === 'proses')
                                    $badge = 'badge bg-warning text-dark';

                                // preserve query
                                $q = 'mode=' . urlencode($mode)
                                    . '&tahun=' . urlencode($filters['tahun'] ?? date('Y'))
                                    . '&status=' . urlencode($filters['status'] ?? '');
                                if ($mode === 'opd')
                                    $q .= '&opd_id=' . urlencode($selectedOpdId ?? '');

                                $tambahUrl = base_url('adminkab/lakip/tambah/' . ($r['target_id'] ?? 0)) . '?' . $q;

                                $editUrl = base_url('adminkab/lakip/edit/' . ($r['indikator_id'] ?? 0)) . '?' . $q;

                                $changeStatusUrl = '';
                                $nextStatus = '';
                                if (!empty($lakipItem['id'])) {
                                    $nextStatus = ($statusText === 'siap') ? 'proses' : 'siap';
                                    $changeStatusUrl = base_url('adminkab/lakip/status/' . $lakipItem['id'] . '/' . $nextStatus) . '?' . $q;
                                }

                                // rowspan keys
                                if ($mode === 'opd') {
                                    $opdKey = (string) ($r['opd_id'] ?? '');
                                    $sasKey = $opdKey . '|' . (string) ($r['sasaran'] ?? '');
                                } else {
                                    $sasKey = (string) ($r['sasaran'] ?? '');
                                }
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>

                                    <?php if ($mode === 'opd'): ?>
                                        <?php if (empty($opdSeen[$opdKey])):
                                            $opdSeen[$opdKey] = true; ?>
                                            <td rowspan="<?= $opdCounts[$opdKey] ?? 1 ?>" class="text-start align-middle">
                                                <?= esc($r['nama_opd'] ?? '-') ?>
                                            </td>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (empty($sasSeen[$sasKey])):
                                        $sasSeen[$sasKey] = true; ?>
                                        <td rowspan="<?= $sasCounts[$sasKey] ?? 1 ?>" class="text-start align-middle">
                                            <?= esc($r['sasaran'] ?? '-') ?>
                                        </td>
                                    <?php endif; ?>

                                    <td class="text-start"><?= esc($r['indikator_sasaran'] ?? '-') ?></td>
                                    <td><?= esc($r['satuan'] ?? '-') ?></td>
                                    <td><?= esc(ucwords(str_replace('indikator ', '', strtolower((string) $jenis)))) ?></td>
                                    <td><?= esc($r['tahun'] ?? '-') ?></td>
                                    <td><?= formatAngkaID(toFloatComma($targetNow), 2) ?></td>

                                    <td><?= esc($lakipItem['target_lalu'] ?? '-') ?></td>
                                    <td><?= esc($lakipItem['capaian_lalu'] ?? '-') ?></td>
                                    <td><?= formatAngkaID(toFloatComma($lakipItem['capaian_tahun_ini'] ?? null), 2) ?></td>

                                    <td>
                                        <?= ($capaianPersen === null)
                                            ? '-'
                                            : formatAngkaID($capaianPersen, 2) . '%' ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($statusText)): ?>
                                            <span class="<?= $badge ?>"><?= esc(ucfirst($statusText)) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="col-aksi">
                                        <div class="aksi-wrap">
                                            <?php if (empty($lakipItem['id'])): ?>
                                                <a class="btn btn-primary aksi-btn" href="<?= $tambahUrl ?>" title="Tambah LAKIP">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                            <?php else: ?>
                                                <a class="btn btn-warning aksi-btn" href="<?= $editUrl ?>" title="Edit LAKIP">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (!empty($changeStatusUrl)): ?>
                                                    <a class="btn btn-info aksi-btn" href="<?= $changeStatusUrl ?>"
                                                        title="Ubah status ke <?= esc($nextStatus) ?>">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

    <script>
        function filterData() {
            const params = new URLSearchParams();

            const mode = document.getElementById('mode_filter')?.value || 'kabupaten';
            const opd = document.getElementById('opd_filter')?.value || '';
            const thn = document.getElementById('tahun_filter')?.value || '';
            const sts = document.getElementById('status_filter')?.value || '';

            params.set('mode', mode);
            if (mode === 'opd') params.set('opd_id', opd);
            if (thn) params.set('tahun', thn);
            if (sts) params.set('status', sts);

            window.location.href = '?' + params.toString();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const modeEl = document.getElementById('mode_filter');
            const wrap = document.getElementById('opd_filter_wrap');
            if (modeEl && wrap) {
                const toggle = () => wrap.style.display = (modeEl.value === 'opd') ? '' : 'none';
                modeEl.addEventListener('change', toggle);
                toggle();
            }
        });
    </script>
</body>

</html>
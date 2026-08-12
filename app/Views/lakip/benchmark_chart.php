<?php
/**
 * CHART PERBANDINGAN — Kabupaten/OPD vs Provinsi Lampung vs Nasional.
 *
 * Ditempatkan di antara tabel capaian LAKIP dan Analisis Faktor:
 *   Tabel Capaian LAKIP -> Chart Perbandingan -> Analisis Faktor -> Efisiensi
 *
 * Satu chart dinamis untuk seluruh indikator (bukan satu chart per indikator);
 * indikator dipilih lewat dropdown searchable di atas chart.
 *
 * Yang dibandingkan adalah NILAI INDIKATOR (mis. 3,52 vs 3,68 vs 3,71),
 * bukan persentase capaian.
 *
 * Variabel dari LakipBenchmarkTrait::lakipBenchmarkData():
 *   $benchmarkList          daftar indikator + nilai daerah/provinsi/nasional
 *   $benchmarkCanManage     boleh input/ubah benchmark
 *   $benchmarkPerluPilihOpd mode OPD tapi belum memilih OPD
 *   $benchmarkSiap          tabel lakip_benchmark sudah ada
 *   $addendumScope          ['mode','tahun','opdScope',...]
 *   $addendumBase           prefix url ('adminkab/lakip' | 'adminopd/lakip')
 */

$benchmarkList          = $benchmarkList ?? [];
$benchmarkCanManage     = (bool) ($benchmarkCanManage ?? false);
$benchmarkPerluPilihOpd = (bool) ($benchmarkPerluPilihOpd ?? false);
$benchmarkSiap          = (bool) ($benchmarkSiap ?? false);

$bmScope = $addendumScope ?? ['mode' => 'opd', 'tahun' => '', 'opdScope' => null];
$bmTahun = (string) ($bmScope['tahun'] ?? '');
$bmMode  = (string) ($bmScope['mode'] ?? 'opd');
$bmOpd   = (int) ($bmScope['opdScope'] ?? 0);
$bmBase  = $addendumBase ?? 'adminopd/lakip';

$bmLabelDaerah = $bmMode === 'kabupaten' ? 'Kabupaten Pringsewu' : 'OPD Terpilih';
?>

<!-- ============================================================
     CARD — PERBANDINGAN CAPAIAN: KABUPATEN/OPD vs PROVINSI vs NASIONAL
     ============================================================ -->
<div class="bg-white rounded shadow p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="h5 fw-bold text-success mb-1">PERBANDINGAN CAPAIAN: PROVINSI &amp; NASIONAL</h3>
            <p class="text-muted small mb-0">
                Tahun <?= esc($bmTahun !== '' ? $bmTahun : '-') ?>.
                Membandingkan <strong>nilai indikator</strong> (bukan persentase capaian)
                dengan Provinsi Lampung dan Nasional.
            </p>
        </div>
        <?php if ($benchmarkCanManage && !$benchmarkPerluPilihOpd && $benchmarkSiap): ?>
            <button type="button" class="btn btn-success btn-sm" id="bm-btn-isi" disabled>
                <i class="fas fa-pen-to-square me-1"></i> Isi / Ubah Pembanding
            </button>
        <?php endif; ?>
    </div>

    <?php if (!$benchmarkSiap): ?>
        <div class="alert alert-warning mb-0">
            <i class="fas fa-triangle-exclamation me-1"></i>
            Tabel <code>lakip_benchmark</code> belum tersedia. Jalankan
            <code>db/update_2026-08-12_lakip_benchmark.sql</code> terlebih dahulu.
        </div>

    <?php elseif ($benchmarkPerluPilihOpd): ?>
        <?php // Indikator antar-OPD berbeda-beda, jadi TIDAK diagregasi. ?>
        <div class="alert alert-info mb-0">
            <i class="fas fa-circle-info me-1"></i>
            Pilih <strong>satu OPD</strong> pada filter di atas untuk menampilkan grafik perbandingan.
            Indikator tiap OPD berbeda sehingga tidak dapat digabungkan menjadi satu grafik.
        </div>

    <?php elseif (empty($benchmarkList)): ?>
        <div class="alert alert-secondary mb-0">
            <i class="fas fa-circle-info me-1"></i>
            Belum ada indikator pada tahun <?= esc($bmTahun !== '' ? $bmTahun : '-') ?> untuk dibandingkan.
        </div>

    <?php else: ?>
        <div class="row g-3 align-items-end mb-3">
            <div class="col-lg-8">
                <label class="form-label small fw-semibold mb-1" for="bm-indikator">Indikator</label>
                <select class="form-select" id="bm-indikator">
                    <?php foreach ($benchmarkList as $i => $b): ?>
                        <option value="<?= (int) $b['indikator_id'] ?>" <?= $i === 0 ? 'selected' : '' ?>>
                            <?= esc($b['nama']) ?><?= $b['satuan'] !== '' ? ' (' . esc($b['satuan']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Grafik mengikuti filter LAKIP di atas (tahun, mode, OPD). Pemilih ini hanya menentukan indikator.
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-2 small bg-light" id="bm-meta">
                    <div class="text-muted">Satuan: <span id="bm-satuan">-</span></div>
                    <div class="text-muted">Tahun: <strong><?= esc($bmTahun !== '' ? $bmTahun : '-') ?></strong></div>
                    <div class="text-muted">Diperbarui: <span id="bm-updated">-</span></div>
                </div>
            </div>
        </div>

        <div id="bm-kosong" class="alert alert-secondary d-none">
            <i class="fas fa-circle-info me-1"></i>
            <strong>Data pembanding belum tersedia</strong> untuk indikator ini pada tahun
            <?= esc($bmTahun !== '' ? $bmTahun : '-') ?>.
            <?php if ($benchmarkCanManage): ?>
                Gunakan tombol <em>Isi / Ubah Pembanding</em> untuk menambahkan nilai Provinsi dan Nasional.
            <?php else: ?>
                Silakan hubungi Admin Kabupaten untuk melengkapinya.
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div style="position:relative; height:320px;">
                    <canvas id="bm-chart"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-2">
                        <thead class="table-light">
                            <tr>
                                <th style="width:55%">Wilayah</th>
                                <th class="text-end">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge" style="background:#00743e">&nbsp;</span> <?= esc($bmLabelDaerah) ?></td>
                                <td class="text-end fw-semibold" id="bm-val-daerah">-</td>
                            </tr>
                            <tr>
                                <td><span class="badge" style="background:#0d6efd">&nbsp;</span> Provinsi Lampung</td>
                                <td class="text-end fw-semibold" id="bm-val-provinsi">-</td>
                            </tr>
                            <tr>
                                <td><span class="badge" style="background:#6c757d">&nbsp;</span> Nasional</td>
                                <td class="text-end fw-semibold" id="bm-val-nasional">-</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="small text-muted">
                        <div class="mb-1"><strong>Sumber Provinsi:</strong> <span id="bm-src-prov">-</span></div>
                        <div class="mb-1"><strong>Sumber Nasional:</strong> <span id="bm-src-nas">-</span></div>
                        <div id="bm-catatan-wrap" class="d-none"><strong>Catatan:</strong> <span id="bm-catatan"></span></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($benchmarkCanManage): ?>
            <!-- Modal input benchmark -->
            <div class="modal fade" id="bm-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <form method="post" action="<?= base_url($bmBase . '/benchmark/save') ?>" class="modal-content">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tahun" value="<?= esc($bmTahun) ?>">
                        <input type="hidden" name="mode" value="<?= esc($bmMode) ?>">
                        <input type="hidden" name="opd_id" value="<?= $bmOpd ?: '' ?>">
                        <input type="hidden" name="indikator_id" id="bm-form-indikator">

                        <div class="modal-header">
                            <h5 class="modal-title">Pembanding Provinsi &amp; Nasional</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border small mb-3">
                                <div><strong>Indikator:</strong> <span id="bm-form-nama">-</span></div>
                                <div><strong>Tahun:</strong> <?= esc($bmTahun) ?> &nbsp;
                                    <strong>Satuan:</strong> <span id="bm-form-satuan">-</span></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="bm-in-prov">Nilai Provinsi Lampung</label>
                                    <input type="text" class="form-control" id="bm-in-prov" name="nilai_provinsi"
                                           inputmode="decimal" placeholder="mis. 3,68">
                                    <div class="form-text">Kosongkan bila belum ada data.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="bm-in-nas">Nilai Nasional</label>
                                    <input type="text" class="form-control" id="bm-in-nas" name="nilai_nasional"
                                           inputmode="decimal" placeholder="mis. 3,71">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="bm-in-srcprov">Sumber Provinsi</label>
                                    <input type="text" class="form-control" id="bm-in-srcprov" name="sumber_provinsi"
                                           maxlength="255" placeholder="mis. BPS Provinsi Lampung 2025">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="bm-in-srcnas">Sumber Nasional</label>
                                    <input type="text" class="form-control" id="bm-in-srcnas" name="sumber_nasional"
                                           maxlength="255" placeholder="mis. BPS RI 2025">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="bm-in-catatan">Catatan (opsional)</label>
                                    <textarea class="form-control" id="bm-in-catatan" name="catatan" rows="2" maxlength="5000"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <div id="bm-hapus-wrap"></div>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        (function () {
            const DATA = <?= json_encode($benchmarkList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const LABEL_DAERAH = <?= json_encode($bmLabelDaerah, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
            const byId = {};
            DATA.forEach(d => { byId[d.indikator_id] = d; });

            const sel     = document.getElementById('bm-indikator');
            const kosong  = document.getElementById('bm-kosong');
            const canvas  = document.getElementById('bm-chart');
            if (!sel || !canvas) { return; }

            const fmt = (v) => (v === null || v === undefined)
                ? '-'
                : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 4 });

            let chart = null;

            function render(id) {
                const d = byId[id];
                if (!d) { return; }

                document.getElementById('bm-satuan').textContent  = d.satuan || '-';
                document.getElementById('bm-updated').textContent = d.updated_at || '-';
                document.getElementById('bm-val-daerah').textContent   = fmt(d.nilai_daerah);
                document.getElementById('bm-val-provinsi').textContent = fmt(d.nilai_provinsi);
                document.getElementById('bm-val-nasional').textContent = fmt(d.nilai_nasional);
                document.getElementById('bm-src-prov').textContent = d.sumber_provinsi || '-';
                document.getElementById('bm-src-nas').textContent  = d.sumber_nasional || '-';

                const catWrap = document.getElementById('bm-catatan-wrap');
                if (d.catatan) {
                    document.getElementById('bm-catatan').textContent = d.catatan;
                    catWrap.classList.remove('d-none');
                } else {
                    catWrap.classList.add('d-none');
                }

                // Empty-state: benchmark belum ada -> JANGAN dianggap 0.
                kosong.classList.toggle('d-none', !!d.ada_benchmark);

                // Nilai null tidak diplot sebagai 0; Chart.js melewatinya.
                const nilai = [
                    d.nilai_daerah === null ? null : Number(d.nilai_daerah),
                    d.nilai_provinsi === null ? null : Number(d.nilai_provinsi),
                    d.nilai_nasional === null ? null : Number(d.nilai_nasional)
                ];

                const cfgData = {
                    labels: [LABEL_DAERAH, 'Provinsi Lampung', 'Nasional'],
                    datasets: [{
                        label: 'Nilai indikator' + (d.satuan ? ' (' + d.satuan + ')' : ''),
                        data: nilai,
                        backgroundColor: ['#00743e', '#0d6efd', '#6c757d'],
                        borderRadius: 4,
                        maxBarThickness: 90
                    }]
                };

                if (chart) {
                    chart.data = cfgData;
                    chart.options.plugins.title.text = d.nama;
                    chart.update();
                    return;
                }

                chart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: cfgData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            title: { display: true, text: d.nama, font: { size: 13 } },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => (ctx.parsed.y === null || ctx.parsed.y === undefined)
                                        ? 'Belum ada data'
                                        : fmt(ctx.parsed.y) + (d.satuan ? ' ' + d.satuan : '')
                                }
                            }
                        },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }

            sel.addEventListener('change', () => render(Number(sel.value)));
            render(Number(sel.value));

            // Dropdown searchable bila select2 tersedia (pola project existing).
            if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery(sel).select2({ width: '100%', placeholder: 'Cari indikator...' });
                jQuery(sel).on('change', () => render(Number(sel.value)));
            }

            const btnIsi = document.getElementById('bm-btn-isi');
            if (btnIsi) {
                btnIsi.disabled = false;
                btnIsi.addEventListener('click', () => {
                    const d = byId[Number(sel.value)];
                    if (!d) { return; }
                    document.getElementById('bm-form-indikator').value = d.indikator_id;
                    document.getElementById('bm-form-nama').textContent = d.nama;
                    document.getElementById('bm-form-satuan').textContent = d.satuan || '-';
                    document.getElementById('bm-in-prov').value    = d.nilai_provinsi ?? '';
                    document.getElementById('bm-in-nas').value     = d.nilai_nasional ?? '';
                    document.getElementById('bm-in-srcprov').value = d.sumber_provinsi || '';
                    document.getElementById('bm-in-srcnas').value  = d.sumber_nasional || '';
                    document.getElementById('bm-in-catatan').value = d.catatan || '';

                    // Tombol hapus hanya muncul kalau barisnya memang sudah ada.
                    const wrap = document.getElementById('bm-hapus-wrap');
                    wrap.innerHTML = '';
                    if (d.benchmark_id) {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'btn btn-outline-danger btn-sm';
                        a.innerHTML = '<i class="fas fa-trash me-1"></i>Hapus';
                        a.addEventListener('click', () => {
                            if (!confirm('Hapus data pembanding untuk indikator ini?')) { return; }
                            document.getElementById('bm-form-hapus-' + d.benchmark_id).submit();
                        });
                        wrap.appendChild(a);
                    }
                    new bootstrap.Modal(document.getElementById('bm-modal')).show();
                });
            }
        })();
        </script>

        <?php if ($benchmarkCanManage): ?>
            <?php // Form hapus terpisah agar tetap POST + CSRF, satu per baris benchmark. ?>
            <?php foreach ($benchmarkList as $b): ?>
                <?php if (!empty($b['benchmark_id'])): ?>
                    <form method="post" class="d-none"
                          id="bm-form-hapus-<?= (int) $b['benchmark_id'] ?>"
                          action="<?= base_url($bmBase . '/benchmark/delete/' . (int) $b['benchmark_id']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tahun" value="<?= esc($bmTahun) ?>">
                        <input type="hidden" name="mode" value="<?= esc($bmMode) ?>">
                        <input type="hidden" name="opd_id" value="<?= $bmOpd ?: '' ?>">
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

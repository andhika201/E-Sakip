<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Detail Pemetaan OPD' ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container { width: 100% !important; }
        .baris-anak { display: none; }
        .baris-anak.terbuka { display: table-row; }
        .tombol-lipat { cursor: pointer; user-select: none; }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <?php
        $rupiah = static fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
        $adaSelisih = ($preview['selisih_program'] > 0 || $preview['selisih_kegiatan'] > 0);
        ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1300px;">
                <h2 class="h4 fw-bold mb-1" style="color: #00743e;">Pemetaan OPD</h2>
                <p class="text-muted small mb-4">
                    Batch #<?= (int) $unit['batch_id'] ?> &middot; Tahun <?= esc($batch['tahun'] ?? '-') ?>
                    &middot; <?= esc($batch['jenis_anggaran'] ?? '-') ?>
                </p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Ringkasan unit -->
                <div class="border rounded p-3 mb-4 bg-light">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="small text-muted">Nama unit pada Excel</div>
                            <div class="fw-bold"><?= esc($unit['nama_unit']) ?></div>
                            <div class="small text-muted mt-1">Kode unit: <code><?= esc($unit['kode_unit']) ?></code></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row text-center">
                                <div class="col">
                                    <div class="small text-muted">Program</div>
                                    <div class="fw-bold"><?= (int) $unit['jumlah_program'] ?></div>
                                </div>
                                <div class="col">
                                    <div class="small text-muted">Kegiatan</div>
                                    <div class="fw-bold"><?= (int) $unit['jumlah_kegiatan'] ?></div>
                                </div>
                                <div class="col">
                                    <div class="small text-muted">Sub Kegiatan</div>
                                    <div class="fw-bold"><?= (int) $unit['jumlah_sub'] ?></div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="small text-muted">Total anggaran unit</div>
                                    <div class="fw-bold text-success"><?= $rupiah($preview['total_program']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($adaSelisih): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Rekonsiliasi anggaran:</strong>
                        <?= (int) $preview['selisih_program'] ?> Program berbeda dari total Kegiatannya dan
                        <?= (int) $preview['selisih_kegiatan'] ?> Kegiatan berbeda dari total Sub Kegiatannya.
                        Selisih ini <strong>tidak menggagalkan</strong> pemetaan — nilai yang disimpan tetap
                        mengikuti kolom anggaran pada Excel apa adanya.
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('adminkab/program_pk/mapping/unit/' . (int) $unit['id'] . '/save') ?>">
                    <?= csrf_field() ?>

                    <div class="border rounded p-3 mb-4">
                        <label class="form-label fw-semibold" for="opd_id">OPD tujuan</label>
                        <select name="opd_id" id="opd_id" class="form-select" required>
                            <option value="">-- Pilih OPD --</option>
                            <?php foreach ($opds as $o): ?>
                                <option value="<?= (int) $o['id'] ?>"
                                    <?= (!empty($unit['saran_opd_id']) && (int) $unit['saran_opd_id'] === (int) $o['id']) ? 'selected' : '' ?>>
                                    <?= esc($o['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($namaSaran)): ?>
                            <div class="form-text">
                                Saran sistem: <strong><?= esc($namaSaran) ?></strong>
                                (<?= (int) $unit['saran_skor'] ?>% mirip). Saran ini <em>tidak</em> diterapkan
                                otomatis — periksa dan konfirmasi sendiri.
                            </div>
                        <?php endif; ?>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="simpan_alias" name="simpan_alias" checked>
                            <label class="form-check-label" for="simpan_alias">
                                Gunakan mapping ini untuk import berikutnya
                                <div class="small text-muted">
                                    Menyimpan pasangan nama/kode unit &rarr; OPD, sehingga import berikutnya
                                    langsung mengenalinya tanpa perlu dipetakan lagi.
                                </div>
                            </label>
                        </div>

                        <div class="alert alert-success mt-3 mb-0 d-none" id="kotak-konfirmasi">
                            Seluruh data berikut akan dipetakan ke: <strong id="teks-opd"></strong><br>
                            <?= (int) $unit['jumlah_program'] ?> Program,
                            <?= (int) $unit['jumlah_kegiatan'] ?> Kegiatan,
                            <?= (int) $unit['jumlah_sub'] ?> Sub Kegiatan &middot;
                            total <?= $rupiah($preview['total_program']) ?>.
                        </div>
                    </div>

                    <!-- Pratinjau hierarki -->
                    <h3 class="h6 fw-bold mb-2">Pratinjau Hierarki</h3>
                    <p class="small text-muted">
                        Klik baris Program untuk melihat Kegiatan, lalu klik Kegiatan untuk melihat Sub Kegiatan.
                    </p>
                    <div class="table-responsive mb-4" style="max-height:460px; overflow-y:auto;">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Uraian</th>
                                    <th class="text-end" style="width:190px">Anggaran</th>
                                    <th class="text-end" style="width:190px">Total anak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preview['program'] as $pi => $p): ?>
                                    <tr class="tombol-lipat table-success" data-target="p-<?= $pi ?>">
                                        <td>
                                            <i class="fas fa-caret-right me-1"></i>
                                            <strong><?= esc($p['uraian']) ?></strong>
                                            <?php if (!$p['punya_baris']): ?>
                                                <span class="badge bg-secondary ms-1" title="Tidak ada barisnya di Excel; dibuat otomatis">auto</span>
                                            <?php endif; ?>
                                            <div class="small text-muted"><code><?= esc($p['kode']) ?></code></div>
                                        </td>
                                        <td class="text-end fw-semibold"><?= $rupiah($p['anggaran'] ?? 0) ?></td>
                                        <td class="text-end <?= abs($p['selisih']) >= 1 ? 'text-danger' : 'text-muted' ?>">
                                            <?= $rupiah($p['total_kegiatan']) ?>
                                            <?php if (abs($p['selisih']) >= 1): ?>
                                                <div class="small">selisih <?= $rupiah($p['selisih']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <?php foreach ($p['kegiatan'] as $ki => $k): ?>
                                        <tr class="baris-anak anak-p-<?= $pi ?> tombol-lipat" data-target="k-<?= $pi ?>-<?= $ki ?>">
                                            <td class="ps-4">
                                                <i class="fas fa-caret-right me-1"></i>
                                                <?= esc($k['uraian']) ?>
                                                <?php if (!$k['punya_baris']): ?>
                                                    <span class="badge bg-secondary ms-1">auto</span>
                                                <?php endif; ?>
                                                <div class="small text-muted"><code><?= esc($k['kode']) ?></code></div>
                                            </td>
                                            <td class="text-end"><?= $rupiah($k['anggaran'] ?? 0) ?></td>
                                            <td class="text-end <?= abs($k['selisih']) >= 1 ? 'text-danger' : 'text-muted' ?>">
                                                <?= $rupiah($k['total_sub']) ?>
                                                <?php if (abs($k['selisih']) >= 1): ?>
                                                    <div class="small">selisih <?= $rupiah($k['selisih']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <?php foreach ($k['sub'] as $s): ?>
                                            <tr class="baris-anak anak-k-<?= $pi ?>-<?= $ki ?>">
                                                <td class="ps-5 small">
                                                    <?= esc($s['uraian']) ?>
                                                    <div class="text-muted"><code><?= esc($s['kode']) ?></code></div>
                                                </td>
                                                <td class="text-end small"><?= $rupiah($s['anggaran'] ?? 0) ?></td>
                                                <td></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('adminkab/program_pk/mapping/' . (int) $unit['batch_id']) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-success" id="btn-simpan">
                            <i class="fas fa-check me-1"></i> Simpan Mapping &amp; Proses
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function () {
            // Lipat/buka hierarki. Menutup Program ikut menutup Sub di bawahnya.
            document.querySelectorAll('.tombol-lipat').forEach(function (baris) {
                baris.addEventListener('click', function () {
                    const target = baris.dataset.target;
                    const anak = document.querySelectorAll('.anak-' + target);
                    let buka = false;
                    anak.forEach(function (el) {
                        el.classList.toggle('terbuka');
                        buka = el.classList.contains('terbuka');
                    });
                    if (!buka && target.charAt(0) === 'p') {
                        document.querySelectorAll('[class*="anak-k-' + target.slice(2) + '-"]')
                            .forEach(el => el.classList.remove('terbuka'));
                    }
                    const ikon = baris.querySelector('.fa-caret-right, .fa-caret-down');
                    if (ikon) {
                        ikon.classList.toggle('fa-caret-right', !buka);
                        ikon.classList.toggle('fa-caret-down', buka);
                    }
                });
            });

            const sel = document.getElementById('opd_id');
            const kotak = document.getElementById('kotak-konfirmasi');
            const teks = document.getElementById('teks-opd');

            function perbarui() {
                if (sel.value) {
                    teks.textContent = sel.options[sel.selectedIndex].text.trim();
                    kotak.classList.remove('d-none');
                } else {
                    kotak.classList.add('d-none');
                }
            }
            sel.addEventListener('change', perbarui);

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(sel).select2({ width: '100%', placeholder: 'Cari OPD...' });
                jQuery(sel).on('change', perbarui);
            }
            perbarui();

            document.getElementById('btn-simpan').addEventListener('click', function (e) {
                if (!sel.value) {
                    alert('Pilih OPD tujuan terlebih dahulu.');
                    e.preventDefault();
                    return;
                }
                const nama = sel.options[sel.selectedIndex].text.trim();
                if (!confirm('Seluruh data unit ini akan dipetakan ke: ' + nama + '. Lanjutkan?')) {
                    e.preventDefault();
                }
            });
        })();
    </script>
</body>

</html>

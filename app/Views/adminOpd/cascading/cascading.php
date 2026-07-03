<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'CASCADING') ?></title>

    <?= $this->include('adminOpd/templates/style.php'); ?>
    <?= $this->include('adminOpd/cascading/_pohon_opd_styles'); ?>

    <?php if (function_exists('csrf_token')): ?>
        <meta name="csrf-token" content="<?= csrf_token() ?>">
        <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?php endif; ?>

    <style>
        /* ===================== Polish layar cascading OPD ===================== */
        .casc-head {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e8ece9;
        }
        .casc-head .casc-icon {
            flex: 0 0 auto;
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: linear-gradient(135deg, #0a8f50 0%, #00743e 100%);
            color: #fff;
            font-size: 23px;
            box-shadow: 0 8px 18px rgba(0, 116, 62, .28);
        }
        .casc-head h2 { margin: 0; font-weight: 800; font-size: 1.35rem; color: #16321f; letter-spacing: .2px; }
        .casc-head p { margin: 3px 0 0; color: #6b7a70; font-size: .85rem; }

        .casc-toolbar {
            background: #f6f9f7;
            border: 1px solid #e6ece8;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 22px;
        }
        .casc-toolbar .tb-label {
            font-size: .72rem; font-weight: 700; letter-spacing: .5px;
            text-transform: uppercase; color: #5d8a3f; margin-bottom: 8px;
        }

        .casc-viewbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap; margin-bottom: 18px;
        }
        .casc-viewtoggle {
            display: inline-flex; background: #eef2ef; border: 1px solid #e0e7e2;
            border-radius: 12px; padding: 4px; gap: 4px;
        }
        .casc-viewtoggle .vt-btn {
            border: 0; background: transparent; color: #5d6b62; font-weight: 600;
            font-size: .85rem; padding: 8px 16px; border-radius: 9px; cursor: pointer; transition: all .15s ease;
        }
        .casc-viewtoggle .vt-btn:hover { color: #00743e; }
        .casc-viewtoggle .vt-btn.active { background: #fff; color: #00743e; box-shadow: 0 2px 6px rgba(0, 0, 0, .08); }
        .casc-viewtools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .casc-act { width: 36px; height: 36px; display: inline-grid; place-items: center; border-radius: 10px; padding: 0; }

        .casc-table-wrap {
            border: 1px solid #e3e8e4; border-radius: 14px; overflow: hidden;
            box-shadow: 0 6px 20px rgba(16, 40, 24, .06);
        }
        .casc-table { margin: 0; font-size: .8rem; }
        .casc-table > :not(caption) > * > * { padding: .55rem .55rem; }
        .casc-table thead th {
            background: linear-gradient(180deg, #00824a 0%, #00743e 100%);
            color: #fff; font-weight: 600; vertical-align: middle; text-align: center;
            font-size: .68rem; letter-spacing: .3px; text-transform: uppercase;
            border-color: rgba(255, 255, 255, .18);
        }
        .casc-table tbody td { vertical-align: middle; color: #344039; border-color: #e8ede9; line-height: 1.4; }
        .casc-table tbody tr:hover td { background: #f1f8f3; }

        .csf-input {
            border: 1px solid #dbe5de; border-radius: 8px; background: #fffdf6;
            resize: none; transition: box-shadow .15s ease, border-color .15s ease;
        }
        .csf-input:focus { border-color: #6eab11; background: #fff; box-shadow: 0 0 0 .18rem rgba(110, 171, 17, .18); }

        .casc-empty {
            text-align: center; padding: 52px 24px; border-radius: 16px;
            border: 1px dashed #cfd8d2; background: #f8faf9; color: #5d6b62;
        }
        .casc-empty .ce-icon { font-size: 42px; margin-bottom: 14px; color: #00743e; opacity: .35; }
        .casc-empty h5 { font-weight: 700; color: #3a4a40; margin-bottom: 6px; }
        .casc-empty p { font-size: .9rem; margin: 0; }
    </style>
</head>

<body data-no-paginate class="bg-light min-vh-100 d-flex flex-column position-relative">

    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">
        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">

                <?php
                // Tampilan dipisah per menu: 'tabel' (Cascading) atau 'pohon' (Pohon Kinerja).
                $view    = in_array(($view ?? 'tabel'), ['tabel', 'pohon'], true) ? $view : 'tabel';
                $isPohon = ($view === 'pohon');
                ?>
                <!-- HEADER -->
                <div class="casc-head">
                    <div class="casc-icon"><i class="fas fa-<?= $isPohon ? 'sitemap' : 'table-cells' ?>"></i></div>
                    <div>
                        <h2><?= $isPohon ? 'Pohon Kinerja' : 'Cascading' ?></h2>
                        <p><?= $isPohon
                            ? 'Visualisasi pohon kinerja Renstra &rarr; Eselon II / III / IV Perangkat Daerah'
                            : 'Matriks cascading Renstra &rarr; Eselon II / III / IV Perangkat Daerah' ?></p>
                    </div>
                </div>

                <!-- FLASH MESSAGE -->
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
                $filters = $filters ?? ['periode' => ''];
                ?>

                <!-- ====================== FILTER ====================== -->
                <div class="casc-toolbar">
                    <div class="tb-label"><i class="fas fa-filter me-1"></i>Filter Periode RPJMD</div>
                    <form method="GET" action="<?= base_url('adminopd/cascading') ?>"
                        class="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center">
                        <input type="hidden" name="view" value="<?= esc($view) ?>">
                        <select name="periode" class="form-select" style="flex:1;">
                            <option value="">-- Pilih Periode --</option>
                            <?php foreach ($periode_master ?? [] as $p): ?>
                                <?php $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir']; ?>
                                <option value="<?= $key ?>" <?= ($filters['periode'] == $key) ? 'selected' : '' ?>>
                                    <?= $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-success text-nowrap">
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <a href="<?= base_url('adminopd/cascading?view=' . $view) ?>" class="btn btn-outline-secondary text-nowrap">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- ====================== DATA ====================== -->
                <?php if (empty($filters['periode'])): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-calendar-days"></i></div>
                        <h5>Pilih Periode Terlebih Dahulu</h5>
                        <p>Silakan pilih periode RPJMD pada filter di atas untuk menampilkan Cascading &amp; Pohon Kinerja.</p>
                    </div>

                <?php elseif (!empty($opd_missing ?? false)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-circle-exclamation"></i></div>
                        <h5>Akun Tidak Terikat Perangkat Daerah</h5>
                        <p>Cascading OPD hanya tersedia untuk akun <strong>Admin OPD</strong>. Silakan masuk sebagai Admin OPD untuk melihat datanya.</p>
                    </div>

                <?php elseif (empty($rows)): ?>

                    <div class="casc-empty">
                        <div class="ce-icon"><i class="fas fa-folder-open"></i></div>
                        <h5>Belum Ada Data RENSTRA</h5>
                        <p>Belum ada data Renstra untuk Perangkat Daerah ini pada periode terpilih.</p>
                    </div>

                <?php else: ?>

                    <!-- TOOLS TAMPILAN (dipisah per menu: Cascading / Pohon Kinerja) -->
                    <div class="casc-viewbar" style="justify-content:flex-end;">
                        <!-- Tools tab Tabel Cascading -->
                        <div class="casc-viewtools" id="tabelTools" <?= $isPohon ? 'hidden' : '' ?>>
                            <a href="<?= base_url('adminopd/cascading/cetak?periode=' . $filters['periode']) ?>"
                                target="_blank" class="btn btn-sm btn-danger text-nowrap">
                                <i class="fas fa-file-pdf me-1"></i> Cetak Cascading
                            </a>
                            <a href="<?= base_url('adminopd/cascading/excel?periode=' . $filters['periode']) ?>"
                                class="btn btn-sm btn-success text-nowrap">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </a>
                        </div>
                        <!-- Tools tab Pohon Kinerja -->
                        <div class="casc-viewtools" id="pohonTools" <?= $isPohon ? '' : 'hidden' ?>>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(-1)" title="Perkecil">
                                <i class="fas fa-magnifying-glass-minus"></i>
                            </button>
                            <span id="pohonZoomLbl" class="small text-muted" style="min-width:42px;text-align:center;">60%</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary casc-act" onclick="pohonZoom(1)" title="Perbesar">
                                <i class="fas fa-magnifying-glass-plus"></i>
                            </button>
                            <a href="<?= base_url('adminopd/cascading/cetakpohon?periode=' . $filters['periode']) ?>"
                                target="_blank" class="btn btn-sm btn-success text-nowrap">
                                <i class="fas fa-print me-1"></i> Cetak Pohon
                            </a>
                        </div>
                    </div>

                    <!-- ============== VIEW: TABEL ============== -->
                    <div id="view-tabel" <?= $isPohon ? 'hidden' : '' ?>>
                        <div class="casc-table-wrap">
                            <div class="table-responsive" id="cascTableWrap"
                                data-table-url="<?= base_url('adminopd/cascading/table') ?>"
                                data-periode="<?= esc($filters['periode'] ?? '', 'attr') ?>">
                                <?= $this->include('adminOpd/cascading/_table') ?>
                            </div>
                        </div>
                    </div>
                    <?php if (false): // Tabel lama dinonaktifkan — render kini via partial _table di atas. ?>
                    <div class="d-none">
                                <table class="table table-bordered text-center align-middle casc-table mb-0">
                                    <thead class="text-center">
                                        <tr>
                                            <th>Tujuan RPJMD</th>
                                            <th>Sasaran RPJMD</th>
                                            <th>Tujuan RENSTRA</th>

                                            <th>Sasaran ESS II</th>
                                            <th>Indikator ESS II</th>

                                            <th>Sasaran ESS III</th>
                                            <th>Indikator ESS III</th>
                                            <th width="90">Aksi ESS III</th>

                                            <th>Sasaran ESS IV / JF</th>
                                            <th>Indikator ESS IV</th>

                                            <th width="90">Aksi ESS IV</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                        // Es3 yang MASIH punya Es4 -> tombol Hapus Es3 disembunyikan
                                        // (user harus menghapus Es4 di bawahnya lebih dulu).
                                        $es3WithEs4 = [];
                                        foreach ($rows as $__r) {
                                            if (!empty($__r['es3_id']) && !empty($__r['es4_id'])) {
                                                $es3WithEs4[$__r['es3_id']] = true;
                                            }
                                        }
                                        ?>
                                        <?php foreach ($rows as $index => $r): ?>
                                            <tr>
                                                <?php if ($firstShow['tujuan'][$r['tujuan_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['tujuan'][$r['tujuan_id']] ?>" class="text-start">
                                                        <?= esc($r['tujuan_rpjmd']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstShow['sasaran'][$r['sasaran_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['sasaran'][$r['sasaran_id']] ?>" class="text-start">
                                                        <?= esc($r['sasaran_rpjmd']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstShow['tujuan_renstra'][$r['renstra_tujuan_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['tujuan_renstra'][$r['renstra_tujuan_id']] ?>" class="text-start">
                                                        <?= esc($r['renstra_tujuan']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if ($firstShow['sasaran_renstra'][$r['renstra_sasaran_id']] == $index): ?>
                                                    <td rowspan="<?= $rowspan['sasaran_renstra'][$r['renstra_sasaran_id']] ?>" class="text-start">
                                                        <?= esc($r['renstra_sasaran']) ?>
                                                    </td>
                                                <?php endif; ?>

                                                <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                                    <td rowspan="<?= $rowspan['indikator'][$r['indikator_id']] ?? 1 ?>" class="text-start">
                                                        <?php if (!empty($r['indikator_sasaran'])): ?>
                                                            <span class="ind-kode">IK</span><?= esc($r['indikator_sasaran']) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <?php if (empty($r['es3_id'])): ?>
                                                    <?php if (($firstShow['indikator'][$r['indikator_id']] ?? null) == $index): ?>
                                                        <td colspan="6" class="text-center">
                                                            <a href="<?= base_url('adminopd/cascading/tambah-es3/' . $r['indikator_id']) ?>"
                                                                class="btn btn-success btn-sm">
                                                                <i class="fas fa-plus"></i> Tambah ESS III
                                                            </a>
                                                        </td>

                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                                                        <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-start">
                                                            <?= esc($r['es3_sasaran']) ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null); ?>
                                                    <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                        <td rowspan="<?= $rowspan['es3_indikator'][$key] ?? 1 ?>" class="text-start">
                                                            <?php if (!empty($r['es3_indikator'])): ?>
                                                                <span class="ind-kode">IK</span><?= esc($r['es3_indikator']) ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <?php // AKSI ESS III: satu sel per Es3 (rowspan penuh), muncul di baris pertama Es3. ?>
                                                    <?php if (($firstShow['es3'][$r['es3_id']] ?? null) == $index): ?>
                                                        <td rowspan="<?= $rowspan['es3'][$r['es3_id']] ?? 1 ?>" class="text-nowrap text-center">
                                                            <a href="<?= base_url('adminopd/cascading/edit-es3/' . $r['es3_id']) ?>"
                                                                class="btn btn-warning btn-sm casc-act" title="Edit ESS III">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php // Hapus Es3 hanya bila TIDAK ada Es4 di bawahnya (hapus Es4 dulu). ?>
                                                            <?php if (empty($es3WithEs4[$r['es3_id']])): ?>
                                                                <a href="<?= base_url('adminopd/cascading/delete-es3/' . $r['es3_id']) ?>"
                                                                    onclick="return confirm('Hapus Sasaran Eselon III ini beserta seluruh indikatornya?');"
                                                                    class="btn btn-danger btn-sm casc-act" title="Hapus ESS III">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php // Blok Es4 (Sasaran/Indikator/Aksi ES IV) HANYA utk baris yang sudah punya Es3.
                                                      // Saat Es3 kosong, sel "Tambah ESS III" (colspan=6) sudah menutup seluruh blok kanan. ?>
                                                <?php if (!empty($r['es3_id'])): ?>
                                                    <?php if (empty($r['es4_id'])): ?>
                                                        <?php if (($firstShow['es3_indikator'][$key] ?? null) == $index): ?>
                                                            <td colspan="2" class="text-center">
                                                                <a href="<?= base_url('adminopd/cascading/tambah-es4/' . $r['es3_indikator_id']) ?>"
                                                                    class="btn btn-success btn-sm">
                                                                    <i class="fas fa-plus"></i>
                                                                </a>
                                                            </td>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if (($firstShow['es4'][$r['es4_id']] ?? null) == $index): ?>
                                                            <!-- Sasaran ES IV -->
                                                            <td rowspan="<?= $rowspan['es4'][$r['es4_id']] ?? 1 ?>" class="text-start">
                                                                <?= esc($r['es4_sasaran']) ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td class="text-start">
                                                            <?php if (!empty($r['es4_indikator'])): ?>
                                                                <span class="ind-kode">IK</span><?= esc($r['es4_indikator']) ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>

                                                    <td class="text-nowrap">
                                                        <?php if (!empty($r['es4_id'])): ?>
                                                            <a href="<?= base_url('adminopd/cascading/edit-es4/' . $r['es4_id']) ?>"
                                                                class="btn btn-warning btn-sm casc-act" title="Edit ESS IV">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="<?= base_url('adminopd/cascading/delete-es4/' . $r['es4_id']) ?>"
                                                                onclick="return confirm('Hapus Sasaran Eselon IV ini beserta seluruh indikatornya?');"
                                                                class="btn btn-danger btn-sm casc-act" title="Hapus ESS IV">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; // akhir tabel lama yang dinonaktifkan ?>

                    <!-- ============== VIEW: POHON KINERJA ============== -->
                    <div id="view-pohon" <?= $isPohon ? '' : 'hidden' ?>>
                        <?php if (empty($tree ?? [])): ?>
                            <div class="casc-empty">
                                <div class="ce-icon"><i class="fas fa-diagram-project"></i></div>
                                <h5>Pohon Kinerja Belum Tersedia</h5>
                                <p>Belum ada data cascading Renstra/Eselon untuk periode ini.</p>
                            </div>
                        <?php else: ?>
                            <?= $this->include('adminOpd/cascading/_pohon_opd_tree') ?>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </div>
        </main>
        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <!-- AJAX Script for CSF Input -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csfInputs = document.querySelectorAll('.csf-input');
            let timeout = null;

            csfInputs.forEach(input => {
                input.addEventListener('input', function () {
                    const id = this.getAttribute('data-id');
                    const level = this.getAttribute('data-level');
                    const value = this.value;

                    this.style.backgroundColor = '#fff3cd';

                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('csf', value);
                        formData.append('level', level);

                        const csrfToken = document.querySelector('meta[name="csrf-hash"]');
                        if (csrfToken) {
                            formData.append('csrf_test_name', csrfToken.content);
                        }

                        fetch('<?= base_url('adminopd/cascading/savecsf') ?>', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    this.style.backgroundColor = '#d1e7dd';
                                    setTimeout(() => { this.style.backgroundColor = ''; }, 1000);
                                }
                            })
                            .catch(error => {
                                console.error('Error saving CSF:', error);
                                this.style.backgroundColor = '#f8d7da';
                            });
                    }, 500);
                });
            });
        });
    </script>

    <!-- Toggle Tabel / Pohon Kinerja + Zoom -->
    <script>
        (function () {
            const btns = document.querySelectorAll('.vt-btn');
            const vTabel = document.getElementById('view-tabel');
            const vPohon = document.getElementById('view-pohon');
            const toolsPohon = document.getElementById('pohonTools');
            const toolsTabel = document.getElementById('tabelTools');

            btns.forEach(b => b.addEventListener('click', () => {
                btns.forEach(x => x.classList.remove('active'));
                b.classList.add('active');
                const v = b.dataset.view;
                if (vTabel) vTabel.hidden = (v !== 'tabel');
                if (vPohon) vPohon.hidden = (v !== 'pohon');
                if (toolsTabel) toolsTabel.hidden = (v !== 'tabel');
                if (toolsPohon) toolsPohon.hidden = (v !== 'pohon');
                if (v === 'pohon') pohonZoom(0); // terapkan skala setelah pohon tampil (offset valid)
            }));
        })();

        let _pohonZoom = 0.60;
        function pohonZoom(dir) {
            _pohonZoom = Math.min(1.2, Math.max(0.3, _pohonZoom + dir * 0.1));
            const t = document.getElementById('tree-container');
            if (t) {
                // Pakai transform:scale (BUKAN zoom) agar tak muncul kotak hitam
                // (bug render Chromium: zoom + gradient + box-shadow pada banyak node).
                t.style.zoom = '';
                t.style.transformOrigin = 'top left';
                t.style.transform = 'scale(' + _pohonZoom + ')';
                // Transform tak mengubah layout box -> kompensasi agar tak ada ruang kosong.
                const natW = t.offsetWidth, natH = t.offsetHeight;
                t.style.marginRight  = (natW * (_pohonZoom - 1)) + 'px';
                t.style.marginBottom = (natH * (_pohonZoom - 1)) + 'px';
            }
            const lbl = document.getElementById('pohonZoomLbl');
            if (lbl) lbl.textContent = Math.round(_pohonZoom * 100) + '%';
        }
        document.addEventListener('DOMContentLoaded', () => pohonZoom(0));
    </script>

    <!-- Modal Edit Cascading (Es3/Es4) — diisi via AJAX -->
    <div class="modal fade" id="cascEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cascEditTitle">Edit Cascading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="cascEditBody">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin me-1"></i> Memuat…
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notifikasi -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1090;">
        <div id="cascToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="cascToastBody">Berhasil</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
        </div>
    </div>

    <!-- Helper form edit (fungsi global dipakai form di dalam modal) -->
    <script src="<?= base_url('assets/js/adminOpd/cascading/cascading-es3-edit.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminOpd/cascading/cascading-es4.js') ?>"></script>
    <!-- Orkestrasi AJAX: buka modal, submit, delete, refresh tabel tanpa reload -->
    <script src="<?= base_url('assets/js/adminOpd/cascading/cascading-ajax.js') ?>"></script>
</body>

</html>

<?php
helper('capaian');

$isBupati = ($jenis === 'bupati');
$isEdit   = ($mode === 'edit');

// Satuan bertipe predikat (mis. Opini BPK) -> Target Triwulan dipilih dari
// skala, bukan diketik bebas. Lihat app/Models/SatuanModel.php.
$skala      = $skala ?? [];
$isPredikat = $skala !== [];
$eselonLabel = function ($pkJenis, $jabatanEselon = null, $jabatanNama = null) {
    $map = ['bupati' => 'Bupati', 'jpt' => 'Eselon II', 'camat' => 'Eselon III', 'administrator' => 'Eselon III', 'pengawas' => 'Eselon IV'];
    $pkJenis = strtolower(trim((string) $pkJenis));
    if ($pkJenis !== '' && isset($map[$pkJenis])) {
        return $map[$pkJenis];
    }

    $formatNamaEselon = static function ($value) {
        $value = trim((string) $value);
        if ($value === '' || ctype_digit($value)) {
            return null;
        }
        if (preg_match('/^eselon\s+/i', $value)) {
            return $value;
        }
        return 'Eselon ' . $value;
    };

    $label = $formatNamaEselon($jabatanEselon);
    if ($label !== null) {
        return $label;
    }

    $jabatanText = strtolower(trim(preg_replace('/\s+/', ' ', (string) $jabatanNama)));
    if ($jabatanText !== '') {
        if (strpos($jabatanText, 'kepala sub') === 0) {
            return 'Eselon IV';
        }
        if (strpos($jabatanText, 'kepala bidang') === 0) {
            return 'Eselon III';
        }
        if ($jabatanText === 'sekretaris' || strpos($jabatanText, 'sekretaris dinas') === 0 || strpos($jabatanText, 'sekretaris badan') === 0) {
            return 'Eselon III';
        }
        if (in_array($jabatanText, ['inspektur', 'inspektur kabupaten', 'inspektur daerah', 'inspektur kabupaten pringsewu'], true) || strpos($jabatanText, 'kepala dinas') === 0 || strpos($jabatanText, 'kepala bagian') === 0) {
            return 'Eselon II';
        }
    }

    return '-';
};
$ctxEselon = $eselonLabel($ctx['pk_jenis'] ?? '', $ctx['pejabat_eselon'] ?? null, $ctx['pejabat_jabatan'] ?? '');
$judul    = ($isEdit ? 'Edit' : 'Tambah') . ' Rencana Aksi';
$renaksiPath = ($jenis === 'bupati') ? 'adminkab/target_renaksi'
             : (($base === 'adminopd') ? 'adminopd/target_renaksi' : ($base . '/renaksi_pk/' . $jenis));
$baseUrl  = base_url($renaksiPath);
$action   = $isEdit
    ? $baseUrl . '/update/' . (int) ($detail['id'] ?? 0)
    : $baseUrl . '/save';

// Nilai prefill (edit pakai $detail, tambah pakai old())
$val = function (string $k) use ($isEdit, $detail, $ctx) {
    if ($isEdit) {
        $default = $detail[$k] ?? '';
        if ($k === 'penanggung_jawab' && $default === '') {
            $default = $ctx['pejabat_jabatan'] ?? '';
        }
        return old($k, $default);
    }

    $default = ($k === 'penanggung_jawab') ? ($ctx['pejabat_jabatan'] ?? '') : '';
    return old($k, $default);
};
$tahun  = $ctx['tahun'] ?? ($ctx['indikator_tahun'] ?? '-');

// format_helper tidak ikut autoload (lihat Config\Autoload::$helpers), jadi
// formatRupiah() dipanggil lewat pembungkus yang punya cadangan sendiri.
$rupiah = function ($nilai) {
    if (function_exists('formatRupiah')) {
        return formatRupiah($nilai);
    }
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($judul) ?> - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">
    <?= $this->include($isBupati ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php'); ?>
    <?= $this->include($isBupati ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php'); ?>

    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width:100%; max-width:900px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color:#00743e;"><?= esc($judul) ?></h2>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-3"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= $action ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <?php if (!$isEdit): ?>
                    <input type="hidden" name="pk_indikator_id" value="<?= (int) ($ctx['pk_indikator_id'] ?? 0) ?>">
                <?php endif; ?>

                <?php if (!$isBupati): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label">Eselon</label>
                            <input type="text" class="form-control" value="<?= esc($ctxEselon) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Pejabat (PK)</label>
                            <?php
                            $pejabatPk = trim((string) ($ctx['pejabat_nama'] ?? ''));
                            if (!empty($ctx['pejabat_jabatan'])) {
                                $pejabatPk .= ' (' . $ctx['pejabat_jabatan'] . ')';
                            }
                            ?>
                            <input type="text" class="form-control" value="<?= esc($pejabatPk !== '' ? $pejabatPk : '-') ?>" readonly>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Sasaran</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['sasaran_renstra'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label class="form-label">Indikator PK</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['indikator_sasaran'] ?? '-') ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['satuan'] ?? '-') ?>" readonly>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Tahun PK</label>
                        <input type="text" class="form-control" value="<?= esc($tahun) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Target</label>
                        <input type="text" class="form-control" value="<?= esc($ctx['indikator_target'] ?? '-') ?>" readonly>
                    </div>
                </div>

                <?php // Program & anggaran ikut Perjanjian Kinerja indikator ini — hanya ditampilkan. ?>
                <div class="mb-3">
                    <label class="form-label">Program &amp; Anggaran <span class="text-muted fw-normal">(dari Perjanjian Kinerja)</span></label>
                    <?php if (empty($programPk ?? [])): ?>
                        <div class="alert alert-light border mb-0 py-2 px-3 text-muted small">
                            Indikator PK ini belum ditautkan ke program mana pun. Atur lewat menu Program Perjanjian Kinerja.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-1">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:90px;">Kode</th>
                                        <th>Program</th>
                                        <th class="text-end" style="width:170px;">Anggaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $totalAnggaran = 0; ?>
                                    <?php foreach ($programPk as $prog): ?>
                                        <?php $totalAnggaran += (float) $prog['anggaran']; ?>
                                        <tr>
                                            <td><?= esc($prog['kode'] ?? '-') ?></td>
                                            <td class="text-start"><?= esc($prog['program']) ?></td>
                                            <td class="text-end text-nowrap"><?= esc($rupiah($prog['anggaran'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if (count($programPk) > 1): ?>
                                    <tfoot>
                                        <tr class="fw-semibold">
                                            <td colspan="2" class="text-end">Total</td>
                                            <td class="text-end text-nowrap"><?= esc($rupiah($totalAnggaran)) ?></td>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <small class="text-muted">Diambil otomatis dari PK — ubah datanya di menu Program Perjanjian Kinerja.</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Aksi &amp; Sub Rencana Aksi</label>
                    <?php if ($isPredikat): ?>
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="fas fa-list-ol me-1"></i>
                            Satuan <strong><?= esc($ctx['satuan'] ?? '-') ?></strong> memakai <strong>skala predikat</strong>,
                            jadi Target Triwulan dipilih dari daftar:
                            <?php foreach ($skala as $s): ?>
                                <span class="badge bg-white text-dark border ms-1"><?= esc($s['kode']) ?> = <?= esc(rtrim(rtrim(number_format((float) $s['nilai'], 2, '.', ''), '0'), '.')) ?></span>
                            <?php endforeach; ?>
                            <div class="text-muted mt-1">Ubah skalanya di <em>Master Data &rarr; Satuan</em>.</div>
                        </div>
                    <?php endif; ?>
                    <div id="renaksi-list"></div>
                    <button type="button" id="add-renaksi" class="btn btn-outline-success btn-sm mt-1">
                        <i class="fas fa-plus me-1"></i> Tambah Rencana Aksi
                    </button>
                    <small class="text-muted d-block mt-1">
                        Tiap rencana aksi bisa dirinci lagi jadi beberapa sub rencana aksi; di tabel akan tampil
                        sebagai daftar 1, 2, 3 … dengan sub-nya masing-masing.
                    </small>
                    <textarea name="rencana_aksi" id="rencana_aksi_joined" class="d-none" required></textarea>
                    <input type="hidden" name="sub_rencana_json" id="sub_rencana_json" value="">
                </div>

                <?php // Target triwulan tingkat indikator DIHAPUS dari form: targetnya kini
                      // diisi per Sub Rencana Aksi di atas, dan MONEV membacanya dari sana. ?>

                <div class="mb-3">
                    <label class="form-label" for="penanggung_jawab">
                        Penanggung Jawab <?= $isBupati ? '(Perangkat Daerah)' : '' ?>
                    </label>
                    <?php if ($isBupati): ?>
                        <?php $pjVal = (string) $val('penanggung_jawab'); ?>
                        <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" required>
                            <option value="">&mdash; Pilih Perangkat Daerah &mdash;</option>
                            <?php foreach (($opdList ?? []) as $opd): ?>
                                <option value="<?= esc($opd['nama_opd']) ?>" <?= ($pjVal === $opd['nama_opd']) ? 'selected' : '' ?>>
                                    <?= esc($opd['nama_opd']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php // fallback: data lama (teks jabatan) yang belum cocok OPD, tetap ditampilkan agar tak hilang ?>
                            <?php if ($pjVal !== '' && !in_array($pjVal, array_column($opdList ?? [], 'nama_opd'), true)): ?>
                                <option value="<?= esc($pjVal) ?>" selected><?= esc($pjVal) ?> (data lama)</option>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Pilih <strong>Perangkat Daerah</strong> penanggung jawab rencana aksi ini.</small>
                    <?php else: ?>
                        <input type="text" class="form-control" id="penanggung_jawab" name="penanggung_jawab"
                            value="<?= esc($val('penanggung_jawab')) ?>" placeholder="Isi nama jabatan (mis. Kepala Bidang ...)">
                        <small class="text-muted">Diisi dengan <strong>nama jabatan</strong> penanggung jawab.</small>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $baseUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        (function () {
            var initial = <?= json_encode((string) $val('rencana_aksi')) ?>;
            // Skala predikat satuan indikator ini; kosong = target diketik bebas.
            var SKALA = <?= json_encode(array_map(static fn ($s) => [
                'kode'  => $s['kode'],
                'label' => $s['label'] ?? '',
            ], $skala), JSON_UNESCAPED_UNICODE) ?>;
            // Sub rencana aksi tersimpan: { "<indeks butir>": [ {id, teks}, ... ] }
            var initialSub = <?= json_encode($subRencana ?? [], JSON_UNESCAPED_UNICODE) ?>;
            var oldSub = <?= json_encode((string) (old('sub_rencana_json') ?? '')) ?>;
            if (oldSub) {
                try { initialSub = JSON.parse(oldSub); } catch (err) { /* pakai data tersimpan */ }
            }

            var list = document.getElementById('renaksi-list');
            var joined = document.getElementById('rencana_aksi_joined');
            var subJson = document.getElementById('sub_rencana_json');
            if (!list || !joined || !subJson) return;

            function esc(s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

            /** Satu baris sub: teksnya + target triwulan I-IV milik sub itu sendiri. */
            function subRowHtml(sub) {
                sub = sub || {};
                var teks = sub.teks || '';
                var tw = sub.tw || [];
                var id = sub.id ? String(sub.id) : '';
                var twHtml = [0, 1, 2, 3].map(function (i) {
                    var label = ['I', 'II', 'III', 'IV'][i];
                    var nilai = tw[i] || '';

                    // Satuan berpredikat -> dropdown skala. Nilai lama yang tidak
                    // ada di skala tetap ditawarkan agar tidak terhapus diam-diam.
                    if (SKALA.length) {
                        var dikenal = SKALA.some(function (s) {
                            return String(s.kode).toLowerCase() === String(nilai).trim().toLowerCase();
                        });
                        var opts = '<option value="">TW ' + label + '</option>'
                            + SKALA.map(function (s) {
                                var pilih = String(s.kode).toLowerCase() === String(nilai).trim().toLowerCase() ? ' selected' : '';
                                var teks = s.label ? (s.kode + ' — ' + s.label) : s.kode;
                                return '<option value="' + esc(s.kode) + '"' + pilih + '>' + esc(teks) + '</option>';
                            }).join('')
                            + (nilai !== '' && !dikenal
                                ? '<option value="' + esc(nilai) + '" selected>' + esc(nilai) + ' (di luar skala)</option>'
                                : '');
                        return '<div class="col">'
                            + '<select class="form-select form-select-sm sub-tw" data-q="' + i + '"'
                            + ' title="Target Triwulan ' + label + '">' + opts + '</select>'
                            + '</div>';
                    }

                    return '<div class="col">'
                        + '<input type="text" class="form-control form-control-sm sub-tw" data-q="' + i + '"'
                        + ' placeholder="TW ' + label + '" title="Target Triwulan ' + label + '"'
                        + ' value="' + esc(nilai) + '">'
                        + '</div>';
                }).join('');

                return '<div class="sub-item mb-2" data-id="' + esc(id) + '">'
                    + '<div class="input-group input-group-sm mb-1">'
                    + '<span class="input-group-text sub-no bg-white text-muted"></span>'
                    + '<input type="text" class="form-control sub-input" placeholder="Tulis sub rencana aksi" value="' + esc(teks) + '">'
                    + '<button type="button" class="btn btn-outline-danger remove-sub" title="Hapus sub"><i class="fas fa-times"></i></button>'
                    + '</div>'
                    + '<div class="row g-1 ps-4">' + twHtml + '</div>'
                    + '</div>';
            }

            function rowHtml(val, subs) {
                var subHtml = (subs && subs.length ? subs : []).map(subRowHtml).join('');
                return '<div class="renaksi-item border rounded p-2 mb-2 bg-light">'
                    + '<div class="input-group mb-2">'
                    + '<span class="input-group-text renaksi-no bg-white fw-semibold"></span>'
                    + '<input type="text" class="form-control renaksi-input" placeholder="Tulis rencana aksi" value="' + esc(val) + '">'
                    + '<button type="button" class="btn btn-outline-danger remove-renaksi" title="Hapus rencana aksi"><i class="fas fa-trash"></i></button>'
                    + '</div>'
                    + '<div class="ps-3 border-start">'
                    + '<div class="small text-muted mb-1">Sub Rencana Aksi</div>'
                    + '<div class="sub-list">' + subHtml + '</div>'
                    + '<button type="button" class="btn btn-outline-secondary btn-sm add-sub"><i class="fas fa-plus me-1"></i>Tambah Sub</button>'
                    + '</div>'
                    + '</div>';
            }

            /** Nomori ulang label 1,2,3 supaya cocok dengan tampilan tabel. */
            function renumber() {
                var n = 0;
                Array.prototype.forEach.call(list.querySelectorAll('.renaksi-item'), function (item) {
                    var label = item.querySelector('.renaksi-no');
                    if (label) label.textContent = (++n);
                    var s = 0;
                    Array.prototype.forEach.call(item.querySelectorAll('.sub-no'), function (el) {
                        el.textContent = (++s);
                    });
                });
            }

            /**
             * Butir kosong tidak ikut disimpan, jadi indeks sub HARUS dihitung dari
             * urutan butir yang TIDAK kosong — sama dengan cara tabel memecah baris.
             */
            function sync() {
                var vals = [];
                var map = {};
                var idx = 0;

                Array.prototype.forEach.call(list.querySelectorAll('.renaksi-item'), function (item) {
                    var inp = item.querySelector('.renaksi-input');
                    var v = inp ? inp.value.trim() : '';
                    if (v === '') return;

                    vals.push(v);

                    var subs = [];
                    Array.prototype.forEach.call(item.querySelectorAll('.sub-item'), function (si) {
                        var teksEl = si.querySelector('.sub-input');
                        var teks = teksEl ? teksEl.value.trim() : '';
                        if (teks === '') return;

                        var tw = ['', '', '', ''];
                        Array.prototype.forEach.call(si.querySelectorAll('.sub-tw'), function (t) {
                            var q = parseInt(t.getAttribute('data-q'), 10);
                            if (!isNaN(q) && q >= 0 && q < 4) tw[q] = t.value.trim();
                        });

                        // id ikut dikirim supaya sub yang sudah ada di DB diperbarui
                        // di tempat — capaian MONEV menempel ke id ini.
                        var id = parseInt(si.getAttribute('data-id'), 10);
                        subs.push({ id: isNaN(id) ? 0 : id, teks: teks, tw: tw });
                    });

                    if (subs.length) map[idx] = subs;
                    idx++;
                });

                joined.value = vals.join('\n');
                subJson.value = JSON.stringify(map);
            }

            function addRow(val, subs) {
                list.insertAdjacentHTML('beforeend', rowHtml(val, subs));
                renumber();
                sync();
            }

            // Inisialisasi baris dari nilai tersimpan (edit) atau 1 baris kosong (tambah)
            var lines = String(initial || '').split(/\r\n|\r|\n/).map(function (s) { return s.trim(); }).filter(function (s) { return s !== ''; });
            if (lines.length === 0) lines = [''];

            lines.forEach(function (line, i) {
                var raw = initialSub ? initialSub[i] : null;
                var subs = [];
                if (Array.isArray(raw)) {
                    subs = raw.map(function (s) {
                        if (s && typeof s === 'object') {
                            // Dari DB tw berindeks 1..4 (jadi objek saat di-JSON-kan);
                            // dari old() tw berupa array berindeks 0..3.
                            var t = s.tw || {};
                            var tw = Array.isArray(t)
                                ? [t[0] || '', t[1] || '', t[2] || '', t[3] || '']
                                : [t[1] || '', t[2] || '', t[3] || '', t[4] || ''];
                            return { id: s.id || 0, teks: s.teks || '', tw: tw };
                        }
                        return { id: 0, teks: String(s || ''), tw: ['', '', '', ''] };
                    }).filter(function (s) { return s.teks !== ''; });
                }
                addRow(line, subs);
            });

            document.getElementById('add-renaksi').addEventListener('click', function () { addRow('', []); });

            list.addEventListener('click', function (e) {
                if (e.target.closest('.add-sub')) {
                    var wrap = e.target.closest('.renaksi-item').querySelector('.sub-list');
                    if (wrap) wrap.insertAdjacentHTML('beforeend', subRowHtml({ id: 0, teks: '', tw: ['', '', '', ''] }));
                    renumber();
                    sync();
                    return;
                }

                if (e.target.closest('.remove-sub')) {
                    e.target.closest('.sub-item').remove();
                    renumber();
                    sync();
                    return;
                }

                if (e.target.closest('.remove-renaksi')) {
                    var items = list.querySelectorAll('.renaksi-item');
                    if (items.length > 1) {
                        e.target.closest('.renaksi-item').remove();
                    } else {
                        // sisa satu butir: kosongkan saja, jangan sampai form tanpa baris
                        var item = e.target.closest('.renaksi-item');
                        var inp = item.querySelector('.renaksi-input');
                        if (inp) inp.value = '';
                        var sl = item.querySelector('.sub-list');
                        if (sl) sl.innerHTML = '';
                    }
                    renumber();
                    sync();
                }
            });

            list.addEventListener('input', function (e) {
                var c = e.target.classList;
                if (c.contains('renaksi-input') || c.contains('sub-input') || c.contains('sub-tw')) sync();
            });

            var form = list.closest('form');
            if (form) form.addEventListener('submit', sync);
        })();
    </script>
</body>

</html>

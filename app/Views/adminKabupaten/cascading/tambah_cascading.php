<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Cascading</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .opd-group {
            margin-bottom: 20px;
        }

        .program-container {
            margin-top: 10px;
            padding-left: 15px;
            border-left: 3px solid #e9ecef;
        }

        .program-container select {
            margin-top: 6px;
        }
    </style>
</head>

<body class="bg-light">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="p-4">

        <div class="bg-white rounded shadow-sm p-4" style="max-width:800px;margin:auto">

            <?php $sumber = $sumber_isian ?? ($existing_mapping ? 'manual' : 'kosong'); ?>
            <h4 class="text-success mb-3"><?= $sumber === 'kosong' ? 'Tambah' : 'Edit' ?> Mapping Cascading</h4>

            <?php /* Penolakan simpan (program bukan milik OPD, galat basis data, ...)
                     dikembalikan ke halaman ini lewat redirect()->back(). Tanpa blok
                     ini pesannya hilang: form tampil lagi seperti tidak terjadi apa-apa.
                     Pesannya teks polos -> di-esc(). */ ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger py-2 px-3 mb-3"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success py-2 px-3 mb-3"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <?php if ($sumber === 'otomatis'): ?>
                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.9rem">
                    <i class="fas fa-wand-magic-sparkles me-1"></i>
                    Isian di bawah adalah <strong>penurunan otomatis</strong> yang sedang tampil di Cascading:
                    Perangkat Daerah dari rantai Renstra yang berjangkar ke sasaran ini, beserta seluruh program
                    PK JPT-nya. Sesuaikan lalu <strong>Simpan</strong> &mdash; yang tersimpan akan
                    <strong>menggantikan</strong> penurunan otomatis untuk indikator ini.
                </div>
            <?php elseif ($sumber === 'manual'): ?>
                <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.9rem">
                    <i class="fas fa-pen me-1"></i>
                    Indikator ini sudah punya <strong>mapping manual</strong>; penurunan otomatis tidak dipakai lagi.
                    Untuk kembali ke penurunan otomatis, tekan <em>Kembalikan ke otomatis</em> di bawah.
                </div>
            <?php endif; ?>

            <form action="<?= base_url('adminkab/cascading/save') ?>" method="post">
                <?= csrf_field() ?>

                <?php // indikator_id = id indikator IKU KABUPATEN (tulang punggung Cascading sejak 14 Sep 2026) ?>
                <input type="hidden" name="indikator_id" value="<?= (int) $indikator['id'] ?>">
                <input type="hidden" name="periode" value="<?= esc($periode ?? '') ?>">

                <?php if (!empty($indikator['sasaran'])): ?>
                    <div class="mb-3">
                        <label>Sasaran IKU Kabupaten</label>
                        <input type="text" class="form-control" value="<?= esc($indikator['sasaran']) ?>" readonly>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label>Indikator IKU Kabupaten</label>
                    <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>"
                        readonly>
                    <?php if (empty($indikator['rpjmd_indikator_id'])): ?>
                        <div class="form-text">Indikator ini lahir di IKU (tidak punya padanan RPJMD).</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label>Tahun</label>
                    <?php $currentYear = date('Y'); ?>

                    <select name="tahun" id="tahun" class="form-select">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= ($selected_tahun == $y) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="mb-3">
                    <label>OPD & Program Pendukung</label>

                    <div id="opd-container"></div>

                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addOpdGroup()">

                        + Tambah OPD

                    </button>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <a href="<?= base_url('adminkab/cascading?periode=' . $periode) ?>"
                        class="btn btn-secondary">Kembali</a>
                    <?php if ($sumber === 'manual'): ?>
                        <?php /* Tombol milik form TERPISAH di bawah (form="..."): <form> di
                                 dalam <form> tidak sah dan dibuang peramban. */ ?>
                        <button type="submit" form="form-hapus-mapping" class="btn btn-outline-danger float-end"
                                title="Buang mapping manual; indikator kembali memakai penurunan otomatis">
                            <i class="fas fa-rotate-left me-1"></i>Kembalikan ke otomatis
                        </button>
                    <?php endif; ?>
                </div>

            </form>

            <?php if ($sumber === 'manual'): ?>
                <form id="form-hapus-mapping" method="post" action="<?= base_url('adminkab/cascading/hapus-mapping') ?>"
                      data-konfirmasi="Mapping manual indikator ini pada tahun terpilih dibuang. Cascading kembali menampilkan penurunan otomatis dari Renstra & PK."
                      data-konfirmasi-judul="Kembalikan ke Penurunan Otomatis"
                      data-konfirmasi-jenis="peringatan"
                      data-konfirmasi-nama="<?= esc($indikator['indikator_sasaran'], 'attr') ?>"
                      data-konfirmasi-ya="Ya, Kembalikan">
                    <?= csrf_field() ?>
                    <input type="hidden" name="indikator_id" value="<?= (int) $indikator['id'] ?>">
                    <input type="hidden" name="periode" value="<?= esc($periode ?? '') ?>">
                    <input type="hidden" name="tahun" value="<?= (int) $selected_tahun ?>" id="tahun-hapus">
                </form>
            <?php endif; ?>

        </div>

    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>


    <script>
        const BASE_URL = "<?= rtrim(base_url(), '/') ?>"; // tanpa "/" di ekor: template di bawah menambahkannya sendiri
        const EXISTING_MAPPING = <?= json_encode($existing_mapping ?? []) ?>;
        // Daftar program per OPD untuk mapping yang sudah terpilih, disematkan
        // server: halaman terbuka langsung utuh tanpa satu pun fetch.
        const PROGRAM_AWAL = <?= json_encode((object) ($program_awal ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const OPD_OPTIONS = <?= json_encode(array_map(static fn ($o) => ['id' => (int) $o['id'], 'nama' => $o['nama_opd']], $opd_list), JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <script>
        /* =====================================================================
           ALUR HALAMAN
           - Buka halaman : kartu OPD + program dirakit dari PROGRAM_AWAL, sekali
                            jadi, tanpa fetch. (Dulu: N fetch berurutan, kartu
                            muncul satu-satu.)
           - Ganti OPD    : kartu itu saja mengambil daftar programnya; selama
                            menunggu tampil "Memuat daftar program…" dan tombol
                            "+ Tambah Program" dinonaktifkan.
           - Ganti tahun  : semua kartu mengambil ulang, PARALEL (Promise.all).
           - Daftar program per (opd, tahun) di-cache; membuka OPD yang sama dua
             kali tidak memanggil server lagi.
           ===================================================================== */
        var cacheProgram = {};   // "opd:tahun" => [ {id, program_kegiatan}, ... ]
        var opdIndex = 0;

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function tahunTerpilih() {
            return document.getElementById('tahun').value;
        }

        function opsiOpd(terpilih) {
            var html = '<option value="">-- Pilih OPD --</option>';
            OPD_OPTIONS.forEach(function (o) {
                html += '<option value="' + o.id + '"' + (String(o.id) === String(terpilih) ? ' selected' : '') + '>' + esc(o.nama) + '</option>';
            });
            return html;
        }

        function opsiProgram(daftar, terpilih) {
            var html = '<option value="">-- Pilih Program --</option>';
            (daftar || []).forEach(function (p) {
                html += '<option value="' + esc(p.id) + '"' + (String(p.id) === String(terpilih) ? ' selected' : '') + '>' + esc(p.program_kegiatan) + '</option>';
            });
            return html;
        }

        function barisProgram(idx, daftar, terpilih) {
            return '<div class="input-group mb-2 program-item">'
                + '<select name="opd[' + idx + '][program][]" class="form-select" required>' + opsiProgram(daftar, terpilih) + '</select>'
                + '<button type="button" class="btn btn-danger remove-program" title="Hapus program ini">-</button>'
                + '</div>';
        }

        /** Satu kartu OPD lengkap (select OPD + baris-baris program). */
        function kartuOpd(idx, opdId, daftar, programTerpilih) {
            var isi = '';
            if (programTerpilih && programTerpilih.length) {
                isi = '<label class="form-label mt-2 program-label">Program</label>';
                programTerpilih.forEach(function (pid) { isi += barisProgram(idx, daftar, pid); });
            }
            return '<div class="card mb-3 opd-group shadow-sm">'
                + '<div class="card-body">'
                + '<div class="row mb-2">'
                + '<div class="col-md-10"><label>OPD</label>'
                + '<select name="opd[' + idx + '][id]" class="form-select opd-select" data-index="' + idx + '" required>' + opsiOpd(opdId) + '</select>'
                + '</div>'
                + '<div class="col-md-2 d-flex align-items-end">'
                + '<button type="button" class="btn btn-danger w-100 remove-opd" title="Hapus OPD ini dari mapping">-</button>'
                + '</div></div>'
                + '<div class="program-container mb-2">' + isi + '</div>'
                + '<div class="status-muat small text-muted mb-2" hidden><i class="fas fa-spinner fa-spin me-1"></i>Memuat daftar program&hellip;</div>'
                + '<button type="button" class="btn btn-sm btn-outline-success add-program" data-index="' + idx + '">+ Tambah Program</button>'
                + '</div></div>';
        }

        function setDaftar(group, daftar) {
            group.dataset.programList = JSON.stringify(daftar || []);
        }

        function daftarGroup(group) {
            try { return JSON.parse(group.dataset.programList || '[]'); } catch (e) { return []; }
        }

        function setMemuat(group, memuat) {
            var st = group.querySelector('.status-muat');
            var btn = group.querySelector('.add-program');
            if (st) st.hidden = !memuat;
            if (btn) btn.disabled = !!memuat;
            group.querySelectorAll('.program-item select').forEach(function (s) { s.disabled = !!memuat; });
        }

        /** Ambil daftar program (opd, tahun) — dari cache bila ada. */
        function ambilProgram(opdId, tahun) {
            var kunci = opdId + ':' + tahun;
            if (cacheProgram[kunci]) return Promise.resolve(cacheProgram[kunci]);
            return fetch(BASE_URL + '/adminkab/cascading/get-pk-program-by-opd?opd_id=' + encodeURIComponent(opdId) + '&tahun=' + encodeURIComponent(tahun))
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (d) { cacheProgram[kunci] = Array.isArray(d) ? d : []; return cacheProgram[kunci]; })
                .catch(function () { return []; });
        }

        /**
         * Muat ulang daftar program sebuah kartu. Baris program yang sudah ada
         * DIPERTAHANKAN bila programnya masih ada di daftar baru; yang tidak
         * ada lagi dibuang (mis. ganti tahun ke tahun yang PK-nya berbeda).
         */
        function muatUlangGroup(group) {
            var sel = group.querySelector('.opd-select');
            var opdId = sel.value, tahun = tahunTerpilih();
            var container = group.querySelector('.program-container');

            if (!opdId || !tahun) {
                setDaftar(group, []);
                container.innerHTML = '';
                return Promise.resolve();
            }

            setMemuat(group, true);
            return ambilProgram(opdId, tahun).then(function (daftar) {
                setDaftar(group, daftar);
                var ada = {};
                daftar.forEach(function (p) { ada[String(p.id)] = true; });
                var terpilih = [];
                container.querySelectorAll('.program-item select').forEach(function (s) {
                    if (s.value && ada[s.value]) terpilih.push(s.value);
                });
                var idx = sel.dataset.index;
                var html = '';
                if (terpilih.length) {
                    html = '<label class="form-label mt-2 program-label">Program</label>';
                    terpilih.forEach(function (pid) { html += barisProgram(idx, daftar, pid); });
                }
                container.innerHTML = html;
            }).finally(function () { setMemuat(group, false); });
        }

        window.addOpdGroup = function (opdId, daftar, programTerpilih) {
            var wadah = document.getElementById('opd-container');
            var idx = opdIndex++;
            wadah.insertAdjacentHTML('beforeend', kartuOpd(idx, opdId || '', daftar || [], programTerpilih || []));
            var group = wadah.lastElementChild;
            setDaftar(group, daftar || []);
            return group;
        };

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('opd-select')) {
                var group = e.target.closest('.opd-group');
                group.querySelector('.program-container').innerHTML = '';
                muatUlangGroup(group);
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-program')) {
                var item = e.target.closest('.program-item');
                var cont = item.parentElement;
                item.remove();
                if (!cont.querySelector('.program-item')) cont.innerHTML = '';
                return;
            }
            if (e.target.classList.contains('remove-opd')) {
                e.target.closest('.opd-group').remove();
                return;
            }
            if (e.target.classList.contains('add-program')) {
                var group = e.target.closest('.opd-group');
                var sel = group.querySelector('.opd-select');
                if (!sel.value) { alert('Pilih OPD terlebih dahulu.'); return; }
                if (!tahunTerpilih()) { alert('Pilih Tahun terlebih dahulu!'); return; }
                var daftar = daftarGroup(group);
                if (!daftar.length) {
                    alert('Tidak ada Program Kegiatan untuk OPD ini di tahun ' + tahunTerpilih() + '.');
                    return;
                }
                var container = group.querySelector('.program-container');
                if (!container.querySelector('.program-label')) {
                    container.insertAdjacentHTML('afterbegin', '<label class="form-label mt-2 program-label">Program</label>');
                }
                container.insertAdjacentHTML('beforeend', barisProgram(sel.dataset.index, daftar, ''));
            }
        });

        document.getElementById('tahun').addEventListener('change', function () {
            var th = document.getElementById('tahun-hapus');
            if (th) th.value = this.value;
            // Semua kartu sekaligus, bukan bergiliran.
            Promise.all(Array.prototype.map.call(document.querySelectorAll('.opd-group'), muatUlangGroup));
        });

        // Buka halaman: rakit dari data yang disematkan — tanpa fetch, sekali jadi.
        (function () {
            var ada = false;
            Object.keys(EXISTING_MAPPING || {}).forEach(function (opdId) {
                var daftar = PROGRAM_AWAL[opdId] || [];
                cacheProgram[opdId + ':' + tahunTerpilih()] = daftar;
                addOpdGroup(opdId, daftar, (EXISTING_MAPPING[opdId] || []).map(String));
                ada = true;
            });
            if (!ada) addOpdGroup('', [], []);
        })();
    </script>
    </div>
</body>

</html>
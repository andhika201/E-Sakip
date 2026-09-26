/**
 * Pemilik Kinerja (sampai Pelaksana) — interaksi halaman adminopd/pemilik-kinerja.
 *
 * Satu sumber templat: chip pemilik, nilai indikator, status simpul, dan
 * angka cakupan SELALU digambar dari state D (JSON #pmk-data) — baik saat
 * halaman dimuat maupun sesudah setiap simpan — supaya tampilan tidak pernah
 * berselisih dengan data yang baru disimpan.
 *
 * Semua mutasi: fetch POST JSON + header X-CSRF-TOKEN (token tetap per sesi,
 * regenerate=false). Hapus/lepas pemilik lewat Konfirmasi.hapus().
 * Dimuat dengan `defer`, jadi jQuery/Bootstrap/Select2 dari footer sudah ada.
 */
(function () {
    'use strict';

    var akar = document.getElementById('pmk-akar');
    var dataEl = document.getElementById('pmk-data');
    if (!akar || !dataEl) { return; }

    var D = JSON.parse(dataEl.textContent || '{}');
    var M = D.meta || {};
    var LEVEL = ['es3', 'es4', 'pelaksana'];
    D.simpul = D.simpul && !Array.isArray(D.simpul) ? D.simpul : {};
    D.indikator = D.indikator && !Array.isArray(D.indikator) ? D.indikator : {};
    D.jumlahPeran = D.jumlahPeran && !Array.isArray(D.jumlahPeran) ? D.jumlahPeran : {};
    D.usulan = Array.isArray(D.usulan) ? D.usulan : [];
    D.ikp = Array.isArray(D.ikp) ? D.ikp : [];

    var modeUsulan = false;
    var saringBelum = false;
    var kataCari = '';

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function $$(sel, el) { return Array.prototype.slice.call((el || document).querySelectorAll(sel)); }
    function tokenCsrf() {
        var m = document.querySelector('meta[name="csrf-hash"]');
        return m ? m.getAttribute('content') : '';
    }
    function perbaruiCsrf(h) {
        if (!h) { return; }
        $$('meta[name="csrf-hash"]').forEach(function (m) { m.setAttribute('content', h); });
    }
    function persen(a, b) { return b > 0 ? Math.round(a * 100 / b) : 0; }
    function normal(s) { return String(s || '').toLowerCase(); }

    var wadahToast = akar.querySelector('.pmk-toast-wadah');
    function toast(pesan, jenis) {
        if (!wadahToast) { return; }
        var t = document.createElement('div');
        t.className = 'pmk-toast' + (jenis ? ' ' + jenis : '');
        t.setAttribute('role', jenis === 'galat' ? 'alert' : 'status');
        var ikon = jenis === 'galat' ? 'fa-circle-exclamation' : (jenis === 'peringatan' ? 'fa-triangle-exclamation' : 'fa-circle-check');
        t.innerHTML = '<i class="fas ' + ikon + ' mt-1"></i><span>' + esc(pesan) + '</span>';
        wadahToast.appendChild(t);
        setTimeout(function () { t.remove(); }, jenis === 'galat' ? 7000 : 3500);
    }

    /**
     * POST JSON. Menolak (reject) dengan pesan yang layak tampil bila server
     * menolak — termasuk bila modperm mengalihkan ke /unauthorized (balasan
     * HTML, bukan JSON).
     */
    function kirim(url, payload) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenCsrf()
            },
            body: JSON.stringify(payload || {})
        }).then(function (res) {
            var jenis = res.headers.get('content-type') || '';
            if (jenis.indexOf('application/json') === -1) {
                if (res.redirected && /unauthorized/.test(res.url)) {
                    throw new Error('Anda tidak memiliki izin untuk aksi ini.');
                }
                if (res.redirected && /login/.test(res.url)) {
                    throw new Error('Sesi Anda berakhir. Silakan masuk kembali.');
                }
                throw new Error('Server menolak permintaan (' + res.status + '). Muat ulang halaman lalu coba lagi.');
            }
            return res.json().then(function (j) {
                perbaruiCsrf(j && j.csrfHash);
                if (!res.ok || !j || j.status !== 'success') {
                    throw new Error((j && j.message) || 'Permintaan gagal diproses.');
                }
                return j;
            });
        });
    }

    // ------------------------------------------------------------------
    // Status & templat
    // ------------------------------------------------------------------
    function indLengkap(ind) {
        return !!ind && String(ind.satuan || '').trim() !== ''
            && (ind.target !== null && ind.target !== undefined || String(ind.target_teks || '').trim() !== '');
    }
    function statusSimpul(id) {
        var s = D.simpul[id];
        var inds = (s.indikator || []).map(function (i) { return D.indikator[i]; });
        var kurang = inds.filter(function (i) { return !indLengkap(i); }).length;
        var punya = (s.pemilik || []).length > 0;
        return { punya: punya, jmlInd: inds.length, kurang: kurang, lengkap: punya && inds.length > 0 && kurang === 0 };
    }
    function adaPj(id) {
        return (D.simpul[id].pemilik || []).some(function (p) { return p.peran === 'penanggung_jawab'; });
    }

    function htmlChip(p) {
        var pj = p.peran === 'penanggung_jawab';
        var jab = [p.jabatan, p.opd_lain ? 'pegawai ' + p.opd_lain : ''].filter(Boolean).join(' · ');
        var h = '<span class="pmk-chip' + (pj ? ' pmk-chip-pj' : '') + '" data-pemilik-id="' + p.id + '" data-pegawai-id="' + p.pegawai_id + '"'
            + ' title="' + esc(p.nama + (jab ? ' — ' + jab : '') + (p.sumber === 'pk' ? ' (dari usulan PK)' : '')) + '">'
            + '<span class="pmk-chip-teks"><span class="pmk-chip-nama">' + (p.is_plt ? 'Plt. ' : '') + esc(p.nama) + '</span>'
            + (jab ? '<span class="pmk-chip-jab">' + esc(jab) + '</span>' : '') + '</span>';
        if (M.bolehUbah) {
            h += '<button type="button" class="pmk-chip-peran' + (pj ? '' : ' anggota') + '" data-aksi="ganti-peran"'
                + ' title="' + (pj ? 'Penanggung jawab — klik untuk menjadikan anggota' : 'Anggota — klik untuk menjadikan penanggung jawab') + '">'
                + (pj ? 'PJ' : 'Anggota') + '</button>'
                + (M.bolehHapus
                    ? '<button type="button" class="pmk-chip-x" data-aksi="hapus-pemilik" aria-label="Lepas ' + esc(p.nama) + ' dari simpul ini" title="Lepas pemilik">'
                        + '<i class="fas fa-xmark"></i></button>'
                    : '');
        } else {
            h += '<span class="pmk-chip-peran' + (pj ? '' : ' anggota') + '">' + (pj ? 'PJ' : 'Anggota') + '</span>';
        }
        return h + '</span>';
    }
    function htmlUsulan(u) {
        var ket = 'PK ' + (u.jenis_pk === 'pengawas' ? 'Pengawas' : 'Administrator') + ' ' + M.tahun + ' · teks ' + u.cocok + ' sama'
            + (u.jumlah_calon > 1 ? ' · 1 dari ' + u.jumlah_calon + ' calon' : '');
        return '<span class="pmk-chip pmk-chip-usulan" data-usulan="' + u.node_id + '-' + u.pegawai_id + '"'
            + ' title="' + esc('Usulan: ' + u.nama + ' — ' + (u.jabatan || '') + ' (' + ket + ')') + '">'
            + '<span class="pmk-chip-teks"><span class="pmk-chip-nama">' + esc(u.nama) + '<span class="pmk-chip-tanda">usulan</span></span>'
            + '<span class="pmk-chip-jab">' + esc(ket) + '</span></span>'
            + '<button type="button" class="pmk-chip-x pmk-chip-ok" data-aksi="terima-usulan" title="Terima usulan" aria-label="Terima usulan ' + esc(u.nama) + '"><i class="fas fa-check"></i></button>'
            + '<button type="button" class="pmk-chip-x" data-aksi="abaikan-usulan" title="Abaikan usulan" aria-label="Abaikan usulan ' + esc(u.nama) + '"><i class="fas fa-xmark"></i></button>'
            + '</span>';
    }

    function gambarPemilik(id) {
        var wadah = akar.querySelector('[data-pemilik-simpul="' + id + '"]');
        if (!wadah) { return; }
        var s = D.simpul[id];
        var h = (s.pemilik || []).map(htmlChip).join('');
        if (modeUsulan) {
            h += D.usulan.filter(function (u) { return String(u.node_id) === String(id); }).map(htmlUsulan).join('');
        }
        wadah.innerHTML = h || '<span class="pmk-belum">Belum ada pemilik</span>';
    }

    function gambarIndikator(iid) {
        var li = akar.querySelector('.pmk-ind[data-indikator-id="' + iid + '"]');
        var ind = D.indikator[iid];
        if (!li || !ind) { return; }
        var lengkap = indLengkap(ind);
        li.setAttribute('data-lengkap', lengkap ? '1' : '0');
        var h = '';
        h += ind.satuan
            ? '<span class="pmk-tag"><i class="fas fa-ruler-horizontal"></i>Satuan: <b>' + esc(ind.satuan) + '</b></span>'
            : '<span class="pmk-tag kosong"><i class="fas fa-ruler-horizontal"></i>Satuan belum diisi</span>';
        h += String(ind.target_teks || '') !== ''
            ? '<span class="pmk-tag"><i class="fas fa-bullseye"></i>Target ' + M.tahun + ': <b>' + esc(ind.target_teks) + '</b></span>'
            : '<span class="pmk-tag kosong"><i class="fas fa-bullseye"></i>Target ' + M.tahun + ' belum diisi</span>';
        if (String(ind.target_teks || '') !== '' || ind.ikp_id) {
            h += '<span class="pmk-tag" title="' + esc((M.metode || {})[ind.metode] || '') + '"><i class="fas fa-calculator"></i>'
                + esc((M.metodeSingkat || {})[ind.metode] || ind.metode) + '</span>';
        }
        if (ind.ikp_id) {
            h += '<span class="pmk-tag ikp" title="' + esc('Turunan IKP: ' + (ind.ikp_nama || '')) + '"><i class="fas fa-link"></i>IKP: <b>'
                + esc(ind.ikp_nama || ('#' + ind.ikp_id)) + '</b>' + (ind.ikp_dihapus ? ' (sudah dihapus)' : '') + '</span>';
        }
        if (M.bolehUbah) {
            h += '<button type="button" class="pmk-ubah" data-aksi="ubah-indikator"><i class="fas fa-pen"></i> '
                + (lengkap ? 'Ubah' : 'Isi satuan &amp; target') + '</button>';
        }
        li.querySelector('.pmk-ind-nilai').innerHTML = h;
    }

    function gambarStatus(id) {
        var el = akar.querySelector('[data-status-simpul="' + id + '"]');
        if (!el) { return; }
        var st = statusSimpul(id);
        var h = '';
        if (st.lengkap) {
            h = '<span class="pmk-pil pmk-pil-ok"><i class="fas fa-circle-check"></i>Lengkap</span>';
        } else {
            if (!st.punya) { h += '<span class="pmk-pil pmk-pil-belum"><i class="fas fa-user-slash"></i>Belum ada pemilik</span>'; }
            if (st.jmlInd === 0) { h += '<span class="pmk-pil pmk-pil-kurang"><i class="fas fa-circle-info"></i>Tanpa indikator</span>'; }
            else if (st.kurang > 0) { h += '<span class="pmk-pil pmk-pil-kurang"><i class="fas fa-ruler"></i>' + st.kurang + ' indikator belum lengkap</span>'; }
        }
        el.innerHTML = h;
        var node = el.closest('.pmk-simpul');
        if (node) { node.setAttribute('data-lengkap', st.lengkap ? '1' : '0'); }
    }

    function gambarSimpul(id) {
        gambarPemilik(id);
        gambarStatus(id);
        (D.simpul[id].indikator || []).forEach(gambarIndikator);
    }

    // ------------------------------------------------------------------
    // Cakupan (dihitung ulang dari state)
    // ------------------------------------------------------------------
    function setStat(kunci, teks) {
        $$('[data-stat="' + kunci + '"]').forEach(function (el) { el.textContent = teks; });
    }
    function setBar(kunci, p) {
        $$('[data-stat="' + kunci + '"]').forEach(function (el) { el.style.width = p + '%'; });
    }
    function hitungUlang() {
        var lv = {};
        LEVEL.forEach(function (l) { lv[l] = { s: 0, p: 0, i: 0, il: 0 }; });
        Object.keys(D.simpul).forEach(function (id) {
            var s = D.simpul[id];
            var x = lv[s.level];
            if (!x) { return; }
            x.s++;
            if ((s.pemilik || []).length) { x.p++; }
            (s.indikator || []).forEach(function (iid) { x.i++; if (indLengkap(D.indikator[iid])) { x.il++; } });
        });
        var t = { s: 0, p: 0, i: 0, il: 0 };
        LEVEL.forEach(function (l) {
            var x = lv[l];
            t.s += x.s; t.p += x.p; t.i += x.i; t.il += x.il;
            setStat('pemilik-' + l, x.p + '/' + x.s); setBar('pemilik-bar-' + l, persen(x.p, x.s));
            setStat('ind-' + l, x.il + '/' + x.i); setBar('ind-bar-' + l, persen(x.il, x.i));
        });
        setStat('pemilik-total', t.p + '/' + t.s); setStat('pemilik-persen', persen(t.p, t.s) + '%');
        setStat('ind-total', t.il + '/' + t.i); setStat('ind-persen', persen(t.il, t.i) + '%');
        setStat('mini-pemilik', t.p + '/' + t.s); setStat('mini-ind', t.il + '/' + t.i);

        // Roster "matriks 0".
        var tanpa = 0, perKat = {};
        $$('.pmk-roster li[data-roster-id]').forEach(function (li) {
            var n = +(D.jumlahPeran[li.getAttribute('data-roster-id')] || 0);
            var k = li.getAttribute('data-kategori');
            li.classList.toggle('pmk-punya-peran', n > 0);
            if (n === 0) { tanpa++; perKat[k] = (perKat[k] || 0) + 1; }
        });
        setStat('tanpa-peran', tanpa); setStat('tanpa-peran-lencana', tanpa); setStat('mini-tanpa', tanpa);
        ['struktural', 'fungsional', 'pelaksana', 'lainnya'].forEach(function (k) { setStat('tanpa-' + k, perKat[k] || 0); });
        saringRoster();

        setStat('usulan', D.usulan.length); setStat('usulan-info', D.usulan.length);
        var btnUsulan = akar.querySelector('.pmk-bar [data-aksi="usulan"]');
        if (btnUsulan) {
            btnUsulan.disabled = D.usulan.length === 0 && !modeUsulan;
        }
    }

    // ------------------------------------------------------------------
    // Saringan: "hanya yang belum lengkap" + pencarian
    // ------------------------------------------------------------------
    function anakLangsung(node) {
        return $$(':scope > .pmk-anak > .pmk-grup > .pmk-simpul', node);
    }
    function teksSimpul(node) {
        var id = node.getAttribute('data-simpul-id');
        if (!id) {
            // Eselon II: sasaran IKU + nama indikator IKU-nya.
            return normal($$(':scope > .pmk-kartu .pmk-sasaran, :scope > .pmk-anak > .pmk-grup > .pmk-grup-kepala .nm', node)
                .map(function (el) { return el.textContent; }).join(' '));
        }
        var s = D.simpul[id];
        var t = [s.sasaran];
        (s.indikator || []).forEach(function (i) { t.push(D.indikator[i] ? D.indikator[i].nama : ''); });
        (s.pemilik || []).forEach(function (p) { t.push(p.nama, p.jabatan); });
        return normal(t.join(' '));
    }
    function evaluasi(node) {
        var anak = anakLangsung(node);
        var subBelum = false, subCocok = false;
        anak.forEach(function (a) {
            var r = evaluasi(a);
            if (r.belum) { subBelum = true; }
            if (r.cocok) { subCocok = true; }
        });
        var id = node.getAttribute('data-simpul-id');
        var selfBelum = id ? !statusSimpul(id).lengkap : false;
        // Grup indikator IKU yang belum diturunkan juga "belum lengkap".
        if (!id && node.querySelector(':scope > .pmk-anak > .pmk-grup[data-kosong="1"]')) { selfBelum = true; }
        var selfCocok = kataCari ? teksSimpul(node).indexOf(kataCari) !== -1 : true;

        var belum = selfBelum || subBelum;
        var cocok = kataCari ? (selfCocok || subCocok) : true;
        var tampil = (!saringBelum || belum) && cocok;
        node.classList.toggle('pmk-sembunyi', !tampil);
        // Simpul yang tampil hanya sebagai konteks (dirinya lengkap / tidak cocok) diredupkan.
        var konteks = tampil && ((saringBelum && !selfBelum) || (kataCari && !selfCocok));
        node.classList.toggle('pmk-konteks', !!konteks);
        if (tampil && kataCari && subCocok) { lipat(node, false); }

        // Grup kosong di dalam simpul ini.
        $$(':scope > .pmk-anak > .pmk-grup', node).forEach(function (g) {
            var isi = $$(':scope > .pmk-simpul', g);
            var ada = isi.some(function (s) { return !s.classList.contains('pmk-sembunyi'); });
            var kosong = g.getAttribute('data-kosong') === '1';
            g.classList.toggle('pmk-sembunyi', isi.length ? !ada : (kataCari !== ''));
            if (kosong && !kataCari) { g.classList.remove('pmk-sembunyi'); }
        });
        return { belum: belum, cocok: cocok };
    }
    function terapkanSaringan() {
        var akarSimpul = $$('#pmk-pohon > .pmk-simpul');
        var ada = false;
        akarSimpul.forEach(function (n) {
            evaluasi(n);
            if (!n.classList.contains('pmk-sembunyi')) { ada = true; }
        });
        var nihil = akar.querySelector('[data-hasil-nihil]');
        if (nihil) {
            nihil.classList.toggle('tampil', !ada);
            var judul = nihil.querySelector('[data-hasil-nihil-judul]');
            var teks = nihil.querySelector('[data-hasil-nihil-teks]');
            if (saringBelum && !kataCari) {
                judul.textContent = 'Semua simpul sudah lengkap';
                teks.textContent = 'Setiap simpul sudah punya pemilik dan setiap indikatornya bersatuan & bertarget tahun ' + M.tahun + '.';
            } else {
                judul.textContent = 'Tidak ada simpul yang cocok';
                teks.textContent = 'Ubah kata pencarian atau matikan saringan.';
            }
        }
    }

    // ------------------------------------------------------------------
    // Lipat / buka
    // ------------------------------------------------------------------
    function lipat(node, terlipat) {
        if (!node.querySelector(':scope > .pmk-anak')) { return; }
        node.classList.toggle('terlipat', terlipat);
        var b = node.querySelector(':scope > .pmk-kartu .pmk-lipat');
        if (b) { b.setAttribute('aria-expanded', terlipat ? 'false' : 'true'); }
    }
    /** Tampilkan simpul sampai jenjang `level` (simpul jenjang itu dilipat); 'semua' = buka semua. */
    function bukaHingga(level) {
        var urut = ['es2', 'es3', 'es4', 'pelaksana'];
        var batas = level === 'semua' ? urut.length : urut.indexOf(level);
        if (batas < 0) { return; }
        $$('.pmk-simpul', akar).forEach(function (n) {
            var i = urut.indexOf(n.getAttribute('data-level'));
            lipat(n, i >= batas);
        });
    }
    function bukaLeluhur(el) {
        var n = el.closest('.pmk-simpul');
        while (n) {
            lipat(n, false);
            n.classList.remove('pmk-sembunyi');
            n = n.parentElement ? n.parentElement.closest('.pmk-simpul') : null;
        }
    }
    function sorot(el) {
        var kartu = el.closest('.pmk-kartu');
        if (!kartu) { return; }
        kartu.classList.add('pmk-sorot');
        setTimeout(function () { kartu.classList.remove('pmk-sorot'); }, 1600);
    }

    // ------------------------------------------------------------------
    // Roster "matriks 0"
    // ------------------------------------------------------------------
    var katRoster = '';
    var cariRoster = '';
    function saringRoster() {
        var terlihat = 0;
        $$('.pmk-roster li[data-roster-id]').forEach(function (li) {
            var ok = (!katRoster || li.getAttribute('data-kategori') === katRoster)
                && (!cariRoster || (li.getAttribute('data-cari') || '').indexOf(cariRoster) !== -1);
            li.classList.toggle('pmk-tersaring', !ok);
            if (ok && !li.classList.contains('pmk-punya-peran')) { terlihat++; }
        });
        var kosong = akar.querySelector('[data-roster-kosong]');
        if (kosong) {
            kosong.classList.toggle('d-none', terlihat > 0);
            kosong.innerHTML = (katRoster || cariRoster)
                ? '<i class="fas fa-circle-info me-1"></i>Tidak ada pegawai tanpa peran yang cocok dengan saringan.'
                : '<i class="fas fa-circle-check me-1"></i>Semua pegawai sudah punya peran di pohon kinerja.';
        }
    }

    // ------------------------------------------------------------------
    // Perubahan state pemilik
    // ------------------------------------------------------------------
    function tambahPemilikKeState(p) {
        var s = D.simpul[p.node_id];
        if (!s) { return; }
        s.pemilik = (s.pemilik || []).filter(function (x) { return x.id !== p.id; });
        s.pemilik.push(p);
        s.pemilik.sort(function (a, b) {
            var pa = a.peran === 'penanggung_jawab' ? 0 : 1, pb = b.peran === 'penanggung_jawab' ? 0 : 1;
            return pa - pb || a.id - b.id;
        });
        D.usulan = D.usulan.filter(function (u) { return !(String(u.node_id) === String(p.node_id) && String(u.pegawai_id) === String(p.pegawai_id)); });
    }

    // ------------------------------------------------------------------
    // Modal: tambah pemilik
    // ------------------------------------------------------------------
    var modalPemilikEl = document.getElementById('pmkModalPemilik');
    var modalPemilik = modalPemilikEl && window.bootstrap ? new bootstrap.Modal(modalPemilikEl) : null;
    var simpulAktif = null;
    var $pilih = null;

    function siapkanSelect2() {
        if ($pilih || !window.jQuery || !jQuery.fn.select2 || !modalPemilikEl) { return; }
        $pilih = jQuery('#pmkPilihPegawai');
        $pilih.select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: jQuery(modalPemilikEl),
            placeholder: 'Cari nama, jabatan, atau NIP pegawai…',
            allowClear: true,
            minimumInputLength: 0,
            language: {
                searching: function () { return 'Mencari…'; },
                noResults: function () { return 'Pegawai tidak ditemukan. Ketik minimal 3 huruf untuk mencari di perangkat daerah lain.'; },
                errorLoading: function () { return 'Daftar pegawai gagal dimuat.'; },
                loadingMore: function () { return 'Memuat…'; }
            },
            ajax: {
                url: M.url.pegawai,
                dataType: 'json',
                delay: 250,
                data: function (p) { return { q: p.term || '', opd_id: M.opdId }; },
                processResults: function (d) { return { results: (d && d.results) || [] }; },
                cache: true
            },
            templateResult: function (it) {
                if (!it.id) { return it.text; }
                var el = document.createElement('div');
                el.className = 'pmk-s2';
                el.innerHTML = '<div class="n"></div><div class="j"></div>';
                el.querySelector('.n').textContent = it.nama || it.text;
                el.querySelector('.j').textContent = [it.jabatan, it.opd, it.nip ? 'NIP ' + it.nip : ''].filter(Boolean).join(' · ');
                return el;
            },
            templateSelection: function (it) { return it.nama || it.text; }
        });
        // jQuery 3.6.0 memutus fokus otomatis Select2 ke kotak cari (bug yang
        // dikenal); tanpa ini pengguna harus mengeklik kotak cari dulu.
        $pilih.on('select2:open', function () {
            setTimeout(function () {
                var f = modalPemilikEl.querySelector('.select2-container--open .select2-search__field');
                if (f) { f.focus(); }
            }, 0);
        });
        $pilih.on('select2:select select2:clear', function (e) {
            var it = e.params && e.params.data;
            var pr = modalPemilikEl.querySelector('[data-isi="pratinjau"]');
            pr.textContent = it && it.id ? [it.jabatan, it.opd, it.nip ? 'NIP ' + it.nip : ''].filter(Boolean).join(' · ') : '';
        });
    }

    function galatModal(modalEl, pesan) {
        var g = modalEl.querySelector('[data-isi="galat"]');
        if (!g) { return; }
        g.textContent = pesan || '';
        g.classList.toggle('d-none', !pesan);
    }

    function isiKotakSimpul(modalEl, id) {
        var s = D.simpul[id];
        var kotak = modalEl.querySelector('[data-isi="kotak"]');
        kotak.style.setProperty('--warna', 'var(--pmk-' + s.level + ')');
        modalEl.querySelector('[data-isi="level"]').textContent = M.label[s.level] || s.level;
        var sas = modalEl.querySelector('[data-isi="sasaran"]');
        if (sas) { sas.textContent = s.sasaran; }
    }

    function bukaModalPemilik(id) {
        if (!modalPemilik) { return; }
        siapkanSelect2();
        simpulAktif = id;
        isiKotakSimpul(modalPemilikEl, id);
        galatModal(modalPemilikEl, '');
        modalPemilikEl.querySelector('[data-isi="pratinjau"]').textContent = '';
        if ($pilih) { $pilih.val(null).trigger('change'); }
        var peran = adaPj(id) ? 'anggota' : 'penanggung_jawab';
        $$('input[name="pmkPeran"]', modalPemilikEl).forEach(function (r) { r.checked = r.value === peran; });
        modalPemilik.show();
    }
    if (modalPemilikEl) {
        // Di layar lebar daftar pegawai langsung dibuka; di ponsel tidak, supaya
        // papan ketik tidak menutupi pilihan peran.
        modalPemilikEl.addEventListener('shown.bs.modal', function () {
            if ($pilih && window.innerWidth >= 768) { $pilih.select2('open'); }
        });
    }

    function simpanPemilik(btn) {
        var id = simpulAktif;
        var pegawaiId = $pilih ? $pilih.val() : null;
        var peranEl = modalPemilikEl.querySelector('input[name="pmkPeran"]:checked');
        if (!pegawaiId) { galatModal(modalPemilikEl, 'Pilih pegawai terlebih dahulu.'); return; }
        var sudah = (D.simpul[id].pemilik || []).some(function (p) { return String(p.pegawai_id) === String(pegawaiId); });
        if (sudah) { galatModal(modalPemilikEl, 'Pegawai ini sudah menjadi pemilik simpul ini.'); return; }
        btn.disabled = true;
        kirim(M.url.simpan, { tahun: M.tahun, node_id: +id, pegawai_id: +pegawaiId, peran: peranEl ? peranEl.value : 'penanggung_jawab' })
            .then(function (j) {
                (j.data.pemilik || []).forEach(function (p) {
                    tambahPemilikKeState(p);
                    D.jumlahPeran[p.pegawai_id] = (+D.jumlahPeran[p.pegawai_id] || 0) + 1;
                });
                gambarSimpul(id);
                hitungUlang();
                terapkanSaringan();
                modalPemilik.hide();
                var node = akar.querySelector('.pmk-simpul[data-simpul-id="' + id + '"]');
                if (node) { sorot(node); }
                var pj = (D.simpul[id].pemilik || []).filter(function (p) { return p.peran === 'penanggung_jawab'; }).length;
                toast(j.message + (pj > 1 ? ' Catatan: simpul ini kini punya ' + pj + ' penanggung jawab.' : ''), pj > 1 ? 'peringatan' : '');
            })
            .catch(function (e) { galatModal(modalPemilikEl, e.message); })
            .then(function () { btn.disabled = false; });
    }

    // ------------------------------------------------------------------
    // Ganti peran & lepas pemilik
    // ------------------------------------------------------------------
    function cariPemilik(simpulId, pemilikId) {
        return (D.simpul[simpulId].pemilik || []).filter(function (p) { return String(p.id) === String(pemilikId); })[0];
    }

    function gantiPeran(simpulId, chip, btn) {
        var p = cariPemilik(simpulId, chip.getAttribute('data-pemilik-id'));
        if (!p) { return; }
        var baru = p.peran === 'penanggung_jawab' ? 'anggota' : 'penanggung_jawab';
        btn.disabled = true;
        kirim(M.url.simpan, { tahun: M.tahun, node_id: +simpulId, pegawai_id: +p.pegawai_id, peran: baru, ganti_peran: true })
            .then(function (j) {
                (j.data.pemilik || []).forEach(tambahPemilikKeState);
                gambarSimpul(simpulId);
                toast(p.nama + ' kini ' + (baru === 'anggota' ? 'anggota' : 'penanggung jawab') + '.');
            })
            .catch(function (e) { toast(e.message, 'galat'); btn.disabled = false; });
    }

    function lepasPemilik(simpulId, chip) {
        var p = cariPemilik(simpulId, chip.getAttribute('data-pemilik-id'));
        if (!p) { return; }
        var s = D.simpul[simpulId];
        var tanya = window.Konfirmasi && Konfirmasi.hapus
            ? Konfirmasi.hapus({
                judul: 'Lepas Pemilik Simpul',
                nama: p.nama,
                pesan: 'Pegawai ini tidak lagi tercatat sebagai pemilik simpul "' + s.sasaran + '" untuk tahun ' + M.tahun
                    + '. SKP di eKin yang sudah menarik simpul ini tidak ikut berubah.',
                ya: 'Lepas',
                permanen: false
            })
            : Promise.resolve(window.confirm('Lepas ' + p.nama + ' dari simpul ini?'));
        tanya.then(function (ya) {
            if (!ya) { return; }
            kirim(M.url.hapus + p.id, {})
                .then(function (j) {
                    s.pemilik = (s.pemilik || []).filter(function (x) { return x.id !== p.id; });
                    var n = (+D.jumlahPeran[p.pegawai_id] || 0) - 1;
                    D.jumlahPeran[p.pegawai_id] = n > 0 ? n : 0;
                    gambarSimpul(simpulId);
                    hitungUlang();
                    terapkanSaringan();
                    toast(j.message);
                })
                .catch(function (e) { toast(e.message, 'galat'); });
        });
    }

    // ------------------------------------------------------------------
    // Usulan dari PK
    // ------------------------------------------------------------------
    function aturModeUsulan(nyala) {
        modeUsulan = nyala;
        akar.classList.toggle('pmk-usulan-mode', nyala);
        var tombol = akar.querySelector('.pmk-bar [data-aksi="usulan"]');
        if (tombol) { tombol.classList.toggle('active', nyala); tombol.setAttribute('aria-pressed', nyala ? 'true' : 'false'); }
        var simpulUsulan = {};
        D.usulan.forEach(function (u) { simpulUsulan[u.node_id] = true; });
        Object.keys(D.simpul).forEach(gambarPemilik);
        if (nyala) {
            Object.keys(simpulUsulan).forEach(function (id) {
                var n = akar.querySelector('.pmk-simpul[data-simpul-id="' + id + '"]');
                if (n) { bukaLeluhur(n); }
            });
            var pertama = akar.querySelector('.pmk-chip-usulan');
            if (pertama) { pertama.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }
        hitungUlang();
    }

    function terimaUsulan(daftar) {
        if (!daftar.length) { return Promise.resolve(); }
        // Peran: orang pertama pada simpul yang belum punya PJ = penanggung jawab; sisanya anggota.
        var dapatPj = {};
        var items = daftar.map(function (u) {
            var perlu = !adaPj(u.node_id) && !dapatPj[u.node_id];
            if (perlu) { dapatPj[u.node_id] = true; }
            return { node_id: +u.node_id, pegawai_id: +u.pegawai_id, peran: perlu ? 'penanggung_jawab' : 'anggota' };
        });
        return kirim(M.url.simpan, { tahun: M.tahun, sumber: 'pk', items: items })
            .then(function (j) {
                var tersentuh = {};
                (j.data.pemilik || []).forEach(function (p) {
                    tambahPemilikKeState(p);
                    D.jumlahPeran[p.pegawai_id] = (+D.jumlahPeran[p.pegawai_id] || 0) + 1;
                    tersentuh[p.node_id] = true;
                });
                (j.data.dilewati || []).forEach(function (x) {
                    D.usulan = D.usulan.filter(function (u) { return !(u.node_id === x.node_id && u.pegawai_id === x.pegawai_id); });
                    tersentuh[x.node_id] = true;
                });
                Object.keys(tersentuh).forEach(gambarSimpul);
                if (!D.usulan.length) { aturModeUsulan(false); }
                hitungUlang();
                terapkanSaringan();
                toast(j.message);
            })
            .catch(function (e) { toast(e.message, 'galat'); });
    }

    function usulanDari(chip) {
        var kunci = chip.getAttribute('data-usulan');
        return D.usulan.filter(function (u) { return (u.node_id + '-' + u.pegawai_id) === kunci; })[0];
    }

    // ------------------------------------------------------------------
    // Modal: satuan & target indikator
    // ------------------------------------------------------------------
    var modalIndEl = document.getElementById('pmkModalIndikator');
    var modalInd = modalIndEl && window.bootstrap ? new bootstrap.Modal(modalIndEl) : null;
    var indAktif = null;
    var BANTU_METODE = {
        sum: 'Target tahunan = jumlah capaian bulanan (mis. jumlah kegiatan, dokumen, ton).',
        trend_naik: 'Target tahunan = posisi akhir tahun; makin tinggi makin baik (mis. persentase, indeks).',
        trend_turun: 'Target tahunan = posisi akhir tahun; makin rendah makin baik (mis. jumlah kasus, waktu layanan).',
        trend_flat: 'Nilai dipertahankan sama sepanjang tahun (mis. predikat yang harus tetap).'
    };

    function ikpById(id) { return D.ikp.filter(function (i) { return String(i.id) === String(id); })[0]; }

    function segarkanInfoIkp() {
        var sel = modalIndEl.querySelector('#pmkIkp');
        var info = modalIndEl.querySelector('[data-isi="info-ikp"]');
        var i = ikpById(sel.value);
        if (!i) { info.classList.remove('tampil'); info.innerHTML = ''; return; }
        info.innerHTML = '<div><i class="fas fa-link me-1"></i>IKP ini: satuan <b>' + esc(i.satuan || '—') + '</b>, target ' + M.tahun + ' <b>'
            + esc(i.target || '—') + '</b>' + (i.metode ? ', ' + esc((M.metodeSingkat || {})[i.metode] || i.metode) : '') + '.</div>'
            + '<div class="mt-1">Target bulanan indikator ini di eKin akan mengikuti target bulanan IKP.</div>'
            + '<button type="button" class="btn btn-sm btn-outline-primary mt-2" data-aksi="samakan-ikp"><i class="fas fa-clone"></i> Samakan satuan, target &amp; metode dengan IKP</button>';
        info.classList.add('tampil');
    }
    function segarkanBantuMetode() {
        var v = modalIndEl.querySelector('#pmkMetode').value;
        modalIndEl.querySelector('[data-isi="metode-bantu"]').textContent = BANTU_METODE[v] || '';
    }

    function bukaModalIndikator(iid) {
        if (!modalInd) { return; }
        var ind = D.indikator[iid];
        var s = D.simpul[ind.simpul_id];
        indAktif = iid;
        isiKotakSimpul(modalIndEl, ind.simpul_id);
        modalIndEl.querySelector('[data-isi="indikator"]').textContent = ind.nama;
        modalIndEl.querySelector('[data-isi="sasaran"]').textContent = 'Sasaran: ' + s.sasaran;
        galatModal(modalIndEl, '');
        modalIndEl.querySelector('#pmkSatuan').value = ind.satuan || '';
        modalIndEl.querySelector('#pmkTarget').value = ind.target_teks || '';
        modalIndEl.querySelector('#pmkMetode').value = ind.metode || 'sum';
        var selIkp = modalIndEl.querySelector('#pmkIkp');
        selIkp.value = ind.ikp_id ? String(ind.ikp_id) : '';
        if (ind.ikp_id && selIkp.value !== String(ind.ikp_id)) {
            // IKP tertaut sudah tidak aktif: tampilkan tetap sebagai pilihan agar tidak hilang diam-diam.
            var o = document.createElement('option');
            o.value = ind.ikp_id; o.textContent = (ind.ikp_nama || ('IKP #' + ind.ikp_id)) + ' (tidak aktif)';
            selIkp.appendChild(o); selIkp.value = String(ind.ikp_id);
        }
        segarkanInfoIkp();
        segarkanBantuMetode();
        modalInd.show();
    }
    if (modalIndEl) {
        modalIndEl.addEventListener('shown.bs.modal', function () { modalIndEl.querySelector('#pmkSatuan').focus(); });
        modalIndEl.querySelector('#pmkIkp').addEventListener('change', segarkanInfoIkp);
        modalIndEl.querySelector('#pmkMetode').addEventListener('change', segarkanBantuMetode);
        modalIndEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
                simpanIndikator(modalIndEl.querySelector('.modal-footer .btn-success'), false);
            }
        });
    }

    function indikatorBerikutnya(dari) {
        var semua = $$('.pmk-ind[data-indikator-id]', akar);
        var mulai = semua.findIndex(function (li) { return li.getAttribute('data-indikator-id') === String(dari); });
        for (var i = mulai + 1; i < semua.length; i++) {
            var li = semua[i];
            if (li.getAttribute('data-lengkap') === '1') { continue; }
            if (li.closest('.pmk-sembunyi')) { continue; }
            return li;
        }
        return null;
    }

    function simpanIndikator(btn, lanjut) {
        var iid = indAktif;
        var payload = {
            tahun: M.tahun,
            indikator_id: +iid,
            satuan: modalIndEl.querySelector('#pmkSatuan').value,
            target: modalIndEl.querySelector('#pmkTarget').value,
            metode: modalIndEl.querySelector('#pmkMetode').value,
            ikp_id: modalIndEl.querySelector('#pmkIkp').value ? +modalIndEl.querySelector('#pmkIkp').value : null
        };
        $$('.modal-footer .btn', modalIndEl).forEach(function (b) { b.disabled = true; });
        kirim(M.url.indikator, payload)
            .then(function (j) {
                if (j.data && j.data.indikator) {
                    var lama = D.indikator[iid];
                    D.indikator[iid] = Object.assign({}, lama, j.data.indikator, { simpul_id: lama.simpul_id });
                }
                var simpulId = D.indikator[iid].simpul_id;
                gambarIndikator(iid);
                gambarStatus(simpulId);
                hitungUlang();
                terapkanSaringan();
                var li = akar.querySelector('.pmk-ind[data-indikator-id="' + iid + '"]');
                var berikut = lanjut ? indikatorBerikutnya(iid) : null;
                toast(j.message + (String(D.indikator[iid].target_teks || '') !== '' && D.indikator[iid].target === null
                    ? ' Target berupa teks: capaiannya tidak dihitung otomatis.' : ''));
                if (berikut) {
                    bukaLeluhur(berikut);
                    berikut.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    sorot(berikut);
                    bukaModalIndikator(berikut.getAttribute('data-indikator-id'));
                } else {
                    modalInd.hide();
                    if (li) { sorot(li); }
                    if (lanjut) { toast('Tidak ada lagi indikator yang belum lengkap di bawah indikator ini.', 'peringatan'); }
                }
            })
            .catch(function (e) { galatModal(modalIndEl, e.message); })
            .then(function () { $$('.modal-footer .btn', modalIndEl).forEach(function (b) { b.disabled = false; }); });
    }

    // ------------------------------------------------------------------
    // Delegasi klik
    // ------------------------------------------------------------------
    akar.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-aksi]');
        if (!btn || !akar.contains(btn)) { return; }
        var aksi = btn.getAttribute('data-aksi');
        var node = btn.closest('.pmk-simpul');
        var simpulId = node ? node.getAttribute('data-simpul-id') : null;

        switch (aksi) {
            case 'lipat':
                lipat(node, !node.classList.contains('terlipat'));
                break;
            case 'tambah-pemilik':
                if (simpulId) { bukaModalPemilik(simpulId); }
                break;
            case 'simpan-pemilik':
                simpanPemilik(btn);
                break;
            case 'ganti-peran':
                gantiPeran(simpulId, btn.closest('.pmk-chip'), btn);
                break;
            case 'hapus-pemilik':
                lepasPemilik(simpulId, btn.closest('.pmk-chip'));
                break;
            case 'ubah-indikator':
                bukaModalIndikator(btn.closest('.pmk-ind').getAttribute('data-indikator-id'));
                break;
            case 'simpan-indikator':
                simpanIndikator(btn, btn.getAttribute('data-lanjut') === '1');
                break;
            case 'samakan-ikp':
                var i = ikpById(modalIndEl.querySelector('#pmkIkp').value);
                if (i) {
                    if (i.satuan) { modalIndEl.querySelector('#pmkSatuan').value = i.satuan; }
                    if (i.target) { modalIndEl.querySelector('#pmkTarget').value = i.target; }
                    if (i.metode && (M.metode || {})[i.metode]) { modalIndEl.querySelector('#pmkMetode').value = i.metode; segarkanBantuMetode(); }
                }
                break;
            case 'usulan':
                aturModeUsulan(!modeUsulan);
                break;
            case 'terima-usulan':
                var u = usulanDari(btn.closest('.pmk-chip'));
                if (u) { btn.disabled = true; terimaUsulan([u]); }
                break;
            case 'abaikan-usulan':
                var ua = usulanDari(btn.closest('.pmk-chip'));
                if (ua) {
                    D.usulan = D.usulan.filter(function (x) { return x !== ua; });
                    gambarPemilik(ua.node_id);
                    if (!D.usulan.length) { aturModeUsulan(false); } else { hitungUlang(); }
                }
                break;
            case 'terima-semua':
                // Hanya usulan yang jelas (satu calon per simpul). Simpul dengan
                // beberapa calon harus dipilih satu per satu.
                var jelas = D.usulan.filter(function (x) { return (x.jumlah_calon || 1) === 1; });
                var ragu = D.usulan.length - jelas.length;
                if (!jelas.length) {
                    toast('Semua usulan yang tersisa punya lebih dari satu calon per simpul — pilih satu per satu dengan tombol ✓.', 'peringatan');
                    break;
                }
                var tanya = window.Konfirmasi && Konfirmasi.tanya
                    ? Konfirmasi.tanya({
                        jenis: 'tanya',
                        judul: 'Terima usulan yang jelas?',
                        pesan: jelas.length + ' pegawai akan ditetapkan sebagai pemilik simpul sesuai PK tahun ' + M.tahun
                            + '. Anda tetap bisa melepas atau mengubah perannya satu per satu.',
                        rincian: ragu ? [ragu + ' usulan lain TIDAK ikut karena simpulnya punya beberapa calon; pilih manual.'] : [],
                        rincianJudul: 'Catatan',
                        ya: 'Terima ' + jelas.length + ' usulan'
                    })
                    : Promise.resolve(window.confirm('Terima ' + jelas.length + ' usulan?'));
                tanya.then(function (ya) { if (ya) { btn.disabled = true; terimaUsulan(jelas).then(function () { btn.disabled = false; }); } });
                break;
            case 'buka-roster':
                e.preventDefault();
                var det = document.getElementById('pmk-tanpa-peran');
                if (det) { det.open = true; det.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
                break;
        }
    });

    // Saringan & pencarian.
    var tundaCari = null;
    var inputCari = akar.querySelector('[data-aksi="cari"]');
    if (inputCari) {
        inputCari.addEventListener('input', function () {
            clearTimeout(tundaCari);
            tundaCari = setTimeout(function () {
                kataCari = normal(inputCari.value.trim());
                terapkanSaringan();
            }, 180);
        });
    }
    var sakelar = akar.querySelector('[data-aksi="belum-lengkap"]');
    if (sakelar) {
        sakelar.addEventListener('change', function () {
            saringBelum = sakelar.checked;
            if (saringBelum) { bukaHingga('semua'); }
            terapkanSaringan();
        });
    }
    var kedalaman = akar.querySelector('[data-aksi="kedalaman"]');
    if (kedalaman) {
        kedalaman.addEventListener('change', function () {
            var v = kedalaman.value;
            if (v) { bukaHingga(v === 'pelaksana' ? 'semua' : v); }
            kedalaman.value = '';
        });
    }

    // Roster.
    var cariR = akar.querySelector('[data-aksi="cari-roster"]');
    if (cariR) {
        cariR.addEventListener('input', function () { cariRoster = normal(cariR.value.trim()); saringRoster(); });
    }
    $$('.pmk-kat-btn', akar).forEach(function (b) {
        b.addEventListener('click', function () {
            katRoster = b.getAttribute('data-kategori') || '';
            $$('.pmk-kat-btn', akar).forEach(function (x) { x.classList.toggle('aktif', x === b); });
            saringRoster();
        });
    });

    // Bilah lengket tepat di bawah kepala aplikasi (tingginya berubah di ponsel).
    function aturTinggiKepala() {
        var h = document.getElementById('main-header');
        akar.style.setProperty('--pmk-atas', (h ? h.offsetHeight : 0) + 'px');
    }
    window.addEventListener('resize', aturTinggiKepala);

    // ------------------------------------------------------------------
    // Awal
    // ------------------------------------------------------------------
    aturTinggiKepala();
    Object.keys(D.simpul).forEach(gambarSimpul);
    // Pohon besar: tampilkan sampai jenjang kedua dulu, sisanya dibuka sesuai kebutuhan.
    if ((M.jumlahSimpul || 0) > 80) { bukaHingga('es3'); }
    hitungUlang();
    terapkanSaringan();
})();

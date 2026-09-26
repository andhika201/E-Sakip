/*
 * AKSARA+ — Kinerja Prioritas (IKP): cerminan JS dari app/Helpers/ikp_helper.php.
 *
 * MENGAPA ADA CERMINAN: layar perlu memberi umpan balik seketika (cek jumlah,
 * rekap triwulan, Bagi Rata) tanpa bolak-balik ke server. Angka yang
 * TERSIMPAN tetap hasil hitung server (balasan JSON membawa cek server), jadi
 * bila keduanya berbeda, yang benar adalah server — ubah keduanya bersamaan.
 *
 * Aturan angka sama dengan ikp_angka_baca(): "302.663" = 302663 (titik
 * ribuan), "1.234,5" = 1234,5, "24,65" = 24,65, "2.5" = 2,5, tanda % dibuang,
 * kosong/"-" = belum diisi; nol = belum ada untuk TARGET, nol = sah untuk
 * REALISASI.
 */
(function (w) {
    'use strict';

    var KOSONG = ['', '-', '–', '—'];

    function rapikan(t) {
        return String(t === null || t === undefined ? '' : t)
            .replace(/[   \t]/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function kosong(t) {
        return KOSONG.indexOf(rapikan(t)) !== -1;
    }

    function sah(t) {
        if (t === null || t === undefined || kosong(t)) return true;
        var b = rapikan(t);
        return /^\d{1,3}(\.\d{3})*(,\d+)?\s*%?$/.test(b) || /^\d+([.,]\d+)?\s*%?$/.test(b);
    }

    function baca(t, nolKosong) {
        if (nolKosong === undefined) nolKosong = true;
        if (t === null || t === undefined || kosong(t)) return null;
        var b = rapikan(t).replace(/%/g, '').trim();
        if (b.indexOf(',') !== -1) {
            b = b.replace(/\./g, '').replace(/,/g, '.');
        } else if (/^-?\d{1,3}(\.\d{3})+$/.test(b)) {
            b = b.replace(/\./g, '');
        }
        if (!/^-?\d+(\.\d+)?$/.test(b)) return null;
        var v = parseFloat(b);
        if (Math.abs(v) < 1e-12) return nolKosong ? null : 0;
        return v;
    }

    function fmt(v, maxDes) {
        if (v === null || v === undefined || !isFinite(v)) return '-';
        if (maxDes === undefined) maxDes = 2;
        var p = Math.pow(10, maxDes);
        var r = Math.round(Number(v) * p) / p;
        if (r === 0) r = 0; // -0 -> 0
        return r.toLocaleString('id-ID', { maximumFractionDigits: maxDes, useGrouping: true });
    }

    function metodeSah(m) {
        return ['sum', 'trend_naik', 'trend_turun', 'trend_flat'].indexOf(m) !== -1;
    }

    /** bulan: {1..12: angka|null} -> {1..4: angka|null} */
    function triwulan(bulan, metode) {
        var tw = {};
        for (var q = 1; q <= 4; q++) {
            if (!metodeSah(metode)) { tw[q] = null; continue; }
            var isi = [];
            for (var m = 3 * q - 2; m <= 3 * q; m++) {
                var v = bulan[m];
                if (v !== null && v !== undefined && v !== '') isi.push(Number(v));
            }
            if (!isi.length) tw[q] = null;
            else if (metode === 'sum') tw[q] = isi.reduce(function (a, b) { return a + b; }, 0);
            else tw[q] = isi[isi.length - 1];
        }
        return tw;
    }

    /** Hasil: array 0-based sepanjang n. Sama persis dengan ikp_bagi_rata(). */
    function bagiRata(total, n, metode, awal, bulat) {
        if (n < 1 || !metodeSah(metode) || !isFinite(total)) return [];
        var out = [], k;
        if (metode === 'trend_flat') {
            for (k = 0; k < n; k++) out.push(total);
            return out;
        }
        if (metode === 'sum') {
            var tanda = total < 0 ? -1 : 1, mutlak = Math.abs(total);
            var des = (bulat && Math.abs(mutlak - Math.round(mutlak)) < 1e-9) ? 0 : 2;
            if (des === 2 && Math.floor(Math.round(mutlak * 100) / n) === 0 && mutlak > 0) des = 4;
            var skala = Math.pow(10, des), unit = Math.round(mutlak * skala);
            var dasar = Math.floor(unit / n), sisa = unit - dasar * n;
            for (k = 1; k <= n; k++) out.push(tanda * (dasar + (k <= sisa ? 1 : 0)) / skala);
            return out;
        }
        if (awal === null || awal === undefined || !isFinite(awal)) {
            for (k = 0; k < n; k++) out.push(total);
            return out;
        }
        for (k = 1; k <= n; k++) {
            var v = awal + (total - awal) * k / n;
            if (k === n) v = total;
            else if (bulat) v = Math.round(v);
            else v = Math.round(v * 100) / 100;
            out.push(v);
        }
        return out;
    }

    /** anak: array berurutan (null = kosong). Sama dengan ikp_cek(). */
    function cek(metode, induk, anak) {
        var tol = 0.005, isi = [], i;
        for (i = 0; i < anak.length; i++) {
            if (anak[i] !== null && anak[i] !== undefined && anak[i] !== '') isi.push({ k: i, v: Number(anak[i]) });
        }
        function h(ok, pesan, selisih, status) { return { ok: ok, pesan: pesan, selisih: selisih, status: status }; }
        if (!metodeSah(metode)) return h(false, 'Metode perhitungan belum dipilih, konsistensi tidak dapat diperiksa.', null, 'tanpa_metode');
        if (induk === null || induk === undefined) return h(false, 'Target induk belum berupa angka, konsistensi tidak dapat diperiksa.', null, 'tanpa_induk');
        if (!isi.length) return h(false, 'Belum ada rincian yang diisi.', null, 'kosong');

        if (metode === 'sum') {
            var jml = isi.reduce(function (a, b) { return a + b.v; }, 0), s = jml - induk;
            if (Math.abs(s) <= tol) return h(true, 'Jumlah ' + fmt(jml, 4) + ' sesuai target ' + fmt(induk, 4) + '.', 0, 'cocok');
            return h(false, 'Jumlah ' + fmt(jml, 4) + (s < 0 ? ' kurang ' : ' lebih ') + fmt(Math.abs(s), 4) + ' dari target ' + fmt(induk, 4) + '.', s, 'selisih');
        }
        var akhir = anak[anak.length - 1];
        var akhirKosong = akhir === null || akhir === undefined || akhir === '';
        if (metode === 'trend_flat') {
            for (i = 0; i < isi.length; i++) {
                if (Math.abs(isi[i].v - induk) > tol) {
                    return h(false, 'Nilai ' + fmt(isi[i].v, 4) + ' pada periode ke-' + (isi[i].k + 1) + ' berbeda dari target ' + fmt(induk, 4) + ' (metode dipertahankan: semua periode sama).', isi[i].v - induk, 'selisih');
                }
            }
            if (akhirKosong) return h(false, 'Periode terakhir belum diisi.', null, 'selisih');
            return h(true, 'Semua periode terisi sama dengan target ' + fmt(induk, 4) + '.', 0, 'cocok');
        }
        var naik = metode === 'trend_naik', sebelum = null;
        for (i = 0; i < isi.length; i++) {
            if (sebelum !== null && (naik ? isi[i].v < sebelum - tol : isi[i].v > sebelum + tol)) {
                return h(false, 'Nilai ' + fmt(isi[i].v, 4) + ' pada periode ke-' + (isi[i].k + 1) + (naik
                    ? ' turun dari periode sebelumnya (metode makin tinggi makin baik: tidak boleh turun).'
                    : ' naik dari periode sebelumnya (metode makin rendah makin baik: tidak boleh naik).'), null, 'selisih');
            }
            sebelum = isi[i].v;
        }
        if (akhirKosong) return h(false, 'Periode terakhir belum diisi; nilainya harus sama dengan target ' + fmt(induk, 4) + '.', null, 'selisih');
        var sl = Number(akhir) - induk;
        if (Math.abs(sl) > tol) return h(false, 'Nilai periode terakhir ' + fmt(Number(akhir), 4) + ' belum sama dengan target ' + fmt(induk, 4) + '.', sl, 'selisih');
        return h(true, 'Periode terakhir sama dengan target ' + fmt(induk, 4) + (naik ? ', naik bertahap.' : ', turun bertahap.'), 0, 'cocok');
    }

    /** HTML lencana hasil cek (kelas .ikp-cek.cocok|selisih|netral). */
    function lencanaCek(h) {
        var kelas = h.status === 'cocok' ? 'cocok' : (h.status === 'selisih' ? 'selisih' : 'netral');
        var ikon = kelas === 'cocok' ? 'fa-circle-check' : (kelas === 'selisih' ? 'fa-triangle-exclamation' : 'fa-minus');
        var teks = kelas === 'cocok' ? 'Sesuai' : (kelas === 'selisih' ? (h.selisih !== null ? 'Selisih ' + fmt(h.selisih, 4) : 'Periksa') : 'Belum dicek');
        var span = document.createElement('span');
        span.className = 'ikp-cek ' + kelas;
        span.title = h.pesan;
        span.innerHTML = '<i class="fas ' + ikon + '"></i>';
        span.appendChild(document.createTextNode(teks));
        return span;
    }

    // ------------------------------------------------------------------
    // Jaringan
    // ------------------------------------------------------------------
    function tokenCsrf() {
        var m = document.querySelector('meta[name="csrf-hash"]');
        return m ? m.getAttribute('content') : '';
    }

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
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.text().then(function (teks) {
                var j = null;
                try { j = JSON.parse(teks); } catch (e) { j = null; }
                if (j && j.csrfHash) {
                    var m = document.querySelector('meta[name="csrf-hash"]');
                    if (m) m.setAttribute('content', j.csrfHash);
                }
                if (!res.ok || !j || j.status !== 'success') {
                    var pesan = (j && j.message) ? j.message
                        : (res.status === 403 || res.redirected ? 'Sesi berakhir atau Anda tidak berizin. Muat ulang halaman lalu coba lagi.'
                            : 'Gagal menyimpan (kode ' + res.status + '). Coba lagi.');
                    throw new Error(pesan);
                }
                return j.data;
            });
        });
    }

    function ambil(url) {
        return fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().then(function (j) {
                if (!res.ok || j.status !== 'success') throw new Error(j.message || 'Gagal memuat data.');
                return j.data;
            });
        });
    }

    // ------------------------------------------------------------------
    // Toast
    // ------------------------------------------------------------------
    function toast(pesan, jenis) {
        var wadah = document.querySelector('.ikp-toast-wadah');
        if (!wadah) {
            wadah = document.createElement('div');
            wadah.className = 'ikp-toast-wadah';
            wadah.setAttribute('aria-live', 'polite');
            document.body.appendChild(wadah);
        }
        var el = document.createElement('div');
        el.className = 'ikp-toast' + (jenis === 'galat' ? ' galat' : '');
        el.innerHTML = '<i class="fas ' + (jenis === 'galat' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i>';
        var s = document.createElement('span');
        s.textContent = pesan;
        el.appendChild(s);
        wadah.appendChild(el);
        setTimeout(function () { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; }, jenis === 'galat' ? 6500 : 2600);
        setTimeout(function () { el.remove(); }, jenis === 'galat' ? 7000 : 3000);
    }

    // ------------------------------------------------------------------
    // Penyaring ketikan untuk <input class="isian">
    // ------------------------------------------------------------------
    function pasangIsian(akar) {
        (akar || document).querySelectorAll('input.isian').forEach(function (el) {
            if (el.dataset.ikpPasang) return;
            el.dataset.ikpPasang = '1';
            el.setAttribute('inputmode', 'decimal');
            el.setAttribute('autocomplete', 'off');
            el.addEventListener('input', function () {
                var awal = el.value, bersih = awal.replace(/[^\d.,%\-\s]/g, '');
                if (bersih !== awal) el.value = bersih;
                el.classList.toggle('salah', !sah(el.value));
            });
            el.addEventListener('blur', function () {
                // Target: nol = belum ada target -> dikosongkan agar jelas terlihat.
                if (el.dataset.nol !== 'sah' && !kosong(el.value) && sah(el.value) && baca(el.value, true) === null) el.value = '';
            });
        });
    }

    /** Konfirmasi memakai dialog bersama AKSARA (templates/konfirmasi.php) bila ada. */
    function tanya(pesan, judul) {
        if (w.Konfirmasi && w.Konfirmasi.tanya) {
            return w.Konfirmasi.tanya({ judul: judul || 'Timpa isian?', pesan: pesan, jenis: 'tanya', ya: 'Ya, lanjutkan' });
        }
        return Promise.resolve(w.confirm(pesan));
    }

    w.IkpAngka = {
        tanya: tanya,
        rapikan: rapikan, kosong: kosong, sah: sah, baca: baca, fmt: fmt, metodeSah: metodeSah,
        triwulan: triwulan, bagiRata: bagiRata, cek: cek, lencanaCek: lencanaCek,
        kirim: kirim, ambil: ambil, toast: toast, pasangIsian: pasangIsian
    };

    document.addEventListener('DOMContentLoaded', function () { pasangIsian(document); });
})(window);

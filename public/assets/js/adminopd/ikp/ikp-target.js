/*
 * Target satu IKP (app/Views/ikp/target.php).
 * Langkah 1: target tahunan vs target 5 tahun. Langkah 2: 12 bulan vs target
 * tahunan + TW otomatis. Cek & Bagi Rata memakai IkpAngka (cerminan
 * ikp_helper.php); hasil cek server di balasan simpan yang menjadi pegangan.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var akar = document.getElementById('ikp-target');
        if (!akar) return;
        var A = window.IkpAngka;
        var k = JSON.parse(akar.dataset.konfig || '{}');
        var kotor = { tahunan: false, bulanan: false };

        var inThn = Array.prototype.slice.call(akar.querySelectorAll('input[data-tahun]'));
        var inBln = Array.prototype.slice.call(akar.querySelectorAll('input[data-bulan]'));

        function nilai(el) { return A.baca(el.value, true); }
        function induKBulanan() {
            var el = document.getElementById('thn-' + k.tahun);
            return el ? nilai(el) : null;
        }
        function awalBulanan() {
            var sebelum = document.getElementById('thn-' + (k.tahun - 1));
            var v = sebelum ? nilai(sebelum) : null;
            return v !== null ? v : k.baseline;
        }

        function tulisCek(idLencana, idPesan, h) {
            var wadah = document.getElementById(idLencana);
            wadah.innerHTML = '';
            wadah.appendChild(A.lencanaCek(h));
            document.getElementById(idPesan).textContent = h.pesan;
        }

        function segarTahunan() {
            var anak = inThn.map(nilai);
            tulisCek('cek-tahunan', 'cek-tahunan-pesan', A.cek(k.metode, k.t5, anak));
            var induk = induKBulanan();
            document.getElementById('induk-bulanan').textContent = A.fmt(induk, 4);
            segarBulanan();
        }

        function segarBulanan() {
            var bulan = {};
            inBln.forEach(function (el) { bulan[el.dataset.bulan] = nilai(el); });
            var tw = A.triwulan(bulan, k.metode);
            for (var q = 1; q <= 4; q++) document.getElementById('tw-' + q).textContent = A.fmt(tw[q], 4);
            tulisCek('cek-bulanan', 'cek-bulanan-pesan', A.cek(k.metode, induKBulanan(), inBln.map(nilai)));
        }

        function tandaiKotor(jenis, el) {
            kotor[jenis] = true;
            if (el) el.classList.add('berubah');
        }
        inThn.forEach(function (el) {
            el.addEventListener('input', function () { tandaiKotor('tahunan', el); segarTahunan(); });
            el.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); simpan('tahunan'); } });
        });
        inBln.forEach(function (el) {
            el.addEventListener('input', function () { tandaiKotor('bulanan', el); segarBulanan(); });
            el.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); simpan('bulanan'); } });
        });

        var tanya = A.tanya;

        function isiDari(inputs, hasil, jenis) {
            inputs.forEach(function (el, i) {
                el.value = hasil[i] === undefined ? '' : A.fmt(hasil[i], 4);
                el.classList.remove('salah');
                tandaiKotor(jenis, el);
            });
        }

        var bT = document.getElementById('bagi-tahunan');
        if (bT) bT.addEventListener('click', function () {
            if (k.t5 === null) { A.toast('Target 5 tahun belum berupa angka.', 'galat'); return; }
            var hasil = A.bagiRata(k.t5, inThn.length, k.metode, k.baseline, k.bulat);
            if (!hasil.length) { A.toast('Pilih metode perhitungan dulu.', 'galat'); return; }
            var ada = inThn.some(function (el) { return !A.kosong(el.value); });
            (ada ? tanya('Isian target tahunan yang sudah ada akan diganti hasil Bagi Rata. Lanjutkan?') : Promise.resolve(true))
                .then(function (ya) { if (!ya) return; isiDari(inThn, hasil, 'tahunan'); segarTahunan(); });
        });

        var bB = document.getElementById('bagi-bulanan');
        if (bB) bB.addEventListener('click', function () {
            var induk = induKBulanan();
            if (induk === null) { A.toast('Isi target tahunan ' + k.tahun + ' dulu.', 'galat'); return; }
            var hasil = A.bagiRata(induk, 12, k.metode, awalBulanan(), k.bulat);
            if (!hasil.length) { A.toast('Pilih metode perhitungan dulu.', 'galat'); return; }
            var ada = inBln.some(function (el) { return !A.kosong(el.value); });
            (ada ? tanya('Isian target bulanan yang sudah ada akan diganti hasil Bagi Rata. Lanjutkan?') : Promise.resolve(true))
                .then(function (ya) { if (!ya) return; isiDari(inBln, hasil, 'bulanan'); segarBulanan(); });
        });

        function simpan(jenis) {
            var inputs = jenis === 'tahunan' ? inThn : inBln;
            var salah = inputs.filter(function (el) { return !A.sah(el.value); });
            if (salah.length) {
                salah.forEach(function (el) { el.classList.add('salah'); });
                salah[0].focus();
                A.toast('Masih ada isian yang bukan angka.', 'galat');
                return;
            }
            var payload = {};
            if (jenis === 'tahunan') {
                payload.tahunan = {};
                inThn.forEach(function (el) { payload.tahunan[el.dataset.tahun] = el.value; });
            } else {
                payload.tahun = k.tahun;
                payload.bulanan = {};
                inBln.forEach(function (el) { payload.bulanan[el.dataset.bulan] = el.value; });
            }
            var tombol = document.getElementById('simpan-' + jenis);
            if (tombol) { tombol.disabled = true; }
            A.kirim(k.urlSimpan, payload).then(function (d) {
                if (jenis === 'tahunan') {
                    inThn.forEach(function (el) {
                        el.value = d.tahunan[el.dataset.tahun] !== undefined ? d.tahunan[el.dataset.tahun] : el.value;
                        el.classList.remove('berubah');
                    });
                    tulisCek('cek-tahunan', 'cek-tahunan-pesan', d.cek_tahunan);
                    document.getElementById('induk-bulanan').textContent = A.fmt(induKBulanan(), 4);
                    segarBulanan();
                } else if (d.baris) {
                    inBln.forEach(function (el) {
                        el.value = d.baris.bulan[el.dataset.bulan].target;
                        el.classList.remove('berubah');
                    });
                    for (var q = 1; q <= 4; q++) document.getElementById('tw-' + q).textContent = d.baris.triwulan[q].target;
                    tulisCek('cek-bulanan', 'cek-bulanan-pesan', d.baris.cek_bulanan);
                }
                kotor[jenis] = false;
                A.toast(d.pesan);
            }).catch(function (e) {
                A.toast(e.message, 'galat');
            }).then(function () { if (tombol) tombol.disabled = false; });
        }
        ['tahunan', 'bulanan'].forEach(function (j) {
            var t = document.getElementById('simpan-' + j);
            if (t) t.addEventListener('click', function () { simpan(j); });
        });

        // Jangan hilangkan isian yang belum disimpan saat pindah halaman / tahun.
        window.addEventListener('beforeunload', function (e) {
            if (kotor.tahunan || kotor.bulanan) { e.preventDefault(); e.returnValue = ''; }
        });

        segarTahunan();
    });
})();

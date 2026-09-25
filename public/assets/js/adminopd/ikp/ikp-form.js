/*
 * Form tambah/ubah IKP (app/Views/ikp/form.php).
 *   - kategori penugasan_* -> tampilkan "Dasar penugasan"
 *   - saran Buku Saku (GET ikp/buku-saku?q=, ≥ 3 huruf) -> isi kolom + buku_saku_id
 *   - satuan: daftar master atau tulis sendiri
 *   - penjelasan metode
 *   - simpul pohon kinerja -> indikator (GET ikp/node)
 *   - penanggung jawab: Select2 AJAX (GET ikp/pegawai?q=) -> jabatan otomatis
 * Semua pilihan divalidasi ulang di server; skrip ini hanya kemudahan.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('form-ikp');
        if (!form) return;
        var cfg = window.IKP_FORM || { metodeJelas: {}, satuan: [] };
        var $ = window.jQuery;
        var A = window.IkpAngka;

        function el(id) { return document.getElementById(id); }
        function setNilai(id, nilai) { var e = el(id); if (e && nilai !== undefined && nilai !== null) e.value = nilai; }

        // ---------------- Kategori ----------------
        function kategori() {
            var r = form.querySelector('input[name="kategori"]:checked');
            return r ? r.value : '';
        }
        function segarKategori() {
            var k = kategori();
            el('blok-dasar').hidden = k.indexOf('penugasan_') !== 0;
            el('label-pu').classList.toggle('wajib', k === 'program_unggulan');
        }
        form.querySelectorAll('input[name="kategori"]').forEach(function (r) { r.addEventListener('change', segarKategori); });
        segarKategori();

        // ---------------- Metode ----------------
        var metode = el('metode');
        function segarMetode() {
            var j = cfg.metodeJelas[metode.value];
            var kotak = el('metode-ket');
            kotak.innerHTML = '';
            if (!j) {
                kotak.textContent = 'Metode menentukan cara bulan dijumlah menjadi triwulan & tahun, serta cara capaian dihitung.';
                return;
            }
            var b = document.createElement('strong'); b.textContent = j.judul + ': ';
            kotak.appendChild(b);
            kotak.appendChild(document.createTextNode(j.isi));
            var c = document.createElement('div'); c.className = 'text-secondary mt-1';
            c.textContent = 'Contoh: ' + j.contoh + '.';
            kotak.appendChild(c);
        }
        metode.addEventListener('change', segarMetode);
        segarMetode();

        // ---------------- Satuan ----------------
        var satSel = el('satuan_id'), blokTeks = el('blok-satuan-teks'), tombolTeks = el('tombol-satuan-teks');
        if ($ && $.fn.select2) {
            $(satSel).select2({ theme: 'bootstrap-5', width: '100%', placeholder: '— pilih satuan —', allowClear: true });
        }
        function modeTeks(aktif) {
            blokTeks.hidden = !aktif;
            tombolTeks.textContent = aktif ? 'Pilih dari daftar satuan' : 'Satuan tidak ada di daftar? Tulis sendiri';
            if (aktif) {
                if ($ && $.fn.select2) $(satSel).val('').trigger('change'); else satSel.value = '';
                el('satuan_teks').focus();
            } else {
                el('satuan_teks').value = '';
            }
        }
        tombolTeks.addEventListener('click', function () { modeTeks(blokTeks.hidden); });
        function pilihSatuan(nama, id) {
            if (id) {
                blokTeks.hidden = true; el('satuan_teks').value = '';
                tombolTeks.textContent = 'Satuan tidak ada di daftar? Tulis sendiri';
                if ($ && $.fn.select2) $(satSel).val(String(id)).trigger('change'); else satSel.value = String(id);
            } else if (nama) {
                modeTeks(true);
                el('satuan_teks').value = nama;
            }
        }

        // ---------------- Buku Saku ----------------
        var ta = el('output_prioritas'), kotakSaran = el('saran-bs'), tunda = null, hasil = [], fokus = -1, terakhirQ = '';
        function tutupSaran() { kotakSaran.hidden = true; kotakSaran.innerHTML = ''; fokus = -1; }
        function tampilSaran(items) {
            hasil = items; kotakSaran.innerHTML = ''; fokus = -1;
            if (!items.length) { tutupSaran(); return; }
            var kepala = document.createElement('div');
            kepala.className = 'px-3 py-2 small text-secondary border-bottom';
            kepala.textContent = 'Saran dari Buku Saku Program Unggulan (' + items.length + ')';
            kotakSaran.appendChild(kepala);
            items.forEach(function (it, i) {
                var b = document.createElement('button');
                b.type = 'button'; b.setAttribute('role', 'option'); b.dataset.i = i;
                var o = document.createElement('div'); o.className = 'o'; o.textContent = it.output_prioritas;
                var m = document.createElement('div'); m.className = 'm';
                m.textContent = [it.pu_nama || 'Tanpa Program Unggulan', it.program_opd, (it.target_5_tahun || it.target_5_tahun_teks || '') + ' ' + (it.satuan || '')]
                    .filter(function (x) { return x && String(x).trim() !== ''; }).join(' · ');
                b.appendChild(o); b.appendChild(m);
                b.addEventListener('mousedown', function (e) { e.preventDefault(); pakai(i); });
                kotakSaran.appendChild(b);
            });
            kotakSaran.hidden = false;
        }
        function pakai(i) {
            var it = hasil[i];
            if (!it) return;
            ta.value = it.output_prioritas || ta.value;
            setNilai('indikator_outcome', it.indikator);
            setNilai('outcome', it.outcome);
            setNilai('program_opd', it.program_opd);
            setNilai('bidang_urusan', it.bidang_urusan);
            if (it.sasaran_pembangunan_id) setNilai('sasaran_pembangunan_id', String(it.sasaran_pembangunan_id));
            if (it.rpjmd_misi_id) setNilai('rpjmd_misi_id', String(it.rpjmd_misi_id));
            var pu = form.querySelector('input[name="program_unggulan_id"][value="' + (it.program_unggulan_id || '') + '"]');
            if (pu) pu.checked = true;
            pilihSatuan(it.satuan, it.satuan_id);
            if (it.target_5_tahun) { setNilai('target_5_tahun', it.target_5_tahun); setNilai('target_5_tahun_teks', ''); }
            else if (it.target_5_tahun_teks) { setNilai('target_5_tahun_teks', it.target_5_tahun_teks); }
            el('buku_saku_id').value = it.id;
            el('bs-tanda-teks').textContent = 'Terhubung ke Buku Saku #' + it.id;
            el('bs-tanda').hidden = false;
            tutupSaran();
            A.toast('Kolom diisi dari Buku Saku. Periksa kembali sebelum menyimpan.');
        }
        ta.addEventListener('input', function () {
            clearTimeout(tunda);
            var q = ta.value.trim();
            if (q.length < 3) { tutupSaran(); return; }
            tunda = setTimeout(function () {
                if (q === terakhirQ && !kotakSaran.hidden) return;
                terakhirQ = q;
                var url = form.dataset.urlBukuSaku + (form.dataset.urlBukuSaku.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(q.slice(0, 100));
                A.ambil(url).then(function (d) {
                    if (ta.value.trim() === q) tampilSaran(d.items || []);
                }).catch(function () { tutupSaran(); });
            }, 300);
        });
        ta.addEventListener('keydown', function (e) {
            if (kotakSaran.hidden) return;
            var tombol = kotakSaran.querySelectorAll('button');
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                fokus = e.key === 'ArrowDown' ? Math.min(tombol.length - 1, fokus + 1) : Math.max(0, fokus - 1);
                tombol.forEach(function (b, i) { b.classList.toggle('fokus', i === fokus); });
                if (tombol[fokus]) tombol[fokus].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && fokus >= 0) {
                e.preventDefault(); pakai(fokus);
            } else if (e.key === 'Escape') {
                tutupSaran();
            }
        });
        ta.addEventListener('blur', function () { setTimeout(tutupSaran, 150); });
        el('bs-lepas').addEventListener('click', function () {
            el('buku_saku_id').value = '';
            el('bs-tanda').hidden = true;
        });

        // ---------------- Simpul pohon kinerja ----------------
        var selSimpul = el('cascading_sasaran_id'), selInd = el('cascading_indikator_id');
        var simpul = [];
        function isiIndikator(idSimpul, pilih) {
            selInd.innerHTML = '';
            var n = simpul.filter(function (s) { return String(s.id) === String(idSimpul); })[0];
            var o0 = document.createElement('option');
            if (!n) { o0.value = ''; o0.textContent = '— pilih simpul dulu —'; selInd.appendChild(o0); selInd.disabled = true; return; }
            o0.value = ''; o0.textContent = n.indikator.length ? '— (opsional) pilih indikator —' : 'Simpul ini belum punya indikator';
            selInd.appendChild(o0);
            n.indikator.forEach(function (i) {
                var o = document.createElement('option');
                o.value = i.id; o.textContent = i.indikator + (i.satuan ? ' (' + i.satuan + ')' : '');
                if (String(i.id) === String(pilih)) o.selected = true;
                selInd.appendChild(o);
            });
            selInd.disabled = !n.indikator.length;
        }
        A.ambil(form.dataset.urlNode).then(function (d) {
            simpul = d.nodes || [];
            selSimpul.innerHTML = '';
            var o0 = document.createElement('option');
            o0.value = ''; o0.textContent = simpul.length ? '— tidak ditautkan —' : 'Pohon kinerja OPD ini belum berisi simpul';
            selSimpul.appendChild(o0);
            var grup = {};
            simpul.forEach(function (s) {
                if (!grup[s.level_label]) {
                    grup[s.level_label] = document.createElement('optgroup');
                    grup[s.level_label].label = s.level_label;
                    selSimpul.appendChild(grup[s.level_label]);
                }
                var o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.sasaran.length > 140 ? s.sasaran.slice(0, 137) + '…' : s.sasaran;
                if (String(s.id) === String(form.dataset.simpul)) o.selected = true;
                grup[s.level_label].appendChild(o);
            });
            isiIndikator(form.dataset.simpul, form.dataset.indikator);
            if ($ && $.fn.select2) {
                $(selSimpul).select2({ theme: 'bootstrap-5', width: '100%' }).on('change', function () { isiIndikator(selSimpul.value, ''); });
            } else {
                selSimpul.addEventListener('change', function () { isiIndikator(selSimpul.value, ''); });
            }
        }).catch(function (e) {
            selSimpul.innerHTML = '<option value="">Gagal memuat simpul</option>';
            A.toast(e.message, 'galat');
        });

        // ---------------- Penanggung jawab ----------------
        if ($ && $.fn.select2) {
            $('#pj_pegawai_id').select2({
                theme: 'bootstrap-5', width: '100%', allowClear: true,
                placeholder: 'Cari nama atau jabatan…',
                minimumInputLength: 0,
                ajax: {
                    url: form.dataset.urlPegawai, dataType: 'json', delay: 250,
                    data: function (p) { return { q: p.term || '' }; },
                    processResults: function (d) { return { results: d.results || [] }; }
                },
                language: {
                    noResults: function () { return 'Pegawai tidak ditemukan'; },
                    searching: function () { return 'Mencari…'; },
                    errorLoading: function () { return 'Gagal memuat daftar pegawai'; }
                }
            }).on('select2:select', function (e) {
                var d = e.params.data || {};
                if (d.jabatan) el('pj_jabatan_teks').value = d.jabatan;
            }).on('select2:clear', function () {
                el('pj_jabatan_teks').value = '';
            });
        }

        // ---------------- Kirim ----------------
        form.addEventListener('submit', function (e) {
            var salah = [];
            if (!kategori()) salah.push('Pilih kategori IKP.');
            if (!ta.value.trim()) salah.push('Indikator IKP wajib diisi.');
            if (!satSel.value && !el('satuan_teks').value.trim()) salah.push('Satuan wajib diisi.');
            if (!metode.value) salah.push('Pilih metode perhitungan.');
            ['baseline', 'target_5_tahun'].forEach(function (id) {
                if (!A.sah(el(id).value)) salah.push((id === 'baseline' ? 'Baseline' : 'Target 5 tahun') + ' harus berupa angka.');
            });
            if (!el('target_5_tahun').value.trim() && !el('target_5_tahun_teks').value.trim()) salah.push('Isi target 5 tahun (angka atau uraian).');
            if (kategori() === 'program_unggulan' && !form.querySelector('input[name="program_unggulan_id"]:checked:not([value=""])')) {
                salah.push('Pilih salah satu Program Unggulan Bupati.');
            }
            if (salah.length) {
                e.preventDefault();
                A.toast(salah.join(' '), 'galat');
            }
        });
    });
})();

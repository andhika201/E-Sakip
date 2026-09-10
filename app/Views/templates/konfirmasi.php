<?php

/**
 * DIALOG KONFIRMASI BERSAMA — satu-satunya penjaga tindakan merusak.
 *
 * =====================================================================
 * MENGAPA ADA
 *
 * `confirm()` bawaan peramban menempel di tepi atas jendela, tidak bisa
 * diberi nama data yang akan dihapus, tidak bisa merinci apa saja yang
 * ikut terhapus, dan pada beberapa peramban bisa dibungkam pengguna
 * ("jangan tampilkan lagi") — sehingga penghapusan berjalan tanpa
 * pertanyaan sama sekali. Berkas ini menggantinya dengan satu dialog
 * milik aplikasi yang seragam di semua modul.
 *
 * Berkas ini disisipkan SEKALI lewat layout/footer.php, yang di-include
 * oleh keempat footer role (adminKabupaten, adminOpd, templates, user),
 * jadi setiap halaman interaktif sudah memilikinya tanpa perlu diubah.
 *
 * Tidak bergantung pada Bootstrap JS maupun jQuery: footer role memuat
 * kedua pustaka itu SETELAH include ini, jadi dialog harus berdiri
 * sendiri. Simpulnya dibangun di JS dan ditempelkan ke <body> supaya
 * tidak terperangkap `transform`/`overflow` milik pembungkus halaman.
 *
 * =====================================================================
 * CARA PAKAI — 1. DEKLARATIF (dianjurkan)
 *
 * Pada <form>, <a>, atau <button>:
 *
 *   <form method="post" action="..."
 *         data-konfirmasi="Pengguna ini akan dihapus permanen."
 *         data-konfirmasi-judul="Hapus Pengguna"
 *         data-konfirmasi-nama="Budi Santoso"
 *         data-konfirmasi-rincian="3 Perjanjian Kinerja|12 indikator"
 *         data-konfirmasi-ketik="HAPUS">
 *
 * Atribut yang dikenali:
 *   data-konfirmasi           pesan utama (wajib, ini yang mengaktifkan)
 *   data-konfirmasi-judul     judul dialog          (bawaan: "Konfirmasi Hapus")
 *   data-konfirmasi-nama      nama objek, tampil menonjol di kotak
 *   data-konfirmasi-rincian   daftar dampak, dipisah "|"
 *   data-konfirmasi-jenis     hapus | peringatan | tanya  (bawaan: hapus)
 *   data-konfirmasi-ketik     kata yang harus diketik ulang dulu
 *   data-konfirmasi-ya        label tombol setuju   (bawaan ikut jenis)
 *   data-konfirmasi-tidak     label tombol batal    (bawaan: "Batal")
 *   data-konfirmasi-permanen  "0" untuk menyembunyikan garis "tidak bisa
 *                             dibatalkan" (bawaan: tampil bila jenis=hapus)
 *
 * CARA PAKAI — 2. PROGRAMATIS (untuk alur AJAX)
 *
 *   Konfirmasi.hapus({ nama: 'PK 2025', rincian: ['4 sasaran'] })
 *       .then(function (ya) { if (ya) { ...kirim fetch... } });
 *
 *   Konfirmasi.tanya({ judul, pesan, jenis, ya, tidak, ketik, ... })
 *       -> Promise<boolean>
 *
 * =====================================================================
 * JARING PENGAMAN
 *
 * Selain yang ditandai eksplisit, setiap <form> yang action-nya
 * mengandung delete/hapus/destroy/purge ikut dijaga dengan pesan umum,
 * walau lupa diberi atribut. Jadi modul baru yang menyusul tetap
 * bertanya lebih dulu. Untuk mematikannya pada satu form tertentu:
 * tambahkan data-konfirmasi-lewati.
 */

// Cukup sekali per permintaan, walau footer ter-include berlapis.
if (defined('ESAKIP_KONFIRMASI_TERPASANG')) {
    return;
}
define('ESAKIP_KONFIRMASI_TERPASANG', true);
?>
<style>
    .eskf-tirai {
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(20, 32, 26, .55);
        -webkit-backdrop-filter: blur(3px);
        backdrop-filter: blur(3px);
        opacity: 0;
        transition: opacity .16s ease;
    }

    .eskf-tirai.eskf-tampil { opacity: 1; }

    .eskf-kartu {
        width: 100%;
        max-width: 468px;
        max-height: calc(100vh - 40px);
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 64px rgba(11, 26, 18, .28), 0 2px 8px rgba(11, 26, 18, .12);
        font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: #24302a;
        transform: translateY(10px) scale(.98);
        transition: transform .18s cubic-bezier(.2, .8, .3, 1);
    }

    .eskf-tirai.eskf-tampil .eskf-kartu { transform: none; }

    .eskf-kepala {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 22px 22px 16px;
    }

    .eskf-lencana {
        flex: 0 0 auto;
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        font-size: 1.15rem;
    }

    .eskf-kepala-teks { flex: 1 1 auto; min-width: 0; }

    .eskf-judul {
        margin: 2px 0 3px;
        font-size: 1.06rem;
        font-weight: 700;
        line-height: 1.3;
        letter-spacing: -.01em;
    }

    .eskf-permanen {
        margin: 0;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .01em;
    }

    .eskf-tutup {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        margin: -4px -4px 0 0;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: #8b988f;
        font-size: 1rem;
        line-height: 1;
        cursor: pointer;
        transition: background .12s ease, color .12s ease;
    }

    .eskf-tutup:hover { background: #f1f4f2; color: #24302a; }

    .eskf-badan {
        padding: 0 22px 4px;
        overflow-y: auto;
    }

    .eskf-pesan {
        margin: 0;
        font-size: .9rem;
        line-height: 1.6;
        color: #47554c;
        white-space: pre-line;
    }

    .eskf-nama {
        margin-top: 13px;
        padding: 11px 14px;
        border: 1px solid #e2e8e4;
        border-left: 3px solid #b0bbb4;
        border-radius: 10px;
        background: #f7f9f8;
        font-size: .92rem;
        font-weight: 650;
        line-height: 1.45;
        color: #24302a;
        overflow-wrap: anywhere;
    }

    .eskf-rincian {
        margin: 14px 0 0;
        padding: 12px 14px;
        border-radius: 10px;
        background: #fdf6f4;
        border: 1px solid #f6ded6;
    }

    .eskf-rincian-judul {
        margin: 0 0 7px;
        font-size: .73rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #a5442c;
    }

    .eskf-rincian ul {
        margin: 0;
        padding-left: 17px;
        font-size: .855rem;
        line-height: 1.65;
        color: #6b4438;
    }

    .eskf-ketik { margin-top: 15px; }

    .eskf-ketik label {
        display: block;
        margin-bottom: 6px;
        font-size: .82rem;
        color: #47554c;
    }

    .eskf-ketik label b {
        padding: 1px 6px;
        border-radius: 5px;
        background: #eef2f0;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .84em;
        letter-spacing: .04em;
        color: #24302a;
    }

    .eskf-ketik input {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #d6ded9;
        border-radius: 9px;
        font-size: .9rem;
        font-family: inherit;
        color: #24302a;
        outline: 0;
        transition: border-color .12s ease, box-shadow .12s ease;
    }

    .eskf-ketik input:focus {
        border-color: #c0392b;
        box-shadow: 0 0 0 3px rgba(192, 57, 43, .13);
    }

    .eskf-kaki {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 18px 22px 20px;
    }

    .eskf-btn {
        min-width: 96px;
        padding: 9px 17px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-family: inherit;
        font-size: .875rem;
        font-weight: 600;
        line-height: 1.4;
        cursor: pointer;
        transition: background .12s ease, border-color .12s ease, color .12s ease, box-shadow .12s ease;
    }

    .eskf-btn:focus-visible { outline: 0; box-shadow: 0 0 0 3px rgba(0, 116, 62, .28); }
    .eskf-btn:disabled { opacity: .6; cursor: not-allowed; }

    .eskf-btn-batal {
        background: #fff;
        border-color: #d6ded9;
        color: #3d4a42;
    }

    .eskf-btn-batal:hover:not(:disabled) { background: #f3f6f4; border-color: #b9c5be; }

    .eskf-btn-ya { color: #fff; }
    .eskf-btn-ya:focus-visible { box-shadow: 0 0 0 3px rgba(192, 57, 43, .3); }

    /* --- nada per jenis --- */
    .eskf-hapus .eskf-lencana { background: #fdecea; color: #c0392b; }
    .eskf-hapus .eskf-permanen { color: #c0392b; }
    .eskf-hapus .eskf-btn-ya { background: #c0392b; border-color: #c0392b; }
    .eskf-hapus .eskf-btn-ya:hover:not(:disabled) { background: #a5301f; border-color: #a5301f; }

    .eskf-peringatan .eskf-lencana { background: #fff5e2; color: #b06e00; }
    .eskf-peringatan .eskf-permanen { color: #b06e00; }
    .eskf-peringatan .eskf-btn-ya { background: #b8760a; border-color: #b8760a; }
    .eskf-peringatan .eskf-btn-ya:hover:not(:disabled) { background: #9a6208; border-color: #9a6208; }
    .eskf-peringatan .eskf-btn-ya:focus-visible { box-shadow: 0 0 0 3px rgba(184, 118, 10, .3); }
    .eskf-peringatan .eskf-rincian { background: #fffaf0; border-color: #f6e6c8; }
    .eskf-peringatan .eskf-rincian-judul { color: #9a6208; }
    .eskf-peringatan .eskf-rincian ul { color: #6b5323; }
    .eskf-peringatan .eskf-ketik input:focus { border-color: #b8760a; box-shadow: 0 0 0 3px rgba(184, 118, 10, .13); }

    .eskf-tanya .eskf-lencana { background: #e9f4ed; color: #00743e; }
    .eskf-tanya .eskf-permanen { color: #4b7a5f; }
    .eskf-tanya .eskf-btn-ya { background: #00743e; border-color: #00743e; }
    .eskf-tanya .eskf-btn-ya:hover:not(:disabled) { background: #005a30; border-color: #005a30; }
    .eskf-tanya .eskf-btn-ya:focus-visible { box-shadow: 0 0 0 3px rgba(0, 116, 62, .3); }
    .eskf-tanya .eskf-rincian { background: #f4f8f5; border-color: #dde8e1; }
    .eskf-tanya .eskf-rincian-judul { color: #00743e; }
    .eskf-tanya .eskf-rincian ul { color: #46564c; }
    .eskf-tanya .eskf-ketik input:focus { border-color: #00743e; box-shadow: 0 0 0 3px rgba(0, 116, 62, .13); }

    /* putaran tunggu pada tombol setuju setelah ditekan */
    .eskf-putar {
        display: inline-block;
        width: 13px;
        height: 13px;
        margin-right: 7px;
        vertical-align: -2px;
        border: 2px solid rgba(255, 255, 255, .45);
        border-top-color: #fff;
        border-radius: 50%;
        animation: eskf-putar .6s linear infinite;
    }

    @keyframes eskf-putar { to { transform: rotate(360deg); } }

    @media (max-width: 480px) {
        .eskf-tirai { padding: 12px; align-items: flex-end; }
        .eskf-kartu { max-width: none; border-radius: 16px; }
        .eskf-kaki { flex-direction: column-reverse; }
        .eskf-btn { width: 100%; }
    }

    @media (prefers-reduced-motion: reduce) {
        .eskf-tirai, .eskf-kartu { transition: none; }
        .eskf-putar { animation-duration: 1.6s; }
    }

    /* Halaman cetak tidak boleh membawa sisa dialog. */
    @media print { .eskf-tirai { display: none !important; } }
</style>
<script>
    (function (window, document) {
        'use strict';

        if (window.Konfirmasi) { return; } // sudah terpasang

        // Ikon digambar sebagai SVG sebaris: tidak bergantung Font Awesome,
        // yang di layout publik dimuat lewat kit lawas dan bisa saja absen.
        var IKON = {
            hapus: '<svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4.5A1.5 1.5 0 0 1 9.5 3h5A1.5 1.5 0 0 1 16 4.5V6"/><path d="M18.5 6l-.8 13.1a2 2 0 0 1-2 1.9H8.3a2 2 0 0 1-2-1.9L5.5 6"/><path d="M10 10.5v6M14 10.5v6"/></svg>',
            peringatan: '<svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.9 18.3A2 2 0 0 0 3.6 21.3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4.6"/><path d="M12 17.3h.01"/></svg>',
            tanya: '<svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.2"/><path d="M9.4 9.3a2.7 2.7 0 0 1 5.2.9c0 1.8-2.6 2.7-2.6 2.7"/><path d="M12 16.8h.01"/></svg>'
        };
        var YA_BAWAAN = { hapus: 'Ya, Hapus', peringatan: 'Lanjutkan', tanya: 'Ya, Lanjutkan' };
        var JUDUL_BAWAAN = { hapus: 'Konfirmasi Hapus', peringatan: 'Perlu Perhatian', tanya: 'Konfirmasi' };

        // Rute yang bersifat menghapus. Dipakai jaring pengaman di bawah.
        var POLA_HAPUS = /(?:^|[\/_-])(?:delete|hapus|destroy|purge)(?:[\/_-]|\?|$)/i;

        var simpul = null;   // simpul dialog, dibuat sekali saat pertama dipakai
        var aktif = null;    // { selesai, elemenAsal } saat dialog terbuka

        function buat() {
            var tirai = document.createElement('div');
            tirai.className = 'eskf-tirai';
            tirai.setAttribute('role', 'dialog');
            tirai.setAttribute('aria-modal', 'true');
            tirai.setAttribute('aria-labelledby', 'eskf-judul');
            tirai.hidden = true;
            tirai.innerHTML =
                '<div class="eskf-kartu" role="document">'
                + '<div class="eskf-kepala">'
                + '<div class="eskf-lencana" aria-hidden="true"></div>'
                + '<div class="eskf-kepala-teks">'
                + '<h2 class="eskf-judul" id="eskf-judul"></h2>'
                + '<p class="eskf-permanen"></p>'
                + '</div>'
                + '<button type="button" class="eskf-tutup" aria-label="Tutup">&times;</button>'
                + '</div>'
                + '<div class="eskf-badan">'
                + '<p class="eskf-pesan"></p>'
                + '<div class="eskf-nama"></div>'
                + '<div class="eskf-rincian">'
                + '<p class="eskf-rincian-judul">Ikut terhapus</p>'
                + '<ul></ul>'
                + '</div>'
                + '<div class="eskf-ketik">'
                + '<label for="eskf-ketik-isian"></label>'
                + '<input type="text" id="eskf-ketik-isian" autocomplete="off" autocapitalize="characters" spellcheck="false">'
                + '</div>'
                + '</div>'
                + '<div class="eskf-kaki">'
                + '<button type="button" class="eskf-btn eskf-btn-batal"></button>'
                + '<button type="button" class="eskf-btn eskf-btn-ya"></button>'
                + '</div>'
                + '</div>';
            document.body.appendChild(tirai);

            var s = {
                tirai: tirai,
                kartu: tirai.querySelector('.eskf-kartu'),
                ikon: tirai.querySelector('.eskf-lencana'),
                judul: tirai.querySelector('.eskf-judul'),
                permanen: tirai.querySelector('.eskf-permanen'),
                pesan: tirai.querySelector('.eskf-pesan'),
                nama: tirai.querySelector('.eskf-nama'),
                rincian: tirai.querySelector('.eskf-rincian'),
                rincianList: tirai.querySelector('.eskf-rincian ul'),
                rincianJudul: tirai.querySelector('.eskf-rincian-judul'),
                ketik: tirai.querySelector('.eskf-ketik'),
                ketikLabel: tirai.querySelector('.eskf-ketik label'),
                ketikIsian: tirai.querySelector('.eskf-ketik input'),
                tutup: tirai.querySelector('.eskf-tutup'),
                batal: tirai.querySelector('.eskf-btn-batal'),
                ya: tirai.querySelector('.eskf-btn-ya')
            };

            s.tutup.addEventListener('click', function () { tutup(false); });
            s.batal.addEventListener('click', function () { tutup(false); });
            s.ya.addEventListener('click', function () { setuju(); });
            tirai.addEventListener('mousedown', function (e) {
                if (e.target === tirai) { tutup(false); }
            });

            // Esc membatalkan; Enter menyetujui (kecuali fokus di tombol batal).
            //
            // Dipasang di DOCUMENT, bukan di tirai: kalau fokus kebetulan masih
            // di luar dialog — mis. tombol asalnya lenyap saat tabel disegarkan —
            // Esc yang hanya didengar tirai tidak akan pernah sampai, dan
            // dialognya terkunci terbuka.
            document.addEventListener('keydown', function (e) {
                if (!aktif) { return; }
                if (e.key === 'Escape') { e.preventDefault(); tutup(false); return; }
                if (e.key === 'Enter' && document.activeElement !== s.batal && document.activeElement !== s.tutup) {
                    e.preventDefault();
                    if (!s.ya.disabled) { setuju(); }
                    return;
                }
                if (e.key !== 'Tab') { return; }
                // Kurung fokus: Tab tidak boleh keluar dari dialog.
                var bisa = Array.prototype.filter.call(
                    s.kartu.querySelectorAll('button, input, [href], select, textarea, [tabindex]:not([tabindex="-1"])'),
                    function (el) { return !el.disabled && el.offsetParent !== null; }
                );
                if (!bisa.length) { return; }
                var awal = bisa[0];
                var akhir = bisa[bisa.length - 1];
                if (!s.kartu.contains(document.activeElement)) {
                    // Fokus terlanjur di luar: tarik masuk lagi.
                    e.preventDefault();
                    awal.focus();
                    return;
                }
                if (e.shiftKey && document.activeElement === awal) { e.preventDefault(); akhir.focus(); }
                else if (!e.shiftKey && document.activeElement === akhir) { e.preventDefault(); awal.focus(); }
            }, true);

            s.ketikIsian.addEventListener('input', function () {
                var wajib = s.ketikIsian.getAttribute('data-wajib') || '';
                s.ya.disabled = s.ketikIsian.value.trim().toUpperCase() !== wajib.toUpperCase();
            });

            return s;
        }

        function setuju() {
            if (!aktif) { return; }
            // Kunci tombol supaya klik ganda tidak mengirim dua permintaan hapus.
            simpul.ya.disabled = true;
            simpul.batal.disabled = true;
            simpul.ketikIsian.disabled = true;
            simpul.ya.innerHTML = '<span class="eskf-putar"></span>' + simpul.ya.getAttribute('data-label-tunggu');
            var selesai = aktif.selesai;
            aktif = null;
            sembunyikan();
            selesai(true);
        }

        function tutup(hasil) {
            if (!aktif) { return; }
            var selesai = aktif.selesai;
            var asal = aktif.elemenAsal;
            aktif = null;
            sembunyikan();
            if (asal && typeof asal.focus === 'function') { asal.focus(); }
            selesai(!!hasil);
        }

        function sembunyikan() {
            simpul.tirai.classList.remove('eskf-tampil');
            document.documentElement.style.overflow = '';
            window.setTimeout(function () {
                if (!aktif) { simpul.tirai.hidden = true; }
            }, 180);
        }

        /**
         * Menampilkan dialog. Mengembalikan Promise<boolean>.
         *
         * @param {Object} o  judul, pesan, nama, rincian[], jenis, ya, tidak,
         *                    ketik, permanen (bool), rincianJudul, elemenAsal
         */
        function tanya(o) {
            o = o || {};
            if (!simpul) { simpul = buat(); }

            // Dialog sebelumnya (kalau ada) dianggap dibatalkan.
            if (aktif) { tutup(false); }

            var jenis = ({ hapus: 1, peringatan: 1, tanya: 1 })[o.jenis] ? o.jenis : 'hapus';
            var s = simpul;

            s.kartu.className = 'eskf-kartu eskf-' + jenis;
            s.ikon.innerHTML = IKON[jenis];

            s.judul.textContent = o.judul || JUDUL_BAWAAN[jenis];

            var permanen = o.permanen === undefined ? (jenis === 'hapus') : !!o.permanen;
            s.permanen.textContent = permanen ? 'Tindakan ini tidak dapat dibatalkan.' : '';
            s.permanen.hidden = !permanen;

            s.pesan.textContent = o.pesan || 'Apakah Anda yakin ingin melanjutkan?';
            s.pesan.hidden = !s.pesan.textContent;

            var nama = (o.nama || '').toString().trim();
            s.nama.textContent = nama;
            s.nama.hidden = !nama;

            var rincian = (o.rincian || []).filter(function (r) { return String(r).trim() !== ''; });
            s.rincianList.textContent = '';
            rincian.forEach(function (r) {
                var li = document.createElement('li');
                li.textContent = String(r).trim();
                s.rincianList.appendChild(li);
            });
            s.rincianJudul.textContent = o.rincianJudul || (jenis === 'hapus' ? 'Ikut terhapus' : 'Yang akan terjadi');
            s.rincian.hidden = rincian.length === 0;

            var ketik = (o.ketik || '').toString().trim();
            s.ketik.hidden = !ketik;
            s.ketikIsian.value = '';
            s.ketikIsian.disabled = false;
            s.ketikIsian.setAttribute('data-wajib', ketik);
            if (ketik) {
                s.ketikLabel.textContent = 'Ketik ';
                var b = document.createElement('b');
                b.textContent = ketik;
                s.ketikLabel.appendChild(b);
                s.ketikLabel.appendChild(document.createTextNode(' untuk melanjutkan'));
            }

            var labelYa = o.ya || YA_BAWAAN[jenis];
            s.ya.textContent = labelYa;
            s.ya.setAttribute('data-label-tunggu', o.tunggu || 'Memproses…');
            s.ya.disabled = !!ketik;
            s.batal.disabled = false;
            s.batal.textContent = o.tidak || 'Batal';

            s.tirai.hidden = false;
            document.documentElement.style.overflow = 'hidden';
            // Paksa reflow agar transisi masuk benar-benar berjalan.
            void s.tirai.offsetWidth;
            s.tirai.classList.add('eskf-tampil');

            // Fokus awal sengaja di jalur AMAN: isian ketik bila ada, selain itu
            // tombol Batal — supaya Enter refleks tidak langsung menghapus.
            // Dipanggil langsung (bukan lewat setTimeout) agar Esc dan Tab sudah
            // berlaku sejak embusan pertama, bukan beberapa milidetik kemudian.
            (ketik ? s.ketikIsian : s.batal).focus();

            return new Promise(function (resolve) {
                aktif = { selesai: resolve, elemenAsal: o.elemenAsal || document.activeElement };
            });
        }

        /* ================= pembacaan atribut data-konfirmasi ================= */

        function dariAtribut(el) {
            var d = el.dataset;
            return {
                pesan: d.konfirmasi,
                judul: d.konfirmasiJudul,
                nama: d.konfirmasiNama,
                rincian: (d.konfirmasiRincian || '').split('|'),
                rincianJudul: d.konfirmasiRincianJudul,
                jenis: d.konfirmasiJenis,
                ketik: d.konfirmasiKetik,
                ya: d.konfirmasiYa,
                tidak: d.konfirmasiTidak,
                permanen: d.konfirmasiPermanen === undefined ? undefined : d.konfirmasiPermanen !== '0',
                elemenAsal: el
            };
        }

        function punyaTanda(el) {
            return el.hasAttribute('data-konfirmasi')
                && el.getAttribute('data-konfirmasi') !== '0';
        }

        /**
         * Apakah form ini harus dijaga walau tidak diberi atribut?
         * Ya, bila action-nya berbau menghapus — jaring pengaman untuk modul
         * yang belum/lupa ditandai.
         */
        function perluJaring(form) {
            if (form.hasAttribute('data-konfirmasi-lewati')) { return false; }
            // Sudah punya confirm() sebaris: biarkan, jangan bertanya dua kali.
            var os = form.getAttribute('onsubmit') || '';
            if (os.indexOf('confirm(') !== -1) { return false; }
            return POLA_HAPUS.test(form.getAttribute('action') || '');
        }

        function kirimUlang(form, pengirim) {
            form.setAttribute('data-konfirmasi-lolos', '1');
            try {
                if (typeof form.requestSubmit === 'function') {
                    // requestSubmit mempertahankan tombol pengirim (name/value,
                    // formaction) — form.submit() membuangnya.
                    form.requestSubmit(pengirim && pengirim.form === form ? pengirim : undefined);
                    return;
                }
            } catch (e) { /* peramban lama: jatuh ke submit() biasa */ }
            if (pengirim && pengirim.name) {
                var bayangan = document.createElement('input');
                bayangan.type = 'hidden';
                bayangan.name = pengirim.name;
                bayangan.value = pengirim.value || '';
                form.appendChild(bayangan);
            }
            form.submit();
        }

        /* ============ penyelaras `onsubmit="return confirm(...)"` lama ============
         *
         * Modul-modul lama menaruh pertanyaannya di atribut sebaris. Alih-alih
         * menyunting puluhan berkas satu per satu — dan meninggalkan dua gaya
         * dialog berdampingan di aplikasi yang sama — atribut itu dipindahkan
         * ke data-konfirmasi tepat sebelum sempat dijalankan.
         *
         * Bisa dilakukan karena penyadap di bawah dipasang pada fase CAPTURE:
         * document kebagian lebih dulu daripada elemen sasarannya, jadi
         * atributnya sempat dicopot sebelum peramban menjalankannya.
         *
         * Argumennya hanya diterima bila berupa rangkaian teks apa adanya —
         * tanpa tanda kurung, kurung siku, atau titik koma. Ekspresi yang
         * memanggil fungsi TIDAK disentuh: menjalankannya untuk sekadar
         * membaca pesan bisa menimbulkan efek samping. Yang tidak lolos
         * dibiarkan apa adanya dan tetap berjalan seperti sebelumnya.
         *
         * Kode baru sebaiknya langsung memakai data-konfirmasi.
         */
        var POLA_CONFIRM = /^\s*return\s+confirm\(([\s\S]*)\)\s*;?\s*$/;
        var POLA_AMAN = /^[^()\[\]{};=]*$/;
        var POLA_NADA_HAPUS = /hapus|dihapus|dibuang|hilang|musnah|bersihkan|delete/i;

        function migrasiConfirmLama(el, atribut) {
            if (!el || !el.getAttribute) { return false; }
            var isi = el.getAttribute(atribut);
            if (!isi || isi.indexOf('confirm(') === -1) { return false; }

            var cocok = isi.match(POLA_CONFIRM);
            if (!cocok || !POLA_AMAN.test(cocok[1])) { return false; }

            var pesan;
            try {
                pesan = (new Function('return (' + cocok[1] + ');'))();
            } catch (e) {
                return false;
            }
            if (typeof pesan !== 'string' || pesan.trim() === '') { return false; }

            el.removeAttribute(atribut);
            el.setAttribute('data-konfirmasi', pesan);
            el.setAttribute('data-konfirmasi-jenis', POLA_NADA_HAPUS.test(pesan) ? 'hapus' : 'tanya');
            return true;
        }

        /* ================= penyadap global ================= */

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') { return; }

            if (form.getAttribute('data-konfirmasi-lolos') === '1') {
                form.removeAttribute('data-konfirmasi-lolos');
                return; // sudah disetujui pengguna, teruskan
            }

            var pengirim = e.submitter || null;
            var sumber = null;

            if (pengirim && punyaTanda(pengirim)) { sumber = pengirim; }
            else if (punyaTanda(form)) { sumber = form; }
            else if (migrasiConfirmLama(form, 'onsubmit')) { sumber = form; }
            else if (pengirim && migrasiConfirmLama(pengirim, 'onclick')) { sumber = pengirim; }

            var opsi;
            if (sumber) {
                opsi = dariAtribut(sumber);
            } else if (perluJaring(form)) {
                opsi = {
                    pesan: 'Data ini akan dihapus permanen dari sistem.',
                    jenis: 'hapus',
                    elemenAsal: pengirim || form
                };
            } else {
                return; // bukan urusan kita
            }

            e.preventDefault();
            e.stopPropagation();
            tanya(opsi).then(function (ya) {
                if (ya) { kirimUlang(form, pengirim); }
            });
        }, true);

        document.addEventListener('click', function (e) {
            if (!e.target.closest) { return; }
            var el = e.target.closest('[data-konfirmasi]');
            if (!el) {
                // Belum ditandai — mungkin masih memakai onclick="return confirm(...)".
                var lawas = e.target.closest('[onclick]');
                if (lawas && migrasiConfirmLama(lawas, 'onclick')) { el = lawas; }
            }
            if (!el || !punyaTanda(el)) { return; }
            if (el.getAttribute('data-konfirmasi-lolos') === '1') {
                el.removeAttribute('data-konfirmasi-lolos');
                return;
            }

            // Tombol kirim di dalam form ditangani penyadap submit di atas,
            // supaya validasi bawaan form tetap berjalan lebih dulu.
            var tag = el.tagName;
            if ((tag === 'BUTTON' && (el.type === 'submit' || !el.type)) || (tag === 'INPUT' && el.type === 'submit')) {
                if (el.form) { return; }
            }
            if (tag === 'FORM') { return; }

            e.preventDefault();
            e.stopPropagation();

            tanya(dariAtribut(el)).then(function (ya) {
                if (!ya) { return; }
                el.setAttribute('data-konfirmasi-lolos', '1');
                if (tag === 'A') {
                    // href="#" bukan tujuan navigasi — itu tombol berpenangan JS
                    // (mis. .casc-del). Menyusurinya justru membatalkan aksinya.
                    var href = el.getAttribute('href') || '';
                    var menuju = href !== '' && href.charAt(0) !== '#'
                        && href.slice(0, 11).toLowerCase() !== 'javascript:';
                    if (menuju) {
                        window.location.href = el.href;
                        return;
                    }
                }
                el.click(); // teruskan ke penangan asli milik elemen
            });
        }, true);

        /**
         * Membuang satu blok isian dari formulir yang sedang disusun
         * (mis. satu Sasaran beserta seluruh indikator di bawahnya).
         *
         * Blok yang masih KOSONG dibuang begitu saja: bertanya tentang sesuatu
         * yang belum diketik apa pun hanya melatih orang menekan "Ya" tanpa
         * membaca — dan justru melemahkan pertanyaan yang benar-benar penting.
         * Blok yang sudah berisi ketikan ditanyakan dulu, lengkap dengan
         * jumlah isian yang akan hilang.
         *
         * @param {Element} pemicu   tombol yang ditekan
         * @param {string}  pemilih  selektor blok; kosong = elemen induk tombol
         * @param {string}  label    sebutan blok untuk judul dialog
         * @returns {Promise<boolean>}
         */
        function buangBlok(pemicu, pemilih, label) {
            var blok = pemilih ? pemicu.closest(pemilih) : pemicu.parentElement;
            if (!blok) { return Promise.resolve(false); }

            var terisi = Array.prototype.filter.call(
                blok.querySelectorAll('input, textarea, select'),
                function (f) {
                    if (f.type === 'hidden' || f.type === 'button' || f.type === 'submit') { return false; }
                    if (f.type === 'checkbox' || f.type === 'radio') { return f.checked; }
                    return String(f.value == null ? '' : f.value).trim() !== '';
                }
            ).length;

            if (terisi === 0) {
                blok.remove();
                return Promise.resolve(true);
            }

            var pertama = blok.querySelector('input[type="text"], textarea');
            var sebutan = label || 'Blok Ini';

            return tanya({
                judul: 'Hapus ' + sebutan,
                pesan: sebutan + ' dikeluarkan dari formulir beserta isinya. '
                    + 'Perubahan baru tersimpan setelah Anda menekan Simpan.',
                nama: pertama ? String(pertama.value || '').trim() : '',
                rincian: [terisi + ' isian yang sudah diketik akan hilang'],
                jenis: 'hapus',
                elemenAsal: pemicu
            }).then(function (ya) {
                if (ya) { blok.remove(); }
                return ya;
            });
        }

        window.Konfirmasi = {
            tanya: tanya,
            hapus: function (o) {
                o = o || {};
                o.jenis = 'hapus';
                return tanya(o);
            },
            buangBlok: buangBlok,
            /** Bantu alur AJAX: baca atribut data-konfirmasi milik sebuah elemen. */
            dariElemen: function (el) { return tanya(dariAtribut(el)); }
        };
    })(window, document);
</script>

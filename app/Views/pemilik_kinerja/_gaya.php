<?php
/*
 * Gaya halaman Pemilik Kinerja. Disisipkan ke <style> milik templates/shell_atas
 * lewat variabel $shellCss (dirender controller), jadi berkas ini TANPA tag <style>.
 * Semua kelas berawalan .pmk- supaya tidak bertabrakan dengan gaya global.
 */
?>
.pmk { --pmk-hijau: #00743e; --pmk-lime: #6eab11; --pmk-garis: #e3e9e5; --pmk-teks: #243b2e; --pmk-redup: #6b7a70;
       --pmk-es2: #00743e; --pmk-es3: #0f766e; --pmk-es4: #4d7c0f; --pmk-pelaksana: #a16207; --pmk-atas: 66px; }
.pmk-head { display: flex; align-items: center; gap: 16px; padding-bottom: 16px; margin-bottom: 18px; border-bottom: 1px solid #e8ece9; }
.pmk-head .pmk-ikon { flex: 0 0 auto; width: 54px; height: 54px; display: grid; place-items: center; border-radius: 15px;
    background: linear-gradient(135deg, #0a8f50 0%, #00743e 100%); color: #fff; font-size: 23px; box-shadow: 0 8px 18px rgba(0, 116, 62, .28); }
.pmk-head h2 { margin: 0; font-weight: 800; font-size: 1.35rem; color: #16321f; letter-spacing: .2px; }
.pmk-head p { margin: 3px 0 0; color: var(--pmk-redup); font-size: .86rem; }

.pmk-filter { background: #f6f9f7; border: 1px solid #e6ece8; border-radius: 14px; padding: 12px 14px; margin-bottom: 16px; }
.pmk-filter .tb-label { font-size: .72rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #5d8a3f; margin-bottom: 8px; }
.pmk-filter .pmk-filter-baris { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.pmk-filter .pmk-filter-opd { flex: 1 1 320px; min-width: 0; }
.pmk-filter .pmk-filter-tahun { flex: 0 0 150px; }
.pmk-filter .pmk-opd-nama { flex: 1 1 260px; font-weight: 700; color: var(--pmk-teks); }
.pmk-filter .pmk-opd-nama small { display: block; font-weight: 500; color: var(--pmk-redup); }

.pmk-cara { display: flex; flex-wrap: wrap; gap: 8px 18px; font-size: .82rem; color: #3f5247; margin: 0 0 16px; padding: 0; list-style: none; }
.pmk-cara li { display: flex; gap: 8px; align-items: flex-start; flex: 1 1 250px; }
.pmk-cara .no { flex: 0 0 22px; height: 22px; border-radius: 50%; background: #e7f4ec; color: var(--pmk-hijau); font-weight: 800;
    font-size: .75rem; display: grid; place-items: center; }

/* ---------- Kartu cakupan ---------- */
.pmk-cakupan { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
@media (max-width: 1199.98px) { .pmk-cakupan { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .pmk-cakupan { grid-template-columns: minmax(0, 1fr); } }
.pmk-tile { border: 1px solid var(--pmk-garis); border-radius: 14px; padding: 12px 14px; background: #fff; box-shadow: 0 4px 14px rgba(16, 40, 24, .05); min-width: 0; }
.pmk-tile .judul { font-size: .72rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #5d8a3f; margin-bottom: 4px; }
.pmk-tile .besar { font-size: 1.45rem; font-weight: 800; color: var(--pmk-teks); line-height: 1.15; }
.pmk-tile .besar small { font-size: .8rem; font-weight: 600; color: var(--pmk-redup); margin-left: 4px; }
.pmk-tile .pmk-baris-lv { display: grid; grid-template-columns: 96px minmax(0, 1fr) 44px; gap: 8px; align-items: center; font-size: .75rem; color: #44564b; margin-top: 6px; }
.pmk-tile .pmk-baris-lv .angka { text-align: right; font-variant-numeric: tabular-nums; }
.pmk-progres { height: 7px; border-radius: 99px; background: #edf1ee; overflow: hidden; }
.pmk-progres > span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--pmk-hijau), var(--pmk-lime)); transition: width .3s ease; }
.pmk-tile .pmk-es2-orang { font-size: .85rem; font-weight: 700; color: var(--pmk-teks); margin-top: 2px; }
.pmk-tile .pmk-es2-orang small { display: block; font-weight: 500; color: var(--pmk-redup); font-size: .75rem; }
.pmk-tile .pmk-kat-mini { display: flex; flex-wrap: wrap; gap: 4px 10px; font-size: .74rem; color: #44564b; margin-top: 6px; }
.pmk-tile a { font-size: .78rem; font-weight: 600; }
.pmk-tile .pmk-tile-catatan { font-size: .76rem; color: var(--pmk-redup); margin-top: 8px; }

/* ---------- Panel pegawai tanpa peran ---------- */
.pmk-tanpa-peran { border: 1px solid var(--pmk-garis); border-radius: 14px; margin-bottom: 16px; background: #fff; }
.pmk-tanpa-peran > summary { list-style: none; cursor: pointer; padding: 11px 14px; display: flex; gap: 10px; align-items: center; font-weight: 700; color: var(--pmk-teks); font-size: .9rem; }
.pmk-tanpa-peran > summary::-webkit-details-marker { display: none; }
.pmk-tanpa-peran > summary .fa-chevron-right { transition: transform .2s ease; color: var(--pmk-redup); font-size: .8rem; }
.pmk-tanpa-peran[open] > summary .fa-chevron-right { transform: rotate(90deg); }
.pmk-tanpa-peran .isi { padding: 0 14px 14px; }
.pmk-roster-alat { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
.pmk-roster-alat input { flex: 1 1 220px; max-width: 360px; }
.pmk-kat-btn { border: 1px solid #dce4de; background: #fff; border-radius: 99px; padding: 3px 10px; font-size: .76rem; font-weight: 600; color: #44564b; }
.pmk-kat-btn.aktif { background: var(--pmk-hijau); border-color: var(--pmk-hijau); color: #fff; }
.pmk-roster { list-style: none; margin: 0; padding: 0; max-height: 340px; overflow-y: auto; display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 6px; }
.pmk-roster li { border: 1px solid #edf1ee; border-radius: 10px; padding: 6px 10px; font-size: .8rem; min-width: 0; }
.pmk-roster li .n { font-weight: 600; color: var(--pmk-teks); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pmk-roster li .j { color: var(--pmk-redup); font-size: .72rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pmk-roster li.pmk-punya-peran, .pmk-roster li.pmk-tersaring { display: none; }

/* ---------- Bilah lengket ---------- */
.pmk-bar { position: sticky; top: var(--pmk-atas); z-index: 20; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
    background: rgba(255, 255, 255, .97); border: 1px solid #e3e9e5; border-radius: 12px; padding: 8px 10px; margin-bottom: 12px;
    box-shadow: 0 8px 20px rgba(16, 40, 24, .07); }
.pmk-bar .pmk-cari { flex: 1 1 220px; min-width: 0; position: relative; }
.pmk-bar .pmk-cari i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9fada4; font-size: .85rem; }
.pmk-bar .pmk-cari input { padding-left: 32px; height: 36px; }
.pmk-bar .form-check { margin: 0 4px 0 0; white-space: nowrap; font-size: .84rem; font-weight: 600; color: #384a3f; }
.pmk-bar .btn { height: 36px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.pmk-bar .form-select { height: 36px; width: auto; padding-top: 4px; padding-bottom: 4px; font-size: .84rem; }
.pmk-bar .pmk-teks-sempit { display: none; }
.pmk-bar .pmk-mini { flex: 1 0 100%; display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: .76rem; color: var(--pmk-redup); border-top: 1px dashed #e6ece8; padding-top: 6px; }
.pmk-bar .pmk-mini b { color: var(--pmk-teks); }
.pmk-bar [data-aksi="usulan"].active { background: #fff1c2; border-color: #d4a017; }
.pmk-usulan-info { display: none; border: 1px dashed #d4a017; background: #fffbeb; color: #6a5104; border-radius: 12px; padding: 9px 12px; margin-bottom: 12px; font-size: .84rem; }
.pmk-usulan-mode .pmk-usulan-info { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }

/* ---------- Pohon ---------- */
.pmk-pohon { margin-top: 4px; }
.pmk-simpul { margin-top: 10px; }
.pmk-kartu { position: relative; border: 1px solid var(--pmk-garis); border-left: 4px solid var(--warna, var(--pmk-es3)); border-radius: 12px; background: #fff;
    padding: 10px 12px; box-shadow: 0 2px 8px rgba(16, 40, 24, .04); transition: box-shadow .2s ease, background-color .6s ease; }
.pmk-simpul[data-level="es2"] { --warna: var(--pmk-es2); }
.pmk-simpul[data-level="es3"] { --warna: var(--pmk-es3); }
.pmk-simpul[data-level="es4"] { --warna: var(--pmk-es4); }
.pmk-simpul[data-level="pelaksana"] { --warna: var(--pmk-pelaksana); }
.pmk-simpul[data-level="es2"] > .pmk-kartu { background: linear-gradient(180deg, #f3faf6 0%, #fff 70%); }
.pmk-kartu.pmk-sorot { box-shadow: 0 0 0 3px rgba(110, 171, 17, .35); background-color: #fbfff4; }
.pmk-kepala { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 4px; }
.pmk-lipat { width: 26px; height: 26px; border: 1px solid #dce4de; border-radius: 8px; background: #fff; color: #4a5a50; display: inline-grid; place-items: center; padding: 0; font-size: .72rem; }
.pmk-lipat:hover { border-color: var(--pmk-lime); color: var(--pmk-hijau); }
.pmk-lipat i { transition: transform .2s ease; }
.pmk-simpul.terlipat > .pmk-kartu .pmk-lipat i { transform: rotate(-90deg); }
.pmk-level { display: inline-block; font-size: .66rem; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: #fff; background: var(--warna); border-radius: 6px; padding: 3px 7px; }
.pmk-status { display: inline-flex; flex-wrap: wrap; gap: 4px; }
.pmk-pil { display: inline-flex; align-items: center; gap: 4px; font-size: .7rem; font-weight: 700; border-radius: 99px; padding: 2px 8px; }
.pmk-pil-ok { background: #e7f4ec; color: #14532d; }
.pmk-pil-kurang { background: #fff4db; color: #7a5300; }
.pmk-pil-belum { background: #fde8e8; color: #9b1c1c; }
.pmk-turunan { margin-left: auto; font-size: .72rem; color: var(--pmk-redup); white-space: nowrap; }
.pmk-sasaran { font-weight: 650; font-size: .92rem; color: var(--pmk-teks); line-height: 1.4; margin: 2px 0 6px; word-break: break-word; }
.pmk-label { font-size: .68rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #7b8a80; margin-right: 4px; }
.pmk-pemilik-wadah { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 6px; }
.pmk-pemilik { display: contents; }
.pmk-belum { font-size: .78rem; color: #9b1c1c; font-style: italic; }
.pmk-catatan { flex: 1 0 100%; font-size: .74rem; color: var(--pmk-redup); }

/* Chip pemilik */
.pmk-chip { display: inline-flex; align-items: center; gap: 6px; max-width: 100%; min-width: 0; border: 1px solid #dfe4e0; background: #f7f8f7;
    border-radius: 99px; padding: 3px 4px 3px 11px; font-size: .8rem; }
.pmk-chip-pj { background: #eaf6ef; border-color: #a9d6b9; }
.pmk-chip-teks { display: flex; flex-direction: column; min-width: 0; line-height: 1.15; }
.pmk-chip-nama { font-weight: 650; color: #1f3a2a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pmk-chip-jab { font-size: .69rem; color: var(--pmk-redup); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px; }
.pmk-chip-peran { flex: 0 0 auto; border: 0; border-radius: 99px; padding: 2px 8px; font-size: .64rem; font-weight: 800; letter-spacing: .3px; color: #fff; background: var(--pmk-hijau); }
.pmk-chip-peran.anggota { background: #8a9a90; }
button.pmk-chip-peran:hover { filter: brightness(1.12); }
.pmk-chip-x { flex: 0 0 auto; width: 22px; height: 22px; border: 0; border-radius: 50%; background: transparent; color: #7b8a80; display: grid; place-items: center; padding: 0; }
.pmk-chip-x:hover { background: #fde8e8; color: #b42318; }
.pmk-chip-ok { background: #e7f4ec; color: var(--pmk-hijau); }
.pmk-chip-ok:hover { background: var(--pmk-hijau); color: #fff; }
.pmk-chip-kunci { background: #f1f5f2; border-style: solid; }
.pmk-chip-usulan { border: 1px dashed #d4a017; background: #fffbeb; }
.pmk-chip-tanda { font-size: .62rem; font-weight: 700; color: #7a5300; background: #fff1c2; border-radius: 6px; padding: 1px 5px; margin-left: 4px; }
.pmk-tambah { border: 1px dashed #9fcfb1; background: #fff; color: var(--pmk-hijau); border-radius: 99px; font-size: .76rem; font-weight: 700; padding: 3px 10px; }
.pmk-tambah:hover { background: #eaf6ef; border-style: solid; }

/* Indikator */
.pmk-ind-daftar { list-style: none; margin: 4px 0 0; padding: 0; border-top: 1px dashed #e3e9e5; }
.pmk-ind { padding: 7px 0 5px; border-bottom: 1px dashed #eef2ef; }
.pmk-ind:last-child { border-bottom: 0; padding-bottom: 0; }
.pmk-ind-nama { font-size: .84rem; color: #33433a; line-height: 1.35; word-break: break-word; }
.pmk-ind-nama .fa-circle { font-size: .45rem; vertical-align: middle; margin-right: 6px; color: #b42318; }
.pmk-ind[data-lengkap="1"] .pmk-ind-nama .fa-circle { color: var(--pmk-lime); }
.pmk-ind-nilai { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; margin-top: 5px; }
.pmk-tag { display: inline-flex; align-items: center; gap: 5px; max-width: 100%; font-size: .73rem; color: #3a4a40; background: #f3f6f4; border: 1px solid #e3e9e5; border-radius: 7px; padding: 2px 8px; }
.pmk-tag b { font-weight: 700; color: var(--pmk-teks); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pmk-tag.kosong { background: #fff5f5; border-color: #f3c9c9; color: #9b2c2c; }
.pmk-tag.ikp { background: #eef6ff; border-color: #cfe0f5; color: #1e4b7a; }
.pmk-tag.ikp b { max-width: 280px; }
.pmk-ubah { border: 1px solid #b9dcc6; background: #fff; color: var(--pmk-hijau); border-radius: 8px; font-size: .74rem; font-weight: 700; padding: 2px 9px; }
.pmk-ubah:hover { background: var(--pmk-hijau); color: #fff; }
.pmk-ind-es2 { display: flex; flex-wrap: wrap; gap: 6px; align-items: baseline; font-size: .82rem; color: #33433a; }

/* Anak & grup */
.pmk-anak { margin-left: 20px; padding-left: 14px; border-left: 2px solid #e4ebe6; }
.pmk-simpul.terlipat > .pmk-anak { display: none; }
.pmk-grup { padding-top: 2px; }
.pmk-grup + .pmk-grup { margin-top: 8px; }
.pmk-grup-kepala { display: flex; flex-wrap: wrap; gap: 6px; align-items: baseline; font-size: .78rem; color: #44564b; margin: 10px 0 0; }
.pmk-grup-kepala .ikon { color: var(--pmk-hijau); }
.pmk-grup-kepala .nm { font-weight: 700; color: var(--pmk-teks); }
.pmk-kosong-kecil { font-size: .78rem; color: #7a5300; background: #fffbeb; border: 1px dashed #ecd48a; border-radius: 10px; padding: 7px 10px; margin-top: 8px; }
.pmk-sembunyi, .pmk-grup.pmk-sembunyi { display: none !important; }
.pmk-konteks > .pmk-kartu { opacity: .72; }
.pmk-kosong { text-align: center; padding: 44px 22px; border-radius: 16px; border: 1px dashed #cfd8d2; background: #f8faf9; color: #5d6b62; }
.pmk-kosong .ikon { font-size: 40px; margin-bottom: 12px; color: var(--pmk-hijau); opacity: .35; }
.pmk-kosong h5 { font-weight: 700; color: #3a4a40; }
.pmk-hasil-nihil { display: none; }
.pmk-hasil-nihil.tampil { display: block; }

/* Modal */
.pmk-modal-simpul { border: 1px solid var(--pmk-garis); border-left: 4px solid var(--warna, var(--pmk-es3)); border-radius: 10px; padding: 8px 10px; margin-bottom: 12px; background: #fafcfb; }
.pmk-modal-simpul .isi { font-weight: 600; font-size: .88rem; color: var(--pmk-teks); margin-top: 4px; }
.pmk-opsi-peran { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
@media (max-width: 575.98px) { .pmk-opsi-peran { grid-template-columns: minmax(0, 1fr); } }
.pmk-opsi { display: flex; gap: 8px; align-items: flex-start; border: 1px solid #dce4de; border-radius: 10px; padding: 8px 10px; cursor: pointer; margin: 0; }
.pmk-opsi:has(input:checked) { border-color: var(--pmk-hijau); background: #f1f8f3; }
.pmk-opsi input { margin-top: 4px; accent-color: var(--pmk-hijau); }
.pmk-opsi b { display: block; font-size: .86rem; color: var(--pmk-teks); }
.pmk-opsi small { display: block; color: var(--pmk-redup); font-size: .75rem; line-height: 1.3; }
.pmk-s2 .n { font-weight: 600; }
.pmk-s2 .j { font-size: .75rem; opacity: .8; }
.pmk-info-ikp { display: none; font-size: .8rem; background: #eef6ff; border: 1px solid #cfe0f5; color: #1e4b7a; border-radius: 10px; padding: 8px 10px; margin-top: 8px; }
.pmk-info-ikp.tampil { display: block; }

/* Toast */
.pmk-toast-wadah { position: fixed; right: 16px; bottom: 88px; z-index: 2000; display: flex; flex-direction: column; gap: 8px; max-width: min(380px, calc(100vw - 32px)); }
.pmk-toast { background: #173a27; color: #fff; border-radius: 12px; padding: 10px 14px; font-size: .85rem; box-shadow: 0 12px 30px rgba(0, 0, 0, .2);
    display: flex; gap: 10px; align-items: flex-start; animation: pmk-masuk .2s ease; }
.pmk-toast.galat { background: #7f1d1d; }
.pmk-toast.peringatan { background: #7a5300; }
@keyframes pmk-masuk { from { transform: translateY(8px); opacity: 0; } to { transform: none; opacity: 1; } }

/* Ponsel */
@media (max-width: 767.98px) {
    .pmk-head { gap: 12px; }
    .pmk-head .pmk-ikon { width: 44px; height: 44px; font-size: 19px; border-radius: 12px; }
    .pmk-head h2 { font-size: 1.12rem; }
    .pmk-anak { margin-left: 6px; padding-left: 8px; }
    .pmk-kartu { padding: 9px 10px; }
    .pmk-bar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 6px; padding: 7px 8px; align-items: center; }
    .pmk-bar .pmk-cari { grid-column: 1 / -1; }
    .pmk-bar .form-check { min-width: 0; overflow: hidden; }
    .pmk-bar .pmk-teks-lebar { display: none; }
    .pmk-bar .pmk-teks-sempit { display: inline; }
    .pmk-bar .form-check { font-size: .8rem; }
    /* Di ponsel pemilih jenjang disembunyikan: tombol lipat per simpul sudah cukup,
       dan bilah lengket harus tetap dua baris agar pohon tidak tertutup. */
    .pmk-bar [data-aksi="kedalaman"] { display: none; }
    .pmk-bar .btn { padding-left: 10px; padding-right: 10px; }
    .pmk-bar .pmk-mini { display: none; }
    .pmk-chip-jab { max-width: 180px; }
    .pmk-tile .pmk-baris-lv { grid-template-columns: 84px minmax(0, 1fr) 40px; }
    .pmk-turunan { margin-left: 0; flex-basis: 100%; }
}

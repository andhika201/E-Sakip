# Urutan eksekusi di server — 29 Agustus 2026

Seluruh langkah di bawah **sudah diuji** pada salinan `backup-29 agustus.sql`
yang dimuat ke basis data sementara, bukan diperkirakan dari pembacaan kode.
Angka sebelum/sesudahnya nyata.

Keadaan awal server (terukur):

    sasaran IKU OPD    84, bersilsilah   5
    indikator IKU OPD 109, bersilsilah 102
    indikator IKU KAB  11, bersilsilah   8
    cascading        1413, berjangkar IKU 1335   <-- 78 baris tanpa jangkar

Keadaan sesudah seluruh langkah (terukur):

    sasaran IKU OPD   100, bersilsilah  98
    indikator IKU OPD 130, bersilsilah 125
    indikator IKU KAB  11, bersilsilah  10
    cascading        1413, berjangkar IKU 1413   <-- LENGKAP
    casc:akar-check  113 LULUS / 0 GAGAL
    casc:versi-check   9 LULUS / 0 GAGAL

---

## 0. CADANGKAN DULU

    mysqldump -u USER -p NAMA_DB > backup_sebelum_deploy_29agustus.sql

Tanpa ini, tidak ada jalan mundur. Jangan lewati.

---

## 1. SKRIP SQL — jalankan BERURUTAN

Semuanya idempoten: aman diulang, dan yang sudah pernah dijalankan akan
melapor "dilewati" alih-alih menggandakan pekerjaan.

    1. db/perbaiki_2026-08-28_teks_renstra_pringsewu.sql
    2. db/perbaiki_2026-08-28_iku_kembar_setwan.sql
    3. db/perbaiki_2026-08-28_sasaran_iku_bapperida.sql
    4. db/perbaiki_2026-08-28_iku_pringsewu_samakan_renstra.sql
    5. db/perbaiki_2026-08-28_iku_kembar_sisa_sync.sql
    6. db/perbaiki_2026-08-28_sisa_tunggal_indikator_iku.sql
    7. db/update_2026-08-28_silsilah_sasaran_iku_opd.sql       <-- silsilah 5 -> 82
    8. db/server/2026-08-28_B_silsilah_iku_kabupaten.sql       <-- VARIAN SERVER
    9. db/update_2026-08-29_silsilah_iku_kab_akronim.sql       <-- kabupaten 8 -> 10
   10. db/server/2026-08-29_A_kolom_sasaran_mandiri.sql        <-- VARIAN SERVER, kolom + FK
   11. db/update_2026-08-29_tandai_indikator_iku_mandiri.sql   <-- lindungi IKP

### Mengapa ada map `db/server/`

Pengguna basis data di server tidak diizinkan membaca `information_schema`:

    #1044 - Access denied for user 'esakippringsewu'@'localhost'
            to database 'information_schema'

Tiga skrip memakainya untuk membuat dirinya aman-diulang. Ketiganya sudah
diganti dengan varian di `db/server/` yang tidak menyentuhnya sama sekali,
dan seluruh urutan di halaman ini SUDAH DIUJI ULANG memakai varian itu —
hasil akhirnya sama persis: 1413 dari 1413 baris berjangkar.

PAKAI YANG DI `db/server/`, JANGAN yang asli, untuk nomor 8, 10, dan 12.
Nomor lain tidak terpengaruh (tidak satu pun memakai `information_schema`).

Nomor 10 sengaja TIDAK punya penjaga aman-diulang, karena MySQL tidak
mengenal `ADD COLUMN IF NOT EXISTS`. Jalankan sekali. Bila muncul galat
#1060 (kolom duplikat), #1061 (indeks duplikat), atau #1826 (kunci asing
duplikat), artinya bagian itu memang sudah ada — abaikan, lanjutkan.
Galat LAIN jangan diabaikan.

Nomor 1-6 hampir tidak mengubah apa pun di server Anda — koreksi itu sudah
Anda kerjakan manual di sana. Dijalankan tetap, supaya tidak ada yang
terlewat bila ternyata ada sisa.

Nomor 11 SENGAJA hanya menyentuh lingkup kabupaten. Versi tanpa pembatasan
itu menandai TUJUH indikator pada salinan server Anda, enam di antaranya milik
OPD dan bukan satu pun yang benar-benar mandiri. Lihat bagian 4.

---

## 2. PASANG KODE BARU

Deploy seluruh perubahan aplikasi. Urutannya boleh setelah langkah 1;
kodenya dijaga `fieldExists`, jadi ia tetap berjalan pada basis data yang
belum dimigrasi.

---

## 3. SYNC EMPAT KECAMATAN — WAJIB, JANGAN DILEWATI

Ini langkah yang paling mudah terlupa dan paling terasa akibatnya.

78 baris cascading di server belum punya jangkar IKU, karena empat kecamatan
ini punya indikator Renstra yang belum pernah ditarik ke IKU:

    Kecamatan Adiluwih   5 indikator belum di IKU
    Kecamatan Banyumas   6
    Kecamatan Pagelaran  6
    Kecamatan Pringsewu  4

Kode sekarang membaca cascading dari IKU. Baris tanpa jangkar TIDAK AKAN
MUNCUL di matriks — pekerjaan Eselon III/IV mereka akan tampak hilang.

Lewat CLI (paling cepat, periode dibatasi supaya periode nyasar tidak ikut):

    php spark iku:sync-renstra --opd 35 --periode 2025-2029 --fix
    php spark iku:sync-renstra --opd 36 --periode 2025-2029 --fix
    php spark iku:sync-renstra --opd 37 --periode 2025-2029 --fix
    php spark iku:sync-renstra --opd 31 --periode 2025-2029 --fix

Periksa dulu id OPD-nya di server Anda:

    SELECT id, nama_opd FROM opd
     WHERE nama_opd IN ('Kecamatan Adiluwih','Kecamatan Banyumas',
                        'Kecamatan Pagelaran','Kecamatan Pringsewu');

Atau minta tiap operator kecamatan menekan tombol **Sync dari Renstra**.
Hasilnya sama.

---

## 4. JANGKAR ULANG — SETELAH langkah 3

   12. db/server/2026-08-27_C_jangkar_cascading_isi.sql        <-- VARIAN SERVER

Varian ini hanya berisi bagian PENGISIAN. Bagian pembuatan kolom/indeks/kunci
asing pada skrip asli sudah ada di server Anda, dan justru bagian itulah yang
memakai `information_schema` serta PROCEDURE — dengan membuangnya, masalahnya
ikut hilang alih-alih ditambal.

Dijalankan SESUDAH sync, bukan sebelumnya: penjangkaran bergantung pada
silsilah indikator yang baru dibuat sync. Pada uji coba, langkah ini membawa
jangkar dari 1365 menjadi 1413 dari 1413 — lengkap.

---

## 5. PERIKSA HASILNYA

    php spark casc:akar-check     # harus: LULUS 113, GAGAL 0, baris hilang 0
    php spark casc:versi-check    # harus: LULUS 9, GAGAL 0

Dan satu kueri yang paling menentukan — hasilnya HARUS 0:

    SELECT COUNT(*) AS baris_tanpa_jangkar
      FROM cascading_sasaran_opd
     WHERE iku_indikator_id IS NULL;

---

## 6. YANG PERLU KEPUTUSAN ANDA — bukan langkah otomatis

Enam indikator IKU milik OPD tidak punya padanan di Renstra dan TIDAK
ditandai mandiri oleh skrip nomor 11. Skrip itu akan menampilkannya di
bagian "indikator OPD yang SENGAJA DILEWATI", lengkap dengan kolom
`ada_padanan_persis_di_renstra`:

  * Dua di antaranya punya padanan PERSIS di Renstra. Menekan Sync pada OPD
    itu akan menautkannya — itu jalan yang benar.
  * Empat sisanya perlu Anda putuskan: benar-benar berdiri sendiri, atau
    redaksinya menyimpang dari Renstra dan perlu dirapikan.

Selama belum diputuskan, keenamnya tetap muncul di daftar "tidak ada padanan"
pada layar Sync. Mode Ganti akan menawarkan membuangnya — TETAPI selalu lewat
pratinjau yang menyebut namanya satu per satu, jadi tidak ada yang terhapus
diam-diam.

---

## 6b. LAKIP — PEMULIHAN REALISASI YATIM (baru, 29 Agustus malam)

Dijalankan SETELAH kode baru terpasang (butuh LakipModel yang sudah
ditambal). Urutannya WAJIB seperti ini:

    L1. db/perbaiki_2026-08-29_lakip_pulangkan_lingkup.sql
    L2. db/perbaiki_2026-08-29_lakip_yatim_eksekusi.sql
    L3. db/perbaiki_2026-08-29_lakip_jangkar_ulang.sql

Ketiganya sudah diuji UTUH pada salinan `backup-29 agustus.sql`, dan hasil
akhirnya diverifikasi IDENTIK baris-per-baris dengan basis data lokal
(152 baris lakip, 0 beda, 0 slot dobel).

Apa yang terjadi:

  * L1 memulangkan lingkup 11 baris yang jangkarnya masih hidup;
  * L2 mengarsipkan SEMUA 123 yatim tanpa jangkar ke tabel baru
    `lakip_yatim_arsip` (salinan JSON utuh — tidak ada angka yang lenyap),
    lalu membersihkan tabel kerja;
  * L3 menjangkar-ulang 18 baris yang lingkupnya utuh tapi jangkarnya putus
    (Dinkes 12, Disporapar 2, DPMP 2, Setwan 1, Sekda 1) — deterministik
    lewat jejak `source_entity_id` + cadangan bergenerasi lama, TERMASUK
    seluruh realisasi Dinas Kesehatan 2025 yang delapan bulan tak terlihat;
    dua baris Sekda yang indikatornya sudah dihapus dari Renstra ikut
    diarsipkan.

Setelahnya, LAKIP Dinkes 2025 menampilkan 6 dari 7 indikator terisi
(stunting memang belum pernah diisi operatornya).

---

## 6c. LAKIP MENILAI TERHADAP IKU (tahap akhir)

Dijalankan SETELAH bagian 6b selesai dan kode terbaru terpasang.

    S1. php spark iku:sahkan-massal              (pratinjau — tidak menulis)
    S2. php spark iku:sahkan-massal --fix        (bekukan Kondisi Awal 36 OPD)
    S3. db/migrasi_2026-08-29_lakip_ke_iku.sql   (pindahkan kunci realisasi)

S2 memakai `IkuRevisiModel::pastikanBaseline()` — mekanisme yang sudah ada:
membekukan IKU BERJALAN menjadi revisi bernomor 0 "Kondisi Awal". Ia tidak
mengarang dokumen bertanda tangan; isinya potret IKU yang sudah ada, yang
sendirinya berasal dari Renstra lewat Sync. Tanpa ini, LAKIP tidak punya
versi yang memayungi tahun laporan, sehingga selamanya jatuh ke Renstra.

S3 memindahkan kunci realisasi dari target Renstra ke indikator IKU.
`renstra_target_id` SENGAJA dipertahankan sebagai jejak asal sekaligus jalan
pulang (perintah pembatalannya tertulis di kepala skrip).

Hasil terukur di lokal (cermin server):

    Kondisi Awal dibekukan : 36 OPD (1 dilewati: sudah punya revisi)
    lingkup berpayung 2025 : 37 dari 37
    realisasi bermigrasi   : 118 baris (83 berisi angka)
    tanpa versi payung     : 0
    tabrakan slot          : 0
    sengaja tertinggal     : 2 baris

Dua yang tertinggal adalah indikator yang padanan IKU-nya ada TAPI belum
bersilsilah — Disdukcapil "Akta Kematian" dan Kec. Sukoharjo "Pembinaan
Aparatur". Keduanya tetap tampil lewat jembatan; penautannya menunggu
keputusan (redaksinya berbeda, dan untuk Sukoharjo subjeknya pun berbeda).

Regresi sesudah S3: lakip:jaga-snapshot 48/0 · casc:akar-check 113/0 ·
casc:versi-check 9/0.

---

## 7. TIDAK PERLU DIJALANKAN

    db/hapus_2026-08-30_realisasi_anggaran_warisan.sql
    db/update_2026-08-29_periode_iku_nyasar.sql

Keduanya menyentuh persoalan lain (warisan monev dan periode Renstra nyasar
Kec. Banyumas 2026-2030 / Kec. Pringsewu 2029-2033) yang belum kita bahas dan
belum Anda putuskan.

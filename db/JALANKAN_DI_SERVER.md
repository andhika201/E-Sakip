# Yang harus dijalankan di server

> **CATATAN 30 Agustus — bila server Anda TIDAK punya akses `php spark`:**
> Langkah 3 dan bagian pertama Langkah 5 memakai perintah CLI. Bila keduanya
> terlewat, gejalanya `cascading tanpa jangkar = 48` dan `lakip iku=8`.
> Penggantinya berupa SQL murni ada di **db/LANJUTAN_TANPA_CLI.md** —
> sudah diuji dan hasilnya identik.


Titik awal: server Anda ada pada keadaan `backup-29 agustus.sql`.

Seluruh urutan di bawah **sudah diuji utuh** dengan membangun ulang salinan dari
dump itu, menjalankan semuanya, lalu membandingkannya dengan basis data lokal:

    tabel            99 vs 99      (0 kolom berbeda)
    lakip           150 vs 150
    lakip sumber IKU 118 vs 118
    cascading        1413 berjangkar IKU, dua-duanya
    revisi IKU       38 vs 38
    arsip yatim      125 vs 125

Hasilnya identik. Angka-angka itulah yang harus Anda lihat di server setelah
selesai.

---

## LANGKAH 0 — CADANGKAN

    mysqldump -u USER -p NAMA_DB > backup_sebelum_deploy.sql

Jangan dilewati. Tidak ada langkah lain yang bisa menggantikan ini.

---

## LANGKAH 1 — SQL, JALANKAN BERURUTAN

Semuanya idempoten (aman diulang). Nomor 8 dan 10 **wajib memakai berkas di
`db/server/`** — pengguna basis data server tidak boleh membaca
`information_schema`, dan varian itulah yang tidak memerlukannya.

     1. db/perbaiki_2026-08-28_teks_renstra_pringsewu.sql
     2. db/perbaiki_2026-08-28_iku_kembar_setwan.sql
     3. db/perbaiki_2026-08-28_sasaran_iku_bapperida.sql
     4. db/perbaiki_2026-08-28_iku_pringsewu_samakan_renstra.sql
     5. db/perbaiki_2026-08-28_iku_kembar_sisa_sync.sql
     6. db/perbaiki_2026-08-28_sisa_tunggal_indikator_iku.sql
     7. db/update_2026-08-28_silsilah_sasaran_iku_opd.sql
     8. db/server/2026-08-28_B_silsilah_iku_kabupaten.sql      <-- VARIAN SERVER
     9. db/update_2026-08-29_silsilah_iku_kab_akronim.sql
    10. db/server/2026-08-29_A_kolom_sasaran_mandiri.sql       <-- VARIAN SERVER
    11. db/update_2026-08-29_tandai_indikator_iku_mandiri.sql
    12. db/update_2026-08-30_lakip_pengesahan.sql              <-- 2 tabel + 1 izin

Nomor 10 tidak punya penjaga aman-diulang (MySQL tidak mengenal
`ADD COLUMN IF NOT EXISTS`). Jalankan sekali; bila muncul galat **#1060**
(kolom duplikat), **#1061** (indeks duplikat), atau **#1826** (kunci asing
duplikat), artinya bagian itu memang sudah ada — abaikan dan lanjutkan.
Galat LAIN jangan diabaikan.

---

## LANGKAH 2 — PASANG KODE BARU

**Harus sebelum Langkah 3 dan seterusnya.** Perintah `spark` di bawah dan
perilaku LAKIP bergantung pada kode ini.

Berkas yang berubah / baru:

    app/Commands/IkuSahkanMassal.php                  (baru)
    app/Models/LakipPengesahanModel.php               (baru)
    app/Views/lakip/pengesahan_panel.php              (baru)
    app/Views/adminKabupaten/lakip/permintaan.php     (baru)

    app/Config/Routes.php
    app/Commands/LakipSnapshotIkuCheck.php
    app/Controllers/AdminKab/LakipController.php
    app/Controllers/AdminOpd/LakipOpdController.php
    app/Controllers/AdminOpd/RenstraController.php
    app/Controllers/Concerns/LakipSnapshotTrait.php
    app/Controllers/Concerns/LakipSumberTrait.php
    app/Models/LakipModel.php
    app/Models/Opd/RenstraModel.php
    app/Views/adminKabupaten/lakip/lakip.php
    app/Views/adminOpd/lakip/lakip.php
    app/Views/templates/admin_menu.php

Di dalamnya ada dua tambalan keamanan yang **hanya aktif setelah kode
ter-deploy**: hapus dan ubah-status LAKIP lintas OPD. Selama kode lama masih
berjalan, celah itu terbuka di server.

---

## LANGKAH 3 — SYNC EMPAT KECAMATAN (WAJIB)

78 baris cascading di server belum berjangkar IKU karena empat kecamatan ini
punya indikator Renstra yang belum pernah ditarik ke IKU. Kode membaca
cascading dari IKU, jadi baris tanpa jangkar **tidak akan muncul** — pekerjaan
Eselon III/IV mereka akan tampak hilang.

    php spark iku:sync-renstra --opd 35 --periode 2025-2029 --fix   # Adiluwih
    php spark iku:sync-renstra --opd 36 --periode 2025-2029 --fix   # Banyumas
    php spark iku:sync-renstra --opd 37 --periode 2025-2029 --fix   # Pagelaran
    php spark iku:sync-renstra --opd 31 --periode 2025-2029 --fix   # Pringsewu

Periksa dulu id-nya di server:

    SELECT id, nama_opd FROM opd
     WHERE nama_opd IN ('Kecamatan Adiluwih','Kecamatan Banyumas',
                        'Kecamatan Pagelaran','Kecamatan Pringsewu');

---

## LANGKAH 4 — JANGKAR ULANG CASCADING

    db/server/2026-08-27_C_jangkar_cascading_isi.sql              <-- VARIAN SERVER

**Sesudah Langkah 3, bukan sebelumnya.** Penjangkaran bergantung pada silsilah
indikator yang baru dibuat sync. Terbalik urutannya, hasilnya berhenti di 1365
dari 1413.

---

## LANGKAH 5 — LAKIP: PULIHKAN REALISASI YANG HILANG

    php spark iku:sahkan-massal              (pratinjau — tidak menulis apa pun)
    php spark iku:sahkan-massal --fix        (bekukan Kondisi Awal IKU, 36 OPD)

    db/perbaiki_2026-08-29_lakip_pulangkan_lingkup.sql
    db/perbaiki_2026-08-29_lakip_yatim_eksekusi.sql
    db/perbaiki_2026-08-29_lakip_jangkar_ulang.sql
    db/migrasi_2026-08-29_lakip_ke_iku.sql

Urutan keempat SQL itu tidak boleh ditukar.

Apa yang terjadi:

  * `iku:sahkan-massal` membekukan IKU berjalan menjadi revisi bernomor 0
    "Kondisi Awal". Ia TIDAK mengarang dokumen bertanda tangan — isinya potret
    IKU yang sudah ada. Tanpa ini, LAKIP tidak punya versi yang memayungi
    tahun laporan dan selamanya jatuh ke Renstra.
  * skrip 1 memulangkan lingkup 11 baris yang jangkarnya masih hidup;
  * skrip 2 mengarsipkan 123 baris yatim ke `lakip_yatim_arsip` (salinan JSON
    utuh — tidak ada angka yang lenyap), lalu membersihkan tabel kerja;
  * skrip 3 menjangkar ulang 18 baris, termasuk SELURUH realisasi Dinas
    Kesehatan 2025 yang delapan bulan tidak terlihat;
  * skrip 4 memindahkan kunci 118 realisasi dari target Renstra ke indikator
    IKU. `renstra_target_id` sengaja dipertahankan sebagai jejak asal sekaligus
    jalan pulang (perintah pembatalannya ada di kepala skrip).

---

## LANGKAH 6 — PERIKSA HASILNYA

    php spark casc:akar-check      # harus LULUS 113 / GAGAL 0, baris hilang 0
    php spark casc:versi-check     # harus LULUS 9 / GAGAL 0

Lalu kueri berikut — semuanya harus sesuai:

    SELECT COUNT(*) FROM cascading_sasaran_opd WHERE iku_indikator_id IS NULL;
    -- harus 0

    SELECT source_type, COUNT(*) FROM lakip WHERE mode='opd' GROUP BY source_type;
    -- harus: iku=118, renstra=2

    SELECT COUNT(*) FROM lakip WHERE mode IS NULL;
    -- harus 0

    SELECT COUNT(*) FROM lakip_yatim_arsip;
    -- harus 125

---

## YANG TIDAK PERLU DIJALANKAN

    db/pratinjau_2026-08-29_lakip_yatim.sql        (baca-saja, hanya pratinjau)
    db/hapus_2026-08-30_realisasi_anggaran_warisan.sql
    db/update_2026-08-29_periode_iku_nyasar.sql

Dua yang terakhir menyentuh persoalan yang belum dibahas dan belum Anda
putuskan (warisan monev, dan periode Renstra nyasar Kec. Banyumas 2026-2030 /
Kec. Pringsewu 2029-2033).

---

## SESUDAH SELESAI

Yang berubah bagi pengguna:

  * LAKIP menilai terhadap **IKU** untuk seluruh OPD, bukan lagi Renstra.
  * Panel **Snapshot & Penyesuaian Kebijakan** hilang dari layar LAKIP;
    penguncian tahun kini lewat panel **Pengesahan**.
  * OPD: tombol **Sahkan** mengunci tahun; bila ada typo, **Ajukan Permintaan
    Perbaikan** dengan alasan.
  * Admin Kabupaten: menu **Pelaporan Kinerja > Permintaan Perbaikan**, dengan
    lencana merah berisi jumlah permintaan yang menunggu.

Dua hal yang sengaja tertinggal dan menunggu keputusan Anda: realisasi
Disdukcapil "Akta Kematian" dan Kec. Sukoharjo "Pembinaan Aparatur" masih
berkunci Renstra karena padanan IKU-nya berbeda redaksi (untuk Sukoharjo,
subjeknya pun berbeda: aparatur kecamatan vs desa/kelurahan). Keduanya TETAP
TAMPIL lewat jembatan, jadi tidak ada yang hilang.

Catatan keamanan: kredensial 37 akun OPD sempat dibagikan selama pengerjaan.
Begitu server bukan lagi lingkungan internal murni, sebaiknya kata sandi
seragam seperti `seribubambu` diganti.

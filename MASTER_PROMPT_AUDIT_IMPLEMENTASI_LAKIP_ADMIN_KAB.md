# MASTER PROMPT --- AUDIT, AMANKAN, DAN IMPLEMENTASI FLOW LAKIP ADMIN_KAB

## e-SAKIP / AKSARA Kabupaten Pringsewu

> **Tujuan utama:** memperbaiki dan mengembangkan modul **LAKIP untuk
> `admin_kab`** dengan mekanisme pemilihan versi IKU yang eksplisit,
> source binding yang konsisten, pengesahan yang benar, Dashboard yang
> mengikuti sumber LAKIP yang benar-benar digunakan, serta PDF/Excel
> yang mencetak sumber yang sama.
>
> **Prinsip paling penting:** data existing harus dipertahankan. Jangan
> mengubah status historis menjadi `disahkan` hanya berdasarkan asumsi.
> Jangan meminta user menginput ulang data yang sudah tersimpan. Jangan
> menyentuh data `admin_opd` secara destruktif.

------------------------------------------------------------------------

# 1. KONTEKS PROJECT

Project ini adalah aplikasi e-SAKIP/AKSARA berbasis **CodeIgniter 4**
dengan domain utama:

``` text
RPJMD
  ↓
Renstra
  ↓
IKU
  ↓
Cascading
  ↓
PK
  ↓
Rencana Aksi
  ↓
MONEV
  ↓
LAKIP
```

Project memiliki scope:

-   `admin` / superadmin
-   `admin_kab`
-   `admin_inspektorat`
-   `admin_opd`
-   `admin_kecamatan`

Fokus pekerjaan pada prompt ini adalah:

> **ADMIN_KAB → LAKIP KABUPATEN**

Jangan memperluas pekerjaan menjadi redesign seluruh e-SAKIP apabila
dependency tidak terbukti diperlukan.

------------------------------------------------------------------------

# 2. KONDISI DAN KEPUTUSAN BISNIS YANG SUDAH DISEPAKATI

## 2.1 Fokus utama

Untuk saat ini fokus implementasi adalah:

``` text
AdminKab
  ↓
LAKIP Kabupaten
  ↓
Pemilihan IKU sebagai sumber
  ↓
Preview
  ↓
Yakin
  ↓
Input LAKIP
  ↓
Selesai
  ↓
Pengesahan
  ↓
Disahkan
  ↓
Dashboard / PDF / Excel
```

------------------------------------------------------------------------

# 3. ATURAN MUTLAK DATA EXISTING

## 3.1 Jangan hapus data

DILARANG tanpa persetujuan eksplisit user:

``` sql
DELETE
TRUNCATE
DROP TABLE
DROP COLUMN
UPDATE massal yang mengubah makna historis
```

Jangan membuat script cleanup destruktif.

Jika menurut agent deletion benar-benar diperlukan:

1.  STOP.
2.  Jelaskan data apa yang akan dihapus.
3.  Jelaskan alasan teknis dan bisnis.
4.  Tampilkan SQL yang akan dijalankan.
5.  Minta persetujuan user.
6.  Jangan eksekusi sebelum disetujui.

------------------------------------------------------------------------

## 3.2 Jangan mengubah `selesai` menjadi `disahkan`

Ini aturan penting.

Status:

``` text
draft
proses
selesai
disahkan
```

memiliki makna berbeda.

Khusus:

``` text
selesai ≠ disahkan
```

`selesai` berarti data LAKIP telah selesai diinput/dilengkapi.

`disahkan` berarti telah melalui proses pengesahan formal.

DILARANG melakukan:

``` sql
UPDATE lakip
SET status = 'disahkan'
WHERE status = 'selesai';
```

atau variasi sejenis.

Jangan mengasumsikan data lama sudah disahkan hanya karena statusnya
`selesai`.

Jika ada bukti administratif bahwa suatu LAKIP lama memang sudah
disahkan, itu harus ditangani sebagai proses rekonsiliasi terkontrol,
bukan asumsi otomatis.

------------------------------------------------------------------------

# 4. KESELAMATAN DATA ADMIN_OPD

Walaupun fokus sekarang `admin_kab`, database dan beberapa service
LAKIP/IKU dapat digunakan bersama oleh Kabupaten dan OPD.

Karena itu:

## 4.1 Jangan mengubah data AdminOPD

Perubahan AdminKab tidak boleh:

-   mengubah LAKIP OPD;
-   mengubah IKU OPD;
-   mengubah target OPD;
-   mengubah source version OPD;
-   mengubah status pengesahan OPD;
-   menghapus snapshot OPD;
-   mengubah Renstra OPD;
-   mengubah PK OPD;
-   mengubah MONEV OPD.

## 4.2 Perubahan shared service harus scoped

Jika Model/Service dipakai bersama:

``` text
AdminKab
AdminOPD
```

jangan melakukan perubahan global tanpa pemeriksaan.

Gunakan pemisahan scope secara eksplisit:

``` php
if ($scope === 'kabupaten') {
    // aturan Kabupaten
}

if ($scope === 'opd') {
    // aturan OPD
}
```

Jangan mengandalkan asumsi bahwa perubahan query global tidak akan
berdampak ke OPD.

------------------------------------------------------------------------

# 5. KONDISI DATA EXISTING

Sebelum coding, audit terlebih dahulu data aktual.

Jangan berasumsi seluruh LAKIP lama sudah menggunakan mekanisme source
binding baru.

Beberapa kondisi legacy dapat terjadi:

### Kondisi A

Data sudah jelas:

``` text
tahun = 2025
source_type = iku
source_version_id = 118
```

→ pertahankan.

### Kondisi B

Data LAKIP sudah ada tetapi `source_version_id` belum tersimpan.

→ jangan menebak.

Periksa lineage yang tersedia.

Jika hubungan dapat dibuktikan secara deterministik, backfill boleh
direncanakan.

Jika tidak dapat dibuktikan:

``` text
source_version_id = NULL
```

tetap boleh sebagai legacy.

Jangan mengarang hubungan source.

### Kondisi C

Data lama menggunakan RPJMD/Renstra.

→ jangan dihapus.

Tetap dapat dibaca, ditampilkan, dan dicetak sesuai kemampuan legacy.

------------------------------------------------------------------------

# 6. TARGET FLOW BARU ADMIN_KAB

Flow yang harus diimplementasikan:

``` text
1. User memilih Tahun LAKIP
          ↓
2. Sistem menampilkan pilihan IKU resmi
          ↓
3. User bebas memilih versi IKU
          ↓
4. Sistem menampilkan preview IKU
          ↓
5. User menekan "Yakin"
          ↓
6. Sistem mengikat LAKIP ke IKU tersebut
          ↓
7. User mengisi data LAKIP
          ↓
8. Data lengkap → status selesai
          ↓
9. User melakukan Pengesahan
          ↓
10. Sistem menyimpan source version pada pengesahan
          ↓
11. Status menjadi disahkan/final
```

------------------------------------------------------------------------

# 7. TAHUN LAKIP TETAP MENJADI FILTER

Jangan menghapus year filter existing.

Contoh:

``` text
Tahun LAKIP:
[ 2025 ▼ ]
```

Setelah memilih:

``` text
LAKIP Tahun 2025
```

user dapat memilih IKU yang akan digunakan.

------------------------------------------------------------------------

# 8. USER BOLEH MEMILIH IKU VERSI MANAPUN YANG RESMI

Ini keputusan bisnis yang sudah final:

> User AdminKab boleh memilih **IKU resmi versi mana pun**, walaupun
> tahun/masa berlaku IKU tersebut berbeda dengan tahun LAKIP.

Contoh:

``` text
LAKIP 2025
```

boleh menggunakan:

``` text
IKU versi 2025
IKU versi 2026
IKU versi 2024
```

selama versi tersebut memenuhi kriteria sebagai versi resmi yang dapat
digunakan.

Jangan memaksa:

``` text
tahun LAKIP = tahun IKU
```

dan jangan memaksa:

``` text
effective_from IKU harus berada di tahun LAKIP
```

------------------------------------------------------------------------

# 9. STATUS IKU YANG BOLEH DIPILIH

Untuk LAKIP resmi, pilihan IKU harus berasal dari versi yang dianggap
resmi oleh sistem.

Pada audit sebelumnya, status resmi yang relevan antara lain:

``` text
berlaku
superseded
```

Draft/canceled atau status nonresmi jangan ditawarkan sebagai sumber
LAKIP resmi kecuali hasil audit terhadap kode/schema menunjukkan
terminologi aktual berbeda.

WAJIB audit enum/status aktual di database dan code sebelum
implementasi.

------------------------------------------------------------------------

# 10. REKOMENDASI VERSI IKU

Sistem boleh memberikan rekomendasi.

Contoh:

``` text
IKU V2
Tahun/periode: 2026
Status: Berlaku

[Direkomendasikan]
```

Namun:

> **Rekomendasi bukan pembatasan.**

User tetap bebas memilih versi resmi lainnya.

Jangan membuat:

``` text
if not recommended:
    reject
```

------------------------------------------------------------------------

# 11. ALASAN MEMILIH VERSI NON-REKOMENDASI

Alasan override bersifat:

> **OPSIONAL**

Bukan field wajib.

Jika user memilih versi yang tidak direkomendasikan:

``` text
Alasan memilih versi ini (opsional)
[................................]
```

User boleh:

-   mengisi alasan;
-   atau membiarkannya kosong.

Jangan membuat validasi `required_if_not_recommended`.

------------------------------------------------------------------------

# 12. PREVIEW IKU

Sebelum source dikunci, tampilkan preview.

Minimal:

``` text
Tahun LAKIP
IKU Version ID
Label versi
Periode
Effective From
Status
Jumlah Sasaran
Jumlah Indikator
```

Kemudian daftar indikator:

``` text
No
Sasaran
Indikator
Satuan
Target
```

Preview harus mengambil data dari versi IKU yang benar-benar dipilih.

Jangan menampilkan preview dari versi lain karena query fallback.

------------------------------------------------------------------------

# 13. BUTTON "YAKIN"

Gunakan makna yang jelas.

Contoh:

> **Yakin menggunakan IKU ini sebagai acuan LAKIP?**

Setelah user menekan:

``` text
Yakin
```

sistem melakukan source binding.

Source binding minimal harus menyimpan:

``` text
source_type
source_version_id
```

dan identitas source/entity yang memang diperlukan oleh schema aktual.

------------------------------------------------------------------------

# 14. SOURCE BINDING HARUS MENJADI SINGLE SOURCE OF TRUTH

Setelah LAKIP memiliki source:

``` text
LAKIP 2025
   ↓
source_type = iku
source_version_id = 118
```

semua fitur berikut wajib membaca source tersebut:

``` text
Form LAKIP
Dashboard
PDF
Excel
Preview
Pengesahan
Capaian
Target
Analisis
```

DILARANG masing-masing fitur mencari versi IKU sendiri.

Contoh yang salah:

``` text
Dashboard → cari IKU terbaru
PDF       → cari IKU recommended
Excel     → cari IKU pertama
Form      → pakai source_version_id
```

Yang benar:

``` text
LAKIP document
      ↓
source_version_id
      ↓
IKU versi yang sama
      ↓
Form
Dashboard
PDF
Excel
Pengesahan
```

------------------------------------------------------------------------

# 15. PRIORITAS ARSITEKTUR: DOCUMENT-LEVEL SOURCE BINDING

Audit terlebih dahulu apakah struktur existing sudah memiliki
entitas/header LAKIP yang cocok.

Jika belum, pertimbangkan membuat struktur header/document context yang
menyimpan:

``` text
tahun
scope/mode
opd_id bila relevan
source_type
source_version_id
source selection metadata
status
created_by
created_at
updated_at
```

Nama tabel/kolom jangan diada-adakan.

Sebelum membuat schema:

1.  inspect schema aktual;
2.  inspect Model;
3.  inspect Controller;
4.  inspect query;
5.  inspect route;
6.  inspect migration;
7.  tentukan apakah struktur existing dapat digunakan;
8.  hanya jika memang diperlukan, buat struktur baru.

Jangan membuat tabel baru hanya karena terlihat lebih rapi jika existing
schema masih dapat digunakan dengan aman.

------------------------------------------------------------------------

# 16. JANGAN MEMAKSA SNAPSHOT LAMA JIKA TIDAK DIPERLUKAN

Project memiliki konsep/service terkait:

-   source validation;
-   preview;
-   revision;
-   snapshot;
-   source override.

Audit terlebih dahulu penggunaannya.

Jangan sekadar mengaktifkan kembali flow snapshot lama jika flow
tersebut sudah tidak menjadi governance utama.

Gunakan konsep yang masih relevan, terutama:

``` text
source validation
source binding
preview
override metadata
lineage
```

Tetapi tetap pertahankan flow Pengesahan sebagai governance final
apabila itu adalah flow aktual project.

------------------------------------------------------------------------

# 17. SOURCE SWITCHING

Jika LAKIP belum disahkan, user boleh memiliki mekanisme:

``` text
Ganti IKU Acuan
```

Tetapi perubahan source harus:

-   eksplisit;
-   terlihat oleh user;
-   tidak diam-diam;
-   tidak menghapus data otomatis.

Contoh:

``` text
IKU saat ini:
IKU V2 — ID 118

[ Ganti IKU Acuan ]
```

Ketika source diganti:

``` text
Peringatan:
Mengganti IKU acuan dapat memengaruhi daftar indikator dan target
yang ditampilkan pada LAKIP.

Data lama tidak akan dihapus otomatis.

Lanjutkan?
```

Jika mapping indikator tidak aman:

> blok perubahan dan jelaskan alasannya.

Jangan melakukan auto-delete atau auto-remapping yang berisiko.

------------------------------------------------------------------------

# 18. STATUS LAKIP

Audit seluruh status aktual terlebih dahulu.

Saat ini dapat ditemukan vocabulary seperti:

``` text
draft
selesai
proses
siap
```

Jangan langsung mengganti semua data.

Pisahkan:

### Status pengisian

``` text
draft
proses
selesai
```

### Status formal

``` text
belum disahkan
disahkan
```

Jika perlu perubahan vocabulary, buat compatibility mapping.

Target konseptual:

``` text
DRAFT
  ↓
PROSES
  ↓
SELESAI
  ↓
PENGESAHAN
  ↓
DISAHKAN
```

`selesai` bukan `disahkan`.

------------------------------------------------------------------------

# 19. PENGESAHAN

Pengesahan harus menjadi proses formal.

Sebelum pengesahan, server WAJIB memvalidasi:

-   LAKIP memiliki source binding;
-   source version valid;
-   seluruh indikator wajib telah diisi;
-   target/capaian yang wajib tersedia;
-   tidak ada baris invalid;
-   tidak ada source version campuran;
-   scope/year sesuai;
-   user memiliki permission;
-   data belum final/disahkan.

Jangan hanya memeriksa:

``` text
ada minimal 1 capaian
```

karena itu tidak cukup untuk menyatakan LAKIP lengkap.

------------------------------------------------------------------------

# 20. PENGESAHAN HARUS MENYIMPAN IKU VERSION

Ini requirement wajib.

Ketika LAKIP Kabupaten disahkan, informasi pengesahan harus dapat
menunjukkan:

``` text
Tahun LAKIP
Source Type
IKU Version ID
Nomor pengesahan
Tanggal
User/pengesah
Catatan
Status
```

Jika struktur `lakip_pengesahan` belum memiliki
`source_type`/`source_version_id`, lakukan audit lalu tambahkan secara
aman bila diperlukan.

Jangan menghapus data pengesahan lama.

------------------------------------------------------------------------

# 21. FINAL = DISAHKAN

Jangan menganggap:

``` text
semua baris selesai
```

sebagai:

``` text
FINAL
```

Final/official harus berasal dari pengesahan formal.

Contoh:

``` text
LAKIP 2025
status input = selesai
pengesahan = belum
```

→ belum final.

Sedangkan:

``` text
LAKIP 2025
status input = selesai
pengesahan = disahkan
```

→ final/official.

------------------------------------------------------------------------

# 22. DATA LAMA YANG BELUM MEMILIKI SOURCE VERSION

Jika existing LAKIP tidak mempunyai source_version_id:

1.  jangan hapus;
2.  jangan tebak;
3.  jangan mengubah source;
4.  cari lineage;
5.  jika deterministik → rencanakan backfill;
6.  jika ambigu → biarkan legacy;
7.  tampilkan status kompatibilitas jika diperlukan.

Backfill harus selalu memiliki:

``` text
preflight SQL
backup
UPDATE terukur
post-check
rollback strategy
```

Tidak boleh:

``` sql
UPDATE semua row
```

tanpa identifikasi record.

------------------------------------------------------------------------

# 23. TARGET DATA IKU

Audit bug existing terkait perbedaan struktur target.

Pastikan controller/service/view tidak mengasumsikan field yang salah.

Jika service mengembalikan:

``` text
target
```

tetapi view mengharapkan:

``` text
target_tahunan
```

buat normalisasi di service/DTO/presenter yang tepat.

Contoh konsep:

``` php
$targetTahunIni = ...
```

dan seluruh view memakai field yang konsisten.

Jangan membuat patch satu view yang menyebabkan view lain tetap salah.

------------------------------------------------------------------------

# 24. SOURCE VERSION HARUS IKUT DALAM QUERY

Audit seluruh query LAKIP.

Jika query saat ini hanya menggunakan:

``` text
tahun
opd_id
source_type
source_entity_id
```

periksa apakah harus ditambah:

``` text
source_version_id
```

Identitas source minimal harus mempertimbangkan:

``` text
tahun
scope
source_type
source_version_id
source_entity_id
```

sesuai struktur aktual.

Tujuan:

> mencegah data dari IKU versi berbeda tercampur.

------------------------------------------------------------------------

# 25. PERBAIKI QUERY PEMETAAN LAKIP KE IKU

Jika pada code aktual terdapat method dengan fungsi seperti:

``` text
getLakipMapIku()
getLakipByIku()
```

audit dan perbaiki.

Pastikan tidak terjadi:

``` text
LAKIP
source_version_id = 118
```

tetapi query mengambil indikator dari versi:

``` text
116
```

karena query hanya berdasarkan `source_entity_id`.

Nama method boleh berbeda pada project aktual. Jangan mengasumsikan nama
method; cari berdasarkan fungsi.

------------------------------------------------------------------------

# 26. DASHBOARD ADMIN_KAB

Dashboard harus otomatis menggunakan LAKIP resmi yang relevan, tetapi:

> **Hanya LAKIP yang sudah disahkan yang boleh menjadi official annual
> result.**

Flow:

``` text
Dashboard
   ↓
tentukan relevant annual LAKIP
   ↓
cari LAKIP tahun tersebut
   ↓
cek pengesahan
   │
   ├── BELUM DISAHKAN
   │       ↓
   │   tampilkan status:
   │   "LAKIP 2025 belum disahkan"
   │
   └── SUDAH DISAHKAN
           ↓
       ambil source_version_id
           ↓
       gunakan IKU versi tersebut
           ↓
       hitung official annual result
```

Dashboard tidak boleh memilih IKU secara independen.

------------------------------------------------------------------------

# 27. TAHUN DASHBOARD

Konsep existing:

``` text
tahun berjalan
   ↓
monitoring PK/MONEV
```

dan:

``` text
official annual LAKIP
   ↓
tahun LAKIP yang relevan/jatuh tempo
```

Pertahankan konsep tersebut jika hasil audit mengonfirmasi implementasi
existing.

Contoh:

``` text
Dashboard 2026
```

dapat menampilkan:

``` text
Monitoring berjalan 2026
```

sementara:

``` text
Official annual LAKIP
```

menggunakan LAKIP tahun yang memang sudah selesai/jatuh tempo, misalnya
2025.

Jangan memaksakan LAKIP 2026 yang belum final sebagai official annual
result.

------------------------------------------------------------------------

# 28. JIKA LAKIP BELUM DISAHKAN

Jangan menampilkan score seolah-olah official.

Gunakan status informatif seperti:

``` text
LAKIP 2025 belum disahkan
```

atau:

``` text
Data LAKIP 2025 tersedia, tetapi belum disahkan.
```

Jangan memberi kesan bahwa angka tersebut adalah hasil resmi.

------------------------------------------------------------------------

# 29. LABEL DASHBOARD

Audit label existing.

Jika card bernama:

``` text
Capaian PK Bupati
```

tetapi sumber aktualnya adalah service LAKIP Kabupaten, jangan
mempertahankan label yang semantik-nya salah.

Pertimbangkan label yang sesuai sumber aktual, misalnya:

``` text
Capaian Kinerja Kabupaten
```

atau:

``` text
Capaian LAKIP Kabupaten
```

Tetapkan berdasarkan audit service dan makna data aktual.

------------------------------------------------------------------------

# 30. DASHBOARD HARUS MENAMPILKAN TRACEABILITY

Jika LAKIP disahkan, Dashboard sebaiknya menampilkan metadata kecil:

``` text
LAKIP 2025
IKU V2
IKU Revision ID: 118
Disahkan
```

atau bentuk UI yang lebih natural.

Tujuannya agar user dapat mengetahui:

> angka dashboard berasal dari dokumen/source mana.

------------------------------------------------------------------------

# 31. PDF DAN EXCEL

PDF dan Excel harus menggunakan source binding yang sama dengan LAKIP.

DILARANG:

``` text
Screen → IKU 118
PDF    → IKU 116
Excel  → IKU 118
```

Semua harus:

``` text
LAKIP source_version_id
        ↓
PDF
Excel
Screen
Dashboard
```

Jika link print saat ini hanya membawa:

``` text
year
mode
status
opd
```

audit apakah harus membawa document ID atau source context.

Lebih baik PDF/Excel mengambil LAKIP document yang sudah tersimpan
daripada mencari ulang versi IKU berdasarkan tahun.

------------------------------------------------------------------------

# 32. LEGACY LAKIP RPJMD/RENSTRA

Existing data yang masih menggunakan:

``` text
RPJMD
RENSTRA
```

harus tetap dapat dibaca.

Jangan memaksa semua data lama dikonversi menjadi IKU.

Compatibility bridge boleh dipertahankan jika diperlukan.

Namun:

> untuk LAKIP baru dengan flow baru, source binding harus eksplisit.

------------------------------------------------------------------------

# 33. ADMIN_OPD: REGRESSION ONLY UNTUK SEKARANG

Kita belum melakukan redesign AdminOPD.

Tetapi setelah perubahan AdminKab:

WAJIB test:

``` text
AdminOPD login
AdminOPD lihat LAKIP
AdminOPD input
AdminOPD edit
AdminOPD update
AdminOPD delete jika memang tersedia
AdminOPD print PDF
AdminOPD export Excel
AdminOPD pengesahan
```

Pastikan tidak terjadi regresi.

Jika ditemukan bug existing AdminOPD yang tidak berkaitan langsung
dengan AdminKab:

1.  catat;
2.  jangan otomatis memperluas scope;
3.  laporkan;
4.  hanya perbaiki jika dependency/keamanan/data integrity mengharuskan.

------------------------------------------------------------------------

# 34. KEAMANAN

Audit dan perbaiki minimal:

## Authorization

Pastikan:

``` text
admin_kab
```

tidak dapat mengakses route OPD secara tidak semestinya.

Pastikan:

``` text
admin_opd
```

tidak dapat membaca/mengubah LAKIP Kabupaten melalui IDOR.

Jangan hanya mengandalkan ID.

Validasi scope/year/OPD sesuai kebutuhan.

------------------------------------------------------------------------

## CSRF

Audit seluruh form POST/PUT/DELETE.

Project sebelumnya memiliki area yang pernah menonaktifkan CSRF.

Pastikan flow baru tidak membutuhkan CSRF disabled.

Jangan menghapus CSRF protection hanya agar form bekerja.

------------------------------------------------------------------------

## Validation

Validasi:

-   year;
-   source_version_id;
-   source_type;
-   document ID;
-   indicator ID;
-   scope;
-   status transition.

Jangan percaya hidden input.

------------------------------------------------------------------------

## IDOR

Test ID yang bukan milik scope user.

Harus ditolak.

------------------------------------------------------------------------

# 35. TRANSACTION

Operasi berikut wajib transactional:

### Source binding

``` text
buat/ubah LAKIP document
+
source binding
```

### Pengesahan

``` text
validasi
+
simpan pengesahan
+
update status
```

Jika salah satu gagal:

``` text
ROLLBACK
```

Jangan menghasilkan kondisi:

``` text
pengesahan tersimpan
status belum berubah
```

atau:

``` text
status disahkan
pengesahan gagal
```

------------------------------------------------------------------------

# 36. CONCURRENCY

Cegah dua user melakukan perubahan source/pengesahan bersamaan.

Audit:

-   transaction;
-   row locking;
-   status check;
-   optimistic check jika tersedia.

Contoh:

``` text
User A → Yakin IKU 118
User B → Ganti IKU 116
```

Sistem harus memiliki aturan yang konsisten.

------------------------------------------------------------------------

# 37. SQL WAJIB JIKA ADA PERUBAHAN DATABASE

Jika schema/data perlu diubah, agent WAJIB menghasilkan:

## A. Preflight SQL

Untuk mengetahui kondisi sebelum perubahan.

## B. Migration

CI4 migration sesuai schema aktual.

## C. Production SQL

Jika user meminta SQL manual, berikan SQL yang aman.

## D. Post-check SQL

Membandingkan:

``` text
before
vs
after
```

## E. Rollback plan

Jelaskan cara membatalkan perubahan.

Jangan membuat SQL generik yang tidak cocok dengan schema aktual.

------------------------------------------------------------------------

# 38. JANGAN LANGSUNG MENJALANKAN SQL PRODUKSI

Jika agent memiliki akses terminal/database:

1.  inspect;
2.  backup/check;
3.  tampilkan rencana;
4.  baru lakukan perubahan non-destruktif yang sudah disetujui.

Untuk operasi destruktif:

> wajib STOP dan minta persetujuan.

------------------------------------------------------------------------

# 39. WAJIB AUDIT KODE SEBELUM IMPLEMENTASI

Sebelum mengubah code, cari dan petakan:

``` text
Routes
Controllers
Models
Services
Views
Libraries
Queries
Migrations
Commands
PDF
Excel
Dashboard
Pengesahan
```

Khusus cari:

``` text
lakip
lakip_pengesahan
iku_revisi
source_version_id
source_type
snapshot
pengesahan
LakipSourceService
LakipRevisionService
LakipKabupatenCapaianService
```

Nama class/method dapat berbeda.

Gunakan semantic search/grep berdasarkan fungsi, bukan asumsi nama.

------------------------------------------------------------------------

# 40. HASIL FASE 0 YANG WAJIB DIBERIKAN SEBELUM CODING

Jangan langsung coding.

Berikan laporan:

## A. Struktur

``` text
file
class
method
route
view
table
```

## B. Existing flow

``` text
Pilih tahun
↓
...
```

## C. Data existing

Berikan statistik:

``` text
jumlah LAKIP Kabupaten
per tahun
per status
per source_type
per source_version_id
```

## D. Data yang berisiko

Contoh:

``` text
15 row tidak memiliki source_version_id
```

## E. Dependency AdminOPD

Tampilkan:

``` text
shared table
shared service
shared model
```

## F. Daftar bug

Kelompokkan:

``` text
Critical
High
Medium
Low
```

## G. Rekomendasi schema

Hanya jika diperlukan.

------------------------------------------------------------------------

# 41. FASE IMPLEMENTASI

Setelah audit disetujui, lakukan bertahap.

## PHASE 1 --- SAFETY & BUG FIX

Perbaiki bug yang tidak memerlukan perubahan besar:

-   target display;
-   source filtering;
-   status inconsistency;
-   authorization;
-   CSRF;
-   validation;
-   error handling;
-   PDF/Excel source consistency.

Test.

------------------------------------------------------------------------

## PHASE 2 --- SOURCE BINDING

Implementasikan source binding document-level.

Test:

``` text
create
read
edit
update
```

dan regression.

------------------------------------------------------------------------

## PHASE 3 --- WIZARD ADMIN_KAB

Implementasikan:

``` text
Tahun
↓
Pilih IKU
↓
Preview
↓
Yakin
↓
Input
```

Test cross-year dan cross-version.

------------------------------------------------------------------------

## PHASE 4 --- PENGESAHAN

Implementasikan:

``` text
Selesai
↓
Validasi
↓
Pengesahan
↓
Source version tersimpan
↓
Disahkan
```

Test incomplete data.

------------------------------------------------------------------------

## PHASE 5 --- PDF / EXCEL

Pastikan source exact.

Test minimal:

``` text
IKU 116
IKU 118
```

jika dua versi tersedia.

Pastikan output tidak tercampur.

------------------------------------------------------------------------

## PHASE 6 --- DASHBOARD

Dashboard:

``` text
relevant LAKIP
↓
signed?
↓
source_version_id
↓
exact source
```

Test:

``` text
belum disahkan
sudah disahkan
versi non-recommended
legacy
```

------------------------------------------------------------------------

## PHASE 7 --- REGRESSION ADMIN_OPD

Tidak redesign.

Hanya pastikan tidak rusak.

------------------------------------------------------------------------

# 42. TESTING WAJIB

Testing tidak boleh hanya happy path.

## CREATE

-   create LAKIP;
-   pilih tahun;
-   pilih IKU;
-   preview;
-   yakin.

## READ

-   buka LAKIP;
-   lihat source;
-   lihat target;
-   lihat indikator.

## UPDATE

-   ubah data;
-   ganti source jika masih diizinkan;
-   pastikan data tidak hilang.

## DELETE

Jika ada fitur delete:

-   test permission;
-   test scope;
-   test confirmation;
-   test tidak terjadi cascade delete berbahaya.

## PENGESAHAN

Test:

``` text
data incomplete → ditolak
data lengkap → boleh
already signed → ditolak untuk edit normal
```

## CROSS VERSION

``` text
LAKIP 2025 → IKU 118
LAKIP 2025 → IKU 116
```

Pastikan data tidak tercampur.

## CROSS YEAR

``` text
LAKIP 2025
LAKIP 2026
```

Pastikan source masing-masing independen.

## SECURITY

Test IDOR.

## REGRESSION

Test AdminOPD.

------------------------------------------------------------------------

# 43. TEST DATA YANG WAJIB DICEK

Minimal buat skenario:

### Skenario 1

``` text
LAKIP 2025
IKU 118
status selesai
belum disahkan
```

Dashboard:

``` text
belum disahkan
```

------------------------------------------------------------------------

### Skenario 2

``` text
LAKIP 2025
IKU 118
status selesai
disahkan
```

Dashboard:

``` text
official result
IKU 118
```

------------------------------------------------------------------------

### Skenario 3

``` text
LAKIP 2025
IKU 116
```

walaupun IKU 116 bukan rekomendasi.

Harus tetap valid jika status IKU resmi.

------------------------------------------------------------------------

### Skenario 4

``` text
LAKIP 2025
IKU legacy/RPJMD
```

Harus tetap dapat dibaca jika merupakan data existing.

------------------------------------------------------------------------

### Skenario 5

``` text
LAKIP OPD
```

Pastikan tidak berubah.

------------------------------------------------------------------------

# 44. ACCEPTANCE CRITERIA

Implementasi dianggap berhasil hanya jika seluruh poin berikut
terpenuhi.

## Data

-   [ ] Tidak ada data existing yang dihapus.
-   [ ] Tidak ada `selesai` yang otomatis menjadi `disahkan`.
-   [ ] Tidak ada data AdminOPD yang berubah karena migrasi AdminKab.
-   [ ] Existing legacy tetap terbaca.
-   [ ] User tidak perlu input ulang data yang valid.

## LAKIP AdminKab

-   [ ] Year filter tetap ada.
-   [ ] User bebas memilih IKU resmi.
-   [ ] Pilihan tidak dibatasi tahun yang sama.
-   [ ] Versi resmi lama/superseded dapat dipilih jika memang resmi.
-   [ ] Draft/nonresmi tidak dipakai sebagai source resmi.
-   [ ] Preview tersedia.
-   [ ] Tombol "Yakin" tersedia.
-   [ ] Source binding tersimpan.
-   [ ] Alasan non-recommended optional.

## Pengesahan

-   [ ] Tidak otomatis disahkan.
-   [ ] Validasi completeness server-side.
-   [ ] Source version tersimpan.
-   [ ] Pengesahan memiliki lineage yang jelas.
-   [ ] Setelah disahkan, edit normal dibatasi.

## Dashboard

-   [ ] Tidak memilih IKU sendiri.
-   [ ] Mengikuti LAKIP yang relevan.
-   [ ] Hanya signed LAKIP menjadi official result.
-   [ ] Jika belum signed, tampil status yang jelas.
-   [ ] Menggunakan exact source_version_id.
-   [ ] Label sesuai semantik data.

## PDF/Excel

-   [ ] Menggunakan source exact.
-   [ ] Tidak re-resolve IKU berdasarkan tahun.
-   [ ] Output sama dengan screen.

## Security

-   [ ] CSRF aktif.
-   [ ] Authorization benar.
-   [ ] IDOR ditolak.
-   [ ] Scope AdminKab/AdminOPD terisolasi.

## Database

-   [ ] Ada preflight.
-   [ ] Ada migration jika diperlukan.
-   [ ] Tidak ada destructive SQL tanpa approval.
-   [ ] Ada post-check.
-   [ ] Ada rollback strategy.

------------------------------------------------------------------------

# 45. ATURAN IMPLEMENTASI UNTUK CODING AGENT

Selalu ikuti urutan:

``` text
INSPECT
  ↓
UNDERSTAND
  ↓
REPORT
  ↓
PLAN
  ↓
CONFIRM IF DESTRUCTIVE
  ↓
IMPLEMENT
  ↓
TEST
  ↓
VERIFY DATABASE
  ↓
REPORT
```

Jangan:

``` text
GUESS
  ↓
CODE
  ↓
UPDATE DATABASE
```

------------------------------------------------------------------------

# 46. ERROR HANDLING

Jangan menampilkan exception mentah kepada user.

Jangan bocorkan:

``` text
SQL
filesystem path
stack trace
credential
internal table structure
```

Gunakan:

``` text
log internal
+
pesan user yang aman
```

------------------------------------------------------------------------

# 47. LOGGING / AUDIT TRAIL

Untuk tindakan penting:

``` text
source selection
source change
pengesahan
```

simpan audit trail jika infrastructure project mendukung.

Minimal catat:

``` text
user
timestamp
document
old value
new value
reason jika ada
```

Reason source override tetap optional.

------------------------------------------------------------------------

# 48. JANGAN MENGUBAH DOMAIN YANG TIDAK TERKAIT

Jangan redesign:

``` text
RPJMD
Renstra
PK
Cascading
RKT/Rencana Aksi
MONEV
Dashboard OPD
```

kecuali audit membuktikan perubahan tersebut merupakan dependency wajib
untuk membuat AdminKab LAKIP benar.

Jika ditemukan dependency:

``` text
STOP
jelaskan dependency
tunjukkan impact
```

baru lanjut sesuai persetujuan.

------------------------------------------------------------------------

# 49. OUTPUT SETIAP FASE

Setelah setiap fase, laporkan:

## 1. Apa yang diperiksa

## 2. Apa yang ditemukan

## 3. Apa yang diubah

## 4. File yang berubah

## 5. Database yang berubah

## 6. SQL yang digunakan

## 7. Test yang dijalankan

## 8. Hasil test

## 9. Risiko tersisa

## 10. Apakah aman lanjut ke fase berikutnya

------------------------------------------------------------------------

# 50. FORMAT LAPORAN DATABASE

Jika mengubah database, berikan tabel:

  Item                       Before   After Keterangan
  ------------------------ -------- ------- ---------------------
  Total LAKIP Kabupaten         ...     ... harus konsisten
  LAKIP 2025                    ...     ... 
  LAKIP 2026                    ...     ... 
  Status selesai                ...     ... 
  Status disahkan               ...     ... 
  source_version_id NULL        ...     ... 
  Data OPD                      ...     ... harus tidak berubah

Data AdminOPD harus dibandingkan sebelum dan sesudah perubahan jika ada
perubahan schema/data yang berpotensi shared.

------------------------------------------------------------------------

# 51. ATURAN BACKFILL

Backfill hanya boleh dilakukan jika:

``` text
old data
   ↓
lineage dapat dibuktikan
   ↓
source version dapat ditentukan secara deterministic
```

Contoh aman:

``` text
LAKIP existing
source_entity_id
+
tahun
+
lineage
→
hanya cocok dengan IKU revision 118
```

Jika ada dua kemungkinan:

``` text
118
atau
116
```

jangan memilih otomatis.

Tandai sebagai ambiguous.

------------------------------------------------------------------------

# 52. ATURAN MIGRATION

Migration harus:

-   idempotent bila memungkinkan;
-   tidak menghapus data;
-   backward-compatible;
-   memiliki default/null yang aman;
-   memiliki index yang sesuai;
-   tidak mengubah semantics existing tanpa kebutuhan.

Jika menambahkan:

``` text
source_version_id
```

jangan langsung membuat:

``` text
NOT NULL
```

jika existing data belum semuanya dapat di-backfill secara
deterministik.

Lebih aman:

``` text
NULL
```

terlebih dahulu, lalu lakukan validasi pada flow baru.

------------------------------------------------------------------------

# 53. SOURCE OF TRUTH

Setelah flow baru berjalan, tetapkan:

``` text
LAKIP Document
      ↓
source_type
source_version_id
      ↓
source data
```

Semua downstream harus membaca dari sini.

Jangan membuat setiap service memiliki algoritma pemilihan IKU sendiri.

Jika ada:

``` text
LakipSourceService
LakipKabupatenCapaianService
PDF service
Excel service
Dashboard
```

pastikan mereka menggunakan source binding document.

------------------------------------------------------------------------

# 54. KETIKA ADA KONFLIK ANTARA CODE LAMA DAN FLOW BARU

Prioritas:

1.  Data existing aman.
2.  Source binding benar.
3.  Security.
4.  Data integrity.
5.  Pengesahan formal.
6.  Konsistensi Dashboard/PDF/Excel.
7.  UX.

Jangan mengorbankan data existing hanya agar flow baru terlihat bersih.

------------------------------------------------------------------------

# 55. HASIL AKHIR YANG DIHARAPKAN

Setelah implementasi selesai, user AdminKab harus mengalami flow
sederhana:

``` text
┌─────────────────────────┐
│  LAKIP KABUPATEN        │
├─────────────────────────┤
│ Tahun: [2025 ▼]         │
└────────────┬────────────┘
             ↓
┌─────────────────────────┐
│ Pilih IKU Acuan         │
│                         │
│ IKU V2                  │
│ ID: 118                 │
│ Status: Superseded      │
│ Periode: 2025–2029      │
│                         │
│ IKU V3                  │
│ ID: 116                 │
│ Status: Berlaku         │
│ Periode: 2025–2029      │
└────────────┬────────────┘
             ↓
          PREVIEW
             ↓
       [ YAKIN ]
             ↓
       INPUT LAKIP
             ↓
         SELESAI
             ↓
       PENGESAHAN
             ↓
        DISAHKAN
             ↓
      OFFICIAL RESULT
```

Dan setelah disahkan:

``` text
Dashboard
    ↓
LAKIP 2025
    ↓
IKU Version 118
    ↓
hasil yang sama
```

``` text
PDF
    ↓
IKU Version 118
```

``` text
Excel
    ↓
IKU Version 118
```

Tidak boleh ada service yang tiba-tiba memilih IKU 116 karena menganggap
itu versi terbaru/recommended.

------------------------------------------------------------------------

# 56. PERINTAH AKHIR UNTUK AGENT

Mulai pekerjaan dengan **PHASE 0 --- AUDIT ONLY**.

Pada Phase 0:

-   jangan ubah database;
-   jangan menjalankan DELETE;
-   jangan menjalankan UPDATE;
-   jangan membuat migration;
-   jangan mengubah source code;
-   jangan mengubah status LAKIP.

Lakukan:

1.  inspect repository;
2.  inspect route;
3.  inspect controller;
4.  inspect Model;
5.  inspect Service;
6.  inspect view;
7.  inspect migration;
8.  inspect database schema;
9.  inspect data existing;
10. inspect AdminKab;
11. inspect AdminOPD dependency;
12. inspect Dashboard;
13. inspect PDF;
14. inspect Excel;
15. inspect Pengesahan;
16. inspect source/version resolution;
17. buat laporan dependency dan risiko.

Setelah Phase 0 selesai, berhenti dan berikan laporan.

**Jangan lanjut Phase 1 sebelum hasil audit dapat ditinjau.**

------------------------------------------------------------------------

# 57. KALIMAT PEGANGAN PROJECT

Gunakan prinsip berikut selama seluruh implementasi:

> **"Kita tidak memperbaiki sistem dengan mengorbankan data lama."**

> **"Selesai bukan berarti disahkan."**

> **"AdminKab adalah fokus implementasi, AdminOPD adalah area regression
> protection."**

> **"IKU yang dipakai LAKIP harus merupakan IKU yang dipilih dan diikat
> pada dokumen, bukan IKU yang ditebak ulang oleh
> Dashboard/PDF/Excel."**

> **"User boleh memilih versi IKU resmi mana pun; rekomendasi bukan
> pembatasan."**

> **"Alasan memilih versi non-rekomendasi bersifat optional."**

> **"Tidak ada destructive migration tanpa persetujuan."**

> **"Jika source lama tidak dapat dibuktikan secara deterministik,
> jangan menebak."**

> **"Setiap perubahan database harus dapat diverifikasi before/after."**

> **"Kerjakan bertahap, test setiap tahap, dan laporkan hasil sebelum
> melanjutkan."**

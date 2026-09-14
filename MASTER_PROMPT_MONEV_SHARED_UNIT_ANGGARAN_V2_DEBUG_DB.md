# MASTER PROMPT — SHARED PROGRAM/KEGIATAN/SUB KEGIATAN PADA MONEV ANGGARAN
## Project baseline: E-Sakip_CI(6) + database terbaru yang tersedia
## Fokus: satu unit anggaran dipakai ≥2 indikator, input realisasi tetap per indikator, validasi pagu, dan Dashboard

Anda bekerja pada project terbaru **e-SAKIP / AKSARA Kabupaten Pringsewu** berbasis **CodeIgniter 4 + MySQL/MariaDB**.

Task ini mempunyai keputusan bisnis yang SUDAH DISETUJUI:

> Satu Program/Kegiatan/Sub Kegiatan dapat mendukung dua atau lebih indikator PK.

> Hubungan unit anggaran dengan indikator tetap ditampilkan pada masing-masing indikator.

> Realisasi anggaran TW I–TW IV tetap dapat diinput berbeda untuk masing-masing indikator, walaupun Program/Kegiatan/Sub Kegiatannya sama.

> Nilai yang diinput pada masing-masing indikator berarti **bagian realisasi anggaran yang digunakan untuk mendukung indikator tersebut**, bukan salinan realisasi unit secara keseluruhan.

> Total seluruh realisasi TW I–TW IV dari semua indikator yang memakai unit anggaran yang sama TIDAK BOLEH melebihi pagu unit tersebut.

> Tampilan khusus “satu unit → beberapa indikator → beberapa input TW I–IV” HANYA berlaku apabila unit yang sama benar-benar dipakai oleh **2 atau lebih indikator berbeda**.

Jangan redesign modul lain yang tidak berkaitan.

---

# 0. ATURAN DATA — SANGAT PENTING

JANGAN menghapus, mereset, menggabungkan, memindahkan, atau menimpa data existing secara destructive.

Jika menemukan data existing yang:
- melebihi pagu;
- terlihat duplicate;
- kemungkinan merupakan copy realisasi yang sama pada beberapa indikator;
- orphan;
- salah referensi;
- perlu cleanup;

lakukan:

```text
AUDIT
↓
laporkan jumlah & contoh
↓
beri warning di aplikasi bila relevan
↓
JANGAN ubah datanya
↓
minta persetujuan saya jika cleanup diperlukan
```

Untuk task ini, data existing yang melebihi pagu **tetap dipertahankan**.

User harus dapat melihatnya dan memperbaikinya melalui form.

---

# 1. AUDIT SOURCE & DATABASE TERLEBIH DAHULU

Jangan langsung coding.

Audit minimal:

```text
app/Models/Opd/TargetModel.php
app/Models/Opd/MonevModel.php
app/Controllers/AdminOpd/PkRenaksiController.php
app/Views/adminOpd/pk_renaksi/monev.php
app/Views/adminOpd/pk_renaksi/monev_anggaran_form.php
app/Services/OpdDashboardService.php
app/Services/KabupatenDashboardService.php
app/Commands/DashVerify.php
app/Helpers/pk_unit_helper.php
app/Config/Routes.php
```

Audit juga:

```text
pk
pk_sasaran
pk_indikator
pk_program
pk_kegiatan
pk_subkegiatan
program_pk
kegiatan_pk
sub_kegiatan_pk
target_rencana
monev_anggaran
```

Verifikasi semua nama tabel/kolom dari source/database aktual.

---

# 2. BASELINE STRUKTUR DATABASE YANG SUDAH DITEMUKAN

Database sekarang mempunyai konsep:

```text
monev_anggaran
├── id
├── target_rencana_id
├── opd_id
├── ref_level
│   ├── program
│   ├── kegiatan
│   └── subkegiatan
├── ref_id
├── ref_key
├── realisasi_triwulan_1
├── realisasi_triwulan_2
├── realisasi_triwulan_3
└── realisasi_triwulan_4
```

Unique key existing:

```text
(target_rencana_id, ref_key)
```

`ref_key` secara konsep:

```text
program:123
kegiatan:456
subkegiatan:789
```

Struktur ini secara prinsip **SUDAH SESUAI** dengan business rule baru karena:

```text
Indikator A / target_rencana A
+ Program X
→ satu row realisasi

Indikator B / target_rencana B
+ Program X yang sama
→ row realisasi lain
```

Keduanya bukan duplicate jika nilainya memang bagian realisasi yang berbeda untuk masing-masing indikator.

## JANGAN mengubah unique key menjadi:

```text
(opd_id, ref_level, ref_id)
```

karena itu akan memaksa satu realisasi global per unit dan menghilangkan kemampuan input berbeda per indikator.

JANGAN menghapus `target_rencana_id` dari `monev_anggaran`.

---

# 3. CATATAN ARSITEKTUR YANG HARUS DIAUDIT

Saat ini attribution realisasi terikat ke:

```text
target_rencana_id
```

bukan langsung:

```text
pk_indikator_id
```

Pada database yang diaudit sebelumnya, setiap indikator PK yang sudah mempunyai Rencana Aksi hanya mempunyai satu `target_rencana`.

Jadi pada kondisi sekarang:

```text
target_rencana_id
≈
konteks indikator
```

Namun jangan mengasumsikan selamanya.

Audit apakah business rule aplikasi memang:

```text
1 indikator PK
→ maksimal 1 target_rencana parent
→ detail pelaksanaan ada di target_sub_rencana
```

Jika ya, current schema aman untuk model ini.

Jika ternyata satu indikator dapat mempunyai beberapa `target_rencana` parent, STOP sebelum melakukan perubahan schema dan laporkan karena grain realisasi perlu diputuskan lebih dahulu.

Jangan membuat constraint baru tanpa persetujuan.

---

# 4. TEMUAN DATABASE YANG HARUS DIRECHECK AGENT

Pada database terakhir yang diaudit, shared unit bukan edge case.

Ditemukan banyak unit PK yang dipakai lebih dari satu indikator dalam OPD/tahun yang sama, termasuk:

```text
Program       : banyak kasus
Kegiatan      : banyak kasus
Sub Kegiatan  : banyak kasus
```

Juga sudah ada `monev_anggaran` di mana unit yang sama mempunyai row realisasi pada beberapa indikator berbeda.

Bahkan ditemukan kondisi:

```text
masing-masing row indikator sendiri <= pagu
TETAPI
jika seluruh row indikator pada unit yang sama dijumlahkan
> pagu unit
```

Jadi validasi existing yang hanya melihat satu indikator tidak cukup.

Agent WAJIB menghitung ulang angka aktual dari database yang sedang dipakai.
Jangan hardcode jumlah dari prompt ini.

---

# 5. DEFINISI BUSINESS RULE REALISASI

Misalnya:

```text
PROGRAM X
Pagu = Rp1.000.000.000

Mendukung:
Indikator A
Indikator B
```

Input:

```text
Indikator A
TW I  = Rp150.000.000
TW II = Rp100.000.000

Indikator B
TW I  = Rp75.000.000
TW II = Rp125.000.000
```

Maka realisasi Program X:

```text
TW I  = 150 + 75  = Rp225.000.000
TW II = 100 + 125 = Rp225.000.000

TOTAL TAHUNAN
= Rp450.000.000
```

Ini sah.

Input Indikator A TIDAK boleh otomatis mengganti Indikator B.

Input Indikator B TIDAK boleh otomatis mengganti Indikator A.

---

# 6. PENGERTIAN ANGKA PER INDIKATOR

Gunakan bahasa bisnis:

```text
“Realisasi untuk indikator ini”
```

Maknanya:

> Bagian realisasi Program/Kegiatan/Sub Kegiatan yang digunakan untuk mendukung indikator tersebut.

Jangan memakai istilah teknis seperti:

```text
attribution
grain
ref_key
target_rencana_id
```

di UI user.

Internal code boleh memakai istilah tersebut.

---

# 7. RISIKO DOUBLE INPUT YANG HARUS DICEGAH

Jika satu pengeluaran Rp100 juta sebenarnya hanya terjadi sekali tetapi user menginput:

```text
Indikator A = Rp100 juta
Indikator B = Rp100 juta
```

sistem akan menganggap:

```text
Total realisasi unit = Rp200 juta
```

Karena itu UI shared unit WAJIB memperlihatkan realisasi indikator lain agar operator tidak menginput nilai yang sama dua kali tanpa sadar.

Gunakan bantuan teks pendek:

```text
Program ini digunakan pada beberapa indikator.
Isi hanya realisasi yang digunakan untuk masing-masing indikator.
```

atau redaksi lain yang lebih natural dan singkat.

---

# 8. KAPAN TAMPILAN SHARED UNIT AKTIF

Tampilan baru HANYA aktif jika exact unit yang sama digunakan oleh minimal 2 indikator berbeda dalam scope:

```text
OPD yang sama
+
tahun PK yang sama
+
ref_level yang sama
+
ref_id yang sama
```

Unit:

```text
Program
Kegiatan
Sub Kegiatan
```

harus diperlakukan terpisah.

Jangan menganggap:

```text
Program ID 10
```

sama dengan:

```text
Kegiatan ID 10
```

Gunakan pasangan:

```text
ref_level + ref_id
```

---

# 9. JANGAN GROUP LINTAS OPD

Shared unit harus tetap di-scope:

```text
opd_id
```

Jangan menggabungkan realisasi unit dari dua OPD berbeda walaupun `ref_id` kebetulan sama/berasal dari master bersama.

Untuk PK Bupati/Kabupaten yang `opd_id = NULL`, gunakan scope Kabupaten yang sesuai.

---

# 10. TAHUN HARUS KONSISTEN

Shared unit hanya dicari dalam:

```text
tahun PK yang sama
```

Verifikasi `program_pk.tahun_anggaran`, `kegiatan_pk.tahun_anggaran`, `sub_kegiatan_pk.tahun_anggaran`, dan `pk.tahun`.

Jangan mempertemukan indikator 2025 dengan 2026.

---

# 11. SUMBER DAFTAR UNIT

Reuse semantic existing:

```text
TargetModel::getUnitPkByIndikator()
```

atau refactor menjadi service/helper reusable.

PENTING:

Current helper dapat mempunyai fallback:

```text
subkegiatan → kegiatan → program
kegiatan → program
```

Shared detection harus menggunakan **unit final yang benar-benar ditampilkan/dipakai MONEV**, bukan raw relation yang berbeda dengan UI.

Hindari satu definisi unit pada MONEV dan definisi lain pada shared-unit resolver.

---

# 12. BUAT REVERSE MAP UNIT → INDIKATOR

Tambahkan method/service batch yang secara konsep menghasilkan:

```php
[
    'program:123' => [
        'level' => 'program',
        'ref_id' => 123,
        'nama' => 'PROGRAM ...',
        'pagu' => 1000000000,
        'indikator_count' => 2,
        'indikator' => [
            [
                'pk_indikator_id' => ...,
                'target_rencana_id' => ...,
                'nama' => 'Indikator A',
                'pk_jenis' => ...,
                'editable' => true,
            ],
            [
                'pk_indikator_id' => ...,
                'target_rencana_id' => ...,
                'nama' => 'Indikator B',
                'pk_jenis' => ...,
                'editable' => true,
            ],
        ],
    ],
]
```

Nama method bebas mengikuti convention project.

Jangan N+1 query.

---

# 13. INDIKATOR TANPA RENCANA AKSI

Jika Unit X secara PK mendukung:

```text
Indikator A
Indikator B
Indikator C
```

tetapi C belum mempunyai `target_rencana`, tetap tampilkan C pada informasi:

```text
Program ini mendukung 3 indikator
```

Namun pada C:

```text
Rencana Aksi belum tersedia
```

dan jangan tampilkan input realisasi sampai konteks MONEV-nya tersedia.

Jangan membuat `target_rencana` otomatis hanya untuk kebutuhan form anggaran.

---

# 14. DUPLICATE RELATION PK

Jika ditemukan bridge duplicate seperti:

```text
pk_program
```

yang menghubungkan indikator yang sama ke unit yang sama lebih dari sekali:

- dedupe hanya pada presentation/query;
- laporkan sebagai data-quality issue;
- JANGAN delete tanpa persetujuan.

Shared count dihitung berdasarkan:

```text
COUNT(DISTINCT pk_indikator_id)
```

bukan jumlah row bridge.

---

# 15. URL EXISTING TETAP BOLEH DIPAKAI

Current route:

```text
/adminopd/monev/anggaran/{target_rencana_id}
```

boleh tetap dipakai sebagai entry point.

Contoh:

```text
/adminopd/monev/anggaran/464
```

Halaman mengetahui indikator yang sedang dibuka dari `target_rencana_id`.

Dari indikator tersebut, halaman menemukan unit anggaran dan sibling indicator yang memakai exact unit yang sama.

Tidak wajib membuat URL baru kecuali memang diperlukan.

---

# 16. TAMPILAN NON-SHARED — JANGAN DIUBAH BESAR

Jika satu Program/Kegiatan/Sub Kegiatan hanya dipakai satu indikator:

```text
indikator_count = 1
```

pertahankan layout/form existing semaksimal mungkin.

Jangan membebani user dengan panel “digunakan oleh 1 indikator”.

Tidak perlu badge shared.

---

# 17. TAMPILAN SHARED PROGRAM

Jika:

```text
Program X
→ 2+ indikator
```

Program tampil SATU KALI.

Contoh UI:

```text
PROGRAM X
Pagu: Rp1.000.000.000

Program ini mendukung 2 indikator
```

Di bawahnya:

```text
1. Indikator A
   [Indikator yang sedang dibuka]

   Realisasi untuk indikator ini
   TW I | TW II | TW III | TW IV

2. Indikator B

   Realisasi untuk indikator ini
   TW I | TW II | TW III | TW IV
```

Gunakan satu card/panel Program, bukan Program X diulang dua kali.

---

# 18. TAMPILAN SHARED KEGIATAN

Rule sama:

```text
KEGIATAN X
Pagu: ...
Kegiatan ini mendukung 3 indikator
```

Lalu satu bagian input per indikator.

---

# 19. TAMPILAN SHARED SUB KEGIATAN

Rule sama:

```text
SUB KEGIATAN X
Pagu: ...
Sub Kegiatan ini mendukung 2 indikator
```

Lalu satu bagian input per indikator.

---

# 20. SEMUA FORM INDIKATOR PADA SHARED UNIT

Untuk shared unit, tampilkan nilai TW I–TW IV masing-masing indikator dalam unit yang sama.

Jika current user mempunyai izin mengubah seluruh indikator tersebut dalam OPD/context yang sama, boleh dibuat editable dalam satu halaman.

Jika ada sibling indicator yang berada di scope/jenis PK yang tidak boleh dimutasi oleh current user:

```text
tampilkan read-only
```

dan beri tombol:

```text
Lihat
```

jika route detail sah tersedia.

JANGAN memperluas permission hanya demi kenyamanan UI.

---

# 21. INDIKATOR YANG SEDANG DIBUKA

Beri badge singkat:

```text
Sedang dibuka
```

atau:

```text
Indikator saat ini
```

agar user tidak bingung.

Jangan gunakan istilah:

```text
Current target
```

---

# 22. INFORMASI REALISASI INDIKATOR LAIN

Ini WAJIB sesuai keputusan user.

Ketika user membuka Indikator A, dia harus bisa melihat nilai Indikator B/C yang memakai unit sama.

Contoh:

```text
Indikator B
TW I  Rp75.000.000
TW II Rp125.000.000
```

Tujuan:

> operator langsung mengetahui unit yang sama sudah mempunyai realisasi pada indikator lain.

---

# 23. SUMMARY SHARED UNIT

Di bagian bawah shared unit tampilkan:

```text
Total Realisasi per Triwulan
TW I
TW II
TW III
TW IV
```

dan:

```text
Total Realisasi TW I–IV
Sisa Pagu
```

Contoh:

```text
Total Realisasi : Rp450.000.000
Pagu            : Rp1.000.000.000
Sisa Pagu       : Rp550.000.000
```

Jika melebihi:

```text
Total Realisasi : Rp1.100.000.000
Pagu            : Rp1.000.000.000
Melebihi Pagu   : Rp100.000.000
```

gunakan visual warning yang jelas.

---

# 24. VALIDASI UTAMA — TOTAL TW I–IV TIDAK BOLEH > PAGU

Untuk setiap exact unit:

```text
TOTAL =
SUM(
 seluruh indikator yang memakai unit tersebut,
 seluruh TW I + TW II + TW III + TW IV
)
```

Wajib:

```text
TOTAL <= PAGU
```

Contoh valid:

```text
Pagu = 1.000.000.000
Total = 1.000.000.000
→ BOLEH
```

Contoh tidak valid:

```text
Pagu = 1.000.000.000
Total = 1.000.000.001
→ TOLAK
```

---

# 25. PAGU 0

Jika:

```text
Pagu = 0
```

maka:

```text
Total realisasi > 0
```

secara normal adalah melebihi pagu dan harus ditolak untuk input baru/update.

Jika database existing sudah memiliki kondisi tersebut:
- jangan hapus;
- tampilkan warning;
- izinkan user memperbaiki.

---

# 26. NULL VS 0

Pertahankan:

```text
NULL = belum diisi
0    = sudah diisi dengan nilai nol
```

Untuk validasi ceiling:

```text
NULL
```

dapat diperlakukan 0 dalam penjumlahan nominal, tetapi completeness tetap berbeda.

Jangan mengubah NULL menjadi 0 di database hanya karena validasi pagu.

---

# 27. VALIDASI SERVER-SIDE WAJIB

JavaScript hanya untuk preview.

Keputusan akhir harus server-side.

Jangan percaya:
- pagu dari hidden input;
- ref_level dari browser;
- ref_id dari browser;
- target ID sibling dari browser.

Server harus membangun ulang allowed matrix berdasarkan PK/database.

---

# 28. VALIDASI SHARED UNIT HARUS MEMPERHITUNGKAN DATA SIBLING

Jika user hanya mengubah Indikator A:

```text
A baru = 300 juta
B existing = 600 juta
Pagu = 800 juta
```

maka:

```text
300 + 600 = 900
```

harus ditolak.

Jangan hanya memvalidasi nilai A.

---

# 29. SIMPAN MULTI-INDIKATOR HARUS ATOMIC

Current project sudah mempunyai transaction untuk batch realisasi satu `target_rencana`.

Shared UI membutuhkan kemungkinan beberapa `target_rencana_id`.

Buat operasi multi-target yang:

```text
BEGIN
authorize seluruh target
resolve seluruh shared units
lock
read existing sibling values
build prospective final state
validate pagu
upsert seluruh perubahan
COMMIT
```

Jika satu gagal:

```text
ROLLBACK ALL
```

Tidak boleh sebagian indikator berubah.

---

# 30. CONCURRENCY / RACE CONDITION

Validasi pagu tidak cukup dilakukan sebelum transaction.

Contoh dua tab:

```text
User A membaca sisa pagu 100 juta
User B membaca sisa pagu 100 juta

A simpan 80 juta
B simpan 80 juta
```

Tanpa locking hasil akhir bisa melebihi pagu.

Di dalam transaction:

- lock master unit yang sedang diubah menggunakan `FOR UPDATE` bila memungkinkan;
- kemudian baca seluruh `monev_anggaran` pada unit/scope tersebut;
- hitung prospective total;
- baru upsert.

Master lock:
- `program_pk.id` untuk program;
- `kegiatan_pk.id` untuk kegiatan;
- `sub_kegiatan_pk.id` untuk sub kegiatan.

Dengan begitu concurrent save unit yang sama harus antre.

---

# 31. SCOPE VALIDASI TOTAL

Key validasi minimal:

```text
opd_id
+
tahun/context PK
+
ref_level
+
ref_id
```

Jangan menjumlahkan OPD lain.

Jangan menjumlahkan tahun lain.

Untuk Bupati/Kabupaten, sesuaikan scope dengan desain current.

---

# 32. JANGAN DEDUPE REALISASI ANTAR INDIKATOR

Ini sangat penting.

Setelah business rule ini berlaku:

```text
Program X
A = 150 juta
B = 75 juta
```

maka realisasi Program X memang:

```text
225 juta
```

JANGAN memakai:

```text
DISTINCT realisasi
```

atau hanya memilih salah satu indikator.

Yang harus `distinct` adalah **pagu/unit master**, bukan nilai realisasi per indikator.

---

# 33. PAGU DIHITUNG SEKALI

Jika Program X mendukung 5 indikator:

```text
Pagu Program X
```

hanya dihitung sekali pada Dashboard.

Jangan:

```text
Pagu X × 5
```

Tetapi realisasi:

```text
SUM seluruh bagian realisasi indikator pada Program X
```

---

# 34. DASHBOARD OPD — AUDIT KODE EXISTING

Audit:

```text
OpdDashboardService::loadRealisasiAnggaran()
OpdDashboardService::realisasiPerProgram()
OpdDashboardService::getBudgetAbsorption()
OpdDashboardService::getPriorityInsights()
OpdDashboardService::ringkasPerhatian()
```

Project terbaru sudah memiliki beberapa improvement untuk:
- realisasi per-unit;
- pagu Program dihitung sekali;
- realisasi Program ditelusuri melalui `ref_level/ref_id`;
- warisan tidak dijumlahkan bersama per-unit.

PERTAHANKAN improvement tersebut.

Jangan rewrite kalau logic existing sudah kompatibel dengan model attribution.

---

# 35. DASHBOARD — AGREGASI BARU YANG BENAR

Untuk Program X shared:

```text
Indikator A → Program X → Rp150 juta
Indikator B → Program X → Rp75 juta
```

Dashboard:

```text
Program X
Pagu = satu kali
Realisasi = 225 juta
```

Untuk Kegiatan/Sub Kegiatan, roll-up ke Program pada card utama harus tetap mengikuti relation master existing.

---

# 36. OVER-BUDGET AUDIT SERVICE

Tambahkan reusable service/query yang menemukan exact unit dengan:

```text
SUM seluruh TW I–IV seluruh indikator
>
pagu
```

Return minimal:

```text
opd_id
tahun
ref_level
ref_id
kode
nama
pagu
total_realisasi
selisih
persentase
jumlah_indikator
indikator[]
target_rencana_id[]
```

Gunakan untuk:
- form MONEV Anggaran;
- Dashboard OPD;
- Dashboard Kabupaten;
- Dashboard Bupati bila relevan;
- command/test audit.

---

# 37. DASHBOARD OPD — WARNING EXISTING OVER PAGU

Data existing yang saat ini melebihi pagu JANGAN dihapus.

Dashboard OPD harus memberi warning.

Gunakan bahasa singkat:

```text
Realisasi anggaran melebihi pagu
```

Subteks:

```text
2 unit anggaran perlu diperbaiki
```

Detail:

```text
Kegiatan X
Pagu: Rp...
Realisasi: Rp...
Melebihi: Rp...
```

Tombol:

```text
Perbaiki Realisasi
```

arah ke MONEV Anggaran yang relevan.

---

# 38. WARNING BUKAN STATUS KINERJA KRITIS

Over-budget adalah masalah validitas/realisasi anggaran.

Jangan otomatis mengubah:

```text
status capaian kinerja indikator
```

menjadi:

```text
Kritis
```

Masukkan sebagai:

```text
Perlu Tindak Lanjut / Data Anggaran
```

dengan severity tinggi.

Pisahkan dari capaian kinerja.

---

# 39. INTEGRASI KE “PERLU PERHATIAN”

Jika project mempunyai:

```text
PERHATIAN_JENIS
insight_groups
groupInsights()
```

tambahkan jenis baru secara konsisten, misalnya internal code:

```text
anggaran_melebihi_pagu
```

UI label:

```text
realisasi anggaran melebihi pagu
```

Pastikan:

```text
total tindak lanjut
=
jumlah rincian
```

tetap benar.

Update `DashVerify`.

---

# 40. DASHBOARD CARD PENYERAPAN

Jika ada unit over-budget:

Card tetap boleh menunjukkan angka penyerapan aktual.

Contoh:

```text
Penyerapan Anggaran
103,8%
```

Tetapi beri visual warning:

```text
Ada realisasi yang melebihi pagu
```

Jangan diam-diam cap ke 100%.

Nilai asli harus terlihat.

---

# 41. DASHBOARD KABUPATEN

Admin Kabupaten melihat seluruh OPD.

Tambahkan ringkasan:

```text
OPD dengan realisasi melebihi pagu
```

Contoh:

```text
3 OPD perlu memperbaiki realisasi anggaran
```

Drill-down:

```text
OPD
Unit
Pagu
Realisasi
Selisih
```

Jangan menjadikan seluruh OPD “Kinerja Kritis” hanya karena masalah anggaran.

Masukkan ke kelengkapan/data quality/prioritas pimpinan yang sesuai desain existing.

---

# 42. DASHBOARD BUPATI

Jika Dashboard Bupati memakai service Kabupaten yang sama, warning over-budget harus ikut tersedia secara ringkas.

Jangan tampilkan detail teknis berlebihan.

Contoh:

```text
3 OPD memiliki realisasi anggaran melebihi pagu
```

dan drill-down bila tersedia.

---

# 43. MONEV UTAMA

MONEV utama tetap indicator-centric.

Jika unit shared tampil di beberapa indikator:

ini BOLEH dan memang benar karena hubungan kinerjanya ada.

Tambahkan badge kecil hanya pada shared case:

```text
Dipakai 2 indikator
```

atau:

```text
Mendukung 2 indikator
```

Jangan hilangkan Program dari indikator kedua.

---

# 44. FORM ANGGARAN SHARED — REDAKSI USER AWAM

Gunakan Bahasa Indonesia sederhana.

Direkomendasikan:

```text
Program ini mendukung 2 indikator
```

```text
Realisasi untuk indikator ini
```

```text
Realisasi indikator lain
```

```text
Total realisasi
```

```text
Sisa pagu
```

```text
Total realisasi melebihi pagu
```

```text
Perbaiki nilai realisasi sebelum menyimpan.
```

Hindari istilah:

```text
atribusi
grain
shared entity
polymorphic reference
ref_key
```

di layar user.

---

# 45. PESAN VALIDASI MELEBIHI PAGU

Contoh:

```text
Realisasi belum dapat disimpan.

Total realisasi Program X adalah Rp1.100.000.000,
melebihi pagu Rp1.000.000.000 sebesar Rp100.000.000.

Periksa kembali realisasi pada indikator yang menggunakan program ini.
```

Boleh diringkas agar sesuai UI.

Jangan tampilkan SQL/table/ID.

---

# 46. EXISTING INVALID DATA — EDIT FLOW

Jika data existing sudah over-budget:

- halaman tetap dapat dibuka;
- semua nilai existing tetap terlihat;
- tampilkan warning;
- user dapat mengubah beberapa indikator dalam shared group sekaligus;
- save baru hanya berhasil jika final total sudah <= pagu.

Jangan memaksa user menghapus row manual dari database.

---

# 47. JANGAN BLOK READ KARENA DATA INVALID

Dashboard/MONEV/PDF tetap harus dapat membaca data over-budget.

Validation keras berlaku saat create/update.

Existing invalid state:
```text
readable + warning + repair path
```

---

# 48. AUDIT SQL — SHARED UNIT

Buat file read-only, misalnya:

```text
db/audit_YYYY-MM-DD_monev_anggaran_shared_unit.sql
```

Query minimal:

## A. Program dipakai ≥2 indikator

Scope:
```text
OPD + tahun + program_id
```

## B. Kegiatan dipakai ≥2 indikator

## C. Sub Kegiatan dipakai ≥2 indikator

Gunakan:

```text
COUNT(DISTINCT pk_indikator_id) >= 2
```

---

# 49. AUDIT SQL — SHARED REALISASI

Query unit yang sudah mempunyai `monev_anggaran` pada ≥2 indikator berbeda.

Output:

```text
opd
tahun
level
unit
pagu
indikator_count
total_tw1
total_tw2
total_tw3
total_tw4
total_semua_tw
```

---

# 50. AUDIT SQL — OVER BUDGET

Query:

```text
SUM(TW1 + TW2 + TW3 + TW4)
>
PAGU
```

group exact unit per OPD/tahun.

Output:

```text
nama_opd
tahun
level
kode
nama_unit
pagu
total_realisasi
selisih
persen
jumlah_indikator
```

READ ONLY.

Jangan auto-update.

---

# 51. DATABASE CHANGE

Secara baseline, task ini **seharusnya dapat dilakukan tanpa mengubah struktur `monev_anggaran`**.

Jangan menambah kolom hanya untuk menyimpan nama indikator karena hubungan sudah dapat ditelusuri:

```text
monev_anggaran.target_rencana_id
→ target_rencana.pk_indikator_id
→ pk_indikator
```

Jangan duplikasi data tanpa alasan.

---

# 52. INDEX — HANYA JIKA TERBUKTI PERLU

Shared query sering memfilter:

```text
opd_id
ref_level
ref_id
```

Current schema sudah mempunyai index terpisah.

Audit EXPLAIN.

Jika benar dibutuhkan, boleh mengusulkan composite index:

```text
(opd_id, ref_level, ref_id)
```

Tetapi:
- jangan menambah tanpa bukti;
- buat migration;
- berikan SQL manual.

Index bukan perubahan data user, tetapi tetap laporkan.

---

# 53. SQL MANUAL PRODUCTION WAJIB JIKA ADA DB CHANGE

Jika ada schema/index change:

buat:

```text
db/update_YYYY-MM-DD_monev_shared_unit.sql
```

Isi:
- preflight;
- ALTER yang diperlukan;
- post-check.

MySQL/MariaDB compatible.

Jika tidak ada schema change, tulis:

```text
Tidak ada perubahan schema database.
```

---

# 54. BACKWARD COMPATIBILITY

Pertahankan:
- MONEV non-shared;
- MONEV warisan compatibility;
- parent/sub measurement rule;
- target zero semantics;
- PDF/cetak;
- Dashboard existing;
- Bupati;
- AdminKab;
- Rencana Aksi;
- LAKIP.

Task ini tidak boleh merusak improvement sebelumnya.

---

# 55. SECURITY

Semua sibling target yang ditampilkan/diubah harus diverifikasi server-side.

Minimal:

```text
same OPD
same year
unit benar-benar linked
target benar-benar milik indikator
permission sesuai role/jenis PK
```

Anti-IDOR.

Jangan percaya target ID dalam POST.

---

# 56. TEST — NON-SHARED PROGRAM

Program A hanya dipakai Indikator A.

Expected:
- layout existing normal;
- tidak ada panel shared;
- save existing tetap bekerja.

---

# 57. TEST — SHARED PROGRAM 2 INDIKATOR

```text
Program X pagu 1 M
Indikator A
Indikator B
```

Expected halaman:
- Program X tampil sekali;
- tulisan `Program ini mendukung 2 indikator`;
- nama A dan B tampil;
- masing-masing punya TW I–IV;
- existing values masing-masing tampil benar.

---

# 58. TEST — SHARED KEGIATAN

Kegiatan X dipakai 2+ indikator.

Expected behavior identik dengan Program.

---

# 59. TEST — SHARED SUB KEGIATAN

Sub Kegiatan X dipakai 2+ indikator.

Expected behavior identik.

---

# 60. TEST — INDEPENDENT VALUES

Input:

```text
A TW1 = 150
B TW1 = 75
```

Expected DB:

dua row/dua target tetap independen.

Update A menjadi 200.

Expected:
```text
A = 200
B tetap 75
```

Jangan mirror.

---

# 61. TEST — TOTAL TEPAT PAGU

```text
Pagu = 1000
A total = 600
B total = 400
```

Expected:
```text
SAVE PASS
```

---

# 62. TEST — TOTAL MELEBIHI PAGU

```text
Pagu = 1000
A total = 600
B total = 401
```

Expected:
```text
SAVE REJECTED
```

Tidak ada partial update.

---

# 63. TEST — EXISTING SIBLING DIPERHITUNGKAN

Existing:

```text
B = 800
```

User mengedit A:

```text
A = 250
```

Expected:
```text
total 1050
→ reject
```

---

# 64. TEST — NULL & ZERO

```text
NULL
```
tetap belum diisi.

```text
0
```
tetap nilai valid.

Ceiling calculation boleh memperlakukan keduanya sebagai 0 nominal, tetapi completeness tidak boleh disamakan.

---

# 65. TEST — EXISTING OVER-BUDGET

Siapkan/temukan existing data:

```text
total > pagu
```

Expected:
- tidak dihapus;
- Dashboard warning;
- MONEV warning;
- dapat dibuka;
- dapat diperbaiki;
- final save valid hanya jika <= pagu.

---

# 66. TEST — DASHBOARD AGGREGATION

```text
Program X pagu 1000
A realisasi 150
B realisasi 75
```

Expected:

```text
Pagu Dashboard = 1000
Realisasi Program X = 225
```

BUKAN:

```text
Pagu = 2000
```

dan BUKAN:

```text
Realisasi = 150 saja
```

---

# 67. TEST — DASHBOARD OVER-BUDGET WARNING

Existing:

```text
Pagu = 1000
Total attribution = 1200
```

Expected OPD Dashboard:

```text
Realisasi anggaran melebihi pagu
```

detail dapat dibuka.

Tidak mengubah performance status indikator menjadi kritis hanya karena budget data.

---

# 68. TEST — ADMIN KAB

Jika OPD A over-budget:

AdminKab dashboard/monitoring harus dapat mengetahui:

```text
OPD A
1 unit anggaran melebihi pagu
```

scope cross-OPD sesuai permission.

---

# 69. TEST — CONCURRENCY

Simulasikan dua transaksi pada shared unit yang sama.

Pastikan locking/transaction mencegah kedua save lolos jika hasil gabungannya > pagu.

Jika automated concurrency test sulit, buat integration test/manual reproducible test dan dokumentasikan.

---

# 70. TEST — SECURITY / IDOR

Ubah POST:

```text
target_rencana_id sibling OPD lain
ref_id unit lain
ref_level lain
```

Expected:
```text
rejected
```

Tidak ada data OPD lain berubah.

---

# 71. TEST — TRANSACTION

Multi-indicator submit.

Paksa row kedua gagal.

Expected:
```text
row pertama juga rollback
```

---

# 72. TEST — REGRESSION

Jalankan minimal:

```text
MONEV utama
MONEV Anggaran
Rencana Aksi
MONEV capaian
Dashboard OPD
Dashboard Kabupaten
Dashboard Bupati
Cetak/PDF MONEV
php spark dash:verify
```

Tambahkan test baru ke `DashVerify` atau test suite existing.

Jangan menghapus test lama.

---

# 73. COPYWRITING FINAL

Semua user-facing text harus:
- Bahasa Indonesia;
- singkat;
- jelas untuk user awam;
- tidak terlalu teknis.

Contoh final:

```text
Program ini mendukung 2 indikator
```

```text
Realisasi untuk indikator ini
```

```text
Total realisasi
```

```text
Sisa pagu
```

```text
Realisasi melebihi pagu
```

```text
Periksa kembali nilai pada indikator yang menggunakan program ini.
```

---

# 74. OUTPUT AKHIR AGENT

Setelah selesai, laporkan:

## 1. Audit Database
- apakah current schema mendukung business rule;
- shared Program count;
- shared Kegiatan count;
- shared Sub Kegiatan count;
- shared unit yang sudah mempunyai realisasi multi-indikator;
- jumlah unit existing yang over-budget.

Gunakan angka database aktual saat agent bekerja.

## 2. Architecture
Jelaskan final grain:

```text
unit anggaran
→ dapat mendukung banyak indikator

monev_anggaran
→ realisasi per indikator + unit

dashboard
→ pagu unit sekali
→ realisasi semua indikator dijumlahkan
```

## 3. Files Changed

## 4. UI Changes

## 5. Validation Changes

## 6. Transaction/Locking

## 7. Dashboard Changes

## 8. AdminKab/Bupati Impact

## 9. Database Changes
atau:

```text
Tidak ada perubahan schema database.
```

## 10. SQL
- audit SQL;
- migration SQL jika ada;
- post-deploy checks.

## 11. Existing Invalid Data
Daftar jumlah over-budget tanpa mengubah datanya.

## 12. Tests
Tampilkan PASS/FAIL:

```text
Non-shared
Shared Program
Shared Kegiatan
Shared Sub Kegiatan
Independent input
Exact pagu
Over pagu
Sibling existing
NULL vs 0
Existing over-budget warning
Dashboard aggregation
AdminKab
Concurrency
IDOR
Transaction
Regression
```

## 13. Items Requiring Approval

Jika cleanup data diperlukan, STOP dan minta persetujuan.

---

# 75. DILARANG

Jangan:
- menghapus data over-budget;
- mengubah realisasi sibling otomatis;
- menjadikan satu row realisasi global per Program;
- mengganti unique key sehingga multi-indikator tidak bisa menyimpan nilai berbeda;
- menyembunyikan Program dari indikator kedua;
- menghitung pagu berkali-kali karena banyak indikator;
- menggunakan DISTINCT pada nilai realisasi yang memang merupakan bagian berbeda;
- menerima save jika total final TW I–IV seluruh indikator > pagu;
- hanya validasi lewat JavaScript;
- percaya target/ref dari POST tanpa server-side verification;
- merusak MONEV non-shared;
- membuat warning budget menjadi status “Kinerja Kritis”;
- melakukan cleanup existing data tanpa persetujuan saya.

---

# 76. QUALITY BAR

Target akhir:

> Jika satu Program/Kegiatan/Sub Kegiatan hanya mendukung satu indikator, pengalaman user hampir tidak berubah.

> Jika unit yang sama mendukung dua atau lebih indikator, unit tampil sekali pada halaman input anggaran dan seluruh indikator pendukung terlihat jelas.

> Masing-masing indikator tetap mempunyai realisasi TW I–IV sendiri dan perubahan pada satu indikator tidak mengubah indikator lain.

> Pagu unit dihitung satu kali, sedangkan realisasi unit adalah jumlah seluruh bagian realisasi dari indikator-indikator yang memakai unit tersebut.

> Total realisasi TW I–IV seluruh indikator pada satu unit tidak boleh melebihi pagu.

> Data existing yang sudah melebihi pagu tidak dihapus; Dashboard memberi peringatan dan user diberi jalan untuk memperbaikinya.

> Semua penyimpanan multi-indikator transactional, server-side validated, scope-safe, dan aman dari race condition.


---

# 77. WAJIB AUDIT STRUKTUR DATABASE AKTUAL SEBELUM IMPLEMENTASI

Sebelum mengubah code MONEV Anggaran, lakukan audit database aktual terhadap:

```text
monev_anggaran
target_rencana
target_sub_rencana
pk
pk_sasaran
pk_indikator
pk_program
pk_kegiatan
pk_subkegiatan
program_pk
kegiatan_pk
sub_kegiatan_pk
```

Verifikasi minimal:

```text
primary key
foreign key
unique key
index
nullable
default value
data type
generated column
trigger bila ada
```

Jangan hanya mengandalkan migration lama.

Gunakan struktur database aktual sebagai source of truth.

---

# 78. JIKA STRUKTUR DATABASE TERNYATA BELUM CUKUP

Baseline saat ini mengindikasikan struktur:

```text
monev_anggaran
target_rencana_id
opd_id
ref_level
ref_id
ref_key
realisasi_triwulan_1
realisasi_triwulan_2
realisasi_triwulan_3
realisasi_triwulan_4
```

sudah cukup untuk:

```text
1 unit anggaran
→ beberapa indikator
→ realisasi berbeda per indikator
```

Tetapi agent WAJIB memverifikasi hal tersebut dari database aktual.

Jika setelah audit ternyata struktur database perlu disesuaikan, agent BOLEH mengusulkan dan mengimplementasikan perubahan yang aman.

Contoh kemungkinan:

```text
index tambahan
constraint aman
kolom audit
foreign key
kolom metadata
```

Tetapi:

```text
JANGAN mengubah grain data
JANGAN menghapus target_rencana_id
JANGAN mengubah realisasi per indikator menjadi satu row global
```

tanpa menghentikan pekerjaan dan meminta persetujuan saya.

---

# 79. WAJIB KIRIM QUERY SQL JIKA ADA PERUBAHAN DATABASE

Jika perubahan code membutuhkan:

```text
ALTER TABLE
CREATE INDEX
ADD COLUMN
ADD CONSTRAINT
UPDATE data
INSERT seed
backfill
```

maka WAJIB kirim query SQL manual.

Buat file misalnya:

```text
db/update_YYYY-MM-DD_monev_shared_unit_anggaran.sql
```

Isi minimal:

```sql
-- =====================================================
-- PRECHECK
-- =====================================================

-- cek database
SELECT DATABASE();
SELECT VERSION();

-- cek struktur/tabel terkait
-- sesuaikan dengan MySQL/MariaDB aktual


-- =====================================================
-- SCHEMA UPDATE
-- =====================================================

-- ALTER / CREATE INDEX / CONSTRAINT bila memang diperlukan


-- =====================================================
-- POSTCHECK
-- =====================================================

-- verifikasi struktur
-- verifikasi jumlah data
-- verifikasi tidak ada row hilang
```

SQL harus:

```text
MySQL/MariaDB compatible
non-destructive
idempotent semaksimal mungkin
tidak DROP/TRUNCATE
tidak DELETE data user
```

Jika ada data existing yang perlu diubah secara massal:

```text
STOP
audit
laporkan
minta persetujuan saya
```

sebelum menjalankan UPDATE/DELETE destructive.

---

# 80. JIKA TIDAK PERLU PERUBAHAN DATABASE

Tulis secara eksplisit:

```text
Tidak diperlukan perubahan struktur database.
```

Tetap berikan SQL audit/read-only untuk membuktikan bahwa struktur existing sudah memenuhi kebutuhan.

Minimal tampilkan hasil/penjelasan:

```text
unique key monev_anggaran
index ref_level/ref_id
relasi target_rencana → pk_indikator
shared Program
shared Kegiatan
shared Sub Kegiatan
existing over-budget
```

---

# 81. WAJIB BUAT BACKUP CHECK SEBELUM DB CHANGE

Sebelum perubahan schema/data:

```text
1. tampilkan row count tabel utama
2. tampilkan checksum/statistik sederhana bila memungkinkan
3. sarankan backup database
4. jangan lanjut destructive operation tanpa approval
```

Minimal record count:

```text
monev_anggaran
target_rencana
pk_indikator
pk_program
pk_kegiatan
pk_subkegiatan
```

Sesudah perubahan, bandingkan kembali.

Tidak boleh terjadi penurunan jumlah data tanpa alasan yang disetujui.

---

# 82. DEBUGGING WAJIB SETELAH SETIAP PERUBAHAN

Jangan menunggu seluruh task selesai.

Setelah setiap perubahan besar:

```text
1. lint/syntax check
2. route check
3. controller check
4. model/service check
5. database query check
6. view rendering check
7. validation check
8. transaction check
9. permission check
10. regression check
```

Jika ada error:

```text
temukan root cause
perbaiki
jalankan ulang test
```

Jangan menutupi error dengan try/catch yang hanya mengabaikan exception.

---

# 83. DEBUGGING CODEIGNITER 4

Lakukan minimal:

```bash
php spark routes
```

dan lint PHP pada file yang diubah.

Jika environment mendukung:

```bash
php spark test
```

atau test command project existing.

Jalankan juga:

```bash
php spark dash:verify
```

jika command tersebut tersedia.

Pastikan tidak ada:

```text
Fatal error
Parse error
Undefined variable
Undefined index
Trying to access array offset
SQLSTATE
Duplicate entry
Foreign key error
CSRF error
403 palsu
404 palsu
```

pada happy path.

---

# 84. DEBUGGING FORM SHARED UNIT

Test halaman seperti:

```text
/adminopd/monev/anggaran/{target_rencana_id}
```

dalam skenario:

```text
1 indikator / 1 Program
2 indikator / 1 Program
3+ indikator / 1 Program

2 indikator / 1 Kegiatan
3+ indikator / 1 Kegiatan

2 indikator / 1 Sub Kegiatan
3+ indikator / 1 Sub Kegiatan
```

Pastikan:

```text
unit hanya tampil sekali pada shared mode
semua indikator yang benar tampil
nilai existing tidak tertukar
input indikator A tidak mengubah B
nilai B tetap tampil saat A diedit
```

---

# 85. DEBUGGING VALIDASI PAGU

Test boundary:

```text
total = pagu - 1
→ PASS

total = pagu
→ PASS

total = pagu + 1
→ REJECT
```

Test juga:

```text
TW1 saja melebihi
gabungan TW1-TW4 melebihi
gabungan beberapa indikator melebihi
sibling existing membuat total melebihi
nilai 0
NULL
nilai sangat besar
format ribuan
decimal bila didukung
```

Server-side harus menjadi keputusan final.

---

# 86. DEBUGGING TRANSACTION

Untuk multi-indikator shared form:

Paksa salah satu update gagal.

Expected:

```text
SEMUA perubahan rollback
```

Verifikasi database sebelum dan sesudah.

Jangan hanya percaya pesan UI.

---

# 87. DEBUGGING CONCURRENCY

Lakukan test race condition pada unit yang sama.

Contoh:

```text
Pagu = 1.000
Existing total = 900

Request A menambah 80
Request B menambah 80
```

Expected:

```text
hanya satu request yang boleh commit
request lain harus gagal karena total final > pagu
```

Jika automated concurrency test tidak praktis, buat manual reproducible test dan dokumentasikan.

---

# 88. DEBUGGING DASHBOARD OPD

Setelah perubahan MONEV Anggaran, audit ulang seluruh card yang memakai data anggaran.

Pastikan:

```text
Pagu unit shared dihitung sekali
Realisasi semua indikator dijumlahkan
tidak ada double pagu
tidak ada realisasi hilang karena DISTINCT
persentase penyerapan benar
warning over-budget benar
```

Contoh:

```text
Program X
Pagu = 1.000
A = 150
B = 75

Dashboard:
Pagu = 1.000
Realisasi = 225
Penyerapan = 22,5%
```

---

# 89. DEBUGGING DASHBOARD OVER-BUDGET

Existing:

```text
Pagu = 1.000
Total realisasi = 1.200
```

Expected:

```text
Penyerapan = 120%
Warning = tampil
```

Jangan cap ke 100%.

Jangan mengubah status capaian kinerja menjadi kritis hanya karena over-budget.

Warning masuk kategori anggaran/data quality.

---

# 90. DEBUGGING DASHBOARD KABUPATEN & BUPATI

Pastikan agregasi lintas OPD tidak:

```text
menggandakan pagu
menggandakan realisasi
mencampur unit antar OPD
mencampur tahun
```

AdminKab harus dapat melihat OPD mana yang over-budget.

Dashboard Bupati harus mendapat ringkasan yang sama bila menggunakan service Kabupaten.

---

# 91. DEBUGGING NAVIGATION / LINK

Semua warning yang memiliki action:

```text
Perbaiki Realisasi
```

harus menuju target yang benar.

Jika shared unit dipakai beberapa indikator, tentukan landing page yang konsisten.

Tidak boleh:

```text
404
wrong OPD
wrong indicator
wrong year
```

---

# 92. DEBUGGING PERMISSION / IDOR

Test manual:

```text
ubah target_rencana_id di URL
ubah sibling target ID di POST
ubah ref_level
ubah ref_id
ubah opd_id
```

Expected:

```text
403/404/rejected
```

Tidak boleh membaca/mengubah OPD lain.

---

# 93. DEBUGGING ERROR HANDLING

User-facing validation:

```text
Total realisasi melebihi pagu.
Periksa kembali nilai pada indikator yang menggunakan program ini.
```

Technical exception:

```text
log server-side
pesan aman ke user
```

Jangan tampilkan:

```text
SQLSTATE
table
column
stack trace
absolute path
```

---

# 94. TEST CREATE / READ / EDIT / UPDATE / DELETE

Untuk bagian code yang berubah, lakukan:

```text
CREATE TEST
READ TEST
EDIT TEST
UPDATE TEST
DELETE TEST
```

Catatan:

`DELETE` hanya diuji jika fitur delete memang ada.

Jangan membuat fitur delete baru untuk `monev_anggaran` hanya agar ada test delete.

Jika delete existing:
- test authorized;
- test unauthorized;
- test relation safety;
- test rollback.

---

# 95. BUG TEST WAJIB

Minimal bug test:

```text
Shared Program muncul dua kali
→ harus tidak terjadi

Indikator sibling hilang
→ harus tidak terjadi

Update A mengubah B
→ harus tidak terjadi

Pagu dihitung dua kali
→ harus tidak terjadi

Realisasi hanya mengambil satu indikator
→ harus tidak terjadi

Total > pagu masih bisa save
→ harus tidak terjadi

Existing over-budget tidak muncul warning
→ harus tidak terjadi

Warning salah OPD/tahun
→ harus tidak terjadi

Partial save
→ harus tidak terjadi

Concurrent over-budget
→ harus dicegah
```

---

# 96. REGRESSION GATE

Sebelum menyatakan selesai, jalankan ulang fitur existing:

```text
Admin OPD Dashboard
PK
Rencana Aksi
MONEV capaian
MONEV Anggaran non-shared
MONEV Anggaran shared
Cetak MONEV
Dashboard Kabupaten
Dashboard Bupati
```

Jika ada regression:

```text
FIX
RETEST
```

Jangan menyerahkan code dengan regression yang diketahui.

---

# 97. FINAL PASS CONDITION

Task baru boleh dinyatakan selesai jika:

```text
PHP syntax PASS
route PASS
DB query PASS
shared Program PASS
shared Kegiatan PASS
shared Sub Kegiatan PASS
independent input PASS
pagu validation PASS
transaction PASS
concurrency PASS / documented manual PASS
Dashboard OPD PASS
Dashboard Kabupaten PASS
Dashboard Bupati PASS
permission PASS
error handling PASS
regression PASS
```

Jika ada FAIL:

jangan tulis “selesai”.

Tulis:

```text
BLOCKED / NEEDS FIX
```

dan jelaskan root cause.

---

# 98. LAPORAN DEBUG AKHIR

Tambahkan ke output akhir:

```text
DEBUG & TEST REPORT
```

berisi tabel:

```text
Test
Scenario
Expected
Actual
PASS/FAIL
```

Minimal:

```text
Non-shared form
Shared Program
Shared Kegiatan
Shared Sub Kegiatan
Independent A/B values
Exact pagu
Over pagu
Existing sibling
Existing over-budget
Transaction rollback
Concurrency
Dashboard OPD
Dashboard Kabupaten
Dashboard Bupati
IDOR
CSRF jika terkait
Regression
```

---

# 99. FINAL DATABASE REPORT

Pada akhir task tulis salah satu:

```text
A. Tidak diperlukan perubahan struktur database.
```

atau:

```text
B. Struktur database perlu disesuaikan.
```

Jika B, lampirkan:

```text
alasan
schema before
schema after
migration
SQL manual
preflight
post-check
rollback plan
```

Jangan hanya memberikan migration PHP.

SQL manual tetap wajib.

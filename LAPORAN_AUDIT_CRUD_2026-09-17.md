# Laporan Penyelesaian Audit CRUD & Transaksi Basis Data e-SAKIP / AKSARA Pringsewu

**Aplikasi:** e-SAKIP (CodeIgniter 4.7.3, MySQL 8)
**Tanggal audit & perbaikan:** 17 September 2026
**Lingkup:** seluruh jalur tulis aplikasi — **215 rute POST/DELETE** (semua form tambah/ubah/hapus) di 160+ method controller/trait, 35 model, seluruh blok transaksi, lapisan autentikasi/otorisasi, dan skema basis data.
**Status:** **SELESAI.** Seluruh temuan (5 tinggi, 8 sedang, 12 rendah) telah diperbaiki, diuji, dan skema basis data server sudah diperbarui.

---

## 1. Ringkasan Eksekutif

Audit ini memeriksa apakah setiap operasi simpan/ubah/hapus di e-SAKIP (a) hanya bisa dilakukan oleh pihak yang berhak atas datanya, (b) benar-benar tersimpan atau benar-benar dibatalkan — tidak setengah jalan, dan (c) tidak menghapus data lain secara diam-diam.

Hasilnya, sebelum perbaikan ditemukan **25 kelemahan**. Lima di antaranya berdampak tinggi:

| # | Kelemahan | Dampak sebelum diperbaiki |
|---|---|---|
| 1 | Admin OPD bisa mengubah sasaran Cascading Eselon III/IV **milik OPD lain** hanya dengan mengganti nomor id di alamat form | Integritas data lintas OPD |
| 2 | Admin OPD bisa membalik status Renstra (draft/selesai) **milik OPD lain** lewat AJAX | Integritas data lintas OPD |
| 3 | Form LAKIP OPD bisa menulis realisasi untuk target Renstra **milik OPD lain**, dan **melewati kunci tahun yang sudah disahkan** dengan mengirim tahun berbeda | Integritas & keabsahan LAKIP |
| 4 | Menghapus satu misi/sasaran RPJMD atau satu sasaran Renstra **menghapus berantai** Renstra semua OPD → indikator → target → Rencana Aksi → capaian MONEV, tanpa peringatan (misi #7 mengikat 91 tujuan Renstra di 21 OPD) | Kehilangan data massal |
| 5 | Simpan Perjanjian Kinerja bisa menampilkan **"berhasil disimpan" padahal seluruhnya dibatalkan** basis data | Data hilang tanpa disadari pengguna |

Semua kelemahan itu **sudah ditutup** pada 17 September 2026 dan dibuktikan lewat pengujian langsung (§5). Tidak ada satu pun rute yang menjadi rusak akibat perbaikan: uji sapu terhadap 342 halaman untuk lima peran pengguna menghasilkan **0 error**.

---

## 2. Cara Audit Dilakukan

1. **Pemetaan rute** — seluruh rute tulis di `app/Config/Routes.php` dipetakan ke method penanganannya (termasuk yang berada di trait), lalu tiap method dibaca untuk empat hal: CSRF, otorisasi objek (apakah baris yang disentuh milik pengguna), validasi masukan, dan penanganan transaksi.
2. **Verifikasi perilaku kerangka kerja** — perilaku transaksi CodeIgniter 4.7.3 dibaca langsung dari sumbernya (`BaseConnection.php`), bukan diasumsikan. Tiga fakta yang menentukan penilaian:
   - query yang gagal **di dalam** `transStart()/transBegin()` **tidak melempar exception** (kecuali `transException(true)`), hanya menandai `transStatus=false`;
   - `transComplete()` otomatis membatalkan dan mengembalikan `false`, tetapi **`transCommit()` tidak memeriksa status sama sekali**;
   - `transStatus` bersifat **lengket** antar transaksi dalam satu request.
   Kode yang tidak memeriksa hasil transaksi karena itu bisa melaporkan "berhasil" untuk data yang sudah dibatalkan.
3. **Pemeriksaan basis data nyata** — 99 tabel: seluruh foreign key beserta aturan `ON DELETE`, indeks unik, kolasi, data yatim, dan data ganda.
4. **Pengujian** — setiap perbaikan diuji lewat server lokal (`spark serve`) dengan skenario penyerangan dan skenario normal, plus uji tingkat model untuk pagar penghapusan.

---

## 3. Temuan dan Perbaikan

Kolom **Berkas** menunjuk lokasi perbaikan di kode.

### 3.1 Tingkat TINGGI

| # | Temuan | Perbaikan | Berkas |
|---|---|---|---|
| 1 | `updateEs3($id)` dan `updateEs4($id)` langsung `UPDATE … WHERE id` tanpa memeriksa pemilik | Baris dimuat dulu, diperiksa jenjang (`level`) dan kepemilikan OPD (`canAccessOpd`); nama sasaran wajib diisi; **blok "sasaran baru" dimasukkan ke dalam transaksi** (sebelumnya `transComplete()` dipanggil di tengah, sehingga separuh proses berjalan tanpa transaksi); transaksi yang dibatalkan tidak lagi dilaporkan "berhasil" | `app/Controllers/AdminOpd/CascadingController.php` |
| 2 | `RenstraController::updateStatus()` tidak menyaring `opd_id`; pesan exception teknis bocor ke JSON | Pemeriksaan `canAccessOpd` ditambahkan (lapisan kedua di atas kunci siklus hidup versi); pesan galat lewat penyaring terpusat `pesanGalatBerawalan()` | `app/Controllers/AdminOpd/RenstraController.php` |
| 3 | `LakipOpdController::save()` cabang Renstra: `opd_id` diambil dari dokumen target tanpa dibandingkan dengan sesi; kunci "tahun sudah disahkan" memakai `tahun` dari POST padahal tahun yang disimpan diambil dari target | Kepemilikan dan kunci tahun kini diperiksa terhadap **dokumen target** (sama seperti versi Admin Kabupaten yang sudah benar); hasil `insert()` diperiksa; pesan galat `update()` tidak lagi membocorkan teks teknis | `app/Controllers/AdminOpd/LakipOpdController.php` |
| 4 | `RpjmdModel::deleteMisi/deleteTujuan/deleteSasaran/deleteIndikatorSasaran` dan `RenstraModel::deleteCompleteRenstra` menghapus tanpa memeriksa data hilir, padahal FK `ON DELETE CASCADE` menjalar sampai Rencana Aksi & MONEV | **Pagar dependen**: sebelum menghapus, dihitung apa saja yang bergantung (tujuan Renstra per OPD, Rencana Aksi, RKT, LAKIP, IKU turunan, cascading, mapping). Bila ada, penghapusan **ditolak** dengan pesan yang menyebut jumlahnya — termasuk saat baris dibuang dari form sunting. Rujukan arsip versi sengaja tidak menahan (arsip menyimpan salinannya sendiri) | `app/Models/RpjmdModel.php`, `app/Models/Opd/RenstraModel.php`, `app/Controllers/RpjmdController.php` |
| 5 | `PkModel::saveCompletePk()` mengembalikan id PK walau `transComplete()` sudah membatalkan semuanya → layar "Data PK berhasil disimpan" untuk PK yang tidak ada | Status transaksi diperiksa; bila dibatalkan, dilempar pesan jelas dan query gagal dicatat ke log. **Terbukti**: `id_satuan` tak sah → dulu "berhasil", kini "Perjanjian Kinerja tidak tersimpan…" dan tabel `pk` tidak bertambah | `app/Models/PkModel.php` |

### 3.2 Tingkat SEDANG

| # | Temuan | Perbaikan | Berkas |
|---|---|---|---|
| 6 | Hapus OPD hanya mengecek pegawai & user, padahal FK CASCADE menjalar ke Renstra, IKU, Cascading, Rencana Aksi, MONEV; PK/LAKIP/RKT (tanpa FK) jadi yatim | Diperiksa **17 tabel dependen**; ditolak dengan rincian jumlah | `app/Controllers/SuperAdmin/MasterController.php` |
| 7 | Mengganti slug role tidak memperbarui `users.role` (relasi string) → pemegang role kehilangan akses seketika | `users.role` ikut diperbarui dalam satu transaksi | `MasterController.php` |
| 8 | Sinkron SIMPEG (`PegawaiSyncService`) melaporkan "N baru, M diperbarui" walau transaksi dibatalkan; pada mode *all*, gagal di satu entitas membatalkan entitas berikutnya diam-diam | Hasil tiap transaksi diperiksa; kegagalan dilempar sebagai galat terang dan status lengket direset | `app/Libraries/Pegawai/PegawaiSyncService.php` |
| 9 | `updateEs3` separuh di luar transaksi | Digabung ke #1 | — |
| 10 | Autentikasi: tidak ada `session()->regenerate()` setelah login (*session fixation*); tidak ada pembatasan percobaan login/kode 2FA; akun yang dinonaktifkan tetap aktif sampai sesi habis; alur 2FA mengarahkan role `bupati` ke halaman yang menolaknya | ID sesi diganti saat login (`mulai_sesi_login()`); pembatasan **5 percobaan/menit** per IP+username (login) dan per akun (2FA); `AuthFilter` memeriksa ulang `is_active`/role/OPD **tiap request** (akun nonaktif/dihapus → sesi dihancurkan); pemetaan role→dashboard disatukan (`dashboard_path_by_role()`) | `LoginController.php`, `TwoFactorController.php`, `app/Filters/AuthFilter.php`, `app/Helpers/rbac_helper.php` |
| 11 | `savePd` (PD pendukung PK Bupati): hapus lalu tulis ulang tanpa transaksi; `opd_ids` tak divalidasi | Validasi id OPD; hapus+tulis dalam satu transaksi | `app/Controllers/AdminOpd/PkRenaksiController.php` |
| 12 | `saveEs4` mempercayai `parent_id`/`es3_indikator_id`/`renstra_indikator_sasaran_id` dari POST | Seluruh kolom induk diturunkan dari indikator Es3 di basis data (`konteksIndikatorEs3()`), kepemilikan diperiksa | `CascadingController.php` |
| 13 | `ModulePermissionFilter` menurunkan aksi dari kata kunci path; rute tulis tanpa kata kunci (mis. `iku/revisi/sahkan`, `rpjmd/versi/hapus`, `lakip/snapshot/finalkan`) jatuh ke izin **`view`** — role baca-saja lolos filter (tertahan hanya oleh pagar di controller) | Metode POST/PUT/PATCH/DELETE tanpa kata kunci kini wajib salah satu izin **create/update/delete** | `app/Filters/ModulePermissionFilter.php` |

### 3.3 Tingkat RENDAH / kebersihan

| # | Temuan | Perbaikan |
|---|---|---|
| 14 | `rolePermSave` → transaksi terpisah per role tanpa cek; id permission tak divalidasi | Satu transaksi untuk seluruh matriks; id disaring terhadap daftar sah; `syncPermissions` melempar bila dibatalkan |
| 15 | `DashboardThresholdModel::simpanSemua` tidak memeriksa hasil transaksi; log aktivitas tetap "diperbarui" | Dilempar bila dibatalkan; controller menampilkan pesan galat |
| 16 | Hapus pegawai/jabatan/pangkat/satuan tanpa cek pemakaian (`pk.pihak_1/2`, `pegawai.jabatan_id`, `pk_indikator.id_satuan`) | Ditolak bila masih dipakai, dengan rincian |
| 17 | `PkController::update` tidak memvalidasi `pegawai_1_id/2_id` seperti `save` | Aturan validasi disamakan |
| 18 | `RenstraController::update` menjalankan beberapa transaksi terpisah per sasaran (tidak atomik) | Dibungkus satu transaksi luar |
| 19 | `RktController::save` melaporkan sukses walau tidak ada program dipilih | Ditolak dengan pesan |
| 20 | Pesan exception teknis bocor ke layar (hapus IKU Kab/OPD, update LAKIP, status RPJMD/Renstra) | Lewat penyaring terpusat `pesanGalat()` (kode rujukan ke log) |
| 21 | 14 penulisan berkas debug `writable/debug_rpjmd_model.txt` di `RpjmdModel`; log POST mentah (termasuk token CSRF) di `PkController`; `print_r` POST di `RktController` | Dihapus |
| 22 | Method mati yang `return true` tanpa cek (`PkModel::updateCompletePk`, `RkpdModel::deleteCompleteRkpd`, `RenjaModel::deleteCompleteRenja`) dan 3 helper hapus di `RenstraModel` mengembalikan hasil query terakhir, bukan hasil transaksi | Mengembalikan `transStatus()` |
| 23 | Email user tidak divalidasi formatnya | `FILTER_VALIDATE_EMAIL` |
| 24 | Tidak ada UNIQUE untuk `target_rencana (pk_indikator_id, opd_id)` — cek-lalu-insert rawan kiriman ganda | Indeks unik ditambahkan (§4) |
| 25 | Kolasi tabel campur (`utf8mb4_general_ci` / `0900_ai_ci` / `unicode_ci`) → JOIN antar kolom teks gagal "Illegal mix of collations" | 14 tabel diseragamkan (§4) |

---

## 4. Perubahan Skema Basis Data

Skrip: `db/update_2026-09-17_audit_crud.sql` — idempoten (aman diulang), seluruh logika di dalam satu prosedur tersimpan agar aman dijalankan lewat phpMyAdmin (phpMyAdmin memindahkan konteks ke `information_schema` setelah pernyataan yang menyebutnya).

| Perubahan | Alasan |
|---|---|
| `UNIQUE uq_target_rencana_pk_indikator (pk_indikator_id, opd_id)` pada `target_rencana` | Menjamin satu Rencana Aksi per indikator PK per OPD di tingkat basis data; baris berjangkar RPJMD/Renstra (`pk_indikator_id` NULL) tidak tersentuh |
| 14 tabel dikonversi ke `utf8mb4_general_ci`: `activity_logs`, `app_settings`, `kegiatan_pk`, `lakip_yatim_arsip`, `pegawai`, `permissions`, `renstra_indikator_tujuan`, `renstra_target_tujuan`, `renstra_tujuan`, `role_permissions`, `roles`, `rpjmd_cascading`, `rpjmd_visi`, `sub_kegiatan_pk` | Menghilangkan galat kolasi campur. Kolom FK semuanya numerik, relasi tidak tersentuh. Tabel arsip (`_backup_*`, `_bak_*`) dibiarkan |

**Status eksekusi:**

| Basis data | Dijalankan | Hasil |
|---|---|---|
| Lokal `test-sakip-new` | 17 Sep 2026 | UNIQUE terpasang, 14 tabel dikonversi, 0 baris ganda |
| Server `esakippringsewu_project` | 17 Sep 2026 (phpMyAdmin) | UNIQUE terpasang, 14 tabel dikonversi, 0 baris ganda; hanya 2 tabel arsip yang tersisa non-general_ci (disengaja) |

`renstra_target` dan `rpjmd_target` **sengaja tidak** diberi UNIQUE `(indikator, tahun)`: alur sunting "hapus baris lalu tambah lagi dengan tahun sama" dalam satu kiriman akan tertolak. Data saat ini 0 ganda; aplikasi menjaganya lewat upsert.

---

## 5. Pengujian

### 5.1 Uji langsung (server lokal `spark serve` + curl, sesi asli)

| Skenario | Hasil |
|---|---|
| Admin OPD 16 mengubah sasaran Es3 milik OPD 18 (form biasa) | Ditolak, pesan "tidak memiliki akses ke data OPD lain", **nama di basis data tidak berubah** |
| Skenario sama via AJAX | HTTP 403, JSON `success:false` |
| `updateEs4` dengan id baris Es3 (jenjang salah) | Ditolak "Data tidak ditemukan" |
| Membalik status Renstra OPD lain via AJAX | Ditolak, status di basis data tetap |
| Sasaran Es3 milik sendiri, nama dikosongkan | Ditolak validasi "Nama Sasaran Eselon III wajib diisi" |
| Sasaran Es3 milik sendiri, data sah (jalur normal) | **Berhasil** — jalur normal tetap bekerja |
| Akun dinonaktifkan admin saat sesi masih hidup | Request berikutnya dilempar ke halaman login, sesi dihancurkan |
| 6× login salah beruntun | Percobaan ke-6 ditolak "Terlalu banyak percobaan login" |
| ID sesi sebelum vs sesudah login | Berbeda (regenerate bekerja) |
| `admin_inspektorat` POST ke `rpjmd/versi/hapus`, `lakip/snapshot/finalkan`, `cascading/hapus-mapping`, `rpjmd/izin-sunting/ajukan` | Semua dialihkan ke `/unauthorized`; halaman baca tetap 200 |
| `admin_kab` menghapus misi RPJMD #7 | Ditolak: "belum bisa dihapus — masih dipakai: 91 tujuan Renstra (21 Perangkat Daerah)…" |

### 5.2 Uji tingkat model (perintah spark sementara, sudah dihapus)

| Skenario | Hasil |
|---|---|
| `deleteMisi()` misi dengan Renstra terbanyak | Ditolak; `renstra_tujuan` tetap 117 baris |
| `deleteSasaran()` sasaran RPJMD yang punya Renstra | Ditolak (15 tujuan Renstra, 5 PD) |
| `deleteCompleteRenstra()` sasaran yang punya RKT/IKU/cascading | Ditolak dengan rincian |
| `deleteCompleteRenstra()` sasaran **tanpa** dependen | Berhasil dihapus |
| `saveCompletePk()` dengan `id_satuan` tak sah | Dilempar pesan jelas; jumlah `pk` tetap 325 (sebelumnya: "berhasil" palsu) |

### 5.3 Uji sapu regresi

Seluruh rute GET tanpa parameter dikunjungi dengan sesi asli tiap peran:

| Peran | Rute | Error 5xx |
|---|---:|---:|
| admin_opd | 102 | 0 |
| admin_kab | 81 | 0 |
| admin (super) | 81 | 0 |
| admin_inspektorat | 81 | 0 |
| bupati | 78 | 0 |

Satu regresi ditemukan pada putaran pertama (variabel `$hitung` di `penghalangHapusPeriode` setelah refaktor) dan **sudah dibetulkan** sebelum putaran akhir. Seluruh berkas lolos `php -l`. Semua user uji, log uji, dan berkas uji sementara sudah dibersihkan.

---

## 6. Yang Sudah Baik Sejak Sebelum Audit

CSRF global aktif; tidak ada mutasi lewat GET; tidak ada *mass-assignment* (semua model utama memakai `allowedFields`); penyaring anti-`<>` pada input teks; trait `TransaksiAman` dan `transException(true)` di modul versi dokumen, revisi IKU, snapshot LAKIP, MONEV, Program PK; cek pemilik di LAKIP update/delete, Target, RKT, IKU, PK, Rencana Aksi, Pelaksana; kunci siklus hidup versi resmi; unggah logo membatasi ekstensi (SVG ditolak).

---

## 7. Catatan yang Sengaja Tidak Diubah

| Hal | Alasan |
|---|---|
| `AiAnalysisController` menampilkan pesan galat Gemini apa adanya | Pesan berasal dari klien API (tanpa kunci API) dan memang ditujukan ke pengguna |
| `saveEs3` masih menerima `renstra_indikator_sasaran_id` dari POST | Jangkarnya diterjemahkan ulang oleh `jangkarSumber()`; risiko rendah |
| `RktController::update` — `$tahun ?? date('Y')` tidak menangkap string kosong | Kosmetik |

---

## 8. Berkas yang Berubah

24 berkas kode (+1.012 / −165 baris) dan 1 skrip SQL:

```
app/Controllers/AdminKab/DashboardThresholdController.php
app/Controllers/AdminKab/IkuController.php
app/Controllers/AdminOpd/CascadingController.php
app/Controllers/AdminOpd/IkuController.php
app/Controllers/AdminOpd/LakipOpdController.php
app/Controllers/AdminOpd/PkController.php
app/Controllers/AdminOpd/PkRenaksiController.php
app/Controllers/AdminOpd/RenstraController.php
app/Controllers/AdminOpd/RktController.php
app/Controllers/LoginController.php
app/Controllers/RpjmdController.php
app/Controllers/SuperAdmin/MasterController.php
app/Controllers/TwoFactorController.php
app/Filters/AuthFilter.php
app/Filters/ModulePermissionFilter.php
app/Helpers/rbac_helper.php
app/Libraries/Pegawai/PegawaiSyncService.php
app/Models/DashboardThresholdModel.php
app/Models/Opd/RenjaModel.php
app/Models/Opd/RenstraModel.php
app/Models/PkModel.php
app/Models/RkpdModel.php
app/Models/RoleModel.php
app/Models/RpjmdModel.php
db/update_2026-09-17_audit_crud.sql
```

---

## 9. Langkah Penerapan

1. **Skema basis data server** — sudah diterapkan (§4). Tidak perlu diulang.
2. **Kode** — commit dan deploy 24 berkas di atas ke server. Perbaikan aplikasi (§3) baru berlaku di produksi setelah langkah ini.
3. **Pasca-deploy** — pengguna yang sedang login tidak terpengaruh; pemeriksaan ulang akun oleh `AuthFilter` berjalan otomatis pada request berikutnya.

## 10. Pedoman untuk Pengembangan Selanjutnya

- Setiap penghapusan dokumen induk (RPJMD, Renstra, OPD, master) **wajib** lewat pagar dependen (`penghalangHapus*()` / `pemakaian()`), bukan langsung `delete()`.
- Setelah `transComplete()` **selalu** periksa `transStatus()`; atau pakai `transException(true)` / trait `TransaksiAman`.
- Kepemilikan baris diperiksa terhadap **data di basis data**, bukan nilai kiriman form.
- Rute tulis baru di `adminkab/*` dan `adminopd/*` otomatis menuntut izin tulis; tetap tambahkan `user_can()` bila aksinya butuh izin khusus.

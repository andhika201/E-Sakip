# Rencana Migrasi E-SAKIP: CodeIgniter 4 → Laravel

> Dibuat: 2026-06-10 · Sumber: hasil pembacaan kode di branch `main`.

---

## ✅ STATUS: MIGRASI PADA DASARNYA SELESAI (update 2026-06-11)

Proyek Laravel ada di **`C:\diskominfo\e-sakip-laravel`** (skema fresh `e_sakip_laravel`,
Eloquent, pola thin Controller → Form Request → Service → Model, role Spatie
`super_admin`/`admin_kab`/`admin_opd`). Catatan di bawah (§2 dst.) adalah rencana awal
yang **disempurnakan** saat eksekusi (mis. memakai Eloquent + skema baru, bukan
mempertahankan skema CI seperti saran §2).

**Sudah dimigrasikan & terverifikasi (semua halaman render 200):**

- **Admin CRUD**: RPJMD, Renstra, RKT (+ hapus & ubah status), IKU, Cascading,
  PK (semua jenis + cetak PDF), Program/Kegiatan/Sub Kegiatan, Target, Monev, Lakip,
  OPD, Pegawai, Jabatan, Pangkat, Satuan, User, Role/Permission, RKPD (rekap).
- **Auth**: login/logout (Fortify) + halaman **Profil & ganti password**.
- **Portal publik read-only** (`/publik/*`, tanpa login): beranda, RPJMD, RKPD, Renstra,
  RKT, IKU OPD, LAKIP kab/opd, PK bupati/JPT/administrator/pengawas,
  Cascading kab/opd, Pohon Kinerja kab/opd, Tentang Kami. Hanya data `status='selesai'`.
- **Cetak PDF** (laravel-dompdf): cascading kab/opd (publik), target & monev (admin),
  PK (sudah ada sebelumnya).
- **API** (`/api/*`, middleware token `api.token`): perangkat-daerah (index/show/iku/
  cascading/pohon-kinerja) + endpoint global, plus dokumentasi di `/api-docs`.
- **Import Excel Program PK** (phpoffice/phpspreadsheet) di modul Program.

Sisa pekerjaan bersifat poles: penyesuaian tampilan/format PDF agar pixel-perfect
dengan CI, dan pengisian data uji untuk modul yang tabelnya masih kosong di dev DB.

---

## 1. Status proyek saat ini

| Komponen | Jumlah | Lokasi |
|---|---:|---|
| Controllers | 29 | `app/Controllers/` (termasuk `AdminKab/`, `AdminOpd/`, `Api/`) |
| Models | 20 | `app/Models/` (termasuk `Opd/`) |
| Views | 128 | `app/Views/` |
| Migrations | 50 | `app/Database/Migrations/` |
| Helpers | 3 | `format_helper`, `lakip_helper`, `number_helper` |
| Routes | ~279 baris | `app/Config/Routes.php` |
| Total LOC `app/` | ~55.800 | — |

**Framework:** CodeIgniter 4 (`codeigniter4/framework ^4.0`), PHP `^8.1`.
**Dependency pihak ketiga:** `mpdf/mpdf` (cetak PDF, dipakai 8 controller),
`phpoffice/phpspreadsheet` (import Excel, dipakai `ProgramPkController`).

### Karakteristik penting (memengaruhi strategi)

1. **Model nyaris tidak memakai ORM.** Semua `protected $returnType = 'array'` dan
   query ditulis manual via `$this->db->table()->select()->join()...`
   (lihat `app/Models/Opd/IkuModel.php`). → Lebih tepat dimigrasikan ke
   **Laravel Query Builder (`DB::table()`)**, bukan dipaksa ke Eloquent.
2. **View pakai `$this->include()`** (319 kemunculan), bukan sistem
   `extend()`/`section()` CI4. → Memetakan rapi ke Blade `@include`/`@extends`.
3. **Auth custom berbasis session** dengan 3 role: `admin_kab`, `admin_opd`,
   `admin` (superadmin). Filter `auth:admin_kab,admin`.
4. **CSRF dinonaktifkan** saat ini (`app/Config/Filters.php` globals di-comment).
   Laravel mengaktifkan CSRF default → **gotcha besar** (lihat §6).

---

## 2. Keputusan database — PERTAHANKAN SKEMA & DATA

**Rekomendasi: pakai database MySQL existing apa adanya.**

- Arahkan Laravel ke DB yang sama lewat `.env` (`DB_*`). Tabel, kolom, relasi, dan
  data **tidak diubah**.
- 50 migrasi CI4 **tidak perlu ditulis ulang** agar aplikasi jalan.
- Primary key / nama tabel non-standar cukup dideklarasikan di model:
  - `users` → PK `user_id` (bukan `id`)
  - tabel lain pakai PK `id` standar
- **Opsional (nanti):** untuk portabilitas deploy fresh, generate migrasi Laravel
  dari skema existing dengan `kitloong/laravel-migrations-generator` (sekali jalan).

**Jangan** menulis ulang 50 migrasi secara manual di awal — risiko tinggi, manfaat rendah.

---

## 3. Pemetaan konsep CI4 → Laravel

| CI4 | Laravel | Kesulitan | Catatan |
|---|---|:---:|---|
| `app/Controllers/*` | `app/Http/Controllers/*` | 🟢 | `return view()` → `return view()`, mirip |
| Model Query Builder | `DB::table()` (Query Builder) | 🟡 | Sintaks ~1:1, lihat contoh di bawah |
| View `.php` + `$this->include` | Blade `.blade.php` + `@include`/`@extends` | 🟡 | Banyak file, kerja mekanis |
| `app/Config/Routes.php` | `routes/web.php` + `routes/api.php` | 🟢 | `group()`/prefix mirip |
| `AuthFilter` (session+role) | Middleware + parameter role | 🟢 | Logika sederhana |
| `ApiTokenFilter` | Middleware token | 🟢 | — |
| `$this->validate($rules)` | Form Request / `Validator` | 🟡 | Sintaks aturan beda |
| Validation di Model | Form Request | 🟡 | Pindah ke layer request |
| `session()->get/set` | `session()->get/put` / `Auth` | 🟢 | API mirip |
| Helpers (`app/Helpers`) | `app/Helpers` + autoload composer / `@php` | 🟢 | — |
| `mpdf` (manual) | `mpdf` tetap, atau `barryvdh/laravel-dompdf` | 🟢 | mpdf bisa dipakai langsung |
| `phpspreadsheet` | tetap, atau `maatwebsite/excel` | 🟢 | — |
| 50 Migrations | DB existing (lihat §2) | 🟢 | — |

### Contoh konkret — Query Builder (paling sering muncul)

```php
// CI4  (app/Models/Opd/IkuModel.php)
$this->db->table('iku')
    ->select('iku.*, renstra_sasaran.sasaran AS sasaran_renstra', false)
    ->join('renstra_sasaran', 'renstra_sasaran.id = iku.renstra_id', 'left')
    ->where('renstra_sasaran.opd_id', $opd_id)
    ->orderBy('iku.id', 'ASC')
    ->get()->getResultArray();

// Laravel  (nyaris identik)
DB::table('iku')
    ->selectRaw('iku.*, renstra_sasaran.sasaran AS sasaran_renstra')
    ->leftJoin('renstra_sasaran', 'renstra_sasaran.id', '=', 'iku.renstra_id')
    ->where('renstra_sasaran.opd_id', $opd_id)
    ->orderBy('iku.id', 'asc')
    ->get();   // -> Collection of stdClass; ->map(fn($r)=>(array)$r) jika butuh array
```

### Contoh — Transaksi

```php
// CI4
$db->transStart(); ... $db->transComplete();
if ($db->transStatus() === false) { ... }

// Laravel
DB::transaction(function () { ... });           // auto rollback on exception
// atau manual: DB::beginTransaction(); ... DB::commit(); / DB::rollBack();
```

### Contoh — View

```php
// CI4 view: <?= $namaOpd ?>  +  <?php $this->include('templates/header') ?>
// Blade:    {{ $namaOpd }}    +  @include('templates.header')
```

### Contoh — Auth filter → middleware

```php
// CI4: routes group ['filter' => 'auth:admin_kab,admin']
// Laravel: Route::middleware(['auth.role:admin_kab,admin'])->group(...)
//   middleware mengecek session('isLoggedIn') + in_array(session('role'), $roles)
```

---

## 4. Dependency Laravel yang dibutuhkan

- `laravel/framework` (target: versi LTS terbaru yang didukung PHP 8.1+)
- `mpdf/mpdf` — **tetap** (sudah dipakai), atau ganti `barryvdh/laravel-dompdf`
- `phpoffice/phpspreadsheet` — **tetap**, atau `maatwebsite/excel`
- (dev) `kitloong/laravel-migrations-generator` — opsional, untuk generate migrasi dari DB

Auth: cukup **session custom** yang sudah ada (port langsung). Tidak wajib pakai
Laravel Breeze/Jetstream/Sanctum kecuali ingin refactor login.

---

## 5. Struktur folder target (Laravel)

```
app/
  Http/
    Controllers/        # 29 controller dipindah, namespace App\Http\Controllers
      AdminKab/
      AdminOpd/
      Api/
    Middleware/         # AuthRole, ApiToken
    Requests/           # Form Request (validasi) — opsional bertahap
  Models/               # jika pakai Eloquent (bertahap); awalnya bisa kosong
  Helpers/              # 3 helper, di-autoload via composer
resources/
  views/                # 128 view → .blade.php
    adminKabupaten/
    adminOpd/
    user/
    templates/
    layout/
routes/
  web.php               # mayoritas route
  api.php               # group 'api'
database/
  migrations/           # (opsional) hasil generate dari DB existing
public/                 # asset (CSS/JS/img) dari public/ lama
```

---

## 6. Gotchas & risiko khusus proyek ini

1. **CSRF (RISIKO TERTINGGI).** Laravel mengaktifkan CSRF di semua `POST`.
   Setiap form di 128 view yang mengirim POST **wajib** menambah `@csrf`,
   jika tidak → error **419 Page Expired**. Buat checklist per form.
2. **`AdminOpd\PkController` dipakai lintas group** (adminkab & adminopd merujuk
   controller PK yang sama). Pastikan prefix route & otorisasi role konsisten.
3. **mpdf manual** (8 controller cetak): penyesuaian path font, `tempDir`, dan
   cara `output()`/stream di environment Laravel.
4. **Upload/download file LAKIP** (`lakip/download/(:num)`): sesuaikan storage path
   ke `storage/app` + symlink `public`, atau pertahankan folder `writable/`.
5. **Import Excel** (`program_pk/import`): logika parsing phpspreadsheet dipindah
   apa adanya; hanya cara terima file (`$request->file()`) yang berubah.
6. **Return type array.** Query Builder Laravel mengembalikan `stdClass`/Collection,
   bukan array. View yang mengakses `$row['kolom']` perlu `(array)` cast atau
   ubah ke `$row->kolom`. Tentukan satu konvensi di awal.
7. **Routing param.** `(:num)` → `{id}` (+ `->whereNumber('id')`),
   `(:any)`/`(:segment)` → `{param}`. Route `match(['get','post','delete'])` →
   `Route::match(['get','post','delete'], ...)`.
8. **Helper global.** `format_helper`/`number_helper`/`lakip_helper` di-load via
   `composer.json` `autoload.files`.

---

## 7. Fase migrasi yang disarankan

| Fase | Isi | Output |
|---|---|---|
| **0** | Setup Laravel fresh + sambung DB existing + asset `public/` | App jalan, koneksi DB OK |
| **1** | Auth (Login/Logout/ChangePassword) + middleware role + layout/template Blade | Bisa login, role terjaga |
| **2** | 1 modul vertikal penuh = **IKU** (jadi pola/template modul) | Pola baku CRUD+view |
| **3** | Replikasi pola ke modul lain (lihat checklist §8) | Semua modul fungsional |
| **4** | Cetak PDF (mpdf) + Import Excel (Program PK) + Upload/Download LAKIP | Fitur dokumen jalan |
| **5** | API (`/api/*`) + token middleware + API docs | API paritas |
| **6** | QA paritas fitur, hardening CSRF, hapus kode CI4 lama | Rilis |

---

## 8. Checklist per modul

Tanda: `[ ]` belum · `[~]` sebagian · `[x]` selesai.
Kolom: **C** = Controller, **M** = Model/Query, **V** = View→Blade, **R** = Route, **Mid** = Middleware/otorisasi.

### Fondasi
- [ ] **Setup & DB** — `.env`, koneksi MySQL existing, asset publik
- [ ] **Layout/Template** — `templates/header,footer,style,sidebar` (3 set: adminKab, adminOpd, user) → Blade layout
- [ ] **Auth** — Login, Logout, ChangePassword · C/V/R/Mid · session role
- [ ] **Middleware** — `auth.role:...`, `api-token`
- [ ] **Helpers** — `format_helper`, `number_helper`, `lakip_helper`

### Admin Kabupaten (`/adminkab`)
- [ ] **Dashboard Kab** — `AdminKabupatenController` + `getDashboardData` (AJAX)
- [ ] **RPJMD** — misi/tujuan/sasaran/indikator/target · CRUD + status
- [ ] **RKPD** — CRUD + status
- [ ] **IKU (kab)** — `AdminKab\IkuController` · CRUD + change_status
- [ ] **Cascading (kab)** — index/tambah/save/CSF/cetak/cetak-pohon (PDF)
- [ ] **RKT (kab)** — CRUD + status
- [ ] **Target (kab)** — CRUD + cetak (PDF)
- [ ] **Monev (kab)** — CRUD + cetak (PDF)
- [ ] **LAKIP Kab** — CRUD + upload/download + status
- [ ] **Program PK** — CRUD + **import Excel**
- [ ] **PK Bupati** — cetak
- [ ] **Tentang Kami (kab)** — view/edit/save

### Admin OPD (`/adminopd`)
- [ ] **Dashboard OPD** — `AdminOpdController`
- [ ] **Renstra** — sasaran/indikator/target + edit-tujuan · CRUD + status
- [ ] **Renja** — CRUD (cek route aktif)
- [ ] **RKT (opd)** — CRUD + delete-indikator + status
- [ ] **IKU (opd)** — CRUD + change_status
- [ ] **Target (opd)** — CRUD
- [ ] **Monev (opd)** — CRUD + cetak (PDF)
- [ ] **LAKIP OPD** — CRUD + upload/download + status
- [ ] **Cascading (opd)** — ES3 & ES4 (tambah/edit/update/delete) + CSF + cetak + pohon (PDF)
- [ ] **PK** — `AdminOpd\PkController` (index/tambah/edit/update/cetak/delete) + capaian
- [ ] **PK Admin** — `PkAdminController` + cetak
- [ ] **PK JPT** — `PkJptController`
- [ ] **PK Pengawas** — `PkPengawasController` + cetak
- [ ] **Program PK Search (opd)** — `AdminOpd\ProgramPkController::search`
- [ ] **Tentang Kami (opd)**

### Publik / User (read-only, tanpa login)
- [ ] **Dashboard publik** + RKPD/RPJMD/Renja/Renstra/RKT
- [ ] **LAKIP** kabupaten & opd (view)
- [ ] **IKU OPD** (view)
- [ ] **PK** bupati/pimpinan/administrator/pengawas (view)
- [ ] **Cascading** kabupaten & opd + cetak + cetak-pohon (PDF)
- [ ] **Tentang Kami** (view)

### API (`/api`, filter token)
- [ ] **Perangkat Daerah** — index, show, iku, cascading, pohon-kinerja
- [ ] **IKU / Cascading / Pohon Kinerja** (global)
- [ ] **API Docs** (`/api-docs`, swagger view)
- [ ] **Token middleware** paritas dengan `ApiTokenFilter`

---

## 9. Inventaris controller (referensi)

```
AdminKab/      CascadingController, IkuController, LakipController, MonevController, TargetController
AdminOpd/      CascadingController, IkuController, LakipOpdController, MonevController,
               PkAdminController, PkController, PkJptController, PkPengawasController,
               RenstraController, RktController, TargetController
Api/           PerangkatDaerahController
(root)         AdminKabupatenController, AdminOpdController, ApiDocsController, BaseController,
               ChangePasswordController, Home, LoginController, ProgramPkController,
               RkpdController, RpjmdController, User, UserController
```

## 10. Inventaris model (referensi)

```
CascadingModel, DashboardModel, DashboardKabupatenModel, DashboardOpdModel,
LakipModel, OpdModel, PegawaiModel, PkModel, ProgramPkModel, RkpdModel, RktModel,
RpjmdModel, SatuanModel, UserModel, UserPublicModel,
Opd/IkuModel, Opd/MonevModel, Opd/RenjaModel, Opd/RenstraModel, Opd/TargetModel
```

---

## 11. Estimasi & catatan akhir

- Migrasi penuh realistis butuh **beberapa minggu kerja** karena volume (128 view,
  29 controller, banyak modul cetak PDF).
- **Bagian termurah:** database (dipertahankan), routes, controller, middleware.
- **Bagian termahal:** konversi 128 view ke Blade + audit `@csrf` semua form.
- **Strategi paling aman:** kerjakan **1 modul vertikal (IKU) sampai tuntas dulu**
  sebagai template, baru replikasi — bukan migrasi semua layer sekaligus.
- Pakai **Query Builder Laravel**, bukan Eloquent, untuk paritas tercepat dengan
  model existing. Eloquent bisa diadopsi bertahap setelahnya.

# e-SAKIP Kabupaten Pringsewu — AKSARA

**AKSARA** (Akuntabilitas Sistem Kinerja Aparatur) adalah aplikasi **e-SAKIP** (Sistem Informasi
Akuntabilitas Kinerja Instansi Pemerintah) Kabupaten Pringsewu. Aplikasi ini menyajikan data
perencanaan, pengukuran, dan pelaporan kinerja pemerintah daerah secara transparan dan terintegrasi —
mulai dari RPJMD/RKPD hingga Renstra, Pohon Kinerja (Cascading), Perjanjian Kinerja, dan LAKIP.

Dibangun dengan **CodeIgniter 4**.

---

## ✨ Fitur

### Publik (tanpa login)
- **Beranda** — landing dengan akses cepat ke modul utama.
- **Kinerja Pemerintah Kabupaten** — RPJMD, RKPD, Pohon Kinerja & Cascading, PK Bupati, LAKIP.
- **Kinerja Perangkat Daerah** — Renstra, Pohon Kinerja & Cascading (OPD), RKT/Renja, IKU,
  PK (JPT, Administrator, Pengawas), LAKIP OPD.
- **Pohon Kinerja** tampil inline (toggle Tabel ⇄ Pohon) + zoom, serta cetak PDF.
- **Tentang Kami** — profil & makna logo AKSARA.
- Tabel dengan **pagination otomatis** dan tampilan **responsif** (mobile-friendly).

### Area Admin (login)
Tiga peran: **Super Admin** (`admin`), **Admin Kabupaten** (`admin_kab`), **Admin OPD** (`admin_opd`).
Menu tampil otomatis sesuai hak akses (RBAC per-menu).

- **Dashboard** statistik + grafik (Chart.js).
- **Kabupaten** — RPJMD, RKPD, IKU, Perjanjian Kerja (PK Bupati & Program PK),
  Pengukuran Kinerja (Target & Rencana Aksi, MONEV, LAKIP), Pohon Kinerja & Cascading.
- **Perangkat Daerah** — Renstra, Renja/RKT, IKU OPD, Perjanjian Kinerja (JPT/Administrator/Pengawas),
  Pengukuran Kinerja (Target, MONEV, LAKIP), Pohon Kinerja & Cascading (ESS II/III/IV) yang dapat diedit.
- **Cetak PDF** (mPDF) untuk cascading, pohon kinerja, target, monev, dan LAKIP.
- **Super Admin** — Master Data (pegawai, pangkat, jabatan, OPD, pengguna, role & permission, satuan),
  Sinkronisasi SIMPEG/SIKASN, Log Aktivitas, dan **Pengaturan Aplikasi**.
- **Keamanan** — login, **2FA (TOTP / Google Authenticator)**, ganti password, RBAC, filter peran per-rute.

### Pengaturan Aplikasi (Super Admin)
Halaman `adminkab/pengaturan` untuk mengatur identitas web tanpa mengubah kode:
- Nama aplikasi & tagline
- Nama instansi + alamat, telepon, email (footer)
- Logo aplikasi & favicon
- Logo pengembang, nama pengembang, serial number
- SEO (meta description, keywords, author, Open Graph)

Nilai disimpan di tabel `app_settings` dan dibaca lewat helper `setting()` di seluruh aplikasi
(footer, header, login, favicon, dan meta SEO).

---

## 🧱 Teknologi

- **Backend:** CodeIgniter 4 (PHP 8.1+)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5.3, Font Awesome 6.4, font Inter, Select2, Chart.js
- **PDF:** mpdf/mpdf · **QR Code:** qrcodejs (untuk setup 2FA)

---

## ⚙️ Kebutuhan Sistem

- PHP **8.1+** dengan ekstensi: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`, `gd` (untuk mPDF)
- MySQL **5.7+** / MariaDB
- **Composer**

---

## 🚀 Instalasi

```bash
# 1. Clone repository
git clone <url-repo> e-sakip
cd e-sakip

# 2. Install dependency
composer install

# 3. Konfigurasi environment
cp env .env
```

Edit `.env`, sesuaikan minimal:

```ini
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = test_sakip
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

```bash
# 4. Siapkan database
#    - Buat database (mis. test_sakip)
#    - Import skema + data awal (file SQL dari tim)

# 5. Jalankan
php spark serve
# akses: http://localhost:8080
```

> Pada produksi, arahkan web server / virtual host ke folder **`public/`** (bukan root proyek).

---

## 🗄️ Database

Skema & data SQL disimpan di folder **`db/`**:

- `db/test_sakip.sql` — dump skema + data awal (import sekali saat setup).
- `db/update_YYYY-MM-DD.sql` — berkas pembaruan skema **idempoten** (aman dijalankan berulang).
  **Setiap ada tabel/kolom baru, dibuatkan file update bertanggal di sini.**

```bash
# import awal
mysql -u root -p test_sakip < db/test_sakip.sql

# pembaruan terbaru
mysql -u root -p test_sakip < db/update_2026-06-13.sql   # RBAC, activity_logs, 2FA, simpeg_id, IKU
mysql -u root -p test_sakip < db/update_2026-06-28.sql   # tabel app_settings (Pengaturan Aplikasi)
```

> Catatan: `php spark migrate` saat ini **terblokir** oleh salah satu migrasi lama, sehingga
> perubahan skema diterapkan lewat berkas SQL di `db/` (bukan migration).

- Tabel `app_settings` (key-value) menyimpan konfigurasi Pengaturan Aplikasi; sudah berisi nilai default.
- Pastikan direktori **`public/uploads/`** dapat ditulis (untuk unggah logo/favicon dari Pengaturan Aplikasi).

---

## 📁 Struktur Singkat

```
app/
├─ Controllers/           # AdminKabupatenController, AdminOpdController, SettingController, AdminKab/*, AdminOpd/*, ...
├─ Models/                # CascadingModel, DashboardOpdModel, ...
├─ Helpers/               # rbac_helper, activity_helper, setting_helper
├─ Views/
│  ├─ user/               # halaman publik (+ templates: header, footer, style)
│  ├─ adminKabupaten/     # halaman admin kabupaten/super admin
│  ├─ adminOpd/           # halaman admin OPD
│  ├─ templates/          # admin_chrome (header+sidebar terpadu), admin_menu, layout
│  ├─ login.php, two_factor_*.php, dashboard.php
└─ Config/                # Routes.php, Autoload.php, ...
public/                    # web root (index.php, assets/, uploads/)
```

Catatan desain:
- **Header + sidebar admin** disatukan di `app/Views/templates/admin_chrome.php` (satu sumber untuk
  semua peran, otomatis menyesuaikan label peran).
- **Menu sidebar** terpusat di `app/Views/templates/admin_menu.php` dengan gating `user_can()`.

---

## 🔐 Akses & Peran

| Peran        | Kode        | Akses |
|--------------|-------------|-------|
| Publik       | —           | Halaman informasi tanpa login |
| Super Admin  | `admin`     | Semua modul + Master Data, Log Aktivitas, Pengaturan Aplikasi |
| Admin Kabupaten | `admin_kab` | Modul tingkat kabupaten |
| Admin OPD    | `admin_opd` | Modul tingkat perangkat daerah (sesuai OPD pengguna) |

Login melalui `/login`. Menu & rute dibatasi oleh filter peran (`auth:<role>`) dan helper `user_can()`.

---

## 👨‍💻 Pengembang

**DevTech** — Dinas Komunikasi dan Informatika (Diskominfo) Kabupaten Pringsewu.

© 2026 Pemerintah Kabupaten Pringsewu. All rights reserved.

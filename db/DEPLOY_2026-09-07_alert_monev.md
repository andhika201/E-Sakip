# Perbaikan early warning palsu di dashboard — 7 September 2026

Dua peringatan palsu, satu akar yang sama: **dashboard menagih pekerjaan yang
belum jatuh tempo.** Keduanya diperbaiki bersama dan diunggah sebagai satu paket.

| # | Gejala | Keadaannya |
|---|---|---|
| A | Indikator ber-MONEV lengkap tetap *Belum Valid* karena target triwulannya baru jatuh tempo nanti | sudah diperbaiki di `main` (`b4bda74`), server belum menerimanya |
| B | *Laporan Kinerja (LAKIP) 2026 — Belum ada data* muncul di tengah tahun 2026 | **baru** diperbaiki 7 Sep, belum ada di `main` |

Gejala yang dilaporkan: indikator **"Jumlah PMA dan PMDN"** (DPMPTSP) muncul di
kartu **Perlu Perhatian / Prioritas Tindak Lanjut** dengan status *Belum Valid*
dan tombol *Lengkapi MONEV*, padahal MONEV-nya sudah terisi lengkap dan layar
MONEV-nya sendiri sudah menampilkan **100%**.

Kalimatnya di layar:

    Total target triwulan bernilai 0, Capaian Total tidak dapat dihitung.
    Yang belum lengkap: sub rencana aksi "Penyediaan peta potensi dan peluang
    usaha unggulan daerah" (dari 3 sub).

---

# A. Alert MONEV — *Belum Valid* padahal sudah lengkap

## SEBAB — bukan datanya, dan bukan pula bug yang masih hidup

Sub itu targetnya jatuh tempo di **TW III** (pola target `0-0-1-0`). Dashboard
menyaring data sampai triwulan terpilih, dan bawaannya = triwulan terakhir yang
sudah tutup — pada September berarti **TW II**
(`dash_triwulan_terakhir_selesai()`, `app/Helpers/dashboard_status_helper.php`).

Sampai TW II akumulasi targetnya `0 + 0 = 0`. Aturan LAMA menolak pembagi nol
itu sebagai kesalahan input, menandai indikatornya *Belum Valid*, lalu
memasukkannya ke daftar tindak lanjut — pekerjaan yang tidak bisa diselesaikan
siapa pun, karena targetnya memang baru jatuh tempo triwulan berikutnya.

Aturan itu **sudah diganti** pada commit `b4bda74` (5 Sep 2026): keadaan
tersebut kini berstatus *Belum Dapat Dinilai* (abu-abu), dikeluarkan dari
rata-rata indikator, dan tidak lagi menjadi butir tindak lanjut.

### Bukti versi server tertinggal

Kalimat `Total target triwulan bernilai 0, ...` **sudah tidak ada di kode
manapun** sejak `b4bda74` — sisanya hanya komentar sejarah di
`app/Helpers/capaian_helper.php`. Sementara kalimat `Yang belum lengkap: sub
rencana aksi ...` baru lahir di `a8f15d2` (27 Agu).

Kedua kalimat hanya bisa tampil BERSAMAAN pada rentang `a8f15d2 .. a4d7f18`.
Jadi server berada di rentang itu — hampir pasti `a4d7f18` (3 Sep, "dashboard"),
commit tepat sebelum perbaikannya.

---

## YANG TIDAK PERLU DISENTUH

| | Alasan |
|---|---|
| **Basis data** | Tidak ada satu pun perubahan skema atau data. Alert dihitung ulang setiap kali halaman dibuka, langsung dari `target_sub_rencana.target_triwulan_*` + `monev.capaian_triwulan_*`. Tidak ada tabel alert, tidak ada cache, tidak ada cron. |
| **View dashboard** | `b4bda74` tidak mengubah satu pun view dashboard, dan tidak ada view yang menyebut kunci baru (`belum_dinilai`, `tak_terukur`, `not_evaluable`) — semuanya merender keluaran service secara umum. View di server tetap cocok apa adanya. |
| **Data MONEV yang sudah diisi** | Sudah benar. Tidak perlu dibuka-simpan ulang. |

---

# B. Alert LAKIP — menagih laporan yang belum boleh ada

Gejala: dashboard Dinas Pertanian, **Tahun 2026 s.d. Triwulan II**, panel
Prioritas Tindak Lanjut berisi satu butir:

    Laporan Kinerja (LAKIP) 2026
    Belum ada data LAKIP tahun 2026 sehingga capaian belum difinalkan.
    [Belum final] [Buka LAKIP]

## SEBAB

`statusLakip()` menanyakan LAKIP untuk **tahun filter dashboard**. Padahal LAKIP
tahun X baru dapat disusun SETELAH tahun X berakhir — isinya capaian setahun
penuh. Menagih LAKIP 2026 pada Juli 2026 berarti menuntut dokumen yang memang
belum boleh ada, dan butirnya akan menyala sepanjang tahun tanpa bisa
diselesaikan siapa pun. Kelas kesalahan yang sama persis dengan bagian A.

## PERBAIKANNYA

Fungsi baru `dash_tahun_lakip_jatuh_tempo()` di
`app/Helpers/dashboard_status_helper.php` menentukan LAKIP terakhir yang
tahunnya **sudah tutup**:

    dashboard 2026 dibuka pada 2026  ->  LAKIP 2025   (satu tahun ke belakang)
    dashboard 2025 dibuka pada 2026  ->  LAKIP 2025   (tahun itu sendiri, sudah tutup)
    dashboard 2024 dibuka pada 2026  ->  LAKIP 2024

Aturannya sengaja **bukan** "selalu tahun − 1". Bila yang dibuka tahun yang sudah
lewat, LAKIP tahun itu sendirilah yang jatuh tempo; menggesernya lagi ke belakang
akan membuat dashboard tahun lampau menagih dokumen yang salah dan tidak pernah
menyinggung LAKIP tahun yang sedang ditampilkan.

Judul butir, kalimatnya, **dan tautan tombol "Buka LAKIP"** ikut digeser ke tahun
itu — tombol yang membuka tahun lain akan memperlihatkan halaman yang tampak
baik-baik saja, dan butirnya terbaca sebagai tuduhan keliru.

Dikunci 6 assertion `php spark dash:verify`, blok
*"LAKIP: yang ditagih hanya tahun yang sudah tutup"*. Sudah dibuktikan menggigit:
dijalankan pada service versi lama, 3 dari 6 gagal.

**Yang berubah:** `app/Helpers/dashboard_status_helper.php`,
`app/Services/OpdDashboardService.php` — dua berkas yang sudah ada di daftar
unggah bagian A, jadi tidak menambah berkas baru.

**Catatan tenggat:** tagihan menyala sejak 1 Januari, sedangkan tenggat resmi
penyampaian LAKIP sekitar akhir Maret. Itu memang masa penyusunannya dan bobot
butirnya paling ringan (abu-abu, *Belum final*), jadi tidak diberi tenggang.
Bila kelak diminta, cukup ubah `dash_tahun_lakip_jatuh_tempo()`.

---

## LANGKAH 1 — CADANGKAN

Salin dulu 5 berkas yang akan ditimpa (lewat File Manager / FTP), mis. ke
`_backup_2026-09-07/` dengan struktur folder yang sama. Ini satu-satunya jalan
mundur; tidak ada perintah yang bisa menggantikannya.

Basis data **tidak** perlu di-dump untuk langkah ini — tidak ada tulisan ke DB.

---

## LANGKAH 2 — UNGGAH 5 BERKAS

**Ambil dari berkas di dalam paket ini**, bukan dari `main`: perbaikan B
(alert LAKIP) belum di-commit, sehingga `main` hari ini baru memuat perbaikan A.
Timpa di server pada path yang sama persis:

    app/Helpers/capaian_helper.php
    app/Helpers/dashboard_status_helper.php
    app/Services/OpdDashboardService.php
    app/Controllers/AdminOpd/PkRenaksiController.php
    app/Views/adminOpd/pk_renaksi/monev_form.php

**Kenapa cukup 5 berkas, dan kenapa aman:**

* `b4bda74` adalah anak LANGSUNG dari `a4d7f18` (sudah diperiksa:
  `b4bda74^ == a4d7f18`). Menimpakan berkas yang diubahnya ke server ber-`a4d7f18`
  menghasilkan keadaan **persis sama** dengan `b4bda74` — bukan campuran versi.
* Kelima berkas itu **tidak disentuh lagi** oleh commit setelahnya (`8d75682`,
  `ef2efdd` hanya mengurus IKU & cascading), jadi isinya di `main` hari ini
  identik dengan di `b4bda74`.
* Satu-satunya fungsi baru, `capaianAngkaRingkas()`, didefinisikan di dalam
  `capaian_helper.php` itu sendiri — tidak ada ketergantungan ke berkas di luar
  daftar ini.

Peran masing-masing:

| Berkas | Perannya |
|---|---|
| `capaian_helper.php` | Pembagi 0 -> status `not_evaluable`, bukan galat. **Inti perbaikannya.** |
| `dashboard_status_helper.php` | Status *Belum Dapat Dinilai* + meneruskan `not_evaluable`; **dan** `dash_tahun_lakip_jatuh_tempo()` (perbaikan B). |
| `OpdDashboardService.php` | Mengeluarkan baris itu dari rata-rata **dan** dari Prioritas Tindak Lanjut; **dan** menggeser tagihan LAKIP ke tahun yang sudah tutup (perbaikan B). |
| `PkRenaksiController.php` | Kartu *Rata-rata Realisasi* di layar MONEV berhenti menjumlah baris warisan. Tidak wajib untuk alert-nya, tapi satu paket dengan `b4bda74`. |
| `monev_form.php` | Kalimat di form MONEV ikut aturan baru (kosmetik). |

Berkas `app/Commands/DashVerify.php` dan `app/Commands/MonevHitungUlangTotal.php`
**tidak perlu diunggah**: keduanya perintah CLI, sedangkan server ini tidak
menyediakan akses shell. (`DashVerify.php` memuat 6 assertion penjaga perbaikan B
— berguna di mesin pengembang, tidak di server.)

---

## LANGKAH 3 — VERIFIKASI (tanpa CLI)

Login sebagai admin OPD **DPMPTSP**, buka Dashboard, biarkan filter triwulan
pada bawaannya.

Yang harus terlihat:

1. Indikator *Jumlah PMA dan PMDN* **hilang** dari Perlu Perhatian /
   Prioritas Tindak Lanjut.
2. Bila ditelusuri per baris, sub *"Penyediaan peta potensi dan peluang usaha
   unggulan daerah"* berlabel **Belum Dapat Dinilai** (abu-abu), bukan
   *Belum Valid* (merah).
3. Buka form MONEV sub itu — kalimatnya kini berbunyi
   `Belum dapat dinilai. Target kumulatif sampai Triwulan II masih 0.`
   Bila yang muncul masih kalimat `Total target triwulan bernilai 0, ...`,
   berkasnya belum benar-benar tergantikan (lihat catatan opcache di bawah).
4. Pilih filter **Triwulan III** — indikatornya muncul dengan angka wajar
   (target `1`, capaian `1`), tanpa alert.

Untuk perbaikan B, login sebagai admin OPD **Dinas Pertanian** (atau OPD mana pun),
Tahun **2026**:

5. Butir Prioritas Tindak Lanjut yang berbunyi *Laporan Kinerja (LAKIP) **2026***
   sudah **tidak ada lagi**.
6. Bila LAKIP 2025 OPD itu memang belum final, butirnya kini berbunyi
   *Laporan Kinerja (LAKIP) **2025***, dan tombol **Buka LAKIP** mendarat di
   halaman LAKIP **2025** (periksa `tahun=2025` pada alamatnya) — bukan 2026.

Tidak ada data yang perlu disimpan ulang. Cukup muat ulang halaman.

### Bila belum berubah setelah diunggah

Sebagian hosting menyalakan **OPcache** dengan `revalidate_freq` panjang,
sehingga PHP masih menjalankan berkas lama walau berkasnya sudah baru.
Restart PHP dari panel hosting (atau tunggu masa revalidasi habis).

---

## SISA PEKERJAAN — `monev.total` (terpisah, bukan penyebab alert)

`monev.total` adalah kolom **tersimpan**, ditulis dengan aturan yang berlaku
saat form MONEV disimpan. Baris lama masih membawa angka aturan lama — termasuk
nilai yang jelas bukan persentase (mis. `4091`, dan `"58%"` lengkap dengan tanda
persen). Yang membacanya: kartu *Rata-rata Capaian*, ekspor, dan API.

Ini **tidak** memengaruhi early warning (yang dihitung langsung dari data
mentah), jadi tidak menghalangi Langkah 1-3.

Alat resminya `php spark monev:hitung-ulang --fix` — **tidak bisa dipakai di
server ini** karena tidak ada shell. Perlu pengganti tanpa CLI, mengikuti pola
`db/LANJUTAN_TANPA_CLI.md`. Belum dibuat; kerjakan terpisah dan uji dulu pada
salinan basis data.

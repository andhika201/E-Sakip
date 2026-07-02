# Integrasi Data Pegawai dari SIMPEG

Sinkronisasi **OPD, Pangkat, Jabatan, dan Pegawai** dari **SIMPEG (Presensi Sync API)**
ke database E‑SAKIP. Spesifikasi API ada di [api_dokumentasi_presensi.md](api_dokumentasi_presensi.md).

> Akses: **khusus super admin** (role `admin`) lewat **Master Data → Sinkron SIMPEG**
> (`/adminkab/pegawai/sync`, filter `auth:admin`).

---

## 1. Komponen

| Komponen | Lokasi | Fungsi |
|---|---|---|
| Config | `app/Config/PegawaiSync.php` | `simpegEnabled`, `simpegBaseUrl`, `simpegToken`, `httpTimeout` (dari `.env`) |
| HTTP client | `app/Libraries/Pegawai/SimpegClient.php` | Panggil endpoint `/opd`, `/pangkat`, `/jabatan`, `/pegawai` (Bearer + X‑API‑TOKEN, envelope `{success,data,meta}`, paginasi) |
| Service | `app/Libraries/Pegawai/PegawaiSyncService.php` | Orkestrasi upsert OPD→Pangkat→Jabatan→Pegawai + `preview()` |
| Controller | `app/Controllers/AdminKab/PegawaiController.php` (`sync`, `runSync`) | Halaman pratinjau + tombol terapkan |
| View | `app/Views/adminKabupaten/pegawai/sync.php` | UI |

Kolom pemetaan: `simpeg_id VARCHAR(50)` ditambahkan ke tabel **opd, pangkat, jabatan**
(migration `2026-06-13-000004_AddSimpegIdToMasters`). Pegawai dipetakan via **NIP**.

---

## 2. Cara mengaktifkan

Isi `.env` (lihat blok *INTEGRASI PEGAWAI*):

```
simpeg.enabled = true
simpeg.baseUrl = https://<host>/api/presensi
simpeg.token   = <api-token minimal 32 karakter>
# pegawaiSync.httpTimeout = 30
```

Lalu buka **Master Data → (tombol) Sinkron SIMPEG → Pratinjau Data → Terapkan Sinkron Penuh**.

---

## 3. Cara kerja sinkron (Full Sync)

Sesuai strategi di dokumentasi API:

1. `GET /opd`     → upsert tabel `opd`     (map `id_opd` → `opd.id` via `simpeg_id`)
2. `GET /pangkat` → upsert tabel `pangkat` (map `id_pangkat` mis. `pns-7`)
3. `GET /jabatan` → upsert tabel `jabatan` (map `id_jabatan` mis. `struktural-STR-001`, `eselon_id`→`eselon`)
4. `GET /pegawai?per_page=500&page=N` (loop semua halaman) → upsert `pegawai` **by NIP**,
   menautkan `opd_id`/`jabatan_id`/`pangkat_id` dari hasil langkah 1–3.

Sifat:
- **Idempoten** — aman dijalankan berulang (cek `simpeg_id`/NIP; tidak menduplikasi).
- **Adopsi data lama** — master lokal lama tanpa `simpeg_id` dicocokkan via nama lalu
  `simpeg_id`‑nya diisi (sekali), sehingga tidak membuat duplikat.
- **Pegawai baru** dibuat dengan `password = hash(NIP)`, `level` dari feed (default `USER`),
  `first_time = 1`. Password asli **tidak pernah** dikirim API (sesuai dokumentasi).

Pemetaan field utama (feed `/pegawai` → lokal `pegawai`):
`nama_pegawai→nama_pegawai`, `nip_pegawai→nip_pegawai`, `id_opd→opd_id`(via map),
`id_jabatan→jabatan_id`(via map), `id_pangkat→pangkat_id`(via map),
`status_pegawai→status`, `url_foto_pegawai→url_foto_pegawai`.

> Catatan: feed `/pegawai` **tidak** memuat `nama_opd`; nama OPD diambil dari master `/opd`.

---

## 4. Status pengujian

- **Live ke API SIMPEG Pringsewu sudah berhasil** (Pratinjau): OPD 43, Pangkat 35,
  Jabatan 1939, Pegawai 6807 tertarik dengan benar; sampel NIP tampil.
- Logika service (upsert + pemetaan ID + idempotensi) diuji: run‑1 membuat semua entitas,
  run‑2 hanya update (0 duplikat), pegawai tertaut ke master lewat `simpeg_id`.
- **Sinkron penuh** (tombol *Terapkan*) belum dijalankan ke DB (impor 6807 pegawai)
  — tinggal klik oleh super admin.

### Catatan SSL / CA bundle (PENTING)

Server `simpeg.pringsewukab.go.id` mengirim **chain tidak lengkap** (hanya cert leaf,
tanpa intermediate Let's Encrypt **R12**). `curl` sistem modern menambalnya otomatis,
tetapi **PHP cURL tidak** → error `SSL certificate problem: unable to get local issuer certificate`.

Solusi (sudah diterapkan): bundle CA gabungan **`writable/simpeg_cacert.pem`**
(= root Mozilla `cacert.pem` + intermediate `R12`), dipakai otomatis oleh `SimpegClient`.
- Regenerasi bila perlu:
  ```
  curl -o writable/cacert.pem https://curl.se/ca/cacert.pem
  curl -o r12.pem https://letsencrypt.org/certs/2024/r12.pem
  cp writable/cacert.pem writable/simpeg_cacert.pem && cat r12.pem >> writable/simpeg_cacert.pem
  ```
- `writable/` biasanya di‑gitignore → **salin `simpeg_cacert.pem` ke server** saat deploy,
  atau set `simpeg.caBundle` di `.env` ke path bundle yang valid.
- Darurat (TIDAK disarankan, dev saja): `simpeg.verifySsl = false` di `.env`.

Prioritas pemilihan CA di `SimpegClient::sslVerify()`:
`simpeg.verifySsl=false` → `simpeg.caBundle` → `writable/simpeg_cacert.pem` →
`writable/cacert.pem` → default php.ini.

---

## 5. Delta sync (opsional, nanti)

`PegawaiSyncService::apply($updatedSince)` & `SimpegClient::allPegawai($updatedSince)`
sudah mendukung parameter `updated_since` (format `Y-m-d H:i:s`) untuk menarik hanya
pegawai yang berubah — berguna untuk penjadwalan berkala. Saat ini UI memakai full sync.

---

## 6. SIKASN

Belum diimplementasikan pada alur ini (fokus SIMPEG sesuai kebutuhan). Kelas generik lama
(`EmployeeDirectoryProvider`, `AbstractDirectoryProvider`, `SikasnProvider`, `ProviderFactory`)
masih ada tetapi tidak dipakai oleh sync SIMPEG; bisa dihapus atau dijadikan basis bila
SIKASN nanti diintegrasikan.

# AKSARA+ — Kinerja Prioritas (IKP) & Pemilik Kinerja sampai Pelaksana

Catatan serah terima untuk tim pengembang AKSARA. Cabang: `fitur/ikp-kinerja-pelaksana`, di atas `63a8275`.

## Latar belakang

IKU Eselon II umumnya indikator tahunan yang terbit terlambat (PDRB, indeks, dsb.). Akibatnya Bupati tidak bisa memantau dan
menilai kinerja Kepala OPD setiap bulan, dan penilaian SKP bulanan menjadi prosedural. Perubahan ini menambah dua hal:

1. **Indikator Kinerja Prioritas (IKP)**: KPI terukur dari Program Unggulan Bupati, program prioritas, penugasan khusus dan
   penugasan tambahan, dengan target 5 tahun → tahunan → **bulanan**, realisasi bulanan, dan rekap triwulan yang dihitung
   (bukan diisi). Fitur prototipe "Prioritas" (Laravel) dipindah ke sini dengan gaya AKSARA.
2. **Pemilik Kinerja**: setiap simpul pohon kinerja (Es III, Es IV, pelaksana) diberi pemilik (pegawai, per tahun), dan
   setiap indikatornya satuan + target tahunan. Jenjang pelaksana sudah ada di AKSARA sejak migrasi 2026-07-27, tetapi belum
   terikat ke orang. Ini menjadi dasar SKP pegawai (aplikasi pendamping eKin menarik datanya lewat API).

## Pemasangan

| Lingkungan | Langkah |
|---|---|
| Dengan CLI | `php spark migrate` lalu `php spark db:seed IkpReferensiSeeder` |
| Tanpa CLI (phpMyAdmin) | jalankan `db/update_2026-09-26_ikp_kinerja.sql`, lalu `db/update_2026-09-26_ikp_referensi.sql` |

Keduanya idempoten. Untuk API eKin tambahkan `EKIN_API_TOKEN` di `.env` (token terpisah dari `API_TOKEN`).
Impor data prototipe Prioritas (opsional): `php spark ikp:impor-prioritas --db=/path/database.sqlite [--kecuali=23,20,11] [--kering]`.

## Yang ditambahkan

- **Tabel** (semua baru, `utf8mb4_general_ci`): `ikp_program_unggulan`, `ikp_sasaran_pembangunan`, `ikp_buku_saku`, `ikp`,
  `ikp_target_tahunan`, `ikp_bulanan`, `ikp_inovasi`, `cascading_pemilik`, `cascading_indikator_target`.
- **Izin**: `ikp_opd.*`, `ikp_kab.*`, `ikp_bupati_monitoring.view`, `pemilik_kinerja.view|update|delete`
  (`IkpPermissionSeeder`, pola `RoleBupatiSeeder`).
- **Halaman OPD**: `adminopd/ikp` (daftar, form, target, breakdown, realisasi, rekap, cetak), `adminopd/ikp/inovasi`,
  `adminopd/ikp/lampiran-pk` (PDF Folio: PK + Lampiran I–V), `adminopd/pemilik-kinerja`.
- **Halaman Kabupaten/Bupati**: `adminkab/ikp` (+ `opd/{id}`, `program-unggulan`, `cetak`), `adminkab/pemilik-kinerja` (baca),
  `bupati/ikp` (+ `opd/{id}`), dan satu kartu pintasan di `bupati/dashboard`.
- **API** `api/ekin/{opd, pegawai, pegawai/{id}/kinerja, opd/{id}/ikp}`: GET saja, token terpisah, tanpa kolom pribadi.
  Didokumentasikan di `API_DOCUMENTATION.md` dan `public/openapi.json`.
- **Logika**: `app/Helpers/ikp_helper.php` dan `app/Services/IkpRekapService.php` (satu sumber angka untuk semua halaman, PDF
  dan API). Capaian memakai `calculateCapaianTotalPercentage()` apa adanya (helper lama tidak diubah).
- **Tes**: `tests/unit/IkpRekapTest.php` (28 kasus).

## Berkas lama yang disentuh (semua penambahan)

`app/Config/Routes.php` (satu blok di ujung berkas), `app/Filters/ModulePermissionFilter.php` (peta `ikp`, `pemilik-kinerja`),
`app/Filters/ApiTokenFilter.php` (argumen konsumen `api-token:ekin`; tanpa argumen perilaku lama tidak berubah),
`app/Views/templates/admin_menu.php` (blok menu bertanda AKSARA+), `app/Views/bupati/dashboard.php` (kartu pintasan),
`app/Commands/JagaAsap.php` (halaman IKP), `ALUR_FITUR_DAN_ROUTE.md` (§8.10, §9.10, §9.11), `API_DOCUMENTATION.md`, `public/openapi.json`.

## Keputusan desain penting

- IKP melekat ke **OPD × periode RPJMD** (bukan per dokumen PK). Hapus IKP = *soft delete* (`dihapus_pada`) karena aplikasi
  lain merujuk `ikp.id`.
- `ikp_bulanan` **tidak** ber-FK ke `iku_target`/tahunan: `IkuModel::updateIndikator()` menghapus-lalu-menyisipkan `iku_target`,
  sehingga FK CASCADE akan menghapus data bulanan diam-diam.
- Metode perhitungan memakai kosakata monev: `sum | trend_naik | trend_turun | trend_flat`.
- Kategori IKP selalu lewat `?kategori=` karena kata `tambah` di path dibaca modperm sebagai aksi tulis.
- Scope OPD dari sesi untuk admin OPD; admin kabupaten memakai `?opd_id=` yang divalidasi (bukan `canAccessOpd()`, karena akun
  admin_kab punya `opd_id`).
- Pemilik Es II tidak disimpan ulang; mengikuti PK JPT/Camat `pihak_1`.

## Temuan di kode/data lama (belum diubah)

- `tests/unit/CapaianTotalTest.php`: 2 kasus sudah gagal di `63a8275` (kebijakan `not_evaluable → 0%` 16 Sep belum diselaraskan).
- `AdminOpd\PkController::cetak()` tidak memeriksa kepemilikan OPD.
- `PegawaiSyncService::syncJabatan()` membaca `nama_eselon`, padahal feed mengirim `eselon_id`, sehingga `jabatan.eselon` kosong
  99,6%. `pegawai.atasan_id` basi dan tidak dipakai. 42 pegawai BKPSDM tercatat di `opd_id = 210` yang tidak ada.
- mPDF memerlukan folder tmp yang bisa ditulis PHP (`adminkab/target/cetak` 500 bila tidak).
- **`server.sql` di repo publik memuat data produksi** (NIP dan hash kata sandi pegawai). Sebaiknya dihapus dari repo beserta
  riwayat git-nya.

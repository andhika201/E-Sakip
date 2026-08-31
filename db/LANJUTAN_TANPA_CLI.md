# Lanjutan untuk server — tanpa perlu `php spark`

## Apa yang terjadi

Empat kueri pemeriksa Anda menghasilkan:

| Kueri | Hasil Anda | Seharusnya |
|---|---|---|
| `cascading_sasaran_opd WHERE iku_indikator_id IS NULL` | **48** | 0 |
| `lakip mode='opd' GROUP BY source_type` | **iku=8, renstra=112** | iku=118, renstra=2 |
| `lakip WHERE mode IS NULL` | 0 | 0 ✓ |
| `lakip_yatim_arsip` | 125 | 125 ✓ |

Dua yang gagal, dua yang lulus — dan polanya menunjuk satu sebab tunggal:
**seluruh berkas SQL berhasil dijalankan, tetapi dua langkah `php spark` tidak.**

Saya buktikan dengan membangun ulang salinan dari `backup-29 agustus.sql`,
menjalankan HANYA berkas SQL-nya (melewati kedua perintah CLI), dan hasilnya
persis sama dengan server Anda: `48`, `iku=8`, `renstra=112`, `yatim=0`,
`arsip=125`.

Angka **8** itu sendiri sudah bercerita: delapan baris itu milik Dinas
Koperindag — satu-satunya OPD yang revisi IKU-nya memang sudah ada di dump.
OPD lain tidak punya revisi yang memayungi tahun laporan, jadi migrasi LAKIP
melewatinya.

Penyebab paling lazim: server tidak menyediakan akses shell, sehingga
`php spark` tidak bisa dijalankan. **Tidak ada yang rusak** — dua langkah
saja yang belum sempat berjalan.

---

## Yang perlu dijalankan sekarang

Empat berkas, berurutan. Semuanya SQL murni, tidak menyentuh
`information_schema`, dan idempoten.

### 1. Cadangkan dulu

    mysqldump -u USER -p NAMA_DB > backup_sebelum_lanjutan.sql

### 2. Pengganti sync empat kecamatan

    db/server/2026-08-30_E_sync_kecamatan_TANPA_CLI.sql

Menyalin sasaran, indikator, dan target Renstra ke IKU untuk Kec. Pringsewu,
Adiluwih, Banyumas, dan Pagelaran (periode 2025-2029 saja) — lengkap dengan
silsilahnya. Periode nyasar Banyumas 2026-2030 dan Pringsewu 2029-2033
sengaja TIDAK disentuh.

Di akhir skrip, kolom `masih_belum_di_iku` harus **0** pada keempat baris.

### 3. Pengganti `iku:sahkan-massal`

    db/server/2026-08-30_D_sahkan_massal_TANPA_CLI.sql

Membekukan "Kondisi Awal IKU 2025-2029" (revisi bernomor 0, status berlaku)
untuk 36 OPD yang belum punya revisi, beserta salinan beku sasaran, indikator,
target, dan programnya.

Ini bukan mengarang dokumen bertanda tangan — isinya potret IKU yang sudah
ada, yang sendirinya berasal dari Renstra lewat Sync. Tanpa revisi yang
memayungi tahun laporan, LAKIP tidak punya rujukan IKU dan selamanya jatuh ke
Renstra.

Di akhir skrip: `revisi_kondisi_awal_TANPA_isi` harus **0**, dan
`OPD ber-LAKIP yang masih TANPA payung 2025` harus **0**.

### 4. Jangkar ulang cascading (ULANGI)

    db/server/2026-08-27_C_jangkar_cascading_isi.sql

Sudah pernah dijalankan, tetapi waktu itu belum ada isi IKU keempat kecamatan
untuk dicocokkan. Sekarang ada. Aman diulang.

### 5. Migrasi LAKIP ke IKU (ULANGI)

    db/migrasi_2026-08-29_lakip_ke_iku.sql

Sama halnya: waktu itu belum ada revisi yang memayungi, jadi hanya 8 baris
yang pindah. Sekarang 118. Aman diulang.

---

## Periksa lagi

Jalankan ulang keempat kueri yang tadi. Sesudah langkah di atas, hasilnya:

    SELECT COUNT(*) FROM cascading_sasaran_opd WHERE iku_indikator_id IS NULL;
    -- 0

    SELECT source_type, COUNT(*) FROM lakip WHERE mode='opd' GROUP BY source_type;
    -- iku = 118, renstra = 2

    SELECT COUNT(*) FROM lakip WHERE mode IS NULL;
    -- 0

    SELECT COUNT(*) FROM lakip_yatim_arsip;
    -- 125

---

## Sudah diuji, bukan diperkirakan

Keempat langkah di atas dijalankan pada salinan yang mereproduksi keadaan
server Anda persis, lalu hasilnya dibandingkan dengan basis data lokal:

| | Lokal | Salinan server |
|---|---|---|
| Tabel | 99 | 99 |
| Revisi IKU | 38 | 38 |
| Arsip indikator revisi | 134 | 134 |
| Arsip target revisi | 670 | 670 |
| `iku_indikator` | 141 | 141 |
| Cascading berjangkar | 1413 | 1413 |
| LAKIP bersumber IKU | 118 | 118 |

Identik seluruhnya — termasuk isi arsip revisi, bagian yang paling mudah
meleset bila dikerjakan dengan SQL alih-alih lewat model.

---

## Catatan

**Kode barunya tetap harus ter-deploy.** SQL ini hanya menyiapkan data. Kalau
kode lama masih berjalan di server, dua tambalan keamanan (hapus & ubah status
LAKIP lintas OPD) belum aktif, dan panel Pengesahan belum muncul.

**Tiga indikator Kec. Pringsewu tanpa silsilah** (`Indeks Kepuasan Masyarakat`,
`Prosentase Pemberdayaan Masyarakat Desa`, `NILAI SAKIP`) akan tetap terlihat
di pemeriksaan. Itu bawaan lama sejak 13 Agustus, bukan akibat skrip ini —
lokal pun punya tiga yang sama. Tidak mengganggu.

**Untuk seterusnya**: kalau server memang tidak punya akses shell, perintah
`php spark` lain di panduan (misalnya `casc:akar-check`) juga tidak bisa
dijalankan di sana. Pemeriksaannya cukup lewat empat kueri di atas.

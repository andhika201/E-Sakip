# Laporan Audit Data e-SAKIP / AKSARA Pringsewu

**Basis data:** `esakip` (lingkungan kerja lokal)
**Waktu potret:** 10 September 2026, 11:20 WIB
**Sifat:** audit **baca-saja** — tidak ada satu baris pun yang diubah, dihapus, atau ditambah selama pemeriksaan ini.

> **Catatan penting soal waktu.** Basis data ini sedang dipakai dan diisi. Selama analisis hari ini saja, jumlah unit anggaran yang melampaui pagu naik dari 14 menjadi 22. Semua angka di bawah adalah potret pada jam yang tertulis di atas, bukan angka tetap.

---

## 1. Ringkasan Eksekutif

**48 OPD** masuk lingkup penilaian (dari 51 baris pada tabel `opd`; tiga dikecualikan sistem: id 1, 46, 209).

| Keadaan | Jumlah OPD |
|---|---|
| Sudah mengisi seluruh modul inti (Renstra, IKU, RKT, PK, Renaksi, MONEV, LAKIP) | **31** |
| Terisi sebagian | **6** |
| Sama sekali kosong di semua modul | **11** |

Tiga hal yang paling perlu ditindaklanjuti:

1. **138 baris RKT tahun 2026 menunjuk ke indikator Renstra yang sudah tidak ada** — tersebar di 25 OPD, dan 8 OPD di antaranya sudah menandai RKT-nya **selesai**. Di layar, baris-baris ini tampil tanpa nama indikator.
2. **22 unit anggaran melampaui pagu, total kelebihan Rp 59.036.002.845** — terkonsentrasi di Sekretariat Daerah, Dinas Perhubungan, dan Satpol PP.
3. **35 dari 48 versi Renstra berstatus `published` tidak punya arsip isi**, begitu pula satu-satunya versi RPJMD. Versi ini tidak bisa dipakai sebagai pembanding historis.

---

## 2. Kelengkapan per OPD

Angka = jumlah baris data. Kolom **Status** membandingkan terhadap 7 modul inti (Renstra, IKU, RKT, PK, Renaksi, MONEV, LAKIP): `lengkap` = ketujuhnya terisi, `n kurang` = n modul masih nol, `KOSONG` = ketujuhnya nol.

| ID | OPD | Renstra | IKU | RKT | PK | Renaksi | MONEV | Anggaran | LAKIP | Status |
|---:|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| 2 | Sekretariat Daerah | 1 | 2 | 8 | 22 | 23 | 75 | 27 | 1 | lengkap |
| 3 | Sekretariat DPRD | 1 | 1 | 4 | 5 | 9 | 20 | 21 | 2 | lengkap |
| 4 | Inspektorat | 4 | 4 | 6 | 7 | 49 | 98 | 49 | 4 | lengkap |
| 5 | Badan Pengelolaan Keuangan Dan Aset Daerah | 1 | 1 | 3 | 21 | 1 | 5 | 3 | 1 | lengkap |
| 6 | Badan Pendapatan Daerah | 1 | 1 | 2 | 9 | 14 | 58 | 43 | 2 | lengkap |
| 7 | Badan Perencanaan Pembangunan Riset Dan Inovasi Daerah | 3 | 3 | 5 | 9 | 13 | 159 | 5 | 6 | lengkap |
| 8 | BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA | 1 | 1 | 5 | 6 | 12 | 56 | 32 | 1 | lengkap |
| 9 | Satuan Polisi Pamong Praja | 1 | 1 | 9 | 14 | 24 | 36 | 41 | 1 | lengkap |
| 10 | Dinas Pendidikan Dan Kebudayaan | 2 | 2 | 13 | 14 | 73 | 6 | 3 | 5 | lengkap |
| 11 | Dinas Kesehatan | 3 | 3 | 10 | 8 | 38 | 213 | 48 | 14 | lengkap |
| 12 | Dinas Sosial | 1 | 3 | 4 | 5 | 15 | 50 | 4 | 0 | 1 kurang |
| 13 | DINAS PEMBERDAYAAN PEREMPUAN, PERLINDUNGAN ANAK, PENGENDALIAN PENDUDUK DAN KELUARGA BENCANA | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 14 | Dinas Kependudukan Dan Pencatatan Sipil | 4 | 4 | 15 | 7 | 39 | 48 | 10 | 8 | lengkap |
| 15 | Dinas Kepemudaan, Olahraga Dan Pariwisata | 6 | 12 | 15 | 6 | 15 | 46 | 17 | 4 | lengkap |
| 16 | Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian | 7 | 7 | 16 | 10 | 9 | 1 | 0 | 0 | 1 kurang |
| 17 | Dinas Perhubungan | 1 | 2 | 2 | 13 | 18 | 21 | 32 | 4 | lengkap |
| 18 | Dinas Pekerjaan Umum Dan Perumahan Rakyat | 3 | 3 | 10 | 10 | 19 | 11 | 16 | 8 | lengkap |
| 19 | Dinas Perikanan | 2 | 2 | 8 | 7 | 8 | 25 | 0 | 0 | 1 kurang |
| 20 | Dinas Komunikasi Dan Informatika | 1 | 1 | 7 | 6 | 9 | 9 | 0 | 1 | lengkap |
| 21 | Dinas Pertanian | 2 | 2 | 6 | 8 | 4 | 8 | 12 | 3 | lengkap |
| 22 | Dinas Pemberdayaan Masyarakat Dan Pekon | 1 | 1 | 15 | 7 | 6 | 6 | 7 | 2 | lengkap |
| 23 | Dinas Lingkungan Hidup | 1 | 1 | 6 | 11 | 22 | 94 | 44 | 6 | lengkap |
| 24 | Dinas Ketahanan Pangan | 4 | 4 | 5 | 8 | 5 | 4 | 0 | 2 | lengkap |
| 25 | Dinas Penanaman Modal Dan Pelayanan Terpadu Satu Pintu | 3 | 3 | 10 | 3 | 5 | 36 | 22 | 5 | lengkap |
| 26 | Dinas Perpustakaan Dan Kearsipan | 2 | 2 | 4 | 5 | 9 | 28 | 16 | 2 | lengkap |
| 27 | Dinas Tenaga Kerja Dan Transmigrasi | 1 | 1 | 5 | 5 | 7 | 22 | 22 | 1 | lengkap |
| 28 | Badan Kesatuan Bangsa Dan Politik | 2 | 2 | 6 | 6 | 13 | 118 | 31 | 2 | lengkap |
| 29 | Badan Penanggulangan Bencana Daerah | 2 | 2 | 2 | 6 | 10 | 19 | 19 | 2 | lengkap |
| 30 | RUMAH SAKIT UMUM DAERAH PRINGSEWU | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 31 | Kecamatan Pringsewu | 5 | 7 | 16 | 17 | 5 | 7 | 0 | 1 | lengkap |
| 32 | KECAMATAN GADING REJO | 4 | 4 | 6 | 8 | 7 | 7 | 11 | 12 | lengkap |
| 33 | Kecamatan Ambarawa | 3 | 3 | 6 | 3 | 5 | 0 | 0 | 0 | 2 kurang |
| 34 | Kecamatan Sukoharjo | 1 | 1 | 6 | 8 | 18 | 42 | 29 | 6 | lengkap |
| 35 | Kecamatan Adiluwih | 3 | 3 | 9 | 9 | 5 | 1 | 14 | 2 | lengkap |
| 36 | Kecamatan Banyumas | 5 | 4 | 12 | 2 | 0 | 0 | 0 | 0 | 3 kurang |
| 37 | Kecamatan Pagelaran | 6 | 6 | 13 | 9 | 7 | 25 | 11 | 7 | lengkap |
| 38 | Kecamatan Pardasuka | 3 | 3 | 12 | 12 | 8 | 28 | 0 | 5 | lengkap |
| 39 | Kecamatan Pagelaran Utara | 3 | 6 | 6 | 8 | 6 | 16 | 0 | 0 | 1 kurang |
| 40 | UPT PENGEMBANGAN BUDIDAYA IKAN DINAS PERIKANAN | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 41 | Kelurahan Pajaresuk | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 42 | Kelurahan Pringsewu Barat | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 43 | Kelurahan Pringsewu Selatan | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 44 | Kelurahan Pringsewu Timur | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 45 | Kelurahan Pringsewu Utara | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 210 | Badan Kepegawaian Dan Pengembangan Sumberdaya Manusia | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 211 | Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk Dan Keluarga Berencana | 4 | 4 | 18 | 9 | 17 | 22 | 25 | 8 | lengkap |
| 212 | Kabupaten Pringsewu | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |
| 213 | Kecamatan Gadingrejo | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | KOSONG |

### 2.1 Sebelas OPD yang sama sekali kosong

| ID | OPD | Keterangan |
|---:|---|---|
| 13 | Dinas PPPA, Pengendalian Penduduk dan Keluarga **Bencana** | Kembaran dari id 211 yang aktif. Namanya sendiri salah ketik: seharusnya "Berencana". |
| 30 | RSUD Pringsewu | **Benar-benar belum mengisi apa pun.** |
| 40 | UPT Pengembangan Budidaya Ikan Dinas Perikanan | **Benar-benar belum mengisi apa pun.** |
| 41 | Kelurahan Pajaresuk | **Benar-benar belum mengisi apa pun.** |
| 42 | Kelurahan Pringsewu Barat | **Benar-benar belum mengisi apa pun.** |
| 43 | Kelurahan Pringsewu Selatan | **Benar-benar belum mengisi apa pun.** |
| 44 | Kelurahan Pringsewu Timur | **Benar-benar belum mengisi apa pun.** |
| 45 | Kelurahan Pringsewu Utara | **Benar-benar belum mengisi apa pun.** |
| 210 | BKPSDM | Kembaran dari id 8. Pegawainya ada di sini (43 orang), data SAKIP-nya di id 8. |
| 212 | Kabupaten Pringsewu | Entitas tingkat kabupaten, memang tidak mengisi modul OPD. |
| 213 | Kecamatan Gadingrejo | Kembaran dari id 32. Pegawainya ada di sini (24 orang), data SAKIP-nya di id 32. |

Jadi yang benar-benar perlu ditagih pengisiannya ada **7**: RSUD, UPT Budidaya Ikan, dan lima kelurahan.

### 2.2 Enam OPD terisi sebagian

| ID | OPD | Yang belum ada |
|---:|---|---|
| 12 | Dinas Sosial | LAKIP |
| 16 | Dinas Koperasi, UKM, Perdagangan dan Perindustrian | LAKIP; MONEV baru 1 baris untuk 9 rencana aksi |
| 19 | Dinas Perikanan | LAKIP |
| 33 | Kecamatan Ambarawa | MONEV, LAKIP |
| 36 | Kecamatan Banyumas | Rencana Aksi, MONEV, LAKIP |
| 39 | Kecamatan Pagelaran Utara | LAKIP |

---

## 3. Keadaan per Menu

### 3.1 RPJMD (tingkat kabupaten)

| Unsur | Jumlah |
|---|---:|
| Visi | 1 |
| Misi | 5 |
| Tujuan | 5 |
| Sasaran | 10 |
| Indikator tujuan | 5 |
| Indikator sasaran | 15 |
| Target tahunan | 75 |
| Versi (`published`) | 1, periode 2025-2029 |

Isinya utuh: 15 indikator sasaran x 5 tahun = 75 target, tidak ada yang bolong.

**Kekurangan:** satu-satunya versi `published` itu **tidak punya arsip isi** (tabel `rpjmd_versi_*` kosong untuk version_id tersebut). Artinya kalau RPJMD nanti direvisi, tidak ada rekaman "seperti apa bunyinya sebelum direvisi".

### 3.2 Renstra

| Hal | Angka |
|---|---:|
| OPD yang punya Renstra | 38 dari 48 |
| Sasaran | 100 (90 `selesai`, 10 `draft`) |
| Sasaran tanpa indikator | 0 |
| Indikator tanpa target | 0 |
| Versi `published` | 48 |
| Versi `cancelled` | 2 |

Periode yang tercatat:

| Periode | OPD | Sasaran |
|---|---:|---:|
| 2025-2029 | 38 | 98 |
| 2026-2030 | 1 | 1 |
| 2029-2033 | 1 | 1 |

Dua periode terakhir masing-masing hanya berisi 1 sasaran — kemungkinan besar sisa percobaan, bukan Renstra sungguhan.

**Kekurangan:** **35 dari 48 versi `published` tidak punya arsip isi.** Hanya 13 versi yang benar-benar menyimpan salinan Renstra saat diterbitkan. Versi yang kosong arsipnya tetap tampil di daftar versi, tetapi tidak bisa dibuka isinya dan tidak bisa dipakai LAKIP sebagai pembanding.

### 3.3 IKU

| Hal | Angka |
|---|---:|
| OPD yang punya IKU | 37 dari 48 |
| Sasaran IKU | 122 |
| Indikator tanpa target | 0 |
| Versi OPD `published` | 35 (34 OPD) |
| Versi kabupaten `published` | 1 |

IKU adalah modul paling rapi: tidak ada indikator menggantung, tidak ada target bolong. Perhatikan bahwa 37 OPD punya isi IKU tetapi hanya 34 yang punya versi resmi — ada 3 OPD yang datanya hidup tanpa pernah diterbitkan sebagai versi.

### 3.4 RKT

| Hal | Angka |
|---|---:|
| Baris RKT | 305 |
| OPD | 37 |
| Tahun | seluruhnya 2026 |
| Status | `draft` dan `selesai` |
| **Baris yang indikator Renstra-nya sudah hilang** | **138 (45%)** |

Ini temuan terbesar dalam audit ini dan dibahas tersendiri di bagian 4.1.

### 3.5 Perjanjian Kinerja (PK)

325 dokumen PK. Sebarannya:

| Tahun | Jenis | Dokumen | OPD |
|---:|---|---:|---:|
| 2026 | administrator | 129 | 37 |
| 2026 | pengawas | 140 | 35 |
| 2026 | jpt | 35 | 29 |
| 2026 | camat | 9 | 9 |
| 2026 | bupati | 2 | 2 |
| 2025 | administrator | 3 | 3 |
| 2025 | jpt | 2 | 2 |
| 2025 | pengawas | 2 | 1 |
| 2024 | administrator | 1 | 1 |
| 2024 | pengawas | 2 | 2 |

Tahun 2024 dan 2025 praktis kosong (10 dokumen); seluruh isi nyata ada di 2026.

Penandatangan:

| Pemeriksaan | Hasil |
|---|---:|
| Pihak ke-1 kosong | 0 |
| Pihak ke-2 kosong | **2** |
| Pihak ke-1 menunjuk pegawai yang sudah dihapus | **1** |
| Pihak ke-2 menunjuk pegawai yang sudah dihapus | 0 |
| Pihak ke-1 pegawai dari OPD lain | **33** |
| Pihak ke-2 pegawai dari OPD lain | **74** |

Angka 33 dan 74 itu sebagian besar **bukan kesalahan**, melainkan akibat OPD kembar (bagian 4.3): PK milik BKPSDM id 8 memakai pejabat yang terdaftar di BKPSDM id 210, dan itu memang orang yang sama.

### 3.6 Target Rencana Aksi

| Hal | Angka |
|---|---:|
| Rencana aksi | 555 |
| Rencana aksi tanpa sub-rencana | **57** |
| Sub-rencana aksi | 1.782 |
| Sub-rencana yang belum ada MONEV-nya | **671 (38%)** |

### 3.7 MONEV

| Hal | Angka |
|---|---:|
| Baris MONEV capaian | 1.420 |
| **Baris MONEV yang sub-rencananya sudah dihapus** | **309** |
| Baris MONEV anggaran | 614 |
| Baris anggaran warisan (belum dirinci per unit) | 0 |
| **Unit anggaran melampaui pagu** | **22** |
| **Total kelebihan** | **Rp 59.036.002.845** |

Kabar baiknya: baris anggaran warisan sudah nol — seluruh realisasi anggaran kini sudah terhubung ke unit (program/kegiatan/sub-kegiatan) yang jelas.

### 3.8 LAKIP

| Tahun | Tingkat | Status | Baris | OPD |
|---:|---|---|---:|---:|
| 2025 | kabupaten | proses | 1 | 1 |
| 2025 | kabupaten | selesai | 15 | 1 |
| 2025 | OPD | draft | 18 | 8 |
| 2025 | OPD | selesai | 72 | 23 |
| 2026 | kabupaten | proses | 15 | 1 |
| 2026 | OPD | draft | 29 | 11 |
| 2026 | OPD | selesai | 9 | 5 |

**Kekurangan:** LAKIP adalah modul yang paling tertinggal. Untuk 2026, baru **5 OPD** yang menyelesaikan LAKIP-nya dan 11 masih draft — hanya **15 OPD** yang punya baris LAKIP 2026, sehingga **33 OPD belum menyentuhnya sama sekali**. Di tingkat kabupaten, LAKIP 2026 masih seluruhnya berstatus `proses`.

Sebagai pembanding, LAKIP 2025 dikerjakan oleh **31 OPD** (23 selesai, 8 draft). Jadi cakupan LAKIP turun separuh dari 2025 ke 2026 — wajar bila tahun berjalan belum jatuh tempo, tetapi selisihnya perlu dipastikan memang karena jadwal, bukan karena terlewat.

### 3.9 Cascading

| Hal | Angka |
|---|---:|
| Baris sasaran cascading | 1.494 |
| Baris indikator cascading | 1.603 |
| OPD | 37 |
| Bersumber dari IKU | 1.494 (100%) |
| Bersumber dari Renstra | 0 |
| Jangkar IKU menggantung | 0 |
| Jangkar induk (`parent_id`) menggantung | 0 |
| **Tanpa jangkar Renstra** | **49** |

Cascading sepenuhnya sudah mengambil dari IKU — sesuai yang diharapkan. Yang 49 baris tanpa jangkar Renstra terpusat di empat OPD: Dinas Sosial (21), Kecamatan Pringsewu (17), Kecamatan Pagelaran Utara (6), Dinas Koperasi (5).

---

## 4. Data yang Berantakan

Diurutkan dari yang paling berdampak.

### 4.1 138 baris RKT 2026 kehilangan indikator Renstra — PERLU TINDAKAN

`rkt.indikator_id` menunjuk ke `renstra_indikator_sasaran.id`, dan kode menggabungkannya dengan `LEFT JOIN` (lihat `app/Models/RktModel.php:311`). Karena `LEFT JOIN`, baris tetap tampil — hanya kolom indikatornya yang kosong. Tidak ada pesan galat, tidak ada tanda apa pun bahwa datanya rusak.

**138 dari 305 baris RKT (45%) mengalami ini.** Semuanya tahun 2026, tersebar di 25 OPD:

| OPD | Yatim / Total | Status |
|---|---:|---|
| Kecamatan Pringsewu | 13 / 16 | **selesai** |
| Dinas Pemberdayaan Masyarakat dan Pekon | 12 / 15 | draft |
| Dinas PPPA & KB (id 211) | 11 / 18 | draft |
| Dinas Kependudukan dan Pencatatan Sipil | 10 / 15 | **selesai** |
| Dinas Kepemudaan, Olahraga dan Pariwisata | 9 / 15 | draft + selesai |
| Dinas Koperasi, UKM, Perdagangan dan Perindustrian | 9 / 16 | draft |
| Satuan Polisi Pamong Praja | 8 / 9 | draft |
| Kecamatan Adiluwih | 8 / 9 | draft |
| Dinas Pendidikan dan Kebudayaan | 7 / 13 | draft |
| Kecamatan Pagelaran | 7 / 13 | **selesai** |
| Sekretariat Daerah | 6 / 8 | draft |
| Kecamatan Banyumas | 6 / 12 | draft |
| Kecamatan Pardasuka | 6 / 12 | draft |
| Dinas Penanaman Modal dan PTSP | 5 / 10 | draft |
| Dinas Perikanan | 4 / 8 | draft |
| Dinas Sosial | 3 / 4 | draft |
| Kecamatan Pagelaran Utara | 3 / 6 | draft |
| Sekretariat DPRD | 2 / 4 | **selesai** |
| BKPSDM (id 8) | 2 / 5 | **selesai** |
| Dinas Komunikasi dan Informatika | 2 / 7 | draft |
| Inspektorat | 1 / 6 | **selesai** |
| BPKAD | 1 / 3 | **selesai** |
| Bappeda | 1 / 5 | **selesai** |
| Dinas Perhubungan | 1 / 2 | draft |
| Dinas Tenaga Kerja dan Transmigrasi | 1 / 5 | draft |

Delapan OPD sudah menandai RKT-nya **selesai** padahal isinya menggantung. Kecamatan Pringsewu yang paling parah: 13 dari 16 baris, dan sudah dinyatakan selesai.

**Dugaan penyebab:** indikator Renstra dihapus atau diganti (lewat penggantian versi Renstra) setelah RKT disusun, sementara `rkt.indikator_id` tidak ikut disesuaikan. Tidak ada `FOREIGN KEY` pada kolom itu — yang ada hanya pada `rkt.program_id` — sehingga basis data tidak menahan penghapusan tersebut.

**Saran:** jangan dibersihkan otomatis. Yang perlu diputuskan lebih dulu adalah apakah baris-baris ini dipetakan ulang ke indikator Renstra yang berlaku sekarang, atau dikembalikan ke OPD masing-masing untuk disusun ulang. Menghapusnya berarti menghilangkan RKT 8 OPD yang sudah dinyatakan selesai.

### 4.2 22 unit anggaran melampaui pagu — Rp 59,04 miliar

| OPD | Tahun | Unit | Pagu | Realisasi | Kelebihan | Baris |
|---|---:|---|---:|---:|---:|---:|
| Sekretariat Daerah | 2026 | program 119 | 32.497.065.043 | 59.235.534.300 | **26.738.469.257** | 4 |
| Dinas Perhubungan | 2026 | program 70 | 8.868.244.151 | 26.379.470.780 | **17.511.226.629** | 5 |
| Satuan Polisi Pamong Praja | 2026 | program 210 | 16.045.284.428 | 24.286.984.378 | **8.241.699.950** | 2 |
| Satuan Polisi Pamong Praja | 2026 | program 27 | 932.668.500 | 3.273.135.590 | 2.340.467.090 | 5 |
| Inspektorat | 2026 | kegiatan 381 | 858.600.000 | 2.932.650.000 | 2.074.050.000 | 10 |
| Badan Kesatuan Bangsa dan Politik | 2026 | kegiatan 498 | 934.328.000 | 2.013.989.448 | 1.079.661.448 | 3 |
| Dinas Kependudukan dan Capil | 2026 | program 58 | 420.520.000 | 656.328.936 | 235.808.936 | 2 |
| Inspektorat | 2026 | kegiatan 521 | 226.800.000 | 382.500.000 | 155.700.000 | 5 |
| Dinas Pertanian | 2026 | program 107 | 512.689.000 | 662.700.700 | 150.011.700 | 2 |
| Dinas Pertanian | 2026 | program 109 | 67.000.000 | 195.648.822 | 128.648.822 | 3 |
| Dinas Perhubungan | 2026 | subkegiatan 711 | 100.000.000 | 200.000.000 | 100.000.000 | 2 |
| Kecamatan Sukoharjo | 2026 | program 171 | 145.200.000 | 234.000.000 | 88.800.000 | 4 |
| Kecamatan Adiluwih | 2026 | program 174 | 2.006.320.505 | 2.083.077.912 | 76.757.407 | 2 |
| Inspektorat | 2026 | subkegiatan 1309 | 111.370.000 | 170.544.156 | 59.174.156 | 2 |
| Kecamatan Sukoharjo | 2026 | program 169 | 8.040.000 | 32.160.000 | 24.120.000 | 4 |
| Dinas Lingkungan Hidup | 2026 | subkegiatan 596 | 258.950.000 | 273.532.300 | 14.582.300 | 2 |
| Dinas Kependudukan dan Capil | 2026 | program 59 | 7.800.000 | 14.538.600 | 6.738.600 | 4 |
| Kecamatan Sukoharjo | 2026 | program 173 | 1.680.000 | 6.720.000 | 5.040.000 | 4 |
| Dinas Lingkungan Hidup | 2026 | kegiatan 140 | 3.093.500 | 5.500.050 | 2.406.550 | 2 |
| Kecamatan Sukoharjo | 2026 | kegiatan 444 | 1.680.000 | 3.360.000 | 1.680.000 | 2 |
| Kecamatan Sukoharjo | 2026 | program 172 | 240.000 | 960.000 | 720.000 | 4 |
| Kecamatan Sukoharjo | 2026 | kegiatan 443 | 240.000 | 480.000 | 240.000 | 2 |

**Pola yang terbaca dari kolom "Baris":** sebagian besar kasus adalah **angka yang sama diketik berulang pada beberapa indikator**, bukan pemborosan anggaran. Perhatikan Kecamatan Sukoharjo — program 169, 172, 173 dan kegiatan 443, 444 semuanya tepat **400%** atau **200%**, yaitu persis sejumlah barisnya. Operatornya mengisi nilai penuh pada tiap indikator, padahal aturannya tiap indikator hanya mengisi **bagiannya**.

Kasus yang perlu diperiksa manual: Inspektorat kegiatan 381 (10 baris) dan Sekretariat Daerah program 119 — kelebihannya terlalu besar untuk sekadar salah ketik.

Data ini **tidak dibersihkan**. Sesuai aturan, nilai yang sudah telanjur ada dipertahankan, ditandai peringatan merah di layar, dan diperbaiki lewat form oleh operator OPD masing-masing.

### 4.3 Tiga pasang OPD kembar

| Pasangan | Punya data SAKIP | Punya pegawai |
|---|---|---|
| BKPSDM | id **8** (1 renstra, 6 PK) | id **210** (43 pegawai) |
| Kecamatan Gadingrejo | id **32** "KECAMATAN GADING REJO" (4 renstra, 8 PK) | id **213** (24 pegawai) |
| Dinas PPPA & KB | id **211** (4 renstra, 9 PK, 26 pegawai) | id **13** (5 pegawai) |

Akibatnya: PK milik id 8 menandatangani dengan pejabat yang terdaftar di id 210, sehingga pemeriksaan "penandatangan berasal dari OPD lain" menyala padahal orangnya benar. Ini juga yang membuat 11 OPD terhitung "kosong" padahal empat di antaranya sebenarnya hanya kembaran atau entitas kabupaten.

Nama id 13 juga salah ketik: "KELUARGA **BENCANA**", seharusnya "Keluarga Berencana".

### 4.4 Rujukan menggantung pada tabel jembatan PK

| Rujukan | Menggantung | Sebab |
|---|---:|---|
| `pk_program` ke `program_pk` | **272** | 267 karena `program_id = 0`; 5 karena program induknya dihapus |
| `pk_kegiatan` ke `kegiatan_pk` | **68** | kegiatan induk dihapus |
| `pk_subkegiatan` ke `sub_kegiatan_pk` | **34** | sub-kegiatan induk dihapus |
| `rkt_kegiatan` ke `kegiatan_pk` | 0 | — |
| `rkt_subkegiatan` ke `sub_kegiatan_pk` | 0 | — |

Yang **267 baris `program_id = 0`** menyentuh 61 indikator PK. Nilai 0 bukan rujukan yang rusak melainkan rujukan yang tidak pernah diisi — kemungkinan besar peninggalan bentuk form lama. Yang 5, 68, dan 34 baris sisanya adalah rujukan yang benar-benar putus karena induknya dihapus.

Dampaknya: pada halaman PK, baris-baris ini tampil tanpa nama program/kegiatan, dan pagunya tidak terhitung.

### 4.5 309 baris MONEV menggantung

309 dari 1.420 baris MONEV menunjuk `target_sub_rencana_id` yang sudah tidak ada. Angka capaian di dalamnya tidak bisa lagi ditampilkan di halaman mana pun karena tidak punya induk — tetapi tetap terbawa dalam hitungan `COUNT(*)` mentah.

### 4.6 Pagu bernilai nol

| Tabel | Total baris | Pagu nol/kosong |
|---|---:|---:|
| `program_pk` | 441 | **25** |
| `kegiatan_pk` | 1.071 | **125** |
| `sub_kegiatan_pk` | 3.185 | **626** |

**626 sub-kegiatan (20%) belum diisi pagunya.** Untuk unit-unit ini, bar serapan anggaran di MONEV menampilkan "pagu belum diisi" dan persentase serapan tidak bisa dihitung. Kalau realisasinya sudah diisi sementara pagunya nol, unit itu otomatis terbaca melampaui pagu.

### 4.7 Arsip versi kosong

| Modul | Versi `published` | Tanpa arsip isi |
|---|---:|---:|
| Renstra | 48 | **35** |
| RPJMD | 1 | **1** |

Versi tanpa arsip tidak menyimpan salinan isi dokumen pada saat diterbitkan. Konsekuensinya: LAKIP tidak bisa membandingkan capaian terhadap versi tertentu, dan riwayat perubahan dokumen tidak dapat ditelusuri.

### 4.8 Sub-rencana dan rencana aksi yang belum ditindaklanjuti

- **57 rencana aksi** belum punya sub-rencana aksi sama sekali.
- **671 sub-rencana aksi (38%)** belum punya baris MONEV — capaiannya belum pernah dilaporkan.

### 4.9 Temuan lama yang belum tuntas

- `casc:akar-check` melaporkan 109 lolos / 1 gagal: *"Renstra ke IKU menemukan jangkar yang sama — 6 meleset"*. Sudah dipastikan ini bukan akibat perubahan terbaru, tetapi penyebabnya belum ditelusuri.
- Program id 158 dipakai bersama oleh dua kecamatan dengan pagu 0.
- Hanya **2** program yang benar-benar dipakai lintas OPD (id 1 dan id 158). Kesan "10 OPD memakai satu program" sebelumnya berasal dari baris ber-`program_id = 0` yang tergabung jadi satu kelompok.

---

## 5. Yang Sudah Bersih

Supaya proporsional, ini yang terbukti tidak bermasalah:

| Pemeriksaan | Hasil |
|---|---|
| Indikator sasaran RPJMD tanpa target | 0 |
| Sasaran Renstra tanpa indikator | 0 |
| Indikator Renstra tanpa target | 0 |
| Indikator IKU tanpa target | 0 |
| Jangkar IKU pada cascading | 0 menggantung |
| Jangkar induk cascading | 0 menggantung |
| Indikator cascading tanpa sasaran induk | 0 |
| `rkt_kegiatan` / `rkt_subkegiatan` | 0 menggantung |
| Baris anggaran warisan (belum dirinci per unit) | 0 |
| Pihak ke-1 PK kosong | 0 |

---

## 6. Urutan Tindakan yang Disarankan

**Tidak ada satu pun yang dijalankan otomatis.** Semua menunggu keputusan.

| Prioritas | Tindakan | Alasan |
|---|---|---|
| 1 | Putuskan nasib 138 baris RKT yatim | 45% RKT 2026 terdampak; 8 OPD sudah menyatakan selesai |
| 2 | Kembalikan 22 unit lebih pagu ke OPD masing-masing untuk diperbaiki lewat form | Rp 59 miliar; peringatan visual sudah terpasang di layar |
| 3 | Satukan tiga pasang OPD kembar | Menyesatkan laporan kelengkapan dan pemeriksaan penandatangan |
| 4 | Tagih LAKIP 2026 ke 33 OPD | Modul paling tertinggal |
| 5 | Isi pagu 626 sub-kegiatan | Serapan tidak bisa dihitung tanpa pagu |
| 6 | Terbitkan ulang 35 versi Renstra agar arsipnya terisi | Riwayat dokumen tidak bisa ditelusuri |
| 7 | Bersihkan 267 baris `program_id = 0` dan 107 rujukan putus lain | Setelah dipastikan tidak ada isi yang masih dipakai |
| 8 | Tagih pengisian ke RSUD, UPT Budidaya Ikan, dan 5 kelurahan | Belum mengisi apa pun |
| 9 | Telusuri `casc:akar-check` 109/1 | Sisa temuan lama |

---

## 7. Cara Angka Ini Diperoleh

Seluruh angka berasal dari kueri `SELECT` langsung ke basis data `esakip` pada waktu potret di atas. Tidak ada `INSERT`, `UPDATE`, `DELETE`, `TRUNCATE`, atau `DROP` yang dijalankan.

Perintah `php spark monev:shared-check` **tidak** dipakai karena perintah itu menulis ke basis data dan mensyaratkan `--db <salinan>`; angka unit lebih pagu di laporan ini dihitung ulang lewat kueri baca-saja yang setara.

Lingkup OPD memakai `OpdModel::EXCLUDED_OPD_IDS = [1, 46, 209]`, sehingga 48 dari 51 baris `opd` masuk penilaian.

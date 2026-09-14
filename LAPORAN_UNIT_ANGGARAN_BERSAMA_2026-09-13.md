# Laporan Unit Anggaran yang Diampu Beberapa Indikator PK

**Basis data:** `esakip` · **Waktu potret:** 13 September 2026, 22:25 WIB · **Sifat:** baca-saja

## Cara membaca

Satu **unit anggaran** = satu Program, Kegiatan, atau Sub Kegiatan pada satu OPD dan satu tahun PK. Unit dihitung "diampu beberapa indikator" bila **dua indikator PK atau lebih** memilihnya **pada tingkat unit miliknya sendiri**:

| Jenis PK | Tingkat unit anggarannya |
|---|---|
| JPT, Camat, Bupati | Program |
| Administrator | Kegiatan |
| Pengawas | Sub Kegiatan |

Yang **tidak** dihitung: kegiatan milik Administrator yang bernaung di program milik JPT, atau sub kegiatan milik Pengawas di bawah kegiatan milik Administrator. Itu struktur berjenjang PK yang memang seharusnya begitu, bukan pemakaian bersama. Kalau ikut dihitung, angkanya membengkak menjadi 448 unit di 37 OPD dan laporan ini kehilangan makna.

Untuk tiap unit ditampilkan juga realisasi MONEV-nya (jumlah seluruh bagian dari semua indikator pemakai) dibandingkan pagu — karena di sanalah pemakaian bersama berdampak: plafon pagu berlaku pada **jumlah** seluruh bagian.


## 1. Ringkasan

**157 unit** di **22 OPD** (seluruhnya PK tahun 2026):

| Tingkat | Unit | OPD | Indikator terbanyak pada satu unit |
|---|---:|---:|---|
| Program | 30 | 10 | 8 — Sekretariat Daerah, PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUP |
| Kegiatan | 49 | 16 | 24 — Dinas Kesehatan, Penyediaan Layanan Kesehatan untuk UKM dan UKP Ruj |
| Sub Kegiatan | 78 | 15 | 6 — Kecamatan Pringsewu, Peningkatan Partisipasi Masyarakat dalam Forum Mus |

- **21 unit** di antaranya realisasi MONEV-nya sudah **melampaui pagu** (total kelebihan Rp 41.524.776.216).
- **7 unit** pagunya masih **0** — serapannya tidak bisa dihitung.
- **99 unit** dipakai oleh indikator dari **lebih dari satu dokumen PK** (mis. dua PK Pengawas berbeda memilih sub kegiatan yang sama); **58 unit** dipakai beberapa indikator dalam **satu dokumen PK yang sama**.

### 1.1 Per OPD

| OPD | Program | Kegiatan | Sub Kegiatan | Total unit | Indikator terlibat | Lebih pagu |
|---|---:|---:|---:|---:|---:|---:|
| Badan Kesatuan Bangsa Dan Politik | 0 | 2 | 0 | **2** | 5 | 1 |
| Badan Pendapatan Daerah | 0 | 1 | 0 | **1** | 4 |  |
| Badan Pengelolaan Keuangan Dan Aset Daerah | 0 | 2 | 5 | **7** | 14 |  |
| Dinas Kependudukan Dan Pencatatan Sipil | 3 | 9 | 4 | **16** | 63 | 2 |
| Dinas Kesehatan | 1 | 2 | 0 | **3** | 34 |  |
| Dinas Ketahanan Pangan | 2 | 4 | 9 | **15** | 32 |  |
| Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian | 0 | 3 | 1 | **4** | 8 |  |
| Dinas Lingkungan Hidup | 0 | 2 | 6 | **8** | 16 | 2 |
| Dinas Pemberdayaan Masyarakat Dan Pekon | 0 | 0 | 5 | **5** | 10 |  |
| Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk Dan Keluarga Berencana | 0 | 4 | 0 | **4** | 10 |  |
| Dinas Pendidikan Dan Kebudayaan | 0 | 3 | 3 | **6** | 21 |  |
| Dinas Perhubungan | 0 | 0 | 4 | **4** | 8 | 1 |
| Dinas Pertanian | 5 | 6 | 0 | **11** | 27 | 2 |
| Dinas Sosial | 0 | 1 | 0 | **1** | 2 |  |
| Inspektorat | 1 | 5 | 1 | **7** | 43 | 3 |
| Kecamatan Adiluwih | 4 | 0 | 1 | **5** | 10 | 1 |
| Kecamatan Pagelaran | 0 | 0 | 13 | **13** | 26 |  |
| Kecamatan Pardasuka | 3 | 0 | 1 | **4** | 9 |  |
| Kecamatan Pringsewu | 0 | 0 | 13 | **13** | 31 |  |
| Kecamatan Sukoharjo | 5 | 3 | 11 | **19** | 48 | 6 |
| Satuan Polisi Pamong Praja | 2 | 1 | 0 | **3** | 9 | 2 |
| Sekretariat Daerah | 4 | 1 | 1 | **6** | 22 | 1 |
| **Total** | **30** | **49** | **78** | **157** | **452** | **21** |

## 2. Yang Perlu Perhatian Lebih Dulu

### 2.1 Unit bersama yang realisasinya sudah melampaui pagu

| OPD | Tingkat | Ref | Unit | Indikator | Pagu | Realisasi | Kelebihan |
|---|---|---|---|---:|---:|---:|---:|
| Sekretariat Daerah | Program | #119 | PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA | 8 | Rp 32.497.065.043 | Rp 59.235.534.300 | **Rp 26.738.469.257** |
| Satuan Polisi Pamong Praja | Program | #210 | PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA | 2 | Rp 16.045.284.428 | Rp 24.286.984.378 | **Rp 8.241.699.950** |
| Satuan Polisi Pamong Praja | Program | #27 | PROGRAM PENINGKATAN KETENTERAMAN DAN KETERTIBAN UMUM | 5 | Rp 932.668.500 | Rp 3.273.135.590 | **Rp 2.340.467.090** |
| Inspektorat | Kegiatan | #381 | Penyelenggaraan Pengawasan Internal | 10 | Rp 858.600.000 | Rp 2.932.650.000 | **Rp 2.074.050.000** |
| Badan Kesatuan Bangsa Dan Politik | Kegiatan | #498 | Perumusan Kebijakan Teknis dan Pemantapan Pelaksanaan Bidang | 3 | Rp 934.328.000 | Rp 2.013.989.448 | **Rp 1.079.661.448** |
| Dinas Kependudukan Dan Pencatatan Sipil | Program | #58 | PROGRAM PENDAFTARAN PENDUDUK | 2 | Rp 420.520.000 | Rp 656.328.936 | **Rp 235.808.936** |
| Inspektorat | Kegiatan | #521 | Monitoring dan Evaluasi Tindak Lanjut Hasil Pemeriksaan BPK  | 5 | Rp 226.800.000 | Rp 382.500.000 | **Rp 155.700.000** |
| Dinas Pertanian | Program | #107 | PROGRAM PENYEDIAAN DAN PENGEMBANGAN SARANA PERTANIAN | 2 | Rp 512.689.000 | Rp 662.700.700 | **Rp 150.011.700** |
| Dinas Pertanian | Program | #109 | PROGRAM PENGENDALIAN KESEHATAN HEWAN DAN KESEHATAN MASYARAKA | 3 | Rp 67.000.000 | Rp 195.648.822 | **Rp 128.648.822** |
| Dinas Perhubungan | Sub Kegiatan | #711 | Penyediaan Bukti Lulus Uji Pengujian Berkala Kendaraan Bermo | 2 | Rp 100.000.000 | Rp 200.000.000 | **Rp 100.000.000** |
| Kecamatan Sukoharjo | Program | #171 | PROGRAM KOORDINASI KETENTRAMAN DAN KETERTIBAN UMUM | 4 | Rp 145.200.000 | Rp 234.000.000 | **Rp 88.800.000** |
| Kecamatan Adiluwih | Program | #174 | PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA | 2 | Rp 2.006.320.505 | Rp 2.083.077.912 | **Rp 76.757.407** |
| Inspektorat | Sub Kegiatan | #1309 | Pendidikan dan Pelatihan Pegawai Berdasarkan Tugas dan Fungs | 2 | Rp 111.370.000 | Rp 170.544.156 | **Rp 59.174.156** |
| Kecamatan Sukoharjo | Program | #169 | PROGRAM PENYELENGGARAAN PEMERINTAHAN DAN PELAYANAN PUBLIK | 4 | Rp 8.040.000 | Rp 32.160.000 | **Rp 24.120.000** |
| Dinas Lingkungan Hidup | Sub Kegiatan | #596 | Penyediaan Sarana dan Prasarana Pengelolaan Persampahan di T | 2 | Rp 258.950.000 | Rp 273.532.300 | **Rp 14.582.300** |
| Dinas Kependudukan Dan Pencatatan Sipil | Program | #59 | PROGRAM PENCATATAN SIPIL | 4 | Rp 7.800.000 | Rp 14.538.600 | **Rp 6.738.600** |
| Kecamatan Sukoharjo | Program | #173 | PROGRAM PEMBINAAN DAN PENGAWASAN PEMERINTAHAN DESA | 4 | Rp 1.680.000 | Rp 6.720.000 | **Rp 5.040.000** |
| Dinas Lingkungan Hidup | Kegiatan | #140 | Pemulihan Pencemaran dan/atau Kerusakan Lingkungan Hidup Kab | 2 | Rp 3.093.500 | Rp 5.500.050 | **Rp 2.406.550** |
| Kecamatan Sukoharjo | Kegiatan | #444 | Fasilitasi, Rekomendasi dan Koordinasi\nPembinaan dan Pengaw | 2 | Rp 1.680.000 | Rp 3.360.000 | **Rp 1.680.000** |
| Kecamatan Sukoharjo | Program | #172 | PROGRAM PENYELENGGARAAN URUSAN PEMERINTAHAN UMUM | 4 | Rp 240.000 | Rp 960.000 | **Rp 720.000** |
| Kecamatan Sukoharjo | Kegiatan | #443 | Penyelenggaraan Urusan Pemerintahan Umum Sesuai Penugasan Ke | 2 | Rp 240.000 | Rp 480.000 | **Rp 240.000** |

Pola yang sering terlihat: kelebihannya persis kelipatan pagu (200%, 300%, 400%) — nilai penuh diketik ulang pada tiap indikator, padahal tiap indikator hanya mengisi **bagiannya**.

### 2.2 Unit dengan indikator terbanyak

| OPD | Tingkat | Ref | Unit | Indikator | Dari PK |
|---|---|---|---|---:|---:|
| Dinas Kesehatan | Kegiatan | #22 | Penyediaan Layanan Kesehatan untuk UKM dan UKP Rujukan Tingk | **24** | 3 |
| Inspektorat | Kegiatan | #384 | Pendampingan dan Asistensi | **20** | 5 |
| Inspektorat | Kegiatan | #381 | Penyelenggaraan Pengawasan Internal | **10** | 3 |
| Sekretariat Daerah | Program | #119 | PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA | **8** | 5 |
| Dinas Kesehatan | Program | #6 | PROGRAM PEMENUHAN UPAYA KESEHATAN PERORANGAN DAN UPAYA KESEH | **7** | 1 |
| Dinas Kependudukan Dan Pencatatan Sipil | Kegiatan | #154 | Penyelenggaraan Pendaftaran Penduduk | **7** | 1 |
| Dinas Kependudukan Dan Pencatatan Sipil | Kegiatan | #155 | Penyelenggaraan Pencatatan Sipil | **7** | 1 |
| Dinas Pendidikan Dan Kebudayaan | Kegiatan | #8 | Pengelolaan Pendidikan Sekolah Dasar | **6** | 1 |
| Dinas Pendidikan Dan Kebudayaan | Kegiatan | #9 | Pengelolaan Pendidikan Sekolah Menengah Pertama | **6** | 1 |
| Dinas Pertanian | Kegiatan | #275 | Pengawasan Penggunaan Sarana Pertanian | **6** | 3 |
| Kecamatan Pringsewu | Sub Kegiatan | #1343 | Peningkatan Partisipasi Masyarakat dalam Forum Musyawarah Pe | **6** | 6 |
| Satuan Polisi Pamong Praja | Program | #27 | PROGRAM PENINGKATAN KETENTERAMAN DAN KETERTIBAN UMUM | **5** | 2 |

### 2.3 Unit bersama yang pagunya masih 0

| OPD | Tingkat | Ref | Unit | Indikator | Realisasi terisi |
|---|---|---|---|---:|---:|
| Dinas Lingkungan Hidup | Sub Kegiatan | #589 | Pengelolaan Laboratorium Lingkungan Hidup kabupaten/kota | 2 | — |
| Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk Dan Keluarga Berencana | Kegiatan | #110 | Pelembagaan Pengarusutamaan Gender (PUG) pada Lembaga Pemeri | 3 | — |
| Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk Dan Keluarga Berencana | Kegiatan | #113 | Pencegahan Kekerasan Terhadap Perempuan Lingkup Daerah Kabup | 2 | — |
| Inspektorat | Program | #139 | PROGRAM PERUMUSAN KEBIJAKAN, PENDAMPINGAN DAN ASISTENSI | 2 | — |
| Inspektorat | Kegiatan | #383 | Perumusan Kebijakan Teknis di Bidang Pengawasan dan Fasilita | 2 | — |
| Inspektorat | Kegiatan | #384 | Pendampingan dan Asistensi | 20 | — |
| Kecamatan Pringsewu | Sub Kegiatan | #662 | Fasilitasi Penyediaan Sarana dan Prasarana Kelembagaan Lemba | 3 | — |

### 2.4 Unit yang indikatornya berteks sama di beberapa dokumen PK

**48 unit.** Dua dokumen PK berbeda (mis. dua PK Pengawas, atau PK Camat dan PK JPT) memuat indikator dengan **bunyi yang persis sama** pada unit yang sama. Ini yang paling rawan: tiap indikator punya rencana aksi dan MONEV sendiri, sehingga realisasi yang sama mudah terketik dua kali.

| OPD | Tingkat | Ref | Unit | Indikator | Berteks sama | Contoh |
|---|---|---|---|---:|---:|---|
| Inspektorat | Kegiatan | #384 | Pendampingan dan Asistensi | 20 | 17 | Level Penilaian Mandiri SPIP Perangkat Daerah |
| Inspektorat | Kegiatan | #381 | Penyelenggaraan Pengawasan Internal | 10 | 6 | Persentase Hasil Pengawasan yang didukung dengan bukti  |
| Sekretariat Daerah | Program | #119 | PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUP | 8 | 6 | Indeks Reformasi Birokrasi General |
| Kecamatan Pringsewu | Sub Kegiatan | #1343 | Peningkatan Partisipasi Masyarakat dalam Forum Mus | 6 | 5 | Persentase tingkat kepuasan masyarakat dan partisipasi  |
| Badan Pendapatan Daerah | Kegiatan | #356 | Kegiatan Pengelolaan Pendapatan Daerah | 4 | 4 | Persentase realisasi pajak daerah |
| Dinas Pertanian | Kegiatan | #275 | Pengawasan Penggunaan Sarana Pertanian | 6 | 4 | Jumlah produksi jagung |
| Kecamatan Sukoharjo | Program | #169 | PROGRAM PENYELENGGARAAN PEMERINTAHAN DAN PELAYANAN | 4 | 4 | Nilai Evaluasi SAKIP |
| Kecamatan Sukoharjo | Program | #171 | PROGRAM KOORDINASI KETENTRAMAN DAN KETERTIBAN UMUM | 4 | 4 | Nilai Evaluasi SAKIP |
| Kecamatan Sukoharjo | Program | #172 | PROGRAM PENYELENGGARAAN URUSAN PEMERINTAHAN UMUM | 4 | 4 | Nilai Evaluasi SAKIP |
| Kecamatan Sukoharjo | Program | #173 | PROGRAM PEMBINAAN DAN PENGAWASAN PEMERINTAHAN DESA | 4 | 4 | Nilai Evaluasi SAKIP |
| Kecamatan Pardasuka | Sub Kegiatan | #1553 | Pembinaan Kerukunan Antar Suku dan Intra Suku, Uma | 3 | 3 | Jumlah Kehadiran Peserta Pembinaan Kerukunan Anatr Suku |
| Kecamatan Pringsewu | Sub Kegiatan | #662 | Fasilitasi Penyediaan Sarana dan Prasarana Kelemba | 3 | 3 | Prosentase pencapaian program penyelenggaraan pemerinta |
| Kecamatan Sukoharjo | Sub Kegiatan | #1445 | Koordinasi/Sinergi Perencanaan dan Pelaksanaan Keg | 3 | 3 | Prosentase Kepuasan Masyarakat Terhadap Pelayanan Publi |
| Badan Pengelolaan Keuangan Dan Aset Daerah | Sub Kegiatan | #1227 | Analisis Investasi Pemerintah Daerah | 2 | 2 | Prosentase penyerapan Belanja (Belanja Bunga, Bantuan K |
| Badan Pengelolaan Keuangan Dan Aset Daerah | Sub Kegiatan | #1228 | Analisis Perencanaan dan Penyaluran Bantuan Keuang | 2 | 2 | Prosentase penyerapan Belanja (Belanja Bunga, Bantuan K |
| Badan Pengelolaan Keuangan Dan Aset Daerah | Sub Kegiatan | #1229 | Pengelolaan Dana Darurat dan Mendesak | 2 | 2 | Prosentase penyerapan Belanja (Belanja Bunga, Bantuan K |
| Badan Pengelolaan Keuangan Dan Aset Daerah | Sub Kegiatan | #1230 | Pengelolaan Dana bagi Hasil Kabupaten/Kota | 2 | 2 | Prosentase penyerapan Belanja (Belanja Bunga, Bantuan K |
| Dinas Ketahanan Pangan | Sub Kegiatan | #549 | Pengamanan Barang Milik Daerah SKPD | 2 | 2 | Jumlah dokumen pengaman Barang Milik Daerah SKPD |
| Dinas Ketahanan Pangan | Sub Kegiatan | #550 | Penyediaan Komponen Instalasi\nListrik/Penerangan  | 2 | 2 | Jumlah bulan Penyediaan Administrasi Umum Daerah |
| Dinas Ketahanan Pangan | Sub Kegiatan | #552 | Penyediaan Bahan Logistik Kantor | 2 | 2 | Jumlah bulan Penyediaan Administrasi Umum Daerah |
| Dinas Ketahanan Pangan | Sub Kegiatan | #553 | Penyediaan Barang Cetakan dan Penggandaan | 2 | 2 | Jumlah bulan Penyediaan Administrasi Umum Daerah |
| Dinas Ketahanan Pangan | Sub Kegiatan | #554 | Penyediaan Bahan Bacaan dan Peraturan Perundang-un | 2 | 2 | Jumlah bulan Penyediaan Administrasi Umum Daerah |
| Dinas Ketahanan Pangan | Sub Kegiatan | #555 | Penyelenggaraan Rapat Koordinasi dan Konsultasi SK | 2 | 2 | Jumlah bulan Penyediaan Administrasi Umum Daerah |
| Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian | Kegiatan | #287 | Pembangunan dan Pengelolaan Sarana Distribusi Perd | 2 | 2 | Kontribusi sektor perdagangan terhadap PDRB |
| Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian | Kegiatan | #288 | Pengendalian Harga, dan Stok Barang Kebutuhan Poko | 2 | 2 | Kontribusi sektor perdagangan terhadap PDRB |
| Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian | Kegiatan | #289 | Pelaksanaan Metrologi Legal, Berupa Tera, Tera Ula | 2 | 2 | Kontribusi sektor perdagangan terhadap PDRB |
| Dinas Lingkungan Hidup | Sub Kegiatan | #589 | Pengelolaan Laboratorium Lingkungan Hidup kabupate | 2 | 2 | Jumlah pengujian yang dilaksanakan oleh laboratorium li |
| Dinas Lingkungan Hidup | Sub Kegiatan | #596 | Penyediaan Sarana dan Prasarana Pengelolaan Persam | 2 | 2 | Indeks Kinerja Pengelolaan Sampah (IKPS) |
| Dinas Lingkungan Hidup | Sub Kegiatan | #597 | Penanganan sampah melalui pemilahan dan pengolahan | 2 | 2 | Indeks Kinerja Pengelolaan Sampah (IKPS) |
| Dinas Lingkungan Hidup | Sub Kegiatan | #598 | Penyusunan Kebijakan Kerja Sama Pengelolaan Persam | 2 | 2 | Indeks Kinerja Pengelolaan Sampah (IKPS) |
| Dinas Pemberdayaan Masyarakat Dan Pekon | Sub Kegiatan | #634 | Penyediaan Bahan Logistik Kantor | 2 | 2 | Jumlah arsip yang ditata |
| Dinas Pemberdayaan Masyarakat Dan Pekon | Sub Kegiatan | #636 | Penyediaan Bahan Bacaan dan Peraturan Perundang-un | 2 | 2 | Jumlah arsip yang ditata |
| Dinas Pemberdayaan Masyarakat Dan Pekon | Sub Kegiatan | #640 | Penyediaan Jasa Komunikasi, Sumber Daya Air dan Li | 2 | 2 | Jumlah arsip yang ditata |
| Dinas Pemberdayaan Masyarakat Dan Pekon | Sub Kegiatan | #642 | Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan,  | 2 | 2 | Jumlah arsip yang ditata |
| Dinas Pemberdayaan Masyarakat Dan Pekon | Sub Kegiatan | #643 | Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan,  | 2 | 2 | Jumlah arsip yang ditata |
| Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk Dan Keluarga Berencana | Kegiatan | #119 | Penyediaan Layanan bagi Anak yang Memerlukan Perli | 3 | 2 | 1.Presentase Pelayanan Bagi Anak Yang Memerlukan Perlin |
| Dinas Pendidikan Dan Kebudayaan | Sub Kegiatan | #86 | Perhitungan dan Pemetaan Pendidik dan Tenaga Kepen | 2 | 2 | Jumlah Dokumen Hasil Perhitungan dan Pemetaan Pendidik  |
| Dinas Pendidikan Dan Kebudayaan | Sub Kegiatan | #87 | Penataan Pendistribusian Pendidik dan Tenaga Kepen | 2 | 2 | Jumlah Laporan Hasil Pelaksanaan Penataan Pendistribusi |
| Dinas Perhubungan | Sub Kegiatan | #711 | Penyediaan Bukti Lulus Uji Pengujian Berkala Kenda | 2 | 2 | Persentaqse kendaraan bermotor laik jalan |
| Dinas Perhubungan | Sub Kegiatan | #712 | Pemeliharaan Sarana dan Prasarana Pengujian Berkal | 2 | 2 | Persentaqse kendaraan bermotor laik jalan |
| Dinas Perhubungan | Sub Kegiatan | #718 | Pengendalian dan Pengawasan Ketersediaan Angkutan  | 2 | 2 | Persentase angkutan umum yang memenuhi perijinan dan te |
| Dinas Pertanian | Kegiatan | #279 | Pengembangan Prasarana Pertanian | 2 | 2 | Rasio pembangunan prasarana pertanian |
| Kecamatan Adiluwih | Sub Kegiatan | #1471 | Peningkatan Efektifitas Pelaksanaan Pelayanan kepa | 2 | 2 | Jumlah Laporan Fasilitasi Percepatan Pencapaian Standar |
| Kecamatan Pringsewu | Sub Kegiatan | #1348 | Pembangunan Sarana dan Prasarana Kelurahan | 2 | 2 | Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan |
| Kecamatan Pringsewu | Sub Kegiatan | #1349 | Pemberdayaan Masyarakat di Kelurahan | 2 | 2 | Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan |
| Kecamatan Sukoharjo | Program | #168 | PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUP | 2 | 2 | Nilai Evaluasi SAKIP |
| Kecamatan Sukoharjo | Sub Kegiatan | #1450 | Pembinaan Kerukunan Antar Suku dan Intra Suku, Uma | 3 | 2 | Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan  |
| Sekretariat Daerah | Sub Kegiatan | #995 | Fasilitasi Keprotokolan | 2 | 2 | Jumlah Laporan Kegiatan Protokol dan Komunikasi Pimpina |

**Kasus khusus — Kecamatan Sukoharjo.** Satu-satunya OPD yang punya PK **Camat (#211)** dan PK **JPT (#490)** sekaligus untuk 2026, ditandatangani pihak ke-1 yang sama, dengan 4 indikator berbunyi identik. Kelima program bersamanya (dan angka 400% di MONEV) adalah akibat langsung dokumen kembar ini: 2 indikator × 2 dokumen = 4 baris realisasi untuk satu program. Perlu diputuskan salah satu dokumen yang berlaku.

## 3. Rincian per OPD

Tiap unit: nama (id unit di tabel master), pagu, keadaan realisasi MONEV, lalu daftar indikator pemakainya — dengan nomor dokumen PK dan jenisnya, supaya terlihat apakah pemakaian bersama itu terjadi di **satu** PK atau **lintas** PK.

### Badan Kesatuan Bangsa Dan Politik (id 28)

**Kegiatan**

- **Perumusan Kebijakan Teknis dan Pemantapan Pelaksanaan Bidang Ideologi Pancasila dan Karakter Kebangsaan** (#498) — pagu Rp 934.328.000 · **LEBIH 216%** (+Rp 1.079.661.448)  
  3 indikator dari 1 dokumen PK (Administrator: #409)
    - PK #409 · Persentase Masyarakat yang memahami Nilai Nilai Idiologi Pancasila dengan Kategori Baik
    - PK #409 · Persentase Penguatan Kelestarian Budaya Lokal Melalui FPK (Forum Pembauran Kebangsaan )
    - PK #409 · Persentase Konflik Umat Beragama yang Terselesaikan

- **Perumusan Kebijakan Teknis dan Pelaksanaan Pemantapan Kewaspadaan Nasional dan Penanganan Konflik Sosial** (#502) — pagu Rp 367.384.900 · 62% terserap  
  2 indikator dari 1 dokumen PK (Administrator: #187)
    - PK #187 · Cakupan Stabilitas Keamanan, Ketertiban dan Strabilitas Politik
    - PK #187 · Persensatase Konflik Umat Beragama yang Terselesaikan

### Badan Pendapatan Daerah (id 6)

**Kegiatan**

- **Kegiatan Pengelolaan Pendapatan Daerah** (#356) — pagu Rp 1.504.188.500 · 84% terserap  
  4 indikator dari 2 dokumen PK (Administrator: #30, #31) — **4 indikator berteks sama**
    - PK #30 · Persentase realisasi pajak daerah ⟵ kembar
    - PK #30 · Persentase realisasi retribusi daerah ⟵ kembar
    - PK #31 · Persentase Realisasi Pajak Daerah ⟵ kembar
    - PK #31 · Persentase realisasi retribusi daerah ⟵ kembar

### Badan Pengelolaan Keuangan Dan Aset Daerah (id 5)

**Kegiatan**

- **Koordinasi dan Penyusunan Rencana Anggaran Daerah** (#351) — pagu Rp 1.011.650.900 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #276)
    - PK #276 · Presentase belanja pegawai di luar tunjangan guru yang dialokasi melalui TKD
    - PK #276 · presentase Alokasi belanja infrastruktur pelayanan publik

- **Koordinasi dan Pengelolaan Perbendaharaan Daerah** (#352) — pagu Rp 153.124.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #277)
    - PK #277 · Prosentase realisasi Anggaran belanja Urusan Wajib dan Pelayanan Dasar
    - PK #277 · Persentase Penurunan SILPA

**Sub Kegiatan**

- **Koordinasi dan Penyusunan Laporan Keuangan Akhir Tahun SKPD** (#1180) — pagu Rp 7.610.500 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #281, #312)
    - PK #281 · Jumlah Bulan Administrasi keuangan
    - PK #312 · Nilai SAKIP

- **Analisis Investasi Pemerintah Daerah** (#1227) — pagu Rp 200.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #285, #312) — **2 indikator berteks sama**
    - PK #285 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar
    - PK #312 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar

- **Analisis Perencanaan dan Penyaluran Bantuan Keuangan** (#1228) — pagu Rp 161.592.238.100 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #285, #312) — **2 indikator berteks sama**
    - PK #285 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar
    - PK #312 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar

- **Pengelolaan Dana Darurat dan Mendesak** (#1229) — pagu Rp 2.000.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #285, #312) — **2 indikator berteks sama**
    - PK #285 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar
    - PK #312 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar

- **Pengelolaan Dana bagi Hasil Kabupaten/Kota** (#1230) — pagu Rp 7.725.500.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #285, #312) — **2 indikator berteks sama**
    - PK #285 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar
    - PK #312 · Prosentase penyerapan Belanja (Belanja Bunga, Bantuan Keuangan, Belanja Tidak Terduga) dan Investasi daerah ⟵ kembar

### Dinas Kependudukan Dan Pencatatan Sipil (id 14)

**Program**

- **PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA** (#57) — pagu Rp 6.413.657.115 · 98% terserap  
  2 indikator dari 1 dokumen PK (JPT: #413)
    - PK #413 · Indeks Kepuasan Masyarakat terhadap Layanan Adminduk
    - PK #413 · Pembangunan Zona Integritas Layanan Adminduk

- **PROGRAM PENDAFTARAN PENDUDUK** (#58) — pagu Rp 420.520.000 · **LEBIH 156%** (+Rp 235.808.936)  
  2 indikator dari 1 dokumen PK (JPT: #413)
    - PK #413 · Persentase Kepemilikan KTP el
    - PK #413 · Persentase Kepemilikan KIA

- **PROGRAM PENCATATAN SIPIL** (#59) — pagu Rp 7.800.000 · **LEBIH 186%** (+Rp 6.738.600)  
  4 indikator dari 1 dokumen PK (JPT: #413)
    - PK #413 · Persentase Anak Memiliki Akta Lahir 0-18 Th
    - PK #413 · Persentase Kepemilikan Dokumen Perkawinan yang dicatatkan
    - PK #413 · Persentase Kepemilikan Dokumen Perceraian yang dicatatkan
    - PK #413 · Persentase Penerbitan Akta Kematian bagi yang melaporkan

**Kegiatan**

- **Administrasi Keuangan Perangkat Daerah** (#146) — pagu Rp 5.607.571.115 · belum ada realisasi  
  4 indikator dari 1 dokumen PK (Administrator: #432)
    - PK #432 · Nilai SAKIP
    - PK #432 · Persentase Penyusunan Dokumen Perencanaan, Anggaran dan Laporan Capaian Kinerja
    - PK #432 · Persentase Pegawai yang mengikuti kegiatan Peningkatan Kompetensi
    - PK #432 · Persentase Sarana Prasarana dan Fasilitas Layanan Adminduk yang memadai

- **Administrasi Umum Perangkat Daerah** (#149) — pagu Rp 112.667.000 · belum ada realisasi  
  4 indikator dari 1 dokumen PK (Administrator: #432)
    - PK #432 · Nilai SAKIP
    - PK #432 · Persentase Penyusunan Dokumen Perencanaan, Anggaran dan Laporan Capaian Kinerja
    - PK #432 · Persentase Pegawai yang mengikuti kegiatan Peningkatan Kompetensi
    - PK #432 · Persentase Sarana Prasarana dan Fasilitas Layanan Adminduk yang memadai

- **Pengadaan Barang Milik Daerah Penunjang Urusan Pemerintah Daerah** (#150) — pagu Rp 356.760.000 · belum ada realisasi  
  4 indikator dari 1 dokumen PK (Administrator: #432)
    - PK #432 · Nilai SAKIP
    - PK #432 · Persentase Penyusunan Dokumen Perencanaan, Anggaran dan Laporan Capaian Kinerja
    - PK #432 · Persentase Pegawai yang mengikuti kegiatan Peningkatan Kompetensi
    - PK #432 · Persentase Sarana Prasarana dan Fasilitas Layanan Adminduk yang memadai

- **Penyediaan Jasa Penunjang Urusan Pemerintahan Daerah** (#151) — pagu Rp 160.149.000 · belum ada realisasi  
  4 indikator dari 1 dokumen PK (Administrator: #432)
    - PK #432 · Nilai SAKIP
    - PK #432 · Persentase Penyusunan Dokumen Perencanaan, Anggaran dan Laporan Capaian Kinerja
    - PK #432 · Persentase Pegawai yang mengikuti kegiatan Peningkatan Kompetensi
    - PK #432 · Persentase Sarana Prasarana dan Fasilitas Layanan Adminduk yang memadai

- **Pemeliharaan Barang Milik Daerah Penunjang Urusan Pemerintahan Daerah** (#152) — pagu Rp 176.510.000 · belum ada realisasi  
  4 indikator dari 1 dokumen PK (Administrator: #432)
    - PK #432 · Nilai SAKIP
    - PK #432 · Persentase Penyusunan Dokumen Perencanaan, Anggaran dan Laporan Capaian Kinerja
    - PK #432 · Persentase Pegawai yang mengikuti kegiatan Peningkatan Kompetensi
    - PK #432 · Persentase Sarana Prasarana dan Fasilitas Layanan Adminduk yang memadai

- **Penyelenggaraan Pendaftaran Penduduk** (#154) — pagu Rp 420.520.000 · belum ada realisasi  
  7 indikator dari 1 dokumen PK (Administrator: #414)
    - PK #414 · Jumlah Perekaman KTP el
    - PK #414 · Jumlah Kepemilikan KTP el
    - PK #414 · Jumlah KIA yang diterbitkan
    - PK #414 · Jumlah Kecamatan yang difasilitasi layanan perekaman KTP el
    - PK #414 · Jumlah Pekon yang memfasilitasi Layanan Pengiriman KTP el dan KIA
    - PK #414 · Jumlah Peralatan Cetak KTP el dan KIA yang disediakan di MPP
    - PK #414 · Jumlah Kegiatan Pemutakhiran Elemen Data Dokumen Kependudukan

- **Penyelenggaraan Pencatatan Sipil** (#155) — pagu Rp 7.800.000 · belum ada realisasi  
  7 indikator dari 1 dokumen PK (Administrator: #415)
    - PK #415 · Jumlah Akta Lahir 0-18 Th yang diterbitkan
    - PK #415 · Persentase Akta Kematian yang diterbitkan
    - PK #415 · Persentase Akta Perkawinan Non Muslim yang diterbitkan
    - PK #415 · Persentase Akta Perceraian Non Muslim yang diterbitkan
    - PK #415 · Jumlah Koordinasi untuk Peningkatan Pelaporan Pencatatan Perkawinan/ Perceraian
    - PK #415 · Jumlah Pemutakhiran Dokumen Kependudukan data anak beresiko Stunting
    - PK #415 · Jumlah Kegiatan Forum Komunikasi dengan seluruh pemuka agama Non Muslim

- **Pengumpulan Data Kependudukan dan Pemanfaatan dan Penyajian Database Kependudukan** (#156) — pagu Rp 705.000 · belum ada realisasi  
  5 indikator dari 1 dokumen PK (Administrator: #416)
    - PK #416 · Jumlah Dokumen Profil Kependudukan Tingkat Kabupaten yang diterbitkan
    - PK #416 · Jumlah dokumen Data Agregat Kependudukan Tingkat Kabupaten yang diterbitkan
    - PK #416 · Jumlah Perjanjian Kerja Sama Pemanfaatan Data Kependudukan dengan Perangkat Daerah
    - PK #416 · Persentase Penduduk yang melakukan Aktivasi IKD
    - PK #416 · Jumlah Inovasi Baru Layanan Adminduk yang memudahkan masyarakat

- **Penyusunan Profil Kependudukan** (#157) — pagu Rp 8.100.000 · belum ada realisasi  
  5 indikator dari 1 dokumen PK (Administrator: #416)
    - PK #416 · Jumlah Dokumen Profil Kependudukan Tingkat Kabupaten yang diterbitkan
    - PK #416 · Jumlah dokumen Data Agregat Kependudukan Tingkat Kabupaten yang diterbitkan
    - PK #416 · Jumlah Perjanjian Kerja Sama Pemanfaatan Data Kependudukan dengan Perangkat Daerah
    - PK #416 · Persentase Penduduk yang melakukan Aktivasi IKD
    - PK #416 · Jumlah Inovasi Baru Layanan Adminduk yang memudahkan masyarakat

**Sub Kegiatan**

- **Pelaksanaan Penatausahaan dan\nPengujian/Verifikasi Keuangan SKPD** (#602) — pagu Rp 111.460.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Pengawas: #433)
    - PK #433 · Jumlah Dokumen Perencanaan, Anggaran dan Pelaporan Kinerja dan Keuangan Perangkat Daerah
    - PK #433 · Persentase Penatausahaan/ Pengelolaan Administrasi Keuangan Perangkat Daerah

- **Penyediaan Bahan Logistik Kantor** (#607) — pagu Rp 35.707.000 · belum ada realisasi  
  5 indikator dari 1 dokumen PK (Pengawas: #434)
    - PK #434 · Penyelenggaraan urusan kesekretariatan dan rumah tangga
    - PK #434 · Penilaian kinerja pegawai
    - PK #434 · Penyelenggaraan Kompetensi Pegawai
    - PK #434 · Penyelenggaraan survey kepuasan masyarakat
    - PK #434 · Pengelolaan Barang Milik Negara dan Daerah

- **Penyediaan Barang Cetakan dan Penggandaan** (#608) — pagu Rp 8.224.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Pengawas: #433)
    - PK #433 · Jumlah Dokumen Perencanaan, Anggaran dan Pelaporan Kinerja dan Keuangan Perangkat Daerah
    - PK #433 · Persentase Penatausahaan/ Pengelolaan Administrasi Keuangan Perangkat Daerah

- **Pemeliharaan Peralatan dan Mesin Lainnya** (#616) — pagu Rp 29.920.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Pengawas: #434)
    - PK #434 · Penyelenggaraan urusan kesekretariatan dan rumah tangga
    - PK #434 · Pengelolaan Barang Milik Negara dan Daerah

### Dinas Kesehatan (id 11)

**Program**

- **PROGRAM PEMENUHAN UPAYA KESEHATAN PERORANGAN DAN UPAYA KESEHATAN MASYARAKAT** (#6) — pagu Rp 160.176.881.557 · 36% terserap  
  7 indikator dari 1 dokumen PK (JPT: #21)
    - PK #21 · Angka Kematian Ibu
    - PK #21 · Angka Kematian Balita
    - PK #21 · Prevalensi stunting
    - PK #21 · Angka Populasi bebas penyakit menular
    - PK #21 · Angka populasi bebas PTM
    - PK #21 · Cakupan penerima pemeriksaan kesehatan gratis
    - PK #21 · Proporsi fasyankes yang mencapai akreditasi â€œutamaâ€ atau lebih tinggi

**Kegiatan**

- **Penyediaan Fasilitas Pelayanan Kesehatan untuk UKM dan UKP Kewenangan Daerah Kabupaten/Kota** (#21) — pagu Rp 21.228.382.057 · 1% terserap  
  3 indikator dari 2 dokumen PK (Administrator: #263, #403)
    - PK #263 · Proporsi fasilitas sediaan farmasi, alat kesehatan dan makanan minuman sesuai standar
    - PK #263 · Proporsi fasyankes terintegrasi SIKN
    - PK #403 · Cakupan kespesertaan aktif JKN

- **Penyediaan Layanan Kesehatan untuk UKM dan UKP Rujukan Tingkat Daerah Kabupaten/Kota** (#22) — pagu Rp 106.421.034.000 · 54% terserap  
  24 indikator dari 3 dokumen PK (Administrator: #262, #403, #405)
    - PK #262 · % ANC 6 kali (K6)
    - PK #262 · Cakupan ANC sesuai standar (12T)
    - PK #262 · % ibu hamil KEK
    - PK #262 · Prevalensi anemia ibu hamil
    - PK #262 · % Skrining anemia pada ibu hamil
    - PK #262 · % calon pengantin yang mendapatkan skrining kesehatan
    - PK #262 · Persalinan di Fasyankes
    - PK #262 · Cakupan KF sesuai standar
    - PK #262 · Cakupan KN lengkap sesuai standar
    - PK #262 · % balita dipantau pertumbuhan dan perkembangan
    - PK #262 · Prevalensi balita underweight
    - PK #262 · % Desa/Kelurahan STBM
    - PK #403 · Indeks kepuasaan masyarakat terhadap layanan kesehatan
    - PK #403 · Cakupan kespesertaan aktif JKN
    - PK #405 · Cakupan Notifikasi kasus TBC
    - PK #405 · TB Success Rate
    - PK #405 · % Pasien TBC memulai pengobatan (Enrollment TBC)
    - PK #405 · Cakupan pemberian TPT pada orang kontak serumah
    - PK #405 · Cakupan imunisasi bayi lengkap
    - PK #405 · Cakupan imunisasi lanjutan pada baduta
    - PK #405 · % Desa mencapai Universal Child Imunization
    - PK #405 · % masyarakat mendapatkan skrining PTM sesuai standar
    - PK #405 · % hipertensi dalam pengendalian
    - PK #405 · % diabetes mellitus dalam pengendalian

### Dinas Ketahanan Pangan (id 24)

**Program**

- **PROGRAM PENINGKATAN DIVERSIFIKASI DAN KETAHANAN PANGAN MASYARAKAT** (#47) — pagu Rp 100.870.100 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (JPT: #242)
    - PK #242 · Skor Pola Pangan Harapan (PPH)Konsumsi Pangan
    - PK #242 · Skor Pola Pangan Harapan (PPH)Ketersediaan Pangan

- **PROGRAM PENGAWASAN KEAMANAN PANGAN** (#49) — pagu Rp 13.125.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (JPT: #242)
    - PK #242 · Skor Pola Pangan Harapan (PPH)Konsumsi Pangan
    - PK #242 · Persentase PSAT yang memenuhi persyaratan Keamanan Pangan

**Kegiatan**

- **Penyediaan dan Penyaluran Pangan Pokok atau Pangan Lainnya sesuai dengan Kebutuhan Daerah Kabupaten/Kota dalam rangka Stabilisasi Pasokan dan Harga Pangan** (#127) — pagu Rp 68.900.100 · belum ada realisasi  
  3 indikator dari 3 dokumen PK (Administrator: #306, #307, #452)
    - PK #306 · Persentase Ketersediaan Data dan Neraca Pangan Tepat Waktu
    - PK #307 · Laporan Kegiatan Stabilisasi Pasokan dan Harga Pangan Tingkat Produsen dan Konsumen di Kabupaten/Kota
    - PK #452 · Persentase Terlaksananya Kegiatan Intervensi Dalam Rangka Menjaga Stabilitas Harga Pangan Strategis

- **Pelaksanaan Pencapaian Target Konsumsi Pangan Perkapita/Tahun sesuai dengan Angka Kecukupan Gizi** (#129) — pagu Rp 31.970.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #309)
    - PK #309 · Persentase Tingkat Penganeragaman, ketersediaan pangan dan konsumsi pangan
    - PK #309 · Persentase Pola Konsumsi Pangan yang Memenuhi Standar

- **Penanganan Kerawanan Pangan Kewenangan Kabupaten/Kota** (#131) — pagu Rp 87.030.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #306)
    - PK #306 · Jumlah Desa Yang Bebas Dari Kerentanan Pangan
    - PK #306 · Persentase penyaluran Bantuan Pangan dalam rangka pengendalian Kerentanan Pangan

- **Pelaksanaan Pengawasan Keamanan Pangan Segar Daerah Kabupaten/Kota** (#132) — pagu Rp 13.125.000 · belum ada realisasi  
  3 indikator dari 1 dokumen PK (Administrator: #309)
    - PK #309 · Persentase PSAT yang Memenuhi Standar Keamanan Pangan terhadap residu pestisida
    - PK #309 · Persentase Pengujian yang diselesaikan Tepat Waktu Sesuai SOP
    - PK #309 · Persentase Permohonan Registrasi Yang Diterbitkan Sesuai Standart

**Sub Kegiatan**

- **Pengamanan Barang Milik Daerah SKPD** (#549) — pagu Rp 9.300.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497) — **2 indikator berteks sama**
    - PK #341 · Jumlah dokumen Pengaman Barang Milik Daerah SKPD ⟵ kembar
    - PK #497 · Jumlah dokumen pengaman Barang Milik Daerah SKPD ⟵ kembar

- **Penyediaan Komponen Instalasi\nListrik/Penerangan Bangunan Kantor** (#550) — pagu Rp 1.440.500 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497) — **2 indikator berteks sama**
    - PK #341 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar
    - PK #497 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar

- **Penyediaan Bahan Logistik Kantor** (#552) — pagu Rp 31.490.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497) — **2 indikator berteks sama**
    - PK #341 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar
    - PK #497 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar

- **Penyediaan Barang Cetakan dan Penggandaan** (#553) — pagu Rp 5.772.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497) — **2 indikator berteks sama**
    - PK #341 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar
    - PK #497 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar

- **Penyediaan Bahan Bacaan dan Peraturan Perundang-undangan** (#554) — pagu Rp 18.480.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497) — **2 indikator berteks sama**
    - PK #341 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar
    - PK #497 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar

- **Penyelenggaraan Rapat Koordinasi dan Konsultasi SKPD** (#555) — pagu Rp 24.801.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497) — **2 indikator berteks sama**
    - PK #341 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar
    - PK #497 · Jumlah bulan Penyediaan Administrasi Umum Daerah ⟵ kembar

- **Penyediaan Jasa Komunikasi, Sumber Daya Air dan Listrik** (#556) — pagu Rp 38.399.400 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497)
    - PK #341 · Jumlah Laporan Pembayaran Jasa Penunjang Urusan Pemerintah Daerah
    - PK #497 · Jumlah Kendaraan Dinas yang mendapat pemeliharaan

- **Penyediaan Jasa Peralatan dan Perlengkapan Kantor** (#557) — pagu Rp 9.420.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497)
    - PK #341 · Jumlah Laporan Pembayaran Jasa Penunjang Urusan Pemerintah Daerah
    - PK #497 · Jumlah Kendaraan Dinas yang mendapat pemeliharaan

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, dan Pajak Kendaraan Perorangan Dinas atau Kendaraan Dinas Jabatan** (#559) — pagu Rp 133.010.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #341, #497)
    - PK #341 · Jumlah Kendaraan Dinas yang mendapat pemeliharaan
    - PK #497 · Jumlah Laporan Pembayaran Jasa Penunjang Urusan Pemerintah Daerah

### Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian (id 16)

**Kegiatan**

- **Pembangunan dan Pengelolaan Sarana Distribusi Perdagangan** (#287) — pagu Rp 146.143.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Administrator: #369, #514) — **2 indikator berteks sama**
    - PK #369 · Kontribusi sektor perdagangan terhadap PDRB ⟵ kembar
    - PK #514 · Kontribusi sektor perdagangan terhadap PDRB ⟵ kembar

- **Pengendalian Harga, dan Stok Barang Kebutuhan Pokok dan Barang Penting di Tingkat Pasar Kabupaten/Kota** (#288) — pagu Rp 69.194.500 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Administrator: #369, #514) — **2 indikator berteks sama**
    - PK #369 · Kontribusi sektor perdagangan terhadap PDRB ⟵ kembar
    - PK #514 · Kontribusi sektor perdagangan terhadap PDRB ⟵ kembar

- **Pelaksanaan Metrologi Legal, Berupa Tera, Tera Ulang, dan Pengawasan** (#289) — pagu Rp 30.929.800 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Administrator: #369, #514) — **2 indikator berteks sama**
    - PK #369 · Kontribusi sektor perdagangan terhadap PDRB ⟵ kembar
    - PK #514 · Kontribusi sektor perdagangan terhadap PDRB ⟵ kembar

**Sub Kegiatan**

- **Pengawasan/Penyuluhan Metrologi Legal** (#945) — pagu Rp 14.511.300 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #511, #512)
    - PK #511 · Cakupan Tera Dan Tera UTTP
    - PK #512 · Jumlah Pengaduan Konsumen yang Ditangani

### Dinas Lingkungan Hidup (id 23)

**Kegiatan**

- **Pencegahan Pencemaran dan/atau Kerusakan Lingkungan Hidup Kabupaten/Kota** (#139) — pagu Rp 32.522.000 · 92% terserap  
  2 indikator dari 1 dokumen PK (Administrator: #440)
    - PK #440 · Indeks Kualitas Air
    - PK #440 · indeks kualitas udara

- **Pemulihan Pencemaran dan/atau Kerusakan Lingkungan Hidup Kabupaten/Kota** (#140) — pagu Rp 3.093.500 · **LEBIH 178%** (+Rp 2.406.550)  
  2 indikator dari 1 dokumen PK (Administrator: #440)
    - PK #440 · Indeks Kualitas Air
    - PK #440 · indeks kualitas udara

**Sub Kegiatan**

- **Penyediaan Gaji dan Tunjangan ASN** (#573) — pagu Rp 4.793.944.489 · 99% terserap  
  2 indikator dari 1 dokumen PK (Pengawas: #474)
    - PK #474 · Jumlah Dokumen Administrasi Keuangan Perangkat Daerah
    - PK #474 · Jumlah Dokumen Administrasi Perencanaan Perangkat Daerah

- **Pelaksanaan Penatausahaan dan\nPengujian/Verifikasi Keuangan SKPD** (#574) — pagu Rp 129.000.000 · 100% terserap  
  2 indikator dari 1 dokumen PK (Pengawas: #474)
    - PK #474 · Jumlah Dokumen Administrasi Keuangan Perangkat Daerah
    - PK #474 · Jumlah Dokumen Administrasi Perencanaan Perangkat Daerah

- **Pengelolaan Laboratorium Lingkungan Hidup kabupaten/kota** (#589) — pagu Rp 0 · pagu belum diisi  
  2 indikator dari 2 dokumen PK (Pengawas: #336, #363) — **2 indikator berteks sama**
    - PK #336 · Jumlah pengujian yang dilaksanakan oleh laboratorium lingkungan ⟵ kembar
    - PK #363 · Jumlah pengujian yang dilaksanakan oleh laboratorium lingkungan ⟵ kembar

- **Penyediaan Sarana dan Prasarana Pengelolaan Persampahan di TPA/TPST/SPA Kabupaten/Kota** (#596) — pagu Rp 258.950.000 · **LEBIH 106%** (+Rp 14.582.300)  
  2 indikator dari 2 dokumen PK (Pengawas: #451, #505) — **2 indikator berteks sama**
    - PK #451 · Indeks Kinerja Pengelolaan Sampah (IKPS) ⟵ kembar
    - PK #505 · Indeks Kinerja Pengelolaan Sampah (IKPS) ⟵ kembar

- **Penanganan sampah melalui pemilahan dan pengolahan sampah di instalasi pengolahan sampah TPS3R, PDU, TPST, TPS, SPA, PSEL/PLTSa, RDF, pusat pengomposan, biodigester, Bank Sampah dan fasilitas lainnya sesuai dengan peraturan perundangan** (#597) — pagu Rp 291.615.500 · 85% terserap  
  2 indikator dari 2 dokumen PK (Pengawas: #451, #505) — **2 indikator berteks sama**
    - PK #451 · Indeks Kinerja Pengelolaan Sampah (IKPS) ⟵ kembar
    - PK #505 · Indeks Kinerja Pengelolaan Sampah (IKPS) ⟵ kembar

- **Penyusunan Kebijakan Kerja Sama Pengelolaan Persampahan** (#598) — pagu Rp 900.000 · 0% terserap  
  2 indikator dari 2 dokumen PK (Pengawas: #451, #505) — **2 indikator berteks sama**
    - PK #451 · Indeks Kinerja Pengelolaan Sampah (IKPS) ⟵ kembar
    - PK #505 · Indeks Kinerja Pengelolaan Sampah (IKPS) ⟵ kembar

### Dinas Pemberdayaan Masyarakat Dan Pekon (id 22)

**Sub Kegiatan**

- **Penyediaan Bahan Logistik Kantor** (#634) — pagu Rp 24.220.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #264, #265) — **2 indikator berteks sama**
    - PK #264 · Jumlah arsip yang ditata ⟵ kembar
    - PK #265 · Jumlah arsip yang ditata ⟵ kembar

- **Penyediaan Bahan Bacaan dan Peraturan Perundang-undangan** (#636) — pagu Rp 37.500.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #264, #265) — **2 indikator berteks sama**
    - PK #264 · Jumlah arsip yang ditata ⟵ kembar
    - PK #265 · Jumlah arsip yang ditata ⟵ kembar

- **Penyediaan Jasa Komunikasi, Sumber Daya Air dan Listrik** (#640) — pagu Rp 54.700.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #264, #265) — **2 indikator berteks sama**
    - PK #264 · Jumlah arsip yang ditata ⟵ kembar
    - PK #265 · Jumlah arsip yang ditata ⟵ kembar

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, dan Pajak Kendaraan Perorangan Dinas atau Kendaraan Dinas Jabatan** (#642) — pagu Rp 4.300.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #264, #265) — **2 indikator berteks sama**
    - PK #264 · Jumlah arsip yang ditata ⟵ kembar
    - PK #265 · Jumlah arsip yang ditata ⟵ kembar

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, Pajak dan Perizinan Kendaraan Dinas Operasional atau Lapangan** (#643) — pagu Rp 73.060.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #264, #265) — **2 indikator berteks sama**
    - PK #264 · Jumlah arsip yang ditata ⟵ kembar
    - PK #265 · Jumlah arsip yang ditata ⟵ kembar

### Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk Dan Keluarga Berencana (id 211)

**Kegiatan**

- **Pelembagaan Pengarusutamaan Gender (PUG) pada Lembaga Pemerintah Kewenangan Kabupaten/Kota** (#110) — pagu Rp 0 · pagu belum diisi  
  3 indikator dari 2 dokumen PK (Administrator: #253, #459)
    - PK #253 · Partisipasi angkatan kerja perempuan
    - PK #459 · Tingkat Partisipasi Angkatan Kerja ( TPAK) Perempuan
    - PK #459 · Presentase anak memerlukan perlindungan khusus yang mendapatkan layanan komprehensif

- **Pemberdayaan Perempuan Bidang Politik, Hukum, Sosial, dan Ekonomi pada Organisasi Kemasyarakatan Kewenangan Kabupaten/Kota** (#111) — pagu Rp 49.996.000 · 8% terserap  
  2 indikator dari 2 dokumen PK (Administrator: #253, #459)
    - PK #253 · Partisipasi angkatan kerja perempuan
    - PK #459 · Tingkat Partisipasi Angkatan Kerja ( TPAK) Perempuan

- **Pencegahan Kekerasan Terhadap Perempuan Lingkup Daerah Kabupaten/Kota** (#113) — pagu Rp 0 · pagu belum diisi  
  2 indikator dari 2 dokumen PK (Administrator: #253, #459)
    - PK #253 · Partisipasi angkatan kerja perempuan
    - PK #459 · Menurunnya Kekerasan Terhadap perempuan

- **Penyediaan Layanan bagi Anak yang Memerlukan Perlindungan Khusus yang Memerlukan Koordinasi Tingkat Daerah Kabupaten/Kota** (#119) — pagu Rp 51.597.000 · 20% terserap  
  3 indikator dari 2 dokumen PK (Administrator: #253, #459) — **2 indikator berteks sama**
    - PK #253 · Partisipasi angkatan kerja perempuan
    - PK #253 · 1.Presentase Pelayanan Bagi Anak Yang Memerlukan Perlindungan Khusus 2. Presentase Terbentuknya Forum Tingkat Kecamatan ⟵ kembar
    - PK #459 · 1.Presentase Pelayanan Bagi Anak Yang Memerlukan Perlindungan Khusus 2. Presentase Terbentuknya Forum Tingkat Kecamatan ⟵ kembar

### Dinas Pendidikan Dan Kebudayaan (id 10)

**Kegiatan**

- **Pengelolaan Pendidikan Sekolah Dasar** (#8) — pagu Rp 37.074.918.550 · belum ada realisasi  
  6 indikator dari 1 dokumen PK (Administrator: #259)
    - PK #259 · Angka Partisipasi Sekolah (APS) 7 - 12 Tahun
    - PK #259 · Iklim Kebinekaan SD
    - PK #259 · Iklim Keamanan SD
    - PK #259 · Iklim Inklusivitas SD
    - PK #259 · Kemampuan Literasi SD
    - PK #259 · Kemampuan Numerasi SD

- **Pengelolaan Pendidikan Sekolah Menengah Pertama** (#9) — pagu Rp 21.746.971.718 · belum ada realisasi  
  6 indikator dari 1 dokumen PK (Administrator: #259)
    - PK #259 · Angka Partisipasi Sekolah (APS) 13 - 15 Tahun
    - PK #259 · Iklim Kebinekaan SMP
    - PK #259 · Iklim Keamanan SMP
    - PK #259 · Iklim Inklusivitas SMP
    - PK #259 · Kemampuan Literasi SMP
    - PK #259 · Kemampuan Numerasi SD

- **Pemerataan Kuantitas dan Kualitas Pendidik dan Tenaga Kependidikan bagi Satuan Pendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan** (#12) — pagu Rp 204.798.000 · belum ada realisasi  
  3 indikator dari 1 dokumen PK (Administrator: #273)
    - PK #273 · Pertumbuhan Proporsi Guru PAUD Formal dengan kualifikasi S1/D IV
    - PK #273 · Guru berkualifikasi SD
    - PK #273 · Guru berkualifikasi SMP

**Sub Kegiatan**

- **Penyediaan Bahan Logistik Kantor** (#9) — pagu Rp 34.588.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Pengawas: #304)
    - PK #304 · Jumlah Paket Bahan Logistik Kantor yang Disediakan
    - PK #304 · Jumlah Bahan Logistik Kantor yang Disediakan

- **Perhitungan dan Pemetaan Pendidik dan Tenaga Kependidikan Satuan Pendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan** (#86) — pagu Rp 74.855.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #280, #282) — **2 indikator berteks sama**
    - PK #280 · Jumlah Dokumen Hasil Perhitungan dan Pemetaan Pendidik dan Tenaga Kependidikan Satuan SatuanPendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan ⟵ kembar
    - PK #282 · Jumlah Dokumen Hasil Perhitungan dan Pemetaan Pendidik dan Tenaga Kependidikan Satuan SatuanPendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan ⟵ kembar

- **Penataan Pendistribusian Pendidik dan Tenaga Kependidikan bagi Satuan Pendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan** (#87) — pagu Rp 129.943.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #280, #282) — **2 indikator berteks sama**
    - PK #280 · Jumlah Laporan Hasil Pelaksanaan Penataan Pendistribusian Pendidik dan Tenaga Kependidikan SatuanPendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan ⟵ kembar
    - PK #282 · Jumlah Laporan Hasil Pelaksanaan Penataan Pendistribusian Pendidik dan Tenaga Kependidikan SatuanPendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan ⟵ kembar

### Dinas Perhubungan (id 17)

**Sub Kegiatan**

- **Penyediaan Bukti Lulus Uji Pengujian Berkala Kendaraan Bermotor** (#711) — pagu Rp 100.000.000 · **LEBIH 200%** (+Rp 100.000.000)  
  2 indikator dari 2 dokumen PK (Pengawas: #56, #84) — **2 indikator berteks sama**
    - PK #56 · Persentaqse kendaraan bermotor laik jalan ⟵ kembar
    - PK #84 · Persentaqse kendaraan bermotor laik jalan ⟵ kembar

- **Pemeliharaan Sarana dan Prasarana Pengujian Berkala Kendaraan Bermotor** (#712) — pagu Rp 12.750.000 · 41% terserap  
  2 indikator dari 2 dokumen PK (Pengawas: #56, #84) — **2 indikator berteks sama**
    - PK #56 · Persentaqse kendaraan bermotor laik jalan ⟵ kembar
    - PK #84 · Persentaqse kendaraan bermotor laik jalan ⟵ kembar

- **Pelaksanaan Inspeksi, Audit dan Pemantauan Sistem Manajemen Keselamatan Perusahaan Angkutan Umum** (#717) — pagu Rp 108.855.000 · 58% terserap  
  2 indikator dari 2 dokumen PK (Pengawas: #47, #50)
    - PK #47 · Volume / Capacity (VC) Ratio
    - PK #50 · Persentase titik parkir yang tidak menimbulkan kemacetan

- **Pengendalian dan Pengawasan Ketersediaan Angkutan Umum untuk Jasa Angkutan Orang dan/atau Barang Antar Kota dalam 1 (Satu) Kabupaten/Kota** (#718) — pagu Rp 6.324.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #52, #445) — **2 indikator berteks sama**
    - PK #52 · Persentase angkutan umum yang memenuhi perijinan dan tersertifikasi sistem manajemen keselamatan ⟵ kembar
    - PK #445 · Persentase angkutan umum yang memenuhi perijinan dan tersertifikasi sistem manajemen keselamatan ⟵ kembar

### Dinas Pertanian (id 21)

**Program**

- **PROGRAM PENYEDIAAN DAN PENGEMBANGAN SARANA PERTANIAN** (#107) — pagu Rp 512.689.000 · **LEBIH 129%** (+Rp 150.011.700)  
  2 indikator dari 1 dokumen PK (JPT: #131)
    - PK #131 · PDRB sektor pertanian
    - PK #131 · Indeks pertanaman

- **PROGRAM PENYEDIAAN DAN PENGEMBANGAN PRASARANA PERTANIAN** (#108) — pagu Rp 394.120.000 · 3% terserap  
  2 indikator dari 1 dokumen PK (JPT: #131)
    - PK #131 · PDRB sektor pertanian
    - PK #131 · Indeks pertanaman

- **PROGRAM PENGENDALIAN KESEHATAN HEWAN DAN KESEHATAN MASYARAKAT VETERINER** (#109) — pagu Rp 67.000.000 · **LEBIH 292%** (+Rp 128.648.822)  
  3 indikator dari 1 dokumen PK (JPT: #131)
    - PK #131 · PDRB sektor pertanian
    - PK #131 · Produksi daging
    - PK #131 · Produksi telur

- **PROGRAM PENGENDALIAN DAN PENANGGULANGAN BENCANA PERTANIAN** (#110) — pagu Rp 55.500.000 · 0% terserap  
  2 indikator dari 1 dokumen PK (JPT: #131)
    - PK #131 · PDRB sektor pertanian
    - PK #131 · Indeks pertanaman

- **PROGRAM PENYULUHAN PERTANIAN** (#111) — pagu Rp 242.846.000 · 59% terserap  
  2 indikator dari 1 dokumen PK (JPT: #131)
    - PK #131 · PDRB sektor pertanian
    - PK #131 · Indeks pertanaman

**Kegiatan**

- **Pengawasan Penggunaan Sarana Pertanian** (#275) — pagu Rp 336.699.000 · belum ada realisasi  
  6 indikator dari 3 dokumen PK (Administrator: #233, #236, #367) — **4 indikator berteks sama**
    - PK #233 · jumlah produksi kakao
    - PK #233 · Jumlah produksi tembakau
    - PK #236 · Jumlah produksi padi ⟵ kembar
    - PK #236 · Jumlah produksi jagung ⟵ kembar
    - PK #367 · Jumlah produksi padi ⟵ kembar
    - PK #367 · Jumlah produksi jagung ⟵ kembar

- **Pengelolaan Sumber Daya Genetik (SDG) Hewan, Tumbuhan, dan Mikro Organisme Kewenangan Kabupaten/Kota** (#276) — pagu Rp 122.990.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #152)
    - PK #152 · Jumlah produksi padi
    - PK #152 · Jumlah produksi jagung

- **Pengembangan Prasarana Pertanian** (#279) — pagu Rp 394.120.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Administrator: #233, #236) — **2 indikator berteks sama**
    - PK #233 · Rasio pembangunan prasarana pertanian ⟵ kembar
    - PK #236 · Rasio pembangunan prasarana pertanian ⟵ kembar

- **Penjaminan Kesehatan Hewan, Penutupan dan Pembukaan Daerah Wabah Penyakit Hewan Menular Dalam daerah Kabupaten/Kota** (#281) — pagu Rp 56.000.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #235)
    - PK #235 · jumlah pelayanan pengendalian penyakit hewan
    - PK #235 · jumlah Unit Usaha Pangan Asal Hewan yang Memiliki Sertifikat Pra NKV atau NKV (Nomor KontrolÂ Veteriner)

- **Pengawasan Pemasukan dan Pengeluaran Hewan dan Produk Hewan Daerah Kabupaten/Kota** (#282) — pagu Rp 11.000.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Administrator: #235)
    - PK #235 · jumlah ketersediaan daging
    - PK #235 · jumlah ketersediaan telur

- **Pengendalian dan Penanggulangan Bencana Pertanian Kabupaten/Kota** (#285) — pagu Rp 55.500.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Administrator: #152, #233)
    - PK #152 · jumlah luasan lahan yang mendapat pengendalian dan pananggulangan bencana pertanian
    - PK #233 · Rasio pembangunan prasarana pertanian

### Dinas Sosial (id 12)

**Kegiatan**

- **Pengelolaan Data Fakir Miskin Cakupan Daerah Kabupaten/Kota** (#90) — pagu Rp 163.920.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Administrator: #246, #247)
    - PK #246 · Persentase penduduk miskin dan rentan yang terdata dalam DT-SEN (Data Tunggal Sosial Ekonomi Nasional)
    - PK #247 · Persentase penduduk miskin dalam DT-SEN (Data Tunggal Sosial Ekonomi Nasional) yang mendapat bantuan perlindungan dan jaminan sosial

### Inspektorat (id 4)

**Program**

- **PROGRAM PERUMUSAN KEBIJAKAN, PENDAMPINGAN DAN ASISTENSI** (#139) — pagu Rp 0 · pagu belum diisi  
  2 indikator dari 1 dokumen PK (JPT: #337)
    - PK #337 · Nilai Maturnitas SPIP.
    - PK #337 · Indeks Integritas Nasional

**Kegiatan**

- **Monitoring dan Evaluasi Tindak Lanjut Hasil Pemeriksaan BPK RI dan Tindak Lanjut Hasil Pemeriksaan APIP** (#521) — pagu Rp 226.800.000 · **LEBIH 169%** (+Rp 155.700.000)  
  5 indikator dari 1 dokumen PK (Administrator: #394)
    - PK #394 · PPersentase rekomendasi hasil pengawasan yang ditindaklanjuti
    - PK #394 · Persentase dokumen perencanaan kinerja yang selaras dengan dokumen perencanaan pembangunan
    - PK #394 · Persentase indikator kinerja yang terukur dan memiliki
    - PK #394 · Persentase kesesuaian laporan kinerja dengan pedoman SAKIP
    - PK #394 · Persentase rekomendasi evaluasi kinerja yang ditindaklanjuti

- **Penyelenggaraan Pengawasan Internal** (#381) — pagu Rp 858.600.000 · **LEBIH 342%** (+Rp 2.074.050.000)  
  10 indikator dari 3 dokumen PK (Administrator: #390, #392, #393) — **6 indikator berteks sama**
    - PK #390 · Persentase Penyelenggaraan Assurance dan Consulting sesuai dengan PKPT ⟵ kembar
    - PK #390 · Persentase Hasil Pengawasan yang didukung dengan bukti yang dapat dipertanggungjawabkan ⟵ kembar
    - PK #390 · Persentase Pengawasan Terhadap Dokumen Perencanaan Kinerja yang selaras dengan dokumen perencanaan pembangunan
    - PK #390 · Persentase Pengawasan terhadap indikator kinerja yang terukur dan memiliki target yang jelas
    - PK #390 · Persentase Pengawasan terhadap kesesuaian laporan kinerja dengan pedoman SAKIP
    - PK #390 · Persentase Pengawasan terhadap rekomendasi evaluasi kinerja yang ditindaklanjuti
    - PK #392 · Persentase Penyelenggaraan Assurance dan Consulting sesuai dengan PKPT ⟵ kembar
    - PK #392 · Persentase Hasil Pengawasan yang didukung dengan bukti yang dapat dipertanggungjawabkan ⟵ kembar
    - PK #393 · Persentase Penyelenggaraan Assurance dan Consulting sesuai dengan PKPT ⟵ kembar
    - PK #393 · Persentase Hasil Pengawasan yang didukung dengan bukti yang dapat dipertanggungjawabkan ⟵ kembar

- **Penyelenggaraan Pengawasan dengan Tujuan Tertentu** (#382) — pagu Rp 210.600.000 · 58% terserap  
  2 indikator dari 1 dokumen PK (Administrator: #391)
    - PK #391 · Persentase Penyelenggaraan Assurance dan Consulting sesuai dengan PKPT
    - PK #391 · Persentase Penyelesaian Pengaduan Masyarakat

- **Perumusan Kebijakan Teknis di Bidang Pengawasan dan Fasilitasi Pengawasan** (#383) — pagu Rp 0 · pagu belum diisi  
  2 indikator dari 1 dokumen PK (Administrator: #391)
    - PK #391 · Persentase penerapan SPIP Level 3 pada Perangkat daerah
    - PK #391 · Nilai SPIP

- **Pendampingan dan Asistensi** (#384) — pagu Rp 0 · pagu belum diisi  
  20 indikator dari 5 dokumen PK (Administrator: #390, #391, #392, #393, #394) — **17 indikator berteks sama**
    - PK #390 · Persentase penerapan SPIP pada Perangkat daerah ⟵ kembar
    - PK #390 · Level Penilaian Mandiri SPIP Perangkat Daerah ⟵ kembar
    - PK #390 · Persentase Perangkat Daerah yang terbina menuju WBK/WBBM ⟵ kembar
    - PK #391 · Nilai McSP KPK
    - PK #392 · Persentase penerapan SPIP pada Perangkat daerah ⟵ kembar
    - PK #392 · Level Penilaian Mandiri SPIP Perangkat Daerah ⟵ kembar
    - PK #392 · Persentase Pengawasan Terhadap dokumen perencanaan kinerja yang selaras dengan dokumen perencanaan pembangunan ⟵ kembar
    - PK #392 · Persentase Pengawasan Terhadap indikator kinerja yang terukur dan memiliki target yang jelas ⟵ kembar
    - PK #392 · Persentase Pengawasan terhadap kesesuaian laporan kinerja dengan pedoman SAKIP ⟵ kembar
    - PK #392 · Persentase Pengawasan terhadap rekomendasi evaluasi kinerja yang ditindaklanjuti ⟵ kembar
    - PK #392 · Persentase Perangkat Daerah yang terbina menuju WBK/WBBM ⟵ kembar
    - PK #393 · Persentase penerapan SPIP pada Perangkat daerah ⟵ kembar
    - PK #393 · Level Penilaian Mandiri SPIP Perangkat Daerah ⟵ kembar
    - PK #393 · Persentase Pengawasan Terhadap Dokumen Perencanaan Kinerja yang selaras dengan dokumen perencanaan pembangunan ⟵ kembar
    - PK #393 · Persentase Pengawasan terhadap indikator kinerja yang terukur dan memiliki target yang jelas ⟵ kembar
    - PK #393 · Persentase Pengawasan terhadap kesesuaian laporan kinerja dengan pedoman SAKIP ⟵ kembar
    - PK #393 · Persentase Pengawasan terhadap rekomendasi evaluasi kinerja yang ditindaklanjuti ⟵ kembar
    - PK #393 · Persentase Perangkat Daerah yang terbina menuju WBK/WBBM ⟵ kembar
    - PK #394 · Persentase penerapan SPIP Level 3 pada Perangkat daerah
    - PK #394 · Nilai SPIP

**Sub Kegiatan**

- **Pendidikan dan Pelatihan Pegawai Berdasarkan Tugas dan Fungsi** (#1309) — pagu Rp 111.370.000 · **LEBIH 153%** (+Rp 59.174.156)  
  2 indikator dari 1 dokumen PK (Pengawas: #435)
    - PK #435 · Persentase APIP yang melakukan pendidikan dan pelatihan
    - PK #435 · Persentase PKS yang dilaksanakan oleh APIP yang telah melakukan pendidikan dan pelatihan

### Kecamatan Adiluwih (id 35)

**Program**

- **PROGRAM PENYELENGGARAAN PEMERINTAHAN DAN PELAYANAN PUBLIK** (#208) — pagu Rp 5.139.000 · 98% terserap  
  2 indikator dari 1 dokumen PK (Camat: #302)
    - PK #302 · Indeks Kepuasan Masyarakat
    - PK #302 · Indeks Kepuasan Masyarakat (IKM)

- **PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA** (#174) — pagu Rp 2.006.320.505 · **LEBIH 104%** (+Rp 76.757.407)  
  2 indikator dari 1 dokumen PK (Camat: #302)
    - PK #302 · Indeks Kepuasan Masyarakat
    - PK #302 · Indeks Kepuasan Masyarakat (IKM)

- **PROGRAM PEMBERDAYAAN MASYARAKAT DESA DAN KELURAHAN** (#176) — pagu Rp 5.000.000 · 47% terserap  
  2 indikator dari 1 dokumen PK (Camat: #302)
    - PK #302 · Indeks Kepuasan Masyarakat
    - PK #302 · "Meningkatnya pemberdayaan lembaga kemasyarakatan desa / kelurahan"

- **PROGRAM KOORDINASI KETENTRAMAN DAN KETERTIBAN UMUM** (#177) — pagu Rp 130.800.000 · 83% terserap  
  2 indikator dari 1 dokumen PK (Camat: #302)
    - PK #302 · Indeks Kepuasan Masyarakat
    - PK #302 · "Persentase Gangguan keamanan, ketentraman dan ketertiban masyarakat yang dapat diselesaikan "

**Sub Kegiatan**

- **Peningkatan Efektifitas Pelaksanaan Pelayanan kepada Masyarakat di Wilayah Kecamatan** (#1471) — pagu Rp 5.139.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #473, #492) — **2 indikator berteks sama**
    - PK #473 · Jumlah Laporan Fasilitasi Percepatan Pencapaian Standar Pelayanan Minimal di Wilayah Kecamatan ⟵ kembar
    - PK #492 · Jumlah Laporan Fasilitasi Percepatan Pencapaian Standar Pelayanan Minimal di Wilayah Kecamatan ⟵ kembar

### Kecamatan Pagelaran (id 37)

**Sub Kegiatan**

- **Penyusunan Dokumen Perencanaan Perangkat Daerah** (#1382) — pagu Rp 1.485.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #91, #271)
    - PK #91 · Nilai Evaluasi SAKIP
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran

- **Penyediaan Gaji dan Tunjangan ASN** (#1383) — pagu Rp 1.779.904.838 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #91, #271)
    - PK #91 · Prosentase Administrasi Keuangan Perangkat Daerah
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran

- **Pelaksanaan Penatausahaan dan\nPengujian/Verifikasi Keuangan SKPD** (#1384) — pagu Rp 100.820.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #91, #271)
    - PK #91 · Prosentase Administrasi Keuangan Perangkat Daerah
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran

- **Penyediaan Komponen Instalasi\nListrik/Penerangan Bangunan Kantor** (#1385) — pagu Rp 4.865.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyediaan Bahan Logistik Kantor** (#1387) — pagu Rp 42.115.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyediaan Barang Cetakan dan Penggandaan** (#1388) — pagu Rp 7.905.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyediaan Bahan Bacaan dan Peraturan Perundang-undangan** (#1389) — pagu Rp 15.815.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyelenggaraan Rapat Koordinasi dan Konsultasi SKPD** (#1390) — pagu Rp 5.025.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyediaan Jasa Komunikasi, Sumber Daya Air dan Listrik** (#1391) — pagu Rp 12.200.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Pemeliharaan Peralatan dan Mesin Lainnya** (#1394) — pagu Rp 7.920.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Pemeliharaan/Rehabilitasi Gedung Kantor dan Bangunan Lainnya** (#1395) — pagu Rp 4.050.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, dan Pajak Kendaraan Perorangan Dinas atau Kendaraan Dinas Jabatan** (#1442) — pagu Rp 41.070.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, Pajak dan Perizinan Kendaraan Dinas Operasional atau Lapangan** (#1493) — pagu Rp 3.200.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #271, #284)
    - PK #271 · Prosentase capaian Pelayanan Administrasi Perkantoran
    - PK #284 · Prosentase capaian Capaian Administrasi Umum

### Kecamatan Pardasuka (id 38)

**Program**

- **PROGRAM PEMBERDAYAAN MASYARAKAT DESA DAN KELURAHAN** (#194) — pagu Rp 4.510.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Camat: #350)
    - PK #350 · Indeks Pelayanan Publik Kecamatan Pardasuka
    - PK #350 · Prosentase Pembinaan dan Pengawasan pemerintahan desa/Kelurahan

- **PROGRAM KOORDINASI KETENTRAMAN DAN KETERTIBAN UMUM** (#195) — pagu Rp 178.800.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Camat: #350)
    - PK #350 · Indeks Pelayanan Publik Kecamatan Pardasuka
    - PK #350 · "Persentase Gangguan keamanan, ketentraman dan ketertiban masyarakat yang dapat diselesaikan "

- **PROGRAM PEMBINAAN DAN PENGAWASAN PEMERINTAHAN DESA** (#197) — pagu Rp 3.000.000 · belum ada realisasi  
  2 indikator dari 1 dokumen PK (Camat: #350)
    - PK #350 · Indeks Pelayanan Publik Kecamatan Pardasuka
    - PK #350 · Prosentase lembaga kemasyarakatan yang difasilitasi

**Sub Kegiatan**

- **Pembinaan Kerukunan Antar Suku dan Intra Suku, Umat Beragama, Ras, dan Golongan Lainnya Guna Mewujudkan Stabilitas Keamanan Lokal, Regional, dan Nasional** (#1553) — pagu Rp 6.900.000 · belum ada realisasi  
  3 indikator dari 3 dokumen PK (Pengawas: #467, #468, #476) — **3 indikator berteks sama**
    - PK #467 · Jumlah Kehadiran Peserta Pembinaan Kerukunan Anatr Suku dan Intra Suku,Umat Beragama, Ras dan Golongan Lainnya Guna mewujudkan stabilitas Keamanan lokal, Regional dan Nasional ⟵ kembar
    - PK #468 · Jumlah Kehadiran Peserta Pembinaan Kerukunan Anatr Suku dan Intra Suku,Umat Beragama, Ras dan Golongan Lainnya Guna mewujudkan stabilitas Keamanan lokal, Regional dan Nasional ⟵ kembar
    - PK #476 · Jumlah Kehadiran Peserta Pembinaan Kerukunan Anatr Suku dan Intra Suku,Umat Beragama, Ras dan Golongan Lainnya Guna mewujudkan stabilitas Keamanan lokal, Regional dan Nasional ⟵ kembar

### Kecamatan Pringsewu (id 31)

**Sub Kegiatan**

- **Fasilitasi Penyediaan Sarana dan Prasarana Kelembagaan Lembaga Kemasyarakatan Desa/Kelurahan (RT, RW, PKK, Posyandu, LPM, dan Karang Taruna), Lembaga Adat Desa/Kelurahan dan Masyarakat Hukum Adat** (#662) — pagu Rp 0 · pagu belum diisi  
  3 indikator dari 3 dokumen PK (Pengawas: #385, #386, #387) — **3 indikator berteks sama**
    - PK #385 · Prosentase pencapaian program penyelenggaraan pemerintahan dan pelayanan public ⟵ kembar
    - PK #386 · Prosentase pencapaian program penyelenggaraan pemerintahan dan pelayanan public ⟵ kembar
    - PK #387 · Prosentase pencapaian program penyelenggaraan pemerintahan dan pelayanan public ⟵ kembar

- **Peningkatan Partisipasi Masyarakat dalam Forum Musyawarah Perencanaan Pembangunan di Desa** (#1343) — pagu Rp 10.000.000 · belum ada realisasi  
  6 indikator dari 6 dokumen PK (Pengawas: #324, #325, #326, #329, #330, #388) — **5 indikator berteks sama**
    - PK #324 · Persentase tingkat kepuasan masyarakat dan partisipasi masyarakat dalam forum musyawarah perencanaan pembangunan di Desa/Kelurahan ⟵ kembar
    - PK #325 · Persentase tingkat kepuasan masyarakat dan partisipasi masyarakat dalam forum musyawarah perencanaan pembangunan di Desa/Kelurahan ⟵ kembar
    - PK #326 · Persentase tingkat kepuasan masyarakat dan partisipasi masyarakat dalam forum musyawarah perencanaan pembangunan di Desa/Kelurahan ⟵ kembar
    - PK #329 · Persentase tingkat kepuasan masyarakat dan partisipasi masyarakat dalam forum musyawarah perencanaan pembangunan di Desa/Kelurahan ⟵ kembar
    - PK #330 · Persentase tingkat kepuasan masyarakat dan partisipasi masyarakat dalam forum musyawarah perencanaan pembangunan di Desa/Kelurahan ⟵ kembar
    - PK #388 · Indeks Kepuasan Masyarakat Kecamatan Pringsewu

- **Sinergitas dengan Kepolisian Negara Republik Indonesia, Tentara Nasional Indonesia dan Instansi Vertikal di Wilayah Kecamatan** (#1345) — pagu Rp 198.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #326, #513)
    - PK #326 · Prosentase Keamanan dan Ketertiban di setiap pekon/Kelurahan
    - PK #513 · Persentase Keamanan dan Ketertiban disetiap Pekon/ Kelurahan

- **Pembangunan Sarana dan Prasarana Kelurahan** (#1348) — pagu Rp 200.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #331) — **2 indikator berteks sama**
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan ⟵ kembar
    - PK #331 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan ⟵ kembar

- **Pemberdayaan Masyarakat di Kelurahan** (#1349) — pagu Rp 423.320.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #331) — **2 indikator berteks sama**
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan ⟵ kembar
    - PK #331 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan ⟵ kembar

- **Pembangunan Sarana dan Prasarana Kelurahan** (#1350) — pagu Rp 200.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #332)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #332 · Prosentase Pemberdayaan Masyarakat Desa dan Kelurahan

- **Pemberdayaan Masyarakat di Kelurahan** (#1351) — pagu Rp 543.321.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #332)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #332 · Prosentase Pemberdayaan Masyarakat Desa dan Kelurahan

- **Pembangunan Sarana dan Prasarana Kelurahan** (#1352) — pagu Rp 200.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #333)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #333 · Prosentase Pemberdayaan Masyarakat Desa dan Kelurahan

- **Pemberdayaan Masyarakat di Kelurahan** (#1353) — pagu Rp 615.320.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #333)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #333 · Prosentase Pemberdayaan Masyarakat Desa dan Kelurahan

- **Pembangunan Sarana dan Prasarana Kelurahan** (#1354) — pagu Rp 200.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #334)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #334 · Meningkatnya Pemberdayaan Dan Pemerintahan Di Desa/Kelurahan Serta Meningkatkan Sarana Prasarana Yang Memadai

- **Pemberdayaan Masyarakat di Kelurahan** (#1355) — pagu Rp 471.320.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #334)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #334 · Meningkatnya Pemberdayaan Dan Pemerintahan Di Desa/Kelurahan Serta Meningkatkan Sarana Prasarana Yang Memadai

- **Pembangunan Sarana dan Prasarana Kelurahan** (#1356) — pagu Rp 200.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #335)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #335 · Meningkatnya Pemberdayaan Dan Pemerintahan Di Desa/Kelurahan Serta Meningkatkan Sarana Prasarana Yang Memadai

- **Pemberdayaan Masyarakat di Kelurahan** (#1357) — pagu Rp 309.320.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #325, #335)
    - PK #325 · Prosentase Pemberdaya an Masyarakat Desa dan Kelurahan
    - PK #335 · Meningkatnya Pemberdayaan Dan Pemerintahan Di Desa/Kelurahan Serta Meningkatkan Sarana Prasarana Yang Memadai

### Kecamatan Sukoharjo (id 34)

**Program**

- **PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA** (#168) — pagu Rp 1.671.635.727 · 40% terserap  
  2 indikator dari 2 dokumen PK (Camat, JPT: #211, #490) — **2 indikator berteks sama**
    - PK #211 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #490 · Nilai Evaluasi SAKIP ⟵ kembar

- **PROGRAM PENYELENGGARAAN PEMERINTAHAN DAN PELAYANAN PUBLIK** (#169) — pagu Rp 8.040.000 · **LEBIH 400%** (+Rp 24.120.000)  
  4 indikator dari 2 dokumen PK (Camat, JPT: #211, #490) — **4 indikator berteks sama**
    - PK #211 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #211 · Prosentase Kepuasan Masyarakat Terhadap Pelayanan Publik ⟵ kembar
    - PK #490 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #490 · Prosentase Kepuasan Masyarakat Terhadap Pelayanan Publik ⟵ kembar

- **PROGRAM KOORDINASI KETENTRAMAN DAN KETERTIBAN UMUM** (#171) — pagu Rp 145.200.000 · **LEBIH 161%** (+Rp 88.800.000)  
  4 indikator dari 2 dokumen PK (Camat, JPT: #211, #490) — **4 indikator berteks sama**
    - PK #211 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #211 · Prosentase Gangguan Keamanan, Ketentraman dan Ketertiban Masyarakat yang dapat diselesaikan ⟵ kembar
    - PK #490 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #490 · Prosentase Gangguan Keamanan, Ketentraman dan Ketertiban Masyarakat yang dapat diselesaikan ⟵ kembar

- **PROGRAM PENYELENGGARAAN URUSAN PEMERINTAHAN UMUM** (#172) — pagu Rp 240.000 · **LEBIH 400%** (+Rp 720.000)  
  4 indikator dari 2 dokumen PK (Camat, JPT: #211, #490) — **4 indikator berteks sama**
    - PK #211 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #211 · Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum ⟵ kembar
    - PK #490 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #490 · Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum ⟵ kembar

- **PROGRAM PEMBINAAN DAN PENGAWASAN PEMERINTAHAN DESA** (#173) — pagu Rp 1.680.000 · **LEBIH 400%** (+Rp 5.040.000)  
  4 indikator dari 2 dokumen PK (Camat, JPT: #211, #490) — **4 indikator berteks sama**
    - PK #211 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #211 · Prosentase Peningkatan Kualitas Tata Kelola Pemerintahan Berdasarkan Hasil Pembinaan dan Pengawasan ⟵ kembar
    - PK #490 · Nilai Evaluasi SAKIP ⟵ kembar
    - PK #490 · Prosentase Peningkatan Kualitas Tata Kelola Pemerintahan Berdasarkan Hasil Pembinaan dan Pengawasan ⟵ kembar

**Kegiatan**

- **Koordinasi Upaya Penyelenggaraan Ketenteraman dan Ketertiban Umum** (#442) — pagu Rp 145.200.000 · 81% terserap  
  2 indikator dari 1 dokumen PK (Administrator: #213)
    - PK #213 · Nilai Evaluasi SAKIP
    - PK #213 · Prosentase Gangguan Keamanan, Ketentraman dan Ketertiban Masyarakat yang dapat diselesaikan

- **Penyelenggaraan Urusan Pemerintahan Umum Sesuai Penugasan Kepala Daerah** (#443) — pagu Rp 240.000 · **LEBIH 200%** (+Rp 240.000)  
  2 indikator dari 1 dokumen PK (Administrator: #213)
    - PK #213 · Nilai Evaluasi SAKIP
    - PK #213 · Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum

- **Fasilitasi, Rekomendasi dan Koordinasi\nPembinaan dan Pengawasan Pemerintahan Desa** (#444) — pagu Rp 1.680.000 · **LEBIH 200%** (+Rp 1.680.000)  
  2 indikator dari 1 dokumen PK (Administrator: #213)
    - PK #213 · Nilai Evaluasi SAKIP
    - PK #213 · Prosentase Peningkatan Kualitas Tata Kelola Pemerintahan Berdasarkan Hasil Pembinaan dan Pengawasan

**Sub Kegiatan**

- **Penyediaan Bahan Logistik Kantor** (#1436) — pagu Rp 24.024.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyediaan Barang Cetakan dan Penggandaan** (#1437) — pagu Rp 3.042.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyediaan Bahan Bacaan dan Peraturan Perundang-undangan** (#1438) — pagu Rp 12.000.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyelenggaraan Rapat Koordinasi dan Konsultasi SKPD** (#1439) — pagu Rp 16.910.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyediaan Jasa Komunikasi, Sumber Daya Air dan Listrik** (#1440) — pagu Rp 13.272.700 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyediaan Jasa Peralatan dan Perlengkapan Kantor** (#1441) — pagu Rp 19.815.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, dan Pajak Kendaraan Perorangan Dinas atau Kendaraan Dinas Jabatan** (#1442) — pagu Rp 41.070.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, Pajak dan Perizinan Kendaraan Dinas Operasional atau Lapangan** (#1443) — pagu Rp 2.700.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #239, #240)
    - PK #239 · Prosentase Capaian Administrasi Umum
    - PK #240 · Cakupan Dokumen Perencanaan dan Administrasi Keuangan

- **Koordinasi/Sinergi Perencanaan dan Pelaksanaan Kegiatan Pemerintahan dengan Perangkat Daerah dan Instansi Vertikal Terkait** (#1445) — pagu Rp 8.040.000 · belum ada realisasi  
  3 indikator dari 3 dokumen PK (Pengawas: #238, #239, #240) — **3 indikator berteks sama**
    - PK #238 · Prosentase Kepuasan Masyarakat Terhadap Pelayanan Publik ⟵ kembar
    - PK #239 · Prosentase Kepuasan Masyarakat Terhadap Pelayanan Publik ⟵ kembar
    - PK #240 · Prosentase Kepuasan Masyarakat Terhadap Pelayanan Publik ⟵ kembar

- **Pembinaan Kerukunan Antar Suku dan Intra Suku, Umat Beragama, Ras, dan Golongan Lainnya Guna Mewujudkan Stabilitas Keamanan Lokal, Regional, dan Nasional** (#1450) — pagu Rp 240.000 · belum ada realisasi  
  3 indikator dari 3 dokumen PK (Pengawas: #238, #239, #240) — **2 indikator berteks sama**
    - PK #238 · Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum ⟵ kembar
    - PK #239 · Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum ⟵ kembar
    - PK #240 · Prosentase Sasaran Penyelengaraan Urusan Pemerintahan Umum

- **Fasilitasi Pengelolaan Keuangan Desa dan Pendayagunaan Aset Desa** (#1451) — pagu Rp 1.680.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #238, #508)
    - PK #238 · Nilai Evaluasi SAKIP
    - PK #508 · Prosentase Fasilitasi Pengelolaan Keuangan Desa dan Pendayagunaan Aset Desa

### Satuan Polisi Pamong Praja (id 9)

**Program**

- **PROGRAM PENINGKATAN KETENTERAMAN DAN KETERTIBAN UMUM** (#27) — pagu Rp 932.668.500 · **LEBIH 351%** (+Rp 2.340.467.090)  
  5 indikator dari 2 dokumen PK (JPT: #358, #421)
    - PK #358 · 1.\tPersentase Perda dan Perkada yang ditegakkan
    - PK #358 · 2.\tPersentase Penyelenggaraan Tibumtranmas
    - PK #358 · 3. Persentase Cakupan Perlindungan Masyarakat
    - PK #358 · 4. Persentase PPNS yang ditingkatkan Kompetensinya
    - PK #421 · 1.\tINDEKS PENYELENGGARAAN KETENTRAMAN DAN KETERTIBAN UMUM (IPKKU)

- **PROGRAM PENUNJANG URUSAN PEMERINTAH DAERAH KABUPATEN/KOTA** (#210) — pagu Rp 16.045.284.428 · **LEBIH 151%** (+Rp 8.241.699.950)  
  2 indikator dari 2 dokumen PK (JPT: #358, #421)
    - PK #358 · 4. Persentase PPNS yang ditingkatkan Kompetensinya
    - PK #421 · 1.\tINDEKS PENYELENGGARAAN KETENTRAMAN DAN KETERTIBAN UMUM (IPKKU)

**Kegiatan**

- **Penanganan Gangguan Ketenteraman dan Ketertiban Umum dalam 1 (Satu) Daerah Kabupaten/Kota** (#544 · KEG-6a4f494948196) — pagu Rp 889.259.500 · 85% terserap  
  2 indikator dari 2 dokumen PK (Administrator: #424, #425)
    - PK #424 · Rasio linmas aktif teradap jumlah penduduk
    - PK #425 · Jumlah Gangguan Ketentraman Dan Ketertiban Umum

### Sekretariat Daerah (id 2)

**Program**

- **PROGRAM PEREKONOMIAN DAN PEMBANGUNAN** (#205 · 1769053630) — pagu Rp 478.163.000 · 44% terserap  
  2 indikator dari 2 dokumen PK (JPT: #6, #102)
    - PK #6 · Indeks Reformasi Birokrasi
    - PK #102 · Indeks Reformasi Birokrasi Tematik

- **PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA** (#119) — pagu Rp 32.497.065.043 · **LEBIH 182%** (+Rp 26.738.469.257)  
  8 indikator dari 5 dokumen PK (JPT: #6, #19, #102, #148, #344) — **6 indikator berteks sama**
    - PK #6 · Indeks Reformasi Birokrasi
    - PK #19 · Survey Kepuasan Layanan Internal Sekretariat Daerah ⟵ kembar
    - PK #102 · Indeks Reformasi Birokrasi General ⟵ kembar
    - PK #148 · Indeks Reformasi Birokrasi General ⟵ kembar
    - PK #148 · Survey Kepuasan Layanan Internal Sekretariat Daerah ⟵ kembar
    - PK #148 · Indeks Reformasi Birokrasi Tematik
    - PK #344 · Indeks Reformasi Birokrasi General ⟵ kembar
    - PK #344 · Survey Kepuasan Layanan Internal Sekretariat Daerah ⟵ kembar

- **PROGRAM PEMERINTAHAN DAN KESEJAHTERAAN RAKYAT** (#120) — pagu Rp 3.676.023.300 · 79% terserap  
  2 indikator dari 2 dokumen PK (JPT: #6, #99)
    - PK #6 · Indeks Reformasi Birokrasi
    - PK #99 · Indeks Reformasi Birokrasi General

- **PROGRAM PEREKONOMIAN DAN PEMBANGUNAN** (#122) — pagu Rp 964.940.000 · 50% terserap  
  3 indikator dari 2 dokumen PK (JPT: #6, #102)
    - PK #6 · Indeks Reformasi Birokrasi
    - PK #102 · Indeks Reformasi Birokrasi Tematik
    - PK #102 · Survey Kepuasan Layanan Internal Sekretariat Daerah

**Kegiatan**

- **Penataan Organisasi** (#304) — pagu Rp 360.815.500 · 28% terserap  
  5 indikator dari 1 dokumen PK (Administrator: #231)
    - PK #231 · Nilai Akuntabilitas Kinerja Internal Pemerintah (AKIP)
    - PK #231 · Indeks Pelayanan Publik
    - PK #231 · Tingkat Kepatuhan Pelayanan Publik
    - PK #231 · Nilai Kebutuhan Formasi Jabatan dalam Organisasi
    - PK #231 · Tingkat Maturitas SPIP

**Sub Kegiatan**

- **Fasilitasi Keprotokolan** (#995) — pagu Rp 152.973.000 · belum ada realisasi  
  2 indikator dari 2 dokumen PK (Pengawas: #15, #402) — **2 indikator berteks sama**
    - PK #15 · Jumlah Laporan Kegiatan Protokol dan Komunikasi Pimpinan yang dilaksanakan ⟵ kembar
    - PK #402 · Jumlah Laporan Kegiatan Protokol dan Komunikasi Pimpinan yang dilaksanakan ⟵ kembar

## 4. Catatan Data

- Jumlah indikator yang besar pada satu **kegiatan payung** (mis. "Penyediaan Layanan Kesehatan untuk UKM dan UKP" di Dinas Kesehatan, 24 indikator dari 3 PK Administrator) bukan kesalahan — itu memang satu kegiatan yang menampung banyak indikator program. Yang perlu dicermati bukan jumlahnya, melainkan **pembagian realisasi anggarannya di MONEV**: tiap indikator mengisi bagiannya, dan jumlah seluruh bagian tidak boleh melampaui pagu.
- Kode program/kegiatan di tabel master tidak seragam (208 program dan 489 kegiatan hanya berkode satu digit), sehingga laporan ini memakai **id** sebagai rujukan pasti.
- 13 baris teks tersimpan dengan pengkodean rusak (tampil seperti `â€œutamaâ€`): 1 di `pk_indikator` (id 1935, Dinas Kesehatan), 11 di `target_sub_rencana`, 1 di `iku_indikator`. Datanya memang tersimpan begitu, bukan salah tampil. Tidak diubah.

## 5. Cara Angka Ini Diperoleh

Kueri `SELECT` langsung ke `pk_program` → `pk_kegiatan` → `pk_subkegiatan` (rantai jembatan PK) digabung ke `pk_indikator` → `pk_sasaran` → `pk`, dikelompokkan per (OPD, tahun, tingkat, id unit), disaring `COUNT(DISTINCT pk_indikator.id) >= 2` dan jenis PK sesuai tabel di atas. Baris `program_id = 0` (rujukan kosong) diabaikan. Realisasi diambil dari `monev_anggaran` dengan lingkup yang sama, dijumlahkan lintas indikator — persis seperti `AnggaranUnitService` di aplikasi. Tidak ada data yang diubah.

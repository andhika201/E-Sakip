# Dokumentasi API e-SAKIP

API ini menyediakan data read-only untuk perangkat daerah, IKU, cascading, pohon kinerja, serta Target & Rencana Aksi perangkat daerah.

## Swagger UI

Dokumentasi interaktif tersedia di:

```text
/api-docs
```

Spesifikasi OpenAPI tersedia di:

```text
/openapi.json
```

Pada halaman Swagger UI, klik tombol **Authorize**, masukkan API token, lalu jalankan endpoint yang dibutuhkan.

## Autentikasi

Semua endpoint dengan prefix `/api` wajib memakai API token.

Token dikirim melalui salah satu header berikut:

```http
api-token: ISI_API_TOKEN
```

atau:

```http
X-API-Token: ISI_API_TOKEN
```

atau:

```http
Authorization: Bearer ISI_API_TOKEN
```

Jika token tidak dikirim atau salah, API mengembalikan:

```json
{
  "status": "error",
  "message": "API token tidak valid."
}
```

## Format Response

Response sukses memakai format:

```json
{
  "status": "success",
  "meta": {},
  "data": []
}
```

Response error memakai format:

```json
{
  "status": "error",
  "message": "Pesan error"
}
```

## Filter Umum

### Filter Periode

Endpoint IKU, cascading, dan pohon kinerja mendukung filter periode.

Format utama:

```text
periode=2025-2029
```

Alternatif:

```text
tahun_mulai=2025&tahun_akhir=2029
```

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah/20/iku?periode=2025-2029"
```

Catatan:

- Untuk cascading dan pohon kinerja, jika periode tidak dikirim maka API memakai periode Renstra terbaru perangkat daerah.
- Untuk IKU, jika periode tidak dikirim maka API menampilkan semua periode yang tersedia.
- Daftar periode yang tersedia dikembalikan di `meta.available_periods`.

## Endpoint

### 1. Daftar Perangkat Daerah

```http
GET /api/perangkat-daerah
```

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah"
```

Response `data`:

```json
[
  {
    "id": 20,
    "nama_opd": "DINAS KOMUNIKASI DAN INFORMATIKA",
    "singkatan": null
  }
]
```

### 2. Detail Perangkat Daerah

```http
GET /api/perangkat-daerah/{opd_id}
```

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah/20"
```

### 3. IKU Perangkat Daerah

```http
GET /api/perangkat-daerah/{opd_id}/iku
```

Alias:

```http
GET /api/iku?opd_id={opd_id}
```

Filter:

| Parameter | Keterangan | Contoh |
| --- | --- | --- |
| `periode` | Filter periode Renstra | `2025-2029` |
| `tahun_mulai` | Tahun awal periode | `2025` |
| `tahun_akhir` | Tahun akhir periode | `2029` |
| `status` | Filter status IKU. Default `selesai` | `selesai`, `all`, `draft`, `belum`, `tercapai` |

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah/20/iku?periode=2025-2029&status=selesai"
```

Response `data`:

```json
[
  {
    "id": 52,
    "renstra_id": 126,
    "definisi": "Definisi IKU",
    "status": "selesai",
    "opd": {
      "id": 20,
      "nama_opd": "DINAS KOMUNIKASI DAN INFORMATIKA",
      "singkatan": null
    },
    "sasaran": {
      "id": 66,
      "nama": "Meningkatnya kualitas layanan aplikasi informatika pemerintah daerah"
    },
    "indikator": {
      "id": 126,
      "nama": "Indeks SPBE",
      "satuan": "19"
    },
    "periode": {
      "tahun_mulai": 2025,
      "tahun_akhir": 2029
    },
    "target_tahunan": {
      "2025": "3,05",
      "2026": "3,3"
    },
    "program_pendukung": [
      {
        "id": 80,
        "program": "Program Pengelolaan Aplikasi Informatika"
      }
    ],
    "created_at": "2026-01-15 15:00:12",
    "updated_at": "2026-04-13 10:45:58"
  }
]
```

### 4. Cascading Perangkat Daerah

```http
GET /api/perangkat-daerah/{opd_id}/cascading
```

Alias:

```http
GET /api/cascading?opd_id={opd_id}
```

Filter:

| Parameter | Keterangan | Contoh |
| --- | --- | --- |
| `periode` | Filter periode Renstra | `2025-2029` |
| `tahun_mulai` | Tahun awal periode | `2025` |
| `tahun_akhir` | Tahun akhir periode | `2029` |

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah/20/cascading?periode=2025-2029"
```

Response `data` berisi baris matriks cascading:

```json
[
  {
    "tujuan_id": 10,
    "tujuan_rpjmd": "Terwujudnya Tata Kelola Pemerintahan yang Profesional, Modern dan Inovatif",
    "sasaran_id": 12,
    "sasaran_rpjmd": "Meningkatnya Kualitas Tata Kelola Pemerintahan",
    "renstra_tujuan_id": 29,
    "renstra_tujuan": "Tujuan Renstra",
    "renstra_sasaran_id": 66,
    "renstra_sasaran": "Sasaran Renstra",
    "indikator_id": 126,
    "indikator_sasaran": "Indeks SPBE",
    "satuan": "19",
    "es3_id": 44,
    "es3_sasaran": "Sasaran ES.III",
    "es3_indikator_id": 45,
    "es3_indikator": "Indikator ES.III",
    "es4_id": null,
    "es4_sasaran": null,
    "es4_indikator_id": null,
    "es4_indikator": null
  }
]
```

### 5. Pohon Kinerja Perangkat Daerah

```http
GET /api/perangkat-daerah/{opd_id}/pohon-kinerja
```

Alias:

```http
GET /api/pohon-kinerja?opd_id={opd_id}
```

Filter:

| Parameter | Keterangan | Contoh |
| --- | --- | --- |
| `periode` | Filter periode Renstra | `2025-2029` |
| `tahun_mulai` | Tahun awal periode | `2025` |
| `tahun_akhir` | Tahun akhir periode | `2029` |

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah/20/pohon-kinerja?periode=2025-2029"
```

Response `data` berbentuk tree:

```json
[
  {
    "id": 10,
    "nama": "Tujuan RPJMD",
    "sasaran": [
      {
        "id": 12,
        "nama": "Sasaran RPJMD",
        "tujuan_renstra": [
          {
            "id": 29,
            "nama": "Tujuan Renstra",
            "es2": [
              {
                "id": 66,
                "nama": "Sasaran ES.II",
                "csf": null,
                "indikator": [],
                "es3": []
              }
            ]
          }
        ]
      }
    ]
  }
]
```

### 6. Target & Rencana Aksi Perangkat Daerah

Isinya sama dengan tabel **Target dan Rencana Aksi** PK OPD/Kecamatan (Eselon II/III/IV) di web:
Sasaran → Indikator → unit anggaran → Rencana Aksi → Sub Rencana Aksi → Target Triwulan I–IV → Penanggung Jawab.

```http
GET /api/perangkat-daerah/{opd_id}/target-renaksi
```

Alias (tanpa `opd_id` hasilnya lintas perangkat daerah, seperti tampilan admin kabupaten):

```http
GET /api/target-renaksi?opd_id={opd_id}
```

Filter:

| Parameter | Keterangan | Contoh |
| --- | --- | --- |
| `tahun` | Filter tahun PK. Kosong atau `all` = semua tahun | `2026` |
| `opd_id` | Batasi pada satu perangkat daerah | `20` |
| `eselon` | `jpt` (Eselon II), `administrator` (Eselon III, alias `camat`/`kecamatan`), `pengawas` (Eselon IV) | `jpt` |
| `pejabat_id` | Pejabat penandatangan PK (`pk.pihak_1`) | `151` |

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/perangkat-daerah/20/target-renaksi?tahun=2026&eselon=jpt"
```

`data` dikelompokkan per Sasaran PK — satu elemen = satu blok bernomor pada kolom **No** di web:

```json
[
  {
    "no": 1,
    "pk_sasaran_id": 1896,
    "pk_id": 453,
    "sasaran": "Meningkatnya transformasi layanan berbasis digital ...",
    "opd": {
      "id": 20,
      "nama_opd": "Dinas Komunikasi Dan Informatika"
    },
    "pejabat": {
      "id": 151,
      "nama": "MOUDY ARY NAZOLLA, S.STP.MH",
      "jabatan": "Kepala Dinas Komunikasi Dan Informatika",
      "eselon": "Eselon II",
      "jenis": "jpt"
    },
    "indikator": [
      {
        "pk_indikator_id": 2321,
        "indikator": "Indeks Pemerintah Digital",
        "tahun": "2026",
        "satuan": "Indeks",
        "target": "1,4",
        "unit": [
          {
            "level": "program",
            "level_label": "Program",
            "ref_key": "program:74",
            "kode": "2",
            "nama": "PROGRAM PENGELOLAAN APLIKASI INFORMATIKA",
            "anggaran": 1062961500,
            "anggaran_format": "Rp 1.062.961.500",
            "tahun_anggaran": "2026",
            "fallback": false
          }
        ],
        "total_anggaran": 6702706507,
        "total_anggaran_format": "Rp 6.702.706.507",
        "target_id": 426,
        "punya_renaksi": true,
        "rencana_aksi": [
          {
            "no": 1,
            "uraian": "Penyusunan rencana kerja Pemerintah Digital ...",
            "sub_rencana_aksi": [
              {
                "id": 27,
                "no": 1,
                "uraian": "Identifikasi kebutuhan perangkat daerah",
                "target_triwulan": {
                  "1": "10",
                  "2": "20",
                  "3": null,
                  "4": "40"
                }
              }
            ]
          }
        ],
        "target_triwulan": {
          "1": "0",
          "2": "0",
          "3": "0",
          "4": "1,4"
        },
        "penanggung_jawab": "Kepala Dinas Komunikasi Dan Informatika"
      }
    ]
  }
]
```

Catatan:

- Kolom **unit** mengikuti jenis PK, sama seperti di web: Program (`jpt`, `camat`), Kegiatan (`administrator`), Sub Kegiatan (`pengawas`). Judul kolomnya dikirim di `meta.label_kolom_unit`.
- `unit[].fallback = true` berarti tingkat aslinya kosong sehingga isinya diambil dari tingkat di atasnya (di web ditandai badge kuning).
- `rencana_aksi` memecah teks multi-baris `target_rencana.rencana_aksi` menjadi butir; **Target Triwulan yang tampil di layar** adalah milik `sub_rencana_aksi`. `target_triwulan` pada level indikator adalah nilai rekaman `target_rencana` itu sendiri.
- `target_id: null` / `punya_renaksi: false` = indikator PK yang belum diturunkan jadi Rencana Aksi (di web tampil tombol **Tambah**).
- `meta.summary` sama dengan kartu ringkasan di atas tabel, dan `meta.available_years` sama dengan isi dropdown Tahun.

### 7. Target & Rencana Aksi PK Bupati

```http
GET /api/target-renaksi/bupati
```

Filter:

| Parameter | Keterangan | Contoh |
| --- | --- | --- |
| `tahun` | Filter tahun PK. Kosong atau `all` = semua tahun | `2026` |

Contoh:

```bash
curl -H "api-token: ISI_API_TOKEN" \
  "http://127.0.0.1:8080/api/target-renaksi/bupati?tahun=2026"
```

Tabel PK Bupati di web memang hanya menampilkan Sasaran, Indikator, Tahun, Satuan, Target, dan Perangkat Daerah Pendukung, jadi response-nya pun berhenti di situ:

```json
[
  {
    "no": 1,
    "pk_sasaran_id": 1354,
    "pk_id": 364,
    "sasaran": "Meningkatnya kualitas Pendidikan",
    "indikator": [
      {
        "pk_indikator_id": 1644,
        "indikator": "Indeks  Pendidikan",
        "tahun": "2026",
        "satuan": "Nilai",
        "target": "0,654"
      }
    ],
    "perangkat_daerah_pendukung": {
      "sumber": "manual",
      "opd": [
        {
          "id": 10,
          "nama": "Dinas Pendidikan Dan Kebudayaan"
        }
      ]
    }
  }
]
```

Catatan:

- `perangkat_daerah_pendukung.sumber` bernilai `manual` (ditetapkan admin lewat tombol Aksi), `otomatis` (hasil pencocokan sasaran PK ke rantai cascading), atau `null` bila belum ditetapkan. Penetapan manual mengalahkan hasil otomatis, sama seperti di web.

## Kode Status HTTP

| Status | Keterangan |
| --- | --- |
| `200` | Berhasil |
| `400` | Parameter tidak valid atau kurang |
| `401` | API token tidak valid |
| `404` | Perangkat daerah atau periode tidak ditemukan |
| `500` | Konfigurasi server belum lengkap |

## API eKin (token terpisah)

Empat endpoint GET di bawah prefix `/api/ekin` melayani **eKin Internal Pringsewu** (SKP, Rencana Aksi, kinerja harian). eKin **menarik** data dari AKSARA; tidak ada endpoint tulis.

### Token

- Endpoint `/api/ekin/*` memakai **token khusus eKin**: variabel `.env` `EKIN_API_TOKEN` (filter `api-token:ekin`). Token API umum (`API_TOKEN`) **ditolak** di sini, dan token eKin ditolak di endpoint API umum — sehingga token eKin dapat dicabut tanpa memutus konsumen lain.
- Header pengiriman sama seperti API umum: `api-token`, `X-API-Token`, atau `Authorization: Bearer`.
- Bila `EKIN_API_TOKEN` belum diisi, semua endpoint eKin menjawab `500` "API token belum dikonfigurasi."

### Data pribadi

Endpoint ini memuat nama dan NIP pegawai. Kolom yang **tidak pernah** dikirim: kata sandi, tanggal lahir, tukin, data perangkat (device), nomor WhatsApp, dan kolom presensi lainnya. Simpan token hanya di server eKin (`.env`), jangan di peramban.

### Peta alias OPD

Sebagian pegawai tercatat di kode OPD lama. API memetakannya ke OPD **efektif** (yang dipakai akun, pohon kinerja, PK, dan IKP):

| Kode OPD pegawai | OPD efektif |
| --- | --- |
| `210` | `8` (BKPSDM) |
| `213` | `32` (Kec. Gadingrejo) |
| `13` | `211` (DP3AP2KB) |

`GET /api/ekin/pegawai?opd_id=8` karenanya mengembalikan pegawai ber-`opd_id` 8 **dan** 210; tiap baris membawa `opd_id` (asal) dan `opd_id_efektif`. Id alias (`13`, `210`, `213`) tidak muncul di daftar OPD.

### Parameter `tahun`

Semua endpoint menerima `?tahun=YYYY` (2000–2100). Kosong = tahun berjalan. Nilai lain → `400`.

### 8. eKin — Daftar OPD Aktif + Kepala

```http
GET /api/ekin/opd?tahun=2026
```

`kepala` = pihak pertama PK `jpt` (atau `camat` untuk kecamatan) OPD itu pada tahun tersebut; bila ada lebih dari satu PK, yang terbaru. `null` bila PK belum ada.

```json
{
  "status": "success",
  "meta": { "tahun": 2026, "count": 45 },
  "data": [
    {
      "id": 23,
      "nama_opd": "Dinas Lingkungan Hidup",
      "singkatan": null,
      "jenis": "opd",
      "pegawai_opd_ids": [23],
      "kepala": {
        "pegawai_id": 57, "nama": "…", "nip": "…", "jabatan": "Kepala Dinas Lingkungan Hidup",
        "plt": false, "plh": false, "pk_id": 2, "jenis_pk": "jpt"
      }
    }
  ]
}
```

### 9. eKin — Roster Pegawai

```http
GET /api/ekin/pegawai?opd_id=23
GET /api/ekin/pegawai?q=Nurlela
GET /api/ekin/pegawai?ids=44,57,253
```

Wajib **salah satu** dari `opd_id`, `q`, `ids` (boleh digabung; digabung berarti DAN). Maksimal **500** baris; `meta.terpotong = true` bila hasilnya lebih banyak (persempit dengan `q` atau `ids`).

| Parameter | Keterangan |
| --- | --- |
| `opd_id` | ID OPD (id alias ikut dipetakan). OPD tak dikenal → `404`. |
| `q` | Cari nama atau NIP, minimal 3 karakter; hanya huruf, angka, spasi, titik, koma, petik, dan tanda hubung. |
| `ids` | Daftar id dipisah koma (atau `ids[]=`), maksimal 500. |

```json
{
  "status": "success",
  "meta": { "tahun": 2026, "count": 1, "maks": 500, "terpotong": false, "filter": { "ids": [44] } },
  "data": [
    {
      "id": 44,
      "nip": "…",
      "nama": "…",
      "opd_id": 23,
      "opd_id_efektif": 23,
      "opd": "Dinas Lingkungan Hidup",
      "jabatan_id": 121,
      "jabatan": "Kepala Bidang Pengelolaan Sampah, Limbah B3 Dan Pengendalian Pencemaran",
      "jabatan_kategori": "struktural",
      "status": "PNS",
      "pangkat": "…",
      "plt": false,
      "pk_jenis": ["administrator"]
    }
  ]
}
```

- `jabatan_kategori` diturunkan dari awalan `jabatan.simpeg_id` (`struktural-`, `fungsional-`, `pelaksana-`); `null` bila jabatan tidak berkode SIMPEG. Kolom `jabatan.eselon` tidak dipakai karena hampir seluruhnya kosong.
- `pk_jenis` = jenis PK tahun itu yang ia tanda tangani sebagai pihak pertama (`jpt|camat|administrator|pengawas`) — petunjuk jenis jabatan.

### 10. eKin — Sumber RHK Seorang Pegawai

```http
GET /api/ekin/pegawai/{pegawai_id}/kinerja?tahun=2026
```

Pegawai tak dikenal → `404`. Isi `data`:

| Kunci | Isi |
| --- | --- |
| `pegawai` | Baris pegawai (bentuk sama dengan endpoint 9, tanpa `pk_jenis`). |
| `peran_pk` | PK tahun itu yang ia tanda tangani sebagai pihak pertama: `pk_id`, `jenis`, `tahun`, `opd_id`, `pihak_2_pegawai_id` (atasan penanda tangan), `plt`, `plh`. |
| `cascading` | Simpul pohon kinerja yang ia miliki tahun itu (menu **Pemilik Kinerja**), dengan indikator, satuan, target tahunan, metode, tautan IKP, dan **induk** (usulan "RHK pimpinan yang diintervensi"). |
| `ikp` | IKP yang relevan baginya, masing-masing dengan `alasan`. |
| `pk_indikator` | Indikator PK tahun itu (pihak pertama = dia) + target triwulan & butir rencana aksi (menu Target & Rencana Aksi). |

Rincian `cascading[]`:

```json
{
  "node_id": 2181,
  "opd_id": 23,
  "level": "pelaksana",
  "level_label": "Pelaksana",
  "sasaran": "Meningkatnya partisipasi masyarakat dalam pengurangan sampah melalui bank sampah",
  "peran": "anggota",
  "jabatan_teks": "Pengelola Umum Operasional",
  "plt": false,
  "induk": {
    "jenis": "cascading", "level": "es4", "level_label": "Eselon IV / JF",
    "node_id": 376, "sasaran": "Pengelolaan Sampah",
    "indikator_id": 463, "indikator": "Terlaksananya pelaksanaan pengelolaan persampahan",
    "pemilik_pegawai_ids": [248, 253]
  },
  "indikator": [
    {
      "id": 2485, "nama": "Jumlah nasabah aktif bank sampah", "satuan": "Orang",
      "target_tahunan": 400, "target_teks": "400", "metode": "trend_naik",
      "ikp_id": 314,
      "target_bulanan": { "1": 215, "2": 230, "…": "…", "12": 400 }
    }
  ]
}
```

- `level`: `es3|es4|pelaksana`; `level_label` sudah digeser untuk kecamatan (Camat = Eselon III, dst.).
- `induk.jenis = "iku"` untuk simpul Eselon III: induknya indikator IKU OPD (`induk.node_id = null`, `induk.sasaran_id` = id sasaran IKU, `pemilik_pegawai_ids` = kepala OPD dari PK). Untuk Eselon IV/Pelaksana `induk.jenis = "cascading"`: simpul induk + **indikator induk** (`es3_indikator_id`) + pemilik simpul induk tahun itu (penanggung jawab lebih dulu). Rantai ini adalah rantai **intervensi**, bukan rantai penilai — pejabat penilai tetap diatur di eKin.
- `target_bulanan` hanya terisi bila indikator ditautkan ke IKP (`ikp_id`); nilainya target bulanan IKP tahun itu. Selain itu `null` — target bulanan pegawai disusun di Rencana Aksi eKin.
- Simpul yang tidak tampil di pohon (IKU induknya dihentikan, atau periode IKU tidak memuat tahun itu) **tidak dikirim**; jumlahnya ada di `meta.simpul_tersembunyi`.

Rincian `ikp[]`:

```json
{
  "ikp_id": 314, "opd_id": 23, "kategori": "program_unggulan", "program_unggulan": "Pringsewu Bersih",
  "indikator": "Jumlah nasabah aktif bank sampah", "satuan": "Orang", "metode": "trend_naik",
  "target_5_tahun": 1000, "target_tahunan": 400, "target_tahunan_teks": null,
  "target_bulanan":    { "1": 215, "2": 230, "…": "…", "12": 400 },
  "realisasi_bulanan": { "1": 212, "2": 226, "…": "…", "9": null },
  "pj_pegawai_id": 44, "cascading_sasaran_id": 2181, "cascading_indikator_id": 2485,
  "alasan": "simpul", "node_ids": [2181]
}
```

`alasan` (satu nilai per IKP; bila beberapa sebab berlaku, yang paling spesifik dipakai: `pj` → `simpul` → `kepala_opd`):

| `alasan` | Arti |
| --- | --- |
| `pj` | `ikp.pj_pegawai_id` = pegawai ini. |
| `simpul` | IKP tertaut ke simpul yang ia miliki tahun itu: `ikp.cascading_sasaran_id` = simpulnya, `ikp.cascading_indikator_id` = indikator simpulnya, atau `cascading_indikator_target.ikp_id` pada indikator simpulnya. `node_ids` menyebut simpul penautnya. |
| `kepala_opd` | Ia pihak pertama PK JPT/Camat OPD itu tahun itu → **semua** IKP aktif OPD dikirim. |

`target_bulanan`/`realisasi_bulanan` selalu berkunci `"1"`..`"12"`; `null` = belum diisi (realisasi `0` tetap `0`). Hanya IKP yang belum dihapus.

Rincian `pk_indikator[]`:

```json
{
  "pk_id": 440, "jenis_pk": "administrator", "opd_id": 23, "pk_indikator_id": 2289,
  "sasaran": "…", "indikator": "Indeks Kinerja Pengelolaan Sampah (IKPS)", "jenis_indikator": "Indikator Positif",
  "satuan": "%", "target": "40.0",
  "target_triwulan": { "1": "10", "2": "20", "3": "30", "4": "40" },
  "rencana_aksi": ["…", "…"]
}
```

Nilai target PK dan triwulan dikirim **apa adanya (teks)** seperti tersimpan di AKSARA (bisa memakai koma atau titik desimal, atau predikat). `target_triwulan` `null` bila Target & Rencana Aksi indikator itu belum diisi.

### 11. eKin — IKP Perangkat Daerah

```http
GET /api/ekin/opd/{opd_id}/ikp?tahun=2026
```

OPD tak dikenal → `404`. Angka dihitung oleh `IkpRekapService::rekapOpd` — sama persis dengan layar IKP AKSARA, rekap Kabupaten, dan monitoring Bupati.

```json
{
  "status": "success",
  "meta": {
    "opd": { "id": 23, "nama_opd": "Dinas Lingkungan Hidup", "singkatan": null, "jenis": "opd" },
    "tahun": 2026, "kepala_pegawai_id": 57, "count": 9
  },
  "data": [
    {
      "ikp_id": 314, "kategori": "program_unggulan", "program_unggulan": "Pringsewu Bersih",
      "indikator": "Jumlah nasabah aktif bank sampah", "satuan": "Orang", "metode": "trend_naik",
      "target_5_tahun": 1000, "target_tahunan": 400, "target_tahunan_teks": null,
      "bulan": { "1": { "target": 215, "realisasi": 212 }, "…": "…", "12": { "target": 400, "realisasi": null } },
      "triwulan": {
        "1": { "target": 245, "realisasi": 241, "capaian": 98.37, "status": "achieved", "status_label": "Tercapai", "warna": "hijau", "berjalan": false },
        "…": "…",
        "4": { "target": 400, "realisasi": null, "capaian": null, "status": "belum_ada_data", "status_label": "Belum Ada Data", "warna": "abu", "berjalan": false }
      },
      "capaian_tahun_berjalan": { "persen": 96.36, "status": "achieved", "status_label": "Tercapai", "warna": "hijau", "sampai_bulan": 8 },
      "pj_pegawai_id": 44, "cascading_sasaran_id": 2181, "cascading_indikator_id": 2485
    }
  ]
}
```

- `metode`: `sum` (bulanan = tambahan; triwulan = jumlah) atau `trend_naik|trend_turun|trend_flat` (bulanan = posisi; triwulan = bulan terisi terakhir). `null` bila OPD belum memilih metode — rekap triwulannya kosong.
- `berjalan: true` = triwulan belum lengkap terisi (mis. TW III per Agustus): `target` tetap target triwulan penuh, sedangkan `capaian` dihitung dari bulan yang sudah terisi saja.
- `capaian` hanya menghitung bulan yang **sudah ada realisasinya**; `status` memakai ambang warna Pengaturan Dashboard (`critical|attention|near_target|achieved|exceeded`, atau `belum_ada_data|belum_dinilai|belum_valid`).

### Kode status eKin

| Status | Keterangan |
| --- | --- |
| `200` | Berhasil |
| `400` | Parameter tidak valid atau tidak ada satu pun filter wajib |
| `401` | Token eKin tidak dikirim / salah (termasuk bila memakai token API umum) |
| `404` | Pegawai atau perangkat daerah tidak ditemukan |
| `500` | `EKIN_API_TOKEN` belum dikonfigurasi, atau galat server (pesan memuat kode rujukan `ERR-…` untuk log) |

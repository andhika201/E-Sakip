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

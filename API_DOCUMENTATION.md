# Dokumentasi API e-SAKIP

API ini menyediakan data read-only untuk perangkat daerah, IKU, cascading, dan pohon kinerja perangkat daerah.

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

## Kode Status HTTP

| Status | Keterangan |
| --- | --- |
| `200` | Berhasil |
| `400` | Parameter tidak valid atau kurang |
| `401` | API token tidak valid |
| `404` | Perangkat daerah atau periode tidak ditemukan |
| `500` | Konfigurasi server belum lengkap |

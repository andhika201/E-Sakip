# Dokumentasi API Presensi Sync

API ini dirancang khusus untuk **sinkronisasi data master & pegawai** dari SIMPEG BKPSDM ke backend sistem presensi eksternal (Express.js / **elapkin**). Schema response sudah disesuaikan agar kompatibel dengan tabel `elapkin.*`.

- **Base URL**: `https://<host>/api/presensi`
- **Implementasi**: [PresensiSyncController](../app/Http/Controllers/Api/PresensiSyncController.php)
- **Route definition**: [routes/api.php:39-47](../routes/api.php#L39-L47)

---

## Autentikasi

Semua endpoint dilindungi middleware [CheckApiToken](../app/Http/Middleware/CheckApiToken.php). Token statis (min. **32 karakter**) dikonfigurasi di `config/services.php` → `services.api.tokens`.

**Pengiriman token** — pilih salah satu header. **Query param `?token=` TIDAK diterima** (bocor di log nginx/proxy):

```http
Authorization: Bearer <api-token>
```
atau
```http
X-API-TOKEN: <api-token>
```

**Response 401** (token tidak valid / tidak ada):
```json
{
  "success": false,
  "message": "Unauthorized. Token API tidak valid atau tidak ditemukan."
}
```

---

## Rate Limit

**120 request / menit** per IP. Cukup untuk sync periodik, mencegah enumeration.

**Response 429** (limit terlampaui):
```json
{ "message": "Too Many Attempts." }
```

---

## Ringkasan Endpoint

| Method | Endpoint                       | Cache    | Deskripsi                              |
|--------|--------------------------------|----------|----------------------------------------|
| GET    | `/api/presensi/pegawai`        | -        | List pegawai aktif (paginated)         |
| GET    | `/api/presensi/pegawai/{nip}`  | -        | Detail satu pegawai by NIP             |
| GET    | `/api/presensi/opd`            | -        | Master OPD aktif                       |
| GET    | `/api/presensi/pangkat`        | 1 jam    | Master golongan/pangkat (PNS & PPPK)   |
| GET    | `/api/presensi/jabatan`        | 1 jam    | Master jabatan (struktural/fungsional/pelaksana) |

> **Catatan cache**: endpoint master (`pangkat`, `jabatan`) di-cache 1 jam tanpa auto-bust. Setelah re-seed / import massal jalankan `php artisan cache:clear` agar konsumen langsung mendapat data baru.

---

## 1. GET `/api/presensi/pegawai`

Daftar pegawai aktif (`status_aktif = 1`), diformat untuk tabel `elapkin.pegawai`.

### Query Params

| Param           | Tipe      | Default | Wajib | Deskripsi                                                    |
|-----------------|-----------|---------|-------|--------------------------------------------------------------|
| `opd_id`        | int       | -       | ✗     | Filter berdasarkan `pegawais.opd_id`                         |
| `updated_since` | datetime  | -       | ✗     | Hanya pegawai dengan `updated_at >= updated_since` (format `Y-m-d H:i:s`) — **gunakan untuk delta sync**|
| `per_page`      | int       | 100     | ✗     | Item per halaman. **Max 500** (di-clamp otomatis)            |
| `page`          | int       | 1       | ✗     | Nomor halaman                                                |

Urutan default: `ORDER BY nip ASC`.

### Response 200

```json
{
  "success": true,
  "meta": {
    "current_page": 1,
    "last_page": 13,
    "per_page": 100,
    "total": 1245
  },
  "data": [
    {
      "id_pegawai": 5,
      "nama_pegawai": "Budi Santoso, S.Kom",
      "nip_pegawai": "198001012005011001",
      "id_opd": 1,
      "id_kantor": 1,
      "id_jabatan": "struktural-STR-001",
      "nama_jabatan": "Kepala Bidang Mutasi",
      "kategori_jabatan": "struktural",
      "id_pangkat": "pns-7",
      "nama_pangkat": "Penata Muda",
      "jenis_pangkat": "pns",
      "id_atasan": null,
      "level": "USER",
      "url_foto_pegawai": "uploads/pegawai/5.jpg",
      "tukin": 0,
      "edited_by": 1,
      "first_time": 1,
      "created_at": "2024-01-10T03:21:00+00:00",
      "updated_at": "2026-05-20T07:11:33+00:00",
      "no_whatsapp": "*********890",
      "device_id": null,
      "device_type": "WEB",
      "is_active": 1,
      "status_pegawai": "PNS",
      "is_banned": 0,
      "pelanggaran_lokasi": 0
    }
  ]
}
```

### Skema Field

| Field                | Tipe          | Sumber / Catatan                                                       |
|----------------------|---------------|------------------------------------------------------------------------|
| `id_pegawai`         | int           | `pegawais.id`                                                          |
| `nama_pegawai`       | string        | `pegawais.nama_lengkap`                                                |
| `nip_pegawai`        | string        | `pegawais.nip`                                                         |
| `id_opd`             | int\|null     | `pegawais.opd_id`                                                      |
| `id_kantor`          | int\|null     | Sama dengan `id_opd` (asumsi 1 OPD = 1 kantor)                         |
| `id_jabatan`         | string\|null  | Format `<kategori>-<kode_siasn>`. Lihat **Fallback Jabatan** di bawah  |
| `nama_jabatan`       | string\|null  | Nama jabatan dari master SIASN / legacy                                |
| `kategori_jabatan`   | string\|null  | `struktural` / `fungsional` / `pelaksana` / `legacy`                   |
| `id_pangkat`         | string\|null  | Format `<jenis>-<id>`. Lihat **Fallback Pangkat** di bawah              |
| `nama_pangkat`       | string\|null  | Dari `golongan_pns.nama_pangkat` atau `golongan_pppk.nama_pangkat`     |
| `jenis_pangkat`      | string\|null  | `pns` / `pppk` / `legacy`                                              |
| `id_atasan`          | null          | Saat ini selalu `null` — belum dipetakan                                |
| `level`              | string        | Hardcoded `"USER"` — backend presensi atur sendiri elevasi              |
| `url_foto_pegawai`   | string\|null  | `pegawais.foto`                                                        |
| `tukin`              | int           | Hardcoded `0`                                                          |
| `edited_by`          | int           | Hardcoded `1`                                                          |
| `first_time`         | int           | Hardcoded `1` — menandai pegawai baru di sistem presensi               |
| `created_at`         | ISO8601\|null | `pegawais.created_at`                                                  |
| `updated_at`         | ISO8601\|null | `pegawais.updated_at`                                                  |
| `no_whatsapp`        | string\|null  | **Di-mask** — hanya 3 digit terakhir terlihat (`*********890`)         |
| `device_id`          | null          | Diisi backend presensi saat device-binding                              |
| `device_type`        | string        | Hardcoded `"WEB"`                                                      |
| `is_active`          | int           | `1` jika `status_aktif = 1`, else `0`                                   |
| `status_pegawai`     | string        | `jenis_pegawais.nama` (fallback `"PNS"`)                                |
| `is_banned`          | int           | Hardcoded `0`                                                          |
| `pelanggaran_lokasi` | int           | Hardcoded `0`                                                          |

### Fallback Jabatan (`id_jabatan` / `nama_jabatan` / `kategori_jabatan`)

Sumber data: baris **riwayat aktif** (`tmt_selesai IS NULL`) → kalau tidak ada, **riwayat terakhir** by `tmt_jabatan`.

Prioritas dalam baris tersebut:
1. `jabatan_struktural_id` → format `struktural-<kode_siasn>`
2. `jabatan_fungsional_id` → format `fungsional-<kode_siasn>`
3. `jabatan_pelaksana_id`  → format `pelaksana-<kode_siasn>`
4. `jabatan_id` (legacy)   → format `legacy-<id>`
5. FK ada tapi master hilang → kirim ID mentah (`nama_jabatan` & `kategori_jabatan` = `null`) supaya konsumen sadar
6. Tidak ada riwayat sama sekali → semua field `null`

### Fallback Pangkat (`id_pangkat` / `nama_pangkat` / `jenis_pangkat`)

Sumber data dengan prioritas:
1. `pegawais.golongan_pns_id` → format `pns-<id>`
2. `pegawais.golongan_pppk_id` → format `pppk-<id>`
3. `pegawais.golongan_terakhir_id` (legacy) → format `legacy-<id>`
4. Baris **riwayat pangkat terakhir** (`pangkatTerakhir`):
   - `golongan_pns_id` → `pns-<id>`
   - `golongan_pppk_id` → `pppk-<id>`
   - `golongan_id` (legacy) → `legacy-<id>`
5. Tidak ada → semua field `null`

### Catatan Keamanan

- **Password TIDAK pernah dikirim**. Backend presensi wajib generate password random (dengan `first_time = 1`) lalu trigger reset via channel aman.
- **No HP di-mask** (3 digit terakhir saja). Implementasi: [PresensiSyncController::maskPhone()](../app/Http/Controllers/Api/PresensiSyncController.php#L455).
- **Tanggal lahir tidak di-expose** — tidak dibutuhkan untuk presensi.

---

## 2. GET `/api/presensi/pegawai/{nip}`

Detail satu pegawai berdasarkan NIP. Schema response **sama persis** dengan list (1 objek tanpa pagination).

### URL Param

| Param | Tipe   | Wajib | Deskripsi                  |
|-------|--------|-------|----------------------------|
| `nip` | string | ✓     | NIP pegawai (di URL path)  |

### Response 200

```json
{
  "success": true,
  "data": {
    "id_pegawai": 5,
    "nama_pegawai": "Budi Santoso, S.Kom",
    "nip_pegawai": "198001012005011001",
    "id_opd": 1,
    ...
  }
}
```

### Response 404

```json
{
  "success": false,
  "message": "Pegawai dengan NIP 198001012005011001 tidak ditemukan."
}
```

---

## 3. GET `/api/presensi/opd`

Daftar OPD aktif (`opds.is_active = 1`), terurut `nama ASC`. Untuk sinkronisasi ke `elapkin.opd`.

### Query Params

Tidak ada.

### Response 200

```json
{
  "success": true,
  "total": 35,
  "data": [
    {
      "id_opd": 1,
      "nama_opd": "BKPSDM",
      "alamat_opd": null,
      "id_kepala_opd": null,
      "lat_opd": null,
      "long_opd": null,
      "edited_by": 1,
      "created_at": "2024-01-01T00:00:00+00:00",
      "updated_at": "2024-01-01T00:00:00+00:00"
    }
  ]
}
```

### Skema Field

| Field           | Tipe          | Sumber / Catatan                                          |
|-----------------|---------------|-----------------------------------------------------------|
| `id_opd`        | int           | `opds.id`                                                 |
| `nama_opd`      | string        | `opds.nama`                                               |
| `alamat_opd`    | null          | Placeholder — field belum tersedia di DB SIMPEG           |
| `id_kepala_opd` | null          | Placeholder — belum dipetakan                              |
| `lat_opd`       | null          | Placeholder                                               |
| `long_opd`      | null          | Placeholder                                               |
| `edited_by`     | int           | Hardcoded `1`                                             |
| `created_at`    | ISO8601\|null | `opds.created_at`                                         |
| `updated_at`    | ISO8601\|null | `opds.updated_at`                                         |

---

## 4. GET `/api/presensi/pangkat`

Master golongan/pangkat untuk sinkronisasi ke `elapkin.pangkat`. **Cached 1 jam.**

### Query Params

| Param   | Tipe   | Default | Deskripsi                                                          |
|---------|--------|---------|--------------------------------------------------------------------|
| `jenis` | string | -       | `pns` / `pppk`. Kosong = **gabungan keduanya** (PNS + PPPK)        |

### Response 200

```json
{
  "success": true,
  "total": 24,
  "rekap": [
    { "jenis": "pns",  "jumlah": 17 },
    { "jenis": "pppk", "jumlah": 7  }
  ],
  "data": [
    {
      "id_pangkat": "pns-7",
      "id": 7,
      "jenis": "pns",
      "golongan": "III/a",
      "nama_pangkat": "Penata Muda",
      "edited_by": 1,
      "created_at": "2024-01-01T00:00:00+00:00",
      "updated_at": "2024-01-01T00:00:00+00:00"
    },
    {
      "id_pangkat": "pppk-3",
      "id": 3,
      "jenis": "pppk",
      "golongan": "IX",
      "nama_pangkat": "Ahli Pertama PPPK",
      "edited_by": 1,
      "created_at": "2024-01-01T00:00:00+00:00",
      "updated_at": "2024-01-01T00:00:00+00:00"
    }
  ]
}
```

### Skema Field (per item `data[]`)

| Field          | Tipe          | Sumber / Catatan                                                  |
|----------------|---------------|-------------------------------------------------------------------|
| `id_pangkat`   | string        | `"<jenis>-<id>"` — unique key cross-jenis untuk elapkin           |
| `id`           | int           | ID asli (`golongan_pns.id` atau `golongan_pppk.id`)               |
| `jenis`        | string        | `pns` atau `pppk`                                                 |
| `golongan`     | string        | Kode golongan (mis. `III/a`, `IX`)                                |
| `nama_pangkat` | string        | `nama_pangkat`, fallback ke `golongan` jika null                  |
| `edited_by`    | int           | Hardcoded `1`                                                     |
| `created_at`   | ISO8601\|null | -                                                                 |
| `updated_at`   | ISO8601\|null | -                                                                 |

---

## 5. GET `/api/presensi/jabatan`

Master jabatan gabungan SIASN (struktural + fungsional + pelaksana) untuk sinkronisasi ke `elapkin.jabatan`. **Cached 1 jam.**

### Query Params

| Param      | Tipe   | Default | Deskripsi                                                            |
|------------|--------|---------|----------------------------------------------------------------------|
| `kategori` | string | -       | `struktural` / `fungsional` / `pelaksana`. Kosong = **gabungan ketiganya** |

### Response 200

```json
{
  "success": true,
  "total": 432,
  "rekap": [
    { "kategori": "struktural", "jumlah": 52  },
    { "kategori": "fungsional", "jumlah": 180 },
    { "kategori": "pelaksana",  "jumlah": 200 }
  ],
  "data": [
    {
      "id_jabatan": "struktural-STR-001",
      "kode_siasn": "STR-001",
      "kategori": "struktural",
      "nama_jabatan": "Kepala Bidang Mutasi",
      "nama_unit_kerja": "Bidang Mutasi",
      "eselon_id": 3,
      "kelas": 11,
      "bup": 60,
      "tupoksi": null,
      "edited_by": 1,
      "created_at": "2024-01-01T00:00:00+00:00",
      "updated_at": "2024-01-01T00:00:00+00:00"
    }
  ]
}
```

### Skema Field

| Field             | Tipe          | Catatan                                                                 |
|-------------------|---------------|-------------------------------------------------------------------------|
| `id_jabatan`      | string        | `"<kategori>-<kode_siasn>"` — unique key cross-kategori                 |
| `kode_siasn`      | string        | PK dari tabel master SIASN                                              |
| `kategori`        | string        | `struktural` / `fungsional` / `pelaksana`                               |
| `nama_jabatan`    | string        | -                                                                       |
| `nama_unit_kerja` | string\|null  | Hanya terisi untuk **struktural**; `null` untuk fungsional & pelaksana  |
| `eselon_id`       | int\|null     | Hanya terisi untuk **struktural**                                       |
| `kelas`           | int\|null     | Kelas jabatan                                                           |
| `bup`             | int\|null     | Batas Usia Pensiun                                                      |
| `tupoksi`         | null          | Placeholder — belum di-expose                                           |
| `edited_by`       | int           | Hardcoded `1`                                                           |
| `created_at`      | ISO8601\|null | -                                                                       |
| `updated_at`      | ISO8601\|null | -                                                                       |

---

## Strategi Sinkronisasi

### Full Sync (Initial Load)
Ambil seluruh master + pegawai untuk pertama kali:
```
1. GET /api/presensi/opd
2. GET /api/presensi/pangkat
3. GET /api/presensi/jabatan
4. GET /api/presensi/pegawai?per_page=500&page=1
   ... loop selama page <= meta.last_page
```

### Delta Sync (Periodic, mis. tiap 15 menit)
Cukup ambil pegawai yang berubah sejak sync terakhir:
```
GET /api/presensi/pegawai?updated_since=2026-05-25 10:00:00&per_page=500
```
Master (OPD/pangkat/jabatan) cukup di-sync sekali sehari atau saat ada notifikasi perubahan.

### Single Lookup
Untuk lookup ad-hoc oleh aplikasi presensi (mis. saat pegawai login):
```
GET /api/presensi/pegawai/{nip}
```

---

## Contoh Penggunaan

### cURL

```bash
# List pegawai (page 1)
curl "https://<host>/api/presensi/pegawai?per_page=500" \
  -H "Authorization: Bearer <api-token>"

# Delta sync sejak timestamp tertentu (URL-encode spasi)
curl "https://<host>/api/presensi/pegawai?updated_since=2026-05-25%2010:00:00&per_page=500" \
  -H "X-API-TOKEN: <api-token>"

# Detail by NIP
curl "https://<host>/api/presensi/pegawai/198001012005011001" \
  -H "Authorization: Bearer <api-token>"

# Master OPD
curl "https://<host>/api/presensi/opd" \
  -H "Authorization: Bearer <api-token>"

# Master pangkat — hanya PNS
curl "https://<host>/api/presensi/pangkat?jenis=pns" \
  -H "Authorization: Bearer <api-token>"

# Master jabatan — hanya struktural
curl "https://<host>/api/presensi/jabatan?kategori=struktural" \
  -H "Authorization: Bearer <api-token>"
```

### Node.js (Express/elapkin)

```javascript
const axios = require('axios');

const api = axios.create({
  baseURL: 'https://<host>/api/presensi',
  headers: { 'Authorization': `Bearer ${process.env.SIMPEG_API_TOKEN}` },
  timeout: 30000,
});

// Delta sync pegawai
async function syncPegawai(updatedSince) {
  let page = 1;
  let lastPage = 1;
  const result = [];

  do {
    const { data } = await api.get('/pegawai', {
      params: { updated_since: updatedSince, per_page: 500, page },
    });
    result.push(...data.data);
    lastPage = data.meta.last_page;
    page++;
  } while (page <= lastPage);

  return result;
}
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://<host>/api/presensi/',
    'headers'  => ['Authorization' => 'Bearer ' . env('SIMPEG_API_TOKEN')],
    'timeout'  => 30,
]);

$res = $client->get('pegawai', [
    'query' => ['updated_since' => '2026-05-25 10:00:00', 'per_page' => 500],
]);

$payload = json_decode($res->getBody(), true);
foreach ($payload['data'] as $pegawai) {
    // upsert ke elapkin.pegawai
}
```

---

## Format Error

**401 Unauthorized**
```json
{ "success": false, "message": "Unauthorized. Token API tidak valid atau tidak ditemukan." }
```

**404 Not Found** (hanya pada `/pegawai/{nip}`)
```json
{ "success": false, "message": "Pegawai dengan NIP ... tidak ditemukan." }
```

**429 Too Many Requests**
```json
{ "message": "Too Many Attempts." }
```

---

## Changelog

| Tanggal      | Perubahan                                                              |
|--------------|------------------------------------------------------------------------|
| 2026-05-25   | Dokumen awal Presensi Sync API (5 endpoint).                           |

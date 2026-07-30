# ERD e-SAKIP — Database `test_sakip`

Diagram relasi entitas (ERD) hasil **introspeksi langsung** dari database live `test_sakip`
per **2026-07-28**: 62 tabel, 64 foreign key aktif.

Legenda:

- ✅ **FK** — foreign key benar-benar ada di database (`information_schema`)
- ⚠️ **implisit** — relasi hanya dijaga di kode aplikasi (join di Model), belum ada FK
- `CASCADE` / `SET NULL` — aksi `ON DELETE`

> ERD dipecah per modul supaya terbaca. Untuk narasi keselarasan SAKIP KemenPAN-RB
> (Perencanaan → Penetapan → Pengukuran → Pelaporan), lihat [RELASI_SAKIP.md](RELASI_SAKIP.md).

---

## 0. Peta Besar — alur dokumen SAKIP

```mermaid
flowchart LR
    subgraph P[1 · Perencanaan]
        VISI[RPJMD<br/>visi→misi→tujuan→sasaran]
        RENSTRA[Renstra OPD<br/>tujuan→sasaran→indikator]
        CASC[Pohon Kinerja<br/>Es II→III→IV→Pelaksana]
        IKU[IKU<br/>kabupaten & OPD]
        RKT[RKT + Program/Kegiatan]
    end
    subgraph T[2 · Penetapan]
        PK[Perjanjian Kinerja<br/>pk + sasaran + indikator]
    end
    subgraph U[3 · Pengukuran]
        TR[Rencana Aksi<br/>target_rencana + sub]
        MON[MONEV<br/>capaian & anggaran triwulan]
    end
    subgraph L[4 · Pelaporan]
        LAKIP[LAKIP<br/>capaian + analisis + efisiensi]
    end

    VISI --> RENSTRA --> CASC
    VISI --> IKU
    RENSTRA --> IKU
    RENSTRA --> RKT
    VISI --> PK
    RENSTRA --> PK
    RKT --> PK
    PK --> TR --> MON --> LAKIP
    VISI --> LAKIP
    RENSTRA --> LAKIP
```

---

## 1. Master & Hak Akses

`opd` adalah pusat gravitasi seluruh sistem — hampir semua modul menyaring datanya per OPD.

```mermaid
erDiagram
    opd ||--o{ jabatan : "opd_id (SET NULL)"
    opd ||--o{ users : "opd_id (implisit)"
    opd ||--o{ pegawai : "opd_id (implisit)"
    jabatan ||--o{ pegawai : "jabatan_id (implisit)"
    pangkat ||--o{ pegawai : "pangkat_id (implisit)"
    pegawai ||--o{ pegawai : "atasan_id (implisit)"
    satuan ||--o{ satuan_skala : "satuan_id"
    roles ||--o{ role_permissions : "role_id (implisit)"
    permissions ||--o{ role_permissions : "permission_id (implisit)"
    users ||--o{ activity_logs : "user_id (implisit)"

    opd {
        int id PK
        varchar nama_opd
        varchar singkatan
        int simpeg_id "sinkron SIMPEG"
        int id_kepala_opd
    }
    users {
        int user_id PK
        varchar username
        varchar role "super_admin|admin_kab|admin_opd|admin_kecamatan|admin_inspektorat"
        int opd_id FK
        tinyint is_active
        varchar two_factor_secret
    }
    satuan {
        int id PK
        varchar satuan
        varchar tipe "angka|predikat"
    }
    satuan_skala {
        int id PK
        int satuan_id FK
        varchar kode "WTP, WDP, ..."
        decimal nilai "nilai numerik predikat"
        int urutan
    }
    app_settings {
        varchar skey PK
        text svalue
    }
    dashboard_status_thresholds {
        int id PK
        varchar code UK "critical|attention|near_target|achieved|exceeded"
        varchar name
        decimal min_value "batas bawah persen"
        decimal max_value "NULL = tanpa batas atas"
        varchar color "slug palet terbatas"
        tinyint is_active
    }
```

| Anak | Kolom | Induk | Status |
|---|---|---|---|
| jabatan | opd_id | opd.id | ✅ SET NULL |
| satuan_skala | satuan_id | satuan.id | ✅ CASCADE |
| users | opd_id | opd.id | ⚠️ implisit |
| pegawai | opd_id / jabatan_id / pangkat_id / atasan_id | opd / jabatan / pangkat / pegawai | ⚠️ implisit |
| role_permissions | role_id / permission_id | roles / permissions | ⚠️ implisit |
| activity_logs | user_id | users.user_id | ⚠️ implisit |

`app_settings` berdiri sendiri (key–value: nama aplikasi, logo, favicon, SEO, penandatangan).

`dashboard_status_thresholds` juga berdiri sendiri (tanpa FK): rentang persentase → status
capaian (Kritis … Melampaui Target) beserta warna & ikonnya, dikelola Super Admin lewat
`adminkab/dashboard-thresholds` dan dipakai oleh Dashboard OPD maupun Dashboard Kabupaten.

---

## 2. RPJMD — perencanaan kabupaten

Rantai lurus 5 tingkat: **visi → misi → tujuan → sasaran → indikator → target tahunan**,
dengan cabang indikator tujuan di sisi tujuan.

```mermaid
erDiagram
    rpjmd_visi   ||--o{ rpjmd_misi   : "rpjmd_visi_id"
    rpjmd_misi   ||--o{ rpjmd_tujuan : "misi_id"
    rpjmd_tujuan ||--o{ rpjmd_indikator_tujuan : "tujuan_id"
    rpjmd_indikator_tujuan ||--o{ rpjmd_target_tujuan : "indikator_tujuan_id"
    rpjmd_tujuan  ||--o{ rpjmd_sasaran : "tujuan_id"
    rpjmd_sasaran ||--o{ rpjmd_indikator_sasaran : "sasaran_id"
    rpjmd_indikator_sasaran ||--o{ rpjmd_target : "indikator_sasaran_id"

    rpjmd_indikator_sasaran ||--o{ rpjmd_cascading : "indikator_sasaran_id"
    opd        ||--o{ rpjmd_cascading : "opd_id"
    pk_program ||--o{ rpjmd_cascading : "pk_program_id"

    rpjmd_visi { int id PK
        text visi }
    rpjmd_misi { int id PK
        int rpjmd_visi_id FK
        text misi
        enum status "draft|selesai"
        year tahun_mulai
        year tahun_akhir }
    rpjmd_tujuan { int id PK
        int misi_id FK
        text tujuan_rpjmd }
    rpjmd_sasaran { int id PK
        int tujuan_id FK
        text sasaran_rpjmd
        text csf "critical success factor"
        enum status }
    rpjmd_indikator_sasaran { int id PK
        int sasaran_id FK
        text indikator_sasaran
        text definisi_op
        varchar satuan
        varchar baseline
        varchar jenis_indikator }
    rpjmd_target { int id PK
        int indikator_sasaran_id FK
        year tahun
        varchar target_tahunan }
    rpjmd_cascading { int id PK
        int indikator_sasaran_id FK
        int opd_id FK
        int pk_program_id FK
        year tahun }
```

Semua relasi di atas ✅ **FK CASCADE**. `rpjmd_cascading` = pohon kinerja tingkat kabupaten:
memetakan tiap indikator sasaran RPJMD ke **OPD penanggung jawab + program**.
Indikator tanpa baris di sini = *gap* (indikator tanpa penanggung jawab).

---

## 3. Renstra OPD — dan jembatan ke RPJMD

Titik keselarasan vertikal ada di `renstra_tujuan.rpjmd_sasaran_id`.

```mermaid
erDiagram
    rpjmd_sasaran  ||--o{ renstra_tujuan : "rpjmd_sasaran_id — JEMBATAN"
    renstra_tujuan ||--o{ renstra_indikator_tujuan : "tujuan_id"
    renstra_indikator_tujuan ||--o{ renstra_target_tujuan : "indikator_tujuan_id"
    renstra_tujuan  ||--o{ renstra_sasaran : "renstra_tujuan_id"
    opd             ||--o{ renstra_sasaran : "opd_id"
    renstra_sasaran ||--o{ renstra_indikator_sasaran : "renstra_sasaran_id"
    renstra_indikator_sasaran ||--o{ renstra_target : "renstra_indikator_id"

    renstra_tujuan { int id PK
        int rpjmd_sasaran_id FK
        text tujuan }
    renstra_sasaran { int id PK
        int opd_id FK
        int renstra_tujuan_id FK
        text sasaran
        text csf
        enum status "draft|selesai"
        int tahun_mulai
        int tahun_akhir }
    renstra_indikator_sasaran { int id PK
        int renstra_sasaran_id FK
        text indikator_sasaran
        varchar satuan
        varchar baseline
        varchar jenis_indikator }
    renstra_target { int id PK
        int renstra_indikator_id FK
        year tahun
        varchar target }
```

Seluruh relasi ✅ **FK CASCADE**. Renstra tanpa `rpjmd_sasaran_id` = Renstra "menggantung"
(tidak selaras dengan RPJMD).

---

## 4. Cascading / Pohon Kinerja OPD

Satu tabel rekursif menampung 4 jenjang lewat kolom `level` + `parent_id`.

```mermaid
erDiagram
    renstra_indikator_sasaran ||--o{ cascading_sasaran_opd : "renstra_indikator_sasaran_id"
    opd                   ||--o{ cascading_sasaran_opd : "opd_id"
    cascading_sasaran_opd ||--o{ cascading_sasaran_opd : "parent_id (rekursif)"
    cascading_sasaran_opd ||--o{ cascading_indikator_opd : "cascading_sasaran_id"
    cascading_indikator_opd |o--o{ cascading_sasaran_opd : "es3_indikator_id (indikator induk, SET NULL)"

    cascading_sasaran_opd { int id PK
        int opd_id FK
        int renstra_indikator_sasaran_id FK
        int parent_id FK "induk 1 jenjang di atas"
        int es3_indikator_id FK "indikator induk yang diturunkan"
        enum level "es2|es3|es4|pelaksana"
        text nama_sasaran
        text csf }
    cascading_indikator_opd { int id PK
        int cascading_sasaran_id FK
        text indikator
        varchar satuan }
```

Jenjang: **Eselon II → Eselon III → Eselon IV/JF → Pelaksana**.
Untuk role `admin_kecamatan`, label eselon digeser satu tingkat (II→III, III→IV, IV/JF→Pelaksana)
— murni kosmetik, nilai `level` di database tetap `es2/es3/es4`.

---

## 5. IKU — Indikator Kinerja Utama (modul mandiri)

Sejak 2026-07-27 IKU berdiri sendiri, tidak lagi menumpang indikator RPJMD/Renstra.
`iku_sasaran.opd_id` **NULL = IKU Kabupaten**, terisi = IKU OPD.

```mermaid
erDiagram
    opd           ||--o{ iku_sasaran   : "opd_id (NULL = kabupaten)"
    iku_sasaran   ||--o{ iku_indikator : "iku_sasaran_id"
    iku_indikator ||--o{ iku_target    : "iku_indikator_id"
    iku_indikator ||--o{ iku_program   : "iku_indikator_id"

    iku_sasaran { int id PK
        int opd_id FK "NULL = kabupaten"
        text sasaran
        int tahun_mulai
        int tahun_akhir
        int urutan }
    iku_indikator { int id PK
        int iku_sasaran_id FK
        text indikator
        text definisi
        text rumusan_perhitungan
        varchar satuan
        varchar sumber_data
        varchar penanggung_jawab
        varchar baseline
        varchar status }
    iku_target { int id PK
        int iku_indikator_id FK
        year tahun
        varchar target }
    iku_program { int id PK
        int iku_indikator_id FK
        text program }
```

Semua ✅ **FK CASCADE**.

**Tabel lama (deprecated, tidak dihapus):** `iku` (`rpjmd_id`, `renstra_id` — ⚠️ tanpa FK)
dan `iku_program_pendukung` (`iku_id` ✅ FK CASCADE). Tidak dipakai modul IKU baru.

---

## 6. Program–Kegiatan–Sub Kegiatan & RKT

```mermaid
erDiagram
    program_pk      ||--o{ kegiatan_pk     : "program_id"
    kegiatan_pk     ||--o{ sub_kegiatan_pk : "kegiatan_id"
    opd             ||--o{ program_pk      : "opd_id (implisit, NULL = milik kabupaten)"

    program_pk      ||--o{ rkt             : "program_id"
    renstra_indikator_sasaran ||--o{ rkt   : "indikator_id (implisit)"
    rkt             ||--o{ rkt_kegiatan    : "rkt_id"
    kegiatan_pk     ||--o{ rkt_kegiatan    : "kegiatan_id"
    rkt_kegiatan    ||--o{ rkt_subkegiatan : "rkt_kegiatan_id"
    sub_kegiatan_pk ||--o{ rkt_subkegiatan : "sub_kegiatan_id"

    program_pk { int id PK
        int opd_id
        int kode_program
        text program_kegiatan
        year tahun_anggaran
        varchar jenis_anggaran
        decimal anggaran }
    kegiatan_pk { int id PK
        int program_id FK
        int kode_kegiatan
        text kegiatan
        year tahun_anggaran
        decimal anggaran }
    sub_kegiatan_pk { int id PK
        int kegiatan_id FK
        int kode_sub_kegiatan
        text sub_kegiatan
        decimal anggaran }
    rkt { int id PK
        int opd_id
        year tahun
        int indikator_id "renstra_indikator_sasaran"
        int program_id FK
        enum status "draft|selesai" }
    rkt_subkegiatan { int id PK
        int rkt_kegiatan_id FK
        int sub_kegiatan_id FK
        text indikator_sasaran_sub_kegiatan
        varchar target }
```

FK aktif: `kegiatan_pk.program_id`, `sub_kegiatan_pk.kegiatan_id`, `rkt.program_id`,
`rkt_kegiatan.rkt_id`, `rkt_kegiatan.kegiatan_id`, `rkt_subkegiatan.rkt_kegiatan_id`,
`rkt_subkegiatan.sub_kegiatan_id` — semuanya CASCADE.
⚠️ Tanpa FK: `program_pk.opd_id`, `rkt.opd_id`, `rkt.indikator_id`.

---

## 7. Perjanjian Kinerja (PK)

`pk` bersifat rekursif (`parent_pk_id`) untuk berjenjang Bupati → Eselon II → III → IV.

```mermaid
erDiagram
    pk            ||--o{ pk               : "parent_pk_id (SET NULL)"
    pk            ||--o{ pk_misi          : "pk_id"
    rpjmd_misi    ||--o{ pk_misi          : "rpjmd_misi_id (implisit)"
    pk            ||--o{ pk_sasaran       : "pk_id"
    pk_sasaran    ||--o{ pk_sasaran_opd   : "pk_sasaran_id"
    opd           ||--o{ pk_sasaran_opd   : "opd_id"
    pk_sasaran    ||--o{ pk_indikator     : "pk_sasaran_id"
    satuan        ||--o{ pk_indikator     : "id_satuan (SET NULL)"
    pk_indikator  ||--o{ pk_program       : "pk_indikator_id (SET NULL)"
    program_pk    ||--o{ pk_program       : "program_id (implisit)"
    pk_program    ||--o{ pk_kegiatan      : "pk_program_id (SET NULL)"
    kegiatan_pk   ||--o{ pk_kegiatan      : "kegiatan_id (implisit)"
    pk_kegiatan   ||--o{ pk_subkegiatan   : "pk_kegiatan_id (SET NULL)"
    pk            ||--o{ pk_referensi     : "pk_id / referensi_pk_id"
    pk_indikator  ||--o{ pk_referensi     : "referensi_indikator_id"
    pegawai       ||--o{ pk               : "pihak_1 / pihak_2 (implisit)"

    pk { int id PK
        int parent_pk_id FK
        int opd_id
        year tahun
        varchar jenis "bupati|jpt|..."
        int pihak_1 "pegawai"
        tinyint is_plt_pihak_1
        tinyint is_plh_pihak_1
        varchar jabatan_pihak_1_manual
        int pihak_2 "pegawai"
        date tanggal }
    pk_sasaran { int id PK
        int pk_id FK
        varchar jenis
        text sasaran }
    pk_indikator { int id PK
        int pk_sasaran_id FK
        varchar indikator
        varchar jenis_indikator
        int id_satuan FK
        varchar target }
    pk_referensi { int id PK
        int pk_id FK "PK anak"
        int referensi_pk_id FK "PK induk yang dirujuk"
        int referensi_indikator_id FK }
```

`pk_referensi` = mekanisme *cascade* PK: indikator PK atasan dirujuk oleh PK bawahan.
`pk_sasaran_opd` menandai OPD pengampu sasaran PK Bupati.
Menu **PK JPT** dan **PK Kecamatan** memakai data yang sama (`pk.jenis='jpt'`), pemisahan hanya di rute/tampilan.

---

## 8. Rencana Aksi, Target & MONEV — inti pengukuran

`target_rencana` adalah simpul yang menyatukan tiga sumber target: RPJMD, Renstra, dan PK.

```mermaid
erDiagram
    opd            ||--o{ target_rencana     : "opd_id"
    renstra_target ||--o{ target_rencana     : "renstra_target_id"
    rpjmd_target   ||--o{ target_rencana     : "rpjmd_target_id (SET NULL)"
    pk_indikator   ||--o{ target_rencana     : "pk_indikator_id"
    target_rencana ||--o{ target_sub_rencana : "target_rencana_id"
    target_rencana ||--o{ monev              : "target_rencana_id"
    target_sub_rencana ||--o{ monev          : "target_sub_rencana_id (implisit)"
    target_rencana ||--o{ monev_anggaran     : "target_rencana_id"
    opd            ||--o{ monev              : "opd_id"
    opd            ||--o{ monev_anggaran     : "opd_id"

    target_rencana { int id PK
        int opd_id FK
        int renstra_target_id FK
        int rpjmd_target_id FK
        int pk_indikator_id FK "penghubung realisasi PK"
        text rencana_aksi
        varchar target_triwulan_1
        varchar target_triwulan_2
        varchar target_triwulan_3
        varchar target_triwulan_4
        varchar penanggung_jawab }
    target_sub_rencana { int id PK
        int target_rencana_id FK
        int baris_rencana
        text sub_rencana_aksi
        varchar target_triwulan_1
        varchar target_triwulan_4
        int urutan }
    monev { int id PK
        int opd_id FK
        int target_rencana_id FK
        int target_sub_rencana_id
        varchar capaian_triwulan_1
        varchar capaian_triwulan_4
        varchar metode_perhitungan "akumulasi|rata2|akhir"
        decimal total "capaian total = persen otomatis" }
    monev_anggaran { int id PK
        int target_rencana_id FK
        int opd_id FK
        decimal realisasi_triwulan_1
        decimal realisasi_triwulan_4 }
```

`target_rencana.pk_indikator_id` adalah kunci monitoring realisasi PK (Bupati & Eselon II/III/IV):
realisasi PK dibaca dari MONEV lewat rantai ini, bukan dari tabel capaian PK lama.

---

## 9. LAKIP — pelaporan kinerja

```mermaid
erDiagram
    rpjmd_target   ||--o{ lakip : "rpjmd_target_id (SET NULL)"
    renstra_target ||--o{ lakip : "renstra_target_id (SET NULL)"
    rpjmd_target   ||--o{ lakip_analisis_faktor : "rpjmd_target_id"
    renstra_target ||--o{ lakip_analisis_faktor : "renstra_target_id"
    opd            ||--o{ lakip_analisis_faktor : "opd_id (implisit)"
    program_pk     ||--o{ lakip_efisiensi_program : "program_id"
    opd            ||--o{ lakip_efisiensi_program : "opd_id (implisit)"

    lakip { int id PK
        int renstra_target_id FK "LAKIP OPD"
        int rpjmd_target_id FK "LAKIP Kabupaten"
        varchar target_hitung
        varchar target_lalu
        varchar capaian_lalu
        varchar capaian_tahun_ini
        varchar capaian_hitung
        enum status "proses|siap" }
    lakip_analisis_faktor { int id PK
        int renstra_target_id FK
        int rpjmd_target_id FK
        int opd_id
        year tahun
        text faktor_pendukung
        text faktor_penghambat
        text upaya_peningkatan }
    lakip_efisiensi_program { int id PK
        int program_id FK
        int opd_id
        year tahun
        decimal anggaran
        decimal realisasi
        decimal efisiensi }
```

Satu baris `lakip` mengisi tepat **salah satu** dari `rpjmd_target_id` (LAKIP Kabupaten)
atau `renstra_target_id` (LAKIP OPD) — pola yang sama dipakai `lakip_analisis_faktor`.
Program + pagu pada tabel efisiensi diambil dari `program_pk` melalui rantai PK.

---

## 10. Catatan skema

**Tabel cadangan (bukan bagian model, aman diabaikan/dihapus):**
`_backup_monev_total_20260727`, `_bak_target_rencana_capaian_20260727`.

**Relasi implisit yang paling berisiko** (belum dijaga FK, mudah jadi data yatim):

| Kolom | Seharusnya menunjuk | Dampak bila yatim |
|---|---|---|
| `users.opd_id` | `opd.id` | user tanpa OPD → data kosong di semua menu |
| `program_pk.opd_id` | `opd.id` | program tak terbaca di RKT/PK OPD |
| `rkt.indikator_id` | `renstra_indikator_sasaran.id` | RKT lepas dari Renstra |
| `pk.opd_id`, `pk.pihak_1/2` | `opd.id`, `pegawai.id` | PK tanpa penandatangan/OPD |
| `monev.target_sub_rencana_id` | `target_sub_rencana.id` | capaian sub rencana menggantung |
| `iku.rpjmd_id`, `iku.renstra_id` | tabel IKU lama — sudah tidak dipakai | — |

**Konvensi FK yang berlaku:** `ON UPDATE CASCADE`; `ON DELETE CASCADE` untuk relasi
struktural induk–anak, `ON DELETE SET NULL` untuk pointer opsional
(`cascading_sasaran_opd.es3_indikator_id`, `lakip.*_target_id`, `pk.parent_pk_id`, `pk_indikator.id_satuan`).

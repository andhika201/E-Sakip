# MASTER PROMPT — HARDENING & PENYELARASAN E-SAKIP / AKSARA
## Project terbaru: E-Sakip_CI(5) + database terbaru
## Urutan: Security → Renstra → IKU → Cascading → LAKIP → Transaction MONEV → AdminKab → Testing

Anda bekerja pada project terbaru **e-SAKIP / AKSARA Kabupaten Pringsewu** berbasis **CodeIgniter 4 + MySQL/MariaDB**.

Tujuan task ini adalah **hardening, bug fixing, source consistency, lifecycle/version safety, transaction safety, validation, error handling, dan penyelarasan Admin OPD serta Admin Kabupaten**.

JANGAN rewrite project dari nol.
JANGAN menghapus data user.
JANGAN melakukan migration/destructive cleanup tanpa persetujuan saya.

---

# 0. ATURAN DATA PALING PENTING

Jika menemukan data yang menurut Anda:
- orphan;
- duplicate;
- legacy;
- stale;
- salah referensi;
- tidak terpakai;
- perlu cleanup;
- perlu dihapus;
- perlu di-reset;
- perlu dimigrasikan secara destructive;

**JANGAN DELETE / TRUNCATE / DROP / OVERWRITE SECARA OTOMATIS.**

Lakukan:

```text
1. Audit
2. Tampilkan jumlah dan contoh record
3. Jelaskan dampak dan risiko
4. Berikan rekomendasi
5. BERHENTI dan minta konfirmasi saya
```

Hanya lakukan destructive data change setelah saya menyetujui.

Pengecualian yang boleh langsung dikerjakan:
- file debug di `public/`;
- route/state mutation yang tidak aman;
- code hardening;
- dead debug code yang tidak menyimpan data user.

---

# 1. AUDIT DULU, BARU CODING

Pelajari source aktif, jangan hanya dokumentasi.

Minimal audit:
- `app/Config/Routes.php`
- `app/Config/Filters.php`
- Controllers Admin OPD
- Controllers Admin Kabupaten
- Controllers Bupati
- Models
- Services
- Traits
- Helpers
- Views
- Commands
- database schema terbaru
- seluruh SQL di `db/`
- `AI_AGENT_GUIDE_AKSARA.md` jika ada
- PDF/Excel/API/Public view yang terkait

Source aktif adalah source of truth.

---

# 2. ARSITEKTUR YANG DIINGINKAN

## Admin OPD

```text
RENSTRA VERSION PUBLISHED
        │
        │ one-time sync
        ▼
IKU OPD OFFICIAL REVISION
        │
        ├───────────────┐
        │               │
        ▼               ▼
   CASCADING          LAKIP
        │
        ▼
       PK
        │
        ▼
    RENAKSI
        │
        ▼
     MONEV
```

## RKT

**RKT tetap mengambil data dari Renstra.**
Jangan ubah RKT ke IKU pada task ini.

```text
RENSTRA
  ↓
 RKT
```

## Admin Kabupaten

```text
RPJMD VERSION PUBLISHED
        │
        │ one-time sync
        ▼
IKU KABUPATEN OFFICIAL REVISION
        │
        ├────────────────┐
        │                │
        ▼                ▼
CASCADING KABUPATEN    LAKIP
        │
        ▼
    PK BUPATI
        │
        ▼
     RENAKSI
        │
        ▼
      MONEV
```

---

# 3. KEPUTUSAN CASCADING KABUPATEN

Untuk **data baru/current**, Cascading Kabupaten harus mengambil source kinerja dari:

```text
IKU KABUPATEN OFFICIAL REVISION
```

IKU Kabupaten sebelumnya dibuat melalui:

```text
RPJMD PUBLISHED VERSION
        ↓
one-time sync
        ↓
IKU KABUPATEN
```

## Jangan buat dual source normal

JANGAN membuat dropdown operasional biasa:

```text
Source:
( ) RPJMD
( ) IKU
```

untuk Cascading current.

Alasan:
- menciptakan dua sumber kebenaran;
- Cascading bisa berbeda dengan LAKIP;
- mudah drift;
- membingungkan user.

## RPJMD tetap dipakai sebagai konteks strategis

Pertahankan lineage:

```text
IKU
↓
source_rpjmd_version_id / source_rpjmd_entity_id
```

atau mapping existing yang setara.

Di UI Cascading Kabupaten boleh tampil:

```text
Misi RPJMD
→ Tujuan RPJMD
→ Sasaran RPJMD
→ IKU Kabupaten
→ Cascading
```

RPJMD menjadi konteks/breadcrumb/read-only strategic backbone, tetapi source entity current Cascading adalah IKU.

## Backward compatibility

Jika Cascading lama masih:

```text
source_type = rpjmd
```

JANGAN migrasi paksa.
Tetap readable sebagai historical/legacy.

Untuk Cascading baru:

```text
source_type = iku
```

menjadi default.

Jika migrasi legacy dianggap perlu, STOP dan minta persetujuan saya.

---

# 4. URUTAN PENGERJAAN WAJIB

Kerjakan bertahap:

```text
PHASE 0 — Security kritis lintas role
PHASE 1 — Renstra
PHASE 2 — IKU
PHASE 3 — Cascading
PHASE 4 — LAKIP
PHASE 5 — Transaction Rencana Aksi & MONEV Anggaran
PHASE 6 — Admin Kabupaten / RPJMD / cross-OPD
PHASE 7 — Error handling & cleanup technical debt
PHASE 8 — Testing menyeluruh
```

Prioritaskan bug/celah yang berbahaya untuk data dan security.

---

# 5. PHASE 0 — AKTIFKAN CSRF DENGAN AMAN

Jika CSRF global masih nonaktif di:

```text
app/Config/Filters.php
```

aktifkan.

Tetapi sebelum commit audit:
- seluruh form;
- AJAX/fetch;
- modal save;
- dynamic form;
- delete;
- status;
- submit;
- approve;
- publish.

Pastikan token CSRF tersedia dan valid untuk request mutasi.

Jangan mengaktifkan CSRF lalu membiarkan AJAX rusak.

### Wajib test
- login;
- create;
- edit;
- update;
- delete;
- status;
- AJAX;
- modal;
- verification.

---

# 6. PHASE 0 — HILANGKAN MUTATION VIA GET

Audit Routes.

GET harus read-only.

State-changing action seperti:

```text
delete
change_status
submit
publish
approve
reject
cancel
finalize
```

harus menggunakan:

```text
POST / PATCH / PUT / DELETE
```

sesuai convention project + CSRF.

Jika route GET lama masih diperlukan untuk compatibility:
- jangan lakukan mutation;
- redirect/read-only;
- atau return method not allowed.

---

# 7. PHASE 0 — HAPUS DEBUG FILE DARI PUBLIC

Audit dan hapus/pindahkan file debug seperti:

```text
public/test_db.php
public/cek_lakip.php
public/test_out.txt
```

serta file debug lain yang dapat diakses web.

Pindahkan ke non-public `tests/` atau `scripts/` jika masih dibutuhkan.

Pastikan production route:
```text
/test_db.php
/cek_lakip.php
/test_out.txt
```
menghasilkan 404.

---

# 8. ERROR HANDLING — SOLUSI TERPUSAT

Hentikan pola user-facing:

```php
with('error', $e->getMessage())
```

untuk exception teknis.

## User melihat

```text
Data gagal disimpan.
Silakan coba kembali atau hubungi administrator.
Kode referensi: ERR-XXXXXX
```

## Server log menyimpan
- reference code;
- user id;
- role;
- opd_id;
- route;
- entity;
- exception message;
- stack trace;
- context aman.

Jangan log password/token.

## Validation error
Tetap boleh spesifik:

```text
Target TW II wajib berupa angka.
```

## 403 / 404
Gunakan response aman untuk ownership/authorization/not-found.

Prefer helper/service reusable seperti:

```text
SafeExceptionResponder
AppErrorHandler
```

atau pola existing yang sesuai.

Jangan duplikasi technical exception message di seluruh controller.

---

# 9. PHASE 1 — RENSTRA

Kerjakan Renstra dulu.

## Lifecycle

Published version:
```text
READ ONLY
```

Direct edit/update/delete/status tidak boleh bypass lifecycle.

Perubahan substantif:
```text
buat version baru
```

Jika correction flow existing:
reuse.

## Ownership
Semua Admin OPD endpoint harus enforce:

```text
renstra.opd_id == current_user.opd_id
```

Audit:
- view;
- edit;
- update;
- delete;
- status;
- version;
- copy;
- submit.

Anti-IDOR server-side.

---

# 10. RENSTRA — HAPUS TRANSACTION READ-ONLY

Jika `RenstraController::index()` masih membuka transaction pada halaman read-only:

```php
$db->transStart();
```

hapus jika memang tidak diperlukan.

Jangan membuka transaction tanpa commit/complete pada read-only flow.

---

# 11. RENSTRA — VALIDASI

Perkuat server-side validation minimal:

```text
periode valid
tahun_akhir >= tahun_mulai
target tahun berada dalam periode
duplicate tahun target ditolak
satuan_id benar-benar ada
jenis_indikator allowlist
nama/text required + max length
nilai target valid
rpjmd_sasaran_id valid bila dipakai
source/version valid
OPD scope valid
```

Save dan Update harus menggunakan validation set yang konsisten.

Jangan hanya mengandalkan HTML `required`.

---

# 12. PHASE 2 — IKU OPD

Normal source:

```text
RENSTRA PUBLISHED VERSION
↓
one-time sync
↓
IKU REVISION
```

Jika user tidak memilih version:
```text
gunakan effective/current published Renstra version
```

JANGAN fallback ke live Renstra.

Jika request mengirim version ID invalid:
```text
REJECT
```

JANGAN ubah invalid ID menjadi `null` lalu fallback ke live source.

Bedakan:
```text
parameter tidak dikirim
```
vs
```text
parameter dikirim tetapi invalid
```

---

# 13. PHASE 2 — IKU KABUPATEN

Gunakan rule yang sama:

```text
RPJMD PUBLISHED VERSION
↓
one-time sync
↓
IKU KABUPATEN OFFICIAL REVISION
```

Jika version tidak dipilih:
gunakan effective/current published RPJMD.

Jika version ID invalid:
reject.

Jangan fallback silent ke live RPJMD.

---

# 14. IKU PUBLISHED IMMUTABLE

Audit endpoint legacy:

```text
edit
update
delete
change_status
```

Jika entity sudah menjadi bagian dari Published/Official IKU Revision:
direct structural mutation harus diblok.

Jangan izinkan:

```text
Published IKU
→ delete indikator live
```

atau:

```text
Published IKU
→ nonaktif lewat jalur legacy
```

Perubahan dilakukan melalui revision baru.

---

# 15. IKU MODE GANTI HARUS ATOMIC

Jika Mode Ganti sekarang melakukan:

```text
sync
↓
prune/buang tanpa padanan
```

jadikan satu transaction:

```text
BEGIN
validate source
sync
prune
validate result
COMMIT
```

Jika gagal:
```text
ROLLBACK
```

Tidak boleh partial update.

---

# 16. DUAL IKU VERSION REGISTRY

Audit:

```text
dokumen_versi module=iku
iku_revisi
```

Tentukan source of truth runtime.

JANGAN:
- DROP table;
- DELETE record;
- merge destructive.

Untuk task ini:
- dokumentasikan;
- konsistenkan caller;
- gunakan registry official yang memang dipakai runtime.

Jika konsolidasi data dianggap perlu:
STOP dan minta persetujuan saya.

---

# 17. PHASE 3 — CASCADING OPD

Source current/official:

```text
IKU OPD OFFICIAL REVISION
```

Jika code sekarang fallback ke live IKU saat version kosong:
perbaiki.

Official Cascading tidak boleh berubah hanya karena live/draft IKU berubah.

Legacy behavior tetap readable bila diperlukan.

---

# 18. PHASE 3 — CASCADING KABUPATEN

Implementasikan desain keputusan pada bagian #3.

Current/new data:
```text
IKU KABUPATEN OFFICIAL REVISION
```

RPJMD:
```text
context + lineage
```

Jangan dual source bebas untuk current workflow.

Validasi:
```text
IKU revision published
scope Kabupaten benar
entity_id benar-benar milik revision tersebut
```

Jangan menerima arbitrary source ID.

---

# 19. PHASE 4 — LAKIP OPD

Default source:
```text
IKU OPD OFFICIAL REVISION
```

Validasi:
```text
source_version_id valid
source_entity_id valid
source OPD == LAKIP OPD
revision official/published
```

Jangan fallback silent ke live IKU.

Historical LAKIP existing:
JANGAN dimigrasi paksa.

Tetap readable/printable/exportable.

---

# 20. PHASE 4 — LAKIP KABUPATEN

Default current source:
```text
IKU KABUPATEN OFFICIAL REVISION
```

Historical LAKIP source RPJMD:
JANGAN migrasi paksa.

Untuk view semua OPD, jangan mencari satu IKU revision global.

Gunakan:
```text
OPD A → official IKU A
OPD B → official IKU B
OPD C → official IKU C
```

lalu agregasikan view.

---

# 21. LAKIP SNAPSHOT LEGACY

Jika snapshot flow lama sudah deprecated dan current flow memakai:

```text
Pengesahan
```

JANGAN menghidupkan snapshot lama.

Tabel snapshot lama boleh tetap sebagai compatibility/history.

Jangan hapus tanpa persetujuan.

---

# 22. PHASE 5 — SAVE RENCANA AKSI SATU TRANSACTION

Perbaiki create:

```text
Rencana Aksi
+
Sub Rencana Aksi
```

menjadi atomic.

```text
BEGIN
authorize
validate PK/context
insert target_rencana
insert semua target_sub_rencana
validate result
COMMIT
```

Jika child gagal:
```text
ROLLBACK
```

Parent tidak boleh tertinggal.

---

# 23. UPDATE RENCANA AKSI JUGA TRANSACTION

Update parent + children harus satu transaction.

PENTING:

Jika current update menghapus Sub yang tidak ada lagi di request, audit apakah Sub tersebut sudah mempunyai:
```text
MONEV
realisasi
history
```

Jika penghapusan dapat menghilangkan data user:
STOP dan minta persetujuan.

Jangan hard-delete child yang sudah mempunyai data tanpa izin.

---

# 24. PHASE 5 — BATCH MONEV ANGGARAN TRANSACTION

Satu form batch:

```text
Program
Kegiatan
Sub Kegiatan
```

harus all-or-nothing.

```text
BEGIN
validate ALL rows
validate ownership
validate references
upsert ALL rows
COMMIT
```

Jika satu gagal:
```text
ROLLBACK ALL
```

Jangan membiarkan sebagian tersimpan tetapi user mendapat pesan gagal.

---

# 25. MONEV ANGGARAN VALIDATION

Validasi:
```text
ref_level allowlist
ref_id benar-benar ada
ref belongs to context PK/unit
OPD scope valid
nilai numerik
0 valid
NULL = belum input
```

Jangan menerima arbitrary `ref_id`.

---

# 26. PK — VALIDASI PROGRAM DITUNDA

Data:

```text
Program → Kegiatan → Sub Kegiatan
```

tahun 2026 masih belum rapi.

JANGAN membuat domain validation ketat hierarchy tersebut pada task ini.

Yang tetap WAJIB diperbaiki:
- security;
- ownership;
- CSRF;
- unsafe GET;
- error handling;
- obvious type validation.

## FIX PK READ IDOR

Audit `PkController::edit()` dan endpoint lain.

Pastikan:
```text
PK.opd_id == current OPD
```

untuk read/edit/update/delete.

Jika bukan milik OPD:
403/404.

---

# 27. PHASE 6 — ADMIN KAB PRINCIPLE

AdminKab:

```text
WRITE
→ dokumen Kabupaten

VERIFY
→ pengajuan/version OPD

READ CROSS-OPD
→ monitoring pekerjaan OPD
```

Jangan memberi direct edit bebas terhadap data OPD kecuali workflow override khusus memang ada.

---

# 28. RPJMD LIFECYCLE

Harden RPJMD setelah flow OPD utama.

Published RPJMD:
```text
immutable
```

Legacy controller tidak boleh direct edit/delete/status terhadap Published hierarchy.

Perubahan:
```text
buat version baru
```

Correction non-substantif:
reuse correction flow bila ada.

---

# 29. RPJMD VALIDATION

Perkuat nested validation:
```text
periode
visi
misi
tujuan
sasaran
indikator
satuan
jenis indikator
target per tahun
duplicate tahun
numeric values
hierarchy parent-child
version context
```

Pertahankan transaction yang sudah benar.

---

# 30. ADMIN KAB CROSS-OPD MONITORING

Jangan duplicate seluruh CRUD Admin OPD.

Gunakan read-only monitoring pattern.

Minimal AdminKab dapat:
```text
pilih OPD
lihat Renstra version
lihat IKU official revision
lihat Cascading
lihat PK
lihat Renaksi
lihat MONEV
lihat LAKIP
lihat status approval
```

Mutation OPD tetap melalui workflow yang benar.

---

# 31. VERSION CONFLICT

Audit Renstra/RPJMD/IKU publish.

Dua Published version dalam context yang sama tidak boleh mempunyai:
```text
effective_from sama
```

Draft boleh sementara conflict.

Revalidate tepat sebelum commit untuk mencegah race condition.

---

# 32. TRANSACTION SAFETY GLOBAL

Gunakan transaction untuk:
```text
deep copy
publish
sync
Mode Ganti
Rencana Aksi + Sub
batch MONEV Anggaran
correction approval
```

Jangan gunakan transaction yang tidak perlu pada read-only index.

---

# 33. VALIDATION PIPELINE WAJIB

Setiap write endpoint:

```text
1. authenticate
2. authorize
3. validate ownership/scope
4. validate input type
5. validate referenced entity exists
6. validate referenced entity belongs to correct scope/version
7. transaction
8. activity log
9. safe response
```

Jangan percaya:
```text
hidden input
URL ID
select option
```
dari frontend.

---

# 34. MASS ASSIGNMENT

Audit `$allowedFields` dan raw POST mass assignment.

Field sensitif tidak boleh bebas diisi user:

```text
opd_id
status
approved_by
published_at
created_by
source_version_id
```

kecuali melalui controlled business logic.

---

# 35. DELETE STRATEGY

Jika entity sudah direferensikan oleh:
```text
IKU
Cascading
PK
MONEV
LAKIP
Version
```

prefer:
```text
block delete
retire/nonaktif
new version
```

daripada hard delete.

Draft yang belum direferensikan boleh mengikuti delete policy existing.

Jika production data perlu dihapus:
STOP dan minta konfirmasi saya.

---

# 36. LOGGING & AUDIT

Reuse activity log existing.

Untuk action penting log:
```text
user_id
role
opd_id
module
action
entity_id
version_id
timestamp
before/after summary
error reference code bila gagal
```

Jangan log credential/token.

---

# 37. DATABASE / SQL PRODUCTION

Jika ada:
- table baru;
- column;
- index;
- FK;
- permission;
- seed;
- backfill;
- data update;

buat migration CI4 bila sesuai project.

Selain itu WAJIB berikan SQL manual production.

Contoh:
```text
db/update_YYYY-MM-DD_security_version_hardening.sql
```

atau phase-based files.

SQL harus:
- MySQL/MariaDB compatible;
- tidak DROP data user;
- tidak TRUNCATE;
- tidak DELETE existing user data tanpa approval;
- idempotent semaksimal mungkin;
- komentar per section;
- ada preflight;
- ada post-deploy check.

Jika tidak ada DB change:
```text
Tidak ada perubahan schema database.
```

---

# 38. PREFLIGHT DATABASE

Berikan query read-only:
```text
SELECT DATABASE();
SELECT VERSION();
```

dan audit:
```text
counts
orphans
duplicates
version conflicts
published states
legacy sources
```

Jangan destructive.

---

# 39. POST-DEPLOY CHECK

Validasi:
```text
record count tidak turun tak terduga
FK valid
version history tetap ada
Published data tetap ada
tidak ada orphan baru
tidak ada duplicate conflict baru
```

---

# 40. TESTING WAJIB SETELAH PERUBAHAN

Untuk SETIAP module yang diubah lakukan:

```text
CREATE TEST
READ TEST
EDIT TEST
UPDATE TEST
DELETE TEST
BUG TEST
SECURITY TEST
```

Jika delete seharusnya diblok:
expected rejection dianggap PASS.

---

# 41. TEST RENSTRA

Minimal:
```text
create Draft
read
edit Draft
update Draft
delete Draft aman
submit
Published read-only
ownership OPD
IDOR
invalid year
duplicate target year
invalid satuan
invalid CSRF
```

---

# 42. TEST IKU

Minimal:
```text
sync dari valid Renstra version
no explicit version → current Published
invalid version → reject
Mode Tambah
Mode Ganti atomic
Published direct edit blocked
Published delete blocked
Published status mutation blocked
ownership OPD
Kabupaten sync dari RPJMD
```

---

# 43. TEST CASCADING

## OPD
```text
official IKU revision menjadi source
invalid entity reject
ownership scope
live IKU tidak menyebabkan drift official source
```

## Kabupaten
```text
new Cascading source = IKU Kabupaten
RPJMD strategic context tetap tampil
legacy RPJMD Cascading tetap readable
tidak ada dual-source normal dropdown
```

---

# 44. TEST LAKIP

## OPD
```text
official IKU source
scope OPD
invalid revision reject
historical report readable
PDF
Excel
```

## Kabupaten
```text
default official IKU Kabupaten
historical RPJMD LAKIP readable
all-OPD memakai masing-masing official IKU
```

---

# 45. TEST RENCANA AKSI TRANSACTION

Paksa child insert gagal.

Expected:
```text
parent rollback
```

Paksa update child gagal.

Expected:
```text
tidak ada partial changes
```

---

# 46. TEST BATCH MONEV ANGGARAN

Batch 5 row.

Paksa row ke-4 gagal.

Expected:
```text
0 row batch tersimpan
```

Valid batch:
```text
semua tersimpan
```

Test:
```text
0 = valid value
NULL = belum input
```

---

# 47. TEST SECURITY

Minimal:
```text
CSRF missing → rejected
CSRF invalid → rejected
GET mutation → tidak mengubah data
Admin OPD A akses OPD B → rejected
AdminKab cross-OPD read → allowed sesuai permission
AdminKab unauthorized direct mutation OPD → rejected
Published direct mutation → rejected
```

---

# 48. TEST ERROR HANDLING

Paksa controlled exception pada test/dev.

User harus melihat:
```text
pesan aman + reference code
```

Log harus berisi detail teknis.

User TIDAK boleh melihat:
```text
SQLSTATE
table name
column
file path
stack trace
```

---

# 49. REGRESSION TEST

Minimal:
```text
Login
Dashboard OPD
Renstra
RKT
IKU
Cascading
PK
Renaksi
MONEV
MONEV Anggaran
LAKIP
PDF
Excel
Dashboard Kabupaten
Verifikasi
Dashboard Bupati
```

---

# 50. DATA SAFETY CHECK

Sebelum/sesudah bandingkan:

```text
COUNT Renstra
COUNT IKU
COUNT Cascading
COUNT PK
COUNT target_rencana
COUNT target_sub_rencana
COUNT monev
COUNT monev_anggaran
COUNT lakip
```

Tidak boleh turun tanpa persetujuan saya.

---

# 51. STOP CONDITION — WAJIB KONFIRMASI SAYA

BERHENTI dan minta konfirmasi jika:
- perlu DELETE production data;
- DROP table/column;
- TRUNCATE;
- rewrite historical LAKIP;
- migrate Cascading legacy RPJMD ke IKU;
- merge/delete `dokumen_versi` / `iku_revisi`;
- delete orphan user data;
- perubahan membuat historical data tidak bisa dibuka.

Jangan mengambil keputusan destructive sendiri.

---

# 52. OUTPUT PER PHASE

Setelah setiap phase berikan ringkasan:

```text
Phase
Files changed
Bug fixed
Security fix
Database change
Data touched
Tests
PASS/FAIL
Risk
```

Jika aman dan tidak membutuhkan destructive approval, lanjut phase berikutnya.

---

# 53. OUTPUT AKHIR

Berikan laporan:

## 1. Existing Architecture Verified

## 2. Final Architecture

OPD:
```text
Renstra → IKU → Cascading → PK → Renaksi → MONEV
              └──────────────→ LAKIP
Renstra → RKT
```

Kabupaten:
```text
RPJMD → IKU Kabupaten → Cascading → PK Bupati → Renaksi → MONEV
                    └────────────────────────→ LAKIP
```

## 3. Security Fixes

## 4. Renstra Fixes

## 5. IKU Fixes

## 6. Cascading OPD/Kabupaten

## 7. LAKIP Fixes

## 8. Transaction Fixes

## 9. Error Handling

## 10. AdminKab Cross-OPD Monitoring

## 11. Files Changed

## 12. Database Changes

## 13. SQL Production
Isi SQL lengkap + urutan eksekusi.

## 14. Test Results

Per module:
```text
Create PASS/FAIL
Read PASS/FAIL
Edit PASS/FAIL
Update PASS/FAIL
Delete PASS/FAIL
Bug PASS/FAIL
Security PASS/FAIL
Regression PASS/FAIL
```

## 15. Data Safety Comparison

Before vs After counts.

## 16. Risks/TODO

## 17. Items Requiring My Approval

---

# 54. DILARANG

Jangan:
- ubah RKT dari Renstra ke IKU;
- buat Cascading Kabupaten current dengan dual source bebas RPJMD/IKU;
- delete data user tanpa konfirmasi;
- migrate historical LAKIP tanpa izin;
- delete legacy Cascading tanpa izin;
- drop registry version tanpa izin;
- hard-delete orphan tanpa audit;
- tampilkan raw exception;
- biarkan mutation lewat GET;
- aktifkan CSRF tanpa memperbaiki AJAX;
- partial save multi-step;
- fallback invalid version ke live source;
- edit Published IKU/Renstra/RPJMD melalui jalur legacy;
- melakukan validasi ketat Program→Kegiatan→Sub Kegiatan PK tahun 2026 pada task ini.

---

# 55. QUALITY BAR

Target akhir:

> Security-critical issue diselesaikan lebih dahulu.

> Published version benar-benar terlindungi.

> Renstra→IKU OPD dan RPJMD→IKU Kabupaten memakai official version yang valid.

> Cascading current mengambil IKU official sehingga tidak drift terhadap live/draft source.

> Cascading Kabupaten tetap menunjukkan konteks strategis RPJMD tetapi tidak mempunyai dua source current yang bersaing.

> LAKIP memakai official IKU tanpa merusak historical report.

> Rencana Aksi dan batch MONEV Anggaran bersifat atomic.

> Technical exception tidak bocor ke user.

> Tidak ada data user yang dihapus tanpa persetujuan.

> Semua perubahan dibuktikan dengan create/read/edit/update/delete/bug/security/regression testing.

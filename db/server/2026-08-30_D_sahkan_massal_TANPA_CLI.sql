-- =====================================================================
-- [SERVER] PENGGANTI `php spark iku:sahkan-massal --fix`
-- Tanggal : 2026-08-30
-- Sifat   : IDEMPOTEN. Hanya membuat "Kondisi Awal" bagi lingkup yang
--           BELUM punya revisi apa pun. Tidak menyentuh revisi yang sudah
--           ada, tidak mengubah satu pun angka IKU.
--
-- =====================================================================
-- MENGAPA VERSI SQL INI ADA
--
-- Panduan aslinya memakai perintah CLI. Bila server tidak menyediakan akses
-- shell (`php spark` tidak bisa dijalankan), langkah itu terlewat — dan
-- gejalanya persis seperti yang terlihat di server Anda:
--
--     lakip mode='opd'  ->  iku = 8, renstra = 112
--
-- Delapan itu milik Dinas Koperindag, satu-satunya OPD yang revisinya memang
-- sudah ada di dump. OPD lain tidak punya revisi yang memayungi tahun
-- laporan, sehingga migrasi LAKIP melewatinya dan layarnya tetap membaca
-- Renstra.
--
-- =====================================================================
-- APA YANG DIBUAT
--
-- Untuk tiap OPD yang punya IKU periode 2025-2029 namun belum punya revisi:
--
--   1. satu baris `iku_revisi` bernomor 0, nama "Kondisi Awal IKU 2025-2029",
--      status 'berlaku', berlaku_mulai_tahun = 2025, tanpa batas akhir;
--   2. salinan beku seluruh sasaran, indikator, target, dan program IKU
--      berjalan ke `iku_revisi_sasaran` / `_indikator` / `_target` / `_program`.
--
-- Isinya BUKAN karangan: ia potret IKU yang sudah ada, yang sendirinya
-- berasal dari Renstra lewat Sync. Tidak ada dokumen bertanda tangan yang
-- dipalsukan di sini.
--
-- Perilakunya disalin dari IkuRevisiModel::pastikanBaseline() +
-- bekukanLiveKeRevisi(), termasuk kolom jejak asal (source_type,
-- source_version_id, source_ref_id) dan pembekuan nama satuan.
--
-- =====================================================================
-- SESUDAH MENJALANKAN INI
--
-- Jalankan ULANG dua berkas berikut (keduanya idempoten):
--
--     db/server/2026-08-27_C_jangkar_cascading_isi.sql
--     db/migrasi_2026-08-29_lakip_ke_iku.sql
--
-- Tanpa itu, revisi sudah ada tetapi LAKIP belum dipindahkan kuncinya.
-- =====================================================================


SELECT '========== SEBELUM ==========' AS laporan;

SELECT COUNT(*) AS revisi_iku_sekarang FROM iku_revisi;

SELECT COUNT(DISTINCT s.opd_id) AS opd_akan_dibekukan
FROM iku_sasaran s
JOIN iku_indikator i ON i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL
WHERE s.opd_id IS NOT NULL
  AND s.tahun_mulai = 2025 AND s.tahun_akhir = 2029
  AND s.dihentikan_pada IS NULL
  AND NOT EXISTS (SELECT 1 FROM iku_revisi r WHERE r.opd_key = s.opd_id);


-- ---------------------------------------------------------------------
-- 1. KEPALA REVISI — satu "Kondisi Awal" per OPD yang belum punya revisi
--
-- `opd_key` dan `berlaku_key` adalah generated column: JANGAN diisi.
-- OPD yang IKU-nya kosong sengaja dilewati (JOIN ke iku_indikator):
-- baseline kosong membuat layar LAKIP-nya kosong, lebih buruk daripada
-- membiarkannya memakai cadangan Renstra.
-- ---------------------------------------------------------------------
INSERT INTO `iku_revisi`
    (`opd_id`, `tahun_mulai`, `tahun_akhir`, `nomor`, `nama`,
     `berlaku_mulai_tahun`, `status`, `catatan`, `disahkan_pada`)
SELECT DISTINCT
    s.opd_id, 2025, 2029, 0, 'Kondisi Awal IKU 2025-2029',
    2025, 'berlaku',
    'Dibekukan otomatis dari kondisi IKU yang berlaku saat revisi pertama dibuat.',
    NOW()
FROM iku_sasaran s
JOIN iku_indikator i ON i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL
WHERE s.opd_id IS NOT NULL
  AND s.tahun_mulai = 2025 AND s.tahun_akhir = 2029
  AND s.dihentikan_pada IS NULL
  AND NOT EXISTS (SELECT 1 FROM iku_revisi r WHERE r.opd_key = s.opd_id);


-- ---------------------------------------------------------------------
-- 2. BEKUKAN SASARAN
--
-- Hanya ke revisi Kondisi Awal yang arsipnya MASIH KOSONG, supaya
-- menjalankan ulang skrip ini tidak menggandakan isi.
-- ---------------------------------------------------------------------
INSERT INTO `iku_revisi_sasaran`
    (`revisi_id`, `sumber_sasaran_id`, `source_type`, `source_version_id`,
     `source_ref_id`, `sasaran`, `tahun_mulai`, `tahun_akhir`, `urutan`,
     `jenis_perubahan`, `created_at`, `updated_at`)
SELECT r.id, s.id, s.source_type, s.source_version_id, s.source_sasaran_id,
       s.sasaran, s.tahun_mulai, s.tahun_akhir, s.urutan,
       'tetap', NOW(), NOW()
FROM iku_revisi r
JOIN iku_sasaran s
  ON s.opd_id = r.opd_key
 AND s.tahun_mulai = r.tahun_mulai
 AND s.tahun_akhir = r.tahun_akhir
 AND s.dihentikan_pada IS NULL
WHERE r.nomor = 0
  AND r.opd_key > 0
  AND NOT EXISTS (SELECT 1 FROM iku_revisi_sasaran x WHERE x.revisi_id = r.id)
ORDER BY r.id, s.urutan, s.id;


-- ---------------------------------------------------------------------
-- 3. BEKUKAN INDIKATOR
--
-- `satuan_nama` ikut dibekukan supaya arsip tetap berbunyi benar walau
-- master `satuan` kelak diganti namanya. Pola COALESCE-nya sama persis
-- dengan SATUAN_SELECT di IkuRevisiModel.
-- ---------------------------------------------------------------------
INSERT INTO `iku_revisi_indikator`
    (`revisi_id`, `revisi_sasaran_id`, `sumber_indikator_id`, `source_type`,
     `source_version_id`, `source_ref_id`, `indikator`, `definisi`,
     `rumusan_perhitungan`, `satuan`, `satuan_nama`, `sumber_data`,
     `penanggung_jawab`, `jenis_indikator`, `baseline`, `urutan`, `status`,
     `jenis_perubahan`, `indikator_sebelumnya_id`, `perubahan_substansial`,
     `created_at`, `updated_at`)
SELECT rs.revisi_id, rs.id, i.id, i.source_type, i.source_version_id,
       i.source_indikator_id, i.indikator, i.definisi, i.rumusan_perhitungan,
       i.satuan,
       COALESCE(sat.satuan, NULLIF(i.satuan, '')),
       i.sumber_data, i.penanggung_jawab, i.jenis_indikator, i.baseline,
       i.urutan, i.status, 'tetap', i.indikator_sebelumnya_id,
       COALESCE(i.perubahan_substansial, 0), NOW(), NOW()
FROM iku_revisi_sasaran rs
JOIN iku_revisi r  ON r.id = rs.revisi_id AND r.nomor = 0 AND r.opd_key > 0
JOIN iku_indikator i ON i.iku_sasaran_id = rs.sumber_sasaran_id
                    AND i.dihentikan_pada IS NULL
LEFT JOIN satuan sat ON i.satuan REGEXP '^[0-9]+$' AND sat.id = i.satuan
WHERE NOT EXISTS (SELECT 1 FROM iku_revisi_indikator x WHERE x.revisi_sasaran_id = rs.id)
ORDER BY rs.id, i.urutan, i.id;


-- ---------------------------------------------------------------------
-- 4. BEKUKAN TARGET
-- ---------------------------------------------------------------------
INSERT INTO `iku_revisi_target`
    (`revisi_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`)
SELECT ri.id, t.tahun, t.target, NOW(), NOW()
FROM iku_revisi_indikator ri
JOIN iku_revisi r ON r.id = ri.revisi_id AND r.nomor = 0 AND r.opd_key > 0
JOIN iku_target t ON t.iku_indikator_id = ri.sumber_indikator_id
WHERE NOT EXISTS (SELECT 1 FROM iku_revisi_target x WHERE x.revisi_indikator_id = ri.id)
ORDER BY ri.id, t.tahun;


-- ---------------------------------------------------------------------
-- 5. BEKUKAN PROGRAM (bila tabelnya dipakai)
-- ---------------------------------------------------------------------
-- Tabel ini TIDAK punya kolom updated_at (berbeda dari tiga tabel arsip
-- lainnya) — jangan disamakan begitu saja.
INSERT INTO `iku_revisi_program`
    (`revisi_indikator_id`, `program`, `urutan`, `created_at`)
SELECT ri.id, p.program, p.urutan, NOW()
FROM iku_revisi_indikator ri
JOIN iku_revisi r ON r.id = ri.revisi_id AND r.nomor = 0 AND r.opd_key > 0
JOIN iku_program p ON p.iku_indikator_id = ri.sumber_indikator_id
WHERE NOT EXISTS (SELECT 1 FROM iku_revisi_program x WHERE x.revisi_indikator_id = ri.id)
ORDER BY ri.id, p.urutan;


-- ---------------------------------------------------------------------
-- 6. PERIKSA HASIL
-- ---------------------------------------------------------------------
SELECT '========== SESUDAH ==========' AS laporan;

SELECT COUNT(*) AS revisi_total,
       SUM(status = 'berlaku') AS berlaku,
       COUNT(DISTINCT opd_key) AS lingkup
FROM iku_revisi;

SELECT '-- arsip terisi: tiap revisi harus punya indikator --' AS laporan;

SELECT COUNT(*) AS revisi_kondisi_awal_TANPA_isi
FROM iku_revisi r
WHERE r.nomor = 0 AND r.opd_key > 0
  AND NOT EXISTS (SELECT 1 FROM iku_revisi_indikator x WHERE x.revisi_id = r.id);
-- harus 0

SELECT '-- OPD ber-LAKIP yang masih TANPA payung 2025 (harus 0) --' AS laporan;

SELECT COUNT(*) AS n FROM (
    SELECT DISTINCT l.opd_id
    FROM lakip l
    WHERE l.mode = 'opd' AND l.tahun = 2025
      AND NOT EXISTS (
          SELECT 1 FROM iku_revisi r
           WHERE r.opd_key = l.opd_id
             AND r.status IN ('berlaku','superseded')
             AND r.berlaku_mulai_tahun <= 2025
             AND (r.berlaku_sampai_tahun IS NULL OR r.berlaku_sampai_tahun >= 2025)
             AND r.tahun_mulai <= 2025 AND r.tahun_akhir >= 2025
      )
) z;

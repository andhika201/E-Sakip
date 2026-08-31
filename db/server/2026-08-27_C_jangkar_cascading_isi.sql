-- =====================================================================
-- [SERVER] ISI JANGKAR IKU PADA CASCADING
-- Pengganti  : db/update_2026-08-27_cascading_sumber_iku.sql (bagian pengisian)
-- Tanggal    : 2026-08-27 (varian server, 2026-08-29)
--
-- =====================================================================
-- MENGAPA HANYA SEPARUH
--
-- Skrip aslinya melakukan dua hal: (1) membuat kolom, indeks, dan kunci
-- asing; (2) MENGISI jangkarnya. Bagian (1) SUDAH ADA di server Anda —
-- diperiksa langsung pada dump `backup-29 agustus.sql`: kolom
-- `iku_indikator_id` dan `source_type` ada, dan 1335 dari 1413 baris sudah
-- terisi. Yang tersisa hanyalah pengisiannya.
--
-- Bagian (1) itu pula yang memakai `information_schema` dan PROCEDURE, dan
-- yang membuat skrip asli gagal di server (#1044). Dengan membuang bagian
-- yang memang tidak diperlukan, masalahnya ikut hilang — bukan ditambal.
--
-- =====================================================================
-- JALANKAN SESUDAH SYNC EMPAT KECAMATAN, BUKAN SEBELUMNYA
--
-- Penjangkaran mencocokkan indikator Renstra dengan indikator IKU lewat
-- teksnya. Selama IKU keempat kecamatan itu belum berisi, tidak ada yang
-- bisa dicocokkan. Pada uji coba:
--
--     dijalankan sebelum sync : 1335 -> 1365   (48 baris tetap kosong)
--     dijalankan sesudah sync : 1365 -> 1413   (LENGKAP)
--
-- =====================================================================
-- AMAN DIULANG
--
-- Hanya mengisi yang masih NULL. Pemetaan yang sudah ada tidak pernah
-- ditimpa, dan padanan ganda sengaja dilewati — menebak lebih buruk
-- daripada membiarkan kosong dan kelihatan.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. SEBELUM
-- ---------------------------------------------------------------------
SELECT '========== SEBELUM ==========' AS `laporan`;

SELECT COUNT(*)                            AS baris_cascading,
       SUM(iku_indikator_id IS NOT NULL)   AS berjangkar_iku,
       SUM(iku_indikator_id IS NULL)       AS belum
FROM cascading_sasaran_opd;


-- ---------------------------------------------------------------------
-- 2. PETA PADANAN — hanya yang TUNGGAL
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS _peta_iku;

CREATE TEMPORARY TABLE _peta_iku (
  renstra_ind_id INT UNSIGNED NOT NULL,
  iku_ind_id     INT UNSIGNED NOT NULL,
  jml_padanan    INT NOT NULL,
  PRIMARY KEY (renstra_ind_id)
) ENGINE=InnoDB;

INSERT INTO _peta_iku (renstra_ind_id, iku_ind_id, jml_padanan)
SELECT ri.id, MIN(ii.id), COUNT(DISTINCT ii.id)
FROM renstra_indikator_sasaran ri
JOIN renstra_sasaran rs ON rs.id = ri.renstra_sasaran_id
JOIN iku_sasaran isx
  ON isx.opd_id      = rs.opd_id
 AND isx.tahun_mulai = rs.tahun_mulai
 AND isx.tahun_akhir = rs.tahun_akhir
 AND TRIM(REGEXP_REPLACE(isx.sasaran, '[[:space:]]+', ' '))
   = TRIM(REGEXP_REPLACE(rs.sasaran,  '[[:space:]]+', ' '))
JOIN iku_indikator ii
  ON ii.iku_sasaran_id = isx.id
 AND TRIM(REGEXP_REPLACE(ii.indikator,         '[[:space:]]+', ' '))
   = TRIM(REGEXP_REPLACE(ri.indikator_sasaran, '[[:space:]]+', ' '))
WHERE ii.dihentikan_pada  IS NULL
  AND isx.dihentikan_pada IS NULL
GROUP BY ri.id;

-- Padanan ganda dibuang: menebak lebih buruk daripada jatuh ke fallback.
DELETE FROM _peta_iku WHERE jml_padanan <> 1;


-- ---------------------------------------------------------------------
-- 3. ISI JANGKARNYA — hanya yang masih NULL
-- ---------------------------------------------------------------------
UPDATE cascading_sasaran_opd c
JOIN _peta_iku p ON p.renstra_ind_id = c.renstra_indikator_sasaran_id
SET c.iku_indikator_id = p.iku_ind_id,
    c.source_type      = 'iku'
WHERE c.iku_indikator_id IS NULL;

-- Sisanya ditandai tegas sebagai pembaca Renstra, bukan dibiarkan NULL,
-- supaya kode tidak perlu menebak arti NULL.
UPDATE cascading_sasaran_opd
SET source_type = 'renstra'
WHERE source_type IS NULL;


-- ---------------------------------------------------------------------
-- 4. JEMBATAN REALISASI LAKIP
--    `iku_indikator.source_indikator_id` adalah satu-satunya penghubung
--    realisasi LAKIP lama (berkunci Renstra) ke baris bersumber IKU.
--    Diisi hanya bila masih kosong — hasil sync tidak akan tertimpa.
-- ---------------------------------------------------------------------
UPDATE iku_indikator ii
JOIN _peta_iku p ON p.iku_ind_id = ii.id
SET ii.source_indikator_id = p.renstra_ind_id,
    ii.source_type         = COALESCE(ii.source_type, 'renstra')
WHERE ii.source_indikator_id IS NULL;

DROP TEMPORARY TABLE IF EXISTS _peta_iku;


-- ---------------------------------------------------------------------
-- 5. SESUDAH — angka inilah yang menentukan
-- ---------------------------------------------------------------------
SELECT '========== SESUDAH ==========' AS `laporan`;

SELECT COUNT(*)                          AS baris_cascading,
       SUM(iku_indikator_id IS NOT NULL) AS berjangkar_iku,
       SUM(iku_indikator_id IS NULL)     AS belum_HARUS_NOL
FROM cascading_sasaran_opd;

SELECT '-- bila "belum" masih > 0, ini pemiliknya --' AS `laporan`;

SELECT o.nama_opd, c.level, COUNT(*) AS baris
FROM cascading_sasaran_opd c
LEFT JOIN opd o ON o.id = c.opd_id
WHERE c.iku_indikator_id IS NULL
GROUP BY o.nama_opd, c.level
ORDER BY o.nama_opd, c.level;

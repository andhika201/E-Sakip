-- =====================================================================
-- SILSILAH IKU KABUPATEN -> RPJMD
-- Tanggal : 2026-08-28
-- Sifat   : IDEMPOTEN & ADDITIVE. Hanya MENGISI kolom jejak yang masih NULL.
--           Tidak ada DROP, DELETE, atau penimpaan nilai yang sudah terisi.
--
-- Prasyarat: db/deploy_2026-08-24_server_pringsewu.sql sudah dijalankan
--            (kolom iku_sasaran.source_* & iku_indikator.source_* berasal
--            dari migrasi 2026-08-20).
--
-- =====================================================================
-- MASALAHNYA
--
-- IKU Kabupaten yang tersusun SEBELUM kolom jejak ada tidak menyimpan asal
-- usulnya: `iku_indikator.source_indikator_id` NULL untuk seluruh barisnya.
-- Tanpa jembatan itu, Cascading Kabupaten tidak punya cara menghubungkan
-- indikator RPJMD dengan indikator IKU yang lahir dari sana — sehingga
-- layarnya terpaksa terus membaca RPJMD walau IKU Kabupaten sudah disusun
-- dan bahkan sudah direvisi.
--
-- Ini kembaran persis dari db/update_2026-08-27_cascading_sumber_iku.sql
-- yang menjembatani sisi OPD (Renstra -> IKU OPD).
--
-- =====================================================================
-- PENJAGANYA
--
-- Pencocokan dilakukan pada PASANGAN sasaran + indikator yang dinormalkan
-- (spasi dirapatkan, dibandingkan case-insensitive lewat collation), dan
-- HANYA dipakai bila padanannya TEPAT SATU di kedua arah:
--
--   * satu indikator RPJMD tidak boleh cocok ke >1 indikator IKU, dan
--   * satu indikator IKU tidak boleh diklaim >1 indikator RPJMD.
--
-- Yang ambigu sengaja DIBIARKAN kosong. Menebak silsilah lebih buruk
-- daripada tidak punya silsilah: layar cascading akan jatuh ke RPJMD
-- (perilaku hari ini, yang benar), sedangkan tebakan yang salah membuat
-- indikator menampilkan angka milik indikator lain.
--
-- IKU yang TIDAK punya padanan RPJMD juga wajar dan dibiarkan: IKU adalah
-- PILIHAN indikator utama, bukan salinan penuh RPJMD.
-- =====================================================================

-- [SERVER] Penjaga "apakah kolom source_indikator_id ada" DILEPAS.
--
-- Aslinya kolom itu ditanyakan ke `information_schema`, yang tidak boleh
-- dibaca pengguna basis data server (#1044). Pemeriksaannya sudah dilakukan
-- langsung pada dump `backup-29 agustus.sql`: kolomnya ADA, dan 102 dari 109
-- indikator IKU OPD sudah memakainya. Jadi penjaga ini memang tidak
-- diperlukan di sini.
SET @punyaKolom := 1;

-- ---------------------------------------------------------------------
-- 1. PETA PADANAN (hanya yang tunggal di kedua arah)
-- ---------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS _peta_kab;

CREATE TEMPORARY TABLE _peta_kab (
    rpjmd_ind_id  INT UNSIGNED NOT NULL,
    rpjmd_sas_id  INT UNSIGNED NOT NULL,
    iku_ind_id    INT UNSIGNED NOT NULL,
    iku_sas_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (rpjmd_ind_id)
) ENGINE=InnoDB;

SET @sql := IF(@punyaKolom > 0, '
INSERT INTO _peta_kab (rpjmd_ind_id, rpjmd_sas_id, iku_ind_id, iku_sas_id)
SELECT p.rpjmd_ind_id, p.rpjmd_sas_id, p.iku_ind_id, p.iku_sas_id
FROM (
    SELECT ris.id AS rpjmd_ind_id, rs.id AS rpjmd_sas_id,
           ii.id  AS iku_ind_id,   iks.id AS iku_sas_id,
           COUNT(*) OVER (PARTITION BY ris.id) AS n_per_rpjmd,
           COUNT(*) OVER (PARTITION BY ii.id)  AS n_per_iku
    FROM rpjmd_indikator_sasaran ris
    JOIN rpjmd_sasaran rs   ON rs.id = ris.sasaran_id
    JOIN iku_sasaran   iks  ON iks.opd_id IS NULL
                           AND iks.dihentikan_pada IS NULL
                           AND TRIM(REGEXP_REPLACE(iks.sasaran,        ''[[:space:]]+'', '' ''))
                             = TRIM(REGEXP_REPLACE(rs.sasaran_rpjmd,   ''[[:space:]]+'', '' ''))
    JOIN iku_indikator ii   ON ii.iku_sasaran_id = iks.id
                           AND ii.dihentikan_pada IS NULL
                           AND TRIM(REGEXP_REPLACE(ii.indikator,          ''[[:space:]]+'', '' ''))
                             = TRIM(REGEXP_REPLACE(ris.indikator_sasaran, ''[[:space:]]+'', '' ''))
) p
WHERE p.n_per_rpjmd = 1 AND p.n_per_iku = 1', 'DO 0');

PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- 2. TULIS JEJAK — hanya yang masih kosong
-- ---------------------------------------------------------------------
SET @sql := IF(@punyaKolom > 0, '
UPDATE iku_indikator ii
JOIN _peta_kab p ON p.iku_ind_id = ii.id
SET ii.source_type         = COALESCE(ii.source_type, ''rpjmd''),
    ii.source_indikator_id = p.rpjmd_ind_id,
    ii.updated_at          = NOW()
WHERE ii.source_indikator_id IS NULL', 'DO 0');

PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF(@punyaKolom > 0, '
UPDATE iku_sasaran iks
JOIN (SELECT DISTINCT iku_sas_id, rpjmd_sas_id FROM _peta_kab) p
  ON p.iku_sas_id = iks.id
SET iks.source_type       = COALESCE(iks.source_type, ''rpjmd''),
    iks.source_sasaran_id = p.rpjmd_sas_id,
    iks.updated_at        = NOW()
WHERE iks.source_sasaran_id IS NULL', 'DO 0');

PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

DROP TEMPORARY TABLE IF EXISTS _peta_kab;

-- =====================================================================
-- LAPORAN
-- =====================================================================
SELECT '========== SILSILAH IKU KABUPATEN ==========' AS `laporan`;

SELECT
    (SELECT COUNT(*) FROM rpjmd_indikator_sasaran) AS indikator_rpjmd,
    (SELECT COUNT(*) FROM iku_indikator ii
       JOIN iku_sasaran s ON s.id = ii.iku_sasaran_id
      WHERE s.opd_id IS NULL) AS indikator_iku_kab,
    (SELECT COUNT(*) FROM iku_indikator ii
       JOIN iku_sasaran s ON s.id = ii.iku_sasaran_id
      WHERE s.opd_id IS NULL AND ii.source_indikator_id IS NOT NULL) AS sudah_bersilsilah;

SELECT '-- IKU Kabupaten yang BELUM bersilsilah (wajar bila memang bukan dari RPJMD) --' AS `laporan`;

SELECT ii.id, LEFT(ii.indikator, 70) AS indikator
FROM iku_indikator ii
JOIN iku_sasaran s ON s.id = ii.iku_sasaran_id
WHERE s.opd_id IS NULL
  AND ii.source_indikator_id IS NULL
ORDER BY ii.id;

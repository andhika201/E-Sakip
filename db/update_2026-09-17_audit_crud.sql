-- =====================================================================
-- AUDIT CRUD 2026-09-17 — PENGERASAN SKEMA
--
-- Jalankan SEKALI pada basis data server: lewat `mysql < berkas ini`
-- ATAU tab SQL phpMyAdmin dengan basis data yang dituju SUDAH DIPILIH.
-- Aman diulang: tiap langkah memeriksa keadaan sebelumnya.
--
-- MENGAPA SELURUHNYA DI DALAM SATU PROSEDUR
--   phpMyAdmin menjalankan tiap pernyataan terpisah, dan sesudah pernyataan
--   yang menyebut `information_schema.TABLES` ia memindahkan konteks ke
--   information_schema — pernyataan berikutnya lalu gagal dengan
--   "Unknown table 'TARGET_RENCANA' in information_schema". Di dalam
--   prosedur tersimpan, tabel tanpa prefiks dan DATABASE() selalu mengacu
--   ke basis data tempat prosedur dibuat, apa pun konteks pemanggilnya.
--   Laporan dikumpulkan ke tabel sementara dan ditampilkan SEKALI di akhir,
--   karena phpMyAdmin hanya menampilkan result set pertama dari CALL.
--
-- MENGAPA
--   1. target_rencana (pk_indikator_id, opd_id) UNIK.
--      Aplikasi menjaga "satu Rencana Aksi per indikator PK per OPD" hanya
--      lewat cek-lalu-insert (TargetModel::existsForPkIndikator). Dua
--      kiriman bersamaan lolos keduanya. Baris ber-pk_indikator_id NULL
--      (Rencana Aksi berjangkar RPJMD/Renstra) tidak tersentuh — MySQL
--      mengizinkan banyak NULL pada indeks unik.
--
--   2. KOLASI DISERAGAMKAN ke utf8mb4_general_ci.
--      83 tabel general_ci, 11 tabel 0900_ai_ci (roles, permissions,
--      role_permissions, pegawai, rpjmd_visi, rpjmd_cascading, ...), 5 tabel
--      unicode_ci (kegiatan_pk, sub_kegiatan_pk, renstra_tujuan, ...).
--      Setiap perbandingan/JOIN antar kolom teks dari dua kelompok berbeda
--      gagal dengan "Illegal mix of collations" — terbukti pada
--      `users.role = roles.name`. Kolom FK semuanya bertipe angka, jadi
--      konversi ini tidak menyentuh relasi.
--      Tabel arsip (`_backup_*`, `_bak_*`) sengaja dibiarkan.
-- =====================================================================

DROP PROCEDURE IF EXISTS `audit_2026_09_17_crud`;

DELIMITER $$
CREATE PROCEDURE `audit_2026_09_17_crud`()
BEGIN
  DECLARE selesai INT DEFAULT 0;
  DECLARE nama    VARCHAR(128);
  DECLARE ganda   INT DEFAULT 0;
  DECLARE ada     INT DEFAULT 0;
  DECLARE kursor CURSOR FOR
    SELECT TABLE_NAME FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_TYPE = 'BASE TABLE'
       AND TABLE_COLLATION <> 'utf8mb4_general_ci'
       AND TABLE_NAME NOT LIKE '\_%';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET selesai = 1;

  DROP TEMPORARY TABLE IF EXISTS `audit_laporan`;
  CREATE TEMPORARY TABLE `audit_laporan` (
    `no` INT AUTO_INCREMENT PRIMARY KEY,
    `laporan` VARCHAR(500)
  );

  INSERT INTO `audit_laporan` (`laporan`) VALUES (CONCAT('basis data: ', DATABASE()));

  -- ------------------------------------------------------------------
  -- SEBELUM
  -- ------------------------------------------------------------------
  INSERT INTO `audit_laporan` (`laporan`)
  SELECT CONCAT('SEBELUM kolasi ', TABLE_COLLATION, ': ', TABLE_NAME)
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION <> 'utf8mb4_general_ci'
   ORDER BY TABLE_COLLATION, TABLE_NAME;

  SELECT COUNT(*) INTO ganda FROM (
    SELECT pk_indikator_id, opd_id FROM target_rencana
     WHERE pk_indikator_id IS NOT NULL GROUP BY 1, 2 HAVING COUNT(*) > 1) t;
  INSERT INTO `audit_laporan` (`laporan`) VALUES (CONCAT('SEBELUM target_rencana ganda per (pk_indikator_id, opd_id): ', ganda));

  -- ------------------------------------------------------------------
  -- 1. target_rencana: UNIQUE (pk_indikator_id, opd_id)
  --    Bila masih ada baris ganda, langkah ini dilewati dengan laporan —
  --    bereskan dulu datanya.
  -- ------------------------------------------------------------------
  SELECT COUNT(*) INTO ada FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'target_rencana'
     AND INDEX_NAME = 'uq_target_rencana_pk_indikator';

  IF ada > 0 THEN
    INSERT INTO `audit_laporan` (`laporan`) VALUES ('1. uq_target_rencana_pk_indikator sudah ada, dilewati');
  ELSEIF ganda > 0 THEN
    INSERT INTO `audit_laporan` (`laporan`) VALUES ('1. DILEWATI: masih ada baris target_rencana ganda per (pk_indikator_id, opd_id) — bereskan dulu');
  ELSE
    ALTER TABLE `target_rencana`
      ADD UNIQUE KEY `uq_target_rencana_pk_indikator` (`pk_indikator_id`, `opd_id`);
    INSERT INTO `audit_laporan` (`laporan`) VALUES ('1. uq_target_rencana_pk_indikator DITAMBAHKAN');
  END IF;

  -- ------------------------------------------------------------------
  -- 2. Kolasi: semua tabel non-arsip -> utf8mb4_general_ci
  -- ------------------------------------------------------------------
  OPEN kursor;
  baca: LOOP
    FETCH kursor INTO nama;
    IF selesai = 1 THEN LEAVE baca; END IF;
    SET @q := CONCAT('ALTER TABLE `', nama, '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
    INSERT INTO `audit_laporan` (`laporan`) VALUES (CONCAT('2. kolasi diseragamkan: ', nama));
  END LOOP;
  CLOSE kursor;
  SET selesai = 0;

  -- ------------------------------------------------------------------
  -- SESUDAH
  -- ------------------------------------------------------------------
  INSERT INTO `audit_laporan` (`laporan`)
  SELECT CONCAT('SESUDAH masih bukan general_ci (arsip, dibiarkan): ', TABLE_NAME, ' [', TABLE_COLLATION, ']')
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION <> 'utf8mb4_general_ci'
   ORDER BY TABLE_NAME;

  SELECT COUNT(*) INTO ada FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'target_rencana'
     AND INDEX_NAME = 'uq_target_rencana_pk_indikator';
  INSERT INTO `audit_laporan` (`laporan`)
  VALUES (CONCAT('SESUDAH uq_target_rencana_pk_indikator: ', IF(ada > 0, 'ADA', 'TIDAK ADA')));

  SELECT `no`, `laporan` FROM `audit_laporan` ORDER BY `no`;
  DROP TEMPORARY TABLE IF EXISTS `audit_laporan`;
END$$
DELIMITER ;

CALL `audit_2026_09_17_crud`();
DROP PROCEDURE IF EXISTS `audit_2026_09_17_crud`;

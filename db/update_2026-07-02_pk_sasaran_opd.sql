-- =====================================================================
-- Perangkat Daerah pendukung PK Bupati — mapping MANUAL (override otomatis)
-- Tanggal : 2026-07-02
-- Sifat   : IDEMPOTEN (CREATE TABLE IF NOT EXISTS). ADDITIVE.
-- Tujuan  : Simpan pemetaan manual Sasaran PK Bupati -> Perangkat Daerah (OPD)
--           pendukung, yang bisa diedit/ditambah dari halaman Target & Rencana
--           Aksi (kolom Aksi). Bila ada baris utk sebuah pk_sasaran_id, ia
--           MENGGANTIKAN hasil pencocokan otomatis (cascading) di tampilan.
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-02_pk_sasaran_opd.sql
-- =====================================================================
CREATE TABLE IF NOT EXISTS `pk_sasaran_opd` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pk_sasaran_id` INT UNSIGNED NOT NULL,
  `opd_id`        INT UNSIGNED NOT NULL,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pksopd` (`pk_sasaran_id`, `opd_id`),
  KEY `idx_pksopd_sasaran` (`pk_sasaran_id`),
  KEY `idx_pksopd_opd` (`opd_id`),
  CONSTRAINT `fk_pksopd_sasaran` FOREIGN KEY (`pk_sasaran_id`) REFERENCES `pk_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pksopd_opd`     FOREIGN KEY (`opd_id`)        REFERENCES `opd` (`id`)        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- REALISASI ANGGARAN per TRIWULAN pada MONEV
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tabel baru).
--
-- Tujuan  : Halaman MONEV menampilkan Program & Anggaran (ikut Perjanjian
--           Kinerja, sama seperti Target & Rencana Aksi) plus REALISASI
--           ANGGARAN per triwulan yang diinput sendiri.
--
-- Granularitas: PER INDIKATOR — satu baris realisasi untuk satu
--           `target_rencana` (yang memang 1 indikator PK per OPD), bukan per
--           program dan bukan per sub rencana aksi.
--
-- Nilai   : DECIMAL(15,0) mengikuti `program_pk.anggaran` (rupiah tanpa sen),
--           NULL = belum diisi (dibedakan dari 0 = realisasi nol).
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_monev_anggaran.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `monev_anggaran` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_rencana_id`   INT NOT NULL,
  `opd_id`              INT UNSIGNED NULL COMMENT 'NULL = PK Bupati (tingkat kabupaten)',
  `realisasi_triwulan_1` DECIMAL(15,0) NULL,
  `realisasi_triwulan_2` DECIMAL(15,0) NULL,
  `realisasi_triwulan_3` DECIMAL(15,0) NULL,
  `realisasi_triwulan_4` DECIMAL(15,0) NULL,
  `created_at`          DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_monev_anggaran_target` (`target_rencana_id`),
  KEY `idx_monev_anggaran_opd` (`opd_id`),
  CONSTRAINT `fk_monev_anggaran_target` FOREIGN KEY (`target_rencana_id`) REFERENCES `target_rencana` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_monev_anggaran_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

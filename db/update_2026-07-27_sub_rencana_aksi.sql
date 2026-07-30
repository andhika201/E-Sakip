-- =====================================================================
-- SUB RENCANA AKSI pada Target & Rencana Aksi
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak mengubah
--           tabel/kolom lama; `target_rencana.rencana_aksi` tetap apa adanya).
--
-- Tujuan  : Tiap butir Rencana Aksi bisa dirinci lagi jadi beberapa Sub
--           Rencana Aksi (1, 2, 3 ...), seperti pada format Target & Rencana
--           Aksi KemenPAN-RB.
--
-- Catatan bentuk data:
--   `target_rencana.rencana_aksi` menyimpan BEBERAPA butir rencana aksi
--   sebagai teks multi-baris (1 baris = 1 butir). Supaya bentuk itu tidak
--   perlu diubah (dipakai halaman cetak & MONEV), sub rencana aksi ditautkan
--   ke NOMOR BARIS butir tersebut lewat kolom `baris_rencana` (0 = butir ke-1).
--
-- Engine  : InnoDB. FK ke target_rencana ON DELETE CASCADE — sub ikut terhapus
--           kalau rencana aksinya dihapus.
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_sub_rencana_aksi.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `target_sub_rencana` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_rencana_id` INT NOT NULL,
  `baris_rencana`     INT NOT NULL DEFAULT 0 COMMENT 'indeks butir rencana aksi (0 = butir ke-1)',
  `sub_rencana_aksi`  TEXT NOT NULL,
  `urutan`            INT NOT NULL DEFAULT 0,
  `created_at`        DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sub_rencana_target` (`target_rencana_id`, `baris_rencana`, `urutan`),
  CONSTRAINT `fk_sub_rencana_target` FOREIGN KEY (`target_rencana_id`) REFERENCES `target_rencana` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

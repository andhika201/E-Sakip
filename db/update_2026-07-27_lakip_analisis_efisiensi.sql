-- =====================================================================
-- LAKIP: ANALISIS FAKTOR PENCAPAIAN KINERJA + EFISIENSI PROGRAM & ANGGARAN
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tabel baru saja,
--           tabel `lakip` lama TIDAK disentuh sama sekali).
--
-- Tujuan  : Dua tabel tambahan di bawah tabel utama LAKIP.
--
-- ---------------------------------------------------------------------
-- KEPUTUSAN RELASI (penting)
--
-- Satu baris LAKIP menempel ke TARGET, bukan ke indikator:
--   `lakip`.`renstra_target_id`  -> renstra_target (mode OPD / RENSTRA)
--   `lakip`.`rpjmd_target_id`    -> rpjmd_target   (mode Kabupaten / RPJMD)
-- keduanya NULLABLE dan hanya salah satu terisi.
--
-- `lakip_analisis_faktor` MENIRU pola yang sama, BUKAN menunjuk `lakip`.`id`,
-- karena:
--   1. baris target sudah memuat indikator DAN tahun sekaligus, jadi tidak
--      perlu kolom indikator terpisah yang bisa tidak sinkron;
--   2. analisis harus bisa diisi walau baris LAKIP-nya belum dibuat
--      (di halaman, indikator tanpa LAKIP tetap muncul dan tetap bisa
--      diberi analisis).
--
-- `opd_id` disimpan mendatar untuk penyaringan & otorisasi cepat.
--   0 = tingkat kabupaten (RPJMD), sejalan dengan pola `monev`.
--   NOT NULL DEFAULT 0 dipakai supaya UNIQUE benar-benar mengikat —
--   MySQL menganggap dua NULL selalu berbeda.
--
-- `tahun` juga disimpan mendatar (samakan dengan tahun target induknya)
--   supaya filter tahun tidak perlu JOIN ke tabel target.
-- ---------------------------------------------------------------------
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_lakip_analisis_efisiensi.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) ANALISIS FAKTOR PENCAPAIAN KINERJA  (1 indikator : banyak analisis)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lakip_analisis_faktor` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `renstra_target_id` INT UNSIGNED NULL COMMENT 'mode OPD (RENSTRA); salah satu saja yang terisi',
  `rpjmd_target_id`   INT UNSIGNED NULL COMMENT 'mode Kabupaten (RPJMD); salah satu saja yang terisi',
  `opd_id`            INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = tingkat kabupaten (RPJMD)',
  `tahun`             YEAR NOT NULL,
  `faktor_pendukung`  TEXT NULL COMMENT 'faktor pendukung keberhasilan/kegagalan, penurunan/peningkatan kinerja',
  `faktor_penghambat` TEXT NULL,
  `upaya_peningkatan` TEXT NULL,
  `created_by`        INT UNSIGNED NULL,
  `updated_by`        INT UNSIGNED NULL,
  `created_at`        DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analisis_renstra` (`renstra_target_id`, `tahun`),
  KEY `idx_analisis_rpjmd`   (`rpjmd_target_id`, `tahun`),
  KEY `idx_analisis_opd`     (`opd_id`, `tahun`),
  CONSTRAINT `fk_analisis_renstra_target` FOREIGN KEY (`renstra_target_id`)
    REFERENCES `renstra_target` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_analisis_rpjmd_target` FOREIGN KEY (`rpjmd_target_id`)
    REFERENCES `rpjmd_target` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 2) EFISIENSI PROGRAM DAN ANGGARAN  (1 program : 1 baris per tahun per OPD)
--
-- `anggaran` adalah SNAPSHOT dari program_pk.anggaran saat disimpan, supaya
-- laporan tahun berjalan tidak ikut berubah kalau pagu di PK direvisi.
-- Snapshot diperbarui setiap kali baris disimpan/diperbarui lewat form
-- (lihat LakipAddendumTrait::simpanEfisiensi()).
--
-- `realisasi` & `efisiensi` DIINPUT MANUAL — sengaja TIDAK dihitung dari
-- (anggaran - realisasi), sesuai aturan yang diminta.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lakip_efisiensi_program` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` INT UNSIGNED NOT NULL COMMENT '-> program_pk.id',
  `opd_id`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = tingkat kabupaten',
  `tahun`      YEAR NOT NULL,
  `anggaran`   DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT 'snapshot pagu dari program_pk saat disimpan',
  `realisasi`  DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT 'angka murni, tanpa Rp / pemisah ribuan',
  `efisiensi`  DECIMAL(20,2) NOT NULL DEFAULT 0 COMMENT 'diinput manual, bukan hasil hitung sistem',
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_efisiensi_program_tahun_opd` (`program_id`, `tahun`, `opd_id`),
  KEY `idx_efisiensi_opd_tahun` (`opd_id`, `tahun`),
  CONSTRAINT `fk_efisiensi_program` FOREIGN KEY (`program_id`)
    REFERENCES `program_pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- SATUAN BERTIPE PREDIKAT + SKALA NILAI
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE.
--
-- Tujuan  : Indikator yang target/capaiannya berupa PREDIKAT (opini BPK
--           WTP/WDP/TW/TMP, nilai SAKIP AA..D, akreditasi, dsb) tetap bisa
--           dipersentasekan. Tiap predikat diberi SKOR, lalu skornya masuk
--           rumus Capaian Total yang sudah ada
--           (lihat db/update_2026-07-27_monev_metode_perhitungan.sql).
--
-- Perubahan:
--   1. `satuan.tipe` — 'angka' (default) | 'persen' | 'predikat'.
--      Hanya 'predikat' yang mengubah perilaku form: input target & capaian
--      berubah jadi dropdown berisi skala di bawah.
--   2. Tabel `satuan_skala` — daftar predikat per satuan (kode, label, nilai,
--      urutan). Dikelola lewat Master Data > Satuan.
--   3. Seed skala baku untuk satuan yang SUDAH ADA di database ini:
--      "Opini BPK" (WTP/WDP/TW/TMP) & "Nilai Sakip" (AA/A/BB/B/CC/C/D),
--      keduanya mengikuti standar nasional. Hanya diisi kalau satuannya ada
--      DAN skalanya masih kosong, jadi hasil suntingan manual tidak tertimpa.
--
-- Membatalkan sebagian: cukup set `satuan`.`tipe` = 'angka' pada satuan yang
-- tidak ingin memakai dropdown — datanya tetap, formnya kembali input bebas.
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_satuan_predikat.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `satuan_skala` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `satuan_id` INT UNSIGNED NOT NULL,
  `kode`      VARCHAR(50)  NOT NULL COMMENT 'yang ditulis di target/capaian, mis. WTP',
  `label`     VARCHAR(255) NULL     COMMENT 'keterangan panjang, mis. Wajar Tanpa Pengecualian',
  `nilai`     DECIMAL(10,2) NOT NULL COMMENT 'skor untuk perhitungan persentase',
  `urutan`    INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_satuan_skala_kode` (`satuan_id`, `kode`),
  KEY `idx_satuan_skala_urut` (`satuan_id`, `urutan`),
  CONSTRAINT `fk_satuan_skala_satuan` FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP PROCEDURE IF EXISTS _satuan_predikat_upgrade;
DELIMITER $$
CREATE PROCEDURE _satuan_predikat_upgrade()
BEGIN
  DECLARE v_id INT UNSIGNED;

  -- 1) kolom tipe
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'satuan' AND COLUMN_NAME = 'tipe'
  ) THEN
    ALTER TABLE `satuan`
      ADD COLUMN `tipe` VARCHAR(20) NOT NULL DEFAULT 'angka'
      COMMENT 'angka | persen | predikat' AFTER `satuan`;
  END IF;

  -- 2) seed Opini BPK (standar pemeriksaan BPK RI)
  SET v_id = (SELECT `id` FROM `satuan` WHERE LOWER(`satuan`) = 'opini bpk' ORDER BY `id` LIMIT 1);
  IF v_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `satuan_skala` WHERE `satuan_id` = v_id) THEN
    UPDATE `satuan` SET `tipe` = 'predikat' WHERE `id` = v_id;
    INSERT INTO `satuan_skala` (`satuan_id`, `kode`, `label`, `nilai`, `urutan`) VALUES
      (v_id, 'TMP', 'Tidak Menyatakan Pendapat (Disclaimer)', 1, 1),
      (v_id, 'TW',  'Tidak Wajar',                            2, 2),
      (v_id, 'WDP', 'Wajar Dengan Pengecualian',              3, 3),
      (v_id, 'WTP', 'Wajar Tanpa Pengecualian',               4, 4);
  END IF;

  -- 3) seed Nilai SAKIP (PermenPAN-RB)
  SET v_id = (SELECT `id` FROM `satuan` WHERE LOWER(`satuan`) = 'nilai sakip' ORDER BY `id` LIMIT 1);
  IF v_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `satuan_skala` WHERE `satuan_id` = v_id) THEN
    UPDATE `satuan` SET `tipe` = 'predikat' WHERE `id` = v_id;
    INSERT INTO `satuan_skala` (`satuan_id`, `kode`, `label`, `nilai`, `urutan`) VALUES
      (v_id, 'D',  'Sangat Kurang',   1, 1),
      (v_id, 'C',  'Kurang',          2, 2),
      (v_id, 'CC', 'Cukup',           3, 3),
      (v_id, 'B',  'Baik',            4, 4),
      (v_id, 'BB', 'Sangat Baik',     5, 5),
      (v_id, 'A',  'Memuaskan',       6, 6),
      (v_id, 'AA', 'Sangat Memuaskan', 7, 7);
  END IF;
END$$
DELIMITER ;

CALL _satuan_predikat_upgrade();
DROP PROCEDURE IF EXISTS _satuan_predikat_upgrade;

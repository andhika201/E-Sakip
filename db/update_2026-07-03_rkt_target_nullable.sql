-- =====================================================================
-- FIX: rkt_subkegiatan.target -> NULLABLE
-- Tanggal : 2026-07-03
-- Sifat   : IDEMPOTEN (cek IS_NULLABLE dulu) + AMAN (pelebaran constraint).
-- Masalah : Menyimpan RENJA/RKT gagal ("Gagal menyimpan perubahan
--           (transaksi gagal)") bila kolom Target dikosongkan. Penyebab:
--           kolom `target` NOT NULL tanpa default, sedangkan controller
--           RktController::update() menulis `$s['target'] ?? null` = NULL
--           -> MySQL menolak (Error 1048: Column 'target' cannot be null)
--           -> transaksi rollback.
-- Solusi  : Target bersifat OPSIONAL saat menyusun RENJA/RKT, jadi kolom
--           dijadikan NULLABLE. Data lama (berisi '' atau nilai) tetap utuh.
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-03_rkt_target_nullable.sql
-- =====================================================================

SET @needs := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'rkt_subkegiatan'
    AND COLUMN_NAME  = 'target'
    AND IS_NULLABLE  = 'NO'
);
SET @sql := IF(@needs > 0,
  'ALTER TABLE `rkt_subkegiatan` MODIFY `target` VARCHAR(255) NULL DEFAULT NULL',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- Selesai. Kolom rkt_subkegiatan.target kini NULL DEFAULT NULL (opsional).

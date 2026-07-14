-- ============================================================
-- Aktifkan enforcement is_active saat login (2026-07-14)
-- Selama ini kolom users.is_active DIABAIKAN saat login, sehingga
-- SEMUA akun (yang semuanya is_active=0) tetap bisa masuk.
-- Sebelum enforcement diaktifkan di LoginController/TwoFactorController,
-- semua akun yang saat ini masih dipakai di-set aktif (=1) agar tidak
-- ada yang terkunci. Admin baru bisa menonaktifkan akun tertentu setelahnya.
-- Idempoten & aman diulang.
-- ============================================================

UPDATE `users` SET `is_active` = 1 WHERE `is_active` = 0;

-- Default kolom = 1 agar user baru otomatis aktif bila form tidak mengirim nilai
ALTER TABLE `users` MODIFY COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1;

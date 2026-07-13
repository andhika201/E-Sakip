UPDATE `program_pk`
SET `created_at` = NOW()
WHERE CAST(`created_at` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `program_pk`
SET `updated_at` = NOW()
WHERE CAST(`updated_at` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `kegiatan_pk`
SET `created_at` = NOW()
WHERE CAST(`created_at` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `kegiatan_pk`
SET `updated_at` = NOW()
WHERE CAST(`updated_at` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `sub_kegiatan_pk`
SET `created_at` = NOW()
WHERE CAST(`created_at` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `sub_kegiatan_pk`
SET `updated_at` = NOW()
WHERE CAST(`updated_at` AS CHAR) = '0000-00-00 00:00:00';

ALTER TABLE `program_pk`
  MODIFY COLUMN `anggaran` DECIMAL(15,0) NOT NULL DEFAULT 0;

ALTER TABLE `kegiatan_pk`
  MODIFY COLUMN `anggaran` DECIMAL(15,0) NULL DEFAULT 0;

ALTER TABLE `sub_kegiatan_pk`
  MODIFY COLUMN `anggaran` DECIMAL(15,0) NULL DEFAULT 0;

SET @db_name := DATABASE();

UPDATE `jabatan`
SET `created_at` = NOW()
WHERE CAST(`created_at` AS CHAR) = '0000-00-00 00:00:00';

UPDATE `jabatan`
SET `updated_at` = NOW()
WHERE CAST(`updated_at` AS CHAR) = '0000-00-00 00:00:00';

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `jabatan` ADD COLUMN `eselon` VARCHAR(50) NULL AFTER `updated_at`',
        'ALTER TABLE `jabatan` MODIFY COLUMN `eselon` VARCHAR(50) NULL'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'jabatan'
      AND COLUMN_NAME = 'eselon'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @copy_sql := (
    SELECT IF(
        COUNT(*) = 1,
        'UPDATE `jabatan` SET `eselon` = `nama_eselon` WHERE `nama_eselon` IS NOT NULL AND `nama_eselon` <> ''''',
        'SELECT ''Column jabatan.nama_eselon not found; skip copy'' AS info'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'jabatan'
      AND COLUMN_NAME = 'nama_eselon'
);

PREPARE stmt FROM @copy_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Header dokumen LAKIP yang menyimpan sumber acuan satu kali per lingkup.
 *
 * Additive dan sengaja tanpa backfill: baris legacy tetap terbaca melalui
 * jangkar lama sampai lineage-nya dapat dibuktikan secara deterministik.
 */
class CreateLakipDokumenBinding extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('lakip_dokumen')) {
            $this->db->query("CREATE TABLE `lakip_dokumen` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `tahun` INT NOT NULL,
                `mode` VARCHAR(12) NOT NULL COMMENT 'kabupaten | opd',
                `opd_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = kabupaten',
                `source_type` VARCHAR(16) NULL COMMENT 'iku | rpjmd | renstra; NULL = legacy',
                `source_version_id` INT UNSIGNED NULL,
                `source_override_reason` TEXT NULL,
                `status_input` VARCHAR(16) NOT NULL DEFAULT 'draft' COMMENT 'draft | proses | selesai',
                `created_by` INT UNSIGNED NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_lakip_dokumen_lingkup` (`tahun`, `mode`, `opd_id`),
                KEY `idx_lakip_dokumen_source` (`source_type`, `source_version_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if ($this->db->tableExists('lakip') && ! $this->db->fieldExists('lakip_dokumen_id', 'lakip')) {
            $this->db->query('ALTER TABLE `lakip` ADD COLUMN `lakip_dokumen_id` INT UNSIGNED NULL AFTER `lakip_version_id`, ADD KEY `idx_lakip_dokumen` (`lakip_dokumen_id`)');
        }

        if ($this->db->tableExists('lakip_pengesahan') && ! $this->db->fieldExists('source_type', 'lakip_pengesahan')) {
            $this->db->query('ALTER TABLE `lakip_pengesahan` ADD COLUMN `source_type` VARCHAR(16) NULL AFTER `status`, ADD COLUMN `source_version_id` INT UNSIGNED NULL AFTER `source_type`, ADD COLUMN `lakip_dokumen_id` INT UNSIGNED NULL AFTER `source_version_id`, ADD KEY `idx_lakip_pengesahan_source` (`source_type`, `source_version_id`)');
        }
    }

    /**
     * No-op: menurunkan migration tidak boleh menghapus header atau linkage
     * yang mungkin sudah menjadi rekam administratif.
     */
    public function down()
    {
    }
}

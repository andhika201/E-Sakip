<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jenjang baru "Pelaksana" pada Cascading, di bawah Eselon IV / JF.
 *
 * TIDAK ada tabel baru: struktur `cascading_sasaran_opd` +
 * `cascading_indikator_opd` sudah self-similar dan bertingkat sendiri, jadi
 * Pelaksana cukup jadi nilai baru pada enum `level`. Penambatnya sama seperti
 * Eselon IV: kolom `es3_indikator_id` (artinya "indikator induk" — untuk
 * level='es4' berisi id indikator ES III, untuk level='pelaksana' berisi id
 * indikator ES IV; keduanya dibedakan oleh kolom `level`).
 *
 * Kembaran SQL langsungnya: db/update_2026-07-27_cascading_pelaksana.sql
 */
class AddPelaksanaLevelToCascading extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('cascading_sasaran_opd')) {
            return;
        }

        $this->db->query(
            "ALTER TABLE `cascading_sasaran_opd`
             MODIFY COLUMN `level` ENUM('es2','es3','es4','pelaksana') NOT NULL
             COMMENT 'jenjang cascading; pelaksana = di bawah Eselon IV/JF'"
        );

        $index = $this->db->query(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'cascading_sasaran_opd'
               AND INDEX_NAME = 'idx_cascading_level_indikator'"
        )->getRowArray();

        if (!$index) {
            $this->db->query(
                'ALTER TABLE `cascading_sasaran_opd`
                 ADD INDEX `idx_cascading_level_indikator` (`level`, `es3_indikator_id`)'
            );
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('cascading_sasaran_opd')) {
            return;
        }

        // Baris Pelaksana harus dibuang dulu, kalau tidak enum-nya gagal menyempit.
        $this->db->table('cascading_sasaran_opd')->where('level', 'pelaksana')->delete();

        $this->db->query(
            "ALTER TABLE `cascading_sasaran_opd`
             MODIFY COLUMN `level` ENUM('es2','es3','es4') NOT NULL"
        );
    }
}

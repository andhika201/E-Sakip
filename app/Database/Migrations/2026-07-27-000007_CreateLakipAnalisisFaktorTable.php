<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Analisis Faktor Pencapaian Kinerja pada halaman LAKIP.
 *
 * Satu indikator LAKIP boleh punya BANYAK baris analisis (one-to-many).
 * Relasinya meniru tabel `lakip`: menempel ke TARGET (renstra_target /
 * rpjmd_target), bukan ke `lakip`.`id`, karena baris target sudah memuat
 * indikator + tahun sekaligus dan analisis harus tetap bisa diisi walau
 * baris LAKIP-nya belum dibuat.
 *
 * Kembaran SQL langsungnya: db/update_2026-07-27_lakip_analisis_efisiensi.sql
 */
class CreateLakipAnalisisFaktorTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('lakip_analisis_faktor')) {
            return;
        }

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'renstra_target_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'rpjmd_target_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'opd_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'comment' => '0 = tingkat kabupaten (RPJMD)'],
            'tahun'             => ['type' => 'YEAR'],
            'faktor_pendukung'  => ['type' => 'TEXT', 'null' => true],
            'faktor_penghambat' => ['type' => 'TEXT', 'null' => true],
            'upaya_peningkatan' => ['type' => 'TEXT', 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['renstra_target_id', 'tahun'], false, false, 'idx_analisis_renstra');
        $this->forge->addKey(['rpjmd_target_id', 'tahun'], false, false, 'idx_analisis_rpjmd');
        $this->forge->addKey(['opd_id', 'tahun'], false, false, 'idx_analisis_opd');
        $this->forge->addForeignKey('renstra_target_id', 'renstra_target', 'id', 'CASCADE', 'CASCADE', 'fk_analisis_renstra_target');
        $this->forge->addForeignKey('rpjmd_target_id', 'rpjmd_target', 'id', 'CASCADE', 'CASCADE', 'fk_analisis_rpjmd_target');
        $this->forge->createTable('lakip_analisis_faktor', true);
    }

    public function down()
    {
        $this->forge->dropTable('lakip_analisis_faktor', true);
    }
}
